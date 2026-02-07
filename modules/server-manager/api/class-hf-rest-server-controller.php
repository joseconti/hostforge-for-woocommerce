<?php
/**
 * REST Server Controller.
 *
 * REST API endpoints for managing servers.
 * Namespace: hostforge/v1/servers
 *
 * @package HostForge\Modules\ServerManager\Api
 */

namespace HostForge\Modules\ServerManager\Api;

use HostForge\Abstracts\HF_REST_Controller;
use HostForge\HF_Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_REST_Server_Controller
 */
class HF_REST_Server_Controller extends HF_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'servers';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /servers - List all servers.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'per_page' => array(
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'page'     => array(
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'status'   => array(
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'group'    => array(
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// GET /servers/{id} - Get a single server.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// POST /servers/{id}/test - Test connection.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/test',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'test_connection' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// GET /servers/{id}/packages - Get packages.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/packages',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_packages' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// GET /servers/{id}/stats - Get server stats.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/stats',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_stats' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Get all servers.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ): \WP_REST_Response {
		if ( ! $this->check_rate_limit( 'servers_list' ) ) {
			return rest_ensure_response(
				$this->error( 'rate_limited', __( 'Too many requests.', 'hostforge' ), 429 )
			);
		}

		$args = array(
			'post_type'      => 'hf_server',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ),
			'paged'          => $request->get_param( 'page' ),
		);

		$status = $request->get_param( 'status' );
		if ( ! empty( $status ) ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_hf_status',
					'value' => $status,
				),
			);
		}

		$group = $request->get_param( 'group' );
		if ( ! empty( $group ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'hf_server_group',
					'field'    => is_numeric( $group ) ? 'term_id' : 'slug',
					'terms'    => $group,
				),
			);
		}

		$query   = new \WP_Query( $args );
		$servers = array();

		foreach ( $query->posts as $post ) {
			$servers[] = $this->prepare_server_data( $post );
		}

		return $this->success(
			array(
				'servers' => $servers,
				'total'   => $query->found_posts,
				'pages'   => $query->max_num_pages,
			)
		);
	}

	/**
	 * Get a single server.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$server = get_post( $request->get_param( 'id' ) );

		if ( ! $server || 'hf_server' !== $server->post_type ) {
			return $this->error( 'not_found', __( 'Server not found.', 'hostforge' ), 404 );
		}

		return $this->success( $this->prepare_server_data( $server ) );
	}

	/**
	 * Test server connection.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function test_connection( $request ) {
		$server_id = $request->get_param( 'id' );
		$server    = get_post( $server_id );

		if ( ! $server || 'hf_server' !== $server->post_type ) {
			return $this->error( 'not_found', __( 'Server not found.', 'hostforge' ), 404 );
		}

		$module   = \HostForge\HostForge::instance()->module_manager()->get_module( 'server-manager' );
		$provider = $module ? $module->get_provider( $server_id ) : null;

		if ( ! $provider ) {
			return $this->error( 'invalid_panel', __( 'Invalid panel type.', 'hostforge' ), 400 );
		}

		$result = $provider->test_connection();

		return $this->success( $result );
	}

	/**
	 * Get server packages.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_packages( $request ) {
		$server_id = $request->get_param( 'id' );
		$server    = get_post( $server_id );

		if ( ! $server || 'hf_server' !== $server->post_type ) {
			return $this->error( 'not_found', __( 'Server not found.', 'hostforge' ), 404 );
		}

		$module   = \HostForge\HostForge::instance()->module_manager()->get_module( 'server-manager' );
		$provider = $module ? $module->get_provider( $server_id ) : null;

		if ( ! $provider ) {
			return $this->error( 'invalid_panel', __( 'Invalid panel type.', 'hostforge' ), 400 );
		}

		$result = $provider->get_packages();

		return $this->success( $result );
	}

	/**
	 * Get server stats.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_stats( $request ) {
		$server_id = $request->get_param( 'id' );
		$server    = get_post( $server_id );

		if ( ! $server || 'hf_server' !== $server->post_type ) {
			return $this->error( 'not_found', __( 'Server not found.', 'hostforge' ), 404 );
		}

		$module   = \HostForge\HostForge::instance()->module_manager()->get_module( 'server-manager' );
		$provider = $module ? $module->get_provider( $server_id ) : null;

		if ( ! $provider ) {
			return $this->error( 'invalid_panel', __( 'Invalid panel type.', 'hostforge' ), 400 );
		}

		$result = $provider->get_server_stats();

		return $this->success( $result );
	}

	/**
	 * Prepare server data for REST response.
	 * Excludes sensitive credentials.
	 *
	 * @param \WP_Post $post Server post.
	 * @return array
	 */
	private function prepare_server_data( \WP_Post $post ): array {
		$groups = wp_get_object_terms( $post->ID, 'hf_server_group', array( 'fields' => 'names' ) );

		return array(
			'id'               => $post->ID,
			'name'             => $post->post_title,
			'panel_type'       => get_post_meta( $post->ID, '_hf_panel_type', true ),
			'hostname'         => get_post_meta( $post->ID, '_hf_hostname', true ),
			'port'             => (int) get_post_meta( $post->ID, '_hf_port', true ),
			'auth_method'      => get_post_meta( $post->ID, '_hf_auth_method', true ),
			'max_accounts'     => (int) get_post_meta( $post->ID, '_hf_max_accounts', true ),
			'current_accounts' => (int) get_post_meta( $post->ID, '_hf_current_accounts', true ),
			'status'           => get_post_meta( $post->ID, '_hf_status', true ) ?: 'unknown',
			'last_check'       => get_post_meta( $post->ID, '_hf_last_check', true ),
			'server_groups'    => is_wp_error( $groups ) ? array() : $groups,
		);
	}
}
