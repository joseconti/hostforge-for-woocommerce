<?php

/**
 * Subscription Cancelled Email.
 * 
 * @class SUMOSubs_Subscription_Cancelled_Email
 */
class SUMOSubs_Subscription_Cancelled_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_cancel_order';
		$this->key            = 'subscription_cancelled';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Cancel - Success', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Cancel - Success emails are sent to the customers when the subscription is cancelled completely.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-cancelled-order.php';
		$this->template_plain = 'emails/plain/subscription-cancelled-order.php';

		$this->subject = __( '[{site_title}] - Subscription Cancelled', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Cancelled', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Cancelled_Email();
