<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function sumo_get_subscription( $subscription ) {
    $subscription = new SUMOSubs_Subscription( $subscription );

    if ( $subscription->exists() ) {
        return $subscription;
    }
    return false;
}

function sumo_get_subscription_product( $subscription ) {
    $product = new SUMOSubs_Product( $subscription );

    if ( $product->exists() && $product->is_subscription() ) {
        return $product;
    }
    return false;
}

/**
 * Retrieve Subscription Plan Details briefly. Provide either $post_id or $product_id.
 * If No $post_id or $product_id is given then it will provide this Checkout Order Subscription Plan.
 *
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @param int $user_id 
 * @return array
 */
function sumo_get_subscription_plan( $post_id = 0, $product_id = 0, $user_id = 0, $calculate_with_qty = true ) {
    $is_synced                               = false;
    $is_trial_enabled                        = false;
    $is_trial_forced                         = false;
    $is_trial_optional                       = false;
    $is_signup_enabled                       = false;
    $is_signup_forced                        = false;
    $is_signup_optional                      = false;
    $is_paid_trial_enabled                   = false;
    $has_additional_digital_dwnld            = false;
    $subscription_fee                        = '';
    $synchronization_duration                = '';
    $synchronization_duration_value          = '';
    $synchronize_start_year                  = '';
    $subscribed_after_sync_date_type         = '';
    $xtra_time_to_charge_full_fee            = '';
    $cutoff_time_to_not_renew_nxt_subs_cycle = '';
    $send_payment_reminder_email             = '';

    $args = SUMOSubs_Subscription_Factory::get_subscription( ( $post_id > 0 ? $post_id : $product_id ), $user_id );

    if ( '1' === $args[ 'susbcription_status' ] ) {
        $is_trial_forced              = '1' === $args[ 'trial_selection' ];
        $is_trial_optional            = '3' === $args[ 'trial_selection' ];
        $is_trial_enabled             = $is_trial_forced || $is_trial_optional;
        $is_paid_trial_enabled        = $is_trial_enabled && 'paid' === $args[ 'fee_type' ] && $args[ 'trialfee' ] > 0;
        $is_signup_forced             = '1' === $args[ 'signusumoee_selection' ] && is_numeric( $args[ 'signup_fee' ] ) && $args[ 'signup_fee' ] >= 0;
        $is_signup_optional           = '3' === $args[ 'signusumoee_selection' ] && is_numeric( $args[ 'signup_fee' ] ) && $args[ 'signup_fee' ] >= 0;
        $is_signup_enabled            = $is_signup_forced || $is_signup_optional;
        $has_additional_digital_dwnld = '1' === $args[ 'additional_digital_downloads_status' ];
        $send_payment_reminder_email  = '' === $args[ 'send_payment_reminder_email' ] ? array(
            'auto'   => 'yes',
            'manual' => 'yes',
                ) : $args[ 'send_payment_reminder_email' ];

        if ( '1' === $args[ 'synchronization_status' ] ) {
            $is_synced                               = true;
            $synchronization_duration_value          = ( in_array( $args[ 'subperiod' ], array( 'M', 'Y' ) ) && $args[ 'synchronization_period_value' ] > 0 ) ? $args[ 'synchronization_period_value' ] : '';
            $subscribed_after_sync_date_type         = $args[ 'subscribed_after_sync_date_type' ];
            $xtra_time_to_charge_full_fee            = $args[ 'xtra_time_to_charge_full_fee' ];
            $cutoff_time_to_not_renew_nxt_subs_cycle = $args[ 'cutoff_time_to_not_renew_nxt_subs_cycle' ];
            $synchronize_start_year                  = $args[ 'synchronize_start_year' ];

            if ( is_numeric( $args[ 'synchronize_mode' ] ) ) {
                $args[ 'synchronize_mode' ] = '2' === $args[ 'synchronize_mode' ] ? 'first-occurrence' : 'exact-date-r-day';
            }

            if ( 'W' === $args[ 'subperiod' ] ) {
                $synchronization_duration = $args[ 'synchronization_period' ] > 0 ? $args[ 'synchronization_period' ] : '';
            } else if ( 'M' === $args[ 'subperiod' ] && 'exact-date-r-day' === $args[ 'synchronize_mode' ] ) {
                $synchronization_duration = ( ( 0 === 12 % $args[ 'subperiodvalue' ] || '24' === $args[ 'subperiodvalue' ] ) && $args[ 'synchronization_period_value' ] > 0 ) ? $args[ 'synchronization_period' ] : '';
            } else if ( 'Y' === $args[ 'subperiod' ] && 'exact-date-r-day' === $args[ 'synchronize_mode' ] ) {
                $synchronization_duration = $args[ 'synchronization_period_value' ] > 0 ? $args[ 'synchronization_period' ] : '';
            }
        }
        if ( is_numeric( $post_id ) && $post_id ) {
            $subscription_fee = sumo_get_recurring_fee( $post_id, array(), 0, $calculate_with_qty );
        } else {
            $subscription_fee = is_numeric( $args[ 'sale_fee' ] ) ? $args[ 'sale_fee' ] : $args[ 'subfee' ];
        }
    }

    $subscription_plan = array(
        'subscription_status'                     => $args[ 'susbcription_status' ],
        'trial_status'                            => $is_trial_forced ? '1' : '2',
        'signup_status'                           => $is_signup_forced ? '1' : '2',
        'synchronization_status'                  => $is_synced ? '1' : '2',
        'additional_digital_downloads_status'     => $has_additional_digital_dwnld ? '1' : '2',
        'subscription_product_id'                 => '1' === $args[ 'susbcription_status' ] ? $args[ 'productid' ] : '',
        'subscription_product_qty'                => '1' === $args[ 'susbcription_status' ] ? $args[ 'product_qty' ] : '',
        'variable_product_id'                     => '1' === $args[ 'susbcription_status' ] ? $args[ 'variation_product_level_id' ] : '',
        'subscription_fee'                        => $subscription_fee,
        'subscription_regular_fee'                => '1' === $args[ 'susbcription_status' ] ? $args[ 'subfee' ] : '',
        'subscription_sale_fee'                   => '1' === $args[ 'susbcription_status' ] ? $args[ 'sale_fee' ] : '',
        'subscription_order_item_fee'             => '1' === $args[ 'susbcription_status' ] ? $args[ 'item_fee' ] : '',
        'trial_fee'                               => $is_paid_trial_enabled ? $args[ 'trialfee' ] : '',
        'signup_fee'                              => $is_signup_enabled ? $args[ 'signup_fee' ] : '',
        'subscription_duration'                   => '1' === $args[ 'susbcription_status' ] ? $args[ 'subperiod' ] : '',
        'subscription_duration_value'             => '1' === $args[ 'susbcription_status' ] ? $args[ 'subperiodvalue' ] : '',
        'is_trial_optional'                       => $is_trial_optional ? true : false,
        'is_signup_optional'                      => $is_signup_optional ? true : false,
        'trial_type'                              => $is_trial_enabled ? ( $is_paid_trial_enabled ? 'paid' : 'free' ) : '',
        'trial_duration'                          => $is_trial_enabled ? $args[ 'trialperiod' ] : '',
        'trial_duration_value'                    => $is_trial_enabled ? $args [ 'trialperiodvalue' ] : '',
        'synchronize_mode'                        => $is_synced ? $args[ 'synchronize_mode' ] : '',
        'synchronization_duration'                => $synchronization_duration,
        'synchronization_duration_value'          => $synchronization_duration_value,
        'synchronize_start_year'                  => $synchronize_start_year,
        'subscribed_after_sync_date_type'         => $subscribed_after_sync_date_type,
        'xtra_time_to_charge_full_fee'            => $xtra_time_to_charge_full_fee,
        'cutoff_time_to_not_renew_nxt_subs_cycle' => $cutoff_time_to_not_renew_nxt_subs_cycle,
        'subscription_recurring'                  => '1' === $args[ 'susbcription_status' ] ? $args[ 'instalment' ] : '',
        'downloadable_products'                   => $has_additional_digital_dwnld ? $args[ 'downloadable_products' ] : '',
        'send_payment_reminder_email'             => $send_payment_reminder_email,
        'subscription_discount'                   => '1' === $args[ 'susbcription_status' ] ? $args[ 'subscription_discount' ] : '',
    );

    /**
     * Get the subscription plan.
     * 
     * @since 1.0
     */
    return apply_filters( 'sumosubscriptions_alter_subscription_plan', $subscription_plan, $post_id, $product_id, $user_id );
}

/**
 * Retrieve Subscription Meta. Provide either $post_id or $product_id. 
 * If No $post_id or $product_id is given then it will provide this Checkout Order Subscription Info.
 *
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @param int $user_id 
 * @return array
 */
function sumo_get_subscription_meta( $post_id = 0, $product_id = 0, $user_id = 0 ) {
    return SUMOSubs_Subscription_Factory::get_subscription( ( $post_id > 0 ? $post_id : $product_id ), $user_id );
}

/**
 * Get the Payment mode for the respective Subscription.
 *
 * @param int $post_id The Subscription post ID
 * @return string
 */
