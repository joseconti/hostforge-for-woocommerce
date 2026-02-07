<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Check it is a Subscription enabled product.
 *
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_is_subscription_product( $product_id ) {
    $subscription_plan = sumo_get_subscription_plan( 0, $product_id );

    return '1' === $subscription_plan[ 'subscription_status' ];
}

/**
 * Check whether Subscription has any Trial.
 *
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_subscription_has_trial( $post_id = 0, $product_id = 0 ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id );

    if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
        return false;
    }

    return '1' === $subscription_plan[ 'trial_status' ];
}

/**
 * Check whether Subscription has Paid Trial.
 *
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_subscription_has_paid_trial( $post_id = 0, $product_id = 0 ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id );

    return 'paid' === $subscription_plan[ 'trial_type' ];
}

/**
 * Check whether Subscription has Free Trial.
 *
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_subscription_has_free_trial( $post_id = 0, $product_id = 0 ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id );

    return 'free' === $subscription_plan[ 'trial_type' ];
}

/**
 * Check whether Subscription has Signup fee.
 *
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_subscription_has_signup( $post_id = 0, $product_id = 0 ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id );

    if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
        return false;
    }

    return '1' === $subscription_plan[ 'signup_status' ];
}

function sumo_can_purchase_subscription( $product_id, $user_id = 0 ) {
    if ( SUMOSubs_Restrictions::is_restriction_available_in_site() && 102 === SUMOSubs_Restrictions::get_subscription_limit_code( $product_id, $user_id ) ) {
        return false;
    }
    return true;
}

function sumo_can_purchase_subscription_trial( $product_id, $user_id = 0 ) {
    if ( SUMOSubs_Restrictions::is_restriction_available_in_site() && 102 === SUMOSubs_Restrictions::get_trial_limit_code( $product_id, $user_id ) ) {
        return false;
    }
    return true;
}

/**
 * Check cart contains Subscription items.
 *
 * @param bool $check_membership_too
 * @return boolean
 */
function sumo_is_cart_contains_subscription_items( $check_membership_too = false ) {
    if ( ! function_exists( 'WC' ) ) {
        return false;
    }

    if ( ! isset( WC()->cart->cart_contents ) || empty( WC()->cart->cart_contents ) ) {
        return false;
    }

    foreach ( WC()->cart->cart_contents as $cart_item ) {
        if ( ! isset( $cart_item[ 'product_id' ] ) ) {
            continue;
        }

        $product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ];

        //Check cart contains Membership Plan accessible products.
        if ( $check_membership_too ) {
            if (
                    ( class_exists( 'SUMOMemberships' ) && function_exists( 'sumo_is_membership_product' ) && sumo_is_membership_product( $product_id ) ) ||
                    sumo_is_subscription_product( $product_id )
            ) {
                //may be Subscription/Membership Plan accessible product.
                return true;
            }
        } else if ( sumo_is_subscription_product( $product_id ) ) {
            //may be Subscription Product.
            return true;
        }
    }
    return false;
}

function sumo_is_my_subscriptions_page() {
    return wc_post_content_has_shortcode( 'sumo_my_subscriptions' );
}

/**
 * Check whether it is Possible to create Next Renewal Order.
 *
 * @param int $post_id The Subscription post ID
 * @return boolean
 */
function sumo_is_next_renewal_possible( $post_id ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id );

    $is_trial_active          = 'Trial' === get_post_meta( $post_id, 'sumo_get_status', true );
    $installment              = absint( $subscription_plan[ 'subscription_recurring' ] );
    $renewed_count            = sumosubs_get_renewed_count( $post_id );
    $is_next_renewal_possible = true;

    if ( $installment > 0 && SUMOSubs_Synchronization::initial_payment_delayed( $post_id ) ) {
        ++ $installment;
    }

    if ( $installment > 0 && ! $is_trial_active && ( ( 1 === $installment && 1 === $renewed_count ) ||
            ( sumo_subscription_has_trial( $post_id ) && ( $installment === $renewed_count ) ) ||
            ( ! sumo_subscription_has_trial( $post_id ) && ( $installment - 1 == $renewed_count ) )
            ) ) {
        $is_next_renewal_possible = false;
    }

    return $is_next_renewal_possible;
}

