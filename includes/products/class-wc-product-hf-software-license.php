<?php
/**
 * Software License Product Type.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Product_HF_Software_License
 */
class WC_Product_HF_Software_License extends \WC_Product {

	/**
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'hf_software_license';

	/**
	 * Get product type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'hf_software_license';
	}

	/*
	|--------------------------------------------------------------------------
	| License Meta Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get license type (e.g. cpanel, plesk, litespeed, cloudlinux, softaculous).
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_license_type( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_license_type', true );
	}

	/**
	 * Get license provider/vendor.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_license_provider( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_license_provider', true );
	}

	/**
	 * Whether server IP is required at checkout.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_require_server_ip( string $context = 'view' ): bool {
		$value = $this->get_meta( '_hf_require_server_ip', true );
		return '' === $value || 'yes' === $value;
	}

	/**
	 * Whether a license key is auto-generated.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_auto_generate_key( string $context = 'view' ): bool {
		return 'yes' === $this->get_meta( '_hf_auto_generate_key', true );
	}

	/**
	 * Get the license key prefix.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_key_prefix( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_key_prefix', true );
	}

	/**
	 * Get maximum activations per license.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_max_activations( string $context = 'view' ): int {
		$max = (int) $this->get_meta( '_hf_max_activations', true );
		return $max > 0 ? $max : 1;
	}

	/**
	 * Whether IP changes are allowed.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_allow_ip_change( string $context = 'view' ): bool {
		$value = $this->get_meta( '_hf_allow_ip_change', true );
		return '' === $value || 'yes' === $value;
	}

	/*
	|--------------------------------------------------------------------------
	| License Meta Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set license type.
	 *
	 * @param string $value License type.
	 */
	public function set_hf_license_type( string $value ): void {
		$this->update_meta_data( '_hf_license_type', sanitize_text_field( $value ) );
	}

	/**
	 * Set license provider.
	 *
	 * @param string $value License provider.
	 */
	public function set_hf_license_provider( string $value ): void {
		$this->update_meta_data( '_hf_license_provider', sanitize_text_field( $value ) );
	}

	/**
	 * Set require server IP.
	 *
	 * @param bool $value Require IP.
	 */
	public function set_hf_require_server_ip( bool $value ): void {
		$this->update_meta_data( '_hf_require_server_ip', $value ? 'yes' : 'no' );
	}

	/**
	 * Set auto-generate key.
	 *
	 * @param bool $value Auto-generate.
	 */
	public function set_hf_auto_generate_key( bool $value ): void {
		$this->update_meta_data( '_hf_auto_generate_key', $value ? 'yes' : 'no' );
	}

	/**
	 * Set license key prefix.
	 *
	 * @param string $value Prefix.
	 */
	public function set_hf_key_prefix( string $value ): void {
		$this->update_meta_data( '_hf_key_prefix', sanitize_text_field( $value ) );
	}

	/**
	 * Set max activations.
	 *
	 * @param int $value Max activations.
	 */
	public function set_hf_max_activations( int $value ): void {
		$this->update_meta_data( '_hf_max_activations', max( 1, absint( $value ) ) );
	}

	/**
	 * Set allow IP change.
	 *
	 * @param bool $value Allow IP change.
	 */
	public function set_hf_allow_ip_change( bool $value ): void {
		$this->update_meta_data( '_hf_allow_ip_change', $value ? 'yes' : 'no' );
	}

	/*
	|--------------------------------------------------------------------------
	| Product Behavior Overrides
	|--------------------------------------------------------------------------
	*/

	/**
	 * License products are virtual.
	 *
	 * @return bool
	 */
	public function is_virtual(): bool {
		return true;
	}

	/**
	 * License products are sold individually.
	 *
	 * @return bool
	 */
	public function is_sold_individually(): bool {
		return true;
	}
}
