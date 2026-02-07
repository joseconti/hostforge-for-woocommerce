<?php

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! function_exists( 'ywsbs_box_content_is_editable' ) ) {
	/**
	 * Check if subscription box content is editable.
	 * Check:
	 *  - main option must be enabled;
	 *  - subscription payment method must be a valid one.
	 *
	 * @since 4.0.0
	 * @param YWSBS_Subscription $subscription The subscription object.
	 * @return boolean
	 */
	function ywsbs_box_content_is_editable( $subscription ) {
		// First of all check if edit option is enabled.
		if ( ! 'yes' === get_option( 'ywsbs_subscription_box_editable', 'no' ) ) {
			return false;
		}

		// Then check payment method.
		$payment_method   = $subscription->get_payment_method();
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		switch ( $payment_method ) {
			case 'paypal':
				return false;

			case 'yith-stripe':
				// Subscriptions' renewal mode must be YWSBS Renews.
				$gateway = $payment_gateways['yith-stripe'] ?? null;
				if ( ! empty( $gateway ) ) {
					return 'ywsbs' === $gateway->get_option( 'renew_mode' );
				}

				return false;

			default:
				return true;
		}
	}
}

if ( ! function_exists( 'ywsbs_box_content_is_edit_enabled' ) ) {
	/**
	 * Check if subscription box content edit is enabled.
	 *  Check:
	 *   - subscription status must be active;
	 *   - subscription box product must be purchasable.
	 *
	 * @since 4.0.0
	 * @param YWSBS_Subscription $subscription The subscription object.
	 * @return boolean
	 */
	function ywsbs_box_content_is_edit_enabled( $subscription ) {

		if ( ! ywsbs_box_content_is_editable( $subscription ) ) {
			return false;
		}

		// Subscription status must be active.
		if ( ! $subscription->has_status( 'active' ) ) {
			return false;
		}

		// Check if subscription box product is purchasable.
		$box = $subscription->get_product();
		if ( ! $box || ! $box->is_purchasable() ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'ywsbs_is_a_box_subscription' ) ) {
	/**
	 * Check if given subscription is a subscription box
	 *
	 * @since 4.0.0
	 * @param integer|YWSBS_Subscription $subscription The subscription instance or subscription ID.
	 * @return boolean
	 */
	function ywsbs_is_a_box_subscription( $subscription ) {
		if ( ! $subscription instanceof YWSBS_Subscription ) {
			$subscription = ywsbs_get_subscription( absint( $subscription ) );
		}

		return ! empty( $subscription->get( 'box_content' ) ) && ! empty( $subscription->get( 'box_options' ) );
	}
}

if ( ! function_exists( 'ywsbs_is_cart_item_subscription_box' ) ) {
	/**
	 * Check if given cart item is a subscription box
	 *
	 * @since 4.0.0
	 * @param array $item The cart item to check.
	 * @return boolean
	 */
	function ywsbs_is_cart_item_subscription_box( $item ) {
		return ! empty( $item['_ywsbs_box_data'] ) && ! empty( $item['_ywsbs_box_data']['content'] ) && $item['data']->is_type( YWSBS_Subscription_Box::PRODUCT_TYPE );
	}
}

if ( ! function_exists( 'ywsbs_box_get_next_delivery_date' ) ) {
	/**
	 * Get next delivery date for given subscription box
	 *
	 * @since 4.0.0
	 * @param YWSBS_Subscription $subscription The subscription object.
	 * @param string             $format       The date format. Default is mysql format.
	 * @return string Date in 'Y-m-d H:i:s' format, or empty string if there are no delivery scheduled.
	 */
	function ywsbs_box_get_next_delivery_date( $subscription, $format = 'Y-m-d H:i:s' ) {
		if ( ! class_exists( 'YWSBS_Subscription_Delivery_Schedules' ) ) {
			return '';
		}

		$delivery_date = YWSBS_Subscription_Delivery_Schedules()->get_next_delivery_date( $subscription );

		return $delivery_date ? date_i18n( $format, strtotime( $delivery_date ) ) : '';
	}
}

if ( ! function_exists( 'ywsbs_box_calculate_next_delivery_date' ) ) {
	/**
	 * Calculate next delivery date starting from start date for given subscription box
	 *
	 * @since 4.0.0
	 * @param YWSBS_Subscription $subscription The subscription object.
	 * @param string             $format       The date format. Default is mysql format.
	 * @return string Date in 'Y-m-d H:i:s' format, or empty string if there are no delivery scheduled.
	 */
	function ywsbs_box_calculate_next_delivery_date( $subscription, $format = 'Y-m-d H:i:s' ) {
		if ( ! class_exists( 'YWSBS_Subscription_Delivery_Schedules' ) ) {
			return '';
		}

		$delivery_settings = $subscription->get( 'delivery_schedules' );
		if ( empty( $delivery_settings ) ) {
			return '';
		}

		$start_date = new DateTime();
		$start_date->setTimestamp( $subscription->get_payment_due_date() );

		$delivery_date = YWSBS_Subscription_Delivery_Schedules()->calculate_first_delivery_date( $delivery_settings, $start_date );

		return $delivery_date ? date_i18n( $format, $delivery_date ) : '';
	}
}

if ( ! function_exists( 'ywsbs_box_get_content_to_display' ) ) {
	/**
	 * Get box formatted content to display in frontend.
	 *
	 * @since 4.0.0
	 * @param array            $box_content The box content to format.
	 * @param WC_Product|false $box_product (Optional) The box product.
	 */
	function ywsbs_box_get_content_to_display( $box_content, $box_product = false ) {

		$formatted_box_content = array();
		$placeholder_image     = wc_placeholder_img_src( 'woocommerce_thumbnail' );
		$index                 = 1;

		$price_format    = get_woocommerce_price_format();
		$currency_symbol = get_woocommerce_currency_symbol();

		foreach ( $box_content as $step_id => $step_items ) {

			$formatted_box_step = array(
				'label' => $box_product ? $box_product->get_step_label( $step_id ) : sprintf( __( 'Step #%d', 'yith-woocommerce-subscription' ), $index++ ), // phpcs:ignore
				'items' => array(),
			);

			foreach ( $step_items as $item ) {

				$product    = wc_get_product( $item['product'] );
				$image_data = $product ? wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' ) : array();
				$image      = ! empty( $image_data ) ? $image_data[0] : $placeholder_image;

				$formatted_box_step['items'][] = array(
					'image' => $image,
					'name'  => sprintf( '%d x %s', $item['quantity'], $product->get_name() ),
					'price' => ! empty( $item['price'] ) ? sprintf( $price_format, $currency_symbol, wc_format_decimal( (float) $item['price'] ) ) : '',
				);
			}

			$formatted_box_content[] = $formatted_box_step;
		}

		return $formatted_box_content;
	}
}

if ( ! function_exists( 'ywsbs_prepare_box_content_for_edit' ) ) {
	/**
	 * Prepare given box content for edit
	 *
	 * @since 4.0.0
	 * @param array      $box_content The subscription box content to edit.
	 * @param WC_Product $box         The subscription box product.
	 * @return array
	 */
	function ywsbs_prepare_box_content_for_edit( $box_content, $box ) {
		$content = array();

		foreach ( $box_content as $step_id => $items ) {
			// Is step still valid for box?
			if ( ! $box->has_step( $step_id ) ) {
				continue;
			}

			$content[ $step_id ] = array();

			foreach ( $items as $item ) {
				$product = wc_get_product( $item['product'] );
				if ( ! $product || ! $product->is_purchasable() || ! $box->is_product_valid_for_step( $product, $step_id ) ) {
					continue;
				}

				$content[ $step_id ][] = array(
					'quantity' => intval( $item['quantity'] ),
					'total'    => ( $item['quantity'] * $product->get_price() ),
					'product'  => array(
						'id'     => $product->get_id(),
						'name'   => $product->get_name(),
						'price'  => $product->get_price(),
						'images' => ywsbs_product_get_image_data( $product, 'thumbnail' ),
					),
				);
			}
		}

		return $content;
	}
}

if ( ! function_exists( 'ywsbs_product_get_image_data' ) ) {
	/**
	 * Return an array of product images data.
	 *
	 * @since 4.0.0
	 * @param WC_Product   $product The product object.
	 * @param array|string $type    An array of image type size to retrieve, ar a single image size. Default is [ 'thumbnail', 'single' ].
	 * @return array
	 */
	function ywsbs_product_get_image_data( $product, $type = '' ) {

		$images = array();
		if ( empty( $type ) ) {
			$type = array( 'thumbnail', 'single' );
		}

		if ( is_array( $type ) ) {
			foreach ( $type as $t ) {
				$images = array_merge( $images, ywsbs_product_get_image_data( $product, $t ) );
			}
		} else {

			$size = 'woocommerce_' . $type;
			// Get the image data.
			$image_data = $product->get_image_id() ? wp_get_attachment_image_src( $product->get_image_id(), $size ) : false;

			if ( ! empty( $image_data ) ) {
				unset( $image_data[3] );
				$images[ $type ] = array_combine( array( 'src', 'width', 'height' ), $image_data );
			} else {
				$dimensions      = wc_get_image_size( $size );
				$images[ $type ] = array(
					'src'    => wc_placeholder_img_src( $size ),
					'width'  => $dimensions['width'],
					'height' => $dimensions['height'],
				);
			}
		}

		return apply_filters( 'ywsbs_product_get_image_data', $images, $type, $product );
	}
}