function sumo_get_payment_type( $post_id ) {
    if ( ! is_numeric( $post_id ) || ! $post_id ) {
        return '';
    }

    $subscription_payment_mode = sumo_get_subscription_payment( $post_id, 'payment_type' );
    return in_array( $subscription_payment_mode, array( 'auto', 'automatic' ) ) ? 'auto' : 'manual';
}

/**
 * Get Subscription Type.
 *
 * @param int $post_id The Subscription post ID
 * @param int $user_id
 * @param boolean $sanitize_value
 * @return string
 */
function sumo_get_subscription_type( $post_id = 0, $user_id = 0, $sanitize_value = true ) {
    $subscription_type = 'Product Subscription';

    if ( $post_id ) {
        if ( SUMOSubs_Synchronization::is_subscription_synced( $post_id ) ) {
            $subscription_type = 'Synchronized Subscription';
        } else if ( SUMOSubs_Order_Subscription::is_subscribed( $post_id ) ) {
            $subscription_type = 'Order Subscription';
        }
    } elseif ( SUMOSubs_Order_Subscription::is_subscribed( 0, 0, $user_id ) ) {
        $subscription_type = 'Order Subscription';
    }

    /**
     * Get the subscription type.
     * 
     * @since 1.0
     */
    $subscription_type = apply_filters( 'sumosubscriptions_subscription_type', $subscription_type, $post_id, $user_id );
    return $sanitize_value ? sanitize_title( $subscription_type ) : $subscription_type;
}

/**
 * Retrieve Subscription Payment Information from the Order. The Order will be either Renewal Order or Parent Order
 *
 * @param int $post_id The Subscription post ID
 * @param string either payment_type, payment_method, payment_key, profile_id
 * @return array|string
 */
function sumo_get_subscription_payment( $post_id, $get = '' ) {
    $payment_info = array();
    $parent_order = wc_get_order( get_post_meta( $post_id, 'sumo_get_parent_order_id', true ) );

    if ( $parent_order ) {
        $subscriptions = $parent_order->get_meta( 'sumo_subsc_get_available_postids_from_parent_order', true );
        $payment_data  = $parent_order->get_meta( 'sumosubscription_payment_order_information', true );

        if ( is_array( $payment_data ) && is_array( $subscriptions ) && count( $payment_data ) > 0 ) {
            //Check whether it is Order Subscription.
            if ( SUMOSubs_Order_Subscription::is_subscribed( $post_id ) ) {
                $payment_info = $payment_data;
            } else {
                $subscription_item = 0;

                foreach ( $subscriptions as $subscription_item_id => $subscription_id ) {
                    if ( $subscription_item_id && $subscription_id == $post_id ) {
                        $subscription_item = $subscription_item_id;
                    }
                }

                foreach ( $payment_data as $subscription_product_id => $data ) {
                    if ( ! is_numeric( $subscription_product_id ) ) {
                        continue;
                    }

                    if ( $subscription_product_id == $subscription_item && is_array( $data ) ) {
                        $payment_info = $data;
                    }
                }
            }
        }

        //BKWD CMPT for plugin version < 9.5
        if ( ! empty( $payment_info[ 'payment_method' ] ) ) {
            switch ( $payment_info[ 'payment_method' ] ) {
                case 'sumosubscription_paypal_adaptive':
                    $payment_info[ 'payment_method' ] = 'sumo_paypal_preapproval';
                    break;
                case 'sumosubscription_paypal_reference_transactions':
                    $payment_info[ 'payment_method' ] = 'sumo_paypal_reference_txns';
                    break;
                case 'sumosubscription_stripe_instant':
                    $payment_info[ 'payment_method' ] = 'sumo_stripe';
                    break;
            }
        }
    }

    if ( '' === $get ) {
        return $payment_info;
    }

    return isset( $payment_info[ $get ] ) ? $payment_info[ $get ] : '';
}

/**
 * Retrieve Subscription Payment info from the Order. The Order will be either Renewal Order or Parent Order
 *
 * @param mixed $order The Order 
 * @param string either payment_type, payment_method, payment_key, profile_id
 * @return array|string
 */
function sumo_get_subscription_order_payment( $order, $get = '' ) {
    $order = sumosubs_maybe_get_order_instance( $order );
    if ( ! $order ) {
        return array();
    }

    $parent_order_id = sumosubs_get_parent_order_id( $order );
    $parent_order    = $parent_order_id == $order->get_id() ? $order : wc_get_order( $parent_order_id );
    if ( ! $parent_order ) {
        return array();
    }

    $payment_data = $parent_order->get_meta( 'sumosubscription_payment_order_information', true );
    $payment_data = is_array( $payment_data ) ? $payment_data : array();

    if ( SUMOSubs_Order_Subscription::is_subscribed( 0, $order, $order->get_customer_id() ) ) {
        return isset( $payment_data[ $get ] ) ? $payment_data[ $get ] : '';
    } else {
        if ( sumosubs_is_renewal_order( $order ) ) {
            $subscription_products   = sumo_pluck_subscription_products( $order );
            $subscription_product_id = current( $subscription_products );
        } else {
            $subscription_product_id = 0;
        }

        foreach ( $payment_data as $product_id => $data ) {
            if ( isset( $data[ $get ] ) && ( 0 === $subscription_product_id || $subscription_product_id == $product_id ) ) {
                return $data[ $get ];
            }
        }
    }

    return ( '' === $get ? $payment_data : '' );
}

/**
 * Retrieve Subscription Payment Method from the Order.
 *
 * @param int $post_id The Subscription post ID
 * @return string
 */
function sumo_get_subscription_payment_method( $post_id ) {
    $payment_method = sumo_get_subscription_payment( $post_id, 'payment_method' );
    return $payment_method;
}

/**
 * Get Subscription Fee for the Product.
 *
 * @param int $product_id The Product post ID
 * @return string
 */
function sumo_get_subscription_fee( $product_id ) {
    $_product = wc_get_product( $product_id );
    if ( ! $_product ) {
        return '0';
    }

    $sale_price = $_product->get_sale_price();
    if ( ! empty( $sale_price ) && $sale_price > 0 ) {
        $subscription_fee = $_product->get_sale_price();
    } else {
        $subscription_fee = $_product->get_regular_price();
    }

    return $subscription_fee;
}

/**
 * Get Subscription registered number.
 *
 * @param int $post_id The Subscription post ID
 * @return int
 */
function sumo_get_subscription_number( $post_id ) {
    /**
     * Get Subscription registered number.
     * 
     * @since 15.5.0
     */
    return apply_filters( 'sumosubscriptions_get_subscription_number', sanitize_title( get_post_meta( $post_id, 'sumo_get_subscription_number', true ) ), $post_id );
}

/**
 * Get no. of payments renewed.
 *
 * @param int $post_id The Subscription post ID
 * @return int
 */
function sumosubs_get_renewed_count( $post_id ) {
    return absint( get_post_meta( $post_id, 'sumo_get_renewals_count', true ) );
}

/**
 * Get Subscription date. Format date/time as GMT/UTC
 * If parameters nothing is given then it returns the current date in Y-m-d H:i:s format.
 * 
 * @param int|string $time should be Date/Timestamp.
 * @param int $base_time
 * @param boolean $exclude_hh_mm_ss
 * @param string $format
 * @return string
 */
function sumo_get_subscription_date( $time = 0, $base_time = 0, $exclude_hh_mm_ss = false, $format = 'Y-m-d' ) {
    $timestamp = time();

    if ( is_numeric( $time ) && $time ) {
        $timestamp = $time;
    } else if ( is_string( $time ) && $time ) {
        $timestamp = strtotime( $time );

        if ( is_numeric( $base_time ) && $base_time ) {
            $timestamp = strtotime( $time, $base_time );
        }
    }

    if ( ! $format ) {
        $format = 'Y-m-d';
    }

    if ( $exclude_hh_mm_ss ) {
        return gmdate( "$format", $timestamp );
    }

    return gmdate( "{$format} H:i:s", $timestamp );
}

/**
 * Get Subscription Timestamp. Format date/time as GMT/UTC
 * If parameters nothing is given then it returns the current timestamp.
 * 
 * @param int|string $date should be Date/Timestamp 
 * @param int $base_time
 * @param boolean $exclude_hh_mm_ss
 * @return int
 */
function sumo_get_subscription_timestamp( $date = '', $base_time = 0, $exclude_hh_mm_ss = false ) {
    $formatted_date = sumo_get_subscription_date( $date, $base_time, $exclude_hh_mm_ss );
    return strtotime( "{$formatted_date} UTC" );
}

/**
 * Get Subscription Duration Select box Field options.
 *
 * @param string $subscription_period
 * @param bool $get_as_string
 * @return array
 */
