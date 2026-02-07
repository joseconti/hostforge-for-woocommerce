<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/includes
 * @author     Jose Conti
 */

/**
 * The plugin activator class.
 *
 * This class is responsible for defining all the actions that should be taken
 * when the plugin is activated.
 *
 * @since      1.0.0
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/includes
 */
class ASWC_Activator {

	/**
	 * Activator function
	 *
	 * @param String $network_wide as network wide.
	 * @return void
	 */
	public static function subscriptions_for_woocommerce_activate( $network_wide ) {
							global $wpdb;

		// Load the scheduler API first - required for restoration to work.
		if ( ! class_exists( 'ASWC_Scheduler_API' ) ) {
			$scheduler_path = plugin_dir_path( __DIR__ ) . 'scheduler-api/scheduler.php';
			if ( file_exists( $scheduler_path ) ) {
				require_once $scheduler_path;
			}
		}

		if ( ! class_exists( 'ASWC_Schedule_Restorer' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'class-aswc-schedule-restorer.php';
		}

				$usage_tracking_hooks = array(
					'aswc_tracker_send_event',
					'wpswings_tracker_send_event',
				);

				if ( is_multisite() && $network_wide ) {
										// Get all blogs in the network and activate plugins on each one.
										// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
																										$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
					foreach ( $blog_ids as $blog_id ) {
									switch_to_blog( $blog_id );

																		// Previously scheduled events for usage tracking.
						foreach ( $usage_tracking_hooks as $hook_name ) {
								wp_clear_scheduled_hook( $hook_name );
						}

																		ASWC_Schedule_Restorer::schedule_restoration();
																		ASWC_Schedule_Restorer::schedule_on_hold_retries();

																		restore_current_blog();
					}
				} else {
																				// Remove any previously scheduled usage tracking events.
					foreach ( $usage_tracking_hooks as $hook_name ) {
							wp_clear_scheduled_hook( $hook_name );
					}

																				ASWC_Schedule_Restorer::schedule_restoration();
																				ASWC_Schedule_Restorer::schedule_on_hold_retries();
				}
	}
}
