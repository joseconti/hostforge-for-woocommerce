<?php

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

/**
 * Store API integration class.
 *
 * @class SUMOSubs_Store_API
 * @package Class
 */
class SUMOSubs_Store_API {

    /**
     * Plugin identifier, unique to each plugin.
     *
     * @var string
     */
    const IDENTIFIER = 'sumosubscriptions';

    /**
     * Bootstraps the class and hooks required data.
     */
    public static function init() {
        self::extend_store();
    }

    /**
     * Register cart data handler.
     */
    public static function extend_store() {
        if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
            return;
        }

        woocommerce_store_api_register_endpoint_data(
                array(
                    'endpoint'        => CartItemSchema::IDENTIFIER,
                    'namespace'       => self::IDENTIFIER,
                    'data_callback'   => array( __CLASS__, 'extend_cart_item_data' ),
                    'schema_callback' => array( __CLASS__, 'extend_cart_item_schema' ),
                    'schema_type'     => ARRAY_A,
                )
        );

        woocommerce_store_api_register_endpoint_data(
                array(
                    'endpoint'        => CartSchema::IDENTIFIER,
                    'namespace'       => self::IDENTIFIER,
                    'data_callback'   => array( __CLASS__, 'extend_cart_data' ),
                    'schema_callback' => array( __CLASS__, 'extend_cart_schema' ),
                    'schema_type'     => ARRAY_A,
                )
        );

        woocommerce_store_api_register_update_callback(
                array(
                    'namespace' => self::IDENTIFIER,
                    'callback'  => array( __CLASS__, 'handle_update_endpoint' ),
                )
        );
    }

    /**
     * Register payment plan product data into cart/items endpoint.
     *
     * @param  array $cart_item Current cart item data.
     * @return array $item_data Registered deposits product data.
     */
    public static function extend_cart_item_data( $cart_item ) {
        return self::extend_cart_data( $cart_item );
    }

    /**
     * Adds extension data to cart route responses.
     *
     * @return array
     */
    public static function extend_cart_data( $cart_item = null ) {
        $cart_data = array(
            'is_sumosubs'                       => false,
            'order_subscribe_enabled'           => false,
            'order_subscribe_form_html'         => null,
            'order_subscription_recurring_info' => ''
        );

        if ( SUMOSubs_Order_Subscription::can_user_subscribe() ) {
            $cart_data[ 'order_subscribe_enabled' ] = true;
            $cart_data[ 'is_sumosubs' ]             = SUMOSubs_Order_Subscription::is_subscribed();

            if ( 'yes' === SUMOSubs_Admin_Options::get_option( 'order_subs_allow_in_cart' ) ) {
                $cart_data[ 'order_subscribe_form_html' ] = SUMOSubs_Order_Subscription::get_subscribe_form();
            }

            if ( $cart_data[ 'is_sumosubs' ] ) {
                $cart_data[ 'order_subscription_recurring_info' ] = sumo_display_subscription_plan();

                if ( is_numeric( WC()->cart->sumosubscriptions[ 'order' ][ 'discounted_recurring_fee' ] ) ) {
                    $cart_data[ 'order_subscription_recurring_info' ] .= str_replace( '[renewal_fee_after_discount]', wc_price( WC()->cart->sumosubscriptions[ 'order' ][ 'discounted_recurring_fee' ] ), SUMOSubs_Admin_Options::get_option( 'discounted_renewal_amount_strings' ) );
                }
            }
        }

        return $cart_data;
    }

    /**
     * Handle our actions through StoreAPI.
     *
     * @param array $args
     */
    public static function handle_update_endpoint( $args ) {
        
    }

    /**
     * Register payment plan product schema into cart/items endpoint.
     *
     * @return array Registered schema.
     */
    public static function extend_cart_item_schema() {
        return array();
    }

    /**
     * Register schema into cart endpoint.
     *
     * @return  array  Registered schema.
     */
    public static function extend_cart_schema() {
        return array();
    }
}
