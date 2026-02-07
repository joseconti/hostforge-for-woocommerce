<?php
/**
 * Shared Hosting Product Type.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Product_HF_Shared_Hosting
 */
class WC_Product_HF_Shared_Hosting extends \WC_Product {

	/**
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'hf_shared_hosting';

	/**
	 * Get product type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'hf_shared_hosting';
	}

	/*
	|--------------------------------------------------------------------------
	| Hosting Meta Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get assigned server group.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_server_group( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_server_group', true );
	}

	/**
	 * Get the hosting plan/package name on the server.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_plan( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_plan', true );
	}

	/**
	 * Get disk space limit in MB.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_disk_limit( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_disk_limit', true );
	}

	/**
	 * Get bandwidth limit in MB.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_bandwidth_limit( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_bandwidth_limit', true );
	}

	/**
	 * Get email accounts limit.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_email_limit( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_email_limit', true );
	}

	/**
	 * Get databases limit.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_db_limit( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_db_limit', true );
	}

	/**
	 * Get subdomains limit.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_subdomain_limit( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_subdomain_limit', true );
	}

	/**
	 * Get parked domains limit.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_parked_domains_limit( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_parked_domains_limit', true );
	}

	/**
	 * Get addon domains limit.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_addon_domains_limit( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_addon_domains_limit', true );
	}

	/**
	 * Whether a domain is required at checkout.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_require_domain( string $context = 'view' ): bool {
		return 'yes' === $this->get_meta( '_hf_require_domain', true );
	}

	/**
	 * Whether auto-username generation is enabled.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_auto_username( string $context = 'view' ): bool {
		$value = $this->get_meta( '_hf_auto_username', true );
		return '' === $value || 'yes' === $value;
	}

	/**
	 * Get setup fee amount.
	 *
	 * @param string $context View or edit context.
	 * @return float
	 */
	public function get_hf_setup_fee( string $context = 'view' ): float {
		return (float) $this->get_meta( '_hf_setup_fee', true );
	}

	/**
	 * Get trial days.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_trial_days( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_trial_days', true );
	}

	/*
	|--------------------------------------------------------------------------
	| Hosting Meta Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set server group.
	 *
	 * @param string $value Server group slug.
	 */
	public function set_hf_server_group( string $value ): void {
		$this->update_meta_data( '_hf_server_group', sanitize_text_field( $value ) );
	}

	/**
	 * Set hosting plan.
	 *
	 * @param string $value Plan name.
	 */
	public function set_hf_plan( string $value ): void {
		$this->update_meta_data( '_hf_plan', sanitize_text_field( $value ) );
	}

	/**
	 * Set disk space limit.
	 *
	 * @param int $value Disk in MB.
	 */
	public function set_hf_disk_limit( int $value ): void {
		$this->update_meta_data( '_hf_disk_limit', absint( $value ) );
	}

	/**
	 * Set bandwidth limit.
	 *
	 * @param int $value Bandwidth in MB.
	 */
	public function set_hf_bandwidth_limit( int $value ): void {
		$this->update_meta_data( '_hf_bandwidth_limit', absint( $value ) );
	}

	/**
	 * Set email accounts limit.
	 *
	 * @param int $value Email limit.
	 */
	public function set_hf_email_limit( int $value ): void {
		$this->update_meta_data( '_hf_email_limit', absint( $value ) );
	}

	/**
	 * Set databases limit.
	 *
	 * @param int $value DB limit.
	 */
	public function set_hf_db_limit( int $value ): void {
		$this->update_meta_data( '_hf_db_limit', absint( $value ) );
	}

	/**
	 * Set subdomains limit.
	 *
	 * @param int $value Subdomain limit.
	 */
	public function set_hf_subdomain_limit( int $value ): void {
		$this->update_meta_data( '_hf_subdomain_limit', absint( $value ) );
	}

	/**
	 * Set parked domains limit.
	 *
	 * @param int $value Parked domains limit.
	 */
	public function set_hf_parked_domains_limit( int $value ): void {
		$this->update_meta_data( '_hf_parked_domains_limit', absint( $value ) );
	}

	/**
	 * Set addon domains limit.
	 *
	 * @param int $value Addon domains limit.
	 */
	public function set_hf_addon_domains_limit( int $value ): void {
		$this->update_meta_data( '_hf_addon_domains_limit', absint( $value ) );
	}

	/**
	 * Set whether domain is required.
	 *
	 * @param bool $value Require domain.
	 */
	public function set_hf_require_domain( bool $value ): void {
		$this->update_meta_data( '_hf_require_domain', $value ? 'yes' : 'no' );
	}

	/**
	 * Set auto-username generation.
	 *
	 * @param bool $value Auto-username.
	 */
	public function set_hf_auto_username( bool $value ): void {
		$this->update_meta_data( '_hf_auto_username', $value ? 'yes' : 'no' );
	}

	/**
	 * Set setup fee.
	 *
	 * @param float $value Setup fee amount.
	 */
	public function set_hf_setup_fee( float $value ): void {
		$this->update_meta_data( '_hf_setup_fee', wc_format_decimal( $value ) );
	}

	/**
	 * Set trial days.
	 *
	 * @param int $value Trial days.
	 */
	public function set_hf_trial_days( int $value ): void {
		$this->update_meta_data( '_hf_trial_days', absint( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Product Behavior Overrides
	|--------------------------------------------------------------------------
	*/

	/**
	 * Hosting products are virtual.
	 *
	 * @return bool
	 */
	public function is_virtual(): bool {
		return true;
	}

	/**
	 * Hosting products are not downloadable.
	 *
	 * @return bool
	 */
	public function is_downloadable(): bool {
		return false;
	}

	/**
	 * Hosting products are sold individually.
	 *
	 * @return bool
	 */
	public function is_sold_individually(): bool {
		return true;
	}
}
