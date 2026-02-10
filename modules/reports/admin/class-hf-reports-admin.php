<?php
/**
 * Reports Admin.
 *
 * Admin screen for viewing reports with Chart.js and exporting CSV.
 *
 * @package HostForge\Modules\Reports\Admin
 */

namespace HostForge\Modules\Reports\Admin;

use HostForge\Modules\Reports\HF_Reports_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Reports_Admin
 */
class HF_Reports_Admin {

	/**
	 * Module instance.
	 *
	 * @var HF_Reports_Module
	 */
	private HF_Reports_Module $module;

	/**
	 * Constructor.
	 *
	 * @param HF_Reports_Module $module Module instance.
	 */
	public function __construct( HF_Reports_Module $module ) {
		$this->module = $module;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add admin menu pages.
	 *
	 * @return void
	 */
	public function add_menu_pages(): void {
		add_submenu_page(
			'hostforge-dashboard',
			__( 'Reports', 'hostforge' ),
			__( 'Reports', 'hostforge' ),
			'view_hostforge_reports',
			'hostforge-reports',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue assets for reports page.
	 *
	 * @param string $hook_suffix Admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( strpos( $hook_suffix, 'hostforge-reports' ) === false ) {
			return;
		}

		// Chart.js from CDN.
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js',
			array(),
			'4.4.7',
			true
		);

		wp_enqueue_style(
			'hf-reports-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/css/reports-admin.css',
			array(),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hf-reports-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/js/reports-admin.js',
			array( 'chartjs' ),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hf-reports-admin',
			'hfReports',
			array(
				'restUrl' => rest_url( 'hostforge/v1/reports/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'revenue'    => __( 'Revenue', 'hostforge' ),
					'orders'     => __( 'Orders', 'hostforge' ),
					'customers'  => __( 'New Customers', 'hostforge' ),
					'loading'    => __( 'Loading...', 'hostforge' ),
					'error'      => __( 'Failed to load data.', 'hostforge' ),
					'active'     => __( 'Active', 'hostforge' ),
					'pending'    => __( 'Pending', 'hostforge' ),
					'suspended'  => __( 'Suspended', 'hostforge' ),
					'terminated' => __( 'Terminated', 'hostforge' ),
					'cancelled'  => __( 'Cancelled', 'hostforge' ),
					'open'       => __( 'Open', 'hostforge' ),
					'closed'     => __( 'Closed', 'hostforge' ),
				),
			)
		);
	}

	/**
	 * Render the reports page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'view_hostforge_reports' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		include $this->module->get_module_dir() . 'admin/templates/reports-dashboard.php';
	}
}
