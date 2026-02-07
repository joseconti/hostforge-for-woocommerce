<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements Privacy DPA of YITH WooCommerce Subscription
 *
 * @class   YITH_YWSBS_Privacy_DPA
 * @package YITH\Subscription
 * @since   1.4.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_YWSBS_Privacy_DPA' ) && class_exists( 'YITH_Privacy_Plugin_Abstract' ) ) {
	/**
	 * Class YITH_YWSBS_Privacy_DPA
	 * Privacy Class.
	 */
	class YITH_YWSBS_Privacy_DPA extends YITH_Privacy_Plugin_Abstract {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * YITH_YWSBS_Privacy constructor.
		 */
		private function __construct() {
			parent::__construct( esc_html_x( 'YITH WooCommerce Subscription Premium', 'Privacy Policy Content', 'yith-woocommerce-subscription' ) );
		}

		/**
		 * Return the message
		 *
		 * @param string $section Section.
		 *
		 * @return string
		 */
		public function get_privacy_message( $section ) {
			$message = '';

			switch ( $section ) {
				case 'collect_and_store':
					$message = '<p>' . esc_html__( 'When you buy a subscription product the following information will be stored:', 'yith-woocommerce-subscription' ) . '</p>' .
					'<ul>' .
					'<li>' . esc_html__( 'Your name, address, email, phone number, and billing address that will be used to populate the order and the recurring order.', 'yith-woocommerce-subscription' ) . '</li>' .
					'<li>' . esc_html__( 'Shipping address: this data allow us to send you the current order and the recurring ones.', 'yith-woocommerce-subscription' ) . '</li>' .
					'<li>' . esc_html__( 'Location, IP address and browser type: we\'ll use these for estimating taxes and shipping purposes.', 'yith-woocommerce-subscription' ) . '</li>' .
					'</ul>' .
					'<p>' . esc_html__( 'We\'ll use this information for different purposes, such as, to:', 'yith-woocommerce-subscription' ) . '</p>' .
					'<ul>' .
					'<li>' . esc_html__( 'Send you information about your subscription', 'yith-woocommerce-subscription' ) . '</li>' .
					'</ul>' .
					'<p>' . esc_html__( 'We generally store your information for as long as it is needed for the purposes for which we collect and use it, and as long as we are not legally required to continue to keep it.', 'yith-woocommerce-subscription' ) . '</p>' .
					'<p class="privacy-policy-tutorial">' . esc_html__( 'For example, if a subscription is cancelled, it is permanently removed after xxx months. This includes your name, email address and billing and shipping addresses associated with that subscription.', 'yith-woocommerce-subscription' ) . '</p>';
					break;
				case 'has_access':
					$message = '<p>' . esc_html__( 'Members of our team have access to the information you provide us. For example, both Administrators and Shop Managers can access:', 'yith-woocommerce-subscription' ) . '</p>' .
					'<ul>' .
					'<li>' . esc_html__( 'Subscription information like what was purchased, when it was purchased and where it should be sent, and customer information like your name, email address, and billing and shipping information.', 'yith-woocommerce-subscription' ) . '</li>' .
					'</ul>' .
					'<p>' . esc_html__( 'Our team members have access to this information to help fulfill orders, process refunds and support you.', 'yith-woocommerce-subscription' ) . '</p>';
					break;
				default:
					break;
			}

			return apply_filters( 'ywsbs_privacy_policy_content', $message, $section );
		}
	}
}
