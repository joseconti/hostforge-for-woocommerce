<?php

/**
 * Subscription Auto Renewal Success Email.
 * 
 * @class SUMOSubs_Subscription_Auto_Renewal_Success_Email
 */
class SUMOSubs_Subscription_Auto_Renewal_Success_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_renewed_order_automatic';
		$this->key            = 'subscription_auto_renewal_success';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Automatic Renewal Success', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Automatic Renewal Success emails are sent to the customers when the automatic subscription is Successfully.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-renewed-order-automatic.php';
		$this->template_plain = 'emails/plain/subscription-renewed-order-automatic.php';

		$this->subject  = __( '[{site_title}] - Subscription Renewal Successful', 'sumosubscriptions' );
		$this->heading  = __( 'Subscription Renewal Success', 'sumosubscriptions' );
		$this->supports = array( 'mail_to_admin', 'recipient' );

		// Call parent constructor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Auto_Renewal_Success_Email();
