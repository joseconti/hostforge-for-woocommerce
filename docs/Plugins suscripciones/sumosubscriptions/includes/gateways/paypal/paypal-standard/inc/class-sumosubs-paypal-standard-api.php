<?php

/**
 * Abstract PayPal Standard API
 * 
 * @class SUMOSubs_PayPal_Standard_API
 */
abstract class SUMOSubs_PayPal_Standard_API {

	/**
	 * Payment Order (post) ID.
	 * 
	 * @var int 
	 */
	public $payment_order_id = 0;

	/**
	 * Subscription (post) ID.
	 * 
	 * @var int 
	 */
	public $subscription_id = 0;

	/**
	 * Payment Order.
	 * 
	 * @var object 
	 */
	public $payment_order;

	/**
	 * Subscription.
	 * 
	 * @var object 
	 */
	public $subscription;

	/**
	 * Populate
	 */
	protected function populate( $payment_order_id = 0, $subscription_id = 0 ) {
		$this->payment_order_id = absint( $payment_order_id );
		$this->subscription_id  = absint( $subscription_id );

		if ( 0 === $this->subscription_id && $this->payment_order_id ) {
			$subscriptions         = sumosubscriptions()->query->get( array(
				'type'       => 'sumosubscriptions',
				'status'     => 'publish',
				'limit'      => 1,
				'meta_key'   => 'sumo_get_parent_order_id',
				'meta_value' => sumosubs_get_parent_order_id( $this->payment_order_id ),
					) );
			$this->subscription_id = ! empty( $subscriptions[ 0 ] ) ? $subscriptions[ 0 ] : 0;
		}
	}

	/**
	 * Get saved PayPal Subscription profile ID
	 *
	 * @return string
	 */
	public function get_profile_id() {
		if ( $this->subscription_id > 0 ) {
			$profile_id = sumo_get_subscription_payment( $this->subscription_id, 'profile_id' );
		} else {
			$profile_id = sumo_get_subscription_order_payment( $this->payment_order_id, 'profile_id' );
		}

		return $profile_id;
	}

	/**
	 * Get WC PayPal Settings.
	 *
	 * @return array
	 */
	public function get_paypal_settings() {
		$wc_paypal_gateway = new WC_Gateway_Paypal();
		return $wc_paypal_gateway->settings;
	}

	/**
	 * Check whether it is PayPal Subscription or not.
	 *
	 * @return boolean
	 */
	public function is_paypal_subscription() {
		$payment_type   = sumo_get_payment_type( $this->subscription_id );
		$payment_method = sumo_get_subscription_payment( $this->subscription_id, 'payment_method' );

		if ( $this->gateway_id === $payment_method && 'auto' === $payment_type ) {
			return true;
		}

		return false;
	}

	/**
	 * Get payment Order
	 *
	 * @return object|false
	 */
	public function get_payment_order() {
		$this->payment_order = wc_get_order( $this->payment_order_id );
		return $this->payment_order;
	}

	public function get_subscribed_product_id() {
		return is_callable( array( $this->subscription, 'get_subscribed_product' ) ) ? $this->subscription->get_subscribed_product() : $this->subscription->get_id();
	}

	/**
	 * Get the Subscription Order Item.
	 *
	 * @return array
	 */
	public function get_subscription_order_item() {
		foreach ( $this->payment_order->get_items() as $item ) {
			if ( ! empty( $item[ 'product_id' ] ) ) {
				$product_id = $item[ 'variation_id' ] > 0 ? $item[ 'variation_id' ] : $item[ 'product_id' ];
				$product_id = sumosubs_wpml_maybe_get_translated_product_id( $product_id );

				if ( $product_id == $this->get_subscribed_product_id() ) {
					return $item;
				}
			}
		}

		return array();
	}

