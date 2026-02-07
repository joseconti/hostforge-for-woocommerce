<?php
/**
 * Lifecycle event callbacks for scheduled actions.
 *
 * Handles execution of subscription lifecycle events when their
 * corresponding scheduled actions run.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI
 */

class ASWC_Scheduler_Lifecycle_Events {
    /**
     * Register hooks for lifecycle events.
     */
    public static function init() {
        add_action( 'advanced_scheduled_subscription_expiration', [ __CLASS__, 'expire_subscription' ], 10, 1 );
        add_action( 'advanced_scheduled_subscription_end_of_prepaid_term', [ __CLASS__, 'subscription_end_of_prepaid_term' ], 10, 1 );
        add_action( 'advanced_scheduled_subscription_trial_end', [ __CLASS__, 'trigger_subscription_trial_ended_hook' ], 10, 1 );
        add_action( 'advanced_scheduled_subscription_payment', [ __CLASS__, 'prepare_renewal' ], 1, 1 );
    }

    /**
     * Expire a subscription when the scheduled expiration action runs.
     *
     * @param int $subscription_id Subscription post ID.
     */
    public static function expire_subscription( $subscription_id ) {
        $subscription = aswc_get_subscription( $subscription_id );

        if ( false === $subscription ) {
            /* translators: placeholder is a subscription ID. */
            throw new InvalidArgumentException( sprintf( __( 'Subscription doesn\'t exist in scheduled action: %d', 'advanced-subscriptions-for-woocommerce' ), $subscription_id ) );
        }

        // Use meta as single source of truth instead of update_status().
        aswc_update_meta_data( $subscription_id, 'aswc_subscription_status', 'expired' );
    }

    /**
     * Handle the end of the prepaid term for a cancelled subscription.
     *
     * @param int $subscription_id Subscription post ID.
     */
    public static function subscription_end_of_prepaid_term( $subscription_id ) {
        $subscription = aswc_get_subscription( $subscription_id );

        if ( $subscription ) {
            // Use meta as single source of truth instead of update_status().
            aswc_update_meta_data( $subscription_id, 'aswc_subscription_status', 'cancelled' );
        }
    }

    /**
     * Trigger a hook after a subscription\'s trial period has ended.
     *
     * @param int $subscription_id Subscription post ID.
     */
    public static function trigger_subscription_trial_ended_hook( $subscription_id ) {
        do_action( 'woocommerce_subscription_trial_ended', $subscription_id );
    }


    /**
     * Prepare a subscription renewal before the payment is processed.
     *
     * @param int $subscription_id Subscription post ID.
     */
    public static function prepare_renewal( $subscription_id ) {
        $order_note = _x( 'Subscription renewal payment due:', 'used in order note as reason for why subscription status changed', 'advanced-subscriptions-for-woocommerce' );

        self::process_renewal( $subscription_id, 'active', $order_note );
    }

    /**
     * Process renewal for a subscription.
     *
     * @param int    $subscription_id Subscription post ID.
     * @param string $required_status Required subscription status.
     * @param string $order_note      Reason for subscription status change.
     *
     * @return WC_Order|false Renewal order or false on failure.
     */
    public static function process_renewal( $subscription_id, $required_status, $order_note ) {
        $subscription = aswc_get_subscription( $subscription_id );

        if ( ! empty( $subscription ) && $subscription->has_status( $required_status ) ) {
            // Use meta as single source of truth instead of update_status().
            aswc_update_meta_data( $subscription_id, 'aswc_subscription_status', 'on-hold' );

            $renewal_order = aswc_create_renewal_order( $subscription );

            if ( is_wp_error( $renewal_order ) ) {
                $renewal_order = aswc_create_renewal_order( $subscription );

                if ( is_wp_error( $renewal_order ) ) {
                    throw new Exception( sprintf( __( 'Error: Unable to create renewal order with note "%1$s". Message: %2$s', 'advanced-subscriptions-for-woocommerce' ), $order_note, $renewal_order->get_error_message() ) );
                }
            }

            if ( 0 == $renewal_order->get_total() ) {
                $renewal_order->payment_complete();
            } else {
                if ( $subscription->is_manual() ) {
                    do_action( 'woocommerce_generated_manual_renewal_order', aswc_get_objects_property( $renewal_order, 'id' ), $subscription );
                    $renewal_order->add_order_note( __( 'Manual renewal order awaiting customer payment.', 'advanced-subscriptions-for-woocommerce' ) );
                } else {
                    $renewal_order->set_payment_method( aswc_get_payment_gateway_by_order( $subscription ) );

                    if ( is_callable( array( $renewal_order, 'save' ) ) ) {
                        $renewal_order->save();
                    }
                }
            }
        } else {
            $renewal_order = false;
        }

        return $renewal_order;
    }
}

// Register the lifecycle event handlers.
ASWC_Scheduler_Lifecycle_Events::init();

