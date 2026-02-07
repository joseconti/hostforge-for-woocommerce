<?php
/**
 * Plugin Loader
 *
 * Handles loading dependencies and initializing components based on context.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Loader
 *
 * Main loader class that initializes the plugin components.
 *
 * @since 1.0.0
 */
class DemoWP_Loader {

    /**
     * Run the loader
     *
     * @since 1.0.0
     */
    public function run() {
        if ( DEMOWP_IS_CLONE ) {
            // In clones: only load restrictions and autologin
            $this->init_clone_mode();
        } else {
            // In template: load everything
            $this->init_template_mode();
        }
    }

    /**
     * Initialize template mode (main installation)
     */
    private function init_template_mode() {
        // Initialize demo tracker
        new DemoWP_Demo_Tracker();

        // Admin functionality
        if ( is_admin() ) {
            new DemoWP_Admin_Page();
            // Initialize license manager
            new DemoWP_License();
        }

        // Frontend - Demo creation endpoint
        new DemoWP_Public();

        // AJAX handlers
        new DemoWP_Ajax();

        // Cleanup scheduler
        new DemoWP_Cleanup();

        // Maintenance mode
        new DemoWP_Maintenance();
    }

    /**
     * Initialize clone mode (demo installations)
     */
    private function init_clone_mode() {
        // Restrictions are now handled by the mu-plugin (demowp-restrictions-loader.php)
        // which cannot be deactivated. We only load DemoWP_Restrictions as a fallback
        // if the mu-plugin class doesn't exist (for backwards compatibility).
        if ( ! class_exists( 'DemoWP_Clone_Restrictions' ) ) {
            new DemoWP_Restrictions();
        }

        // Autologin is also handled by mu-plugin. Only load as fallback.
        if ( ! class_exists( 'DemoWP_Clone_Autologin' ) ) {
            new DemoWP_Autologin();
        }
    }
}
