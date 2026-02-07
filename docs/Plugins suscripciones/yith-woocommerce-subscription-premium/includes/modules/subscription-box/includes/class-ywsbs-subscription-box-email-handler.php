<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Email_Handler Class.
 * Handle the cart for module "subscription box"
 *
 * @class   YWSBS_Subscription_Email_Handler
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_Email_Handler' ) ) {
	/**
	 * Class YWSBS_Subscription_Box_Order
	 */
	class YWSBS_Subscription_Box_Email_Handler {

		/**
		 * Init
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public static function init() {
			add_filter( 'woocommerce_email_classes', array( __CLASS__, 'register_email' ), 10, 1 );
			// Listen subscription creation.
			add_action( 'ywsbs_subscription_payment_complete', array( __CLASS__, 'handle_subscription_payment_complete' ), 10, 1 );
			// Listen delete subscription.
			add_action( 'ywsbs_subscription_cancelled', array( __CLASS__, 'maybe_delete_scheduled_email' ), 10, 1 );
			add_action( 'deleted_post', array( __CLASS__, 'maybe_delete_scheduled_email' ), 10, 1 );
			// Handle email action.
			add_action( 'ywsbs_send_subscription_box_email', array( __CLASS__, 'send_box_email' ), 10, 1 );
		}

		/**
		 * Register module email
		 *
		 * @since  4.0.0
		 * @param array $emails WooCommerce email list.
		 * @return array
		 */
		public static function register_email( $emails ) {
			$emails['YWSBS_Subscription_Box_Email'] = include YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'includes/emails/class-ywsbs-subscription-box-email.php';
			return $emails;
		}

		/**
		 * Handle send subscription box mail
		 * Subscription must be a BOX subscription and have active status.
		 *
		 * @since  4.0.0
		 * @param integer $subscription_id The subscription ID.
		 * @return void
		 */
		public static function send_box_email( $subscription_id ) {
			$subscription = self::get_subscription( $subscription_id );
			if ( ! ywsbs_is_a_box_subscription( $subscription ) || ! $subscription->has_status( 'active' ) ) {
				return;
			}

			WC()->mailer();
			do_action( 'ywsbs_subscription_box_mail_notification', $subscription );
		}

		/**
		 * Schedule box email on subscription payment completed.
		 *
		 * @since  4.0.0
		 * @param YWSBS_Subscription $subscription The subscription object.
		 * @return void
		 */
		public static function handle_subscription_payment_complete( $subscription ) {
			$subscription = self::get_subscription( $subscription );
			if ( ! ywsbs_is_a_box_subscription( $subscription ) ) {
				return;
			}

			$subscription_id = $subscription->get_id();
			// Make sure subscription has no scheduled action.
			// If an event schedules is found, remove it. Always schedule event with the most recent dates.
			if ( as_next_scheduled_action( 'ywsbs_send_subscription_box_email', null, "subscription_{$subscription_id}" ) ) {
				self::unschedule( $subscription_id );
			}

			// Otherwise set next action using next_payment_due_date.
			self::schedule( $subscription->get_id(), self::calculate_event_time( $subscription, $subscription->get_next_payment_due_date() ) );
		}

		/**
		 * Maybe delete schedules action on delete subscription
		 *
		 * @since  4.0.0
		 * @param integer $post_id Post deleted.
		 * @return void
		 */
		public static function maybe_delete_scheduled_email( $post_id ) {
			$post = get_post( $post_id );
			if ( empty( $post ) || YITH_YWSBS_POST_TYPE !== $post->post_type ) {
				return;
			}

			self::unschedule( $post_id );
		}

		/**
		 * Get the subscription object.
		 *
		 * @since  4.0.0
		 * @param integer|YWSBS_Subscription $subscription The subscription ID or the subscription object.
		 * @return YWSBS_Subscription
		 */
		protected static function get_subscription( $subscription ) {
			if ( ! $subscription instanceof YWSBS_Subscription ) {
				$subscription = ywsbs_get_subscription( absint( $subscription ) );
			}

			return $subscription;
		}

		/**
		 * Calculate schedule run time
		 *
		 * @since  4.0.0
		 * @param integer |YWSBS_Subscription $subscription The subscription ID or the subscription object.
		 * @param integer                     $timestamp    The timestamp to use for calculate.
		 * @return integer
		 */
		protected static function calculate_event_time( $subscription, $timestamp ) {
			$subscription = self::get_subscription( $subscription );
			$box_options  = $subscription->get( 'box_options' );
			return $timestamp - ( $box_options['email_schedule_before'] * DAY_IN_SECONDS );
		}

		/**
		 * Schedule a single email action
		 *
		 * @since  4.0.0
		 * @param integer $subscription_id Job related subscription id.
		 * @param integer $timestamp       When the job will run.
		 * @param array   $args            An array of additional arguments.
		 * @return void
		 */
		protected static function schedule( $subscription_id, $timestamp, $args = array() ) {
			// Create arguments.
			$args = array_merge(
				array(
					'subscription_id' => $subscription_id,
				),
				$args
			);

			as_schedule_single_action( $timestamp, 'ywsbs_send_subscription_box_email', $args, "subscription_{$subscription_id}" );
		}

		/**
		 * Schedule a single email action
		 *
		 * @since  4.0.0
		 * @param integer $subscription_id Job related subscription id.
		 * @return void
		 */
		protected static function unschedule( $subscription_id ) {
			as_unschedule_all_actions( '', null, "subscription_{$subscription_id}" );
		}
	}
}
