<?php
/**
 * Tests for REST Status endpoint.
 *
 * @package HostForge\Tests\Integration
 */

namespace HostForge\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Class RestStatusTest
 */
class RestStatusTest extends WP_UnitTestCase {

	/**
	 * Admin user ID.
	 *
	 * @var int
	 */
	private int $admin_id;

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
		// Grant HostForge caps.
		$admin = get_user_by( 'ID', $this->admin_id );
		$admin->add_cap( 'manage_hostforge' );

		$this->subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Suppress the REST route registration notice in test environment.
		$this->setExpectedIncorrectUsage( 'register_rest_route' );

		// Register REST routes.
		$controller = new \HostForge\Admin\HF_REST_Status_Controller();
		$controller->register_routes();
	}

	/**
	 * Test status endpoint returns data for admin.
	 */
	public function test_status_endpoint_as_admin(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/status' );
		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );

		$status_data = $data['data'];
		$this->assertArrayHasKey( 'version', $status_data );
		$this->assertArrayHasKey( 'php_version', $status_data );
		$this->assertArrayHasKey( 'wp_version', $status_data );
	}

	/**
	 * Test status endpoint returns correct version.
	 */
	public function test_status_returns_correct_version(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/status' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertEquals( HOSTFORGE_VERSION, $data['data']['version'] );
		$this->assertEquals( PHP_VERSION, $data['data']['php_version'] );
	}

	/**
	 * Test status endpoint denied for subscriber.
	 */
	public function test_status_denied_for_subscriber(): void {
		wp_set_current_user( $this->subscriber_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/status' );
		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test status endpoint denied for unauthenticated user.
	 */
	public function test_status_denied_for_unauthenticated(): void {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/status' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * Test active_modules is an array.
	 */
	public function test_active_modules_is_array(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/status' );
		$response = rest_do_request( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data['data']['active_modules'] );
	}
}
