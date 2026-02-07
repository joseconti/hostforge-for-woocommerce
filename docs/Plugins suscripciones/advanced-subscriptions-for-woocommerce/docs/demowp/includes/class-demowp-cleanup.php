<?php
/**
 * Cleanup System
 *
 * Handles automatic cleanup of expired demos using Action Scheduler.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Cleanup
 *
 * Manages automatic cleanup of expired demo installations.
 *
 * @since 1.0.0
 */
class DemoWP_Cleanup {

    /**
     * Constructor
     */
    public function __construct() {
        // Register cleanup action handler.
        add_action( 'demowp_cleanup_demo', array( $this, 'cleanup_demo' ) );

        // Register emergency cleanup handler.
        add_action( 'demowp_emergency_cleanup', array( $this, 'emergency_cleanup' ) );

        // Register old records cleanup handler.
        add_action( 'demowp_cleanup_old_records', array( $this, 'cleanup_old_records' ) );

        // Schedule recurring actions AFTER Action Scheduler is fully initialized.
        // Using 'init' with late priority ensures AS data store is ready.
        add_action( 'init', array( $this, 'maybe_schedule_recurring_actions' ), 99 );
    }

    /**
     * Schedule recurring actions if not already scheduled.
     *
     * This runs on 'init' to ensure Action Scheduler is fully initialized.
     */
    public function maybe_schedule_recurring_actions() {
        $this->schedule_emergency_cleanup();
        $this->schedule_old_records_cleanup();
    }

    /**
     * Schedule emergency cleanup
     */
    private function schedule_emergency_cleanup() {
        if ( ! DemoWP_Utils::has_action_scheduler() ) {
            return;
        }

        // Check if already scheduled
        if ( as_has_scheduled_action( 'demowp_emergency_cleanup', array(), 'demowp' ) ) {
            return;
        }

        // Schedule to run every hour
        as_schedule_recurring_action(
            time() + HOUR_IN_SECONDS,
            HOUR_IN_SECONDS,
            'demowp_emergency_cleanup',
            array(),
            'demowp'
        );

        DemoWP_Utils::log( 'Scheduled emergency cleanup action', 'info' );
    }

    /**
     * Schedule old records cleanup
     */
    private function schedule_old_records_cleanup() {
        if ( ! DemoWP_Utils::has_action_scheduler() ) {
            return;
        }

        // Check if already scheduled
        if ( as_has_scheduled_action( 'demowp_cleanup_old_records', array(), 'demowp' ) ) {
            return;
        }

        // Schedule to run daily
        as_schedule_recurring_action(
            time() + DAY_IN_SECONDS,
            DAY_IN_SECONDS,
            'demowp_cleanup_old_records',
            array(),
            'demowp'
        );
    }

    /**
     * Cleanup a specific demo
     *
     * @param string $clone_id The clone ID to cleanup.
     */
    public function cleanup_demo( $clone_id ) {
        DemoWP_Utils::log( sprintf( 'Starting cleanup for demo: %s', $clone_id ), 'info' );

        $cloner = new DemoWP_Cloner();
        $result = $cloner->delete_demo( $clone_id );

        if ( $result ) {
            DemoWP_Utils::log( sprintf( 'Successfully cleaned up demo: %s', $clone_id ), 'info' );
        } else {
            DemoWP_Utils::log( sprintf( 'Failed to cleanup demo: %s', $clone_id ), 'error' );
        }
    }

    /**
     * Emergency cleanup - find and remove expired demos
     */
    public function emergency_cleanup() {
        DemoWP_Utils::log( 'Running emergency cleanup', 'info' );

        $tracker = new DemoWP_Demo_Tracker();

        // Get expired demos from tracker
        $expired_demos = $tracker->get_expired_demos();

        DemoWP_Utils::log( sprintf( 'Found %d expired demos', count( $expired_demos ) ), 'info' );

        foreach ( $expired_demos as $demo ) {
            $this->cleanup_demo( $demo['clone_id'] );
        }

        // Also check for orphaned directories
        $this->cleanup_orphaned_directories();
    }

