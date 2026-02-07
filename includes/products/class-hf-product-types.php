<?php
/**
 * Product Types Registration.
 *
 * Registers all HostForge custom product types with WooCommerce.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Product_Types
 */
class HF_Product_Types {

	/**
	 * Product type definitions.
	 *
	 * @var array<string, array{class: string, label: string}>
	 */
	private static array $types = array(
		'hf_shared_hosting'   => array(
			'class' => 'HostForge\\Products\\WC_Product_HF_Shared_Hosting',
			'label' => 'Shared Hosting',
		),
		'hf_reseller_hosting' => array(
			'class' => 'HostForge\\Products\\WC_Product_HF_Reseller_Hosting',
			'label' => 'Reseller Hosting',
		),
		'hf_vps_server'       => array(
			'class' => 'HostForge\\Products\\WC_Product_HF_VPS_Server',
			'label' => 'VPS / Cloud Server',
		),
		'hf_dedicated_server' => array(
			'class' => 'HostForge\\Products\\WC_Product_HF_Dedicated_Server',
			'label' => 'Dedicated Server',
		),
		'hf_domain'           => array(
			'class' => 'HostForge\\Products\\WC_Product_HF_Domain',
			'label' => 'Domain Name',
		),
		'hf_ssl_certificate'  => array(
			'class' => 'HostForge\\Products\\WC_Product_HF_SSL_Certificate',
			'label' => 'SSL Certificate',
		),
		'hf_software_license' => array(
			'class' => 'HostForge\\Products\\WC_Product_HF_Software_License',
			'label' => 'Software License',
		),
	);

	/**
	 * Initialize product type registration.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'product_type_selector', array( __CLASS__, 'add_product_types' ) );
		add_filter( 'woocommerce_product_class', array( __CLASS__, 'resolve_product_class' ), 10, 2 );
		add_filter( 'woocommerce_product_type_query', array( __CLASS__, 'product_type_query' ), 10, 2 );
	}

	/**
	 * Add HostForge product types to the product type selector.
	 *
	 * @param array<string, string> $types Existing product types.
	 * @return array<string, string>
	 */
	public static function add_product_types( array $types ): array {
		foreach ( self::$types as $type_slug => $type_info ) {
			$types[ $type_slug ] = esc_html__( $type_info['label'], 'hostforge' );
		}

		return $types;
	}

	/**
	 * Resolve HostForge product class names.
	 *
	 * @param string $classname  Default class name.
	 * @param string $product_type Product type slug.
	 * @return string
	 */
	public static function resolve_product_class( string $classname, string $product_type ): string {
		if ( isset( self::$types[ $product_type ] ) ) {
			return self::$types[ $product_type ]['class'];
		}

		return $classname;
	}

	/**
	 * Handle product type query for HostForge types.
	 *
	 * @param false|string $type       Product type or false.
	 * @param int          $product_id Product ID.
	 * @return false|string
	 */
	public static function product_type_query( $type, int $product_id ) {
		if ( false !== $type ) {
			return $type;
		}

		$terms = get_the_terms( $product_id, 'product_type' );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$term = reset( $terms );
			if ( isset( self::$types[ $term->slug ] ) ) {
				return $term->slug;
			}
		}

		return $type;
	}

	/**
	 * Get all registered product type slugs.
	 *
	 * @return array<string>
	 */
	public static function get_type_slugs(): array {
		return array_keys( self::$types );
	}

	/**
	 * Get hosting-related product type slugs (excludes domain, SSL, license).
	 *
	 * @return array<string>
	 */
	public static function get_hosting_type_slugs(): array {
		return array(
			'hf_shared_hosting',
			'hf_reseller_hosting',
			'hf_vps_server',
			'hf_dedicated_server',
		);
	}

	/**
	 * Check if a product type is a HostForge type.
	 *
	 * @param string $type Product type slug.
	 * @return bool
	 */
	public static function is_hf_type( string $type ): bool {
		return isset( self::$types[ $type ] );
	}
}
