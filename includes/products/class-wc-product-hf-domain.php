<?php
/**
 * Domain Name Product Type.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Product_HF_Domain
 */
class WC_Product_HF_Domain extends \WC_Product {

	/**
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'hf_domain';

	/**
	 * Get product type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'hf_domain';
	}

	/*
	|--------------------------------------------------------------------------
	| Domain Meta Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get allowed TLDs (JSON array of extensions).
	 *
	 * @param string $context View or edit context.
	 * @return array<string>
	 */
	public function get_hf_allowed_tlds( string $context = 'view' ): array {
		$tlds = $this->get_meta( '_hf_allowed_tlds', true );
		if ( is_string( $tlds ) && ! empty( $tlds ) ) {
			$decoded = json_decode( $tlds, true );
			return is_array( $decoded ) ? $decoded : array();
		}
		return is_array( $tlds ) ? $tlds : array();
	}

	/**
	 * Get the assigned registrar ID.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_registrar( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_registrar', true );
	}

	/**
	 * Get registration period in years.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_registration_years( string $context = 'view' ): int {
		$years = (int) $this->get_meta( '_hf_registration_years', true );
		return $years > 0 ? $years : 1;
	}

	/**
	 * Whether auto-renewal is enabled by default.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_auto_renew_default( string $context = 'view' ): bool {
		$value = $this->get_meta( '_hf_auto_renew_default', true );
		return '' === $value || 'yes' === $value;
	}

	/**
	 * Whether domain transfer is allowed.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_allow_transfer( string $context = 'view' ): bool {
		$value = $this->get_meta( '_hf_allow_transfer', true );
		return '' === $value || 'yes' === $value;
	}

	/**
	 * Whether ID protection (WHOIS privacy) is available.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_id_protection( string $context = 'view' ): bool {
		return 'yes' === $this->get_meta( '_hf_id_protection', true );
	}

	/**
	 * Get TLD pricing data (JSON map of tld => price).
	 *
	 * @param string $context View or edit context.
	 * @return array<string, float>
	 */
	public function get_hf_tld_pricing( string $context = 'view' ): array {
		$pricing = $this->get_meta( '_hf_tld_pricing', true );
		if ( is_string( $pricing ) && ! empty( $pricing ) ) {
			$decoded = json_decode( $pricing, true );
			return is_array( $decoded ) ? $decoded : array();
		}
		return is_array( $pricing ) ? $pricing : array();
	}

	/*
	|--------------------------------------------------------------------------
	| Domain Meta Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set allowed TLDs.
	 *
	 * @param array<string> $value TLD list.
	 */
	public function set_hf_allowed_tlds( array $value ): void {
		$this->update_meta_data( '_hf_allowed_tlds', wp_json_encode( array_map( 'sanitize_text_field', $value ) ) );
	}

	/**
	 * Set registrar ID.
	 *
	 * @param string $value Registrar ID.
	 */
	public function set_hf_registrar( string $value ): void {
		$this->update_meta_data( '_hf_registrar', sanitize_text_field( $value ) );
	}

	/**
	 * Set registration period.
	 *
	 * @param int $value Years.
	 */
	public function set_hf_registration_years( int $value ): void {
		$this->update_meta_data( '_hf_registration_years', max( 1, absint( $value ) ) );
	}

	/**
	 * Set auto-renew default.
	 *
	 * @param bool $value Auto-renew.
	 */
	public function set_hf_auto_renew_default( bool $value ): void {
		$this->update_meta_data( '_hf_auto_renew_default', $value ? 'yes' : 'no' );
	}

	/**
	 * Set allow transfer.
	 *
	 * @param bool $value Allow transfer.
	 */
	public function set_hf_allow_transfer( bool $value ): void {
		$this->update_meta_data( '_hf_allow_transfer', $value ? 'yes' : 'no' );
	}

	/**
	 * Set ID protection availability.
	 *
	 * @param bool $value ID protection.
	 */
	public function set_hf_id_protection( bool $value ): void {
		$this->update_meta_data( '_hf_id_protection', $value ? 'yes' : 'no' );
	}

	/**
	 * Set TLD pricing.
	 *
	 * @param array<string, float> $value TLD => price map.
	 */
	public function set_hf_tld_pricing( array $value ): void {
		$sanitized = array();
		foreach ( $value as $tld => $price ) {
			$sanitized[ sanitize_text_field( $tld ) ] = (float) $price;
		}
		$this->update_meta_data( '_hf_tld_pricing', wp_json_encode( $sanitized ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Product Behavior Overrides
	|--------------------------------------------------------------------------
	*/

	/**
	 * Domain products are virtual.
	 *
	 * @return bool
	 */
	public function is_virtual(): bool {
		return true;
	}

	/**
	 * Domain products are sold individually.
	 *
	 * @return bool
	 */
	public function is_sold_individually(): bool {
		return true;
	}
}
