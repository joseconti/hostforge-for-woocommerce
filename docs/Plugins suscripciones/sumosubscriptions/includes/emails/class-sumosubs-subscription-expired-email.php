<?php

/**
 * Subscription Expired Email.
 * 
 * @class SUMOSubs_Subscription_Expired_Email
 */
class SUMOSubs_Subscription_Expired_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_expired_order';
		$this->key            = 'subscription_expired';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Expired', 'sumosubscriptions' );
		$this->description    = __( 'Subscription Expired emails are sent to the customers when a subscription is expired.', 'sumosubscriptions' );

		$this->template_html  = 'emails/subscription-expired-order.php';
		$this->template_plain = 'emails/plain/subscription-expired-order.php';

		$this->subject = __( '[{site_title}] - Subscription Expired', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Expired', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Expired_Email();
