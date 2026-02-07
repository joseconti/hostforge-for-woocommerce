<?php

/**
 * Subscription Overdue - Automatic Email.
 * 
 * @class SUMOSubs_Subscription_Overdue_Automatic_Email
 */
class SUMOSubs_Subscription_Overdue_Automatic_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_overdue_order_automatic';
		$this->key            = 'subscription_overdue_automatic';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Payment Overdue - Automatic', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Overdue Order emails are sent to the customer when the amount for the subscription renewal has not been paid within the overdue period.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-overdue-order-automatic.php';
		$this->template_plain = 'emails/plain/subscription-overdue-order-automatic.php';

		$this->subject = __( '[{site_title}] - Subscription Payment Overdue', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Payment Overdue', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'pay_link', 'upcoming_mail_info', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Overdue_Automatic_Email();
