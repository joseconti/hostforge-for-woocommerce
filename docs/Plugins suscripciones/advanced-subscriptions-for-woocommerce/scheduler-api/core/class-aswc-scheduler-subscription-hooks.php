<?php
/**
 * Subscription event hooks for the Scheduler API.
 *
 * Registers callbacks for subscription date and status changes so that
 * updates are delegated to the central Scheduler API without relying on
 * legacy scheduler classes.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

class ASWC_Scheduler_Subscription_Hooks {
    /**
     * Attach callbacks to subscription-related events.
     *
     * @return void
     */
    public static function init() {
        add_action( 'woocommerce_subscription_date_updated', [ __CLASS__, 'update_date' ], 10, 3 );
        add_action( 'woocommerce_subscription_date_deleted', [ __CLASS__, 'delete_date' ], 10, 2 );
        add_action( 'woocommerce_subscription_status_updated', [ __CLASS__, 'update_status' ], 10, 3 );
    }

    /**
     * Delegate a subscription date update to the Scheduler API.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Updated date type.
     * @param string          $datetime     MySQL date/time string in GMT/UTC.
     *
     * @return void
     */
    public static function update_date( $subscription, $date_type, $datetime ) {
        ASWC_Scheduler_API::update_date( $subscription, $date_type, $datetime );
    }

    /**
     * Delegate a subscription date deletion to the Scheduler API.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $date_type    Deleted date type.
     *
     * @return void
     */
    public static function delete_date( $subscription, $date_type ) {
        ASWC_Scheduler_API::delete_date( $subscription, $date_type );
    }

    /**
     * Delegate a subscription status update to the Scheduler API.
     *
     * @param WC_Subscription $subscription Subscription instance.
     * @param string          $new_status   New status.
     * @param string          $old_status   Previous status.
     *
     * @return void
     */
    public static function update_status( $subscription, $new_status, $old_status ) {
        ASWC_Scheduler_API::update_status( $subscription, $new_status, $old_status );
    }
}

// Register the hooks immediately when the file loads.
ASWC_Scheduler_Subscription_Hooks::init();
