<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Send Email to Customer to reminder a renew.
 *
 * @class   YITH_WC_Customer_Subscription_Renew_Reminder
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Customer_Subscription_Renew_Reminder' ) ) {
	/**
	 * YITH_WC_Customer_Subscription_Renew_Reminder
	 *
	 * @since 1.0.0
	 */
	class YITH_WC_Customer_Subscription_Renew_Reminder extends YITH_WC_Customer_Subscription {
		/**
		 * Constructor method, used to return object of the class to WC
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->id          = 'ywsbs_customer_subscription_renew_reminder';
			$this->title       = __( 'Subscription renew reminder', 'yith-woocommerce-subscription' );
			$this->description = __( 'This email is sent to the customer as a reminder for the next payment.', 'yith-woocommerce-subscription' );
			$this->email_type  = 'html';
			$this->heading     = __( 'Subscription renew reminder', 'yith-woocommerce-subscription' );
			$this->subject     = __( 'Reminder for the order renewal {order_number}', 'yith-woocommerce-subscription' );

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

            $already_sent = $subscription->get( '_ywsbs_reminder_email_sent' );

			// Check if this email type is enabled, recipient is set.
			if ( ! $this->is_enabled() || ! $this->get_recipient() || $subscription->get_order_id() === 0 || 'yes' === $already_sent ) {
				return;
			}

			$order = wc_get_order( $subscription->get_order_id() );
			if ( ! $order ) {
				return;
			}

			$this->object = $subscription;
			$this->order  = $order;

			$this->placeholders['{order_number}'] = $order->get_order_number();

            $return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
            if ( $return ) {
                YITH_WC_Activity()->add_activity( $subscription->get_id(), 'email_sent', 'success', $subscription->get_order_id(), __( 'Renew reminder sent.', 'yith-woocommerce-subscription' ) );
                $subscription->set( '_ywsbs_reminder_email_sent', 'yes' );
            }
		}

		/**
		 * Get the right email args
		 *
		 * @return array
		 */
		protected function get_template_args() {
			return array_merge(
				parent::get_template_args(),
				array(
					'order'              => $this->get_order(),
					'next_activity'      => __( 'Renew', 'yith-woocommerce-subscription' ),
					'next_activity_date' => $this->get_subscription()->get_payment_due_date(),
				)
			);
		}

		/**
		 * Initialise settings form fields
		 *
		 * @access public
		 * @return void
		 */
		public function init_form_fields() {
			parent::init_form_fields();

			$this->form_fields = array_merge(
				$this->form_fields,
				array(
					'delay' => array(
						'title'       => __( 'Number of days before the next subscription payment.', 'yith-woocommerce-subscription' ),
						'type'        => 'number',
						'css'         => 'width:50px;',
						'description' => __( 'Specify the number of days before the next subscription payment to send this email.', 'yith-woocommerce-subscription' ),
						'placeholder' => '',
						'default'     => '15',
					),
				)
			);
		}
	}
}

// returns instance of the mail on file include.
return new YITH_WC_Customer_Subscription_Renew_Reminder();
