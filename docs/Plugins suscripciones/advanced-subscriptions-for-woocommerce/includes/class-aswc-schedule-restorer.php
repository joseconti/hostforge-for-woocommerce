<?php
/**
 * Restore scheduled actions for existing subscriptions and verify daily.
 *
 * @package Advanced_Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class ASWC_Schedule_Restorer
 */
class ASWC_Schedule_Restorer {

		/**
		 * Hook name for restoring schedules.
		 */
		const RESTORE_HOOK = 'aswc_restore_subscription_schedules';

		/**
		 * Hook name for daily verification.
		 */
		const DAILY_CHECK_HOOK = 'aswc_verify_subscription_schedules';

		/**
		 * Number of subscriptions to process per batch.
		 */
		const BATCH_SIZE = 20;

		/**
		 * Register action hooks.
		 *
		 * @return void
		 */
	public static function init() {
			add_action( self::RESTORE_HOOK, array( __CLASS__, 'restore_batch' ) );
			add_action( self::DAILY_CHECK_HOOK, array( __CLASS__, 'daily_check' ) );
	}

		/**
		 * Schedule restoration of subscription actions.
		 *
		 * @return void
		 */
	public static function schedule_restoration() {
		if ( ! class_exists( 'ASWC_Scheduler_API' ) ) {
						return;
		}

		if ( ASWC_Scheduler_API::has_scheduled_action( self::RESTORE_HOOK ) ) {
						return;
		}

		ASWC_Scheduler_API::background()->schedule_action( aswc_get_wp_timestamp(), self::RESTORE_HOOK, array() );
	}

	/**
	 * Schedule a retry for an on-hold subscription based on previous attempts.
	 *
	 * Respects the maximum number of allowed retries and the automatic retry
	 * setting.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 *
	 * @return void
	 */
	protected static function schedule_retry_from_attempt( $subscription ) {
		if ( 'yes' !== get_option( 'aswc_enable_automatic_retry_failed_attempts', 'no' ) ) {
			return;
		}

		$last_order_id = $subscription->get_meta( '_aswc_last_renewal_order_id', true );
		$attempt       = 1;

		if ( ! empty( $last_order_id ) ) {
			$attempt = (int) aswc_get_meta_data( $last_order_id, 'aswc_no_of_retry_attempt', true );
			if ( 0 >= $attempt ) {
				$attempt = 1;
			}
		}

		$max_attempts = (int) get_option( 'aswc_after_no_failed_attempt_cancel', '3' );

		if ( $max_attempts > $attempt ) {
			$attempt_index = $attempt - 1;
			ASWC_Scheduler_API::schedule_retry_for_attempt( $subscription, $attempt_index );
		}
	}

