<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YITH_WC_Subscription_WC_Stripe_UPE integration with WooCommerce Stripe Plugin
 *
 * @class   YITH_WC_Subscription_WC_Stripe
 * @since   1.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Compatibility class for WooCommerce Gateway Stripe.
 *
 * @extends WC_Gateway_Stripe
 */
class YITH_WC_Subscription_WC_Stripe_UPE extends WC_Stripe_UPE_Payment_Gateway {
	use YITH_WC_Subscription_Singleton_Trait;

	/**
	 * Stripe gateway id
	 *
	 * @since 1.0
	 * @var   string ID of specific gateway
	 */
	public static $gateway_id = 'stripe';

	/**
	 * Upe Available Methods
	 *
	 * @type WC_Stripe_UPE_Payment_Method[]
	 */
	const UPE_AVAILABLE_METHODS = array(
		WC_Stripe_UPE_Payment_Method_CC::class,
		WC_Stripe_UPE_Payment_Method_Sepa::class,
	);

	/**
	 * Constructor
	 */
	public function __construct() {

		if ( version_compare( WC_STRIPE_VERSION, '8.3.0', '>=' ) ) {
			parent::__construct( WC_Stripe::get_instance()->account );
		} else {
			parent::__construct();
		}

		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
			'yith_subscriptions',
			'yith_subscriptions_scheduling',
			'yith_subscriptions_pause',
			'yith_subscriptions_multiple',
			'yith_subscriptions_payment_date',
			'yith_subscriptions_recurring_amount',
		);

		$this->payment_methods = array();
		foreach ( self::UPE_AVAILABLE_METHODS as $payment_method_class ) {
			$payment_method                                     = new $payment_method_class();
			$this->payment_methods[ $payment_method->get_id() ] = $payment_method;

			$action = 'ywsbs_pay_renew_order_with_stripe';
			if ( 'card' !== $payment_method->get_id() ) {
				$action .= '_' . $payment_method->get_id();
			}

			// Pay the renew orders.
			add_action( $action, array( $this, 'pay_renew_order' ), 10, 2 );
		}

		// Exclude gateways from checkout in not included in UPE list.
		add_filter( 'ywsbs_checkout_include_gateway', array( $this, 'disable_gateways' ), 10, 2 );

