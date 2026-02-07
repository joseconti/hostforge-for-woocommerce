<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Send Email to Customer when the subscription is suspended.
 *
 * @class   YITH_WC_Customer_Subscription_Suspended
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Customer_Subscription_Suspended' ) ) {
	/**
	 * YITH_WC_Customer_Subscription_Suspended
	 *
	 * @since 1.0.0
	 */
	class YITH_WC_Customer_Subscription_Suspended extends YITH_WC_Customer_Subscription {

		/**
		 * Constructor method, used to return object of the class to WC
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			// Call parent constructor.
			$this->id          = 'ywsbs_customer_subscription_suspended';
			$this->title       = __( 'Subscription suspended', 'yith-woocommerce-subscription' );
			$this->description = __( 'This email is sent to the customer when the subscription is suspended', 'yith-woocommerce-subscription' );
			$this->email_type  = 'html';
			$this->heading     = __( 'Your subscription has been suspended', 'yith-woocommerce-subscription' );
			$this->subject     = __( 'Your subscription has been suspended', 'yith-woocommerce-subscription' );

			parent::__construct();
		}

		/**
		 * Method triggered to send email
		 *
		 * @param YWSBS_Subscription $subscription Subscription.
		 * @return void
		 * @since  1.0
		 */
		public function trigger( $subscription ) {

			$this->recipient = $subscription->get_billing_email();

			if ( $this->send_copy_to_admin() ) {
				$this->recipient .= ',' . get_option( 'admin_email' );
			}

			// Check if this email type is enabled, recipient is set.
			if ( ! $this->is_enabled() || ! $this->get_recipient() || $subscription->get_renew_order_id() === 0 ) {
				return;
			}

			$order = $subscription->get_renew_order();

			if ( ! $order ) {
				return;
			}

			$this->object = $subscription;
			$this->order  = $order;

			$this->placeholders['{order_number}'] = $order->get_order_number();

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
		}

		/**
		 * Get the right email args
		 *
		 * @return array
		 */
		protected function get_template_args() {
			$change_status_after_renew_order_creation = (array) get_option( 'ywsbs_change_status_after_renew_order_creation' );
			$wait_for                                 = isset( $change_status_after_renew_order_creation['wait_for'] ) ? HOUR_IN_SECONDS * intval( $change_status_after_renew_order_creation['wait_for'] ) : 0;
			$next_activity_date                       = $this->get_subscription()->get_payment_due_date() + $wait_for + ywsbs_get_overdue_time() + ywsbs_get_suspension_time();

			return array_merge(
				parent::get_template_args(),
				array(
					'order'              => $this->get_order(),
					'next_activity'      => __( 'cancelled', 'yith-woocommerce-subscription' ),
					'next_activity_date' => $next_activity_date,
				)
			);
		}
	}
}


// returns instance of the mail on file include.
return new YITH_WC_Customer_Subscription_Suspended();