	/**
	 * Schedule payment retries for on-hold subscriptions.
	 *
	 * Ensures that subscriptions in the on-hold status have a
	 * corresponding payment retry action scheduled when the
	 * plugin is (re)activated.
	 *
	 * @return void
	 */
	public static function schedule_on_hold_retries() {
		if ( ! class_exists( 'ASWC_Scheduler_API' ) ) {
										return;
		}

		if ( 'yes' !== get_option( 'aswc_enable_automatic_retry_failed_attempts', 'no' ) ) {
										return;
		}

		$batch  = self::BATCH_SIZE;
		$offset = 0;

		do {
			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$args             = array(
					'return'    => 'ids',
					'post_type' => 'aswc_subscriptions',
					'status'    => 'on-hold',
					'orderby'   => 'ID',
					'order'     => 'ASC',
					'limit'     => $batch,
					'offset'    => $offset,
				);
				$subscription_ids = wc_get_orders( $args );
			} else {
				$args             = array(
					'post_type'      => 'aswc_subscriptions',
					'post_status'    => 'wc-on-hold',
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'posts_per_page' => $batch,
					'offset'         => $offset,
				);
				$subscription_ids = get_posts( $args );
			}

			if ( empty( $subscription_ids ) ) {
				break;
			}

			foreach ( $subscription_ids as $subscription_id ) {
				$subscription = aswc_get_subscription( $subscription_id );
				if ( false === $subscription ) {
					continue;
				}

				if ( ASWC_Scheduler_API::has_scheduled_retry( $subscription, false ) ) {
					continue;
				}

				self::schedule_retry_from_attempt( $subscription );
			}

			$offset += $batch;
		} while ( true );
	}

				/**
				 * Restore scheduled actions for a batch of subscriptions.
				 *
				 * Ensures payment actions for active subscriptions and retry
				 * actions for on-hold subscriptions are scheduled in batches to
				 * avoid server overload.
				 *
				 * @return void
				 */
	public static function restore_batch() {
		if ( ! class_exists( 'ASWC_Scheduler_API' ) ) {
				return;
		}

		$offset = (int) get_option( 'aswc_restore_schedule_offset', 0 );

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$args             = array(
				'return'    => 'ids',
				'post_type' => 'aswc_subscriptions',
				'status'    => aswc_get_subscription_statuses(),
				'orderby'   => 'ID',
				'order'     => 'ASC',
				'limit'     => self::BATCH_SIZE,
				'offset'    => $offset,
			);
			$subscription_ids = wc_get_orders( $args );
		} else {
			$args = array(
				'post_type'      => 'aswc_subscriptions',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'posts_per_page' => self::BATCH_SIZE,
				'offset'         => $offset,
			);

			$subscription_ids = get_posts( $args );
		}

		if ( empty( $subscription_ids ) ) {
				delete_option( 'aswc_restore_schedule_offset' );
				self::schedule_daily_check();
				return;
		}

		foreach ( $subscription_ids as $subscription_id ) {
				$subscription = aswc_get_subscription( $subscription_id );
			if ( false === $subscription ) {
										continue;
			}

				$status = $subscription->get_status();

				ASWC_Scheduler_API::lifecycle()->schedule_all( $subscription );

			if ( 'active' === $status ) {
				// Check if the next payment date is in the past (overdue payment).
				$next_payment_time = $subscription->get_time( 'next_payment', 'site' );
				$current_time      = aswc_get_wp_timestamp();

				if ( $next_payment_time > 0 && $next_payment_time < $current_time ) {
					// Payment is overdue - schedule it to run immediately (1 minute from now).
					$immediate_time = $current_time + 60;
					ASWC_Scheduler_API::schedule_payment( $subscription, $immediate_time );

					if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log(
							sprintf(
								'[restore_batch] Subscription %d has overdue payment (was: %s, now: %s). Scheduled for immediate processing.',
								$subscription_id,
								gmdate( 'Y-m-d H:i:s', $next_payment_time ),
								gmdate( 'Y-m-d H:i:s', $current_time )
							)
						);
					}
				} else {
					// Normal scheduling.
					ASWC_Scheduler_API::schedule_payment( $subscription );
				}
			} elseif ( 'on-hold' === $status ) {
									self::schedule_retry_from_attempt( $subscription );
			}

									ASWC_Scheduler_API::schedule_all_notifications( $subscription );
		}

			$offset += self::BATCH_SIZE;
			update_option( 'aswc_restore_schedule_offset', $offset );

			ASWC_Scheduler_API::background()->schedule_action( aswc_get_wp_timestamp(), self::RESTORE_HOOK, array() );
	}

		/**
		 * Schedule daily verification of subscription payment actions at 2am.
		 *
		 * @return void
		 */
	public static function schedule_daily_check() {
		if ( ! class_exists( 'ASWC_Scheduler_API' ) ) {
				return;
		}

		// Remove any previously scheduled task to ensure it runs at 2am.
		if ( ASWC_Scheduler_API::has_scheduled_action( self::DAILY_CHECK_HOOK ) ) {
			ASWC_Scheduler_API::unschedule_action( self::DAILY_CHECK_HOOK );
		}

			$timestamp = aswc_get_wp_timestamp();
			$timezone  = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( get_option( 'timezone_string', 'UTC' ) );
			$next_run  = new DateTime( 'today 2:00', $timezone );
		if ( $next_run->getTimestamp() <= $timestamp ) {
				$next_run->modify( '+1 day' );
		}

			ASWC_Scheduler_API::background()->schedule_recurring_action( $next_run->getTimestamp(), DAY_IN_SECONDS, self::DAILY_CHECK_HOOK );
	}

		/**
		 * Ensure active subscriptions have a scheduled payment.
		 *
		 * @return void
		 */
	public static function daily_check() {
		if ( ! class_exists( 'ASWC_Scheduler_API' ) ) {
				return;
		}

			$batch  = self::BATCH_SIZE;
			$offset = 0;

		do {
				$args = array(
					'post_type'      => 'aswc_subscriptions',
					'post_status'    => 'wc-active',
					'fields'         => 'ids',
					'orderby'        => 'ID',
					'order'          => 'ASC',
					'posts_per_page' => $batch,
					'offset'         => $offset,
				);

				$ids = get_posts( $args );
				if ( empty( $ids ) ) {
						break;
				}

				foreach ( $ids as $subscription_id ) {
						$action_args = array( 'subscription_id' => $subscription_id );
					if ( ! ASWC_Scheduler_API::has_scheduled_action( 'advanced_scheduled_subscription_payment', $action_args ) ) {
							$subscription = aswc_get_subscription( $subscription_id );
						if ( false !== $subscription ) {
							ASWC_Scheduler_API::schedule_payment( $subscription );
						}
					}
				}

				$offset += $batch;
		} while ( true );
	}
}

ASWC_Schedule_Restorer::init();
