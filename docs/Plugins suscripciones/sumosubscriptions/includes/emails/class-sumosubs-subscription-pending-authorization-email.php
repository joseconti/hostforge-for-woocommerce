<?php

/**
 * Subscription Pending Authorization Email.
 * 
 * @class SUMOSubs_Subscription_Pending_Authorization_Email
 */
class SUMOSubs_Subscription_Pending_Authorization_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_pending_authorization';
		$this->key            = 'subscription_pending_authorization';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Pending Authorization', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Pending Authorization emails are sent to the customer when authorized card is declined by the bank.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-pending-authorization.php';
		$this->template_plain = 'emails/plain/subscription-pending-authorization.php';

		$this->subject = __( '[{site_title}] - Subscription Pending Authorization', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Pending Authorization', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'pay_link', 'upcoming_mail_info', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Pending_Authorization_Email();
