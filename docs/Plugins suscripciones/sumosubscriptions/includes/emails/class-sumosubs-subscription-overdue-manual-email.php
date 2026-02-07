<?php

/**
 * Subscription Overdue - Manual Email.
 * 
 * @class SUMOSubs_Subscription_Overdue_Manual_Email
 */
class SUMOSubs_Subscription_Overdue_Manual_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_overdue_order_manual';
		$this->key            = 'subscription_overdue_manual';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Payment Overdue - Manual', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Payment Overdue - Manual emails are sent to the customer and the amount for the subscription renewal has not been paid within the overdue period.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-overdue-order-manual.php';
		$this->template_plain = 'emails/plain/subscription-overdue-order-manual.php';

		$this->subject = __( '[{site_title}] - Subscription Payment Overdue', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Payment Overdue', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'upcoming_mail_info', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Overdue_Manual_Email();
