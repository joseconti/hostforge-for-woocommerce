<?php

defined( 'ABSPATH' ) || exit;

/**
 * SUMOSubs_REST_Subscriptions_Controller
 */
class SUMOSubs_REST_Subscriptions_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc-sumosubs/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'subscriptions';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'sumosubscriptions';

	/**
	 * Register the routes for subscriptions.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_items' ),
				'permission_callback' => array( $this, 'create_items_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'type'        => 'boolean',
						'description' => __( 'Whether to bypass trash and force deletion.', 'sumosubscriptions' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'sumosubscriptions' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create single/multiple items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_items_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'sumosubscriptions' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$post = get_post( ( int ) $request[ 'id' ] );

		if ( $post && ! wc_rest_check_post_permissions( $this->post_type, 'read', $post->ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'sumosubscriptions' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$post = get_post( ( int ) $request[ 'id' ] );

		if ( $post && ! wc_rest_check_post_permissions( $this->post_type, 'edit', $post->ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'sumosubscriptions' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$post = get_post( ( int ) $request[ 'id' ] );

		if ( $post && ! wc_rest_check_post_permissions( $this->post_type, 'delete', $post->ID ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'sumosubscriptions' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id   = ( int ) $request[ 'id' ];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $post->post_type !== $this->post_type ) {
			return new WP_Error( "woocommerce_rest_invalid_{$this->post_type}_id", __( 'Invalid ID', 'sumosubscriptions' ), array( 'status' => '404' ) );
		}

		$data     = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$args                     = array();
		$args[ 'offset' ]         = $request[ 'offset' ];
		$args[ 'order' ]          = $request[ 'order' ];
		$args[ 'orderby' ]        = $request[ 'orderby' ];
		$args[ 'paged' ]          = $request[ 'page' ];
		$args[ 'post__in' ]       = $request[ 'include' ];
		$args[ 'post__not_in' ]   = $request[ 'exclude' ];
		$args[ 'posts_per_page' ] = $request[ 'per_page' ];
		$args[ 's' ]              = $request[ 'search' ];

		if ( 'date' === $args[ 'orderby' ] ) {
			$args[ 'orderby' ] = 'date ID';
		}

		$args[ 'date_query' ] = array();
		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $request[ 'before' ] ) ) {
			$args[ 'date_query' ][ 0 ][ 'before' ] = $request[ 'before' ];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $request[ 'after' ] ) ) {
			$args[ 'date_query' ][ 0 ][ 'after' ] = $request[ 'after' ];
		}

		// Force the post_type argument, since it's not a user input variable.
		$args[ 'post_type' ] = $this->post_type;
		$args[ 'fields' ]    = 'ids';

		$args = apply_filters( "woocommerce_rest_{$this->post_type}_object_query", $args, $request );

		$query       = new WP_Query();
		$result      = $query->query( $args );
		$total_posts = $query->found_posts;

		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $args[ 'paged' ] );
			$count_query = new WP_Query();
			$count_query->query( $args );
			$total_posts = $count_query->found_posts;
		}

		$objects = array();
		foreach ( $result as $id ) {
			$post      = get_post( $id );
			$data      = $this->prepare_item_for_response( $post, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $objects );
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id    = ( int ) $request[ 'id' ];
		$force = ( bool ) $request[ 'force' ];

		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $post->post_type !== $this->post_type ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'ID is invalid.', 'sumosubscriptions' ), array( 'status' => 404 ) );
		}

		if ( $force ) {
			$result = wp_delete_post( $id, true );
		} else {
			$supports_trash = EMPTY_TRASH_DAYS > 0;

			if ( ! $supports_trash ) {
				/* translators: %s: post type */
				return new WP_Error( 'woocommerce_rest_trash_not_supported', sprintf( __( 'The %s does not support trashing.', 'sumosubscriptions' ), $this->post_type ), array( 'status' => 501 ) );
			}

			if ( 'trash' === $post->post_status ) {
				/* translators: %s: post type */
				return new WP_Error( 'woocommerce_rest_already_trashed', sprintf( __( 'The %s has already been deleted.', 'sumosubscriptions' ), $this->post_type ), array( 'status' => 410 ) );
			}

			$result = wp_trash_post( $id );
		}

		if ( ! $result ) {
			/* translators: %s: post type */
			return new WP_Error( 'woocommerce_rest_cannot_delete', sprintf( __( 'The %s cannot be deleted.', 'sumosubscriptions' ), $this->post_type ), array( 'status' => 500 ) );
		}

		$data     = sprintf( __( 'The item #%s is deleted.', 'sumosubscriptions' ), $id );
		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Create single/multiple items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_items( $request ) {
		$order_id = ( int ) $request[ 'parent_order_id' ];
		$order    = wc_get_order( $order_id );

		if ( ! $order || 'shop_order' !== $order->get_type() || 0 !== $order->get_parent_id() ) {
			return new WP_Error( "woocommerce_rest_invalid_{$this->post_type}_id", sprintf( __( 'Invalid order to create %s.', 'sumosubscriptions' ), $this->post_type ), array( 'status' => '404' ) );
		}

		if ( ! sumo_is_order_contains_subscriptions( $order_id ) ) {
			/* translators: %s: post type */
			return new WP_Error( 'woocommerce_rest_cannot_create', sprintf( __( 'The %s cannot be created, since the order does not contain subscription products.', 'sumosubscriptions' ), $this->post_type ), array( 'status' => 500 ) );
		}

		$subscriptions = sumosubs_get_subscriptions_from_parent_order( $order_id );

		if ( ! empty( $subscriptions ) ) {
			/* translators: %s: post type */
			return new WP_Error( 'woocommerce_rest_already_created', sprintf( __( 'The %s has already been created', 'sumosubscriptions' ), $this->post_type ), array( 'status' => 410 ) );
		}

		SUMOSubscriptions_Order::create_new_subscriptions( $order_id, 'pending', $order->get_status() );

		$subscriptions = sumosubs_get_subscriptions_from_parent_order( $order_id );

		if ( empty( $subscriptions ) ) {
			/* translators: %s: post type */
			return new WP_Error( 'woocommerce_rest_cannot_create', sprintf( __( 'The %s cannot be created.', 'sumosubscriptions' ), $this->post_type ), array( 'status' => 500 ) );
		}

		$request->set_param( 'context', 'edit' );

		$objects = array();
		foreach ( $subscriptions as $id ) {
			$post      = get_post( $id );
			$data      = $this->prepare_item_for_response( $post, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $objects );
	}

	/**
	 * Update a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$id   = ( int ) $request[ 'id' ];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $post->post_type !== $this->post_type ) {
			return new WP_Error( "woocommerce_rest_invalid_{$this->post_type}_id", __( 'Invalid ID', 'sumosubscriptions' ), array( 'status' => '404' ) );
		}

		$this->prepare_item_for_database( $request );

		do_action( "woocommerce_rest_update_{$this->post_type}", $post, $request );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Get the item data.
	 *
	 * @return array
	 */
	protected function get_item_data( $subscription_id ) {
		if ( $this->post_type !== get_post_type( $subscription_id ) ) {
			return false;
		}

		$parent_order_id = absint( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) );
		$plan            = sumo_get_subscription_plan( $subscription_id, 0, 0, false );
		$parent_order    = wc_get_order( $parent_order_id );

		$data = apply_filters( "woocommerce_rest_get_{$this->post_type}_item_data", array(
			'subscription_id'              => $subscription_id,
			'parent_order_id'              => $parent_order_id,
			'subscription_status'          => get_post_meta( $subscription_id, 'sumo_get_status', true ),
			'subscription_number'          => sumo_get_subscription_number( $subscription_id ),
			'subscription_currency'        => $parent_order ? $parent_order->get_currency() : '',
			'subscription_version'         => get_post_meta( $subscription_id, 'sumo_subscription_version', true ),
			'subscriber_id'                => absint( get_post_meta( $subscription_id, 'sumo_get_user_id', true ) ),
			'billing_email'                => get_post_meta( $subscription_id, 'sumo_buyer_email', true ),
			'trial_enabled'                => '1' === $plan[ 'trial_status' ] ? 'yes' : 'no',
			'signup_enabled'               => '1' === $plan[ 'signup_status' ] ? 'yes' : 'no',
			'sync_enabled'                 => '1' === $plan[ 'synchronization_status' ] ? 'yes' : 'no',
			'resubscribed'                 => get_post_meta( $subscription_id, 'sumo_subscription_is_resubscribed', true ),
			'is_manual'                    => 'auto' === sumo_get_payment_type( $subscription_id ) ? 'no' : 'yes',
			'subscribed_product'           => SUMO_Order_Subscription::is_subscribed( $subscription_id ) ? null : absint( $plan[ 'subscription_product_id' ] ),
			'subscribed_qty'               => SUMO_Order_Subscription::is_subscribed( $subscription_id ) ? null : absint( $plan[ 'subscription_product_qty' ] ),
			'subscribed_items'             => SUMO_Order_Subscription::is_subscribed( $subscription_id ) ? $plan[ 'subscription_product_id' ] : null,
			'subscribed_items_qty'         => SUMO_Order_Subscription::is_subscribed( $subscription_id ) ? $plan[ 'subscription_product_qty' ] : null,
			'renewal_price'                => $plan[ 'subscription_fee' ],
			'trial_fee'                    => $plan[ 'trial_fee' ],
			'signup_fee'                   => $plan[ 'signup_fee' ],
			'trial_type'                   => $plan[ 'trial_type' ],
			'additional_digital_downloads' => $plan[ 'downloadable_products' ],
			'installments_cycle'           => 0 === absint( $plan[ 'subscription_recurring' ] ) ? 'indefinite' : absint( $plan[ 'subscription_recurring' ] ),
			'billing_plan'                 => get_post_meta( $subscription_id, 'sumo_subscr_plan', true ),
			'trial_plan'                   => get_post_meta( $subscription_id, 'sumo_trial_plan', true ),
			'start_date'                   => get_post_meta( $subscription_id, 'sumo_get_sub_start_date', true ),
			'trial_end_date'               => get_post_meta( $subscription_id, 'sumo_get_trial_end_date', true ),
			'next_payment_date'            => get_post_meta( $subscription_id, 'sumo_get_next_payment_date', true ),
			'last_payment_date'            => get_post_meta( $subscription_id, 'sumo_get_last_payment_date', true ),
			'end_date'                     => get_post_meta( $subscription_id, 'sumo_get_sub_end_date', true ),
			'expiry_date'                  => get_post_meta( $subscription_id, 'sumo_get_saved_due_date', true ),
			'renewed_count'                => sumosubs_get_renewed_count( $subscription_id ),
			'pending_renewal_order'        => absint( get_post_meta( $subscription_id, 'sumo_get_renewal_id', true ) ),
			'renewal_orders'               => get_post_meta( $subscription_id, 'sumo_get_every_renewal_ids', true ),
			'subscription_type'            => sumo_get_subscription_type( $subscription_id ),
			'payment_method'               => sumo_get_subscription_payment_method( $subscription_id ),
			'payment_data'                 => sumo_get_subscription_payment( $subscription_id ),
				) );

		return $data;
	}

	/**
	 * Only return writable props from schema.
	 * 
	 * @param  array $schema
	 * @return bool
	 */
	protected function filter_writable_props( $schema ) {
		return empty( $schema[ 'readonly' ] );
	}

	/**
	 * Prepare a single item output for response.
	 *
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $post, $request ) {
		$context  = ! empty( $request[ 'context' ] ) ? $request[ 'context' ] : 'view';
		$data     = $this->get_item_data( $post->ID );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}_response", $response, $post, $request );
	}

	/**
	 * Prepare a single item for update.
	 *
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_Error|WC_Order $data Object.
	 */
	protected function prepare_item_for_database( $request ) {
		$id              = isset( $request[ 'id' ] ) ? absint( $request[ 'id' ] ) : 0;
		$schema          = $this->get_item_schema();
		$data_keys       = array_keys( array_filter( $schema[ 'properties' ], array( $this, 'filter_writable_props' ) ) );
		$parent_order_id = absint( get_post_meta( $id, 'sumo_get_parent_order_id', true ) );

		// Handle all writable props
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {
					case 'subscription_status':
						if ( ! empty( $value ) ) {
							$old_status = strtolower( get_post_meta( $id, 'sumo_get_status', true ) );
							$new_status = strtolower( $value );

							if ( $new_status !== $old_status ) {
								switch ( $new_status ) {
									case 'pause':
										if ( in_array( $old_status, array( 'trial', 'active' ) ) ) {
											sumo_pause_subscription( $id, '', 'rest-api' );
											//Trigger after Subscription gets Paused
											do_action( 'sumosubscriptions_pause_subscription', $id, $parent_order_id );
										}
										break;
									case 'resume':
										if ( in_array( $old_status, array( 'pause' ) ) ) {
											sumo_resume_subscription( $id, 'rest-api' );
											//Trigger after Subscription gets Resumed
											do_action( 'sumosubscriptions_active_subscription', $id, $parent_order_id );
										}
										break;
									case 'activate-trial':
										if ( in_array( $old_status, array( 'pending' ) ) && sumosubs_free_trial_awaiting_admin_approval( $id ) ) {
											SUMOSubscriptions_Order::maybe_activate_subscription( $id, $parent_order_id, 'pending', 'free-trial' );
										}
										break;
									case 'active':
										if ( in_array( $old_status, array( 'pending' ) ) ) {
											$cron_event = new SUMO_Subscription_Cron_Event( $id );
											$cron_event->unset_events();

											SUMOSubscriptions_Order::maybe_activate_subscription( $id, $parent_order_id, 'pending', 'Active', true );
										}
										break;
									case 'cancelled':
										if ( in_array( $old_status, array( 'trial', 'active', 'pending_cancellation' ) ) ) {
											sumosubs_cancel_subscription( $id, array(
												'request_by' => 'rest-api',
											) );

											do_action( 'sumosubscriptions_cancel_subscription', $id, $parent_order_id );
										}
										break;
								}
							}
						}
						break;
					case 'billing_email':
						if ( ! empty( $value ) ) {
							$old_buyer_email = get_post_meta( $id, 'sumo_buyer_email', true );
							$new_buyer_email = $value;

							if ( $new_buyer_email !== $old_buyer_email && ( ! filter_var( $new_buyer_email, FILTER_VALIDATE_EMAIL ) === false ) ) {
								update_post_meta( $id, 'sumo_buyer_email', $new_buyer_email );
								sumo_add_subscription_note( sprintf( __( 'Subscription Buyer Email has changed to %s. Customer will be notified via email by this Mail ID only.', 'sumosubscriptions' ), $new_buyer_email ), $id, 'success', __( 'Buyer Email Changed', 'sumosubscriptions' ) );
							}
						}
						break;
					case 'renewal_price':
						if ( is_numeric( $value ) && ! SUMO_Order_Subscription::is_subscribed( $id ) ) {
							$new_renewal_fee = wc_format_decimal( $value );
							$old_renewal_fee = sumo_get_recurring_fee( $id, array(), 0, false );

							if ( is_numeric( $new_renewal_fee ) && $new_renewal_fee != $old_renewal_fee ) {
								if ( 'auto' === sumo_get_payment_type( $id ) && in_array( sumo_get_subscription_payment_method( $id ), array( 'sumo_paypal_preapproval', 'sumosubscription_paypal_adaptive', 'paypal' ) ) ) {
									//Warning !! Do not update the renewal fee. Preapproved amount should not be greater than the entered fee. It results in payment error.
								} else {
									update_post_meta( $id, 'sumo_get_updated_renewal_fee', $new_renewal_fee );
									sumo_add_subscription_note( sprintf( __( 'Subscription Renewal Fee has changed from %1$s to %2$s.', 'sumosubscriptions' ), sumo_format_subscription_price( $old_renewal_fee, array( 'currency' => sumosubs_get_order_currency( $parent_order_id ) ) ), sumo_format_subscription_price( $new_renewal_fee, array( 'currency' => sumosubs_get_order_currency( $parent_order_id ) ) ) ), $id, 'success', __( 'Renewal Fee Changed', 'sumosubscriptions' ) );
								}
							}
						}
						break;
					case 'subscribed_qty':
						if ( is_numeric( $value ) && ! SUMO_Order_Subscription::is_subscribed( $id ) ) {
							$subscription_plan = ( array ) get_post_meta( $id, 'sumo_subscription_product_details', true );
							$old_qty           = absint( $subscription_plan[ 'product_qty' ] );
							$new_qty           = absint( $value );

							if ( $new_qty > 0 && $new_qty !== $old_qty ) {
								$subscription_plan[ 'product_qty' ] = $new_qty;
								update_post_meta( $id, 'sumo_subscription_product_details', $subscription_plan );
								sumo_add_subscription_note( sprintf( __( 'Subscription quantity has changed from <b>%1$s</b> to <b>%2$s</b>.', 'sumosubscriptions' ), $old_qty, $new_qty ), $id, 'success', __( 'Subscription Qty Changed', 'sumosubscriptions' ) );
								do_action( 'sumosubscriptions_subscription_qty_changed', $new_qty, $id, $subscription_plan, $parent_order_id );
							}
						}
						break;
					case 'trial_end_date':
					case 'next_payment_date':
						if ( ! empty( $value ) && ! SUMO_Subscription_Synchronization::is_subscription_synced( $id ) && in_array( get_post_meta( $id, 'sumo_get_status', true ), array( 'Active', 'Trial' ) ) ) {
							$new_renewal_timestamp = sumo_get_subscription_timestamp( $value );

							if ( $new_renewal_timestamp > sumo_get_subscription_timestamp() && $new_renewal_timestamp !== sumo_get_subscription_timestamp( get_post_meta( $id, 'sumo_get_next_payment_date', true ) ) ) {
								$cron_event = new SUMO_Subscription_Cron_Event( $id );
								$cron_event->unset_events( array(
									'create_renewal_order',
									'automatic_pay',
									'notify_overdue',
									'notify_suspend',
									'notify_cancel',
									'switch_to_manual_pay_mode',
								) );

								$new_renewal_date = sumo_get_subscription_date( $new_renewal_timestamp );

								SUMOSubscriptions_Order::set_next_payment_date( $id, $new_renewal_date );
								sumo_add_subscription_note( sprintf( __( 'Subscription Due Date has changed to %s.', 'sumosubscriptions' ), $new_renewal_date ), $id, 'success', __( 'Due Date Changed', 'sumosubscriptions' ) );
							}
						}
						break;
				}
			}
		}
	}

	/**
	 * Get subscription statuses.
	 * 
	 * @return array
	 */
	protected function get_subscription_statuses() {
		$statuses   = array_map( 'strtolower', array_keys( sumo_get_subscription_statuses() ) );
		$statuses[] = 'resume';
		$statuses[] = 'activate-trial';

		return $statuses;
	}

	/**
	 * Get the item's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'subscription_id'              => array(
					'description' => __( 'Unique identifier for the resource.', 'sumosubscriptions' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_order_id'              => array(
					'description' => __( 'Parent order ID.', 'sumosubscriptions' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subscription_status'          => array(
					'description' => __( 'Subscription status.', 'sumosubscriptions' ),
					'type'        => 'string',
					'default'     => 'pending',
					'enum'        => $this->get_subscription_statuses(),
					'context'     => array( 'view', 'edit' ),
				),
				'subscription_number'          => array(
					'description' => __( 'Subscription number.', 'sumosubscriptions' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subscription_currency'        => array(
					'description' => __( 'Currency the subscription was created with, in ISO format.', 'sumosubscriptions' ),
					'type'        => 'string',
					'default'     => get_woocommerce_currency(),
					'enum'        => array_keys( get_woocommerce_currencies() ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subscription_version'         => array(
					'description' => __( 'Version of SUMO Subscriptions which last updated the subscription.', 'sumosubscriptions' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subscriber_id'                => array(
					'description' => __( 'User ID who owns the subscription.', 'sumosubscriptions' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'billing_email'                => array(
					'description' => __( 'Email address.', 'sumosubscriptions' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view', 'edit' ),
				),
				'trial_enabled'                => array(
					'description' => __( 'Define if the subscription is bought with the trial.', 'sumosubscriptions' ),
					'type'        => 'string',
					'default'     => 'no',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'signup_enabled'               => array(
					'description' => __( 'Define if the customer is charged for the signup fee when he/she subscribed.', 'sumosubscriptions' ),
					'type'        => 'string',
					'default'     => 'no',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'sync_enabled'                 => array(
					'description' => __( 'Define if the customer is bought the synchronized subscription.', 'sumosubscriptions' ),
					'type'        => 'string',
					'default'     => 'no',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'resubscribed'                 => array(
					'description' => __( 'Define if the customer has resubscribed the subscription.', 'sumosubscriptions' ),
					'type'        => 'string',
					'default'     => 'no',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_manual'                    => array(
					'description' => __( 'Define if the subscription requires manual renewal payments.', 'sumosubscriptions' ),
					'type'        => 'string',
					'default'     => 'yes',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subscribed_product'           => array(
					'description' => __( 'The product ID which is subscribed by the customer.', 'sumosubscriptions' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subscribed_qty'               => array(
					'description' => __( 'Quantity which is subscribed by the customer.', 'sumosubscriptions' ),
					'type'        => 'integer',
					'default'     => 1,
					'context'     => array( 'view', 'edit' ),
				),
				'subscribed_items'             => array(
					'description' => __( 'The product(s) which are subscribed as order level by the customer.', 'sumosubscriptions' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subscribed_items_qty'         => array(
					'description' => __( 'Quantity which is subscribed as order level by the customer.', 'sumosubscriptions' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'renewal_price'                => array(
					'description' => __( 'Renewal price of the subscription.', 'sumosubscriptions' ),
					'type'        => 'float',
					'context'     => array( 'view', 'edit' ),
				),
				'trial_fee'                    => array(
					'description' => __( 'Trial fee which is charged before the subscription price gets charged to the customer.', 'sumosubscriptions' ),
					'type'        => 'float',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'signup_fee'                   => array(
					'description' => __( 'Signup fee which is charged to the customer.', 'sumosubscriptions' ),
					'type'        => 'float',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'trial_type'                   => array(
					'description' => __( 'Define if the subscription is bought with either free/paid trial.', 'sumosubscriptions' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'additional_digital_downloads' => array(
					'description' => __( 'Define if the customer having additional digital downloads for the subscription.', 'sumosubscriptions' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'installments_cycle'           => array(
					'description' => __( 'Recurring cycle of the subscription.', 'sumosubscriptions' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'billing_plan'                 => array(
					'description' => __( 'Billing plan of the subscription. eg. 1 M', 'sumosubscriptions' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'trial_plan'                   => array(
					'description' => __( 'Trial plan of the subscription. eg. 1 W', 'sumosubscriptions' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'start_date'                   => array(
					'description' => __( "The subscription's start date.", 'sumosubscriptions' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'trial_end_date'               => array(
					'description' => __( "The subscription's trial end date.", 'sumosubscriptions' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'next_payment_date'            => array(
					'description' => __( "The subscription's next payment date.", 'sumosubscriptions' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'last_payment_date'            => array(
					'description' => __( "The subscription's last payment date.", 'sumosubscriptions' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'end_date'                     => array(
					'description' => __( "The subscription's end date.", 'sumosubscriptions' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'expiry_date'                  => array(
					'description' => __( "The subscription's expiry date.", 'sumosubscriptions' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'renewed_count'                => array(
					'description' => __( 'Total renewed count of the subscription.', 'sumosubscriptions' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'pending_renewal_order'        => array(
					'description' => __( 'The pending renewal order ID created for the subscription.', 'sumosubscriptions' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'renewal_orders'               => array(
					'description' => __( 'List of renewal orders which is created for the subscription.', 'sumosubscriptions' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'subscription_type'            => array(
					'description' => __( 'Define the type of subscription.', 'sumosubscriptions' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'payment_method'               => array(
					'description' => __( 'Payment method ID.', 'sumosubscriptions' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'payment_data'                 => array(
					'description' => __( 'Payment data for the subscription.', 'sumosubscriptions' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
