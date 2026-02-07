/**
 * DemoWP Statistics Charts
 *
 * Uses Chart.js for rendering statistical charts.
 */

(function($) {
	'use strict';

	var DemoWPStatistics = {
		charts: {},

		/**
		 * Initialize charts
		 */
		init: function() {
			if (typeof Chart === 'undefined' || typeof demowpChartData === 'undefined') {
				return;
			}

			this.initDailyChart();
			this.initHourlyChart();
			this.initDayOfWeekChart();
			this.initStatusChart();
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			$('#demowp-export-csv').on('click', this.handleExportCSV);
		},

		/**
		 * Default chart options
		 */
		getDefaultOptions: function(type) {
			var options = {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: {
						display: false
					}
				}
			};

			if (type === 'line' || type === 'bar') {
				options.scales = {
					y: {
						beginAtZero: true,
						ticks: {
							precision: 0
						}
					}
				};
			}

			return options;
		},

		/**
		 * Initialize daily demos chart
		 */
		initDailyChart: function() {
			var ctx = document.getElementById('demowp-demos-chart');
			if (!ctx) return;

			var gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
			gradient.addColorStop(0, 'rgba(37, 99, 235, 0.3)');
			gradient.addColorStop(1, 'rgba(37, 99, 235, 0.01)');

			this.charts.daily = new Chart(ctx, {
				type: 'line',
				data: {
					labels: demowpChartData.daily.labels,
					datasets: [{
						label: 'Demos Created',
						data: demowpChartData.daily.data,
						borderColor: '#2563eb',
						backgroundColor: gradient,
						borderWidth: 2,
						fill: true,
						tension: 0.4,
						pointBackgroundColor: '#2563eb',
						pointBorderColor: '#fff',
						pointBorderWidth: 2,
						pointRadius: 4,
						pointHoverRadius: 6
					}]
				},
				options: this.getDefaultOptions('line')
			});
		},

		/**
		 * Initialize hourly distribution chart
		 */
		initHourlyChart: function() {
			var ctx = document.getElementById('demowp-hourly-chart');
			if (!ctx) return;

			this.charts.hourly = new Chart(ctx, {
				type: 'bar',
				data: {
					labels: demowpChartData.hourly.labels,
					datasets: [{
						label: 'Demos',
						data: demowpChartData.hourly.data,
						backgroundColor: 'rgba(139, 92, 246, 0.7)',
						borderColor: '#8b5cf6',
						borderWidth: 1,
						borderRadius: 4
					}]
				},
				options: $.extend(true, {}, this.getDefaultOptions('bar'), {
					scales: {
						x: {
							ticks: {
								maxRotation: 45,
								minRotation: 45,
								callback: function(value, index) {
									// Show every 3rd label to avoid crowding
									return index % 3 === 0 ? this.getLabelForValue(value) : '';
								}
							}
						}
					}
				})
			});
		},

		/**
		 * Initialize day of week chart
		 */
		initDayOfWeekChart: function() {
			var ctx = document.getElementById('demowp-dow-chart');
			if (!ctx) return;

			var colors = [
				'#ef4444', // Sunday - red
				'#3b82f6', // Monday - blue
				'#10b981', // Tuesday - green
				'#f59e0b', // Wednesday - yellow
				'#8b5cf6', // Thursday - purple
				'#ec4899', // Friday - pink
				'#14b8a6'  // Saturday - teal
			];

			this.charts.dayOfWeek = new Chart(ctx, {
				type: 'bar',
				data: {
					labels: demowpChartData.dayOfWeek.labels,
					datasets: [{
						label: 'Demos',
						data: demowpChartData.dayOfWeek.data,
						backgroundColor: colors.map(function(c) { return c + 'CC'; }),
						borderColor: colors,
						borderWidth: 1,
						borderRadius: 4
					}]
				},
				options: this.getDefaultOptions('bar')
			});
		},

		/**
		 * Initialize status distribution chart
		 */
		initStatusChart: function() {
			var ctx = document.getElementById('demowp-status-chart');
			if (!ctx) return;

			// Check if there's data
			var hasData = demowpChartData.status.data.some(function(v) { return v > 0; });

			if (!hasData) {
				$(ctx).closest('.demowp-chart-container').html(
					'<p class="demowp-no-data">No data available for this period.</p>'
				);
				return;
			}

			var defaultColors = ['#4CAF50', '#FF9800', '#f44336', '#9E9E9E', '#2196F3'];

			this.charts.status = new Chart(ctx, {
				type: 'doughnut',
				data: {
					labels: demowpChartData.status.labels,
					datasets: [{
						data: demowpChartData.status.data,
						backgroundColor: demowpChartData.status.colors.length > 0
							? demowpChartData.status.colors
							: defaultColors,
						borderWidth: 0
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: true,
							position: 'bottom',
							labels: {
								padding: 16,
								usePointStyle: true,
								pointStyle: 'circle'
							}
						}
					},
					cutout: '60%'
				}
			});
		},

		/**
		 * Handle CSV export
		 */
		handleExportCSV: function(e) {
			e.preventDefault();

			var startDate = demowpChartData.export.startDate;
			var endDate = demowpChartData.export.endDate;

			// Create form and submit
			var form = $('<form>', {
				method: 'POST',
				action: demowpAdmin.ajaxUrl
			}).append(
				$('<input>', { type: 'hidden', name: 'action', value: 'demowp_export_csv' }),
				$('<input>', { type: 'hidden', name: 'nonce', value: demowpAdmin.nonce }),
				$('<input>', { type: 'hidden', name: 'start_date', value: startDate }),
				$('<input>', { type: 'hidden', name: 'end_date', value: endDate })
			);

			$('body').append(form);
			form.submit();
			form.remove();
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		DemoWPStatistics.init();
	});

})(jQuery);
