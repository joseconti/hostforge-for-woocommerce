<?php
/**
 * IP Manager.
 *
 * Manages IP allowlist and blocklist for the security module.
 * Blocks requests from blocklisted IPs at an early stage.
 *
 * @package HostForge\Modules\Security
 */

namespace HostForge\Modules\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_IP_Manager
 */
class HF_IP_Manager {

	/**
	 * Module instance.
	 *
	 * @var HF_Security_Module
	 */
	private HF_Security_Module $module;

	/**
	 * Constructor.
	 *
	 * @param HF_Security_Module $module Module instance.
	 */
	public function __construct( HF_Security_Module $module ) {
		$this->module = $module;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Early check — block blacklisted IPs.
		add_action( 'init', array( $this, 'check_ip_access' ), 1 );
	}

	/**
	 * Check if the visitor's IP is blocked.
	 *
	 * @return void
	 */
	public function check_ip_access(): void {
		// Skip admin-ajax and REST for WordPress internal needs.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		$ip = $this->get_visitor_ip();

		if ( empty( $ip ) ) {
			return;
		}

		// Check allowlist first — always allow.
		if ( $this->is_ip_in_list( $ip, 'ip_allowlist' ) ) {
			return;
		}

		// Check settings-based blocklist.
		if ( $this->is_ip_in_list( $ip, 'ip_blocklist' ) ) {
			/**
			 * Fires when an IP is denied access (from settings blocklist or DB).
			 *
			 * @since 1.0.0
			 *
			 * @param string $ip     The blocked IP address.
			 * @param string $source Source of the block: 'settings_blocklist' or 'database'.
			 */
			do_action( 'hostforge_ip_access_denied', $ip, 'settings_blocklist' );

			$this->block_request();
			return;
		}

		// Check database blocklist.
		if ( $this->is_ip_blocked_in_db( $ip ) ) {
			/** This action is documented above. */
			do_action( 'hostforge_ip_access_denied', $ip, 'database' );

			$this->block_request();
			return;
		}
	}

	/**
	 * Add an IP to the blocklist database.
	 *
	 * @param string      $ip         IP address.
	 * @param string      $reason     Block reason.
	 * @param string|null $expires_at Expiry datetime (null for permanent).
	 * @param string      $type       Block type (manual or auto).
	 * @return bool
	 */
	public function add_block( string $ip, string $reason = '', ?string $expires_at = null, string $type = 'manual' ): bool {
		global $wpdb;

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		$table = $wpdb->prefix . 'hf_ip_blocks';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (ip_address, block_type, reason, expires_at, created_at)
				VALUES (%s, %s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE
				reason = VALUES(reason), expires_at = VALUES(expires_at), block_type = VALUES(block_type)",
				$ip,
				$type,
				$reason,
				$expires_at,
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return false !== $result;
	}

	/**
	 * Remove an IP from the blocklist database.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	public function remove_block( string $ip ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'hf_ip_blocks';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table,
			array( 'ip_address' => $ip ),
			array( '%s' )
		);

		return false !== $result;
	}

	/**
	 * Get all blocked IPs.
	 *
	 * @param int $per_page Items per page.
	 * @param int $page     Current page.
	 * @return array{items: array, total: int}
	 */
	public function get_blocked_ips( int $per_page = 20, int $page = 1 ): array {
		global $wpdb;

		$table  = $wpdb->prefix . 'hf_ip_blocks';
		$offset = ( $page - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
		$items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'items' => ! empty( $items ) ? $items : array(),
			'total' => $total,
		);
	}

	/**
	 * Check if an IP is in the blocklist database.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private function is_ip_blocked_in_db( string $ip ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'hf_ip_blocks';

		// Bail if the table doesn't exist yet (before activation).
		static $table_exists = null;
		if ( null === $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		}
		if ( ! $table_exists ) {
			return false;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name.
		$block = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE ip_address = %s",
				$ip
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $block ) {
			return false;
		}

		// Permanent block.
		if ( empty( $block->expires_at ) ) {
			return true;
		}

		// Expired block.
		if ( strtotime( $block->expires_at ) < time() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if an IP is in a settings list (allowlist or blocklist).
	 *
	 * @param string $ip       IP address.
	 * @param string $list_key Settings key (ip_allowlist or ip_blocklist).
	 * @return bool
	 */
	private function is_ip_in_list( string $ip, string $list_key ): bool {
		$settings = $this->module->get_security_settings();
		$list     = ! empty( $settings[ $list_key ] ) ? $settings[ $list_key ] : '';

		if ( empty( $list ) ) {
			return false;
		}

		$ips = array_map( 'trim', explode( "\n", $list ) );
		$ips = array_filter( $ips );

		if ( 'ip_allowlist' === $list_key ) {
			/**
			 * Filter the IP allowlist before checking.
			 *
			 * Allows third-party code to modify the list of allowed IPs
			 * at runtime (e.g. to add dynamic entries from an external source).
			 *
			 * @since 1.0.0
			 *
			 * @param array  $ips The allowlist IP/CIDR entries.
			 * @param string $ip  The visitor IP being checked.
			 */
			$ips = apply_filters( 'hostforge_ip_allowlist', $ips, $ip );
		} else {
			/**
			 * Filter the IP blocklist before checking.
			 *
			 * Allows third-party code to modify the list of blocked IPs
			 * at runtime (e.g. to merge in an external blocklist).
			 *
			 * @since 1.0.0
			 *
			 * @param array  $ips The blocklist IP/CIDR entries.
			 * @param string $ip  The visitor IP being checked.
			 */
			$ips = apply_filters( 'hostforge_ip_blocklist', $ips, $ip );
		}

		foreach ( $ips as $entry ) {
			// Exact match.
			if ( $entry === $ip ) {
				return true;
			}

			// CIDR match.
			if ( strpos( $entry, '/' ) !== false && $this->ip_in_cidr( $ip, $entry ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if an IP is within a CIDR range.
	 *
	 * @param string $ip   IP address.
	 * @param string $cidr CIDR notation (e.g. 192.168.1.0/24).
	 * @return bool
	 */
	private function ip_in_cidr( string $ip, string $cidr ): bool {
		$parts = explode( '/', $cidr );

		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$subnet = $parts[0];
		$mask   = (int) $parts[1];

		// IPv4.
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$ip_long     = ip2long( $ip );
			$subnet_long = ip2long( $subnet );

			if ( false === $ip_long || false === $subnet_long || $mask < 0 || $mask > 32 ) {
				return false;
			}

			$mask_long = -1 << ( 32 - $mask );

			return ( $ip_long & $mask_long ) === ( $subnet_long & $mask_long );
		}

		return false;
	}

	/**
	 * Block the current request.
	 *
	 * @return void
	 */
	private function block_request(): void {
		status_header( 403 );
		nocache_headers();
		wp_die(
			esc_html__( 'Access denied. Your IP address has been blocked.', 'hostforge' ),
			esc_html__( 'Forbidden', 'hostforge' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Get the visitor's IP address.
	 *
	 * @return string IP address.
	 */
	private function get_visitor_ip(): string {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '';
		}

		return $ip;
	}
}
