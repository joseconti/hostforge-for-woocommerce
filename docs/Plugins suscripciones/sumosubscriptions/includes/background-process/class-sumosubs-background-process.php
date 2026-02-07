<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handle subscription background process.
 * 
 * @class SUMOSubs_Background_Process
 */
class SUMOSubs_Background_Process {

    /**
     * Cron Interval in Seconds.
     * 
     * @var int
     */
    private static $cron_interval = SUMO_SUBSCRIPTIONS_CRON_INTERVAL;

    /**
     * Cron hook identifier
     *
     * @var mixed
     */
    protected static $cron_hook_identifier;

    /**
     * Cron interval identifier
     *
     * @var mixed
     */
    protected static $cron_interval_identifier;

    /**
     * Get the cached order ID which is in progress.
     * 
     * @var array
     */
    protected static $order_in_progress = array();

    /**
     * Init SUMOSubs_Background_Process
     */
    public static function init() {
        self::$cron_hook_identifier     = 'sumosubscriptions_background_updater';
        self::$cron_interval_identifier = 'sumosubscriptions_cron_interval';

        self::schedule_event();
        self::handle_cron_healthcheck();
    }

    /**
     * Schedule event
     */
    public static function schedule_event() {
        //may be preventing the recurrence Cron interval not to be greater than SUMO_SUBSCRIPTIONS_CRON_INTERVAL
        if ( ( wp_next_scheduled( self::$cron_hook_identifier ) - sumo_get_subscription_timestamp() ) > self::$cron_interval ) {
            self::cancel();
        }

        //Schedule Recurrence Cron job
        if ( ! wp_next_scheduled( self::$cron_hook_identifier ) ) {
            wp_schedule_event( sumo_get_subscription_timestamp() + self::$cron_interval, self::$cron_interval_identifier, self::$cron_hook_identifier );
        }
    }

    /**
     * Handle cron healthcheck
     */
    public static function handle_cron_healthcheck() {
        //Fire when Recurrence cron gets elapsed
        add_action( self::$cron_hook_identifier, array( __CLASS__, 'run' ) );

        // Fire Scheduled Cron Hooks.
        $jobs = array(
            'start_subscription'               => 'start_subscription',
            'create_renewal_order'             => 'create_renewal_order',
            'notify_invoice_reminder'          => 'remind_invoice',
            'notify_expiry_reminder'           => 'remind_expiry',
            'notify_overdue'                   => 'set_overdue',
            'notify_suspend'                   => 'set_suspend',
            'notify_cancel'                    => 'set_cancel',
            'notify_expire'                    => 'set_expire',
            'automatic_pay'                    => 'automatic_pay',
            'automatic_resume'                 => 'automatic_resume',
            'retry_automatic_pay_in_overdue'   => 'retry_automatic_pay',
            'retry_automatic_pay_in_suspended' => 'retry_automatic_pay',
        );

        foreach ( $jobs as $job_name => $job_callback ) {
            add_action( "sumosubscriptions_fire_{$job_name}", __CLASS__ . "::{$job_callback}" );
        }

        add_action( 'sumosubscriptions_find_products_to_bulk_update', __CLASS__ . '::find_products_to_bulk_update' );
        add_action( 'sumosubscriptions_update_products_in_bulk', __CLASS__ . '::update_products_in_bulk' );

        add_action( 'sumosubscriptions_find_subscriptions_to_bulk_update', __CLASS__ . '::find_subscriptions_to_bulk_update', 10, 2 );
        add_action( 'sumosubscriptions_update_subscriptions_in_bulk', __CLASS__ . '::update_subscriptions_in_bulk', 10, 3 );
    }