function sumo_get_subscription_duration_options( $subscription_period = '', $get_as_string = true, $min = false, $max = false ) {
    switch ( $subscription_period ) {
        case 'W':
            $max          = ( ! $max || $max > 52 ) ? 52 : $max;
            $week_options = array();
            for ( $j = 1; $j <= $max; $j ++ ) {
                if ( is_numeric( $min ) && $j < $min ) {
                    continue;
                }
                if ( 1 === $j ) {
                    /* translators: %s: week value */
                    $week_options[ $j ] = $get_as_string ? sprintf( __( '%s week', 'sumosubscriptions' ), $j ) : $j;
                } else {
                    /* translators: %s: weeks value */
                    $week_options[ $j ] = $get_as_string ? sprintf( __( '%s weeks', 'sumosubscriptions' ), $j ) : $j;
                }
            }
            $subscription_duration = $week_options;
            break;
        case 'M':
            $max                   = ( ! $max || $max > 24 ) ? 24 : $max;
            $month_options         = array();
            for ( $k = 1; $k <= $max; $k ++ ) {
                if ( is_numeric( $min ) && $k < $min ) {
                    continue;
                }
                if ( 1 === $k ) {
                    /* translators: %s: month value */
                    $month_options[ $k ] = $get_as_string ? sprintf( __( '%s month', 'sumosubscriptions' ), $k ) : $k;
                } else {
                    /* translators: %s: months value */
                    $month_options[ $k ] = $get_as_string ? sprintf( __( '%s months', 'sumosubscriptions' ), $k ) : $k;
                }
            }
            $subscription_duration = $month_options;
            break;
        case 'Y':
            $max                   = ( ! $max || $max > 10 ) ? 10 : $max;
            $year_options          = array();
            for ( $l = 1; $l <= $max; $l ++ ) {
                if ( is_numeric( $min ) && $l < $min ) {
                    continue;
                }
                if ( 1 === $l ) {
                    /* translators: %s: year value */
                    $year_options[ $l ] = $get_as_string ? sprintf( __( '%s year', 'sumosubscriptions' ), $l ) : $l;
                } else {
                    /* translators: %s: years value */
                    $year_options[ $l ] = $get_as_string ? sprintf( __( '%s years', 'sumosubscriptions' ), $l ) : $l;
                }
            }
            $subscription_duration = $year_options;
            break;
        default:
            $max                   = ( ! $max || $max > 90 ) ? 90 : $max;
            $days_options          = array();
            for ( $i = 1; $i <= $max; $i ++ ) {
                if ( is_numeric( $min ) && $i < $min ) {
                    continue;
                }
                if ( 1 === $i ) {
                    /* translators: %s: day value */
                    $days_options[ $i ] = $get_as_string ? sprintf( __( '%s day', 'sumosubscriptions' ), $i ) : $i;
                } else {
                    /* translators: %s: days value */
                    $days_options[ $i ] = $get_as_string ? sprintf( __( '%s days', 'sumosubscriptions' ), $i ) : $i;
                }
            }
            $subscription_duration = $days_options;
            break;
    }

    return $subscription_duration;
}

/**
 * Get Subscription Recurring Select box Field options.
 *
 * @return array
 */
function sumo_get_subscription_recurring_options( $indefinite = 'first', $min = 1, $max = 52 ) {
    $recurring_options = array();
    /**
     * Max recurring option.
     * 
     * @since 15.0.3
     */
    $max               = absint( apply_filters( 'sumosubscriptions_max_recurring_option_value', $max ) );

    if ( 'first' === $indefinite ) {
        $recurring_options[ 0 ] = __( 'Indefinite', 'sumosubscriptions' );
    }

    for ( $i = $min; $i <= $max; $i ++ ) {
        if ( 1 === $i ) {
            /* translators: %s: installment value */
            $recurring_options[ $i ] = sprintf( __( '%s installment', 'sumosubscriptions' ), $i );
        } else {
            /* translators: %s: installments value */
            $recurring_options[ $i ] = sprintf( __( '%s installments', 'sumosubscriptions' ), $i );
        }
    }

    if ( 'last' === $indefinite ) {
        $recurring_options[ 0 ] = __( 'Indefinite', 'sumosubscriptions' );
    }

    return $recurring_options;
}

/**
 * Get available Variations.
 *
 * @param int $product_id The Product post ID
 * @param $perpage
 * @param $page
 * @return array
 */
function sumo_get_available_variations( $product_id, $perpage = -1, $page = 1 ) {
    $variations = get_posts( array(
        'post_type'      => 'product_variation',
        'post_status'    => array( 'private', 'publish' ),
        'posts_per_page' => $perpage,
        'paged'          => $page,
        'fields'         => 'ids',
        'orderby'        => array(
            'menu_order' => 'ASC',
            'ID'         => 'DESC',
        ),
        'post_parent'    => $product_id,
            ) );

    return $variations;
}

/**
 * Get available Subscription Variations.
 *
 * @param int $product_id The Product post ID
 * @return array
 */
function sumo_get_available_subscription_variations( $product_id, $perpage = -1, $page = 1 ) {
    $variations = sumo_get_available_variations( $product_id, $perpage, $page );
    if ( empty( $variations ) ) {
        return array();
    }

    $subscription_variations = array();
    foreach ( $variations as $variation_id ) {
        if ( sumo_is_subscription_product( $variation_id ) ) {
            $subscription_variations[] = $variation_id;
        }
    }

    return $subscription_variations;
}

/**
 * Get Subscription status from Order.
 *
 * @param string $order_status
 * @return string
 */
function sumo_get_subscription_status_from_order_status( $order_status ) {
    $valid_order_statuses = array(
        'pending'    => 'Pending',
        'on-hold'    => 'Pending',
        'completed'  => 'Active',
        'processing' => 'Active',
        'cancelled'  => 'Cancelled',
        'failed'     => 'Failed',
    );

    return isset( $valid_order_statuses[ $order_status ] ) ? $valid_order_statuses[ $order_status ] : '';
}

/**
 * Pluck subscription order item.
 *
 * @param WC_Order $order The Order
 * @param int $subscription_id The Subscription post ID
 * @return array
 */
function sumo_pluck_subscription_order_item( $order, $subscription_id ) {
    $subscription_plan = sumo_get_subscription_plan( $subscription_id );

    $subscription_item = array();
    foreach ( $order->get_items() as $item_id => $item ) {
        if ( ! isset( $item[ 'product_id' ] ) ) {
            continue;
        }

        $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ];
        $product_id = sumosubs_wpml_maybe_get_translated_product_id( $product_id );

        if ( $subscription_plan[ 'subscription_product_id' ] == $product_id ) {
            $subscription_item[ $item_id ] = $item;
        }
    }

    return $subscription_item;
}

/**
 * Pluck order items by the given context.
 *
 * @param mixed $order
 * @param string $context
 * @return array
 */
function sumo_pluck_order_items_by( $order, $context = '' ) {
    $order = sumosubs_maybe_get_order_instance( $order );
    if ( ! $order ) {
        return array();
    }

    $items = array();
    foreach ( $order->get_items() as $item_id => $item ) {
        if ( ! isset( $item[ 'product_id' ] ) ) {
            continue;
        }

        $product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ];
        $product_id = sumosubs_wpml_maybe_get_translated_product_id( $product_id );

        switch ( $context ) {
            case 'name':
                $items[ $product_id ]  = $item[ 'name' ];
                break;
            case 'meta_data':
                /**
                 * Get the hidden order item meta.
                 * 
                 * @since 1.0
                 */
                $hidden_order_itemmeta = apply_filters( 'woocommerce_hidden_order_itemmeta', array(
                    '_qty',
                    '_tax_class',
                    '_product_id',
                    '_variation_id',
                    '_line_subtotal',
                    '_line_subtotal_tax',
                    '_line_total',
                    '_line_tax',
                    'method_id',
                    'cost',
                    '_reduced_stock',
                        ) );

                $item_metadata = array();
                $meta_data     = $order->get_meta_data( $item_id );
                if ( ! empty( $meta_data ) ) {
                    foreach ( $meta_data as $meta ) {
                        if ( ! in_array( $meta->key, $hidden_order_itemmeta, true ) ) {
                            $item_metadata[ $meta->key ] = $meta->value;
                        }
                    }
                }

                $items[ $product_id ] = $item_metadata;
                break;
            default:
                $items[ $item_id ]    = $product_id;
                break;
        }
    }

    return $items;
}

/**
 * Get the Subscription product IDs from Cart/Order.
 *
 * @param mixed $object Cart/Order
 * @return array
 */
function sumo_pluck_subscription_products( $object ) {
    $product_ids = array();

    if ( is_a( $object, 'WC_Cart' ) ) {
        if ( ! empty( WC()->cart->cart_contents ) ) {
            foreach ( WC()->cart->cart_contents as $cart_item ) {
                if ( empty( $cart_item[ 'product_id' ] ) ) {
                    continue;
                }

                $product_id = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ];
                if ( sumo_is_subscription_product( $product_id ) ) {
                    $product_ids[] = $product_id;
                }
            }
        }
    } else {
        $order_items = sumo_pluck_order_items_by( $object );
        if ( empty( $order_items ) ) {
            return array();
        }

        $parent_order_id = sumosubs_get_parent_order_id( $object );
        $parent_order    = wc_get_order( $parent_order_id );
        $subscriptions   = $parent_order ? $parent_order->get_meta( 'sumo_subsc_get_available_postids_from_parent_order', true ) : array();

        if ( ! empty( $subscriptions ) ) {
            foreach ( $subscriptions as $product_id => $subscription_id ) {
                if ( $product_id && $subscription_id && in_array( $product_id, $order_items ) ) {
                    $product_ids[] = $product_id;
                }
            }
        } else {
            foreach ( $order_items as $product_id ) {
                if ( sumo_is_subscription_product( $product_id ) ) {
                    $product_ids[] = $product_id;
                }
            }
        }
    }

    return $product_ids;
}

