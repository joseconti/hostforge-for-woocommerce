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
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

		return ! empty( $results ) ? $results : array();
	}

	/**
	 * Get Monthly Recurring Revenue estimate.
	 *
	 * @return float
	 */
	public function get_mrr(): float {
		$active_services = get_posts(
			array(
				'post_type'      => 'hf_service',
				'post_status'    => 'publish',
				'meta_key'       => '_hf_status',
				'meta_value'     => 'active',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$mrr = 0;

		foreach ( $active_services as $service_id ) {
			$product_id = (int) get_post_meta( $service_id, '_hf_product_id', true );
			$product    = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$price = (float) $product->get_price();

			if ( $price > 0 ) {
				$mrr += $price;
			}
		}

		return round( $mrr, 2 );
	}

	/**
	 * Get services count by status.
	 *
	 * @return array
	 */
	public function get_services_by_status(): array {
		$statuses = array( 'active', 'pending', 'suspended', 'terminated', 'cancelled' );
		$result   = array();

		foreach ( $statuses as $status ) {
			$count = count(
				get_posts(
					array(
						'post_type'      => 'hf_service',
						'post_status'    => 'publish',
						'meta_key'       => '_hf_status',
						'meta_value'     => $status,
						'posts_per_page' => -1,
						'fields'         => 'ids',
					)
				)
			);

			$result[ $status ] = $count;
		}

		return $result;
	}

	/**
	 * Get support ticket metrics.
	 *
	 * @return array
	 */
	public function get_ticket_metrics(): array {
		$statuses = array( 'open', 'customer_reply', 'staff_reply', 'in_progress', 'closed' );
		$by_status = array();

		foreach ( $statuses as $status ) {
			$count = count(
				get_posts(
					array(
						'post_type'      => 'hf_ticket',
						'post_status'    => 'publish',
						'meta_key'       => '_hf_status',
						'meta_value'     => $status,
						'posts_per_page' => -1,
						'fields'         => 'ids',
					)
				)
			);

			$by_status[ $status ] = $count;
		}

		// Average resolution time (closed tickets in last 30 days).
		$closed_tickets = get_posts(
			array(
				'post_type'      => 'hf_ticket',
				'post_status'    => 'publish',
				'meta_key'       => '_hf_status',
				'meta_value'     => 'closed',
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		$total_hours = 0;
		$count       = 0;

		foreach ( $closed_tickets as $ticket_id ) {
			$created = get_the_date( 'U', $ticket_id );
			$closed  = get_post_meta( $ticket_id, '_hf_closed_at', true );

			if ( ! empty( $closed ) ) {
				$diff         = abs( strtotime( $closed ) - (int) $created );
				$total_hours += $diff / HOUR_IN_SECONDS;
				++$count;
			}
		}

		$avg_resolution = $count > 0 ? round( $total_hours / $count, 1 ) : 0;

		return array(
			'by_status'      => $by_status,
			'avg_resolution' => $avg_resolution,
			'total_open'     => array_sum( array_intersect_key( $by_status, array_flip( array( 'open', 'customer_reply', 'in_progress' ) ) ) ),
		);
	}

	/**
	 * Get domain statistics.
	 *
	 * @return array
	 */
	public function get_domain_stats(): array {
		$statuses = array( 'active', 'pending', 'expired', 'transferred' );
		$result   = array();

		foreach ( $statuses as $status ) {
			$result[ $status ] = count(
				get_posts(
					array(
						'post_type'      => 'hf_domain',
						'post_status'    => 'publish',
						'meta_key'       => '_hf_status',
						'meta_value'     => $status,
						'posts_per_page' => -1,
						'fields'         => 'ids',
					)
				)
			);
		}

		// Expiring in next 30 days.
		global $wpdb;
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

		return $result;
	}

	/**
	 * Get server capacity data.
	 *
	 * @return array
	 */
	public function get_server_capacity(): array {
		$servers = get_posts(
			array(
				'post_type'      => 'hf_server',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$data = array();

		foreach ( $servers as $server_id ) {
			$name     = get_the_title( $server_id );
			$max      = (int) get_post_meta( $server_id, '_hf_max_accounts', true );
			$current  = (int) get_post_meta( $server_id, '_hf_current_accounts', true );

			$data[] = array(
				'id'       => $server_id,
				'name'     => $name,
				'max'      => $max,
				'current'  => $current,
				'usage'    => $max > 0 ? round( ( $current / $max ) * 100, 1 ) : 0,
			);
		}

		return $data;
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
