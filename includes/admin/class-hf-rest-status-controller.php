<?php
/**
 * REST Status Controller.
 *
 * Provides health-check endpoint: GET /wp-json/hostforge/v1/status
 *
 * @package HostForge\Admin
 */

namespace HostForge\Admin;

use HostForge\Abstracts\HF_REST_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_REST_Status_Controller
 */
class HF_REST_Status_Controller extends HF_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'status';

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
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);
	}

	/**
	 * Get system status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_status( \WP_REST_Request $request ): \WP_REST_Response {
		$module_manager = \HostForge\HostForge::instance()->module_manager();

		return $this->success(
			array(
				'version'        => HOSTFORGE_VERSION,
				'php_version'    => PHP_VERSION,
				'wp_version'     => get_bloginfo( 'version' ),
				'wc_version'     => defined( 'WC_VERSION' ) ? WC_VERSION : 'N/A',
				'active_modules' => $module_manager->get_active_module_ids(),
				'hpos_enabled'   => $this->is_hpos_enabled(),
				'debug_mode'     => 'yes' === get_option( 'hf_debug_mode', 'no' ),
				'timestamp'      => current_time( 'mysql', true ),
			)
		);
	}

	/**
	 * Check if HPOS is enabled.
	 *
	 * @return bool
	 */
	private function is_hpos_enabled(): bool {
		if ( ! class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class ) ) {
			return false;
		}

		return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}
}
