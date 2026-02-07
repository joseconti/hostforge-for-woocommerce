<?php
/**
 * Exit if accessed directly
 *
 * @since      1.0.0
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * This is construct of class where all susbcriptions listed.
 *
 * @name ASWC_Admin_Subscription_List
 * @since      1.0.0
 * @category Class
 */
class ASWC_Admin_Subscription_List extends WP_List_Table {
	/**
	 * This is variable which is used for the store all the data.
	 *
	 * @var array $example_data variable for store data.
	 */
	public $example_data;

	/**
	 * This is variable which is used for the total count.
	 *
	 * @var array $aswc_total_count variable for total count.
	 */
	public $aswc_total_count;


	/**
	 * This construct colomns in susbcription table.
	 *
	 * @name get_columns.
	 * @since      1.0.0
	 */
	public function get_columns() {

				$columns = array(
					'cb'                        => '<input type="checkbox" />',
					'subscription_id'           => __( 'Subscription ID', 'advanced-subscriptions-for-woocommerce' ),
					'parent_order_id'           => __( 'Parent Order ID', 'advanced-subscriptions-for-woocommerce' ),
					'status'                    => __( 'Status', 'advanced-subscriptions-for-woocommerce' ),
					'items'                     => __( 'Items', 'advanced-subscriptions-for-woocommerce' ),
					'total'                     => __( 'Total', 'advanced-subscriptions-for-woocommerce' ),
					'start_date'                => __( 'Start Date', 'advanced-subscriptions-for-woocommerce' ),
					'trial_end'                 => __( 'Trial End', 'advanced-subscriptions-for-woocommerce' ),
					'next_payment_date'         => __( 'Next Payment', 'advanced-subscriptions-for-woocommerce' ),
					'last_order_date'           => __( 'Last Order Date', 'advanced-subscriptions-for-woocommerce' ),
					'subscriptions_expiry_date' => __( 'End Date', 'advanced-subscriptions-for-woocommerce' ),

				);
				return apply_filters( 'aswc_column_subscription_table', $columns );
	}

