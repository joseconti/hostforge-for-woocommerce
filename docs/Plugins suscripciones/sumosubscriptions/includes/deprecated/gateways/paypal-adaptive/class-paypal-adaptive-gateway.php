<?php

/**
 * Register new Payment Gateway id of PayPal Adaptive.
 * 
 * @class SUMO_PayPal_Adaptive_Gateway
 */
class SUMO_PayPal_Adaptive_Gateway extends WC_Payment_Gateway {

	protected $preapproval_api;
	public $testmode;
	public $api_user_id;
	public $api_password;
	public $api_signature;
	public $api_app_id;
	public $primary_mail;
	public $include_validity_period;
	public $validity_period_value;
	public $include_maxTotalAmountOfAllPayments;
	public $maxTotalAmountOfAllPayments_value;

	/**
	 * SUMO_PayPal_Adaptive_Gateway constructor.
	 */
	public function __construct() {
		$this->id                                  = 'sumo_paypal_preapproval';
		$this->method_title                        = 'SUMO Subscriptions - PayPal Adaptive';
		$this->method_description                  = __( 'Take payments from your customers using PayPal account or using their Credit/Debit card if they don\'t have a PayPal account', 'sumosubscriptions' );
		$this->icon                                = SUMO_SUBSCRIPTIONS_PLUGIN_URL . '/assets/images/paypalpre.jpg';
		$this->has_fields                          = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled                             = $this->get_option( 'enabled' );
		$this->title                               = $this->get_option( 'title' );
		$this->testmode                            = $this->get_option( 'testmode' );
		$this->description                         = $this->get_option( 'description' );
		$this->api_user_id                         = $this->get_option( 'api_user_id' );
		$this->api_password                        = $this->get_option( 'api_password' );
		$this->api_signature                       = $this->get_option( 'api_signature' );
		$this->api_app_id                          = $this->get_option( 'api_app_id' );
		$this->primary_mail                        = $this->get_option( 'primary_mail' );
		$this->include_validity_period             = $this->get_option( 'validity_period' );
		$this->validity_period_value               = $this->get_option( 'validity_period_value' );
		$this->include_maxTotalAmountOfAllPayments = $this->get_option( 'maxTAOAP' );
		$this->maxTotalAmountOfAllPayments_value   = $this->get_option( 'maxTAOAP_value' );

		include_once 'class-paypal-preapproval-api.php';

		$this->preapproval_api = new SUMO_PayPal_Preapproval_API( $this );
	}

	/**
	 * Get option keys which are available
	 */
	public function _get_option_keys() {
		return array(
			'enabled'               => 'enabled',
			'title'                 => 'title',
			'description'           => 'description',
			'testmode'              => 'testmode',
			'api_user_id'           => 'security_user_id',
			'api_password'          => 'security_password',
			'api_signature'         => 'security_signature',
			'api_app_id'            => 'security_application_id',
			'primary_mail'          => 'pri_r_paypal_mail',
			'validity_period'       => 'validity_period',
			'validity_period_value' => 'validity_period_value',
			'maxTAOAP'              => 'maxTotalAmountOfAllPayments',
			'maxTAOAP_value'        => 'maxTotalAmountOfAllPayments_value',
		);
	}

	/**
	 * Return the name of the old option in the WP DB.
	 *
	 * @return string
	 */
	public function _get_old_option_key() {
		return $this->plugin_id . 'sumosubscription_paypal_adaptive_settings';
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
	 * Admin Settings For PayPal Adaptive.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'               => array(
				'title'   => __( 'Enable/Disable', 'sumosubscriptions' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable PayPal Adaptive Payment', 'sumosubscriptions' ),
				'default' => 'no',
			),
			'title'                 => array(
				'title'       => __( 'Title:', 'sumosubscriptions' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user see during checkout.', 'sumosubscriptions' ),
				'default'     => __( 'SUMO Subscriptions - PayPal Adaptive', 'sumosubscriptions' ),
			),
			'description'           => array(
				'title'       => __( 'Description', 'sumosubscriptions' ),
				'type'        => 'textarea',
				'default'     => 'Pay with PayPal Adaptive Payment. You can pay with your credit card if you don�t have a PayPal account',
				'desc_tip'    => true,
				'description' => __( 'This controls the description which the user see during checkout.', 'sumosubscriptions' ),
			),
			'validity_period'       => array(
				'title'       => __( 'Enable Preapproval Validity', 'sumosubscriptions' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable', 'sumosubscriptions' ),
				'default'     => 'yes',
				'description' => __( 'Disable this option if you have got special permission from PayPal to ignore the Preapproval validity.', 'sumosubscriptions' ),
			),
			'validity_period_value' => array(
				'title'             => __( 'Preapproval Validity', 'sumosubscriptions' ),
				'type'              => 'number',
				'default'           => '365',
				'css'               => 'width:150px;',
				'custom_attributes' => array(
					'max'  => '365',
					'step' => '1',
					'min'  => '1',
				),
				'description'       => __( 'The Validity of Pre approval key start will be 365 days from the date of purchase by default. <br>You can set your own Pre approval key validity in this option. <br>Contact PayPal if you need more than 365 days.', 'sumosubscriptions' ),
			),
			'maxTAOAP'              => array(
				'title'       => __( 'Include "maxTotalAmountOfAllPayments"', 'sumosubscriptions' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable', 'sumosubscriptions' ),
				'default'     => 'yes',
				'description' => __( 'Disable this option if you have got special permission from PayPal to ignore the maxTotalAmountOfAllPayments parameter.', 'sumosubscriptions' ),
			),
			'maxTAOAP_value'        => array(
				'title'             => __( 'maxTotalAmountOfAllPayments', 'sumosubscriptions' ),
				'type'              => 'number',
				'default'           => '2000',
				'css'               => 'max-width:150px;',
				'custom_attributes' => array(
					'max'  => '2000',
					'step' => '1',
					'min'  => '1',
				),
				'description'       => __( 'The preapproved maximum total amount of all payments cannot exceed $2,000 USD or its equivalent in other currencies. <br>Contact PayPal if you do not want to specify a maximum amount.', 'sumosubscriptions' ),
			),
			'api_user_id'           => array(
				'title'       => __( 'API User ID', 'sumosubscriptions' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter your API User ID associated with your paypal account', 'sumosubscriptions' ),
			),
			'api_password'          => array(
				'title'       => __( 'API Password', 'sumosubscriptions' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter your API Password associated with your paypal account', 'sumosubscriptions' ),
			),
			'api_signature'         => array(
				'title'       => __( 'API Signature', 'sumosubscriptions' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter your API Signature associated with your paypal account', 'sumosubscriptions' ),
			),
			'api_app_id'            => array(
				'title'       => __( 'Application ID', 'sumosubscriptions' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter your Application ID created with your paypal account', 'sumosubscriptions' ),
			),
			'primary_mail'          => array(
				'title'       => __( 'Receiver PayPal Email', 'sumosubscriptions' ),
				'type'        => 'text',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'Please enter the receiver  paypal mail', 'sumosubscriptions' ),
			),
			'testmode'              => array(
				'title'       => __( 'PayPal Adaptive sandbox', 'sumosubscriptions' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable PayPal Adaptive sandbox', 'sumosubscriptions' ),
				'default'     => 'no',
				'description' => __( 'PayPal Adaptive sandbox can be used to test payments. Sign up for a developer account <a href="https://developer.paypal.com/">here</a>.', 'sumosubscriptions' ),
			),
		);
	}
}

return new SUMO_PayPal_Adaptive_Gateway();
