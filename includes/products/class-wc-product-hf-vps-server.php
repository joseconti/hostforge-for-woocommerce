<?php
/**
 * VPS / Cloud Server Product Type.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Product_HF_VPS_Server
 */
class WC_Product_HF_VPS_Server extends \WC_Product {

	/**
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'hf_vps_server';

	/**
	 * Get product type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'hf_vps_server';
	}

	/*
	|--------------------------------------------------------------------------
	| VPS Meta Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get CPU cores.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_cpu_cores( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_cpu_cores', true );
	}

	/**
	 * Get RAM in MB.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_ram( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_ram', true );
	}

	/**
	 * Get disk space in GB.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_disk( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_disk', true );
	}

	/**
	 * Get disk type (SSD, NVMe, HDD).
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_disk_type( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_disk_type', true );
	}

	/**
	 * Get bandwidth in GB (0 = unlimited).
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_bandwidth( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_bandwidth', true );
	}

	/**
	 * Get number of IPv4 addresses.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_ipv4_count( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_ipv4_count', true );
	}

	/**
	 * Get number of IPv6 addresses.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_ipv6_count( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_ipv6_count', true );
	}

	/**
	 * Get available OS choices (JSON-encoded array).
	 *
	 * @param string $context View or edit context.
	 * @return array<string>
	 */
	public function get_hf_os_choices( string $context = 'view' ): array {
		$choices = $this->get_meta( '_hf_os_choices', true );
		if ( is_string( $choices ) && ! empty( $choices ) ) {
			$decoded = json_decode( $choices, true );
			return is_array( $decoded ) ? $decoded : array();
		}
		return is_array( $choices ) ? $choices : array();
	}

	/**
	 * Whether hostname is required at checkout.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_require_hostname( string $context = 'view' ): bool {
		$value = $this->get_meta( '_hf_require_hostname', true );
		return '' === $value || 'yes' === $value;
	}

	/**
	 * Whether root password is set at checkout.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_require_root_password( string $context = 'view' ): bool {
		return 'yes' === $this->get_meta( '_hf_require_root_password', true );
	}

	/**
	 * Get server group.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_server_group( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_server_group', true );
	}

	/**
	 * Get setup fee.
	 *
	 * @param string $context View or edit context.
	 * @return float
	 */
	public function get_hf_setup_fee( string $context = 'view' ): float {
		return (float) $this->get_meta( '_hf_setup_fee', true );
	}

	/*
	|--------------------------------------------------------------------------
	| VPS Meta Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set CPU cores.
	 *
	 * @param int $value CPU cores.
	 */
	public function set_hf_cpu_cores( int $value ): void {
		$this->update_meta_data( '_hf_cpu_cores', absint( $value ) );
	}

	/**
	 * Set RAM.
	 *
	 * @param int $value RAM in MB.
	 */
	public function set_hf_ram( int $value ): void {
		$this->update_meta_data( '_hf_ram', absint( $value ) );
	}

	/**
	 * Set disk space.
	 *
	 * @param int $value Disk in GB.
	 */
	public function set_hf_disk( int $value ): void {
		$this->update_meta_data( '_hf_disk', absint( $value ) );
	}

	/**
	 * Set disk type.
	 *
	 * @param string $value Disk type.
	 */
	public function set_hf_disk_type( string $value ): void {
		$allowed = array( 'ssd', 'nvme', 'hdd' );
		$value   = strtolower( $value );
		if ( in_array( $value, $allowed, true ) ) {
			$this->update_meta_data( '_hf_disk_type', $value );
		}
	}

	/**
	 * Set bandwidth.
	 *
	 * @param int $value Bandwidth in GB.
	 */
	public function set_hf_bandwidth( int $value ): void {
		$this->update_meta_data( '_hf_bandwidth', absint( $value ) );
	}

	/**
	 * Set IPv4 count.
	 *
	 * @param int $value Number of IPv4 addresses.
	 */
	public function set_hf_ipv4_count( int $value ): void {
		$this->update_meta_data( '_hf_ipv4_count', absint( $value ) );
	}

	/**
	 * Set IPv6 count.
	 *
	 * @param int $value Number of IPv6 addresses.
	 */
	public function set_hf_ipv6_count( int $value ): void {
		$this->update_meta_data( '_hf_ipv6_count', absint( $value ) );
	}

	/**
	 * Set available OS choices.
	 *
	 * @param array<string> $value OS list.
	 */
	public function set_hf_os_choices( array $value ): void {
		$this->update_meta_data( '_hf_os_choices', wp_json_encode( array_map( 'sanitize_text_field', $value ) ) );
	}

	/**
	 * Set require hostname.
	 *
	 * @param bool $value Require hostname.
	 */
	public function set_hf_require_hostname( bool $value ): void {
		$this->update_meta_data( '_hf_require_hostname', $value ? 'yes' : 'no' );
	}

	/**
	 * Set require root password.
	 *
	 * @param bool $value Require root password.
	 */
	public function set_hf_require_root_password( bool $value ): void {
		$this->update_meta_data( '_hf_require_root_password', $value ? 'yes' : 'no' );
	}

	/**
	 * Set server group.
	 *
	 * @param string $value Server group.
	 */
	public function set_hf_server_group( string $value ): void {
		$this->update_meta_data( '_hf_server_group', sanitize_text_field( $value ) );
	}

	/**
	 * Set setup fee.
	 *
	 * @param float $value Setup fee.
	 */
	public function set_hf_setup_fee( float $value ): void {
		$this->update_meta_data( '_hf_setup_fee', wc_format_decimal( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Product Behavior Overrides
	|--------------------------------------------------------------------------
	*/

	/**
	 * VPS products are virtual.
	 *
	 * @return bool
	 */
	public function is_virtual(): bool {
		return true;
	}

	/**
	 * VPS products are sold individually.
	 *
	 * @return bool
	 */
	public function is_sold_individually(): bool {
		return true;
	}
}
