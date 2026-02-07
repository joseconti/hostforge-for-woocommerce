<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_Cart_Controller REST API.
 *
 * @class   YWSBS_Subscription_Box_Cart_Controller
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_Cart_Controller' ) ) {

	/**
	 * Class YWSBS_Subscription_Synchronization
	 */
	class YWSBS_Subscription_Box_Cart_Controller extends WP_REST_Controller {

		/**
		 * Endpoint namespace.
		 *
		 * @var string
		 */
		protected $namespace = YWSBS_Subscription_Box_Rest::NAMESPACE;

		/**
		 * Route base.
		 *
		 * @var string
		 */
		protected $rest_base = 'box-cart';

		/**
		 * Register the routes.
		 */
		public function register_routes() {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/update',
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'update_cart_box' ),
						'permission_callback' => '__return_true',
						'args'                => array(
							'box_id' => array(
								'description'       => _x( 'The subscription box ID.', 'REST API property description', 'yith-woocommerce-subscription' ),
								'type'              => 'integer',
								'sanitize_callback' => 'absint',
								'required'          => true,
							),
							'items'  => array(
								'description' => _x( 'The subscription box items.', 'REST API property description', 'yith-woocommerce-subscription' ),
								'type'        => 'object',
								'items'       => array(
									'type'       => 'object',
									'properties' => array(
										'id'       => array(
											'description' => _x( 'The subscription box item ID.', 'REST API property description', 'yith-woocommerce-subscription' ),
											'type'        => 'integer',
											'sanitize_callback' => 'absint',
											'required'    => true,
										),
										'product'  => array(
											'description' => _x( 'The subscription box item product ID.', 'REST API property description', 'yith-woocommerce-subscription' ),
											'type'        => 'integer',
											'sanitize_callback' => 'absint',
											'required'    => true,
										),
										'quantity' => array(
											'description' => _x( 'The subscription box item quantity.', 'REST API property description', 'yith-woocommerce-subscription' ),
											'type'        => 'integer',
											'sanitize_callback' => 'absint',
											'default'     => 1,
										),
									),
								),
							),
						),
					),
					'schema' => array( $this, 'get_item_schema' ),
				)
			);
		}

		/**
		 * Update box cart and return box totals
		 *
		 * @since  4.0.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|WP_REST_Response
		 * @throws Exception Error calculating cart totals.
		 */
		public function update_cart_box( $request ) {

			try {
				$box         = $this->get_box( $request['box_id'] );
				$box_content = $request['items'] ?? array();
				$this->load_cart();

				// Add box to cart.
				$cart_item_key = YWSBS_Subscription_Box_Cart::add_box_to_cart( $box->get_id(), $box_content );
				if ( is_wp_error( $cart_item_key ) ) {
					return rest_ensure_response( array( 'errors' => $cart_item_key->get_error_messages() ) );
				}

				return rest_ensure_response( array( 'totals' => $this->calculate_totals() ) );

			} catch ( Exception $e ) {
				return new WP_Error( 'ywsbs_subscription_box_rest_api_error', $e->getMessage(), array( 'status' => $e->getCode() ) );
			}
		}

		/**
		 * Prepare response
		 *
		 * @since  4.0.0
		 * @return array
		 */
		protected function calculate_totals() {
			// Recalculate totals.
			WC()->cart->calculate_totals();

			$cart_content            = WC()->cart->get_cart_contents();
			$display_including_taxes = WC()->cart->display_prices_including_tax();
			$totals                  = array();
			$cart_item               = array_shift( $cart_content ); // Product in cart is always one.
			$discount_amount         = ! empty( $cart_item['data'] ) ? $cart_item['data']->get_discount_amount() : 0;

			foreach ( array_keys( $this->get_item_schema_properties() ) as $key ) {
				switch ( $key ) {
					case 'subtotal':
						$totals['subtotal']  = $display_including_taxes ? ( WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() ) : WC()->cart->get_subtotal();
						$totals['subtotal'] += $discount_amount;
						break;
					case 'taxes':
						if ( ! $display_including_taxes ) {
							$totals['taxes'] = WC()->cart->get_taxes_total();
						}
						break;
					case 'shipping':
						$totals['shipping'] = $display_including_taxes ? ( WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() ) : WC()->cart->get_shipping_total();
						break;
					case 'discount':
						if ( $discount_amount ) {
							$totals['discount'] = -1 * $discount_amount;
						}
						break;
					case 'total':
						$totals['total'] = WC()->cart->get_total( 'edit' );
						break;
					default:
						$method = "get_{$key}";
						if ( method_exists( WC()->cart, $method ) ) {
							$totals[ $key ] = WC()->cart->$method();
						}
						break;
				}
			}

			return array_map(
				function ( $value ) {
					return is_scalar( $value ) ? wc_format_decimal( $value, wc_get_price_decimals() ) : $value;
				},
				$totals
			);
		}

		/**
		 * Get subscription box product from given id or throw Exception
		 *
		 * @since  4.0.0
		 * @param integer $box_id The box ID to retrieve.
		 * @return WC_Product
		 * @throws Exception Error if a subscription box was not found.
		 */
		protected function get_box( $box_id ) {
			$box = wc_get_product( $box_id );
			if ( ! $box || ! $box->is_type( YWSBS_Subscription_Box::PRODUCT_TYPE ) || ! $box->is_purchasable() ) {
				throw new Exception( esc_html__( 'Invalid subscription box ID.', 'yith-woocommerce-subscription' ), 404 );
			}

			return $box;
		}

		/**
		 * Makes the cart and sessions available to a route by loading them from core.
		 *
		 * @since  4.0.0
		 * @throws Exception Error loading cart.
		 */
		protected function load_cart() {
			if ( ! did_action( 'woocommerce_load_cart_from_session' ) && function_exists( 'wc_load_cart' ) ) {
				// Avoid using session.
				add_filter( 'woocommerce_cart_session_initialize', '__return_false' );
				add_filter( 'woocommerce_persistent_cart_enabled', '__return_false' );
				do_action( 'woocommerce_load_cart_from_session' ); // Prevent using session on WC()->cart->get_cart().

				wc_load_cart();

				// Check if cart is correctly loaded.
				if ( empty( WC()->cart ) ) {
					throw new Exception( esc_html__( 'Error loading cart instance.', 'yith-woocommerce-subscription' ), 500 );
				}

				// clear also notices.
				wc_clear_notices();
			}
		}

		/**
		 * Get the cart totals schema, conforming to JSON Schema.
		 *
		 * @return array
		 */
		public function get_item_schema() {
			return array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'box-cart',
				'type'       => 'object',
				'properties' => array(
					'subtotal' => array(
						'description' => __( 'Box cart subtotal.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'taxes'    => array(
						'description' => __( 'Box cart total taxes.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'shipping' => array(
						'description' => __( 'Box cart total shipping cost.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'discount' => array(
						'description' => __( 'Box cart total discount amount.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'total'    => array(
						'description' => __( 'Box cart total.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),

				),
			);
		}

		/**
		 * Get schema properties,
		 *
		 * @return array
		 */
		public function get_item_schema_properties() {
			return $this->get_item_schema()['properties'];
		}
	}
}
