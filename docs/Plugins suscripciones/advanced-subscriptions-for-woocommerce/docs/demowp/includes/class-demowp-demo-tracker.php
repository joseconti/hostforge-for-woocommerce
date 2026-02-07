<?php
/**
 * Demo Tracker
 *
 * Handles tracking of active demos in the database.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Demo_Tracker
 *
 * Manages the database table for tracking demo instances.
 *
 * @since 1.0.0
 */
class DemoWP_Demo_Tracker {

    /**
     * The table name (without prefix)
     *
     * @var string
     */
    const TABLE_NAME = 'demowp_demos';

    /**
     * Get the full table name with prefix
     *
     * @return string The full table name.
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Create the tracking table
     *
     * Called on plugin activation.
     */
    public static function create_table() {
        global $wpdb;

        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            clone_id varchar(64) NOT NULL,
            db_prefix varchar(64) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
            username varchar(60) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            blocked tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY clone_id (clone_id),
            KEY expires_at (expires_at),
            KEY status (status),
            KEY ip_address (ip_address),
            KEY blocked (blocked)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Drop the tracking table
     *
     * Called on plugin uninstall.
     */
    public static function drop_table() {
        global $wpdb;
        $table_name = self::get_table_name();
        $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    /**
     * Register a new demo
     *
     * @param array $data Demo data.
     * @return int|false The inserted ID or false on failure.
     */
    public function register_demo( $data ) {
        global $wpdb;

        $result = $wpdb->insert(
            self::get_table_name(),
            array(
                'clone_id'   => sanitize_text_field( $data['clone_id'] ),
                'db_prefix'  => DemoWP_Utils::sanitize_db_prefix( $data['db_prefix'] ),
                'user_id'    => absint( $data['user_id'] ?? 0 ),
                'username'   => sanitize_user( $data['username'] ?? '' ),
                'created_at' => current_time( 'mysql' ),
                'expires_at' => $data['expires_at'],
                'ip_address' => DemoWP_Utils::get_client_ip(),
                'status'     => 'active',
            ),
            array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get a demo by clone ID
     *
     * @param string $clone_id The clone ID.
     * @return array|null The demo data or null if not found.
     */
    public function get_demo( $clone_id ) {
        global $wpdb;

        $table = self::get_table_name();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE clone_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $clone_id
            ),
            ARRAY_A
        );
    }

    /**
     * Update demo status
     *
     * @param string $clone_id The clone ID.
     * @param string $status   The new status.
     * @return bool True on success, false on failure.
     */
    public function update_status( $clone_id, $status ) {
        global $wpdb;

        return (bool) $wpdb->update(
            self::get_table_name(),
            array( 'status' => sanitize_text_field( $status ) ),
            array( 'clone_id' => $clone_id ),
            array( '%s' ),
            array( '%s' )
        );
    }

    /**
     * Delete a demo record
     *
     * @param string $clone_id The clone ID.
     * @return bool True on success, false on failure.
     */
    public function delete_demo( $clone_id ) {
        global $wpdb;

        return (bool) $wpdb->delete(
            self::get_table_name(),
            array( 'clone_id' => $clone_id ),
            array( '%s' )
        );
    }

    /**
     * Get all expired demos
     *
     * Excludes blocked demos from the results.
     *
     * @return array List of expired demo records.
     */
    public function get_expired_demos() {
        global $wpdb;

        $table = self::get_table_name();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE expires_at < %s AND status = 'active' AND blocked = 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                current_time( 'mysql' )
            ),
            ARRAY_A
        );
    }

    /**
     * Block a demo from automatic deletion
     *
     * @since 1.1.0
     *
     * @param string $clone_id The clone ID.
     * @return bool True on success, false on failure.
     */
    public function block_demo( $clone_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update(
            self::get_table_name(),
            array( 'blocked' => 1 ),
            array( 'clone_id' => $clone_id ),
            array( '%d' ),
            array( '%s' )
        );

        if ( false !== $result ) {
            // Cancel any scheduled cleanup for this demo.
            DemoWP_Cleanup::cancel_cleanup( $clone_id );
        }

        return false !== $result;
    }

    /**
     * Unblock a demo to allow automatic deletion
     *
     * @param string $clone_id The clone ID.
     * @return bool True on success, false on failure.
     */
    public function unblock_demo( $clone_id ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update(
            self::get_table_name(),
            array( 'blocked' => 0 ),
            array( 'clone_id' => $clone_id ),
            array( '%d' ),
            array( '%s' )
        );

        if ( false !== $result ) {
            // Get demo data to check expiration.
            $demo = $this->get_demo( $clone_id );

            if ( $demo ) {
                $expires_at = strtotime( $demo['expires_at'] );

                if ( $expires_at > time() ) {
                    // Still has time, schedule cleanup for when it expires.
                    $delay = $expires_at - time();
                    DemoWP_Cleanup::schedule_cleanup( $clone_id, $delay );
                }
                // If already expired, emergency_cleanup will handle it.
            }
        }

        return false !== $result;
    }

