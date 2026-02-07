<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_Products_Controller REST API.
 *
 * @class   YWSBS_Subscription_Box_Products_Controller
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_Products_Controller' ) ) {

	/**
	 * Class YWSBS_Subscription_Synchronization
	 */
	class YWSBS_Subscription_Box_Products_Controller extends WC_REST_Products_Controller {

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
		protected $rest_base = 'box-products';

		/**
		 * Register the routes for products.
		 */
		public function register_routes() {

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)',
				array(
					'args'   => array(
						'id' => array(
							'description' => __( 'Unique identifier for the resource.', 'yith-woocommerce-subscription' ),
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_products' ),
						'permission_callback' => '__return_true',
						'args'                => array(
							'context' => $this->get_context_param(
								array(
									'default' => 'view',
								)
							),
						),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);
		}

		/**
		 * Get the Product's schema, conforming to JSON Schema.
		 *
		 * @return array
		 */
		public function get_item_schema() {
			return array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => $this->post_type,
				'type'       => 'object',
				'properties' => array(
					'id'                => array(
						'description' => __( 'Unique identifier for the resource.', 'yith-woocommerce-subscription' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
					),
					'name'              => array(
						'description' => __( 'Product name.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'short_description' => array(
						'description' => __( 'Product short description.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'description'       => array(
						'description' => __( 'Product description.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'price'             => array(
						'description' => __( 'Current product price.', 'yith-woocommerce-subscription' ),
						'type'        => 'float',
						'context'     => array( 'view' ),
					),
					'price_html'        => array(
						'description' => __( 'Product price HTML.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'stock_quantity'    => array(
						'description' => __( 'Product stock quantity.', 'yith-woocommerce-subscription' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
					),
					'images'            => array(
						'description' => __( 'Product images.', 'yith-woocommerce-subscription' ),
						'type'        => 'object',
						'context'     => array( 'view' ),
						'items'       => array(
							'type'       => 'object',
							'context'    => array( 'view' ),
							'properties' => array(
								'src'    => array(
									'description' => __( 'Image URL.', 'yith-woocommerce-subscription' ),
									'type'        => 'string',
									'context'     => array( 'view' ),
								),
								'width'  => array(
									'description' => __( 'Image width.', 'yith-woocommerce-subscription' ),
									'type'        => 'integer',
									'context'     => array( 'view' ),
								),
								'height' => array(
									'description' => __( 'Image height.', 'yith-woocommerce-subscription' ),
									'type'        => 'integer',
									'context'     => array( 'view' ),
								),
							),
						),
					),
					'attributes'        => array(
						'description' => __( 'List of attributes.', 'yith-woocommerce-subscription' ),
						'type'        => 'array',
						'context'     => array( 'view' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'label' => array(
									'description' => __( 'Attribute name.', 'yith-woocommerce-subscription' ),
									'type'        => 'string',
									'context'     => array( 'view' ),
								),
								'value' => array(
									'description' => __( 'Attribute values comma separated.', 'yith-woocommerce-subscription' ),
									'type'        => 'string',
									'context'     => array( 'view' ),
								),
							),
						),
					),
					'rating_count'      => array(
						'description' => __( 'Amount of reviews that the product has.', 'yith-woocommerce-subscription' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
				),
			);
		}

		/**
		 * Get product data.
		 *
		 * @param WC_Product $product Product instance.
		 * @param string     $context Request context. Options: 'view' and 'edit'.
		 *
		 * @return array
		 */
		protected function get_product_data( $product, $context = 'view' ) {

			// Remove YITH Proteo filters.
			remove_filter( 'woocommerce_short_description', 'yith_proteo_limit_woocommerce_short_description', 10 );

			// Remove subscription price filter.
			remove_filter( 'woocommerce_get_price_html', array( YWSBS_Subscription_Helper::get_instance(), 'change_price_html' ) );

			$data = parent::get_product_data( $product, $context );
			// Unset rating count if reviews are disabled for the product.
			if ( ! $product->get_reviews_allowed() ) {
				unset( $data['rating_count'] );
			}

			return $data;
		}

		/**
		 * Get products.
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|WP_REST_Response
		 * @throws Exception Error get step products.
		 */
		public function get_products( $request ) {

			// phpcs:disable WordPress.Security.NonceVerification
			try {

				$box_product = $this->get_object( (int) $request['id'] );
				if ( ! $box_product || 0 === $box_product->get_id() || ! $box_product->is_type( YWSBS_Subscription_Box::PRODUCT_TYPE ) ) {
					throw new Exception( __( 'Invalid subscription box ID.', 'yith-woocommerce-subscription' ), 404 );
				}

				$steps = $box_product->get_steps();
				if ( empty( $steps ) ) {
					throw new Exception( __( 'The requested subscription box ID has no steps defined.', 'yith-woocommerce-subscription' ), 404 );
				}

				$step_id = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : null;
				if ( empty( $steps[ $step_id ] ) ) {
					throw new Exception( __( 'The requested step does not match a valid step ID.', 'yith-woocommerce-subscription' ), 404 );
				}

				$page          = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
				$products      = array();
				$query_results = $box_product->get_step_products(
					$step_id,
					array(
						'page'     => $page,
						'paginate' => true,
					)
				);

				if ( empty( $query_results ) ) {
					throw new Exception( __( 'The requested step does not contain any valid products.', 'yith-woocommerce-subscription' ), 404 );
				}

				foreach ( $query_results->products as $product ) {
					if ( ! $product->is_purchasable() ) { // double check for purchasable.
						continue;
					}
					$data       = $this->prepare_object_for_response( $product, $request );
					$products[] = $data instanceof WP_REST_Response ? $data->get_data() : $data;
				}

				$response = rest_ensure_response( $products );
				$response->header( 'X-WP-Total', $query_results->total );
				$response->header( 'X-WP-TotalPages', (int) $query_results->max_num_pages );

				$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $box_product->get_id() ) ) );

				if ( (int) $query_results->max_num_pages > $page ) {
					$next_page = $page + 1;
					$next_link = add_query_arg( 'page', $next_page, $base );
					$response->link_header( 'next', $next_link );
				}

				return $response;

			} catch ( Exception $e ) {
				return new WP_Error( 'ywsbs_subscription_box_rest_api_error', $e->getMessage(), array( 'status' => $e->getCode() ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification
		}

		/**
		 * Get product images
		 *
		 * @param WC_Product $product Product instance.
		 * @return array
		 */
		protected function get_images( $product ) {
			return ywsbs_product_get_image_data( $product );
		}

		/**
		 * Get product attributes
		 *
		 * @param WC_Product $product Product instance.
		 * @return array
		 */
		protected function get_attributes( $product ) {
			$product_attributes = array();

			// Display weight and dimensions before attribute list.
			$display_dimensions = apply_filters( 'wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions() );

			if ( $display_dimensions && $product->has_weight() ) {
				$product_attributes[] = array(
					'id'    => 'weight',
					'label' => __( 'Weight', 'yith-woocommerce-subscription' ),
					'value' => wc_format_weight( $product->get_weight() ),
				);
			}

			if ( $display_dimensions && $product->has_dimensions() ) {
				$product_attributes[] = array(
					'id'    => 'dimensions',
					'label' => __( 'Dimensions', 'yith-woocommerce-subscription' ),
					'value' => wc_format_dimensions( $product->get_dimensions( false ) ),
				);
			}

			// Add product attributes to list.
			$attributes = array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' );

			foreach ( $attributes as $attribute ) {
				$values = array();

				if ( $attribute->is_taxonomy() ) {
					$attribute_values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

					foreach ( $attribute_values as $attribute_value ) {
						$values[] = esc_html( $attribute_value->name );
					}
				} else {
					$values = array_map( 'esc_html', $attribute->get_options() );
				}

				$product_attributes[] = array(
					'id'    => wc_attribute_taxonomy_name( $attribute->get_name() ),
					'label' => wc_attribute_label( $attribute->get_name() ),
					'value' => apply_filters( 'woocommerce_attribute', wptexturize( implode( ', ', $values ) ), $attribute, $values ),
				);
			}

			return $product_attributes;
		}
	}
}
