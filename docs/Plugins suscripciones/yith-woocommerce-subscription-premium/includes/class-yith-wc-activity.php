<?php
/**
 * YITH_WC_Activity is an log of all transactions
 *
 * @class   YITH_WC_Activity
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Activity' ) ) {
	/**
	 * Class YITH_WC_Activity
	 */
	class YITH_WC_Activity {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Activity Table name.
		 *
		 * @var string
		 * @deprecated 3.0.0 Use $wpdb->ywsbs_activities_log
		 */
		public $table_name = '';

		/**
		 * Constructor
		 *
		 * Initialize class and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		private function __construct() {
			// Backward compatibility.
			global $wpdb;
			$this->table_name = $wpdb->ywsbs_activities_log;

			// remove activities if a subscription is deleted.
			add_action( 'deleted_post', array( $this, 'delete_activities' ) );
		}

		/**
		 * Add new activity
		 *
		 * Initialize class and registers actions and filters to be used.
		 *
		 * @param int    $subscription_id Subscription id.
		 * @param string $activity Activity.
		 * @param string $status Status.
		 * @param int    $order Order ID.
		 * @param string $description Description.
		 *
		 * @since 1.0.0
		 */
		public function add_activity( $subscription_id, $activity = '', $status = 'success', $order = 0, $description = '' ) {
			global $wpdb;

			$activity  = $this->get_activity( $activity );
			$order     = $order ? $order : 0;
			$post_date = current_time( 'mysql' );
			$data      = array(
				'activity'       => $activity,
				'status'         => $status,
				'subscription'   => $subscription_id,
				'order'          => $order,
				'description'    => esc_sql( $description ),
				'timestamp_date' => $post_date,
			);

			$wpdb->insert( $wpdb->ywsbs_activities_log, $data ); // phpcs:ignore
		}

		/**
		 * Fill the activity array.
		 *
		 * @deprecated
		 */
		public function fill_activities() {}

		/**
		 * Get activity by subscription.
		 *
		 * @param int $subscription_id Subscription ID.
		 * @param int $limit Limit of activities.
		 * @return array|null|object
		 */
		public function get_activity_by_subscription( $subscription_id, $limit = false ) {
			global $wpdb;

			if ( ! $limit ) {
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->ywsbs_activities_log} WHERE subscription = %d ORDER BY timestamp_date DESC ", $subscription_id ) );  // phpcs:ignore
			} else {
				$results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->ywsbs_activities_log} WHERE subscription = %d ORDER BY timestamp_date DESC LIMIT %d", $subscription_id, $limit ) );  // phpcs:ignore
			}

			return $results;
		}


		/**
		 * Remove the activities of subscription.
		 *
		 * @param int $subscription_id Subscription id.
		 *
		 * @return array|null|object
		 */
		public function remove_activities_of_subscription( $subscription_id ) {
			global $wpdb;

			$results = $wpdb->get_results( $wpdb->prepare( "DELETE FROM {$wpdb->ywsbs_activities_log} WHERE subscription = %d", $subscription_id ) );  // phpcs:ignore

			return $results;
		}

		/**
		 * Delete all activities of a subscription
		 *
		 * @param int $post_id Post ID.
		 */
		public function delete_activities( $post_id ) {
			$post = get_post( $post_id );
			if ( $post && YITH_YWSBS_POST_TYPE === $post->post_type ) {
				$this->remove_activities_of_subscription( $post_id );
			}
		}

		/**
		 * Check if there are activities on table
		 *
		 * @return bool
		 * @since 2.1.0
		 */
		public function is_activities_list_empty() {
			global $wpdb;
			$count = $wpdb->get_var( "SELECT count(0) AS c FROM {$wpdb->ywsbs_activities_log}" );  // phpcs:ignore
			return 0 === (int) $count;
		}

		/**
		 * Get an array of activities.
		 *
		 * @return array
		 */
		protected function get_activities() {
			return apply_filters(
				'ywsbs_subscription_activities',
				array(
					'new'            => esc_html_x( 'New subscription', 'new subscription has been created', 'yith-woocommerce-subscription' ),
					'renew-order'    => esc_html_x( 'Renewal order', 'new order has been created for the subscription', 'yith-woocommerce-subscription' ),
					'activated'      => esc_html_x( 'Subscription activated', '', 'yith-woocommerce-subscription' ),
					'trial'          => esc_html_x( 'Started trial period', '', 'yith-woocommerce-subscription' ),
					'cancelled'      => esc_html_x( 'Cancelled subscription', 'subscription cancelled by shop manager or customer', 'yith-woocommerce-subscription' ),
					'auto-cancelled' => esc_html_x( 'Subscription cancelled automatically', 'subscription cancelled by system', 'yith-woocommerce-subscription' ),
					'expired'        => esc_html_x( 'Subscription expired', 'subscription expired', 'yith-woocommerce-subscription' ),
					'switched'       => esc_html_x( 'Subscription switched to another subscription', 'subscription switched', 'yith-woocommerce-subscription' ),
					'resumed'        => esc_html_x( 'Subscription resumed', 'subscription resumed by shop manager or customer', 'yith-woocommerce-subscription' ),
					'auto-resumed'   => esc_html_x( 'Subscription resumed automatically', 'subscription resumed for expired pause', 'yith-woocommerce-subscription' ),
					'paused'         => esc_html_x( 'Subscription paused', 'subscription paused by shop manager or customer', 'yith-woocommerce-subscription' ),
					'suspended'      => esc_html_x( 'Subscription suspended', 'subscription suspended automatically due to non-payment', 'yith-woocommerce-subscription' ),
					'overdue'        => esc_html_x( 'Subscription overdue', 'subscription overdue automatically due to non-payment', 'yith-woocommerce-subscription' ),
					'failed-payment' => esc_html_x( 'Failed payment', 'subscription failed payment', 'yith-woocommerce-subscription' ),
					'trashed'        => esc_html_x( 'Subscription trashed', 'subscription was trashed', 'yith-woocommerce-subscription' ),
					'changed'        => esc_html_x( 'Subscription changed', 'subscription was changed', 'yith-woocommerce-subscription' ),
					'email_sent'     => esc_html_x( 'Email sent', 'an email was sent to inform the customer', 'yith-woocommerce-subscription' ),
				)
			);
		}

		/**
		 * Get a single activity
		 *
		 * @param string $key The activity key.
		 * @return string
		 */
		protected function get_activity( $key ) {
			$activities = $this->get_activities();
			return $activities[ $key ] ?? '';
		}
	}
}

/**
 * Unique access to instance of YITH_WC_Activity class
 *
 * @return YITH_WC_Activity
 */
function YITH_WC_Activity() { //phpcs:ignore
	return YITH_WC_Activity::get_instance();
}