		// Prevent show multiple times order fee and payout as the actions are added directly by WC_Gateway_Stripe class.
		remove_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'display_order_fee' ) );
		remove_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'display_order_payout' ), 20 );
	}

	/**
	 * Is $order_id a subscription?
	 *
	 * @since 5.6.0
	 *
	 * @param  int $order_id The order ID.
	 * @return boolean
	 */
	public function has_subscription( $order_id ) {
		return YWSBS_Subscription_Cart::cart_has_subscriptions() || ! empty( YWSBS_Subscription_Order()->get_subscription_items_inside_the_order( $order_id ) );
	}

	/**
	 * Pay the renew order.
	 *
	 * It is triggered by ywsbs_pay_renew_order_with_{gateway_id} action.
	 *
	 * @since  1.1.0
	 *
	 * @param WC_Order $renewal_order Order to renew.
	 * @param bool     $manually      Check if this is a manual renew.
	 * @return array|bool|WP_Error
	 * @throws WC_Stripe_Exception Trigger an error.
	 */
	public function pay_renew_order( $renewal_order = null, $manually = false ) {

		if ( ! $renewal_order instanceof WC_Order ) {
			return false;
		}

		// Refresh order.
		$renewal_order   = wc_get_order( $renewal_order->get_id() );
		$is_a_renew      = $renewal_order->get_meta( 'is_a_renew' );
		$subscriptions   = $renewal_order->get_meta( 'subscriptions' );
		$subscription_id = $subscriptions ? $subscriptions[0] : false;
		$order_id        = $renewal_order->get_id();

		if ( ! $subscription_id ) {
			// translators: placeholder order id.
			WC_Stripe_Logger::log( sprintf( __( 'Sorry, no subscription is found for this order: %s', 'yith-woocommerce-subscription' ), $order_id ) );
			// translators: placeholder order id.
			yith_subscription_log( sprintf( __( 'Sorry, no subscription is found for this order: %s', 'yith-woocommerce-subscription' ), $order_id ), 'subscription_payment' );

			return false;
		}

		if ( $this->lock_order_payment( $renewal_order ) || $renewal_order->has_status( array( 'processing', 'completed' ) ) ) {
			return false;
		}

		foreach ( $subscriptions as $subscription_id ) {

			$subscription   = ywsbs_get_subscription( $subscription_id );
			$has_source     = $subscription->get( '_stripe_source_id' );
			$has_customer   = $subscription->get( '_stripe_customer_id' );
			$previous_error = false;

			if ( 'yes' !== $is_a_renew || empty( $has_source ) || empty( $has_customer ) ) {
				yith_subscription_log( 'Cannot pay order for the subscription ' . $subscription->id . ' stripe_customer_id=' . $has_customer . ' stripe_source_id=' . $has_source, 'subscription_payment' );
				ywsbs_register_failed_payment( $renewal_order, __( 'Error: Stripe customer and source info are missing.', 'yith-woocommerce-subscription' ) );

				return false;
			}

			$amount = $renewal_order->get_total();
			if ( $amount <= 0 ) {
				$renewal_order->payment_complete();
				return true;
			}

			try {

				if ( $amount * 100 < WC_Stripe_Helper::get_minimum_amount() ) {
					/* translators: minimum amount */
					$message = sprintf( __( 'Sorry, the minimum allowed order total is %1$s to use this payment method.', 'woocommerce-gateway-stripe' ), wc_price( WC_Stripe_Helper::get_minimum_amount() / 100 ) );
					ywsbs_register_failed_payment( $renewal_order, $message );

					return new WP_Error( 'stripe_error', $message );
				}

				$order_id = $renewal_order->get_id();
				// Get source from order.
				$prepared_source = $this->prepare_order_source( $renewal_order );

				if ( ! $prepared_source ) {
					throw new WC_Stripe_Exception( WC_Stripe_Helper::get_localized_messages()['missing'] );
				}

				$source_object = $prepared_source->source_object;

				if ( ! $prepared_source->customer ) {
					throw new WC_Stripe_Exception(
						'Failed to process renewal for order ' . $renewal_order->get_id() . '. Stripe customer id is missing in the order',
						__( 'Customer not found', 'woocommerce-gateway-stripe' )
					);
				}

				WC_Stripe_Logger::log( "Info: Begin processing subscription payment for order {$order_id} for the amount of {$amount}" );

				/*
				 * If we're doing a retry and source is chargeable, we need to pass
				 * a different idempotency key and retry for success.
				 */
				if ( is_object( $source_object ) && empty( $source_object->error ) && $this->need_update_idempotency_key( $source_object, $previous_error ) ) {
					add_filter( 'wc_stripe_idempotency_key', array( $this, 'change_idempotency_key' ), 10, 2 );
				}

				if ( ( $this->is_no_such_source_error( $previous_error ) || $this->is_no_linked_source_error( $previous_error ) ) && apply_filters( 'wc_stripe_use_default_customer_source', true ) ) {
					// Passing empty source will charge customer default.
					$prepared_source->source = '';
				}

				$response                   = $this->create_and_confirm_intent_for_off_session( $renewal_order, $prepared_source, $amount );
				$is_authentication_required = $this->is_authentication_required_for_payment( $response );

				if ( ! empty( $response->error ) && ! $is_authentication_required ) {
					$localized_message = __( 'Sorry, we are unable to process your payment at this time. Please retry later.', 'woocommerce-gateway-stripe' );
					$renewal_order->add_order_note( $localized_message );
					throw new WC_Stripe_Exception( print_r( $response, true ), $localized_message ); // phpcs:ignore
				}

				if ( $is_authentication_required ) {
					do_action( 'wc_gateway_stripe_process_payment_authentication_required', $renewal_order, $response );

					$error_message = __( 'This transaction requires authentication.', 'woocommerce-gateway-stripe' );
					$renewal_order->add_order_note( $error_message );

					$charge = end( $response->error->payment_intent->charges->data );
					$id     = $charge->id;
					$renewal_order->set_transaction_id( $id );
					/* translators: %s is the charge ID */
					$renewal_order->update_status( 'failed', sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'woocommerce-gateway-stripe' ), $id ) );
					$renewal_order->save();
				} else {
					do_action( 'wc_gateway_stripe_process_payment', $response, $renewal_order );

					$latest_charge = $this->get_latest_charge_from_intent( $response );
					// Use the last charge within the intent or the full response body in case of SEPA.
					$this->process_response( ( ! empty( $latest_charge ) ) ? $latest_charge : $response, $renewal_order );

				}
			} catch ( WC_Stripe_Exception $e ) {
				WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() );
				ywsbs_register_failed_payment( $renewal_order, 'Error: ' . $e->getMessage() );
				do_action( 'wc_gateway_stripe_process_payment_error', $e, $renewal_order );
			}
		}

		return true;
	}

	/**
	 * Create a setup intent for the order.
	 *
	 * @param WC_Order $order               The WC Order for which we're handling a setup intent.
	 * @param array    $payment_information The payment information to be used for the setup intent.
	 *
	 * @throws WC_Stripe_Exception When there's an error creating the setup intent.
	 * @return stdClass
	 */
	protected function process_setup_intent_for_order( $order, $payment_information ) {
		// If cart has subscription and payment is not needed, make sure to remove order, amount and currency from payment information to let Stripe correctly set up payment_intent.
		if ( $this->has_subscription( $order->get_id() ) && ! $this->is_payment_needed( $order->get_id() ) ) {
            unset( $payment_information['amount'], $payment_information['currency'], $payment_information['order'] );
		}

		return parent::process_setup_intent_for_order( $order, $payment_information );
	}

	/**
	 * Get payment source from an order.
	 *
	 * Not using 2.6 tokens for this part since we need a customer AND a card
	 * token, and not just one.
	 *
	 * @since   3.1.0
	 * @param WC_Order $order Order.
	 *
	 * @return  boolean|object
	 * @version 4.0.0
	 */
	public function prepare_order_source( $order = null ) {

        $subscriptions = ( $order instanceof WC_Order ) ? $order->get_meta( 'subscriptions' ) : array();

        // If order is not set or subscription is empty, fallback to parent method.
        if ( empty( $subscriptions ) ) {
            return parent::prepare_order_source( $order );
        }

		$stripe_customer = new WC_Stripe_Customer();
		$stripe_source   = false;
		$token_id        = false;
		$source_object   = false;

        foreach ( $subscriptions as $subscription_id ) {
            $subscription       = ywsbs_get_subscription( $subscription_id );
            $stripe_customer_id = $subscription->get( '_stripe_customer_id' );

            if ( $stripe_customer_id ) {
                $stripe_customer->set_id( $stripe_customer_id );
            }

            $source_id = $subscription->get( '_stripe_source_id' );
            if ( $source_id ) {
                $stripe_source = $source_id;
                $source_object = WC_Stripe_API::get_payment_method( $source_id );

                if (
                    (
                        empty( $source_object ) || // empty object.
                        empty( $source_object->customer ) || // no customer associated with the payment method, in ex payment method removed from the account.
                        $stripe_customer_id !== $source_object->customer || // customer associated with the payment method do not match the one associated with the subscription.
                        ( isset( $source_object->error->code ) && 'resource_missing' === $source_object->error->code ) || // source missing error.
                        ( isset( $source_object->status ) && 'consumed' === $source_object->status ) // source consumed error.
                    ) &&
                    apply_filters( 'ywsbs_wc_stripe_get_alternative_sources', true ) ) {
                    /**
                     * If the source status is "Consumed" this means that the customer has removed it from its account.
                     * So we search for the default source ID.
                     * If this ID is empty, this means that the customer has no credit card saved on the account so the payment will fail.
                     */

                    // Retrieve the available PaymentMethods from the customer.
                    $payment_methods = $stripe_customer->get_payment_methods( 'card' );
                    $payment_method  = ! empty( $payment_methods ) ? array_shift( $payment_methods ) : null;

                    if ( ! empty( $payment_method ) ) {
                        $stripe_source = $payment_method->id;
                        $source_object = $payment_method;
                    } else {
                        return false;
                    }
                }
            } elseif ( apply_filters( 'wc_stripe_use_default_customer_source', true ) ) {
                /*
                * We can attempt to charge the customer's default source
                * by sending empty source id.
                */
                $stripe_source = '';
            }
        }

        return (object) array(
            'token_id'       => $token_id,
            'customer'       => $stripe_customer ? $stripe_customer->get_id() : false,
            'source'         => $stripe_source,
            'source_object'  => $source_object,
            'payment_method' => null,
        );
	}

	/**
	 * Locks an order for payment intent processing for 5 minutes.
	 *
	 * @since 4.2
	 * @param WC_Order $order  The order that is being paid.
	 * @param stdClass $intent The intent that is being processed.
	 * @return bool            A flag that indicates whether the order is already locked.
	 */
	public function lock_order_payment( $order, $intent = null ) {
		static $processing_orders;

		if ( is_null( $processing_orders ) ) {
			$processing_orders = array();
		}

		$order_id = $order->get_id();
		if ( in_array( $order_id, $processing_orders, true ) ) {
			return true;
		}

		$processing_orders[] = $order_id;
		return parent::lock_order_payment( $order, $intent );
	}

	/**
	 * Disable gateways instance of WC_Stripe_UPE_Payment_Method not included in ::UPE_AVAILABLE_METHODS
	 *
	 * @since 4.6.0
	 * @param bool               $enabled True if enabled, false otherwise.
	 * @param WC_Payment_Gateway $gateway The gateway object.
	 */
	public function disable_gateways( $enabled, $gateway ) {
		return ! ( $gateway instanceof WC_Stripe_UPE_Payment_Method ) ? $enabled : in_array( get_class( $gateway ), self::UPE_AVAILABLE_METHODS, true );
	}
}
