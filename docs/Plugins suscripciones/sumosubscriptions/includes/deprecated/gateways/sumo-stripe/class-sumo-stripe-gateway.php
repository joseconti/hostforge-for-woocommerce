<?php

/**
 * Register new Payment Gateway id of Stripe.
 * 
 * @class SUMO_Stripe_Gateway
 */
class SUMO_Stripe_Gateway extends WC_Payment_Gateway {

	const STRIPE_REQUIRES_AUTH            = 100;
	const PAYMENT_RETRY_WITH_DEFAULT_CARD = 200;

	/**
	 * Sandbox.
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Test secret key.
	 *
	 * @var string
	 */
	public $testsecretkey;

	/**
	 * Test publishable key.
	 *
	 * @var string
	 */
	public $testpublishablekey;

	/**
	 * Live secret key.
	 *
	 * @var string
	 */
	public $livesecretkey;

	/**
	 * Live publishable key.
	 *
	 * @var string
	 */
	public $livepublishablekey;

	/**
	 * Auth email reminder.
	 *
	 * @var int
	 */
	public $pendingAuthEmailReminder;

	/**
	 * Auth period.
	 *
	 * @var int
	 */
	public $pendingAuthPeriod;

	/**
	 * Check if we need to retry with the Default card
	 *
	 * @var bool 
	 */
	public $retry_failed_payment = false;

	/**
	 * SUMO_Stripe_Gateway constructor.
	 */
	public function __construct() {
		$this->id                       = 'sumo_stripe';
		$this->method_title             = 'SUMO Subscriptions - Stripe';
		$this->method_description       = __( 'Take payments from your customers using Credit/Debit card', 'sumosubscriptions' );
		$this->has_fields               = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled                  = $this->get_option( 'enabled' );
		$this->title                    = $this->get_option( 'title' );
		$this->description              = $this->get_option( 'description' );
		$this->testmode                 = 'yes' === $this->get_option( 'testmode' );
		$this->testsecretkey            = $this->get_option( 'testsecretkey' );
		$this->testpublishablekey       = $this->get_option( 'testpublishablekey' );
		$this->livesecretkey            = $this->get_option( 'livesecretkey' );
		$this->livepublishablekey       = $this->get_option( 'livepublishablekey' );
		$this->pendingAuthEmailReminder = $this->get_option( 'pendingAuthEmailReminder', '2' );
		$this->pendingAuthPeriod        = $this->get_option( 'pendingAuthPeriod', '1' );
		$this->supports                 = array(
			'products',
			'refunds',
		);

		include_once 'class-sumo-stripe-api-request.php';
		add_filter( 'sumosubscriptions_is_' . $this->id . '_preapproval_status_valid', array( $this, 'can_charge_renewal_payment' ), 10, 3 );
		add_filter( 'sumosubscriptions_is_' . $this->id . '_preapproved_payment_transaction_success', array( $this, 'charge_renewal_payment' ), 10, 3 );
		add_filter( 'sumosubscriptions_' . $this->id . '_pending_auth_period', array( $this, 'get_pending_auth_period' ) );
		add_filter( 'sumosubscriptions_' . $this->id . '_remind_pending_auth_times_per_day', array( $this, 'get_pending_auth_times_per_day_to_remind' ) );
		add_action( 'sumosubscriptions_stripe_requires_authentication', array( $this, 'prepare_customer_to_authorize_payment' ), 10, 2 );
		add_filter( 'sumosubscriptions_get_next_eligible_subscription_failed_status', array( $this, 'set_next_eligible_subscription_status' ), 10, 2 );
		add_action( 'sumosubscriptions_status_in_pending_authorization', array( $this, 'subscription_in_pending_authorization' ) );
	}

	/**
	 * Get option keys which are available
	 */
	public function _get_option_keys() {
		return array(
			'enabled'            => 'enabled',
			'title'              => 'title',
			'description'        => 'description',
			'livesecretkey'      => 'livesecretkey',
			'livepublishablekey' => 'livepublishablekey',
			'testmode'           => 'testmode',
			'testsecretkey'      => 'testsecretkey',
			'testpublishablekey' => 'testpublishablekey',
		);
	}

