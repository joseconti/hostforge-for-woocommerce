<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Send Email to Administrator to Advice that a customer has paused/resumed/cancelled
 *
 * @class   YITH_WC_Subscription_Status
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Status' ) ) {
	/**
	 * YITH_WC_Subscription_Status
	 *
	 * @since 1.0.0
	 */
	class YITH_WC_Subscription_Status extends YITH_WC_Subscription_Email {

		/**
		 * True when the email notification is sent to customers.
		 *
		 * @var string
		 */
		protected $reply_to = null;

		/**
		 * Constructor method, used to return object of the class to WC
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->id          = 'ywsbs_subscription_admin_mail';
			$this->title       = __( 'Subscription Status', 'yith-woocommerce-subscription' );
			$this->description = __( 'This email is sent to the administrator to inform that the status of a subscription is changed', 'yith-woocommerce-subscription' );

			$this->heading  = __( 'A subscription status changed', 'yith-woocommerce-subscription' );
			$this->subject  = __( 'Subscription {subscription_id} is now {status}', 'yith-woocommerce-subscription' );
			$this->reply_to = '';

			$this->email_type = 'html';

			$this->template_base = YITH_YWSBS_TEMPLATE_PATH . '/';
			$this->template_html = 'emails/email-subscription-status.php';

			// Triggers for this email.
			add_action( 'ywsbs_subscription_paused_mail_notification', array( $this, 'trigger' ), 15 );
			add_action( 'ywsbs_subscription_resumed_mail_notification', array( $this, 'trigger' ), 15 );
			add_action( 'ywsbs_subscription_cancelled_mail_notification', array( $this, 'trigger' ), 15 );
			add_action( 'ywsbs_subscription_admin_mail_notification', array( $this, 'trigger' ), 15 );

			parent::__construct();

			if ( ! $this->email_type ) {
				$this->email_type = 'html';
			}

			// Other settings.
			$this->recipient = $this->get_option( 'recipient' );

			if ( ! $this->recipient ) {
				$this->recipient = get_option( 'admin_email' );
			}
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

			if ( ! $this->is_enabled() ) {
				return;
			}

			$this->object = $subscription;

			$status = ywsbs_get_status();

			$this->placeholders['{subscription_id}'] = $subscription->get_number();
			$this->placeholders['{status}']          = isset( $status[ $subscription->get_status() ] ) ? $status[ $subscription->get_status() ] : $subscription->get_status();

			if ( ! is_array( $this->get_option( 'status' ) ) || ! in_array( $subscription->get_status(), (array) $this->get_option( 'status' ), true ) ) {
				return;
			}

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
		}

		/**
		 * Initialise settings form fields
		 *
		 * @return void
		 */
		public function init_form_fields() {
			parent::init_form_fields();

			$this->form_fields['status'] = array(
				'title'       => __( 'Send email for these status', 'yith-woocommerce-subscription' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose which subscription status to send.', 'yith-woocommerce-subscription' ),
				'default'     => array( 'expired', 'cancelled' ),
				'class'       => 'wc-enhanced-select',
				'options'     => ywsbs_get_status(),
				'desc_tip'    => true,
			);
		}
	}
}


// returns instance of the mail on file include.
return new YITH_WC_Subscription_Status();
