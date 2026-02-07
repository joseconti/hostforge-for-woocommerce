<?php
/**
 * Security Module.
 *
 * Anti brute-force, IP allowlist/blocklist, fraud detection,
 * CAPTCHA integration and activity audit log.
 *
 * @package HostForge\Modules\Security
 */

namespace HostForge\Modules\Security;

use HostForge\Abstracts\HF_Module;
use HostForge\Traits\HF_Has_Logs;
use HostForge\Traits\HF_Has_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Security_Module
 */
class HF_Security_Module extends HF_Module {

	use HF_Has_Logs;
	use HF_Has_Settings;

	/**
	 * Get the module identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'security';
	}

	/**
	 * Get the module display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Security', 'hostforge' );
	}

	/**
	 * Get the module description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Anti brute-force protection, IP management, fraud detection, CAPTCHA and audit logging.', 'hostforge' );
	}

	/**
	 * Get required dependencies.
	 *
	 * @return array<string>
	 */
	public function get_dependencies(): array {
		return array();
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init(): void {
		// Anti brute-force.
		$brute_force = new HF_Brute_Force_Protection( $this );
		$brute_force->init();

		// IP manager.
		$ip_manager = new HF_IP_Manager( $this );
		$ip_manager->init();

		// Fraud detection.
		$fraud = new HF_Fraud_Detection( $this );
		$fraud->init();

		// CAPTCHA.
		$captcha = new HF_Captcha( $this );
		$captcha->init();

		// Audit log.
		$audit = new HF_Audit_Log( $this );
		$audit->init();

		// Admin hooks.
		if ( is_admin() ) {
			$admin = new Admin\HF_Security_Admin( $this );
			$admin->init();
		}

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Scheduled actions.
		add_action( 'hostforge_cleanup_login_attempts', array( $this, 'cleanup_login_attempts' ) );
		add_action( 'hostforge_cleanup_expired_blocks', array( $this, 'cleanup_expired_blocks' ) );
		add_action( 'hostforge_cleanup_audit_log', array( $this, 'cleanup_audit_log' ) );

		// Dashboard widget.
		add_filter( 'hostforge_dashboard_widgets', array( $this, 'register_dashboard_widget' ) );
	}

	/**
	 * Called when the module is activated.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->create_tables();

		// Schedule recurring cleanup tasks.
		if ( function_exists( 'as_has_scheduled_action' ) ) {
			if ( ! as_has_scheduled_action( 'hostforge_cleanup_login_attempts' ) ) {
				as_schedule_recurring_action(
					time() + 300,
					DAY_IN_SECONDS,
					'hostforge_cleanup_login_attempts',
					array(),
					'hostforge-security'
				);
			}

			if ( ! as_has_scheduled_action( 'hostforge_cleanup_expired_blocks' ) ) {
				as_schedule_recurring_action(
					time() + 600,
					HOUR_IN_SECONDS,
					'hostforge_cleanup_expired_blocks',
					array(),
					'hostforge-security'
				);
			}

			if ( ! as_has_scheduled_action( 'hostforge_cleanup_audit_log' ) ) {
				as_schedule_recurring_action(
					time() + 900,
					DAY_IN_SECONDS,
					'hostforge_cleanup_audit_log',
					array(),
					'hostforge-security'
				);
			}
		}

		$this->log_info( 'Security module activated.' );
	}

	/**
	 * Called when the module is deactivated.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'hostforge_cleanup_login_attempts', array(), 'hostforge-security' );
			as_unschedule_all_actions( 'hostforge_cleanup_expired_blocks', array(), 'hostforge-security' );
			as_unschedule_all_actions( 'hostforge_cleanup_audit_log', array(), 'hostforge-security' );
		}

		$this->log_info( 'Security module deactivated.' );
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controller = new Api\HF_REST_Security_Controller();
		$controller->register_routes();
	}

	/**
	 * Register dashboard widget.
	 *
	 * @param array $widgets Existing widgets.
	 * @return array
	 */
	public function register_dashboard_widget( array $widgets ): array {
		$widgets[] = array(
			'id'       => 'hf_security_summary',
			'title'    => __( 'Security', 'hostforge' ),
			'callback' => array( $this, 'render_dashboard_widget' ),
		);

		return $widgets;
	}

	/**
	 * Render the dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		global $wpdb;

		$table_attempts = $wpdb->prefix . 'hf_login_attempts';
		$table_blocks   = $wpdb->prefix . 'hf_ip_blocks';

		// Failed attempts in last 24h.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$failed_24h = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_attempts}
				WHERE status = 'failed' AND created_at > %s",
				gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
			)
		);

		// Active IP blocks.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active_blocks = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_blocks}
			WHERE expires_at IS NULL OR expires_at > NOW()"
		);

		// Successful logins in last 24h.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$success_24h = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_attempts}
				WHERE status = 'success' AND created_at > %s",
				gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
			)
		);

		?>
		<ul class="hf-dashboard-stats">
			<li>
				<strong><?php echo esc_html( number_format_i18n( $failed_24h ) ); ?></strong>
				<?php esc_html_e( 'Failed Logins (24h)', 'hostforge' ); ?>
			</li>
			<li>
				<strong><?php echo esc_html( number_format_i18n( $active_blocks ) ); ?></strong>
				<?php esc_html_e( 'Blocked IPs', 'hostforge' ); ?>
			</li>
			<li>
				<strong><?php echo esc_html( number_format_i18n( $success_24h ) ); ?></strong>
				<?php esc_html_e( 'Successful Logins (24h)', 'hostforge' ); ?>
			</li>
		</ul>
		<?php
	}

	/**
	 * Get admin menu items.
	 *
	 * @return array
	 */
	public function get_admin_menu_items(): array {
		return array(
			array(
				'title'      => __( 'Security', 'hostforge' ),
				'slug'       => 'hostforge-security',
				'capability' => 'manage_hostforge',
				'callback'   => array( $this, 'render_admin_page' ),
			),
		);
	}

	/**
	 * Render admin page (delegated to admin class).
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		// Handled by HF_Security_Admin.
	}

	/**
	 * Cleanup old login attempts (older than 30 days).
	 *
	 * @return void
	 */
	public function cleanup_login_attempts(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'hf_login_attempts';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s",
				gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
			)
		);

