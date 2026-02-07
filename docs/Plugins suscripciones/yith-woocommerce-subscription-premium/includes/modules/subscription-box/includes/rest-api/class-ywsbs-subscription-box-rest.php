<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_Rest REST API.
 *
 * @class   YWSBS_Subscription_Box_Rest
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_Rest' ) ) {

	/**
	 * Class YWSBS_Subscription_Box_Rest
	 */
	class YWSBS_Subscription_Box_Rest {

		/**
		 * Endpoint namespace.
		 *
		 * @var string
		 */
		const NAMESPACE = 'yith-ywsbs';

		/**
		 * Install class
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function install() {
			// REST API extensions init.
			add_action( 'rest_api_init', array( __CLASS__, 'init' ) );
		}

		/**
		 * Init class
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function init() {
			foreach ( self::get_controllers() as $controller ) {
				if ( ! class_exists( $controller ) ) {
					continue;
				}

				$class = new $controller();
				$class->register_routes();
			}
		}

		/**
		 * Get controllers
		 *
		 * @since 4.0.0
		 * @return array
		 */
		protected static function get_controllers() {
			return apply_filters(
				'ywsbs_subscription_box_rest_controllers',
				array(
					'YWSBS_Subscription_Box_Product_Reviews_Controller',
					'YWSBS_Subscription_Box_Products_Controller',
					'YWSBS_Subscription_Box_Cart_Controller',
				)
			);
		}
	}
}
