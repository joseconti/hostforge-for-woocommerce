<?php
/**
 * Audit log admin template.
 *
 * @package HostForge\Modules\Security
 */

defined( 'ABSPATH' ) || exit;

$audit = new \HostForge\Modules\Security\HF_Audit_Log( $this->module );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$page_num = ! empty( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$filter_type = ! empty( $_GET['object_type'] ) ? sanitize_text_field( wp_unslash( $_GET['object_type'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search = ! empty( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

$result = $audit->get_entries(
	array(
		'per_page'    => 30,
		'page'        => $page_num,
		'object_type' => $filter_type,
		'search'      => $search,
	)
);

$items = $result['items'];
$total = $result['total'];
$pages = ceil( $total / 30 );

$object_types = array(
	''        => __( 'All Types', 'hostforge' ),
	'user'    => __( 'User', 'hostforge' ),
	'module'  => __( 'Module', 'hostforge' ),
	'service' => __( 'Service', 'hostforge' ),
	'ticket'  => __( 'Ticket', 'hostforge' ),
	'domain'  => __( 'Domain', 'hostforge' ),
	'order'   => __( 'Order', 'hostforge' ),
	'setting' => __( 'Setting', 'hostforge' ),
);
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

	<div class="hf-audit-log-page">
		<div class="tablenav top">
			<form method="get" class="alignleft actions">
				<input type="hidden" name="page" value="hostforge-security" />
				<input type="hidden" name="tab" value="audit-log" />

				<select name="object_type">
					<?php foreach ( $object_types as $type_value => $type_label ) : ?>
						<option value="<?php echo esc_attr( $type_value ); ?>" <?php selected( $filter_type, $type_value ); ?>>
							<?php echo esc_html( $type_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
					placeholder="<?php esc_attr_e( 'Search...', 'hostforge' ); ?>" />

				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'hostforge' ); ?></button>
			</form>

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
			<p><?php esc_html_e( 'No audit log entries.', 'hostforge' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th class="column-date"><?php esc_html_e( 'Date', 'hostforge' ); ?></th>
						<th class="column-user"><?php esc_html_e( 'User', 'hostforge' ); ?></th>
						<th class="column-action"><?php esc_html_e( 'Action', 'hostforge' ); ?></th>
						<th class="column-type"><?php esc_html_e( 'Type', 'hostforge' ); ?></th>
						<th class="column-details"><?php esc_html_e( 'Details', 'hostforge' ); ?></th>
						<th class="column-ip"><?php esc_html_e( 'IP Address', 'hostforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $entry ) : ?>
						<?php
						$user_display = '—';
						if ( ! empty( $entry->user_id ) ) {
							$user         = get_userdata( (int) $entry->user_id );
							$user_display = $user ? $user->display_name : sprintf( '#%d', $entry->user_id );
						}
						?>
						<tr>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry->created_at ) ) ); ?></td>
							<td><?php echo esc_html( $user_display ); ?></td>
							<td><code><?php echo esc_html( $entry->action ); ?></code></td>
							<td>
								<span class="hf-badge"><?php echo esc_html( $entry->object_type ); ?></span>
							</td>
							<td><?php echo esc_html( $entry->details ); ?></td>
							<td>
								<?php if ( ! empty( $entry->ip_address ) ) : ?>
									<code><?php echo esc_html( $entry->ip_address ); ?></code>
								<?php else : ?>
									—
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						$base_url = admin_url( 'admin.php?page=hostforge-security&tab=audit-log' );
						if ( ! empty( $filter_type ) ) {
							$base_url .= '&object_type=' . $filter_type;
						}
						if ( ! empty( $search ) ) {
							$base_url .= '&s=' . rawurlencode( $search );
						}

						echo wp_kses_post(
							paginate_links(
								array(
									'base'    => add_query_arg( 'paged', '%#%', $base_url ),
									'format'  => '',
									'current' => $page_num,
									'total'   => $pages,
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
