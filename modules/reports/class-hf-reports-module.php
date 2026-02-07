<?php
/**
 * Reports Module.
 *
 * Dashboard reports with Chart.js: revenue, services, support metrics,
 * domains and server capacity. CSV export.
 *
 * @package HostForge\Modules\Reports
 */

namespace HostForge\Modules\Reports;

use HostForge\Abstracts\HF_Module;
use HostForge\Traits\HF_Has_Logs;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Reports_Module
 */
class HF_Reports_Module extends HF_Module {

	use HF_Has_Logs;

	/**
	 * Get the module identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'reports';
	}

	/**
	 * Get the module display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Reports', 'hostforge' );
	}

	/**
	 * Get the module description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Revenue reports, service metrics, support statistics and CSV export with Chart.js dashboards.', 'hostforge' );
	}

	/**
	 * Get required dependencies.
	 *
	 * @return array<string>
	 */
	public function get_dependencies(): array {
		return array();
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init(): void {
		// Admin screens.
		if ( is_admin() ) {
			$admin = new Admin\HF_Reports_Admin( $this );
			$admin->init();
		}

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// CSV export handler.
		add_action( 'admin_init', array( $this, 'handle_csv_export' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controller = new Api\HF_REST_Reports_Controller();
		$controller->register_routes();
	}

	/**
	 * Get admin menu items.
	 *
	 * @return array
	 */
	public function get_admin_menu_items(): array {
		return array(
			array(
				'title'      => __( 'Reports', 'hostforge' ),
				'slug'       => 'hostforge-reports',
				'capability' => 'view_hostforge_reports',
				'callback'   => array( $this, 'render_admin_page' ),
			),
		);
	}

	/**
	 * Render admin page (delegated to admin class).
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		// Handled by HF_Reports_Admin.
	}

	/**
	 * Handle CSV export request.
	 *
	 * @return void
	 */
	public function handle_csv_export(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['hf_export'] ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'hf_csv_export' ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'hostforge' ) );
		}

		if ( ! current_user_can( 'view_hostforge_reports' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'hostforge' ) );
		}

		$export_type = sanitize_text_field( wp_unslash( $_GET['hf_export'] ) );

		$valid_types = array( 'revenue', 'services', 'tickets', 'domains', 'servers' );

		/**
		 * Filter the available report/export types.
		 *
		 * Allows third-party code to register additional export types
		 * that the CSV exporter and reports system will recognize.
		 *
		 * @since 1.0.0
		 *
		 * @param array $valid_types List of valid export type slugs.
		 */
		$valid_types = apply_filters( 'hostforge_report_types', $valid_types );

		if ( ! in_array( $export_type, $valid_types, true ) ) {
			return;
		}

		$data_provider = new HF_Report_Data();

		$csv_exporter = new HF_CSV_Exporter( $data_provider );
		$csv_exporter->export( $export_type );
	}
}
