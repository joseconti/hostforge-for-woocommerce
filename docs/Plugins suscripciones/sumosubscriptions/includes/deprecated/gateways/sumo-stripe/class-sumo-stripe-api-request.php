<?php

/**
 * Stripe API Request.
 * 
 * @class SUMO_Stripe_API_Request
 */
class SUMO_Stripe_API_Request {

	protected static $sandbox = false ;
	protected static $secret_key ;
	protected static $logger  = '' ;

	/**
	 * Init the SUMO_Stripe_API_Request
	 */
	public static function init( SUMO_Stripe_Gateway $stripe_gateway ) {
		self::$sandbox    = ( bool ) $stripe_gateway->testmode ;
		self::$secret_key = self::$sandbox ? $stripe_gateway->testsecretkey : $stripe_gateway->livesecretkey ;

		//make sure the classes are load once
		if ( ! class_exists( '\Stripe\Stripe', false ) ) {
			require_once 'vendor/Stripe.php' ;
		}

		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			throw new Exception( __( 'Cannot connect to Stripe.', 'sumosubscriptions' ) ) ;
		}

		//Set Stripe API key
		if ( is_wp_error( self::request( 'set_apiKey' ) ) ) {
			throw new Exception( self::get_last_error_message() ) ;
		}
	}

	/**
	 * Localize Stripe messages based on code
	 *
	 * @return array
	 */
	public static function get_localized_messages() {
		return apply_filters( 'sumosubs_stripe_localized_messages', array(
			'invalid_number'           => __( 'The card number is not a valid credit card number.', 'sumosubscriptions' ),
			'invalid_expiry_month'     => __( 'The card\'s expiration month is invalid.', 'sumosubscriptions' ),
			'invalid_expiry_year'      => __( 'The card\'s expiration year is invalid.', 'sumosubscriptions' ),
			'invalid_cvc'              => __( 'The card\'s security code is invalid.', 'sumosubscriptions' ),
			'incorrect_number'         => __( 'The card number is incorrect.', 'sumosubscriptions' ),
			'incomplete_number'        => __( 'The card number is incomplete.', 'sumosubscriptions' ),
			'incomplete_cvc'           => __( 'The card\'s security code is incomplete.', 'sumosubscriptions' ),
			'incomplete_expiry'        => __( 'The card\'s expiration date is incomplete.', 'sumosubscriptions' ),
			'expired_card'             => __( 'The card has expired.', 'sumosubscriptions' ),
			'incorrect_cvc'            => __( 'The card\'s security code is incorrect.', 'sumosubscriptions' ),
			'incorrect_zip'            => __( 'The card\'s zip code failed validation.', 'sumosubscriptions' ),
			'invalid_expiry_year_past' => __( 'The card\'s expiration year is in the past', 'sumosubscriptions' ),
			'card_declined'            => __( 'The card was declined.', 'sumosubscriptions' ),
			'missing'                  => __( 'There is no card on a customer that is being charged.', 'sumosubscriptions' ),
			'processing_error'         => __( 'An error occurred while processing the card.', 'sumosubscriptions' ),
			'invalid_request_error'    => __( 'Unable to process this payment, please try again or use alternative method.', 'sumosubscriptions' ),
			'email_invalid'            => __( 'Invalid email address, please correct and try again.', 'sumosubscriptions' ),
				) ) ;
	}

	/**
	 * Validate Zero decimal currencies. Since it must be effective to charge the Amount in or from the Stripe.
	 * 
	 * @param mixed $total
	 * @param string $currency
	 * @return int
	 */
	protected static function prepare_amount( $total, $currency = '' ) {
		if ( ! $currency ) {
			$currency = get_woocommerce_currency() ;
		}

		switch ( strtoupper( $currency ) ) {
			// Zero decimal currencies
			case 'BIF':
			case 'CLP':
			case 'DJF':
			case 'GNF':
			case 'JPY':
			case 'KMF':
			case 'KRW':
			case 'MGA':
			case 'PYG':
			case 'RWF':
			case 'VND':
			case 'VUV':
			case 'XAF':
			case 'XOF':
			case 'XPF':
				$total = absint( $total ) ;
				break ;
			default:
				// In cents. 100 cents make 1 USD
				$total = round( $total, 2 ) * apply_filters( 'sumosubscriptions_multiplication_factor', 100, $total, $currency ) ;
				break ;
		}
		return $total ;
	}

	/**
	 * Prepare customer details
	 */
	public static function prepare_customer_details( $params, $exclude_params = array() ) {
		$default_params = array(
			'address'     => array(
				'line1'       => '',
				'line2'       => '',
				'city'        => '',
				'state'       => '',
				'postal_code' => '',
				'country'     => '',
			),
			'name'        => '',
			'phone'       => '',
			'email'       => '',
			'description' => '',
				) ;

		$params = wp_parse_args( $params, $default_params ) ;

		if ( ! empty( $params[ 'fname' ] ) ) {
			$params[ 'name' ] = $params[ 'fname' ] ;

			if ( ! empty( $params[ 'lname' ] ) ) {
				$params[ 'name' ] .= ' ' ;
				$params[ 'name' ] .= $params[ 'lname' ] ;
			}
		}

		if ( empty( $params[ 'description' ] ) ) {
			/* translators: 1: site name 2: user name */
			$params[ 'description' ] = sprintf( __( '%1$s - Name: %2$s', 'sumosubscriptions' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $params[ 'name' ] ) ;
		}

		$allowed_params = array_keys( $default_params ) ;
		foreach ( $params as $param => $val ) {
			if ( '' === $val || ! in_array( $param, $allowed_params ) ) {
				unset( $params[ $param ] ) ;
			}
		}

		if ( ! empty( $exclude_params ) ) {
			foreach ( $exclude_params as $exclude_param ) {
				if ( in_array( $exclude_param, $allowed_params ) ) {
					unset( $params[ $exclude_param ] ) ;
				}
			}
		}
		return $params ;
	}

	/**
	 * Stripe Logger
	 */
	protected static function log( $e ) {
		self::$logger = $e ;
	}

	/**
	 * Check if saved customer is deleted in Stripe server
	 */
	public static function is_customer_deleted( $customer ) {
		if ( isset( $customer->id, $customer->deleted ) && $customer->deleted ) {
			return true ;
		}
		return false ;
	}

	/**
	 * Request Stripe
	 */
	public static function request( $api, $args = array() ) {
		// make sure to clear the cache before request
		self::clear_cache() ;

		try {
			switch ( $api ) {
				case 'set_apiKey':
					\Stripe\Stripe::setApiKey( self::$secret_key ) ;
					return true ;
				case 'create_customer':
					return \Stripe\Customer::create( $args ) ;
				case 'retrieve_customer':
					$request = wp_parse_args( $args, array(
						'id' => '',
							) ) ;

					if ( empty( $request[ 'id' ] ) ) {
						throw new Exception() ;
					}

					return \Stripe\Customer::retrieve( $request[ 'id' ] ) ;
				case 'retrieve_all_pm':
					$request = wp_parse_args( $args, array(
						'customer' => '',
						'type'     => 'card',
							) ) ;

					return \Stripe\PaymentMethod::all( $request ) ;
				case 'update_customer':
					$request = wp_parse_args( $args, array(
						'id' => '',
							) ) ;

					$id      = $request[ 'id' ] ;
					unset( $request[ 'id' ] ) ;
					return \Stripe\Customer::update( $id, $request ) ;
				case 'attach_pm':
					$request = wp_parse_args( $args, array(
						'id'       => '',
						'customer' => '',
							) ) ;

					$stripe = new \Stripe\StripeClient( self::$secret_key ) ;

					return $stripe->paymentMethods->attach( $request[ 'id' ], array( 'customer' => $request[ 'customer' ] ) ) ;
				case 'update_pm':
					$request = wp_parse_args( $args, array(
						'id' => '',
							) ) ;

					if ( ! empty( $request[ 'billing_details' ] ) ) {
						$request[ 'billing_details' ] = self::prepare_customer_details( $request[ 'billing_details' ], array( 'description' ) ) ;
					}

					$id      = $request[ 'id' ] ;
					unset( $request[ 'id' ] ) ;
					return \Stripe\PaymentMethod::update( $id, $request ) ;
				case 'retrieve_pm':
					$request = wp_parse_args( $args, array(
						'id' => '',
							) ) ;

					if ( empty( $request[ 'id' ] ) ) {
						throw new Exception() ;
					}

					return \Stripe\PaymentMethod::retrieve( $request[ 'id' ] ) ;
				case 'create_si':
					$request = wp_parse_args( $args, array(
						'payment_method_types' => array( 'card' ),
							) ) ;

					return \Stripe\SetupIntent::create( $request ) ;
				case 'retrieve_si':
					$request = wp_parse_args( $args, array(
						'id' => '',
							) ) ;

					if ( empty( $request[ 'id' ] ) ) {
						throw new Exception() ;
					}

					return \Stripe\SetupIntent::retrieve( $request[ 'id' ] ) ;
				case 'update_si':
					$request = wp_parse_args( $args, array(
						'id' => '',
							) ) ;

					$id                    = $request[ 'id' ] ;
					unset( $request[ 'id' ] ) ;
					return \Stripe\SetupIntent::update( $id, $request ) ;
				case 'create_pi':
					$request               = wp_parse_args( $args, array(
						'amount'               => 0,
						'currency'             => '',
						'capture_method'       => 'automatic',
						'confirmation_method'  => 'automatic',
						'payment_method_types' => array( 'card' ),
							) ) ;
					$request[ 'amount' ]   = self::prepare_amount( $request[ 'amount' ], $request[ 'currency' ] ) ;
					$request[ 'currency' ] = strtolower( $request[ 'currency' ] ) ;

					if ( ! empty( $request[ 'shipping' ] ) ) {
						$request[ 'shipping' ] = self::prepare_customer_details( $request[ 'shipping' ], array( 'email', 'description' ) ) ;
					}

					return \Stripe\PaymentIntent::create( $request ) ;
				case 'retrieve_pi':
					$request = wp_parse_args( $args, array(
						'id' => '',
							) ) ;

					if ( empty( $request[ 'id' ] ) ) {
						throw new Exception() ;
					}

					return \Stripe\PaymentIntent::retrieve( $request[ 'id' ] ) ;
				case 'update_pi':
					$request = wp_parse_args( $args, array(
						'id' => '',
							) ) ;

					if ( isset( $request[ 'amount' ] ) ) {
						$request[ 'amount' ] = self::prepare_amount( $request[ 'amount' ], $request[ 'currency' ] ) ;
					}

					if ( ! empty( $request[ 'currency' ] ) ) {
						$request[ 'currency' ] = strtolower( $request[ 'currency' ] ) ;
					}

					if ( ! empty( $request[ 'shipping' ] ) ) {
						$request[ 'shipping' ] = self::prepare_customer_details( $request[ 'shipping' ], array( 'email', 'description' ) ) ;
					}

					$id                    = $request[ 'id' ] ;
					unset( $request[ 'id' ] ) ;
					return \Stripe\PaymentIntent::update( $id, $request ) ;
				case 'charge_customer':
					$request               = wp_parse_args( $args, array(
						'amount'   => 0,
						'currency' => '',
							) ) ;
					$request[ 'amount' ]   = self::prepare_amount( $request[ 'amount' ], $request[ 'currency' ] ) ;
					$request[ 'currency' ] = strtolower( $request[ 'currency' ] ) ;

					if ( ! empty( $request[ 'shipping' ] ) ) {
						$request[ 'shipping' ] = self::prepare_customer_details( $request[ 'shipping' ], array( 'email', 'description' ) ) ;
					}

					return \Stripe\Charge::create( $request ) ;
				case 'create_refund':
					$request = wp_parse_args( $args, array(
						'charge'   => '',
						'amount'   => 0,
						'currency' => '',
							) ) ;

					$request[ 'amount' ] = self::prepare_amount( $request[ 'amount' ], $request[ 'currency' ] ) ;

					if ( empty( $request[ 'reason' ] ) ) {
						unset( $request[ 'reason' ] ) ;
					}
					unset( $request[ 'currency' ] ) ;

					return \Stripe\Refund::create( $request ) ;
			}
			// Use Stripe's library to make requests..
		} catch ( \Stripe\Error\RateLimit $e ) {// Too many requests made to the API too quickly
			self::log( $e->getJsonBody() ) ;
			return new WP_Error( 'sumosubscriptions-stripe-error', self::get_last_error_message() ) ;
		} catch ( \Stripe\Error\InvalidRequest $e ) {// Invalid parameters were supplied to Stripe's API
			self::log( $e->getJsonBody() ) ;
			return new WP_Error( 'sumosubscriptions-stripe-error', self::get_last_error_message() ) ;
		} catch ( \Stripe\Error\Authentication $e ) {// Authentication with Stripe's API failed or Too many requests made to the API too quickly
			self::log( $e->getJsonBody() ) ;
			return new WP_Error( 'sumosubscriptions-stripe-error', self::get_last_error_message() ) ;
		} catch ( \Stripe\Error\ApiConnection $e ) {// Network communication with Stripe failed
			self::log( $e->getJsonBody() ) ;
			return new WP_Error( 'sumosubscriptions-stripe-error', self::get_last_error_message() ) ;
		} catch ( \Stripe\Error\Base $e ) {// Display a very generic error to the user
			self::log( $e->getJsonBody() ) ;
			return new WP_Error( 'sumosubscriptions-stripe-error', self::get_last_error_message() ) ;
		} catch ( Exception $e ) {// Something else happened, completely unrelated to Stripe
			self::log( $e ) ;
			return new WP_Error( 'sumosubscriptions-stripe-error', self::get_last_error_message() ) ;
		}
		// If we reached this point then there were errors
		return new WP_Error( 'sumosubscriptions-stripe-error', self::get_last_error_message() ) ;
	}

	/**
	 * Get last error response while requesting Stripe
	 */
	public static function get_last_error_response( $response_type = '' ) {
		$last_error = array(
			'code'    => '',
			'message' => '',
			'type'    => '',
				) ;

		if ( is_array( self::$logger ) ) {
			if ( isset( self::$logger[ 'error' ] ) ) {
				$last_error = wp_parse_args( self::$logger[ 'error' ], $last_error ) ;
			}
		} else if ( is_callable( array( self::$logger, 'getJsonBody' ) ) ) {
			$body = self::$logger->getJsonBody() ;

			if ( isset( $body[ 'error' ] ) ) {
				$last_error = wp_parse_args( $body[ 'error' ], $last_error ) ;
			}
		}

		return '' === $response_type ? $last_error : $last_error[ $response_type ] ;
	}

	/**
	 * Get last error message while requesting Stripe
	 */
	public static function get_last_error_message( $localized = true ) {
		$error_message = self::get_last_error_response( 'message' ) ;
		$code          = self::get_last_error_response( 'code' ) ;
		$type          = self::get_last_error_response( 'type' ) ;

		if ( $localized ) {
			$localized_messages = self::get_localized_messages() ;

			if ( 'card_error' === $type ) {
				if ( isset( $localized_messages[ $code ] ) ) {
					$error_message = $localized_messages[ $code ] ;
				}
			} elseif ( isset( $localized_messages[ $type ] ) ) {
					$error_message = $localized_messages[ $type ] ;
			}
		}

		if ( empty( $error_message ) ) {
			$error_message = __( 'An error occurred while processing the card', 'sumosubscriptions' ) ;
		}

		return $error_message ;
	}

	/**
	 * Get last declined code while requesting Stripe
	 */
	public static function get_last_declined_code() {
		return self::get_last_error_response( 'code' ) ;
	}

	/**
	 * Get last log while requesting Stripe
	 */
	public static function get_last_log() {
		return self::$logger ;
	}

	/**
	 * Clear the cache 
	 */
	public static function clear_cache() {
		self::$logger = '' ;
	}
}
