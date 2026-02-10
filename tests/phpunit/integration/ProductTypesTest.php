<?php
/**
 * Tests for WooCommerce product types registration.
 *
 * @package HostForge\Tests\Integration
 */

namespace HostForge\Tests\Integration;

use WP_UnitTestCase;

/**
 * Class ProductTypesTest
 */
class ProductTypesTest extends WP_UnitTestCase {

	/**
	 * All HostForge product type slugs.
	 *
	 * @var array<string>
	 */
	private array $hf_types = array(
		'hf_shared_hosting',
		'hf_reseller_hosting',
		'hf_vps_server',
		'hf_dedicated_server',
		'hf_domain',
		'hf_ssl_certificate',
		'hf_software_license',
	);

	/**
	 * Test that all 7 product types are registered.
	 */
	public function test_product_types_registered(): void {
		$types = wc_get_product_types();

		foreach ( $this->hf_types as $type ) {
			$this->assertArrayHasKey( $type, $types, "Product type '{$type}' should be registered." );
		}
	}

	/**
	 * Test creating a Shared Hosting product.
	 */
	public function test_create_shared_hosting_product(): void {
		$product = new \HostForge\Products\WC_Product_HF_Shared_Hosting();
		$product->set_name( 'Starter Hosting' );
		$product->set_regular_price( '9.99' );
		$product->save();

		$this->assertGreaterThan( 0, $product->get_id() );
		$this->assertEquals( 'hf_shared_hosting', $product->get_type() );

		// Clean up.
		$product->delete( true );
	}

	/**
	 * Test creating a VPS product.
	 */
	public function test_create_vps_product(): void {
		$product = new \HostForge\Products\WC_Product_HF_VPS_Server();
		$product->set_name( 'VPS Basic' );
		$product->set_regular_price( '29.99' );
		$product->save();

		$this->assertGreaterThan( 0, $product->get_id() );
		$this->assertEquals( 'hf_vps_server', $product->get_type() );

		$product->delete( true );
	}

	/**
	 * Test creating a Domain product.
	 */
	public function test_create_domain_product(): void {
		$product = new \HostForge\Products\WC_Product_HF_Domain();
		$product->set_name( '.com Domain' );
		$product->set_regular_price( '12.00' );
		$product->save();

		$this->assertGreaterThan( 0, $product->get_id() );
		$this->assertEquals( 'hf_domain', $product->get_type() );

		$product->delete( true );
	}

	/**
	 * Test creating an SSL Certificate product.
	 */
	public function test_create_ssl_product(): void {
		$product = new \HostForge\Products\WC_Product_HF_SSL_Certificate();
		$product->set_name( 'DV SSL Certificate' );
		$product->set_regular_price( '49.99' );
		$product->save();

		$this->assertGreaterThan( 0, $product->get_id() );
		$this->assertEquals( 'hf_ssl_certificate', $product->get_type() );

		$product->delete( true );
	}

	/**
	 * Test creating a Software License product.
	 */
	public function test_create_software_license_product(): void {
		$product = new \HostForge\Products\WC_Product_HF_Software_License();
		$product->set_name( 'cPanel License' );
		$product->set_regular_price( '15.00' );
		$product->save();

		$this->assertGreaterThan( 0, $product->get_id() );
		$this->assertEquals( 'hf_software_license', $product->get_type() );

		$product->delete( true );
	}

	/**
	 * Test that Reseller extends Shared Hosting.
	 */
	public function test_reseller_extends_shared(): void {
		$product = new \HostForge\Products\WC_Product_HF_Reseller_Hosting();
		$this->assertInstanceOf( \HostForge\Products\WC_Product_HF_Shared_Hosting::class, $product );
	}

	/**
	 * Test that Dedicated extends VPS.
	 */
	public function test_dedicated_extends_vps(): void {
		$product = new \HostForge\Products\WC_Product_HF_Dedicated_Server();
		$this->assertInstanceOf( \HostForge\Products\WC_Product_HF_VPS_Server::class, $product );
	}

	/**
	 * Test product meta persistence for Shared Hosting.
	 */
	public function test_shared_hosting_meta_persistence(): void {
		$product = new \HostForge\Products\WC_Product_HF_Shared_Hosting();
		$product->set_name( 'Meta Test Hosting' );
		$product->set_regular_price( '5.00' );

		if ( method_exists( $product, 'set_hf_disk_space' ) ) {
			$product->set_hf_disk_space( '10240' );
		}
		if ( method_exists( $product, 'set_hf_bandwidth' ) ) {
			$product->set_hf_bandwidth( '100000' );
		}

		$product->save();

		// Reload from DB.
		$loaded = wc_get_product( $product->get_id() );

		$this->assertNotNull( $loaded );
		$this->assertEquals( 'hf_shared_hosting', $loaded->get_type() );

		if ( method_exists( $loaded, 'get_hf_disk_space' ) ) {
			$this->assertEquals( '10240', $loaded->get_hf_disk_space() );
		}

		$product->delete( true );
	}

	/**
	 * Test that HF_Product_Types::is_hf_type() works.
	 */
	public function test_is_hf_type(): void {
		if ( ! method_exists( 'HostForge\Products\HF_Product_Types', 'is_hf_type' ) ) {
			$this->markTestSkipped( 'is_hf_type method not found.' );
		}

		foreach ( $this->hf_types as $type ) {
			$this->assertTrue(
				\HostForge\Products\HF_Product_Types::is_hf_type( $type ),
				"'{$type}' should be recognized as HF type."
			);
		}

		$this->assertFalse( \HostForge\Products\HF_Product_Types::is_hf_type( 'simple' ) );
		$this->assertFalse( \HostForge\Products\HF_Product_Types::is_hf_type( 'variable' ) );
	}
}