	/**
	 * Get Subscription
	 *
	 * @return array
	 */
	public function get_subscription() {
		if ( $this->subscription_id > 0 ) {
			$this->subscription = sumo_get_subscription( $this->subscription_id );
		} else {
			$subscription_products = sumo_pluck_subscription_products( $this->payment_order );
			$this->subscription    = sumo_get_subscription_product( current( $subscription_products ) );
		}

		return $this->subscription;
	}

	public function get_renewal_amount( $order_item ) {
		$qty             = ! empty( $order_item[ 'qty' ] ) ? absint( $order_item[ 'qty' ] ) : 1;
		$recurring_total = floatval( $this->subscription->get_recurring_amount() * $qty );

		if ( 'yes' !== get_option( 'woocommerce_prices_include_tax' ) ) {
			$recurring_total += $this->payment_order->get_line_tax( $order_item );
		}

		$recurring_total += ( $this->payment_order->get_shipping_total() + $this->payment_order->get_shipping_tax() );
		return $recurring_total;
	}

	/**
	 * Setup Subscription.
	 *
	 * @param array $paypal_args
	 * @return array
	 */
	public function set_up_future_payments( $paypal_args ) {
		//set recurring payment method
		$paypal_args[ 'cmd' ]       = '_xclick-subscriptions';
		/* translators: 1: order id */
		$paypal_args[ 'item_name' ] = sprintf( __( 'Order #%s', 'sumosubscriptions' ), $this->payment_order_id );
		return $paypal_args;
	}

	/**
	 * Setup Trial parameters.
	 *
	 * @param array $paypal_args
	 * @return array
	 */
	public function set_up_trial_parameters( $paypal_args ) {
		if ( $this->subscription_id ) {
			unset( $paypal_args[ 'a1' ], $paypal_args[ 'p1' ], $paypal_args[ 't1' ] );
			return $paypal_args;
		}

		$order_total     = $this->number_format( $this->payment_order->get_total() );
		$recurring_total = $this->number_format( $this->get_renewal_amount( $this->get_subscription_order_item() ) );
		$customer_id     = $this->payment_order ? $this->payment_order->get_customer_id() : 0;
		$trial_enabled   = $this->subscription->get_trial( 'forced' ) && sumo_can_purchase_subscription_trial( $this->get_subscribed_product_id(), $customer_id );

		if ( $recurring_total != $order_total || $trial_enabled ) {
			$paypal_args[ 'a1' ] = $order_total;

			if ( $trial_enabled ) {
				$paypal_args[ 'p1' ] = $this->subscription->get_trial( 'duration_period_length' );
				$paypal_args[ 't1' ] = $this->subscription->get_trial( 'duration_period' );
			} else {
				$paypal_args[ 'p1' ] = $this->subscription->get_duration_period_length();
				$paypal_args[ 't1' ] = $this->subscription->get_duration_period();
			}
		}

		return $paypal_args;
	}

	/**
	 * Setup Subscription parameters.
	 *
	 * @param array $paypal_args
	 * @return array
	 */
	public function set_up_subscription_parameters( $paypal_args ) {
		if ( ! $this->subscription_id && isset( $paypal_args[ 'a1' ], $paypal_args[ 'p1' ], $paypal_args[ 't1' ] ) ) {
			$recurring_total = $this->get_renewal_amount( $this->get_subscription_order_item() );
		} else {
			$recurring_total = $this->payment_order->get_total();
		}

		if ( is_numeric( $recurring_total ) && $recurring_total && is_numeric( $this->subscription->get_duration_period_length() ) ) {
			$paypal_args[ 'a3' ] = $this->number_format( $recurring_total );
			$paypal_args[ 'p3' ] = $this->subscription->get_duration_period_length();
			$paypal_args[ 't3' ] = $this->subscription->get_duration_period();
		}

		return $paypal_args;
	}

