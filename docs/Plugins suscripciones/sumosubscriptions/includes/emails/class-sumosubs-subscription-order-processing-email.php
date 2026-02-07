<?php

/**
 * Subscription Order Processing Email.
 * 
 * @class SUMOSubs_Subscription_Order_Processing_Email
 */
class SUMOSubs_Subscription_Order_Processing_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_processing_order';
		$this->key            = 'subscription_order_processing';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Processing Order', 'sumosubscriptions' );
		$this->description    = __( 'Subscription Processing Order emails are sent to the customers when a subscription new order becomes processing.', 'sumosubscriptions' );

		$this->template_html  = 'emails/subscription-processing-order.php';
		$this->template_plain = 'emails/plain/subscription-processing-order.php';

		$this->subject = __( 'Your {site_title} Subscription order receipt from {order_date}', 'sumosubscriptions' );
		$this->heading = __( 'Thank you for your Order', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Order_Processing_Email();