/**
 * Get Subscription Qty.
 *
 * @param int $post_id The Subscription post ID
 * @return int
 */
function sumo_get_subscription_qty( $post_id ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id );

    //For Order Subscription Qty is 1 by default.
    if ( is_array( $subscription_plan[ 'subscription_product_qty' ] ) ) {
        $product_qty = 1;
    } else {
        $product_qty = $subscription_plan[ 'subscription_product_qty' ];
    }

    return is_numeric( $product_qty ) && $product_qty ? absint( $product_qty ) : 1;
}

/**
 * Get Subscription/Trial cycle.
 *
 * @param string $interval
 * @param bool $calc_for_per_day whether to return based on per day
 * @return int
 */
function sumo_get_subscription_cycle( $interval, $calc_for_per_day = false ) {
    $interval               = explode( ' ', $interval );
    $duration_period        = isset( $interval[ 1 ] ) ? $interval[ 1 ] : 'D';
    $duration_period_length = isset( $interval[ 0 ] ) ? absint( $interval[ 0 ] ) : 1;

    switch ( $duration_period ) {
        case 'Y':
            $cycle = 31556926 * $duration_period_length;
            break;
        case 'M':
            $cycle = 2629743 * $duration_period_length;
            break;
        case 'W':
            $cycle = 604800 * $duration_period_length;
            break;
        default:
            $cycle = 86400 * $duration_period_length;
            break;
    }

    return $calc_for_per_day ? round( $cycle / 86400, 2 ) : $cycle;
}

/**
 * Get subscription duration period selector to display
 *
 * @return array
 */
function sumosubs_get_duration_period_selector() {
    return array(
        'D' => __( 'day(s)', 'sumosubscriptions' ),
        'W' => __( 'week(s)', 'sumosubscriptions' ),
        'M' => __( 'month(s)', 'sumosubscriptions' ),
        'Y' => __( 'year(s)', 'sumosubscriptions' ),
    );
}

/**
 * Get Number Suffix to Display.
 *
 * @param int $number
 * @return string
 */
function sumo_get_number_suffix( $number ) {
    // Special case 'teenth'
    if ( 1 != ( ( $number / 10 ) / 10 ) ) {
        // Handle 1st, 2nd, 3rd
        switch ( $number % 10 ) {
            case 1:
                return $number . 'st';
            case 2:
                return $number . 'nd';
            case 3:
                return $number . 'rd';
        }
    }
    // Everything else is 'nth'
    return $number . 'th';
}

/**
 * Get Subscription URL End Point.
 *
 * @param int $post_id The Subscription post ID
 * @return string
 */
function sumo_get_subscription_endpoint_url( $post_id ) {
    global $post;

    if ( is_admin() && ! wp_doing_ajax() ) {
        $url = "post.php?post=$post_id&action=edit";
    } else {
        if ( sumo_is_my_subscriptions_page() ) {
            $permalink = ! empty( $post->ID ) ? get_permalink( $post->ID ) : get_home_url();
        } else {
            $permalink = wc_get_page_permalink( 'myaccount' );
        }

        $endpoint = sumosubscriptions()->query->get_query_var( 'view-subscription' );

        if ( get_option( 'permalink_structure' ) ) {
            if ( strstr( $permalink, '?' ) ) {
                $query_string = '?' . wp_parse_url( $permalink, PHP_URL_QUERY );
                $permalink    = current( explode( '?', $permalink ) );
            } else {
                $query_string = '';
            }

            $url = trailingslashit( $permalink ) . trailingslashit( $endpoint );
            $url .= trailingslashit( $post_id );
            $url .= $query_string;
        } else {
            $url = add_query_arg( $endpoint, $post_id, $permalink );
        }
    }

    return $url;
}

/**
 * Get SUMO Subscriptions. 
 *
 * @param array $args
 * @return array
 */
function sumo_get_wp_subscriptions( $args = array(), $get_posts = false ) {
    return sumosubscriptions()->query->get( array_merge( array(
                'type'   => 'sumosubscriptions',
                'return' => $get_posts ? 'ids' : 'q',
                            ), $args ) );
}

/**
 * Get Additional Digital Downloadable products from the Subscription.
 *
 * @param int $post_id The Subscription post ID
 * @param int $product_id The Product post ID
 * @return array
 */
function sumo_get_additional_digital_downloadable_products( $post_id = 0, $product_id = 0 ) {
    $subscription_plan     = sumo_get_subscription_plan( $post_id, $product_id );
    $downloadable_products = array();

    if ( '1' !== $subscription_plan[ 'additional_digital_downloads_status' ] ) {
        return array();
    }

    foreach ( $subscription_plan[ 'downloadable_products' ] as $associated_product_id ) {
        $associated_product = wc_get_product( $associated_product_id );

        if ( is_object( $associated_product ) && $associated_product->has_file() ) {
            $downloadable_products[] = $associated_product_id;
        }
    }

    return $downloadable_products;
}

/**
 * Register Subscription ID for the New Subscription Entry.
 *
 * @return int
 */
function sumo_register_subscription_number() {
    $prefix        = esc_attr( SUMOSubs_Admin_Options::get_option( 'subscription_number_prefix' ) );
    $registered_ID = absint( get_option( 'sumo_get_subscription_number' ) );
    $new_ID        = $registered_ID > 0 ? 1 + $registered_ID : 1;

    update_option( 'sumo_get_subscription_number', $new_ID );
    return $prefix . absint( $new_ID );
}

/**
 * Update Reccurring Count for the Subscription. If Renewal Payment made successfully and the subscription becomes Active.
 *
 * @param int $post_id The Subscription post ID
 */
function sumo_update_renewal_count( $post_id ) {
    $renewal_order_logs = get_post_meta( $post_id, 'sumo_get_every_renewal_ids', true );
    $new_renewed_count  = 1;

    if ( ! empty( $renewal_order_logs ) && is_array( $renewal_order_logs ) ) {
        $new_renewed_count = count( $renewal_order_logs );
    }

    update_post_meta( $post_id, 'sumo_get_renewals_count', $new_renewed_count );
}

/**
 * Add Subscription Note.
 *
 * @param string $note
 * @param int $post_id The Subscription post ID
 * @param string $comment_status may be used for updating in Masterlog
 * @param string $comment_action may be used for updating in Masterlog
 * @return int|false The new comment's ID on success, false on failure.
 */
function sumo_add_subscription_note( $note, $post_id, $comment_status, $comment_action = '' ) {
    if ( '' === $note || ! $post_id ) {
        return false;
    }

    if ( is_user_logged_in() && is_admin() ) {
        $user                 = get_user_by( 'id', get_current_user_id() );
        $comment_author_email = $user->user_email;
        $comment_author_name  = $user->display_name;
    } else {
        $comment_author_email = '';
        $comment_author_name  = '';
    }

    $comment_id = wp_insert_comment( array(
        'comment_post_ID'      => $post_id,
        'comment_author'       => $comment_author_name,
        'comment_author_email' => $comment_author_email,
        'comment_author_url'   => '',
        'comment_content'      => $note,
        'comment_type'         => 'subscription_note',
        'comment_agent'        => 'SUMOSubscriptions',
        'comment_parent'       => 0,
        'comment_approved'     => 1,
        'comment_date'         => sumo_get_subscription_date(),
        'comment_meta'         => array(
            'comment_action' => $comment_action,
            'comment_status' => $comment_status,
        ),
            ) );

    $comment = get_comment( $comment_id );
    if ( $comment && apply_filters( 'sumosubscriptions_allow_subscription_log', false ) ) {
        $comment_meta      = get_comment_meta( $comment->comment_ID );
        $subscription_name = get_post_meta( $comment->comment_post_ID, 'sumo_product_name', true );

        if ( 'failure' === $comment_status ) {
            $result = '<div style="background-color: #ef381c;width:60px;height:20px;text-align:center;color:#ffffff;font-weight:bold;padding:5px;">Error</div>';
        } else {
            $result = '<div style="background-color: #259e12;width:60px;height:20px;text-align:center;color:#ffffff;font-weight:bold;padding:5px;">Success</div>';
        }

        //Insert each comment in Masterlog
        $log_id = wp_insert_post( array(
            'post_status' => 'publish',
            'post_type'   => 'sumomasterlog',
                ) );

        $log_data = array(
            'subscription_id'   => $comment->comment_post_ID,
            'subscription_no'   => sumo_get_subscription_number( $comment->comment_post_ID ),
            'subscription_name' => is_array( $subscription_name ) ? implode( ', ', $subscription_name ) : $subscription_name,
            'user_name'         => $comment->comment_author,
            'event'             => $comment->comment_content,
            'orderid'           => get_post_meta( $comment->comment_post_ID, 'sumo_get_parent_order_id', true ),
            'date'              => $comment->comment_date,
            'action'            => isset( $comment_meta[ 'comment_action' ] ) ? implode( $comment_meta[ 'comment_action' ] ) : 'N/A',
            'result'            => $result,
        );

        foreach ( $log_data as $column => $content ) {
            add_post_meta( $log_id, "{$column}", $content );
        }
    }

    return $comment_id;
}

