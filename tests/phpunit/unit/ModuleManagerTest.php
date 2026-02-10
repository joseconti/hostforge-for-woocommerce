<?php
/**
 * Tests for HF_Module_Manager class.
 *
 * @package HostForge\Tests\Unit
 */

namespace HostForge\Tests\Unit;

use HostForge\HF_Module_Manager;
use WP_UnitTestCase;

/**
 * Class ModuleManagerTest
 */
class ModuleManagerTest extends WP_UnitTestCase {

	/**
	 * Module manager instance.
	 *
	 * @var HF_Module_Manager
	 */
	private HF_Module_Manager $manager;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->manager = new HF_Module_Manager();
		delete_option( 'hf_active_modules' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		delete_option( 'hf_active_modules' );
		parent::tearDown();
	}

	/**
	 * Test registering core modules.
	 */
	public function test_register_core_modules(): void {
		$this->manager->register_core_modules();
		$info = $this->manager->get_all_modules_info();

		$expected_modules = array(
			'server-manager',
			'auto-provisioning',
			'support-desk',
			'domain-manager',
			'security',
			'notifications',
			'reports',
		);

		foreach ( $expected_modules as $module_id ) {
			$this->assertArrayHasKey( $module_id, $info, "Module '{$module_id}' should be registered." );
		}
	}

	/**
	 * Test that no modules are active by default.
	 */
	public function test_no_modules_active_by_default(): void {
		$active = $this->manager->get_active_module_ids();
		$this->assertIsArray( $active );
		$this->assertEmpty( $active );
	}

	/**
	 * Test is_module_active returns false for inactive module.
	 */
	public function test_is_module_active_returns_false_for_inactive(): void {
		$this->assertFalse( $this->manager->is_module_active( 'server-manager' ) );
	}

	/**
	 * Test activating a module.
	 */
	public function test_activate_module(): void {
		$this->manager->register_core_modules();
		$result = $this->manager->activate_module( 'server-manager' );

		$this->assertTrue( $result );
		$this->assertTrue( $this->manager->is_module_active( 'server-manager' ) );
	}

	/**
	 * Test activating an unregistered module fails.
	 */
	public function test_activate_unregistered_module_fails(): void {
		$result = $this->manager->activate_module( 'nonexistent-module' );
		$this->assertFalse( $result );
	}

	/**
	 * Test deactivating a module.
	 */
	public function test_deactivate_module(): void {
		$this->manager->register_core_modules();
		$this->manager->activate_module( 'server-manager' );

		$result = $this->manager->deactivate_module( 'server-manager' );

		$this->assertTrue( $result );
		$this->assertFalse( $this->manager->is_module_active( 'server-manager' ) );
	}

	/**
	 * Test deactivating an inactive module returns false.
	 */
	public function test_deactivate_inactive_module_returns_false(): void {
		$this->manager->register_core_modules();
		$result = $this->manager->deactivate_module( 'server-manager' );
		$this->assertFalse( $result );
	}

	/**
	 * Test that auto-provisioning requires server-manager dependency.
	 */
	public function test_activate_module_with_unmet_dependency(): void {
		$this->manager->register_core_modules();

		// auto-provisioning depends on server-manager.
		$result = $this->manager->activate_module( 'auto-provisioning' );
		$this->assertFalse( $result, 'Should fail when dependency is not active.' );
	}

	/**
	 * Test activating module with met dependency.
	 */
	public function test_activate_module_with_met_dependency(): void {
		$this->manager->register_core_modules();

		// First activate dependency.
		$this->manager->activate_module( 'server-manager' );

		// Now auto-provisioning should succeed.
		$result = $this->manager->activate_module( 'auto-provisioning' );
		$this->assertTrue( $result );
	}

	/**
	 * Test that deactivating a module also deactivates dependents.
	 */
	public function test_deactivate_cascades_to_dependents(): void {
		$this->manager->register_core_modules();
		$this->manager->activate_module( 'server-manager' );
		$this->manager->activate_module( 'auto-provisioning' );

		// Deactivate dependency — should cascade.
		$this->manager->deactivate_module( 'server-manager' );

		$this->assertFalse( $this->manager->is_module_active( 'server-manager' ) );
		$this->assertFalse( $this->manager->is_module_active( 'auto-provisioning' ) );
	}

	/**
	 * Test get_module returns null for non-loaded module.
	 */
	public function test_get_module_returns_null_for_unloaded(): void {
		$this->assertNull( $this->manager->get_module( 'server-manager' ) );
	}

	/**
	 * Test loading active modules.
	 */
	public function test_load_active_modules(): void {
		$this->manager->register_core_modules();
		$this->manager->activate_module( 'support-desk' );

		// Create a fresh manager and load.
		$fresh = new HF_Module_Manager();
		$fresh->register_core_modules();
		$fresh->load_active_modules();

		$this->assertNotNull( $fresh->get_module( 'support-desk' ) );
	}

	/**
	 * Test the registered modules filter.
	 */
	public function test_registered_modules_filter(): void {
		add_filter(
			'hostforge_registered_modules',
			function ( $modules ) {
				unset( $modules['reports'] );
				return $modules;
			}
		);

		$this->manager->register_core_modules();
		$info = $this->manager->get_all_modules_info();

		$this->assertArrayNotHasKey( 'reports', $info );

		remove_all_filters( 'hostforge_registered_modules' );
	}

	/**
	 * Test the active module IDs filter.
	 */
	public function test_active_module_ids_filter(): void {
		add_filter(
			'hostforge_active_module_ids',
			function ( $active ) {
				$active[] = 'forced-module';
				return $active;
			}
		);

		$this->assertTrue( $this->manager->is_module_active( 'forced-module' ) );

		remove_all_filters( 'hostforge_active_module_ids' );
	}

	/**
	 * Test module activation fires hooks.
	 */
	public function test_activate_fires_action_hooks(): void {
		$this->manager->register_core_modules();

		$before_fired = false;
		$after_fired  = false;

		add_action(
			'hostforge_before_module_activate',
			function () use ( &$before_fired ) {
				$before_fired = true;
			}
		);

		add_action(
			'hostforge_module_activated',
			function () use ( &$after_fired ) {
				$after_fired = true;
			}
		);

		$this->manager->activate_module( 'server-manager' );

		$this->assertTrue( $before_fired, 'Before activate hook should fire.' );
		$this->assertTrue( $after_fired, 'After activate hook should fire.' );

		remove_all_actions( 'hostforge_before_module_activate' );
		remove_all_actions( 'hostforge_module_activated' );
	}

	/**
	 * Test get_all_modules_info returns correct structure.
	 */
	public function test_get_all_modules_info_structure(): void {
		$this->manager->register_core_modules();
		$info = $this->manager->get_all_modules_info();

		foreach ( $info as $id => $module_info ) {
			$this->assertArrayHasKey( 'id', $module_info );
			$this->assertArrayHasKey( 'name', $module_info );
			$this->assertArrayHasKey( 'description', $module_info );
			$this->assertArrayHasKey( 'dependencies', $module_info );
			$this->assertArrayHasKey( 'active', $module_info );
			$this->assertIsBool( $module_info['active'] );
			$this->assertIsArray( $module_info['dependencies'] );
		}
	}
}
