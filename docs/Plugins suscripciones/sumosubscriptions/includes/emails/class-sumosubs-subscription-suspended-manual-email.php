<?php

/**
 * Subscription Suspended - Manual Email.
 * 
 * @class SUMOSubs_Subscription_Suspended_Manual_Email
 */
class SUMOSubs_Subscription_Suspended_Manual_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_suspended_order_manual';
		$this->key            = 'subscription_suspended_manual';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Suspended - Manual', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Suspended - Manual emails are sent to the customer when the amount for the subscription renewal has not been paid within the suspend period.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-suspended-order-manual.php';
		$this->template_plain = 'emails/plain/subscription-suspended-order-manual.php';

		$this->subject = __( '[{site_title}] - Subscription Suspended', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Suspended', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'upcoming_mail_info', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Suspended_Manual_Email();
