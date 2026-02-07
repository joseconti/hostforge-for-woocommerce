<?php
/**
 * Plan Going to Expire Email template
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/email
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Aswc_LoaderReminder_Email' ) ) {

	/**
	 * Renewal Invoice Email template Class
	 *
	 * @since      1.0.0
	 *
	 * @package    Aswc_Loader
	 * @subpackage Aswc_Loader/email
	 */
	class Aswc_LoaderReminder_Email extends WC_Email {
		/**
		 * Create class for email notification.
		 *
		 * @access public
		 */
		public function __construct() {

			$this->id             = 'aswc_recurring_reminder';
			$this->title          = __( 'Subscription Recurring Payment Notification', 'advanced-subscriptions-for-woocommerce' );
			$this->customer_email = true;
			$this->description    = __( 'This Email Notification Send to customer regarding recurring payment reminder', 'advanced-subscriptions-for-woocommerce' );

						$this->template_html  = 'aswc-reminder-email-template.php';
						$this->template_plain = 'plain/aswc-reminder-email-template.php';
			$this->template_base              = ASWC_INCLUDES_PATH . 'emails/templates/';

			parent::__construct();
		}

		/**
		 * Get email subject.
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Reminder Of Recurring Payment', 'advanced-subscriptions-for-woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  1.0.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Reminder Of Recurring Payment', 'advanced-subscriptions-for-woocommerce' );
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

			if ( $aswc_subscription ) {

				$this->object = $aswc_subscription;
				if ( aswc_loader_is_hpos_enabled() ) {
					$subscription = new ASWC_Subscription( $aswc_subscription );
				} else {
					$subscription = wc_get_order( $aswc_subscription );
				}
				$user_email = $subscription->get_billing_email();
				if ( isset( $user_email ) && ! empty( $user_email ) ) {
					$this->recipient = $user_email;
				} else {
					$aswc_parent_order_id = aswc_get_meta_data( $aswc_subscription, 'aswc_parent_order', true );
					$aswc_parent_order    = wc_get_order( $aswc_parent_order_id );
					$user_email           = $aswc_parent_order->get_billing_email();
					$this->recipient      = $user_email;
				}
			}

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
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
					'sent_to_admin'     => false,
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
					'sent_to_admin'     => false,
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

return new Aswc_LoaderReminder_Email();