	/**
	 * Setup Subscription Installment.
	 *
	 * @param array $paypal_args
	 * @return array
	 */
	public function set_up_recurring_parameters( $paypal_args ) {
		/* $paypal_args[ 'src' ] -> 0 For Non Recurring and 1 for Recurring. */
		$paypal_args[ 'src' ] = 1;
		//Limited Intervals.
		if ( $this->subscription->get_installments() > 0 ) {
			// If the Subscription is having more than 1 Installments.
			if ( $this->subscription->get_installments() > 1 ) {
				$paypal_args[ 'src' ] = 1;
				$paypal_args[ 'srt' ] = $this->subscription->get_installments();

				//Incase of Payment method Switch it may happens.
				if ( 'Trial' !== get_post_meta( $this->subscription_id, 'sumo_get_status', true ) && $this->subscription_id > 0 ) {
					//Since the Parent Order has not been paid through PayPal Subscription, reduce 1 from the Limited Subscription Intervals.
					$remaining_intervals   = $this->subscription->get_installments() - 1;
					$remaining_intallments = $remaining_intervals - sumosubs_get_renewed_count( $this->subscription_id );

					if ( is_numeric( $remaining_intallments ) && $remaining_intallments > 1 ) {
						$paypal_args[ 'srt' ] = $remaining_intallments;
					} else {
						$paypal_args[ 'src' ] = 0;
					}
				}
			} else {
				//If the Subscription is having 1 Installment.
				$paypal_args[ 'src' ] = 0;
			}
		}
		$paypal_args[ 'rm' ] = 2;
		return $paypal_args;
	}

	/**
	 * Request PayPal and retrieve response via cURL by Subscription Profile ID.
	 *
	 * @param string $action
	 * @param string $note
	 * @param string $method CreateRecurringPaymentsProfile | ManageRecurringPaymentsProfileStatus | GetRecurringPaymentsProfileDetails
	 * @return array
	 */
	public function request_paypal( $action = '', $note = '', $method = 'ManageRecurringPaymentsProfileStatus' ) {
		$paypal_settings = $this->get_paypal_settings();

		if ( 'yes' === $paypal_settings[ 'testmode' ] ) {
			$endpoint = 'https://api-3t.sandbox.paypal.com/nvp'; // sandbox url
			$prefix   = 'sandbox_';
		} else {
			$endpoint = 'https://api-3t.paypal.com/nvp'; //live url
			$prefix   = '';
		}

		$data = array(
			'USER'      => $paypal_settings[ "{$prefix}api_username" ],
			'PWD'       => $paypal_settings[ "{$prefix}api_password" ],
			'SIGNATURE' => $paypal_settings[ "{$prefix}api_signature" ],
			'METHOD'    => $method,
			'PROFILEID' => urlencode( $this->get_profile_id() ),
			'VERSION'   => '95.0',
		);

		switch ( $method ) {
			case 'ManageRecurringPaymentsProfileStatus':
				$data[ 'ACTION' ] = $action;
				$data[ 'NOTE' ]   = $note;
				break;
			case 'GetRecurringPaymentsProfileDetails':
			case 'CreateRecurringPaymentsProfile':
				break;
		}

		//Get NVP Reponse via cURL.
		$nvp_response = sumo_get_cURL_response( $endpoint, array(), $data );
		parse_str( $nvp_response, $parsed_response );
		$this->set_error_log( $parsed_response );
		return $parsed_response;
	}

	/**
	 * Check PayPal response Successful
	 *
	 * @param array $parsed_response
	 * @return boolean
	 */
	public function is_ACK_success( $parsed_response ) {
		return ! empty( $parsed_response[ 'ACK' ] ) && 'Success' === $parsed_response[ 'ACK' ];
	}

	/**
	 * Retrieve Subscription Profile Info.
	 *
	 * @return array
	 */
	public function get_subscription_profile_details() {
		$profile = $this->request_paypal( '', '', 'GetRecurringPaymentsProfileDetails' );
		return $profile;
	}

