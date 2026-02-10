<?php
/**
 * Tests for HF_Activator class.
 *
 * @package HostForge\Tests\Integration
 */

namespace HostForge\Tests\Integration;

use HostForge\HF_Activator;
use WP_UnitTestCase;

/**
 * Class ActivatorTest
 */
class ActivatorTest extends WP_UnitTestCase {

	/**
	 * Test that shared database tables are created.
	 */
	public function test_shared_tables_created(): void {
		global $wpdb;

		HF_Activator::activate();

		$tables = $wpdb->get_col( 'SHOW TABLES' );

		$this->assertContains( "{$wpdb->prefix}hf_logs", $tables, 'hf_logs table should exist.' );
		$this->assertContains( "{$wpdb->prefix}hf_activity_log", $tables, 'hf_activity_log table should exist.' );
	}

	/**
	 * Test hf_logs table has correct columns.
	 */
	public function test_logs_table_columns(): void {
		global $wpdb;

		HF_Activator::activate();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$columns = $wpdb->get_results( "DESCRIBE {$wpdb->prefix}hf_logs" );
		$names   = wp_list_pluck( $columns, 'Field' );

		$expected = array( 'id', 'module', 'level', 'message', 'context', 'created_at' );

		foreach ( $expected as $col ) {
			$this->assertContains( $col, $names, "Column '{$col}' should exist in hf_logs." );
		}
	}

	/**
	 * Test activity_log table has correct columns.
	 */
	public function test_activity_log_table_columns(): void {
		global $wpdb;

		HF_Activator::activate();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$columns = $wpdb->get_results( "DESCRIBE {$wpdb->prefix}hf_activity_log" );
		$names   = wp_list_pluck( $columns, 'Field' );

		$expected = array( 'id', 'user_id', 'action', 'object_type', 'object_id', 'details', 'ip_address', 'created_at' );

		foreach ( $expected as $col ) {
			$this->assertContains( $col, $names, "Column '{$col}' should exist in hf_activity_log." );
		}
	}

	/**
	 * Test admin capabilities are created.
	 */
	public function test_admin_capabilities_created(): void {
		HF_Activator::activate();

		$admin_role = get_role( 'administrator' );
		$this->assertNotNull( $admin_role );

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
			$this->assertTrue( $admin_role->has_cap( $cap ), "Admin should have '{$cap}' capability." );
		}
	}

	/**
	 * Test that editor role does NOT have HostForge capabilities.
	 */
	public function test_editor_lacks_capabilities(): void {
		HF_Activator::activate();

		$editor_role = get_role( 'editor' );
		$this->assertNotNull( $editor_role );

		$this->assertFalse( $editor_role->has_cap( 'manage_hostforge' ) );
		$this->assertFalse( $editor_role->has_cap( 'manage_hostforge_servers' ) );
	}

	/**
	 * Test version option is set.
	 */
	public function test_version_option_set(): void {
		HF_Activator::activate();

		$this->assertEquals( HOSTFORGE_VERSION, get_option( 'hf_version' ) );
	}

	/**
	 * Test DB version option is set.
	 */
	public function test_db_version_option_set(): void {
		HF_Activator::activate();

		$this->assertEquals( HOSTFORGE_VERSION, get_option( 'hf_db_version' ) );
	}

	/**
	 * Test activation is idempotent.
	 */
	public function test_activation_idempotent(): void {
		HF_Activator::activate();
		HF_Activator::activate(); // Second call should not error.

		$this->assertEquals( HOSTFORGE_VERSION, get_option( 'hf_version' ) );
	}
}
