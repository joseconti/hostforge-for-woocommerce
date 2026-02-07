<?php 
/**
 * YWSBS_Subscription_Coupons_Legacy abstract Class.
 *
 * @class   YWSBS_Subscription_Coupons_Legacy
 * @since   1.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Coupons_Legacy' ) ) {

	/**
	 * Class YWSBS_Subscription_Coupons
	 */
	abstract class YWSBS_Subscription_Coupons_Legacy {

		/**
		 * Return the discounted price.
		 *
		 * @param float   $price     Price of cart item.
		 * @param array   $cart_item Cart item.
		 * @param WC_Cart $cart      Cart Object.
		 *
		 * @return mixed
		 * @throws Exception Return an error.
		 * @deprecated
		 */
		public function get_discounted_price( $price, $cart_item, $cart ) {
			_deprecated_function( __METHOD__, '4.0.0' );
			return $price;
		}

		/**
		 * Total of coupon discounts
		 *
		 * @param string  $coupon_code        Coupon Code.
		 * @param float   $amount             Amount.
		 * @param float   $total_discount_tax Total discount tax.
		 * @param WC_Cart $cart               Cart.
		 *
		 * @return void
		 */
		public function increase_coupon_discount_amount( $coupon_code, $amount, $total_discount_tax, $cart ) {
			_deprecated_function( __METHOD__, '4.0.0' );
		}
	}
}
