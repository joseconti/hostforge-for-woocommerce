<?php
/**
 * Implements helper functions for YITH WooCommerce Subscription
 *
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

add_action( 'admin_init', 'ywsbs_schedule_report_import', 30 );
if ( ! function_exists( 'ywsbs_schedule_report_import' ) ) {
	/**
	 * Schedule the import report
	 */
	function ywsbs_schedule_report_import() {
		$ywsbs_option_version = get_option( 'ywsbs_schedule_report_import', '2.2.0' );

		if ( version_compare( $ywsbs_option_version, '2.3.0', '<' ) ) {

			$schedule_info = array(
				'hook' => 'ywsbs_import_subscriptions',
				'args' => array(
					'limit' => 10,
					'page'  => 1,
				),
			);

			$has_hook_scheduled = as_next_scheduled_action( $schedule_info['hook'], $schedule_info['args'] );

			if ( ! $has_hook_scheduled ) {
				as_schedule_single_action( time() + 5, $schedule_info['hook'], $schedule_info['args'] );
			}
		}

		update_option( 'ywsbs_schedule_report_import', '2.3.0', 1 );
	}
}
