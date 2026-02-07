<?php
/**
 * IP Blocks admin template.
 *
 * @package HostForge\Modules\Security
 */

defined( 'ABSPATH' ) || exit;

$ip_manager = new \HostForge\Modules\Security\HF_IP_Manager( $this->module );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$page_num = ! empty( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$result   = $ip_manager->get_blocked_ips( 20, $page_num );
$items    = $result['items'];
$total    = $result['total'];
$pages    = ceil( $total / 20 );
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

	<div class="hf-ip-blocks-page">
		<div class="hf-add-block-form">
			<h2><?php esc_html_e( 'Block an IP Address', 'hostforge' ); ?></h2>
			<form id="hf-block-ip-form" class="hf-inline-form">
				<input type="text" id="hf-block-ip-address" name="ip_address"
					placeholder="<?php esc_attr_e( 'IP address', 'hostforge' ); ?>"
					class="regular-text" required />
				<input type="text" id="hf-block-ip-reason" name="reason"
					placeholder="<?php esc_attr_e( 'Reason (optional)', 'hostforge' ); ?>"
					class="regular-text" />
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Block IP', 'hostforge' ); ?>
				</button>
				<span class="spinner" id="hf-block-ip-spinner"></span>
			</form>
		</div>

		<div id="hf-block-ip-notice" class="notice" style="display: none;"></div>

		<h2><?php esc_html_e( 'Blocked IPs', 'hostforge' ); ?></h2>

		<?php if ( empty( $items ) ) : ?>
			<p><?php esc_html_e( 'No blocked IPs.', 'hostforge' ); ?></p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'IP Address', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Type', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Expires', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Blocked At', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'hostforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $block ) : ?>
						<tr data-ip="<?php echo esc_attr( $block->ip_address ); ?>">
							<td><code><?php echo esc_html( $block->ip_address ); ?></code></td>
							<td>
								<?php if ( 'auto' === $block->block_type ) : ?>
									<span class="hf-badge hf-badge-warning"><?php esc_html_e( 'Auto', 'hostforge' ); ?></span>
								<?php else : ?>
									<span class="hf-badge hf-badge-info"><?php esc_html_e( 'Manual', 'hostforge' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( ! empty( $block->reason ) ? $block->reason : '—' ); ?></td>
							<td>
								<?php
								if ( empty( $block->expires_at ) ) {
									esc_html_e( 'Never (permanent)', 'hostforge' );
								} else {
									echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $block->expires_at ) ) );
								}
								?>
							</td>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $block->created_at ) ) ); ?></td>
							<td>
								<button type="button" class="button button-small hf-unblock-ip"
									data-ip="<?php echo esc_attr( $block->ip_address ); ?>">
									<?php esc_html_e( 'Unblock', 'hostforge' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'    => add_query_arg( 'paged', '%#%' ),
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
