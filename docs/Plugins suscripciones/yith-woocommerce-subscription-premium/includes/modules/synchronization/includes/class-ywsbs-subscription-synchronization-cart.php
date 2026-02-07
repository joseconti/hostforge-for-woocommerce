<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Synchronization_Cart Object.
 *
 * @class   YWSBS_Subscription_Synchronization_Cart
 * @since   2.1.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Synchronization_Cart' ) ) {

	/**
	 * Class YWSBS_Subscription_Synchronization
	 */
	class YWSBS_Subscription_Synchronization_Cart {

		/**
		 * Constructor
		 * Initialize the YWSBS_Subscription_Synchronization_Cart Object
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			add_filter( 'ywsbs_subscription_meta_on_cart', array( $this, 'sync_next_payment_due_date' ), 10, 2 );
			add_filter( 'woocommerce_cart_contents_changed', array( $this, 'customize_cart_contents' ), 20 );
		}

		/**
		 * Filter the subscription cart meta information.
		 *
		 * @param array      $subscription_cart_meta Cart item subscription info.
		 * @param WC_Product $product                Product.
		 * @return array
		 */
		public function sync_next_payment_due_date( $subscription_cart_meta, $product ) {
			if ( ! YWSBS_Subscription_Synchronization()->is_synchronizable( $product, true ) ) {
				return $subscription_cart_meta;
			}

			$subscription_cart_meta['next_payment_due_date'] = YWSBS_Subscription_Synchronization()->get_next_payment_due_date_sync( $subscription_cart_meta['next_payment_due_date'], $product );
			return $subscription_cart_meta;
		}

		/**
		 * Customize cart contents based on synchronization module settings
		 *
		 * @since 4.2.0
		 * @param array $cart_content The cart content.
		 * @return array
		 */
		public function customize_cart_contents( $cart_content ) {
			foreach ( $cart_content as &$cart_item ) {
				$cart_item = $this->set_sync_changes_on_cart( $cart_item );
			}

			return $cart_content;
		}

		/**
		 * Set the new price inside the cart when a subscription can be synchronized and the price is prorated.
		 *
		 * @param array $cart_item Cart item.
		 * @return array
		 */
		public function set_sync_changes_on_cart( $cart_item ) {

			$product = $cart_item['data'];
			if ( ! isset( $cart_item['ywsbs-subscription-info'] ) || ! YWSBS_Subscription_Synchronization()->is_synchronizable( $product, true ) ) {
				return $cart_item;
			}

			$subscription_info = $cart_item['ywsbs-subscription-info'];
			// check the next payment due date.
			$next_payment_due_date = YWSBS_Subscription_Helper::get_billing_payment_due_date( $product );
			$next_payment_due_date = YWSBS_Subscription_Synchronization()->get_next_payment_due_date_sync( $next_payment_due_date, $product );

			$today                           = new DateTime();
			$next_payment_due_date_date_time = new DateTime( '@' . $next_payment_due_date );

			if ( $today->format( 'Y-m-d' ) === $next_payment_due_date_date_time->format( 'Y-m-d' ) ) {
				return $cart_item;
			}

			$pay_now = YWSBS_Subscription_Synchronization()->get_new_price_sync( $product->get_price( 'edit' ), $product, $subscription_info['next_payment_due_date'] );

			if ( (float) $subscription_info['recurring_price'] !== $pay_now ) {
				$cart_item['data']->set_price( $pay_now );
				$cart_item['ywsbs-subscription-info']['sync'] = true;
			}

			return $cart_item;
		}
	}
}
