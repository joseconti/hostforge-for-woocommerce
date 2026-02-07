<?php
/**
 * Helper trait for the subscriptions handling.
 *
 * @package YITH/Subscription/Gateways
 */

declare(strict_types=1);

/**
 * Class YWSBS_WC_PayPal_Payments_Trial_Handler
 */
trait YWSBS_WC_PayPal_Payments_Trial_Handler {

	/**
	 * Checks if the cart contains only free trial.
	 *
	 * @return bool
	 */
	protected function is_free_trial_cart(): bool {
		$cart = WC()->cart;
		if ( ! $cart || $cart->is_empty() || (float) $cart->get_total( 'numeric' ) > 0 ) {
			return false;
		}

		foreach ( $cart->get_cart() as $item ) {
			$product = $item['data'] ?? null;
			if ( $product && ywsbs_is_subscription_product( $product ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the current product contains free trial.
	 *
	 * @return bool
	 */
	protected function is_free_trial_product(): bool {

		$product = wc_get_product();

		if ( ! $product || ! ywsbs_is_subscription_product( $product ) ) {
			return false;
		}

		if ( (int) ywsbs_get_product_trial( $product ) > 0 || ( class_exists( 'YWSBS_Subscription_Synchronization' ) && YWSBS_Subscription_Synchronization()->is_synchronizable( $product ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the given order contains only free trial.
	 *
	 * @param object $wc_order The WooCommerce order.
	 * @return bool
	 */
	protected function is_free_trial_order( $wc_order ): bool {
		if ( (float) $wc_order->get_total( 'numeric' ) > 0 ) {
			return false;
		}

		$subs = $wc_order->get_meta( 'subscriptions' );

		return ! empty( $subs );
	}
}
