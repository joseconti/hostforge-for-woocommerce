<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_My_Account Class.
 * Handle the frontend section for module "subscription box"
 *
 * @class   YWSBS_Subscription_Box_My_Account
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_My_Account' ) ) {
	/**
	 * Class YWSBS_Subscription_Box_Frontend
	 */
	class YWSBS_Subscription_Box_My_Account {

		/**
		 * Constructor
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function __construct() {
			$this->init_hooks();
		}

		/**
		 * Init class hooks
		 *
		 * @since  4.0.0
		 * @return void
		 */
		protected function init_hooks() {
			add_filter( 'ywsbs_subscription_box_frontend_product', array( $this, 'filter_frontend_product' ), 10, 1 );
			add_filter( 'ywsbs_subscription_box_frontend_script_data', array( $this, 'filter_script_data' ) );
			// My account section.
			add_action( 'ywsbs_my_account_after_subscription_info', array( $this, 'my_account_box' ), 10, 1 );

			// Handle box edit save.
			add_action( 'wp_loaded', array( $this, 'handle_subscription_box_edit' ), 20 );
		}

		/**
		 * Get my account subscription
		 *
		 * @since  4.0.0
		 * @return YWSBS_Subscription|false The subscription object, false if not found.
		 */
		public function get_subscription() {
			if ( ! YWSBS_Subscription_My_Account()->is_view_subscription_endpoint() ) {
				return false;
			}

			return YWSBS_Subscription_My_Account()->get_view_subscription_endpoint_queried_object();
		}

		/**
		 * Get my account product
		 *
		 * @since 4.0.0
		 * @param WC_Product|boolean $product Current product instance, false if not set.
		 * @return WC_Product|boolean
		 */
		public function filter_frontend_product( $product ) {
			$subscription = $this->get_subscription();
			return $subscription ? $subscription->get_product() : $product;
		}

		/**
		 * Filter script data for add edit box
		 *
		 * @since  4.0.0
		 * @param array $data An array of script data to use in localize.
		 * @return array
		 */
		public function filter_script_data( $data ) {

			$subscription = $this->get_subscription();
			if ( ! $subscription || ! ywsbs_is_a_box_subscription( $subscription ) || ! ywsbs_box_content_is_edit_enabled( $subscription ) ) {
				return $data;
			}

			$content = array();
			// Set initial box content for edit.
			if ( apply_filters( 'ywsbs_box_pre_select_content_on_edit', true, $subscription ) ) {
				$box         = $subscription->get_product();
				$box_content = $subscription->get( 'next_box_content' ) ?: $subscription->get( 'box_content' ); // phpcs:ignore
				$content     = ywsbs_prepare_box_content_for_edit( $box_content, $box );
			}

			$data = array_merge(
				$data,
				array(
					'subscriptionID' => $subscription->get_id(),
					'editNonce'      => wp_create_nonce( '_ywsbs_subscription_box_edit' ),
					'cartContent'    => array_filter( $content ),
				)
			);

			return $data;
		}

		/**
		 * Add my account box section in view-subscription endpoint
		 *
		 * @sicne  4.0.0
		 * @param YWSBS_Subscription $subscription The subscription object.
		 * @return void
		 */
		public function my_account_box( $subscription ) {
			if ( ! ywsbs_is_a_box_subscription( $subscription ) ) {
				return;
			}

			$box_editable = ywsbs_box_content_is_editable( $subscription );
            remove_filter( 'woocommerce_is_purchasable', array( 'YITH_WC_Subscription_Limit', 'is_purchasable' ) );
            $edit_enabled = ywsbs_box_content_is_edit_enabled( $subscription );
            add_filter( 'woocommerce_is_purchasable', array( 'YITH_WC_Subscription_Limit', 'is_purchasable' ), 10, 2 );
			// Box content.
			$box_content      = ywsbs_box_get_content_to_display( $subscription->get( 'box_content' ) );
			$next_box_content = ywsbs_box_get_content_to_display( $subscription->get( 'next_box_content' ) ?: array() ); // phpcs:ignore,

			// Build template arguments.
			$args = array(
				'subscription'     => $subscription,
				'box_editable'     => $box_editable,
				'edit_enabled'     => $edit_enabled,
				'is_edit'          => true,
				'payment_due_date' => date_i18n( wc_date_format(), $subscription->get_next_payment_due_date() ),
			);

			$this->output_my_account_box(
				$subscription,
				wp_parse_args(
					array(
						'title'         => _x( 'Current box', 'My account box section title', 'yith-woocommerce-subscription' ),
						'box_content'   => $box_content,
						'edit_enabled'  => $edit_enabled && empty( $next_box_content ),
						'delivery_date' => ywsbs_box_get_next_delivery_date( $subscription, wc_date_format() ),
					),
					$args
				)
			);

			if ( empty( $next_box_content ) ) {
				return;
			}

			$this->output_my_account_box(
				$subscription,
				wp_parse_args(
					array(
						'title'         => _x( 'Your next box', 'My account box section title', 'yith-woocommerce-subscription' ),
						'box_content'   => $next_box_content,
						'delivery_date' => ywsbs_box_calculate_next_delivery_date( $subscription, wc_date_format() ),
					),
					$args
				)
			);
		}

		/**
		 * Output my account box section in view-subscription endpoint
		 *
		 * @sicne  4.0.0
		 * @param YWSBS_Subscription $subscription The subscription object.
		 * @param array              $args         The template arguments.
		 * @return void
		 */
		protected function output_my_account_box( $subscription, $args = array() ) {
			wc_get_template(
				'myaccount/subscription-box-content.php',
				apply_filters( 'ywsbs_single_subscription_box_my_account', $args, $subscription ),
				'',
				YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . '/templates/'
			);
		}

		/**
		 * Handle subscription box edit.
		 *
		 * @since 4.0.0
		 * @return void
		 * @throws Exception Error on edit subscription box content.
		 */
		public function handle_subscription_box_edit() {

			if ( ! isset( $_POST['_ywsbs_subscription_box_edit'], $_POST['_ywsbs_box_content'], $_POST['security'] )
				|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), '_ywsbs_subscription_box_edit' ) ) {
				return;
			}

			try {

				$subscription_id = absint( wp_unslash( $_POST['_ywsbs_subscription_box_edit'] ) );
				$subscription    = $subscription_id ? ywsbs_get_subscription( $subscription_id ) : false;
				// Validate subscription.
				if ( ! $subscription || $subscription->get_user_id() !== get_current_user_id() || ! ywsbs_box_content_is_edit_enabled( $subscription ) ) {
					throw new Exception( __( 'You cannot edit this subscription.', 'yith-woocommerce-subscription' ) );
				}

				$box         = $subscription->get_product();
				$box_content = wc_clean( json_decode( wp_unslash( $_POST['_ywsbs_box_content'] ), true ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( empty( $box_content ) ) {
					throw new Exception( __( 'The subscription box content cannot be empty.', 'yith-woocommerce-subscription' ) );
				}

				// Create a temp cart and simulate the add to cart action.
				add_filter( 'woocommerce_cart_session_initialize', '__return_false' );
				add_filter( 'woocommerce_persistent_cart_enabled', '__return_false' );

				$cart = new WC_Cart();

				// Validate box content.
				$item_key = YWSBS_Subscription_Box_Cart::add_box_to_cart( $box->get_id(), $box_content, $cart );
				if ( is_wp_error( $item_key ) ) {
					throw new Exception( __( 'An error occurred editing this box content. Please try again.', 'yith-woocommerce-subscription' ) );
				}

				$cart->calculate_totals();

				// Store new box content. This will be applied before next renew.
				$subscription->set( 'next_box_content', $box_content );
				YITH_WC_Activity()->add_activity( $subscription->get_id(), 'changed', 'success', 0, esc_html__( 'Box content updated by customer.', 'yith-woocommerce-subscription' ) );

				if ( 'sum' === $box->get_price_type() ) {

					$line_item = $cart->get_cart_item( $item_key );

					$subscription->update_prices(
						array(
							'ywsbs_line_total'              => $line_item['line_total'],
							'ywsbs_line_tax'                => $line_item['line_tax'],
							'ywsbs_shipping_cost_line_cost' => $cart->get_shipping_total(),
							'ywsbs_shipping_cost_line_tax'  => $cart->get_shipping_tax(),
						)
					);

					$subscription->recalculate_prices();
				}

				// translators: %s is the next subscription payment due date.
				wc_add_notice( sprintf( __( 'Subscription box content updated successfully. The new box content will be valid from the next renewal scheduled on %s.', 'yith-woocommerce-subscription' ), date_i18n( wc_date_format(), $subscription->get_next_payment_due_date() ) ) );

				wp_safe_redirect( ywsbs_get_view_subscription_url( $subscription_id ) );
				exit;

			} catch ( Exception $e ) {
				wc_add_notice( $e->getMessage(), 'error' );

				wp_safe_redirect( $subscription_id ? ywsbs_get_view_subscription_url( $subscription_id ) : wc_get_endpoint_url( 'my-subscription' ) );
				exit;
			}
		}
	}
}