/**
 * Check if the Order contains Subscription.
 *
 * @param mixed $order
 * @return bool
 */
function sumo_order_contains_subscription( $order ) {
    $order = sumosubs_maybe_get_order_instance( $order );
    if ( ! $order ) {
        return false;
    }

    $parent_order_id = sumosubs_get_parent_order_id( $order );
    $bool            = false;

    if ( SUMOSubs_Order_Subscription::is_subscribed( 0, $parent_order_id, $order->get_customer_id() ) ) {
        $bool = true;
    } else {
        $subscription_products = sumo_pluck_subscription_products( $order );
        $bool                  = ! empty( $subscription_products );
    }

    /**
     * Returns when the order contains subscription. 
     * 
     * @since 1.0
     */
    return apply_filters( 'sumosubscriptions_order_contains_subscription', $bool, $order->get_id(), $parent_order_id );
}

/**
 * Check whether the Addon Amount is applicable in the Parent Order.
 *
 * @param int $post_id The Subscription post ID
 * @return boolean
 */
function sumo_subscription_has_addon_amount( $post_id ) {
    $order_item_data = get_post_meta( $post_id, 'sumo_subscription_parent_order_item_data', true );
    if ( ! is_array( $order_item_data ) ) {
        return false;
    }

    foreach ( $order_item_data as $_item ) {
        if ( isset( $_item[ 'addon' ] ) && is_numeric( $_item[ 'addon' ] ) && $_item[ 'addon' ] > 0 ) {
            return true;
        }
    }
    return false;
}

/**
 * Check whether the Subscription is Published.
 *
 * @param int $post_id The Subscription post ID
 * @return boolean
 */
function sumo_is_subscription_exists( $post_id ) {
    $posted = get_post( $post_id );

    if ( isset( $posted->post_type ) && 'sumosubscriptions' === $posted->post_type ) {
        return 'publish' === $posted->post_status;
    }
    return false;
}

/**
 * Check whether the Subscription has Unpaid renewal order right now
 *
 * @param int $post_id The Subscription post ID
 * @return boolean
 */
function sumosubs_unpaid_renewal_order_exists( $post_id ) {
    $unpaid_renewal_id = get_post_meta( $post_id, 'sumo_get_renewal_id', true );
    if ( empty( $unpaid_renewal_id ) ) {
        return false;
    }

    $renewal_order = wc_get_order( $unpaid_renewal_id );
    if ( ! $renewal_order ) {
        return false;
    }

    if ( sumosubs_is_order_paid( $renewal_order ) ) {
        return false;
    }

    return true;
}

/**
 * Check the currently installed WC version
 *
 * @param string $comparison_opr The possible operators are: <, lt, <=, le, >, gt, >=, ge, ==, =, eq, !=, <>, ne respectively.
  This parameter is case-sensitive, values should be lowercase
 * @param string $version
 * @return boolean
 */
function sumosubs_is_wc_version( $comparison_opr, $version ) {
    return defined( 'WC_VERSION' ) && version_compare( WC_VERSION, $version, $comparison_opr );
}

/**
 * Check product contains subscription variations.
 *
 * @param int $product_id The Product post ID
 * @return boolean
 */
function sumo_is_product_contains_subscription_variations( $product_id ) {
    $subscription_variation = sumo_get_available_subscription_variations( $product_id, 1 );
    return ! empty( $subscription_variation );
}

/**
 * Check whether every Subscription is canceled from the Parent Order.
 *
 * @param int $order_id The Parent Order post ID
 * @return boolean
 */
