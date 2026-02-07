<?php
/**
 * Plugin Name:       HostForge for WooCommerce
 * Plugin URI:        https://joseconti.com/hostforge
 * Description:       Modular hosting management platform for WooCommerce. Sell shared hosting, reseller, VPS, dedicated servers, domains, SSL and software licenses with automatic provisioning via cPanel and Plesk.
 * Version:           1.0.0
 * Author:            Jose Conti
 * Author URI:        https://joseconti.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hostforge
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * WC requires at least: 8.0
 * WC tested up to:   9.6
 *
 * @package HostForge
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'HOSTFORGE_VERSION', '1.0.0' );
define( 'HOSTFORGE_PLUGIN_FILE', __FILE__ );
define( 'HOSTFORGE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HOSTFORGE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'HOSTFORGE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'HOSTFORGE_MIN_PHP', '8.0' );
define( 'HOSTFORGE_MIN_WP', '6.0' );
define( 'HOSTFORGE_MIN_WC', '8.0' );

// Declare HPOS compatibility.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Load autoloader.
require_once HOSTFORGE_PLUGIN_DIR . 'includes/class-hf-autoloader.php';

// Register activation, deactivation and uninstall hooks.
register_activation_hook( __FILE__, array( 'HostForge\\HF_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HostForge\\HF_Deactivator', 'deactivate' ) );

/**
 * Initialize the plugin on plugins_loaded.
 *
 * Checks PHP version and WooCommerce availability before loading.
 *
 * @return void
 */
function hostforge_init(): void {
	// Check PHP version.
	if ( version_compare( PHP_VERSION, HOSTFORGE_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', 'hostforge_php_notice' );
		return;
	}

	// Check WordPress version.
	if ( version_compare( get_bloginfo( 'version' ), HOSTFORGE_MIN_WP, '<' ) ) {
		add_action( 'admin_notices', 'hostforge_wp_notice' );
		return;
	}

	// Check WooCommerce.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'hostforge_wc_notice' );
		return;
	}

	// Check WooCommerce version.
	if ( version_compare( WC_VERSION, HOSTFORGE_MIN_WC, '<' ) ) {
		add_action( 'admin_notices', 'hostforge_wc_version_notice' );
		return;
	}

	// Boot the plugin.
	HostForge\HostForge::instance()->init();
}
add_action( 'plugins_loaded', 'hostforge_init', 10 );

/**
 * Admin notice: PHP version too low.
 *
 * @return void
 */
function hostforge_php_notice(): void {
	$message = sprintf(
		/* translators: 1: required PHP version, 2: current PHP version */
		esc_html__( 'HostForge for WooCommerce requires PHP %1$s or higher. You are running PHP %2$s.', 'hostforge' ),
		HOSTFORGE_MIN_PHP,
		PHP_VERSION
	);
	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

/**
 * Admin notice: WordPress version too low.
 *
 * @return void
 */
function hostforge_wp_notice(): void {
	$message = sprintf(
		/* translators: %s: required WordPress version */
		esc_html__( 'HostForge for WooCommerce requires WordPress %s or higher.', 'hostforge' ),
		HOSTFORGE_MIN_WP
	);
	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

/**
 * Admin notice: WooCommerce not active.
 *
 * @return void
 */
function hostforge_wc_notice(): void {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'HostForge for WooCommerce requires WooCommerce to be installed and active.', 'hostforge' )
	);
}

/**
 * Admin notice: WooCommerce version too low.
 *
 * @return void
 */
function hostforge_wc_version_notice(): void {
	$message = sprintf(
		/* translators: %s: required WooCommerce version */
		esc_html__( 'HostForge for WooCommerce requires WooCommerce %s or higher.', 'hostforge' ),
		HOSTFORGE_MIN_WC
	);
	printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
}

/**
 * Plugin action links.
 *
 * @param array $links Existing links.
 * @return array Modified links.
 */
function hostforge_plugin_action_links( array $links ): array {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=hostforge-settings' ) ),
		esc_html__( 'Settings', 'hostforge' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . HOSTFORGE_PLUGIN_BASENAME, 'hostforge_plugin_action_links' );
