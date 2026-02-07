<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle subscription frontend part.
 * 
 * @class SUMOSubs_Frontend
 */
class SUMOSubs_Frontend {

    /**
     * Get the subscription. 
     * 
     * @var object 
     */
    public static $subscription = false;

    /**
     * Get the subscription product types. 
     * 
     * @var array 
     */
    public static $subscription_product_types = array( 'simple', 'variation', 'grouped' );

    /**
     * Get the subscription object type. 
     * 
     * @var string 
     */
    public static $subscription_obj_type = 'product';

    /**
     * Variation data to display 
     * 
     * @var array 
     */
    protected static $variation_data = array();

    /**
     * Init SUMOSubs_Frontend
     */
    public static function init() {
        add_filter( 'woocommerce_product_add_to_cart_text', __CLASS__ . '::alter_add_to_cart_label', 999, 2 );
        add_filter( 'woocommerce_product_single_add_to_cart_text', __CLASS__ . '::alter_add_to_cart_label', 999, 2 );
        add_filter( 'sumosubscriptions_get_single_variation_data_to_display', __CLASS__ . '::alter_add_to_cart_label', 10, 2 );
        add_filter( 'woocommerce_get_price_html', __CLASS__ . '::get_product_data_to_display', 0, 2 );
        add_filter( 'woocommerce_cart_item_price', __CLASS__ . '::get_subscription_message_in_cart_r_checkout', 10, 3 );
        add_filter( 'woocommerce_checkout_cart_item_quantity', __CLASS__ . '::get_subscription_message_in_cart_r_checkout', 10, 3 );
        add_filter( 'woocommerce_get_item_data', __CLASS__ . '::display_cart_item_data', 10, 2 );

        add_action( 'wp_enqueue_scripts', __CLASS__ . '::add_custom_style' );

        //Force Signup if Guests placing the Subscription Order.
        add_action( 'woocommerce_before_checkout_form', __CLASS__ . '::force_enable_guest_signup_on_checkout', 10, 1 );
        add_action( 'woocommerce_checkout_process', __CLASS__ . '::force_create_account_for_guest' );

        //Manage Subscription Cart/Checkout Total.
        add_filter( 'woocommerce_product_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 );
        add_filter( 'woocommerce_product_variation_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 );
        add_filter( 'woocommerce_product_get_regular_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 );
        add_filter( 'woocommerce_product_variation_get_regular_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 );
        add_filter( 'woocommerce_calculated_total', __CLASS__ . '::alter_cart_total' );
        add_filter( 'woocommerce_cart_total', __CLASS__ . '::alter_cart_total' );

        include_once 'class-sumosubs-variation-data.php';
        include_once 'class-sumosubs-optional-trial-or-signup.php';
        include_once 'class-sumosubs-my-account.php';
    }

    /**
     * Add to cart Label Customization.
     *
     * @param string|array $data
     * @param mixed $product
     * @return string
     */
    public static function alter_add_to_cart_label( $data, $product ) {
        $maybe_subscription = new SUMOSubs_Product( $product );

        if ( $maybe_subscription->exists() && sumosubs_is_subscription_product_type( $maybe_subscription->get_type() ) ) {
            switch ( $maybe_subscription->get_type() ) {
                case 'variation':
                    if ( 'sumosubscriptions_get_single_variation_data_to_display' === current_filter() ) {
                        if ( $maybe_subscription->is_subscription() ) {
                            $data[ 'sumosubs_add_to_cart_label' ] = SUMOSubs_Admin_Options::get_option( 'subscribe_text' );
                        } else {
                            $data[ 'sumosubs_add_to_cart_label' ] = $maybe_subscription->product->single_add_to_cart_text();
                        }
                    }
                    break;
                case 'grouped':
                    foreach ( $maybe_subscription->product->get_children() as $child_id ) {
                        $child = new SUMOSubs_Product( $child_id );

                        if ( $child->exists() && $child->product->is_in_stock() && $child->is_subscription() ) {
                            return SUMOSubs_Admin_Options::get_option( 'subscribe_text' );
                        }
                    }
                    break;
                default:
                    if ( $maybe_subscription->product->is_in_stock() && $maybe_subscription->is_subscription() ) {
                        if ( sumo_can_purchase_subscription( $maybe_subscription->get_id() ) ) {
                            return SUMOSubs_Admin_Options::get_option( 'subscribe_text' );
                        }

                        return __( 'Read More', 'sumosubscriptions' );
                    }
                    break;
            }
        }

        return $data;
    }

    /**
     * Get subscription data product level.
     * 
     * @param string $price
     * @param WC_Product $product
     * @return string
     */
    public static function get_product_data_to_display( $price, $product ) {
        $maybe_subscription = new SUMOSubs_Product( $product );

        if ( $maybe_subscription->exists() ) {
            switch ( $maybe_subscription->get_type() ) {
                case 'variable':
                    if ( ! in_array( SUMOSubs_Admin_Options::get_option( 'variable_product_price_display_as' ), array( 'subscription-message', 'non-subscription-message' ) ) ) {
                        break;
                    }

                    $variations = $maybe_subscription->product->get_children();
                    if ( $variations ) {
                        remove_filter( 'woocommerce_product_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 );
                        remove_filter( 'woocommerce_product_variation_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 );

                        $subscription_variations     = array();
                        $non_subscription_variations = array();
                        foreach ( $variations as $variation_id ) {
                            $variation = new SUMOSubs_Product( $variation_id );

                            if ( ! $variation->exists() ) {
                                continue;
                            }

                            $variation_price = $variation->get_price();
                            if ( $variation->is_subscription() ) {
                                $subscription_variations[ $variation_price ] = array(
                                    'id'    => $variation_id,
                                    'price' => $variation_price,
                                );
                            } else {
                                $non_subscription_variations[ $variation_price ] = array(
                                    'id'            => $variation_id,
                                    'price'         => $variation_price,
                                    'regular_price' => $variation->product->get_regular_price(),
                                    'is_on_sale'    => $variation->product->is_on_sale(),
                                );
                            }
                        }

                        add_filter( 'woocommerce_product_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 );
                        add_filter( 'woocommerce_product_variation_get_price', __CLASS__ . '::set_cart_item_line_total', 99, 2 );

                        if ( 'non-subscription-message' === SUMOSubs_Admin_Options::get_option( 'variable_product_price_display_as' ) ) {
                            if ( empty( $non_subscription_variations ) ) {
                                return $price;
                            }

                            if ( 1 === count( $non_subscription_variations ) ) {
                                $variation_data = current( $non_subscription_variations );
                                if ( $variation_data[ 'is_on_sale' ] ) {
                                    return wc_format_sale_price( $variation_data[ 'regular_price' ], $variation_data[ 'price' ] );
                                }

                                return wc_price( $variation_data[ 'price' ] );
                            } else {
                                ksort( $non_subscription_variations, SORT_NUMERIC );
                                $min = current( $non_subscription_variations );
                                $max = end( $non_subscription_variations );
                                return wc_format_price_range( wc_price( $min[ 'price' ] ), wc_price( $max[ 'price' ] ) );
                            }
                        } else {
                            if ( empty( $subscription_variations ) ) {
                                return $price;
                            }

                            ksort( $subscription_variations, SORT_NUMERIC );
                            $variation_data = current( $subscription_variations );
                            return sumo_display_subscription_plan( 0, $variation_data[ 'id' ], 0, count( $subscription_variations ) > 1 );
                        }
                    }
                    break;
                case 'variation':
                    if ( $maybe_subscription->is_subscription() ) {
                        $price = sumo_display_subscription_plan( 0, $maybe_subscription->get_id() );
                    }
                    break;
                default:
                    if ( $maybe_subscription->is_subscription() ) {
                        if ( is_product() && ! sumo_can_purchase_subscription( $maybe_subscription->get_id() ) ) {
                            if ( 'yes' === SUMOSubs_Admin_Options::get_option( 'show_error_messages_in_product_page' ) ) {
                                $price = '<span id="sumosubs_restricted_message">' . SUMOSubs_Restrictions::add_error_notice() . '</span>';
                            }
                        } else {
                            $price = sumo_display_subscription_plan( 0, $maybe_subscription->get_id() );
                        }
                    }
                    break;
            }
        }

        return $price;
    }

    /**
     * Get subscription message to display in cart/checkout.
     *
     * @param string $message
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public static function get_subscription_message_in_cart_r_checkout( $message, $cart_item, $cart_item_key ) {
        if ( empty( $cart_item[ 'product_id' ] ) ) {
            return $message;
        }

        $maybe_subscription = new SUMOSubs_Product( $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] );
        if ( $maybe_subscription->exists() && $maybe_subscription->is_subscription() ) {
            self::$subscription_obj_type = 'product';
            self::$subscription          = $maybe_subscription;

            if ( ! sumosubs_is_subscription_product_type( self::$subscription->get_type() ) ) {
                return $message;
            }

            if ( SUMOSubs_Resubscribe::is_subscription_resubscribed( self::$subscription->get_id() ) ) {
                self::$subscription_obj_type = 'subscription';
                self::$subscription          = new SUMOSubs_Subscription( SUMOSubs_Resubscribe::get_resubscribed_subscription( self::$subscription->get_id() ) );
            }

            if ( 'product' === self::$subscription_obj_type ) {
                /**
                 * Get the product addon fee.
                 * 
                 * @since 1.0
                 */
                $addon_fee                = apply_filters( 'sumosubscriptions_get_product_addon_fee', 0, self::$subscription->get_id(), $cart_item, $cart_item_key );
                $is_trial_enabled         = self::$subscription->get_trial( 'forced' ) && sumo_can_purchase_subscription_trial( self::$subscription->get_id() ) ? true : false;
                $subscription_plan_string = sumo_display_subscription_plan( 0, self::$subscription->get_id(), $addon_fee );

                if ( SUMOSubs_Synchronization::cart_item_contains_sync( $cart_item ) ) {
                    $initial_renewal_date = $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_time' ];
                } else {
                    $initial_renewal_date = sumosubs_get_next_payment_date( 0, self::$subscription->get_id(), array(
                        'initial_payment'     => true,
                        'use_trial_if_exists' => $is_trial_enabled,
                            ) );
                }
            } else {
                $is_trial_enabled         = false;
                $subscription_plan_string = sumo_display_subscription_plan( 0, self::$subscription->get_subscribed_product() );
                $initial_renewal_date     = sumosubs_get_next_payment_date( self::$subscription->get_id(), 0, array( 'initial_payment' => true ) );
            }

            if ( is_checkout() ) {
                $message .= '<div>( ' . $subscription_plan_string . ')</div>';
            } else {
                $message = $subscription_plan_string;
            }

            $message .= '<p class="sumosubs_first_renewal_date"><small style="color:#777;font-size:smaller;">';
            if ( $is_trial_enabled || 1 !== self::$subscription->get_installments() ) {
                if (
                        'product' === self::$subscription_obj_type &&
                        SUMOSubs_Synchronization::cart_item_contains_sync( $cart_item ) &&
                        SUMOSubs_Synchronization::cart_item_contains_sync( $cart_item, 'xtra_time_to_charge_full_fee' ) &&
                        SUMOSubs_Synchronization::cart_item_contains_sync( $cart_item, 'awaiting_initial_payment' )
                ) {
                    /* translators: 1: first payment date */
                    $message .= sprintf( __( 'First Payment On: <b>%s</b>', 'sumosubscriptions' ), sumo_display_subscription_date( $initial_renewal_date ) );
                } else {
                    /* translators: 1: first renewal date */
                    $message .= sprintf( __( 'First Renewal On: <b>%s</b>', 'sumosubscriptions' ), sumo_display_subscription_date( $initial_renewal_date ) );
                }
            }

            /**
             * Get the subscription cart item message.
             * 
             * @since 1.0
             */
            $message = apply_filters( 'sumosubscriptions_get_message_to_display_in_cart_and_checkout', $message, self::$subscription, $cart_item, $cart_item_key );
            $message .= '</small><p>';
        }

        return $message;
    }

    /**
     * Add cart item data to subscription item.
     *
     * @param array $data Cart Item data.
     * @param array $cart_item Cart Item.
     * @return array
     */
    public static function display_cart_item_data( $data, $cart_item ) {
        $maybe_subscription = new SUMOSubs_Product( $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] );

        if ( $maybe_subscription->exists() && $maybe_subscription->is_subscription() ) {
            self::$subscription_obj_type = 'product';
            self::$subscription          = $maybe_subscription;

            if ( ! sumosubs_is_subscription_product_type( self::$subscription->get_type() ) ) {
                return $data;
            }

            if ( SUMOSubs_Resubscribe::is_subscription_resubscribed( self::$subscription->get_id() ) ) {
                self::$subscription_obj_type = 'subscription';
                self::$subscription          = new SUMOSubs_Subscription( SUMOSubs_Resubscribe::get_resubscribed_subscription( self::$subscription->get_id() ) );
            }

            if ( 'product' === self::$subscription_obj_type ) {
                /**
                 * Get the product addon fee.
                 * 
                 * @since 1.0
                 */
                $addon_fee                = apply_filters( 'sumosubscriptions_get_product_addon_fee', 0, self::$subscription->get_id(), $cart_item, $cart_item[ 'key' ] );
                $is_trial_enabled         = self::$subscription->get_trial( 'forced' ) && sumo_can_purchase_subscription_trial( self::$subscription->get_id() ) ? true : false;
                $subscription_plan_string = sumo_display_subscription_plan( 0, self::$subscription->get_id(), $addon_fee );

                if ( SUMOSubs_Synchronization::cart_item_contains_sync( $cart_item ) ) {
                    $initial_renewal_date = $cart_item[ 'sumosubscriptions' ][ 'sync' ][ 'initial_payment_time' ];
                } else {
                    $initial_renewal_date = sumosubs_get_next_payment_date( 0, self::$subscription->get_id(), array(
                        'initial_payment'     => true,
                        'use_trial_if_exists' => $is_trial_enabled,
                            ) );
                }
            } else {
                $is_trial_enabled         = false;
                $subscription_plan_string = sumo_display_subscription_plan( 0, self::$subscription->get_subscribed_product() );
                $initial_renewal_date     = sumosubs_get_next_payment_date( self::$subscription->get_id(), 0, array( 'initial_payment' => true ) );
            }

            $data[] = array(
                'key'                                      => ' ',
                'value'                                    => $subscription_plan_string,
                'hidden'                                   => true,
                '__experimental_woocommerce_blocks_hidden' => false,
            );

            if ( $is_trial_enabled || 1 !== self::$subscription->get_installments() ) {
                if (
                        'product' === self::$subscription_obj_type &&
                        SUMOSubs_Synchronization::cart_item_contains_sync( $cart_item ) &&
                        SUMOSubs_Synchronization::cart_item_contains_sync( $cart_item, 'xtra_time_to_charge_full_fee' ) &&
                        SUMOSubs_Synchronization::cart_item_contains_sync( $cart_item, 'awaiting_initial_payment' )
                ) {
                    $data[] = array(
                        'key'                                      => __( 'First Payment On', 'sumosubscriptions' ),
                        'value'                                    => sumo_display_subscription_date( $initial_renewal_date ),
                        'hidden'                                   => true,
                        '__experimental_woocommerce_blocks_hidden' => false,
                    );
                } else {
                    $data[] = array(
                        'key'                                      => __( 'First Renewal On', 'sumosubscriptions' ),
                        'value'                                    => sumo_display_subscription_date( $initial_renewal_date ),
                        'hidden'                                   => true,
                        '__experimental_woocommerce_blocks_hidden' => false,
                    );
                }

                if ( is_array( self::$subscription->get_coupons() ) ) {
                    $discounting_amount = self::$subscription->get_recurring_amount();
                    $discounting_amount *= $cart_item[ 'quantity' ];
                    $data[]             = array(
                        'key'                                      => __( 'Renewal Fee After Discount', 'sumosubscriptions' ),
                        'value'                                    => wc_price( SUMOSubs_Coupons::get_recurring_discount_amount( self::$subscription->get_coupons(), $discounting_amount, $cart_item[ 'quantity' ] ) ),
                        'hidden'                                   => true,
                        '__experimental_woocommerce_blocks_hidden' => false,
                    );
                }
            }
        }

        return $data;
    }

    /**
     * Apply custom style
     */
    public static function add_custom_style() {
        wp_register_style( 'sumo-subsc-inline', false, array(), SUMO_SUBSCRIPTIONS_VERSION );
        wp_enqueue_style( 'sumo-subsc-inline' );
        wp_add_inline_style( 'sumo-subsc-inline', SUMOSubs_Admin_Options::get_option( 'inline_style' ) );
    }

    /**
     * Calculate Subscription Product Line Total in Cart/Checkout.
     *
     * @param string $price
     * @param object $product
     * @return string
     */
    public static function set_cart_item_line_total( $price, $product ) {
        if ( is_shop() && ! is_front_page() ) {
            return $price;
        }

        $maybe_subscription = new SUMOSubs_Product( $product );
        if (
                ! $maybe_subscription->exists() ||
                ! $maybe_subscription->is_subscription() ||
                ! sumosubs_is_subscription_product_type( $maybe_subscription->get_type() )
        ) {
            return $price;
        }

        $default_line_total          = $price;
        self::$subscription_obj_type = 'product';
        self::$subscription          = $maybe_subscription;
        $subscription_price          = self::$subscription->get_recurring_amount();

        if ( ! is_admin() && SUMOSubs_Resubscribe::is_subscription_resubscribed( self::$subscription->get_id() ) ) {
            $default_line_total          = 0;
            self::$subscription_obj_type = 'subscription';
            self::$subscription          = new SUMOSubs_Subscription( SUMOSubs_Resubscribe::get_resubscribed_subscription( self::$subscription->get_id() ) );
            $subscription_price          = self::$subscription->get_recurring_amount() / self::$subscription->get_subscribed_qty();
        }

        if ( ! is_numeric( $subscription_price ) ) {
            return $price;
        }

        $is_trial_enabled = 'product' === self::$subscription_obj_type && self::$subscription->get_trial( 'forced' ) && sumo_can_purchase_subscription_trial( self::$subscription->get_id() ) ? true : false;
        $line_total       = ( float ) apply_filters( 'sumosubscriptions_get_subscription_price', floatval( $subscription_price ), self::$subscription, $default_line_total, $is_trial_enabled, self::$subscription_obj_type );

        if ( $is_trial_enabled ) {
            $line_total = 0; //Consider fee as 0 for Free Trial

            if ( 'paid' === self::$subscription->get_trial( 'type' ) ) {
                $line_total = floatval( self::$subscription->get_trial( 'fee' ) );
            }
        }

        if ( 'product' === self::$subscription_obj_type && self::$subscription->get_signup( 'forced' ) ) {//Onetime fee
            $line_total += floatval( self::$subscription->get_signup( 'fee' ) );
        }

        /**
         * Get the subscription final cart item total.
         * 
         * @since 1.0
         */
        return apply_filters( 'sumosubscriptions_get_line_total', $line_total, self::$subscription, $default_line_total, $is_trial_enabled, self::$subscription_obj_type );
    }

    /**
     * Alter cart total.
     *
     * @param float|int $total
     * @return mixed
     */
    public static function alter_cart_total( $total ) {
        if ( WC()->cart->needs_shipping() && ( 1 === count( WC()->cart->cart_contents ) && SUMOSubs_Synchronization::cart_contains_sync( 'xtra_time_to_charge_full_fee' ) ) ) {
            if ( ( WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() ) <= 0 ) {
                if ( 'woocommerce_cart_total' === current_filter() ) {
                    $message = '<div>';
                    $message .= '<small style="color:#777;font-size:smaller;">';
                    /* translators: 1: currency symbol 2: shipping amount */
                    $message .= sprintf( __( '(Shipping amount <strong>%1$s%2$s</strong> will be calculated during each renewal)', 'sumosubscriptions' ), get_woocommerce_currency_symbol(), WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() );
                    $message .= '</small>';
                    $message .= '</div>';
                    $total   .= $message;
                } else {
                    $total = 0;
                }
            }
        }

        return $total;
    }

    /**
     * Force Display Signup on Checkout for Guest. 
     * Since Guest don't have the permission to buy Subscriptions.
     */
    public static function force_enable_guest_signup_on_checkout( $checkout ) {
        if ( is_user_logged_in() || $checkout->is_registration_required() ) {
            return;
        }

        if ( ! $checkout->is_registration_enabled() && SUMOSubs_Order_Subscription::can_user_subscribe() ) {
            add_filter( 'woocommerce_checkout_registration_enabled', '__return_true', 99 );
            add_filter( 'woocommerce_checkout_registration_required', '__return_true', 99 );
        } else if ( ( sumo_is_cart_contains_subscription_items() || SUMOSubs_Order_Subscription::is_subscribed() ) ) {
            $checkout->enable_signup         = true;
            $checkout->enable_guest_checkout = false;
        }
    }

    /**
     * To Create account for Guest. 
     */
    public static function force_create_account_for_guest() {
        if ( ! is_user_logged_in() && ( sumo_is_cart_contains_subscription_items() || SUMOSubs_Order_Subscription::is_subscribed() ) ) {
            add_filter( 'woocommerce_checkout_registration_enabled', '__return_true', 99 );
            add_filter( 'woocommerce_checkout_registration_required', '__return_true', 99 );
            $_POST[ 'createaccount' ] = 1;
        }
    }

}

SUMOSubs_Frontend::init();

/**
 * For Backward Compatibility.
 */
class SUMOSubscriptions_Frontend extends SUMOSubs_Frontend {
    
}
