<?php 
/**
 * YWSBS_Subscription_Post_Type_Admin_Legacy Class.
 *
 * Manage the subscription post type in admin.
 *
 * @class   YWSBS_Subscription_Post_Type_Admin_Legacy
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

// phpcs:disable WordPress.Security.NonceVerification.Missing
if ( ! class_exists( 'YWSBS_Subscription_Post_Type_Admin_Legacy' ) ) {

	/**
	 * Class YWSBS_Subscription_Post_Type_Admin
	 */
	abstract class YWSBS_Subscription_Post_Type_Admin_Legacy {

		/**
		 * Add the metabox to show the product of subscription
		 *
		 * @access public
		 *
		 * @param string $post_type Post type.
		 * @param object $post WP_Post.
		 *
		 * @return void
		 * @since  1.0.0
		 * @deprecated
		 */
		public function show_subscription_delivery_schedules( $post_type, $post ) {
			_deprecated_function( __METHOD__, '3.0.0' );
			function_exists( 'YWSBS_Subscription_Delivery_Schedules' ) && YWSBS_Subscription_Delivery_Schedules()->admin->delivery_schedules_metabox( $post_type, $post );
		}

		/**
		 * Delivery schedules metabox
		 *
		 * @param WP_Post $post Current post.
		 */
		public function show_delivery_schedules_metabox( $post ) {
			_deprecated_function( __METHOD__, '3.0.0' );
			function_exists( 'YWSBS_Subscription_Delivery_Schedules' ) && YWSBS_Subscription_Delivery_Schedules()->admin->output_metabox( $post );
		}
	}
}
