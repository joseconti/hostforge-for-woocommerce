<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle regular products in cart to Order Subscription conversion.
 * 
 * @class SUMOSubs_Order_Subscription
 */
class SUMOSubs_Order_Subscription {

    /**
     * Check whether the customer can proceed to subscribe
     *
     * @var bool 
     */
    protected static $can_user_subscribe;

    /**
     * Form to render Order Subscription
     */
    protected static $form;

    /**
     * Form to render Order Subscription in cart
     */
    protected static $cart_form;

    /**
     * Get options
     */
    public static $get_option = array();

    /**
     * Get subscribed plan props
     *
     * @var array 
     */
    protected static $subscribed_plan_props = array(
        'subscribed'               => null,
        'has_signup'               => null,
        'signup_fee'               => null,
        'recurring_fee'            => null,
        'duration_period'          => null,
        'duration_length'          => null,
        'recurring_length'         => null,
        'item_fee'                 => null,
        'item_qty'                 => null,
        'discounted_recurring_fee' => null,
    );

    /**
     * Get form to render Order Subscription
     */
    public static function get_form() {
        if ( is_null( self::$form ) ) {
            self::$form = SUMOSubs_Admin_Options::get_option( 'order_subs_checkout_position' );
        }

        return self::$form;
    }

    /**
     * Get form to render Order Subscription in cart page
     * 
     * @since 14.8.0
     */
    public static function get_cart_form() {
        if ( is_null( self::$cart_form ) ) {
            self::$cart_form = SUMOSubs_Admin_Options::get_option( 'order_subs_cart_position' );
        }

        return self::$cart_form;
    }

    /**
     * Init SUMOSubs_Order_Subscription.
     */
    public static function init() {
        if ( empty( self::$get_option ) ) {
            self::populate();
        }

        if ( 'yes' !== SUMOSubs_Admin_Options::get_option( 'order_subs_allow_in_checkout' ) ) {
            return;
        }

        if ( 'yes' === SUMOSubs_Admin_Options::get_option( 'order_subs_allow_in_cart' ) ) {
            add_action( 'woocommerce_' . self::get_cart_form(), __CLASS__ . '::render_subscribe_form' );
            add_action( 'woocommerce_' . self::get_cart_form(), __CLASS__ . '::add_custom_style' );
        }

        add_action( 'woocommerce_' . self::get_form(), __CLASS__ . '::render_subscribe_form' );
        add_action( 'woocommerce_' . self::get_form(), __CLASS__ . '::add_custom_style' );

        add_action( 'wp_loaded', __CLASS__ . '::get_subscription_from_session', 20 );
        add_action( 'woocommerce_after_calculate_totals', __CLASS__ . '::get_subscription_from_session', 20 );
        add_action( 'woocommerce_cart_loaded_from_session', __CLASS__ . '::maybe_unsubscribe' );
        add_action( 'woocommerce_cart_emptied', __CLASS__ . '::unsubscribe' );
        add_filter( 'woocommerce_cart_total', __CLASS__ . '::render_subscribed_plan_message', 10, 1 );
        add_filter( 'sumosubscriptions_alter_subscription_plan_meta', __CLASS__ . '::save_subscribed_plan_meta', 10, 4 );
    }

