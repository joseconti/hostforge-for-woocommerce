<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Subscription Factory
 * 
 * Creating right object for Subscription or Product.
 * 
 * @class SUMOSubs_Subscription_Factory
 */
class SUMOSubs_Subscription_Factory {

    protected static $internal_args = array(
        'susbcription_status'                     => '',
        'trial_selection'                         => '',
        'fee_type'                                => '',
        'trialfee'                                => '',
        'trialperiod'                             => '',
        'trialperiodvalue'                        => '',
        'subfee'                                  => '',
        'sale_fee'                                => '',
        'subperiod'                               => '',
        'subperiodvalue'                          => '',
        'signusumoee_selection'                   => '',
        'signup_fee'                              => '',
        'instalment'                              => '',
        'productid'                               => '',
        'synchronization_status'                  => '',
        'synchronize_mode'                        => '',
        'synchronization_period'                  => '',
        'synchronization_period_value'            => '',
        'synchronize_start_year'                  => '',
        'subscribed_after_sync_date_type'         => '',
        'xtra_time_to_charge_full_fee'            => '',
        'cutoff_time_to_not_renew_nxt_subs_cycle' => '',
        'variation_product_level_id'              => '',
        'item_fee'                                => '',
        'product_qty'                             => '',
        'subscription_discount'                   => '',
        'additional_digital_downloads_status'     => '',
        'downloadable_products'                   => '',
        'send_payment_reminder_email'             => '',
    );

    /**
     * Get the Subscription ID depending on what was passed.
     *
     * @param  mixed $subscription Subscription data to convert to an ID.
     * @return int|bool false on failure
     */
    public static function get_subscription_id( $subscription ) {
        global $post;

        if ( is_numeric( $subscription ) ) {
            return absint( $subscription );
        } elseif ( $subscription instanceof SUMOSubs_Subscription || $subscription instanceof SUMOSubs_Product ) {
            return $subscription->get_id();
        } elseif ( ! empty( $subscription->ID ) ) {
            return absint( $subscription->ID );
        } elseif ( is_a( $post, 'WP_Post' ) && in_array( get_post_type( $post ), array( 'sumosubscriptions', 'product' ) ) ) {
            return absint( $post->ID );
        }
        return false;
    }

