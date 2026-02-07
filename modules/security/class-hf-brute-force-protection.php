<?php
/**
 * Brute Force Protection.
 *
 * Tracks login attempts and blocks IPs after exceeding the configured
 * maximum number of failed attempts.
 *
 * @package HostForge\Modules\Security
 */

namespace HostForge\Modules\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Brute_Force_Protection
 */
class HF_Brute_Force_Protection {

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
		// Check if IP is blocked before authentication.
		add_filter( 'authenticate', array( $this, 'check_blocked_ip' ), 1, 3 );

		// Record login attempts.
		add_action( 'wp_login', array( $this, 'record_success' ), 10, 2 );
		add_action( 'wp_login_failed', array( $this, 'record_failure' ) );

		// Block XML-RPC brute force.
		add_filter( 'xmlrpc_enabled', array( $this, 'check_xmlrpc_blocked' ) );
	}

	/**
	 * Check if the visitor's IP is blocked before authentication.
	 *
	 * @param null|\WP_User|\WP_Error $user     User object or error.
	 * @param string                  $username Username.
	 * @param string                  $password Password.
	 * @return null|\WP_User|\WP_Error
	 */
	public function check_blocked_ip( $user, string $username, string $password ) {
		if ( empty( $username ) ) {
			return $user;
		}

		$ip = $this->get_visitor_ip();

		if ( empty( $ip ) ) {
			return $user;
		}

		// Check allowlist first.
		if ( $this->is_ip_allowlisted( $ip ) ) {
			return $user;
		}

		// Check if IP is explicitly blocked.
		if ( $this->is_ip_blocked( $ip ) ) {
			return new \WP_Error(
				'hf_ip_blocked',
				__( 'Your IP address has been blocked due to too many failed login attempts. Please try again later.', 'hostforge' )
			);
		}

		// Check if IP has exceeded max attempts.
		if ( $this->has_exceeded_attempts( $ip ) ) {
			$this->block_ip( $ip, 'auto' );

			return new \WP_Error(
				'hf_ip_blocked',
				__( 'Your IP address has been blocked due to too many failed login attempts. Please try again later.', 'hostforge' )
			);
		}

		return $user;
	}

	/**
	 * Record a successful login.
	 *
	 * @param string   $username Username.
	 * @param \WP_User $user     User object.
	 * @return void
	 */
	public function record_success( string $username, \WP_User $user ): void {
		$this->record_attempt( $username, 'success' );
	}

	/**
	 * Record a failed login attempt.
	 *
	 * @param string $username Username that was attempted.
	 * @return void
	 */
	public function record_failure( string $username ): void {
		$this->record_attempt( $username, 'failed' );

		$ip = $this->get_visitor_ip();

		if ( ! empty( $ip ) && ! $this->is_ip_allowlisted( $ip ) && $this->has_exceeded_attempts( $ip ) ) {
			$this->block_ip( $ip, 'auto' );
		}
	}

	/**
	 * Check XML-RPC blocked.
	 *
	 * @param bool $enabled Whether XML-RPC is enabled.
	 * @return bool
	 */
	public function check_xmlrpc_blocked( bool $enabled ): bool {
		if ( ! $enabled ) {
			return $enabled;
		}

		$ip = $this->get_visitor_ip();

		if ( ! empty( $ip ) && $this->is_ip_blocked( $ip ) ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Record a login attempt.
	 *
	 * @param string $username Username.
	 * @param string $status   Attempt status (success/failed).
	 * @return void
	 */
	private function record_attempt( string $username, string $status ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'hf_login_attempts';
		$ip    = $this->get_visitor_ip();

		$user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table,
			array(
				'ip_address' => $ip,
				'username'   => sanitize_user( $username ),
				'status'     => $status,
				'user_agent' => $user_agent,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		/**
		 * Fires after a login attempt is recorded.
		 *
		 * @since 1.0.0
		 *
		 * @param string $ip       Visitor IP address.
		 * @param string $username Username attempted.
		 * @param string $status   Attempt status: 'success' or 'failed'.
		 */
		do_action( 'hostforge_login_attempt_recorded', $ip, sanitize_user( $username ), $status );
	}

	/**
	 * Check if an IP has exceeded the maximum number of failed login attempts.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private function has_exceeded_attempts( string $ip ): bool {
		global $wpdb;

		$settings     = $this->module->get_security_settings();
		$max_attempts = ! empty( $settings['max_login_attempts'] ) ? (int) $settings['max_login_attempts'] : 5;
		$duration     = ! empty( $settings['lockout_duration'] ) ? (int) $settings['lockout_duration'] : 30;
		$unit         = ! empty( $settings['lockout_duration_unit'] ) ? $settings['lockout_duration_unit'] : 'minutes';

		/**
		 * Filter the maximum number of failed login attempts before blocking an IP.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $max_attempts Maximum allowed failed attempts.
		 * @param string $ip           The IP address being checked.
		 */
		$max_attempts = (int) apply_filters( 'hostforge_max_login_attempts', $max_attempts, $ip );

		$since = $this->calculate_since( $duration, $unit );

		$table = $wpdb->prefix . 'hf_login_attempts';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$failed_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table}
				WHERE ip_address = %s AND status = 'failed' AND created_at > %s",
				$ip,
				$since
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $failed_count >= $max_attempts;
	}

	/**
	 * Block an IP address.
	 *
	 * @param string $ip   IP address to block.
	 * @param string $type Block type (auto or manual).
	 * @return void
	 */
	private function block_ip( string $ip, string $type = 'auto' ): void {
		global $wpdb;

		$table    = $wpdb->prefix . 'hf_ip_blocks';
		$settings = $this->module->get_security_settings();
		$duration = ! empty( $settings['lockout_duration'] ) ? (int) $settings['lockout_duration'] : 30;
		$unit     = ! empty( $settings['lockout_duration_unit'] ) ? $settings['lockout_duration_unit'] : 'minutes';

		$block_seconds = 'hours' === $unit ? $duration * HOUR_IN_SECONDS : $duration * MINUTE_IN_SECONDS;

		/**
		 * Filter the block duration in seconds when an IP is locked out.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $block_seconds Block duration in seconds.
		 * @param string $ip            The IP address being blocked.
		 * @param string $type          Block type: 'auto' or 'manual'.
		 */
		$block_seconds = (int) apply_filters( 'hostforge_login_block_duration', $block_seconds, $ip, $type );

		$expires_at = gmdate( 'Y-m-d H:i:s', time() + $block_seconds );

		// Use INSERT ... ON DUPLICATE KEY UPDATE to handle existing blocks.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (ip_address, block_type, reason, expires_at, created_at)
				VALUES (%s, %s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE
				reason = VALUES(reason), expires_at = VALUES(expires_at), block_type = VALUES(block_type)",
				$ip,
				$type,
				__( 'Too many failed login attempts.', 'hostforge' ),
				$expires_at,
				current_time( 'mysql', true )
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->module->log_warning( sprintf( 'IP %s blocked: too many failed login attempts.', $ip ) );

		/**
		 * Fires when an IP address is blocked due to too many failed login attempts.
		 *
		 * @since 1.0.0
		 *
		 * @param string $ip         The IP address that was blocked.
		 * @param string $type       Block type: 'auto' or 'manual'.
		 * @param string $expires_at Expiry datetime in MySQL format.
		 */
		do_action( 'hostforge_ip_blocked', $ip, $type, $expires_at );
	}

	/**
	 * Check if an IP is currently blocked.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private function is_ip_blocked( string $ip ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'hf_ip_blocks';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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

		// Permanent block (no expiry).
		if ( empty( $block->expires_at ) ) {
			return true;
		}

		// Check if block has expired.
		if ( strtotime( $block->expires_at ) < time() ) {
			// Clean up expired block.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table, array( 'id' => $block->id ), array( '%d' ) );
			return false;
		}

		return true;
	}

	/**
	 * Check if an IP is in the allowlist.
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private function is_ip_allowlisted( string $ip ): bool {
		$settings  = $this->module->get_security_settings();
		$allowlist = ! empty( $settings['ip_allowlist'] ) ? $settings['ip_allowlist'] : '';

		if ( empty( $allowlist ) ) {
			return false;
		}

		$allowed_ips = array_map( 'trim', explode( "\n", $allowlist ) );
		$allowed_ips = array_filter( $allowed_ips );

		return in_array( $ip, $allowed_ips, true );
	}

	/**
	 * Calculate the "since" datetime for login attempt checks.
	 *
	 * @param int    $duration Duration value.
	 * @param string $unit     Duration unit (minutes or hours).
	 * @return string MySQL datetime string.
	 */
	private function calculate_since( int $duration, string $unit ): string {
		$seconds = 'hours' === $unit ? $duration * HOUR_IN_SECONDS : $duration * MINUTE_IN_SECONDS;

		return gmdate( 'Y-m-d H:i:s', time() - $seconds );
	}

	/**
	 * Calculate block expiry datetime.
	 *
	 * @param int    $duration Duration value.
	 * @param string $unit     Duration unit (minutes or hours).
	 * @return string MySQL datetime string.
	 */
	private function calculate_expiry( int $duration, string $unit ): string {
		$seconds = 'hours' === $unit ? $duration * HOUR_IN_SECONDS : $duration * MINUTE_IN_SECONDS;

		return gmdate( 'Y-m-d H:i:s', time() + $seconds );
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