	/**
	 * Return the name of the old option in the WP DB.
	 *
	 * @return string
	 */
	public function _get_old_option_key() {
		return $this->plugin_id . 'sumosubscription_stripe_instant_settings';
	}

	/**
	 * Check for an old option and get option from DB.
	 *
	 * @param  string $key Option key.
	 * @param  mixed  $empty_value Value when empty.
	 * @return string The value specified for the option or a default value for the option.
	 */
	public function get_option( $key, $empty_value = null ) {
		$new_options = get_option( $this->get_option_key(), null );

		if ( isset( $new_options[ $key ] ) ) {
			return parent::get_option( $key, $empty_value );
		}

		$old_options = get_option( $this->_get_old_option_key(), false );

		if ( false === $old_options || ! is_array( $old_options ) ) {
			return parent::get_option( $key, $empty_value );
		}

		foreach ( $this->_get_option_keys() as $current_key => $maybeOld_key ) {
			if ( $key !== $current_key ) {
				continue;
			}

			if ( is_array( $maybeOld_key ) ) {
				foreach ( $maybeOld_key as $_key ) {
					if ( isset( $old_options[ $_key ] ) ) {
						$this->settings[ $key ] = $old_options[ $_key ];
					}
				}
			} elseif ( isset( $old_options[ $maybeOld_key ] ) ) {
					$this->settings[ $key ] = $old_options[ $maybeOld_key ];
			}
		}

		return parent::get_option( $key, $empty_value );
	}

	/**
	 * Admin Settings For Stripe.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                  => array(
				'title'   => __( 'Enable/Disable', 'sumosubscriptions' ),
				'type'    => 'checkbox',
				'label'   => __( 'Stripe', 'sumosubscriptions' ),
				'default' => 'no',
			),
			'title'                    => array(
				'title'       => __( 'Title:', 'sumosubscriptions' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user see during checkout.', 'sumosubscriptions' ),
				'default'     => __( 'SUMO Subscriptions - Stripe', 'sumosubscriptions' ),
			),
			'description'              => array(
				'title'    => __( 'Description', 'sumosubscriptions' ),
				'type'     => 'textarea',
				'default'  => 'Pay with Stripe. You can pay with your credit card, debit card and master card   ',
				'desc_tip' => true,
			),
			'testmode'                 => array(
				'title'       => __( 'Test Mode', 'sumosubscriptions' ),
				'type'        => 'checkbox',
				'label'       => __( 'Turn on testing', 'sumosubscriptions' ),
				'description' => __( 'Use the test mode on Stripe dashboard to verify everything works before going live.', 'sumosubscriptions' ),
				'default'     => 'no',
			),
			'livepublishablekey'       => array(
				'type'    => 'text',
				'title'   => __( 'Stripe API Live Publishable key', 'sumosubscriptions' ),
				'default' => '',
			),
			'livesecretkey'            => array(
				'type'    => 'text',
				'title'   => __( 'Stripe API Live Secret key', 'sumosubscriptions' ),
				'default' => '',
			),
			'testpublishablekey'       => array(
				'type'    => 'text',
				'title'   => __( 'Stripe API Test Publishable key', 'sumosubscriptions' ),
				'default' => '',
			),
			'testsecretkey'            => array(
				'type'    => 'text',
				'title'   => __( 'Stripe API Test Secret key', 'sumosubscriptions' ),
				'default' => '',
			),
			'autoPaymentFailure'       => array(
				'title' => __( 'Automatic Payment Failure Settings', 'sumosubscriptions' ),
				'type'  => 'title',
			),
			'retryDefaultPM'           => array(
				'title'       => __( 'Authenticate Future Renewals using Default Card', 'sumosubscriptions' ),
				'label'       => __( 'Enable', 'sumosubscriptions' ),
				'type'        => 'checkbox',
				'description' => __( 'If enabled, payment retries will be happen using the default card in case if the originally authorized card for the respective subscription is not able to process the recurring payment for some reason', 'sumosubscriptions' ),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'SCADesc'                  => array(
				'type'        => 'title',
				'description' => __( 'Some banks require customer authentication each time during a payment which is not controlled by Stripe. So, even if customer has authorized for future payments of subscription, the authorization will be declined by banks. In such case, customer has to manually process their renewal payments. The following options controls such scenarios.', 'sumosubscriptions' ),
			),
			'pendingAuthPeriod'        => array(
				'type'              => 'number',
				'title'             => __( 'Pending Authorization Period', 'sumosubscriptions' ),
				'default'           => '1',
				'description'       => __( 'day', 'sumosubscriptions' ),
				'desc_tip'          => __( 'This option controls how long the subscription needs to be in "Pending Authorization" status until the subscriber pays for the renewal or else it was unable to charge for the renewal automatically in case of automatic renewals. For example, if it is set as 2 then, the subscription will be in "Pending Authorization" status for 2 days from the subscription due date. During Pending Authorization period, subscriber still have access to their subscription.', 'sumosubscriptions' ),
				'custom_attributes' => array(
					'min' => 0,
				),
			),
			'pendingAuthEmailReminder' => array(
				'type'              => 'number',
				'title'             => __( 'Number of Emails to send during Pending Authorization', 'sumosubscriptions' ),
				'default'           => '2',
				'description'       => __( 'times per day', 'sumosubscriptions' ),
				'desc_tip'          => __( 'This option controls the number of times the subscription emails will be send to the customer in case of a payment failure when the subscription in Pending Authorization status.', 'sumosubscriptions' ),
				'custom_attributes' => array(
					'min' => 0,
				),
			),
		);
	}

	/**
	 * Gets the transaction URL linked to Stripe dashboard.
	 */
	public function get_transaction_url( $order ) {
		if ( $this->testmode ) {
			$this->view_transaction_url = 'https://dashboard.stripe.com/test/payments/%s';
		} else {
			$this->view_transaction_url = 'https://dashboard.stripe.com/payments/%s';
		}

		if ( 'setup_intent' === $this->get_intentObj_from_order( $order ) ) {
			$this->view_transaction_url = '';
		}

		return parent::get_transaction_url( $order );
	}

