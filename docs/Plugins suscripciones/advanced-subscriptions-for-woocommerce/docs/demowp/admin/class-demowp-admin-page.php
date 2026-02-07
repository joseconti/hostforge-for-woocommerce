<?php
/**
 * Admin Page
 *
 * Handles the admin interface for DemoWP configuration.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Admin_Page
 *
 * Manages the WordPress admin interface for DemoWP.
 *
 * @since 1.0.0
 */
class DemoWP_Admin_Page {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_demowp_delete_demo', array( $this, 'ajax_delete_demo' ) );
        add_action( 'wp_ajax_demowp_cleanup_all', array( $this, 'ajax_cleanup_all' ) );
        add_action( 'wp_ajax_demowp_export_csv', array( $this, 'ajax_export_csv' ) );
        add_action( 'wp_ajax_demowp_block_demo', array( $this, 'ajax_block_demo' ) );
        add_action( 'wp_ajax_demowp_unblock_demo', array( $this, 'ajax_unblock_demo' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __( 'DemoWP', 'demowp' ),
            __( 'DemoWP', 'demowp' ),
            'manage_options',
            'demowp',
            array( $this, 'render_settings_page' ),
            'dashicons-welcome-view-site',
            80
        );

        // Settings submenu
        add_submenu_page(
            'demowp',
            __( 'Settings', 'demowp' ),
            __( 'Settings', 'demowp' ),
            'manage_options',
            'demowp',
            array( $this, 'render_settings_page' )
        );

        // Active Demos submenu
        add_submenu_page(
            'demowp',
            __( 'Active Demos', 'demowp' ),
            __( 'Active Demos', 'demowp' ),
            'manage_options',
            'demowp-active',
            array( $this, 'render_active_demos_page' )
        );

        // Statistics submenu
        add_submenu_page(
            'demowp',
            __( 'Statistics', 'demowp' ),
            __( 'Statistics', 'demowp' ),
            'manage_options',
            'demowp-stats',
            array( $this, 'render_statistics_page' )
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'demowp_settings', 'demowp_endpoint_slug', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_title',
            'default'           => 'demo',
        ) );

