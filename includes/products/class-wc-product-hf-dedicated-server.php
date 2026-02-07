<?php
/**
 * Dedicated Server Product Type.
 *
 * Extends VPS with physical hardware specifications.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Product_HF_Dedicated_Server
 */
class WC_Product_HF_Dedicated_Server extends WC_Product_HF_VPS_Server {

	/**
	 * Product type.
	 *
	 * @var string
	 */
	protected $product_type = 'hf_dedicated_server';

	/**
	 * Get product type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'hf_dedicated_server';
	}

	/*
	|--------------------------------------------------------------------------
	| Dedicated Server Meta Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get processor model description.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_processor( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_processor', true );
	}

	/**
	 * Get RAID configuration.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_raid( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_raid', true );
	}

	/**
	 * Get uplink port speed (e.g. "1Gbps", "10Gbps").
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_uplink( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_uplink', true );
	}

	/**
	 * Get datacenter location.
	 *
	 * @param string $context View or edit context.
	 * @return string
	 */
	public function get_hf_datacenter( string $context = 'view' ): string {
		return (string) $this->get_meta( '_hf_datacenter', true );
	}

	/**
	 * Whether IPMI/KVM access is included.
	 *
	 * @param string $context View or edit context.
	 * @return bool
	 */
	public function get_hf_ipmi_access( string $context = 'view' ): bool {
		return 'yes' === $this->get_meta( '_hf_ipmi_access', true );
	}

	/*
	|--------------------------------------------------------------------------
	| Dedicated Server Meta Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set processor model.
	 *
	 * @param string $value Processor description.
	 */
	public function set_hf_processor( string $value ): void {
		$this->update_meta_data( '_hf_processor', sanitize_text_field( $value ) );
	}

	/**
	 * Set RAID configuration.
	 *
	 * @param string $value RAID config.
	 */
	public function set_hf_raid( string $value ): void {
		$this->update_meta_data( '_hf_raid', sanitize_text_field( $value ) );
	}

	/**
	 * Set uplink speed.
	 *
	 * @param string $value Uplink speed.
	 */
	public function set_hf_uplink( string $value ): void {
		$this->update_meta_data( '_hf_uplink', sanitize_text_field( $value ) );
	}

	/**
	 * Set datacenter location.
	 *
	 * @param string $value Datacenter.
	 */
	public function set_hf_datacenter( string $value ): void {
		$this->update_meta_data( '_hf_datacenter', sanitize_text_field( $value ) );
	}

	/**
	 * Set IPMI access.
	 *
	 * @param bool $value IPMI access.
	 */
	public function set_hf_ipmi_access( bool $value ): void {
		$this->update_meta_data( '_hf_ipmi_access', $value ? 'yes' : 'no' );
	}
}