	/**
	 * Get Cancel url.
	 *
	 * @name aswc_cancel_url.
	 * @since      1.0.0
	 * @param int    $subscription_id subscription_id.
	 * @param String $status status.
	 */
	public function aswc_cancel_url( $subscription_id, $status ) {
		$aswc_link = add_query_arg(
			array(
				'aswc_subscription_id'           => $subscription_id,
				'aswc_subscription_status_admin' => $status,
			)
		);

		$aswc_link = wp_nonce_url( $aswc_link, 'aswc_cancel_subscription_' . $subscription_id );
		$actions   = array(
			'aswc_cancel' => '<a href="' . $aswc_link . '">' . __( 'Cancel', 'advanced-subscriptions-for-woocommerce' ) . '</a>',

		);
		return $actions;
	}
	/**
	 * Get On-hold url.
	 *
	 * @name aswc_on_hold_url.
	 * @since      1.0.0
	 * @param int    $subscription_id subscription_id.
	 * @param String $status status.
	 */
	public function aswc_on_hold_url( $subscription_id, $status ) {
		$aswc_link = add_query_arg(
			array(
				'aswc_subscription_id' => $subscription_id,
				'aswc_subscription_status_admin_reactivate' => $status,
			)
		);

		$aswc_link = wp_nonce_url( $aswc_link, $subscription_id . $status );
		$actions   = array(
			'aswc_reactivate' => '<a href="' . $aswc_link . '">' . __( 'Reactivate', 'advanced-subscriptions-for-woocommerce' ) . '</a>',

		);
		return $actions;
	}
	/**
	 * This show susbcriptions table list.
	 *
	 * @name column_default.
	 * @since      1.0.0
	 * @param array  $item  array of the items.
	 * @param string $column_name name of the colmn.
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'subscription_id':
				$actions             = array();
				$aswc_status         = array( 'active' );
				$aswc_status_on_hold = array( 'on-hold' );
				$aswc_status         = apply_filters( 'aswc_status_array', $aswc_status );
				if ( in_array( $item['status'], $aswc_status ) ) {
					$actions = $this->aswc_cancel_url( $item['subscription_id'], $item['status'] );
				}
				$actions = apply_filters( 'aswc_add_action_details', $actions, $item['subscription_id'] );
				if ( in_array( $item['status'], $aswc_status_on_hold ) ) {
					$actions = $this->aswc_on_hold_url( $item['subscription_id'], $item['status'] );
				}
				return $item[ $column_name ] . $this->row_actions( $actions );
			case 'parent_order_id':
				if ( 'manual' === $item[ $column_name ] ) {
					$html = __( 'Manual', 'advanced-subscriptions-for-woocommerce' );
				} elseif ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$html = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $item[ $column_name ] ) ) . '">' . $item[ $column_name ] . '</a>';
				} else {
					$html = '<a href="' . esc_url( get_edit_post_link( $item[ $column_name ] ) ) . '">' . $item[ $column_name ] . '</a>';
				}
				return $html;
			case 'status':
					$label = ucfirst( $item['status'] );
				return '<mark class="order-status status-' . esc_attr( $item['status'] ) . ' tips"><span>' . esc_html( $label ) . '</span></mark>';
			case 'items':
				return $item['items'];
			case 'total':
				return $item['total'];
			case 'start_date':
				return $item['start_date'];
			case 'trial_end':
				return $item['trial_end'];
			case 'next_payment_date':
				return $item['next_payment_date'];
			case 'last_order_date':
				return $item['last_order_date'];
			case 'subscriptions_expiry_date':
				return $item['subscriptions_expiry_date'];
			default:
				return apply_filters( 'aswc_add_case_column', false, $column_name, $item );
		}
	}

	/**
	 * Perform admin bulk action setting for susbcription table.
	 *
	 * @name process_bulk_action.
	 */
	public function process_bulk_action() {

		if ( 'bulk-delete' === $this->current_action() ) {

			if ( isset( $_POST['susbcription_list_table'] ) ) {
				$susbcription_list_table = sanitize_text_field( wp_unslash( $_POST['susbcription_list_table'] ) );
				if ( wp_verify_nonce( $susbcription_list_table, 'susbcription_list_table' ) ) {
					if ( isset( $_POST['aswc_subscriptions_ids'] ) && ! empty( $_POST['aswc_subscriptions_ids'] ) ) {
						$all_id = map_deep( wp_unslash( $_POST['aswc_subscriptions_ids'] ), 'sanitize_text_field' );
						foreach ( $all_id as $key => $value ) {
							if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
								$subscription = new ASWC_Subscription( $value );
								$subscription->delete( true );
							} else {
								wp_delete_post( $value, true );
							}
						}
						?>
							<div class="notice notice-success is-dismissible"> 
								<p><strong><?php esc_html_e( 'Subscriptions Deleted Successfully', 'advanced-subscriptions-for-woocommerce' ); ?></strong></p>
							</div>
						<?php
					}
				}
			}
		} elseif ( 'bulk-cancel' === $this->current_action() ) {
			if ( isset( $_POST['susbcription_list_table'] ) ) {
				$susbcription_list_table = sanitize_text_field( wp_unslash( $_POST['susbcription_list_table'] ) );
				if ( wp_verify_nonce( $susbcription_list_table, 'susbcription_list_table' ) ) {
					if ( isset( $_POST['aswc_subscriptions_ids'] ) && ! empty( $_POST['aswc_subscriptions_ids'] ) ) {
						$all_id = map_deep( wp_unslash( $_POST['aswc_subscriptions_ids'] ), 'sanitize_text_field' );
						foreach ( $all_id as $key => $value ) {
							do_action( 'aswc_subscription_cancel', $value, 'Cancel' );
							aswc_update_meta_data( $value, 'aswc_subscription_cancelled_by', 'by_admin_bulk_action' );
														aswc_update_meta_data( $value, 'aswc_subscription_cancelled_date', current_time( 'timestamp' ) );
						}
						?>
							<div class="notice notice-success is-dismissible"> 
								<p><strong><?php esc_html_e( 'Subscriptions Cancelled Successfully', 'advanced-subscriptions-for-woocommerce' ); ?></strong></p>
							</div>
						<?php
					}
				}
			}
		}
		do_action( 'aswc_process_bulk_reset_option', $this->current_action(), $_POST );
	}
	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @name process_bulk_action.
	 * @since      1.0.0
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'advanced-subscriptions-for-woocommerce' ),
			'bulk-cancel' => __( 'Cancel', 'advanced-subscriptions-for-woocommerce' ),
		);
		return apply_filters( 'aswc_bulk_option', $actions );
	}

	/**
	 * Returns an associative array containing the bulk action for sorting.
	 *
	 * @name get_sortable_columns.
	 * @since      1.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'subscription_id'           => array( 'subscription_id', false ),
			'parent_order_id'           => array( 'parent_order_id', false ),
			'status'                    => array( 'status', false ),
			'items'                     => array( 'items', false ),
			'total'                     => array( 'total', false ),
			'start_date'                => array( 'start_date', false ),
			'trial_end'                 => array( 'trial_end', false ),
			'next_payment_date'         => array( 'next_payment_date', false ),
			'last_order_date'           => array( 'last_order_date', false ),
			'subscriptions_expiry_date' => array( 'subscriptions_expiry_date', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Prepare items for sorting.
	 *
	 * @name prepare_items.
	 * @since      1.0.0
	 */
	public function prepare_items() {
		$per_page              = 10;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();
		$current_page = $this->get_pagenum();

		$this->example_data = $this->aswc_get_subscription_list();
		$data               = $this->example_data;
		usort( $data, array( $this, 'aswc_usort_reorder' ) );
		$total_items = $this->aswc_total_count;
		$this->items = $data;
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Return sorted associative array.
	 *
	 * @name aswc_usort_reorder.
	 * @since      1.0.0
	 * @return array
	 * @param array $cloumna column of the susbcriptions.
	 * @param array $cloumnb column of the susbcriptions.
	 */
	public function aswc_usort_reorder( $cloumna, $cloumnb ) {

		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'subscription_id';
		$order   = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'desc';

		if ( is_numeric( $cloumna[ $orderby ] ) && is_numeric( $cloumnb[ $orderby ] ) ) {
			if ( $cloumna[ $orderby ] === $cloumnb[ $orderby ] ) {
				return 0;
			} elseif ( $cloumna[ $orderby ] < $cloumnb[ $orderby ] ) {
				$result = -1;
				return ( 'asc' === $order ) ? $result : -$result;
			} elseif ( $cloumna[ $orderby ] > $cloumnb[ $orderby ] ) {
				$result = 1;
				return ( 'asc' === $order ) ? $result : -$result;
			}
		} else {
			$result = strcmp( $cloumna[ $orderby ], $cloumnb[ $orderby ] );
			return ( 'asc' === $order ) ? $result : -$result;
		}
	}

	/**
	 * THis function is used for the add the checkbox.
	 *
	 * @name column_cb.
	 * @since      1.0.0
	 * @return array
	 * @param array $item array of the items.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="aswc_subscriptions_ids[]" value="%s" />',
			$item['subscription_id']
		);
	}


	/**
	 * This function used to get all susbcriptions list.
	 *
	 * @name aswc_get_subscription_list.
	 * @since      1.0.0
	 * @return array
	 */
	public function aswc_get_subscription_list() {

		$current_page = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : 1;

		// get the data by pagination.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$offset = ( $current_page - 1 ) * 10;
			$args   = array(
				'number'     => 10,
				'offset'     => $offset,
				'return'     => 'ids',
				'type'       => 'aswc_subscriptions',
				'status'     => function_exists( 'aswc_get_subscription_statuses_for_query' ) ? aswc_get_subscription_statuses_for_query() : array(),
				'meta_query' => array(
					'key'     => 'aswc_customer_id',
					'compare' => 'EXISTS',
				),
			);

			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				// Logic to fetch subscription using subscription id or parent id.
				$maybe_subscription_or_parent_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

				$sub_id = aswc_get_meta_data( $maybe_subscription_or_parent_id, 'aswc_parent_order', true );
				if ( $sub_id ) {
					$maybe_subscription_or_parent_id = $sub_id;
				}
				if ( $maybe_subscription_or_parent_id ) {
					$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'aswc_parent_order',
							'value'   => $maybe_subscription_or_parent_id,
							'compare' => 'LIKE',
						),
					);
				} else {
					$username_or_email = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
					// Logic to fetch subscription using username or email.

					$user = get_user_by( 'email', $username_or_email );

					// If no user is found by email, try to get by username.
					if ( ! $user ) {
						$user = get_user_by( 'login', $username_or_email );
					}
					$customer_id = $user ? $user->ID : false;

					$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'aswc_customer_id',
							'value'   => $customer_id,
							'compare' => 'LIKE',
						),
					);
				}
			}
			$aswc_subscriptions = wc_get_orders( $args );

		} else {

			$args = array(
				'posts_per_page' => 10,
				'paged'          => $current_page,
				'post_type'      => 'aswc_subscriptions',
				'post_status'    => function_exists( 'aswc_get_subscription_post_statuses_for_query' ) ? aswc_get_subscription_post_statuses_for_query() : 'any',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'aswc_customer_id',
						'compare' => 'EXISTS',
					),
				),
				'fields'         => 'ids',
			);

			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				// Logic to fetch subscription using subscription id or parent id.
				$maybe_subscription_or_parent_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

				$sub_id = aswc_get_meta_data( $maybe_subscription_or_parent_id, 'aswc_parent_order', true );
				if ( $sub_id ) {
					$maybe_subscription_or_parent_id = $sub_id;
				}
				if ( $maybe_subscription_or_parent_id ) {
					$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'aswc_parent_order',
							'value'   => $maybe_subscription_or_parent_id,
							'compare' => 'LIKE',
						),
					);
				} else {
					$username_or_email = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
					// Logic to fetch subscription using username or email.

					$user = get_user_by( 'email', $username_or_email );

					// If no user is found by email, try to get by username.
					if ( ! $user ) {
						$user = get_user_by( 'login', $username_or_email );
					}
					$customer_id = $user ? $user->ID : false;

					$args['meta_query'] = array(
						array(
							'key'     => 'aswc_customer_id',
							'value'   => $customer_id,
							'compare' => 'LIKE',
						),
					);
				}
			}
			$aswc_subscriptions = get_posts( $args );
		}

		// Code to get the total item count.
		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$args2 = array(
				'type'       => 'aswc_subscriptions',
				'limit'      => -1,
				'status'     => function_exists( 'aswc_get_subscription_statuses_for_query' ) ? aswc_get_subscription_statuses_for_query() : array(),
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'aswc_customer_id',
						'compare' => 'EXISTS',
					),
				),
				'return'     => 'ids',
			);
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				// Logic to fetch subscription using subscription id or parent id.
				$maybe_subscription_or_parent_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

				$sub_id = aswc_get_meta_data( $maybe_subscription_or_parent_id, 'aswc_parent_order', true );
				if ( $sub_id ) {
					$maybe_subscription_or_parent_id = $sub_id;
				}
				if ( $maybe_subscription_or_parent_id ) {
					$args2['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'aswc_parent_order',
							'value'   => $maybe_subscription_or_parent_id,
							'compare' => 'LIKE',
						),
					);
				} else {
					$username_or_email = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
					// Logic to fetch subscription using username or email.

					$user = get_user_by( 'email', $username_or_email );

					// If no user is found by email, try to get by username.
					if ( ! $user ) {
						$user = get_user_by( 'login', $username_or_email );
					}
					$customer_id = $user ? $user->ID : false;

					$args2['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'aswc_customer_id',
							'value'   => $customer_id,
							'compare' => 'LIKE',
						),
					);
				}
			}
			$aswc_subscriptions2 = wc_get_orders( $args2 );

		} else {
			$args2 = array(
				'numberposts' => -1,
				'post_type'   => 'aswc_subscriptions',
				'post_status' => function_exists( 'aswc_get_subscription_post_statuses_for_query' ) ? aswc_get_subscription_post_statuses_for_query() : 'any',
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'aswc_customer_id',
						'compare' => 'EXISTS',
					),
				),
				'fields'      => 'ids',
			);
			if ( isset( $_REQUEST['s'] ) && ! empty( $_REQUEST['s'] ) ) {
				// Logic to fetch subscription using subscription id or parent id.
				$maybe_subscription_or_parent_id = (int) sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

				$sub_id = aswc_get_meta_data( $maybe_subscription_or_parent_id, 'aswc_parent_order', true );
				if ( $sub_id ) {
					$maybe_subscription_or_parent_id = $sub_id;
				}
				if ( $maybe_subscription_or_parent_id ) {
					$args2['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'aswc_parent_order',
							'value'   => $maybe_subscription_or_parent_id,
							'compare' => 'LIKE',
						),
					);
				} else {
					$username_or_email = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
					// Logic to fetch subscription using username or email.

					$user = get_user_by( 'email', $username_or_email );

					// If no user is found by email, try to get by username.
					if ( ! $user ) {
						$user = get_user_by( 'login', $username_or_email );
					}
					$customer_id = $user ? $user->ID : false;

					$args2['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'aswc_customer_id',
							'value'   => $customer_id,
							'compare' => 'LIKE',
						),
					);
				}
			}
			$aswc_subscriptions2 = get_posts( $args2 );
		}
		$total_count = count( $aswc_subscriptions2 );

		// redirection from order edit page link to specific subscription .
		if ( isset( $_GET['aswc_order_type'] ) && 'subscription' === $_GET['aswc_order_type'] ) {
			$order_id            = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : 0;
			$aswc_subs_id        = aswc_get_meta_data( $order_id, 'aswc_parent_order', true );
			$args2['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'aswc_parent_order',
					'value'   => $aswc_subs_id,
					'compare' => 'LIKE',
				),
			);

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$aswc_subscriptions = wc_get_orders( $args2 );
			} else {
				$aswc_subscriptions = get_posts( $args2 );
			}
		}

		$aswc_subscriptions_data = array();

		if ( isset( $aswc_subscriptions ) && ! empty( $aswc_subscriptions ) && is_array( $aswc_subscriptions ) ) {
			foreach ( $aswc_subscriptions as $id ) {

				$parent_order_id = aswc_get_meta_data( $id, 'aswc_parent_order', true );
				if ( 'manual' !== $parent_order_id && function_exists( 'aswc_check_valid_order' ) && ! aswc_check_valid_order( $parent_order_id ) ) {
					$total_count = --$total_count;
					continue;
				}
				$aswc_subscription_status = aswc_get_meta_data( $id, 'aswc_subscription_status', true );
				$product_name             = aswc_get_meta_data( $id, 'product_name', true );
				$aswc_recurring_total     = aswc_get_meta_data( $id, 'aswc_recurring_total', true );
				$aswc_curr_args           = array();

				if ( is_array( $product_name ) ) {
					$product_name = implode( ', ', $product_name );
				}
				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$susbcription = new ASWC_Subscription( $id );
				} else {
					$susbcription = wc_get_order( $id );
				}
				if ( isset( $susbcription ) && ! empty( $susbcription ) ) {
					$aswc_recurring_total = $susbcription->get_total();
					$aswc_curr_args       = array(
						'currency' => $susbcription->get_currency(),
					);
				}
				$aswc_recurring_total = aswc_recerring_total_price_list_table_callback( wc_price( $aswc_recurring_total, $aswc_curr_args ), $id );

				$aswc_recurring_total   = apply_filters( 'aswc_recerring_total_price_list_table', $aswc_recurring_total, $id );
				$aswc_next_payment_date = aswc_get_meta_data( $id, 'aswc_next_payment_date', true );
				$aswc_susbcription_end  = aswc_get_meta_data( $id, 'aswc_susbcription_end', true );
				if ( $aswc_next_payment_date === $aswc_susbcription_end ) {
					$aswc_next_payment_date = '';
				}

				if ( 'on-hold' === $aswc_subscription_status ) {
					$aswc_next_payment_date = '';
					$aswc_recurring_total   = '---';
				}
				if ( 'paused' === $aswc_subscription_status ) {
					$aswc_next_payment_date = '';
				}
				if ( 'cancelled' === $aswc_subscription_status ) {
					$aswc_next_payment_date = '';
					$aswc_susbcription_end  = '';
					$aswc_recurring_total   = '---';
				}
				$aswc_customer_id = aswc_get_meta_data( $id, 'aswc_customer_id', true );
				$user             = get_user_by( 'id', $aswc_customer_id );

				$is_payment_manual = aswc_get_meta_data( $id, 'aswc_payment_type', true );

				$parent_order = wc_get_order( $parent_order_id );
				$payment_type = $parent_order ? $parent_order->get_payment_method_title() : null;
				if ( $is_payment_manual ) {
					$payment_type = $payment_type . ' Via Manual Method';
				}
				$user_nicename = isset( $user->user_login ) ? $user->user_login : '';

							$start_date = aswc_get_meta_data( $id, 'aswc_schedule_start', true );
							$trial_end  = aswc_get_meta_data( $id, 'aswc_susbcription_trial_end', true );
				$last_order             = '';
				if ( true === function_exists( 'aswc_get_last_renewal_order_date' ) ) {
					$last_order = aswc_get_last_renewal_order_date( $id );
				}
				$aswc_subscriptions_data[] = apply_filters(
					'aswc_subs_table_data',
					array(
						'subscription_id'           => $id,
						'parent_order_id'           => $parent_order_id,
						'status'                    => $aswc_subscription_status,
						'items'                     => $product_name,
						'total'                     => apply_filters( 'aswc_display_recurring_price', $aswc_recurring_total, $id ),
						'start_date'                => aswc_get_the_wordpress_date_format( $start_date ),
						'trial_end'                 => aswc_get_the_wordpress_date_format( $trial_end ),
						'next_payment_date'         => aswc_get_the_wordpress_date_format( $aswc_next_payment_date ),
						'last_order_date'           => aswc_get_the_wordpress_date_format( $last_order ),
						'subscriptions_expiry_date' => aswc_get_the_wordpress_date_format( $aswc_susbcription_end ),
					)
				);
			}
		}
		$this->aswc_total_count = $total_count;
		return $aswc_subscriptions_data;
	}

	/**
	 * Create the extra table option.
	 *
	 * @name extra_tablenav.
	 * @since      1.0.0
	 * @param string $which which.
	 */
	public function extra_tablenav( $which ) {
		// Add list option.
		do_action( 'aswc_extra_tablenav_html', $which );
	}
}

