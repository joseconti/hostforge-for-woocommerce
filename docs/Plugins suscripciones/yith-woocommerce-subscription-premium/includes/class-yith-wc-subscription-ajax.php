<?php 
/**
 * Implements Ajax calls of YITH WooCommerce Subscription
 *
 * @class   YITH_WC_Subscription_Ajax
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
if ( ! class_exists( 'YITH_WC_Subscription_Ajax' ) ) {
	/**
	 * Handle the assets
	 *
	 * @class YITH_WC_Subscription_Ajax
	 */
	class YITH_WC_Subscription_Ajax {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * YITH_WC_Subscription_Ajax constructor.
		 */
		private function __construct() {
			$ajax_actions = array(
				'save_items',
				'recalculate',
				'cancel_subscription',
				'resume_subscription',
				'pause_subscription',
				'json_search_ywsbs_products',
				'search_categories',
				'update_delivery_status',
				'remove_subscription_coupon',
				'add_subscription_coupon',
				'module_activation_switch',
			);

			foreach ( $ajax_actions as $ajax_action ) {
				if ( ! method_exists( $this, $ajax_action ) ) {
					continue;
				}

				add_action( 'wp_ajax_ywsbs_' . $ajax_action, array( $this, $ajax_action ) );
				add_action( 'wp_ajax_nopriv_ywsbs_' . $ajax_action, array( $this, $ajax_action ) );
			}
		}

		/**
		 * Pause subscription
		 */
		public function pause_subscription() {

			check_ajax_referer( 'ywsbs_pause_subscription', 'security' );

			$posted       = $_POST;
			$subscription = false;

			if ( ! empty( $posted['subscription_id'] ) ) {
				$subscription = ywsbs_get_subscription( $posted['subscription_id'] );
			}

			if ( ! $subscription || empty( $posted['change_status'] ) ) {
				wp_send_json(
					array(
						'error' => sprintf( __( 'Error: Subscription not found or it is not possible to complete your request.', 'yith-woocommerce-subscription' ) ),
					)
				);
			}

			if ( get_current_user_id() !== $subscription->get_user_id() ) {
				wp_send_json(
					array(
						'error' => sprintf( __( 'You cannot change the status of this subscription.', 'yith-woocommerce-subscription' ) ),
					)
				);
			}

			YITH_WC_Subscription()->manual_change_status( 'paused', $subscription, 'customer' );

			// translators: subscription id.
			wp_send_json(
				array(
					// translators: subscription number.
					'success' => sprintf( esc_html__( 'The subscription %s has been paused.', 'yith-woocommerce-subscription' ), $subscription->get_number() ),
				)
			);
		}

		/**
		 * Cancel subscription
		 */
		public function cancel_subscription() {

			check_ajax_referer( 'ywsbs_cancel_subscription', 'security' );

			$posted       = $_POST;
			$subscription = false;

			if ( ! empty( $posted['subscription_id'] ) ) {
				$subscription = ywsbs_get_subscription( $posted['subscription_id'] );
			}

			if ( ! $subscription || empty( $posted['change_status'] ) ) {
				wp_send_json(
					array(
						'error' => sprintf( __( 'Error: Subscription not found or it is not possible to complete your request.', 'yith-woocommerce-subscription' ) ),
					)
				);
			}

			if ( get_current_user_id() !== $subscription->get_user_id() ) {
				wp_send_json(
					array(
						'error' => sprintf( __( 'You cannot change the status of this subscription.', 'yith-woocommerce-subscription' ) ),
					)
				);
			}

			YITH_WC_Subscription()->manual_change_status( 'cancelled', $subscription, 'customer' );

			wp_send_json(
				array(
					// translators: subscription number.
					'success' => sprintf( esc_html__( 'The subscription %s has been cancelled.', 'yith-woocommerce-subscription' ), $subscription->get_number() ),
				)
			);
		}

		/**
		 * Resume subscription
		 */
		public function resume_subscription() {

			check_ajax_referer( 'ywsbs_resume_subscription', 'security' );

			$posted       = $_POST;
			$subscription = false;

			if ( ! empty( $posted['subscription_id'] ) ) {
				$subscription = ywsbs_get_subscription( $posted['subscription_id'] );
			}

			if ( ! $subscription || empty( $posted['change_status'] ) ) {
				wp_send_json(
					array(
						'error' => sprintf( __( 'Error: Subscription not found or it is not possible to complete your request.', 'yith-woocommerce-subscription' ) ),
					)
				);
			}

			if ( get_current_user_id() !== $subscription->get_user_id() ) {
				wp_send_json(
					array(
						'error' => sprintf( __( 'You cannot change the status of this subscription.', 'yith-woocommerce-subscription' ) ),
					)
				);
			}

			YITH_WC_Subscription()->manual_change_status( 'resumed', $subscription, 'customer' );

			wp_send_json(
				array(
					// translators: subscription number.
					'success' => sprintf( esc_html__( 'The subscription %s has been resumed.', 'yith-woocommerce-subscription' ), $subscription->get_number() ),
				)
			);
		}

		/**
		 * Save a new amount on subscription from subscription detail.
		 *
		 * @since 1.4.5
		 */
		public function save_items() {
			check_ajax_referer( 'save-item-nonce', 'security' );

			if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_REQUEST['subscription_id'] ) ) {
				wp_die( -1 );
			}
			if ( isset( $_REQUEST['items'] ) ) {
				parse_str( $_REQUEST['items'], $posted );
				$subscription = ywsbs_get_subscription( $_REQUEST['subscription_id'] );

				do_action( 'ywsbs_before_ajax_save_item_process', $subscription, $posted );

				$subscription->update_prices( $posted );

				do_action( 'ywsbs_after_ajax_save_item_process', $subscription, $posted );

				include YITH_YWSBS_VIEWS_PATH . '/metabox/subscription-product.php';
			}

			wp_die();
		}

		/**
		 * Recalculate the taxes from the total amounts.
		 *
		 * @since 1.4.5
		 */
		public function recalculate() {
			check_ajax_referer( 'recalculate_nonce', 'security' );

			if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_REQUEST['subscription_id'] ) ) { //phpcs:ignore
				wp_die( -1 );
			}

			$subscription = ywsbs_get_subscription( $_REQUEST['subscription_id'] );
			$subscription->recalculate_prices();

			include YITH_YWSBS_VIEWS_PATH . '/metabox/subscription-product.php';
			wp_die();
		}

		/**
		 * Search products.
		 *
		 * @throws Exception Throws Exception.
		 */
		public function json_search_ywsbs_products() {

			check_ajax_referer( 'search-products', 'security' );

			if ( empty( $term ) && isset( $_GET['term'] ) ) {
				$term = (string) wc_clean( wp_unslash( $_GET['term'] ) );
			}

			if ( empty( $term ) ) {
				wp_die();
			}

			if ( ! empty( $_GET['limit'] ) ) {
				$limit = absint( $_GET['limit'] );
			} else {
				$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
			}

			$data_store = WC_Data_Store::load( 'product' );
			$ids        = $data_store->search_products( $term, '', true, false, $limit );

			$product_objects = array_filter( array_map( 'wc_get_product', $ids ), 'wc_products_array_filter_readable' );
			$products        = array();

			foreach ( $product_objects as $product_object ) {
				if ( ywsbs_is_subscription_product( $product_object->get_id() ) ) {

					$products[ $product_object->get_id() ] = rawurldecode( $product_object->get_name() );
				}
			}

			wp_send_json( apply_filters( 'woocommerce_json_search_found_products', $products ) );
		}


		/**
		 * Get Categories via Ajax
		 *
		 * @since 1.0
		 */
		public function search_categories() {

			check_ajax_referer( 'search-products', 'security' );

			if ( ! current_user_can( 'edit_products' ) ) { // phpcs:ignore
				wp_die( -1 );
			}

			if ( ! isset( $_GET['term'] ) || ! wc_clean( stripslashes( $_GET['term'] ) ) ) {
				wp_die();
			}

			$search_text = wc_clean( stripslashes( $_GET['term'] ) );
			$found_tax   = array();
			$args        = array(
				'taxonomy'   => array( 'product_cat' ),
				'orderby'    => 'id',
				'order'      => 'ASC',
				'hide_empty' => true,
				'fields'     => 'all',
				'name__like' => $search_text,
			);

			$terms = get_terms( $args );
			if ( $terms ) {
				foreach ( $terms as $term ) {
					$term->formatted_name       .= $term->name . ' (' . $term->count . ')';
					$found_tax[ $term->term_id ] = $term->formatted_name;
				}
			}

			wp_send_json( $found_tax );
		}

		/**
		 * Resend the product info metabox to ajax script after that a coupon is removed.
		 */
		public function remove_subscription_coupon() {
			check_ajax_referer( 'remove-coupon', 'security' );

			if ( ! current_user_can( 'edit_ywsbs_subs' ) || ! isset( $_POST['subscription_id'] ) || ! isset( $_POST['coupon'] ) ) { // phpcs:ignore
				wp_die( -1 );
			}

			$subscription_id = sanitize_text_field( wp_unslash( $_POST['subscription_id'] ) );

			$subscription = ywsbs_get_subscription( $subscription_id );
			$coupon_code  = sanitize_text_field( wp_unslash( $_POST['coupon'] ) );
			$result       = YWSBS_Subscription_Coupons()->remove_coupon_from_subscription( $subscription, $coupon_code );
			if ( $result ) {
				$product = wc_get_product( ( $subscription->variation_id ) ? $subscription->variation_id : $subscription->product_id );

				ob_start();
				include YITH_YWSBS_VIEWS_PATH . '/metabox/subscription-product.php';
				$html = ob_get_clean();

				wp_send_json_success(
					array(
						'html' => $html,
					)
				);

			} else {
				wp_send_json_error( array( 'error' => esc_html_x( 'It is not possible remove the coupon from this subscription.', 'Error text', 'yith-woocommerce-subscription' ) ) );
			}
		}

		/**
		 * Resend the product info metabox to ajax script after that the coupons has been added.
		 *
		 * @throws Exception Throws an exception.
		 */
		public function add_subscription_coupon() {
			check_ajax_referer( 'add-coupon', 'security' );

			if ( ! current_user_can( 'edit_ywsbs_subs' ) || ! isset( $_POST['subscription_id'] ) || ! isset( $_POST['coupon'] ) ) { //phpcs:ignore
				wp_die( -1 );
			}

			$response = array();

			try {
				$subscription_id = sanitize_text_field( wp_unslash( $_POST['subscription_id'] ) );
				$subscription    = ywsbs_get_subscription( $subscription_id );

				if ( ! $subscription ) {
					throw new Exception( esc_html_x( 'Invalid subscription', 'Error text', 'yith-woocommerce-subscription' ) );
				}

				if ( empty( $_POST['coupon'] ) ) {
					throw new Exception( __( 'Invalid coupon', 'woocommerce' ) );
				}

				$coupon_code = sanitize_text_field( wp_unslash( $_POST['coupon'] ) );

				// Add user ID and/or email so validation for coupon limits works.
				$user_id_arg    = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
				$user_email_arg = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

				$result = YWSBS_Subscription_Coupons()->add_coupon_to_subscription( $subscription, $coupon_code, $user_id_arg, $user_email_arg );

				if ( is_wp_error( $result ) ) {
					wp_send_json_error(
						array(
							'error' => $result->get_error_message(),
						)
					);
				}

				if ( $result ) {
					$product = $subscription->get_product();

					ob_start();
					include YITH_YWSBS_VIEWS_PATH . '/metabox/subscription-product.php';
					$html = ob_get_clean();

					$response = array(
						'html' => $html,
					);
				} else {
					wp_send_json_error(
						array(
							'error' => esc_html_x( 'It is not possible add the coupon to this subscription.', 'Error text', 'yith-woocommerce-subscription' ),
						)
					);
				}
			} catch ( Exception $e ) {
				wp_send_json_error( array( 'error' => $e->getMessage() ) );
			}

			wp_send_json_success( $response );
		}

		/**
		 * Plugin module activation switch
		 *
		 * @since  3.0.0
		 * @throws Exception Error on module activation.
		 */
		public function module_activation_switch() {

			check_ajax_referer( 'ywsbs_module_activation_switch', 'security' );

			try {

				$module = isset( $_POST['module'] ) ? sanitize_text_field( wp_unslash( $_POST['module'] ) ) : '';
				if ( empty( $module ) ) {
					throw new Exception( _x( 'Empty module to activate.', 'Module activation error', 'yith-woocommerce-subscription' ) );
				}

				YWSBS_Subscription_Modules::switch_activation( $module );

			} catch ( Exception $e ) {
				wp_send_json_error( array( 'error' => $e->getMessage() ) );
			}

			wp_send_json_success();
		}
	}
}
