<?php
/**
 * Log Viewer.
 *
 * Admin page for viewing and filtering log entries from the hf_logs table.
 * Also handles auto-pruning of old logs via Action Scheduler.
 *
 * @package HostForge\Admin
 */

namespace HostForge\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Log_Viewer
 */
class HF_Log_Viewer {

	/**
	 * Initialize log viewer.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'hostforge_prune_logs', array( static::class, 'prune_old_logs' ) );

		// Schedule daily log pruning if not already scheduled.
		if ( function_exists( 'as_next_scheduled_action' ) && ! as_next_scheduled_action( 'hostforge_prune_logs', array(), 'hostforge-logs' ) ) {
			as_schedule_recurring_action( time(), DAY_IN_SECONDS, 'hostforge_prune_logs', array(), 'hostforge-logs' );
		}
	}

	/**
	 * Get log entries with optional filters.
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *     @type string $module  Filter by module.
	 *     @type string $level   Filter by level.
	 *     @type string $search  Search in message.
	 *     @type int    $per_page Number of results per page.
	 *     @type int    $page     Current page.
	 *     @type string $orderby  Column to sort by.
	 *     @type string $order    Sort direction (ASC or DESC).
	 * }
	 * @return array{items: array, total: int, pages: int}
	 */
	public static function get_logs( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'module'   => '',
			'level'    => '',
			'search'   => '',
			'per_page' => 50,
			'page'     => 1,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['module'] ) ) {
			$where[]  = 'module = %s';
			$values[] = $args['module'];
		}

		if ( ! empty( $args['level'] ) ) {
			$where[]  = 'level = %s';
			$values[] = $args['level'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'message LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = implode( ' AND ', $where );

		// Sanitize orderby.
		$allowed_orderby = array( 'id', 'module', 'level', 'message', 'created_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		// Count total.
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}hf_logs WHERE {$where_sql}", ...$values ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}hf_logs" );
		}

		$pages  = (int) ceil( $total / max( 1, $args['per_page'] ) );
		$offset = ( max( 1, $args['page'] ) - 1 ) * $args['per_page'];

		// Get items.
		$all_values = array_merge( $values, array( $args['per_page'], $offset ) );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hf_logs WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				...$all_values
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return array(
			'items' => ! empty( $items ) ? $items : array(),
			'total' => $total,
			'pages' => $pages,
		);
	}

	/**
	 * Get distinct modules from logs.
	 *
	 * @return array<string>
	 */
	public static function get_modules(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_col( "SELECT DISTINCT module FROM {$wpdb->prefix}hf_logs ORDER BY module ASC" );

		return ! empty( $results ) ? $results : array();
	}

	/**
	 * Delete old log entries based on retention setting.
	 *
	 * @return int Number of rows deleted.
	 */
	public static function prune_old_logs(): int {
		global $wpdb;

		$retention_days = (int) get_option( 'hf_log_retention_days', 30 );

		/**
		 * Filters the number of days to retain log entries before pruning.
		 *
		 * Allows overriding the log retention period configured in settings.
		 * Return 0 or a negative number to disable automatic pruning.
		 *
		 * @since 1.0.0
		 *
		 * @param int $retention_days Number of days to retain logs.
		 */
		$retention_days = (int) apply_filters( 'hostforge_log_retention_days', $retention_days );

		if ( $retention_days < 1 ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}hf_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$retention_days
			)
		);

		return (int) $wpdb->rows_affected;
	}

	/**
	 * Clear all log entries.
	 *
	 * @return bool
	 */
	public static function clear_all(): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}hf_logs" );

		return false !== $result;
	}
}