    /**
     * Fire when recurrence Cron gets Elapsed
     * 
     * Background process.
     */
    public static function run() {
        $crons = sumosubscriptions()->query->get( array(
            'type'   => 'sumosubs_cron_events',
            'status' => 'publish',
                ) );

        if ( empty( $crons ) ) {
            return;
        }

        //Loop through each Cron Event Query post and check whether time gets elapsed
        foreach ( $crons as $cron_id ) {
            $cron_events = get_post_meta( $cron_id, '_sumo_subscription_cron_events', true );
            if ( ! is_array( $cron_events ) ) {
                continue;
            }

            foreach ( $cron_events as $subscription_id => $events ) {
                foreach ( $events as $_event_name => $args ) {
                    if ( ! is_array( $args ) ) {
                        continue;
                    }

                    foreach ( $args as $event_timestamp => $event_args ) {
                        if ( ! is_int( $event_timestamp ) || ! $event_timestamp ) {
                            continue;
                        }

                        if ( sumo_get_subscription_timestamp() >= $event_timestamp ) {
                            /**
                             * When the time gets elapsed then fire the Subscription Event Hooks.
                             * 
                             * @since 1.0
                             */
                            do_action( "sumosubscriptions_fire_{$_event_name}", array_merge( array(
                                'subscription_id' => $subscription_id,
                                            ), $event_args ) );

                            //Refresh post.
                            $cron_events = get_post_meta( $cron_id, '_sumo_subscription_cron_events', true );

                            //Clear the Event when the corresponding Subscription Event Hook gets fired.
                            if ( did_action( "sumosubscriptions_fire_{$_event_name}" ) ) {
                                unset( $cron_events[ $subscription_id ][ $_event_name ][ $event_timestamp ] );
                            }
                        }
                    }
                    //Flush the meta once the timestamp is not available for the specific Event
                    if ( empty( $cron_events[ $subscription_id ][ $_event_name ] ) ) {
                        unset( $cron_events[ $subscription_id ][ $_event_name ] );
                    }
                }
            }
            //Get updated post.
            if ( is_array( $cron_events ) ) {
                update_post_meta( $cron_id, '_sumo_subscription_cron_events', $cron_events );
            }
        }
    }

    /**
     * Cancel Process
     */
    public static function cancel() {
        wp_clear_scheduled_hook( self::$cron_hook_identifier );
    }

    /**
     * Find subscriptions to update in bulk
     * 
     * @since 15.5.0
     * @param int $deleted_product_id Deleted Product ID
     * @param int $replace_product_id Replace Product ID
     */
    public static function find_subscriptions_to_bulk_update( $deleted_product_id, $replace_product_id ) {
        $subscription_ids = get_transient( 'sumosubs_update_found_subscriptions' );
        if ( empty( $subscription_ids ) || ! is_array( $subscription_ids ) ) {
            return;
        }

        $subscription_ids = array_filter( array_chunk( $subscription_ids, 10 ) );
        foreach ( $subscription_ids as $index => $chunked_payments ) {
            WC()->queue()->schedule_single(
                    time() + $index, 'sumosubscriptions_update_subscriptions_in_bulk', array(
                'subscriptions'      => $chunked_payments,
                'deleted_product_id' => $deleted_product_id,
                'replace_product_id' => $replace_product_id,
                    ), 'sumosubs-subscription-bulk-updates' );
        }
    }