function sumo_is_every_subscription_cancelled_from_parent_order( $order_id ) {
    $subscriptions = sumosubscriptions()->query->get( array(
        'type'       => 'sumosubscriptions',
        'status'     => 'publish',
        'meta_key'   => 'sumo_get_parent_order_id',
        'meta_value' => sumosubs_get_parent_order_id( $order_id ),
            ) );

    if ( empty( $subscriptions ) ) {
        return;
    }

    $valid_cancelled_statuses = array( 'Cancelled', 'Expired', 'Failed' );
    foreach ( $subscriptions as $subscription_id ) {
        $subscription_status = get_post_meta( $subscription_id, 'sumo_get_status', true );

        if ( ! in_array( $subscription_status, $valid_cancelled_statuses ) ) {
            return false;
        }
    }
    return true;
}

/**
 * Check whether the requested order is the Parent Order
 *
 * @param mixed $order
 * @return boolean
 */
function sumosubs_is_parent_order( $order ) {
    $order = sumosubs_maybe_get_order_instance( $order );
    return $order ? 0 === $order->get_parent_id() : false;
}

/**
 * Check whether the requested order is the Renewal Order
 *
 * @param mixed $order
 * @return boolean
 */
function sumosubs_is_renewal_order( $order ) {
    $order = sumosubs_maybe_get_order_instance( $order );
    return $order ? $order->get_parent_id() > 0 : false;
}

/**
 * Check whether the Subscription is eligible to perform Pause.
 *
 * @param int $subscription_id
 * @return boolean
 */
function sumosubs_is_subscription_eligible_for_pause( $subscription_id ) {
    $status = get_post_meta( $subscription_id, 'sumo_get_status', true );
    if ( 'Pause' === $status ) {
        return true;
    }

    if ( ! in_array( $status, array( 'Active', 'Trial' ) ) ) {
        return false;
    }

    $max_pauses   = absint( SUMOSubs_Admin_Options::get_option( 'max_pause_times_for_subscribers' ) );
    $paused_count = absint( get_post_meta( $subscription_id, 'sumo_no_of_pause_count', true ) );

    if ( ! $max_pauses || ( $paused_count < $max_pauses ) ) {
        $subscriber_id = get_post_meta( $subscription_id, 'sumo_get_user_id', true );

        if ( sumosubs_is_subscriber_eligible_for_some_actions( $subscriber_id, array(
                    'user_wide_action_for' => SUMOSubs_Admin_Options::get_option( 'user_wide_pause_for' ),
                    'user_ids'             => ( array ) SUMOSubs_Admin_Options::get_option( 'user_ids_for_pause' ),
                    'user_roles'           => ( array ) SUMOSubs_Admin_Options::get_option( 'user_roles_for_pause' ),
                ) )
        ) {
            return true;
        }
    }

    return false;
}

/**
 * Check whether the Subscription is eligible to perform Cancel 
 *
 * @param int $subscription_id
 * @return boolean
 */