        register_setting( 'demowp_settings', 'demowp_demo_lifetime', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 3600,
        ) );

        register_setting( 'demowp_settings', 'demowp_max_concurrent_demos', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 3,
        ) );

        register_setting( 'demowp_settings', 'demowp_welcome_message', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => '',
        ) );

        register_setting( 'demowp_settings', 'demowp_email_mode', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'admin',
        ) );

        register_setting( 'demowp_settings', 'demowp_maintenance_mode', array(
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        ) );

        register_setting( 'demowp_settings', 'demowp_maintenance_message', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => '',
        ) );

        // License key setting.
        register_setting( 'demowp_settings', DEMOWP_LICENSE_PREFIX . '_license_key', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        // Flush rewrite rules when endpoint changes.
        add_action( 'update_option_demowp_endpoint_slug', array( $this, 'flush_rewrite_rules' ) );
    }

    /**
     * Flush rewrite rules
     */
    public function flush_rewrite_rules() {
        flush_rewrite_rules();
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'demowp' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'demowp-admin',
            DEMOWP_PLUGIN_URL . 'admin/css/demowp-admin.css',
            array(),
            DEMOWP_VERSION
        );

        wp_enqueue_script(
            'demowp-admin',
            DEMOWP_PLUGIN_URL . 'admin/js/demowp-admin.js',
            array( 'jquery' ),
            DEMOWP_VERSION,
            true
        );

        wp_localize_script( 'demowp-admin', 'demowpAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'demowp_admin_nonce' ),
            'strings' => array(
                'confirmDelete'  => __( 'Are you sure you want to delete this demo?', 'demowp' ),
                'confirmCleanup' => __( 'Are you sure you want to delete ALL active demos?', 'demowp' ),
                'deleting'       => __( 'Deleting...', 'demowp' ),
                'deleted'        => __( 'Deleted', 'demowp' ),
                'error'          => __( 'An error occurred', 'demowp' ),
                'cleaningUp'     => __( 'Cleaning up...', 'demowp' ),
                'blocking'       => __( 'Blocking...', 'demowp' ),
                'unblocking'     => __( 'Unblocking...', 'demowp' ),
                'block'          => __( 'Block', 'demowp' ),
                'unblock'        => __( 'Unblock', 'demowp' ),
            ),
        ) );

        // Statistics page specific assets.
        if ( strpos( $hook, 'demowp-stats' ) !== false ) {
            // Chart.js from CDN.
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                array(),
                '4.4.1',
                true
            );

            wp_enqueue_script(
                'demowp-statistics',
                DEMOWP_PLUGIN_URL . 'admin/js/demowp-statistics.js',
                array( 'jquery', 'chartjs', 'demowp-admin' ),
                DEMOWP_VERSION,
                true
            );
        }
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include DEMOWP_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Render active demos page
     */
    public function render_active_demos_page() {
        $tracker = new DemoWP_Demo_Tracker();
        $demos   = $tracker->get_active_demos();

        include DEMOWP_PLUGIN_DIR . 'admin/views/active-demos.php';
    }

    /**
     * Render statistics page
     */
    public function render_statistics_page() {
        $tracker = new DemoWP_Demo_Tracker();
        $stats   = $tracker->get_statistics();

        include DEMOWP_PLUGIN_DIR . 'admin/views/statistics.php';
    }

    /**
     * AJAX: Delete a demo
     */
    public function ajax_delete_demo() {
        check_ajax_referer( 'demowp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'demowp' ) ) );
        }

        $clone_id = isset( $_POST['clone_id'] ) ? sanitize_text_field( wp_unslash( $_POST['clone_id'] ) ) : '';

        if ( empty( $clone_id ) || ! DemoWP_Utils::is_valid_clone_id( $clone_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid clone ID', 'demowp' ) ) );
        }

        $cloner = new DemoWP_Cloner();
        $result = $cloner->delete_demo( $clone_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Demo deleted successfully', 'demowp' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete demo', 'demowp' ) ) );
        }
    }

    /**
     * AJAX: Cleanup all demos
     */
    public function ajax_cleanup_all() {
        check_ajax_referer( 'demowp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'demowp' ) ) );
        }

        $cleaned = DemoWP_Cleanup::force_cleanup_all();

        wp_send_json_success(
            array(
                'message' => sprintf(
                    /* translators: %d: number of demos deleted */
                    __( 'Deleted %d demos', 'demowp' ),
                    $cleaned
                ),
            )
        );
    }

    /**
     * AJAX: Export demos to CSV
     */
    public function ajax_export_csv() {
        check_ajax_referer( 'demowp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'demowp' ) );
        }

        $start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
        $end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
        $status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

        $tracker = new DemoWP_Demo_Tracker();
        $csv     = $tracker->export_to_csv( $start_date, $end_date, $status );

        // Generate filename.
        $filename = 'demowp-export';
        if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
            $filename .= '-' . $start_date . '-to-' . $end_date;
        }
        $filename .= '.csv';

        // Set headers for download.
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV data export.
        exit;
    }

    /**
     * AJAX: Block a demo from automatic deletion
     */
    public function ajax_block_demo() {
        check_ajax_referer( 'demowp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'demowp' ) ) );
        }

        $clone_id = isset( $_POST['clone_id'] ) ? sanitize_text_field( wp_unslash( $_POST['clone_id'] ) ) : '';

        if ( empty( $clone_id ) || ! DemoWP_Utils::is_valid_clone_id( $clone_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid clone ID', 'demowp' ) ) );
        }

        $tracker = new DemoWP_Demo_Tracker();
        $result  = $tracker->block_demo( $clone_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Demo blocked successfully', 'demowp' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to block demo', 'demowp' ) ) );
        }
    }

	/**
	 * AJAX: Unblock a demo to allow automatic deletion
	 */
	public function ajax_unblock_demo() {
		check_ajax_referer( 'demowp_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'demowp' ) ) );
		}

		$clone_id = isset( $_POST['clone_id'] ) ? sanitize_text_field( wp_unslash( $_POST['clone_id'] ) ) : '';

		if ( empty( $clone_id ) || ! DemoWP_Utils::is_valid_clone_id( $clone_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid clone ID', 'demowp' ) ) );
		}

		$tracker = new DemoWP_Demo_Tracker();
		$result  = $tracker->unblock_demo( $clone_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Demo unblocked successfully', 'demowp' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to unblock demo', 'demowp' ) ) );
		}
	}
}