    /**
     * Start bulk update of subscriptions.
     * 
     * @since 15.5.0
     * @param array $subscription_ids Subscription Ids
     * @param int $deleted_product_id Deleted Product ID
     * @param int $replace_product_id Replace Product ID
     */
    public static function update_subscriptions_in_bulk( $subscription_ids, $deleted_product_id, $replace_product_id ) {
        $replace_product = wc_get_product( $replace_product_id );
        if ( ! $replace_product ) {
            return;
        }

        foreach ( $subscription_ids as $subscription_id ) {
            $subscription_meta = sumo_get_subscription_meta( $subscription_id );
            if ( ! $subscription_meta ) {
                continue;
            }

            $parent_order_id  = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );
            $old_parent_order = wc_get_order( $parent_order_id );
            if ( ! $old_parent_order ) {
                continue;
            }
            
            $parent_order_item_data          = get_post_meta( $subscription_id, 'sumo_subscription_parent_order_item_data', true );
            $subscriptions_from_parent_order = $old_parent_order->get_meta( 'sumo_subsc_get_available_postids_from_parent_order', true );
            $payment_info                    = $old_parent_order->get_meta( 'sumosubscription_payment_order_information', true );

            unset( $subscriptions_from_parent_order[ $subscription_meta[ 'productid' ] ] );
            $subscriptions_from_parent_order[ $replace_product_id ] = absint( $subscription_id );

            if ( isset( $payment_info[ $subscription_meta[ 'productid' ] ] ) ) {
                $payment_info[ $replace_product_id ] = $payment_info[ $subscription_meta[ 'productid' ] ];
                unset( $payment_info[ $subscription_meta[ 'productid' ] ] );
            }

            foreach ( $old_parent_order->get_items() as $item_id => $item ) {
                $old_product_id                             = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ];
                $parent_order_item_data[ $item_id ][ 'id' ] = $replace_product_id;
                
				if ( empty( $old_product_id ) || $old_product_id == $deleted_product_id ) {
                    $item->set_product_id( $replace_product_id );
                    $item->set_name( $replace_product->get_name() );
                    $item->save();
                }
            }

            $subscription_meta[ 'productid' ] = $replace_product_id;
            update_post_meta( $subscription_id, 'sumo_subscription_product_details', $subscription_meta );
            update_post_meta( $subscription_id, 'sumo_subscription_parent_order_item_data', $parent_order_item_data );
            update_post_meta( $subscription_id, 'sumo_product_name', wc_get_product( $replace_product_id )->get_title() );

            $old_parent_order->update_meta_data( 'sumo_subsc_get_available_postids_from_parent_order', $subscriptions_from_parent_order );
            $old_parent_order->update_meta_data( 'sumosubscription_payment_order_information', $payment_info );
            $old_parent_order->save_meta_data();