function sumosubs_is_subscription_eligible_for_cancel( $subscription_id ) {
    if ( in_array( get_post_meta( $subscription_id, 'sumo_get_status', true ), array( 'Cancelled', 'Expired', 'Failed' ) ) ) {
        return false;
    }

    $user_wide_cancellation_for    = SUMOSubs_Admin_Options::get_option( 'user_wide_cancellation_for' );
    $product_wide_cancellation_for = SUMOSubs_Admin_Options::get_option( 'product_wide_cancellation_for' );
    $user_ids                      = ( array ) SUMOSubs_Admin_Options::get_option( 'user_ids_for_cancel' );
    $user_roles                    = ( array ) SUMOSubs_Admin_Options::get_option( 'user_roles_for_cancel' );
    $product_ids                   = ( array ) SUMOSubs_Admin_Options::get_option( 'product_ids_for_cancel' );
    $product_cat_ids               = ( array ) SUMOSubs_Admin_Options::get_option( 'product_cat_ids_for_cancel' );

    $subscriber_id     = get_post_meta( $subscription_id, 'sumo_get_user_id', true );
    $subscription_plan = sumo_get_subscription_plan( $subscription_id );

    if ( SUMOSubs_Order_Subscription::is_subscribed( $subscription_id ) && sumosubs_is_subscriber_eligible_for_some_actions( $subscriber_id, array(
                'user_wide_action_for' => $user_wide_cancellation_for,
                'user_ids'             => $user_ids,
                'user_roles'           => $user_roles,
            ) )
    ) {
        return true;
    } else {
        if ( ! sumosubs_is_subscriber_eligible_for_some_actions( $subscriber_id, array(
                    'user_wide_action_for' => $user_wide_cancellation_for,
                    'user_ids'             => $user_ids,
                    'user_roles'           => $user_roles,
                ) )
        ) {
            return false;
        }

        if ( is_numeric( $subscription_plan[ 'variable_product_id' ] ) && $subscription_plan[ 'variable_product_id' ] ) {
            if (
                    sumosubs_is_product_eligible_for_some_actions( $subscription_plan[ 'variable_product_id' ], array(
                        'product_wide_action_for' => $product_wide_cancellation_for,
                        'product_ids'             => $product_ids,
                        'product_cat_ids'         => $product_cat_ids,
                    ) ) ||
                    sumosubs_is_product_eligible_for_some_actions( $subscription_plan[ 'subscription_product_id' ], array(
                        'product_wide_action_for' => $product_wide_cancellation_for,
                        'product_ids'             => $product_ids,
                        'product_cat_ids'         => $product_cat_ids,
                    ) )
            ) {
                return true;
            }
        } else if ( sumosubs_is_product_eligible_for_some_actions( $subscription_plan[ 'subscription_product_id' ], array(
                    'product_wide_action_for' => $product_wide_cancellation_for,
                    'product_ids'             => $product_ids,
                    'product_cat_ids'         => $product_cat_ids,
                ) )
        ) {
            return true;
        }
    }

    return false;
}

/**
 * Find the Subscriber limit set by Admin and check whether the User is eligible to perform some actions 
 *
 * @param object $user
 * @param array $args
 * @return boolean
 */
function sumosubs_is_subscriber_eligible_for_some_actions( $user, $args = array() ) {
    $args = wp_parse_args( $args, array(
        'user_wide_action_for' => '',
        'user_ids'             => array(),
        'user_roles'           => array(),
            ) );

    if ( is_numeric( $user ) && $user ) {
        $user_id = $user;
    } else if ( isset( $user->ID ) ) {
        $user_id = $user->ID;
    } else {
        $user_id = 0;
    }

    $user = get_user_by( 'id', $user_id );
    if ( ! $user ) {
        return false;
    }

    switch ( $args[ 'user_wide_action_for' ] ) {
        case 'allowed-user-ids':
            return in_array( $user->ID, ( array ) $args[ 'user_ids' ] ) ? true : false;
        case 'restricted-user-ids':
            return in_array( $user->ID, ( array ) $args[ 'user_ids' ] ) ? false : true;
        case 'allowed-user-roles':
            return count( array_intersect( $user->roles, ( array ) $args[ 'user_roles' ] ) ) > 0 ? true : false;
        case 'restricted-user-roles':
            return count( array_intersect( $user->roles, ( array ) $args[ 'user_roles' ] ) ) > 0 ? false : true;
    }

    return true;
}

/**
 * Find the Subscription Product/Category limit set by Admin and check whether the Product/Category is eligible to perform some actions 
 *
 * @param int $product_id
 * @param array $args
 * @return boolean
 */
function sumosubs_is_product_eligible_for_some_actions( $product_id, $args = array() ) {
    $args = wp_parse_args( $args, array(
        'product_wide_action_for' => '',
        'product_ids'             => array(),
        'product_cat_ids'         => array(),
            ) );

    switch ( $args[ 'product_wide_action_for' ] ) {
        case 'allowed-product-ids':
            return in_array( $product_id, ( array ) $args[ 'product_ids' ] ) ? true : false;
        case 'restricted-product-ids':
            return in_array( $product_id, ( array ) $args[ 'product_ids' ] ) ? false : true;
        case 'allowed-product-cat-ids':
            $product_cats = wc_get_product_cat_ids( $product_id );

            return count( array_intersect( $product_cats, $args[ 'product_cat_ids' ] ) ) > 0 ? true : false;
        case 'restricted-product-cat-ids':
            $product_cats = wc_get_product_cat_ids( $product_id );

            return count( array_intersect( $product_cats, $args[ 'product_cat_ids' ] ) ) > 0 ? false : true;
    }

    return true;
}

