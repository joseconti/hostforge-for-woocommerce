<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_Product_Reviews_Controller REST API.
 *
 * @class   YWSBS_Subscription_Box_Product_Reviews_Controller
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_Product_Reviews_Controller' ) ) {

	/**
	 * Class YWSBS_Subscription_Synchronization
	 */
	class YWSBS_Subscription_Box_Product_Reviews_Controller extends WP_REST_Controller {

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
		protected $rest_base = 'product-reviews';

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
						'callback'            => array( $this, 'get_product_reviews' ),
						'permission_callback' => '__return_true',
						'args'                => array(
							'page'    => array(
								'description'       => __( 'Current page of the collection.', 'yith-woocommerce-subscription' ),
								'type'              => 'integer',
								'default'           => 1,
								'sanitize_callback' => 'absint',
								'validate_callback' => 'rest_validate_request_arg',
								'minimum'           => 1,
							),
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
				'title'      => 'product-reviews',
				'type'       => 'object',
				'properties' => array(
					'id'      => array(
						'description' => __( 'Unique identifier for the resource.', 'yith-woocommerce-subscription' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
					),
					'rating'  => array(
						'description' => __( 'Product review rating.', 'yith-woocommerce-subscription' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
					),
					'author'  => array(
						'description' => __( 'Product review author.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'date'    => array(
						'description' => __( 'Product review date.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
					'content' => array(
						'description' => __( 'Product review content.', 'yith-woocommerce-subscription' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
					),
				),
			);
		}

		/**
		 * Get product reviews.
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|WP_REST_Response
		 * @throws Exception Error get step products.
		 */
		public function get_product_reviews( $request ) {

			// phpcs:disable WordPress.Security.NonceVerification
			try {

				$product = wc_get_product( (int) $request['id'] );
				if ( ! $product ) {
					throw new Exception( __( 'Invalid product.', 'yith-woocommerce-subscription' ), 404 );
				}

				$page           = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
				$reviews        = array();
				$comments_query = new WP_Comment_Query(
					array(
						'post_id'       => $product->get_id(),
						'type'          => 'review',
						'no_found_rows' => false,
						'paged'         => $page,
						'number'        => 5,
					)
				);

				foreach ( $comments_query->comments as $comment ) {
					$reviews[] = $this->prepare_review( $comment );
				}

				$response = rest_ensure_response( $reviews );
				$response->header( 'X-WP-Total', $comments_query->found_comments );
				$response->header( 'X-WP-TotalPages', $comments_query->max_num_pages );

				$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $product->get_id() ) ) );

				if ( $comments_query->max_num_pages > $page ) {
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
		 * Prepare a single review for response
		 *
		 * @since 4.0.0
		 * @param WP_Comment $comment The comment to prepare for response.
		 * @return array
		 */
		protected function prepare_review( $comment ) {
			return array(
				'id'       => (int) $comment->comment_ID,
				'rating'   => (int) get_comment_meta( $comment->comment_ID, 'rating', true ),
				'author'   => $comment->comment_author,
				'avatar'   => get_avatar_url( $comment->user_id, array( 'size' => 60 ) ),
				'date'     => date_i18n( wc_date_format(), $comment->comment_date ),
				'datetime' => date_i18n( 'Y-m-d H:i:s', strtotime( $comment->comment_date ) ),
				'content'  => $comment->comment_content,
			);
		}
	}
}
