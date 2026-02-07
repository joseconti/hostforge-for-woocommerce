<?php
/**
 * REST Domain Controller.
 *
 * Provides REST API endpoints for domain management:
 * list, get, sync, renew, DNS records, availability check.
 *
 * @package HostForge\Modules\DomainManager\Api
 */

namespace HostForge\Modules\DomainManager\Api;

use HostForge\Abstracts\HF_REST_Controller;
use HostForge\Modules\DomainManager\HF_Domain_Manager_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_REST_Domain_Controller
 */
class HF_REST_Domain_Controller extends HF_REST_Controller {

	/**
	 * REST base.
	 *
	 * @var string
	 */
	protected $rest_base = 'domains';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// List domains.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'status'   => array(
							'type'    => 'string',
							'default' => '',
						),
						'search'   => array(
							'type'    => 'string',
							'default' => '',
						),
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
						),
					),
				),
			)
		);

		// Get single domain.
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
							'type'     => 'integer',
							'required' => true,
						),
					),
				),
			)
		);

		// Sync domain with registrar.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/sync',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'sync_domain' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		// Renew domain.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/renew',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'renew_domain' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		// DNS records.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/dns',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_dns_records' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_dns_record' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		// Domain availability check.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/check',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'check_availability' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'domain' => array(
							'type'     => 'string',
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * List domains.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ): \WP_REST_Response {
		$args = array(
			'post_type'      => 'hf_domain',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ),
			'paged'          => $request->get_param( 'page' ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$status = $request->get_param( 'status' );
		if ( ! empty( $status ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_hf_status',
					'value' => sanitize_text_field( $status ),
				),
			);
		}

		$search = $request->get_param( 'search' );
		if ( ! empty( $search ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_hf_domain_name',
					'value'   => sanitize_text_field( $search ),
					'compare' => 'LIKE',
				),
			);
		}

		$query = new \WP_Query( $args );

		$domains = array();
		foreach ( $query->posts as $post ) {
			$domains[] = $this->prepare_domain( $post );
		}

		return new \WP_REST_Response(
			array(
				'domains' => $domains,
				'total'   => $query->found_posts,
				'pages'   => $query->max_num_pages,
			),
			200
		);
	}

	/**
	 * Get a single domain.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$domain_id = $request->get_param( 'id' );
		$domain    = get_post( $domain_id );

		if ( ! $domain || 'hf_domain' !== $domain->post_type ) {
			return $this->error( 'not_found', __( 'Domain not found.', 'hostforge' ), 404 );
		}

		return $this->success( $this->prepare_domain( $domain ) );
	}

	/**
	 * Sync a domain with registrar.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function sync_domain( $request ) {
		$domain_id = $request->get_param( 'id' );

		as_enqueue_async_action(
			'hostforge_register_domain',
			array( $domain_id ),
			'hostforge-domain-manager'
		);

		return $this->success( array( 'message' => __( 'Domain sync queued.', 'hostforge' ) ) );
	}

	/**
	 * Renew a domain.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function renew_domain( $request ) {
		$domain_id = $request->get_param( 'id' );

		as_enqueue_async_action(
			'hostforge_renew_domain',
			array( $domain_id ),
			'hostforge-domain-manager'
		);

		return $this->success( array( 'message' => __( 'Domain renewal queued.', 'hostforge' ) ) );
	}

	/**
	 * Get DNS records for a domain.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_dns_records( $request ) {
		global $wpdb;

		$domain_id = $request->get_param( 'id' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, record_type, host, value, ttl, priority, registrar_record_id, synced_at
				FROM {$wpdb->prefix}hf_dns_records
				WHERE domain_id = %d
				ORDER BY record_type, host",
				$domain_id
			)
		);

		return $this->success( array( 'records' => $records ) );
	}

	/**
	 * Add a DNS record.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function add_dns_record( $request ) {
		$domain_id   = $request->get_param( 'id' );
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		if ( empty( $domain_name ) ) {
			return $this->error( 'invalid_domain', __( 'Domain not found.', 'hostforge' ), 404 );
		}

		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$module         = $module_manager->get_module( 'domain-manager' );
		$registrar      = $module ? $module->get_registrar() : null;

		if ( ! $registrar ) {
			return $this->error( 'no_registrar', __( 'No registrar configured.', 'hostforge' ) );
		}

		$record = array(
			'type'     => sanitize_text_field( $request->get_param( 'record_type' ) ?? 'A' ),
			'host'     => sanitize_text_field( $request->get_param( 'host' ) ?? '@' ),
			'value'    => sanitize_text_field( $request->get_param( 'value' ) ?? '' ),
			'ttl'      => absint( $request->get_param( 'ttl' ) ?? 3600 ),
			'priority' => absint( $request->get_param( 'priority' ) ?? 0 ),
		);

		$result = $registrar->add_dns_record( $domain_name, $record );

		if ( $result['success'] ) {
			return $this->success( array( 'message' => $result['message'] ) );
		}

		return $this->error( 'dns_error', $result['message'] );
	}

	/**
	 * Check domain availability.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function check_availability( $request ) {
		$domain = sanitize_text_field( $request->get_param( 'domain' ) );

		if ( empty( $domain ) ) {
			return $this->error( 'missing_domain', __( 'Domain name is required.', 'hostforge' ) );
		}

		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$module         = $module_manager->get_module( 'domain-manager' );
		$registrar      = $module ? $module->get_registrar() : null;

		if ( ! $registrar ) {
			return $this->error( 'no_registrar', __( 'No registrar configured.', 'hostforge' ) );
		}

		$result = $registrar->check_availability( $domain );

		return $this->success( $result );
	}

	/**
	 * Prepare a domain for API response.
	 *
	 * @param \WP_Post $post Domain post.
	 * @return array
	 */
	private function prepare_domain( \WP_Post $post ): array {
		return array(
			'id'                => $post->ID,
			'domain_name'       => get_post_meta( $post->ID, '_hf_domain_name', true ),
			'registrar_id'      => get_post_meta( $post->ID, '_hf_registrar_id', true ),
			'user_id'           => absint( get_post_meta( $post->ID, '_hf_user_id', true ) ),
			'order_id'          => absint( get_post_meta( $post->ID, '_hf_order_id', true ) ),
			'product_id'        => absint( get_post_meta( $post->ID, '_hf_product_id', true ) ),
			'status'            => get_post_meta( $post->ID, '_hf_status', true ),
			'registration_date' => get_post_meta( $post->ID, '_hf_registration_date', true ),
			'expiry_date'       => get_post_meta( $post->ID, '_hf_expiry_date', true ),
			'auto_renew'        => get_post_meta( $post->ID, '_hf_auto_renew', true ),
			'locked'            => get_post_meta( $post->ID, '_hf_locked', true ),
			'type'              => get_post_meta( $post->ID, '_hf_type', true ),
			'nameservers'       => json_decode( get_post_meta( $post->ID, '_hf_nameservers', true ) ? get_post_meta( $post->ID, '_hf_nameservers', true ) : '[]', true ),
			'linked_service_id' => absint( get_post_meta( $post->ID, '_hf_linked_service_id', true ) ),
			'last_synced'       => get_post_meta( $post->ID, '_hf_last_synced', true ),
			'created_at'        => $post->post_date,
		);
	}
}
