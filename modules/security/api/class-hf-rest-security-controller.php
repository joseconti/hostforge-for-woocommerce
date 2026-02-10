<?php
/**
 * Security REST API Controller.
 *
 * Provides REST endpoints for security data: login attempts,
 * IP blocks, and audit log entries.
 *
 * @package HostForge\Modules\Security\Api
 */

namespace HostForge\Modules\Security\Api;

use HostForge\Abstracts\HF_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_REST_Security_Controller
 */
class HF_REST_Security_Controller extends HF_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'security';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Login attempts.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/login-attempts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_login_attempts' ),
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
					),
				),
			)
		);

		// IP blocks.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/ip-blocks',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_ip_blocks' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_ip_block' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'ip_address' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'reason'     => array(
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/ip-blocks/(?P<ip>[^/]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_ip_block' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		// Audit log.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/audit-log',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_audit_log' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'per_page'    => array(
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'page'        => array(
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'object_type' => array(
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Get login attempts.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_login_attempts( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$table    = $wpdb->prefix . 'hf_login_attempts';
		$per_page = $request->get_param( 'per_page' );
		$page     = $request->get_param( 'page' );
		$status   = $request->get_param( 'status' );
		$offset   = ( $page - 1 ) * $per_page;

		$where  = '1=1';
		$values = array();

		if ( ! empty( $status ) ) {
			$where   .= ' AND status = %s';
			$values[] = $status;
		}

		if ( ! empty( $values ) ) {
			$query_values = array_merge( $values, array( $per_page, $offset ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $query_values ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
			$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}

		$response_data = array(
			'items' => ! empty( $items ) ? $items : array(),
			'total' => $total,
			'pages' => ceil( $total / $per_page ),
		);

		/**
		 * Filter the REST API security response data.
		 *
		 * @since 1.0.0
		 *
		 * @param array            $response_data Response data array.
		 * @param string           $endpoint      The security endpoint: 'login-attempts', 'ip-blocks', or 'audit-log'.
		 * @param \WP_REST_Request $request        The original request object.
		 */
		$response_data = apply_filters( 'hostforge_rest_security_response', $response_data, 'login-attempts', $request );

		return rest_ensure_response( $response_data );
	}

	/**
	 * Get IP blocks.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_ip_blocks( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$table = $wpdb->prefix . 'hf_ip_blocks';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
		$items = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );

		$response_data = array(
			'items' => ! empty( $items ) ? $items : array(),
		);

		/** This filter is documented in this file, get_login_attempts method. */
		$response_data = apply_filters( 'hostforge_rest_security_response', $response_data, 'ip-blocks', $request );

		return rest_ensure_response( $response_data );
	}

	/**
	 * Create an IP block.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_ip_block( \WP_REST_Request $request ) {
		$ip     = $request->get_param( 'ip_address' );
		$reason = $request->get_param( 'reason' );

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return new \WP_Error( 'invalid_ip', __( 'Invalid IP address.', 'hostforge' ), array( 'status' => 400 ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'hf_ip_blocks';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (ip_address, block_type, reason, created_at)
				VALUES (%s, 'manual', %s, %s)
				ON DUPLICATE KEY UPDATE reason = VALUES(reason), block_type = 'manual'",
				$ip,
				$reason,
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Delete an IP block.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function delete_ip_block( \WP_REST_Request $request ): \WP_REST_Response {
		$ip = sanitize_text_field( $request->get_param( 'ip' ) );

		global $wpdb;
		$table = $wpdb->prefix . 'hf_ip_blocks';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'ip_address' => $ip ), array( '%s' ) );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get audit log entries.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_audit_log( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$table       = $wpdb->prefix . 'hf_activity_log';
		$per_page    = $request->get_param( 'per_page' );
		$page        = $request->get_param( 'page' );
		$object_type = $request->get_param( 'object_type' );
		$offset      = ( $page - 1 ) * $per_page;

		$where  = '1=1';
		$values = array();

		if ( ! empty( $object_type ) ) {
			$where   .= ' AND object_type = %s';
			$values[] = $object_type;
		}

		if ( ! empty( $values ) ) {
			$query_values = array_merge( $values, array( $per_page, $offset ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $query_values ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
			$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}

		$response_data = array(
			'items' => ! empty( $items ) ? $items : array(),
			'total' => $total,
			'pages' => ceil( $total / $per_page ),
		);

		/** This filter is documented in this file, get_login_attempts method. */
		$response_data = apply_filters( 'hostforge_rest_security_response', $response_data, 'audit-log', $request );

		return rest_ensure_response( $response_data );
	}
}
