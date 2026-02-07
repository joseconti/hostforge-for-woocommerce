<?php
/**
 * Public Frontend
 *
 * Handles the demo creation endpoint and forms.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Public
 *
 * Manages public-facing functionality.
 *
 * @since 1.0.0
 */
class DemoWP_Public {

    /**
     * Constructor
     */
    public function __construct() {
        // Register endpoint
        add_action( 'init', array( $this, 'register_endpoint' ) );

        // Handle requests
        add_action( 'template_redirect', array( $this, 'handle_demo_request' ) );

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Register the demo endpoint
     */
    public function register_endpoint() {
        $slug = get_option( 'demowp_endpoint_slug', 'demo' );

        add_rewrite_rule(
            '^' . preg_quote( $slug, '/' ) . '/?$',
            'index.php?demowp_action=create',
            'top'
        );

        add_rewrite_tag( '%demowp_action%', '([^&]+)' );
    }

    /**
     * Handle demo request
     */
    public function handle_demo_request() {
        $action = get_query_var( 'demowp_action' );

        if ( 'create' !== $action ) {
            return;
        }

        // Check rate limit
        if ( $this->is_rate_limited() ) {
            $this->render_error( __( 'You have reached the maximum number of active demos. Please try again later.', 'demowp' ) );
            return;
        }

        // Show form or progress page
        if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['demowp_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $this->process_form_submission();
        } else {
            $this->render_form();
        }
    }

    /**
     * Process form submission
     */
    private function process_form_submission() {
        // Verify nonce
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['demowp_nonce'] ?? '' ) ), 'demowp_create_demo' ) ) {
            $this->render_form( __( 'Session expired. Please refresh and try again.', 'demowp' ) );
            return;
        }

        // Validate captcha
        $captcha_token  = sanitize_text_field( wp_unslash( $_POST['captcha_token'] ?? '' ) );
        $captcha_answer = intval( $_POST['captcha_answer'] ?? 0 );

        if ( ! DemoWP_Captcha::validate( $captcha_token, $captcha_answer ) ) {
            $this->render_form( __( 'Incorrect answer. Please try again.', 'demowp' ) );
            return;
        }

        // Validate user email if required.
        $user_email = '';
        if ( 'user' === get_option( 'demowp_email_mode', 'admin' ) ) {
            $user_email = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

            if ( empty( $user_email ) || ! is_email( $user_email ) ) {
                $this->render_form( __( 'Please enter a valid email address.', 'demowp' ) );
                return;
            }
        }

        // Generate progress key (unique for each attempt).
        $progress_key = 'demowp_progress_' . DemoWP_Utils::generate_random_string( 16 );

        // Store user email in transient for AJAX to retrieve.
        if ( ! empty( $user_email ) ) {
            set_transient( 'demowp_user_email_' . $progress_key, $user_email, 600 );
        }

        // Render progress page.
        // The AJAX handler will use a lock system to prevent duplicate processing.
        $this->render_progress( $progress_key );
    }

    /**
     * Check if user is rate limited
     *
     * @return bool True if rate limited.
     */
    private function is_rate_limited() {
        $max_demos = (int) get_option( 'demowp_max_concurrent_demos', 3 );
        $ip        = DemoWP_Utils::get_client_ip();

        $tracker = new DemoWP_Demo_Tracker();
        $count   = $tracker->count_demos_by_ip( $ip );

        return $count >= $max_demos;
    }

    /**
     * Render the demo creation form
     *
     * @param string $error Optional error message.
     */
    private function render_form( $error = '' ) {
        $captcha = DemoWP_Captcha::generate();

        // Load template
        include DEMOWP_PLUGIN_DIR . 'public/views/demo-request-form.php';
        exit;
    }

    /**
     * Render the progress page
     *
     * @param string $progress_key The progress tracking key.
     */
    private function render_progress( $progress_key ) {
        // Load template
        include DEMOWP_PLUGIN_DIR . 'public/views/demo-progress.php';
        exit;
    }

    /**
     * Render error page
     *
     * @param string $message Error message.
     */
    private function render_error( $message ) {
        // Load template
        include DEMOWP_PLUGIN_DIR . 'public/views/demo-error.php';
        exit;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        $action = get_query_var( 'demowp_action' );

        if ( 'create' !== $action ) {
            return;
        }

        wp_enqueue_style(
            'demowp-public',
            DEMOWP_PLUGIN_URL . 'public/css/demowp-public.css',
            array(),
            DEMOWP_VERSION
        );

        wp_enqueue_style(
            'demowp-progress',
            DEMOWP_PLUGIN_URL . 'public/css/demowp-progress.css',
            array(),
            DEMOWP_VERSION
        );

        wp_enqueue_script(
            'demowp-progress',
            DEMOWP_PLUGIN_URL . 'public/js/demowp-progress.js',
            array( 'jquery' ),
            DEMOWP_VERSION,
            true
        );

        wp_localize_script(
            'demowp-progress',
            'demowpProgress',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'demowp_progress_nonce' ),
                'strings' => array(
                    'error'       => __( 'An error occurred. Please try again.', 'demowp' ),
                    'redirecting' => __( 'Redirecting to your demo...', 'demowp' ),
                ),
            )
        );
    }

    /**
     * Get the demo endpoint URL
     *
     * @return string The endpoint URL.
     */
    public static function get_endpoint_url() {
        $slug = get_option( 'demowp_endpoint_slug', 'demo' );
        return home_url( $slug . '/' );
    }
}