/**
 * Get Subscription Note Status based upon Subscription current status for Note's background color property.
 *
 * @param string $status
 * @return string
 */
function sumo_note_status( $status ) {
    $note_status = array(
        'Active'               => 'success',
        'Pending'              => 'pending',
        'Pending_Cancellation' => 'success',
        'Suspended'            => 'pending',
        'Pause'                => 'pending',
        'Trial'                => 'pending',
        'Overdue'              => 'pending',
        'Cancelled'            => 'success',
        'Expired'              => 'success',
        'Failed'               => 'failure',
    );

    return isset( $note_status[ $status ] ) ? $note_status[ $status ] : 'pending';
}

/**
 * Extract Subscription ID's from Parent Order
 *
 * @param int $parent_order_id
 * @return int
 */
function sumosubs_get_subscriptions_from_parent_order( $parent_order_id ) {
    $parent_order  = wc_get_order( $parent_order_id );
    $subscriptions = $parent_order ? $parent_order->get_meta( 'sumo_subsc_get_available_postids_from_parent_order', true ) : array();

    if ( ! empty( $subscriptions ) ) {
        return array_values( $subscriptions );
    }

    $subscriptions = sumosubscriptions()->query->get( array(
        'type'       => 'sumosubscriptions',
        'status'     => 'publish',
        'meta_key'   => 'sumo_get_parent_order_id',
        'meta_value' => $parent_order_id,
            ) );

    return $subscriptions;
}

/**
 * Extract Subscribed Product ID's from Parent Order
 *
 * @param int $parent_order_id
 * @return int
 */
function sumosubs_get_subscribed_products_from_parent_order( $parent_order_id ) {
    $parent_order  = wc_get_order( $parent_order_id );
    $subscriptions = $parent_order ? $parent_order->get_meta( 'sumo_subsc_get_available_postids_from_parent_order', true ) : array();

    if ( ! empty( $subscriptions ) ) {
        return array_keys( $subscriptions );
    }

    return array();
}

/**
 * Extract Subscription ID from Renewal Order
 *
 * @param int $renewal_order_id
 * @return int
 */
function sumosubs_get_subscription_id_from_renewal_order( $renewal_order_id ) {
    $subscriptions = sumosubscriptions()->query->get( array(
        'type'       => 'sumosubscriptions',
        'status'     => 'publish',
        'limit'      => 1,
        'meta_key'   => 'sumo_get_renewal_id',
        'meta_value' => $renewal_order_id,
            ) );

    return isset( $subscriptions[ 0 ] ) ? $subscriptions[ 0 ] : 0;
}

/**
 * Get Upcoming Mail Timestamp Scheduled by Cron after the Due Date exceeds.
 *
 * @param int $post_id The Subscription post ID
 * @return array
 */
function sumo_get_upcoming_mail_scheduler_after_due_exceeds( $post_id ) {
    $post_id = absint( $post_id );
    $args    = get_post_meta( $post_id, 'sumo_get_args_for_pay_link_templates', true );
    $args    = wp_parse_args( is_array( $args ) ? $args : array(), array(
        'next_status'       => '',
        'scheduled_duedate' => '',
            ) );

    $cron_event = new SUMOSubs_Cron_Event( $post_id );
    if ( $cron_event->exists() ) {
        $cron_events = get_post_meta( $cron_event->event_id, '_sumo_subscription_cron_events', true );
        $timestamp   = 0;

        if ( isset( $cron_events[ $post_id ] ) && is_array( $cron_events[ $post_id ] ) ) {
            foreach ( $cron_events[ $post_id ] as $event_name => $event_args ) {
                if ( ! is_array( $event_args ) ) {
                    continue;
                }

                $timestamps = array_keys( $event_args );
                if ( ! $timestamps ) {
                    continue;
                }

                if ( 'auto' === sumo_get_payment_type( $post_id ) ) {
                    if ( ( 'Suspended' === $args[ 'next_status' ] && 'retry_automatic_pay_in_suspended' === $event_name ) ||
                            'retry_automatic_pay_in_overdue' === $event_name
                    ) {
                        $timestamp = isset( $timestamps[ 0 ] ) ? absint( $timestamps[ 0 ] ) : 0;
                    }
                } elseif ( ( 'Overdue' === $args[ 'next_status' ] && 'notify_overdue' === $event_name ) ||
                        ( 'Suspended' === $args[ 'next_status' ] && 'notify_suspend' === $event_name ) ||
                        'notify_cancel' === $event_name
                ) {

                    $timestamp = isset( $timestamps[ 0 ] ) ? absint( $timestamps[ 0 ] ) : 0;
                }
            }
        }
    }

    return array(
        'upcoming_mail_date'   => $timestamp ? sumo_get_subscription_date( $timestamp ) : '',
        'upcoming_mail_status' => $args[ 'next_status' ],
    );
}

/**
 * Get Next eligible Subscription status when Automatic payment failed to Renew
 *
 * @param int $post_id Subscription post ID
 * @return string
 */
function sumosubs_get_next_eligible_subscription_failed_status( $post_id ) {
    $next_eligible_status = 'Cancelled';

    switch ( get_post_meta( $post_id, 'sumo_get_status', true ) ) {
        case 'Trial':
        case 'Active':
        case 'Pending':
        case 'Pending_Authorization':
            if ( sumosubs_get_overdue_days() > 0 ) {
                $next_eligible_status = 'Overdue';
            } else if ( sumosubs_get_suspend_days() > 0 ) {
                $next_eligible_status = 'Suspended';
            }
            break;
        case 'Overdue':
            if ( sumosubs_get_suspend_days() > 0 ) {
                $next_eligible_status = 'Suspended';
            }
            break;
    }

    /**
     * Get the subscription next eligible failed status.
     * 
     * @since 1.0
     */
    return apply_filters( 'sumosubscriptions_get_next_eligible_subscription_failed_status', $next_eligible_status, $post_id );
}

/**
 * Get Overdue status holding days
 *
 * @return int
 */
function sumosubs_get_overdue_days() {
    return absint( SUMOSubs_Admin_Options::get_option( 'overdue_schedule' ) );
}

/**
 * Get Suspend status holding days
 *
 * @return int
 */
function sumosubs_get_suspend_days() {
    return absint( SUMOSubs_Admin_Options::get_option( 'suspend_schedule' ) );
}

/**
 * Get multiple reminder intervals
 *
 * @param int $post_id Subscription post ID
 * @param string Email template key
 * @return array
 */
function sumosubs_get_reminder_intervals( $post_id, $template_key ) {
    $intervals                 = array();
    $intervals[ 'no-of-days' ] = '1';

    switch ( $template_key ) {
        case 'subscription_invoice':
            $intervals[ 'no-of-days' ] = SUMOSubs_Admin_Options::get_option( 'manual_renewal_due_reminder_schedule' );
            break;
        case 'subscription_auto_renewal_reminder':
            $intervals[ 'no-of-days' ] = SUMOSubs_Admin_Options::get_option( 'auto_renewal_due_reminder_schedule' );
            break;
        case 'subscription_overdue_manual':
        case 'subscription_overdue_automatic':
            $intervals[ 'no-of-days' ] = SUMOSubs_Admin_Options::get_option( 'overdue_reminder_schedule' );
            break;
        case 'subscription_suspended_manual':
        case 'subscription_suspended_automatic':
            $intervals[ 'no-of-days' ] = SUMOSubs_Admin_Options::get_option( 'suspend_reminder_schedule' );
            break;
        case 'subscription_expiry_reminder':
            $intervals[ 'no-of-days' ] = SUMOSubs_Admin_Options::get_option( 'expiration_reminder_schedule' );
            break;
        case 'subscription_pending_authorization':
            $payment_method            = sumo_get_subscription_payment_method( $post_id );

            /**
             * Get pending auth reminder times per day.
             * 
             * @since 1.0
             */
            $intervals[ 'times-per-day' ] = apply_filters( "sumosubscriptions_{$payment_method}_remind_pending_auth_times_per_day", 2, $post_id );
            break;
    }

    if ( isset( $intervals[ 'no-of-days' ] ) ) {
        $intervals[ 'no-of-days' ] = array_map( 'absint', explode( ',', $intervals[ 'no-of-days' ] ) );
    } else {
        $intervals[ 'times-per-day' ] = absint( $intervals[ 'times-per-day' ] );
    }

    return $intervals;
}

/**
 * Get Payment retry times per day when Automatic payment failed to Renew
 *
 * @param string $subscription_status
 * @return int
 */
