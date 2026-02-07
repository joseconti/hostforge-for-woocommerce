<?php 
/**
 * Implements YITH WooCommerce Subscription
 *
 * @class   YITH_WC_Subscription_Limit
 * @package YITH\Subscription
 * @since   2.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Limit' ) ) {

	/**
	 * Class YITH_WC_Subscription_Limit
	 */
	class YITH_WC_Subscription_Limit {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * List of limited products
		 *
		 * @var array $limited_products
		 */
		protected static $limited_products = array();

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used.
		 */
		private function __construct() {

			add_action( 'wp_login', array( $this, 'check_cart_after_login' ), 99 );
			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'check_cart_after_login' ), 1000 );

			add_action( 'template_redirect', array( $this, 'check_blocks' ) );
		}

		/**
		 * Check if wc block are set on single product page and show the message to limited product.
		 *
		 * @return void
		 * @since 3.0.0
		 */
		public function check_blocks() {
			if ( is_single() && yith_plugin_fw_wc_is_using_block_template_in_single_product() ) {
				add_filter( 'render_block_woocommerce/product-price', array( $this, 'show_message_to_limited_product_block' ), 10 );
			} else {
				add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'show_message_to_limited_product' ), 29 );
			}
		}

		/**
		 * Show limited product message on single product page connected to the product price block
		 *
		 * @since 3.0.0
		 * @param string $content Content.
		 * @return string
		 */
		public function show_message_to_limited_product_block( $content ) {
			ob_start();
			$this->show_message_to_limited_product();
			$new_content = ob_get_contents();
			ob_end_clean();
			return $content . $new_content;
		}

		/**
		 * Show a message if the product can't be purchased because is limited.
		 *
		 * @return void
		 */
		public static function show_message_to_limited_product() {
			global $product, $post;

			if ( ! $product instanceof WC_Product && $post instanceof WP_Post ) {
				$product = wc_get_product( $post->ID );
			}

			if ( ! $product || ! self::is_limited( $product ) ) {
				return;
			}

			echo apply_filters( 'ywsbs_show_message_to_limited_product', esc_html__( 'You already have an active subscription to this product.', 'yith-woocommerce-subscription' ), $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Filter is_purchasable property of a product.
		 *
		 * @param bool       $is_purchasable Current is purchasable value.
		 * @param WC_Product $product Current product.
		 */
		public static function is_purchasable( $is_purchasable, $product ) {

			if ( $is_purchasable && self::is_limited( $product ) ) {
				$is_purchasable = false;
			}

			return $is_purchasable;
		}

		/**
		 * Check if th is_purchasable property.
		 *
		 * @param WC_Product $product Current product.
		 *
		 * @return bool|string
		 */
		public static function is_limited( $product ) {
			$is_limited = false;
			if ( $product && $product instanceof WC_Product ) {
				if ( isset( self::$limited_products[ $product->get_id() ] ) ) {
					return self::$limited_products[ $product->get_id() ];
				}

				$limited_value = ywsbs_is_limited_product( $product );

				if ( $limited_value ) {
					$user_id = get_current_user_id();

					if ( 'one-active' === $limited_value ) {
						$one_active_status = apply_filters( 'ywsbs_limit_one_active_status', array( 'active', 'paused', 'suspended', 'overdue', 'trial', 'pending' ) );

						if ( YWSBS_Subscription_User::has_subscription( $user_id, $product->get_id(), $one_active_status ) ) {
							$is_limited = true;
						}
					} elseif ( YWSBS_Subscription_User::has_subscription( $user_id, $product->get_id() ) ) {
						$is_limited = true;
					}
				}

				self::$limited_products[ $product->get_id() ] = apply_filters( 'ywsbs_is_limited', $is_limited, $product->get_id() );
			}

			return $is_limited;
		}

		/**
		 * This checks cart items for mixed checkout.
		 *
		 * @param WC_Cart $cart Cart from session.
		 *
		 * @since 2.2.5
		 */
		public function check_cart_after_login( $cart = '' ) {
			// phpcs:disable WordPress.Security.NonceVerification
			$skip_ajax_call = apply_filters( 'ywsbs_skip_ajax_call_for_cart_check_after_login', array( 'yith_wcstripe_verify_intent' ), $cart );

			if ( isset( $_REQUEST['wc-ajax'] ) && in_array( $_REQUEST['wc-ajax'], $skip_ajax_call, true ) ) {
				return;
			}

			$contents = ( ! empty( $cart ) && isset( $cart->cart_contents ) ) ? $cart->cart_contents : ( isset( WC()->cart ) ? WC()->cart->get_cart() : false );

			if ( ! empty( $contents ) ) {
				foreach ( $contents as $item_key => $item ) {
					$product = $item['data'];

					if ( ywsbs_is_subscription_product( $product ) && ! self::is_purchasable( true, $product ) && ! isset( $item['ywsbs-subscription-resubscribe'] ) ) {
						WC()->cart->remove_cart_item( $item_key );
						$message = esc_html__( 'You already have an active subscription to this product.', 'yith-woocommerce-subscription' );
						wc_add_notice( $message, 'error' );
					}
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification
		}
	}
}

/**
 * Unique access to instance of YITH_WC_Subscription_Limit class
 *
 * @return YITH_WC_Subscription_Limit
 */
function YITH_WC_Subscription_Limit() { //phpcs:ignore
	return YITH_WC_Subscription_Limit::get_instance();
}
