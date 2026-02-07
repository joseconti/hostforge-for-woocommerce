<?php

/**
 * Subscription New Order Email.
 * 
 * @class SUMOSubs_Subscription_New_Order_Email
 */
class SUMOSubs_Subscription_New_Order_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'subscription_new_order';
		$this->key         = 'subscription_new_order';
		$this->title       = __( 'Subscription New Order', 'sumosubscriptions' );
		$this->description = __( 'Subscription New Order emails are sent to the customers when a subscription new order has been generated.', 'sumosubscriptions' );

		$this->template_html  = 'emails/subscription-new-order.php';
		$this->template_plain = 'emails/plain/subscription-new-order.php';

		$this->subject  = __( '[{site_title}] - New Subscription Order (#{order_number}) - {order_date}', 'sumosubscriptions' );
		$this->heading  = __( 'New Subscription Order', 'sumosubscriptions' );
		$this->supports = array( 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_New_Order_Email();
