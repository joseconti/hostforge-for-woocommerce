<?php
/**
 * Domain Admin.
 *
 * Handles admin screens for the Domain Manager module:
 * domain list, domain detail, TLD pricing, registrar settings.
 *
 * @package HostForge\Modules\DomainManager\Admin
 */

namespace HostForge\Modules\DomainManager\Admin;

use HostForge\Modules\DomainManager\HF_Domain_Manager_Module;
use HostForge\HF_Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Domain_Admin
 */
class HF_Domain_Admin {

	/**
	 * Module instance.
	 *
	 * @var HF_Domain_Manager_Module
	 */
	private HF_Domain_Manager_Module $module;

	/**
	 * Constructor.
	 *
	 * @param HF_Domain_Manager_Module $module Module instance.
	 */
	public function __construct( HF_Domain_Manager_Module $module ) {
		$this->module = $module;
	}

	/**
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Domain AJAX handlers.
		add_action( 'wp_ajax_hf_sync_domain', array( $this, 'ajax_sync_domain' ) );
		add_action( 'wp_ajax_hf_save_nameservers', array( $this, 'ajax_save_nameservers' ) );
		add_action( 'wp_ajax_hf_toggle_domain_lock', array( $this, 'ajax_toggle_lock' ) );
		add_action( 'wp_ajax_hf_toggle_domain_auto_renew', array( $this, 'ajax_toggle_auto_renew' ) );
		add_action( 'wp_ajax_hf_manual_renew_domain', array( $this, 'ajax_manual_renew' ) );
		add_action( 'wp_ajax_hf_get_epp_code', array( $this, 'ajax_get_epp_code' ) );

		// DNS AJAX handlers.
		add_action( 'wp_ajax_hf_save_dns_record', array( $this, 'ajax_save_dns_record' ) );
		add_action( 'wp_ajax_hf_delete_dns_record', array( $this, 'ajax_delete_dns_record' ) );
		add_action( 'wp_ajax_hf_sync_dns', array( $this, 'ajax_sync_dns' ) );

		// TLD pricing AJAX handlers.
		add_action( 'wp_ajax_hf_save_tld_pricing', array( $this, 'ajax_save_tld_pricing' ) );
		add_action( 'wp_ajax_hf_delete_tld_pricing', array( $this, 'ajax_delete_tld_pricing' ) );
		add_action( 'wp_ajax_hf_import_tld_pricing', array( $this, 'ajax_import_tld_pricing' ) );

		// Registrar settings AJAX handlers.
		add_action( 'wp_ajax_hf_save_registrar_settings', array( $this, 'ajax_save_registrar_settings' ) );
		add_action( 'wp_ajax_hf_test_registrar', array( $this, 'ajax_test_registrar' ) );
	}

	/**
	 * Enqueue assets on domain admin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$screen = get_current_screen();
		if ( ! $screen || ! str_contains( $screen->id, 'hostforge-domains' ) ) {
			return;
		}

		wp_enqueue_style(
			'hostforge-domain-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/css/domain-admin.css',
			array( 'hostforge-admin' ),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hostforge-domain-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/js/domain-admin.js',
			array( 'hostforge-admin' ),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hostforge-domain-admin',
			'hostforgeDomain',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_domain_admin_nonce' ),
				'i18n'    => array(
					'confirmDelete'  => __( 'Are you sure you want to delete this record?', 'hostforge' ),
					'confirmRenew'   => __( 'Are you sure you want to renew this domain?', 'hostforge' ),
					'saving'         => __( 'Saving...', 'hostforge' ),
					'saved'          => __( 'Saved successfully.', 'hostforge' ),
					'syncing'        => __( 'Syncing...', 'hostforge' ),
					'synced'         => __( 'Synced successfully.', 'hostforge' ),
					'testing'        => __( 'Testing...', 'hostforge' ),
					'error'          => __( 'An error occurred.', 'hostforge' ),
					'importing'      => __( 'Importing...', 'hostforge' ),
					'imported'       => __( 'TLD pricing imported successfully.', 'hostforge' ),
				),
			)
		);
	}

	/**
	 * Render the main domains admin page.
	 *
	 * @return void
	 */
	public function render_domains_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ?? 'domains' ) );

		switch ( $tab ) {
			case 'detail':
				$this->render_domain_detail();
				break;
			case 'tld-pricing':
				$this->render_tld_pricing();
				break;
			case 'registrar':
				$this->render_registrar_settings();
				break;
			default:
				$this->render_domain_list();
				break;
		}
	}

	/**
	 * Render the domain list page.
	 *
	 * @return void
	 */
	private function render_domain_list(): void {
		$list_table = new HF_Domain_List_Table();
		$list_table->prepare_items();

		$template = $this->module->get_module_dir() . 'admin/templates/domain-list.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the domain detail page.
	 *
	 * @return void
	 */
	private function render_domain_detail(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$domain_id = absint( $_GET['domain_id'] ?? 0 );

		if ( ! $domain_id ) {
			wp_die( esc_html__( 'Invalid domain.', 'hostforge' ) );
		}

		$domain = get_post( $domain_id );

		if ( ! $domain || 'hf_domain' !== $domain->post_type ) {
			wp_die( esc_html__( 'Domain not found.', 'hostforge' ) );
		}

		$meta = array();
		foreach ( array_keys( HF_Domain_Manager_Module::get_domain_meta_keys() ) as $key ) {
			$meta[ $key ] = get_post_meta( $domain_id, $key, true );
		}

		// Get DNS records.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$dns_records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hf_dns_records WHERE domain_id = %d ORDER BY record_type, host",
				$domain_id
			)
		);

		$template = $this->module->get_module_dir() . 'admin/templates/domain-detail.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the TLD pricing page.
	 *
	 * @return void
	 */
	private function render_tld_pricing(): void {
		global $wpdb;

		$registrar_id = get_option( 'hf_active_registrar', 'namecheap' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tld_pricing = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hf_tld_pricing WHERE registrar_id = %s ORDER BY tld ASC",
				$registrar_id
			)
		);

		$template = $this->module->get_module_dir() . 'admin/templates/tld-pricing.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the registrar settings page.
	 *
	 * @return void
	 */
	private function render_registrar_settings(): void {
		$template = $this->module->get_module_dir() . 'admin/templates/registrar-settings.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * AJAX: Sync domain with registrar.
	 *
	 * @return void
	 */
	public function ajax_sync_domain(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_id   = absint( $_POST['domain_id'] ?? 0 );
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		if ( ! $domain_id || empty( $domain_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid domain.', 'hostforge' ) ) );
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		// Sync nameservers.
		$ns_result = $registrar->get_nameservers( $domain_name );
		if ( $ns_result['success'] ) {
			update_post_meta( $domain_id, '_hf_nameservers', wp_json_encode( $ns_result['nameservers'] ) );
		}

		// Sync lock status.
		$lock_result = $registrar->get_lock( $domain_name );
		if ( $lock_result['success'] ) {
			update_post_meta( $domain_id, '_hf_locked', $lock_result['locked'] ? 'yes' : 'no' );
		}

		// Sync WHOIS.
		$whois_result = $registrar->get_whois( $domain_name );
		if ( $whois_result['success'] ) {
			update_post_meta( $domain_id, '_hf_whois_cache', wp_json_encode( $whois_result['whois'] ) );
			update_post_meta( $domain_id, '_hf_whois_cache_time', time() );
		}

		update_post_meta( $domain_id, '_hf_last_synced', current_time( 'mysql' ) );

		wp_send_json_success( array( 'message' => __( 'Domain synced successfully.', 'hostforge' ) ) );
	}

	/**
	 * AJAX: Save nameservers.
	 *
	 * @return void
	 */
	public function ajax_save_nameservers(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_id   = absint( $_POST['domain_id'] ?? 0 );
		$nameservers = array_map( 'sanitize_text_field', wp_unslash( $_POST['nameservers'] ?? array() ) );
		$nameservers = array_filter( $nameservers );
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		if ( ! $domain_id || empty( $domain_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid domain.', 'hostforge' ) ) );
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		$result = $registrar->set_nameservers( $domain_name, $nameservers );

		if ( $result['success'] ) {
			update_post_meta( $domain_id, '_hf_nameservers', wp_json_encode( $nameservers ) );
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX: Toggle domain lock.
	 *
	 * @return void
	 */
	public function ajax_toggle_lock(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_id   = absint( $_POST['domain_id'] ?? 0 );
		$lock        = 'yes' === sanitize_text_field( wp_unslash( $_POST['lock'] ?? 'no' ) );
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		if ( ! $domain_id || empty( $domain_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid domain.', 'hostforge' ) ) );
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		$result = $registrar->toggle_lock( $domain_name, $lock );

		if ( $result['success'] ) {
			update_post_meta( $domain_id, '_hf_locked', $lock ? 'yes' : 'no' );
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX: Toggle auto-renew.
	 *
	 * @return void
	 */
	public function ajax_toggle_auto_renew(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_id  = absint( $_POST['domain_id'] ?? 0 );
		$auto_renew = 'yes' === sanitize_text_field( wp_unslash( $_POST['auto_renew'] ?? 'no' ) );

		if ( ! $domain_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid domain.', 'hostforge' ) ) );
		}

		update_post_meta( $domain_id, '_hf_auto_renew', $auto_renew ? 'yes' : 'no' );

		wp_send_json_success(
			array(
				'message' => $auto_renew
					? __( 'Auto-renewal enabled.', 'hostforge' )
					: __( 'Auto-renewal disabled.', 'hostforge' ),
			)
		);
	}

	/**
	 * AJAX: Manual domain renewal.
	 *
	 * @return void
	 */
	public function ajax_manual_renew(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_id = absint( $_POST['domain_id'] ?? 0 );

		if ( ! $domain_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid domain.', 'hostforge' ) ) );
		}

		as_enqueue_async_action(
			'hostforge_renew_domain',
			array( $domain_id ),
			'hostforge-domain-manager'
		);

		wp_send_json_success( array( 'message' => __( 'Domain renewal queued.', 'hostforge' ) ) );
	}

	/**
	 * AJAX: Get EPP code.
	 *
	 * @return void
	 */
	public function ajax_get_epp_code(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_id   = absint( $_POST['domain_id'] ?? 0 );
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		if ( ! $domain_id || empty( $domain_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid domain.', 'hostforge' ) ) );
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		$result = $registrar->get_epp_code( $domain_name );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['epp_code'] ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to retrieve EPP code.', 'hostforge' ) ) );
		}
	}

	/**
	 * AJAX: Save DNS record.
	 *
	 * @return void
	 */
	public function ajax_save_dns_record(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_id   = absint( $_POST['domain_id'] ?? 0 );
		$record_id   = absint( $_POST['record_id'] ?? 0 );
		$record_type = sanitize_text_field( wp_unslash( $_POST['record_type'] ?? 'A' ) );
		$host        = sanitize_text_field( wp_unslash( $_POST['host'] ?? '@' ) );
		$value       = sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) );
		$ttl         = absint( $_POST['ttl'] ?? 3600 );
		$priority    = absint( $_POST['priority'] ?? 0 );

		if ( ! $domain_id || empty( $value ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid record data.', 'hostforge' ) ) );
		}

		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );
		$registrar   = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		$record_data = array(
			'type'     => $record_type,
			'host'     => $host,
			'value'    => $value,
			'ttl'      => $ttl,
			'priority' => $priority,
		);

		if ( $record_id ) {
			$result = $registrar->update_dns_record( $domain_name, $record_id, $record_data );
		} else {
			$result = $registrar->add_dns_record( $domain_name, $record_data );
		}

		if ( $result['success'] ) {
			// Sync DNS records from registrar to local table.
			$this->sync_dns_to_local( $domain_id, $domain_name, $registrar );
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX: Delete DNS record.
	 *
	 * @return void
	 */
	public function ajax_delete_dns_record(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_id   = absint( $_POST['domain_id'] ?? 0 );
		$record_id   = absint( $_POST['record_id'] ?? 0 );
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		if ( ! $domain_id || ! $record_id || empty( $domain_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'hostforge' ) ) );
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		$result = $registrar->delete_dns_record( $domain_name, $record_id );

		if ( $result['success'] ) {
			$this->sync_dns_to_local( $domain_id, $domain_name, $registrar );
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX: Sync DNS records from registrar.
	 *
	 * @return void
	 */
	public function ajax_sync_dns(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_id   = absint( $_POST['domain_id'] ?? 0 );
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		if ( ! $domain_id || empty( $domain_name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid domain.', 'hostforge' ) ) );
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		$this->sync_dns_to_local( $domain_id, $domain_name, $registrar );

		wp_send_json_success( array( 'message' => __( 'DNS records synced.', 'hostforge' ) ) );
	}

	/**
	 * Sync DNS records from registrar to local database.
	 *
	 * @param int                                  $domain_id   Domain post ID.
	 * @param string                               $domain_name Domain name.
	 * @param \HostForge\Interfaces\HF_Registrar   $registrar   Registrar instance.
	 * @return void
	 */
	private function sync_dns_to_local( int $domain_id, string $domain_name, $registrar ): void {
		global $wpdb;

		$result = $registrar->get_dns_records( $domain_name );

		if ( ! $result['success'] ) {
			return;
		}

		$now = current_time( 'mysql' );

		// Remove existing records.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->prefix . 'hf_dns_records',
			array( 'domain_id' => $domain_id ),
			array( '%d' )
		);

		// Insert fresh records.
		foreach ( $result['records'] as $record ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->prefix . 'hf_dns_records',
				array(
					'domain_id'          => $domain_id,
					'record_type'        => sanitize_text_field( $record['type'] ?? 'A' ),
					'host'               => sanitize_text_field( $record['host'] ?? '@' ),
					'value'              => sanitize_text_field( $record['value'] ?? '' ),
					'ttl'                => absint( $record['ttl'] ?? 3600 ),
					'priority'           => absint( $record['priority'] ?? 0 ),
					'registrar_record_id' => sanitize_text_field( $record['id'] ?? '' ),
					'synced_at'          => $now,
					'created_at'         => $now,
					'updated_at'         => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * AJAX: Save TLD pricing.
	 *
	 * @return void
	 */
	public function ajax_save_tld_pricing(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		global $wpdb;

		$id             = absint( $_POST['id'] ?? 0 );
		$tld            = sanitize_text_field( wp_unslash( $_POST['tld'] ?? '' ) );
		$register_price = floatval( $_POST['register_price'] ?? 0 );
		$renew_price    = floatval( $_POST['renew_price'] ?? 0 );
		$transfer_price = floatval( $_POST['transfer_price'] ?? 0 );
		$currency       = sanitize_text_field( wp_unslash( $_POST['currency'] ?? 'USD' ) );
		$is_active      = absint( $_POST['is_active'] ?? 1 );
		$registrar_id   = get_option( 'hf_active_registrar', 'namecheap' );

		if ( empty( $tld ) ) {
			wp_send_json_error( array( 'message' => __( 'TLD is required.', 'hostforge' ) ) );
		}

		// Remove leading dot.
		$tld = ltrim( $tld, '.' );

		$data = array(
			'tld'            => $tld,
			'registrar_id'   => $registrar_id,
			'register_price' => $register_price,
			'renew_price'    => $renew_price,
			'transfer_price' => $transfer_price,
			'currency'       => $currency,
			'is_active'      => $is_active,
			'updated_at'     => current_time( 'mysql' ),
		);

		$format = array( '%s', '%s', '%f', '%f', '%f', '%s', '%d', '%s' );

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->prefix . 'hf_tld_pricing',
				$data,
				array( 'id' => $id ),
				$format,
				array( '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->prefix . 'hf_tld_pricing',
				$data,
				$format
			);
		}

		wp_send_json_success( array( 'message' => __( 'TLD pricing saved.', 'hostforge' ) ) );
	}

	/**
	 * AJAX: Delete TLD pricing.
	 *
	 * @return void
	 */
	public function ajax_delete_tld_pricing(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		global $wpdb;

		$id = absint( $_POST['id'] ?? 0 );

		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid TLD.', 'hostforge' ) ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->prefix . 'hf_tld_pricing',
			array( 'id' => $id ),
			array( '%d' )
		);

		wp_send_json_success( array( 'message' => __( 'TLD pricing deleted.', 'hostforge' ) ) );
	}

	/**
	 * AJAX: Import TLD pricing from registrar.
	 *
	 * @return void
	 */
	public function ajax_import_tld_pricing(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		// For now, provide a set of common TLDs with default pricing.
		// Future: fetch from registrar API (namecheap.users.getPricing).
		$common_tlds = array(
			'com'  => array( 'register' => 10.98, 'renew' => 12.98, 'transfer' => 10.98 ),
			'net'  => array( 'register' => 12.98, 'renew' => 14.98, 'transfer' => 12.98 ),
			'org'  => array( 'register' => 11.98, 'renew' => 14.98, 'transfer' => 11.98 ),
			'info' => array( 'register' => 4.98,  'renew' => 18.98, 'transfer' => 12.98 ),
			'biz'  => array( 'register' => 5.98,  'renew' => 15.98, 'transfer' => 12.98 ),
			'io'   => array( 'register' => 32.98, 'renew' => 32.98, 'transfer' => 32.98 ),
			'co'   => array( 'register' => 12.98, 'renew' => 28.98, 'transfer' => 12.98 ),
			'dev'  => array( 'register' => 14.98, 'renew' => 14.98, 'transfer' => 14.98 ),
			'app'  => array( 'register' => 16.98, 'renew' => 16.98, 'transfer' => 16.98 ),
			'xyz'  => array( 'register' => 1.98,  'renew' => 12.98, 'transfer' => 9.98 ),
		);

		global $wpdb;

		$registrar_id = get_option( 'hf_active_registrar', 'namecheap' );
		$now          = current_time( 'mysql' );
		$imported     = 0;

		foreach ( $common_tlds as $tld => $prices ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}hf_tld_pricing WHERE tld = %s AND registrar_id = %s",
					$tld,
					$registrar_id
				)
			);

			if ( $exists ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->prefix . 'hf_tld_pricing',
				array(
					'tld'            => $tld,
					'registrar_id'   => $registrar_id,
					'register_price' => $prices['register'],
					'renew_price'    => $prices['renew'],
					'transfer_price' => $prices['transfer'],
					'currency'       => 'USD',
					'is_active'      => 1,
					'updated_at'     => $now,
				),
				array( '%s', '%s', '%f', '%f', '%f', '%s', '%d', '%s' )
			);

			++$imported;
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of TLDs imported */
					__( '%d TLD(s) imported.', 'hostforge' ),
					$imported
				),
			)
		);
	}

	/**
	 * AJAX: Save registrar settings.
	 *
	 * @return void
	 */
	public function ajax_save_registrar_settings(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$settings = array(
			'hf_active_registrar'             => sanitize_text_field( wp_unslash( $_POST['active_registrar'] ?? 'namecheap' ) ),
			'hf_namecheap_api_user'           => sanitize_text_field( wp_unslash( $_POST['api_user'] ?? '' ) ),
			'hf_namecheap_username'           => sanitize_text_field( wp_unslash( $_POST['username'] ?? '' ) ),
			'hf_namecheap_client_ip'          => sanitize_text_field( wp_unslash( $_POST['client_ip'] ?? '' ) ),
			'hf_namecheap_sandbox'            => sanitize_text_field( wp_unslash( $_POST['sandbox'] ?? 'no' ) ),
			'hf_domain_auto_register'         => sanitize_text_field( wp_unslash( $_POST['auto_register'] ?? 'yes' ) ),
			'hf_domain_auto_renew_days'       => absint( $_POST['auto_renew_days'] ?? 14 ),
			'hf_domain_expiry_reminder_days'  => sanitize_text_field( wp_unslash( $_POST['expiry_reminder_days'] ?? '30,14,7,1' ) ),
			'hf_domain_default_nameservers'   => sanitize_textarea_field( wp_unslash( $_POST['default_nameservers'] ?? '' ) ),
		);

		foreach ( $settings as $key => $value ) {
			update_option( $key, $value );
		}

		// Handle API key separately (encrypt).
		$api_key = sanitize_text_field( wp_unslash( $_POST['api_key'] ?? '' ) );
		if ( ! empty( $api_key ) && '********' !== $api_key ) {
			update_option( 'hf_namecheap_api_key', HF_Encryption::encrypt( $api_key ) );
		}

		wp_send_json_success( array( 'message' => __( 'Registrar settings saved.', 'hostforge' ) ) );
	}

	/**
	 * AJAX: Test registrar connection.
	 *
	 * @return void
	 */
	public function ajax_test_registrar(): void {
		check_ajax_referer( 'hf_domain_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_domains' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		$result = $registrar->check_availability( 'test-connection-hostforge.com' );

		// If we get any response (available or not), connection works.
		if ( isset( $result['domain'] ) ) {
			wp_send_json_success( array( 'message' => __( 'Connection successful! Registrar API is working.', 'hostforge' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Connection failed. Please check your credentials.', 'hostforge' ) ) );
		}
	}
}
