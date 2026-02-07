<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Helper Class Legacy.
 *
 * @class   YITH_WC_Subscription
 * @package YITH\Subscription
 * @since   3.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Helper_Legacy' ) ) {

	/**
	 * Class YWSBS_Subscription_Helper
	 */
	abstract class YWSBS_Subscription_Helper_Legacy {

		/**
		 * Register ywsbs_subscription post type
		 *
		 * @since 1.0.0
		 * @deprecated 3.0.0
		 */
		public function register_subscription_post_type() {
			_deprecated_function( __METHOD__, '3.0.0', 'YITH_WC_Subscription_Install::register_post_type' );
			YITH_WC_Subscription_Install::register_post_type();
		}

		/**
		 * Flush rules if the event is queued.
		 *
		 * @since 2.0.0
		 * @deprecated 3.0.0
		 */
		public static function maybe_flush_rewrite_rules() {
			_deprecated_function( __METHOD__, '3.0.0', '' );
			flush_rewrite_rules();
		}

		/**
		 * Return the list of subscription capabilities
		 *
		 * @return array
		 * @since  2.0.0
		 * @deprecated 3.0.0
		 */
		public static function get_subscription_capabilities() {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Capabilities::get_capabilities' );
			return YWSBS_Subscription_Capabilities::get_capabilities();
		}

		/**
		 * Add subscription management capabilities to Admin and Shop Manager
		 *
		 * @since 1.0.0
		 * @deprecated 3.0.0
		 */
		public function add_subscription_capabilities() {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Capabilities::add_capabilities' );
			YWSBS_Subscription_Capabilities::add_capabilities();
		}

		/**
		 * Regenerate the capabilities.
		 *
		 * @since 2.0.0
		 * @deprecated 3.0.0
		 */
		public static function maybe_regenerate_capabilities() {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Capabilities::remove_capabilities( \'shop_manager\' )' );
			YWSBS_Subscription_Capabilities::remove_capabilities( 'shop_manager' );
		}
	}
}
