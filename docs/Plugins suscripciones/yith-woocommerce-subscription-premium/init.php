<?php
/**
 * Plugin Name: YITH WooCommerce Subscription Premium
 * Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-subscription/
 * Description: <code><strong>YITH WooCommerce Subscription</strong></code> allows enabling automatic recurring payments on your products. Once you buy a subscription-based product, the plugin will renew the payment automatically based on your own settings. Perfect for any kind of subscriptions, like magazines, software and so on. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce shop on <strong>YITH</strong></a>.
 * Version: 4.18.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-woocommerce-subscription
 * Domain Path: /languages/
 * WC requires at least: 10.2
 * WC tested up to: 10.4
 * Requires Plugins: woocommerce
 *
 * @package YITH\Subscription
 */

defined( 'ABSPATH' ) || exit;

// Free version deactivation if installed.
if ( ! function_exists( 'yith_deactivate_plugins' ) ) {
	require_once 'plugin-fw/yit-deactive-plugin.php';
}
yith_deactivate_plugins( 'YITH_YWSBS_FREE_INIT', plugin_basename( __FILE__ ) );

// Define constants ________________________________________.

! defined( 'YITH_YWSBS_VERSION' ) && define( 'YITH_YWSBS_VERSION', '4.18.0' );
! defined( 'YITH_YWSBS_PREMIUM' ) && define( 'YITH_YWSBS_PREMIUM', plugin_basename( __FILE__ ) );
! defined( 'YITH_YWSBS_INIT' ) && define( 'YITH_YWSBS_INIT', plugin_basename( __FILE__ ) );
! defined( 'YITH_YWSBS_FILE' ) && define( 'YITH_YWSBS_FILE', __FILE__ );
! defined( 'YITH_YWSBS_DIR' ) && define( 'YITH_YWSBS_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'YITH_YWSBS_URL' ) && define( 'YITH_YWSBS_URL', plugins_url( '/', __FILE__ ) );
! defined( 'YITH_YWSBS_ASSETS_URL' ) && define( 'YITH_YWSBS_ASSETS_URL', YITH_YWSBS_URL . 'assets' );
! defined( 'YITH_YWSBS_TEMPLATE_PATH' ) && define( 'YITH_YWSBS_TEMPLATE_PATH', YITH_YWSBS_DIR . 'templates' );
! defined( 'YITH_YWSBS_VIEWS_PATH' ) && define( 'YITH_YWSBS_VIEWS_PATH', YITH_YWSBS_DIR . 'views' );
! defined( 'YITH_YWSBS_INC' ) && define( 'YITH_YWSBS_INC', YITH_YWSBS_DIR . 'includes/' );
! defined( 'YITH_YWSBS_SLUG' ) && define( 'YITH_YWSBS_SLUG', 'yith-woocommerce-subscription' );
! defined( 'YITH_YWSBS_POST_TYPE' ) && define( 'YITH_YWSBS_POST_TYPE', 'ywsbs_subscription' );
! defined( 'YITH_YWSBS_SECRET_KEY' ) && define( 'YITH_YWSBS_SECRET_KEY', 'TfE4SfRN6mtm8qpsumyL' );
! defined( 'YITH_YWSBS_TEST_ON' ) && define( 'YITH_YWSBS_TEST_ON', ( defined( 'WP_DEBUG' ) && WP_DEBUG ) );

// Load plugin fw ________________________________________.
if ( file_exists( YITH_YWSBS_DIR . 'plugin-fw/init.php' ) ) {
	require_once YITH_YWSBS_DIR . 'plugin-fw/init.php';
}

// Require plugin autoload.
if ( ! class_exists( 'YITH_WC_Subscription_Autoloader' ) ) {
	require_once YITH_YWSBS_INC . 'class-yith-wc-subscription-autoloader.php';
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce', false ) ) {
			// Print a notice if WooCommerce is not installed.
			add_action(
				'admin_notices',
				function () {
					// Make sure text domain is loaded.
					YITH_WC_Subscription_Install::load_textdomain();
					?>
					<div class="error">
						<p><?php esc_html_e( 'YITH WooCommerce Subscription is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-subscription' ); ?></p>
					</div>
					<?php
				}
			);
		} else {
			require_once YITH_YWSBS_INC . 'class-yith-wc-subscription.php';
			YITH_WC_Subscription();
		}
	},
	5
);

register_activation_hook( __FILE__, '\YITH_WC_Subscription_Install::activate' );
register_deactivation_hook( __FILE__, '\YITH_WC_Subscription_Install::deactivate' );

if ( ! function_exists( 'yith_plugin_onboarding_registration_hook' ) ) {
	include_once 'plugin-upgrade/functions-yith-licence.php';
}
register_activation_hook( __FILE__, 'yith_plugin_onboarding_registration_hook' );
