<?php
/**
 * Security Admin.
 *
 * Admin screens for security settings, IP management, login attempts
 * and audit log viewer.
 *
 * @package HostForge\Modules\Security\Admin
 */

namespace HostForge\Modules\Security\Admin;

use HostForge\Modules\Security\HF_Security_Module;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Security_Admin
 */
class HF_Security_Admin {

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
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_hf_save_security_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_hf_block_ip', array( $this, 'ajax_block_ip' ) );
		add_action( 'wp_ajax_hf_unblock_ip', array( $this, 'ajax_unblock_ip' ) );
	}

	/**
	 * Add admin menu pages.
	 *
	 * @return void
	 */
	public function add_menu_pages(): void {
		add_submenu_page(
			'hostforge',
			__( 'Security', 'hostforge' ),
			__( 'Security', 'hostforge' ),
			'manage_hostforge',
			'hostforge-security',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue assets for security admin pages.
	 *
	 * @param string $hook_suffix Admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( strpos( $hook_suffix, 'hostforge-security' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'hf-security-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/css/security-admin.css',
			array(),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hf-security-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/js/security-admin.js',
			array(),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hf-security-admin',
			'hfSecurity',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_security_nonce' ),
				'i18n'    => array(
					'saved'          => __( 'Settings saved.', 'hostforge' ),
					'blocked'        => __( 'IP blocked.', 'hostforge' ),
					'unblocked'      => __( 'IP unblocked.', 'hostforge' ),
					'error'          => __( 'An error occurred.', 'hostforge' ),
					'confirmUnblock' => __( 'Are you sure you want to unblock this IP?', 'hostforge' ),
				),
			)
		);
	}

	/**
	 * Render the security admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_hostforge' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = ! empty( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';

		$tabs = array(
			'settings'       => __( 'Settings', 'hostforge' ),
			'ip-blocks'      => __( 'IP Blocks', 'hostforge' ),
			'login-attempts' => __( 'Login Attempts', 'hostforge' ),
			'audit-log'      => __( 'Audit Log', 'hostforge' ),
		);

		switch ( $tab ) {
			case 'ip-blocks':
				include $this->module->get_module_dir() . 'admin/templates/ip-blocks.php';
				break;
			case 'login-attempts':
				include $this->module->get_module_dir() . 'admin/templates/login-attempts.php';
				break;
			case 'audit-log':
				include $this->module->get_module_dir() . 'admin/templates/audit-log.php';
				break;
			default:
				include $this->module->get_module_dir() . 'admin/templates/security-settings.php';
				break;
		}
	}

	/**
	 * AJAX: Save security settings.
	 *
	 * @return void
	 */
	public function ajax_save_settings(): void {
		check_ajax_referer( 'hf_security_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$settings = array(
			'max_login_attempts'    => ! empty( $_POST['max_login_attempts'] ) ? absint( $_POST['max_login_attempts'] ) : 5,
			'lockout_duration'      => ! empty( $_POST['lockout_duration'] ) ? absint( $_POST['lockout_duration'] ) : 30,
			'lockout_duration_unit' => ! empty( $_POST['lockout_duration_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['lockout_duration_unit'] ) ) : 'minutes',
			'ip_allowlist'          => ! empty( $_POST['ip_allowlist'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ip_allowlist'] ) ) : '',
			'ip_blocklist'          => ! empty( $_POST['ip_blocklist'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ip_blocklist'] ) ) : '',
			'fraud_enabled'         => ! empty( $_POST['fraud_enabled'] ) ? 'yes' : 'no',
			'fraud_block_countries' => ! empty( $_POST['fraud_block_countries'] ) ? sanitize_text_field( wp_unslash( $_POST['fraud_block_countries'] ) ) : '',
			'fraud_block_emails'    => ! empty( $_POST['fraud_block_emails'] ) ? sanitize_textarea_field( wp_unslash( $_POST['fraud_block_emails'] ) ) : '',
			'captcha_enabled'       => ! empty( $_POST['captcha_enabled'] ) ? 'yes' : 'no',
			'captcha_provider'      => ! empty( $_POST['captcha_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_provider'] ) ) : 'turnstile',
			'captcha_site_key'      => ! empty( $_POST['captcha_site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_site_key'] ) ) : '',
			'captcha_secret_key'    => ! empty( $_POST['captcha_secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_secret_key'] ) ) : '',
			'captcha_on_login'      => ! empty( $_POST['captcha_on_login'] ) ? 'yes' : 'no',
			'captcha_on_register'   => ! empty( $_POST['captcha_on_register'] ) ? 'yes' : 'no',
			'captcha_on_checkout'   => ! empty( $_POST['captcha_on_checkout'] ) ? 'yes' : 'no',
			'captcha_on_tickets'    => ! empty( $_POST['captcha_on_tickets'] ) ? 'yes' : 'no',
			'audit_enabled'         => ! empty( $_POST['audit_enabled'] ) ? 'yes' : 'no',
			'audit_retention_days'  => ! empty( $_POST['audit_retention_days'] ) ? absint( $_POST['audit_retention_days'] ) : 90,
		);

		update_option( 'hf_security_settings', $settings );

		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'hostforge' ) ) );
	}

	/**
	 * AJAX: Block an IP address.
	 *
	 * @return void
	 */
	public function ajax_block_ip(): void {
		check_ajax_referer( 'hf_security_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$ip     = ! empty( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : '';
		$reason = ! empty( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid IP address.', 'hostforge' ) ) );
		}

		$ip_manager = new \HostForge\Modules\Security\HF_IP_Manager( $this->module );
		$result     = $ip_manager->add_block( $ip, $reason, null, 'manual' );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'IP blocked.', 'hostforge' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to block IP.', 'hostforge' ) ) );
		}
	}

	/**
	 * AJAX: Unblock an IP address.
	 *
	 * @return void
	 */
	public function ajax_unblock_ip(): void {
		check_ajax_referer( 'hf_security_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$ip = ! empty( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : '';

		if ( empty( $ip ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid IP address.', 'hostforge' ) ) );
		}

		$ip_manager = new \HostForge\Modules\Security\HF_IP_Manager( $this->module );
		$result     = $ip_manager->remove_block( $ip );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'IP unblocked.', 'hostforge' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to unblock IP.', 'hostforge' ) ) );
		}
	}
}
