<?php
/**
 * Helper utilities for scheduling actions per subscription.
 *
 * @package Advanced_Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ASWC_Schedule_Helper
 *
 * Simple wrapper around Action Scheduler to manage actions
 * tied to a specific subscription ID.
 */
class ASWC_Schedule_Helper {

	/**
	 * Schedule a single action for a subscription.
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param int    $timestamp       Timestamp for execution.
	 * @param string $hook            Action hook.
	 * @param array  $args            Optional arguments.
	 * @param string $group           Optional group.
	 *
	 * @return int|false Action ID on success, false otherwise.
	 */
	public static function schedule_single( $subscription_id, $timestamp, $hook, $args = array(), $group = 'aswc' ) {
		if ( false === class_exists( 'ASWC_Scheduler_API' ) ) {
				return false;
		}
		$args['subscription_id'] = (int) $subscription_id;

		return ASWC_Scheduler_API::schedule_action( $timestamp, $hook, $args, false, $group );
	}

	/**
	 * Cancel scheduled actions for a subscription.
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param string $hook            Action hook.
	 * @param array  $args            Optional arguments.
	 * @param string $group           Optional group.
	 *
	 * @return void
	 */
	public static function cancel_scheduled( $subscription_id, $hook, $args = array(), $group = 'aswc' ) {
		if ( false === class_exists( 'ASWC_Scheduler_API' ) ) {
				return;
		}
		$args['subscription_id'] = (int) $subscription_id;
		ASWC_Scheduler_API::unschedule_action( $hook, $args, $group );
	}

	/**
	 * Get timestamp for next scheduled action.
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param string $hook            Action hook.
	 * @param array  $args            Optional arguments.
	 * @param string $group           Optional group.
	 *
	 * @return int|false Timestamp if found, otherwise false.
	 */
	public static function next_scheduled( $subscription_id, $hook, $args = array(), $group = 'aswc' ) {
		if ( false === class_exists( 'ASWC_Scheduler_API' ) ) {
				return false;
		}
		$args['subscription_id'] = (int) $subscription_id;

		return ASWC_Scheduler_API::next_scheduled_action( $hook, $args, $group );
	}

	/**
	 * Reschedule a single action for a subscription.
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param int    $timestamp       Timestamp for execution.
	 * @param string $hook            Action hook.
	 * @param array  $args            Optional arguments.
	 * @param string $group           Optional group.
	 *
	 * @return int|false Action ID on success, false otherwise.
	 */
	public static function reschedule_single( $subscription_id, $timestamp, $hook, $args = array(), $group = 'aswc' ) {
		self::cancel_scheduled( $subscription_id, $hook, $args, $group );

		return self::schedule_single( $subscription_id, $timestamp, $hook, $args, $group );
	}
}
