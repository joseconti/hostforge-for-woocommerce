<?php
/**
 * Module Delivery Schedules Synchronization Init.
 *
 * @since   3.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

defined( 'YWSBS_DELIVERY_SCHEDULES_MODULE_PATH' ) || define( 'YWSBS_DELIVERY_SCHEDULES_MODULE_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
defined( 'YWSBS_DELIVERY_SCHEDULES_MODULE_URL' ) || define( 'YWSBS_DELIVERY_SCHEDULES_MODULE_URL', plugins_url( '/', __FILE__ ) );

// Register path for autoload.
YWSBS_Subscription_Modules::register_module_paths(
	array(
		'class-ywsbs-subscription-delivery-schedules.php' => YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-delivery-schedules-legacy.php' => YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-delivery-schedules-db.php' => YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-delivery-schedules-admin.php' => YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . 'includes/admin/',
		'class-ywsbs-subscription-delivery-schedules-list-table.php' => YWSBS_DELIVERY_SCHEDULES_MODULE_PATH . 'includes/admin/',
	)
);

// Register activation hook.
YWSBS_Subscription_Modules::register_module_activation_hook( __FILE__, 'YWSBS_Subscription_Delivery_Schedules_DB::create_table' );

/**
 * Unique access to instance of YWSBS_Delivery_Schedules_List_Table class
 *
 * @return YWSBS_Subscription_Delivery_Schedules
 */
function YWSBS_Subscription_Delivery_Schedules() { //phpcs:ignore
	return YWSBS_Subscription_Delivery_Schedules::get_instance();
}

YWSBS_Subscription_Delivery_Schedules();
