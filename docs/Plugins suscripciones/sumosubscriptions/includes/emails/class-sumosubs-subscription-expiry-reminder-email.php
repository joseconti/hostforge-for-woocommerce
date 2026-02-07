<?php

/**
 * Subscription Expiry Reminder Email.
 * 
 * @class SUMOSubs_Subscription_Expiry_Reminder_Email
 */
class SUMOSubs_Subscription_Expiry_Reminder_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_expiry_reminder';
		$this->key            = 'subscription_expiry_reminder';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Expiry Reminder', 'sumosubscriptions' );
		$this->description    = __( 'Subscription Expiry Reminder emails are sent to the customers before their subscription is going to expire.', 'sumosubscriptions' );

		$this->template_html  = 'emails/subscription-expiry-reminder.php';
		$this->template_plain = 'emails/plain/subscription-expiry-reminder.php';

		$this->subject = __( '[{site_title}] - Subscription Expiry Reminder', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Expiry Reminder', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Expiry_Reminder_Email();
