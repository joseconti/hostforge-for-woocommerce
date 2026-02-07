<?php

/**
 * PayPal Adaptive Preapproval API handler.
 * 
 * @class SUMO_PayPal_Preapproval_API
 */
class SUMO_PayPal_Preapproval_API {

	protected $adaptive;
	protected $payment_order_id                    = 0;
	protected $payment_order                       = false;
	protected $subscription_id                     = 0;
	protected $sandbox                             = false;
	protected $api_credentials                     = array();
	protected $startDate                           = '';
	protected $endDate                             = '';
	protected $include_validity_period             = '';
	protected $include_maxTotalAmountOfAllPayments = '';
	protected $maxTotalAmountOfAllPayments_value   = '';

	/**
	 * SUMO_PayPal_Preapproval_API constructor.
	 */
	public function __construct( SUMO_PayPal_Adaptive_Gateway $adaptive ) {
		$this->adaptive        = $adaptive;
		$this->api_credentials = array(
			'X-PAYPAL-SECURITY-USERID'      => $this->adaptive->api_user_id,
			'X-PAYPAL-SECURITY-PASSWORD'    => $this->adaptive->api_password,
			'X-PAYPAL-SECURITY-SIGNATURE'   => $this->adaptive->api_signature,
			'X-PAYPAL-APPLICATION-ID'       => $this->adaptive->api_app_id,
			'X-PAYPAL-REQUEST-DATA-FORMAT'  => 'NV',
			'X-PAYPAL-RESPONSE-DATA-FORMAT' => 'JSON',
		);

		$this->sandbox                             = 'yes' === $this->adaptive->testmode;
		$this->include_validity_period             = 'yes' === $this->adaptive->include_validity_period;
		$this->include_maxTotalAmountOfAllPayments = 'yes' === $this->adaptive->include_maxTotalAmountOfAllPayments;
		$this->maxTotalAmountOfAllPayments_value   = ( float ) number_format( ( $this->adaptive->maxTotalAmountOfAllPayments_value ? $this->adaptive->maxTotalAmountOfAllPayments_value : 0 ), 2, '.', '' );

		$this->startDate = gmdate( 'Y-m-d\TH:i:s\Z' );
		if ( is_numeric( $this->adaptive->validity_period_value ) && $this->adaptive->validity_period_value > 0 ) {
			$this->endDate = gmdate( 'Y-m-d\TH:i:s\Z', sumo_get_subscription_timestamp( "+{$this->adaptive->validity_period_value} days" ) );
		}

		$this->init_hooks();
	}

	/**
	 * Init hooks
	 */
	protected function init_hooks() {
		add_action( 'init', array( $this, 'update_payment_status_via_IPN' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'update_payment_status_via_cURL' ), 0, 4 );
		add_filter( 'sumosubscriptions_is_' . $this->adaptive->id . '_preapproval_status_valid', array( $this, 'is_preapproval_status_valid' ), 10, 3 );
		add_filter( 'sumosubscriptions_is_' . $this->adaptive->id . '_preapproved_payment_transaction_success', array( $this, 'charge_renewal_order_payment' ), 10, 3 );
	}

	/**
	 * Get PayPal success URL
	 *
	 * @return string
	 */
	public function get_success_url() {
		return $this->get_payment_order() ? $this->adaptive->get_return_url( $this->get_payment_order() ) : '';
	}

	/**
	 * Get PayPal cancel URL
	 *
	 * @return string
	 */
	public function get_cancel_url() {
		if ( is_callable( array( $this->get_payment_order(), 'get_cancel_order_url' ) ) ) {
			return str_replace( '&amp;', '&', $this->get_payment_order()->get_cancel_order_url() );
		}

		return '';
	}

