<?php

/**
 * Subscription Auto Renewal Reminder.
 * 
 * @class SUMOSubs_Subscription_Auto_Renewal_Reminder_Email
 */
class SUMOSubs_Subscription_Auto_Renewal_Reminder_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_automatic_charging_reminder';
		$this->key            = 'subscription_auto_renewal_reminder';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Automatic Renewal Reminder', 'sumosubscriptions' );
		$this->description    = __( 'Subscription Automatic Renewal Reminder emails are sent to the customers before charging for the subscription renewal using the preapproved payment gateway.', 'sumosubscriptions' );

		$this->template_html  = 'emails/subscription-automatic-charging-reminder.php';
		$this->template_plain = 'emails/plain/subscription-automatic-charging-reminder.php';

		$this->subject = __( '[{site_title}] - Subscription Automatic Charging Reminder', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Automatic Charging Reminder', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'payment_charging_date', 'recipient' );

		// Call parent constructor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Auto_Renewal_Reminder_Email();
