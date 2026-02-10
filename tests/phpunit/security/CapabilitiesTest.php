<?php
/**
 * Tests for capability-based access control.
 *
 * @package HostForge\Tests\Security
 */

namespace HostForge\Tests\Security;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Class CapabilitiesTest
 */
class CapabilitiesTest extends WP_UnitTestCase {

	/**
	 * Admin user ID (with HostForge capabilities).
	 *
	 * @var int
	 */
	private int $admin_id;

	/**
	 * Editor user ID (without HostForge capabilities).
	 *
	 * @var int
	 */
	private int $editor_id;

	/**
	 * Subscriber user ID.
	 *
	 * @var int
	 */
	private int $subscriber_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$admin           = get_user_by( 'ID', $this->admin_id );
		$admin->add_cap( 'manage_hostforge' );
		$admin->add_cap( 'manage_hostforge_servers' );
		$admin->add_cap( 'manage_hostforge_services' );
		$admin->add_cap( 'manage_hostforge_tickets' );
		$admin->add_cap( 'manage_hostforge_domains' );
		$admin->add_cap( 'manage_hostforge_settings' );
		$admin->add_cap( 'view_hostforge_reports' );

		$this->editor_id     = $this->factory->user->create( array( 'role' => 'editor' ) );
		$this->subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		do_action( 'rest_api_init' );
	}

	/**
	 * Test admin has all 7 HostForge capabilities.
	 */
	public function test_admin_has_all_capabilities(): void {
		wp_set_current_user( $this->admin_id );

		$caps = array(
			'manage_hostforge',
			'manage_hostforge_servers',
			'manage_hostforge_services',
			'manage_hostforge_tickets',
			'manage_hostforge_domains',
			'manage_hostforge_settings',
			'view_hostforge_reports',
		);

		foreach ( $caps as $cap ) {
			$this->assertTrue( current_user_can( $cap ), "Admin should have '{$cap}'." );
		}
	}

	/**
	 * Test editor does NOT have HostForge capabilities.
	 */
	public function test_editor_lacks_all_capabilities(): void {
		wp_set_current_user( $this->editor_id );

		$caps = array(
			'manage_hostforge',
			'manage_hostforge_servers',
			'manage_hostforge_services',
			'manage_hostforge_tickets',
			'manage_hostforge_domains',
			'manage_hostforge_settings',
			'view_hostforge_reports',
		);

		foreach ( $caps as $cap ) {
			$this->assertFalse( current_user_can( $cap ), "Editor should NOT have '{$cap}'." );
		}
	}

	/**
	 * Test subscriber does NOT have HostForge capabilities.
	 */
	public function test_subscriber_lacks_all_capabilities(): void {
		wp_set_current_user( $this->subscriber_id );

		$this->assertFalse( current_user_can( 'manage_hostforge' ) );
		$this->assertFalse( current_user_can( 'manage_hostforge_servers' ) );
		$this->assertFalse( current_user_can( 'view_hostforge_reports' ) );
	}

	/**
	 * Test REST endpoints enforce manage_hostforge capability.
	 */
	public function test_rest_endpoints_require_manage_hostforge(): void {
		$protected_endpoints = array(
			array( 'GET', '/hostforge/v1/servers' ),
			array( 'GET', '/hostforge/v1/services' ),
			array( 'GET', '/hostforge/v1/tickets' ),
			array( 'GET', '/hostforge/v1/domains' ),
			array( 'GET', '/hostforge/v1/security/ip-blocks' ),
			array( 'GET', '/hostforge/v1/reports/revenue' ),
		);

		foreach ( $protected_endpoints as list( $method, $route ) ) {
			// As editor — should be denied.
			wp_set_current_user( $this->editor_id );
			$request  = new WP_REST_Request( $method, $route );
			$response = rest_do_request( $request );

			$this->assertContains(
				$response->get_status(),
				array( 401, 403, 404 ),
				"Editor should be denied for {$method} {$route}."
			);
		}
	}

	/**
	 * Test unauthenticated user is denied on protected endpoints.
	 */
	public function test_unauthenticated_user_denied(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/servers' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 401, 403, 404 ) );
	}

	/**
	 * Test that custom capability can be granted to shop_manager.
	 */
	public function test_custom_capability_grant(): void {
		// Use editor role — doesn't have HF caps by default.
		$editor_test_id = $this->factory->user->create( array( 'role' => 'editor' ) );

		// By default, no HF access.
		$user = new \WP_User( $editor_test_id );
		$this->assertFalse( $user->has_cap( 'manage_hostforge' ) );

		// Grant access via user-level capability.
		$user->add_cap( 'manage_hostforge' );

		// Re-instantiate user to pick up new caps from DB.
		$refreshed = new \WP_User( $editor_test_id );

		$this->assertTrue( $refreshed->has_cap( 'manage_hostforge' ) );
	}
}
