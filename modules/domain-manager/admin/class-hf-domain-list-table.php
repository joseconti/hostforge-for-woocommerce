<?php
/**
 * Domain List Table.
 *
 * WP_List_Table implementation for displaying domains in admin.
 *
 * @package HostForge\Modules\DomainManager\Admin
 */

namespace HostForge\Modules\DomainManager\Admin;

use HostForge\Modules\DomainManager\HF_Domain_Manager_Module;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class HF_Domain_List_Table
 */
class HF_Domain_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'domain',
				'plural'   => 'domains',
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
		return array(
			'cb'         => '<input type="checkbox" />',
			'domain'     => __( 'Domain', 'hostforge' ),
			'customer'   => __( 'Customer', 'hostforge' ),
			'registrar'  => __( 'Registrar', 'hostforge' ),
			'status'     => __( 'Status', 'hostforge' ),
			'expiry'     => __( 'Expiry Date', 'hostforge' ),
			'auto_renew' => __( 'Auto-Renew', 'hostforge' ),
			'created'    => __( 'Created', 'hostforge' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'domain'  => array( 'title', false ),
			'status'  => array( '_hf_status', false ),
			'expiry'  => array( '_hf_expiry_date', false ),
			'created' => array( 'date', false ),
		);
	}

	/**
	 * Get status views (tabs).
	 *
	 * @return array
	 */
	protected function get_views(): array {
		$statuses = HF_Domain_Manager_Module::get_statuses();
		$current  = sanitize_text_field( wp_unslash( $_GET['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$base_url = admin_url( 'admin.php?page=hostforge-domains' );
		$views    = array();

		// Count all.
		$total = $this->count_domains();

		$views['all'] = sprintf(
			'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
			esc_url( $base_url ),
			empty( $current ) ? 'current' : '',
			esc_html__( 'All', 'hostforge' ),
			$total
		);

		foreach ( $statuses as $slug => $label ) {
			$count = $this->count_domains( $slug );
			if ( $count > 0 ) {
				$views[ $slug ] = sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					esc_url( add_query_arg( 'status', $slug, $base_url ) ),
					$current === $slug ? 'current' : '',
					esc_html( $label ),
					$count
				);
			}
		}

		return $views;
	}

	/**
	 * Count domains by status.
	 *
	 * @param string $status Optional status filter.
	 * @return int
	 */
	private function count_domains( string $status = '' ): int {
		$args = array(
			'post_type'      => 'hf_domain',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		if ( ! empty( $status ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_hf_status',
					'value' => $status,
				),
			);
		}

		$query = new \WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'sync'               => __( 'Sync with Registrar', 'hostforge' ),
			'enable_auto_renew'  => __( 'Enable Auto-Renew', 'hostforge' ),
			'disable_auto_renew' => __( 'Disable Auto-Renew', 'hostforge' ),
		);
	}

	/**
	 * Prepare table items.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$per_page = 20;
		$paged    = $this->get_pagenum();

		$args = array(
			'post_type'      => 'hf_domain',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		);

		// Status filter.
		$status = sanitize_text_field( wp_unslash( $_GET['status'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $status ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_hf_status',
					'value' => $status,
				),
			);
		}

		// Search.
		$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $search ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_hf_domain_name',
					'value'   => $search,
					'compare' => 'LIKE',
				),
			);
		}

		// Sorting.
		$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? 'date' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order   = sanitize_text_field( wp_unslash( $_GET['order'] ?? 'DESC' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( in_array( $orderby, array( '_hf_status', '_hf_expiry_date' ), true ) ) {
			$args['meta_key'] = $orderby; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = 'meta_value';
		} else {
			$args['orderby'] = $orderby;
		}

		$args['order'] = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

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
	 * Column: Checkbox.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="domain_ids[]" value="%d" />', $item->ID );
	}

	/**
	 * Column: Domain name.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_domain( $item ): string {
		$domain_name = get_post_meta( $item->ID, '_hf_domain_name', true );
		$detail_url  = admin_url( 'admin.php?page=hostforge-domains&tab=detail&domain_id=' . $item->ID );
		$order_id    = absint( get_post_meta( $item->ID, '_hf_order_id', true ) );

		$actions = array(
			'view' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $detail_url ),
				esc_html__( 'View Details', 'hostforge' )
			),
		);

		if ( $order_id ) {
			$order_url        = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
			$actions['order'] = sprintf(
				'<a href="%s">%s #%d</a>',
				esc_url( $order_url ),
				esc_html__( 'Order', 'hostforge' ),
				$order_id
			);
		}

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $detail_url ),
			esc_html( $domain_name ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Column: Customer.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_customer( $item ): string {
		$user_id = absint( get_post_meta( $item->ID, '_hf_user_id', true ) );

		if ( ! $user_id ) {
			return '&mdash;';
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return '&mdash;';
		}

		return sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ),
			esc_html( $user->display_name )
		);
	}

	/**
	 * Column: Registrar.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_registrar( $item ): string {
		$registrar = get_post_meta( $item->ID, '_hf_registrar_id', true );
		return esc_html( ucfirst( ! empty( $registrar ) ? $registrar : '&mdash;' ) );
	}

	/**
	 * Column: Status.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_status( $item ): string {
		$raw_status = get_post_meta( $item->ID, '_hf_status', true );
		$status     = ! empty( $raw_status ) ? $raw_status : 'pending';
		$statuses   = HF_Domain_Manager_Module::get_statuses();
		$label      = $statuses[ $status ] ?? ucfirst( $status );

		return sprintf(
			'<span class="hf-status-badge hf-status-badge--%s">%s</span>',
			esc_attr( $status ),
			esc_html( $label )
		);
	}

	/**
	 * Column: Expiry date.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_expiry( $item ): string {
		$expiry = get_post_meta( $item->ID, '_hf_expiry_date', true );

		if ( empty( $expiry ) ) {
			return '&mdash;';
		}

		$expiry_time    = strtotime( $expiry );
		$days_remaining = (int) ceil( ( $expiry_time - time() ) / DAY_IN_SECONDS );

		$class = '';
		if ( $days_remaining <= 7 ) {
			$class = ' hf-text--error';
		} elseif ( $days_remaining <= 30 ) {
			$class = ' hf-text--warning';
		}

		return sprintf(
			'<span class="%s" title="%s">%s</span>',
			esc_attr( $class ),
			/* translators: %d: number of days */
			esc_attr( sprintf( __( '%d days remaining', 'hostforge' ), $days_remaining ) ),
			esc_html( wp_date( get_option( 'date_format' ), $expiry_time ) )
		);
	}

	/**
	 * Column: Auto-renew.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_auto_renew( $item ): string {
		$auto_renew = get_post_meta( $item->ID, '_hf_auto_renew', true );

		if ( 'yes' === $auto_renew ) {
			return '<span class="hf-badge hf-badge--success">' . esc_html__( 'Yes', 'hostforge' ) . '</span>';
		}

		return '<span class="hf-badge hf-badge--muted">' . esc_html__( 'No', 'hostforge' ) . '</span>';
	}

	/**
	 * Column: Created date.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_created( $item ): string {
		$reg_date = get_post_meta( $item->ID, '_hf_registration_date', true );

		if ( ! empty( $reg_date ) ) {
			return esc_html( wp_date( get_option( 'date_format' ), strtotime( $reg_date ) ) );
		}

		return esc_html( get_the_date( '', $item ) );
	}

	/**
	 * Message when no items found.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No domains found.', 'hostforge' );
	}
}
