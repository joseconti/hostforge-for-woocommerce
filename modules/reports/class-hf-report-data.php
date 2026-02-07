<?php
/**
 * Report Data Provider.
 *
 * Queries the database for report data: revenue, services,
 * support metrics, domains, servers.
 *
 * @package HostForge\Modules\Reports
 */

namespace HostForge\Modules\Reports;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Report_Data
 */
class HF_Report_Data {

	/**
	 * Get monthly revenue data for a date range.
	 *
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date   End date (Y-m-d).
	 * @return array
	 */
	public function get_revenue_data( string $start_date, string $end_date ): array {
		global $wpdb;

		$order_stats_table = $wpdb->prefix . 'wc_order_stats';

		// Check if WC order stats table exists (HPOS).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
				DB_NAME,
				$order_stats_table
			)
		);

		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
						DATE_FORMAT(date_created, '%%Y-%%m') AS month,
						SUM(total_sales) AS revenue,
						COUNT(*) AS order_count
					FROM {$order_stats_table}
					WHERE status IN ('wc-completed', 'wc-processing')
					AND date_created >= %s
					AND date_created <= %s
					GROUP BY DATE_FORMAT(date_created, '%%Y-%%m')
					ORDER BY month ASC",
					$start_date . ' 00:00:00',
					$end_date . ' 23:59:59'
				)
			);
		} else {
			$results = array();
		}

		$data = ! empty( $results ) ? $results : array();

		/**
		 * Filter revenue data before returning.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $data       Revenue data rows with month, revenue, order_count.
		 * @param string $start_date Start date (Y-m-d).
		 * @param string $end_date   End date (Y-m-d).
		 */
		return apply_filters( 'hostforge_report_revenue_data', $data, $start_date, $end_date );
	}

	/**
	 * Get Monthly Recurring Revenue estimate.
	 *
	 * Uses a single JOIN query instead of per-service lookups
	 * and caches the result in a transient for 1 hour.
	 *
	 * @return float
	 */
	public function get_mrr(): float {
		$cached = get_transient( 'hf_report_mrr' );

		if ( false !== $cached ) {
			return (float) $cached;
		}

		global $wpdb;

		// Single query: join services → product_id meta → postmeta for price.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$mrr = (float) $wpdb->get_var(
			"SELECT COALESCE(SUM(CAST(price_meta.meta_value AS DECIMAL(10,2))), 0)
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} status_meta
				ON p.ID = status_meta.post_id
				AND status_meta.meta_key = '_hf_status'
				AND status_meta.meta_value = 'active'
			INNER JOIN {$wpdb->postmeta} product_meta
				ON p.ID = product_meta.post_id
				AND product_meta.meta_key = '_hf_product_id'
			INNER JOIN {$wpdb->postmeta} price_meta
				ON product_meta.meta_value = price_meta.post_id
				AND price_meta.meta_key = '_price'
			WHERE p.post_type = 'hf_service'
			AND p.post_status = 'publish'"
		);

		$mrr = round( $mrr, 2 );

		/**
		 * Filter the Monthly Recurring Revenue value before returning.
		 *
		 * @since 1.0.0
		 *
		 * @param float $mrr Calculated MRR value.
		 */
		$mrr = (float) apply_filters( 'hostforge_report_mrr', $mrr );

		set_transient( 'hf_report_mrr', $mrr, HOUR_IN_SECONDS );

		return $mrr;
	}

	/**
	 * Get services count by status.
	 *
	 * Uses a single GROUP BY query instead of one query per status.
	 *
	 * @return array
	 */
	public function get_services_by_status(): array {
		global $wpdb;

		$defaults = array(
			'active'     => 0,
			'pending'    => 0,
			'suspended'  => 0,
			'terminated' => 0,
			'cancelled'  => 0,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT pm.meta_value AS status, COUNT(*) AS total
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm
				ON p.ID = pm.post_id AND pm.meta_key = '_hf_status'
			WHERE p.post_type = 'hf_service'
			AND p.post_status = 'publish'
			GROUP BY pm.meta_value"
		);

		$result = $defaults;

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( isset( $result[ $row->status ] ) ) {
					$result[ $row->status ] = (int) $row->total;
				}
			}
		}

		/**
		 * Filter services by status data before returning.
		 *
		 * @since 1.0.0
		 *
		 * @param array $result Associative array of status => count.
		 */
		return apply_filters( 'hostforge_report_services_data', $result );
	}

	/**
	 * Get support ticket metrics.
	 *
	 * Uses single GROUP BY query for status counts and a single
	 * JOIN query for average resolution time.
	 *
	 * @return array
	 */
	public function get_ticket_metrics(): array {
		global $wpdb;

		$defaults = array(
			'open'           => 0,
			'customer_reply' => 0,
			'staff_reply'    => 0,
			'in_progress'    => 0,
			'closed'         => 0,
		);

		// Single query for all status counts.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT pm.meta_value AS status, COUNT(*) AS total
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm
				ON p.ID = pm.post_id AND pm.meta_key = '_hf_status'
			WHERE p.post_type = 'hf_ticket'
			AND p.post_status = 'publish'
			GROUP BY pm.meta_value"
		);

		$by_status = $defaults;

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( isset( $by_status[ $row->status ] ) ) {
					$by_status[ $row->status ] = (int) $row->total;
				}
			}
		}

		// Average resolution time with a single JOIN query.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$avg_result = $wpdb->get_row(
			"SELECT
				AVG(ABS(TIMESTAMPDIFF(SECOND, p.post_date_gmt, closed_meta.meta_value))) / 3600 AS avg_hours,
				COUNT(*) AS total
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} status_meta
				ON p.ID = status_meta.post_id
				AND status_meta.meta_key = '_hf_status'
				AND status_meta.meta_value = 'closed'
			INNER JOIN {$wpdb->postmeta} closed_meta
				ON p.ID = closed_meta.post_id
				AND closed_meta.meta_key = '_hf_closed_at'
				AND closed_meta.meta_value != ''
			WHERE p.post_type = 'hf_ticket'
			AND p.post_status = 'publish'
			ORDER BY p.post_date DESC
			LIMIT 50"
		);

		$avg_resolution = ( $avg_result && $avg_result->total > 0 )
			? round( (float) $avg_result->avg_hours, 1 )
			: 0;

		$metrics = array(
			'by_status'      => $by_status,
			'avg_resolution' => $avg_resolution,
			'total_open'     => $by_status['open'] + $by_status['customer_reply'] + $by_status['in_progress'],
		);

		/**
		 * Filter ticket metrics data before returning.
		 *
		 * @since 1.0.0
		 *
		 * @param array $metrics Ticket metrics including by_status, avg_resolution, total_open.
		 */
		return apply_filters( 'hostforge_report_ticket_metrics', $metrics );
	}

	/**
	 * Get domain statistics.
	 *
	 * Uses single GROUP BY query for status counts.
	 *
	 * @return array
	 */
	public function get_domain_stats(): array {
		global $wpdb;

		$defaults = array(
			'active'      => 0,
			'pending'     => 0,
			'expired'     => 0,
			'transferred' => 0,
		);

		// Single query for all status counts.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT pm.meta_value AS status, COUNT(*) AS total
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm
				ON p.ID = pm.post_id AND pm.meta_key = '_hf_status'
			WHERE p.post_type = 'hf_domain'
			AND p.post_status = 'publish'
			GROUP BY pm.meta_value"
		);

		$result = $defaults;

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( isset( $result[ $row->status ] ) ) {
					$result[ $row->status ] = (int) $row->total;
				}
			}
		}

		// Expiring in next 30 days.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$expiring = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_hf_expiry_date'
				INNER JOIN {$wpdb->postmeta} ps ON p.ID = ps.post_id AND ps.meta_key = '_hf_status' AND ps.meta_value = 'active'
				WHERE p.post_type = 'hf_domain'
				AND p.post_status = 'publish'
				AND pm.meta_value BETWEEN %s AND %s",
				gmdate( 'Y-m-d' ),
				gmdate( 'Y-m-d', strtotime( '+30 days' ) )
			)
		);

		$result['expiring_soon'] = $expiring;

		/**
		 * Filter domain statistics data before returning.
		 *
		 * @since 1.0.0
		 *
		 * @param array $result Domain stats including status counts and expiring_soon.
		 */
		return apply_filters( 'hostforge_report_domain_stats', $result );
	}

	/**
	 * Get server capacity data.
	 *
	 * Uses a single query with JOINs to fetch all server data
	 * instead of per-server meta lookups.
	 *
	 * @return array
	 */
	public function get_server_capacity(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT
				p.ID AS id,
				p.post_title AS name,
				COALESCE(max_meta.meta_value, 0) AS max_accounts,
				COALESCE(cur_meta.meta_value, 0) AS current_accounts
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} max_meta
				ON p.ID = max_meta.post_id AND max_meta.meta_key = '_hf_max_accounts'
			LEFT JOIN {$wpdb->postmeta} cur_meta
				ON p.ID = cur_meta.post_id AND cur_meta.meta_key = '_hf_current_accounts'
			WHERE p.post_type = 'hf_server'
			AND p.post_status = 'publish'
			ORDER BY p.post_title ASC"
		);

		$data = array();

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
				$max     = (int) $row->max_accounts;
				$current = (int) $row->current_accounts;

				$data[] = array(
					'id'      => (int) $row->id,
					'name'    => $row->name,
					'max'     => $max,
					'current' => $current,
					'usage'   => $max > 0 ? round( ( $current / $max ) * 100, 1 ) : 0,
				);
			}
		}

		/**
		 * Filter server capacity data before returning.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Array of server capacity arrays with id, name, max, current, usage.
		 */
		return apply_filters( 'hostforge_report_server_capacity', $data );
	}

	/**
	 * Get customer growth data (new registrations per month).
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 * @return array
	 */
	public function get_customer_growth( string $start_date, string $end_date ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE_FORMAT(user_registered, '%%Y-%%m') AS month,
					COUNT(*) AS count
				FROM {$wpdb->users}
				WHERE user_registered >= %s
				AND user_registered <= %s
				GROUP BY DATE_FORMAT(user_registered, '%%Y-%%m')
				ORDER BY month ASC",
				$start_date . ' 00:00:00',
				$end_date . ' 23:59:59'
			)
		);

		return ! empty( $results ) ? $results : array();
	}
}
