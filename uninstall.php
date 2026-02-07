<?php
/**
 * HostForge uninstaller.
 *
 * Deletes all plugin data only if hf_delete_data_on_uninstall option is true.
 *
 * @package HostForge
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Only delete data if the user opted in.
if ( 'yes' !== get_option( 'hf_delete_data_on_uninstall', 'no' ) ) {
	return;
}

global $wpdb;

// Drop custom tables.
$tables = array(
	$wpdb->prefix . 'hf_logs',
	$wpdb->prefix . 'hf_activity_log',
	$wpdb->prefix . 'hf_provisioning_queue',
	$wpdb->prefix . 'hf_dns_records',
	$wpdb->prefix . 'hf_login_attempts',
	$wpdb->prefix . 'hf_ip_blocks',
);

foreach ( $tables as $table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Delete all plugin options.
$options = array(
	'hf_version',
	'hf_db_version',
	'hf_active_modules',
	'hf_delete_data_on_uninstall',
	'hf_debug_mode',
	'hf_log_retention_days',
	'hf_company_name',
	'hf_company_email',
	'hf_company_phone',
	'hf_company_address',
	'hf_license_key',
	'hf_suspend_days',
	'hf_terminate_days',
	'hf_auto_suspend',
	'hf_auto_terminate',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete all HostForge post meta.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_hf_%'" );

// Delete HostForge CPTs and their data.
$post_types = array(
	'hf_server',
	'hf_service',
	'hf_ticket',
	'hf_kb_article',
	'hf_canned_response',
	'hf_domain',
);

foreach ( $post_types as $hf_post_type ) {
	$hf_posts = get_posts(
		array(
			'post_type'      => $hf_post_type,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);

	foreach ( $hf_posts as $hf_post_id ) {
		wp_delete_post( $hf_post_id, true );
	}
}

// Delete custom taxonomies terms.
$taxonomies = array( 'hf_department', 'hf_kb_category', 'hf_server_group' );

foreach ( $taxonomies as $hf_taxonomy ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $hf_taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( is_array( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, $hf_taxonomy );
		}
	}
}

// Remove capabilities.
$roles = array( 'administrator' );

$capabilities = array(
	'manage_hostforge',
	'manage_hostforge_servers',
	'manage_hostforge_services',
	'manage_hostforge_tickets',
	'manage_hostforge_domains',
	'manage_hostforge_settings',
	'view_hostforge_reports',
);

foreach ( $roles as $role_name ) {
	$hf_role = get_role( $role_name );
	if ( $hf_role ) {
		foreach ( $capabilities as $cap ) {
			$hf_role->remove_cap( $cap );
		}
	}
}

// Delete transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hf_%'" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hf_%'" );

// Unschedule all Action Scheduler tasks.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	$groups = array(
		'hostforge',
		'hostforge-provisioning',
		'hostforge-server-monitor',
		'hostforge-tickets',
		'hostforge-domains',
		'hostforge-security',
		'hostforge-reports',
		'hostforge-logs',
	);

	foreach ( $groups as $group ) {
		as_unschedule_all_actions( '', array(), $group );
	}
}
