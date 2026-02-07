<?php
/**
 * Payment-related helper functions for the Scheduler API.
 *
 * Provides helpers for payment gateway hooks so the Scheduler API can interact
 * with them without depending on external plugin classes.
 *
 * @package WooCommerce\Subscriptions\SchedulerAPI\Payments
 */

if ( ! function_exists( 'aswc_get_retry_interval_from_rule' ) ) {
	/**
	 * Retrieve the retry interval from a retry rule object.
	 *
	 * Wraps the rule's `get_retry_interval()` method so the Scheduler API
	 * doesn't depend directly on external plugin classes.
	 *
	 * @param object $rule Retry rule instance.
	 * @return int Interval in seconds. Returns 0 if unavailable.
	 */
	function aswc_get_retry_interval_from_rule( $rule ) {
		return method_exists( $rule, 'get_retry_interval' ) ? (int) $rule->get_retry_interval() : 0;
	}
}

if ( ! function_exists( 'aswc_get_order_status_name' ) ) {
	/**
	 * Retrieve the human readable status for an order.
	 *
	 * Wraps the order's `get_status()` method so the Scheduler API does not
	 * call it directly.
	 *
	 * @param WC_Order $order Order instance.
	 * @return string Status name or empty string if unavailable.
	 */
	function aswc_get_order_status_name( $order ) {
		return method_exists( $order, 'get_status' ) ? wc_get_order_status_name( $order->get_status() ) : '';
	}
}

if ( ! function_exists( 'aswc_display_failed_actions_notice' ) ) {
	/**
	 * Display the failed scheduled actions admin notice using core plugin utilities.
	 *
	 * @param array  $failed_scheduled_actions Actions that have failed.
	 * @param string $affected_subscription_events Description of affected events.
	 *
	 * @return void
	 */
	function aswc_display_failed_actions_notice( $failed_scheduled_actions, $affected_subscription_events ) {
		$template_path = aswc_get_plugin_directory( 'templates/admin/' );

		if ( '' === $template_path ) {
			return;
		}

		$notice = aswc_create_admin_notice( 'error' );

		if ( ! $notice ) {
			return;
		}

		$notice->set_content_template(
			'html-failed-scheduled-action-notice.php',
			$template_path,
			array(
				'failed_scheduled_actions'     => $failed_scheduled_actions,
				'affected_subscription_events' => $affected_subscription_events,
			)
		);
		$notice->set_actions(
			array(
				array(
					'name'  => __( 'Ignore this error', 'advanced-subscriptions-for-woocommerce' ),
					'url'   => wp_nonce_url( add_query_arg( 'aswc_scheduled_action_timeout_error_notice', 'ignore' ), 'aswc_scheduled_action_timeout_error_notice', '_aswcnonce' ),
					'class' => 'button',
				),
				array(
					'name'  => __( 'Learn more', 'advanced-subscriptions-for-woocommerce' ),
					'url'   => 'https://plugins.joseconti.com/tutoriales/subscriptions/scheduled-action-errors/',
					'class' => 'button button-primary',
				),
			)
		);
		$notice->display();
	}
}

if ( ! function_exists( 'aswc_get_retry_rule_raw_data' ) ) {
	/**
	 * Retrieve the raw data from a retry rule object.
	 *
	 * Wraps the rule's `get_raw_data()` method so the Scheduler API
	 * does not depend directly on plugin classes when accessing
	 * the underlying rule configuration.
	 *
	 * @param object $rule Retry rule instance.
	 * @return array Raw rule data. Returns an empty array if unavailable.
	 */
	function aswc_get_retry_rule_raw_data( $rule ) {
		return method_exists( $rule, 'get_raw_data' ) ? (array) $rule->get_raw_data() : array();
	}
}

if ( ! function_exists( 'aswc_get_retry_rule_status_to_apply' ) ) {
	/**
	 * Retrieve the status to apply from a retry rule object for a given item.
	 *
	 * Wraps the rule's `get_status_to_apply()` method so the Scheduler API
	 * can determine status transitions without relying on external classes.
	 *
	 * @param object $rule       Retry rule instance.
	 * @param string $object_key Object type to retrieve the status for.
	 * @return string Status slug or empty string if unavailable.
	 */
	function aswc_get_retry_rule_status_to_apply( $rule, $object_key ) {
		return method_exists( $rule, 'get_status_to_apply' ) ? (string) $rule->get_status_to_apply( $object_key ) : '';
	}
}

if ( ! function_exists( 'aswc_retry_rule_has_email_template' ) ) {
	/**
	 * Determine whether a retry rule defines an email template for a recipient.
	 *
	 * Wraps the rule's `has_email_template()` method so the Scheduler API can
	 * check for notification templates without depending on external classes.
	 *
	 * @param object $rule      Retry rule instance.
	 * @param string $recipient Recipient identifier. Defaults to 'customer'.
	 * @return bool True if a template exists, false otherwise.
	 */
	function aswc_retry_rule_has_email_template( $rule, $recipient = 'customer' ) {
		return method_exists( $rule, 'has_email_template' ) ? (bool) $rule->has_email_template( $recipient ) : false;
	}
}

