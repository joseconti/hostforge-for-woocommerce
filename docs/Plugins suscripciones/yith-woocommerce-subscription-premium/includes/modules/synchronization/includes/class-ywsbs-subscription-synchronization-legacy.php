<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Synchronization Object.
 *
 * @class   YWSBS_Subscription_Synchronization
 * @since   2.1.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Synchronization_Legacy' ) ) {

	/**
	 * Class YWSBS_Subscription_Synchronization
	 */
	abstract class YWSBS_Subscription_Synchronization_Legacy {

		/**
		 * YWSBS_Subscription_Synchronization_Cart instance.
		 *
		 * @var YWSBS_Subscription_Synchronization_Cart
		 */
		protected $cart;

		/**
		 * Filter the subscription cart meta information.
		 *
		 * @param array      $subscription_cart_meta Cart item subscription info.
		 * @param WC_Product $product                Product.
		 * @return array
		 * @deprecated
		 */
		public function synchronize_next_payment_due_date( $subscription_cart_meta, $product ) {
			// translators: %s stand for the deprecated method replacement.
			_doing_it_wrong( __METHOD__, sprintf( esc_html__( 'This method is deprecated, use %s instead', 'yith-woocommerce-subscription' ), 'YWSBS_Subscription_Synchronization()->cart->sync_next_payment_due_date' ), '3.0.0' );
			return $this->cart->sync_next_payment_due_date( $subscription_cart_meta, $product );
		}

		/**
		 * Set the new price inside the cart when a subscription can be synchronized and the price is prorated.
		 *
		 * @param array $cart_item Cart item.
		 * @return array
		 */
		public function set_synch_changes_on_cart( $cart_item ) {
			// translators: %s stand for the deprecated method replacement.
			_doing_it_wrong( __METHOD__, sprintf( esc_html__( 'This method is deprecated, use %s instead', 'yith-woocommerce-subscription' ), 'YWSBS_Subscription_Synchronization()->cart->set_sync_changes_on_cart' ), '3.0.0' );
			return $this->cart->set_sync_changes_on_cart( $cart_item );
		}
	}
}