if ( isset( $_GET['aswc_subscription_view_renewal_order'] ) && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) && defined( 'ASWC_INCLUDES_PATH' ) ) {
		$aswc_status     = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_view_renewal_order'] ) );
		$subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) );
	if ( aswc_check_valid_subscription( $subscription_id ) ) {
			global $aswc_subscription_id;
			$aswc_subscription_id = $subscription_id;
			require_once ASWC_INCLUDES_PATH . 'admin/partials/class-aswc-loaderview-renewal-list.php';
	}
} else {
	?>
	<div class="aswc_subscription_table_inner_wrap">
	<h3 class="wp-heading-inline" id="aswc_heading"><?php esc_html_e( 'Subscriptions', 'advanced-subscriptions-for-woocommerce' ); ?></h3>
	<?php do_action( 'aswc_add_button_manual_subscription' ); ?>
	</div>
		<form method="post">
		<input type="hidden" name="page" value="susbcription_list_table">
		<?php wp_nonce_field( 'susbcription_list_table', 'susbcription_list_table' ); ?>
		<div class="aswc_list_table">
			<?php
					$mylisttable = new ASWC_Admin_Subscription_List();
			$mylisttable->prepare_items();
			$mylisttable->search_box( __( 'Search Order', 'advanced-subscriptions-for-woocommerce' ), 'aswc-order' );
			$mylisttable->display();
			?>
		</div>
	</form>
	<?php
}
