<?php 
/**
 * Implements YITH WooCommerce Subscription Database Class
 *
 * @class   YITH_WC_Subscription
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_DB_Legacy' ) ) {
	/**
	 * Class YITH_WC_Subscription_DB_Legacy
	 * handle DB custom tables
	 *
	 * @abstract
	 */
	abstract class YITH_WC_Subscription_DB_Legacy {

		/**
		 * Database version.
		 *
		 * @var string DB version
		 * @deprecated 3.0.0
		 */
		public static $version = '2.3.0';

		/**
		 * Activity Log table name
		 *
		 * @var string
		 * @deprecated 3.0.0
		 */
		public static $activities_log = 'yith_ywsbs_activities_log';

		/**
		 * Delivery schedules table name
		 *
		 * @var string
		 * @deprecated 3.0.0
		 */
		public static $delivery_schedules = 'yith_ywsbs_delivery_schedules';

		/**
		 * Subscription stats table name
		 *
		 * @var string
		 * @deprecated 3.0.0
		 */
		public static $subscription_stats = 'yith_ywsbs_stats';

		/**
		 * Subscription stats table name
		 *
		 * @var string
		 * @deprecated 3.0.0
		 */
		public static $subscription_order_lookup = 'yith_ywsbs_order_lookup';
		/**
		 * Subscription stats table name
		 *
		 * @var string
		 * @deprecated 3.0.0
		 */
		public static $subscription_revenue_lookup = 'yith_ywsbs_revenue_lookup';

		/**
		 * Subscription customer user table name
		 *
		 * @var string
		 * @deprecated 3.0.0
		 */
		public static $subscription_customer_lookup = 'yith_ywsbs_customer_lookup';

		/**
		 * Install the database
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public static function install() {
			self::create_db_tables();
		}

		/**
		 * Create table
		 *
		 * @param bool $force Force the creation.
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public static function create_db_tables( $force = false ) {
			_deprecated_function( __METHOD__, '3.0.0', 'YITH_WC_Subscription_Install::create_tables' );
			YITH_WC_Subscription_Install::create_tables();
		}
	}
}
