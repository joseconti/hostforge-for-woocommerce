<?php
/**
 * Ticket List Table.
 *
 * WP_List_Table implementation for displaying support tickets in admin.
 *
 * @package HostForge\Modules\SupportDesk\Admin
 */

namespace HostForge\Modules\SupportDesk\Admin;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class HF_Ticket_List_Table
 */
class HF_Ticket_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'ticket',
				'plural'   => 'tickets',
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
			'subject'    => __( 'Subject', 'hostforge' ),
			'customer'   => __( 'Customer', 'hostforge' ),
			'department' => __( 'Department', 'hostforge' ),
			'priority'   => __( 'Priority', 'hostforge' ),
			'status'     => __( 'Status', 'hostforge' ),
			'assigned'   => __( 'Assigned', 'hostforge' ),
			'last_reply' => __( 'Last Reply', 'hostforge' ),
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
			'subject'    => array( 'title', false ),
			'priority'   => array( 'priority', false ),
			'status'     => array( 'status', false ),
			'created'    => array( 'date', true ),
			'last_reply' => array( 'last_reply', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'close'           => __( 'Close', 'hostforge' ),
			'priority_high'   => __( 'Change Priority: High', 'hostforge' ),
			'priority_medium' => __( 'Change Priority: Medium', 'hostforge' ),
			'priority_low'    => __( 'Change Priority: Low', 'hostforge' ),
		);
	}

	/**
	 * Get views for status tabs.
	 *
	 * @return array
	 */
	protected function get_views(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_GET['ticket_status'] ) ? sanitize_text_field( wp_unslash( $_GET['ticket_status'] ) ) : '';
		$base    = admin_url( 'admin.php?page=hostforge-tickets' );

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

		$statuses = \HostForge\Modules\SupportDesk\HF_Support_Desk_Module::get_statuses();

		foreach ( $statuses as $status_key => $status_label ) {
			$count = $counts[ $status_key ] ?? 0;
			if ( $count > 0 ) {
				$views[ $status_key ] = sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					esc_url( add_query_arg( 'ticket_status', $status_key, $base ) ),
					$current === $status_key ? 'current' : '',
					esc_html( $status_label ),
					$count
				);
			}
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
			'post_type'      => 'hf_ticket',
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
		$status_filter = isset( $_GET['ticket_status'] ) ? sanitize_text_field( wp_unslash( $_GET['ticket_status'] ) ) : '';

		$meta_query = array();

		if ( ! empty( $status_filter ) ) {
			$meta_query[] = array(
				'key'   => '_hf_status',
				'value' => $status_filter,
			);
		}

		// Priority filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$priority_filter = isset( $_GET['ticket_priority'] ) ? sanitize_text_field( wp_unslash( $_GET['ticket_priority'] ) ) : '';
		if ( ! empty( $priority_filter ) ) {
			$meta_query[] = array(
				'key'   => '_hf_priority',
				'value' => $priority_filter,
			);
		}

		if ( ! empty( $meta_query ) ) {
			if ( count( $meta_query ) > 1 ) {
				$meta_query['relation'] = 'AND';
			}
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		// Department filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$department_filter = isset( $_GET['ticket_department'] ) ? absint( $_GET['ticket_department'] ) : 0;
		if ( $department_filter > 0 ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'hf_department',
					'field'    => 'term_id',
					'terms'    => $department_filter,
				),
			);
		}

		// Sorting.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'date';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';

		if ( 'priority' === $orderby ) {
			$args['meta_key'] = '_hf_priority'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = 'meta_value';
		} elseif ( 'status' === $orderby ) {
			$args['meta_key'] = '_hf_status'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['orderby']  = 'meta_value';
		} elseif ( 'last_reply' === $orderby ) {
			$args['meta_key'] = '_hf_last_reply_at'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
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
		return sprintf( '<input type="checkbox" name="ticket_ids[]" value="%d" />', $item->ID );
	}

	/**
	 * Subject column with row actions.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_subject( $item ): string {
		$detail_url = admin_url( 'admin.php?page=hostforge-tickets&action=detail&ticket_id=' . $item->ID );

		$actions = array(
			'detail' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $detail_url ),
				esc_html__( 'View Details', 'hostforge' )
			),
		);

		$client_id = absint( get_post_meta( $item->ID, '_hf_client_user_id', true ) );
		if ( $client_id ) {
			$actions['customer'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'user-edit.php?user_id=' . $client_id ) ),
				esc_html__( 'View Customer', 'hostforge' )
			);
		}

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $detail_url ),
			esc_html( $item->post_title ),
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
		$user_id = absint( get_post_meta( $item->ID, '_hf_client_user_id', true ) );
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
	 * Department column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_department( $item ): string {
		$terms = wp_get_object_terms( $item->ID, 'hf_department', array( 'fields' => 'names' ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '<span class="hf-muted">' . esc_html__( 'None', 'hostforge' ) . '</span>';
		}

		return esc_html( implode( ', ', $terms ) );
	}

	/**
	 * Priority column with color badge.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_priority( $item ): string {
		$priority = get_post_meta( $item->ID, '_hf_priority', true ) ?: 'medium';

		$labels = array(
			'critical' => __( 'Critical', 'hostforge' ),
			'high'     => __( 'High', 'hostforge' ),
			'medium'   => __( 'Medium', 'hostforge' ),
			'low'      => __( 'Low', 'hostforge' ),
		);

		$label = $labels[ $priority ] ?? ucfirst( $priority );

		return sprintf(
			'<span class="hf-priority hf-priority--%s">%s</span>',
			esc_attr( $priority ),
			esc_html( $label )
		);
	}

	/**
	 * Status column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_status( $item ): string {
		$status = get_post_meta( $item->ID, '_hf_status', true ) ?: 'open';
		return hf_format_status_badge( $status );
	}

	/**
	 * Assigned staff column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_assigned( $item ): string {
		$assigned_id = absint( get_post_meta( $item->ID, '_hf_assigned_to', true ) );
		$user        = $assigned_id ? get_user_by( 'id', $assigned_id ) : null;

		if ( ! $user ) {
			return '<span class="hf-muted">' . esc_html__( 'Unassigned', 'hostforge' ) . '</span>';
		}

		return esc_html( $user->display_name ?: $user->user_email );
	}

	/**
	 * Last reply column with human-readable time and replier label.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_last_reply( $item ): string {
		$last_reply_at = get_post_meta( $item->ID, '_hf_last_reply_at', true );

		if ( empty( $last_reply_at ) ) {
			return '<span class="hf-muted">' . esc_html__( 'No replies', 'hostforge' ) . '</span>';
		}

		$timestamp = strtotime( $last_reply_at );
		$now       = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$diff_secs = $now - $timestamp;

		// Show human_time_diff if less than 7 days, otherwise show date.
		if ( $diff_secs < 7 * DAY_IN_SECONDS ) {
			$date_display = sprintf(
				/* translators: %s: human time difference */
				esc_html__( '%s ago', 'hostforge' ),
				human_time_diff( $timestamp, $now )
			);
		} else {
			$date_display = esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) );
		}

		// Determine who replied last.
		$last_reply_by = absint( get_post_meta( $item->ID, '_hf_last_reply_by', true ) );
		$client_id     = absint( get_post_meta( $item->ID, '_hf_client_user_id', true ) );

		$by_label = '';
		if ( $last_reply_by > 0 ) {
			if ( $last_reply_by === $client_id ) {
				$by_label = '<br><small class="hf-muted">' . esc_html__( 'by Customer', 'hostforge' ) . '</small>';
			} else {
				$by_label = '<br><small class="hf-muted">' . esc_html__( 'by Staff', 'hostforge' ) . '</small>';
			}
		}

		return $date_display . $by_label;
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
	 * Extra controls between bulk actions and pagination.
	 *
	 * @param string $which Top or bottom.
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		$departments = get_terms(
			array(
				'taxonomy'   => 'hf_department',
				'hide_empty' => false,
			)
		);

		$statuses   = \HostForge\Modules\SupportDesk\HF_Support_Desk_Module::get_statuses();
		$priorities = \HostForge\Modules\SupportDesk\HF_Support_Desk_Module::get_priorities();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_status = isset( $_GET['ticket_status'] ) ? sanitize_text_field( wp_unslash( $_GET['ticket_status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_priority = isset( $_GET['ticket_priority'] ) ? sanitize_text_field( wp_unslash( $_GET['ticket_priority'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_department = isset( $_GET['ticket_department'] ) ? absint( $_GET['ticket_department'] ) : 0;

		?>
		<div class="alignleft actions">
			<select name="ticket_status">
				<option value=""><?php esc_html_e( 'All Statuses', 'hostforge' ); ?></option>
				<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_status, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
				<?php endforeach; ?>
			</select>

			<select name="ticket_priority">
				<option value=""><?php esc_html_e( 'All Priorities', 'hostforge' ); ?></option>
				<?php foreach ( $priorities as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_priority, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
				<?php endforeach; ?>
			</select>

			<?php if ( ! is_wp_error( $departments ) && ! empty( $departments ) ) : ?>
			<select name="ticket_department">
				<option value=""><?php esc_html_e( 'All Departments', 'hostforge' ); ?></option>
				<?php foreach ( $departments as $dept ) : ?>
				<option value="<?php echo esc_attr( $dept->term_id ); ?>" <?php selected( $current_department, $dept->term_id ); ?>>
					<?php echo esc_html( $dept->name ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>

			<?php submit_button( __( 'Filter', 'hostforge' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Get status counts for views tabs.
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
				'hf_ticket'
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
	 * Message when no tickets found.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No tickets found.', 'hostforge' );
	}
}
