<?php
/**
 * Log plugin functionality.
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Log all things!
 *
 * @since 1.0.0
 */
class ASWC_Log {
	/**
	 * The logger.
	 *
	 * @since   1.0.0
	 * @var $logger logger.
	 */
	public static $logger;

	const WC_LOG_FILENAME = 'aswc';

	/**
	 * Utilize WC logger class
	 *
	 * @param string $message message.
	 * @since 1.0.0
	 */
	public static function log( $message ) {
		if ( ! class_exists( 'WC_Logger' ) ) {
			return;
		}
		$enable_log = get_option( 'aswc_enable_subscription_log', 'no' );
		if ( 'yes' !== $enable_log ) {
			return;
		}

		if ( apply_filters( 'aswc_logging', true, $message ) ) {
			if ( empty( self::$logger ) ) {
				self::$logger = wc_get_logger();
			}

			self::$logger->debug( $message, array( 'source' => self::WC_LOG_FILENAME ) );
		}
	}

	/**
	 * Log a data change (before/after values)
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param string $field Field name being changed.
	 * @param mixed  $old_value Old value.
	 * @param mixed  $new_value New value.
	 * @param string $context Context of the change.
	 * @since 1.0.0
	 */
	public static function log_data_change( $subscription_id, $field, $old_value, $new_value, $context = '' ) {
		// Format values for logging.
		$old_display = self::format_value_for_log( $old_value, $field );
		$new_display = self::format_value_for_log( $new_value, $field );

		$context_str = $context ? " [$context]" : '';

		self::log(
			sprintf(
				'[DATA_CHANGE]%s Subscription #%d - %s: %s → %s',
				$context_str,
				$subscription_id,
				$field,
				$old_display,
				$new_display
			)
		);
	}

	/**
	 * Log payment processing details
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param int    $order_id Order ID.
	 * @param string $phase Phase of payment (pre-payment, payment-attempt, post-payment).
	 * @param array  $data Payment data to log.
	 * @since 1.0.0
	 */
	public static function log_payment_processing( $subscription_id, $order_id, $phase, $data = array() ) {
		$data_str = '';
		if ( ! empty( $data ) ) {
			$parts = array();
			foreach ( $data as $key => $value ) {
				$parts[] = sprintf( '%s=%s', $key, self::format_value_for_log( $value, $key ) );
			}
			$data_str = ' | ' . implode( ', ', $parts );
		}

		self::log(
			sprintf(
				'[PAYMENT_%s] Subscription #%d, Order #%d%s',
				strtoupper( str_replace( '-', '_', $phase ) ),
				$subscription_id,
				$order_id,
				$data_str
			)
		);
	}

	/**
	 * Log subscription state snapshot
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param string $context Context of snapshot (before-payment, after-payment, etc).
	 * @since 1.0.0
	 */
	public static function log_subscription_snapshot( $subscription_id, $context = '' ) {
		$context_str = $context ? " [$context]" : '';

		// Get all critical subscription data.
		$data = array(
			'status'            => aswc_get_meta_data( $subscription_id, 'aswc_subscription_status', true ),
			'recurring_total'   => aswc_get_meta_data( $subscription_id, 'aswc_recurring_total', true ),
			'next_payment_date' => aswc_get_meta_data( $subscription_id, 'aswc_next_payment_date', true ),
			'interval'          => aswc_get_meta_data( $subscription_id, 'aswc_subscription_interval', true ),
			'interval_count'    => aswc_get_meta_data( $subscription_id, 'aswc_subscription_interval_count', true ),
			'payment_count'     => aswc_get_meta_data( $subscription_id, 'aswc_payment_count', true ),
			'trial_end'         => aswc_get_meta_data( $subscription_id, 'aswc_trial_end_date', true ),
			'subscription_end'  => aswc_get_meta_data( $subscription_id, 'aswc_susbcription_end', true ),
		);

		// Format data.
		$parts = array();
		foreach ( $data as $key => $value ) {
			$parts[] = sprintf( '%s=%s', $key, self::format_value_for_log( $value, $key ) );
		}

		self::log(
			sprintf(
				'[SUBSCRIPTION_SNAPSHOT]%s Subscription #%d: %s',
				$context_str,
				$subscription_id,
				implode( ', ', $parts )
			)
		);
	}

	/**
	 * Format a value for logging
	 *
	 * @param mixed  $value Value to format.
	 * @param string $field Field name (for context-aware formatting).
	 * @return string Formatted value.
	 * @since 1.0.0
	 */
	private static function format_value_for_log( $value, $field = '' ) {
		// Null or empty.
		if ( is_null( $value ) || '' === $value ) {
			return '(empty)';
		}

		// Boolean.
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		// Arrays or objects.
		if ( is_array( $value ) || is_object( $value ) ) {
			return wp_json_encode( $value );
		}

		// Timestamps and dates.
		if ( strpos( $field, 'date' ) !== false || strpos( $field, 'time' ) !== false ) {
			if ( is_numeric( $value ) ) {
				return gmdate( 'Y-m-d H:i:s', $value ) . ' (' . $value . ')';
			}
		}

		// Prices and amounts.
		if ( strpos( $field, 'total' ) !== false || strpos( $field, 'amount' ) !== false || strpos( $field, 'price' ) !== false ) {
			if ( is_numeric( $value ) ) {
				return wc_price( $value ) . ' (' . $value . ')';
			}
		}

		// Default.
		return (string) $value;
	}
}
