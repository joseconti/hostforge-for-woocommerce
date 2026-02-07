<?php

/**
 * Abstract PayPal Reference Transaction API
 * 
 * @class SUMOSubs_PayPal_Reference_Transaction_API
 */
class SUMOSubs_PayPal_Reference_Transaction_API {

    /**
     * Payment Order (post) ID.
     * 
     * @var int 
     */
    protected $payment_order_id = 0;

    /**
     * Subscription (post) ID.
     * 
     * @var int 
     */
    protected $subscription_id = 0;

    /**
     * Order.
     * 
     * @var object 
     */
    protected $payment_order = false;

    /**
     * SUMOSubs_PayPal_Reference_Transaction_Gateway.
     * 
     * @var object 
     */
    protected $reference_txn;

    /**
     * Is Sandbox mode enabled?
     * 
     * @var bool
     */
    protected $sandbox = false;

    /**
     * PayPal API credentials.
     * 
     * @var array 
     */
    protected $api_credentials = array();

    /**
     * PayPal endpoint URL.
     * 
     * @var string 
     */
    protected $endpoint = '';

    /**
     * PayPal token URL.
     * 
     * @var string 
     */
    protected $token_url = '';

    /**
     * Is payment gateway enabled?
     * 
     * @var bool 
     */
    public $gateway_enabled = false;

    /**
     * Is developer DEBUG mode is enabled?
     * 
     * @var bool 
     */
    public $dev_debug_enabled = false;

    /**
     * Selected user roles for debugging.
     * 
     * @var array 
     */
    public $user_roles_for_dev = array();