	/**
	 * Get PayPal Endpoint URl
	 *
	 * @param string $endpoint
	 * @return string
	 */
	public function get_endpoint_url( $endpoint = 'PreapprovalDetails' ) {
		$endpoint_url = "https://svcs.paypal.com/AdaptivePayments/$endpoint ";

		if ( $this->sandbox ) {
			$endpoint_url = "https://svcs.sandbox.paypal.com/AdaptivePayments/$endpoint ";
		}

		return $endpoint_url;
	}

	/**
	 * Get PayPal payment URl
	 *
	 * @return string
	 */
	public function get_payment_url() {
		//may be Automatic payment mode is chosen
		if ( $this->is_auto_payment_type() ) {
			$payment_url = $this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_ap-preapproval&preapprovalkey=' : 'https://www.paypal.com/cgi-bin/webscr?cmd=_ap-preapproval&preapprovalkey=';
		} else {
			//may be Manual payment mode is chosen
			$payment_url = $this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_ap-payment&paykey=' : 'https://www.paypal.com/cgi-bin/webscr?cmd=_ap-payment&paykey=';
		}

		return $payment_url . $this->get_paymentKey();
	}

	/**
	 * Get saved PayPal payment key
	 * may be either preapprovalKey/payKey
	 * 
	 * @return string
	 */
	public function get_paymentKey() {
		$checkout_transient = $this->get_checkout_transient();

		//may be Automatic payment mode is chosen
		if ( isset( $checkout_transient[ 'preapprovalKey' ] ) ) {
			$paymentKey = $checkout_transient[ 'preapprovalKey' ];
		} else if ( isset( $checkout_transient[ 'payKey' ] ) ) {
			//may be Manual payment mode is chosen
			$paymentKey = $checkout_transient[ 'payKey' ];
		} elseif ( $this->subscription_id > 0 ) {
				$paymentKey = sumo_get_subscription_payment( $this->subscription_id, 'payment_key' );
		} else {
			$paymentKey = sumo_get_subscription_order_payment( $this->payment_order_id, 'payment_key' );
		}

		return $paymentKey;
	}

	/**
	 * Get PayPal IPN notification URl
	 *
	 * @return string
	 */
	public function get_ipnNotificationUrl() {
		return esc_url_raw( add_query_arg( array( 'ipn' => 'set', 'self_custom' => $this->payment_order_id ), home_url( '/' ) ) );
	}

	/**
	 * Get payment Order
	 *
	 * @return object|false
	 */
	public function get_payment_order() {
		if ( ! $this->payment_order ) {
			$this->payment_order = $this->payment_order_id > 0 ? wc_get_order( $this->payment_order_id ) : false;
		}

		return $this->payment_order;
	}

	/**
	 * Get payment Order amount
	 *
	 * @return int
	 */
	public function get_payment_amount() {
		return is_callable( array( $this->get_payment_order(), 'get_total' ) ) ? round( $this->get_payment_order()->get_total(), 2 ) : 0;
	}

	/**
	 * Get Parent order ID
	 *
	 * @return int
	 */
	public function get_parent_order_id() {
		return sumosubs_get_parent_order_id( $this->get_payment_order() );
	}

	/**
	 * Get checkout transient data
	 *
	 * @return array|string
	 */
	public function get_checkout_transient() {
		$order              = $this->get_payment_order();
		$checkout_transient = $order ? maybe_unserialize( $order->get_meta( 'sumosubscription_checkout_transient_data', true ) ) : '';
		return is_array( $checkout_transient ) ? $checkout_transient : null;
	}

	/**
	 * Get Payment order method
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return is_callable( array( $this->get_payment_order(), 'get_payment_method' ) ) ? $this->get_payment_order()->get_payment_method() : '';
	}

	/**
	 * Get Payment transaction ID
	 *
	 * @param array $payment_data requested PAY call response from PayPal
	 * @return string
	 */
	public function get_payment_transaction_id( $payment_data ) {
		return isset( $payment_data[ 'paymentInfoList' ][ 'paymentInfo' ][ 0 ][ 'transactionId' ] ) ? $payment_data[ 'paymentInfoList' ][ 'paymentInfo' ][ 0 ][ 'transactionId' ] : '';
	}

