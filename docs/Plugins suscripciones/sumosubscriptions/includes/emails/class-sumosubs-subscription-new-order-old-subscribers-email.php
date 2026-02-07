<?php

/**
 * Subscription New Order - Old Subscriber Email.
 * 
 * @class SUMOSubs_Subscription_New_Order_Old_Subscribers_Email
 */
class SUMOSubs_Subscription_New_Order_Old_Subscribers_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'subscription_new_order_old_subscribers';
		$this->key         = 'subscription_new_order_old_subscribers';
		$this->title       = __( 'Subscription New Order - Old Subscribers', 'sumosubscriptions' );
		$this->description = __( 'Subscription New Order - Old Subscribers emails are sent to the customers when a subscription new order has been generated.', 'sumosubscriptions' );

		$this->template_html  = 'emails/subscription-new-order-old-subscribers.php';
		$this->template_plain = 'emails/plain/subscription-new-order-old-subscribers.php';

		$this->subject  = __( '[{site_title}] - New Subscription Order (#{order_number}) - {order_date}', 'sumosubscriptions' );
		$this->heading  = __( 'New Subscription Order', 'sumosubscriptions' );
		$this->supports = array( 'recipient' );

		// Call parent constuctor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_New_Order_Old_Subscribers_Email();
