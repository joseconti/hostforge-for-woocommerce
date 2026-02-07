<?php
/**
 * AJAX Handlers
 *
 * Handles AJAX requests for demo creation and progress.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Ajax
 *
 * Manages AJAX endpoints for demo creation.
 *
 * @since 1.0.0
 */
class DemoWP_Ajax {

    /**
     * Constructor
     */
    public function __construct() {
        // Start demo creation
        add_action( 'wp_ajax_demowp_create_demo', array( $this, 'create_demo' ) );
        add_action( 'wp_ajax_nopriv_demowp_create_demo', array( $this, 'create_demo' ) );

        // Check progress
        add_action( 'wp_ajax_demowp_check_progress', array( $this, 'check_progress' ) );
        add_action( 'wp_ajax_nopriv_demowp_check_progress', array( $this, 'check_progress' ) );
    }

    /**
     * Start demo creation
     */
    public function create_demo() {
        global $wpdb;

        // Verify nonce.
        if ( ! check_ajax_referer( 'demowp_progress_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'demowp' ) ) );
        }

        // Get progress key.
        $progress_key = sanitize_text_field( wp_unslash( $_POST['progress_key'] ?? '' ) );

        if ( empty( $progress_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'demowp' ) ) );
        }

        // Check rate limit.
        $max_demos = (int) get_option( 'demowp_max_concurrent_demos', 3 );
        $ip        = DemoWP_Utils::get_client_ip();
        $tracker   = new DemoWP_Demo_Tracker();

        if ( $tracker->count_demos_by_ip( $ip ) >= $max_demos ) {
            wp_send_json_error( array( 'message' => __( 'Maximum demos reached.', 'demowp' ) ) );
        }

        // Use a simple lock based on progress_key.
        // Each progress_key is unique per page load, so this only prevents
        // duplicate AJAX requests from the same page from creating multiple demos.
        $lock_key    = 'demowp_lock_' . $progress_key;
        $lock_option = '_transient_' . $lock_key;

        // Try to acquire lock using INSERT IGNORE (atomic).
        // If the row already exists, INSERT IGNORE does nothing and returns 0.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $inserted = $wpdb->query(
            $wpdb->prepare(
                "INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')",
                $lock_option,
                '1'
            )
        );

        if ( ! $inserted ) {
            // Lock already exists - another request is processing.
            // Return success with duplicate status (JS will ignore this).
            wp_send_json_success(
                array(
                    'status'  => 'duplicate',
                    'message' => 'Already processing',
                )
            );
        }

        // Close session to allow concurrent AJAX requests (progress polling).
        if ( session_status() === PHP_SESSION_ACTIVE ) {
            session_write_close();
        }

        // Create cloner with progress tracking.
        $cloner = new DemoWP_Cloner();
        $cloner->set_progress_key( $progress_key );

        // Get user email from transient if exists.
        $user_email = get_transient( 'demowp_user_email_' . $progress_key );
        if ( $user_email ) {
            delete_transient( 'demowp_user_email_' . $progress_key );
        }

        // Create demo.
        $result = $cloner->create_demo( $user_email ? $user_email : '' );

        // Clean up lock after completion.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->delete( $wpdb->options, array( 'option_name' => $lock_option ) );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( $result );
    }

    /**
     * Check demo creation progress
     *
     * Uses direct database reads to bypass object cache and get
     * real-time progress from the concurrent creation process.
     */
    public function check_progress() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'demowp_progress_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'demowp' ) ) );
        }

        // Get progress key.
        $progress_key = sanitize_text_field( wp_unslash( $_POST['progress_key'] ?? '' ) );

        if ( empty( $progress_key ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'demowp' ) ) );
        }

        // Read progress directly from database to bypass object cache.
        global $wpdb;
        $option_name = '_transient_' . $progress_key;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $progress_data = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                $option_name
            )
        );

        if ( $progress_data ) {
            $progress = maybe_unserialize( $progress_data );
        } else {
            // Return initial state if not found.
            $progress = array(
                'status'        => 'pending',
                'percent'       => 0,
                'current_step'  => 0,
                'current_label' => __( 'Initializing demo environment...', 'demowp' ),
                'steps'         => array(
                    array( 'label' => __( 'Initializing demo environment...', 'demowp' ), 'completed' => false ),
                    array( 'label' => __( 'Creating directory structure...', 'demowp' ), 'completed' => false ),
                    array( 'label' => __( 'Copying WordPress files...', 'demowp' ), 'completed' => false ),
                    array( 'label' => __( 'Setting up database...', 'demowp' ), 'completed' => false ),
                    array( 'label' => __( 'Creating your demo account...', 'demowp' ), 'completed' => false ),
                    array( 'label' => __( 'Finalizing configuration...', 'demowp' ), 'completed' => false ),
                    array( 'label' => __( 'Ready! Redirecting...', 'demowp' ), 'completed' => false ),
                ),
                'error'         => null,
                'result'        => null,
            );
        }

        wp_send_json_success( $progress );
    }
}
