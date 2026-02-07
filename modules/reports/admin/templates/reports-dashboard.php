<?php
/**
 * Reports dashboard admin template.
 *
 * @package HostForge\Modules\Reports
 */

defined( 'ABSPATH' ) || exit;

$export_nonce  = wp_create_nonce( 'hf_csv_export' );
$data_provider = new \HostForge\Modules\Reports\HF_Report_Data();

$mrr             = $data_provider->get_mrr();
$services_status = $data_provider->get_services_by_status();
$ticket_metrics  = $data_provider->get_ticket_metrics();
$domain_stats    = $data_provider->get_domain_stats();
$server_capacity = $data_provider->get_server_capacity();
?>
<div class="wrap hf-wrap hf-reports-page">
	<h1><?php esc_html_e( 'Reports', 'hostforge' ); ?></h1>

	<!-- Summary Cards -->
	<div class="hf-report-summary-cards">
		<div class="hf-summary-card">
			<span class="hf-summary-label"><?php esc_html_e( 'Est. MRR', 'hostforge' ); ?></span>
			<span class="hf-summary-value"><?php echo wp_kses_post( wc_price( $mrr ) ); ?></span>
		</div>
		<div class="hf-summary-card">
			<span class="hf-summary-label"><?php esc_html_e( 'Active Services', 'hostforge' ); ?></span>
			<span class="hf-summary-value"><?php echo esc_html( number_format_i18n( ! empty( $services_status['active'] ) ? $services_status['active'] : 0 ) ); ?></span>
		</div>
		<div class="hf-summary-card">
			<span class="hf-summary-label"><?php esc_html_e( 'Open Tickets', 'hostforge' ); ?></span>
			<span class="hf-summary-value"><?php echo esc_html( number_format_i18n( ! empty( $ticket_metrics['total_open'] ) ? $ticket_metrics['total_open'] : 0 ) ); ?></span>
		</div>
		<div class="hf-summary-card">
			<span class="hf-summary-label"><?php esc_html_e( 'Active Domains', 'hostforge' ); ?></span>
			<span class="hf-summary-value"><?php echo esc_html( number_format_i18n( ! empty( $domain_stats['active'] ) ? $domain_stats['active'] : 0 ) ); ?></span>
		</div>
	</div>

	<!-- Charts Row -->
	<div class="hf-report-charts-row">
		<div class="hf-chart-card">
			<div class="hf-chart-header">
				<h2><?php esc_html_e( 'Revenue (12 months)', 'hostforge' ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-reports&hf_export=revenue&_wpnonce=' . $export_nonce ) ); ?>" class="button button-small">
					<?php esc_html_e( 'Export CSV', 'hostforge' ); ?>
				</a>
			</div>
			<div class="hf-chart-container">
				<canvas id="hf-revenue-chart"></canvas>
			</div>
		</div>

		<div class="hf-chart-card">
			<div class="hf-chart-header">
				<h2><?php esc_html_e( 'Customer Growth', 'hostforge' ); ?></h2>
			</div>
			<div class="hf-chart-container">
				<canvas id="hf-customers-chart"></canvas>
			</div>
		</div>
	</div>

	<!-- Status Charts -->
	<div class="hf-report-charts-row">
		<div class="hf-chart-card hf-chart-card-small">
			<div class="hf-chart-header">
				<h2><?php esc_html_e( 'Services by Status', 'hostforge' ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-reports&hf_export=services&_wpnonce=' . $export_nonce ) ); ?>" class="button button-small">
					<?php esc_html_e( 'Export CSV', 'hostforge' ); ?>
				</a>
			</div>
			<div class="hf-chart-container hf-chart-doughnut">
				<canvas id="hf-services-chart"></canvas>
			</div>
		</div>

		<div class="hf-chart-card hf-chart-card-small">
			<div class="hf-chart-header">
				<h2><?php esc_html_e( 'Support Tickets', 'hostforge' ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-reports&hf_export=tickets&_wpnonce=' . $export_nonce ) ); ?>" class="button button-small">
					<?php esc_html_e( 'Export CSV', 'hostforge' ); ?>
				</a>
			</div>
			<div class="hf-chart-container hf-chart-doughnut">
				<canvas id="hf-tickets-chart"></canvas>
			</div>
			<?php if ( ! empty( $ticket_metrics['avg_resolution'] ) ) : ?>
				<p class="hf-chart-note">
					<?php
					printf(
						/* translators: %s: average hours */
						esc_html__( 'Avg. resolution time: %s hours', 'hostforge' ),
						esc_html( $ticket_metrics['avg_resolution'] )
					);
					?>
				</p>
			<?php endif; ?>
		</div>

		<div class="hf-chart-card hf-chart-card-small">
			<div class="hf-chart-header">
				<h2><?php esc_html_e( 'Domains', 'hostforge' ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-reports&hf_export=domains&_wpnonce=' . $export_nonce ) ); ?>" class="button button-small">
					<?php esc_html_e( 'Export CSV', 'hostforge' ); ?>
				</a>
			</div>
			<div class="hf-chart-container hf-chart-doughnut">
				<canvas id="hf-domains-chart"></canvas>
			</div>
		</div>
	</div>

	<!-- Server Capacity -->
	<?php if ( ! empty( $server_capacity ) ) : ?>
		<div class="hf-chart-card">
			<div class="hf-chart-header">
				<h2><?php esc_html_e( 'Server Capacity', 'hostforge' ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=hostforge-reports&hf_export=servers&_wpnonce=' . $export_nonce ) ); ?>" class="button button-small">
					<?php esc_html_e( 'Export CSV', 'hostforge' ); ?>
				</a>
			</div>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Server', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Accounts', 'hostforge' ); ?></th>
						<th><?php esc_html_e( 'Capacity', 'hostforge' ); ?></th>
						<th style="width:40%;"><?php esc_html_e( 'Usage', 'hostforge' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $server_capacity as $server ) : ?>
						<?php
						$bar_class = 'hf-bar-success';
						if ( $server['usage'] > 80 ) {
							$bar_class = 'hf-bar-danger';
						} elseif ( $server['usage'] > 60 ) {
							$bar_class = 'hf-bar-warning';
						}
						?>
						<tr>
							<td><strong><?php echo esc_html( $server['name'] ); ?></strong></td>
							<td><?php echo esc_html( $server['current'] . ' / ' . $server['max'] ); ?></td>
							<td><?php echo esc_html( $server['usage'] . '%' ); ?></td>
							<td>
								<div class="hf-progress-bar">
									<div class="hf-progress-fill <?php echo esc_attr( $bar_class ); ?>" style="width:<?php echo esc_attr( min( $server['usage'], 100 ) ); ?>%;"></div>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>
