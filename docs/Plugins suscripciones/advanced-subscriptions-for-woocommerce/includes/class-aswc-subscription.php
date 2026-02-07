<?php
	/**
	 * The file that defines the main ASWC_Subscription class.
	 *
	 * @link  https://plugins.joseconti.com
	 * @since 1.0.0
	 *
	 * @package    Advanced_Subscriptions_For_Woocommerce
	 * @subpackage Advanced_Subscriptions_For_Woocommerce/include
	 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main subscription class.
 *
 * Extends the WooCommerce order class to represent a subscription.
 *
 * @since 1.0.0
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce/include
 */
class ASWC_Subscription extends WC_Order {

	/**
	 * Store the order data
	 *
	 * @public WC_Order Stores order data for the order in which the subscription was purchased (if any)
	 * @var bool
	 */
	protected $order = null;

	/**
	 * Store the order type
	 *
	 * @public string Order type
	 * @var bool
	 */
	public $order_type = 'aswc_subscriptions';

	/**
	 * Store the order data
	 *
	 * @private int Stores get_payment_count when used multiple times
	 * @var bool
	 */
	private $cached_payment_count = null;


	/**
	 * Store the order data
	 *
	 * Stores the $this->is_editable() returned value in memory
	 *
	 * @var bool
	 */
	private $editable;

	/**
	 * Store the order data
	 *
	 * @private array The set of valid date types that can be set on the subscription
	 * @var bool
	 */
	protected $valid_date_types = array();
	/**
	 * Initializes a specific subscription if the ID is passed, otherwise a new and empty instance of a subscription.
	 *
	 * This class should not be instantiated. Use the helper functions to
	 * create or retrieve subscriptions instead.
	 *
	 * @param int|ASWC_Subscription $subscription Subscription to read.
	 */
	public function __construct( $subscription = 0 ) {

		parent::__construct( $subscription );
		$this->order_type = 'aswc_subscriptions';
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'aswc_subscriptions';
	}
	/**
	 * Added this function to avoid the critical error.
	 *
	 * @return null
	 */
	public function get_report_customer_id() {
		return null;
	}

	/**
	 * Get the subscription status.
	 *
	 * @param string $context View or edit context.
	 * @return string Subscription status (without 'wc-' prefix).
	 */
	public function get_status( $context = 'view' ) {
		return parent::get_status( $context );
	}

	/**
	 * Retrieve a timestamp for a given subscription date type.
	 *
	 * @param string $date_type Subscription date key.
	 *
	 * @return int
	 */
	public function get_time( $date_type ) {
		$timestamp       = 0;
		$subscription_id = $this->get_id();

		if ( 'trial_end' === $date_type ) {
			$timestamp = (int) aswc_get_meta_data( $subscription_id, 'aswc_susbcription_trial_end', true );
		} elseif ( 'end' === $date_type ) {
			$timestamp = (int) aswc_get_meta_data( $subscription_id, 'aswc_susbcription_end', true );
		} elseif ( 'next_payment' === $date_type ) {
			$timestamp = (int) aswc_get_meta_data( $subscription_id, 'aswc_next_payment_date', true );
		} elseif ( 'date_created' === $date_type ) {
			$date_created = $this->get_date_created();
			$timestamp    = $date_created ? $date_created->getTimestamp() : 0;
		} else {
			$timestamp = (int) apply_filters( 'aswc_subscription_get_time', 0, $date_type, $this );
		}

				return $timestamp;
	}

		/**
		 * Retrieve a date string for a given subscription date type.
		 *
		 * @param string $date_type Subscription date key.
		 *
		 * @return string|int
		 */
	public function get_date( $date_type ) {
									$timestamp = $this->get_time( $date_type );

									return 0 !== $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : 0;
	}

		/**
		 * Get the billing period for the subscription.
		 *
		 * @since 1.0.0
		 *
		 * @return string Billing period.
		 */
	public function get_billing_period() {
			$period = aswc_get_meta_data( $this->get_id(), 'aswc_subscription_interval', true );

		if ( empty( $period ) ) {
				$period = 'day';
		}

			return $period;
	}

		/**
		 * Get the billing interval for the subscription.
		 *
		 * @since 1.0.0
		 *
		 * @return int Billing interval.
		 */
	public function get_billing_interval() {
			$interval = (int) aswc_get_meta_data( $this->get_id(), 'aswc_subscription_number', true );

		if ( 0 === $interval ) {
				$interval = 1;
		}

			return $interval;
	}

		/**
		 * Determine if the subscription requires manual renewal payments.
		 *
		 * @since 1.0.0
		 *
		 * @return bool True when the subscription uses the manual payment method.
		 */
	public function is_manual() {
			$payment_type = aswc_get_meta_data( $this->get_id(), 'aswc_payment_type', true );

			$is_manual = ( 'aswc_manual_method' === $payment_type );

			return apply_filters( 'woocommerce_subscription_is_manual', $is_manual, $this );
	}

		/**
		 * Check if the subscription's payment method supports a specific feature.
		 *
		 * If the subscription uses manual renewal payments, all features are considered
		 * supported. Otherwise, support is determined by the configured payment gateway.
		 *
		 * @since 1.0.0
		 *
		 * @param string $payment_gateway_feature Feature to verify support for.
		 *
		 * @return bool True when the payment method supports the feature, otherwise false.
		 */
	public function payment_method_supports( $payment_gateway_feature ) {
		if ( $this->is_manual() ) {
				$payment_gateway_supports = true;
		} else {
					$payment_gateway          = ASWC_Scheduler_API::get_payment_gateway_by_order( $this );
					$payment_gateway_supports = ( false !== $payment_gateway && $payment_gateway->supports( $payment_gateway_feature ) );
		}

			return apply_filters( 'aswc_subscription_payment_gateway_supports', $payment_gateway_supports, $payment_gateway_feature, $this );
	}

		/**
		 * Get the most recent related order for this subscription.
		 *
		 * @param string       $return_fields    Fields to return, either 'all' or 'ids'.
		 * @param array|string $order_types      Order types to search. Accepts 'parent', 'renewal', or 'any'.
		 * @param array        $exclude_statuses Optional array of order statuses to ignore.
		 *
		 * @return int|\WC_Order|false Order ID, order object, or false when no order found.
		 */
	public function get_last_order( $return_fields = 'ids', $order_types = array( 'parent', 'renewal' ), $exclude_statuses = array() ) {
			$return_fields = 'all' === $return_fields ? 'all' : 'ids';
			$order_types   = ( 'any' === $order_types ) ? array( 'parent', 'renewal' ) : (array) $order_types;

			$order_id = 0;

		if ( in_array( 'renewal', $order_types, true ) ) {
				$order_id = (int) aswc_get_meta_data( $this->get_id(), 'aswc_last_renewal_order_id', true );
		}

		if ( 0 === $order_id && in_array( 'parent', $order_types, true ) ) {
					$order_id = (int) aswc_get_meta_data( $this->get_id(), 'aswc_parent_order', true );
		}

		if ( 0 === $order_id ) {
					return false;
		}

		if ( ! empty( $exclude_statuses ) ) {
				$order = wc_get_order( $order_id );
			if ( $order && $order->has_status( $exclude_statuses ) ) {
					return false;
			}
		}

		if ( 'all' === $return_fields ) {
				$last_order = wc_get_order( $order_id );
				$last_order = ( $last_order instanceof \WC_Order ) ? $last_order : false;
		} else {
				$last_order = $order_id;
		}

				return apply_filters( 'advanced_subscriptions_woocommerce_last_order', $last_order, $this );
	}
}
