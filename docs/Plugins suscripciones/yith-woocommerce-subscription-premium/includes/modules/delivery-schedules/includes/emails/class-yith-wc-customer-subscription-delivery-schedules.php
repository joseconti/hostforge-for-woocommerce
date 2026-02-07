<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Send Email to Customer when a payment fails
 *
 * @class   YITH_WC_Customer_Subscription_Delivery_Schedules
 * @since   1.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Customer_Subscription_Delivery_Schedules' ) ) {
	/**
	 * YITH_WC_Customer_Subscription_Delivery_Schedules
	 *
	 * @since 1.0.0
	 */
	class YITH_WC_Customer_Subscription_Delivery_Schedules extends YITH_WC_Customer_Subscription {

		/**
		 * Constructor method, used to return object of the class to WC
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->id          = 'ywsbs_customer_subscription_delivery_schedules';
			$this->title       = __( 'Subscription delivery schedules', 'yith-woocommerce-subscription' );
			$this->description = __( 'This email is sent to the customer when a delivery schedules is shipped', 'yith-woocommerce-subscription' );
			$this->email_type  = 'html';
			$this->heading     = __( 'Subscription shipped', 'yith-woocommerce-subscription' );
			$this->subject     = __( 'Your subscription has been shipped.', 'yith-woocommerce-subscription' );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Method triggered to send email
		 *
		 * @param mixed $delivery_schedules Delivery Schedules.
		 *
		 * @return void
		 * @since  1.0
		 */
		public function trigger( $delivery_schedules ) {

			if ( ! $delivery_schedules ) {
				return;
			}

			$subscription    = ywsbs_get_subscription( $delivery_schedules->subscription_id );
			$this->recipient = $subscription->get_billing_email();

			if ( $this->send_copy_to_admin() ) {
				$this->recipient .= ',' . get_option( 'admin_email' );
			}
			// Check if this email type is enabled, recipient is set.
			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->object = $subscription;

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
		}
	}
}

// returns instance of the mail on file include.
return new YITH_WC_Customer_Subscription_Delivery_Schedules();
