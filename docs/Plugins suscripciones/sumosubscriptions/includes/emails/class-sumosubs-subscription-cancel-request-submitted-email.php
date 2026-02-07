<?php

/**
 * Subscription Cancel Request Submitted.
 * 
 * @class SUMOSubs_Subscription_Cancel_Request_Submitted_Email
 */
class SUMOSubs_Subscription_Cancel_Request_Submitted_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_cancel_request_submitted';
		$this->key            = 'subscription_cancel_request_submitted';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Cancel - Request Submitted', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Cancel - Request Submitted emails are sent to the customers when they have submitted a request to cancel their subscription.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-cancel-request-submitted.php';
		$this->template_plain = 'emails/plain/subscription-cancel-request-submitted.php';

		$this->subject = __( '[{site_title}] - Subscription Cancel Request Submitted', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Cancel Request Submitted', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'cancel_method_requested', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Cancel_Request_Submitted_Email();
