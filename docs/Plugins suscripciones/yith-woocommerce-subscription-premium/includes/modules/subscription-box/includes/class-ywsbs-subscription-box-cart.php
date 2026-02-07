<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_Cart Class.
 * Handle the cart for module "subscription box"
 *
 * @class   YWSBS_Subscription_Box_Cart
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.


if ( ! class_exists( 'YWSBS_Subscription_Box_Cart' ) ) {
	/**
	 * Class YWSBS_Subscription_Box_Cart
	 */
	class YWSBS_Subscription_Box_Cart {

		/**
		 * Persistent cart key.
		 *
		 * @var string
		 */
		const PERSISTENT_CART_KEY = '_ywsbs_box_persistent_cart';

		/**
		 * Init
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public static function init() {

			// Handle box cart.
			add_action( 'wp_loaded', array( __CLASS__, 'add_to_cart_action' ), 20 );
			add_filter( 'woocommerce_cart_contents_changed', array( __CLASS__, 'customize_cart_contents' ), 10, 1 );
			add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'check_box_cart_item' ) );

			add_action( 'wp', array( __CLASS__, 'maybe_restore_cart' ) );

			// Customize cart item.
			add_action( 'woocommerce_after_cart_item_name', array( __CLASS__, 'customize_box_cart_item' ), 0 );
			add_action( 'woocommerce_checkout_cart_item_quantity', array( __CLASS__, 'customize_box_checkout_item' ), -1, 2 );
		}

		/**
		 * Add to cart action.
		 * Checks for a valid request, does validation (via hooks) and then redirects if valid.
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public static function add_to_cart_action() {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_POST['_ywsbs_box_add_to_cart'], $_POST['_ywsbs_box_content'] ) || ! is_numeric( wp_unslash( $_POST['_ywsbs_box_add_to_cart'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return;
			}

			wc_nocache_headers();

			self::empty_cart();

			$box_id      = absint( wp_unslash( $_POST['_ywsbs_box_add_to_cart'] ) );
			$box_content = wc_clean( json_decode( wp_unslash( $_POST['_ywsbs_box_content'] ), true ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$response = self::add_box_to_cart( $box_id, $box_content );

			if ( is_wp_error( $response ) ) {

				wc_add_wp_error_notices( $response );
				self::restore_cart();

				wp_safe_redirect( get_permalink( $box_id ) );
				exit;
			}

			do_action( 'ywsbs_after_box_add_to_cart', $box_id, $box_content );

			wp_safe_redirect( wc_get_checkout_url() );
			exit;
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/**
		 * Add box cart and set box price if needed.
		 *
		 * @since  4.0.0
		 * @param integer      $box_id      The box ID to add.
		 * @param array        $box_content The box content.
		 * @param null|WC_Cart $cart The cart instance to use. Default is the main WooCommerce Cart.
		 * @return string|WP_Error Return the cart_item_key on success, a WP_Error object on failure
		 */
		public static function add_box_to_cart( $box_id, $box_content, $cart = null ) {
			$box = wc_get_product( absint( $box_id ) );

			if ( ! $box || ! $box->is_type( YWSBS_Subscription_Box::PRODUCT_TYPE ) ) {
				// Translators: %d is the box product ID.
				return new WP_Error( 'wrong-product-type', sprintf( __( 'Product with ID #%d is not a subscription box.', 'yith-woocommerce-subscription' ), $box_id ) );
			}

			$validation = self::validate_add_to_cart( $box, $box_content );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}

			// TODO check if all these data are mandatory.

			// Merge box data with default.
			$box_data = array(
				'content'               => $box_content,
				'price_type'            => $box->get_price_type(),
				'email_schedule_before' => $box->get_meta( '_ywsbs_box_email_day_schedule' ),
				'editing_until'         => $box->get_meta( '_ywsbs_box_editing_until' ),
			);

			// Add discount data if any.
			if ( $box->is_on_sale() ) {
				$box_data = array_merge(
					array(
						'discount_type'  => $box->get_discount_type(),
						'discount_value' => $box->get_discount_value(),
					),
					$box_data
				);
			}

			if ( empty( $cart ) ) {
				$cart = WC()->cart;
			}

			$cart_item_key = $cart->add_to_cart( $box->get_id(), 1, 0, array(), array( '_ywsbs_box_data' => $box_data ) );
			if ( ! $cart_item_key ) {
				// Translators: %d is the box product title.
				return new WP_Error( 'add-to-cart-error', sprintf( __( 'An error occurred adding %s to the cart.', 'yith-woocommerce-subscription' ), $box->get_name() ) );
			}

			return $cart_item_key;
		}

		/**
		 * Validate box before add to cart
		 *
		 * @since 4.0.0
		 * @param WC_Product|integer $box         The box to add. Could be either a WC_product instance or an ID.
		 * @param array              $box_content The box content.
		 * @return bool|WP_Error Return true if validation is ok, a WP_Error object with error details otherwise.
		 */
		public static function validate_add_to_cart( $box, $box_content ) {

			$errors = new WP_Error();

			// Validate content first.
			self::validate_box_content( $box, $box_content, $errors );

			// Validate rules.
			self::validate_box_rules( $box, $box_content, $errors );

			return $errors->has_errors() ? $errors : true;
		}

		/**
		 * Validate box content before add to cart
		 *
		 * @since 4.0.0
		 * @param WC_Product|integer $box         The box to add. Could be either a WC_product instance or an ID.
		 * @param array              $box_content The box content.
		 * @param WP_Error           $errors      The WP_Error instance.
		 * @return void
		 */
		protected static function validate_box_content( $box, $box_content, &$errors ) {

			$current_session_order_id = isset( WC()->session->order_awaiting_payment ) ? absint( WC()->session->order_awaiting_payment ) : 0;

			if ( empty( $box_content ) ) {
				$errors->add( 'empty-box-content', __( 'Box content cannot be empty.', 'yith-woocommerce-subscription' ) );
			}

			foreach ( $box_content as $step_id => $step_items ) {

				$step_items_quantity = array_reduce(
					$step_items,
					function ( $carry, $item ) {
						return $carry + $item['quantity'];
					},
					0
				);

				// Check step min quantity if set.
				if ( $box->get_step_minimum_quantity_threshold( $step_id ) > $step_items_quantity ) {
					// Translators: %d is the minimum required product quantity for step, %s is the step label.
					$errors->add( 'step-min-quantity', sprintf( __( 'Please add at least %d product(s) in box step "%s".', 'yith-woocommerce-subscription' ), $box->get_step_minimum_quantity_threshold( $step_id ), $box->get_step_label( $step_id ) ?: $step_id ) ); // phpcs:ignore
				}

				// Check step max quantity if set.
				if ( $box->get_step_maximum_quantity_threshold( $step_id ) && $step_items_quantity > $box->get_step_maximum_quantity_threshold( $step_id ) ) {
					// Translators: %d is the maximum required product quantity for step, %s is the step label.
					$errors->add( 'step-max-quantity', sprintf( __( 'Please add a maximum of %d product(s) in box step "%s".', 'yith-woocommerce-subscription' ), $box->get_step_minimum_quantity_threshold( $step_id ), $box->get_step_label( $step_id ) ?: $step_id ) ); // phpcs:ignore
				}

				foreach ( $step_items as $item ) {

					$product = wc_get_product( $item['product'] );
					if ( empty( $product ) || ! $product->is_purchasable() || ! $box->is_product_valid_for_step( $product, $step_id ) ) {
						// translators: %s is the product id.
						$errors->add( 'product-not-purchasable', sprintf( __( 'Product %s cannot be added to the box.', 'yith-woocommerce-subscription' ), $product ? $product->get_name() : '#' . $item['product'] ) );
					}

					// Check stock based on all items in the cart and consider any held stock within pending orders.
					$held_stock = wc_get_held_stock_quantity( $product, $current_session_order_id );

					// Stock check.
					if ( ! $product->is_in_stock() ) {
						/* translators: %s: product name */
						$errors->add( 'out-of-stock', sprintf( __( 'You cannot add &quot;%s&quot; to the the box because the product is out of stock.', 'yith-woocommerce-subscription' ), $product->get_name() ) );

					} elseif ( ! $product->has_enough_stock( $item['quantity'] + $held_stock ) ) {
						/* translators: 1: product name 2: quantity in stock */
						$errors->add( 'out-of-stock', sprintf( __( 'You cannot add that amount of &quot;%1$s&quot; to the box because there is not enough stock (%2$s remaining).', 'yith-woocommerce-subscription' ), $product->get_name(), wc_format_stock_quantity_for_display( $product->get_stock_quantity() - $held_stock, $product ) ) );
					}

					// TODO: validate product max units.
				}
			}
		}

		/**
		 * Validate box rules before add to cart
		 *
		 * @since 4.0.0
		 * @param WC_Product|integer $box         The box to add. Could be either a WC_product instance or an ID.
		 * @param array              $box_content The box content.
		 * @param WP_Error           $errors      The WP_Error instance.
		 * @return void
		 */
		protected static function validate_box_rules( $box, $box_content, &$errors ) {

			if ( ! $box->has_price_threshold() ) {
				return;
			}

			// Calculate box content price since the price type must be SUM.
			$price     = (float) self::calculate_box_content_price( $box_content );
			$min_price = (float) $box->get_min_price_threshold();
			$max_price = (float) $box->get_max_price_threshold();

			if ( $price < $min_price ) {
				// translators: %1$s is the box product name, %2$s is the box min price.
				$errors->add( 'box-min-price', sprintf( __( '%1$s cannot be purchased because the minimum price required is %2$s.', 'yith-woocommerce-subscription' ), $box->get_title(), wc_price( $min_price ) ) );

			} elseif ( $max_price && $box->get_price() > $max_price ) {
				// translators: %1$s is the box product name, %2$s is the box max price.
				$errors->add( 'box-max-price', sprintf( __( '%1$s cannot be purchased because the maximum price allowed is %2$s.', 'yith-woocommerce-subscription' ), $box->get_title(), wc_price( $max_price ) ) );
			}
		}

		/**
		 * Calculate box content price
		 *
		 * @since 4.0.0
		 * @param array $box_content The box content to precess.
		 * @return float
		 */
		private static function calculate_box_content_price( $box_content ) {

			$price = 0;

			foreach ( $box_content as $step_id => $step_items ) {
				foreach ( $step_items as $item ) {
					$product = wc_get_product( $item['product'] );
					if ( ! $product || ! $product->is_purchasable() ) {
						continue;
					}

					$price += ( floatval( $product->get_price() ) * $item['quantity'] );
				}
			}

			return $price;
		}

		/**
		 * Check cart content before set.
		 * Check if there is a BOX and customize price if needed.
		 *
		 * @since  4.0.0
		 * @param array $cart_content The cart content.
		 * @return array
		 * @throws Exception Error if a product in box is no more purchasable.
		 */
		public static function customize_cart_contents( $cart_content ) {

			foreach ( $cart_content as $item_key => &$item ) {
				$box = $item['data'];

				// Check if is box. Double check product type is subscription box.
				if ( ywsbs_is_cart_item_subscription_box( $item ) ) {

					$box_content = $item['_ywsbs_box_data']['content'];

					$validation = self::validate_add_to_cart( $box, $box_content );
					if ( is_wp_error( $validation ) ) {

						unset( $cart_content[ $item_key ] );

						// Translators: %s is the product name.
						wc_add_notice( sprintf( __( '%s has been removed from your cart because it can no longer be purchased.', 'yith-woocommerce-subscription' ), $box->get_title() ), 'error' );
						continue;
					}

					// If price type is sum, do it!
					if ( 'sum' === $box->get_price_type() ) {

						$price = self::calculate_box_content_price( $box_content );
						// Always set regular.
						$box->set_regular_price( $price );
						$box->set_price( $price );

						if ( $box->is_on_sale() ) {
							$price = $box->get_discounted_price();
							$box->set_sale_price( $price );
							$box->set_price( $price );
						}
					}

					// Set updated product.
					$item['data'] = $box;
					// Fix also subscription meta recurring price.
					$item['ywsbs-subscription-info']['recurring_price'] = $box->get_price();
				} else {
					unset( $item['_ywsbs_box_data'] );
				}
			}

			return $cart_content;
		}

		/**
		 * Check subscription box items for errors.
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public static function check_box_cart_item() {
			foreach ( WC()->cart->get_cart_contents() as $item_key => $item ) {
				$box = $item['data'];
				if ( ! ywsbs_is_cart_item_subscription_box( $item ) ) {
					continue;
				}

				$validation = self::validate_add_to_cart( $box, $item['_ywsbs_box_data']['content'] );
				if ( is_wp_error( $validation ) ) {

					WC()->cart->remove_cart_item( $item_key );

					// Translators: %s is the product name.
					wc_add_notice( sprintf( __( '%s has been removed from your cart because it is no longer valid.', 'yith-woocommerce-subscription' ), $box->get_title() ), 'error' );
				}
			}
		}

		/**
		 * Output subscription box content modal.
		 *
		 * @since 4.0.0
		 * @param WC_Product $box         Box product.
		 * @param array      $box_content Box content array.
		 * @return void
		 */
		protected static function output_box_content_modal( $box, $box_content ) {

			$formatted_box_content = ywsbs_box_get_content_to_display( $box_content, $box );

			echo '<span class="ywsbs-open-modal ywsbs-subscription-box-view-content" data-target="subscription-box-content" data-content="' . esc_attr( wp_json_encode( $formatted_box_content ) ) . '">' . esc_html__( 'View box content', 'yith-woocommerce-subscription' ) . '</span>';

			if ( false === has_action( 'wp_footer', array( __CLASS__, 'include_box_content_template' ) ) ) {
				add_action( 'wp_footer', array( __CLASS__, 'include_box_content_template' ) );
			}
		}

		/**
		 * Include box content modal template.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function include_box_content_template() {
			wc_get_template( 'cart/box-content-template.php', array(), '', YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . '/templates/' );
			do_action( 'ywsbs_print_subscription_modal_backbone' );
		}

		/**
		 * Customize cart item name
		 *
		 * @since 4.0.0
		 * @param array $cart_item The cart item.
		 * @return void
		 */
		public static function customize_box_cart_item( $cart_item ) {
			if ( ! ywsbs_is_cart_item_subscription_box( $cart_item ) ) {
				return;
			}

			self::output_box_content_modal( $cart_item['data'], $cart_item['_ywsbs_box_data']['content'] );
		}

		/**
		 * Customize checkout item name
		 *
		 * @since 4.0.0
		 * @param string $current_html The current filter value.
		 * @param array  $cart_item    The cart item.
		 * @return string
		 */
		public static function customize_box_checkout_item( $current_html, $cart_item ) {
			ob_start();
			self::customize_box_cart_item( $cart_item );
			return $current_html . ob_get_clean();
		}

		/**
		 * Check if box sold individually option is enabled
		 *
		 * @since 4.6.0
		 * @return boolean
		 */
		protected static function is_box_sold_individually() {
			return 'yes' === get_option( 'ywsbs_subscription_box_sold_individually', 'no' );
		}

		/**
		 * Empty cart before add box to cart
		 *
		 * @since 4.6.0
		 * @return void
		 */
		protected static function empty_cart() {
			if ( WC()->cart->is_empty() || ! self::is_box_sold_individually() ) {
				return;
			}

			$cart = array_filter(
				array_map(
					function ( $item ) {
						return ywsbs_is_cart_item_subscription_box( $item ) ? false : $item;
					},
					WC()->cart->get_cart()
				),
			);

			WC()->cart->empty_cart();

			// Maybe store persistent cart if there are cart items that are not box.
			if ( ! empty( $cart ) ) {
				WC()->session->set( self::PERSISTENT_CART_KEY, $cart );
			}

			do_action( 'ywsbs_subscription_box_cart_emptied', $cart );
		}

		/**
		 * Restore session cart
		 *
		 * @since 4.6.0
		 * @return void
		 */
		protected static function restore_cart() {

			$cart = WC()->session->get( self::PERSISTENT_CART_KEY );
			if ( ! empty( $cart ) ) {

				WC()->session->set( self::PERSISTENT_CART_KEY, null );

				// Then reload cart.
				WC()->session->set( 'cart', $cart );
				WC()->cart->get_cart_from_session();
			}

			do_action( 'ywsbs_subscription_box_cart_restored' );
		}

		/**
		 * Maybe restore cart
		 *
		 * @since 4.6.0
		 * @return void
		 */
		public static function maybe_restore_cart() {

			if ( ! self::is_box_sold_individually() || is_checkout() || defined( 'WOOCOMMERCE_CHECKOUT' ) || WC()->cart->is_empty() || wp_doing_ajax() ) {
				return;
			}

			$cart_has_box = array_filter( array_map( 'ywsbs_is_cart_item_subscription_box', WC()->cart->get_cart() ) );
			if ( empty( $cart_has_box ) ) {
				return;
			}

			WC()->cart->empty_cart();

			self::restore_cart();

			wp_safe_redirect( wp_unslash( $_SERVER['REQUEST_URI'] ) ?? '' ); // phpcs:ignore
			exit;
		}
	}
}
