<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Cart Legacy Abstract Class.
 *
 * @class   YWSBS_Subscription_Order_Legacy
 * @package YITH\Subscription
 * @since   4.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Class YWSBS_Subscription_Cart_Legacy
 */
abstract class YWSBS_Subscription_Cart_Legacy {

	/**
	 * Get price.
	 *
	 * @param int   $product_id Product id.
	 * @param float $price      Price.
	 * @param int   $quantity   Quantity.
	 *
	 * @return     float
	 * @deprecated 2.0.0
	 */
	public function get_price( $product_id, $price, $quantity = 1 ) {
		// Load product object.
		$product = wc_get_product( $product_id );

		$price = $product->get_regular_price();

		// Get correct price.
		if ( get_option( 'woocommerce_tax_display_cart' ) ) {
			$price = yit_get_price_including_tax( $product, $quantity, $price );
		} else {
			$price = yit_get_price_excluding_tax( $product, $quantity, $price );
		}

		return (float) $price;
	}

	/**
	 * Check if there are subscription upgrade in progress and change the trial options
	 * During the upgrade or downgrade the trial period will be nulled.
	 *
	 * @param int   $trial     Trial.
	 * @param array $cart_item Cart Item.
	 *
	 * @return int | string
	 */
	public function change_trial_in_cart( $trial, $cart_item ) {

		$new_trial = $trial;

		$product = $cart_item['data'];
		$id      = $product->get_id();

		/* UPGRADE PROCESS */
		$subscription_upgrade_info = get_user_meta( get_current_user_id(), 'ywsbs_upgrade_' . $id, true );
		if ( ! empty( $subscription_upgrade_info ) ) {
			return '';
		}

		/* DOWNGRADE PROCESS */
		$subscription_downgrade_info = get_user_meta( get_current_user_id(), 'ywsbs_trial_' . $id, true );
		if ( ! empty( $subscription_downgrade_info ) ) {
			$new_trial = $subscription_downgrade_info['trial_days'];
		}

		return $new_trial;
	}

	/**
	 * Change price in cart.
	 *
	 * @param string  $price_html    HTML price.
	 * @param array   $cart_item     Cart Item.
	 * @param string  $cart_item_key Cart Item Key.
	 * @param boolean $block         True if is WC block, false otherwise.
	 * @return mixed|void
	 */
	public function change_price_in_cart_html( $price_html, $cart_item, $cart_item_key, $block = false ) {

		$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];

		if ( ywsbs_is_subscription_product( $product_id ) && isset( $cart_item['data'] ) ) {
			$product = $cart_item['data'];

			$price = apply_filters( 'ywsbs_change_price_in_cart_html', $cart_item['data']->get_price( 'edit' ), $cart_item['data'] );

			$price_current = apply_filters( 'ywsbs_change_price_current_in_cart_html', $product->get_price( 'edit' ), $product );
			$product->set_price( $price );

			$price_html = $this->change_general_price_html( $product, 1, true, $cart_item, $block );

			$price_html = apply_filters( 'ywsbs_get_price_html', $price_html, $cart_item, $product_id );
			$product->set_price( $price_current );
		}

