<?php
/**
 * SSL Certificate Product Type.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Product_HF_SSL_Certificate
 */
class WC_Product_HF_SSL_Certificate extends \WC_Product {

	/**
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'hf_ssl_certificate';

	/**
	 * Get product type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'hf_ssl_certificate';
	}

	/*
	|--------------------------------------------------------------------------
	| SSL Meta Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get SSL validation type (DV, OV, EV, Wildcard).
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_ssl_type( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_ssl_type', true );
	}

	/**
	 * Get SSL provider/brand (e.g. Comodo, Let's Encrypt, Sectigo).
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_ssl_brand( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_ssl_brand', true );
	}

	/**
	 * Get certificate validity period in months.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_validity_months( string $context = 'view' ): int {
		$months = (int) $this->get_meta( '_hf_validity_months', true );
		return $months > 0 ? $months : 12;
	}

	/**
	 * Whether multi-domain (SAN) is supported.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_multi_domain( string $context = 'view' ): bool {
		return 'yes' === $this->get_meta( '_hf_multi_domain', true );
	}

	/**
	 * Get max SAN domains.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_max_san_domains( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_max_san_domains', true );
	}

	/**
	 * Whether domain is required at checkout.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_require_domain( string $context = 'view' ): bool {
		$value = $this->get_meta( '_hf_require_domain', true );
		return '' === $value || 'yes' === $value;
	}

	/**
	 * Whether CSR is required at checkout.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_require_csr( string $context = 'view' ): bool {
		return 'yes' === $this->get_meta( '_hf_require_csr', true );
	}

	/**
	 * Get warranty amount in USD.
	 *
	 * @param string $context View or edit context.
	 * @return float
	 */
	public function get_hf_warranty( string $context = 'view' ): float {
		return (float) $this->get_meta( '_hf_warranty', true );
	}

	/*
	|--------------------------------------------------------------------------
	| SSL Meta Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set SSL type.
	 *
	 * @param string $value SSL type (dv, ov, ev, wildcard).
	 */
	public function set_hf_ssl_type( string $value ): void {
		$allowed = array( 'dv', 'ov', 'ev', 'wildcard' );
		$value   = strtolower( $value );
		if ( in_array( $value, $allowed, true ) ) {
			$this->update_meta_data( '_hf_ssl_type', $value );
		}
	}

	/**
	 * Set SSL brand.
	 *
	 * @param string $value SSL brand.
	 */
	public function set_hf_ssl_brand( string $value ): void {
		$this->update_meta_data( '_hf_ssl_brand', sanitize_text_field( $value ) );
	}

	/**
	 * Set validity months.
	 *
	 * @param int $value Months.
	 */
	public function set_hf_validity_months( int $value ): void {
		$this->update_meta_data( '_hf_validity_months', max( 1, absint( $value ) ) );
	}

	/**
	 * Set multi-domain support.
	 *
	 * @param bool $value Multi-domain.
	 */
	public function set_hf_multi_domain( bool $value ): void {
		$this->update_meta_data( '_hf_multi_domain', $value ? 'yes' : 'no' );
	}

	/**
	 * Set max SAN domains.
	 *
	 * @param int $value Max SAN domains.
	 */
	public function set_hf_max_san_domains( int $value ): void {
		$this->update_meta_data( '_hf_max_san_domains', absint( $value ) );
	}

	/**
	 * Set require domain.
	 *
	 * @param bool $value Require domain.
	 */
	public function set_hf_require_domain( bool $value ): void {
		$this->update_meta_data( '_hf_require_domain', $value ? 'yes' : 'no' );
	}

	/**
	 * Set require CSR.
	 *
	 * @param bool $value Require CSR.
	 */
	public function set_hf_require_csr( bool $value ): void {
		$this->update_meta_data( '_hf_require_csr', $value ? 'yes' : 'no' );
	}

	/**
	 * Set warranty amount.
	 *
	 * @param float $value Warranty USD.
	 */
	public function set_hf_warranty( float $value ): void {
		$this->update_meta_data( '_hf_warranty', wc_format_decimal( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Product Behavior Overrides
	|--------------------------------------------------------------------------
	*/

	/**
	 * SSL products are virtual.
	 *
	 * @return bool
	 */
	public function is_virtual(): bool {
		return true;
	}

	/**
	 * SSL products are sold individually.
	 *
	 * @return bool
	 */
	public function is_sold_individually(): bool {
		return true;
	}
}
