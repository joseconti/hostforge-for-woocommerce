<?php
/**
 * Demo Cloner
 *
 * Orchestrates the complete cloning process for demo instances.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Cloner
 *
 * Main orchestrator for creating and deleting demo clones.
 *
 * @since 1.0.0
 */
class DemoWP_Cloner {

    /**
     * Database handler
     *
     * @var DemoWP_Database
     */
    private $database;

    /**
     * Filesystem handler
     *
     * @var DemoWP_Filesystem
     */
    private $filesystem;

    /**
     * Demo tracker
     *
     * @var DemoWP_Demo_Tracker
     */
    private $tracker;

    /**
     * Progress transient key
     *
     * @var string
     */
    private $progress_key;

    /**
     * Constructor
     */
    public function __construct() {
        $this->database   = new DemoWP_Database();
        $this->filesystem = new DemoWP_Filesystem();
        $this->tracker    = new DemoWP_Demo_Tracker();
    }

    /**
     * Set progress key for tracking
     *
     * @param string $key The transient key for progress.
     */
    public function set_progress_key( $key ) {
        $this->progress_key = $key;

        // Set callbacks for sub-components
        $callback = array( $this, 'update_progress' );
        $this->database->set_progress_callback( $callback );
        $this->filesystem->set_progress_callback( $callback );
    }

    /**
     * Update progress in transient
     *
     * @param string $step    Current step identifier.
     * @param int    $percent Sub-step percentage (0-100).
     */
    public function update_progress( $step, $percent ) {
        if ( empty( $this->progress_key ) ) {
            return;
        }

        $progress = $this->get_progress();

        // Map steps to overall progress
        $step_mapping = array(
            'init'              => array( 'index' => 0, 'label' => 'Initializing demo environment...', 'weight' => 5 ),
            'directory'         => array( 'index' => 1, 'label' => 'Creating directory structure...', 'weight' => 10 ),
            'files_start'       => array( 'index' => 2, 'label' => 'Copying WordPress files...', 'weight' => 0 ),
            'files_structure'   => array( 'index' => 2, 'label' => 'Copying WordPress files...', 'weight' => 2 ),
            'files_core'        => array( 'index' => 2, 'label' => 'Copying WordPress files...', 'weight' => 15 ),
            'files_plugins'     => array( 'index' => 2, 'label' => 'Copying WordPress files...', 'weight' => 5 ),
            'files_themes'      => array( 'index' => 2, 'label' => 'Copying WordPress files...', 'weight' => 5 ),
            'files_root'        => array( 'index' => 2, 'label' => 'Copying WordPress files...', 'weight' => 2 ),
            'files_complete'    => array( 'index' => 2, 'label' => 'Copying WordPress files...', 'weight' => 1 ),
            'database_start'    => array( 'index' => 3, 'label' => 'Setting up database...', 'weight' => 0 ),
            'database_progress' => array( 'index' => 3, 'label' => 'Setting up database...', 'weight' => 25 ),
            'database_complete' => array( 'index' => 3, 'label' => 'Setting up database...', 'weight' => 5 ),
            'user'              => array( 'index' => 4, 'label' => 'Creating your demo account...', 'weight' => 10 ),
            'config'            => array( 'index' => 5, 'label' => 'Finalizing configuration...', 'weight' => 10 ),
            'complete'          => array( 'index' => 6, 'label' => 'Ready! Redirecting...', 'weight' => 5 ),
        );

        if ( isset( $step_mapping[ $step ] ) ) {
            $step_info = $step_mapping[ $step ];

            // Calculate overall percentage
            $base_percent = 0;
            foreach ( $step_mapping as $s => $info ) {
                if ( $info['index'] < $step_info['index'] ) {
                    $base_percent += $info['weight'];
                }
            }

            // Add current step progress
            $step_percent     = ( $percent / 100 ) * $step_info['weight'];
            $overall_percent  = min( 100, $base_percent + $step_percent );

            $progress['current_step']  = $step_info['index'];
            $progress['current_label'] = $step_info['label'];
            $progress['percent']       = intval( $overall_percent );
            $progress['status']        = 'in_progress';

            // Mark completed steps
            for ( $i = 0; $i < $step_info['index']; $i++ ) {
                $progress['steps'][ $i ]['completed'] = true;
            }

            $this->save_progress( $progress );
        }
    }

    /**
     * Get current progress
     *
     * @return array Progress data.
     */
    public function get_progress() {
        if ( empty( $this->progress_key ) ) {
            return $this->get_initial_progress();
        }

        $progress = get_transient( $this->progress_key );

        if ( false === $progress ) {
            return $this->get_initial_progress();
        }

        return $progress;
    }

