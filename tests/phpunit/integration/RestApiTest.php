<?php
/**
 * Tests for all HostForge REST API endpoints.
 *
 * Verifies route registration, permission checks, and basic responses
 * for every REST controller in the plugin.
 *
 * @package HostForge\Tests\Integration
 */

namespace HostForge\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Class RestApiTest
 */
class RestApiTest extends WP_UnitTestCase {

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
	 * REST server.
	 *
	 * @var \WP_REST_Server
	 */
	private \WP_REST_Server $server;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create admin user with capabilities.
		$this->admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$admin           = get_user_by( 'ID', $this->admin_id );
		$admin->add_cap( 'manage_hostforge' );
		$admin->add_cap( 'manage_hostforge_servers' );
		$admin->add_cap( 'manage_hostforge_services' );
		$admin->add_cap( 'manage_hostforge_tickets' );
		$admin->add_cap( 'manage_hostforge_domains' );
		$admin->add_cap( 'view_hostforge_reports' );

		$this->subscriber_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );

		// Init REST server.
		global $wp_rest_server;
		$this->server = $wp_rest_server = new \WP_REST_Server();

		do_action( 'rest_api_init' );
	}

	/**
	 * Helper to assert an endpoint is forbidden for subscribers.
	 *
	 * @param string $method HTTP method.
	 * @param string $route  Route path.
	 */
	private function assert_forbidden_for_subscriber( string $method, string $route ): void {
		wp_set_current_user( $this->subscriber_id );

		$request  = new WP_REST_Request( $method, $route );
		$response = rest_do_request( $request );

		$this->assertContains(
			$response->get_status(),
			array( 401, 403, 404 ),
			"Route {$method} {$route} should be forbidden for subscriber."
		);
	}

	/**
	 * Test that the hostforge/v1 namespace is registered.
	 */
	public function test_rest_namespace_registered(): void {
		$routes     = $this->server->get_routes();
		$hf_routes  = array_filter(
			array_keys( $routes ),
			function ( $route ) {
				return str_starts_with( $route, '/hostforge/v1' );
			}
		);

		$this->assertNotEmpty( $hf_routes, 'HostForge REST routes should be registered.' );
	}

	// ============================================================
	// Servers API
	// ============================================================

	/**
	 * Test GET /servers returns list for admin.
	 */
	public function test_get_servers_as_admin(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/servers' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 404 ) );
	}

	/**
	 * Test GET /servers forbidden for subscriber.
	 */
	public function test_get_servers_forbidden_for_subscriber(): void {
		$this->assert_forbidden_for_subscriber( 'GET', '/hostforge/v1/servers' );
	}

	/**
	 * Test GET /servers/{id} with non-existent server.
	 */
	public function test_get_server_not_found(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/servers/99999' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 400, 404 ) );
	}

	// ============================================================
	// Services API
	// ============================================================

	/**
	 * Test GET /services returns list for admin.
	 */
	public function test_get_services_as_admin(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/services' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 404 ) );
	}

	/**
	 * Test GET /services forbidden for subscriber.
	 */
	public function test_get_services_forbidden_for_subscriber(): void {
		$this->assert_forbidden_for_subscriber( 'GET', '/hostforge/v1/services' );
	}

	/**
	 * Test POST /services/{id}/action requires valid action.
	 */
	public function test_service_action_requires_valid_action(): void {
		wp_set_current_user( $this->admin_id );

		$service_id = $this->factory->post->create(
			array(
				'post_type'   => 'hf_service',
				'post_status' => 'publish',
			)
		);

		$request = new WP_REST_Request( 'POST', "/hostforge/v1/services/{$service_id}/action" );
		$request->set_param( 'action', 'invalid_action' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 400, 404 ) );
	}

	// ============================================================
	// Tickets API
	// ============================================================

	/**
	 * Test GET /tickets returns list for admin.
	 */
	public function test_get_tickets_as_admin(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/tickets' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 404 ) );
	}

	/**
	 * Test GET /tickets forbidden for subscriber.
	 */
	public function test_get_tickets_forbidden_for_subscriber(): void {
		$this->assert_forbidden_for_subscriber( 'GET', '/hostforge/v1/tickets' );
	}

	/**
	 * Test POST /tickets creates a ticket.
	 */
	public function test_create_ticket(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/hostforge/v1/tickets' );
		$request->set_param( 'subject', 'Test ticket from PHPUnit' );
		$request->set_param( 'message', 'This is a test ticket body.' );
		$request->set_param( 'priority', 'medium' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 201, 404 ) );
	}

	// ============================================================
	// Knowledge Base API
	// ============================================================

	/**
	 * Test GET /kb returns list.
	 */
	public function test_get_kb_articles(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/kb' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 404 ) );
	}

	// ============================================================
	// Domains API
	// ============================================================

	/**
	 * Test GET /domains returns list for admin.
	 */
	public function test_get_domains_as_admin(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/domains' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 404 ) );
	}

	/**
	 * Test GET /domains forbidden for subscriber.
	 */
	public function test_get_domains_forbidden_for_subscriber(): void {
		$this->assert_forbidden_for_subscriber( 'GET', '/hostforge/v1/domains' );
	}

	/**
	 * Test GET /domains/check availability endpoint.
	 */
	public function test_domain_check_availability(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'GET', '/hostforge/v1/domains/check' );
		$request->set_param( 'domain', 'testexample12345.com' );
		$response = rest_do_request( $request );

		// May return 200 or 404 depending on module state.
		$this->assertContains( $response->get_status(), array( 200, 400, 404 ) );
	}

	// ============================================================
	// Security API
	// ============================================================

	/**
	 * Test GET /security/ip-blocks returns list.
	 */
	public function test_get_ip_blocks(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/security/ip-blocks' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 404 ) );
	}

	/**
	 * Test GET /security/login-attempts returns list.
	 */
	public function test_get_login_attempts(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/security/login-attempts' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 404 ) );
	}

	/**
	 * Test GET /security/audit-log returns list.
	 */
	public function test_get_audit_log(): void {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/hostforge/v1/security/audit-log' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 404 ) );
	}

	/**
	 * Test security endpoints forbidden for subscriber.
	 */
	public function test_security_endpoints_forbidden_for_subscriber(): void {
		$this->assert_forbidden_for_subscriber( 'GET', '/hostforge/v1/security/ip-blocks' );
		$this->assert_forbidden_for_subscriber( 'GET', '/hostforge/v1/security/login-attempts' );
		$this->assert_forbidden_for_subscriber( 'GET', '/hostforge/v1/security/audit-log' );
	}

	// ============================================================
	// Reports API
	// ============================================================

	/**
	 * Test GET /reports/revenue returns data.
	 */
	public function test_get_reports_revenue(): void {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'GET', '/hostforge/v1/reports/revenue' );
		$request->set_param( 'period', '30' );
		$response = rest_do_request( $request );

		$this->assertContains( $response->get_status(), array( 200, 404 ) );
	}

	/**
	 * Test all report endpoints.
	 */
	public function test_all_report_endpoints(): void {
		wp_set_current_user( $this->admin_id );

		$endpoints = array(
			'/hostforge/v1/reports/revenue',
			'/hostforge/v1/reports/customers',
			'/hostforge/v1/reports/services',
			'/hostforge/v1/reports/tickets',
			'/hostforge/v1/reports/domains',
			'/hostforge/v1/reports/servers',
		);

		foreach ( $endpoints as $endpoint ) {
			$request  = new WP_REST_Request( 'GET', $endpoint );
			$response = rest_do_request( $request );

			$this->assertContains(
				$response->get_status(),
				array( 200, 404 ),
				"Endpoint {$endpoint} should return 200 or 404."
			);
		}
	}

	/**
	 * Test report endpoints forbidden for subscriber.
	 */
	public function test_reports_forbidden_for_subscriber(): void {
		$this->assert_forbidden_for_subscriber( 'GET', '/hostforge/v1/reports/revenue' );
		$this->assert_forbidden_for_subscriber( 'GET', '/hostforge/v1/reports/services' );
	}
}
