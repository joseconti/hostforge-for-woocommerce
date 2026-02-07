<?php
/**
 * Reseller Hosting Product Type.
 *
 * Extends Shared Hosting with reseller-specific fields (max accounts, aggregate limits).
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Product_HF_Reseller_Hosting
 */
class WC_Product_HF_Reseller_Hosting extends WC_Product_HF_Shared_Hosting {

	/**
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'hf_reseller_hosting';

	/**
	 * Get product type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'hf_reseller_hosting';
	}

	/*
	|--------------------------------------------------------------------------
	| Reseller-specific Meta Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get max cPanel/Plesk accounts the reseller can create.
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_max_accounts( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_max_accounts', true );
	}

	/**
	 * Get aggregate disk limit for all reseller accounts (MB).
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_aggregate_disk_limit( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_aggregate_disk_limit', true );
	}

	/**
	 * Get aggregate bandwidth limit for all reseller accounts (MB).
	 *
	 * @param string $context View or edit context.
	 * @return int
	 */
	public function get_hf_aggregate_bandwidth_limit( string $context = 'view' ): int {
		return (int) $this->get_meta( '_hf_aggregate_bandwidth_limit', true );
	}

	/**
	 * Get reseller plan name on the server.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_reseller_plan( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_reseller_plan', true );
	}

	/**
	 * Whether to enable WHM access for the reseller.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_whm_access( string $context = 'view' ): bool {
		return 'yes' === $this->get_meta( '_hf_whm_access', true );
	}

	/*
	|--------------------------------------------------------------------------
	| Reseller-specific Meta Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set max accounts.
	 *
	 * @param int $value Max accounts.
	 */
	public function set_hf_max_accounts( int $value ): void {
		$this->update_meta_data( '_hf_max_accounts', absint( $value ) );
	}

	/**
	 * Set aggregate disk limit.
	 *
	 * @param int $value Disk in MB.
	 */
	public function set_hf_aggregate_disk_limit( int $value ): void {
		$this->update_meta_data( '_hf_aggregate_disk_limit', absint( $value ) );
	}

	/**
	 * Set aggregate bandwidth limit.
	 *
	 * @param int $value Bandwidth in MB.
	 */
	public function set_hf_aggregate_bandwidth_limit( int $value ): void {
		$this->update_meta_data( '_hf_aggregate_bandwidth_limit', absint( $value ) );
	}

	/**
	 * Set reseller plan.
	 *
	 * @param string $value Reseller plan name.
	 */
	public function set_hf_reseller_plan( string $value ): void {
		$this->update_meta_data( '_hf_reseller_plan', sanitize_text_field( $value ) );
	}

	/**
	 * Set WHM access.
	 *
	 * @param bool $value Enable WHM access.
	 */
	public function set_hf_whm_access( bool $value ): void {
		$this->update_meta_data( '_hf_whm_access', $value ? 'yes' : 'no' );
	}
}
