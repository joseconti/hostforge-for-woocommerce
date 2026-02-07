<?php
/**
 * REST Service Controller.
 *
 * Provides REST API endpoints for services.
 *
 * @package HostForge\Modules\AutoProvisioning\Api
 */

namespace HostForge\Modules\AutoProvisioning\Api;

use HostForge\Abstracts\HF_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_REST_Service_Controller
 */
class HF_REST_Service_Controller extends HF_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'services';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
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
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'per_page' => array(
							'type'              => 'integer',
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'page'     => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)/action',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'perform_action' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'id'     => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
						'action' => array(
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
							'enum'              => array( 'suspend', 'unsuspend', 'terminate' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Get services list.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_items( $request ): \WP_REST_Response {
		$args = array(
			'post_type'      => 'hf_service',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ),
			'paged'          => $request->get_param( 'page' ),
		);

		$status = $request->get_param( 'status' );
		if ( $status ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_hf_status',
					'value' => $status,
				),
			);
		}

		$query    = new \WP_Query( $args );
		$services = array();

		foreach ( $query->posts as $post ) {
			$services[] = $this->prepare_service( $post );
		}

		return new \WP_REST_Response(
			array(
				'services' => $services,
				'total'    => $query->found_posts,
				'pages'    => $query->max_num_pages,
			),
			200
		);
	}

	/**
	 * Get a single service.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$service = get_post( $request->get_param( 'id' ) );

		if ( ! $service || 'hf_service' !== $service->post_type ) {
			return new \WP_Error( 'not_found', __( 'Service not found.', 'hostforge' ), array( 'status' => 404 ) );
		}

		return new \WP_REST_Response( $this->prepare_service( $service ), 200 );
	}

	/**
	 * Perform a manual action on a service.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function perform_action( $request ) {
		$service_id = $request->get_param( 'id' );
		$action     = $request->get_param( 'action' );

		$service = get_post( $service_id );
		if ( ! $service || 'hf_service' !== $service->post_type ) {
			return new \WP_Error( 'not_found', __( 'Service not found.', 'hostforge' ), array( 'status' => 404 ) );
		}

		$hook = 'hostforge_' . $action . '_service';

		as_enqueue_async_action(
			$hook,
			array( $service_id ),
			'hostforge-provisioning'
		);

		return new \WP_REST_Response(
			array(
				'message' => sprintf(
					/* translators: %s: action name */
					__( '%s action enqueued.', 'hostforge' ),
					ucfirst( $action )
				),
			),
			200
		);
	}

	/**
	 * Prepare a service for API response.
	 *
	 * @param \WP_Post $post Service post.
	 * @return array
	 */
	private function prepare_service( \WP_Post $post ): array {
		$provisioned_at = get_post_meta( $post->ID, '_hf_provisioned_at', true );
		$suspended_at   = get_post_meta( $post->ID, '_hf_suspended_at', true );
		$terminated_at  = get_post_meta( $post->ID, '_hf_terminated_at', true );
		$next_due_date  = get_post_meta( $post->ID, '_hf_next_due_date', true );

		$data = array(
			'id'              => $post->ID,
			'domain'          => get_post_meta( $post->ID, '_hf_domain', true ),
			'status'          => get_post_meta( $post->ID, '_hf_status', true ),
			'panel_username'  => get_post_meta( $post->ID, '_hf_panel_username', true ),
			'panel_type'      => get_post_meta( $post->ID, '_hf_panel_type', true ),
			'package'         => get_post_meta( $post->ID, '_hf_package', true ),
			'server_id'       => absint( get_post_meta( $post->ID, '_hf_server_id', true ) ),
			'order_id'        => absint( get_post_meta( $post->ID, '_hf_order_id', true ) ),
			'subscription_id' => absint( get_post_meta( $post->ID, '_hf_subscription_id', true ) ),
			'user_id'         => absint( get_post_meta( $post->ID, '_hf_user_id', true ) ),
			'product_id'      => absint( get_post_meta( $post->ID, '_hf_product_id', true ) ),
			'provisioned_at'  => ! empty( $provisioned_at ) ? $provisioned_at : null,
			'suspended_at'    => ! empty( $suspended_at ) ? $suspended_at : null,
			'terminated_at'   => ! empty( $terminated_at ) ? $terminated_at : null,
			'next_due_date'   => ! empty( $next_due_date ) ? $next_due_date : null,
			'created_at'      => $post->post_date,
		);

		/**
		 * Filter the service REST response data.
		 *
		 * Allows modification of the data returned for each service
		 * in REST API responses, e.g. to add custom fields.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $data Service data array.
		 * @param \WP_Post $post Service post object.
		 */
		return apply_filters( 'hostforge_rest_service_response', $data, $post );
	}
}
