<?php
/**
 * Domain Search.
 *
 * Provides AJAX domain availability search with rate limiting
 * and pricing information from TLD table.
 *
 * @package HostForge\Modules\DomainManager
 */

namespace HostForge\Modules\DomainManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Domain_Search
 */
class HF_Domain_Search {

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
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_ajax_hf_domain_search', array( $this, 'ajax_search_domain' ) );
		add_action( 'wp_ajax_nopriv_hf_domain_search', array( $this, 'ajax_search_domain' ) );
		add_action( 'wp_ajax_hf_domain_check', array( $this, 'ajax_check_domain' ) );
		add_action( 'wp_ajax_nopriv_hf_domain_check', array( $this, 'ajax_check_domain' ) );
	}

	/**
	 * AJAX: Search domain availability across multiple TLDs.
	 *
	 * @return void
	 */
	public function ajax_search_domain(): void {
		check_ajax_referer( 'hf_domain_search_nonce', 'nonce' );

		// Rate limiting: 10 searches per minute per IP.
		$ip            = $this->get_client_ip();
		$rate_key      = 'hf_domain_search_rate_' . md5( $ip );
		$current_count = (int) get_transient( $rate_key );

		if ( $current_count >= 10 ) {
			wp_send_json_error(
				array( 'message' => __( 'Too many search requests. Please wait a moment.', 'hostforge' ) ),
				429
			);
		}

		set_transient( $rate_key, $current_count + 1, MINUTE_IN_SECONDS );

		$keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ?? '' ) );

		if ( empty( $keyword ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Please enter a domain name to search.', 'hostforge' ) )
			);
		}

		// Clean keyword: remove spaces, special chars, extract SLD if full domain given.
		$keyword = strtolower( preg_replace( '/[^a-z0-9-]/', '', $keyword ) );

		// If keyword contains a dot, treat as full domain and also search TLDs.
		$search_keyword = $keyword;
		$parts          = explode( '.', $keyword, 2 );

		if ( count( $parts ) > 1 ) {
			$search_keyword = $parts[0];
		}

		// Get active TLDs from pricing table.
		$tlds = $this->get_active_tlds();

		if ( empty( $tlds ) ) {
			// Fallback TLDs if no pricing table configured.
			$tlds = array( 'com', 'net', 'org', 'info', 'biz' );
		}

		// Build domain list.
		$domains = array();
		foreach ( $tlds as $tld ) {
			$domains[] = $search_keyword . '.' . $tld;
		}

		// Check availability via registrar.
		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error(
				array( 'message' => __( 'No registrar configured. Please configure a registrar in settings.', 'hostforge' ) )
			);
		}

		$availability = $registrar->check_availability_bulk( $domains );

		// Merge with pricing data.
		$results = $this->merge_with_pricing( $availability );

		wp_send_json_success(
			array( 'results' => $results )
		);
	}

	/**
	 * AJAX: Check a single domain availability.
	 *
	 * @return void
	 */
	public function ajax_check_domain(): void {
		check_ajax_referer( 'hf_domain_search_nonce', 'nonce' );

		$domain = sanitize_text_field( wp_unslash( $_POST['domain'] ?? '' ) );

		if ( empty( $domain ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Domain name is required.', 'hostforge' ) )
			);
		}

		$domain = strtolower( $domain );

		if ( ! preg_match( '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?\.[a-z]{2,}$/', $domain ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid domain name format.', 'hostforge' ) )
			);
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			wp_send_json_error(
				array( 'message' => __( 'No registrar configured.', 'hostforge' ) )
			);
		}

		$result = $registrar->check_availability( $domain );

		// Add pricing.
		$tld   = substr( $domain, strpos( $domain, '.' ) + 1 );
		$price = $this->get_tld_pricing( $tld );

		$result['register_price'] = $price['register_price'] ?? 0;
		$result['renew_price']    = $price['renew_price'] ?? 0;
		$result['currency']       = $price['currency'] ?? 'USD';

		wp_send_json_success( $result );
	}

	/**
	 * Get active TLDs from the pricing table.
	 *
	 * @return array<string>
	 */
	private function get_active_tlds(): array {
		global $wpdb;

		$registrar_id = get_option( 'hf_active_registrar', 'namecheap' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tlds = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT tld FROM {$wpdb->prefix}hf_tld_pricing
				WHERE registrar_id = %s AND is_active = 1
				ORDER BY tld ASC",
				$registrar_id
			)
		);

		return ! empty( $tlds ) ? $tlds : array();
	}

	/**
	 * Get pricing for a specific TLD.
	 *
	 * @param string $tld TLD (e.g. 'com').
	 * @return array
	 */
	private function get_tld_pricing( string $tld ): array {
		global $wpdb;

		$registrar_id = get_option( 'hf_active_registrar', 'namecheap' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT register_price, renew_price, transfer_price, currency
				FROM {$wpdb->prefix}hf_tld_pricing
				WHERE tld = %s AND registrar_id = %s
				LIMIT 1",
				$tld,
				$registrar_id
			),
			ARRAY_A
		);

		return ! empty( $row ) ? $row : array();
	}

	/**
	 * Merge availability results with pricing data.
	 *
	 * @param array $availability Availability results from registrar.
	 * @return array
	 */
	private function merge_with_pricing( array $availability ): array {
		$results = array();

		foreach ( $availability as $domain => $data ) {
			$tld   = substr( $domain, strpos( $domain, '.' ) + 1 );
			$price = $this->get_tld_pricing( $tld );

			$results[] = array(
				'domain'         => $data['domain'],
				'available'      => $data['available'],
				'premium'        => $data['premium'] ?? false,
				'register_price' => $price['register_price'] ?? 0,
				'renew_price'    => $price['renew_price'] ?? 0,
				'transfer_price' => $price['transfer_price'] ?? 0,
				'currency'       => $price['currency'] ?? 'USD',
			);
		}

		// Sort: available first, then by TLD popularity.
		usort(
			$results,
			function ( $a, $b ) {
				if ( $a['available'] !== $b['available'] ) {
					return $a['available'] ? -1 : 1;
				}
				return 0;
			}
		);

		return $results;
	}

	/**
	 * Get the client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}
}
