<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle subscription ajax event.
 * 
 * @class SUMOSubs_Ajax
 */
class SUMOSubs_Ajax {

    /**
     * Init SUMOSubs_Ajax.
     */
    public static function init() {
        //Get Ajax Events.
        $ajax_events = array(
            'add_subscription_note'                             => false,
            'delete_subscription_note'                          => false,
            'get_subscribed_optional_plans_by_user'             => true,
            'subscriber_request'                                => false,
            'cancel_request'                                    => false,
            'checkout_order_subscription'                       => true,
            'get_subscription_variation_attributes_upon_switch' => false,
            'save_swapped_subscription_variation'               => false,
            'init_data_export'                                  => false,
            'handle_exported_data'                              => false,
            'bulk_update_products'                              => false,
            'bulk_update_subscriptions'                         => false,
            'get_subscription_as_regular_product_row'           => false,
            'json_search_subscription_products_and_variations'  => false,
            'json_search_downloadable_products_and_variations'  => false,
            'json_search_customers_by_email'                    => false,
        );

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( "wp_ajax_sumosubscription_{$ajax_event}", __CLASS__ . "::{$ajax_event}" );

            if ( $nopriv ) {
                add_action( "wp_ajax_nopriv_sumosubscription_{$ajax_event}", __CLASS__ . "::{$ajax_event}" );
            }
        }
    }

    /**
     * Admin manually add subscription notes.
     */
    public static function add_subscription_note() {
        check_ajax_referer( 'add-subscription-note', 'security' );

        $posted = $_POST;
        $note   = sumo_add_subscription_note( wc_clean( wp_unslash( $posted[ 'content' ] ) ), absint( wp_unslash( $posted[ 'post_id' ] ) ), 'processing', __( 'Note added by admin', 'sumosubscriptions' ) );
        $note   = sumosubs_get_subscription_note( $note );

        if ( $note ) {
            include 'admin/views/html-admin-subscription-note.php';
        }
        die();
    }

    /**
     * Admin manually delete subscription notes.
     */
    public static function delete_subscription_note() {
        check_ajax_referer( 'delete-subscription-note', 'security' );
        $posted = $_POST;
        wp_send_json( wp_delete_comment( absint( wp_unslash( $posted[ 'delete_id' ] ) ), true ) );
    }

    /**
     * Get optional Subscription plan subscribed by User in product page
     */
    public static function get_subscribed_optional_plans_by_user() {
        check_ajax_referer( 'get-subscription-product-data', 'security' );

        $posted         = $_POST;
        $product_id     = absint( wp_unslash( $posted[ 'product_id' ] ) );
        $selected_plans = wc_clean( wp_unslash( $posted[ 'selected_plans' ] ) );
        if ( ! $product_id ) {
            die();
        }

        $subscription_plan = sumo_get_subscription_plan( 0, $product_id );

        if ( in_array( 'set_trial', $selected_plans ) ) {
            $subscription_plan[ 'trial_status' ] = '1';
        }

        if ( in_array( 'set_signup_fee', $selected_plans ) ) {
            $subscription_plan[ 'signup_status' ] = '1';
        }

        wp_send_json( array(
            /* translators: 1: label 2: initial payment date */
            'next_payment_sync_on' => '1' === $subscription_plan[ 'synchronization_status' ] ? sprintf( '<p id="sumosubs_initial_synced_payment_date">%s<strong>%s</strong></p>', __( 'Next Payment on: ', 'sumosubscriptions' ), SUMOSubs_Synchronization::get_initial_payment_date( $product_id, true ) ) : '',
            'subscribed_plan'      => sumo_display_subscription_plan( 0, 0, 0, false, $subscription_plan ),
        ) );
    }

    public static function subscriber_request() {
        check_ajax_referer( 'subscriber-request', 'security' );

        $posted              = $_POST;
        $action              = wc_clean( wp_unslash( $posted[ 'request' ] ) );
        $requested_by        = wc_clean( wp_unslash( $posted[ 'requested_by' ] ) );
        $subscription_id     = absint( wp_unslash( $posted[ 'subscription_id' ] ) );
        $next_payment_date   = get_post_meta( $subscription_id, 'sumo_get_next_payment_date', true );
        $saved_due_date      = get_post_meta( $subscription_id, 'sumo_get_saved_due_date', true );
        $renewal_order_id    = absint( get_post_meta( $subscription_id, 'sumo_get_renewal_id', true ) );
        $parent_order_id     = absint( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) );
        $persistent_due_date = '--' === $next_payment_date ? $saved_due_date : $next_payment_date;

        try {
            $success_args = array(
                'result'   => 'success',
                'action'   => $action,
                'notice'   => '',
                'redirect' => sumo_get_subscription_endpoint_url( $subscription_id ),
            );

            switch ( $action ) {
                case 'pause':
                    if ( isset( $posted[ 'auto_resume_on' ] ) ) {
                        $auto_resume_on = wc_clean( wp_unslash( $posted[ 'auto_resume_on' ] ) );

                        if ( empty( $auto_resume_on ) || ( ! empty( $auto_resume_on ) && ! sumosubs_is_valid_date( $auto_resume_on, 'Y-m-d' ) ) ) {
                            throw new Exception( __( 'Please enter the valid date to resume the subscription!!', 'sumosubscriptions' ) );
                        }
                    }

                    //Manage Automatic Resume
                    if ( ! empty( $auto_resume_on ) ) {
                        $cron_event = new SUMOSubs_Cron_Event( $subscription_id );
                        $cron_event->schedule_automatic_resume( $auto_resume_on );
                        add_post_meta( $subscription_id, 'sumo_subscription_auto_resume_scheduled_on', $auto_resume_on );
                    }

                    sumo_pause_subscription( $subscription_id, '', $requested_by );

                    /**
                     * After subscription is paused.
                     * 
                     * @since 1.0
                     */
                    do_action( 'sumosubscriptions_pause_subscription', $subscription_id, $parent_order_id );
                    break;
                case 'resume':
                    sumo_resume_subscription( $subscription_id, $requested_by );

                    /**
                     * After subscription is resumed.
                     * 
                     * @since 1.0
                     */
                    do_action( 'sumosubscriptions_active_subscription', $subscription_id, $parent_order_id );
                    break;
                case 'cancel-immediate':
                    sumosubs_cancel_subscription( $subscription_id, array(
                        'request_by' => $requested_by,
                    ) );
                    break;
                case 'cancel-at-the-end-of-billing-cycle':
                    /**
                     * Need to schedule cancel?
                     * 
                     * @since 1.0
                     */
                    if ( apply_filters( 'sumosubscriptions_schedule_cancel', true, $subscription_id, $parent_order_id ) ) {
                        sumosubs_cancel_subscription( $subscription_id, array(
                            'request_by' => $requested_by,
                            'when'       => 'end_of_billing_cycle',
                        ) );
                    }
                    break;
                case 'cancel-on-scheduled-date':
                    $scheduled_date    = wc_clean( wp_unslash( $posted[ 'scheduled_date_to_cancel' ] ) );
                    $scheduled_time    = sumo_get_subscription_timestamp( $scheduled_date );
                    $next_payment_time = sumo_get_subscription_timestamp( $persistent_due_date );

                    if ( $scheduled_time < sumo_get_subscription_timestamp() || $scheduled_time > $next_payment_time ) {
                        throw new Exception( __( 'Selected date must be within current billing cycle !!', 'sumosubscriptions' ) );
                    }

                    /**
                     * Need to schedule cancel?
                     * 
                     * @since 1.0
                     */
                    if ( apply_filters( 'sumosubscriptions_schedule_cancel', true, $subscription_id, $parent_order_id ) ) {
                        sumosubs_cancel_subscription( $subscription_id, array(
                            'request_by'    => $requested_by,
                            'when'          => 'scheduled_date',
                            'schedule_date' => $scheduled_date,
                        ) );
                    }
                    break;
                case 'cancel-revoke':
                    sumosubs_revoke_cancel_request( $subscription_id, __( 'Cancel request revoked by user.', 'sumosubscriptions' ) );
                    break;
                case 'turnoff-auto':
                    /**
                     * Need to turnoff automatic payments?
                     * 
                     * @since 1.0
                     */
                    if ( 'auto' === sumo_get_payment_type( $subscription_id ) && apply_filters( 'sumosubscriptions_revoke_automatic_subscription', true, $subscription_id, $parent_order_id ) ) {
                        sumo_save_subscription_payment_info( $parent_order_id, array(
                            'payment_type' => 'manual',
                        ) );

                        $cron_event = new SUMOSubs_Cron_Event( $subscription_id );
                        $cron_event->unset_events( array(
                            'automatic_pay',
                            'notify_invoice_reminder',
                            'switch_to_manual_pay_mode',
                            'retry_automatic_pay_in_overdue',
                            'retry_automatic_pay_in_suspended',
                        ) );

                        if ( sumosubs_unpaid_renewal_order_exists( $subscription_id ) ) {
                            $cron_event->schedule_next_eligible_payment_failed_status();
                            $cron_event->schedule_reminders( $renewal_order_id, $persistent_due_date );
                        }

                        sumo_add_subscription_note( __( 'Subscriber revoked automatic charging access.', 'sumosubscriptions' ), $subscription_id, 'success', __( 'Revoked automatic payments', 'sumosubscriptions' ) );
                        sumo_trigger_subscription_email( 'subscription_turnoff_auto_payments_success', 0, $subscription_id );

                        /**
                         * After turned off automatic payments.
                         * 
                         * @since 1.0
                         */
                        do_action( 'sumosubscriptions_automatic_subscription_is_revoked', $subscription_id, $parent_order_id );

                        $success_args[ 'notice' ] = esc_html__( 'You have successfully turned off your Automatic Subscription Renewal for this subscription!!', 'sumosubscriptions' );
                    }
                    break;
                case 'resubscribe':
                    $success_args[ 'redirect' ] = SUMOSubs_Resubscribe::do_resubscribe( $subscription_id );
                    break;
                case 'quantity-change':
                    sumo_change_subscription_qty( $subscription_id, absint( wp_unslash( $posted[ 'quantity' ] ) ), 'customer' );
                    break;
            }

            /**
             * Get my account action success args.
             * 
             * @since 1.0
             */
            wp_send_json( ( array ) apply_filters( 'sumosubscriptions_my_account_action_success_args', $success_args ) );
        } catch ( Exception $e ) {
            wp_send_json( array(
                'result' => 'failure',
                'notice' => esc_html( $e->getMessage() ),
            ) );
        }
    }

    /**
     * Cancel request by Admin. Cancelling Subscription by Immediately/End of Billing Cycle/Scheduled Date 
     */
    public static function cancel_request() {
        check_ajax_referer( 'subscription-cancel-request', 'security' );

        $posted              = $_POST;
        $subscription_id     = absint( wp_unslash( $posted[ 'subscription_id' ] ) );
        $requested_method    = wc_clean( wp_unslash( $posted[ 'cancel_method_requested' ] ) );
        $requested_by        = wc_clean( wp_unslash( $posted[ 'cancel_method_requested_by' ] ) );
        $next_due_date       = get_post_meta( $subscription_id, 'sumo_get_next_payment_date', true );
        $saved_due_date      = get_post_meta( $subscription_id, 'sumo_get_saved_due_date', true );
        $parent_order_id     = absint( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) );
        $persistent_due_date = '--' === $next_due_date ? $saved_due_date : $next_due_date;

        try {
            switch ( $requested_method ) {
                case 'immediate':
                    //Cancel Subscription
                    sumosubs_cancel_subscription( $subscription_id, array(
                        'request_by' => $requested_by,
                    ) );
                    break;
                case 'end_of_billing_cycle':
                    /**
                     * Need to schedule cancel?
                     * 
                     * @since 1.0
                     */
                    if ( apply_filters( 'sumosubscriptions_schedule_cancel', true, $subscription_id, $parent_order_id ) ) {
                        sumosubs_cancel_subscription( $subscription_id, array(
                            'request_by' => $requested_by,
                            'when'       => $requested_method,
                        ) );
                    }
                    break;
                case 'scheduled_date':
                    $scheduled_date    = wc_clean( wp_unslash( $posted[ 'scheduled_date' ] ) );
                    $scheduled_time    = sumo_get_subscription_timestamp( $scheduled_date );
                    $next_payment_time = sumo_get_subscription_timestamp( $persistent_due_date );

                    if ( $scheduled_time < sumo_get_subscription_timestamp() || $scheduled_time > $next_payment_time ) {
                        throw new Exception( __( 'Selected date must be within current billing cycle !!', 'sumosubscriptions' ) );
                    }

                    /**
                     * Need to schedule cancel?
                     * 
                     * @since 1.0
                     */
                    if ( apply_filters( 'sumosubscriptions_schedule_cancel', true, $subscription_id, $parent_order_id ) ) {
                        sumosubs_cancel_subscription( $subscription_id, array(
                            'request_by'    => $requested_by,
                            'when'          => $requested_method,
                            'schedule_date' => $scheduled_date,
                        ) );
                    }
                    break;
            }

            wp_send_json( array( 'result' => 'success' ) );
        } catch ( Exception $e ) {
            wp_send_json( array(
                'result' => 'failure',
                'notice' => esc_html( $e->getMessage() ),
            ) );
        }
    }

    /**
     * Load Subscription Variation to be Switched in Admin Page and in My Account Page.
     */
    public static function get_subscription_variation_attributes_upon_switch() {
        check_ajax_referer( 'variation-swapping', 'security' );

        $posted                   = $_POST;
        $subscription_id          = absint( wp_unslash( $posted[ 'post_id' ] ) );
        $selected_attribute_key   = sanitize_title( wp_unslash( $posted[ 'selected_attribute_key' ] ) );
        $selected_attribute_value = wc_clean( wp_unslash( $posted[ 'selected_attribute_value' ] ) );
        $selected_attributes      = is_array( $posted[ 'selected_attributes' ] ) ? array_unique( $posted[ 'selected_attributes' ], SORT_REGULAR ) : array();
        $selected_attributes      = isset( $selected_attributes[ 0 ] ) ? $selected_attributes[ 0 ] : array();
        $matched_variation        = SUMOSubs_Variation_Switcher::get_matched_variation( $subscription_id, $selected_attributes );

        if ( empty( $matched_variation ) ) {
            $altered_attributes                            = array();
            $altered_attributes[ $selected_attribute_key ] = $selected_attribute_value;

            foreach ( $selected_attributes as $attribute_key => $attribute_value ) {
                if ( $attribute_key != $selected_attribute_key && $attribute_value != $selected_attribute_value ) {
                    $altered_attributes[ $attribute_key ] = $attribute_value;
                }
            }

            array_pop( $altered_attributes );

            $matched_variation = SUMOSubs_Variation_Switcher::get_matched_variation( $subscription_id, $altered_attributes );
            if ( empty( $matched_variation ) ) {
                $altered_attributes = array();
                $altered_attributes = array( $selected_attribute_key => $selected_attribute_value );
                $matched_variation  = SUMOSubs_Variation_Switcher::get_matched_variation( $subscription_id, $altered_attributes );
            }
        }

        wp_send_json( $matched_variation );
    }

    /**
     * Save Swapped Subscription Variation in Admin Page and in My Account Page.
     */
    public static function save_swapped_subscription_variation() {
        check_ajax_referer( 'save-swapped-variation', 'security' );

        $posted                 = $_POST;
        $subscription_id        = absint( wp_unslash( $posted[ 'post_id' ] ) );
        $subscription_meta      = sumo_get_subscription_meta( $subscription_id );
        $parent_order_item_data = get_post_meta( $subscription_id, 'sumo_subscription_parent_order_item_data', true );

        $parent_order                    = wc_get_order( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) );
        $subscriptions_from_parent_order = $parent_order->get_meta( 'sumo_subsc_get_available_postids_from_parent_order', true );
        $payment_info                    = $parent_order->get_meta( 'sumosubscription_payment_order_information', true );
        $response_code                   = '0';

        if ( isset( $subscription_meta[ 'productid' ] ) && is_array( $posted[ 'plan_matched_attributes_key' ] ) && is_array( $posted[ 'attribute_value_to_switch' ] ) && ! empty( $posted[ 'plan_matched_attributes_key' ] ) && ! empty( $posted[ 'attribute_value_to_switch' ] ) ) {
            $switch_variation_from = $subscription_meta[ 'productid' ];
            $swap_variation        = false;
            $attributes            = array();

            foreach ( $posted[ 'attribute_value_to_switch' ] as $each_attribute ) {
                $attributes[] = 'attribute_' . $each_attribute;
            }

            //Prevent if User/Admin not selecting Attribute values on Submit.
            if ( $attributes == $posted[ 'plan_matched_attributes_key' ] ) {
                wp_send_json( '2' );
            }

            //Get Variation ID from Variation attributes selected to switch by Admin/User.
            $new_variations         = array_combine( $posted[ 'plan_matched_attributes_key' ], $posted[ 'attribute_value_to_switch' ] );
            $matched_variation_id   = SUMOSubs_Variation_Switcher::get_matched_variation( $subscription_id, $new_variations, true );
            $switch_variation_to    = isset( $matched_variation_id[ 0 ] ) ? $matched_variation_id[ 0 ] : 0;
            $_switched_to_product   = wc_get_product( $switch_variation_to );
            $_switched_from_product = wc_get_product( $switch_variation_from );

            if ( $switch_variation_to > 0 ) {
                $orders           = array( $parent_order );
                $renewal_order_id = get_post_meta( $subscription_id, 'sumo_get_renewal_id', true );

                if ( ! empty( $renewal_order_id ) ) {
                    $renewal_order = wc_get_order( $renewal_order_id );

                    if ( ! sumosubs_is_order_paid( $renewal_order ) ) {
                        $orders[] = $renewal_order;
                    }
                }

                foreach ( $orders as $order ) {
                    foreach ( $order->get_items() as $item_id => $items ) {
                        $variation_id = sumosubs_wpml_maybe_get_translated_product_id( $items[ 'variation_id' ] );

                        //Update Parent Order Details
                        if ( $variation_id == $switch_variation_from && is_array( $_switched_to_product->get_variation_attributes() ) ) {
                            //Update New Variation.
                            wc_update_order_item_meta( $item_id, '_variation_id', $switch_variation_to );
                            //Update New Variation Attributes.
                            foreach ( $new_variations as $key => $value ) {
                                wc_update_order_item_meta( $item_id, str_replace( 'attribute_', '', $key ), $value );
                            }
                            //Is New Variation updated successfull in the Order item meta.
                            $swap_variation = wc_get_order_item_meta( $item_id, '_variation_id' ) == $switch_variation_to;
                        }
                    }
                }

                //Is Valid to process Variation Swap.
                if ( $swap_variation ) {
                    //Swap Variation.
                    unset( $subscriptions_from_parent_order[ $subscription_meta[ 'productid' ] ] );
                    $subscriptions_from_parent_order[ $switch_variation_to ] = absint( $subscription_id );

                    if ( isset( $payment_info[ $subscription_meta[ 'productid' ] ] ) ) {
                        $payment_info[ $switch_variation_to ] = $payment_info[ $subscription_meta[ 'productid' ] ];
                        unset( $payment_info[ $subscription_meta[ 'productid' ] ] );
                    }

                    if ( is_array( $parent_order_item_data ) ) {
                        foreach ( $parent_order_item_data as $order_item_id => $data ) {
                            if ( ! isset( $data[ 'id' ] ) ) {
                                continue;
                            }

                            if ( $subscription_meta[ 'productid' ] == $data[ 'id' ] ) {
                                $parent_order_item_data[ $order_item_id ][ 'id' ] = $switch_variation_to;
                            }
                        }
                    }

                    $subscription_meta[ 'productid' ] = $switch_variation_to;
                    update_post_meta( $subscription_id, 'sumo_subscription_product_details', $subscription_meta );
                    update_post_meta( $subscription_id, 'sumo_subscription_parent_order_item_data', $parent_order_item_data );
                    update_post_meta( $subscription_id, 'sumo_product_name', wc_get_product( $switch_variation_to )->get_title() );

                    $parent_order->update_meta_data( 'sumo_subsc_get_available_postids_from_parent_order', $subscriptions_from_parent_order );
                    $parent_order->update_meta_data( 'sumosubscription_payment_order_information', $payment_info );
                    $parent_order->save_meta_data();

                    /* translators: 1: switched by 2: from product name 3: to product name */
                    $note = sprintf( __( '%1$s switched the variation from <b>%2$s</b> to <b>%3$s</b>.', 'sumosubscriptions' ), wc_clean( wp_unslash( $posted[ 'switched_by' ] ) ), $_switched_from_product->get_formatted_name(), $_switched_to_product->get_formatted_name() );
                    sumo_add_subscription_note( $note, $subscription_id, 'success', __( 'Subscription variation switched', 'sumosubscriptions' ) );

                    //Success
                    $response_code = '1';
                }
            }
        }

        wp_send_json( $response_code );
    }

    /**
     * Init data export
     */
    public static function init_data_export() {
        check_ajax_referer( 'subscription-exporter', 'security' );

        $export_databy = array();
        $posted        = $_POST;
        parse_str( $posted[ 'exportDataBy' ], $export_databy );

        $json_args = array();
        $args      = array(
            'type'     => 'sumosubscriptions',
            'status'   => 'publish',
            'order_by' => 'DESC',
        );

        if ( ! empty( $export_databy ) ) {
            if ( ! empty( $export_databy[ 'subscription_from_date' ] ) ) {
                $to_date              = ! empty( $export_databy[ 'subscription_to_date' ] ) ? strtotime( $export_databy[ 'subscription_to_date' ] ) : strtotime( gmdate( 'Y-m-d' ) );
                $args[ 'date_query' ] = array(
                    array(
                        'after'     => gmdate( 'Y-m-d', strtotime( $export_databy[ 'subscription_from_date' ] ) ),
                        'before'    => array(
                            'year'  => gmdate( 'Y', $to_date ),
                            'month' => gmdate( 'm', $to_date ),
                            'day'   => gmdate( 'd', $to_date ),
                        ),
                        'inclusive' => true,
                    ),
                );
            }

            $meta_query = array();
            if ( ! empty( $export_databy[ 'subscription_statuses' ] ) ) {
                $meta_query[] = array(
                    'key'     => 'sumo_get_status',
                    'value'   => ( array ) $export_databy[ 'subscription_statuses' ],
                    'compare' => 'IN',
                );
            }

            if ( ! empty( $export_databy[ 'subscription_buyers' ] ) ) {
                $meta_query[] = array(
                    'key'     => 'sumo_buyer_email',
                    'value'   => ( array ) $export_databy[ 'subscription_buyers' ],
                    'compare' => 'IN',
                );
            }

            if ( ! empty( $meta_query ) ) {
                $args[ 'meta_query' ] = array( 'relation' => 'AND' ) + $meta_query;
            }
        }

        $subscriptions = sumosubscriptions()->query->get( $args );

        if ( count( $subscriptions ) <= 1 ) {
            if ( ! empty( $export_databy[ 'subscription_products' ] ) ) {
                foreach ( $subscriptions as $key => $subscription_id ) {
                    $subscription = sumo_get_subscription( $subscription_id );

                    if ( $subscription && ! in_array( $subscription->get_subscribed_product(), ( array ) $export_databy[ 'subscription_products' ] ) ) {
                        unset( $subscriptions[ $key ] );
                    }
                }
            }

            $json_args[ 'export' ]         = 'done';
            $json_args[ 'generated_data' ] = array_map( array( 'SUMOSubs_Admin_Subscriptions_Exporter', 'generate_data' ), $subscriptions );
            $json_args[ 'redirect_url' ]   = SUMOSubs_Admin_Subscriptions_Exporter::get_download_url( $json_args[ 'generated_data' ] );
        } else {
            $json_args[ 'export' ]        = 'processing';
            $json_args[ 'original_data' ] = $subscriptions;
        }

        wp_send_json( wp_parse_args( $json_args, array(
            'export'         => '',
            'generated_data' => array(),
            'original_data'  => array(),
            'redirect_url'   => SUMOSubs_Admin_Subscriptions_Exporter::get_exporter_page_url(),
        ) ) );
    }

    /**
     * Handle exported data
     */
    public static function handle_exported_data() {
        check_ajax_referer( 'subscription-exporter', 'security' );

        $export_databy = array();
        $posted        = $_POST;
        parse_str( $posted[ 'exportDataBy' ], $export_databy );

        $subscriptions = array_filter( ( array ) $posted[ 'chunkedData' ] );
        if ( ! empty( $export_databy[ 'subscription_products' ] ) ) {
            foreach ( $subscriptions as $key => $subscription_id ) {
                $subscription = sumo_get_subscription( $subscription_id );

                if ( $subscription && ! in_array( $subscription->get_subscribed_product(), ( array ) $export_databy[ 'subscription_products' ] ) ) {
                    unset( $subscriptions[ $key ] );
                }
            }
        }

        $json_args                     = array();
        $pre_generated_data            = json_decode( stripslashes( $posted[ 'generated_data' ] ) );
        $new_generated_data            = array_map( array( 'SUMOSubs_Admin_Subscriptions_Exporter', 'generate_data' ), $subscriptions );
        $json_args[ 'generated_data' ] = array_values( array_filter( array_merge( array_filter( ( array ) $pre_generated_data ), $new_generated_data ) ) );

        if ( absint( wp_unslash( $posted[ 'originalDataLength' ] ) ) === absint( wp_unslash( $posted[ 'step' ] ) ) ) {
            $json_args[ 'export' ]       = 'done';
            $json_args[ 'redirect_url' ] = SUMOSubs_Admin_Subscriptions_Exporter::get_download_url( $json_args[ 'generated_data' ] );
        }

        wp_send_json( wp_parse_args( $json_args, array(
            'export'         => 'processing',
            'generated_data' => array(),
            'original_data'  => array(),
            'redirect_url'   => SUMOSubs_Admin_Subscriptions_Exporter::get_exporter_page_url(),
        ) ) );
    }

    /**
     * Save order subscription.
     */
    public static function checkout_order_subscription() {
        check_ajax_referer( 'update-order-subscription', 'security' );

        $posted = $_POST;
        if ( 'yes' === wc_clean( wp_unslash( $posted[ 'subscribed' ] ) ) ) {
            WC()->session->set( 'sumo_is_order_subscription_subscribed', 'yes' );
            WC()->session->set( 'sumo_order_subscription_duration_period', wc_clean( wp_unslash( $posted[ 'subscription_duration' ] ) ) );
            WC()->session->set( 'sumo_order_subscription_duration_length', wc_clean( wp_unslash( $posted[ 'subscription_duration_value' ] ) ) );
            WC()->session->set( 'sumo_order_subscription_recurring_length', wc_clean( wp_unslash( $posted[ 'subscription_recurring' ] ) ) );
        } else {
            WC()->session->set( 'sumo_is_order_subscription_subscribed', 'no' );
            WC()->session->set( 'sumo_order_subscription_duration_period', '' );
            WC()->session->set( 'sumo_order_subscription_duration_length', '' );
            WC()->session->set( 'sumo_order_subscription_recurring_length', '' );
        }
        die();
    }

    /**
     * Process products bulk update.
     */
    public static function bulk_update_products() {
        check_ajax_referer( 'produts-bulk-update', 'security' );

        $posted = $_POST;
        $data   = array();
        parse_str( $posted[ 'data' ], $data );

        //Save the settings
        update_option( 'bulk_sumosubs_selected_bulk_type', wc_clean( $data[ 'selected_bulk_type' ] ) );
        update_option( 'bulk_sumosubs_selected_product_categories',  ! empty( $data[ 'selected_product_categories' ] ) ? wc_clean( $data[ 'selected_product_categories' ] ) : array()  );
        update_option( 'bulk_sumosubs_selected_products',  ! empty( $data[ 'selected_products' ] ) ? wc_clean( is_array( $data[ 'selected_products' ] ) ? $data[ 'selected_products' ] : explode( ',', $data[ 'selected_products' ] )  ) : array()  );

        foreach ( SUMOSubs_Admin_Product_Settings::get_subscription_fields() as $field_name => $type ) {
            $meta_key         = "sumo_{$field_name}";
            $posted_meta_data = isset( $data[ "$meta_key" ] ) ? $data[ "$meta_key" ] : '';

            if ( 'price' === $type ) {
                $posted_meta_data = wc_format_decimal( $posted_meta_data );
            } else if ( 'search' === $type ) {
                $posted_meta_data = ! is_array( $posted_meta_data ) ? array_filter( array_map( 'absint', explode( ',', $posted_meta_data ) ) ) : $posted_meta_data;
            }

            update_option( "bulk_{$meta_key}", wc_clean( $posted_meta_data ) );
        }

        $found_products = array();
        switch ( get_option( 'bulk_sumosubs_selected_bulk_type' ) ) {
            case 'all-products':
                $products = new WP_Query( array(
                    'post_type'      => array( 'product', 'product_variation' ),
                    'posts_per_page' => '-1',
                    'post_status'    => 'publish',
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => array( 'variable', 'grouped' ),
                            'operator' => 'NOT IN',
                        ),
                    ),
                        ) );

                if ( ! empty( $products->posts ) ) {
                    $found_products = $products->posts;
                }
                break;
            case 'selected-products':
                $found_products = array_map( 'absint', get_option( 'bulk_sumosubs_selected_products', array() ) );
                break;
            case 'selected-categories':
                $products       = new WP_Query( array(
                    'post_type'      => array( 'product', 'product_variation' ),
                    'post_status'    => 'publish',
                    'posts_per_page' => '-1',
                    'fields'         => 'ids',
                    'cache_results'  => false,
                    'tax_query'      => array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => array_map( 'absint', get_option( 'bulk_sumosubs_selected_product_categories', array() ) ),
                            'operator' => 'IN',
                        ),
                        array(
                            'taxonomy' => 'product_type',
                            'field'    => 'slug',
                            'terms'    => array( 'grouped' ),
                            'operator' => 'NOT IN',
                        ),
                    ),
                        ) );

                if ( ! empty( $products->posts ) ) {
                    $found_products = $products->posts;
                }
                break;
        }

        if ( empty( $found_products ) ) {
            wp_send_json_error( array(
                'productsCount' => 0,
            ) );
        }

        set_transient( 'sumosubs_found_products_to_bulk_update', $found_products, time() + 60 );

        $background_updates = get_option( 'sumosubs_background_updates', array() );
        $job_id             = WC()->queue()->schedule_single( time(), 'sumosubscriptions_find_products_to_bulk_update', array(), 'sumosubscriptions-product-bulk-updates' );

        if ( ! $job_id || ! is_numeric( $job_id ) ) {
            wp_send_json_error( array(
                'productsCount' => count( $found_products ),
            ) );
        }

        if ( WC()->queue()->get_next( 'sumosubscriptions_find_products_to_bulk_update', null, 'sumosubscriptions-product-bulk-updates' ) ) {
            $background_updates[ 'product_update' ] = array(
                'action_status'  => 'in_progress',
                'current_action' => 'sumosubscriptions_find_products_to_bulk_update',
                'next_action'    => 'sumosubscriptions_update_products_in_bulk',
                'action_group'   => 'sumosubscriptions-product-bulk-updates',
            );
        } else {
            unset( $background_updates[ 'product_update' ] );
        }

        update_option( 'sumosubs_background_updates', $background_updates );
        wp_send_json_success( array(
            'productsCount' => count( $found_products ),
        ) );
    }

    /**
     * Get HTML row of subscription as regular product row.
     */
    public static function get_subscription_as_regular_product_row() {
        check_ajax_referer( 'subscription-as-regular-product-row', 'security' );

        include_once 'admin/settings-page/class-sumosubs-admin-settings-advanced.php';

        $posted = $_POST;
        wp_send_json( array(
            'wc_product_search'        => sumosubs_wc_search_field( array(
                'class'       => 'wc-product-search',
                'action'      => 'sumosubscription_json_search_subscription_products_and_variations',
                'id'          => 'selected_subscription_' . $posted[ 'rowID' ],
                'name'        => 'selected_subscription[' . $posted[ 'rowID' ] . ']',
                'type'        => 'product',
                'selected'    => false,
                'placeholder' => __( 'Search for a subscription product&hellip;', 'sumosubscriptions' ),
                    ), false ),
            'wc_user_role_multiselect' => sumosubs_wc_enhanced_select_field( array(
                'id'      => 'selected_userrole_' . $posted[ 'rowID' ],
                'name'    => 'selected_userrole[' . $posted[ 'rowID' ] . ']',
                'options' => sumosubs_get_user_roles( true ),
                    ), false ),
        ) );
    }

    /**
     * JSON Search Product and Variations
     *
     * @param array $meta_query
     */
    public static function json_search_products_and_variations( $meta_query = array() ) {
        check_ajax_referer( 'search-products', 'security' );

        $get     = $_GET;
        $term    = ( string ) wc_clean( stripslashes( isset( $get[ 'term' ] ) ? $get[ 'term' ] : '' ) );
        $exclude = array();

        if ( isset( $get[ 'exclude' ] ) && ! empty( $get[ 'exclude' ] ) ) {
            $exclude = array_map( 'intval', explode( ',', $get[ 'exclude' ] ) );
        }

        $args = array(
            'post_type'      => array( 'product', 'product_variation' ),
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'order'          => 'ASC',
            'orderby'        => 'parent title',
            'meta_query'     => is_array( $meta_query ) ? $meta_query : array(),
            's'              => $term,
            'exclude'        => $exclude,
        );

        $posts          = get_posts( $args );
        $found_products = array();

        if ( ! empty( $posts ) ) {
            foreach ( $posts as $post ) {
                if ( ! current_user_can( 'read_product', $post->ID ) ) {
                    continue;
                }

                if ( class_exists( 'SUMOMemberships' ) && function_exists( 'sumo_is_membership_product' ) && sumo_is_membership_product( $post->ID ) ) {
                    continue;
                }

                $product                     = wc_get_product( $post->ID );
                $found_products[ $post->ID ] = $product->get_formatted_name();
            }
        }

        wp_send_json( $found_products );
    }

    /**
     * Search Subscription Products and Variations without SUMO Memberships products which are linked with.
     */
    public static function json_search_subscription_products_and_variations() {
        self::json_search_products_and_variations( array(
            array(
                'key'     => 'sumo_susbcription_status',
                'value'   => '1',
                'type'    => 'numeric',
                'compare' => '=',
            ),
        ) );
    }

    /**
     * Search Downloadable Non Subscription and Non Membership Products and Variations.
     */
    public static function json_search_downloadable_products_and_variations() {
        self::json_search_products_and_variations( array(
            array(
                'key'   => '_downloadable',
                'value' => 'yes',
            ),
            array(
                'key'     => 'sumo_susbcription_status',
                'value'   => '1',
                'compare' => '!=',
            ),
        ) );
    }

    /**
     * Search for customers by email and return json.
     */
    public static function json_search_customers_by_email() {
        ob_start();

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            wp_die( -1 );
        }

        $get   = $_GET;
        $term  = wc_clean( wp_unslash( $get[ 'term' ] ) );
        $limit = '';

        if ( empty( $term ) ) {
            wp_die();
        }

        $ids = array();
        // Search by ID.
        if ( is_numeric( $term ) ) {
            $customer = new WC_Customer( intval( $term ) );

            // Customer does not exists.
            if ( 0 !== $customer->get_id() ) {
                $ids = array( $customer->get_id() );
            }
        }

        // Usernames can be numeric so we first check that no users was found by ID before searching for numeric username, this prevents performance issues with ID lookups.
        if ( empty( $ids ) ) {
            $data_store = WC_Data_Store::load( 'customer' );

            // If search is smaller than 3 characters, limit result set to avoid
            // too many rows being returned.
            if ( 3 > strlen( $term ) ) {
                $limit = 20;
            }
            $ids = $data_store->search_customers( $term, $limit );
        }

        $found_customers = array();
        if ( ! empty( $get[ 'exclude' ] ) ) {
            $ids = array_diff( $ids, ( array ) $get[ 'exclude' ] );
        }

        foreach ( $ids as $id ) {
            $customer                                  = new WC_Customer( $id );
            $found_customers[ $customer->get_email() ] = sprintf(
                    /* translators: 1: user display name 2: user ID 3: user email */
                    esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'sumosubscriptions' ), $customer->get_first_name() . ' ' . $customer->get_last_name(), $customer->get_id(), $customer->get_email()
            );
        }

        wp_send_json( $found_customers );
    }

    /**
     * Process subscriptions bulk update.
     * 
     * @since 15.5.0
     */
    public static function bulk_update_subscriptions() {
        check_ajax_referer( 'subscriptions-bulk-update', 'security' );

        $posted = $_POST;
        $data   = array();
        parse_str( $posted[ 'data' ], $data );

        if ( empty( $data[ '_deleted_product' ] ) || empty( $data[ '_replace_product' ] ) ) {
            wp_send_json_error( array(
                'itemsCount'  => 0,
                'errorNotice' => __( 'Something went wrong while replacing the product with the deleted product!!', 'sumosubscriptions' ),
            ) );
        }

        $deleted_product_id = absint( $data[ '_deleted_product' ] );
        $replace_product_id = absint( current( $data[ '_replace_product' ] ) );
        $replace_product    = wc_get_product( $replace_product_id );
        $deleted_product    = wc_get_product( $deleted_product_id );

        if ( $deleted_product || ! $replace_product ) {
            wp_send_json_error( array(
                'itemsCount'  => 0,
                'errorNotice' => __( 'Something went wrong while replacing the product with the deleted product!!', 'sumosubscriptions' ),
            ) );
        }

        $product_id             = 0;
        $found_subscription_ids = array();
        $subscription_ids       = sumosubs_get_subscription_ids();
        if ( empty( $subscription_ids ) ) {
            wp_send_json_error( array(
                'itemsCount'  => 0,
                'errorNotice' => __( 'No subscriptions found!!', 'sumosubscriptions' ),
            ) );
        }

        foreach ( $subscription_ids as $subscription_id ) {
            $subscription = sumo_get_subscription_plan( $subscription_id );
            $product_id   = $subscription[ 'subscription_product_id' ];

            if ( $deleted_product_id === $product_id ) {
                $found_subscription_ids[] = $subscription_id;
            }
        }

        if ( empty( $found_subscription_ids ) ) {
            wp_send_json_error( array(
                'itemsCount'  => 0,
                'errorNotice' => __( 'No subscriptions found!!', 'sumosubscriptions' ),
            ) );
        }

        set_transient( 'sumosubs_update_found_subscriptions', $found_subscription_ids, time() + 60 );

        $background_updates = get_option( 'sumosubs_background_updates', array() );
        $args               = array( 'deleted_product_id' => $deleted_product_id, 'replace_product_id' => $replace_product_id );
        $job_id             = WC()->queue()->schedule_single( time(), 'sumosubscriptions_find_subscriptions_to_bulk_update', $args, 'sumosubs-subscription-bulk-updates' );

        if ( ! $job_id || ! is_numeric( $job_id ) ) {
            wp_send_json_error( array(
                'itemsCount'  => count( $found_subscription_ids ),
                'errorNotice' => __( 'Something went wrong while replacing the product with the deleted product!!', 'sumosubscriptions' ),
            ) );
        }

        if ( WC()->queue()->get_next( 'sumosubscriptions_find_subscriptions_to_bulk_update', $args, 'sumosubs-subscription-bulk-updates' ) ) {
            $background_updates[ 'subscription_update' ] = array(
                'action_status'  => 'in_progress',
                'current_action' => 'sumosubscriptions_find_subscriptions_to_bulk_update',
                'next_action'    => 'sumosubscriptions_update_subscriptions_in_bulk',
                'action_group'   => 'sumosubs-subscription-bulk-updates'
            );
        } else {
            unset( $background_updates[ 'subscription_update' ] );
        }

        update_option( 'sumosubs_background_updates', $background_updates );
        wp_send_json_success( array(
            'itemsCount'    => count( $found_subscription_ids ),
            /* translators: 1: subscriptions count */
            'successNotice' => sprintf( __( '%s subscription(s) found. Product is replacing with the deleted product in the background. The product replace process may take a little while, so please be patient.', 'sumosubscriptions' ), count( $subscription_ids ) ),
        ) );
    }

}

SUMOSubs_Ajax::init();