    public static function can_user_subscribe() {
        if ( is_bool( self::$can_user_subscribe ) ) {
            return self::$can_user_subscribe;
        }

        $cart_total = ! is_null( WC()->cart ) ? WC()->cart->total : 0;
        $sub_total  = ! is_null( WC()->cart ) ? ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) ? ( WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() ) : WC()->cart->get_subtotal() ) : 0;

        if (
                ( 'yes' === SUMOSubs_Admin_Options::get_option( 'order_subs_allow_in_checkout' ) ) &&
                ! sumo_is_cart_contains_subscription_items( true ) &&
                self::cart_contains_valid_products() &&
                (
                ! is_numeric( self::$get_option[ 'min_order_total' ] ) ||
                ( 'total' === self::$get_option[ 'min_order_total_consideration' ] && floatval( $cart_total ) >= floatval( self::$get_option[ 'min_order_total' ] ) ) ||
                ( 'subtotal' === self::$get_option[ 'min_order_total_consideration' ] && floatval( $sub_total ) >= floatval( self::$get_option[ 'min_order_total' ] ) )
                )
        ) {
            self::$can_user_subscribe = true;
        }

        return self::$can_user_subscribe;
    }

    public static function get_default_props() {
        return array_map( '__return_null', self::$subscribed_plan_props );
    }

    public static function populate() {
        self::$get_option = array(
            'default_subscribed'                   => 'yes' === SUMOSubs_Admin_Options::get_option( 'order_subs_subscribed_default' ),
            'can_user_select_plan'                 => 'userdefined' === SUMOSubs_Admin_Options::get_option( 'order_subs_subscribe_values' ),
            'can_user_select_recurring_length'     => 'yes' === SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_allow_indefinite_subscription_length' ),
            'min_order_total'                      => SUMOSubs_Admin_Options::get_option( 'order_subs_min_order_total' ),
            'min_order_total_consideration'        => SUMOSubs_Admin_Options::get_option( 'order_subs_min_order_total_consideration' ),
            'default_duration_period'              => SUMOSubs_Admin_Options::get_option( 'order_subs_predefined_subscription_period' ),
            'default_duration_length'              => SUMOSubs_Admin_Options::get_option( 'order_subs_predefined_subscription_period_interval' ),
            'default_recurring_length'             => SUMOSubs_Admin_Options::get_option( 'order_subs_predefined_subscription_length' ),
            'duration_period_selector'             => SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_subscription_periods' ),
            'min_duration_length_user_can_select'  => SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_min_subscription_period_intervals' ),
            'max_duration_length_user_can_select'  => SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_max_subscription_period_intervals' ),
            'min_recurring_length_user_can_select' => SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_min_subscription_length' ),
            'max_recurring_length_user_can_select' => SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_max_subscription_length' ),
            'has_signup'                           => SUMOSubs_Admin_Options::get_option( 'order_subs_charge_signupfee' ),
            'signup_fee'                           => SUMOSubs_Admin_Options::get_option( 'order_subs_signupfee' ),
            'product_wide_selection'               => SUMOSubs_Admin_Options::get_option( 'order_subs_product_wide_selection' ),
            'allowed_product_ids'                  => SUMOSubs_Admin_Options::get_option( 'order_subs_allowed_product_ids' ),
            'restricted_product_ids'               => SUMOSubs_Admin_Options::get_option( 'order_subs_restricted_product_ids' ),
            'allowed_product_cat_ids'              => SUMOSubs_Admin_Options::get_option( 'order_subs_allowed_product_cat_ids' ),
            'restricted_product_cat_ids'           => SUMOSubs_Admin_Options::get_option( 'order_subs_restricted_product_cat_ids' ),
        );
    }

    /**
     * Check whether the cart contains valid products to perform Order Subscription by the user.
     * 
     * @return bool
     */
    public static function cart_contains_valid_products() {
        $product_ids_in_cart = array();

        if ( ! is_null( WC()->cart ) ) {
            foreach ( WC()->cart->cart_contents as $values ) {
                if ( $values[ 'variation_id' ] > 0 ) {
                    $product_ids_in_cart[ $values[ 'variation_id' ] ] = $values[ 'product_id' ];
                } else {
                    $product_ids_in_cart[ $values[ 'product_id' ] ] = 0;
                }
            }
        }

        $valid = true;
        switch ( self::$get_option[ 'product_wide_selection' ] ) {
            case 'allowed-product-ids':
                $allowed_product_ids_count     = 0;
                $not_allowed_product_ids_count = 0;

                if ( count( self::$get_option[ 'allowed_product_ids' ] ) ) {
                    foreach ( $product_ids_in_cart as $product_id => $parent_id ) {
                        $product_ids = array( $product_id, $parent_id );

                        if ( count( array_intersect( $product_ids, self::$get_option[ 'allowed_product_ids' ] ) ) > 0 ) {
                            $allowed_product_ids_count ++;
                        } else {
                            $not_allowed_product_ids_count ++;
                        }
                    }
                }

                $valid                         = ( $allowed_product_ids_count > 0 && 0 === $not_allowed_product_ids_count ) ? true : false;
                break;
            case 'restricted-product-ids':
                $allowed_product_ids_count     = 0;
                $not_allowed_product_ids_count = 0;

                if ( count( self::$get_option[ 'restricted_product_ids' ] ) ) {
                    foreach ( $product_ids_in_cart as $product_id => $parent_id ) {
                        $product_ids = array( $product_id, $parent_id );

                        if ( count( array_intersect( $product_ids, self::$get_option[ 'restricted_product_ids' ] ) ) > 0 ) {
                            $not_allowed_product_ids_count ++;
                        } else {
                            $allowed_product_ids_count ++;
                        }
                    }
                }

                $valid                             = $not_allowed_product_ids_count > 0 ? false : true;
                break;
            case 'allowed-product-cat-ids':
                $allowed_product_cat_ids_count     = 0;
                $not_allowed_product_cat_ids_count = 0;

                if ( count( self::$get_option[ 'allowed_product_cat_ids' ] ) ) {
                    foreach ( $product_ids_in_cart as $product_id => $parent_id ) {
                        $product_cats = wc_get_product_cat_ids( $parent_id > 0 ? $parent_id : $product_id );

                        if ( count( array_intersect( $product_cats, self::$get_option[ 'allowed_product_cat_ids' ] ) ) > 0 ) {
                            $allowed_product_cat_ids_count ++;
                        } else {
                            $not_allowed_product_cat_ids_count ++;
                        }
                    }
                }

                $valid                             = ( $allowed_product_cat_ids_count > 0 && 0 === $not_allowed_product_cat_ids_count ) ? true : false;
                break;
            case 'restricted-product-cat-ids':
                $allowed_product_cat_ids_count     = 0;
                $not_allowed_product_cat_ids_count = 0;

                if ( count( self::$get_option[ 'restricted_product_cat_ids' ] ) ) {
                    foreach ( $product_ids_in_cart as $product_id => $parent_id ) {
                        $product_cats = wc_get_product_cat_ids( $parent_id > 0 ? $parent_id : $product_id );

                        if ( count( array_intersect( $product_cats, self::$get_option[ 'restricted_product_cat_ids' ] ) ) > 0 ) {
                            $not_allowed_product_cat_ids_count ++;
                        } else {
                            $allowed_product_cat_ids_count ++;
                        }
                    }
                }

                $valid = $not_allowed_product_cat_ids_count > 0 ? false : true;
                break;
        }

        return $valid;
    }

    public static function is_subscribed( $subscription_id = 0, $parent_order_id = 0, $customer_id = 0 ) {
        if ( $subscription_id ) {
            return 'yes' === get_post_meta( $subscription_id, 'sumo_is_order_based_subscriptions', true );
        }

        if ( $parent_order_id ) {
            $parent_order = sumosubs_maybe_get_order_instance( $parent_order_id );

            if ( $parent_order && 'yes' === $parent_order->get_meta( 'sumo_is_order_based_subscriptions', true ) ) {
                return true;
            }
        }

        $customer_id = absint( $customer_id );
        if ( $customer_id ) {
            $subscribed_plan = get_user_meta( $customer_id, 'sumo_subscriptions_order_details', true );

            if ( ! empty( $subscribed_plan[ 'subscribed' ] ) && 'yes' === $subscribed_plan[ 'subscribed' ] ) {
                return true;
            }
        }

        if ( self::can_user_subscribe() && ! empty( WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ] ) ) {
            return 'yes' === WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ];
        }

        return false;
    }

    public static function get_subscribed_plan( $customer_id = 0 ) {
        $subscribed_plan = array();

        $customer_id = absint( $customer_id );
        if ( $customer_id ) {
            $subscribed_plan = get_user_meta( $customer_id, 'sumo_subscriptions_order_details', true );
        }

        if ( empty( $subscribed_plan ) && self::is_subscribed() ) {
            $subscribed_plan = WC()->cart->sumosubscriptions[ 'order' ];
        }

        self::$subscribed_plan_props = wp_parse_args( is_array( $subscribed_plan ) ? $subscribed_plan : array(), self::get_default_props() );
        return self::$subscribed_plan_props;
    }

    public static function add_custom_style() {
        if ( self::can_user_subscribe() ) {
            wp_register_style( 'sumo-order-subsc-inline', false, array(), SUMO_SUBSCRIPTIONS_VERSION );
            wp_enqueue_style( 'sumo-order-subsc-inline' );
            wp_add_inline_style( 'sumo-order-subsc-inline', SUMOSubs_Admin_Options::get_option( 'order_subs_inline_style' ) );
        }
    }

    public static function get_subscribe_form( $echo = false ) {
        ob_start();
        sumosubscriptions_get_template( 'order-subscription-form.php', array(
            'options'                     => self::$get_option,
            'subscribe_label'             => SUMOSubs_Admin_Options::get_option( 'order_subs_subscribe_label' ),
            'subscription_duration_label' => SUMOSubs_Admin_Options::get_option( 'order_subs_subscription_duration_label' ),
            'subscription_length_label'   => SUMOSubs_Admin_Options::get_option( 'order_subs_subscription_length_label' ),
            'chosen_plan'                 => self::get_subscribed_plan(),
            'user_action'                 => isset( WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ] ) ? WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ] : '',
        ) );

        if ( $echo ) {
            ob_end_flush();
        } else {
            return ob_get_clean();
        }
    }

    public static function render_subscribe_form() {
        if ( ( ! is_cart() && ! is_checkout() ) || ! self::can_user_subscribe() ) {
            return;
        }

        self::get_subscribe_form( true );
    }

    public static function render_subscribed_plan_message( $total ) {
        if ( self::is_subscribed() ) {
            $total = sumo_display_subscription_plan();

            if ( is_numeric( WC()->cart->sumosubscriptions[ 'order' ][ 'discounted_recurring_fee' ] ) ) {
                $total .= str_replace( '[renewal_fee_after_discount]', wc_price( WC()->cart->sumosubscriptions[ 'order' ][ 'discounted_recurring_fee' ] ), SUMOSubs_Admin_Options::get_option( 'discounted_renewal_amount_strings' ) );
            }
        }

        return $total;
    }

    public static function get_shipping_to_apply_in_renewal( $calc_tax = false ) {
        if ( 'yes' !== SUMOSubs_Admin_Options::get_option( 'charge_shipping_during_renewals' ) ) {
            return false;
        }

        $totals         = is_callable( array( WC()->cart, 'get_totals' ) ) ? WC()->cart->get_totals() : WC()->cart->totals;
        $shipping_total = ! empty( $totals[ 'shipping_total' ] ) ? floatval( $totals[ 'shipping_total' ] ) : false;
        $shipping_tax   = $calc_tax && ! empty( $totals[ 'shipping_tax' ] ) ? floatval( $totals[ 'shipping_tax' ] ) : false;

        if ( $shipping_total && $shipping_tax ) {
            $shipping_total += $shipping_tax;
        }

        return $shipping_total;
    }

    public static function get_items_tax_to_apply_in_renewal( $cart_item = array() ) {
        if ( 'yes' !== SUMOSubs_Admin_Options::get_option( 'charge_tax_during_renewals' ) || ! wc_tax_enabled() ) {
            return false;
        }

        $items_tax = false;
        if ( ! empty( $cart_item ) ) {
            if ( ! empty( $cart_item[ 'line_tax' ] ) ) {
                $items_tax = floatval( $cart_item[ 'line_tax' ] );
            }
        } else {
            $totals       = is_callable( array( WC()->cart, 'get_totals' ) ) ? WC()->cart->get_totals() : WC()->cart->totals;
            $discount_tax = ! empty( $totals[ 'discount_tax' ] ) ? floatval( $totals[ 'discount_tax' ] ) : false;
            $items_tax    = ! empty( $totals[ 'cart_contents_tax' ] ) ? floatval( $totals[ 'cart_contents_tax' ] ) : false;
            $items_tax    = $discount_tax && $items_tax ? $items_tax + $discount_tax : $items_tax;
        }

        return $items_tax;
    }

    public static function update_user_meta( $customer_id ) {
        delete_user_meta( $customer_id, 'sumo_subscriptions_order_details' );

        if ( self::is_subscribed() ) {
            add_user_meta( $customer_id, 'sumo_subscriptions_order_details', self::get_subscribed_plan() );
        }
    }

    public static function check_session_data() {
        if ( 'no' === WC()->session->get( 'sumo_is_order_subscription_subscribed' ) ) {
            return;
        }

        if ( ! in_array( WC()->session->get( 'sumo_order_subscription_duration_period' ), ( array ) self::$get_option[ 'duration_period_selector' ] ) ) {
            self::unsubscribe();
        }
    }

    public static function get_subscription_from_session() {
        if ( ! did_action( 'woocommerce_loaded' ) || is_null( WC()->cart ) ) {
            return;
        }

        if ( ! self::can_user_subscribe() ) {
            return;
        }

        self::check_session_data();
        WC()->cart->sumosubscriptions                            = array();
        WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ] = WC()->session->get( 'sumo_is_order_subscription_subscribed' );
        if ( 'yes' !== WC()->cart->sumosubscriptions[ 'order' ][ 'subscribed' ] ) {
            return;
        }

        $recurring_fee        = 0;
        $items_tax_in_renewal = false;
        $totals               = is_callable( array( WC()->cart, 'get_totals' ) ) ? WC()->cart->get_totals() : WC()->cart->totals;

        WC()->cart->sumosubscriptions[ 'order' ][ 'duration_period' ]  = WC()->session->get( 'sumo_order_subscription_duration_period', 'D' );
        WC()->cart->sumosubscriptions[ 'order' ][ 'duration_length' ]  = WC()->session->get( 'sumo_order_subscription_duration_length', '1' );
        WC()->cart->sumosubscriptions[ 'order' ][ 'recurring_length' ] = WC()->session->get( 'sumo_order_subscription_recurring_length', '0' );

        if ( ! empty( $totals[ 'cart_contents_tax' ] ) ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] = true;
            $items_tax_in_renewal                                    = self::get_items_tax_to_apply_in_renewal();

            if ( is_numeric( $items_tax_in_renewal ) ) {
                $recurring_fee += $items_tax_in_renewal;
            }
        }

        if ( ! empty( $totals[ 'shipping_total' ] ) ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] = true;
            $shipping_in_renewal                                     = self::get_shipping_to_apply_in_renewal( is_numeric( $items_tax_in_renewal ) );

            if ( is_numeric( $shipping_in_renewal ) ) {
                $recurring_fee += $shipping_in_renewal;
            }
        }

        if ( ! empty( $totals[ 'discount_total' ] ) ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] = true;
        }

        foreach ( WC()->cart->cart_contents as $cart_item ) {
            if ( empty( $cart_item[ 'product_id' ] ) ) {
                continue;
            }
            //Calculate Recurring Fee based no. of Item Qty
            $recurring_fee += floatval( wc_format_decimal( wc_get_price_excluding_tax( $cart_item[ 'data' ], array( 'qty' => $cart_item[ 'quantity' ] ) ) ) );
            $item_id       = $cart_item[ 'variation_id' ] > 0 ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ];
            $item_id       = sumosubs_wpml_maybe_get_translated_product_id( $item_id );

            WC()->cart->sumosubscriptions[ 'order' ][ 'item_fee' ][ $item_id ] = $cart_item[ 'data' ]->get_price();
            WC()->cart->sumosubscriptions[ 'order' ][ 'item_qty' ][ $item_id ] = $cart_item[ 'quantity' ];
        }

        $applied_discount_amount = sumosubs_get_applied_recurring_discount_amount();
        if ( ! empty( $totals[ 'discount_total' ] ) && $recurring_fee && ! empty( $applied_discount_amount ) ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'discounted_recurring_fee' ] = max( 0, ( ( ( float ) $totals[ 'subtotal' ] + ( float ) $totals[ 'subtotal_tax' ] ) - ( float ) $applied_discount_amount ) );
            if ( 'yes' === SUMOSubs_Admin_Options::get_option( 'charge_shipping_during_renewals' ) ) {
                WC()->cart->sumosubscriptions[ 'order' ][ 'discounted_recurring_fee' ] += ( ( float ) $totals[ 'shipping_total' ] + ( float ) $totals[ 'shipping_tax' ] );
            }
        }

        if ( 'yes' === self::$get_option[ 'has_signup' ] && is_numeric( self::$get_option[ 'signup_fee' ] ) && self::$get_option[ 'signup_fee' ] > 0 ) {
            WC()->cart->total                                        += wc_format_decimal( self::$get_option[ 'signup_fee' ] );
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] = true;
        }

        if ( wc_format_decimal( $recurring_fee ) == WC()->cart->total ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] = null;
        }

        if ( isset( WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] ) && WC()->cart->sumosubscriptions[ 'order' ][ 'has_signup' ] ) {
            WC()->cart->sumosubscriptions[ 'order' ][ 'signup_fee' ]    = WC()->cart->total;
            WC()->cart->sumosubscriptions[ 'order' ][ 'recurring_fee' ] = $recurring_fee;
        } else {
            WC()->cart->sumosubscriptions[ 'order' ][ 'recurring_fee' ] = WC()->cart->total;
        }

        WC()->cart->sumosubscriptions[ 'order' ] = self::get_subscribed_plan();
    }

    /**
     * Maybe unsubscribe.
     */
    public static function maybe_unsubscribe( $cart ) {
        if ( $cart->is_empty() ) {
            self::unsubscribe();
        }
    }

    /**
     * Unsubscribe.
     */
    public static function unsubscribe() {
        WC()->session->__unset( 'sumo_is_order_subscription_subscribed' );
        WC()->session->__unset( 'sumo_order_subscription_duration_period' );
        WC()->session->__unset( 'sumo_order_subscription_duration_length' );
        WC()->session->__unset( 'sumo_order_subscription_recurring_length' );
    }

    public static function save_subscribed_plan_meta( $subscribed_plan, $subscription_id, $product_id, $customer_id ) {
        if ( $subscription_id || $product_id ) {
            return $subscribed_plan;
        }

        if ( self::is_subscribed( 0, 0, $customer_id ) ) {
            self::get_subscribed_plan( $customer_id );

            $subscribed_plan[ 'susbcription_status' ]   = '1';
            $subscribed_plan[ 'subfee' ]                = self::$subscribed_plan_props[ 'recurring_fee' ];
            $subscribed_plan[ 'subperiod' ]             = self::$subscribed_plan_props[ 'duration_period' ];
            $subscribed_plan[ 'subperiodvalue' ]        = self::$subscribed_plan_props[ 'duration_length' ];
            $subscribed_plan[ 'instalment' ]            = self::$subscribed_plan_props[ 'recurring_length' ];
            $subscribed_plan[ 'signusumoee_selection' ] = self::$subscribed_plan_props[ 'has_signup' ] ? '1' : '';
            $subscribed_plan[ 'signup_fee' ]            = self::$subscribed_plan_props[ 'signup_fee' ];
            $subscribed_plan[ 'productid' ]             = array_keys( self::$subscribed_plan_props[ 'item_fee' ] );
            $subscribed_plan[ 'item_fee' ]              = self::$subscribed_plan_props[ 'item_fee' ];
            $subscribed_plan[ 'product_qty' ]           = self::$subscribed_plan_props[ 'item_qty' ];
        }

        return $subscribed_plan;
    }

}

SUMOSubs_Order_Subscription::init();

/**
 * For Backward Compatibility.
 */
class SUMO_Order_Subscription extends SUMOSubs_Order_Subscription {
    
}
