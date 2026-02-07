/**
 * HostForge Reports Admin JS.
 *
 * Initializes Chart.js charts using REST API data.
 *
 * @package HostForge
 */

( function () {
	'use strict';

	var config = window.hfReports || {};
	var restUrl = config.restUrl || '';
	var nonce = config.nonce || '';
	var i18n = config.i18n || {};

	/**
	 * Fetch JSON from REST endpoint.
	 *
	 * @param {string} endpoint Endpoint path.
	 * @return {Promise} Response data.
	 */
	function fetchData( endpoint ) {
		return fetch( restUrl + endpoint, {
			headers: {
				'X-WP-Nonce': nonce,
			},
		} ).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( response.statusText );
			}
			return response.json();
		} );
	}

	/**
	 * Initialize revenue line chart.
	 */
	function initRevenueChart() {
		var canvas = document.getElementById( 'hf-revenue-chart' );
		if ( ! canvas ) {
			return;
		}

		fetchData( 'revenue?months=12' ).then( function ( result ) {
			var data = result.data || [];
			var labels = data.map( function ( item ) {
				return item.month || '';
			} );
			var revenue = data.map( function ( item ) {
				return parseFloat( item.revenue ) || 0;
			} );
			var orders = data.map( function ( item ) {
				return parseInt( item.orders, 10 ) || 0;
			} );

			new Chart( canvas, {
				type: 'line',
				data: {
					labels: labels,
					datasets: [
						{
							label: i18n.revenue || 'Revenue',
							data: revenue,
							borderColor: '#2271b1',
							backgroundColor: 'rgba(34,113,177,0.1)',
							fill: true,
							tension: 0.3,
							yAxisID: 'y',
						},
						{
							label: i18n.orders || 'Orders',
							data: orders,
							borderColor: '#00a32a',
							backgroundColor: 'rgba(0,163,42,0.1)',
							fill: false,
							tension: 0.3,
							yAxisID: 'y1',
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					interaction: {
						mode: 'index',
						intersect: false,
					},
					scales: {
						y: {
							type: 'linear',
							position: 'left',
							beginAtZero: true,
							ticks: {
								callback: function ( value ) {
									return '$' + value.toLocaleString();
								},
							},
						},
						y1: {
							type: 'linear',
							position: 'right',
							beginAtZero: true,
							grid: {
								drawOnChartArea: false,
							},
						},
					},
				},
			} );
		} );
	}

	/**
	 * Initialize customer growth bar chart.
	 */
	function initCustomersChart() {
		var canvas = document.getElementById( 'hf-customers-chart' );
		if ( ! canvas ) {
			return;
		}

		fetchData( 'customers?months=12' ).then( function ( result ) {
			var data = result.data || [];
			var labels = data.map( function ( item ) {
				return item.month || '';
			} );
			var counts = data.map( function ( item ) {
				return parseInt( item.count, 10 ) || 0;
			} );

			new Chart( canvas, {
				type: 'bar',
				data: {
					labels: labels,
					datasets: [
						{
							label: i18n.customers || 'New Customers',
							data: counts,
							backgroundColor: '#2271b1',
							borderRadius: 3,
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								stepSize: 1,
							},
						},
					},
				},
			} );
		} );
	}

	/**
	 * Initialize services doughnut chart.
	 */
	function initServicesChart() {
		var canvas = document.getElementById( 'hf-services-chart' );
		if ( ! canvas ) {
			return;
		}

		fetchData( 'services' ).then( function ( result ) {
			var data = result.data || {};
			var labels = [];
			var values = [];
			var colors = {
				active: '#00a32a',
				pending: '#dba617',
				suspended: '#d63638',
				terminated: '#646970',
				cancelled: '#a7aaad',
			};
			var bgColors = [];

			var statusKeys = [ 'active', 'pending', 'suspended', 'terminated', 'cancelled' ];
			statusKeys.forEach( function ( key ) {
				var count = parseInt( data[ key ], 10 ) || 0;
				if ( count > 0 ) {
					labels.push( i18n[ key ] || key );
					values.push( count );
					bgColors.push( colors[ key ] || '#646970' );
				}
			} );

			if ( values.length === 0 ) {
				labels.push( i18n.active || 'Active' );
				values.push( 0 );
				bgColors.push( '#e0e0e0' );
			}

			new Chart( canvas, {
				type: 'doughnut',
				data: {
					labels: labels,
					datasets: [
						{
							data: values,
							backgroundColor: bgColors,
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
							labels: {
								padding: 12,
								usePointStyle: true,
							},
						},
					},
				},
			} );
		} );
	}

	/**
	 * Initialize tickets doughnut chart.
	 */
	function initTicketsChart() {
		var canvas = document.getElementById( 'hf-tickets-chart' );
		if ( ! canvas ) {
			return;
		}

		fetchData( 'tickets' ).then( function ( result ) {
			var data = result.data || {};
			var byStatus = data.by_status || {};
			var labels = [];
			var values = [];
			var colors = {
				open: '#dba617',
				'customer-reply': '#2271b1',
				'staff-reply': '#00a32a',
				'on-hold': '#a7aaad',
				closed: '#646970',
			};
			var bgColors = [];

			Object.keys( byStatus ).forEach( function ( key ) {
				var count = parseInt( byStatus[ key ], 10 ) || 0;
				if ( count > 0 ) {
					labels.push( i18n[ key ] || key );
					values.push( count );
					bgColors.push( colors[ key ] || '#646970' );
				}
			} );

			if ( values.length === 0 ) {
				labels.push( i18n.open || 'Open' );
				values.push( 0 );
				bgColors.push( '#e0e0e0' );
			}

			new Chart( canvas, {
				type: 'doughnut',
				data: {
					labels: labels,
					datasets: [
						{
							data: values,
							backgroundColor: bgColors,
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
							labels: {
								padding: 12,
								usePointStyle: true,
							},
						},
					},
				},
			} );
		} );
	}

	/**
	 * Initialize domains doughnut chart.
	 */
	function initDomainsChart() {
		var canvas = document.getElementById( 'hf-domains-chart' );
		if ( ! canvas ) {
			return;
		}

		fetchData( 'domains' ).then( function ( result ) {
			var data = result.data || {};
			var labels = [];
			var values = [];
			var colors = {
				active: '#00a32a',
				pending: '#dba617',
				expired: '#d63638',
				transferred: '#2271b1',
			};
			var bgColors = [];

			var statusKeys = [ 'active', 'pending', 'expired', 'transferred' ];
			statusKeys.forEach( function ( key ) {
				var count = parseInt( data[ key ], 10 ) || 0;
				if ( count > 0 ) {
					labels.push( i18n[ key ] || key );
					values.push( count );
					bgColors.push( colors[ key ] || '#646970' );
				}
			} );

			if ( values.length === 0 ) {
				labels.push( i18n.active || 'Active' );
				values.push( 0 );
				bgColors.push( '#e0e0e0' );
			}

			new Chart( canvas, {
				type: 'doughnut',
				data: {
					labels: labels,
					datasets: [
						{
							data: values,
							backgroundColor: bgColors,
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
							labels: {
								padding: 12,
								usePointStyle: true,
							},
						},
					},
				},
			} );
		} );
	}

	/**
	 * Initialize all charts on DOM ready.
	 */
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( typeof Chart === 'undefined' ) {
			return;
		}

		initRevenueChart();
		initCustomersChart();
		initServicesChart();
		initTicketsChart();
		initDomainsChart();
	} );
} )();
