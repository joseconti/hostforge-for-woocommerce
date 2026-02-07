<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Send Email to Customer
 *
 * @class   YITH_WC_Customer_Subscription
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Customer_Subscription' ) ) {

	/**
	 * YITH_WC_Customer_Subscription
	 *
	 * @since 1.0.0
	 */
	class YITH_WC_Customer_Subscription extends YITH_WC_Subscription_Email {

		/**
		 * Constructor method, used to return object of the class to WC.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			// Cancel sending emails if this is a duplicate website.
			if ( ! apply_filters( 'ywsbs_send_email_in_main_site', YITH_WC_Subscription()->is_main_site() ) ) {
				return;
			}

			// Triggers for this email.
			$this->template_base = YITH_YWSBS_TEMPLATE_PATH . '/';
			$this->email_type    = 'html';
			$this->template_html = 'emails/' . $this->id . '.php';

			add_action( $this->id . '_mail_notification', array( $this, 'trigger' ), 15 );

			// Call parent constructor.
			parent::__construct();

			$this->customer_email = true;

			if ( ! $this->email_type ) {
				$this->email_type = 'html';
			}
		}

		/**
		 * Return true if send a copy of this email to the admin.
		 *
		 * @return bool
		 */
		public function send_copy_to_admin() {
			return 'yes' === $this->get_option( 'send_to_admin' );
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
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
		}

		/**
		 * Initialise settings form fields
		 *
		 * @access public
		 * @return void
		 */
		public function init_form_fields() {
			parent::init_form_fields();

			$this->form_fields['send_to_admin'] = array(
				'title'   => __( 'Send to admin?', 'yith-woocommerce-subscription' ),
				'type'    => 'checkbox',
				'label'   => __( 'Send a copy of this email to the admin', 'yith-woocommerce-subscription' ),
				'default' => 'no',
			);
		}
	}
}
