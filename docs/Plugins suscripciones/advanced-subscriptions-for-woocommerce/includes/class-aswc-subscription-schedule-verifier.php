<?php
/**
 * Verify subscription schedules daily and reschedule missing payments.
 *
 * @package Advanced_Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

/**
 * Class ASWC_Subscription_Schedule_Verifier
 */
class ASWC_Subscription_Schedule_Verifier {

		/**
		 * Hook name for daily verification.
		 */
		const HOOK = 'aswc_daily_subscription_check';

		/**
		 * Register verification hook and schedule recurring background task.
		 *
		 * @return void
		 */
	public static function init() {
			add_action( self::HOOK, array( 'ASWC_Schedule_Restorer', 'daily_check' ) );

		if ( false === class_exists( 'ASWC_Scheduler_API' ) ) {
				return;
		}

			ASWC_Scheduler_API::unschedule_action( 'aswc_cleanup_logs' );

			$timestamp = aswc_get_wp_timestamp();

		if ( false === ASWC_Scheduler_API::has_scheduled_background_action( self::HOOK ) ) {
				ASWC_Scheduler_API::background()->schedule_recurring_action( $timestamp, DAY_IN_SECONDS, self::HOOK );
		}
	}
}

ASWC_Subscription_Schedule_Verifier::init();
