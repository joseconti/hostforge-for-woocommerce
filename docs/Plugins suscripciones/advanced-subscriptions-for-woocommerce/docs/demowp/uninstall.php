<?php
/**
 * DemoWP Uninstall
 *
 * Cleanup all plugin data when uninstalling.
 *
 * @package DemoWP
 * @since   1.0.0
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load required classes.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-demowp-utils.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-demowp-demo-tracker.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-demowp-database.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-demowp-filesystem.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-demowp-cloner.php';

// Delete all active demos.
$tracker = new DemoWP_Demo_Tracker();
$demos   = $tracker->get_active_demos();

foreach ( $demos as $demo ) {
	$cloner = new DemoWP_Cloner();
	$cloner->delete_demo( $demo['clone_id'] );
}

// Drop tracking table.
DemoWP_Demo_Tracker::drop_table();

// Delete options.
delete_option( 'demowp_demo_lifetime' );
delete_option( 'demowp_endpoint_slug' );
delete_option( 'demowp_max_concurrent_demos' );
delete_option( 'demowp_welcome_message' );
delete_option( 'demowp_maintenance_mode' );
delete_option( 'demowp_maintenance_message' );

// Delete license options.
delete_option( 'demowp_lic_license_key' );
delete_option( 'demowp_lic_license_status' );
delete_option( 'demowp_lic_license_salt' );

// Clean up transients.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_demowp_%' OR option_name LIKE '_transient_timeout_demowp_%'"
);

// Unschedule all Action Scheduler tasks.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'demowp_cleanup_demo' );
	as_unschedule_all_actions( 'demowp_emergency_cleanup' );
	as_unschedule_all_actions( 'demowp_cleanup_old_records' );
}

// Flush rewrite rules.
flush_rewrite_rules();
