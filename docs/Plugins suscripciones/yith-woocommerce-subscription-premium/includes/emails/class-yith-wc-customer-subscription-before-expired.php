<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Send Email to Customer before that the subscription expire.
 *
 * @class   YITH_WC_Customer_Subscription_Before_Expired
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Customer_Subscription_Before_Expired' ) ) {

	/**
	 * YITH_WC_Customer_Subscription_Before_Expired
	 *
	 * @since 1.0.0
	 */
	class YITH_WC_Customer_Subscription_Before_Expired extends YITH_WC_Customer_Subscription {

		/**
		 * Constructor method, used to return object of the class to WC
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->id          = 'ywsbs_customer_subscription_before_expired';
			$this->title       = __( 'Subscription is going to expire', 'yith-woocommerce-subscription' );
			$this->description = __( 'This email is sent to the customer when the subscription is going to expire.', 'yith-woocommerce-subscription' );
			$this->email_type  = 'html';
			$this->heading     = __( 'Your subscription is going to expire', 'yith-woocommerce-subscription' );
			$this->subject     = __( 'Your subscription to {site_title} is going to expire', 'yith-woocommerce-subscription' );

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Method triggered to send email
		 *
		 * @param YWSBS_Subscription $subscription Subscription.
		 *
		 * @return void
		 * @since  1.0
		 */
		public function trigger( $subscription ) {

			$this->recipient = $subscription->get_billing_email();

			if ( $this->send_copy_to_admin() ) {
				$this->recipient .= ',' . get_option( 'admin_email' );
			}

			// Check if this email type is enabled, recipient is set.
			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->object = $subscription;
			$return       = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
			if ( $return ) {
				update_post_meta( $subscription->get_id(), '_ywsbs_before_expired_email_sent', 'yes' );
			}
		}
	}
}

// returns instance of the mail on file include.
return new YITH_WC_Customer_Subscription_Before_Expired();
