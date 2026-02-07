<?php
/**
 * Server List Table.
 *
 * WP_List_Table implementation for displaying servers in admin.
 *
 * @package HostForge\Modules\ServerManager\Admin
 */

namespace HostForge\Modules\ServerManager\Admin;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class HF_Server_List_Table
 */
class HF_Server_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'server',
				'plural'   => 'servers',
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
			'cb'           => '<input type="checkbox" />',
			'name'         => __( 'Server Name', 'hostforge' ),
			'hostname'     => __( 'Hostname', 'hostforge' ),
			'panel_type'   => __( 'Panel', 'hostforge' ),
			'server_group' => __( 'Group', 'hostforge' ),
			'accounts'     => __( 'Accounts', 'hostforge' ),
			'status'       => __( 'Status', 'hostforge' ),
			'last_check'   => __( 'Last Check', 'hostforge' ),
		);

		/**
		 * Filter the server list table columns.
		 *
		 * Allows adding, removing, or reordering columns
		 * in the admin server list table.
		 *
		 * @since 1.0.0
		 *
		 * @param array $columns Associative array of column_slug => column_label.
		 */
		return apply_filters( 'hostforge_server_admin_columns', $columns );
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'name'       => array( 'title', false ),
			'hostname'   => array( 'hostname', false ),
			'panel_type' => array( 'panel_type', false ),
			'status'     => array( 'status', false ),
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'delete' => __( 'Delete', 'hostforge' ),
		);
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
			'post_type'      => 'hf_server',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$group_filter = isset( $_GET['server_group'] ) ? absint( $_GET['server_group'] ) : 0;
		if ( $group_filter > 0 ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'hf_server_group',
					'field'    => 'term_id',
					'terms'    => $group_filter,
				),
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status_filter = isset( $_GET['server_status'] ) ? sanitize_text_field( wp_unslash( $_GET['server_status'] ) ) : '';
		if ( ! empty( $status_filter ) ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_hf_status',
					'value' => $status_filter,
				),
			);
		}

		// Handle sorting.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'title';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';

		if ( in_array( $orderby, array( 'hostname', 'panel_type', 'status' ), true ) ) {
			$args['meta_key'] = '_hf_' . $orderby;
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
		return sprintf( '<input type="checkbox" name="server_ids[]" value="%d" />', $item->ID );
	}

	/**
	 * Name column with actions.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_name( $item ): string {
		$edit_url    = admin_url( 'admin.php?page=hostforge-servers&action=edit&server_id=' . $item->ID );
		$monitor_url = admin_url( 'admin.php?page=hostforge-servers&action=monitor&server_id=' . $item->ID );

		$actions = array(
			'edit'    => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				esc_html__( 'Edit', 'hostforge' )
			),
			'monitor' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $monitor_url ),
				esc_html__( 'Monitor', 'hostforge' )
			),
			'delete'  => sprintf(
				'<a href="#" class="hf-delete-server" data-server-id="%d">%s</a>',
				$item->ID,
				esc_html__( 'Delete', 'hostforge' )
			),
		);

		return sprintf(
			'<strong><a href="%s">%s</a></strong>%s',
			esc_url( $edit_url ),
			esc_html( $item->post_title ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Hostname column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_hostname( $item ): string {
		$hostname = get_post_meta( $item->ID, '_hf_hostname', true );
		$port     = get_post_meta( $item->ID, '_hf_port', true );
		return esc_html( $hostname ) . ( $port ? ':' . esc_html( $port ) : '' );
	}

	/**
	 * Panel type column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_panel_type( $item ): string {
		$type  = get_post_meta( $item->ID, '_hf_panel_type', true );
		$label = 'cpanel' === $type ? 'cPanel/WHM' : ( 'plesk' === $type ? 'Plesk' : ucfirst( $type ) );
		return esc_html( $label );
	}

	/**
	 * Server group column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_server_group( $item ): string {
		$terms = wp_get_object_terms( $item->ID, 'hf_server_group', array( 'fields' => 'names' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '<span class="hf-muted">' . esc_html__( 'None', 'hostforge' ) . '</span>';
		}
		return esc_html( implode( ', ', $terms ) );
	}

	/**
	 * Accounts column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_accounts( $item ): string {
		$current = (int) get_post_meta( $item->ID, '_hf_current_accounts', true );
		$max     = (int) get_post_meta( $item->ID, '_hf_max_accounts', true );

		if ( $max > 0 ) {
			$pct   = round( ( $current / $max ) * 100 );
			$class = $pct > 90 ? 'hf-progress--danger' : ( $pct > 70 ? 'hf-progress--warning' : 'hf-progress--ok' );
			return sprintf(
				'%d / %d <div class="hf-progress %s"><div class="hf-progress__bar" style="width:%d%%"></div></div>',
				$current,
				$max,
				esc_attr( $class ),
				$pct
			);
		}

		return sprintf( '%d', $current );
	}

	/**
	 * Status column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_status( $item ): string {
		$status = get_post_meta( $item->ID, '_hf_status', true ) ?: 'unknown';
		return hf_format_status_badge( $status );
	}

	/**
	 * Last check column.
	 *
	 * @param \WP_Post $item Post object.
	 * @return string
	 */
	public function column_last_check( $item ): string {
		$last_check = get_post_meta( $item->ID, '_hf_last_check', true );
		if ( empty( $last_check ) ) {
			return '<span class="hf-muted">' . esc_html__( 'Never', 'hostforge' ) . '</span>';
		}

		$timestamp = strtotime( $last_check );
		$diff      = human_time_diff( $timestamp, current_time( 'timestamp' ) );

		return sprintf(
			/* translators: %s: human time difference */
			esc_html__( '%s ago', 'hostforge' ),
			$diff
		);
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

		$groups = get_terms(
			array(
				'taxonomy'   => 'hf_server_group',
				'hide_empty' => false,
			)
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_group = isset( $_GET['server_group'] ) ? absint( $_GET['server_group'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_status = isset( $_GET['server_status'] ) ? sanitize_text_field( wp_unslash( $_GET['server_status'] ) ) : '';

		?>
		<div class="alignleft actions">
			<?php if ( ! is_wp_error( $groups ) && ! empty( $groups ) ) : ?>
			<select name="server_group">
				<option value=""><?php esc_html_e( 'All Groups', 'hostforge' ); ?></option>
				<?php foreach ( $groups as $group ) : ?>
				<option value="<?php echo esc_attr( $group->term_id ); ?>" <?php selected( $current_group, $group->term_id ); ?>>
					<?php echo esc_html( $group->name ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<?php endif; ?>

			<select name="server_status">
				<option value=""><?php esc_html_e( 'All Statuses', 'hostforge' ); ?></option>
				<option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Active', 'hostforge' ); ?></option>
				<option value="error" <?php selected( $current_status, 'error' ); ?>><?php esc_html_e( 'Error', 'hostforge' ); ?></option>
				<option value="unknown" <?php selected( $current_status, 'unknown' ); ?>><?php esc_html_e( 'Unknown', 'hostforge' ); ?></option>
			</select>

			<?php submit_button( __( 'Filter', 'hostforge' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Message when no servers found.
	 *
	 * @return void
	 */
	public function no_items(): void {
		esc_html_e( 'No servers found. Add your first server to get started.', 'hostforge' );
	}
}