	/**
	 * Log every incoming IPN response.
	 *
	 * @param array $posted
	 */
	public function log_response( $posted ) {
		$this->set_error_log( $posted );

		//Log IPN response belongs to Subscription.
		$this_ipn_data     = array( $posted );
		$previous_ipn_data = get_post_meta( $this->subscription_id, 'sumo_subscription_ipn_data', true );

		if ( is_array( $previous_ipn_data ) && ! empty( $previous_ipn_data ) ) {
			update_post_meta( $this->subscription_id, 'sumo_subscription_ipn_data', array_merge( ( array ) $previous_ipn_data, $this_ipn_data ) );
		}

		add_post_meta( $this->subscription_id, 'sumo_subscription_ipn_data', $this_ipn_data );
		update_option( 'sumosubscription_paypal_std_subscription_payment', $posted );
	}

	/**
	 * Add Subscription note with order note
	 *
	 * @param string $note
	 * @param string $evt
	 */
	public function add_note( $note, $evt ) {
		$status = get_post_meta( $this->subscription_id, 'sumo_get_status', true );
		sumo_add_subscription_note( $note, $this->subscription_id, sumo_note_status( $status ), $evt );
	}

	/**
	 * Get Error message with Code thrown from PayPal
	 *
	 * @param array $posted
	 * @return string
	 */
	public function get_error_message( $posted ) {
		$long_message = __( 'Something Went Wrong!!', 'sumosubscriptions' );

		if ( ! empty( $posted[ 'L_LONGMESSAGE0' ] ) ) {
			$error_code   = ! empty( $posted[ 'L_ERRORCODE0' ] ) ? '#' . $posted[ 'L_ERRORCODE0' ] : '';
			$long_message = $error_code . ' ' . $posted[ 'L_LONGMESSAGE0' ];
		}

		return $long_message;
	}

	/**
	 * Set Error Log with WC_Logger
	 *
	 * @param array $posted
	 * @return boolean
	 */
	public function set_error_log( $posted ) {
		if ( empty( $posted[ 'L_LONGMESSAGE0' ] ) ) {
			return false;
		}

		/* translators: 1: paypal error message */
		$this->add_note( sprintf( __( 'PayPal Error: <b>%s</b>', 'sumosubscriptions' ), $this->get_error_message( $posted ) ), __( 'PayPal Automatic Renewal Unsuccessful', 'sumosubscriptions' ) );

		include_once SUMO_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/log-handlers/class-sumosubs-logger.php';
		SUMOSubs_Logger::log( $posted, array(
			'subscription_id' => $this->subscription_id,
			'order_id'        => $this->payment_order_id,
		) );
		return true;
	}

	/**
	 * Request PayPal to Cancel the Subscription.
	 *
	 * @return array
	 */
	public function request_cancel() {
		$action  = 'Cancel';
		/* translators: 1: subscription id */
		$note    = sprintf( __( 'Subscription #%s has been Cancelled', 'sumosubscriptions' ), $this->subscription_id );
		$request = $this->request_paypal( $action, $note );
		return $request;
	}

	/**
	 * Request PayPal to Suspend the Subscription.
	 *
	 * @return array
	 */
	public function request_suspend() {
		$action  = 'Suspend';
		/* translators: 1: subscription id */
		$note    = sprintf( __( 'Subscription #%s has been Suspended', 'sumosubscriptions' ), $this->subscription_id );
		$request = $this->request_paypal( $action, $note );
		return $request;
	}

	/**
	 * Request PayPal to Reactivate the Subscription.
	 *
	 * @return array
	 */
	public function request_reactivate() {
		$action  = 'Reactivate';
		/* translators: 1: subscription id */
		$note    = sprintf( __( 'Subscription #%s has been Reactivated', 'sumosubscriptions' ), $this->subscription_id );
		$request = $this->request_paypal( $action, $note );
		return $request;
	}

	/**
	 * Check if currency has decimals.
	 *
	 * @param  string $currency Currency to check.
	 * @return bool
	 */
	protected function currency_has_decimals( $currency ) {
		if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD' ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Format prices.
	 *
	 * @param  float|int $price Price to format.
	 * @return string
	 */
	protected function number_format( $price ) {
		return number_format( $price, 2, '.', '' );
	}
}
