<?php
/**
 * Cancelled Email template
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ASWC_Cancel_Subscription_Email' ) ) {

	/**
	 * Cancelled Email template Class
	 *
	 * @since      1.0.0
	 *
	 * @package    advanced-subscriptions-for-woocommerce
	 * @subpackage advanced-subscriptions-for-woocommerce/email
	 */
	class ASWC_Cancel_Subscription_Email extends WC_Email {
		/**
		 * Create class for email notification.
		 *
		 * @access public
		 */
		public function __construct() {

						$this->id = 'aswc_cancel_subscription';
			$this->title          = __( 'Cancelled Subscription Email Notification', 'advanced-subscriptions-for-woocommerce' );

			$this->description = __( 'This Email Notification Send if any subscription is Cancelled', 'advanced-subscriptions-for-woocommerce' );

			$this->template_html  = 'aswc-cancel-subscription-email-template.php';
			$this->template_plain = 'plain/aswc-cancel-subscription-email-template.php';
			$this->template_base  = ASWC_DIR_PATH . 'emails/templates/';

			parent::__construct();
			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Get email subject.
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Cancelled Susbcription Email {site_title}', 'advanced-subscriptions-for-woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Subscription Cancelled', 'advanced-subscriptions-for-woocommerce' );
		}

		/**
		 * This function is used to trigger for email.
		 *
		 * @since  1.0.0
		 * @param int $aswc_subscription aswc_subscription.
		 * @access public
		 * @return void
		 */
		public function trigger( $aswc_subscription ) {

			$user_email = '';

			if ( $aswc_subscription ) {

				$this->object         = $aswc_subscription;
				$aswc_parent_order_id = aswc_get_meta_data( $aswc_subscription, 'aswc_parent_order', true );
				$aswc_parent_order    = wc_get_order( $aswc_parent_order_id );
				if ( ! empty( $aswc_parent_order ) ) {
						$user_email = $aswc_parent_order->get_billing_email();

				}
			}

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

				$this->send( $user_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

			/**
			 * Get_content_html function.
			 *
			 * @access public
			 * @return string
			 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'aswc_subscription' => $this->object,
					'email_heading'     => $this->get_heading(),
					'sent_to_admin'     => true,
					'plain_text'        => false,
					'email'             => $this,
				),
				'',
				$this->template_base
			);
		}

			/**
			 * Get_content_plain function.
			 *
			 * @access public
			 * @return string
			 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'aswc_subscription' => $this->object,
					'email_heading'     => $this->get_heading(),
					'sent_to_admin'     => true,
					'plain_text'        => true,
					'email'             => $this,
				),
				'',
				$this->template_base
			);
		}

			/**
			 * Initialise Settings Form Fields
			 *
			 * @access public
			 * @return void
			 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'    => array(
					'title'   => __( 'Enable/Disable', 'advanced-subscriptions-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'advanced-subscriptions-for-woocommerce' ),
					'default' => 'no',
				),
				'recipient'  => array(
					'title'       => __( 'Recipient Email Address', 'advanced-subscriptions-for-woocommerce' ),
					'type'        => 'text',
					// translators: placeholder is admin email.
					'description' => sprintf( __( 'Enter recipient email address. Defaults to %s.', 'advanced-subscriptions-for-woocommerce' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
					'placeholder' => '',
					'default'     => '',
					'desc_tip'    => true,
				),
				'subject'    => array(
					'title'       => __( 'Subject', 'advanced-subscriptions-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Enter the email subject', 'advanced-subscriptions-for-woocommerce' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
					'desc_tip'    => true,
				),
				'heading'    => array(
					'title'       => __( 'Email Heading', 'advanced-subscriptions-for-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Email Heading', 'advanced-subscriptions-for-woocommerce' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
					'desc_tip'    => true,
				),
				'email_type' => array(
					'title'       => __( 'Email type', 'advanced-subscriptions-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'advanced-subscriptions-for-woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}
	}

}

return new ASWC_Cancel_Subscription_Email();