function sumosubs_get_payment_retry_times_per_day_in( $subscription_status ) {
    switch ( $subscription_status ) {
        case 'Overdue':
            return absint( SUMOSubs_Admin_Options::get_option( 'payment_retries_in_overdue' ) );
        case 'Suspended':
            return absint( SUMOSubs_Admin_Options::get_option( 'payment_retries_in_suspend' ) );
    }

    return 0;
}

/**
 * Get the Subscription Order from Pay for Order page
 *
 * @global object $wp
 * @return int
 */
function sumosubs_get_subscription_order_from_pay_for_order_page() {
    global $wp;

    if ( ! isset( $_GET[ 'pay_for_order' ] ) || ! isset( $_GET[ 'key' ] ) ) {
        return 0;
    }

    $order_id = isset( $wp->query_vars[ 'order-pay' ] ) ? $wp->query_vars[ 'order-pay' ] : 0;
    if ( ! $order_id ) {
        return 0;
    }

    if ( sumo_order_contains_subscription( $order_id ) ) {
        return $order_id;
    }

    return 0;
}

/**
 * Get Subscription Renewal Order from Pay for Order page
 *
 * @global object $wp
 * @return int
 */
function sumosubs_get_subscription_renewal_order_in_pay_for_order() {
    $subscription_order_id = sumosubs_get_subscription_order_from_pay_for_order_page();
    if ( ! $subscription_order_id ) {
        return 0;
    }

    if ( sumosubs_is_renewal_order( $subscription_order_id ) ) {
        return $subscription_order_id;
    }

    return 0;
}

/**
 * Get Subscription Cancel methods eligible for Subscriber
 *
 * @return array
 */
function sumosubs_get_subscription_cancel_methods() {
    $methods                     = array();
    $subscription_cancel_methods = SUMOSubs_Admin_Options::get_option( 'cancel_options_for_subscriber' );

    foreach ( $subscription_cancel_methods as $action_key ) {
        switch ( $action_key ) {
            case 'immediate':
                $methods[ $action_key ] = __( 'Cancel immediately', 'sumosubscriptions' );
                break;
            case 'end_of_billing_cycle':
                $methods[ $action_key ] = __( 'Cancel at the end of billing cycle', 'sumosubscriptions' );
                break;
            case 'scheduled_date':
                $methods[ $action_key ] = __( 'Cancel On Schedule Date', 'sumosubscriptions' );
                break;
        }
    }

    return $methods;
}

/**
 * Revoke Cancel request when the current Subscription status in Pending Cancel
 *
 * @param int $post_id Subscription post ID
 * @param string $note
 * @return bool
 */