		if ( $deleted > 0 ) {
			$this->log_info( sprintf( 'Cleaned %d old login attempts.', $deleted ) );
		}
	}

	/**
	 * Cleanup expired IP blocks.
	 *
	 * @return void
	 */
	public function cleanup_expired_blocks(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'hf_ip_blocks';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table}
				WHERE expires_at IS NOT NULL AND expires_at < %s",
				current_time( 'mysql', true )
			)
		);

		if ( $deleted > 0 ) {
			$this->log_info( sprintf( 'Cleaned %d expired IP blocks.', $deleted ) );
		}
	}

	/**
	 * Cleanup old audit log entries (older than retention period).
	 *
	 * @return void
	 */
	public function cleanup_audit_log(): void {
		global $wpdb;

		$table          = $wpdb->prefix . 'hf_activity_log';
		$settings       = $this->get_security_settings();
		$retention_days = ! empty( $settings['audit_retention_days'] ) ? (int) $settings['audit_retention_days'] : 90;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s",
				gmdate( 'Y-m-d H:i:s', strtotime( sprintf( '-%d days', $retention_days ) ) )
			)
		);

		if ( $deleted > 0 ) {
			$this->log_info( sprintf( 'Cleaned %d old audit log entries.', $deleted ) );
		}
	}

	/**
	 * Get security settings with defaults.
	 *
	 * @return array
	 */
	public function get_security_settings(): array {
		$defaults = array(
			'max_login_attempts'    => 5,
			'lockout_duration'      => 30,
			'lockout_duration_unit' => 'minutes',
			'ip_allowlist'          => '',
			'ip_blocklist'          => '',
			'fraud_enabled'         => 'yes',
			'fraud_block_countries' => '',
			'fraud_block_emails'    => '',
			'captcha_enabled'       => 'no',
			'captcha_provider'      => 'turnstile',
			'captcha_site_key'      => '',
			'captcha_secret_key'    => '',
			'captcha_on_login'      => 'no',
			'captcha_on_register'   => 'yes',
			'captcha_on_checkout'   => 'no',
			'captcha_on_tickets'    => 'yes',
			'audit_enabled'         => 'yes',
			'audit_retention_days'  => 90,
		);

		/**
		 * Filter the default security settings.
		 *
		 * Allows third-party code to modify the default values for all
		 * security settings before they are merged with saved options.
		 *
		 * @since 1.0.0
		 *
		 * @param array $defaults Default security settings key-value pairs.
		 */
		$defaults = apply_filters( 'hostforge_security_settings_defaults', $defaults );

		$saved = get_option( 'hf_security_settings', array() );

		return wp_parse_args( $saved, $defaults );
	}

	/**
	 * Create module database tables.
	 *
	 * @return void
	 */
	private function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql_attempts = "CREATE TABLE {$wpdb->prefix}hf_login_attempts (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ip_address VARCHAR(45) NOT NULL,
			username VARCHAR(100) DEFAULT NULL,
			status VARCHAR(20) NOT NULL,
			user_agent TEXT,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY ip_address (ip_address),
			KEY status (status),
			KEY created_at (created_at),
			KEY ip_created (ip_address, created_at)
		) {$charset_collate};";

		$sql_blocks = "CREATE TABLE {$wpdb->prefix}hf_ip_blocks (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ip_address VARCHAR(45) NOT NULL,
			block_type VARCHAR(20) NOT NULL DEFAULT 'auto',
			reason VARCHAR(255) DEFAULT NULL,
			expires_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY ip_address (ip_address),
			KEY block_type (block_type),
			KEY expires_at (expires_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_attempts );
		dbDelta( $sql_blocks );
	}

	/**
	 * Register scheduled actions.
	 *
	 * @return void
	 */
	public function register_scheduled_actions(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		if ( ! as_has_scheduled_action( 'hostforge_cleanup_login_attempts' ) ) {
			as_schedule_recurring_action(
				time() + 300,
				DAY_IN_SECONDS,
				'hostforge_cleanup_login_attempts',
				array(),
				'hostforge-security'
			);
		}

		if ( ! as_has_scheduled_action( 'hostforge_cleanup_expired_blocks' ) ) {
			as_schedule_recurring_action(
				time() + 600,
				HOUR_IN_SECONDS,
				'hostforge_cleanup_expired_blocks',
				array(),
				'hostforge-security'
			);
		}
	}
}