/**
 * Check whether the current viewing post as SUMOSubscriptions post type
 *
 * @return boolean
 */
function is_sumosubscriptions_post_type() {
    $request = $_REQUEST;

    if ( isset( $request[ 'action' ] ) && 'edit' === $request[ 'action' ] ) {
        return false;
    }

    if ( isset( $request[ 'page' ] ) ) {
        return false;
    }

    if ( 'sumosubscriptions' === get_post_type() ) {
        return true;
    } else if ( isset( $request[ 'post_type' ] ) && 'sumosubscriptions' === $request[ 'post_type' ] ) {
        return true;
    }

    return false;
}

/**
 * Check whether the user can purchase as Subscription product
 *
 * @param int $subscription_product_id
 * @param int $customer_id
 * @return boolean
 */
function sumo_can_user_purchase_as_subscription( $subscription_product_id, $customer_id = 0 ) {
    $defined_rules = ( array ) SUMOSubs_Admin_Options::get_option( 'subscription_as_regular_product_defined_rules' );
    $user          = get_user_by( 'id', $customer_id );

    if ( $user ) {
        $userroles = $user->roles;
    } else {
        $userroles = empty( wp_get_current_user()->roles ) ? array( 'guest' ) : wp_get_current_user()->roles;
    }

    foreach ( $defined_rules as $rule ) {
        if ( ! isset( $rule[ 'selected_subscription' ] ) || ! is_array( $rule[ 'selected_subscription' ] ) ) {
            continue;
        }

        if ( in_array( $subscription_product_id, $rule[ 'selected_subscription' ] ) ) {
            if ( ! isset( $rule[ 'selected_userrole' ] ) || ! is_array( $rule[ 'selected_userrole' ] ) ) {
                continue;
            }

            foreach ( $userroles as $role ) {
                if ( in_array( $role, $rule[ 'selected_userrole' ] ) ) {
                    return false;
                }
            }
        }
    }

    return true;
}

/**
 * Check whether the subscription with pending status is awaiting Admin approval to activate the Free trial
 *
 * @param int $subscription_id
 * @param mixed $parent_order
 * @return boolean
 */
