<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Delivery_Schedules_DB Object. Handle all delivery schedule DB stuff!
 *
 * @class   YWSBS_Subscription_Delivery_Schedules_DB
 * @since   2.2.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Delivery_Schedules_DB' ) ) {

	/**
	 * Class YWSBS_Subscription_Delivery_Schedules_DB
	 */
	class YWSBS_Subscription_Delivery_Schedules_DB {

		/**
		 * Init class
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function init() {
			// Define tables alias.
			self::define_table();
			add_action( 'switch_blog', array( __CLASS__, 'define_table' ), 0 );
		}

		/**
		 * Define table alias
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function define_table() {
			global $wpdb;

			$wpdb->ywsbs_delivery_schedules = $wpdb->prefix . 'yith_ywsbs_delivery_schedules';
			$wpdb->tables[]                 = 'yith_ywsbs_delivery_schedules';
		}

		/**
		 * Check if there are delivery schedules on table
		 *
		 * @since 3.0.0
		 * @return bool
		 */
		public static function is_table_empty() {
			global $wpdb;
			$count = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->ywsbs_delivery_schedules}" );  // phpcs:ignore

			return 0 === absint( $count );
		}

		/**
		 * Return all the schedule
		 *
		 * @since  3.0.0
		 * @return array|object|null
		 */
		public static function get_processing_delivery_schedules() {
			global $wpdb;

			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->ywsbs_delivery_schedules} WHERE status = %s ORDER BY id DESC ", 'processing' ) ); // phpcs:ignore
		}

		/**
		 * Delete the delivery schedules from the table when a subscription is deleted.
		 *
		 * @since  3.0.0
		 * @param int $subscription_id Subscription id.
		 */
		public static function delete_delivery_by_subscription( $subscription_id ) {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->ywsbs_delivery_schedules} WHERE subscription_id = %d", $subscription_id ) ); // phpcs:ignore
		}

		/**
		 * Return the delivery schedules of a subscription.
		 *
		 * @since  3.0.0
		 * @param int $delivery_id Delivery id.
		 * @return array
		 */
		public static function get_delivery_schedules_by_id( $delivery_id ) {
			global $wpdb;

			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->ywsbs_delivery_schedules} WHERE id = %d", $delivery_id ) );  // phpcs:ignore;
		}

		/**
		 * Return the delivery schedules of a subscription.
		 *
		 * @since  3.0.0
		 * @param int          $subscription_id Subscription id.
		 * @param string|array $status          Single status or an array of statuses.
		 * @param int|bool     $limit           Limit quantity.
		 * @return array
		 */
		public static function get_delivery_schedules_by_subscription( $subscription_id, $status = '', $limit = false ) {
			global $wpdb;

			$where_conditions = $wpdb->prepare( 'subscription_id = %d', $subscription_id );
			if ( ! empty( $status ) ) {
				$status            = is_array( $status ) ? array_map( 'trim', $status ) : array( $status );
				$status            = implode( "', '", $status );
				$where_conditions .= " AND status IN ('{$status}')"; // phpcs:ignore
			}

			$limit_condition = '';
			if ( ! empty( $limit ) ) {
				$limit_condition = $wpdb->prepare( 'LIMIT %d', $limit );
			}

			return $wpdb->get_results( "SELECT * FROM {$wpdb->ywsbs_delivery_schedules} WHERE {$where_conditions} ORDER BY scheduled_date ASC {$limit_condition}" ); // phpcs:ignore
		}

		/**
		 * Return the delivery schedules of a subscription.
		 *
		 * @since  3.0.0
		 * @param integer         $subscription_id Subscription id.
		 * @param integer|boolean $limit           Limit number of delivery to retrieve.
		 */
		public static function get_delivery_schedules_ordered( $subscription_id, $limit = false ) {
			global $wpdb;

			$query = $wpdb->prepare( "SELECT * FROM {$wpdb->ywsbs_delivery_schedules} WHERE subscription_id = %d ORDER BY FIELD( status, 'processing','waiting','shipped','cancelled'), scheduled_date ASC", $subscription_id );
			if ( ! empty( $limit ) ) {
				$query .= $wpdb->prepare( ' LIMIT %d', $limit );
			}

			return $wpdb->get_results( $query );  // phpcs:ignore;
		}

		/**
		 * Add new delivery schedule inside the table
		 *
		 * @since  3.0.0
		 * @param int    $subscription_id Subscription id.
		 * @param string $schedule_date   Schedule date.
		 * @param string $status          Status.
		 * @return integer
		 */
		public static function add_delivery_schedules( $subscription_id, $schedule_date, $status = 'waiting' ) {
			global $wpdb;

			$data = array(
				'subscription_id' => $subscription_id,
				'status'          => $status,
				'entry_date'      => current_time( 'mysql' ),
				'scheduled_date'  => wp_date( 'Y-m-d H:i:s', $schedule_date ),
			);

			$wpdb->insert( $wpdb->ywsbs_delivery_schedules, $data ); // phpcs:ignore

			return $wpdb->insert_id;
		}

		/**
		 * Create table
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function create_table() {
			global $wpdb;

			$wpdb->hide_errors();
			$charset_collate = $wpdb->get_charset_collate();

			$table_name = $wpdb->prefix . 'yith_ywsbs_delivery_schedules';

			$sql = "CREATE TABLE $table_name (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `subscription_id` bigint(20),
                    `status` varchar(20),
                    `entry_date` datetime NOT NULL,
                    `scheduled_date` datetime NOT NULL,
                    `sent_on` datetime,
                    PRIMARY KEY (id)
                    ) $charset_collate;";

			if ( ! function_exists( 'dbDelta' ) ) {
				include_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}
			dbDelta( $sql );
		}
	}
}