	/**
	 * Set payment Order ID
	 *
	 * @param int $order_id
	 */
	public function set_order_id( $order_id ) {
		$this->payment_order_id = absint( $order_id );
	}

	/**
	 * Set subscription ID
	 *
	 * @param int $subscription_id
	 */
	public function set_subscription_id( $subscription_id ) {
		$this->subscription_id = absint( $subscription_id );
	}

	/**
	 * Check Payment order contains subscriptions
	 *
	 * @return bool
	 */
	public function payment_order_has_subscriptions() {
		return sumo_order_contains_subscription( $this->get_payment_order() );
	}

	/**
	 * Check it is Automatic payment mode
	 *
	 * @param int $subscription_id
	 * @return bool
	 */
	public function is_auto_payment_type( $subscription_id = 0 ) {
		$checkout_transient = $this->get_checkout_transient();

		//may be Automatic payment mode is chosen in checkout
		if ( isset( $checkout_transient[ 'preapprovalKey' ] ) ) {
			return true;
		}
		return 'auto' === sumo_get_payment_type( $subscription_id ? $subscription_id : $this->subscription_id ) || 'auto' === sumo_get_subscription_order_payment( $this->payment_order_id, 'payment_type' );
	}

	/**
	 * Check PayPal acknowledged success
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function is_ack_success( $data ) {
		if ( isset( $data[ 'error' ][ 0 ] ) ) {
			return false;
		}
		if ( ! isset( $data[ 'responseEnvelope' ][ 'ack' ] ) ) {
			return false;
		}

		if ( in_array( $data[ 'responseEnvelope' ][ 'ack' ], array( 'Success', 'SuccessWithWarning' ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check the initial payment happened Successful
	 *
	 * @return bool
	 */
	public function is_initial_charging_success() {
		$parent_order = wc_get_order( $this->get_parent_order_id() );
		$payment_data = $parent_order ? $parent_order->get_meta( 'sumosubscriptions_preapproval_charging_status_from_adaptive_payment', true ) : array();
		return $this->is_payment_transaction_success( $payment_data );
	}

