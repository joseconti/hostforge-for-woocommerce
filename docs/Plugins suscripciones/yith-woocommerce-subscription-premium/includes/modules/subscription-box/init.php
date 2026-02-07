<?php
/**
 * Module Subscription box Init.
 *
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

defined( 'YWSBS_SUBSCRIPTION_BOX_MODULE_PATH' ) || define( 'YWSBS_SUBSCRIPTION_BOX_MODULE_PATH', __DIR__ . DIRECTORY_SEPARATOR );
defined( 'YWSBS_SUBSCRIPTION_BOX_MODULE_URL' ) || define( 'YWSBS_SUBSCRIPTION_BOX_MODULE_URL', plugins_url( '/', __FILE__ ) );

// Register path for autoload.
YWSBS_Subscription_Modules::register_module_paths(
	array(
		'class-ywsbs-subscription-box.php'                 => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-box-product.php'         => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-box-admin.php'           => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-box-email-handler.php'   => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-box-cart.php'            => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-box-order.php'           => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-box-frontend.php'        => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/',
		'class-ywsbs-subscription-box-my-account.php'      => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/',
		// Rest API.
		'class-ywsbs-subscription-box-rest.php'            => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/rest-api/',
		'class-ywsbs-subscription-box-product-reviews-controller.php' => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/rest-api/controllers/',
		'class-ywsbs-subscription-box-products-controller.php' => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/rest-api/controllers/',
		'class-ywsbs-subscription-box-cart-controller.php' => YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/rest-api/controllers/',
	)
);

/**
 * Unique access to instance of YWSBS_Subscription_Synchronization class
 *
 * @return YWSBS_Subscription_Box
 */
function YWSBS_Subscription_Box() { //phpcs:ignore
	return YWSBS_Subscription_Box::get_instance();
}

YWSBS_Subscription_Box();
