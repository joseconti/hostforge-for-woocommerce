<?php
/**
 * Plugin Name: DemoWP
 * Plugin URI: https://plugins.joseconti.com/demowp
 * Description: Create temporary sandbox demos for users to test WordPress plugins and themes safely.
 * Version: 1.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Jose Conti
 * Author URI: https://joseconti.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: demowp
 * Domain Path: /languages
 *
 * @package DemoWP
 * @since   1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'DEMOWP_VERSION', '1.2.0' );
define( 'DEMOWP_PLUGIN_FILE', __FILE__ );
define( 'DEMOWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DEMOWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DEMOWP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// License constants (unique to avoid conflicts with other plugins)
define( 'DEMOWP_LICENSE_ITEM_NAME', 'demowp' );
define( 'DEMOWP_LICENSE_API', 'https://plugins.joseconti.com' );
define( 'DEMOWP_LICENSE_PREFIX', 'demowp_lic' );

// Detect if we are running in a clone (check for marker file)
if ( ! defined( 'DEMOWP_IS_CLONE' ) ) {
	define( 'DEMOWP_IS_CLONE', file_exists( ABSPATH . '.demowp-clone' ) );
}

// Autoload plugin classes
spl_autoload_register(
	function ( $class ) {
		// Check if the class belongs to our plugin
		if ( strpos( $class, 'DemoWP_' ) !== 0 ) {
				return;
		}

		// Convert class name to file name
		$class_file = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

		// Check in includes directory
		$includes_path = DEMOWP_PLUGIN_DIR . 'includes/' . $class_file;
		if ( file_exists( $includes_path ) ) {
			require_once $includes_path;
			return;
		}

		// Check in admin directory
		$admin_path = DEMOWP_PLUGIN_DIR . 'admin/' . $class_file;
		if ( file_exists( $admin_path ) ) {
			require_once $admin_path;
			return;
		}

		// Check in public directory
		$public_path = DEMOWP_PLUGIN_DIR . 'public/' . $class_file;
		if ( file_exists( $public_path ) ) {
				require_once $public_path;
				return;
		}
	}
);

/**
 * Load Action Scheduler early but after plugins_loaded starts.
 * Must be loaded at priority 1 before other plugins that depend on it.
 */
function demowp_load_action_scheduler() {
	if ( file_exists( DEMOWP_PLUGIN_DIR . 'action-scheduler/action-scheduler.php' ) ) {
		require_once DEMOWP_PLUGIN_DIR . 'action-scheduler/action-scheduler.php';
	}
}
add_action( 'plugins_loaded', 'demowp_load_action_scheduler', 1 );

/**
 * Load plugin text domain for translations.
 *
 * WordPress 6.7+ requires translations to be loaded at 'init' action or later.
 */
function demowp_load_textdomain() {
	load_plugin_textdomain( 'demowp', false, dirname( DEMOWP_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'init', 'demowp_load_textdomain', 1 );

/**
 * Initialize the plugin
 *
 * Runs on 'init' hook to ensure translations are loaded first.
 * Priority 5 ensures we run after textdomain is loaded (priority 1)
 * but before most other init hooks.
 */
function demowp_init() {
	// Initialize the loader.
	if ( class_exists( 'DemoWP_Loader' ) ) {
		$loader = new DemoWP_Loader();
		$loader->run();
	}
}
add_action( 'init', 'demowp_init', 5 );

/**
 * Plugin activation hook
 */
function demowp_activate() {
	// Create tracking table.
	require_once DEMOWP_PLUGIN_DIR . 'includes/class-demowp-demo-tracker.php';
	DemoWP_Demo_Tracker::create_table();

	// Set default options.
	if ( false === get_option( 'demowp_demo_lifetime' ) ) {
		add_option( 'demowp_demo_lifetime', 3600 ); // 1 hour default.
	}
	if ( false === get_option( 'demowp_endpoint_slug' ) ) {
		add_option( 'demowp_endpoint_slug', 'demo' );
	}
	if ( false === get_option( 'demowp_max_concurrent_demos' ) ) {
		add_option( 'demowp_max_concurrent_demos', 3 );
	}
	if ( false === get_option( 'demowp_email_mode' ) ) {
		add_option( 'demowp_email_mode', 'admin' );
	}

	// Register endpoint before flushing rewrite rules.
	$slug = get_option( 'demowp_endpoint_slug', 'demo' );
	add_rewrite_rule(
		'^' . preg_quote( $slug, '/' ) . '/?$',
		'index.php?demowp_action=create',
		'top'
	);
	add_rewrite_tag( '%demowp_action%', '([^&]+)' );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'demowp_activate' );

/**
 * Plugin deactivation hook
 */
function demowp_deactivate() {
	// Cancel all scheduled actions
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'demowp_cleanup_demo' );
		as_unschedule_all_actions( 'demowp_emergency_cleanup' );
	}

	// Flush rewrite rules
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'demowp_deactivate' );

/**
 * Plugin uninstall - cleanup all data
 * This is handled by uninstall.php
 */