function sumosubs_revoke_cancel_request( $post_id, $note ) {
    $next_due_date       = get_post_meta( $post_id, 'sumo_get_next_payment_date', true );
    $saved_due_date      = get_post_meta( $post_id, 'sumo_get_saved_due_date', true );
    $persistent_due_date = '--' === $next_due_date ? $saved_due_date : $next_due_date;
    $parent_order_id     = get_post_meta( $post_id, 'sumo_get_parent_order_id', true );

    delete_post_meta( $post_id, 'sumo_subscription_cancel_requested_by' );
    delete_post_meta( $post_id, 'sumo_subscription_requested_cancel_method' );
    delete_post_meta( $post_id, 'sumo_subscription_cancellation_scheduled_on' );

    /**
     * Should we revoke scheduled cancel request?
     * 
     * @since 1.0
     */
    if ( 'Pending_Cancellation' === get_post_meta( $post_id, 'sumo_get_status', true ) && apply_filters( 'sumosubscriptions_revoke_cancel_request_scheduled', true, $post_id, $parent_order_id ) ) {
        update_post_meta( $post_id, 'sumo_get_status', get_post_meta( $post_id, 'sumo_subscription_previous_status', true ) );
        delete_post_meta( $post_id, 'sumo_get_sub_end_date' );

        $cron_event = new SUMOSubs_Cron_Event( $post_id );
        $cron_event->unset_events( 'notify_cancel' );
        $cron_event->schedule_next_renewal_order( $persistent_due_date );

        sumo_add_subscription_note( $note, $post_id, 'success', __( 'Cancel request revoked', 'sumosubscriptions' ) );
        sumo_trigger_subscription_email( 'subscription_cancel_request_revoked', 0, $post_id );

        /**
         * After subscription scheduled cancel request is revoked.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_scheduled_cancel_request_revoked', $post_id, $parent_order_id );
        return true;
    }

    return false;
}

/**
 * Get WP User roles
 *
 * @global object $wp_roles
 * @param bool $include_guest
 * @return array
 */
function sumosubs_get_user_roles( $include_guest = false ) {
    global $wp_roles;

    $user_roles = array();
    if ( ! empty( $wp_roles->roles ) ) {
        foreach ( $wp_roles->roles as $user_role_key => $user_role ) {
            $user_roles[ $user_role_key ] = $user_role[ 'name' ];
        }
    }

    if ( $include_guest ) {
        $user_roles[ 'guest' ] = 'Guest';
    }

    return $user_roles;
}

/**
 * Return the array of categories for the products.
 *
 * @return array
 */
function sumosubs_get_product_categories() {
    $categories = array();
    $terms      = get_terms( array( 'taxonomy' => 'product_cat', 'orderby' => 'name' ) );

    if ( is_array( $terms ) ) {
        foreach ( $terms as $term ) {
            $categories[ $term->term_id ] = $term->name;
        }
    }

    return $categories;
}

/**
 * Get Active payment gateways
 *
 * @return array
 */
function sumosubs_get_active_payment_gateways() {
    $payment_gateways   = array();
    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

    foreach ( $available_gateways as $key => $value ) {
        $payment_gateways[ $key ] = $value->title;
    }

    return $payment_gateways;
}

/**
 * Get custom background color.
 * This will also handles legacy values.
 *
 * @return array
 */
function sumosubs_get_custom_bgcolor() {
    $custom_bgcolor = get_option( 'sumo_subscription_custom_bgcolor', array() );
    $args           = wp_parse_args( is_array( $custom_bgcolor ) ? $custom_bgcolor : array(), array(
        '_trial'                 => get_option( 'sumo_choose_subsc_status_color_trial', '1800d1' ),
        '_pause'                 => get_option( 'sumo_choose_subsc_status_color_pause', '14a8ad' ),
        '_active'                => get_option( 'sumo_choose_subsc_status_color_active', '008000' ),
        '_overdue'               => get_option( 'sumo_settings_choose_subs_status_color_overdue', '1e1c00' ),
        '_pending'               => get_option( 'sumo_choose_subsc_status_color_pending', '727272' ),
        '_suspended'             => get_option( 'sumo_choose_subsc_status_color_suspend', 'f2cc21' ),
        '_cancelled'             => get_option( 'sumo_choose_subsc_status_color_cancel', 'ef381c' ),
        '_pending_cancel'        => get_option( 'sumo_choose_subsc_status_color_pending_cancellation', 'FF7373' ),
        '_pending_authorization' => get_option( 'sumo_choose_subsc_status_color_pending_authorization', 'F720CC' ),
        '_failed'                => get_option( 'sumo_settings_choose_subs_status_color_failed', '99270B' ),
        '_expired'               => get_option( 'sumo_choose_subsc_status_color_expire', '1cbfed' ),
        'n_processing'           => get_option( 'sumo_choose_subsc_notes_color_processing', 'f79400' ),
        'n_success'              => get_option( 'sumo_choose_subsc_notes_color_success', '259e12' ),
        'n_pending'              => get_option( 'sumo_choose_subsc_notes_color_pending', '727272' ),
        'n_failure'              => get_option( 'sumo_choose_subsc_notes_color_failure', 'ef381c' ),
            ) );

    return $args;
}

/**
 * Get Subscription parent order billing name
 *
 * @param int $post_id
 * @return string
 */
function sumosubs_get_billing_name( $post_id ) {
    $parent_order = wc_get_order( get_post_meta( $post_id, 'sumo_get_parent_order_id', true ) );
    if ( ! $parent_order ) {
        return 'N/A';
    }

    if ( '' === $parent_order->get_billing_first_name() && '' === $parent_order->get_billing_last_name() ) {
        return 'N/A';
    }

    return $parent_order->get_billing_first_name() . ' ' . $parent_order->get_billing_last_name();
}

/**
 * Get UTC offset in frontend
 *
 * @return string
 */
function sumosubs_get_utc_offset_in_frontend() {
    $gmt_offset = floatval( get_option( 'gmt_offset' ) );

    if ( 'default' === SUMOSubs_Admin_Options::get_option( 'show_timezone_in_frontend_as' ) ) {
        $gmt_offset = 0;
    }

    $utc_offset_before_decimal = $gmt_offset % 100;
    $utc_offset_after_decimal  = $gmt_offset * 100 % 100;

    $suffix = '';
    if ( in_array( $utc_offset_after_decimal, array( 75, -75 ) ) ) {
        $suffix = ':45';
    } else if ( in_array( $utc_offset_after_decimal, array( 50, -50 ) ) ) {
        $suffix = ':30';
    }

    if ( false !== strpos( $utc_offset_before_decimal, '-' ) || false !== strpos( $utc_offset_after_decimal, '-' ) ) {
        if ( 0 === $utc_offset_before_decimal ) {
            $utc_offset = "UTC-{$utc_offset_before_decimal}{$suffix}";
        } else {
            $utc_offset = "UTC{$utc_offset_before_decimal}{$suffix}";
        }
    } else {
        $utc_offset = "UTC+{$utc_offset_before_decimal}{$suffix}";
    }

    return $utc_offset;
}

/**
 * Get next possible forthcoming payment dates.
 *
 * @param int $post_id
 * @param int $product_id
 * @param bool $get_as_timestamp
 * @return array
 */
function sumosubs_get_possible_next_payment_dates( $post_id = 0, $product_id = 0, $get_as_timestamp = false ) {
    $subscription_plan = sumo_get_subscription_plan( $post_id, $product_id );

    if ( '1' !== $subscription_plan[ 'subscription_status' ] ) {
        return array();
    }

    $next_payment_dates = array();
    $installment        = absint( $subscription_plan[ 'subscription_recurring' ] );
    $next_payment_time  = sumosubs_get_next_payment_date( $post_id, $product_id, array(
        'initial_payment'     => true,
        'get_as_timestamp'    => true,
        'use_trial_if_exists' => true,
            ) );

    if ( $post_id > 0 ) {
        $subscription_status = get_post_meta( $post_id, 'sumo_get_status', true );
        $next_due_date       = get_post_meta( $post_id, 'sumo_get_next_payment_date', true );
        $renewed_count       = sumosubs_get_renewed_count( $post_id );

        if ( $installment > 0 ) {
            $installment = $renewed_count > $installment ? $renewed_count - $installment : $installment - $renewed_count;
        }
        if ( in_array( $subscription_status, array( 'Active', 'Trial' ) ) && sumo_get_subscription_timestamp( $next_due_date ) >= sumo_get_subscription_timestamp( 0, 0, true ) ) {
            $next_payment_time = sumo_get_subscription_timestamp( $next_due_date );
        }
    }

    $start = ( '1' === $subscription_plan[ 'trial_status' ] || 0 === $installment || SUMOSubs_Synchronization::initial_payment_delayed( $post_id ) ) ? 1 : 2;
    $end   = 0 === $installment ? 10 : $installment;

    for ( $i = $start; $i <= $end; $i ++ ) {
        $next_payment_dates[] = $get_as_timestamp ? $next_payment_time : sumo_get_subscription_date( $next_payment_time );

        $next_payment_time = sumosubs_get_next_payment_date( $post_id, $product_id, array(
            'from_when'           => $next_payment_time,
            'get_as_timestamp'    => true,
            'use_trial_if_exists' => false,
                ) );
    }

    return $next_payment_dates;
}

/**
 * Get an subscription note.
 *
 * @param  int|WP_Comment $comment Note ID.
 * @return stdClass|null  Object with subscription note details or null when does not exists.
 */
function sumosubs_get_subscription_note( $comment ) {
    if ( is_numeric( $comment ) ) {
        $comment = get_comment( $comment );
    }

    if ( ! is_a( $comment, 'WP_Comment' ) ) {
        return null;
    }

    return ( object ) array(
                'id'           => absint( $comment->comment_ID ),
                'date_created' => $comment->comment_date,
                'content'      => $comment->comment_content,
                'added_by'     => $comment->comment_author,
                'meta'         => get_comment_meta( $comment->comment_ID ),
    );
}

/**
 * Get subscription notes.
 *
 * @param  array $args Query arguments
 * @return stdClass[]  Array of stdClass objects with subscription notes details.
 */
function sumosubs_get_subscription_notes( $args = array() ) {
    $key_mapping = array(
        'subscription_id' => 'post_id',
        'limit'           => 'number',
    );

    foreach ( $key_mapping as $query_key => $db_key ) {
        if ( isset( $args[ $query_key ] ) ) {
            $args[ $db_key ] = $args[ $query_key ];
            unset( $args[ $query_key ] );
        }
    }

    $args[ 'orderby' ] = 'comment_ID';
    $args[ 'type' ]    = 'subscription_note';
    $args[ 'status' ]  = 'approve';

    // Does not support 'count' or 'fields'.
    unset( $args[ 'count' ], $args[ 'fields' ] );

    remove_filter( 'comments_clauses', array( 'SUMOSubs_Comments', 'exclude_subscription_comments' ), 10, 1 );

    $notes = get_comments( $args );

    add_filter( 'comments_clauses', array( 'SUMOSubs_Comments', 'exclude_subscription_comments' ), 10, 1 );

    return array_filter( array_map( 'sumosubs_get_subscription_note', $notes ) );
}

/**
 * Get known meta keys either by subscription | subscription parent order | subscription renewal order
 *
 * @return array
 */
function sumosubs_get_meta_keys_by( $type ) {
    switch ( $type ) {
        case 'subscription':
            return array(
                'sumo_get_status',
                'sumo_trial_plan',
                'sumo_subscription_product_details',
                'sumo_product_name',
                'sumo_get_parent_order_id',
                'sumo_buyer_email',
                'sumo_get_user_id',
                'sumo_subscr_plan',
                'sumo_get_renewals_count',
                'sumo_subscription_version',
                'sumo_get_subscription_type',
                'sumo_is_switched',
                'sumo_switched_data',
                'sumo_get_subscriber_data',
                'sumo_subscription_prorated_amount',
                'sumo_subscription_prorated_amount_to_apply_on',
                'sumosubs_subscription_prorated_data',
                'sumo_subscription_is_resubscribed',
                'sumo_resubscribed_plan_associated_subscriptions',
                'sumo_subscription_can_resubscribe',
                'sumo_subscription_awaiting_status',
                'sumo_get_sub_start_date',
                'sumo_get_last_payment_date',
                'sumo_get_next_payment_date',
                'sumo_get_trial_end_date',
                'sumo_get_saved_due_date',
                'sumo_get_sub_end_date',
                'sumo_coupon_in_renewal_order',
                'sumo_coupon_in_renewal_order_applicable_for',
                'sumo_selected_user_roles_for_renewal_order_discount',
                'sumo_selected_user_emails_for_renewal_order_discount',
                'no_of_sumo_selected_renewal_order_discount',
                'sumo_apply_coupon_discount',
                'sumosubs_activate_free_trial_by',
                '_activate_free_trial',
                'sumosubs_activate_subscription_by',
                '_activate_subscription',
                'sumo_get_every_renewal_ids',
                'sumo_subscription_ipn_data',
                'sumo_subscription_inaccessible_time_from_to',
                'sumo_get_updated_renewal_fee',
                'sumo_subscription_cancel_requested_by',
                'sumo_subscription_requested_cancel_method',
                'sumo_subscription_previous_status',
                'sumo_subscription_cancellation_scheduled_on',
                'sumo_subscription_parent_order_item_data',
                'sumo_get_args_for_pay_link_templates',
                'renewal_coupon_count',
                'sumo_get_renewal_id',
                'sumo_subscription_resume_requested_by',
                'sumo_check_trial_status',
                'sumo_subscription_pause_requested_by',
                'sumo_no_of_pause_count',
                'sumo_time_gap_on_paused',
                'sumo_previous_parent_order',
            );
        case 'subscription_parent_order':
            return array(
                'sumo_is_order_based_subscriptions',
                'sumosubs_subscription_prorated_data',
                'sumo_is_subscription_order',
                'sumo_subsc_get_available_postids_from_parent_order',
                'sumosubs_is_switched',
                'sumosubscription_payment_order_information',
                'sumosubscriptions_preapproval_status_from_adaptive_payment',
                'sumosubscriptions_preapproval_charging_status_from_adaptive_payment',
                'sumosubs_adaptive_payment_recurrence',
                'sumosubscription_checkout_transient_data',
                '_sumo_subscription_reference_transaction_id',
            );
        case 'subscription_renewal_order':
            return array(
                'sumo_is_subscription_order',
                'sumo_subscription_id',
                '_referrer_name',
                'sumo_affiliate_id',
                'sumo_get_cart_tax',
                'sumo_get_shipping_tax',
                'sumo_get_total_shipping',
                'sumosubs_adaptive_payment_recurrence',
                'sumosubscription_checkout_transient_data',
            );
    }
    return array();
}

function sumo_get_subscription_statuses() {
    return array(
        'Pending'               => __( 'Pending', 'sumosubscriptions' ),
        'Trial'                 => __( 'Trial', 'sumosubscriptions' ),
        'Active'                => __( 'Active', 'sumosubscriptions' ),
        'Pause'                 => __( 'Pause', 'sumosubscriptions' ),
        'Pending_Authorization' => __( 'Pending Authorization', 'sumosubscriptions' ),
        'Overdue'               => __( 'Overdue', 'sumosubscriptions' ),
        'Suspended'             => __( 'Suspended', 'sumosubscriptions' ),
        'Cancelled'             => __( 'Cancelled', 'sumosubscriptions' ),
        'Pending_Cancellation'  => __( 'Pending Cancellation', 'sumosubscriptions' ),
        'Expired'               => __( 'Expired', 'sumosubscriptions' ),
        'Failed'                => __( 'Failed', 'sumosubscriptions' ),
    );
}

function sumo_get_subscription_status( $subscription_status ) {
    $subscription_statuses = sumo_get_subscription_statuses();
    $subscription_status   = isset( $subscription_statuses[ $subscription_status ] ) ? $subscription_statuses[ $subscription_status ] : '';
    return $subscription_status;
}

function sumo_get_last_renewed_order( $post_id ) {
    $renewal_orders = get_post_meta( $post_id, 'sumo_get_every_renewal_ids', true );

    if ( empty( $renewal_orders ) ) {
        return false;
    }

    rsort( $renewal_orders );

    foreach ( $renewal_orders as $renewal_order_id ) {
        $renewal_order = wc_get_order( $renewal_order_id );

        if ( $renewal_order && $renewal_order->get_transaction_id() ) {
            return $renewal_order;
        }
    }
    return false;
}

function sumosubs_get_subscriptions_by_user( $user_id, $status = '', $limit = -1 ) {
    $user_id = absint( $user_id );

    if ( ! $user_id ) {
        return array();
    }

    if ( empty( $status ) ) {
        $status = array_keys( sumo_get_subscription_statuses() );
    } else {
        $status = ( array ) $status;
    }

    $subscriptions = get_posts( array(
        'post_type'      => 'sumosubscriptions',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'sumo_get_user_id',
                'value'   => $user_id,
                'type'    => 'numeric',
                'compare' => '=',
            ),
            array(
                'key'     => 'sumo_get_status',
                'value'   => $status,
                'compare' => 'IN',
            ),
        ),
            ) );

    return $subscriptions;
}

