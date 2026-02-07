<?php

/**
 * Subscription Paused Email.
 * 
 * @class SUMOSubs_Subscription_Paused_Email
 */
class SUMOSubs_Subscription_Paused_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_pause_order';
		$this->key            = 'subscription_paused';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Paused', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Paused emails are sent to the subscribers when a subscription is paused by site administrator/customer (if they are allowed to do so).', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-pause-order.php';
		$this->template_plain = 'emails/plain/subscription-pause-order.php';

		$this->subject = __( '[{site_title}] - Subscription Paused', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Paused', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Paused_Email();
