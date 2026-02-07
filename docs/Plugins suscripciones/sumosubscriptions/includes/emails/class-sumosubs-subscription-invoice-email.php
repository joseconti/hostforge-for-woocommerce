<?php

/**
 * Subscription Invoice Email.
 * 
 * @class SUMOSubs_Subscription_Invoice_Email
 */
class SUMOSubs_Subscription_Invoice_Email extends SUMOSubs_Abstract_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'subscription_invoice_order_manual';
		$this->key            = 'subscription_invoice';
		$this->customer_email = true;
		$this->title          = __( 'Subscription Invoice - Manual', 'sumosubscriptions' );
		$this->description    = addslashes( __( 'Subscription Invoice - Manual emails are sent to the customers when payment has to be made for subscription renewal.', 'sumosubscriptions' ) );

		$this->template_html  = 'emails/subscription-invoice-order-manual.php';
		$this->template_plain = 'emails/plain/subscription-invoice-order-manual.php';

		$this->subject = __( '[{site_title}] - Invoice for Subscription Renewal', 'sumosubscriptions' );
		$this->heading = __( 'Invoice for Subscription Renewal', 'sumosubscriptions' );

		$this->subject_paid = $this->subject;
		$this->heading_paid = $this->heading;

		$this->supports = array( 'mail_to_admin', 'paid_order', 'upcoming_mail_info', 'recipient' );

		// Call parent constructor
		parent::__construct();
	}
}

return new SUMOSubs_Subscription_Invoice_Email();
