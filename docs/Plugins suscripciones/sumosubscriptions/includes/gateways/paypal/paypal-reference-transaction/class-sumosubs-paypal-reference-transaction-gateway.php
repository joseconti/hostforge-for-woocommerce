<?php

/**
 * Register new Payment Gateway id of PayPal Reference Transaction.
 * 
 * @class SUMOSubs_PayPal_Reference_Transaction_Gateway
 */
class SUMOSubs_PayPal_Reference_Transaction_Gateway extends WC_Payment_Gateway {

    /**
     * Get an Reference Transaction API.
     * 
     * @var SUMOSubs_PayPal_Reference_Transaction_API 
     */
    protected $api;

    /**
     * Sandbox.
     *
     * @var bool
     */
    public $sandbox;

    /**
     * API user.
     *
     * @var string
     */
    public $api_user;

    /**
     * API pwd.
     *
     * @var string
     */
    public $api_pwd;

    /**
     * API signature.
     *
     * @var string
     */
    public $api_signature;

    /**
     * Dev debug enabled.
     *
     * @var bool
     */
    public $dev_debug_enabled;

    /**
     * User roles.
     *
     * @var array
     */
    public $user_roles_for_dev;

    /**
     * Custom payment page.
     *
     * @var array
     */
    public $custom_payment_page;

    /**
     * Hide for non subscription.
     *
     * @var bool
     */
    public $hide_when_non_subscriptions_in_cart;

    /**
     * Endpoint.
     *
     * @var string
     */
    public $endpoint;

    /**
     * Token URL.
     *
     * @var string
     */
    public $token_url;