            $old_renewal_order_id        = get_post_meta( $subscription_id, 'sumo_get_renewal_id', true );
            $unpaid_renewal_order_exists = sumosubs_unpaid_renewal_order_exists( $subscription_id );
            if ( $unpaid_renewal_order_exists ) {
                wc_get_order( $old_renewal_order_id )->delete( 'true' );
                $renewal_order_id = SUMOSubs_Order::create_renewal_order( $parent_order_id, $subscription_id );
                sumo_add_subscription_note( sprintf( __( 'Renewal order #%1$s has been deleted and replaced with a new order #%2$s.', 'sumosubscriptions' ), $old_renewal_order_id, $renewal_order_id ), $subscription_id, 'success', __( 'Renewal Order Created', 'sumosubscriptions' ) );
            }
        }
    }

    /**
     * Find products to update in bulk.
     */
    public static function find_products_to_bulk_update() {
        $found_products = get_transient( 'sumosubs_found_products_to_bulk_update' );

        if ( empty( $found_products ) || ! is_array( $found_products ) ) {
            return;
        }

        $found_products = array_filter( array_chunk( $found_products, 10 ) );
        foreach ( $found_products as $index => $chunked_products ) {
            WC()->queue()->schedule_single(
                    time() + $index, 'sumosubscriptions_update_products_in_bulk', array(
                'products' => $chunked_products,
                    ), 'sumosubscriptions-product-bulk-updates' );
        }
    }

    /**
     * Start bulk updation of products.
     */
    public static function update_products_in_bulk( $products ) {
        $product_props = array();

        foreach ( SUMOSubs_Admin_Product_Settings::get_subscription_fields() as $field_name => $type ) {
            $meta_key                   = "sumo_{$field_name}";
            $product_props[ $meta_key ] = get_option( "bulk_{$meta_key}" );
        }

        foreach ( $products as $product_id ) {
            $_product = wc_get_product( $product_id );

            if ( ! $_product ) {
                continue;
            }

            switch ( $_product->get_type() ) {
                case 'simple':
                case 'variation':
                    SUMOSubs_Admin_Product_Settings::save_meta( $product_id, '', $product_props );
                    break;
                case 'variable':
                    $variations = get_children( array(
                        'post_parent' => $product_id,
                        'post_type'   => 'product_variation',
                        'fields'      => 'ids',
                        'post_status' => array( 'publish', 'private' ),
                        'numberposts' => -1,
                            ) );

                    if ( empty( $variations ) ) {
                        continue 2;
                    }

                    foreach ( $variations as $variation_id ) {
                        if ( $variation_id ) {
                            SUMOSubs_Admin_Product_Settings::save_meta( $variation_id, '', $product_props );
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Start the subscription
     *
     * @param array $args
     */
    public static function start_subscription( $args ) {
        $args = wp_parse_args(
                $args,
                array(
                    'subscription_id' => 0,
                )
        );

        /**
         * Can restrict subscription product when outofstock.
         *
         * @since 15.6.0
         * @param $subscription_id subscription ID
         * @return bool
         */
        if ( apply_filters( 'sumosubscriptions_can_restrict_subscription_product_when_outofstock', false, $args[ 'subscription_id' ] ) ) {
            return;
        }

        if ( sumo_is_subscription_exists( $args[ 'subscription_id' ] ) && 'Pending' === get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ) ) {
            $parent_order_id = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_parent_order_id', true );

            SUMOSubs_Order::maybe_activate_subscription( $args[ 'subscription_id' ], $parent_order_id, 'pending', 'active', true );
        }
    }

    /**
     * Create Renewal Order for the Subscription
     *
     * @param array $args
     */
    public static function create_renewal_order( $args ) {
        $args = wp_parse_args( $args, array(
            'subscription_id' => 0,
            'next_due_on'     => '',
                ) );

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) || ! in_array( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ), array( 'Active', 'Trial', 'Pending' ) ) ) {
            return;
        }

        /**
         * Can restrict subscription product when outofstock.
         *
         * @since 15.6.0
         * @param $subscription_id subscription ID
         * @return bool
         */
        if ( apply_filters( 'sumosubscriptions_can_restrict_subscription_product_when_outofstock', false, $args[ 'subscription_id' ] ) ) {
            return;
        }

        $cron_event           = new SUMOSubs_Cron_Event( $args[ 'subscription_id' ] );
        $new_renewal_order_id = absint( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_renewal_id', true ) );

        //may be existing Renewal Payment is completed.
        $unpaid_renewal_order_exists = sumosubs_unpaid_renewal_order_exists( $args[ 'subscription_id' ] );
        if ( ! $unpaid_renewal_order_exists ) {
            $new_renewal_order_id = SUMOSubs_Order::create_renewal_order( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_parent_order_id', true ), $args[ 'subscription_id' ] );
        }

        switch ( sumo_get_payment_type( $args[ 'subscription_id' ] ) ) {
            case 'auto':
                $cron_event->schedule_automatic_pay( $new_renewal_order_id );

                if ( ! $unpaid_renewal_order_exists ) {
                    $cron_event->schedule_reminders( $new_renewal_order_id, $args[ 'next_due_on' ], '', 'subscription_auto_renewal_reminder' );
                }
                break;
            case 'manual':
                $cron_event->schedule_next_eligible_payment_failed_status( 0, 0, $args[ 'next_due_on' ] );

                if ( ! $unpaid_renewal_order_exists ) {
                    $cron_event->schedule_reminders( $new_renewal_order_id, $args[ 'next_due_on' ] );
                }
                break;
        }
    }

    /**
     * Create Multiple Invoice Reminder
     *
     * @param array $args
     */
    public static function remind_invoice( $args ) {
        $args = wp_parse_args( $args, array(
            'subscription_id'  => 0,
            'renewal_order_id' => 0,
            'mail_template_id' => 'subscription_invoice',
                ) );

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return;
        }
       
        $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] );
        if ( ! $renewal_order ) {
            return;
        }

        if ( sumosubs_is_order_paid( $renewal_order ) ) {
            return;
        }

        switch ( $args[ 'mail_template_id' ] ) {
            case 'subscription_invoice':
            case 'subscription_auto_renewal_reminder':
            case 'subscription_pending_authorization':
                if ( $renewal_order->get_total() > 0 && in_array( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ), array( 'Active', 'Trial', 'Pending', 'Pending_Authorization' ) ) ) {
                    sumo_trigger_subscription_email( $args[ 'mail_template_id' ], $args[ 'renewal_order_id' ], $args[ 'subscription_id' ] );
                }
                break;
            case 'subscription_overdue_automatic':
            case 'subscription_suspended_automatic':
            case 'subscription_overdue_manual':
            case 'subscription_suspended_manual':
                if ( $renewal_order->get_total() > 0 && in_array( get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ), array( 'Overdue', 'Suspended' ) ) ) {
                    sumo_trigger_subscription_email( $args[ 'mail_template_id' ], $args[ 'renewal_order_id' ], $args[ 'subscription_id' ] );
                }
                break;
        }
    }

    /**
     * Create Multiple Expiry Reminders
     *
     * @param array $args
     */
    public static function remind_expiry( $args ) {
        $args = wp_parse_args( $args, array(
            'subscription_id'  => 0,
            'mail_template_id' => 'subscription_expiry_reminder',
                ) );

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return;
        }        

        if ( 'Active' === get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ) ) {
            sumo_trigger_subscription_email( $args[ 'mail_template_id' ], 0, $args[ 'subscription_id' ] );
        }
    }

    /**
     * Set Subscription status as Pending_Authorization
     *
     * @param array $args
     */
    public static function set_pending_authorization( $args ) {
        $args = wp_parse_args( $args, array(
            'subscription_id'       => 0,
            'renewal_order_id'      => 0,
            'payment_charging_days' => 0,
                ) );

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return;
        }

        /**
         * Can restrict subscription product when outofstock.
         *
         * @since 15.6.0
         * @param $subscription_id subscription ID
         * @return bool
         */
        if ( apply_filters( 'sumosubscriptions_can_restrict_subscription_product_when_outofstock', false, $args[ 'subscription_id' ] ) ) {
            return;
        }

        $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] );
        if ( ! $renewal_order ) {
            return;
        }

        if ( sumosubs_is_order_paid( $renewal_order ) ) {
            return;
        }

        update_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', 'Pending_Authorization' );

        sumo_add_subscription_note( __( 'Since the renewal payment has not been paid so far, subscription status moved to Pending Authorization.', 'sumosubscriptions' ), $args[ 'subscription_id' ], sumo_note_status( 'Pending' ), __( 'Subscription Pending Authorization', 'sumosubscriptions' ) );

        $next_due_on   = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_next_payment_date', true );
        $remind_before = sumo_get_subscription_timestamp( $next_due_on ) + ( $args[ 'payment_charging_days' ] * 86400 );

        $cron_event = new SUMOSubs_Cron_Event( $args[ 'subscription_id' ] );
        $cron_event->unset_events();
        $cron_event->schedule_next_eligible_payment_failed_status( $args[ 'payment_charging_days' ] );
        $cron_event->schedule_reminders( $args[ 'renewal_order_id' ], $remind_before, sumo_get_subscription_timestamp(), 'subscription_pending_authorization' );

        /**
         * After subscription is in pending auth.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_status_in_pending_authorization', $args[ 'subscription_id' ] );

        /**
         * Subscription active here.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_active_subscription', $args[ 'subscription_id' ], $args[ 'renewal_order_id' ] );
    }

    /**
     * Set Subscription status as Overdue
     *
     * @param array $args
     */
    public static function set_overdue( $args ) {
        $args = wp_parse_args( $args, array(
            'subscription_id'             => 0,
            'renewal_order_id'            => 0,
            'next_due_on'                 => '',
            'payment_charging_days'       => 0,
            'payment_retry_times_per_day' => sumosubs_get_payment_retry_times_per_day_in( 'Overdue' ),
                ) );

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return;
        }

        /**
         * Can restrict subscription product when outofstock.
         *
         * @since 15.6.0
         * @param $subscription_id subscription ID
         * @return bool
         */
        if ( apply_filters( 'sumosubscriptions_can_restrict_subscription_product_when_outofstock', false, $args[ 'subscription_id' ] ) ) {
            return;
        }

        $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] );
        if ( ! $renewal_order ) {
            return;
        }

        if ( sumosubs_is_order_paid( $renewal_order ) ) {
            return;
        }

        if ( $renewal_order->get_total() <= 0 ) {
            //Auto Renew the Subscription.
            $renewal_order->payment_complete();
            return;
        }

        update_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', 'Overdue' );

        $mail_template = 'auto' === sumo_get_payment_type( $args[ 'subscription_id' ] ) ? 'subscription_overdue_automatic' : 'subscription_overdue_manual';
        sumo_add_subscription_note( __( 'Since the renewal payment has not been paid so far, subscription status moved to Overdue.', 'sumosubscriptions' ), $args[ 'subscription_id' ], sumo_note_status( 'Overdue' ), __( 'Subscription Overdue', 'sumosubscriptions' ) );

        $remind_before = sumo_get_subscription_timestamp() + ( $args[ 'payment_charging_days' ] * 86400 );

        $cron_event = new SUMOSubs_Cron_Event( $args[ 'subscription_id' ] );
        $cron_event->unset_events();
        $cron_event->schedule_next_eligible_payment_failed_status( $args[ 'payment_charging_days' ], $args[ 'payment_retry_times_per_day' ] );
        $cron_event->schedule_reminders( $args[ 'renewal_order_id' ], $remind_before, sumo_get_subscription_timestamp(), $mail_template );

        if ( apply_filters( 'sumosubscriptions_send_instant_email_overdue', true, $args ) ) {
            sumo_trigger_subscription_email( $mail_template, $args[ 'renewal_order_id' ], $args[ 'subscription_id' ] );
        }

        /**
         * Subscription active still.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_active_subscription', $args[ 'subscription_id' ], $args[ 'renewal_order_id' ] );
    }

    /**
     * Set Subscription status as Suspended
     *
     * @param array $args
     */
    public static function set_suspend( $args ) {
        $args = wp_parse_args( $args, array(
            'subscription_id'             => 0,
            'renewal_order_id'            => 0,
            'next_due_on'                 => '',
            'payment_charging_days'       => 0,
            'payment_retry_times_per_day' => 0,
                ) );

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return;
        }

        /**
         * Can restrict subscription product when outofstock.
         *
         * @since 15.6.0
         * @param $subscription_id subscription ID
         * @return bool
         */
        if ( apply_filters( 'sumosubscriptions_can_restrict_subscription_product_when_outofstock', false, $args[ 'subscription_id' ] ) ) {
            return;
        }

        $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] );
        if ( ! $renewal_order ) {
            return;
        }

        if ( sumosubs_is_order_paid( $renewal_order ) ) {
            return;
        }

        if ( $renewal_order->get_total() <= 0 ) {
            //Auto Renew the Subscription.
            $renewal_order->payment_complete();
            return;
        }

        update_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', 'Suspended' );

        $mail_template = 'auto' === sumo_get_payment_type( $args[ 'subscription_id' ] ) ? 'subscription_suspended_automatic' : 'subscription_suspended_manual';
        sumo_add_subscription_note( __( 'Since the renewal payment has not been paid so far, subscription status moved to Suspended.', 'sumosubscriptions' ), $args[ 'subscription_id' ], sumo_note_status( 'Suspended' ), __( 'Subscription Suspended', 'sumosubscriptions' ) );

        $remind_before = sumo_get_subscription_timestamp() + ( $args[ 'payment_charging_days' ] * 86400 );

        $cron_event = new SUMOSubs_Cron_Event( $args[ 'subscription_id' ] );
        $cron_event->unset_events();
        $cron_event->schedule_next_eligible_payment_failed_status( $args[ 'payment_charging_days' ], $args[ 'payment_retry_times_per_day' ] );
        $cron_event->schedule_reminders( $args[ 'renewal_order_id' ], $remind_before, sumo_get_subscription_timestamp(), $mail_template );

        sumo_trigger_subscription_email( $mail_template, $args[ 'renewal_order_id' ], $args[ 'subscription_id' ] );

        /**
         * Subscription not active here.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_pause_subscription', $args[ 'subscription_id' ], $args[ 'renewal_order_id' ] );
    }

    /**
     * Set Subscription status as Cancelled
     *
     * @param array $args
     */
    public static function set_cancel( $args ) {
        $args = wp_parse_args( $args, array(
            'subscription_id'  => 0,
            'renewal_order_id' => 0,
            'force_cancel'     => false,
                ) );

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return;
        }

        //BKWD CMPT
        $args[ 'renewal_order_id' ] = absint( $args[ 'renewal_order_id' ] > 0 ? $args[ 'renewal_order_id' ] : get_post_meta( $args[ 'subscription_id' ], 'sumo_get_renewal_id', true ) );

        if ( ! $args[ 'force_cancel' ] ) {
            $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] );
            if ( ! $renewal_order ) {
                return;
            }

            if ( sumosubs_is_order_paid( $renewal_order ) ) {
                return;
            }

            if ( $renewal_order->get_total() <= 0 ) {
                //Auto Renew the Subscription.
                $renewal_order->payment_complete();
                return;
            }
        }

        //Cancel Subscription.
        sumosubs_cancel_subscription( $args[ 'subscription_id' ], array(
            'note' => __( 'Subscription automatically Cancelled.', 'sumosubscriptions' ),
        ) );
    }

    /**
     * Set Subscription status as Expired
     *
     * @param array $args
     */
    public static function set_expire( $args ) {
        $args = wp_parse_args( $args, array(
            'subscription_id'  => 0,
            'renewal_order_id' => 0,
            'expiry_on'        => '',
                ) );

        if ( sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            //Expire Subscription.
            sumo_expire_subscription( $args[ 'subscription_id' ], $args[ 'expiry_on' ] );
        }
    }

    /**
     * Charge Automatic Payments.
     *
     * @param array $args
     */
    public static function automatic_pay( $args ) {
        $args = wp_parse_args( $args, array(
            'subscription_id'             => 0,
            'parent_order_id'             => 0,
            'renewal_order_id'            => 0,
            'payment_charging_days'       => 0,
            'payment_retry_times_per_day' => 0,
            'next_eligible_status'        => '',
                ) );

        if ( ! sumo_is_subscription_exists( $args[ 'subscription_id' ] ) ) {
            return;
        }

        /**
         * Can restrict subscription product when outofstock.
         *
         * @since 15.6.0
         * @param $subscription_id subscription ID
         * @return bool
         */
        if ( apply_filters( 'sumosubscriptions_can_restrict_subscription_product_when_outofstock', false, $args[ 'subscription_id' ] ) ) {
            return;
        }

        $args[ 'renewal_order_id' ] = absint( $args[ 'renewal_order_id' ] > 0 ? $args[ 'renewal_order_id' ] : get_post_meta( $args[ 'subscription_id' ], 'sumo_get_renewal_id', true ) );
        $next_due_date              = get_post_meta( $args[ 'subscription_id' ], 'sumo_get_next_payment_date', true );

        $renewal_order = wc_get_order( $args[ 'renewal_order_id' ] );
        if ( ! $renewal_order ) {
            return;
        }

        if ( sumosubs_is_order_paid( $renewal_order ) ) {
            return;
        }

        if ( isset( self::$order_in_progress[ $renewal_order->get_id() ] ) ) {
            return; // Bail if the order is in progress already.
        }

        self::$order_in_progress[ $renewal_order->get_id() ] = true;

        if ( sumo_is_next_renewal_possible( $args[ 'subscription_id' ] ) ) {
            /**
             * Fire to get Preapproval Status.
             * 
             * @since 1.0
             */
            do_action( 'sumosubscriptions_process_preapproval_status', $args[ 'subscription_id' ], $args[ 'parent_order_id' ], $args );

            if ( SUMOSubs_Preapproval::is_valid( $args[ 'subscription_id' ], $renewal_order ) ) {
                if ( $renewal_order->get_total() > 0 ) {
                    /**
                     * Trigger to get Preapproved Payment Transaction Status.
                     * 
                     * @since 1.0
                     */
                    do_action( 'sumosubscriptions_process_preapproved_payment_transaction', $args[ 'subscription_id' ], $args[ 'parent_order_id' ], $args );
                }

                if ( SUMOSubs_Preapproval::is_payment_txn_success( $args[ 'subscription_id' ], $renewal_order ) ) {
                    /**
                     * Fire after Preapproved payment Transaction is Successfully.
                     * 
                     * @since 1.0
                     */
                    do_action( 'sumosubscriptions_preapproved_payment_transaction_success', $args );
                } else {
                    /**
                     * Fire after Preapproved payment Transaction is Failed.
                     * 
                     * @since 1.0
                     */
                    do_action( 'sumosubscriptions_preapproved_payment_transaction_failed', $args );
                }
            } else {
                /**
                 * Fire after Preapproved access is revoked.
                 * 
                 * @since 1.0
                 */
                do_action( 'sumosubscriptions_preapproved_access_is_revoked', $args );
            }
        } else {
            //Schedule to Expire Subscription. 
            $cron_event = new SUMOSubs_Cron_Event( $args[ 'subscription_id' ] );
            $cron_event->schedule_expire_notify( $next_due_date, $args[ 'renewal_order_id' ] );

            update_post_meta( $args[ 'subscription_id' ], 'sumo_get_saved_due_date', $next_due_date );
            update_post_meta( $args[ 'subscription_id' ], 'sumo_get_next_payment_date', '--' );
        }
    }

    /**
     * Automatically Resume the Subscription based on the Admin specified interval. May be valid for Subscribers
     *
     * @param array $args
     */
    public static function automatic_resume( $args ) {
        /**
         * Can restrict subscription product when outofstock.
         *
         * @since 15.6.0
         * @param $subscription_id subscription ID
         * @return bool
         */
        if ( apply_filters( 'sumosubscriptions_can_restrict_subscription_product_when_outofstock', false, $args[ 'subscription_id' ] ) ) {
            return;
        }

        if ( sumo_is_subscription_exists( $args[ 'subscription_id' ] ) && 'Pause' === get_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', true ) ) {
            update_post_meta( $args[ 'subscription_id' ], 'sumo_get_status', 'Active' );
            sumo_add_subscription_note( __( 'Subscription resumed automatically.', 'sumosubscriptions' ), $args[ 'subscription_id' ], sumo_note_status( 'Active' ), __( 'Subscription resumed automatically', 'sumosubscriptions' ) );
            SUMOSubs_Order::set_next_payment_date( $args[ 'subscription_id' ] );
        }
    }

    /**
     * Retry Multiple Pay request Automatically. May be the Automatic payment fails to Renew the Subscription
     *
     * @param array $args
     */
    public static function retry_automatic_pay( $args ) {
        /**
         * Can restrict subscription product when outofstock.
         *
         * @since 15.6.0
         * @param $subscription_id subscription ID
         * @return bool
         */
        if ( apply_filters( 'sumosubscriptions_can_restrict_subscription_product_when_outofstock', false, $args[ 'subscription_id' ] ) ) {
            return;
        }

        self::automatic_pay( $args );
    }
}

SUMOSubs_Background_Process::init();