function sumosubs_get_subscriptions_by_order( $order_id, $status = '' ) {
    $order_id = absint( $order_id );

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return array();
    }

    if ( empty( $status ) ) {
        $status = array_map( 'strtolower', array_keys( sumo_get_subscription_statuses() ) );
    } else {
        $status = ( array ) strtolower( $status );
    }

    $subscriptions = array();
    if ( 0 === $order->get_parent_id() ) {
        $subscriptions = get_posts( array(
            'post_type'      => 'sumosubscriptions',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'sumo_get_parent_order_id',
                    'value'   => $order_id,
                    'type'    => 'numeric',
                    'compare' => '=',
                ),
                array(
                    'key'     => 'sumo_get_status',
                    'value'   => $status,
                    'compare' => 'IN',
                ),
            ),
                ) );
    } else {
        $subscription_id     = absint( $order->get_meta( 'sumo_subscription_id', true ) );
        $subscription_status = strtolower( get_post_meta( $subscription_id, 'sumo_get_status', true ) );

        if ( in_array( $subscription_status, $status ) ) {
            $subscriptions[] = $subscription_id;
        }
    }

    return $subscriptions;
}

function sumosubs_get_subscriptions_by_product( $product_id, $user_id, $status = '', $limit = -1 ) {
    $subscriptions_by_user = sumosubs_get_subscriptions_by_user( $user_id, $status, $limit );

    if ( empty( $subscriptions_by_user ) ) {
        return array();
    }

    $subscriptions = array();
    foreach ( $subscriptions_by_user as $subscription_id ) {
        $subscription_meta = ( array ) get_post_meta( $subscription_id, 'sumo_subscription_product_details', true );

        if ( isset( $subscription_meta[ 'productid' ] ) && $subscription_meta[ 'productid' ] == $product_id ) {
            $subscriptions[] = $subscription_id;
        }
    }

    return $subscriptions;
}

/**
 * Get the Order instance.
 *
 * @param mixed $order
 * @return mixed
 */
function sumosubs_maybe_get_order_instance( $order ) {
    if ( ! $order ) {
        return false;
    }

    if ( is_a( $order, 'WC_Order' ) ) {
        return $order;
    }

    return wc_get_order( $order );
}

/**
 * Get Parent Order ID
 *
 * @param mixed $order
 * @return int
 */
function sumosubs_get_parent_order_id( $order ) {
    $order = sumosubs_maybe_get_order_instance( $order );
    if ( ! $order ) {
        return 0;
    }

    return $order->get_parent_id() > 0 ? $order->get_parent_id() : $order->get_id();
}

/**
 * Set payment transaction ID in order
 *
 * @param mixed $order
 * @param string $transaction_id
 * @param bool $set_in_parent
 */
function sumosubs_save_transaction_id( $order, $transaction_id, $set_in_parent = false ) {
    $order = sumosubs_maybe_get_order_instance( $order );

    if ( $order ) {
        $order->set_transaction_id( $transaction_id );
        $order->save();

        if ( $set_in_parent ) {
            sumosubs_save_transaction_id( sumosubs_get_parent_order_id( $order ), $transaction_id );
        }
    }
}

/**
 * Get subscription cancel immediate url.
 * 
 * @param int $subscription_id
 * @return string
 */
function sumosubs_get_cancel_immediate_url( $subscription_id ) {
    return esc_url_raw( add_query_arg( array( 'sumosubs-cancel-immediate' => absint( $subscription_id ), 'wpnonce' => wp_create_nonce( 'sumosubs-cancel-immediate-handler' ) ), sumo_get_subscription_endpoint_url( $subscription_id ) ) );
}

/**
 * Get current admin page URL.
 *
 * Returns an empty string if it cannot generate a URL.
 *
 * @return string
 */
function sumosubs_get_current_admin_url() {
    if ( function_exists( 'wc_get_current_admin_url' ) ) {
        return wc_get_current_admin_url();
    }

    $uri = isset( $_SERVER[ 'REQUEST_URI' ] ) ? esc_url_raw( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) : '';
    $uri = preg_replace( '|^.*/wp-admin/|i', '', $uri );

    if ( ! $uri ) {
        return '';
    }

    return remove_query_arg( array( '_wpnonce', '_wc_notice_nonce', 'wc_db_update', 'wc_db_update_nonce', 'wc-hide-notice' ), admin_url( $uri ) );
}

/**
 * Cancel subscriptions belonging to the user.
 *
 * @param mixed $id ID of user being deleted.
 */
function sumosubs_cancel_subscriptions_for_user( $id ) {
    if ( ! $id ) {
        return;
    }

    $subscription_ids = sumosubs_get_subscriptions_by_user( $id );
    if ( ! empty( $subscription_ids ) ) {
        foreach ( $subscription_ids as $id ) {
            sumosubs_cancel_subscription( $id, array(
                'note' => __( 'Subscription automatically cancelled.', 'sumosubscriptions' ),
            ) );
        }
    }
}

add_action( 'deleted_user', 'sumosubs_cancel_subscriptions_for_user' );

/**
 * Maybe get the WPML translated product ID.
 * 
 * @param int $product_id
 * @return int
 */
function sumosubs_wpml_maybe_get_translated_product_id( $product_id ) {
    global $sitepress;

    if ( $product_id && is_object( $sitepress ) ) {
        $language_to_filter    = $sitepress->get_user_admin_language( get_current_user_id(), true );
        $translated_product_id = apply_filters( 'translate_object_id', $product_id, get_post_type( $product_id ), false, $language_to_filter );

        if ( ! is_null( $translated_product_id ) ) {
            $product_id = $translated_product_id;
        }
    }

    return $product_id;
}

/**
 * Get subscription ids.
 *
 * @since 15.5.0
 * @param string $status Subscription Status
 * @param int $limit Post per Page
 * @return array
 */
function sumosubs_get_subscription_ids( $status = '', $limit = -1 ) {
    if ( empty( $status ) ) {
        $status = array_keys( sumo_get_subscription_statuses() );
    } else {
        $status = ( array ) $status;
    }

    $subscription_ids = get_posts( array(
        'post_type'      => 'sumosubscriptions',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => 'sumo_get_status',
                'value'   => $status,
                'compare' => 'IN',
            ),
        ),
            ) );

    return $subscription_ids;
}

/**
 * Is subscription product outofstock.
 *
 * @since 15.6.0
 * @param int $subscription_id Subscription ID.
 * @return bool
 */
function sumosubs_is_subscription_product_outofstock( $subscription_id ) {
    if ( ! $subscription_id ) {
        return false;
    }

    $is_outofstock     = false;
    $subscription_plan = sumo_get_subscription_plan( $subscription_id );
    if ( SUMOSubs_Order_Subscription::is_subscribed( $subscription_id ) ) {
        foreach ( $subscription_plan[ 'subscription_product_id' ] as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product && ! $product->is_in_stock() ) {
                $is_outofstock = true;
                break;
            }
        }
    } else {
        $product_id = isset( $subscription_plan[ 'subscription_product_id' ] ) ? absint( $subscription_plan[ 'subscription_product_id' ] ) : 0;
        $product    = wc_get_product( $product_id );

        if (
                ! $product ||
                ! $product->is_in_stock() ||
                ! absint( $product->get_stock_quantity() ) ||
                absint( $subscription_plan[ 'subscription_product_qty' ] ) > $product->get_stock_quantity()
        ) {
            $is_outofstock = true;
        }
    }

    return $is_outofstock;
}
