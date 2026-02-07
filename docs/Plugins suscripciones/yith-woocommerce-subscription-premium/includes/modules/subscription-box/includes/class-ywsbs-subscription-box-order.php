<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_Order Class.
 * Handle the cart for module "subscription box"
 *
 * @class   YWSBS_Subscription_Box_Order
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_Order' ) ) {
	/**
	 * Class YWSBS_Subscription_Box_Order
	 */
	class YWSBS_Subscription_Box_Order {

		/**
		 * Init
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public static function init() {
			// Handle box item stock once order is created.
			add_filter( 'ywsbs_itemmeta_to_skip_in_renew_order', array( __CLASS__, 'skip_item_meta_renew_order' ), 10, 1 );
			add_action( 'woocommerce_reduce_order_stock', array( __CLASS__, 'reduce_box_content_stock_levels' ), 10, 1 );
			add_action( 'woocommerce_restore_order_stock', array( __CLASS__, 'restore_box_content_stock_levels' ), 10, 1 );
			// Customize subscription data.
			add_filter( 'ywsbs_subscription_info_meta', array( __CLASS__, 'customize_subscription_info_meta' ), 10, 3 );
			add_filter( 'ywsbs_add_subscription_args', array( __CLASS__, 'filter_new_subscription_args' ), 10, 2 );
			// Handle subscription renew.
			add_action( 'ywsbs_after_create_renew_order', array( __CLASS__, 'maybe_update_box_content' ), 10, 2 );
			add_action( 'ywsbs_renew_order_item_meta_value', array( __CLASS__, 'maybe_update_box_item_meta_value' ), 10, 3 );
		}

		/**
		 * Customize subscription info meta data
		 *
		 * @since  4.0.0
		 * @param array   $subscription_info The current subscription info array.
		 * @param WC_Cart $cart              The cart instance used for calculate subscription info array.
		 * @param string  $cart_item_key     The cart item key related to subscription info.
		 * @return array
		 */
		public static function customize_subscription_info_meta( $subscription_info, $cart, $cart_item_key ) {
			$item = $cart->get_cart_item( $cart_item_key );
			if ( ywsbs_is_cart_item_subscription_box( $item ) ) {
				$subscription_info['box_data'] = $item['_ywsbs_box_data'];
			}

			return $subscription_info;
		}

		/**
		 * Filter new subscription args to add box data
		 *
		 * @since  4.0.0
		 * @param array              $args         An array of subscription arguments.
		 * @param YWSBS_Subscription $subscription The subscription instance.
		 * @return array
		 */
		public static function filter_new_subscription_args( $args, $subscription ) {
			$info = wc_get_order_item_meta( $args['order_item_id'], '_subscription_info' );
			if ( ! empty( $info ) && ! empty( $info['box_data'] ) ) {
				$args['box_content'] = $info['box_data']['content'];
				unset( $info['box_data']['content'] );
				$args['box_options'] = $info['box_data'];
			}

			return $args;
		}

		/**
		 * Skip custom item meta on renew order creation
		 *
		 * @since  4.0.0
		 * @param array $metas An array of meta key to skip.
		 * @return array
		 */
		public static function skip_item_meta_renew_order( $metas ) {
			return array_merge( $metas, array( '_reduced_box_content_stock' ) );
		}

		/**
		 * When order stock status is reduced, we must reduce stock levels for content within a box.
		 *
		 * @since 4.0.0
		 * @param WC_Order $order Order instance.
		 */
		public static function reduce_box_content_stock_levels( $order ) {
			// Loop over all items.
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				// Only reduce stock once for each item.
				$box                  = $item->get_product();
				$subscription_info    = $item->get_meta( '_subscription_info' );
				$item_stock_reduced   = $item->get_meta( '_reduced_box_content_stock' ) ?: array(); // phpcs:ignore
				$item_stock_to_reduce = array();

				if ( ! $box || empty( $subscription_info ) || empty( $subscription_info['box_data'] ) || ! empty( $item_stock_reduced ) ) {
					continue;
				}

				// Loop over box content and collect quantity to reduce.
				foreach ( $subscription_info['box_data']['content'] as $step_id => $step_items ) {
					foreach ( $step_items as $step_item ) {
						// Store quantity to reduce by product ID.
						$item_stock_to_reduce[ $step_item['product'] ] = ( $item_stock_to_reduce[ $step_item['product'] ] ?? 0 ) + $step_item['quantity'];
					}
				}

				foreach ( $item_stock_to_reduce as $product_id => $quantity ) {
					$product = wc_get_product( $product_id );
					if ( ! $product || ! $product->managing_stock() ) {
						continue;
					}

					$new_stock = wc_update_product_stock( $product, $quantity, 'decrease' );
					if ( is_wp_error( $new_stock ) ) {
						/* translators: %s box item name. */
						$order->add_order_note( sprintf( __( 'Unable to reduce stock for subscription box item %s.', 'yith-woocommerce-subscription' ), $product->get_name() ) );
					} else {
						$item_stock_reduced[ $product->get_id() ] = $quantity;
					}
				}

				$item->add_meta_data( '_reduced_box_content_stock', $item_stock_reduced, true );
				$item->save();
			}
		}

		/**
		 * When order stock status is restored, we must restore stock levels for content within a box.
		 *
		 * @since 4.0.0
		 * @param WC_Order $order Order instance.
		 */
		public static function restore_box_content_stock_levels( $order ) {
			// Loop over all items.
			foreach ( $order->get_items( 'line_item' ) as $item ) {

				$box                = $item->get_product();
				$subscription_info  = $item->get_meta( '_subscription_info' );
				$item_stock_reduced = $item->get_meta( '_reduced_box_content_stock' );

				if ( ! $box || empty( $subscription_info ) || empty( $subscription_info['box_data'] ) || empty( $item_stock_reduced ) ) {
					continue;
				}

				// Loop over box content.
				foreach ( $item_stock_reduced as $product_id => $quantity ) {
					$product = wc_get_product( $product_id );
					if ( ! $product || ! $product->managing_stock() ) {
						continue;
					}

					$new_stock = wc_update_product_stock( $product, $quantity, 'increase' );
					if ( is_wp_error( $new_stock ) ) {
						/* translators: %s box item name. */
						$order->add_order_note( sprintf( __( 'Unable to restore stock for subscription box item %s.', 'yith-woocommerce-subscription' ), $product->get_name() ) );
					}
				}

				$item->delete_meta_data( '_reduced_box_content_stock' );
				$item->save();
			}
		}

		/**
		 * Maybe update box content before add item to the renew order.
		 *
		 * @since 4.0.0
		 * @param WC_Order           $order The renew order.
		 * @param YWSBS_Subscription $subscription The subscription object.
		 * @return void
		 */
		public static function maybe_update_box_content( $order, $subscription ) {

			if ( ! ywsbs_is_a_box_subscription( $subscription ) ) {
				return;
			}

			$next_box_content = $subscription->get( 'next_box_content' );
			if ( empty( $next_box_content ) ) {
				return;
			}

			$subscription->set( 'box_content', $next_box_content );
			$subscription->unset_prop( 'next_box_content' );
		}

		/**
		 * Maybe update box order item meta value with the new box content.
		 *
		 * @since 4.0.0
		 * @param mixed              $value The item meta value.
		 * @param string             $key The item meta key.
		 * @param YWSBS_Subscription $subscription The subscription object.
		 * @return mixed
		 */
		public static function maybe_update_box_item_meta_value( $value, $key, $subscription ) {

			if ( ! ywsbs_is_a_box_subscription( $subscription ) || '_subscription_info' !== $key || ! is_array( $value ) ) {
				return $value;
			}

			$value['box_data']['content'] = $subscription->get( 'box_content' );
			return $value;
		}
	}
}