		return $price_html;
	}

	/**
	 * Change subtotal html price on cart.
	 *
	 * @param string $price_html    Html Price.
	 * @param array  $cart_item     Cart item.
	 * @return string
	 */
	public function change_subtotal_price_in_cart_html( $price_html, $cart_item ) {

		$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];

		if ( ! ywsbs_is_subscription_product( $product_id ) || ! isset( $cart_item['data'] ) ) {
			return $price_html;
		}

		$product = $cart_item['data'];
		$price   = apply_filters( 'ywsbs_change_subtotal_price_in_cart_html', $cart_item['data']->get_price( 'edit' ), $cart_item['data'], $cart_item );

		$price_current = apply_filters( 'ywsbs_change_subtotal_price_current_in_cart_html', $product->get_price( 'edit' ), $product );

		$product->set_price( $price );
		$price_html = $this->change_general_price_html( $product, $cart_item['quantity'], false, $cart_item );

		$product->set_price( $price_current );

		return apply_filters( 'ywsbs_subscription_subtotal_html', $price_html, $cart_item['data'], $cart_item );
	}

	/**
	 * Change price HTML to the product
	 *
	 * @since  1.2.0
	 * @param WC_Product $product             WC_Product.
	 * @param int        $quantity            (Optional) Quantity. Default is 1.
	 * @param bool       $show_complete_price (Optional) To show the complete price inside cart subtotal. Default is null.
	 * @param array      $cart_item           (Optional) Cart item. Default is null.
	 * @param boolean    $block               (Optional) True if is a WC block, false otherwise. Default is false.
	 * @return string
	 */
	public function change_general_price_html( $product, $quantity = 1, $show_complete_price = false, $cart_item = null, $block = false ) {

		if ( is_null( $cart_item ) ) {
			return $product->get_price_html();
		}

		$show_complete_price_on_substotal_cart = apply_filters( 'ywsbs_show_complete_price_on_substotal_cart', $show_complete_price );

		if ( isset( $cart_item['ywsbs-subscription-info'] ) ) {
			$subscription_info = $cart_item['ywsbs-subscription-info'];
		} else {
			$subscription_info = $this->get_subscription_meta_on_cart( $cart_item['data'] );
		}

		$price = wc_get_price_to_display(
			$product,
			array(
				'qty'   => $quantity,
				'price' => $product->get_price( 'edit' ),
			)
		);

		$price_html = '<div class="ywsbs-wrapper"><div class="ywsbs-price">';

		$price_html .= $block ? '<price/>' : wc_price( $price );

		if ( ! isset( $subscription_info['sync'] ) || ! $subscription_info['sync'] ) {
			$price_html .= '<span class="price_time_opt"> / ' . YWSBS_Subscription_Helper::get_subscription_period_for_price( $product, $subscription_info ) . '</span>';
		} elseif ( isset( $subscription_info['sync'], $subscription_info['next_payment_due_date'] ) && $subscription_info['sync'] && 0 == $price ) { //phpcs:ignore
			if ( current_action() === 'woocommerce_cart_item_subtotal' ) {
				return $price_html;
			}
			$recurring_period        = YWSBS_Subscription_Helper::get_subscription_period_for_price( $cart_item['data'], $cart_item['ywsbs-subscription-info'] );
			$recurring_price         = YWSBS_Subscription_Helper::get_subscription_recurring_price( $cart_item['data'], $cart_item['ywsbs-subscription-info'] );
			$recurring_price_display = wc_get_price_to_display(
				$cart_item['data'],
				array(
					'qty'   => $cart_item['quantity'],
					'price' => $recurring_price,
				)
			);

			if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
				$recurring_tax = ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
			} else {
				$recurring_tax = ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
			}

			$pri = wc_price( $recurring_price_display ) . ' / ' . $recurring_period . ' ' . $recurring_tax;
			$pri = apply_filters( 'ywsbs_recurring_price_html', $pri, $recurring_price, $recurring_period, $cart_item );

			// translators: placeholder 1. first price, 2. valid period of the first price, 3. end price.
			return sprintf( __( '%1$s until %2$s then %3$s', 'yith-woocommerce-subscription' ), $price_html, date_i18n( wc_date_format(), $subscription_info['next_payment_due_date'] ), $pri );
		}
		$max_length = YWSBS_Subscription_Helper::get_subscription_max_length_formatted_for_price( $product, $subscription_info );
		$max_length = ! empty( $max_length ) ? esc_html__( ' for ', 'yith-woocommerce-subscription' ) . $max_length : '';

		if ( is_cart() && ! $show_complete_price_on_substotal_cart ) {

			$signup_fee = apply_filters( 'ywsbs_product_fee', $subscription_info['fee'], $product );
			$signup_fee = empty( $signup_fee ) ? 0 : (float) $signup_fee;
			$signup_fee = wc_get_price_to_display(
				$product,
				array(
					'qty'   => $quantity,
					'price' => $signup_fee,
				)
			);

			$total_price = ( $price + $signup_fee );
			$price_html  = wc_price( $total_price );

		} else {

			$price_html  = $price_html . '<span class="ywsbs-max-lenght">' . $max_length . '</span>';
			$price_html .= '</div>';
			$trial_price = YWSBS_Subscription_Helper::get_trial_price( $product, 'cart', $subscription_info );
			$fee_price   = YWSBS_Subscription_Helper::get_fee_price( $product, $quantity, null, $subscription_info );

			if ( trim( $fee_price ) !== '' ) {
				$price_html .= '<span class="ywsbs-fee-price">' . $fee_price . '</span>';
			}

			if ( trim( $trial_price ) !== '' ) {
				$price_html .= '<span class="ywsbs-trial-price">' . $trial_price . '</span>';
			}
			$price_html .= '</div>';

		}

		$price_html = apply_filters_deprecated(
			'ywsbs_change_general_price_html',
			array( $price_html, $product, $product->get_meta( '_ywsbs_price_is_per' ), $product->get_meta( '_ywsbs_price_time_option' ), $product->get_meta( '_ywsbs_max_length' ), ywsbs_get_product_fee( $product ), ywsbs_get_product_trial( $product ), $quantity ),
			'2.0.0',
			'ywsbs_change_subtotal_product_price',
			'This filter will be removed in next major release.'
		);

		// APPLY_FILTER: ywsbs_change_subtotal_product_price: to change the html price of a subscription product.
		return apply_filters( 'ywsbs_change_subtotal_product_price', $price_html, $product, $quantity, $cart_item, $show_complete_price_on_substotal_cart );
	}
}
