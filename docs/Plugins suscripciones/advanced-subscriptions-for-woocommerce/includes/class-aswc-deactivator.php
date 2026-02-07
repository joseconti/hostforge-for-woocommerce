<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/includes
 */
class ASWC_Deactivator {

		/**
		 * Desactivator function.
		 *
		 * @param string $network_wide as network wide.
		 * @return void
		 */
	public static function subscriptions_for_woocommerce_deactivate( $network_wide ) {
			global $wpdb;
				$usage_tracking_hooks = array(
					'aswc_tracker_send_event',
					'wpswings_tracker_send_event',
				);

				if ( is_multisite() && $network_wide ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
					foreach ( $blog_ids as $blog_id ) {
						switch_to_blog( $blog_id );
						if ( class_exists( 'ASWC_Scheduler_API' ) ) {
								ASWC_Scheduler_API::unschedule_all_groups();
						}
						foreach ( $usage_tracking_hooks as $hook_name ) {
								wp_clear_scheduled_hook( $hook_name );
						}
						delete_option( 'aswc_restore_schedule_offset' );
						restore_current_blog();
					}
				} else {
					if ( class_exists( 'ASWC_Scheduler_API' ) ) {
							ASWC_Scheduler_API::unschedule_all_groups();
					}
					foreach ( $usage_tracking_hooks as $hook_name ) {
							wp_clear_scheduled_hook( $hook_name );
					}
						delete_option( 'aswc_restore_schedule_offset' );
				}
	}
}