function sumosubs_free_trial_awaiting_admin_approval( $subscription_id, $parent_order = false ) {
    $subscription_status = get_post_meta( $subscription_id, 'sumo_get_status', true );
    $parent_order        = $parent_order ? $parent_order : get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );

    if ( 'Pending' === $subscription_status && sumosubs_is_order_paid( $parent_order ) ) {
        $awaiting_status     = get_post_meta( $subscription_id, 'sumo_subscription_awaiting_status', true );
        $activate_free_trial = metadata_exists( 'post', $subscription_id, '_activate_free_trial' ) ? get_post_meta( $subscription_id, '_activate_free_trial', true ) : get_post_meta( $subscription_id, 'sumosubs_activate_free_trial_by', true );

        if ( 'free-trial' === $awaiting_status && in_array( $activate_free_trial, array( 'after-admin-approval', 'admin_approval' ) ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Check whether the subscription with pending status is awaiting Admin approval to activate the Subscription.
 * 
 * @param int $subscription_id
 * @param mixed $parent_order
 * @return boolean
 */
function sumo_subscription_awaiting_admin_approval( $subscription_id, $parent_order = false ) {
    $subscription_status = get_post_meta( $subscription_id, 'sumo_get_status', true );
    $parent_order        = $parent_order ? $parent_order : get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );

    if ( 'Pending' === $subscription_status && sumosubs_is_order_paid( $parent_order ) && ! SUMOSubs_Synchronization::is_subscription_synced( $subscription_id ) ) {
        $awaiting_status = get_post_meta( $subscription_id, 'sumo_subscription_awaiting_status', true );
        $activate        = metadata_exists( 'post', $subscription_id, '_activate_subscription' ) ? get_post_meta( $subscription_id, '_activate_subscription', true ) : get_post_meta( $subscription_id, 'sumosubs_activate_subscription_by', true );

        if ( in_array( $awaiting_status, array( 'Pending', 'Active' ) ) && in_array( $activate, array( 'after-admin-approval', 'admin_approval' ) ) ) {
            return true;
        }
    }

    return false;
}

function sumosubs_recurring_fee_has_changed( $subscription_id ) {
    return is_numeric( get_post_meta( $subscription_id, 'sumo_get_updated_renewal_fee', true ) );
}

function sumo_subscription_is_switching( $product_id ) {
    if (
            SUMOSubs_Switcher::is_switcher_page() ||
            SUMOSubs_Switcher::is_subscription_switched( $product_id ) ||
            ( doing_action( 'wp_loaded' ) && 'woocommerce_is_purchasable' === current_filter() && get_transient( 'sumo_subscription_switching_into_cart' ) )//Should be useful when cart session is loaded in wp_loaded hook
    ) {
        return true;
    }
    return false;
}

function sumosubs_is_valid_date( $date, $format = 'Y-m-d H:i:s' ) {
    $d = DateTime::createFromFormat( $format, $date );
    return $d && $date === $d->format( $format ) ? true : false;
}

function sumosubs_user_contains_subscription( $user_id, $status = '' ) {
    $subscription = sumosubs_get_subscriptions_by_user( $user_id, $status, 1 );
    return ! empty( $subscription );
}

function sumosubs_is_order_paid( $order ) {
    $order = sumosubs_maybe_get_order_instance( $order );
    if ( ! $order ) {
        return false;
    }

    return $order->has_status( array( 'completed', 'processing' ) ) || 'yes' === $order->get_meta( 'sumosubs_order_paid', true );
}

/**
 * Check whether the given product type is supported for Subscriptions.
 * 
 * @param string $type
 * @return bool
 */
function sumosubs_is_subscription_product_type( $type ) {
    /**
     * Get the valid subscription product types.
     * 
     * @since 1.0
     */
    $valid_product_types = apply_filters( 'sumosubscriptions_valid_subscription_product_types', array( 'simple', 'variation', 'grouped' ) );
    return in_array( $type, ( array ) $valid_product_types );
}

/**
 * Determines if the request is a non-legacy REST API request.
 *
 * This function is a compatibility wrapper for WC()->is_rest_api_request() which was introduced in WC 3.6.
 *
 * @return bool True if it's a REST API request, false otherwise.
 */
function sumosubs_is_rest_api_request() {
    if ( function_exists( 'WC' ) && is_callable( array( WC(), 'is_rest_api_request' ) ) ) {
        return WC()->is_rest_api_request();
    }

    if ( empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
        return false;
    }

    $rest_prefix         = trailingslashit( rest_get_url_prefix() );
    $is_rest_api_request = ( false !== strpos( $_SERVER[ 'REQUEST_URI' ], $rest_prefix ) );

    return apply_filters( 'woocommerce_is_rest_api_request', $is_rest_api_request );
}

/**
 * Determines if the current request is to any or a specific Checkout blocks REST API endpoint.
 *
 * @see Automattic\WooCommerce\Blocks\StoreApi\RoutesController::initialize() for a list of routes.
 *
 * @param string $endpoint The checkout/checkout blocks endpoint. Optional. Can be empty (any checkout blocks API) or a specific endpoint ('checkout', 'cart', 'products' etc)
 * @return bool Whether the current request is for a cart/checkout blocks REST API endpoint.
 */
function sumosubs_is_checkout_blocks_api_request( $endpoint = '' ) {
    if ( ! sumosubs_is_rest_api_request() || empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
        return false;
    }

    $endpoint    = empty( $endpoint ) ? '' : '/' . $endpoint;
    $rest_prefix = trailingslashit( rest_get_url_prefix() );
    $request_uri = esc_url_raw( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) );

    return false !== strpos( $request_uri, $rest_prefix . 'wc/store' . $endpoint );
}