    /**
     * Retrieve Subscription Product Data.
     * 
     * @param mixed $subscription 
     * @param int $user_id 
     * @return array
     */
    public static function get_subscription( $subscription, $user_id = 0 ) {
        $subscription_meta       = array();
        $subscription_id         = 0;
        $subscription_product_id = 0;
        $the_subscription_id     = self::get_subscription_id( $subscription );

        if ( $the_subscription_id ) {
            switch ( get_post_type( $the_subscription_id ) ) {
                case 'sumosubscriptions':
                    $subscription_id = $the_subscription_id;

                    if ( SUMOSubs_Order_Subscription::is_subscribed( $subscription_id ) ) {
                        $subscription_meta = ( array ) get_post_meta( $subscription_id, 'sumo_subscriptions_order_details', true );
                    } else {
                        $subscription_meta = ( array ) get_post_meta( $subscription_id, 'sumo_subscription_product_details', true );
                    }
                    break;
                case 'product':
                case 'product_variation':
                    $subscription_product_id = $the_subscription_id;

                    if ( sumo_can_user_purchase_as_subscription( $subscription_product_id, $user_id ) ) {
                        $subscription_product         = wc_get_product( $subscription_product_id );
                        $is_synced                    = false;
                        $subscription_version         = get_post_meta( $subscription_product_id, 'sumo_subscription_version', true );
                        $subscription_period          = get_post_meta( $subscription_product_id, 'sumo_susbcription_period', true );
                        $subscription_period_value    = get_post_meta( $subscription_product_id, 'sumo_susbcription_period_value', true );
                        $synchronization_period       = get_post_meta( $subscription_product_id, 'sumo_synchronize_period', true );
                        $synchronization_period_value = get_post_meta( $subscription_product_id, 'sumo_synchronize_period_value', true );
                        $synchronize_start_year       = get_post_meta( $subscription_product_id, 'sumo_synchronize_start_year', true );

                        if ( SUMOSubs_Synchronization::$sync_enabled_site_wide ) {
                            switch ( $subscription_period ) {
                                case 'W':
                                    $is_synced = $synchronization_period > 0;
                                    break;
                                case 'M':
                                    $is_synced = $synchronization_period_value > 0 && ( 'first-occurrence' === SUMOSubs_Synchronization::$sync_mode || ( 'exact-date-r-day' === SUMOSubs_Synchronization::$sync_mode && ( 0 === 12 % $subscription_period_value || '24' === $subscription_period_value ) ) );

                                    //BKWD CMPT <= 9.6
                                    if ( $is_synced && 'exact-date-r-day' === SUMOSubs_Synchronization::$sync_mode && '1' === $subscription_period_value && version_compare( $subscription_version, '9.6', '<=' ) ) {
                                        $current_day = gmdate( 'd', sumo_get_subscription_timestamp() );

                                        if ( $synchronization_period_value > $current_day ) {
                                            $synchronization_period = gmdate( 'm', sumo_get_subscription_timestamp() );
                                        } else {
                                            $synchronization_period = gmdate( 'm', sumo_get_subscription_timestamp( 'next month' ) );
                                        }
                                    }
                                    break;
                                case 'Y':
                                    $is_synced = $synchronization_period_value > 0;
                                    break;
                            }
                        }
                        $subscribed_after_sync_date_type = get_post_meta( $subscription_product_id, 'sumo_subscribed_after_sync_date_type', true );
                        $subscribed_after_sync_date_type = empty( $subscribed_after_sync_date_type ) ? 'xtra-time-to-charge-full-fee' : $subscribed_after_sync_date_type;

                        $xtra_time_to_charge_full_fee = get_post_meta( $subscription_product_id, 'sumo_xtra_time_to_charge_full_fee', true );
                        $xtra_time_to_charge_full_fee = is_numeric( $xtra_time_to_charge_full_fee ) ? $xtra_time_to_charge_full_fee : get_post_meta( $subscription_product_id, 'sumo_xtra_duration_to_charge_full_fee', true ); //BKWD CMPT

                        $bckwrd_optional_signup_status = 'yes' === get_post_meta( $subscription_product_id, 'sumo_susbcription_signup_fee_is_optional_for_user', true );
                        $bckwrd_optional_trial_status  = 'yes' === get_post_meta( $subscription_product_id, 'sumo_susbcription_trial_is_optional_for_user', true );

                        $signup_status = $bckwrd_optional_signup_status ? '3' : get_post_meta( $subscription_product_id, 'sumo_susbcription_signusumoee_enable_disable', true );
                        $trial_status  = $bckwrd_optional_trial_status ? '3' : get_post_meta( $subscription_product_id, 'sumo_susbcription_trial_enable_disable', true );

                        remove_filter( 'woocommerce_product_get_regular_price', 'SUMOSubs_Frontend::set_cart_item_line_total', 99, 2 );
                        remove_filter( 'woocommerce_product_variation_get_regular_price', 'SUMOSubs_Frontend::set_cart_item_line_total', 99, 2 );
                        $regular_price = $subscription_product->get_regular_price();
                        $sale_price    = $subscription_product->is_on_sale() ? $subscription_product->get_sale_price() : '';
                        if ( class_exists( 'SUMOSubs_Frontend' ) ) {
                            add_filter( 'woocommerce_product_get_regular_price', 'SUMOSubs_Frontend::set_cart_item_line_total', 99, 2 );
                            add_filter( 'woocommerce_product_variation_get_regular_price', 'SUMOSubs_Frontend::set_cart_item_line_total', 99, 2 );
                        }
                        //Product Subscription meta.
                        $subscription_meta = array(
                            'susbcription_status'                     => get_post_meta( $subscription_product_id, 'sumo_susbcription_status', true ),
                            'trial_selection'                         => $trial_status,
                            'fee_type'                                => get_post_meta( $subscription_product_id, 'sumo_susbcription_fee_type_selector', true ),
                            'trialfee'                                => wc_format_decimal( get_post_meta( $subscription_product_id, 'sumo_trial_price', true ) ),
                            'trialperiod'                             => get_post_meta( $subscription_product_id, 'sumo_trial_period', true ),
                            'trialperiodvalue'                        => get_post_meta( $subscription_product_id, 'sumo_trial_period_value', true ),
                            'subfee'                                  => $regular_price,
                            'sale_fee'                                => $sale_price,
                            'subperiod'                               => $subscription_period,
                            'subperiodvalue'                          => $subscription_period_value,
                            'signusumoee_selection'                   => $signup_status,
                            'signup_fee'                              => wc_format_decimal( get_post_meta( $subscription_product_id, 'sumo_signup_price', true ) ),
                            'instalment'                              => get_post_meta( $subscription_product_id, 'sumo_recurring_period_value', true ),
                            'productid'                               => $subscription_product_id,
                            'variation_product_level_id'              => wp_get_post_parent_id( $subscription_product_id ),
                            'synchronization_status'                  => $is_synced ? '1' : '2',
                            'synchronize_mode'                        => SUMOSubs_Synchronization::$sync_mode,
                            'synchronization_period'                  => $synchronization_period,
                            'synchronization_period_value'            => $synchronization_period_value,
                            'synchronize_start_year'                  => empty( $synchronize_start_year ) || ! is_numeric( $synchronize_start_year ) ? '2017' : $synchronize_start_year,
                            'subscribed_after_sync_date_type'         => $subscribed_after_sync_date_type,
                            'xtra_time_to_charge_full_fee'            => $xtra_time_to_charge_full_fee,
                            'cutoff_time_to_not_renew_nxt_subs_cycle' => get_post_meta( $subscription_product_id, 'sumo_cutoff_time_to_not_renew_nxt_subs_cycle', true ),
                            'additional_digital_downloads_status'     => 'yes' === SUMOSubs_Admin_Options::get_option( 'enable_additional_digital_downloads' ) && 'yes' === get_post_meta( $subscription_product_id, 'sumo_enable_additional_digital_downloads', true ) ? '1' : '2',
                            'downloadable_products'                   => get_post_meta( $subscription_product_id, 'sumo_choose_downloadable_products', true ),
                            'send_payment_reminder_email'             => get_post_meta( $subscription_product_id, 'sumosubs_send_payment_reminder_email', true ),
                            'version'                                 => $subscription_version,
                        );
                    }
                    break;
            }
        }

        /**
         * Get the subscription meta.
         * 
         * @since 1.0
         */
        $subscription_meta = apply_filters( 'sumosubscriptions_alter_subscription_plan_meta', $subscription_meta, $subscription_id, $subscription_product_id, $user_id );
        return wp_parse_args( $subscription_meta, self::$internal_args );
    }
}
