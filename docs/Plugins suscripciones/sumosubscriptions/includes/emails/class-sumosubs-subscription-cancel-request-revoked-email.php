<?php

/**
 * Subscription Cancel Request Revoked.
 * 
 * @class SUMOSubs_Subscription_Cancel_Request_Revoked_Email
 */
class SUMOSubs_Subscription_Cancel_Request_Revoked_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_cancel_request_revoked';
		$this->key            = 'subscription_cancel_request_revoked';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Cancel - Request Revoked', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Cancel - Request Revoked emails are sent to the customers when they revoke the cancel request.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-cancel-request-revoked.php';
		$this->template_plain = 'emails/plain/subscription-cancel-request-revoked.php';

		$this->subject = __( '[{site_title}] - Subscription Cancel Request Revoked', 'sumosubscriptions' );
		$this->heading = __( 'Subscription Cancel Request Revoked', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Cancel_Request_Revoked_Email();
