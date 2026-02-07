<?php
/**
 * YWSBS_Multi_Currency_Switcher class to add compatibility with YITH Multi Currency Switcher for WooCommerce
 *
 * @class   YWSBS_Multi_Currency_Switcher
 * @since   2.4.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Class YWSBS_Multi_Currency_Switcher
 */
class YWSBS_Multi_Currency_Switcher {
	use YITH_WC_Subscription_Singleton_Trait;

	/**
	 * Constructor
	 *
	 * Initialize class and registers actions and filters to be used
	 *
	 * @since  1.0.0
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ), 5 );
	}

	/**
	 * Init class hooks and filters
	 *
	 * @since 3.2.0
	 * @return void
	 */
	public function init() {
		if ( ! class_exists( 'YITH_WCMCS_Products' ) ) {
			return;
		}

		add_filter( 'ywsbs_subscription_recurring_price', array( $this, 'convert_subscription_prices' ), 99, 3 );
		add_filter( 'ywsbs_get_total_subscription_price', array( $this, 'convert_subscription_prices' ), 10, 2 );
		add_filter( 'ywsbs_cart_item_price', array( $this, 'convert_subscription_prices' ), 10, 2 );
		add_filter( 'ywsbs_cart_item_subtotal', array( $this, 'convert_subscription_prices' ), 10, 2 );
		add_filter( 'ywsbs_subscription_price', array( $this, 'convert_subscription_prices' ), 10, 2 );
		add_filter( 'ywsbs_my_subscriptions_view_before', array( $this, 'remove_filters_before_my_subscription_view' ), 10, 2 );
		add_filter( 'ywsbs_before_subscription_view', array( $this, 'remove_filters_before_my_subscription_view' ), 10, 2 );
		add_filter( 'ywsbs_product_fee', array( $this, 'convert_price' ), 10, 2 );
	}

	/**
	 * Convert subscription internal prices
	 *
	 * @param float      $price             Price.
	 * @param WC_Product $product           The product object.
	 * @param array|bool $subscription_info (Optional) The subscription info array. Default is false.
	 * @return float
	 */
	public function convert_subscription_prices( $price, $product, $subscription_info = false ) {
		if ( ! empty( $price ) ) {
			$currency_id = yith_wcmcs_get_current_currency_id();
			if ( $currency_id && yith_wcmcs_get_wc_currency_options( 'currency' ) !== $currency_id ) {
				$price = YITH_WCMCS_Products::get_instance()->filter_manual_price( $price, $product );

				if ( isset( $subscription_info, WC()->cart ) ) {
					$applied_coupons = WC()->cart->get_applied_coupons();
					$is_trial        = ( ! empty( $subscription_info['trial_per'] ) && $subscription_info['trial_per'] > 0 );

					if ( $applied_coupons ) {
						foreach ( $applied_coupons as $coupon_code ) {
							$coupon         = new WC_Coupon( $coupon_code );
							$coupon_type    = $coupon->get_discount_type();
							$limited        = $coupon->get_meta( 'ywsbs_limited_for_payments' );
							$limit_is_valid = empty( $limited ) || $limited > 1 || $is_trial || 0 == $product->get_price(); //phpcs:ignore
							$coupon_amount  = $coupon->get_amount();
							$valid          = ywsbs_coupon_is_valid( $coupon, WC()->cart, $product );
							if ( $valid && in_array(
								$coupon_type,
								array(
									'recurring_percent',
									'recurring_fixed',
								),
								true
							) && $limit_is_valid ) {
								$discount_amount = 0;
								switch ( $coupon_type ) {
									case 'recurring_percent':
										$discount_amount = round( ( $price / 100 ) * $coupon_amount, WC()->cart->dp );
										break;
									case 'recurring_fixed':
										$discount_amount = ( $price < $coupon_amount ) ? $price : $coupon_amount;
										break;
								}
								$prices = yith_wcmcs_get_product_prices( $product, $currency_id );
								$time   = time();
								if ( '' !== $prices['regular'] ) {
									$converted_price = ( '' === $prices['sale_from'] || ( $time > $prices['sale_from'] && $time < $prices['sale_to'] ) ) && '' !== $prices['sale'] && $prices['sale'] < $prices['regular'] ? $prices['sale'] : $prices['regular'];

								}
								$price = empty( $converted_price ) ? $price : $price - $discount_amount;
							}
						}
					}
				}
			}
		}
		return $price;
	}

	/**
	 * Remove the currency symbol filter
	 */
	public function remove_filters_before_my_subscription_view() {
		remove_filter( 'woocommerce_currency_symbol', array( YITH_WCMCS_Products::get_instance(), 'filter_currency_symbol' ), 99 );
	}

	/**
	 * Convert price
	 */
	public function convert_price( $price, $product ) {
		$price = yith_wcmcs_convert_price( $price );

		return $price;
	}
}


/**
 * Get the YWSBS_Multi_Currency_Switcher instance
 *
 * @return YWSBS_Multi_Currency_Switcher
 */
function ywsbs_yith_wcmcs() { // phpcs:ignore
	return YWSBS_Multi_Currency_Switcher::get_instance();
}
