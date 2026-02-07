<?php

/**
 * Subscription Suspended - Automatic Email.
 * 
 * @class SUMOSubs_Subscription_Suspended_Automatic_Email
 */
class SUMOSubs_Subscription_Suspended_Automatic_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_suspended_order_automatic';
		$this->key            = 'subscription_suspended_automatic';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Suspended Order - Automatic', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Suspended Order - Automatic emails are sent to the customer and the amount for the subscription renewal has not been paid within the suspend period.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-suspended-order-automatic.php';
		$this->template_plain = 'emails/plain/subscription-suspended-order-automatic.php';

		$this->subject = __( '[{site_title}] - Subscription Suspended', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Suspended', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'pay_link', 'upcoming_mail_info', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Suspended_Automatic_Email();