	/**
	 * Check whether Preapproval key is Active
	 *
	 * @return bool true on Success
	 */
	public function is_preapproval_active() {
		$data = $this->request_paypal();
		if ( ! isset( $data[ 'approved' ] ) || ! isset( $data[ 'status' ] ) ) {
			return false;
		}

		if ( 'true' === $data[ 'approved' ] && 'ACTIVE' === $data[ 'status' ] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether PAY call is requested without any Transaction Errors
	 *
	 * @param array $payment_data requested PAY call response from PayPal
	 * @return bool true on Success
	 */
	public function is_payment_transaction_success( $payment_data ) {
		if ( ! $this->is_ack_success( $payment_data ) ) {
			return false;
		}

		//may be Preapproved payment .
		if ( isset( $payment_data[ 'paymentExecStatus' ] ) && in_array( $payment_data[ 'paymentExecStatus' ], array( 'COMPLETED', 'PROCESSING' ) ) ) {
			return true;
		} else if ( isset( $payment_data[ 'status' ] ) && 'COMPLETED' === $payment_data[ 'status' ] ) {
			//may be Split payment
			return true;
		}

		return false;
	}

	/**
	 * Save the Subscription checkout data during the payment Order gets processed in the Checkout
	 */
	public function save_checkout_payment_data() {
		if ( is_null( $this->get_checkout_transient() ) ) {
			return;
		}

		$this->get_payment_order();
		if ( ! $this->payment_order ) {
			return;
		}

		if ( $this->is_auto_payment_type() ) {
			$payment_type = 'auto';
		} else {
			$payment_type = 'manual';
		}

		sumo_save_subscription_payment_info( $this->payment_order, array(
			'payment_type'   => $payment_type,
			'payment_method' => $this->get_payment_method(),
			'payment_key'    => $this->get_paymentKey(),
		) );

		// clear cache
		$this->payment_order->delete_meta_data( 'sumosubscription_checkout_transient_data' );
		$this->payment_order->save();
	}

	/**
	 * Get maxNumberOfPayments to request PayPal Preapproval.
	 *
	 * @return float
	 */
	public function get_maxNumberOfPayments() {
		if ( $this->subscription_id > 0 ) {
			$subscription_duration_timestamp = sumo_get_subscription_cycle( get_post_meta( $this->subscription_id, 'sumo_subscr_plan', true ) );
			$maxNumberOfPayments             = ceil( ( sumo_get_subscription_timestamp( '+1 year' ) - sumo_get_subscription_timestamp() ) / $subscription_duration_timestamp );
			return $maxNumberOfPayments;
		}

		$customer_id = is_callable( array( $this->get_payment_order(), 'get_customer_id' ) ) ? $this->get_payment_order()->get_customer_id() : 0;
		if ( SUMOSubs_Order_Subscription::is_subscribed( 0, 0, $customer_id ) ) {
			$subscription_details            = sumo_get_subscription_plan( 0, 0, $customer_id );
			$subscription_duration_timestamp = sumo_get_subscription_cycle( $subscription_details[ 'subscription_duration_value' ] . ' ' . $subscription_details[ 'subscription_duration' ] );
			$maxNumberOfPayments             = ceil( ( sumo_get_subscription_timestamp( '+1 year' ) - sumo_get_subscription_timestamp() ) / $subscription_duration_timestamp );
			return $maxNumberOfPayments;
		}

		$maxNumberOfPayments   = 0;
		$temp                  = 0;
		$subscription_products = sumo_pluck_subscription_products( $this->get_payment_order() );

		if ( $subscription_products ) {
			foreach ( $subscription_products as $product_id ) {
				$subscription_details            = sumo_get_subscription_plan( 0, $product_id );
				$subscription_plan               = '1' === $subscription_details[ 'trial_status' ] ? $subscription_details[ 'trial_duration_value' ] . ' ' . $subscription_details[ 'trial_duration' ] : $subscription_details[ 'subscription_duration_value' ] . ' ' . $subscription_details[ 'subscription_duration' ];
				$subscription_duration_timestamp = sumo_get_subscription_cycle( $subscription_plan );
				$temp                            = ceil( ( sumo_get_subscription_timestamp( '+1 year' ) - sumo_get_subscription_timestamp() ) / $subscription_duration_timestamp );

				if ( $maxNumberOfPayments < $temp ) {
					$maxNumberOfPayments = $temp;
				}
			}
		}

		return $maxNumberOfPayments;
	}

	/**
	 * Request PAY call. And set Error notes if applicable
	 *
	 * @param bool $update_order_status Whether to Update order status
	 * @return PayPal nvp response in array
	 */
	public function request_PAY( $update_order_status = true ) {
		$payment_data = array();

		if ( 0 === $this->subscription_id && sumosubs_is_renewal_order( $this->get_payment_order() ) ) {
			$this->subscription_id = sumosubs_get_subscription_id_from_renewal_order( $this->payment_order_id );
		}

		if ( $this->get_payment_amount() > 0 ) {
			if ( $this->is_auto_payment_type() ) {
				//Request Pay.
				$payment_data = $this->request_paypal( 'Pay' );
			} else {
				//Retrieve Payment data.
				$payment_data = $this->request_paypal( 'PaymentDetails' );
			}
		}
		//set transaction ID
		sumosubs_save_transaction_id( $this->get_payment_order(), $this->get_payment_transaction_id( $payment_data ), true );

		if ( $update_order_status ) {
			$this->update_order_status( $payment_data );
		}

		return $payment_data;
	}

	/**
	 * Check payment status and Update Order status
	 *
	 * @param nvp $payment_data PayPal nvp response
	 */
	public function update_order_status( $payment_data ) {
		$this->save_checkout_payment_data();

		if ( $this->get_payment_amount() > 0 ) {
			if ( $this->is_payment_transaction_success( $payment_data ) ) {
				//Update parent Order status to add new/update existing Subscriptions.
				$this->get_payment_order()->payment_complete();
			} else {
				$this->get_payment_order()->update_status( 'failed' );
			}
		} else {
			$this->get_payment_order()->payment_complete();
		}
	}

	/**
	 * By using valid Preapproval key, request PayPal Adaptive Payment API to retrive or preapprove or pay the Payment Details.
	 * Get PayPal response via cURL.
	 * 
	 * @param string $endpoint
	 * @param string $placed_by whether User is currently placed through Payment Gateway or not
	 * @return PayPal nvp response in array
	 */
	public function request_paypal( $endpoint = 'PreapprovalDetails', $placed_by = '' ) {
		//Pass Endpoint to Request Data.
		switch ( $endpoint ) {
			case 'Preapproval':
				//Create Preapproval Job incase of Automatic Payment.
				$request_data = array(
					'returnUrl'                     => $this->get_success_url(),
					'cancelUrl'                     => $this->get_cancel_url(),
					'startingDate'                  => $this->startDate,
					'currencyCode'                  => get_woocommerce_currency(),
					'custom'                        => $this->payment_order_id,
					'ipnNotificationUrl'            => $this->get_ipnNotificationUrl(),
					'requestEnvelope.errorLanguage' => 'en_US',
				);

				if ( $this->include_validity_period ) {
					$request_data[ 'endingDate' ] = $this->endDate;
				}
				if ( $this->include_maxTotalAmountOfAllPayments ) {
					$request_data[ 'maxTotalAmountOfAllPayments' ] = $this->maxTotalAmountOfAllPayments_value;
				}
				if ( $this->include_validity_period && $this->include_maxTotalAmountOfAllPayments ) {
					$maxNumberOfPayments = $this->get_maxNumberOfPayments();

					if ( $this->get_payment_amount() > 0 ) {
						$request_data[ 'maxAmountPerPayment' ] = $this->get_payment_amount();
					}

					$request_data[ 'maxNumberOfPayments' ]          = $maxNumberOfPayments;
					$request_data[ 'maxNumberOfPaymentsPerPeriod' ] = $maxNumberOfPayments;
				}
				break;
			case 'Pay':
				//To Automatically Preapprove the Renewal Payment. Let us consider the Preapproval Key.
				$request_data = array(
					'actionType'                      => 'PAY',
					'memo'                            => 'PREAPPROVAL',
					'currencyCode'                    => get_woocommerce_currency(),
					'preapprovalKey'                  => $this->get_paymentKey(),
					'receiverList.receiver(0).amount' => $this->get_payment_amount(),
					'receiverList.receiver(0).email'  => $this->adaptive->primary_mail,
					'returnUrl'                       => $this->get_success_url(),
					'cancelUrl'                       => $this->get_cancel_url(),
					'custom'                          => $this->payment_order_id,
					'requestEnvelope.errorLanguage'   => 'en_US',
				);

				//Let us consider as Manual Payment(Adaptive Split).
				if ( 'user' === $placed_by || ! $this->is_auto_payment_type() ) {
					$request_data[ 'ipnNotificationUrl' ] = $this->get_ipnNotificationUrl();
					unset( $request_data[ 'preapprovalKey' ] );
					unset( $request_data[ 'memo' ] );
				}
				break;
			case 'PaymentDetails':
				$request_data = array(
					'payKey'                        => $this->get_paymentKey(),
					'requestEnvelope.errorLanguage' => 'en_US',
				);
				break;
			case 'PreapprovalDetails':
			case 'CancelPreapproval':
				$request_data = array(
					'preapprovalKey'                => $this->get_paymentKey(),
					'requestEnvelope.errorLanguage' => 'en_US',
				);
				break;
			default:
				$request_data = array();
				break;
		}

		//Get Response via cURL.
		$data         = json_decode( sumo_get_cURL_response( $this->get_endpoint_url( $endpoint ), $this->api_credentials, $request_data ), true );
		$parent_order = wc_get_order( $this->get_parent_order_id() );

		switch ( $endpoint ) {
			case 'Preapproval':
				//record Preapproval data
				$parent_order->update_meta_data( 'sumosubscriptions_preapproval_status_from_adaptive_payment', $data );
				$parent_order->save();
				break;
			case 'Pay':
				//record Preapproved payment data
				$parent_order->update_meta_data( 'sumosubscriptions_preapproval_charging_status_from_adaptive_payment', $data );
				$parent_order->save();
				break;
		}

		//may be Error response throws from PayPal
		$this->set_error_note( $data );
		return $data;
	}

	/**
	 * Get error message thrown from PayPal
	 *
	 * @param array $data
	 * @return string
	 */
	public function get_error_message( $data ) {
		$message = 'Something Went Wrong';

		if ( isset( $data[ 'error' ][ 0 ][ 'errorId' ] ) ) {
			$message = $data[ 'error' ][ 0 ][ 'errorId' ] . ' ' . $data[ 'error' ][ 0 ][ 'message' ];
		}

		return $message;
	}

	/**
	 * Set PayPal throws Error message as Subscription notes
	 *
	 * @param array $data requested PAY call response from PayPal
	 * @return bool
	 */
	public function set_error_note( $data ) {
		if ( $this->is_ack_success( $data ) ) {
			return false;
		}

		include_once SUMO_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/log-handlers/class-sumosubs-logger.php';
		SUMOSubs_Logger::log( $data, array(
			'subscription_id' => $this->subscription_id,
			'order_id'        => $this->payment_order_id,
		) );

		/* translators: 1: error */
		$message = sprintf( __( 'PayPal error: <b>%s</b>', 'sumosubscriptions' ), $this->get_error_message( $data ) );
		$event   = __( 'PayPal error', 'sumosubscriptions' );

		if ( $this->subscription_id > 0 ) {
			sumo_add_subscription_note( "$message", $this->subscription_id, 'failure', $event );
		} else {
			$subscriptions = sumosubscriptions()->query->get( array(
				'type'       => 'sumosubscriptions',
				'status'     => 'publish',
				'meta_key'   => 'sumo_get_parent_order_id',
				'meta_value' => $this->get_parent_order_id(),
					) );

			foreach ( $subscriptions as $subscription_id ) {
				if ( ! is_numeric( $subscription_id ) || ! $subscription_id ) {
					continue;
				}

				if ( ! $this->is_auto_payment_type( $subscription_id ) ) {
					$event = __( 'PayPal payment unsuccessful', 'sumosubscriptions' );
				}
				sumo_add_subscription_note( "$message", $subscription_id, 'failure', $event );
			}
		}

		return true;
	}

	/**
	 * Check Adaptive preapproval status is valid
	 *
	 * @param bool $bool The Subscription post ID
	 * @param int $subscription_id
	 * @param object $payment_order
	 * @return bool true upon preapproval is valid
	 */
	public function is_preapproval_status_valid( $bool, $subscription_id, $payment_order ) {
		$this->set_order_id( $payment_order->get_id() );
		$this->set_subscription_id( $subscription_id );

		if ( $this->is_preapproval_active() ) {
			//may be valid to charge the payment
			return true;
		}
		return false;
	}

	/**
	 * Charge the customer for making the renewals to happen
	 * If Preapproval Status is valid then Automatic Subscription Renewal Success otherwise switch Subscription either with Manual Payment/Cancel.
	 * 
	 * @param bool $bool The Subscription post ID
	 * @param int $subscription_id
	 * @param object $payment_order
	 * @return bool true upon payment success
	 */
	public function charge_renewal_order_payment( $bool, $subscription_id, $payment_order ) {
		$this->set_order_id( $payment_order->get_id() );
		$this->set_subscription_id( $subscription_id );

		$payment_data = $this->request_PAY( false );
		if ( $this->is_payment_transaction_success( $payment_data ) ) {
			//Recurring payment Success
			return true;
		}
		return false;
	}

	/**
	 * Request PAY call. Process Subscription order status via IPN.
	 */
	public function update_payment_status_via_IPN() {
		$get = $_GET;
		if ( ! isset( $get[ 'ipn' ] ) || ! isset( $get[ 'self_custom' ] ) ) {
			return;
		}

		//Check HTTP response status.
		header( 'HTTP/1.1 200 OK' );

		$preapproval_data     = array();
		$preapproved_order_id = $get[ 'self_custom' ]; //may be Parent or Renewal Order.
		$received_post        = file_get_contents( 'php://input' ); // Adaptive Payment ipn message is different from normal paypal so we handle like this
		$received_raw_post    = explode( '&', $received_post );

		foreach ( $received_raw_post as $keyval ) {
			$keyval = explode( '=', $keyval );
			if ( 2 === count( $keyval ) ) {
				$preapproval_data[ urldecode( $keyval[ 0 ] ) ] = urldecode( $keyval[ 1 ] );
			}
		}

		$order = wc_get_order( $preapproved_order_id );
		if ( ! $order ) {
			return;
		}

		$this->set_order_id( $preapproved_order_id );

		if ( isset( $preapproval_data[ 'status' ] ) &&
				! sumosubs_is_order_paid( $order ) &&
				$this->adaptive->id === $this->get_payment_method() &&
				! $this->is_initial_charging_success()
		) {
			//may be Preapproved Payment.
			if ( 'true' === $preapproval_data[ 'approved' ] && 'ACTIVE' === $preapproval_data[ 'status' ] ) {
				//Request Pay.
				$this->request_PAY();
				//may be Split Payment.
			} else {
				$this->update_order_status( $preapproval_data );
			}
		}
		//may be Error response throws from PayPal
		$this->set_error_note( $preapproval_data );

		$parent_order = wc_get_order( $this->get_parent_order_id() );
		if ( $parent_order ) {
			//record Preapproval data
			$parent_order->update_meta_data( 'sumosubscriptions_preapproval_status_from_adaptive_payment', $preapproval_data );
			$parent_order->save();
		}
	}

	/**
	 * Request PAY call. Process Subscription order status via cURL.
	 *
	 * @param int $order_id The Order post ID
	 * @param string $old_order_status The Order post ID Default null
	 * @param string $new_order_status The Order post ID Default null
	 */
	public function update_payment_status_via_cURL( $order_id, $old_order_status, $new_order_status, $order ) {
		if ( $this->adaptive->id !== $order->get_payment_method() ) {
			return;
		}

		if ( 0 === absint( $order->get_meta( 'sumosubs_adaptive_payment_recurrence', true ) ) ) {
			$order->update_meta_data( 'sumosubs_adaptive_payment_recurrence', '1' );
		} else {
			$order->update_meta_data( 'sumosubs_adaptive_payment_recurrence', '2' );
		}

		$order->save();

		if ( 2 === absint( $order->get_meta( 'sumosubs_adaptive_payment_recurrence', true ) ) ) {
			return;
		}

		$this->set_order_id( $order_id );

		if ( ! $this->is_initial_charging_success() ) {
			if ( is_admin() ) {
				if ( in_array( $new_order_status, array( 'completed', 'processing' ) ) ) {
					//Request Pay.
					$this->request_PAY( false );
				}
			} else if ( ! in_array( $new_order_status, array( 'completed', 'processing' ) ) ) {
				//Request Pay.
				$this->request_PAY();
			}
		}
	}
}
