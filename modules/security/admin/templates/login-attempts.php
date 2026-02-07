<?php
/**
 * Login attempts admin template.
 *
 * @package HostForge\Modules\Security
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;

$table = $wpdb->prefix . 'hf_login_attempts';

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$page_num = ! empty( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filter_status = ! empty( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
$hf_per_page   = 30;
$offset        = ( $page_num - 1 ) * $hf_per_page;

$where  = '1=1';
$values = array();

if ( ! empty( $filter_status ) && in_array( $filter_status, array( 'success', 'failed' ), true ) ) {
	$where   .= ' AND status = %s';
	$values[] = $filter_status;
}

if ( ! empty( $values ) ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$total        = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $values ) );
	$query_values = array_merge( $values, array( $hf_per_page, $offset ) );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $query_values ) );
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
	$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $hf_per_page, $offset ) );
}

$hf_pages = ceil( $total / $hf_per_page );
?>
<div class="wrap hf-wrap">
	<h1><?php esc_html_e( 'Security', 'hostforge' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_id => $tab_label ) : ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-security&tab=' . $tab_id ) ); ?>"
				class="nav-tab <?php echo $tab === $tab_id ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="hf-login-attempts-page">
		<div class="tablenav top">
			<div class="alignleft actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-security&tab=login-attempts' ) ); ?>"
					class="button <?php echo empty( $filter_status ) ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'All', 'hostforge' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-security&tab=login-attempts&status=failed' ) ); ?>"
					class="button <?php echo 'failed' === $filter_status ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'Failed', 'hostforge' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-security&tab=login-attempts&status=success' ) ); ?>"
					class="button <?php echo 'success' === $filter_status ? 'button-primary' : ''; ?>">
					<?php esc_html_e( 'Successful', 'hostforge' ); ?>
				</a>
			</div>
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					printf(
						/* translators: %s: number of items */
						esc_html( _n( '%s item', '%s items', $total, 'hostforge' ) ),
						esc_html( number_format_i18n( $total ) )
					);
					?>
				</span>
			</div>
		</div>

		<?php if ( empty( $items ) ) : ?>
			<p><?php esc_html_e( 'No login attempts recorded.', 'hostforge' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'IP Address', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Username', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Status', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Date', 'hostforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $attempt ) : ?>
						<tr>
							<td><code><?php echo esc_html( $attempt->ip_address ); ?></code></td>
							<td><?php echo esc_html( ! empty( $attempt->username ) ? $attempt->username : '—' ); ?></td>
							<td>
								<?php if ( 'success' === $attempt->status ) : ?>
									<span class="hf-badge hf-badge-success"><?php esc_html_e( 'Success', 'hostforge' ); ?></span>
								<?php else : ?>
									<span class="hf-badge hf-badge-danger"><?php esc_html_e( 'Failed', 'hostforge' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $attempt->created_at ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $hf_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						$base_url = admin_url( 'admin.php?page=hostforge-security&tab=login-attempts' );
						if ( ! empty( $filter_status ) ) {
							$base_url .= '&status=' . $filter_status;
						}

						echo wp_kses_post(
							paginate_links(
								array(
									'base'    => add_query_arg( 'paged', '%#%', $base_url ),
									'format'  => '',
									'current' => $page_num,
									'total'   => $hf_pages,
								)
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