    /**
     * Cleanup orphaned directories (directories without tracker entries)
     */
    private function cleanup_orphaned_directories() {
        $tracker    = new DemoWP_Cloner();
        $filesystem = new DemoWP_Filesystem();

        // Find directories with .demowp-clone marker
        $pattern = ABSPATH . '*/.demowp-clone';
        $markers = glob( $pattern );

        if ( ! $markers ) {
            return;
        }

        $tracker_instance = new DemoWP_Demo_Tracker();

        foreach ( $markers as $marker_file ) {
            $clone_path = dirname( $marker_file );
            $clone_id   = basename( $clone_path );

            // Check if it's a valid clone ID
            if ( ! DemoWP_Utils::is_valid_clone_id( $clone_id ) ) {
                continue;
            }

            // Check if it exists in tracker
            $demo = $tracker_instance->get_demo( $clone_id );

            if ( ! $demo ) {
                // Orphaned directory - check if it's old enough
                $marker_content = file_get_contents( $marker_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                $marker_data    = json_decode( $marker_content, true );

                if ( is_array( $marker_data ) && isset( $marker_data['created'] ) ) {
                    $age = time() - $marker_data['created'];

                    // If older than 2 hours and not in tracker, delete
                    if ( $age > 2 * HOUR_IN_SECONDS ) {
                        DemoWP_Utils::log( sprintf( 'Removing orphaned directory: %s', $clone_id ), 'info' );
                        $filesystem->delete_clone_directory( $clone_path );
                    }
                }
            }
        }
    }

    /**
     * Cleanup old records from tracker table
     */
    public function cleanup_old_records() {
        $tracker = new DemoWP_Demo_Tracker();
        $deleted = $tracker->cleanup_old_records();

        if ( $deleted > 0 ) {
            DemoWP_Utils::log( sprintf( 'Cleaned up %d old tracker records', $deleted ), 'info' );
        }
    }

    /**
     * Schedule cleanup for a specific demo
     *
     * @param string $clone_id The clone ID.
     * @param int    $delay    Delay in seconds (default: use option).
     */
    public static function schedule_cleanup( $clone_id, $delay = null ) {
        if ( ! DemoWP_Utils::has_action_scheduler() ) {
            return false;
        }

        if ( null === $delay ) {
            $delay = (int) get_option( 'demowp_demo_lifetime', 3600 );
        }

        $scheduled = as_schedule_single_action(
            time() + $delay,
            'demowp_cleanup_demo',
            array( 'clone_id' => $clone_id ),
            'demowp'
        );

        if ( $scheduled ) {
            DemoWP_Utils::log(
                sprintf( 'Scheduled cleanup for demo %s in %d seconds', $clone_id, $delay ),
                'info'
            );
        }

        return $scheduled;
    }

    /**
     * Cancel scheduled cleanup for a demo
     *
     * @param string $clone_id The clone ID.
     * @return bool True if cancelled, false otherwise.
     */
    public static function cancel_cleanup( $clone_id ) {
        if ( ! DemoWP_Utils::has_action_scheduler() ) {
            return false;
        }

        $cancelled = as_unschedule_action(
            'demowp_cleanup_demo',
            array( 'clone_id' => $clone_id ),
            'demowp'
        );

        if ( $cancelled ) {
            DemoWP_Utils::log( sprintf( 'Cancelled cleanup for demo: %s', $clone_id ), 'info' );
        }

        return (bool) $cancelled;
    }

    /**
     * Get next scheduled cleanup time for a demo
     *
     * @param string $clone_id The clone ID.
     * @return int|false Timestamp or false if not scheduled.
     */
    public static function get_next_cleanup_time( $clone_id ) {
        if ( ! DemoWP_Utils::has_action_scheduler() ) {
            return false;
        }

        return as_next_scheduled_action(
            'demowp_cleanup_demo',
            array( 'clone_id' => $clone_id ),
            'demowp'
        );
    }

	/**
	 * Force cleanup all demos (for emergency/testing)
	 *
	 * Skips blocked demos.
	 *
	 * @return int Number of demos cleaned.
	 */
	public static function force_cleanup_all() {
		$tracker = new DemoWP_Demo_Tracker();
		$demos   = $tracker->get_active_demos();
		$cleaned = 0;

		foreach ( $demos as $demo ) {
			// Skip blocked demos.
			if ( isset( $demo['blocked'] ) && 1 === (int) $demo['blocked'] ) {
				continue;
			}

			$cloner = new DemoWP_Cloner();
			if ( $cloner->delete_demo( $demo['clone_id'] ) ) {
				++$cleaned;
			}
		}

		DemoWP_Utils::log( sprintf( 'Force cleaned %d demos', $cleaned ), 'info' );

		return $cleaned;
	}
}
