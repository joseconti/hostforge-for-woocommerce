<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_Product Class.
 * Extend WC_Product and add specific methods for Subscription box products
 *
 * @class   YWSBS_Subscription_Box_Admin
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_Product' ) ) {
	/**
	 * Class YWSBS_Subscription_Box_Product
	 */
	class YWSBS_Subscription_Box_Product extends WC_Product {

		/**
		 * Get internal type.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function get_type() {
			return YWSBS_Subscription_Box::PRODUCT_TYPE;
		}

		/**
		 * Get product setup steps
		 *
		 * @since 4.0.0
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return array
		 */
		public function get_steps( $context = 'view' ) {
			$steps = $this->get_meta( '_ywsbs_box_steps', true, $context );

			// If context is view prevent get empty steps.
			if ( 'view' === $context ) {
				$steps = array_filter(
					$steps,
					function ( $step ) {
						return ( 'specific_products' === $step['content'] && ! empty( $step['products'] ) ) || ( 'specific_categories' === $step['content'] && ! empty( $step['categories'] ) ) || 'all_products' === $step['content'];
					}
				);
			}

			return $steps;
		}

		/**
		 * Get product setup step
		 *
		 * @since 4.0.0
		 * @param string $step_id The step ID to get.
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return array|bool
		 */
		public function get_step( $step_id, $context = 'view' ) {
			$steps = $this->get_steps( $context );

			return isset( $steps[ $step_id ] ) ? $steps[ $step_id ] : false;
		}

		/**
		 * Check if given step ID is valid for product.
		 *
		 * @since 4.0.0
		 * @param string $step_id The step ID to check.
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return bool
		 */
		public function has_step( $step_id, $context = 'view' ) {
			return ! ! $this->get_step( $step_id, $context );
		}

		/**
		 * Get product setup step label
		 *
		 * @since 4.0.0
		 * @param string $step_id The step ID to get.
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return string
		 */
		public function get_step_label( $step_id, $context = 'view' ) {
			$step = $this->get_step( $step_id, $context );

			return $step['label'] ?? '';
		}

		/**
		 * Check if given step has quantity threshold set
		 *
		 * @since 4.0.0
		 * @param string $step_id The step ID to check.
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return bool
		 */
		public function has_step_quantity_threshold( $step_id, $context = 'view' ) {
			$step = $this->get_step( $step_id, $context );
			return ( $step && isset( $step['enabled_threshold'] ) ) ? 'yes' === $step['enabled_threshold'] : false;
		}

		/**
		 * Get given step minimum quantity threshold
		 * If threshold is not set, return 0.
		 *
		 * @since 4.0.0
		 * @param string $step_id The step ID to check.
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return int
		 */
		public function get_step_minimum_quantity_threshold( $step_id, $context = 'view' ) {
			if ( ! $this->has_step_quantity_threshold( $step_id, $context ) ) {
				return 0;
			}

			$step = $this->get_step( $step_id, $context );

			return (int) ( $step['threshold']['min'] ?? 0 );
		}

		/**
		 * Get given step maximum quantity threshold
		 * If threshold is not set, return 0.
		 *
		 * @since 4.0.0
		 * @param string $step_id The step ID to check.
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return int
		 */
		public function get_step_maximum_quantity_threshold( $step_id, $context = 'view' ) {
			if ( ! $this->has_step_quantity_threshold( $step_id, $context ) ) {
				return 0;
			}

			$step = $this->get_step( $step_id, $context );

			return (int) ( $step['threshold']['max'] ?? 0 );
		}

		/**
		 * Get given step single unit maximum quantity threshold
		 * If threshold is not set, return 0.
		 *
		 * @since 4.0.0
		 * @param string $step_id The step ID to check.
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return int
		 */
		public function get_step_maximum_unit_threshold( $step_id, $context = 'view' ) {
			if ( ! $this->has_step_quantity_threshold( $step_id, $context ) ) {
				return 0;
			}

			$step = $this->get_step( $step_id, $context );

			return (int) ( $step['threshold']['max_units'] ?? 0 );
		}

		/**
		 * Check if given product is valid for given step.
		 *
		 * @since 4.0.0
		 * @param string|integer|WC_Product $product The product object or product ID to check.
		 * @param string                    $step_id The step ID.
		 * @return bool
		 */
		public function is_product_valid_for_step( $product, $step_id ) {
			$step = $this->get_step( $step_id );
			if ( empty( $step ) ) {
				return false;
			}

			$product = $product instanceof WC_Product ? $product->get_id() : absint( $product );
			switch ( $step['content'] ) {
				case 'specific_products':
					return in_array( $product, array_map( 'absint', $step['products'] ?? array() ), true );

				case 'specific_categories':
					return is_object_in_term( $product, 'product_cat', $step['categories'] ?? array() );

				default:
					return true;
			}
		}

		/**
		 * Get step related products
		 *
		 * @since 4.0.0
		 * @param string $step_id The step ID.
		 * @param array  $args (Optional) Additional arguments to use in the query. Default empty array.
		 * @return array
		 */
		public function get_step_products( $step_id, $args = array() ) {
			$step = $this->get_step( $step_id );
			if ( empty( $step ) ) {
				return array();
			}

			// Set limit based on module options.
			$limit = ( 'yes' === get_option( 'ywsbs_subscription_box_enable_pagination', 'yes' ) ) ? absint( get_option( 'ywsbs_subscription_box_products_per_page', 25 ) ) : -1;
			// Merge with default.
			$query_args = array_merge(
				array(
					'type'         => 'simple',
					'status'       => 'publish',
					'stock_status' => 'instock',
					'limit'        => $limit,
				),
				$args
			);

			switch ( $step['content'] ) {
				case 'all_products':
					break;

				case 'specific_products':
					if ( empty( $step['products'] ) ) {
						return array();
					}

					$query_args['include'] = array_map( 'absint', $step['products'] );
					$query_args['orderby'] = 'include';
					break;

				case 'specific_categories':
					if ( empty( $step['categories'] ) ) {
						return array();
					}

					$query_args['category'] = array_filter(
						array_map(
							function ( $category_id ) {
								$term = get_term( $category_id, 'product_cat' );
								return $term instanceof WP_Term ? $term->slug : '';
							},
							$step['categories']
						)
					);
					break;
			}

			$query_results = wc_get_products( $query_args );

			return apply_filters( 'yith_ywsbs_step_products_query_results', $query_results, $step, $query_args );
		}

		/**
		 * This product must be sold individually.
		 *
		 * @since 4.0.0
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return boolean
		 */
		public function get_sold_individually( $context = 'view' ) {
			return true;
		}

		/**
		 * Get product price calculate type
		 *
		 * @since 4.0.0
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return array
		 */
		public function get_price_type( $context = 'view' ) {
			return $this->get_meta( '_ywsbs_box_price_type', true, $context );
		}

		/**
		 * Get product discount type
		 *
		 * @since 4.0.0
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return boolean
		 */
		public function get_discount_type( $context = 'view' ) {
			return $this->get_meta( '_ywsbs_box_discount_type', true, $context );
		}

		/**
		 * Get product discount value
		 *
		 * @since 4.0.0
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return string
		 */
		public function get_discount_value( $context = 'view' ) {
			return $this->get_meta( '_ywsbs_box_discount_value', true, $context );
		}

		/**
		 * Return the discount amount if any discount is applied to the product.
		 *
		 * @since 4.0.0
		 * @param  string|float $price (Optional) The price to calculate discount for. Default is regular price.
		 * @param  string       $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return float
		 */
		public function get_discount_amount( $price = 0, $context = 'view' ) {
			if ( empty( $price ) ) {
				$price = $this->get_regular_price( $context );
			}

			$price = (float) $price;
			if ( empty( $price ) || ! $this->is_on_sale( $context ) ) {
				return 0;
			}

			$discount_amount = 0;
			$discount_value  = $this->get_discount_value( $context );
			switch ( $this->get_discount_type( $context ) ) {
				case 'fixed':
					$discount_amount = ( $discount_value < $price ) ? $discount_value : $price;
					break;
				case 'percentage':
					// Prevent wrong percentage.
					$discount_value  = ( $discount_value < 0 ) ? 0 : ( $discount_value > 100 ? 100 : $discount_value );
					$discount_amount = ( $price * $discount_value ) / 100;
					break;
			}

			return wc_format_decimal( $discount_amount );
		}

		/**
		 * Return the discounted price if any discount is applied to the product.
		 *
		 * @since 4.0.0
		 * @param  string|float $price (Optional) The price to calculate discount for. Default is regular price.
		 * @param  string       $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return float|string
		 */
		public function get_discounted_price( $price = 0, $context = 'view' ) {
			if ( empty( $price ) ) {
				$price = $this->get_regular_price( $context );
			}

			$discount_amount = $this->get_discount_amount( $price, $context );
			if ( empty( $discount_amount ) ) {
				return '';
			}

			return wc_format_decimal( $price - $discount_amount );
		}

		/**
		 * Check if this product has price threshold
		 *
		 * @since 4.0.0
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return boolean True is has price threshold, false otherwise.
		 */
		public function has_price_threshold( $context = 'view' ) {
			return 'yes' === $this->get_meta( '_ywsbs_box_enable_price_threshold', true, $context ) &&
				'sum' === $this->get_price_type( $context ) &&
				! empty( $this->get_price_threshold( $context ) );
		}

		/**
		 * Get the price threshold
		 *
		 * @since 4.0.0
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return array
		 */
		public function get_price_threshold( $context = 'view' ) {
			return $this->get_meta( '_ywsbs_box_price_threshold', true, $context );
		}

		/**
		 * Get product min price threshold
		 *
		 * @since 4.0.0
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return string
		 */
		public function get_min_price_threshold( $context = 'view' ) {
			$threshold = $this->get_price_threshold( $context );
			return $threshold['min'] ?? '';
		}

		/**
		 * Get product max price threshold
		 *
		 * @since 4.0.0
		 * @param  string $context (Optional) What the value is for. Valid values are view and edit. Default is view.
		 * @return string
		 */
		public function get_max_price_threshold( $context = 'view' ) {
			$threshold = $this->get_price_threshold( $context );
			return $threshold['max'] ?? '';
		}

		/**
		 * Returns whether or not the product is on sale.
		 *
		 * @since 4.0.0
		 * @param  string $context What the value is for. Valid values are view and edit.
		 * @return bool
		 */
		public function is_on_sale( $context = 'view' ) {
			return 'yes' === $this->get_meta( '_ywsbs_box_discount', true, $context ) && ! empty( $this->get_discount_value( $context ) );
		}

		/**
		 * Subscription box are always in stock.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function is_in_stock() {
			return true;
		}

		/**
		 * Subscription box are always in stock.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function managing_stock() {
			return false;
		}

		/**
		 * Returns false if the product cannot be bought.
		 * A box for be purchasable must have the configuration completed and at least a step configured.
		 *
		 * @since 4.0.0
		 * @return bool
		 */
		public function is_purchasable() {
			return apply_filters(
				'woocommerce_is_purchasable',
				$this->exists() && ( 'publish' === $this->get_status() || current_user_can( 'edit_post', $this->get_id() ) ) &&
				( 'sum' === $this->get_price_type() || '' !== $this->get_price() ) &&
				! empty( $this->get_steps() ),
				$this
			);
		}

		/**
		 * Get the add to cart button text for the single page.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function single_add_to_cart_text() {
			return apply_filters( 'woocommerce_product_single_add_to_cart_text', __( 'Subscribe', 'yith-woocommerce-subscription' ), $this );
		}

		/**
		 * Get the add to cart button text.
		 *
		 * @since 4.0.0
		 * @return string
		 */
		public function add_to_cart_text() {
			return apply_filters( 'woocommerce_product_add_to_cart_text', __( 'Subscribe', 'yith-woocommerce-subscription' ), $this );
		}
	}
}
