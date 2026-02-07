<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Cart Class.
 *
 * @class   YWSBS_Subscription_Cart
 * @since   1.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Cart' ) ) {

	/**
	 * Class YWSBS_Subscription_Cart
	 */
	class YWSBS_Subscription_Cart extends YWSBS_Subscription_Cart_Legacy {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Temporary Cart.
		 *
		 * @var WC_Cart
		 */
		private $actual_cart;

		/**
		 * List of not shippable products
		 *
		 * @var array
		 */
		protected $list_of_not_shippable = array();

		/**
		 * List of not shippable products
		 *
		 * @var array
		 */
		protected $clear_shipping = false;

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used.
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			// change prices in calculation totals to add the fee amount.
			add_action( 'woocommerce_before_calculate_totals', array( $this, 'add_change_prices_filter' ), 10 );
			add_action( 'woocommerce_calculate_totals', array( $this, 'remove_change_prices_filter' ), 10 );
			add_action( 'woocommerce_after_calculate_totals', array( $this, 'remove_change_prices_filter' ), 10 );

			// Change prices and totals in cart.
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'change_cart_item_price_html' ), 99, 2 );
			add_filter( 'woocommerce_cart_item_price', array( $this, 'change_cart_item_price_html' ), 99, 3 );

			add_filter( 'woocommerce_cart_needs_payment', array( $this, 'cart_needs_payment' ), 10, 2 );
			add_filter( 'ywsbs_signup_fee_in_cart', array( $this, 'change_signup_fee_in_cart' ), 10, 2 );

			// Cart and checkout validation.
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'cart_item_validate' ), 10, 4 );
			add_action( 'woocommerce_available_payment_gateways', array( $this, 'disable_gateways' ), 100 );

			add_filter( 'woocommerce_add_cart_item_data', array( $this, 'set_subscription_meta_on_cart' ), 15, 3 );

			add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'cart_recurring_totals' ), 10 );
			add_action( 'woocommerce_review_order_after_order_total', array( $this, 'cart_recurring_totals' ), 10 );

			// remove the shipping for sync products not prorated.
			add_action( 'woocommerce_before_checkout_process', array( $this, 'sync_on_process_checkout' ), 200 );
			add_action( 'woocommerce_checkout_update_customer', array( $this, 'check_shipping_to_clear' ), 200 );

			add_filter( 'woocommerce_before_calculate_totals', array( $this, 'before_calculate_totals' ), 200 );

			add_filter( 'woocommerce_checkout_registration_required', array( $this, 'force_registration' ), 1000 );
			add_action( 'woocommerce_before_checkout_process', array( $this, 'force_registration_during_checkout' ), 10 );

			// WC Cart and Checkout Blocks Integration.
			$this->register_endpoint_data();

			add_filter( 'woocommerce_get_item_data', array( $this, 'woocommerce_get_item_data' ), 10, 2 );
			add_action( 'template_redirect', array( $this, 'apply_filters_to_cart_and_checkout_blocks' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_hide_subtotal_row' ), 999 );
		}

		/**
		 * Register endpoint data for cart block
		 *
		 * @since 4.7.0
		 * @return void
		 */
		public function register_endpoint_data() {
			woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'      => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
					'namespace'     => 'ywsbs_recurring_totals',
					'data_callback' => function () {
						return array( 'html' => $this->cart_recurring_totals( false ) );
					},
				)
			);
		}

		/**
		 * Add filter for Cart and Checkout Blocks Integration
		 *
		 * @since 3.0.0
		 */
		public function apply_filters_to_cart_and_checkout_blocks() {

			if ( has_block( 'woocommerce/cart-totals-block' ) ) {
				add_filter( 'render_block_woocommerce/cart-order-summary-block', array( $this, 'add_resume_subscription_totals_on_cart_block' ), 10, 1 );
			}

			if ( has_block( 'woocommerce/checkout-totals-block' ) ) {
				add_filter( 'render_block_woocommerce/checkout-order-summary-taxes-block', array( $this, 'add_resume_subscription_totals_on_cart_block' ), 10, 1 );
			}
		}

		/**
		 * Add recurring total to cart/checkout summary block total
		 *
		 * @since 3.0.0
		 * @param string $content Current content.
		 * @return string
		 */
		public function add_resume_subscription_totals_on_cart_block( $content = '' ) {
			return $content . '<div class="ywsbs-recurring-totals-items wc-block-components-totals-wrapper">' . $this->cart_recurring_totals( false ) . '</div>';
		}

		/**
		 * Add cart item data info inside the cart item data on WC Cart Block.
		 *
		 * @since 3.0.0
		 * @param array $data      Data.
		 * @param array $cart_item Cart item data.
		 * @return array|mixed
		 */
		public function woocommerce_get_item_data( $data = array(), $cart_item = array() ) {
			if ( isset( $cart_item['ywsbs-subscription-info'] ) ) {
				// add subscription info.
				$data[] = array_merge(
					array(
						'name'   => 'ywsbs-subscription-info',
						'hidden' => true,
					),
					$cart_item['ywsbs-subscription-info']
				);
				// add a formatted suffix that will be used to render.
				$data[] = array(
					'name'   => 'ywsbs-price-html',
					'hidden' => true,
					'value'  => $this->change_cart_item_price_html( '', $cart_item ),
				);
			}
			if ( isset( $cart_item['ywsbs-subscription-resubscribe'] ) ) {
				// add subscription info.
				$data[] = array_merge(
					array(
						'name'   => 'ywsbs-subscription-resubscribe',
						'hidden' => true,
					),
					$cart_item['ywsbs-subscription-resubscribe']
				);
			}
			if ( isset( $cart_item['ywsbs-subscription-switch'] ) ) {
				// add subscription info.
				$data[] = array_merge(
					array(
						'name'   => 'ywsbs-subscription-switch',
						'hidden' => true,
					),
					$cart_item['ywsbs-subscription-switch']
				);
			}
			return $data;
		}

		/**
		 * Clear the shipping methods
		 */
		public function clear_shipping() {
			$this->clear_shipping = true;
			WC()->session->set( 'chosen_shipping_methods', array() );
		}

		/**
		 * Clear the shipping methods
		 */
		public function check_shipping_to_clear() {
			if ( $this->clear_shipping ) {
				WC()->session->set( 'chosen_shipping_methods', array() );
			}
		}

		/**
		 * During the checkout process remove the shipping from order.
		 */
		public function sync_on_process_checkout() {
			$ywsbs_sync_on_process_checkout = WC()->session->get( 'ywsbs_sync_on_process_checkout', false );

			if ( $ywsbs_sync_on_process_checkout ) {
				add_filter( 'woocommerce_after_checkout_validation', array( $this, 'clear_shipping' ), 200 );

			}
		}

		/**
		 * Remove temporary the shipping calculation for the products that are synchronized.
		 *
		 * @return void
		 */
		public function before_calculate_totals() {
			if ( ! self::cart_has_subscriptions() ) {
				return;
			}

			if ( WC()->session->get( 'reload_checkout', false ) === true ) {
				add_filter( 'woocommerce_after_checkout_validation', array( $this, 'clear_shipping' ), 200 );
			}

			WC()->session->set( 'ywsbs_sync_on_process_checkout', false );
			WC()->session->set( 'ywsbs_shipping_methods', WC()->session->get( 'chosen_shipping_methods', array() ) );

			$add_filter     = true; // check if there are only synch subscription on cart.
			$prorate_option = get_option( 'ywsbs_sync_first_payment', 'no' );

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$apply_shipping_on_sync =
					'no' === $prorate_option && ywsbs_is_subscription_product( $cart_item['data'] ) && $cart_item['data']->needs_shipping() &&
					(
						isset( $cart_item['ywsbs-subscription-info']['sync'] ) && 1 === (int) $cart_item['ywsbs-subscription-info']['sync'] &&
						( empty( $cart_item['data']->get_price() ) || ( ! empty( $cart_item['ywsbs-subscription-info']['fee'] ) && 0 == ( $cart_item['data']->get_price() - apply_filters( 'ywsbs_product_fee', $cart_item['ywsbs-subscription-info']['fee'], $cart_item['data'] ) ) ) ) //phpcs:ignore
					);

				if ( apply_filters( 'ywsbs_apply_shipping_on_synch_subscription', $apply_shipping_on_sync, $cart_item, $cart_item_key ) ) {
					$product_id = $cart_item['data']->get_id();
					! in_array( $product_id, $this->list_of_not_shippable, true ) && array_push( $this->list_of_not_shippable, $cart_item['data']->get_id() );
				} else {
					$add_filter = false;
				}
			}

			if ( ! empty( $this->list_of_not_shippable ) ) {
				if ( $add_filter ) {
					add_filter( 'woocommerce_calculated_total', array( $this, 'remove_shipping_cost_from_calculate_totals' ), 200, 2 );
					add_filter( 'woocommerce_cart_tax_totals', array( $this, 'remove_tax_shipping_cost_from_calculate_totals' ), 200, 2 );
					WC()->session->set( 'ywsbs_sync_on_process_checkout', true );
				} else {
					add_filter( 'woocommerce_product_needs_shipping', array( $this, 'maybe_not_shippable' ), 100, 2 );
					add_filter( 'woocommerce_cart_needs_shipping_address', '__return_true' );
					WC()->session->set( 'ywsbs_sync_on_process_checkout', false );
				}
			}
		}

		/**
		 * Remove the shipping amount from cart if there are only sync subscription on cart with price 0.
		 *
		 * @param float   $total Cart total.
		 * @param WC_Cart $cart  Cart.
		 *
		 * @return mixed
		 */
		public function remove_shipping_cost_from_calculate_totals( $total, $cart ) {
			$totals = $cart->get_totals();
			$total -= ( $totals['shipping_total'] + $totals['shipping_tax'] );

			return $total;
		}

		/**
		 * Remove the shipping amount from cart if there are only sync subscription on cart with price 0.
		 *
		 * @param array   $total Cart total.
		 * @param WC_Cart $cart  Cart.
		 *
		 * @return mixed
		 */
		public function remove_tax_shipping_cost_from_calculate_totals( $total, $cart ) {

			foreach ( $cart->get_shipping_taxes() as $key => $value ) {
				foreach ( $total as $k => $t ) {
					if ( $t->tax_rate_id === $key ) {
						$total[ $k ]->amount -= $value;

						if ( empty( $total[ $k ]->amount ) ) {
							unset( $total[ $k ] );
						} else {
							$total[ $k ]->formatted_amount = wc_price( $total[ $k ]->amount );
						}
					}
				}
			}

			return $total;
		}

		/**
		 * Return false for the product saved on list.
		 *
		 * @param bool       $value   Value passed to filter.
		 * @param WC_Product $product Product.
		 *
		 * @return bool
		 */
		public function maybe_not_shippable( $value, $product ) {
			if ( in_array( $product->get_id(), $this->list_of_not_shippable, true ) ) {
				return false;
			}

			return $value;
		}

		/**
		 * Add change prices filter.
		 *
		 * @since 1.4.6
		 */
		public function add_change_prices_filter() {
			add_filter( 'woocommerce_product_get_price', array( $this, 'change_prices_for_calculation' ), 100, 2 );
			add_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_prices_for_calculation' ), 100, 2 );
		}

		/**
		 * Remove the change price filter.
		 *
		 * @since 1.4.6
		 */
		public function remove_change_prices_filter() {
			remove_filter( 'woocommerce_product_get_price', array( $this, 'change_prices_for_calculation' ), 100 );
			remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'change_prices_for_calculation' ), 100 );
		}

		/**
		 * Add additional cart item data to the subscription products.
		 *
		 * @param array $cart_item_data Cart item data.
		 * @param int   $product_id     Product id.
		 * @param int   $variation_id   Variation id.
		 * @return array
		 */
		public function set_subscription_meta_on_cart( $cart_item_data, $product_id, $variation_id ) {
			$product_id = empty( $variation_id ) ? $product_id : $variation_id;
			if ( ! ywsbs_is_subscription_product( $product_id ) ) {
				return $cart_item_data;
			}

			$product                     = wc_get_product( $product_id );
			$cart_item_subscription_data = $this->get_subscription_meta_on_cart( $product );

			if ( $cart_item_subscription_data ) {
				if ( is_array( $cart_item_data ) ) {
					$cart_item_data['ywsbs-subscription-info'] = $cart_item_subscription_data;
				} else {
					$cart_item_data = array( 'ywsbs-subscription-info' => $cart_item_subscription_data );
				}
			}
			return $cart_item_data;
		}


		/**
		 * Get the subscription meta
		 *
		 * @param WC_Product $product Product.
		 */
		public function get_subscription_meta_on_cart( $product ) {
			$cart_item_subscription_data = array();
			if ( $product ) {
				$cart_item_subscription_data = array(
					'recurring_price'       => $product->get_price( 'edit' ),
					'price_is_per'          => $product->get_meta( '_ywsbs_price_is_per' ),
					'price_time_option'     => $product->get_meta( '_ywsbs_price_time_option' ),
					'fee'                   => ywsbs_get_product_fee( $product, 'edit' ),
					'trial_per'             => ywsbs_get_product_trial( $product ),
					'trial_time_option'     => $product->get_meta( '_ywsbs_trial_time_option' ),
					'max_length'            => YWSBS_Subscription_Helper::get_subscription_product_max_length( $product ),
					'next_payment_due_date' => '',
				);
			}

			return apply_filters( 'ywsbs_subscription_meta_on_cart', $cart_item_subscription_data, $product );
		}


		/**
		 * Change price
		 *
		 * @param float      $price   Price.
		 * @param WC_Product $product WC_Product.
		 *
		 * @return mixed
		 */
		public function change_prices_for_calculation( $price, $product ) {

			// Integration with YITH WC Request a Quote.
			$is_raq = $product->get_meta( 'ywraq_product' );
			if ( ! ywsbs_is_subscription_product( $product->get_id() ) || $is_raq ) {
				return $price;
			}

			$signup_fee   = ywsbs_get_product_fee( $product );
			$trial_period = ywsbs_get_product_trial( $product );

			if ( WC()->cart ) {
				foreach ( WC()->cart->get_cart() as $cart_item_element ) {
					$product_in_cart   = (int) $cart_item_element['product_id'];
					$variation_in_cart = (int) $cart_item_element['variation_id'];
					if ( $product->get_id() === $variation_in_cart || $product->get_id() === $product_in_cart ) {
						if ( empty( $cart_item_element['ywsbs-subscription-info'] ) ) {
							continue;
						}

						if ( isset( $cart_item_element['ywsbs-subscription-info']['fee'] ) ) {
							$signup_fee = apply_filters( 'ywsbs_product_fee', $cart_item_element['ywsbs-subscription-info']['fee'], $cart_item_element['data'] );
						}

						if ( isset( $cart_item_element['ywsbs-subscription-info']['trial_per'] ) ) {
							$trial_period = $cart_item_element['ywsbs-subscription-info']['trial_per'];
						}

						break;
					}
				}
			}

			if ( ! empty( $trial_period ) ) {
				$price = 0;
			}

			if ( ! empty( $signup_fee ) ) {
				$price += floatval( $signup_fee );
			}

			return $price;
		}

		/**
		 * Return the subscription total amount of a product.
		 *
		 * @param WC_Product $product           Product.
		 * @param int        $quantity          Quantity.
		 * @param bool|array $subscription_info Subscription information.
		 *
		 * @return string
		 */
		public function get_formatted_subscription_total_amount( $product, $quantity, $subscription_info = false ) {

			$sbs_total_format = '';
			$max_length       = YWSBS_Subscription_Helper::get_subscription_product_max_length( $product );

			if ( $max_length && $max_length > 1 && 'yes' === get_option( 'ywsbs_subscription_total_amount', 'no' ) ) {

				$sbs_total_format         = get_option( 'ywsbs_total_subscription_length_text', esc_html_x( 'Subscription total for {{sub-time}}: {{sub-total}}', 'do not translate the text inside the brackets', 'yith-woocommerce-subscription' ) );
				$max_length_text          = YWSBS_Subscription_Helper::get_subscription_max_length_formatted_for_price( $product );
				$total_subscription_price = YWSBS_Subscription_Helper::get_total_subscription_price( $product, $subscription_info );

				$total_subscription_price = wc_get_price_to_display(
					$product,
					array(
						'qty'             => $quantity,
						'price'           => $total_subscription_price,
						'display_context' => is_cart() || is_checkout() ? 'cart' : 'shop',
					)
				);
				$sbs_total_format         = str_replace( '{{sub-time}}', $max_length_text, $sbs_total_format );
				$sbs_total_format         = str_replace( '{{sub-total}}', wc_price( $total_subscription_price ), $sbs_total_format );

				if ( wc_tax_enabled() ) {
					$sbs_total_format .= ' <small class="tax_label">' . ( 'excl' === WC()->cart->get_tax_price_display_mode() ? WC()->countries->ex_tax_or_vat() : WC()->countries->inc_tax_or_vat() ) . '</small>';
				}

				$sbs_total_format = '<div class="ywsbs-subscription-total">' . $sbs_total_format . '</div>';

			}

			return apply_filters( 'ywsbs_checkout_subscription_total_amount', $sbs_total_format, $product, $quantity );
		}


		/**
		 * Return the subscription next billing date.
		 *
		 * @param WC_Product $product           Product.
		 * @param bool|array $subscription_info Subscription information.
		 *
		 * @return string
		 */
		public function get_formatted_subscription_next_billing_date( $product, $subscription_info = false ) {

			if ( 'yes' === get_option( 'ywsbs_show_next_billing_date', 'no' ) ) {
				$billing_date = ( $subscription_info && ! empty( $subscription_info['next_payment_due_date'] ) ) ? $subscription_info['next_payment_due_date'] : YWSBS_Subscription_Helper::get_billing_payment_due_date( $product );

				// Billing data must be set and or a trial is set or the subscription length is different from the recurring period.
				if ( $billing_date && ( ywsbs_get_product_trial( $product ) || ( empty( $subscription_info['max_length'] ) || $subscription_info['max_length'] !== $subscription_info['price_is_per'] ) ) ) {

					$billing_date_text = empty( ywsbs_get_product_trial( $product ) )
						? get_option( 'ywsbs_show_next_billing_date_text', esc_html__( 'Next billing on:', 'yith-woocommerce-subscription' ) )
						: get_option( 'ywsbs_show_next_billing_date_text_for_trial', esc_html__( 'First billing on:', 'yith-woocommerce-subscription' ) );

					$html = sprintf( '<div class="ywsbs-next-billing-date"><strong>%s</strong> %s</div>', $billing_date_text, date_i18n( wc_date_format(), $billing_date ) );
				}
			}

			return apply_filters( 'ywsbs_checkout_subscription_next_billing_date', $html ?? '', $product );
		}

		/**
		 * Change cart item price HTML
		 *
		 * @param string $price_html    HTML Price.
		 * @param array  $cart_item     Cart item.
		 * @return string
		 */
		public function change_cart_item_price_html( $price_html, $cart_item ) {

			$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( ! ywsbs_is_subscription_product( $product_id ) || empty( $cart_item['data'] ) ) {
				return $price_html;
			}

			$type     = str_replace( 'woocommerce_cart_item_', '', current_action() );
			$quantity = 'subtotal' === $type ? $cart_item['quantity'] : 1;
			$product  = $cart_item['data'];

			if ( isset( $cart_item['ywsbs-subscription-info'] ) ) {
				$subscription_info = $cart_item['ywsbs-subscription-info'];
			} else {
				$subscription_info = $this->get_subscription_meta_on_cart( $product );
			}

			// Let third party plugins filter product price before print.
			$product_price = apply_filters( "ywsbs_cart_item_{$type}", $product->get_price( 'edit' ), $product, $cart_item );

			// Get product base price.
			$price = wc_get_price_to_display(
				$product,
				array(
					'qty'   => $quantity,
					'price' => $product_price,
				)
			);

			if ( 'subtotal' === $type && is_cart() && ! apply_filters( 'ywsbs_force_detailed_price_on_cart_item', false, $cart_item ) ) {
				// Get signup fee if any.
				$signup_fee = apply_filters( 'ywsbs_product_fee', $subscription_info['fee'] ?? 0, $product );
				if ( ! empty( $signup_fee ) ) {
					$price += wc_get_price_to_display(
						$product,
						array(
							'qty'   => $quantity,
							'price' => $signup_fee,
						)
					);
				}

				$price_html = wc_price( $price );

			} else {

				$price_html = '<div class="ywsbs-wrapper"><div class="ywsbs-price">';

				if ( empty( $subscription_info['sync'] ) ) {
					$price_html .= wc_price( $price );
				} else {

					$recurring_price = wc_get_price_to_display(
						$cart_item['data'],
						array(
							'qty'   => $quantity,
							'price' => YWSBS_Subscription_Helper::get_subscription_recurring_price( $cart_item['data'], $cart_item['ywsbs-subscription-info'] ),
						)
					);

					// translators: placeholder 1. first price, 2. valid period of the first price, 3. end price.
					$price_html .= sprintf( __( '%1$s until %2$s then %3$s', 'yith-woocommerce-subscription' ), wc_price( $price ), date_i18n( wc_date_format(), $subscription_info['next_payment_due_date'] ), wc_price( $recurring_price ) );
				}

				// Add subscription period.
				$price_html .= '<span class="price_time_opt"> / ' . YWSBS_Subscription_Helper::get_subscription_period_for_price( $product, $subscription_info ) . '</span>';

				// Maybe add max length.
				$max_length = YWSBS_Subscription_Helper::get_subscription_max_length_formatted_for_price( $product, $subscription_info );
				if ( ! empty( $max_length ) ) {
					$max_length = ! empty( $max_length ) ? esc_html__( ' for ', 'yith-woocommerce-subscription' ) . $max_length : '';

					$price_html .= '<span class="ywsbs-max-lenght">' . $max_length . '</span>';
				}

				$price_html .= '</div>';

				// Maybe add signup fee price.
				$signup_fee = trim( YWSBS_Subscription_Helper::get_fee_price( $product, $quantity, null, $subscription_info ) );
				if ( ! empty( $signup_fee ) ) {
					$price_html .= '<span class="ywsbs-fee-price">' . $signup_fee . '</span>';
				}

				// Maybe add trial price.
				$trial_price = trim( YWSBS_Subscription_Helper::get_trial_price( $product, 'cart', $subscription_info ) );
				if ( ! empty( $trial_price ) ) {
					$price_html .= '<span class="ywsbs-trial-price">' . $trial_price . '</span>';
				}

				$price_html .= '</div>';

			}

			return apply_filters( "ywsbs_change_cart_item_{$type}_html", $price_html, $cart_item );
		}

		/**
		 * Check if there are subscription upgrade in progress and change the fee
		 *
		 * @param float $fee       Fee amount.
		 * @param array $cart_item Cart Item.
		 *
		 * @return bool
		 */
		public function change_signup_fee_in_cart( $fee, $cart_item ) {

			$signup_fee = $fee;

			// add fee is gap payment is available and choosed by user.
			$product = $cart_item['data'];
			$id      = $product->get_id();

			$subscription_info = get_user_meta( get_current_user_id(), 'ywsbs_upgrade_' . $id, true );
			$gap_payment       = $product->get_meta( '_ywsbs_gap_payment' );
			$pay_gap           = 0;

			if ( ! empty( $subscription_info ) && isset( $subscription_info['pay_gap'] ) ) {
				$pay_gap = $subscription_info['pay_gap'];
			}

			if ( 'yes' === $gap_payment && $pay_gap > 0 ) {
				// change the fee of the subscription adding the total amount of the previous rates.
				$signup_fee += $pay_gap;
			}

			return $signup_fee;
		}

		/**
		 * Only a subscription can be added to the cart this method check if there's
		 * a subscription in cart and remove the element if the next product to add is another subscription
		 *
		 * @since  1.0.0
		 * @param bool $valid Is valid boolean.
		 * @param int  $product_id   Product id.
		 * @param int  $quantity     Quantity.
		 * @param int  $variation_id Variation id.
		 * @return bool
		 */
		public function cart_item_validate( $valid, $product_id, $quantity, $variation_id = 0 ) {

			$product_id = (int) ( ! empty( $variation_id ) ? $variation_id : $product_id );

			/**
			 * Current product.
			 *
			 * @var WC_Product
			 */
			$product = wc_get_product( $product_id );

			if ( ! YITH_WC_Subscription_Limit::is_purchasable( true, $product ) ) {
				$message = esc_html__( 'You already have an active subscription to this product.', 'yith-woocommerce-subscription' );
				wc_add_notice( $message, 'error' );

				return false;
			}

			if ( ywsbs_enable_subscriptions_multiple() ) {
				return $valid;
			}

			if ( ywsbs_is_subscription_product( $product ) ) {

				$item_keys = self::cart_has_subscriptions();

				if ( $item_keys ) {
					foreach ( $item_keys as $item_key ) {
						$current_item = WC()->cart->get_cart_item( $item_key );
						if ( ! empty( $current_item ) ) {
							$item_id = (int) ( ! empty( $current_item['variation_id'] ) ? $current_item['variation_id'] : $current_item['product_id'] );
							if ( $item_id !== $product_id ) {
								self::remove_subscription_from_cart( $item_key );
								$message = __( 'A subscription has been removed from your cart. You cannot purchase different subscriptions at the same time.', 'yith-woocommerce-subscription' );
								wc_add_notice( $message, 'error' );
							}
						}
					}
				}
			}

			return $valid;
		}

		/**
		 * Disable gateways that don't support subscription on cart.
		 *
		 * @param array $gateways Gateways list.
		 */
		public function disable_gateways( $gateways ) {

			if ( ! is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) ) {
				return $gateways;
			}

			if ( is_wc_endpoint_url( 'order-pay' ) ) {
				$order_id             = get_query_var( 'order-pay' );
				$order                = wc_get_order( $order_id );
				$order_items          = $order ? $order->get_items() : array();
				$subscription_on_cart = array();

				foreach ( $order_items as $order_item ) {

					/**
					 * WC_Product
					 *
					 * @var $_product
					 */

					$product    = $order_item->get_product();
					$product_id = $product->get_id();

					if ( ywsbs_is_subscription_product( $product_id ) ) {
						array_push( $subscription_on_cart, $order_item );
					}
				}
			} else {
				$subscription_on_cart = self::cart_has_subscriptions();
			}

			// There are no subscription in cart, return all gateways.
			if ( empty( $subscription_on_cart ) ) {
				return $gateways;
			}

			$manual_renews_allowed = 'yes' === get_option( 'ywsbs_enable_manual_renews', 'yes' );
			$gateways              = array_filter(
				$gateways,
				function ( $gateway ) use ( $manual_renews_allowed, $subscription_on_cart ) {
					// If manual renew is allowed always add the gateway.
					$include = ! $gateway->supports( 'yith_subscriptions' ) ? $manual_renews_allowed : ( count( $subscription_on_cart ) < 2 || $gateway->supports( 'yith_subscriptions_multiple' ) );
					return apply_filters( 'ywsbs_checkout_include_gateway', $include, $gateway, $manual_renews_allowed, $subscription_on_cart );
				}
			);

			return $gateways;
		}

		/**
		 * Check if on cart there are subscriptions with signup fee.
		 *
		 * @return bool
		 */
		public static function cart_has_subscription_with_signup() {

			$check = false;

			if ( isset( WC()->cart ) ) {
				foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
					/**
					 * Current product.
					 *
					 * @var WC_Product
					 */
					$product = $cart_item['data'];
					$id      = $product->get_id();

					if ( ywsbs_is_subscription_product( $id ) ) {
						$fee = ywsbs_get_product_fee( $product );
						if ( ! empty( $fee ) && $fee > 0 ) {
							$check = true;
							break;
						}
					}
				}
			}

			return $check;
		}

		/**
		 * Check if in the cart there are subscription products.
		 *
		 * @since  2.0.0
		 * @return bool|array
		 */
		public static function cart_has_subscriptions() {

			$count = 0;
			$items = array();

			if ( ! did_action( 'wp_loaded' ) ) {
				return false;
			}

			if ( ! did_action( 'woocommerce_load_cart_from_session' ) && WC()->is_store_api_request() ) {
				wc_load_cart();
			}

			if ( isset( WC()->cart ) ) {

				$contents = WC()->cart->get_cart();
				if ( ! empty( $contents ) ) {
					foreach ( $contents as $item_key => $item ) {
						$product = $item['data'];

						if ( ywsbs_is_subscription_product( $product ) ) {
							$count = array_push( $items, $item_key );
						}
					}
				}
			}

			return 0 === $count ? false : $items;
		}

		/**
		 * Check whether the cart needs payment even if the order total is $0
		 *
		 * @param bool    $needs_payment Need payment or is free.
		 * @param WC_Cart $cart          Cart.
		 *
		 * @return bool
		 */
		public static function cart_needs_payment( $needs_payment, $cart ) {
			/**
			 * APPLY_FILTERS: ywsbs_subscription_cart_needs_payments
			 *
			 * This filter allows to set if a cart needs of payments or not
			 *
			 * @param bool $needs_payment Value that can be filtered.
			 *
			 * @return bool
			 */
			$cart_needs_payments = apply_filters( 'ywsbs_subscription_cart_needs_payments', ! $needs_payment && self::cart_has_subscriptions() && 0 == $cart->get_total( 'edit' ) ); // phpcs:ignore
			if ( $cart_needs_payments ) {
				$needs_payment = true;
			}

			return $needs_payment;
		}

		/**
		 * Removes all subscription products from the shopping cart.
		 *
		 * @since 2.0.0
		 * @param int $item_key Cart item key to remove.
		 *
		 * @return void
		 */
		public static function remove_subscription_from_cart( $item_key ) {
			WC()->cart->set_quantity( $item_key, 0 );
		}

		/**
		 * Add recurring totals inside the cart.
		 *
		 * @param boolean $output True to echo, false to return.
		 * @return string|void
		 */
		public function cart_recurring_totals( $output = true ) {
			if ( ! isset( WC()->cart ) || ! self::cart_has_subscriptions() ) {
				return;
			}

			ob_start();
			wc_get_template( 'cart/ywsbs-recurring-totals.php', array(), '', YITH_YWSBS_TEMPLATE_PATH . '/' );
			$recurring_totals = ob_get_contents();
			ob_end_clean();

			if ( false === $output ) {
				return $recurring_totals;
			}

			echo $recurring_totals; // phpcs:ignore
		}

		/**
		 * Disable guest checkout if on cart there are subscriptions products
		 *
		 * @param bool $forced Registration forced.
		 */
		public function force_registration( $forced ) {
			return $forced || ( self::cart_has_subscriptions() && isset( $gateways['ppcp-gateway'] ) );
		}

		/**
		 * During the checkout process, force registration when the cart contains a subscription.
		 *
		 * @since 1.1
		 * @return void
		 */
		public function force_registration_during_checkout() {
			$gateways = WC()->payment_gateways()->get_available_payment_gateways();
			if ( isset( $gateways['ppcp-gateway'] ) && self::cart_has_subscriptions() && ! is_user_logged_in() ) {
				$_POST['createaccount'] = 1;
			}
		}

		/**
		 * Maybe hide subtotal row if amount is zero
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public function maybe_hide_subtotal_row() {
			if (
				'yes' !== get_option( 'ywsbs_show_hide_zero_subtotal_row', 'no' ) ||
				( ! is_cart() && ! is_checkout() ) ||
				0 < WC()->cart->get_subtotal() ||
				! self::cart_has_subscriptions()
			) {
				return;
			}

			wp_add_inline_style( 'yith_ywsbs_frontend', '.cart_totals tr.cart-subtotal,#order_review tr.cart-subtotal,.wp-block-woocommerce-cart-order-summary-subtotal-block,.wp-block-woocommerce-checkout-order-summary-subtotal-block{display: none!important;}' );
		}
	}
}


/**
 * Unique access to instance of YWSBS_Subscription_Cart class
 *
 * @return YWSBS_Subscription_Cart
 */
function YWSBS_Subscription_Cart() { // phpcs:ignore
	return YWSBS_Subscription_Cart::get_instance();
}