if ( ! function_exists( 'aswc_get_retry_rule_email_template' ) ) {
	/**
	 * Retrieve the email template defined by a retry rule for a recipient.
	 *
	 * Wraps the rule's `get_email_template()` method so the Scheduler API can
	 * fetch notification templates without directly referencing plugin classes.
	 *
	 * @param object $rule      Retry rule instance.
	 * @param string $recipient Recipient identifier. Defaults to 'customer'.
	 * @return string Email template slug or empty string if unavailable.
	 */
	function aswc_get_retry_rule_email_template( $rule, $recipient = 'customer' ) {
		return method_exists( $rule, 'get_email_template' ) ? (string) $rule->get_email_template( $recipient ) : '';
	}
}

if ( ! function_exists( 'aswc_schedule_payment' ) ) {
	/**
	 * Schedule the next payment for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param int|null        $timestamp    When the payment should run. Defaults to the subscription's next payment time.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore groups when scheduling.
	 */
	function aswc_schedule_payment( $subscription, $timestamp = null, $group = null ) {
		ASWC_Scheduler_API::schedule_payment( $subscription, $timestamp, $group );
	}
}

if ( ! function_exists( 'aswc_schedule_manual_payment' ) ) {
	/**
	 * Schedule a manual payment for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param int|null        $timestamp    When the payment should run. Defaults to the subscription's next payment time.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore groups when scheduling.
	 *
	 * @return int Scheduled timestamp.
	 */
	function aswc_schedule_manual_payment( $subscription, $timestamp = null, $group = null ) {
		return ASWC_Scheduler_API::schedule_manual_payment( $subscription, $timestamp, $group );
	}
}