	/**
	 * Can the order be refunded via Stripe?
	 *
	 * @param  WC_Order $order
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		return $order && $order->get_transaction_id();
	}

	/**
	 * Checks if gateway should be available to use.
	 */
	public function is_available() {
		return false;
	}

	/**
	 * Process a refund if supported.
	 */
	public function process_refund( $order_id, $amount = null, $reason = null ) {

		try {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				throw new Exception( __( 'Refund failed: Invalid order', 'sumosubscriptions' ) );
			}

			if ( ! $this->can_refund_order( $order ) ) {
				throw new Exception( __( 'Refund failed: No transaction ID', 'sumosubscriptions' ) );
			}

			SUMO_Stripe_API_Request::init( $this );

			$pi = SUMO_Stripe_API_Request::request( 'retrieve_pi', array( 'id' => $this->get_intent_from_order( $order ) ) );

			$request = array(
				'amount' => $amount,
				'reason' => $reason,
			);

			if ( ! is_wp_error( $pi ) ) {
				$charge              = end( $pi->charges->data );
				$request[ 'charge' ] = $charge->id;
			} else {
				$request[ 'charge' ] = $order->get_transaction_id(); //BKWD CMPT
			}

			$refund = SUMO_Stripe_API_Request::request( 'create_refund', $request );

			if ( is_wp_error( $refund ) ) {
				throw new Exception( SUMO_Stripe_API_Request::get_last_error_message() );
			}
		} catch ( Exception $e ) {
			if ( isset( $order ) && is_a( $order, 'WC_Order' ) ) {
				$this->log_err( SUMO_Stripe_API_Request::get_last_log(), array(
					'order' => $order->get_id(),
				) );
			} else {
				$this->log_err( SUMO_Stripe_API_Request::get_last_log() );
			}

			return new WP_Error( 'sumosubscriptions-stripe-error', $e->getMessage() );
		}
		return true;
	}

	/**
	 *  Process the given response
	 */
	public function process_response( $response, $order = false ) {

		switch ( $response->status ) {
			case 'succeeded':
			case 'paid': // BKWD CMPT for Charge API
				if ( $order ) {
					if ( ! sumosubs_is_order_paid( $order ) ) {
						$order->payment_complete( $response->id );
					}

					if ( 'setup_intent' === $response->object ) {
						$order->add_order_note( __( 'Stripe: payment complete. Customer has approved for future payments.', 'sumosubscriptions' ) );
					} else {
						$order->add_order_note( __( 'Stripe: payment complete', 'sumosubscriptions' ) );
					}

					$order->set_transaction_id( $response->id );
					$order->save();
				}
				return 'success';
				break;
			case 'processing':
			case 'pending': // BKWD CMPT for Charge API
				if ( $order ) {
					if ( ! $order->has_status( 'on-hold' ) ) {
						$order->update_status( 'on-hold' );
					}

					if ( 'setup_intent' === $response->object ) {
						/* translators: 1: charge ID */
						$order->add_order_note( sprintf( __( 'Stripe: awaiting confirmation by the customer to approve for future payments: %s.', 'sumosubscriptions' ), $response->id ) );
					} else {
						/* translators: 1: charge ID */
						$order->add_order_note( sprintf( __( 'Stripe: awaiting payment: %s.', 'sumosubscriptions' ), $response->id ) );
					}

					$order->set_transaction_id( $response->id );
					$order->save();
				}
				return 'success';
				break;
			case 'requires_payment_method':
			case 'requires_source': // BKWD CMPT
			case 'canceled':
			case 'failed': // BKWD CMPT for Charge API
				$this->log_err( $response, $order ? array( 'order' => $order->get_id() ) : array()  );

				if ( isset( $response->last_setup_error ) ) {
					/* translators: 1: error */
					$message = $response->last_setup_error ? sprintf( __( 'Stripe: SCA authentication failed. Reason: %s', 'sumosubscriptions' ), $response->last_setup_error->message ) : __( 'Stripe: SCA authentication failed.', 'sumosubscriptions' );
				} else if ( isset( $response->last_payment_error ) ) {
					/* translators: 1: error */
					$message = $response->last_payment_error ? sprintf( __( 'Stripe: SCA authentication failed. Reason: %s', 'sumosubscriptions' ), $response->last_payment_error->message ) : __( 'Stripe: SCA authentication failed.', 'sumosubscriptions' );
				} else if ( isset( $response->failure_message ) ) {
					/* translators: 1: error */
					$message = $response->failure_message ? sprintf( __( 'Stripe: payment failed. Reason: %s', 'sumosubscriptions' ), $response->failure_message ) : __( 'Stripe: payment failed.', 'sumosubscriptions' );
				} else {
					$message = __( 'Stripe: payment failed.', 'sumosubscriptions' );
				}

				if ( $order ) {
					$order->add_order_note( $message );
					$order->save();
				}

				return $message;
				break;
		}

		$this->log_err( $response, $order ? array( 'order' => $order->get_id() ) : array()  );
		return 'failure';
	}

	/**
	 * Check whether Stripe can charge customer for the Subscription renewal payment to happen.
	 * 
	 * @param bool $bool
	 * @param int $subscription_id
	 * @param WC_Order $renewal_order
	 * @return bool
	 */
	public function can_charge_renewal_payment( $bool, $subscription_id, $renewal_order ) {

		try {

			SUMO_Stripe_API_Request::init( $this );

			$customer = SUMO_Stripe_API_Request::request( 'retrieve_customer', array(
						'id' => $this->get_stripe_customer_id_from_subscription( $subscription_id ),
					) );

			if ( is_wp_error( $customer ) ) {
				throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ) );
			}

			if ( SUMO_Stripe_API_Request::is_customer_deleted( $customer ) ) {
				/* translators: 1: customer ID */
				throw new Exception( sprintf( __( 'Stripe: Couldn\'t find the customer %s', 'sumosubscriptions' ), $customer->id ) );
			}

			$pm = $this->get_stripe_pm_id_from_subscription( $subscription_id );

			//BKWD CMPT for < v11.0
			if ( empty( $pm ) ) {
				return true;
			}

			$pm = SUMO_Stripe_API_Request::request( 'retrieve_pm', array(
						'id' => $pm,
					) );

			if ( is_wp_error( $pm ) ) {
				throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ), self::PAYMENT_RETRY_WITH_DEFAULT_CARD );
			}

			$this->save_pm_to_order( $renewal_order, $pm );
		} catch ( Exception $e ) {
			$this->add_subscription_err_note( $e->getMessage(), $subscription_id );
			$this->log_err( SUMO_Stripe_API_Request::get_last_log(), array(
				'order'        => $renewal_order->get_id(),
				'subscription' => $subscription_id,
			) );

			switch ( $e->getCode() ) {
				case self::PAYMENT_RETRY_WITH_DEFAULT_CARD:
					if ( isset( $customer, $customer->invoice_settings->default_payment_method ) && $customer->invoice_settings->default_payment_method ) {
						$this->save_pm_to_order( $renewal_order, $customer->invoice_settings->default_payment_method );
						$this->add_subscription_err_note( __( 'Start retrying payment with the default card chosen by the customer.', 'sumosubscriptions' ), $subscription_id );
						return true;
					}
					break;
			}
			return false;
		}
		return true;
	}

	/**
	 * Charge the customer to renew the Subscription.
	 * 
	 * @param bool $bool
	 * @param int $subscription_id
	 * @param WC_Order $renewal_order
	 * @return bool
	 */
	public function charge_renewal_payment( $bool, $subscription_id, $renewal_order, $retry = false ) {

		try {

			SUMO_Stripe_API_Request::init( $this );

			$this->retry_failed_payment = $retry;

			$request = array(
				'customer' => $this->get_stripe_customer_id_from_subscription( $subscription_id ),
			);

			if ( $this->retry_failed_payment ) {
				$customer = SUMO_Stripe_API_Request::request( 'retrieve_customer', array(
							'id' => $request[ 'customer' ],
						) );

				if ( is_wp_error( $customer ) ) {
					throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ) );
				}

				if ( isset( $customer, $customer->invoice_settings->default_payment_method ) && $customer->invoice_settings->default_payment_method ) {
					$this->save_pm_to_order( $renewal_order, $customer->invoice_settings->default_payment_method );
				} else {
					throw new Exception( __( 'Stripe: Couldn\'t find any default card from the customer.', 'sumosubscriptions' ) );
				}
			}

			$request[ 'amount' ]      = $renewal_order->get_total();
			$request[ 'currency' ]    = $renewal_order->get_currency();
			$request[ 'metadata' ]    = $this->prepare_metadata_from_order( $renewal_order, true, $subscription_id );
			$request[ 'shipping' ]    = wc_shipping_enabled() ? $this->prepare_userdata_from_order( $renewal_order, 'shipping' ) : $this->prepare_userdata_from_order( $renewal_order );
			/* translators: 1: site name 2: order ID */
			$request[ 'description' ] = sprintf( __( '%1$s - Order %2$s', 'sumosubscriptions' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $renewal_order->get_id() );

			$pm = $this->get_pm_from_order( $renewal_order );
			if ( $pm ) {
				$request[ 'payment_method' ] = $pm;
				$request[ 'off_session' ]    = true;
				$request[ 'confirm' ]        = true;

				$request_api = 'create_pi';
			} else {
				$request_api = 'charge_customer';
			}

			$response = SUMO_Stripe_API_Request::request( $request_api, $request );

			if ( is_wp_error( $response ) ) {
				if ( 'authentication_required' === SUMO_Stripe_API_Request::get_last_declined_code() ) {
					throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ), self::STRIPE_REQUIRES_AUTH );
				} elseif ( 'create_pi' === $request_api ) {
						throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ), self::PAYMENT_RETRY_WITH_DEFAULT_CARD );
				} else {
					throw new Exception( SUMO_Stripe_API_Request::get_last_error_message( false ) );
				}
			}

			if ( 'payment_intent' === $response->object ) {
				$this->save_intent_to_order( $renewal_order, $response );
			}

			//Process response.
			$result = $this->process_response( $response, $renewal_order );

			if ( 'success' !== $result ) {
				if ( 'payment_intent' === $response->object ) {
					throw new Exception( $result, self::PAYMENT_RETRY_WITH_DEFAULT_CARD );
				} else {
					throw new Exception( $result );
				}
			}
		} catch ( Exception $e ) {
			$this->add_subscription_err_note( $e->getMessage(), $subscription_id );
			$this->log_err( SUMO_Stripe_API_Request::get_last_log(), array(
				'order'        => $renewal_order->get_id(),
				'subscription' => $subscription_id,
			) );

			if ( ! $this->retry_failed_payment ) {
				$this->add_subscription_err_note( __( 'Start retrying payment with the default card chosen by the customer.', 'sumosubscriptions' ), $subscription_id );

				switch ( $e->getCode() ) {
					case self::PAYMENT_RETRY_WITH_DEFAULT_CARD:
					case self::STRIPE_REQUIRES_AUTH:
						return $this->charge_renewal_payment( $bool, $subscription_id, $renewal_order, true );
						break;
				}
			} else {
				switch ( $e->getCode() ) {
					case self::STRIPE_REQUIRES_AUTH:
						do_action( 'sumosubscriptions_stripe_requires_authentication', $renewal_order, $subscription_id );
						break;
				}
			}

			return false;
		}

		return true;
	}

	/**
	 * Prepare the customer to bring it 'OnSession' to complete the renewal
	 */
	public function prepare_customer_to_authorize_payment( $renewal_order, $subscription_id ) {
		$renewal_order->add_meta_data( '_sumo_subsc_stripe_authentication_required', 'yes' );
		$renewal_order->save_meta_data();

		add_post_meta( $subscription_id, 'stripe_authentication_required', 'yes' );
	}

	/**
	 * Hold the subscription until the payment is approved by the customer
	 */
	public function set_next_eligible_subscription_status( $next_eligible_status, $subscription_id ) {
		if ( 'yes' === get_post_meta( $subscription_id, 'stripe_authentication_required', true ) ) {
			$next_eligible_status = 'Pending_Authorization';
		}
		return $next_eligible_status;
	}

	/**
	 * Clear cache
	 */
	public function subscription_in_pending_authorization( $subscription_id ) {
		delete_post_meta( $subscription_id, 'stripe_authentication_required' );
	}

	/**
	 * Return the number of days to be hold in Pending Authorization.
	 * 
	 * @return int
	 */
	public function get_pending_auth_period( $no_of_days ) {
		return $this->pendingAuthPeriod;
	}

	/**
	 * Return the times per day to remind users in Pending Authorization.
	 * 
	 * @return int
	 */
	public function get_pending_auth_times_per_day_to_remind( $times_per_day ) {
		return $this->pendingAuthEmailReminder;
	}

	/**
	 * Save Stripe paymentMethod in Order
	 */
	public function save_pm_to_order( $order, $pm ) {
		$order->update_meta_data( '_sumo_subsc_stripe_pm', isset( $pm->id ) ? $pm->id : $pm  );
		$order->save_meta_data();
	}

	/**
	 * Save Stripe intent in Order
	 */
	public function save_intent_to_order( $order, $intent ) {
		if ( 'payment_intent' === $intent->object ) {
			$order->update_meta_data( '_sumo_subsc_stripe_pi', $intent->id );
		} else if ( 'setup_intent' === $intent->object ) {
			$order->update_meta_data( '_sumo_subsc_stripe_si', $intent->id );
		}

		$order->update_meta_data( '_sumo_subsc_stripe_intentObject', $intent->object );
		$order->save_meta_data();
	}

	/**
	 * Prepare userdata from order
	 * 
	 * @param string $type billing|shipping
	 */
	public function prepare_userdata_from_order( $order, $type = 'billing' ) {
		$userdata = array(
			'address' => array(
				'line1'       => $order->get_meta( "_{$type}_address_1", true ),
				'line2'       => $order->get_meta( "_{$type}_address_2", true ),
				'city'        => $order->get_meta( "_{$type}_city", true ),
				'state'       => $order->get_meta( "_{$type}_state", true ),
				'postal_code' => $order->get_meta( "_{$type}_postcode", true ),
				'country'     => $order->get_meta( "_{$type}_country", true ),
			),
			'fname'   => $order->get_meta( "_{$type}_first_name", true ),
			'lname'   => $order->get_meta( "_{$type}_last_name", true ),
			'phone'   => $order->get_meta( '_billing_phone', true ),
			'email'   => $order->get_meta( '_billing_email', true ),
		);

		if ( 'shipping' === $type && empty( $userdata[ 'fname' ] ) ) {
			$userdata[ 'fname' ] = $order->get_meta( '_billing_first_name', true );
			$userdata[ 'lname' ] = $order->get_meta( '_billing_last_name', true );
		}

		return $userdata;
	}

	/**
	 * Prepare metadata to display in Stripe.
	 * May be useful to keep track the subscription orders
	 */
	public function prepare_metadata_from_order( $order, $order_contains_subscription = false, $subscription_id = null ) {
		$metadata = array(
			'Order' => '#' . $order->get_id(),
		);

		if ( $subscription_id > 0 ) {
			$metadata[ 'Subscription' ] = '#' . $subscription_id;
		}

		if ( $subscription_id > 0 || $order_contains_subscription ) {
			$metadata[ 'Parent Order' ] = 0 === $order->get_parent_id() ? true : false;
			$metadata[ 'Payment Type' ] = 'Recurring';
		}

		$metadata[ 'Site Url' ] = esc_url( get_site_url() );
		return $metadata;
	}

	/**
	 * Add subscription error note
	 */
	public function add_subscription_err_note( $err, $subscription_id ) {
		/* translators: 1: error */
		sumo_add_subscription_note( sprintf( __( 'Stripe error: <b>%s</b>', 'sumosubscriptions' ), $err ), $subscription_id, 'failure', __( 'Stripe error', 'sumosubscriptions' ) );
	}

	/**
	 * Stripe error logger
	 */
	public function log_err( $log, $map_args = array() ) {
		if ( empty( $log ) ) {
			return;
		}

		include_once SUMO_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/log-handlers/class-sumosubs-logger.php';
		SUMOSubs_Logger::log( $log, $map_args );
	}

	/**
	 * Get saved Stripe intent object from Order
	 */
	public function get_intentObj_from_order( $order ) {
		return $order->get_meta( '_sumo_subsc_stripe_intentObject', true );
	}

	/**
	 * Get saved Stripe intent from Order
	 */
	public function get_intent_from_order( $order ) {
		$metakey = 'setup_intent' === $this->get_intentObj_from_order( $order ) ? '_sumo_subsc_stripe_si' : '_sumo_subsc_stripe_pi';
		return $order->get_meta( "$metakey", true );
	}

	/**
	 * Get saved Stripe paymentMethod from Order
	 */
	public function get_pm_from_order( $order ) {
		return $order->get_meta( '_sumo_subsc_stripe_pm', true );
	}

	/**
	 * Get saved Stripe customer ID from Subscription
	 * 
	 * @return string
	 */
	public function get_stripe_customer_id_from_subscription( $subscription_id ) {
		$customer_id = sumo_get_subscription_payment( $subscription_id, 'profile_id' );

		return is_array( $customer_id ) ? implode( $customer_id ) : $customer_id;
	}

	/**
	 * Get saved Stripe paymentMethod ID from Subscription
	 * 
	 * @return string
	 */
	public function get_stripe_pm_id_from_subscription( $subscription_id ) {
		$pm_id = sumo_get_subscription_payment( $subscription_id, 'payment_key' );

		return is_array( $pm_id ) ? implode( $pm_id ) : $pm_id;
	}
}

return new SUMO_Stripe_Gateway();
