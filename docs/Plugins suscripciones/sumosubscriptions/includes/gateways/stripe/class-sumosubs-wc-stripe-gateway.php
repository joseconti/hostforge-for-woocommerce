<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Gateway_Stripe' ) ) {
    return;
}

if ( ! class_exists( 'SUMOSubs_WC_Stripe_Gateway' ) ) {

    /**
     * Handles the WC Stripe Automatic Payments
     * 
     * @class SUMOSubs_WC_Stripe_Gateway
     * @package Class
     */
    class SUMOSubs_WC_Stripe_Gateway {

        /**
         * Get the WC Stripe Gateway
         * 
         * @var WC_Gateway_Stripe 
         */
        protected static $stripe;

        const GATEWAY_ID = 'stripe';

        /**
         * Init SUMOSubs_WC_Stripe_Gateway.
         */
        public static function init() {
            add_filter( 'sumosubscriptions_available_payment_gateways', __CLASS__ . '::add_subscription_supports' );
            add_filter( 'wc_stripe_description', __CLASS__ . '::maybe_render_payment_mode_selector', 99, 2 );
            add_filter( 'wc_stripe_display_save_payment_method_checkbox', __CLASS__ . '::maybe_hide_save_checkbox', 99 );
            add_filter( 'wc_stripe_force_save_source', __CLASS__ . '::force_save_source', 99 );
            add_filter( 'wc_stripe_generate_create_intent_request', __CLASS__ . '::setup_future_usage', 99, 2 );
            add_action( 'woocommerce_order_status_changed', __CLASS__ . '::save_subscription_payment_source', 0, 4 );
            add_filter( 'wc_stripe_payment_metadata', __CLASS__ . '::add_payment_metadata', 10, 2 );
            add_filter( 'woocommerce_payment_gateway_supports', __CLASS__ . '::supports', 99, 3 );

            add_filter( 'sumosubscriptions_is_stripe_preapproval_status_valid', __CLASS__ . '::can_charge_renewal_payment', 10, 3 );
            add_filter( 'sumosubscriptions_is_stripe_preapproved_payment_transaction_success', __CLASS__ . '::charge_renewal_payment', 10, 3 );
            add_action( 'sumosubscriptions_wc_stripe_requires_authentication', __CLASS__ . '::prepare_customer_to_authorize_payment', 10, 2 );
            add_filter( 'sumosubscriptions_get_next_eligible_subscription_failed_status', __CLASS__ . '::set_next_eligible_subscription_status', 10, 2 );
            add_action( 'sumosubscriptions_status_in_pending_authorization', __CLASS__ . '::subscription_in_pending_authorization' );
            // Render subscription active payment method
            add_filter( 'sumosubscriptions_display_payment_method', __CLASS__ . '::render_subscription_active_payment_method', 10, 2 );

            // Process change payment method subscriber
            add_filter( 'sumosubscriptions_process_new_payment_method_via_stripe', __CLASS__ . '::process_change_payment_method', 10, 3 );
            add_action( 'sumosubscriptions_add_or_change_payment_method_before_submit', __CLASS__ . '::prepare_change_payment_method_form' );
        }

        /**
         * Get the Stripe instance.
         */
        public static function get_stripe_instance() {
            if ( is_null( self::$stripe ) && WC()->payment_gateways() ) {
                $payment_gateways = WC()->payment_gateways->payment_gateways();

                if ( isset( $payment_gateways[ self::GATEWAY_ID ] ) && is_a( $payment_gateways[ self::GATEWAY_ID ], 'WC_Gateway_Stripe' ) ) {
                    self::$stripe = $payment_gateways[ self::GATEWAY_ID ];
                }
            }

            return self::$stripe;
        }

        /**
         * Add gateway supports subscription.
         * @since 15.5.0
         * 
         * @param bool $bool
         * @param string $feature
         * @param WC_Payment_Gateway $gateway
         * @return bool
         */
        public static function supports( $bool, $feature, $gateway ) {
            if ( self::GATEWAY_ID === $gateway->id && in_array( $feature, array( 'sumosubs_add_or_change_payment' ) ) ) {
                $bool = true;
            }

            return $bool;
        }

        /**
         * Add gateway to support subscriptions.
         * 
         * @param array $subscription_gateways
         * @return array
         */
        public static function add_subscription_supports( $subscription_gateways ) {
            $subscription_gateways[] = self::GATEWAY_ID;
            return $subscription_gateways;
        }

        /**
         * Render checkbox to select the mode of payment in automatic payment gateways by the customer
         * 
         * @param string $description
         * @param string $gateway_id
         * @return string
         */
        public static function maybe_render_payment_mode_selector( $description, $gateway_id ) {
            return SUMOSubs_Payment_Gateways::maybe_get_gateway_description_with_payment_mode( $description, $gateway_id );
        }

        /**
         * Checks to see if we need to hide the save checkbox field.
         * Because when cart contains a subscription product, it will save regardless.
         */
        public static function maybe_hide_save_checkbox( $display_tokenization ) {
            if ( SUMOSubs_Payment_Gateways::checkout_has_subscription() ) {
                $display_tokenization = false;
            }

            return $display_tokenization;
        }

        /**
         * Check if the gateway requires auto renewals.
         * 
         * @param bool $force_save
         * @return bool
         */
        public static function force_save_source( $force_save ) {
            if ( SUMOSubs_Payment_Gateways::gateway_requires_auto_renewals( self::GATEWAY_ID ) ) {
                $force_save = true;
            }

            return $force_save;
        }

        /**
         * Attach payment method to Stripe Customer.
         * 
         * @param array $request
         * @param WC_Order $order
         * @return array 
         */
        public static function setup_future_usage( $request, $order ) {
            if ( sumo_order_contains_subscription( $order ) && sumosubs_is_parent_order( $order ) ) {
                $request[ 'setup_future_usage' ] = 'off_session';
            }

            return $request;
        }

        /**
         * Save Stripe Source and Customer to order for making automatic payments.
         */
        public static function save_subscription_payment_source( $order_id, $from_status, $to_status, $order ) {
            $order = wc_get_order( $order_id );

            if ( ! sumo_order_contains_subscription( $order ) ) {
                return;
            }

            $payment_method = $order->get_payment_method();
            if ( empty( $payment_method ) ) {
                $payment_method = isset( $_REQUEST[ 'payment_method' ] ) ? wc_clean( wp_unslash( $_REQUEST[ 'payment_method' ] ) ) : '';
            }

            if ( empty( $payment_method ) || self::GATEWAY_ID !== $payment_method ) {
                return;
            }

            sumo_save_subscription_payment_info( $order, array(
                'payment_type'   => sumo_get_subscription_order_payment( $order, 'payment_type' ),
                'payment_method' => self::GATEWAY_ID,
                'profile_id'     => $order->get_meta( '_stripe_customer_id', true ),
                'payment_key'    => $order->get_meta( '_stripe_source_id', true ),
            ) );
        }

        /**
         * Add payment metadata to Stripe.
         */
        public static function add_payment_metadata( $metadata, $order ) {
            $order = sumosubs_maybe_get_order_instance( $order );

            if ( sumo_order_contains_subscription( $order ) ) {
                if ( $order->get_parent_id() > 0 ) {
                    $subscription_id = $order->get_meta( 'sumo_subscription_id', true );

                    if ( $subscription_id > 0 ) {
                        $metadata[ 'subscription' ] = $subscription_id;
                    }

                    $metadata[ 'parent_order' ] = false;
                } else {
                    $metadata[ 'parent_order' ] = true;
                }

                $metadata[ 'payment_type' ] = 'recurring';
                $metadata[ 'payment_via' ]  = 'SUMO Subscriptions';
                $metadata[ 'site_url' ]     = esc_url( get_site_url() );
            }

            return $metadata;
        }

        /**
         * Check whether Stripe can charge customer for the Subscription renewal payment to happen.
         * 
         * @param bool $bool
         * @param int $subscription_id
         * @param WC_Order $renewal_order
         * @return bool
         */
        public static function can_charge_renewal_payment( $bool, $subscription_id, $renewal_order ) {

            try {
                $customer_id = sumo_get_subscription_payment( $subscription_id, 'profile_id' );
                $source_id   = sumo_get_subscription_payment( $subscription_id, 'payment_key' );

                $renewal_order->update_meta_data( '_stripe_customer_id', $customer_id );
                $renewal_order->update_meta_data( '_stripe_source_id', $source_id );
                $renewal_order->save();

                $prepared_source = self::get_stripe_instance()->prepare_order_source( $renewal_order );

                if ( ! $prepared_source->customer ) {
                    throw new WC_Stripe_Exception( 'Failed to process renewal payment for order ' . $renewal_order->get_id() . '. Stripe customer id is missing in the order', __( 'Customer not found', 'sumosubscriptions' ) );
                }

                return true;
            } catch ( WC_Stripe_Exception $e ) {
                WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() );

                if ( $e->getLocalizedMessage() ) {
                    $renewal_order->add_order_note( $e->getLocalizedMessage() );
                    /* translators: 1: error message */
                    sumo_add_subscription_note( sprintf( __( 'Stripe error: <b>%s</b>', 'sumosubscriptions' ), $e->getLocalizedMessage() ), $subscription_id, 'failure', __( 'Stripe error', 'sumosubscriptions' ) );
                }
            }

            return false;
        }

        /**
         * Checks if a order already failed because a manual authentication is required.
         *
         * @param WC_Order $order
         * @return bool
         */
        public static function has_authentication_already_failed( $order, $subscription_id ) {
            $existing_intent = self::get_stripe_instance()->get_intent_from_order( $order );

            if (
                    ! $existing_intent || 'requires_payment_method' !== $existing_intent->status || empty( $existing_intent->last_payment_error ) || 'authentication_required' !== $existing_intent->last_payment_error->code
            ) {
                return false;
            }

            // Make sure all emails are instantiated.
            WC_Emails::instance();

            /**
             * A payment attempt failed because SCA authentication is required.
             *
             * @param WC_Order $order The order that is being renewed.
             */
            do_action( 'sumosubscriptions_wc_stripe_requires_authentication', $subscription_id, $order );

            // Fail the payment attempt (order would be currently pending because of retry rules).
            $charge    = end( $existing_intent->charges->data );
            $charge_id = $charge->id;
            /* translators: %s is the stripe charge Id */
            $order->update_status( 'failed', sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'woocommerce-gateway-stripe' ), $charge_id ) );
            return true;
        }

        /**
         * Force passing of Idempotency key to headers.
         * 
         * @param array $parsed_args
         * @return array
         */
        public static function force_allow_idempotency_key( $parsed_args ) {
            if (
                    isset( $parsed_args[ 'headers' ][ 'Stripe-Version' ] ) &&
                    ! isset( $parsed_args[ 'headers' ][ 'Idempotency-Key' ] ) &&
                    ! empty( $parsed_args[ 'body' ] )
            ) {
                $body = $parsed_args[ 'body' ];

                if ( isset( $body[ 'metadata' ][ 'payment_via' ] ) && 'SUMO Subscriptions' === $body[ 'metadata' ][ 'payment_via' ] ) {
                    $customer                                      = ! empty( $body[ 'customer' ] ) ? $body[ 'customer' ] : '';
                    $source                                        = ! empty( $body[ 'source' ] ) ? $body[ 'source' ] : $customer;
                    $parsed_args[ 'headers' ][ 'Idempotency-Key' ] = apply_filters( 'wc_stripe_idempotency_key', $body[ 'metadata' ][ 'order_id' ] . '-' . $source, $body );
                }
            }

            return $parsed_args;
        }

        /**
         * Charge the customer from their source to renew the Subscription.
         * 
         * @param bool $bool
         * @param int $subscription_id
         * @param WC_Order $renewal_order
         * @return bool
         */
        public static function charge_renewal_payment( $bool, $subscription_id, $renewal_order, $retry = false, $previous_error = false ) {
            try {

                // Check for an existing intent, which is associated with the order.
                if ( self::has_authentication_already_failed( $renewal_order, $subscription_id ) ) {
                    return false;
                }

                $prepared_source = self::get_stripe_instance()->prepare_order_source( $renewal_order );
                $source_object   = $prepared_source->source_object;

                if ( ! $prepared_source->customer ) {
                    throw new WC_Stripe_Exception( 'Failed to process installment for order ' . $renewal_order->get_id() . '. Stripe customer id is missing in the order', __( 'Customer not found', 'sumosubscriptions' ) );
                }

                /*
                 * If we're doing a retry and source is chargeable, we need to pass
                 * a different idempotency key and retry for success.
                 */
                if ( is_object( $source_object ) && empty( $source_object->error ) && self::get_stripe_instance()->need_update_idempotency_key( $source_object, $previous_error ) ) {
                    add_filter( 'wc_stripe_idempotency_key', array( self::get_stripe_instance(), 'change_idempotency_key' ), 10, 2 );
                }

                if ( $retry && ( false === $previous_error || self::get_stripe_instance()->is_no_such_source_error( $previous_error ) || self::get_stripe_instance()->is_no_linked_source_error( $previous_error ) ) && apply_filters( 'sumosubscriptions_wc_stripe_use_default_customer_source', true ) ) {
                    // Passing empty source will charge customer default.
                    $prepared_source->source = '';

                    sumo_add_subscription_note( __( 'Start retrying renewal payment using the default card.', 'sumosubscriptions' ), $subscription_id, 'failure', __( 'Stripe automatic payment retry', 'sumosubscriptions' ) );
                }

                self::get_stripe_instance()->lock_order_payment( $renewal_order );

                add_filter( 'http_request_args', 'SUMOSubs_WC_Stripe_Gateway::force_allow_idempotency_key' );
                $response                   = self::get_stripe_instance()->create_and_confirm_intent_for_off_session( $renewal_order, $prepared_source, $renewal_order->get_total() );
                $is_authentication_required = self::get_stripe_instance()->is_authentication_required_for_payment( $response );
                remove_filter( 'http_request_args', 'SUMOSubs_WC_Stripe_Gateway::force_allow_idempotency_key' );

                if ( ! empty( $response->error ) && ! $is_authentication_required ) {
                    if ( ! $retry && self::get_stripe_instance()->is_retryable_error( $response->error ) ) {
                        return self::charge_renewal_payment( $bool, $subscription_id, $renewal_order, true, $response->error );
                    }

                    $localized_messages = WC_Stripe_Helper::get_localized_messages();

                    if ( 'card_error' === $response->error->type ) {
                        $localized_message = isset( $localized_messages[ $response->error->code ] ) ? $localized_messages[ $response->error->code ] : $response->error->message;
                    } else {
                        $localized_message = isset( $localized_messages[ $response->error->type ] ) ? $localized_messages[ $response->error->type ] : $response->error->message;
                    }

                    $renewal_order->add_order_note( $localized_message );

                    throw new WC_Stripe_Exception( print_r( $response, true ), $localized_message );
                }

                if ( $is_authentication_required ) {
                    if ( ! $retry ) {
                        return self::charge_renewal_payment( $bool, $subscription_id, $renewal_order, true, $response->error );
                    }

                    $charge = end( $response->error->payment_intent->charges->data );

                    /* translators: 1: Stripe charge id */
                    sumo_add_subscription_note( sprintf( __( 'Stripe awaiting for authentication by user: %s.', 'sumosubscriptions' ), $charge->id ), $subscription_id, 'failure', __( 'Stripe awaiting authentication', 'sumosubscriptions' ) );
                    /* translators: 1: Stripe charge id */
                    $renewal_order->add_order_note( sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'sumosubscriptions' ), $charge->id ) );
                    $renewal_order->set_transaction_id( $charge->id );
                    /* translators: %s is the charge Id */
                    $renewal_order->update_status( 'failed', sprintf( __( 'Stripe charge awaiting authentication by user: %s.', 'woocommerce-gateway-stripe' ), $charge->id ) );
                    $renewal_order->save();

                    do_action( 'sumosubscriptions_wc_stripe_requires_authentication', $subscription_id, $renewal_order );
                    return false;
                }

                // The charge was successfully captured
                do_action( 'wc_gateway_stripe_process_payment', $response, $renewal_order );

                if ( is_callable( array( self::get_stripe_instance(), 'get_latest_charge_from_intent' ) ) ) {
                    $latest_charge = self::get_stripe_instance()->get_latest_charge_from_intent( $response );

                    if ( ! empty( $latest_charge ) ) {
                        $response = $latest_charge;
                    }
                } else if ( isset( $response->charges->data ) ) {
                    $response = end( $response->charges->data );
                }

                self::get_stripe_instance()->process_response( $response, $renewal_order );

                do_action( 'sumosubscriptions_wc_stripe_renewal_payment_successful', $subscription_id, $renewal_order );
                return true;
            } catch ( WC_Stripe_Exception $e ) {
                WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() );

                do_action( 'wc_gateway_stripe_process_payment_error', $e, $renewal_order );

                if ( $e->getLocalizedMessage() ) {
                    $renewal_order->add_order_note( $e->getLocalizedMessage() );
                    /* translators: 1: error message */
                    sumo_add_subscription_note( sprintf( __( 'Stripe error: <b>%s</b>', 'sumosubscriptions' ), $e->getLocalizedMessage() ), $subscription_id, 'failure', __( 'Stripe error', 'sumosubscriptions' ) );
                }

                /* translators: error message */
                $renewal_order->update_status( 'failed' );
            }

            return false;
        }

        /**
         * Prepare the customer to bring it 'OnSession' to complete the renewal.
         */
        public static function prepare_customer_to_authorize_payment( $subscription_id, $renewal_order ) {
            $renewal_order->add_meta_data( '_sumo_subsc_wc_stripe_authentication_required', 'yes' );
            $renewal_order->save();

            add_post_meta( $subscription_id, 'wc_stripe_authentication_required', 'yes' );
        }

        /**
         * Hold the subscription until the payment is approved by the customer
         */
        public static function set_next_eligible_subscription_status( $next_eligible_status, $subscription_id ) {
            if ( 'yes' === get_post_meta( $subscription_id, 'wc_stripe_authentication_required', true ) ) {
                $next_eligible_status = 'Pending_Authorization';
            }
            return $next_eligible_status;
        }

        /**
         * Clear cache
         */
        public static function subscription_in_pending_authorization( $subscription_id ) {
            delete_post_meta( $subscription_id, 'wc_stripe_authentication_required' );
        }

        /**
         * To determine proper SCA handling in "Add/Change payment method" form.
         */
        public static function prepare_change_payment_method_form() {
            echo '<input type="hidden" id="wc-stripe-change-payment-method" />';
        }

        /**
         * Render subscription active changed payment method
         * 
         * @since 15.4.0
         * @param string $payment_method_to_display Payment Method
         * @param object $subscription Subscription Object
         * @return string
         */
        public static function render_subscription_active_payment_method( $payment_method_to_display, $subscription ) {
            $stripe_customer_id = sumo_get_subscription_payment( $subscription->get_id(), 'profile_id' );
            $stripe_source_id   = sumo_get_subscription_payment( $subscription->get_id(), 'payment_key' );
            $stripe_customer    = new WC_Stripe_Customer();
            $stripe_customer->set_id( $stripe_customer_id );

            foreach ( $stripe_customer->get_payment_methods( 'card' ) as $source ) {
                if ( $source->id === $stripe_source_id ) {
                    $card = false;
                    if ( isset( $source->type ) && 'card' === $source->type ) {
                        $card = $source->card;
                    } elseif ( isset( $source->object ) && 'card' === $source->object ) {
                        $card = $source;
                    }

                    if ( $card ) {
                        /* translators: 1) card brand 2) last 4 digits */
                        $payment_method_to_display = sprintf( __( 'Via %1$s card ending in %2$s', 'sumosubscriptions' ), ( isset( $card->brand ) ? $card->brand : __( 'N/A', 'sumosubscriptions' ) ), $card->last4 );
                    }

                    break;
                }
            }

            return $payment_method_to_display;
        }

        /**
         * Process change payment method
         * 
         * @since 15.5.0
         * @param array $result
         * @param object $order WC_Order Object
         * @param int $subscription_id Subscription ID
         * @return Exception
         */
        public static function process_change_payment_method( $result, $order, $subscription_id ) {
            try {
                $prepared_source = self::get_stripe_instance()->prepare_source( get_current_user_id(), true );
                $payment_method  = $prepared_source->source;

                if ( ! empty( $_POST[ 'wc-stripe-is-deferred-intent' ] ) ) {
                    $payment_method       = empty( $payment_method ) ? sanitize_text_field( wp_unslash( $_POST[ 'wc-stripe-payment-method' ] ) ) : $payment_method;
                    $payment_method_types = substr( $payment_method, 0, 7 ) === 'stripe_' ? substr( $payment_method, 7 ) : 'card';
                    $payment_information  = array(
                        'payment_method'        => $payment_method,
                        'selected_payment_type' => $payment_method_types,
                        'customer'              => $prepared_source->customer,
                        'confirm'               => 'true',
                        'return_url'            => sumo_get_subscription_endpoint_url( $subscription_id )
                    );

                    $setup_intent = self::get_stripe_instance()->intent_controller->create_and_confirm_setup_intent( $payment_information );
                    if ( ! empty( $setup_intent->error ) ) {
                        // Add the setup intent information to the order meta, if one was created despite the error.
                        if ( ! empty( $setup_intent->error->payment_intent ) ) {
                            self::get_stripe_instance()->save_intent_to_order( $order, $setup_intent->error->payment_intent );
                        }

                        throw new WC_Stripe_Exception(
                                        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                                        print_r( $setup_intent, true ),
                                        __( 'Sorry, we are unable to process your payment at this time. Please retry later.', 'sumosubscriptions' )
                        );
                    }
                } else {
                    self::get_stripe_instance()->maybe_disallow_prepaid_card( $prepared_source );
                    self::get_stripe_instance()->check_source( $prepared_source );
                    self::get_stripe_instance()->save_source_to_order( $order, $prepared_source );
                }

                $payment_information = array(
                    'payment_type'   => 'auto',
                    'payment_method' => self::GATEWAY_ID,
                    'payment_key'    => $payment_method,
                    'profile_id'     => $prepared_source->customer,
                );

                sumo_save_subscription_payment_info( $order, $payment_information );
                $order->set_payment_method( self::GATEWAY_ID );
                $order->save();

                /**
                 * Stripe new payment method processed successful.
                 * 
                 * @param array $prepared_source 
                 * @param object $order WC_Order 
                 * @since 15.5.0
                 */
                do_action( 'sumosubscriptions_stripe_new_payment_method_processed_successful', $prepared_source, $order );

                return array(
                    'result' => 'success',
                );
            } catch ( WC_Stripe_Exception $e ) {
                wc_add_notice( $e->getLocalizedMessage(), 'error' );
                WC_Stripe_Logger::log( 'Error: ' . $e->getMessage() );
            }

            return $result;
        }
    }

    SUMOSubs_WC_Stripe_Gateway::init();
}

if ( class_exists( 'WC_Stripe_UPE_Payment_Gateway' ) ) {

    /**
     * WC_Payment_Gateway class wrapper.
     * 
     * @class SUMOSubs_WC_Stripe_UPE_Payment_Gateway_Helper
     */
    class SUMOSubs_WC_Stripe_UPE_Payment_Gateway_Helper extends WC_Stripe_UPE_Payment_Gateway {

        /**
         * Renders the UPE input fields needed to get the user's payment information on the checkout page
         */
        public function payment_fields() {
            parent::payment_fields();

            SUMOSubs_Payment_Gateways::maybe_get_payment_mode_for_gateway( $this->id );
        }

        /**
         * Checks if the setting to allow the user to save cards is enabled.
         *
         * @return bool Whether the setting to allow saved cards is enabled or not.
         */
        public function is_saved_cards_enabled() {
            if ( SUMOSubs_Payment_Gateways::checkout_has_subscription() ) {
                return false;
            }

            return parent::is_saved_cards_enabled();
        }
    }

}


