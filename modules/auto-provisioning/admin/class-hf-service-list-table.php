<?php
/**
 * Service List Table.
 *
 * WP_List_Table implementation for displaying services in admin.
 *
 * @package HostForge\Modules\AutoProvisioning\Admin
 */

namespace HostForge\Modules\AutoProvisioning\Admin;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class HF_Service_List_Table
 */
class HF_Service_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'service',
				'plural'   => 'services',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get table columns.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'domain'   => __( 'Domain', 'hostforge' ),
			'customer' => __( 'Customer', 'hostforge' ),
			'product'  => __( 'Product', 'hostforge' ),
			'server'   => __( 'Server', 'hostforge' ),
			'status'   => __( 'Status', 'hostforge' ),
			'created'  => __( 'Created', 'hostforge' ),
		);

		/**
		 * Filter the service list table columns in admin.
		 *
		 * Allows adding, removing, or reordering columns
		 * in the admin service list table.
		 *
		 * @since 1.0.0
		 *
		 * @param array $columns Associative array of column_slug => column_label.
		 */
		return apply_filters( 'hostforge_service_admin_columns', $columns );
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'domain'  => array( 'title', false ),
			'status'  => array( 'status', false ),
			'created' => array( 'date', true ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'suspend'   => __( 'Suspend', 'hostforge' ),
			'unsuspend' => __( 'Unsuspend', 'hostforge' ),
		);
	}

	/**
	 * Get views for status tabs.
	 *
	 * @return array
	 */
	protected function get_views(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_GET['service_status'] ) ? sanitize_text_field( wp_unslash( $_GET['service_status'] ) ) : '';
		$base    = admin_url( 'admin.php?page=hostforge-services' );

		$counts = $this->get_status_counts();
		$total  = array_sum( $counts );

		$views = array(
			'all' => sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
				esc_url( $base ),
				empty( $current ) ? 'current' : '',
				esc_html__( 'All', 'hostforge' ),
				$total
			),
		);

		$statuses = \HostForge\Modules\AutoProvisioning\HF_Auto_Provisioning_Module::get_statuses();

		foreach ( $statuses as $status_key => $status_label ) {
			$count = $counts[ $status_key ] ?? 0;
			if ( $count > 0 ) {
				$views[ $status_key ] = sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					esc_url( add_query_arg( 'service_status', $status_key, $base ) ),
					$current === $status_key ? 'current' : '',
					esc_html( $status_label ),
					$count
				);
			}
		}

		// Add cancellation requests view.
		$cancel_count = $this->get_cancellation_count();
		if ( $cancel_count > 0 ) {
			$views['cancellations'] = sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'service_status', 'cancellations', $base ) ),
				'cancellations' === $current ? 'current' : '',
				esc_html__( 'Cancellation Requests', 'hostforge' ),
				$cancel_count
			);
		}

		return $views;
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$per_page = 20;
		$paged    = $this->get_pagenum();

		$args = array(
			'post_type'      => 'hf_service',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		);

		// Search.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Status filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status_filter = isset( $_GET['service_status'] ) ? sanitize_text_field( wp_unslash( $_GET['service_status'] ) ) : '';

		if ( 'cancellations' === $status_filter ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_hf_cancel_requested_at',
					'compare' => 'EXISTS',
				),
			);
		} elseif ( ! empty( $status_filter ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_hf_status',
					'value' => $status_filter,
				),
			);
		}

		// Sorting.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'date';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';

		if ( 'status' === $orderby ) {
			$args['meta_key'] = '_hf_status'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = 'meta_value';
		} else {
			$args['orderby'] = $orderby;
		}
		$args['order'] = $order;

		$query = new \WP_Query( $args );

		$this->items = $query->posts;

		$this->set_pagination_args(
			array(
				'total_items' => $query->found_posts,
				'per_page'    => $per_page,
				'total_pages' => $query->max_num_pages,
			)
		);
	}

	/**
	 * Default column output.
	 *
	 * @param \WP_Post $item        Post object.
	 * @param string   $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return '';
	}

	/**
	 * Checkbox column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="service_ids[]" value="%d" />', $item->ID );
	}

	/**
	 * Domain column with actions.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_domain( $item ): string {
		$domain     = get_post_meta( $item->ID, '_hf_domain', true );
		$detail_url = admin_url( 'admin.php?page=hostforge-services&action=detail&service_id=' . $item->ID );

		$actions = array(
			'detail' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $detail_url ),
				esc_html__( 'View Details', 'hostforge' )
			),
		);

		$order_id = absint( get_post_meta( $item->ID, '_hf_order_id', true ) );
		if ( $order_id ) {
			$actions['order'] = sprintf(
				'<a href="%s">%s #%d</a>',
				esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ),
				esc_html__( 'Order', 'hostforge' ),
				$order_id
			);
		}

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $detail_url ),
			esc_html( $domain ?: $item->post_title ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Customer column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_customer( $item ): string {
		$user_id = absint( get_post_meta( $item->ID, '_hf_user_id', true ) );
		$user    = $user_id ? get_user_by( 'id', $user_id ) : null;

		if ( ! $user ) {
			return '<span class="hf-muted">' . esc_html__( 'Unknown', 'hostforge' ) . '</span>';
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ),
			esc_html( $user->display_name ?: $user->user_email )
		);
	}

	/**
	 * Product column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_product( $item ): string {
		$product_id = absint( get_post_meta( $item->ID, '_hf_product_id', true ) );
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product ) {
			return '<span class="hf-muted">' . esc_html__( 'Deleted', 'hostforge' ) . '</span>';
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'post.php?post=' . $product_id . '&action=edit' ) ),
			esc_html( $product->get_name() )
		);
	}

	/**
	 * Server column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_server( $item ): string {
		$server_id = absint( get_post_meta( $item->ID, '_hf_server_id', true ) );
		$server    = $server_id ? get_post( $server_id ) : null;

		if ( ! $server ) {
			return '<span class="hf-muted">' . esc_html__( 'None', 'hostforge' ) . '</span>';
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=hostforge-servers&action=edit&server_id=' . $server_id ) ),
			esc_html( $server->post_title )
		);
	}

	/**
	 * Status column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_status( $item ): string {
		$status = get_post_meta( $item->ID, '_hf_status', true ) ?: 'pending';
		return hf_format_status_badge( $status );
	}

	/**
	 * Created column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_created( $item ): string {
		$date = get_the_date( get_option( 'date_format' ), $item );
		$time = get_the_time( get_option( 'time_format' ), $item );
		return esc_html( $date . ' ' . $time );
	}

	/**
	 * Get status counts.
	 *
	 * @return array<string, int>
	 */
	private function get_status_counts(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS status, COUNT(*) AS total
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
				WHERE p.post_type = %s AND p.post_status = 'publish'
				GROUP BY pm.meta_value",
				'_hf_status',
				'hf_service'
			)
		);

		$counts = array();
		if ( $results ) {
			foreach ( $results as $row ) {
				$counts[ $row->status ] = absint( $row->total );
			}
		}

		return $counts;
	}

	/**
	 * Get count of services with pending cancellation requests.
	 *
	 * @return int
	 */
	private function get_cancellation_count(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return absint(
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
					WHERE p.post_type = %s AND p.post_status = 'publish'",
					'_hf_cancel_requested_at',
					'hf_service'
				)
			)
		);
	}

	/**
	 * Message when no services found.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No services found. Services are created automatically when hosting orders are completed.', 'hostforge' );
	}
}
