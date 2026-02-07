<?php
/**
 * Plugin activator.
 *
 * Creates shared DB tables, assigns capabilities and sets version option.
 *
 * @package HostForge
 */

namespace HostForge;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Activator
 */
class HF_Activator {

	/**
	 * Run activation logic.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::create_capabilities();
		self::set_version();
		flush_rewrite_rules();
	}

	/**
	 * Create shared database tables.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		// Logs table.
		$sql[] = "CREATE TABLE {$wpdb->prefix}hf_logs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			module VARCHAR(50) NOT NULL,
			level VARCHAR(20) NOT NULL,
			message TEXT NOT NULL,
			context LONGTEXT,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY module (module),
			KEY level (level),
			KEY created_at (created_at)
		) {$charset_collate};";

		// Activity log table.
		$sql[] = "CREATE TABLE {$wpdb->prefix}hf_activity_log (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(100) NOT NULL,
			object_type VARCHAR(50),
			object_id BIGINT UNSIGNED,
			details TEXT,
			ip_address VARCHAR(45),
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'hf_db_version', HOSTFORGE_VERSION );
	}

	/**
	 * Create custom capabilities and assign to administrator.
	 *
	 * @return void
	 */
	private static function create_capabilities(): void {
		$admin_role = get_role( 'administrator' );

		if ( ! $admin_role ) {
			return;
		}

		$capabilities = array(
			'manage_hostforge',
			'manage_hostforge_servers',
			'manage_hostforge_services',
			'manage_hostforge_tickets',
			'manage_hostforge_domains',
			'manage_hostforge_settings',
			'view_hostforge_reports',
		);

		foreach ( $capabilities as $cap ) {
			$admin_role->add_cap( $cap );
		}
	}

	/**
	 * Store plugin version.
	 *
	 * @return void
	 */
	private static function set_version(): void {
		update_option( 'hf_version', HOSTFORGE_VERSION );
	}
}
