<?php

/**
 * Subscription Order Completed Email.
 * 
 * @class SUMOSubs_Subscription_Order_Completed_Email
 */
class SUMOSubs_Subscription_Order_Completed_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_completed_order';
		$this->key            = 'subscription_order_completed';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Completed Order', 'sumosubscriptions' );
		$this->description    = __( 'Subscription Completed Order emails are sent to the customers when a subscription new order has been completed.', 'sumosubscriptions' );

		$this->template_html  = 'emails/subscription-completed-order.php';
		$this->template_plain = 'emails/plain/subscription-completed-order.php';

		$this->subject = __( 'Your {site_title} order from {order_date} is Complete', 'sumosubscriptions' );
		$this->heading = __( 'Your Subscription order is Complete', 'sumosubscriptions' );

		$this->supports = array( 'mail_to_admin', 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Order_Completed_Email();