    /**
     * Check if a demo is blocked
     *
     * @param string $clone_id The clone ID.
     * @return bool True if blocked, false otherwise.
     */
    public function is_blocked( $clone_id ) {
        $demo = $this->get_demo( $clone_id );
        return $demo && isset( $demo['blocked'] ) && 1 === (int) $demo['blocked'];
    }

    /**
     * Get all active demos
     *
     * @return array List of active demo records.
     */
    public function get_active_demos() {
        global $wpdb;

        $table = self::get_table_name();

        return $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'active' ORDER BY created_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            ARRAY_A
        );
    }

    /**
     * Count active demos for an IP address
     *
     * @param string $ip_address The IP address.
     * @return int Number of active demos.
     */
    public function count_demos_by_ip( $ip_address ) {
        global $wpdb;

        $table = self::get_table_name();

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE ip_address = %s AND status = 'active'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $ip_address
            )
        );
    }

    /**
     * Get statistics
     *
     * @return array Statistics about demos.
     */
    public function get_statistics() {
        global $wpdb;

        $table = self::get_table_name();
        $stats = array();

        // Total created
        $stats['total_created'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // Currently active
        $stats['active'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'active'" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // Created today
        $stats['created_today'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                current_time( 'Y-m-d' )
            )
        );

        // Created this week
        $stats['created_this_week'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) )
            )
        );

        // Unique IPs today
        $stats['unique_ips_today'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT ip_address) FROM {$table} WHERE DATE(created_at) = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                current_time( 'Y-m-d' )
            )
        );

        return $stats;
    }

    /**
     * Cleanup old completed records
     *
     * Removes records older than 30 days that are not active.
     *
     * @return int Number of deleted records.
     */
    public function cleanup_old_records() {
        global $wpdb;

        $table    = self::get_table_name();
        $cutoff   = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE status != 'active' AND created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $cutoff
            )
        );
    }

    /**
     * Get demos count by date range
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @param string $status     Optional status filter.
     * @return int Number of demos.
     */
    public function get_demos_count_by_date_range( $start_date, $end_date, $status = '' ) {
        global $wpdb;

        $table = self::get_table_name();

        if ( ! empty( $status ) ) {
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) BETWEEN %s AND %s AND status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $start_date,
                    $end_date,
                    $status
                )
            );
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $start_date,
                $end_date
            )
        );
    }

    /**
     * Get demos grouped by day for chart
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @return array Array of date => count pairs.
     */
    public function get_demos_by_day( $start_date, $end_date ) {
        global $wpdb;

        $table = self::get_table_name();

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                FROM {$table}
                WHERE DATE(created_at) BETWEEN %s AND %s
                GROUP BY DATE(created_at)
                ORDER BY date ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $start_date,
                $end_date
            ),
            ARRAY_A
        );

        $data = array();
        foreach ( $results as $row ) {
            $data[ $row['date'] ] = (int) $row['count'];
        }

        return $data;
    }

    /**
     * Get demos grouped by hour of day
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @return array Array of hour => count pairs (0-23).
     */
    public function get_demos_by_hour( $start_date, $end_date ) {
        global $wpdb;

        $table = self::get_table_name();

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT HOUR(created_at) as hour, COUNT(*) as count
                FROM {$table}
                WHERE DATE(created_at) BETWEEN %s AND %s
                GROUP BY HOUR(created_at)
                ORDER BY hour ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $start_date,
                $end_date
            ),
            ARRAY_A
        );

        // Initialize all hours with 0.
        $data = array_fill( 0, 24, 0 );
        foreach ( $results as $row ) {
            $data[ (int) $row['hour'] ] = (int) $row['count'];
        }

        return $data;
    }

    /**
     * Get demos grouped by day of week
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @return array Array of day name => count pairs.
     */
    public function get_demos_by_day_of_week( $start_date, $end_date ) {
        global $wpdb;

        $table = self::get_table_name();

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DAYOFWEEK(created_at) as day_num, COUNT(*) as count
                FROM {$table}
                WHERE DATE(created_at) BETWEEN %s AND %s
                GROUP BY DAYOFWEEK(created_at)
                ORDER BY day_num ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $start_date,
                $end_date
            ),
            ARRAY_A
        );

        $day_names = array(
            1 => __( 'Sunday', 'demowp' ),
            2 => __( 'Monday', 'demowp' ),
            3 => __( 'Tuesday', 'demowp' ),
            4 => __( 'Wednesday', 'demowp' ),
            5 => __( 'Thursday', 'demowp' ),
            6 => __( 'Friday', 'demowp' ),
            7 => __( 'Saturday', 'demowp' ),
        );

        $data = array();
        foreach ( $day_names as $num => $name ) {
            $data[ $name ] = 0;
        }

        foreach ( $results as $row ) {
            $day_name          = $day_names[ (int) $row['day_num'] ];
            $data[ $day_name ] = (int) $row['count'];
        }

        return $data;
    }

    /**
     * Get top IP addresses by demo count
     *
     * @param int    $limit      Number of results.
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @return array Array of IP data with count.
     */
    public function get_top_ips( $limit = 10, $start_date = '', $end_date = '' ) {
        global $wpdb;

        $table = self::get_table_name();

        if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ip_address, COUNT(*) as count, MAX(created_at) as last_demo
                    FROM {$table}
                    WHERE DATE(created_at) BETWEEN %s AND %s AND ip_address IS NOT NULL
                    GROUP BY ip_address
                    ORDER BY count DESC
                    LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $start_date,
                    $end_date,
                    $limit
                ),
                ARRAY_A
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ip_address, COUNT(*) as count, MAX(created_at) as last_demo
                FROM {$table}
                WHERE ip_address IS NOT NULL
                GROUP BY ip_address
                ORDER BY count DESC
                LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Get unique IPs count by date range
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @return int Number of unique IPs.
     */
    public function get_unique_ips_count( $start_date, $end_date ) {
        global $wpdb;

        $table = self::get_table_name();

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT ip_address) FROM {$table} WHERE DATE(created_at) BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $start_date,
                $end_date
            )
        );
    }

    /**
     * Get status distribution
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @return array Array of status => count pairs.
     */
    public function get_status_distribution( $start_date = '', $end_date = '' ) {
        global $wpdb;

        $table = self::get_table_name();

        if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT status, COUNT(*) as count
                    FROM {$table}
                    WHERE DATE(created_at) BETWEEN %s AND %s
                    GROUP BY status", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $start_date,
                    $end_date
                ),
                ARRAY_A
            );
        } else {
            $results = $wpdb->get_results(
                "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                ARRAY_A
            );
        }

        $data = array();
        foreach ( $results as $row ) {
            $data[ $row['status'] ] = (int) $row['count'];
        }

        return $data;
    }

    /**
     * Get average demos per day
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @return float Average demos per day.
     */
    public function get_average_demos_per_day( $start_date, $end_date ) {
        global $wpdb;

        $table = self::get_table_name();

        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) BETWEEN %s AND %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $start_date,
                $end_date
            )
        );

        $start = new DateTime( $start_date );
        $end   = new DateTime( $end_date );
        $days  = $start->diff( $end )->days + 1;

        return $days > 0 ? round( $total / $days, 2 ) : 0;
    }

    /**
     * Get comparison statistics between two periods
     *
     * @param string $current_start  Current period start date.
     * @param string $current_end    Current period end date.
     * @param string $previous_start Previous period start date.
     * @param string $previous_end   Previous period end date.
     * @return array Comparison data with percentages.
     */
    public function get_period_comparison( $current_start, $current_end, $previous_start, $previous_end ) {
        $current_count  = $this->get_demos_count_by_date_range( $current_start, $current_end );
        $previous_count = $this->get_demos_count_by_date_range( $previous_start, $previous_end );

        $current_ips  = $this->get_unique_ips_count( $current_start, $current_end );
        $previous_ips = $this->get_unique_ips_count( $previous_start, $previous_end );

        $demos_change = $previous_count > 0
            ? round( ( ( $current_count - $previous_count ) / $previous_count ) * 100, 1 )
            : ( $current_count > 0 ? 100 : 0 );

        $ips_change = $previous_ips > 0
            ? round( ( ( $current_ips - $previous_ips ) / $previous_ips ) * 100, 1 )
            : ( $current_ips > 0 ? 100 : 0 );

        return array(
            'current_demos'   => $current_count,
            'previous_demos'  => $previous_count,
            'demos_change'    => $demos_change,
            'current_ips'     => $current_ips,
            'previous_ips'    => $previous_ips,
            'ips_change'      => $ips_change,
        );
    }

    /**
     * Get recent demos with pagination
     *
     * @param int    $page       Page number.
     * @param int    $per_page   Items per page.
     * @param string $start_date Start date filter.
     * @param string $end_date   End date filter.
     * @param string $status     Status filter.
     * @param string $ip_address IP address filter.
     * @return array Array with 'items' and 'total' keys.
     */
    public function get_demos_paginated( $page = 1, $per_page = 20, $start_date = '', $end_date = '', $status = '', $ip_address = '' ) {
        global $wpdb;

        $table  = self::get_table_name();
        $offset = ( $page - 1 ) * $per_page;

        $where_clauses = array( '1=1' );
        $where_values  = array();

        if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
            $where_clauses[] = 'DATE(created_at) BETWEEN %s AND %s';
            $where_values[]  = $start_date;
            $where_values[]  = $end_date;
        }

        if ( ! empty( $status ) ) {
            $where_clauses[] = 'status = %s';
            $where_values[]  = $status;
        }

        if ( ! empty( $ip_address ) ) {
            $where_clauses[] = 'ip_address = %s';
            $where_values[]  = $ip_address;
        }

        $where_sql = implode( ' AND ', $where_clauses );

        // Get total count.
        if ( ! empty( $where_values ) ) {
            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    ...$where_values
                )
            );
        } else {
            $total = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            );
        }

        // Get items.
        $where_values[] = $per_page;
        $where_values[] = $offset;

        if ( count( $where_values ) > 2 ) {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    ...$where_values
                ),
                ARRAY_A
            );
        } else {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );
        }

        return array(
            'items' => $items,
            'total' => $total,
        );
    }

    /**
     * Get advanced statistics for dashboard
     *
     * @param string $start_date Start date (Y-m-d format).
     * @param string $end_date   End date (Y-m-d format).
     * @return array Comprehensive statistics.
     */
    public function get_advanced_statistics( $start_date, $end_date ) {
        // Calculate previous period for comparison.
        $start     = new DateTime( $start_date );
        $end       = new DateTime( $end_date );
        $days_diff = $start->diff( $end )->days + 1;

        $prev_end   = ( clone $start )->modify( '-1 day' );
        $prev_start = ( clone $prev_end )->modify( '-' . ( $days_diff - 1 ) . ' days' );

        $comparison = $this->get_period_comparison(
            $start_date,
            $end_date,
            $prev_start->format( 'Y-m-d' ),
            $prev_end->format( 'Y-m-d' )
        );

        return array(
            'total_demos'        => $comparison['current_demos'],
            'previous_demos'     => $comparison['previous_demos'],
            'demos_change'       => $comparison['demos_change'],
            'unique_ips'         => $comparison['current_ips'],
            'previous_ips'       => $comparison['previous_ips'],
            'ips_change'         => $comparison['ips_change'],
            'average_per_day'    => $this->get_average_demos_per_day( $start_date, $end_date ),
            'by_day'             => $this->get_demos_by_day( $start_date, $end_date ),
            'by_hour'            => $this->get_demos_by_hour( $start_date, $end_date ),
            'by_day_of_week'     => $this->get_demos_by_day_of_week( $start_date, $end_date ),
            'top_ips'            => $this->get_top_ips( 10, $start_date, $end_date ),
            'status_distribution' => $this->get_status_distribution( $start_date, $end_date ),
        );
    }

    /**
     * Export demos to CSV format
     *
     * @param string $start_date Start date filter.
     * @param string $end_date   End date filter.
     * @param string $status     Status filter.
     * @return string CSV content.
     */
    public function export_to_csv( $start_date = '', $end_date = '', $status = '' ) {
        global $wpdb;

        $table = self::get_table_name();

        $where_clauses = array( '1=1' );
        $where_values  = array();

        if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
            $where_clauses[] = 'DATE(created_at) BETWEEN %s AND %s';
            $where_values[]  = $start_date;
            $where_values[]  = $end_date;
        }

        if ( ! empty( $status ) ) {
            $where_clauses[] = 'status = %s';
            $where_values[]  = $status;
        }

        $where_sql = implode( ' AND ', $where_clauses );

        if ( ! empty( $where_values ) ) {
            $demos = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    ...$where_values
                ),
                ARRAY_A
            );
        } else {
            $demos = $wpdb->get_results(
                "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                ARRAY_A
            );
        }

        $csv = "ID,Clone ID,Username,Created At,Expires At,IP Address,Status\n";

        foreach ( $demos as $demo ) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $demo['id'],
                $demo['clone_id'],
                $demo['username'],
                $demo['created_at'],
                $demo['expires_at'],
                $demo['ip_address'],
                $demo['status']
            );
        }

        return $csv;
    }
}