if ( ! function_exists( 'aswc_unschedule_payment' ) ) {
	/**
	 * Unschedule the next payment for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore the group when clearing.
	 */
	function aswc_unschedule_payment( $subscription, $group = null ) {
		ASWC_Scheduler_API::unschedule_payment( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_schedule_retry' ) ) {
	/**
	 * Schedule a payment retry for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param int|null        $timestamp    When the retry should run. Defaults to the subscription's retry time.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore groups when scheduling.
	 */
	function aswc_schedule_retry( $subscription, $timestamp = null, $group = null ) {
		ASWC_Scheduler_API::schedule_retry( $subscription, $timestamp, $group );
	}
}

if ( ! function_exists( 'aswc_schedule_retry_with_rule' ) ) {
	/**
	 * Schedule a payment retry using a retry rule.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param object          $rule         Retry rule providing the interval.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore groups when scheduling.
	 *
	 * @return int Scheduled timestamp.
	 */
	function aswc_schedule_retry_with_rule( $subscription, $rule, $group = null ) {
		return ASWC_Scheduler_API::schedule_retry_with_rule( $subscription, $rule, $group );
	}
}

if ( ! function_exists( 'aswc_schedule_retry_after' ) ) {
	/**
	 * Schedule a payment retry after a given interval.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param int             $interval     Interval in seconds after which the retry should run.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore groups when scheduling.
	 *
	 * @return int Scheduled timestamp.
	 */
	function aswc_schedule_retry_after( $subscription, $interval, $group = null ) {
		return ASWC_Scheduler_API::schedule_retry_after( $subscription, $interval, $group );
	}
}

if ( ! function_exists( 'aswc_schedule_retry_for_attempt' ) ) {
	/**
	 * Schedule a payment retry based on the attempt number.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param int             $attempt      Retry attempt index (0-based).
	 * @param string|null     $group        Optional Action Scheduler group.
	 *
	 * @return int Scheduled timestamp.
	 */
	function aswc_schedule_retry_for_attempt( $subscription, $attempt, $group = null ) {
		return ASWC_Scheduler_API::schedule_retry_for_attempt( $subscription, $attempt, $group );
	}
}

if ( ! function_exists( 'aswc_get_retry_intervals' ) ) {
	/**
	 * Retrieve the configured retry intervals.
	 *
	 * @return array List of intervals in seconds for each retry attempt.
	 */
	function aswc_get_retry_intervals() {
		return ASWC_Scheduler_API::get_retry_intervals();
	}
}

if ( ! function_exists( 'aswc_unschedule_retry' ) ) {
	/**
	 * Unschedule any payment retry for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore the group when clearing.
	 */
	function aswc_unschedule_retry( $subscription, $group = null ) {
		ASWC_Scheduler_API::unschedule_retry( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_schedule_all_payments' ) ) {
	/**
	 * Schedule all payment-related events for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore groups when scheduling.
	 */
	function aswc_schedule_all_payments( $subscription, $group = null ) {
		ASWC_Scheduler_API::schedule_all_payments( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_unschedule_all_payments' ) ) {
	/**
	 * Unschedule all payment-related events for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to ignore the group when clearing.
	 */
	function aswc_unschedule_all_payments( $subscription, $group = null ) {
		ASWC_Scheduler_API::unschedule_all_payments( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_scheduled_payment' ) ) {
	/**
	 * Get the timestamp for the next scheduled payment.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return int|false Timestamp or false if none exists.
	 */
	function aswc_get_scheduled_payment( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_scheduled_payment( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_scheduled_retry' ) ) {
	/**
	 * Get the timestamp for the next scheduled payment retry.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return int|false Timestamp or false if none exists.
	 */
	function aswc_get_scheduled_retry( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_scheduled_retry( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_scheduled_payments' ) ) {
	/**
	 * Get all scheduled payment-related events for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return array Map of date type => timestamp for scheduled events.
	 */
	function aswc_get_scheduled_payments( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_scheduled_payments( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_last_scheduled_payments' ) ) {
	/**
	 * Get the most recently scheduled payment-related events for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return array Map of date type => timestamp for scheduled events.
	 */
	function aswc_get_last_scheduled_payments( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_last_scheduled_payments( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_scheduled_payment_action' ) ) {
	/**
	 * Get the scheduled action object for the next payment.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return ActionScheduler_Action|false The action object or false if none exists.
	 */
	function aswc_get_scheduled_payment_action( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_scheduled_payment_action( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_scheduled_retry_action' ) ) {
	/**
	 * Get the scheduled action object for the next payment retry.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return ActionScheduler_Action|false The action object or false if none exists.
	 */
	function aswc_get_scheduled_retry_action( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_scheduled_retry_action( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_scheduled_payment_actions' ) ) {
	/**
	 * Get scheduled payment and retry action objects for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return array Map of date type => ActionScheduler_Action.
	 */
	function aswc_get_scheduled_payment_actions( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_scheduled_payment_actions( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_scheduled_retry_actions' ) ) {
	/**
	 * Get all scheduled payment retry action objects for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return array Array of ActionScheduler_Action objects.
	 */
	function aswc_get_scheduled_retry_actions( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_scheduled_retry_actions( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_last_scheduled_payment' ) ) {
	/**
	 * Get the timestamp for the most recently scheduled payment.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return int|false Timestamp or false if no action exists.
	 */
	function aswc_last_scheduled_payment( $subscription, $group = null ) {
		return ASWC_Scheduler_API::last_scheduled_payment( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_last_scheduled_retry' ) ) {
	/**
	 * Get the timestamp for the most recently scheduled payment retry.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return int|false Timestamp or false if no action exists.
	 */
	function aswc_last_scheduled_retry( $subscription, $group = null ) {
		return ASWC_Scheduler_API::last_scheduled_retry( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_last_scheduled_payment_action' ) ) {
	/**
	 * Get the most recently scheduled payment action object.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return ActionScheduler_Action|false The action object or false if none exists.
	 */
	function aswc_get_last_scheduled_payment_action( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_last_scheduled_payment_action( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_last_scheduled_retry_action' ) ) {
	/**
	 * Get the most recently scheduled payment retry action object.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return ActionScheduler_Action|false The action object or false if none exists.
	 */
	function aswc_get_last_scheduled_retry_action( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_last_scheduled_retry_action( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_get_last_scheduled_payment_actions' ) ) {
	/**
	 * Get the most recently scheduled payment and retry action objects for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return array Map of date type => ActionScheduler_Action.
	 */
	function aswc_get_last_scheduled_payment_actions( $subscription, $group = null ) {
		return ASWC_Scheduler_API::get_last_scheduled_payment_actions( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_has_scheduled_payment' ) ) {
	/**
	 * Determine if a payment action is scheduled for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return bool
	 */
	function aswc_has_scheduled_payment( $subscription, $group = null ) {
		return ASWC_Scheduler_API::has_scheduled_payment( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_has_scheduled_retry' ) ) {
	/**
	 * Determine if a payment retry action is scheduled for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return bool
	 */
	function aswc_has_scheduled_retry( $subscription, $group = null ) {
		return ASWC_Scheduler_API::has_scheduled_retry( $subscription, $group );
	}
}

if ( ! function_exists( 'aswc_has_scheduled_payments' ) ) {
	/**
	 * Check if any payment-related events are scheduled for a subscription.
	 *
	 * @param WC_Subscription $subscription Subscription instance.
	 * @param string|bool     $group        Optional Action Scheduler group. Pass false to search across all groups.
	 *
	 * @return bool
	 */
	function aswc_has_scheduled_payments( $subscription, $group = null ) {
		return ASWC_Scheduler_API::has_scheduled_payments( $subscription, $group );
	}
}
