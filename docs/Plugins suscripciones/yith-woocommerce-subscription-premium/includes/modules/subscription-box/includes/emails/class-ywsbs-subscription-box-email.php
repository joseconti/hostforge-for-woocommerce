<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Send Email to Customer about box content
 *
 * @class   YWSBS_Subscription_Box_Email
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_Email' ) ) {

	/**
	 * YWSBS_Subscription_Box_Email
	 *
	 * @since 1.0.0
	 */
	class YWSBS_Subscription_Box_Email extends YITH_WC_Customer_Subscription {

		/**
		 * Constructor method, used to return object of the class to WC.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->id           = 'ywsbs_subscription_box';
			$this->title        = __( 'Subscription box', 'yith-woocommerce-subscription' );
			$this->description  = __( 'This email informs the customer of the content of his next subscription box.', 'yith-woocommerce-subscription' );
			$this->heading      = __( 'We\'re preparing your next box', 'yith-woocommerce-subscription' );
			$this->subject      = __( 'We\'re preparing your next box', 'yith-woocommerce-subscription' );
			$this->placeholders = array();

			// Call parent constructor.
			parent::__construct();

			$this->template_base = YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'templates/';
			$this->template_html = 'emails/subscription-box.php';
		}

		/**
		 * Set email recipient
		 *
		 * @since  4.0.0
		 */
		public function set_recipient() {
			$this->recipient = $this->object->get_billing_email();
			// Add admin email.
			if ( $this->send_copy_to_admin() ) {
				$this->recipient .= ',' . get_option( 'admin_email' );
			}
		}

		/**
		 * Method triggered to send email
		 *
		 * @since  4.0.0
		 * @param YWSBS_Subscription $subscription Subscription.
		 * @return void
		 */
		public function trigger( $subscription ) {
			$this->setup_locale();

			$this->object = $subscription;
			$this->set_recipient();
			// Check if this email type is enabled, recipient is set.
			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Format box content for email
		 *
		 * @since  4.0.0
		 * @return array
		 */
		public function get_formatted_box_content() {
			if ( has_filter( 'woocommerce_is_email_preview' ) ) {

				$placeholder_image = wc_placeholder_img_src( 'woocommerce_thumbnail' );

				return array(
					array(
						'label' => 'Step #1',
						'items' => array(
							array(
								'image' => $placeholder_image,
								'name'  => 'Dummy Product #1',
							),
							array(
								'image' => $placeholder_image,
								'name'  => 'Dummy Product #2',
							),
							array(
								'image' => $placeholder_image,
								'name'  => 'Dummy Product #3',
							),
						),
					),
					array(
						'label' => 'Step #2',
						'items' => array(
							array(
								'image' => $placeholder_image,
								'name'  => 'Dummy Product #2',
							),
							array(
								'image' => $placeholder_image,
								'name'  => 'Dummy Product #5',
							),
						),
					),
				);
			} else {
				return ywsbs_box_get_content_to_display( $this->get_subscription()->get( 'next_box_content' ) ?: $this->get_subscription()->get( 'box_content' ), $this->get_subscription()->get_product() ); // phpcs:ignore.
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
					'box_content'   => $this->get_formatted_box_content(),
					'delivery_date' => ywsbs_box_calculate_next_delivery_date( $this->get_subscription(), wc_date_format() ),
				)
			);
		}
	}
}

return new YWSBS_Subscription_Box_Email();
