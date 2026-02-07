<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle subscription shipping.
 * 
 * @class SUMOSubs_Shipping
 */
class SUMOSubs_Shipping {

    protected static $shipping;

    /**
     * Init SUMOSubs_Shipping.
     */
    public static function init() {
        add_filter( 'woocommerce_my_account_edit_address_title', __CLASS__ . '::set_shipping_page_title', 100 );
        add_action( 'woocommerce_after_edit_address_form_shipping', __CLASS__ . '::get_bulk_update_checkbox' );
        add_action( 'woocommerce_customer_save_address', __CLASS__ . '::save_shipping_address', 100 );
    }

    /**
     * Check whether it is Subscription shipping edit address page
     *
     * @return boolean
     */
    public static function is_edit_shipping_address_page() {
        global $wp;

        if (
                is_page( wc_get_page_id( 'myaccount' ) ) &&
                isset( $wp->query_vars[ 'edit-address' ] ) &&
                wc_edit_address_i18n( sanitize_title( $wp->query_vars[ 'edit-address' ] ), true ) &&
                isset( $_GET[ 'subscription_id' ], $_GET[ '_sumosubsnonce' ] ) &&
                wp_verify_nonce( wc_clean( wp_unslash( $_GET[ '_sumosubsnonce' ] ) ), wc_clean( wp_unslash( $_GET[ 'subscription_id' ] ) ) )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check whether the subscriber updated his shipping address to the subscription.
     *
     * @param int $subscriber_id
     * @param int $subscription_id
     * @return bool
     */
    public static function is_updated( $subscriber_id, $subscription_id ) {
        if ( ! wc_shipping_enabled() ) {
            return false;
        }

        $shipping_address = get_post_meta( $subscription_id, 'sumosubs_shipping_address', true );
        if ( ! empty( $shipping_address ) ) {
            return true;
        } else {
            $shipping = get_user_meta( $subscriber_id, 'sumosubs_shipping_address', true );
            if ( ! empty( $shipping ) ) {
                return ! empty( $shipping[ 'update_to_all' ] ) || $shipping[ 'updated_via' ] == $subscription_id;
            }
        }

        return false;
    }

    /**
     * Get Shipping address updated by subscriber
     *
     * @param int $subscriber_id
     * @param int $subscription_id
     * @return array
     */
    public static function get_address( $subscriber_id, $subscription_id ) {
        $shipping_address = get_post_meta( $subscription_id, 'sumosubs_shipping_address', true );

        return array(
            'first_name' => ! empty( $shipping_address ) ? $shipping_address[ 'first_name' ] : get_user_meta( $subscriber_id, 'shipping_first_name', true ),
            'last_name'  => ! empty( $shipping_address ) ? $shipping_address[ 'last_name' ] : get_user_meta( $subscriber_id, 'shipping_last_name', true ),
            'company'    => ! empty( $shipping_address ) ? $shipping_address[ 'company' ] : get_user_meta( $subscriber_id, 'shipping_company', true ),
            'address_1'  => ! empty( $shipping_address ) ? $shipping_address[ 'address_1' ] : get_user_meta( $subscriber_id, 'shipping_address_1', true ),
            'address_2'  => ! empty( $shipping_address ) ? $shipping_address[ 'address_2' ] : get_user_meta( $subscriber_id, 'shipping_address_2', true ),
            'city'       => ! empty( $shipping_address ) ? $shipping_address[ 'city' ] : get_user_meta( $subscriber_id, 'shipping_city', true ),
            'state'      => ! empty( $shipping_address ) ? $shipping_address[ 'state' ] : get_user_meta( $subscriber_id, 'shipping_state', true ),
            'postcode'   => ! empty( $shipping_address ) ? $shipping_address[ 'postcode' ] : get_user_meta( $subscriber_id, 'shipping_postcode', true ),
            'country'    => ! empty( $shipping_address ) ? $shipping_address[ 'country' ] : get_user_meta( $subscriber_id, 'shipping_country', true ),
        );
    }

    /**
     * Get Subscription Shipping address Endpoint URl
     *
     * @param int $subscription_id
     * @return string
     */
    public static function get_shipping_endpoint_url( $subscription_id ) {
        return esc_url_raw( add_query_arg( array( 'subscription_id' => absint( $subscription_id ), 'subscriber_id' => get_current_user_id(), '_sumosubsnonce' => wp_create_nonce( "$subscription_id" ) ), wc_get_endpoint_url( 'edit-address', 'shipping', wc_get_page_permalink( 'myaccount' ) ) ) );
    }

    /**
     * Set Subscription shipping address page title
     *
     * @param string $title
     * @return string
     */
    public static function set_shipping_page_title( $title ) {
        if ( self::is_edit_shipping_address_page() ) {
            return __( 'Change Subscription Shipping Address', 'sumosubscriptions' );
        }

        return $title;
    }

    /**
     * Get bulk update checkbox to update the Shipping address to each Subscriptions he has purchased or new Subscriptions
     */
    public static function get_bulk_update_checkbox() {
        if ( ! self::is_edit_shipping_address_page() ) {
            return;
        }

        echo '<input type="checkbox" class="input-checkbox sumo_update_to_all" name="update_to_all" value="yes">';
        esc_html_e( 'Update this Address to all Subscriptions', 'sumosubscriptions' );
    }

    /**
     * Save Shipping address belongs to the Subscription
     *
     * @param int $subscriber_id
     */
    public static function save_shipping_address( $subscriber_id ) {
        if ( ! self::is_edit_shipping_address_page() ) {
            return;
        }

        $subscription_id  = absint( wp_unslash( $_GET[ 'subscription_id' ] ) );
        $update_to_all    = isset( $_REQUEST[ 'update_to_all' ] ) && 'yes' === wc_clean( wp_unslash( $_REQUEST[ 'update_to_all' ] ) );
        $shipping_address = array(
            'first_name' => isset( $_REQUEST[ 'shipping_first_name' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'shipping_first_name' ] ) ) : '',
            'last_name'  => isset( $_REQUEST[ 'shipping_last_name' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'shipping_last_name' ] ) ) : '',
            'company'    => isset( $_REQUEST[ 'shipping_company' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'shipping_company' ] ) ) : '',
            'address_1'  => isset( $_REQUEST[ 'shipping_address_1' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'shipping_address_1' ] ) ) : '',
            'address_2'  => isset( $_REQUEST[ 'shipping_address_2' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'shipping_address_2' ] ) ) : '',
            'city'       => isset( $_REQUEST[ 'shipping_city' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'shipping_city' ] ) ) : '',
            'state'      => isset( $_REQUEST[ 'shipping_state' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'shipping_state' ] ) ) : '',
            'postcode'   => isset( $_REQUEST[ 'shipping_postcode' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'shipping_postcode' ] ) ) : '',
            'country'    => isset( $_REQUEST[ 'shipping_country' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'shipping_country' ] ) ) : '',
        );

        if ( $update_to_all ) {
            $subscriptions = sumosubs_get_subscriptions_by_user( $subscriber_id );

            foreach ( $subscriptions as $_subscription_id ) {
                update_post_meta( $_subscription_id, 'sumosubs_shipping_address', $shipping_address );
            }

            sumo_add_subscription_note( __( 'Shipping address updated to all.', 'sumosubscriptions' ), $subscription_id, 'success', __( 'Shipping address updated', 'sumosubscriptions' ) );
        } else {
            update_post_meta( $subscription_id, 'sumosubs_shipping_address', $shipping_address );
            sumo_add_subscription_note( __( 'Shipping address updated.', 'sumosubscriptions' ), $subscription_id, 'success', __( 'Shipping address updated', 'sumosubscriptions' ) );
        }

        wp_safe_redirect( sumo_get_subscription_endpoint_url( $subscription_id ) );
        exit();
    }
}

SUMOSubs_Shipping::init();

/**
 * For Backward Compatibility.
 */
class SUMO_Subscription_Shipping extends SUMOSubs_Shipping {
    
}