    /**
     * SUMOSubs_PayPal_Reference_Transaction_Gateway constructor.
     */
    public function __construct() {
        $this->id                                  = 'sumo_paypal_reference_txns';
        $this->icon                                = SUMO_SUBSCRIPTIONS_PLUGIN_URL . '/assets/images/paypalpre.jpg';
        $this->has_fields                          = true;
        $this->method_title                        = 'SUMO Subscriptions - PayPal Reference Transactions';
        $this->method_description                  = __( 'SUMO Subscriptions - PayPal Reference Transactions is a part of Express Checkout that provides option to create Recurring Profile', 'sumosubscriptions' );
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled                             = $this->get_option( 'enabled', 'no' );
        $this->title                               = $this->get_option( 'title' );
        $this->description                         = $this->get_option( 'description' );
        $this->sandbox                             = 'yes' === $this->get_option( 'testmode', 'no' );
        $this->api_user                            = $this->get_option( 'api_user' ); // API User ID goes here
        $this->api_pwd                             = $this->get_option( 'api_pwd' ); // API Password goes here
        $this->api_signature                       = $this->get_option( 'api_signature' ); // API Signature goes here
        $this->dev_debug_enabled                   = 'yes' === $this->get_option( 'dev_debug_enabled', 'no' );
        $this->user_roles_for_dev                  = $this->get_option( 'user_roles_for_dev' );
        $this->custom_payment_page                 = array(
            'style'        => $this->get_option( 'page_style', get_option( 'sumo_customize_paypal_checkout_page_style', '' ) ),
            'logo'         => $this->get_option( 'page_logo', get_option( 'sumo_customize_paypal_checkout_page_logo_attachment_id', '' ) ),
            'border_color' => $this->get_option( 'page_border_color', get_option( 'sumo_customize_paypal_checkout_page_border_color_value', '' ) ),
        );
        $this->supports                            = array(
            'products',
            'sumosubscriptions',
        );
        $this->hide_when_non_subscriptions_in_cart = 'yes' === $this->get_option( 'hide_when_non_subscriptions_in_cart' );

        if ( $this->sandbox ) {
            $this->endpoint  = 'https://api-3t.sandbox.paypal.com/nvp';
            $this->token_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout';
        } else {
            $this->endpoint  = 'https://api-3t.paypal.com/nvp';
            $this->token_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout';
        }

        include_once 'class-sumosubs-paypal-reference-transaction-api.php';

        $this->api = new SUMOSubs_PayPal_Reference_Transaction_API( $this );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'save_data' ) );
        add_filter( 'sumosubscriptions_available_payment_gateways', array( $this, 'add_subscription_supports' ) );

        add_action( 'admin_notices', array( $this, 'add_site_wide_notice_for_reference_transaction' ) );
        add_filter( 'sumosubscriptions_need_payment_gateway', array( $this, 'need_payment_gateway' ), 20, 2 );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'delete_deprecated_meta' ), 20 );
        add_action( 'woocommerce_api_sumo_subscription_reference_transactions', array( $this, 'do_express_checkout' ) );
        add_action( 'woocommerce_api_sumo_subscription_reference_ipn_notification', array( $this, 'perform_action_based_on_ipn_notification' ) );

        //Subscription APIs.
        add_filter( 'sumosubscriptions_is_' . $this->id . '_preapproval_status_valid', array( $this, 'check_reference_status' ), 10, 3 );
        add_filter( 'sumosubscriptions_is_' . $this->id . '_preapproved_payment_transaction_success', array( $this, 'do_reference_transaction' ), 10, 3 );
        add_action( 'sumosubscriptions_subscription_cancelled', array( $this, 'cancel_billing_agreement' ) );
        add_action( 'sumosubscriptions_subscription_expired', array( $this, 'cancel_billing_agreement' ) );
    }

    /**
     * Get option keys which are available
     */
    public function _get_option_keys() {
        return array(
            'enabled'            => 'enabled',
            'title'              => 'title',
            'description'        => 'description',
            'testmode'           => 'testmode',
            'api_user'           => 'sumo_api_user',
            'api_pwd'            => 'sumo_api_pwd',
            'api_signature'      => 'sumo_api_signature',
            'dev_debug_enabled'  => 'dev_debug_mode_enabled',
            'user_roles_for_dev' => 'selected_user_roles_for_dev',
            'page_style'         => 'payment_page_style_name',
            'page_logo'          => 'payment_page_logo',
            'page_border_color'  => 'payment_page_border_color',
        );
    }

    /**
     * Return the name of the old option in the WP DB.
     *
     * @return string
     */
    public function _get_old_option_key() {
        return $this->plugin_id . 'sumosubscription_paypal_reference_transactions_settings';
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
     * Admin Settings For PayPal Reference Transactions.
     */
    public function init_form_fields() {
        $get_option        = get_option( 'sumo_customize_paypal_checkout_page_border_color_value', '' );
        $this->form_fields = array(
            'enabled'                             => array(
                'title'   => __( 'Enable/Disable', 'sumosubscriptions' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable PayPal Reference Transactions', 'sumosubscriptions' ),
                'default' => 'no',
            ),
            'title'                               => array(
                'title'       => __( 'Title', 'sumosubscriptions' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user see during checkout.', 'sumosubscriptions' ),
                'default'     => __( 'SUMO Subscriptions - PayPal Reference Transactions', 'sumosubscriptions' ),
                'desc_tip'    => true,
            ),
            'description'                         => array(
                'title'   => __( 'Description', 'sumosubscriptions' ),
                'type'    => 'textarea',
                'default' => __( 'Pay using PayPal Reference Transactions', 'sumosubscriptions' ),
            ),
            'testmode'                            => array(
                'title'       => __( 'PayPal Reference Transactions sandbox', 'sumosubscriptions' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable PayPal Reference Transactions sandbox', 'sumosubscriptions' ),
                'default'     => 'no',
                /* translators: 1: paypal setup url */
                'description' => sprintf( __( 'PayPal Reference Transactions sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'sumosubscriptions' ), 'https://developer.paypal.com/' ),
            ),
            'api_user'                            => array(
                'title'       => __( 'API User ID', 'sumosubscriptions' ),
                'type'        => 'text',
                'description' => __( 'Enter API Username to perform Reference Transactions', 'sumosubscriptions' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'api_pwd'                             => array(
                'title'       => __( 'API Password', 'sumosubscriptions' ),
                'type'        => 'password',
                'description' => __( 'Enter API Password', 'sumosubscriptions' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'api_signature'                       => array(
                'title'       => __( 'API Signature', 'sumosubscriptions' ),
                'type'        => 'text',
                'description' => __( 'Enter API Signature', 'sumosubscriptions' ),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'dev_debug_enabled'                   => array(
                'title'       => __( 'Developer Debug Mode', 'sumosubscriptions' ),
                'label'       => __( 'Enable', 'sumosubscriptions' ),
                'type'        => 'checkbox',
                'description' => __( 'If Reference Transactions is not enabled for your API credentials, this gateway will not be displayed on the Checkout page. For testing purpose, if you want this gateway to be displayed for specific userrole(s) then, Enable this checkbox.', 'sumosubscriptions' ),
                'default'     => 'no',
            ),
            'user_roles_for_dev'                  => array(
                'title'   => __( 'Select User Role(s)', 'sumosubscriptions' ),
                'class'   => 'wc-enhanced-select',
                'type'    => 'multiselect',
                'options' => sumosubs_get_user_roles(),
                'default' => array(),
            ),
            'hide_when_non_subscriptions_in_cart' => array(
                'title'   => __( 'Hide when Non Subscription Products are in Cart', 'sumosubscriptions' ),
                'label'   => __( 'Enable', 'sumosubscriptions' ),
                'type'    => 'checkbox',
                'default' => get_option( 'sumosubs_hide_auto_payment_gateways_when_non_subscriptions_in_cart', 'no' ),
            ),
            'customize_payment_page'              => array(
                'title' => __( 'Customize Payment Page', 'sumosubscriptions' ),
                'type'  => 'title',
            ),
            'page_style'                          => array(
                'title'    => __( 'Page Style Name', 'sumosubscriptions' ),
                'type'     => 'text',
                'default'  => get_option( 'sumo_customize_paypal_checkout_page_style', '' ),
                'desc_tip' => __( 'Already created page style name in PayPal account should be given here.', 'sumosubscriptions' ),
            ),
            'page_logo'                           => array(
                'type' => 'page_logo_finder',
            ),
            'page_border_color'                   => array(
                'title'    => __( 'Choose PayPal Checkout Page Border Color', 'sumosubscriptions' ),
                'type'     => 'color',
                'css'      => 'width:90px',
                'default'  => $get_option ? '#' . $get_option : '',
                'desc_tip' => __( 'The border color for PayPal checkout page.', 'sumosubscriptions' ),
            ),
        );
    }

    /**
     * Processes and saves options.
     *
     * @return bool was anything saved?
     */
    public function process_admin_options() {
        $saved       = parent::process_admin_options();
        $old_options = get_option( $this->_get_old_option_key(), false );

        if ( false === $old_options || ! is_array( $old_options ) ) {
            return $saved;
        }

        foreach ( $this->settings as $saved_key => $saved_val ) {
            foreach ( $this->_get_option_keys() as $key => $maybeOld_key ) {
                if ( $saved_key !== $key ) {
                    continue;
                }

                if ( is_array( $maybeOld_key ) ) {
                    foreach ( $maybeOld_key as $_key ) {
                        if ( isset( $old_options[ $_key ] ) ) {
                            $old_options[ $_key ] = $saved_val;
                        }
                    }
                } elseif ( isset( $old_options[ $maybeOld_key ] ) ) {
                    $old_options[ $maybeOld_key ] = $saved_val;
                }
            }
        }
        //Save the new options in the old gateway id. Maybe used when revert back to previous version
        update_option( $this->_get_old_option_key(), $old_options );
        return $saved;
    }

    /**
     * Generate PayPal payment page logo finder html.
     *
     * @return string
     */
    public function generate_page_logo_finder_html() {
        $attachment_id = $this->custom_payment_page[ 'logo' ];

        ob_start();
        ?>
        <tr>
            <th>
                <?php esc_html_e( 'Select PayPal Checkout Page Logo', 'sumosubscriptions' ); ?>
                <span class="woocommerce-help-tip" data-tip="<?php esc_html_e( 'Selected logo will be displayed in PayPal payment page.', 'sumosubscriptions' ); ?>"></span>
            </th>
            <td>
                <div class="sumosubs-logo-attachment-wrapper">
                    <span id="sumosubs_logo_attachment" style="padding: 0px 2px"><?php echo wp_get_attachment_image( $attachment_id, array( 90, 60 ) ); ?></span>
                    <img id="sumosubs_logo_preview" src="" width="90" height="60" style="display: none;">

                    <input type="hidden" name="sumosubs_logo_attachment_id" id="sumosubs_logo_attachment_id" value="<?php echo esc_attr( $attachment_id ); ?>">
                    <a href="#" id="sumosubs_upload_logo_button" class="button" data-choose="<?php esc_attr_e( 'Choose a Logo', 'sumosubscriptions' ); ?>" data-update="<?php esc_attr_e( 'Add Logo', 'sumosubscriptions' ); ?>" data-saved_attachment="<?php echo esc_attr( $attachment_id ); ?>"><?php esc_html_e( 'Upload Logo', 'sumosubscriptions' ); ?></a>

                    <?php if ( $attachment_id ) : ?>
                        <a href="#" id="sumosubs_delete_logo" class="button"><?php esc_html_e( 'Delete Logo', 'sumosubscriptions' ); ?></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Save custom fields data.
     */
    public function save_data( $settings ) {
        if ( ! isset( $settings[ 'page_logo' ] ) ) {
            return $settings;
        }

        if ( isset( $_REQUEST[ 'sumosubs_logo_attachment_id' ] ) ) {
            $settings[ 'page_logo' ] = wc_clean( wp_unslash( $_REQUEST[ 'sumosubs_logo_attachment_id' ] ) );
        } else {
            $settings[ 'page_logo' ] = '';
        }

        return $settings;
    }

    /**
     * Add gateway to support subscriptions.
     * 
     * @param array $subscription_gateways
     * @return array
     */
    public function add_subscription_supports( $subscription_gateways ) {
        $subscription_gateways[] = $this->id;
        return $subscription_gateways;
    }

    /**
     * Check Reference Transactions is enabled or not with your API for SUMO Subscription.
     */
    public function add_site_wide_notice_for_reference_transaction() {
        if ( ! $this->api->gateway_enabled || $this->api->has_empty_api_credentials() ) {
            return;
        }

        $current_url = isset( $_SERVER[ 'REQUEST_URI' ] ) ? wc_clean( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) : '#';

        if ( isset( $_REQUEST[ 'sumosubscription_check_reference_txn' ] ) ||
                ( isset( $_REQUEST[ 'page' ] ) && isset( $_REQUEST[ 'tab' ] ) && isset( $_REQUEST[ 'section' ] ) && 'wc-settings' === $_REQUEST[ 'page' ] && 'checkout' === $_REQUEST[ 'tab' ] && $this->id === $_REQUEST[ 'section' ] ) ) {

            $status = $this->api->isMerchantInitiatedBillingAgreement() ? 'success' : 'failure';

            update_option( 'sumosubscription_reference_txn_state', $status );

            if ( isset( $_REQUEST[ 'sumosubscription_check_reference_txn' ] ) ) {
                wp_safe_redirect( esc_url_raw( add_query_arg( array( 'sumosubscription_reference_txn_state' => $status ), remove_query_arg( 'sumosubscription_check_reference_txn', $current_url ) ) ) );
            }
        }

        if ( 'success' !== get_option( 'sumosubscription_reference_txn_state' ) ) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <?php
                    if ( isset( $_REQUEST[ 'sumosubscription_reference_txn_state' ] ) && 'success' === wc_clean( wp_unslash( $_REQUEST[ 'sumosubscription_reference_txn_state' ] ) ) ) {
                        esc_html_e( 'Reference Transactions has been enabled with your API Credentials.', 'sumosubscriptions' );
                    } else if ( isset( $_REQUEST[ 'sumosubscription_reference_txn_state' ] ) && 'failure' === wc_clean( wp_unslash( $_REQUEST[ 'sumosubscription_reference_txn_state' ] ) ) ) {
                        esc_html_e( 'Reference Transactions is not enabled with your API credentials and hence SUMO Subscriptions – PayPal Reference Transactions gateway will not be displayed on the Checkout page. Kindly contact PayPal to enable Reference Transactions for your account.', 'sumosubscriptions' );
                    } else {
                        /* translators: 1: reference api status check url */
                        printf( wp_kses_post( __( 'Check here to know whether Reference Transactions can be enabled with your API Credentials %s. If your credentials are not enabled then, SUMO Subscriptions – PayPal Reference Transactions gateway will not be displayed on the Checkout page.', 'sumosubscriptions' ) ), '<a href="' . esc_url( add_query_arg( array( 'sumosubscription_check_reference_txn' => '1' ), $current_url ) ) . '">' . esc_html__( 'Click here', 'sumosubscriptions' ) . '</a>' );
                    }
                    ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Allow gateway on demand.
     * 
     * @param bool $need
     * @param string $gateway_id
     * @return bool
     */
    public function need_payment_gateway( $need, $gateway_id ) {
        if ( ! $need || $this->id !== $gateway_id ) {
            return $need;
        }

        if ( $this->api->has_empty_api_credentials() ) {
            return false;
        }

        if ( ! SUMOSubs_Payment_Gateways::checkout_has_subscription() && $this->hide_when_non_subscriptions_in_cart ) {
            return false;
        }

        if ( 'success' === get_option( 'sumosubscription_reference_txn_state' ) ) {
            return true;
        }

        if ( $this->dev_debug_enabled ) {
            foreach ( wp_get_current_user()->roles as $current_user_role ) {
                if ( in_array( $current_user_role, ( array ) $this->user_roles_for_dev ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Flush out deprecated meta on save.
     */
    public function delete_deprecated_meta() {
        delete_option( 'sumosubs_hide_auto_payment_gateways_when_non_subscriptions_in_cart' );
    }

    /**
     * Do Express Checkout Payment.
     */
    public function do_express_checkout() {
        $get = $_GET;
        if ( ! isset( $get[ 'action' ] ) || ! isset( $get[ 'token' ] ) || ! isset( $get[ 'order_id' ] ) ) {
            return;
        }

        if ( 'sumosubscription_do_express_checkout' !== $get[ 'action' ] ) {
            return;
        }

        $order = wc_get_order( $get[ 'order_id' ] );
        if ( ! $order ) {
            return;
        }

        $_api = new SUMOSubs_PayPal_Reference_Transaction_API( $this );
        $_api->set_order_id( $get[ 'order_id' ] );

        $token_details = $_api->getExpressCheckoutDetails( $get[ 'token' ] );

        //Initiate Automatic Payment.
        //BILLINGAGREEMENTACCEPTEDSTATUS return 0 mean billing is Not Approved, 1 means billing is Approved.
        if ( isset( $token_details[ 'BILLINGAGREEMENTACCEPTEDSTATUS' ] ) && '1' === $token_details[ 'BILLINGAGREEMENTACCEPTEDSTATUS' ] ) {
            if ( $_api->get_payment_amount() > 0 ) {
                // Perform Do Express Checkout and record Billing Agreement to perform future payment.
                $_api->complete_payment( $_api->doExpressCheckoutPayment( $get[ 'token' ], $get[ 'PayerID' ] ) );
            } else {
                $_api->complete_payment( $_api->createBillingAgreement( $get[ 'token' ] ) );
            }

            //Redirect to Success url.
            wp_safe_redirect( $_api->get_payment_order()->get_checkout_order_received_url() );
            //Manual Payment.
        } else if ( isset( $token_details[ 'ACK' ] ) && in_array( $token_details[ 'ACK' ], array( 'Success', 'SuccessWithWarning' ) ) ) {
            // Billing Agreement is not enable so fetching payment is not valid from recurring perspective whether we can consider as normal payment.
            $_api->complete_payment( $_api->doExpressCheckoutPayment( $get[ 'token' ], $get[ 'PayerID' ] ) );
            //Redirect to Success url.
            wp_safe_redirect( $_api->get_payment_order()->get_checkout_order_received_url() );
        } else {
            // Error in PayPal
            wp_safe_redirect( WC()->cart->get_cart_url() );
        }
    }

    /**
     * PayPal Reference Transaction IPN handler.
     *
     * @param array $request
     */
    public function perform_action_based_on_ipn_notification( $request ) {
        if ( ! isset( $request[ 'txn_type' ] ) ) {
            return;
        }

        switch ( $request[ 'txn_type' ] ) {
            case 'mp_signup':
                //perform billing creation.
                break;
            case 'mp_cancel':
                //perform billing cancellation.
                $parent_order_id = isset( $_REQUEST[ 'custom' ] ) ? sumosubs_get_parent_order_id( absint( wp_unslash( $_REQUEST[ 'custom' ] ) ) ) : 0;
                if ( empty( $parent_order_id ) ) {
                    break;
                }

                $subscriptions = sumosubscriptions()->query->get( array(
                    'type'       => 'sumosubscriptions',
                    'status'     => 'publish',
                    'meta_key'   => 'sumo_get_parent_order_id',
                    'meta_value' => $parent_order_id,
                        ) );

                if ( ! empty( $subscriptions ) ) {
                    foreach ( $subscriptions as $subscription_id ) {
                        /* translators: 1: order id */
                        sumosubs_cancel_subscription( $subscription_id, array( 'note' => sprintf( __( 'Subscription has been cancelled from PayPal for %s.', 'sumosubscriptions' ), $parent_order_id ) ) );
                    }

                    //Clear every Subscription Payment Info from the Parent Order.
                    sumo_save_subscription_payment_info( $parent_order_id, array() );
                }
                break;
        }
    }

    /**
     * Check whether PayPal customer Billing ID valid or invalid.
     *
     * @param bool $bool The Subscription post ID
     * @param int $subscription_id
     * @param object $payment_order
     * @return bool true upon preapproval is valid
     */
    public function check_reference_status( $bool, $subscription_id, $payment_order ) {
        $_api = new SUMOSubs_PayPal_Reference_Transaction_API( $this );
        $_api->set_order_id( $payment_order->get_id() );
        $_api->set_subscription_id( $subscription_id );

        $billing_information = $_api->getBillingAgreementDetails();
        if ( isset( $billing_information[ 'BILLINGAGREEMENTSTATUS' ] ) && 'Active' === $billing_information[ 'BILLINGAGREEMENTSTATUS' ] ) {
            return true;
        }

        return false;
    }

    /**
     * Check whether PayPal Reference Transaction Payment Status valid or invalid.
     * If Reference Transaction Billing Agreement is valid then Automatic Subscription Renewal Success otherwise switch Subscription either with Manual Payment/Cancel.
     * 
     * @param bool $bool The Subscription post ID
     * @param int $subscription_id
     * @param object $payment_order
     * @return bool true upon payment success
     */
    public function do_reference_transaction( $bool, $subscription_id, $payment_order ) {
        $_api = new SUMOSubs_PayPal_Reference_Transaction_API( $this );
        $_api->set_order_id( $payment_order->get_id() );
        $_api->set_subscription_id( $subscription_id );

        $reference_txn = $_api->doReferenceTransaction();
        if ( isset( $reference_txn[ 'PAYMENTSTATUS' ] ) && in_array( $reference_txn[ 'PAYMENTSTATUS' ], array( 'Completed', 'Processed' ) ) ) {
            sumosubs_save_transaction_id( $payment_order, $reference_txn[ 'TRANSACTIONID' ], true );
            return true;
        }

        return false;
    }

    /**
     * Do some action when Reference Transaction Payment expired/cancelled.
     *
     * @param int $subscription_id The Subscription post ID
     */
    public function cancel_billing_agreement( $subscription_id ) {
        $payment_method = sumo_get_subscription_payment( $subscription_id, 'payment_method' );

        if ( $this->id === $payment_method ) {
            $order_id = get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true );

            //It might be used in case of Multiple Subscriptions placed in a single Checkout.
            if ( ! sumo_is_every_subscription_cancelled_from_parent_order( $order_id ) ) {
                return;
            }

            $_api = new SUMOSubs_PayPal_Reference_Transaction_API( $this );
            $_api->set_order_id( $order_id );
            $_api->set_subscription_id( $subscription_id );
            $_api->cancelBillingAgreement();
        }
    }

    /**
     * Create Callback URL.
     *
     * @param int $order_id The Order post ID
     * @return string
     */
    public function create_callback_url( $order_id ) {
        $request_url = WC()->api_request_url( 'sumo_subscription_reference_transactions' );
        $url         = esc_url_raw( add_query_arg( array(
            'order_id' => $order_id,
            'action'   => 'sumosubscription_do_express_checkout',
                        ), $request_url ) );

        return $url;
    }

    /**
     * Process of PayPal Reference Transactions.
     *
     * @param int $order_id The Order post ID
     * @return array
     * @throws Exception
     */
    public function process_payment( $order_id ) {
        try {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                throw new Exception( __( 'Something went wrong !!', 'sumosubscriptions' ) );
            }

            $success_url = $this->create_callback_url( $order_id );
            $cancel_url  = str_replace( '&amp;', '&', $order->get_cancel_order_url() );

            if ( $order->get_total() <= 0 && ! SUMOSubs_Payment_Gateways::gateway_requires_auto_renewals( $this->id ) ) {
                // Complete payment 
                $order->payment_complete();

                // Reduce stock levels
                wc_reduce_stock_levels( $order );

                // Remove cart
                WC()->cart->empty_cart();

                $redirect_url = $this->get_return_url( $order );
            } else {
                $_api = new SUMOSubs_PayPal_Reference_Transaction_API( $this );
                $_api->set_order_id( $order_id );

                $data = $_api->setExpressCheckout( $success_url, $cancel_url );
                if ( ! isset( $data[ 'ACK' ] ) || 'Failure' === $data[ 'ACK' ] ) {
                    $long_message = $_api->get_error_message( $data );
                    throw new Exception( "$long_message" );
                }

                // Reduce stock levels
                wc_reduce_stock_levels( $order );

                // Remove cart
                WC()->cart->empty_cart();

                $redirect_url = esc_url_raw( add_query_arg( array( 'token' => $data[ 'TOKEN' ] ), $this->token_url ) );
            }

            return array(
                'result'   => 'success',
                'redirect' => $redirect_url,
            );
        } catch ( Exception $e ) {
            if ( ! empty( $e ) ) {
                wc_add_notice( $e->getMessage(), 'error' );
            }
        }

        // If we reached this point then there were errors
        return array(
            'result'   => 'failure',
            'redirect' => $this->get_return_url( $order ),
        );
    }
}