    /**
     * Save progress to transient
     *
     * Uses direct database writes to ensure progress is immediately
     * visible to concurrent AJAX polling requests.
     *
     * @param array $progress Progress data.
     */
    private function save_progress( $progress ) {
        if ( empty( $this->progress_key ) ) {
            return;
        }

        global $wpdb;

        // Use direct database write to bypass object cache.
        // This ensures progress is immediately visible to polling requests.
        $option_name  = '_transient_' . $this->progress_key;
        $timeout_name = '_transient_timeout_' . $this->progress_key;
        $expiration   = time() + 600; // 10 minutes.

        // Update or insert the transient value.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s",
                $option_name
            )
        );

        if ( $existing ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $wpdb->options,
                array( 'option_value' => maybe_serialize( $progress ) ),
                array( 'option_name' => $option_name ),
                array( '%s' ),
                array( '%s' )
            );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $wpdb->options,
                array( 'option_value' => (string) $expiration ),
                array( 'option_name' => $timeout_name ),
                array( '%s' ),
                array( '%s' )
            );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $timeout_name,
					'option_value' => (string) $expiration,
					'autoload'     => 'no',
				),
				array( '%s', '%s', '%s' )
			);
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $option_name,
					'option_value' => maybe_serialize( $progress ),
					'autoload'     => 'no',
				),
				array( '%s', '%s', '%s' )
			);
		}
	}

    /**
     * Get initial progress structure
     *
     * @return array Initial progress data.
     */
    private function get_initial_progress() {
        return array(
            'status'        => 'pending',
            'percent'       => 0,
            'current_step'  => 0,
            'current_label' => 'Initializing demo environment...',
            'steps'         => array(
                array( 'label' => 'Initializing demo environment...', 'completed' => false ),
                array( 'label' => 'Creating directory structure...', 'completed' => false ),
                array( 'label' => 'Copying WordPress files...', 'completed' => false ),
                array( 'label' => 'Setting up database...', 'completed' => false ),
                array( 'label' => 'Creating your demo account...', 'completed' => false ),
                array( 'label' => 'Finalizing configuration...', 'completed' => false ),
                array( 'label' => 'Ready! Redirecting...', 'completed' => false ),
            ),
            'error'         => null,
            'result'        => null,
        );
    }

    /**
     * Create a new demo instance
     *
     * @param string $user_email Optional user email to use as admin email in the clone.
     * @return array|WP_Error Result data or error.
     */
    public function create_demo( $user_email = '' ) {
        // Initialize progress
        $progress = $this->get_initial_progress();
        $progress['status'] = 'in_progress';
        $this->save_progress( $progress );

        $this->update_progress( 'init', 100 );

        // Step 1: Generate unique identifiers
        $clone_id  = DemoWP_Filesystem::generate_clone_id();
        $db_prefix = $this->database->generate_unique_prefix( $clone_id );

        // Step 2: Create directory
        $this->update_progress( 'directory', 0 );
        $clone_path = $this->filesystem->create_clone_directory( $clone_id );

        if ( is_wp_error( $clone_path ) ) {
            $this->set_error( $clone_path->get_error_message() );
            return $clone_path;
        }

        $this->update_progress( 'directory', 100 );

        // Step 3: Setup WordPress files
        $files_result = $this->filesystem->setup_wordpress_files( $clone_path );

        if ( is_wp_error( $files_result ) ) {
            // Rollback: delete directory
            $this->filesystem->delete_clone_directory( $clone_path );
            $this->set_error( $files_result->get_error_message() );
            return $files_result;
        }

        // Step 4: Clone database tables
        $db_result = $this->database->clone_tables( $db_prefix, $user_email );

        if ( is_wp_error( $db_result ) ) {
            // Rollback: delete directory
            $this->filesystem->delete_clone_directory( $clone_path );
            $this->set_error( $db_result->get_error_message() );
            return $db_result;
        }

        // Step 5: Create demo user
        $this->update_progress( 'user', 0 );
        $user_data = $this->database->create_demo_user( $db_prefix );
        $this->update_progress( 'user', 100 );

        // Step 6: Create wp-config.php
        $this->update_progress( 'config', 0 );
        $config_result = $this->filesystem->create_wp_config( $clone_path, $db_prefix, $clone_id );

        if ( is_wp_error( $config_result ) ) {
            // Rollback
            $this->database->delete_clone_tables( $db_prefix );
            $this->filesystem->delete_clone_directory( $clone_path );
            $this->set_error( $config_result->get_error_message() );
            return $config_result;
        }

        // Update site URLs in database
        $clone_url = home_url( $clone_id );
        $this->database->update_site_urls( $db_prefix, $clone_url );

        // Step 7: Generate auto-login token (store in clone's database)
        $autologin_token = DemoWP_Autologin::generate_token( $clone_id, $user_data['user_id'], $db_prefix );

        // Step 8: Register in tracker
        $lifetime   = (int) get_option( 'demowp_demo_lifetime', 3600 );
        $expires_at = gmdate( 'Y-m-d H:i:s', time() + $lifetime );

        $this->tracker->register_demo(
            array(
                'clone_id'   => $clone_id,
                'db_prefix'  => $db_prefix,
                'user_id'    => $user_data['user_id'],
                'username'   => $user_data['username'],
                'expires_at' => $expires_at,
            )
        );

        // Step 9: Schedule cleanup
        if ( DemoWP_Utils::has_action_scheduler() ) {
            as_schedule_single_action(
                time() + $lifetime,
                'demowp_cleanup_demo',
                array( 'clone_id' => $clone_id ),
                'demowp'
            );
        }

        $this->update_progress( 'config', 100 );

        // Complete!
        $this->update_progress( 'complete', 100 );

        $result = array(
            'success'         => true,
            'clone_id'        => $clone_id,
            'clone_url'       => $clone_url,
            'autologin_url'   => DemoWP_Autologin::get_autologin_url( $clone_id, $autologin_token ),
            'autologin_token' => $autologin_token,
            'username'        => $user_data['username'],
            'password'        => $user_data['password'],
            'expires_at'      => $expires_at,
            'lifetime'        => $lifetime,
        );

        // Store result in progress
        $progress             = $this->get_progress();
        $progress['status']   = 'complete';
        $progress['percent']  = 100;
        $progress['result']   = $result;

        // Mark all steps complete
        foreach ( $progress['steps'] as $i => $step ) {
            $progress['steps'][ $i ]['completed'] = true;
        }

        $this->save_progress( $progress );

        DemoWP_Utils::log( sprintf( 'Demo created: %s', $clone_id ), 'info' );

        return $result;
    }

    /**
     * Delete a demo instance
     *
     * @param string $clone_id The clone ID to delete.
     * @return bool True on success, false on failure.
     */
    public function delete_demo( $clone_id ) {
        if ( ! DemoWP_Utils::is_valid_clone_id( $clone_id ) ) {
            DemoWP_Utils::log( 'Invalid clone ID for deletion: ' . $clone_id, 'error' );
            return false;
        }

        // Get demo info from tracker
        $demo = $this->tracker->get_demo( $clone_id );

        if ( ! $demo ) {
            DemoWP_Utils::log( 'Demo not found in tracker: ' . $clone_id, 'warning' );
            // Still try to delete files/tables if they exist
        }

        $success = true;

        // Delete database tables
        if ( $demo && ! empty( $demo['db_prefix'] ) ) {
            $tables_deleted = $this->database->delete_clone_tables( $demo['db_prefix'] );
            DemoWP_Utils::log( sprintf( 'Deleted %d tables for demo %s', $tables_deleted, $clone_id ), 'info' );
        }

        // Delete directory
        $clone_path = DemoWP_Filesystem::get_clone_path( $clone_id );
        if ( file_exists( $clone_path ) ) {
            $dir_deleted = $this->filesystem->delete_clone_directory( $clone_path );
            if ( ! $dir_deleted ) {
                DemoWP_Utils::log( 'Failed to delete directory for demo: ' . $clone_id, 'error' );
                $success = false;
            }
        }

        // Mark as deleted in tracker (preserve for statistics)
        if ( $demo ) {
            $this->tracker->update_status( $clone_id, 'deleted' );
        }

        // Cancel scheduled cleanup
        if ( DemoWP_Utils::has_action_scheduler() ) {
            as_unschedule_action( 'demowp_cleanup_demo', array( 'clone_id' => $clone_id ), 'demowp' );
        }

        DemoWP_Utils::log( sprintf( 'Demo deleted: %s (success: %s)', $clone_id, $success ? 'yes' : 'no' ), 'info' );

        return $success;
    }

    /**
     * Set error in progress
     *
     * @param string $message Error message.
     */
    private function set_error( $message ) {
        if ( ! empty( $this->progress_key ) ) {
            $progress           = $this->get_progress();
            $progress['status'] = 'error';
            $progress['error']  = $message;
            $this->save_progress( $progress );
        }

        DemoWP_Utils::log( 'Demo creation error: ' . $message, 'error' );
    }
}
