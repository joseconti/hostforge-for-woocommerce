<?php
/**
 * Reports REST API Controller.
 *
 * Provides REST endpoints for Chart.js data.
 *
 * @package HostForge\Modules\Reports\Api
 */

namespace HostForge\Modules\Reports\Api;

use HostForge\Abstracts\HF_REST_Controller;
use HostForge\Modules\Reports\HF_Report_Data;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_REST_Reports_Controller
 */
class HF_REST_Reports_Controller extends HF_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = 'reports';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Revenue chart data.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/revenue',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_revenue' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'months' => array(
							'default'           => 12,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Customer growth data.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/customers',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_customers' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => array(
						'months' => array(
							'default'           => 12,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		// Services summary.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/services',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_services' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		// Tickets summary.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/tickets',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tickets' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		// Domains summary.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/domains',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_domains' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		// Server capacity.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/servers',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_servers' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);
	}

	/**
	 * Get revenue chart data.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_revenue( \WP_REST_Request $request ): \WP_REST_Response {
		$months     = $request->get_param( 'months' );
		$data       = new HF_Report_Data();
		$start_date = gmdate( 'Y-m-d', strtotime( sprintf( '-%d months', $months ) ) );
		$end_date   = gmdate( 'Y-m-d' );

		$revenue = $data->get_revenue_data( $start_date, $end_date );
		$mrr     = $data->get_mrr();

		return rest_ensure_response(
			array(
				'data' => $revenue,
				'mrr'  => $mrr,
			)
		);
	}

	/**
	 * Get customer growth data.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_customers( \WP_REST_Request $request ): \WP_REST_Response {
		$months     = $request->get_param( 'months' );
		$data       = new HF_Report_Data();
		$start_date = gmdate( 'Y-m-d', strtotime( sprintf( '-%d months', $months ) ) );
		$end_date   = gmdate( 'Y-m-d' );

		$customers = $data->get_customer_growth( $start_date, $end_date );

		return rest_ensure_response( array( 'data' => $customers ) );
	}

	/**
	 * Get services summary.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_services( \WP_REST_Request $request ): \WP_REST_Response {
		$data = new HF_Report_Data();

		return rest_ensure_response( array( 'data' => $data->get_services_by_status() ) );
	}

	/**
	 * Get tickets summary.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_tickets( \WP_REST_Request $request ): \WP_REST_Response {
		$data = new HF_Report_Data();

		return rest_ensure_response( array( 'data' => $data->get_ticket_metrics() ) );
	}

	/**
	 * Get domains summary.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_domains( \WP_REST_Request $request ): \WP_REST_Response {
		$data = new HF_Report_Data();

		return rest_ensure_response( array( 'data' => $data->get_domain_stats() ) );
	}

	/**
	 * Get server capacity.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_servers( \WP_REST_Request $request ): \WP_REST_Response {
		$data = new HF_Report_Data();

		return rest_ensure_response( array( 'data' => $data->get_server_capacity() ) );
	}
}
