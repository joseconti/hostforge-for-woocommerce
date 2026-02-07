<?php
/**
 * Utility Functions
 *
 * Helper functions used throughout the plugin.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Utils
 *
 * Static utility functions.
 *
 * @since 1.0.0
 */
class DemoWP_Utils {

    /**
     * Get the client IP address
     *
     * Handles proxies and load balancers.
     *
     * @return string The client IP address
     */
    public static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxies
            'HTTP_X_REAL_IP',        // Nginx proxy
            'REMOTE_ADDR',           // Standard
        );

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

                // If multiple IPs (X-Forwarded-For), take the first one
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }

                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Validate a clone ID format
     *
     * Clone IDs must be 32 hexadecimal characters.
     *
     * @param string $clone_id The clone ID to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function is_valid_clone_id( $clone_id ) {
        return (bool) preg_match( '/^[a-f0-9]{32}$/', $clone_id );
    }

    /**
     * Sanitize a database prefix
     *
     * Only allow alphanumeric characters and underscores.
     *
     * @param string $prefix The prefix to sanitize.
     * @return string The sanitized prefix.
     */
    public static function sanitize_db_prefix( $prefix ) {
        return preg_replace( '/[^a-zA-Z0-9_]/', '', $prefix );
    }

    /**
     * Generate a secure random string
     *
     * @param int $length The length of the string.
     * @return string The random string (hexadecimal).
     */
    public static function generate_random_string( $length = 32 ) {
        try {
            return bin2hex( random_bytes( $length / 2 ) );
        } catch ( Exception $e ) {
            // Fallback for older PHP versions
            return wp_generate_password( $length, false, false );
        }
    }

    /**
     * Get human-readable time difference
     *
     * @param int $timestamp Unix timestamp.
     * @return string Human-readable time (e.g., "45 minutes").
     */
    public static function get_time_remaining( $timestamp ) {
        $diff = $timestamp - time();

        if ( $diff <= 0 ) {
            return __( 'Expired', 'demowp' );
        }

        return human_time_diff( time(), $timestamp );
    }

    /**
     * Format bytes to human-readable size
     *
     * @param int $bytes The size in bytes.
     * @return string Human-readable size (e.g., "1.5 MB").
     */
    public static function format_bytes( $bytes ) {
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
        $bytes = max( $bytes, 0 );
        $pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
        $pow   = min( $pow, count( $units ) - 1 );
        $bytes /= pow( 1024, $pow );

        return round( $bytes, 2 ) . ' ' . $units[ $pow ];
    }

    /**
     * Check if the current request is an AJAX request
     *
     * @return bool True if AJAX request.
     */
    public static function is_ajax_request() {
        return defined( 'DOING_AJAX' ) && DOING_AJAX;
    }

    /**
     * Get the template site URL
     *
     * Returns the URL of the main template installation.
     *
     * @return string The template site URL.
     */
    public static function get_template_url() {
        // If we're in a clone, we need to get the parent URL
        if ( DEMOWP_IS_CLONE ) {
            $site_url = get_option( 'siteurl' );
            // Remove the clone path from the URL
            $parsed = wp_parse_url( $site_url );
            return $parsed['scheme'] . '://' . $parsed['host'];
        }

        return get_site_url();
    }

    /**
     * Log a message to the error log
     *
     * @param string $message The message to log.
     * @param string $level   The log level (info, warning, error).
     */
    public static function log( $message, $level = 'info' ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        $prefix = sprintf( '[DemoWP][%s]', strtoupper( $level ) );
        error_log( $prefix . ' ' . $message );
    }

    /**
     * Check if Action Scheduler is available
     *
     * @return bool True if Action Scheduler is loaded.
     */
    public static function has_action_scheduler() {
        return function_exists( 'as_schedule_single_action' );
    }

    /**
     * Get directory size recursively
     *
     * @param string $path The directory path.
     * @return int Size in bytes.
     */
    public static function get_directory_size( $path ) {
        $size = 0;

        if ( ! is_dir( $path ) ) {
            return $size;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS )
        );

        foreach ( $iterator as $file ) {
            if ( $file->isFile() && ! $file->isLink() ) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
