<?php
/**
 * Admin Dashboard template.
 *
 * @package HostForge
 */

defined( 'ABSPATH' ) || exit;

$module_manager = \HostForge\HostForge::instance()->module_manager();
$active_modules = $module_manager->get_active_module_ids();
?>
<div class="wrap hostforge-wrap">
	<h1><?php esc_html_e( 'HostForge Dashboard', 'hostforge' ); ?></h1>

	<div class="hostforge-dashboard-widgets">
		<!-- System Overview Widget -->
		<div class="hostforge-widget">
			<h2><?php esc_html_e( 'System Overview', 'hostforge' ); ?></h2>
			<div class="hostforge-widget-content">
				<table class="widefat striped">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Plugin Version', 'hostforge' ); ?></strong></td>
							<td><?php echo esc_html( HOSTFORGE_VERSION ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'PHP Version', 'hostforge' ); ?></strong></td>
							<td><?php echo esc_html( PHP_VERSION ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'WooCommerce Version', 'hostforge' ); ?></strong></td>
							<td><?php echo esc_html( defined( 'WC_VERSION' ) ? WC_VERSION : 'N/A' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Active Modules', 'hostforge' ); ?></strong></td>
							<td><?php echo esc_html( (string) count( $active_modules ) ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Debug Mode', 'hostforge' ); ?></strong></td>
							<td>
								<?php
								if ( 'yes' === get_option( 'hf_debug_mode', 'no' ) ) {
									echo '<span class="hf-badge hf-badge--warning">' . esc_html__( 'Enabled', 'hostforge' ) . '</span>';
								} else {
									echo '<span class="hf-badge hf-badge--success">' . esc_html__( 'Disabled', 'hostforge' ) . '</span>';
								}
								?>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- Quick Actions Widget -->
		<div class="hostforge-widget">
			<h2><?php esc_html_e( 'Quick Actions', 'hostforge' ); ?></h2>
			<div class="hostforge-widget-content">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-settings' ) ); ?>" class="button">
					<?php esc_html_e( 'Settings', 'hostforge' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-modules' ) ); ?>" class="button">
					<?php esc_html_e( 'Manage Modules', 'hostforge' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-logs' ) ); ?>" class="button">
					<?php esc_html_e( 'View Logs', 'hostforge' ); ?>
				</a>
			</div>
		</div>

		<?php
		/**
		 * Fires after the default dashboard widgets.
		 * Modules can add their own widgets here.
		 */
		do_action( 'hostforge_dashboard_widgets' );
		?>
	</div>
</div>
