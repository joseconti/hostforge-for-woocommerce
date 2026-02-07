<?php
/**
 * Admin class.
 *
 * Registers admin menus, enqueues assets and initializes admin pages.
 *
 * @package HostForge\Admin
 */

namespace HostForge\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Admin
 */
class HF_Admin {

	/**
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_hf_toggle_module', array( $this, 'ajax_toggle_module' ) );
	}

	/**
	 * Register admin menu pages.
	 *
	 * @return void
	 */
	public function register_menus(): void {
		// Main menu.
		add_menu_page(
			__( 'HostForge', 'hostforge' ),
			__( 'HostForge', 'hostforge' ),
			'manage_hostforge',
			'hostforge-dashboard',
			array( $this, 'render_dashboard' ),
			'dashicons-cloud',
			56
		);

		// Dashboard submenu (renames the first item).
		add_submenu_page(
			'hostforge-dashboard',
			__( 'Dashboard', 'hostforge' ),
			__( 'Dashboard', 'hostforge' ),
			'manage_hostforge',
			'hostforge-dashboard',
			array( $this, 'render_dashboard' )
		);

		// Settings > General.
		add_submenu_page(
			'hostforge-dashboard',
			__( 'Settings', 'hostforge' ),
			__( 'Settings', 'hostforge' ),
			'manage_hostforge_settings',
			'hostforge-settings',
			array( $this, 'render_settings' )
		);

		// Settings > Modules.
		add_submenu_page(
			'hostforge-dashboard',
			__( 'Modules', 'hostforge' ),
			__( 'Modules', 'hostforge' ),
			'manage_hostforge_settings',
			'hostforge-modules',
			array( $this, 'render_modules' )
		);

		// Logs.
		add_submenu_page(
			'hostforge-dashboard',
			__( 'Logs', 'hostforge' ),
			__( 'Logs', 'hostforge' ),
			'manage_hostforge',
			'hostforge-logs',
			array( $this, 'render_logs' )
		);

		// Let active modules add their menus.
		$module_manager = \HostForge\HostForge::instance()->module_manager();

		foreach ( $module_manager->get_active_module_ids() as $module_id ) {
			$module = $module_manager->get_module( $module_id );
			if ( ! $module ) {
				continue;
			}

			foreach ( $module->get_admin_menu_items() as $item ) {
				add_submenu_page(
					'hostforge-dashboard',
					$item['title'],
					$item['title'],
					$item['capability'],
					$item['slug'],
					$item['callback']
				);
			}
		}

		/**
		 * Fires after all HostForge admin menus have been registered.
		 *
		 * Use this hook to add custom submenu pages under the HostForge menu
		 * or modify the admin menu structure.
		 *
		 * @since 1.0.0
		 */
		do_action( 'hostforge_admin_menus_registered' );
	}

	/**
	 * Enqueue admin assets only on HostForge pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on HostForge admin pages.
		$screen = get_current_screen();
		if ( ! $screen || ! $this->is_hostforge_screen( $screen->id ) ) {
			return;
		}

		wp_enqueue_style(
			'hostforge-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hostforge-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hostforge-admin',
			'hostforgeAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_admin_nonce' ),
				'i18n'    => array(
					'confirmDeactivate' => __( 'Are you sure you want to deactivate this module?', 'hostforge' ),
					'saving'            => __( 'Saving...', 'hostforge' ),
					'saved'             => __( 'Saved', 'hostforge' ),
					'error'             => __( 'An error occurred. Please try again.', 'hostforge' ),
				),
			)
		);

		/**
		 * Fires after HostForge admin assets have been enqueued.
		 *
		 * Use this hook to enqueue additional styles or scripts on
		 * HostForge admin pages.
		 *
		 * @since 1.0.0
		 *
		 * @param string $hook_suffix The current admin page hook suffix.
		 */
		do_action( 'hostforge_admin_assets', $hook_suffix );
	}

	/**
	 * Check if the current screen belongs to HostForge.
	 *
	 * @param string $screen_id Screen identifier.
	 * @return bool
	 */
	private function is_hostforge_screen( string $screen_id ): bool {
		return str_contains( $screen_id, 'hostforge' );
	}

	/**
	 * Render the Dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_hostforge' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		/**
		 * Filters the dashboard data passed to the admin dashboard template.
		 *
		 * Allows modules and third-party code to inject additional data
		 * (widgets, statistics, notices) into the dashboard view.
		 *
		 * @since 1.0.0
		 *
		 * @param array $dashboard_data {
		 *     Dashboard data array.
		 *
		 *     @type array $widgets    Optional custom widgets to display.
		 *     @type array $notices    Optional admin notices.
		 * }
		 */
		$dashboard_data = apply_filters( 'hostforge_admin_dashboard_data', array() );

		$template = HOSTFORGE_PLUGIN_DIR . 'templates/admin/dashboard.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the Settings page.
	 *
	 * @return void
	 */
	public function render_settings(): void {
		if ( ! current_user_can( 'manage_hostforge_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		$template = HOSTFORGE_PLUGIN_DIR . 'templates/admin/settings.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the Modules page.
	 *
	 * @return void
	 */
	public function render_modules(): void {
		if ( ! current_user_can( 'manage_hostforge_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		$template = HOSTFORGE_PLUGIN_DIR . 'templates/admin/modules.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the Logs page.
	 *
	 * @return void
	 */
	public function render_logs(): void {
		if ( ! current_user_can( 'manage_hostforge' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		$template = HOSTFORGE_PLUGIN_DIR . 'templates/admin/logs.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * AJAX handler for toggling module activation.
	 *
	 * @return void
	 */
	public function ajax_toggle_module(): void {
		check_ajax_referer( 'hf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$module_id = isset( $_POST['module_id'] ) ? sanitize_text_field( wp_unslash( $_POST['module_id'] ) ) : '';
		$activate  = isset( $_POST['activate'] ) && 'true' === $_POST['activate'];

		if ( empty( $module_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid module ID.', 'hostforge' ) ) );
		}

		$module_manager = \HostForge\HostForge::instance()->module_manager();

		if ( $activate ) {
			$result = $module_manager->activate_module( $module_id );
		} else {
			$result = $module_manager->deactivate_module( $module_id );
		}

		if ( $result ) {
			wp_send_json_success(
				array(
					'message' => $activate
						? __( 'Module activated.', 'hostforge' )
						: __( 'Module deactivated.', 'hostforge' ),
					'active'  => $activate,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => $activate
						? __( 'Could not activate module. Check dependencies.', 'hostforge' )
						: __( 'Could not deactivate module.', 'hostforge' ),
				)
			);
		}
	}
}