    /**
     * Construct the PayPal Reference API
     */
    public function __construct( SUMOSubs_PayPal_Reference_Transaction_Gateway $reference_txn ) {
        $this->reference_txn      = $reference_txn;
        $this->gateway_enabled    = 'yes' === $this->reference_txn->enabled;
        $this->sandbox            = $this->reference_txn->sandbox;
        $this->endpoint           = $this->reference_txn->endpoint;
        $this->token_url          = $this->reference_txn->token_url;
        $this->dev_debug_enabled  = $this->reference_txn->dev_debug_enabled;
        $this->user_roles_for_dev = $this->reference_txn->user_roles_for_dev;
        $this->api_credentials    = array(
            'USER'      => $this->reference_txn->api_user,
            'PWD'       => $this->reference_txn->api_pwd,
            'SIGNATURE' => $this->reference_txn->api_signature,
        );

        $prefix = "woocommerce_{$this->reference_txn->id}";

        //may be fire upon submit action in WC checkout settings
        if ( isset( $_POST[ 'save' ], $_POST[ '_wpnonce' ] ) && wp_verify_nonce( wc_clean( wp_unslash( $_POST[ '_wpnonce' ] ) ), 'woocommerce-settings' ) ) {
            $this->gateway_enabled = isset( $_POST[ "{$prefix}_enabled" ] ) ? true : false;
            $this->sandbox         = isset( $_POST[ "{$prefix}_testmode" ] );

            if ( $this->sandbox ) {
                $this->endpoint  = 'https://api-3t.sandbox.paypal.com/nvp';
                $this->token_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout';
            } else {
                $this->endpoint  = 'https://api-3t.paypal.com/nvp';
                $this->token_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout';
            }

            $this->dev_debug_enabled  = isset( $_POST[ "{$prefix}_dev_debug_enabled" ] ) ? wc_clean( wp_unslash( $_POST[ "{$prefix}_dev_debug_enabled" ] ) ) : false;
            $this->user_roles_for_dev = isset( $_POST[ "{$prefix}_user_roles_for_dev" ] ) ? wc_clean( wp_unslash( $_POST[ "{$prefix}_user_roles_for_dev" ] ) ) : array();
            $this->api_credentials    = array(
                'USER'      => isset( $_POST[ "{$prefix}_api_user" ] ) ? wc_clean( wp_unslash( $_POST[ "{$prefix}_api_user" ] ) ) : '',
                'PWD'       => isset( $_POST[ "{$prefix}_api_pwd" ] ) ? wc_clean( wp_unslash( $_POST[ "{$prefix}_api_pwd" ] ) ) : '',
                'SIGNATURE' => isset( $_POST[ "{$prefix}_api_signature" ] ) ? wc_clean( wp_unslash( $_POST[ "{$prefix}_api_signature" ] ) ) : '',
            );
        }
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
     * @return float
     */
    public function get_payment_amount() {
        return is_callable( array( $this->get_payment_order(), 'get_total' ) ) ? $this->get_payment_order()->get_total() : 0;
    }

    /**
     * Check whether PayPal Reference API credentials are empty
     *
     * @return boolean
     */
    public function has_empty_api_credentials() {
        if ( empty( $this->api_credentials ) ) {
            return true;
        }

        if ( '' === $this->api_credentials[ 'USER' ] && '' === $this->api_credentials[ 'PWD' ] && '' === $this->api_credentials[ 'SIGNATURE' ] ) {
            return true;
        }

        return false;
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
     * Request PayPal and retrieve Response via cURL.
     *
     * @param array $data
     * @param string $url
     * @param array $headers
     * @return array
     */
    public function curl_request( $data, $url = '', $headers = array() ) {
        if ( ! is_array( $data ) ) {
            return array();
        }

        $url          = '' === $url ? $this->endpoint : $url;
        $data         = array_merge( $this->api_credentials, $data );
        $nvp_response = sumo_get_cURL_response( $url, $headers, $data );
        parse_str( $nvp_response, $parsed_data );
        $this->set_error_note( $parsed_data );
        return $parsed_data;
    }

    /**
     * Get saved billing agreement ID previously created for a PayPal account holder.
     * This can be done by calling CreateBillingAgreement with the respective Token generated for the PayPal account holder.
     * 
     * @return string
     */
    public function get_BillingAgreementID() {
        if ( $this->subscription_id > 0 ) {
            $paymentKey = sumo_get_subscription_payment( $this->subscription_id, 'payment_key' );
        } else {
            $paymentKey = sumo_get_subscription_order_payment( $this->payment_order_id, 'payment_key' );
        }

        return $paymentKey;
    }

    /**
     * Check whether the Admin initiated Merchant Billing Agreement
     * This must be necessary to use this Payment Gateway
     * 
     * @return bool
     */
    public function isMerchantInitiatedBillingAgreement() {
        $reference_data = $this->curl_request( array(
            'VERSION'               => 86,
            'METHOD'                => 'SetExpressCheckout',
            'RETURNURL'             => site_url(),
            'CANCELURL'             => site_url(),
            'L_BILLINGTYPE0'        => 'MerchantInitiatedBilling',
            'L_BILLINGDESCRIPTION0' => 'Check Reference Transaction Enabled or Not',
                ) );

        return isset( $reference_data[ 'TOKEN' ] ) ? true : false;
    }

    /**
     * Initiates an Express Checkout transaction.
     *
     * @param string $return_url
     * @param string $cancel_url
     * @return array A successful response returns a token that you use in subsequent calls.
     */
    public function setExpressCheckout( $return_url, $cancel_url ) {
        $site_name    = get_bloginfo( 'name' );
        $method_param = array(
            'METHOD'          => 'SetExpressCheckout',
            'VERSION'         => 86,
            'NOSHIPPING'      => '1',
            'MAXAMT'          => 0,
            'RETURNURL'       => $return_url,
            'CANCELURL'       => $cancel_url,
            'PAGESTYLE'       => $this->reference_txn->custom_payment_page[ 'style' ],
            'LOGOIMG'         => wp_get_attachment_url( $this->reference_txn->custom_payment_page[ 'logo' ] ),
            'CARTBORDERCOLOR' => str_replace( '#', '', $this->reference_txn->custom_payment_page[ 'border_color' ] ),
        );

        //Create Automatic Billing Payment.
        if ( SUMOSubs_Payment_Gateways::gateway_requires_auto_renewals( $this->reference_txn->id ) && sumo_order_contains_subscription( $this->get_payment_order() ) ) {
            $method_param[ 'L_BILLINGTYPE0' ]                 = 'MerchantInitiatedBilling';
            $method_param[ 'L_BILLINGAGREEMENTDESCRIPTION0' ] = "Your {$site_name} Order #{$this->payment_order_id}";
        }

        $data = $this->payment_request_data( $this->set_product_line_item_information( $method_param ) );
        return $this->curl_request( $data );
    }

    /**
     * Creates a billing agreement with a PayPal account holder.
     *
     * @param string $token
     * @return array A billing agreement ID
     */
    public function createBillingAgreement( $token ) {
        $data = array(
            'VERSION' => 86,
            'METHOD'  => 'CreateBillingAgreement',
            'TOKEN'   => $token,
        );

        return $this->curl_request( $data );
    }

    /**
     * Completes an Express Checkout transaction. 
     * If you set up a billing agreement in your SetExpressCheckout API call, the billing agreement is created when you call DoExpressCheckoutPayment
     * 
     * @param string $token
     * @param string $payer_id
     * @return array
     */
    public function doExpressCheckoutPayment( $token, $payer_id ) {
        $data = $this->payment_request_data( $this->set_product_line_item_information( array(
                    'VERSION' => 86,
                    'METHOD'  => 'DoExpressCheckoutPayment',
                    'TOKEN'   => $token,
                    'PAYERID' => $payer_id,
                ) ) );

        return $this->curl_request( $data );
    }

    /**
     * Capture future payments. Processes a payment from a buyer's account, which is identified by a previous transaction.
     *
     * @param string $billing_agreement_id
     * @return array
     */
    public function doReferenceTransaction( $billing_agreement_id = '' ) {
        $data = $this->payment_request_data( $this->set_product_line_item_information( array(
                    'VERSION'       => 86,
                    'METHOD'        => 'DoReferenceTransaction',
                    'REFERENCEID'   => '' === $billing_agreement_id ? $this->get_BillingAgreementID() : $billing_agreement_id,
                    'PAYMENTACTION' => 'Sale',
                    'AMT'           => $this->get_payment_amount(),
                    'CURRENCYCODE'  => $this->get_payment_order()->get_currency(),
                ) ) );

        return $this->curl_request( $data );
    }

    /**
     * Shows information about an Express Checkout transaction.
     *
     * @param string $token
     * @return array
     */
    public function getExpressCheckoutDetails( $token ) {
        $data = array(
            'VERSION' => 86,
            'METHOD'  => 'GetExpressCheckoutDetails',
            'TOKEN'   => $token,
        );

        return $this->curl_request( $data );
    }

    /**
     * Get billing agreement data
     *
     * @param string $billing_agreement_id
     * @return array
     */
    public function getBillingAgreementDetails( $billing_agreement_id = '' ) {
        $data = array(
            'VERSION'     => 86,
            'METHOD'      => 'BillAgreementUpdate',
            'REFERENCEID' => '' === $billing_agreement_id ? $this->get_BillingAgreementID() : $billing_agreement_id,
        );

        return $this->curl_request( $data );
    }

    /**
     * Updates billing agreement as Canceled.
     *
     * @param string $billing_agreement_id
     * @return array
     */
    public function cancelBillingAgreement( $billing_agreement_id = '' ) {
        $data = array(
            'VERSION'                => 86,
            'METHOD'                 => 'BillAgreementUpdate',
            'REFERENCEID'            => '' === $billing_agreement_id ? $this->get_BillingAgreementID() : $billing_agreement_id,
            'BILLINGAGREEMENTSTATUS' => 'Canceled',
        );

        return $this->curl_request( $data );
    }

    /**
     * Set product line item information upon placing the Order
     *
     * @param array $request
     * @return array
     */
    public function set_product_line_item_information( $request ) {
        $items_of_order = array();

        foreach ( $this->get_payment_order()->get_items( 'line_item' ) as $item ) {
            $product_id = sumosubs_wpml_maybe_get_translated_product_id( $item[ 'product_id' ] );
            $product    = new WC_Product( $product_id );

            $items_of_order[] = array(
                'NAME'    => $this->limit_characters_count( get_the_title( $product_id ) ),
                'AMT'     => $this->round_decimal( $this->get_payment_order()->get_item_subtotal( $item ) ),
                'QTY'     => $item->get_quantity(),
                'ITEMURL' => $product->get_permalink(),
            );
        }

        foreach ( $this->get_payment_order()->get_items( 'fee' ) as $item ) {
            $items_of_order[] = array(
                'NAME' => $this->limit_characters_count( $item->get_name() ),
                'AMT'  => $this->round_decimal( $item->get_total() ),
                'QTY'  => 1,
            );
        }

        if ( $this->get_payment_order()->get_total_discount() > 0 ) {
            $items_of_order[] = array(
                'NAME' => __( 'Discount', 'sumosubscriptions' ),
                'QTY'  => 1,
                'AMT'  => - $this->round_decimal( $this->get_payment_order()->get_total_discount() ),
            );
        }

        $item_info = $this->format_line_item_parameters( $items_of_order, $request );
        return $item_info;
    }

    /**
     * Requesting data for DoExpressCheckoutPayment call
     *
     * @param array $request
     * @return array
     */
    public function payment_request_data( $request ) {
        $item_amount = 0;

        foreach ( $this->get_payment_order()->get_items( array( 'line_item', 'fee' ) ) as $item ) {
            $item_amount += $item->get_total();
        }

        $data = $this->format_payment_request_parameters( array(
            'AMT'              => $this->get_payment_amount(),
            'CURRENCYCODE'     => $this->get_payment_order()->get_currency(),
            'ITEMAMT'          => $this->round_decimal( $item_amount ),
            'TAXAMT'           => $this->get_payment_order()->get_total_tax(),
            'SHIPPINGAMT'      => $this->get_payment_order()->get_shipping_total(),
            'PAYMENTACTION'    => 'Sale',
            'PAYMENTREQUESTID' => $this->payment_order_id,
            'NOTIFYURL'        => WC()->api_request_url( 'sumo_subscription_reference_ipn_notification' ),
            'CUSTOM'           => $this->payment_order_id,
                ), $request );

        return $data;
    }

    /**
     * Format line item parameters
     *
     * @param array $args
     * @param array $request
     * @return array
     */
    public function format_line_item_parameters( $args, $request ) {
        if ( is_array( $args ) && ! empty( $args ) ) {
            foreach ( $args as $key => $value ) {
                foreach ( $value as $newkey => $newvalue ) {
                    $request_line_item             = "L_PAYMENTREQUEST_0_{$newkey}{$key}";
                    $request[ $request_line_item ] = $newvalue;
                }
            }
        }

        return $request;
    }

    /**
     * Format payment requesting parameters
     *
     * @param array $args
     * @param array $request
     * @return array
     */
    public function format_payment_request_parameters( $args, $request ) {
        if ( is_array( $args ) && ! empty( $args ) ) {
            foreach ( $args as $key => $value ) {
                $request_name             = "PAYMENTREQUEST_0_{$key}";
                $request[ $request_name ] = $value;
            }
        }

        return $request;
    }

    /**
     * Complete the payment
     *
     * @param array $data
     * @return bool
     */
    public function complete_payment( $data ) {
        try {
            if ( isset( $data[ 'BILLINGAGREEMENTID' ] ) ) {
                $payment_mode         = 'auto';
                $billing_agreement_id = $data[ 'BILLINGAGREEMENTID' ];
            } else {
                $payment_mode         = 'manual';
                $billing_agreement_id = '';
            }

            //Save Payment Info.
            sumo_save_subscription_payment_info( $this->get_payment_order(), array(
                'payment_type'   => $payment_mode,
                'payment_method' => $this->reference_txn->id,
                'payment_key'    => $billing_agreement_id,
            ) );

            if ( $this->get_payment_amount() > 0 ) {
                $transaction_id = '';
                $payment_status = isset( $data[ 'PAYMENTINFO_0_PAYMENTSTATUS' ] ) ? $data[ 'PAYMENTINFO_0_PAYMENTSTATUS' ] : 'Pending';

                if ( isset( $data[ 'PAYMENTINFO_0_TRANSACTIONID' ] ) ) {
                    $transaction_id = $data[ 'PAYMENTINFO_0_TRANSACTIONID' ];
                    sumosubs_save_transaction_id( $this->get_payment_order(), $transaction_id, true );
                }

                if ( '' === $transaction_id || ! in_array( $payment_status, array( 'Completed', 'Processed' ) ) ) {
                    return false;
                }
            }

            //Complete Payment.
            $this->get_payment_order()->payment_complete();
        } catch ( Exception $e ) {
            return false;
        }

        return true;
    }

    /**
     * Get limited characters
     *
     * @param string $string
     * @return string
     */
    public function limit_characters_count( $string ) {
        $get_first_124_characters = $string;
        $str_length               = strlen( $string );

        if ( $str_length > 127 ) {
            $get_first_124_characters = substr( $string, 0, 124 );
            $get_first_124_characters = $get_first_124_characters . '...';
        }

        return $get_first_124_characters;
    }

    /**
     * Get PayPal thrown error message
     *
     * @param array $response_data
     * @return string
     */
    public function get_error_message( $response_data ) {
        $long_message = __( 'Something Went Wrong!!', 'sumosubscriptions' );

        if ( isset( $response_data[ 'L_LONGMESSAGE0' ] ) ) {
            $error_code   = isset( $response_data[ 'L_ERRORCODE0' ] ) ? '#' . $response_data[ 'L_ERRORCODE0' ] : '';
            $long_message = $error_code . ' ' . $response_data[ 'L_LONGMESSAGE0' ];
        }

        return $long_message;
    }

    /**
     * May be set error note
     *
     * @param array $parsed_data
     * @return boolean
     */
    public function set_error_note( $parsed_data ) {
        if ( ! isset( $parsed_data[ 'L_LONGMESSAGE0' ] ) ) {
            return false;
        }

        include_once SUMO_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/log-handlers/class-sumosubs-logger.php';
        SUMOSubs_Logger::log( $parsed_data, array(
            'subscription_id' => $this->subscription_id,
            'order_id'        => $this->payment_order_id,
        ) );

        /* translators: 1: paypal error message */
        $message = sprintf( __( 'PayPal error: <b>%s</b>', 'sumosubscriptions' ), $this->get_error_message( $parsed_data ) );
        $event   = __( 'PayPal error', 'sumosubscriptions' );

        if ( $this->subscription_id > 0 ) {
            sumo_add_subscription_note( "$message", $this->subscription_id, 'failure', $event );
        } else {
            $subscriptions = sumosubscriptions()->query->get( array(
                'type'       => 'sumosubscriptions',
                'status'     => 'publish',
                'meta_key'   => 'sumo_get_parent_order_id',
                'meta_value' => sumosubs_get_parent_order_id( $this->get_payment_order() ),
                    ) );

            foreach ( $subscriptions as $subscription_id ) {
                if ( ! is_numeric( $subscription_id ) || ! $subscription_id ) {
                    continue;
                }

                if ( 'manual' === sumo_get_payment_type( $subscription_id ) ) {
                    $event = __( 'Adaptive Pay Unsuccessful', 'sumosubscriptions' );
                }
                sumo_add_subscription_note( "$message", $subscription_id, 'failure', $event );
            }
        }

        return true;
    }

    /**
     * Format decimal amount
     *
     * @param int|float $value
     * @param int $decimal_count
     * @return float
     */
    public function round_decimal( $value, $decimal_count = 2 ) {
        return round( ( float ) $value, $decimal_count );
    }
}
