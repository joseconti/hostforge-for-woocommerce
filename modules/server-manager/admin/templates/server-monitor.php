<?php
/**
 * Server Monitor Template.
 *
 * @package HostForge\Modules\ServerManager\Admin
 * @var \WP_Post $server Server post object.
 * @var array    $meta   Server meta values.
 * @var array    $stats  Server statistics (or null).
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap hf-wrap">
	<h1 class="wp-heading-inline">
		<?php
		printf(
			/* translators: %s: server name */
			esc_html__( 'Monitor: %s', 'hostforge' ),
			esc_html( $server->post_title )
		);
		?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-servers' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Back to Servers', 'hostforge' ); ?>
	</a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-servers&action=edit&server_id=' . $server->ID ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Edit Server', 'hostforge' ); ?>
	</a>
	<hr class="wp-header-end">

	<div class="hf-form-grid">
		<!-- Server Info -->
		<div class="hf-form-col">
			<div class="hf-card">
				<h2 class="hf-card__title"><?php esc_html_e( 'Server Information', 'hostforge' ); ?></h2>
				<table class="hf-info-table">
					<tr>
						<td><?php esc_html_e( 'Hostname', 'hostforge' ); ?></td>
						<td><?php echo esc_html( $meta['_hf_hostname'] ?? '' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Panel Type', 'hostforge' ); ?></td>
						<td>
							<?php
							$hf_type = $meta['_hf_panel_type'] ?? '';
							echo esc_html( 'cpanel' === $hf_type ? 'cPanel/WHM' : ( 'plesk' === $hf_type ? 'Plesk' : ucfirst( $hf_type ) ) );
							?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Port', 'hostforge' ); ?></td>
						<td><?php echo esc_html( $meta['_hf_port'] ?? '' ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Status', 'hostforge' ); ?></td>
						<td><?php echo wp_kses_post( hf_format_status_badge( $meta['_hf_status'] ?? 'unknown' ) ); ?></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Accounts', 'hostforge' ); ?></td>
						<td>
							<?php
							$current = (int) ( $meta['_hf_current_accounts'] ?? 0 );
							$max     = (int) ( $meta['_hf_max_accounts'] ?? 0 );
							echo esc_html( $current );
							if ( $max > 0 ) {
								echo ' / ' . esc_html( $max );
							}
							?>
						</td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Last Check', 'hostforge' ); ?></td>
						<td>
							<?php
							$last_check = $meta['_hf_last_check'] ?? '';
							if ( $last_check ) {
								echo esc_html( $last_check );
							} else {
								esc_html_e( 'Never', 'hostforge' );
							}
							?>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Server Stats -->
		<div class="hf-form-col">
			<div class="hf-card">
				<h2 class="hf-card__title"><?php esc_html_e( 'Server Statistics', 'hostforge' ); ?></h2>

				<?php if ( ! empty( $stats ) && is_array( $stats ) ) : ?>
				<table class="hf-info-table">
					<?php if ( ! empty( $stats['hostname'] ) ) : ?>
					<tr>
						<td><?php esc_html_e( 'Hostname', 'hostforge' ); ?></td>
						<td><?php echo esc_html( $stats['hostname'] ); ?></td>
					</tr>
					<?php endif; ?>
					<?php if ( ! empty( $stats['version'] ) ) : ?>
					<tr>
						<td><?php esc_html_e( 'Version', 'hostforge' ); ?></td>
						<td><?php echo esc_html( $stats['version'] ); ?></td>
					</tr>
					<?php endif; ?>
					<?php if ( ! empty( $stats['os'] ) ) : ?>
					<tr>
						<td><?php esc_html_e( 'OS', 'hostforge' ); ?></td>
						<td><?php echo esc_html( $stats['os'] ); ?></td>
					</tr>
					<?php endif; ?>
					<?php if ( ! empty( $stats['load_1'] ) ) : ?>
					<tr>
						<td><?php esc_html_e( 'Load Average', 'hostforge' ); ?></td>
						<td>
							<?php
							echo esc_html(
								sprintf(
									'%s / %s / %s',
									$stats['load_1'],
									$stats['load_5'] ?? '-',
									$stats['load_15'] ?? '-'
								)
							);
							?>
						</td>
					</tr>
					<?php endif; ?>
				</table>
				<?php else : ?>
				<p class="hf-muted"><?php esc_html_e( 'No statistics available. Run a health check first.', 'hostforge' ); ?></p>
				<?php endif; ?>
			</div>

			<?php
			$packages = $meta['_hf_packages_cache'] ?? array();
			if ( ! empty( $packages ) && is_array( $packages ) ) :
				?>
			<div class="hf-card">
				<h2 class="hf-card__title"><?php esc_html_e( 'Available Packages', 'hostforge' ); ?></h2>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Package Name', 'hostforge' ); ?></th>
							<?php if ( isset( $packages[0]['disk'] ) ) : ?>
							<th><?php esc_html_e( 'Disk', 'hostforge' ); ?></th>
							<th><?php esc_html_e( 'Bandwidth', 'hostforge' ); ?></th>
							<?php endif; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $packages as $pkg ) : ?>
						<tr>
							<td><?php echo esc_html( $pkg['name'] ?? '' ); ?></td>
							<?php if ( isset( $pkg['disk'] ) ) : ?>
							<td><?php echo esc_html( $pkg['disk'] ); ?> MB</td>
							<td><?php echo esc_html( $pkg['bandwidth'] ); ?> MB</td>
							<?php endif; ?>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>
		</div>
	</div>
</div>
