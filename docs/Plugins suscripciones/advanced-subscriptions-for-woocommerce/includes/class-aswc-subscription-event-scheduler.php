<?php
/**
 * Per-subscription scheduled events.
 *
 * @package Advanced_Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ASWC_Subscription_Event_Scheduler
 */
class ASWC_Subscription_Event_Scheduler {

	/**
	 * Hook into subscription events.
	 *
	 * @return void
	 */
	public static function init() {
							add_action( 'aswc_after_created_subscription', array( __CLASS__, 'schedule_events' ), 10, 1 );
							add_action( 'aswc_subscription_cancel', array( __CLASS__, 'cancel_events' ), 10, 1 );
                                                        add_filter( 'aswc_subscription_customer_notification_time_offset', array( __CLASS__, 'filter_notification_offset' ), 10, 3 );
	}

	/**
	 * Schedule all relevant actions for a subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return void
	 */
	public static function schedule_events( $subscription_id ) {
		$subscription = aswc_get_subscription( $subscription_id );

		if ( false === $subscription ) {
			return;
		}
		ASWC_Scheduler_API::lifecycle()->schedule_all( $subscription );
				ASWC_Scheduler_API::schedule_payment( $subscription );
				ASWC_Scheduler_API::schedule_all_notifications( $subscription );
	}


		/**
	* Override notification lead time based on plugin setting.
	*
	* @param int             $offset           Existing offset in seconds.
	* @param WC_Subscription $subscription     Subscription instance.
	* @param string          $notification_type Notification date type.
	*
	* @return int Updated offset in seconds.
	*/
	public static function filter_notification_offset( $offset, $subscription, $notification_type ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			$days = absint( get_option( 'aswc_send_before_recurring_reminder', 5 ) );
		if ( 0 < $days ) {
				$offset = $days * DAY_IN_SECONDS;
		}
			return $offset;
	}

		/**
		 * Cancel all scheduled actions for a subscription.
		 *
		 * @param int $subscription_id Subscription ID.
		 * @return void
		 */
	public static function cancel_events( $subscription_id ) {
		$subscription = aswc_get_subscription( $subscription_id );

		if ( false === $subscription ) {
			return;
		}
		ASWC_Scheduler_API::lifecycle()->unschedule_all( $subscription );
		ASWC_Scheduler_API::unschedule_payment( $subscription );
		ASWC_Scheduler_API::unschedule_all_notifications( $subscription );
	}
}

ASWC_Subscription_Event_Scheduler::init();

