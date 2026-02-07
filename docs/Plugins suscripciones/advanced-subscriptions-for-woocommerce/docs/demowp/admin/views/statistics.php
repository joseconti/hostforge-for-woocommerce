<?php
/**
 * Statistics Page View
 *
 * @package DemoWP
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get date range from request or default to last 30 days.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View-only filters, no data modification.
$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : current_time( 'Y-m-d' );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View-only filters, no data modification.
$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View-only filters, no data modification.
$period     = isset( $_GET['period'] ) ? sanitize_text_field( wp_unslash( $_GET['period'] ) ) : '30days';

// Validate dates.
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
	$start_date = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
}
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
	$end_date = current_time( 'Y-m-d' );
}

// Get advanced statistics.
$advanced_stats = $tracker->get_advanced_statistics( $start_date, $end_date );
$basic_stats    = $tracker->get_statistics();

// Prepare chart data.
$chart_labels = array();
$chart_data   = array();

// Fill in missing dates with 0.
$current = new DateTime( $start_date );
$end     = new DateTime( $end_date );

while ( $current <= $end ) {
	$date_str       = $current->format( 'Y-m-d' );
	$chart_labels[] = $current->format( 'M j' );
	$chart_data[]   = isset( $advanced_stats['by_day'][ $date_str ] ) ? $advanced_stats['by_day'][ $date_str ] : 0;
	$current->modify( '+1 day' );
}

// Prepare hourly chart data.
$hourly_labels = array();
$hourly_data   = $advanced_stats['by_hour'];
for ( $i = 0; $i < 24; $i++ ) {
	$hourly_labels[] = sprintf( '%02d:00', $i );
}

// Day of week chart data.
$dow_labels = array_keys( $advanced_stats['by_day_of_week'] );
$dow_data   = array_values( $advanced_stats['by_day_of_week'] );

// Status distribution for pie chart.
$status_labels = array();
$status_data   = array();
$status_colors = array(
	'active'   => '#4CAF50',
	'expired'  => '#FF9800',
	'deleted'  => '#f44336',
	'cleaning' => '#9E9E9E',
);

foreach ( $advanced_stats['status_distribution'] as $demo_status => $count ) {
	$status_labels[] = ucfirst( $demo_status );
	$status_data[]   = $count;
}
?>
<div class="wrap demowp-admin-wrap demowp-statistics-page">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-chart-bar"></span>
		<?php esc_html_e( 'Statistics', 'demowp' ); ?>
	</h1>

	<hr class="wp-header-end">

	<!-- Date Range Filter -->
	<div class="demowp-filters-bar">
		<form method="get" class="demowp-date-filter">
			<input type="hidden" name="page" value="demowp-stats">

			<div class="demowp-filter-group">
				<label><?php esc_html_e( 'Quick Periods:', 'demowp' ); ?></label>
				<div class="demowp-period-buttons">
					<button type="submit" name="period" value="today" class="button <?php echo 'today' === $period ? 'button-primary' : ''; ?>">
						<?php esc_html_e( 'Today', 'demowp' ); ?>
					</button>
					<button type="submit" name="period" value="7days" class="button <?php echo '7days' === $period ? 'button-primary' : ''; ?>">
						<?php esc_html_e( '7 Days', 'demowp' ); ?>
					</button>
					<button type="submit" name="period" value="30days" class="button <?php echo '30days' === $period ? 'button-primary' : ''; ?>">
						<?php esc_html_e( '30 Days', 'demowp' ); ?>
					</button>
					<button type="submit" name="period" value="90days" class="button <?php echo '90days' === $period ? 'button-primary' : ''; ?>">
						<?php esc_html_e( '90 Days', 'demowp' ); ?>
					</button>
					<button type="submit" name="period" value="year" class="button <?php echo 'year' === $period ? 'button-primary' : ''; ?>">
						<?php esc_html_e( 'This Year', 'demowp' ); ?>
					</button>
				</div>
			</div>

			<div class="demowp-filter-group demowp-custom-dates">
				<label for="start_date"><?php esc_html_e( 'Custom Range:', 'demowp' ); ?></label>
				<input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>">
				<span class="demowp-date-separator"><?php esc_html_e( 'to', 'demowp' ); ?></span>
				<input type="date" id="end_date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>">
				<button type="submit" name="period" value="custom" class="button">
					<?php esc_html_e( 'Apply', 'demowp' ); ?>
				</button>
			</div>

			<div class="demowp-filter-group demowp-export-group">
				<button type="button" id="demowp-export-csv" class="button">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export CSV', 'demowp' ); ?>
				</button>
			</div>
		</form>
	</div>

	<div class="demowp-admin-content">
		<!-- Summary Cards -->
		<div class="demowp-stats-grid demowp-stats-grid-6">
			<div class="demowp-stat-card demowp-stat-card-main">
				<div class="demowp-stat-icon demowp-stat-icon-blue">
					<span class="dashicons dashicons-admin-site-alt3"></span>
				</div>
				<div class="demowp-stat-content">
					<span class="demowp-stat-number"><?php echo esc_html( number_format( $advanced_stats['total_demos'] ) ); ?></span>
					<span class="demowp-stat-label"><?php esc_html_e( 'Total Demos', 'demowp' ); ?></span>
					<?php if ( 0 !== $advanced_stats['demos_change'] ) : ?>
						<span class="demowp-stat-change <?php echo $advanced_stats['demos_change'] >= 0 ? 'positive' : 'negative'; ?>">
							<?php echo $advanced_stats['demos_change'] >= 0 ? '+' : ''; ?><?php echo esc_html( $advanced_stats['demos_change'] ); ?>%
							<span class="demowp-stat-compare"><?php esc_html_e( 'vs previous period', 'demowp' ); ?></span>
						</span>
					<?php endif; ?>
				</div>
			</div>

			<div class="demowp-stat-card">
				<div class="demowp-stat-icon demowp-stat-icon-green">
					<span class="dashicons dashicons-yes-alt"></span>
				</div>
				<div class="demowp-stat-content">
					<span class="demowp-stat-number"><?php echo esc_html( number_format( $basic_stats['active'] ) ); ?></span>
					<span class="demowp-stat-label"><?php esc_html_e( 'Currently Active', 'demowp' ); ?></span>
				</div>
			</div>

			<div class="demowp-stat-card">
				<div class="demowp-stat-icon demowp-stat-icon-purple">
					<span class="dashicons dashicons-groups"></span>
				</div>
				<div class="demowp-stat-content">
					<span class="demowp-stat-number"><?php echo esc_html( number_format( $advanced_stats['unique_ips'] ) ); ?></span>
					<span class="demowp-stat-label"><?php esc_html_e( 'Unique Visitors', 'demowp' ); ?></span>
					<?php if ( 0 !== $advanced_stats['ips_change'] ) : ?>
						<span class="demowp-stat-change <?php echo $advanced_stats['ips_change'] >= 0 ? 'positive' : 'negative'; ?>">
							<?php echo $advanced_stats['ips_change'] >= 0 ? '+' : ''; ?><?php echo esc_html( $advanced_stats['ips_change'] ); ?>%
						</span>
					<?php endif; ?>
				</div>
			</div>

			<div class="demowp-stat-card">
				<div class="demowp-stat-icon demowp-stat-icon-orange">
					<span class="dashicons dashicons-performance"></span>
				</div>
				<div class="demowp-stat-content">
					<span class="demowp-stat-number"><?php echo esc_html( $advanced_stats['average_per_day'] ); ?></span>
					<span class="demowp-stat-label"><?php esc_html_e( 'Avg. per Day', 'demowp' ); ?></span>
				</div>
			</div>

			<div class="demowp-stat-card">
				<div class="demowp-stat-icon demowp-stat-icon-teal">
					<span class="dashicons dashicons-calendar-alt"></span>
				</div>
				<div class="demowp-stat-content">
					<span class="demowp-stat-number"><?php echo esc_html( number_format( $basic_stats['created_today'] ) ); ?></span>
					<span class="demowp-stat-label"><?php esc_html_e( 'Today', 'demowp' ); ?></span>
				</div>
			</div>

			<div class="demowp-stat-card">
				<div class="demowp-stat-icon demowp-stat-icon-red">
					<span class="dashicons dashicons-chart-line"></span>
				</div>
				<div class="demowp-stat-content">
					<span class="demowp-stat-number"><?php echo esc_html( number_format( $basic_stats['created_this_week'] ) ); ?></span>
					<span class="demowp-stat-label"><?php esc_html_e( 'This Week', 'demowp' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Charts Row -->
		<div class="demowp-charts-row">
			<!-- Main Chart - Demos by Day -->
			<div class="demowp-card demowp-chart-card demowp-chart-main">
				<h2>
					<span class="dashicons dashicons-chart-area"></span>
					<?php esc_html_e( 'Demos Over Time', 'demowp' ); ?>
				</h2>
				<div class="demowp-chart-container">
					<canvas id="demowp-demos-chart"></canvas>
				</div>
			</div>

			<!-- Hourly Distribution -->
			<div class="demowp-card demowp-chart-card demowp-chart-secondary">
				<h2>
					<span class="dashicons dashicons-clock"></span>
					<?php esc_html_e( 'Hourly Distribution', 'demowp' ); ?>
				</h2>
				<div class="demowp-chart-container">
					<canvas id="demowp-hourly-chart"></canvas>
				</div>
			</div>
		</div>

		<!-- Second Charts Row -->
		<div class="demowp-charts-row demowp-charts-row-3">
			<!-- Day of Week -->
			<div class="demowp-card demowp-chart-card">
				<h2>
					<span class="dashicons dashicons-calendar"></span>
					<?php esc_html_e( 'By Day of Week', 'demowp' ); ?>
				</h2>
				<div class="demowp-chart-container">
					<canvas id="demowp-dow-chart"></canvas>
				</div>
			</div>

			<!-- Status Distribution -->
			<div class="demowp-card demowp-chart-card">
				<h2>
					<span class="dashicons dashicons-chart-pie"></span>
					<?php esc_html_e( 'Status Distribution', 'demowp' ); ?>
				</h2>
				<div class="demowp-chart-container demowp-chart-pie-container">
					<canvas id="demowp-status-chart"></canvas>
				</div>
			</div>

			<!-- Top IPs -->
			<div class="demowp-card demowp-top-ips-card">
				<h2>
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'Top IP Addresses', 'demowp' ); ?>
				</h2>
				<div class="demowp-top-ips-list">
					<?php if ( ! empty( $advanced_stats['top_ips'] ) ) : ?>
						<table class="demowp-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'IP Address', 'demowp' ); ?></th>
									<th><?php esc_html_e( 'Demos', 'demowp' ); ?></th>
									<th><?php esc_html_e( 'Last Demo', 'demowp' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $advanced_stats['top_ips'] as $ip_data ) : ?>
									<tr>
										<td>
											<code><?php echo esc_html( $ip_data['ip_address'] ); ?></code>
										</td>
										<td>
											<span class="demowp-badge demowp-badge-count">
												<?php echo esc_html( $ip_data['count'] ); ?>
											</span>
										</td>
										<td>
											<?php echo esc_html( human_time_diff( strtotime( $ip_data['last_demo'] ), time() ) ); ?>
											<?php esc_html_e( 'ago', 'demowp' ); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p class="demowp-no-data"><?php esc_html_e( 'No data available for this period.', 'demowp' ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<!-- Current Configuration -->
		<div class="demowp-card demowp-info-section">
			<h2>
				<span class="dashicons dashicons-admin-settings"></span>
				<?php esc_html_e( 'Current Configuration', 'demowp' ); ?>
			</h2>
			<table class="demowp-config-table">
				<tr>
					<th><?php esc_html_e( 'Demo Endpoint', 'demowp' ); ?></th>
					<td>
						<a href="<?php echo esc_url( DemoWP_Public::get_endpoint_url() ); ?>" target="_blank">
							<?php echo esc_url( DemoWP_Public::get_endpoint_url() ); ?>
						</a>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Demo Lifetime', 'demowp' ); ?></th>
					<td>
						<?php
						$lifetime = (int) get_option( 'demowp_demo_lifetime', 3600 );
						echo esc_html( human_time_diff( 0, $lifetime ) );
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Max Demos per IP', 'demowp' ); ?></th>
					<td><?php echo esc_html( get_option( 'demowp_max_concurrent_demos', 3 ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Action Scheduler', 'demowp' ); ?></th>
					<td>
						<?php if ( DemoWP_Utils::has_action_scheduler() ) : ?>
							<span class="demowp-badge demowp-badge-active">
								<?php esc_html_e( 'Active', 'demowp' ); ?>
							</span>
						<?php else : ?>
							<span class="demowp-badge demowp-badge-expired">
								<?php esc_html_e( 'Not Available', 'demowp' ); ?>
							</span>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>

<!-- Chart.js Data -->
<script>
var demowpChartData = {
	daily: {
		labels: <?php echo wp_json_encode( $chart_labels ); ?>,
		data: <?php echo wp_json_encode( $chart_data ); ?>
	},
	hourly: {
		labels: <?php echo wp_json_encode( $hourly_labels ); ?>,
		data: <?php echo wp_json_encode( array_values( $hourly_data ) ); ?>
	},
	dayOfWeek: {
		labels: <?php echo wp_json_encode( $dow_labels ); ?>,
		data: <?php echo wp_json_encode( $dow_data ); ?>
	},
	status: {
		labels: <?php echo wp_json_encode( $status_labels ); ?>,
		data: <?php echo wp_json_encode( $status_data ); ?>,
		colors: <?php echo wp_json_encode( array_values( $status_colors ) ); ?>
	},
	export: {
		startDate: <?php echo wp_json_encode( $start_date ); ?>,
		endDate: <?php echo wp_json_encode( $end_date ); ?>
	}
};
</script>
