<?php
/**
 * Domain Frontend.
 *
 * Handles the WooCommerce My Account "My Domains" endpoint,
 * including domain list, detail view, and frontend AJAX actions.
 *
 * @package HostForge\Modules\DomainManager
 */

namespace HostForge\Modules\DomainManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Domain_Frontend
 */
class HF_Domain_Frontend {

	/**
	 * Endpoint slug.
	 */
	private const ENDPOINT = 'my-domains';

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ), 40 );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );
		add_filter( 'the_title', array( $this, 'endpoint_title' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Frontend AJAX handlers.
		add_action( 'wp_ajax_hf_frontend_toggle_domain_auto_renew', array( $this, 'ajax_toggle_auto_renew' ) );
		add_action( 'wp_ajax_hf_frontend_save_nameservers', array( $this, 'ajax_save_nameservers' ) );
		add_action( 'wp_ajax_hf_frontend_get_dns_records', array( $this, 'ajax_get_dns_records' ) );
		add_action( 'wp_ajax_hf_frontend_save_dns_record', array( $this, 'ajax_save_dns_record' ) );
		add_action( 'wp_ajax_hf_frontend_delete_dns_record', array( $this, 'ajax_delete_dns_record' ) );
		add_action( 'wp_ajax_hf_frontend_request_epp_code', array( $this, 'ajax_request_epp_code' ) );
	}

	/**
	 * Register the rewrite endpoint.
	 *
	 * @return void
	 */
	public function register_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add menu item to My Account.
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public function add_menu_item( array $items ): array {
		$new_items = array();

		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) {
				$new_items[ self::ENDPOINT ] = __( 'My Domains', 'hostforge' );
			}
			$new_items[ $key ] = $label;
		}

		return $new_items;
	}

	/**
	 * Set the endpoint page title.
	 *
	 * @param string $title Page title.
	 * @param int    $id    Post ID.
	 * @return string
	 */
	public function endpoint_title( string $title, int $id ): string {
		global $wp_query;

		if ( is_main_query() && in_the_loop() && is_account_page() && isset( $wp_query->query_vars[ self::ENDPOINT ] ) ) {
			return __( 'My Domains', 'hostforge' );
		}

		return $title;
	}

	/**
	 * Render the endpoint content.
	 *
	 * @param string $value Endpoint value (domain ID or empty for list).
	 * @return void
	 */
	public function render_endpoint( string $value = '' ): void {
		$domain_id = absint( $value );

		if ( $domain_id ) {
			$this->render_domain_detail( $domain_id );
		} else {
			$this->render_domain_list();
		}
	}

	/**
	 * Render the domain list.
	 *
	 * @return void
	 */
	private function render_domain_list(): void {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$query_args = array(
			'post_type'      => 'hf_domain',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_hf_user_id',
					'value' => $user_id,
					'type'  => 'NUMERIC',
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		/**
		 * Filters the query arguments for listing user domains on the frontend.
		 *
		 * @since 1.0.0
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param int   $user_id    The current user ID.
		 */
		$query_args = apply_filters( 'hostforge_domain_list_query', $query_args, $user_id );

		$domains = get_posts( $query_args );

		$template = hf_locate_template( 'frontend/domain-list.php' );
		if ( $template ) {
			include $template;
		}
	}

	/**
	 * Render the domain detail.
	 *
	 * @param int $domain_id Domain post ID.
	 * @return void
	 */
	private function render_domain_detail( int $domain_id ): void {
		$user_id = get_current_user_id();

		if ( ! $user_id || ! $this->verify_ownership( $domain_id, $user_id ) ) {
			echo '<p>' . esc_html__( 'Domain not found.', 'hostforge' ) . '</p>';
			return;
		}

		$domain = get_post( $domain_id );
		$meta   = array();

		foreach ( array_keys( HF_Domain_Manager_Module::get_domain_meta_keys() ) as $key ) {
			$meta[ $key ] = get_post_meta( $domain_id, $key, true );
		}

		$ns_decoded  = json_decode( $meta['_hf_nameservers'] ?? '[]', true );
		$nameservers = ! empty( $ns_decoded ) ? $ns_decoded : array();

		// Get DNS records.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$dns_records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hf_dns_records WHERE domain_id = %d ORDER BY record_type, host",
				$domain_id
			)
		);

		$detail_data = array(
			'domain'      => $domain,
			'meta'        => $meta,
			'nameservers' => $nameservers,
			'dns_records' => $dns_records,
		);

		/**
		 * Filters domain detail data before rendering on the frontend.
		 *
		 * @since 1.0.0
		 *
		 * @param array $detail_data Domain detail data (domain, meta, nameservers, dns_records).
		 * @param int   $domain_id   The domain post ID.
		 */
		$detail_data = apply_filters( 'hostforge_domain_detail_data', $detail_data, $domain_id );

		$domain      = $detail_data['domain'];
		$meta        = $detail_data['meta'];
		$nameservers = $detail_data['nameservers'];
		$dns_records = $detail_data['dns_records'];

		$domain_actions = array(
			'auto_renew'  => __( 'Toggle Auto-Renew', 'hostforge' ),
			'nameservers' => __( 'Manage Nameservers', 'hostforge' ),
			'dns'         => __( 'Manage DNS Records', 'hostforge' ),
			'epp_code'    => __( 'Request EPP Code', 'hostforge' ),
		);

		/**
		 * Filters the available domain actions on the frontend detail page.
		 *
		 * @since 1.0.0
		 *
		 * @param array $domain_actions Action slug => label pairs.
		 * @param int   $domain_id      The domain post ID.
		 * @param array $meta           Domain meta data.
		 */
		$domain_actions = apply_filters( 'hostforge_domain_actions', $domain_actions, $domain_id, $meta );

		$template = hf_locate_template( 'frontend/domain-detail.php' );
		if ( $template ) {
			include $template;
		}
	}

	/**
	 * Verify domain ownership.
	 *
	 * @param int $domain_id Domain post ID.
	 * @param int $user_id   User ID.
	 * @return bool
	 */
	private function verify_ownership( int $domain_id, int $user_id ): bool {
		$domain = get_post( $domain_id );

		if ( ! $domain || 'hf_domain' !== $domain->post_type ) {
			return false;
		}

		$domain_user = absint( get_post_meta( $domain_id, '_hf_user_id', true ) );
		return $domain_user === $user_id;
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! is_account_page() ) {
			return;
		}

		wp_enqueue_style(
			'hf-domain-frontend',
			HOSTFORGE_PLUGIN_URL . 'assets/css/domain-frontend.css',
			array(),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hf-domain-frontend',
			HOSTFORGE_PLUGIN_URL . 'assets/js/domain-frontend.js',
			array(),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hf-domain-frontend',
			'hfDomainFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_domain_frontend_nonce' ),
				'i18n'    => array(
					'saving'        => __( 'Saving...', 'hostforge' ),
					'saved'         => __( 'Saved successfully.', 'hostforge' ),
					'error'         => __( 'An error occurred.', 'hostforge' ),
					'confirmDelete' => __( 'Are you sure you want to delete this record?', 'hostforge' ),
				),
			)
		);
	}

	/**
	 * AJAX: Toggle auto-renew.
	 *
	 * @return void
	 */
	public function ajax_toggle_auto_renew(): void {
		check_ajax_referer( 'hf_domain_frontend_nonce', 'nonce' );

		$user_id   = get_current_user_id();
		$domain_id = absint( $_POST['domain_id'] ?? 0 );

		if ( ! $user_id || ! $this->verify_ownership( $domain_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$auto_renew = 'yes' === sanitize_text_field( wp_unslash( $_POST['auto_renew'] ?? 'no' ) );
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
	 * AJAX: Save nameservers.
	 *
	 * @return void
	 */
	public function ajax_save_nameservers(): void {
		check_ajax_referer( 'hf_domain_frontend_nonce', 'nonce' );

		$user_id   = get_current_user_id();
		$domain_id = absint( $_POST['domain_id'] ?? 0 );

		if ( ! $user_id || ! $this->verify_ownership( $domain_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$nameservers = array_map( 'sanitize_text_field', wp_unslash( $_POST['nameservers'] ?? array() ) );
		$nameservers = array_filter( $nameservers );
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$module         = $module_manager->get_module( 'domain-manager' );

		if ( ! $module ) {
			wp_send_json_error( array( 'message' => __( 'Module not active.', 'hostforge' ) ) );
		}

		$registrar = $module->get_registrar();

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
	 * AJAX: Get DNS records.
	 *
	 * @return void
	 */
	public function ajax_get_dns_records(): void {
		check_ajax_referer( 'hf_domain_frontend_nonce', 'nonce' );

		$user_id   = get_current_user_id();
		$domain_id = absint( $_POST['domain_id'] ?? 0 );

		if ( ! $user_id || ! $this->verify_ownership( $domain_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$records = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, record_type, host, value, ttl, priority FROM {$wpdb->prefix}hf_dns_records WHERE domain_id = %d ORDER BY record_type, host",
				$domain_id
			)
		);

		wp_send_json_success( array( 'records' => $records ) );
	}

	/**
	 * AJAX: Save DNS record.
	 *
	 * @return void
	 */
	public function ajax_save_dns_record(): void {
		check_ajax_referer( 'hf_domain_frontend_nonce', 'nonce' );

		$user_id   = get_current_user_id();
		$domain_id = absint( $_POST['domain_id'] ?? 0 );

		if ( ! $user_id || ! $this->verify_ownership( $domain_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );
		$record_id   = absint( $_POST['record_id'] ?? 0 );

		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$module         = $module_manager->get_module( 'domain-manager' );
		$registrar      = $module ? $module->get_registrar() : null;

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		$record_data = array(
			'type'     => sanitize_text_field( wp_unslash( $_POST['record_type'] ?? 'A' ) ),
			'host'     => sanitize_text_field( wp_unslash( $_POST['host'] ?? '@' ) ),
			'value'    => sanitize_text_field( wp_unslash( $_POST['value'] ?? '' ) ),
			'ttl'      => absint( $_POST['ttl'] ?? 3600 ),
			'priority' => absint( $_POST['priority'] ?? 0 ),
		);

		/**
		 * Filters DNS record data before saving via the frontend.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $record_data DNS record data (type, host, value, ttl, priority).
		 * @param int    $domain_id   The domain post ID.
		 * @param int    $record_id   The record ID (0 for new records).
		 */
		$record_data = apply_filters( 'hostforge_dns_record_data', $record_data, $domain_id, $record_id );

		if ( $record_id ) {
			$result = $registrar->update_dns_record( $domain_name, $record_id, $record_data );
		} else {
			$result = $registrar->add_dns_record( $domain_name, $record_data );
		}

		if ( $result['success'] ) {
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
		check_ajax_referer( 'hf_domain_frontend_nonce', 'nonce' );

		$user_id   = get_current_user_id();
		$domain_id = absint( $_POST['domain_id'] ?? 0 );

		if ( ! $user_id || ! $this->verify_ownership( $domain_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );
		$record_id   = absint( $_POST['record_id'] ?? 0 );

		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$module         = $module_manager->get_module( 'domain-manager' );
		$registrar      = $module ? $module->get_registrar() : null;

		if ( ! $registrar ) {
			wp_send_json_error( array( 'message' => __( 'No registrar configured.', 'hostforge' ) ) );
		}

		$result = $registrar->delete_dns_record( $domain_name, $record_id );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX: Request EPP code.
	 *
	 * @return void
	 */
	public function ajax_request_epp_code(): void {
		check_ajax_referer( 'hf_domain_frontend_nonce', 'nonce' );

		$user_id   = get_current_user_id();
		$domain_id = absint( $_POST['domain_id'] ?? 0 );

		if ( ! $user_id || ! $this->verify_ownership( $domain_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$module         = $module_manager->get_module( 'domain-manager' );
		$registrar      = $module ? $module->get_registrar() : null;

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
}
