<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Delivery_Schedules_Legacy Object.
 *
 * @class   YWSBS_Subscription_Delivery_Schedules_Legacy
 * @package YITH\Subscription
 * @since   3.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Delivery_Schedules_Legacy' ) ) {

	/**
	 * Class YWSBS_Subscription_Delivery_Schedules_Legacy
	 */
	abstract class YWSBS_Subscription_Delivery_Schedules_Legacy {

		/**
		 * Magic get method
		 *
		 * @param string $key The prop key.
		 * @return mixed
		 */
		public function __get( $key ) {
			global $wpdb;
			if ( 'table_name' === $key ) {
				return $wpdb->prefix . 'yith_ywsbs_delivery_schedules';
			}

			return $this->$key;
		}

		/**
		 * Check if there are delivery schedules on table
		 *
		 * @return bool
		 * @since 2.2.0
		 * @deprecated
		 */
		public function is_delivery_schedules_table_empty() {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Delivery_Schedules_DB::is_table_empty' );
			return YWSBS_Subscription_Delivery_Schedules_DB::is_table_empty();
		}

		/**
		 * Return all the schedule
		 *
		 * @return array|object|null
		 * @deprecated
		 */
		public function get_processing_delivery_schedules() {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Delivery_Schedules_DB::get_processing_delivery_schedules' );
			return YWSBS_Subscription_Delivery_Schedules_DB::get_processing_delivery_schedules();
		}

		/**
		 * Delete the delivery schedules from the table when a subscription is deleted.
		 *
		 * @param int $subscription_id Subscription id.
		 * @deprecated
		 */
		public function delete_delivery_status_of_a_subscription( $subscription_id ) {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Delivery_Schedules_DB::delete_delivery_by_subscription' );
			YWSBS_Subscription_Delivery_Schedules_DB::delete_delivery_by_subscription( $subscription_id );
		}

		/**
		 * Return the delivery schedules of a subscription.
		 *
		 * @param int $delivery_id Delivery id.
		 * @deprecated
		 */
		public function get_delivery_schedules_by_id( $delivery_id ) {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_by_id' );
			return YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_by_id( $delivery_id );
		}

		/**
		 * Return the delivery schedules of a subscription.
		 *
		 * @param int      $subscription_id Subscription id.
		 * @param string   $status Status.
		 * @param int|bool $limit Limit quantity.
		 * @deprecated
		 */
		public function get_delivery_schedules_by_subscription( $subscription_id, $status = '', $limit = false ) {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_by_subscription' );
			return YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_by_subscription( $subscription_id, $status, $limit );
		}

		/**
		 * Return the delivery schedules of a subscription.
		 *
		 * @param int $subscription_id Subscription id.
		 * @deprecated
		 */
		public function get_delivery_schedules_ordered( $subscription_id ) {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_ordered' );
			return YWSBS_Subscription_Delivery_Schedules_DB::get_delivery_schedules_ordered( $subscription_id );
		}

		/**
		 * Add new delivery schedule inside the table
		 *
		 * @param int    $subscription_id Subscription id.
		 * @param string $schedule_date Schedule date.
		 * @param string $status Status.
		 *
		 * @since 2.2.0
		 * @deprecated
		 */
		public function add_delivery_schedules( $subscription_id, $schedule_date, $status = 'waiting' ) {
			_deprecated_function( __METHOD__, '3.0.0', 'YWSBS_Subscription_Delivery_Schedules_DB::add_delivery_schedules' );
			YWSBS_Subscription_Delivery_Schedules_DB::add_delivery_schedules( $subscription_id, $schedule_date, $status );
		}

		/**
		 * Update the status of the delivery schedules.
		 */
		public function set_status_to_delivery_schedules() {
			global $wpdb;

			$timestamp = time() - DAY_IN_SECONDS;
			$data      = gmdate( 'Y-m-d H:i:s', $timestamp );
			$now       = gmdate( 'Y-m-d H:i:s', time() );

			$q = $wpdb->prepare(
				"Update {$this->table_name} as ds
			LEFT JOIN {$wpdb->postmeta} as pm on ds.subscription_id = pm.post_id SET  status = 'processing' WHERE (pm.meta_key = 'status' and pm.meta_value NOT IN ('cancelled', 'paused' ) ) AND scheduled_date <= %s AND scheduled_date >= %s AND status NOT LIKE %s",
				$now,
				$data,
				'shipped'
			);

			$wpdb->get_results( $q );  // phpcs:ignore
		}
	}
}
