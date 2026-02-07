<?php
/**
 * Module Subscription Synchronization Init.
 *
 * @since   3.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

defined( 'YWSBS_SYNCHRONIZATION_MODULE_PATH' ) || define( 'YWSBS_SYNCHRONIZATION_MODULE_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
defined( 'YWSBS_SYNCHRONIZATION_MODULE_URL' ) || define( 'YWSBS_SYNCHRONIZATION_MODULE_URL', plugins_url( '/', __FILE__ ) );

// Register path for autoload.
YWSBS_Subscription_Modules::register_module_paths(
	array(
		'class-ywsbs-subscription-synchronization.php' => YWSBS_SYNCHRONIZATION_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-synchronization-legacy.php' => YWSBS_SYNCHRONIZATION_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-synchronization-cart.php' => YWSBS_SYNCHRONIZATION_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-synchronization-admin.php' => YWSBS_SYNCHRONIZATION_MODULE_PATH . 'includes/',
	)
);

/**
 * Unique access to instance of YWSBS_Subscription_Synchronization class
 *
 * @return YWSBS_Subscription_Synchronization
 */
function YWSBS_Subscription_Synchronization() { //phpcs:ignore
	return YWSBS_Subscription_Synchronization::get_instance();
}

YWSBS_Subscription_Synchronization();
