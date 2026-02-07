<?php

/**
 * Subscription Turn off Automatic Payments - Success Email.
 * 
 * @class SUMOSubs_Subscription_Turnoff_Auto_Payments_Success_Email
 */
class SUMOSubs_Subscription_Turnoff_Auto_Payments_Success_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_turnoff_automatic_payments_success';
		$this->key            = 'subscription_turnoff_auto_payments_success';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Turn off Automatic Payments - Success', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Turn off Automatic Payments - Success emails are sent to the customers when they disable automatic charging for subscription renewals.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/turnoff-automatic-payments-success.php';
		$this->template_plain = 'emails/plain/turnoff-automatic-payments-success.php';

		$this->subject = __( '[{site_title}] - Subscription Automatic Charging Turned off', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Automatic Charging Turned off', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'recipient' );

		// Call parent constructor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Turnoff_Auto_Payments_Success_Email();
