<?php
/**
 * Tests for HF_Username_Generator class.
 *
 * @package HostForge\Tests\Unit
 */

namespace HostForge\Tests\Unit;

use HostForge\Modules\AutoProvisioning\HF_Username_Generator;
use WP_UnitTestCase;

/**
 * Class UsernameGeneratorTest
 */
class UsernameGeneratorTest extends WP_UnitTestCase {

	/**
	 * Test basic username generation from a domain.
	 */
	public function test_generate_from_simple_domain(): void {
		$username = HF_Username_Generator::generate( 'example.com' );

		$this->assertNotEmpty( $username );
		$this->assertLessThanOrEqual( 8, strlen( $username ), 'Username must be max 8 chars.' );
		$this->assertMatchesRegularExpression( '/^[a-z]/', $username, 'Must start with a letter.' );
		$this->assertMatchesRegularExpression( '/^[a-z0-9]+$/', $username, 'Only lowercase alphanumeric.' );
	}

	/**
	 * Test that www prefix is stripped.
	 */
	public function test_strips_www_prefix(): void {
		$with_www    = HF_Username_Generator::generate( 'www.mysite.com' );
		$without_www = HF_Username_Generator::generate( 'mysite.com' );

		// Both should produce the same base (before uniqueness check).
		$this->assertNotEmpty( $with_www );
		$this->assertNotEmpty( $without_www );
		$this->assertLessThanOrEqual( 8, strlen( $with_www ) );
	}

	/**
	 * Test username from domain starting with numbers.
	 */
	public function test_domain_starting_with_numbers(): void {
		$username = HF_Username_Generator::generate( '123hosting.com' );

		$this->assertMatchesRegularExpression( '/^[a-z]/', $username, 'Must start with a letter even for numeric domains.' );
		$this->assertLessThanOrEqual( 8, strlen( $username ) );
	}

	/**
	 * Test username from short domain.
	 */
	public function test_short_domain(): void {
		$username = HF_Username_Generator::generate( 'ab.com' );

		$this->assertGreaterThanOrEqual( 3, strlen( $username ), 'Minimum 3 chars.' );
		$this->assertLessThanOrEqual( 8, strlen( $username ) );
	}

	/**
	 * Test username from very long domain.
	 */
	public function test_long_domain_is_truncated(): void {
		$username = HF_Username_Generator::generate( 'verylonghostingdomainname.com' );

		$this->assertLessThanOrEqual( 8, strlen( $username ), 'Must not exceed 8 chars.' );
	}

	/**
	 * Test username from domain with special characters (hyphens).
	 */
	public function test_domain_with_special_characters(): void {
		$username = HF_Username_Generator::generate( 'my-hosting-site.com' );

		$this->assertMatchesRegularExpression( '/^[a-z0-9]+$/', $username, 'No special characters allowed.' );
		$this->assertLessThanOrEqual( 8, strlen( $username ) );
	}

	/**
	 * Test username is always lowercase.
	 */
	public function test_username_is_lowercase(): void {
		$username = HF_Username_Generator::generate( 'MyDomain.COM' );

		$this->assertSame( strtolower( $username ), $username, 'Username must be lowercase.' );
	}

	/**
	 * Test that generated usernames are unique.
	 */
	public function test_uniqueness_across_generations(): void {
		// Create a service post with a known username to test uniqueness.
		$service_id = $this->factory->post->create(
			array(
				'post_type'   => 'hf_service',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $service_id, '_hf_panel_username', 'example' );

		$username = HF_Username_Generator::generate( 'example.com' );

		// Should not be the exact duplicate.
		$this->assertNotEquals( 'example', $username, 'Should generate a unique username when collision exists.' );
	}

	/**
	 * Test the filter hook.
	 */
	public function test_generated_username_filter(): void {
		add_filter(
			'hostforge_generated_username',
			function ( $username, $domain, $user_id ) {
				return 'custom' . substr( $username, 0, 2 );
			},
			10,
			3
		);

		$username = HF_Username_Generator::generate( 'testsite.com' );

		$this->assertStringStartsWith( 'custom', $username );

		remove_all_filters( 'hostforge_generated_username' );
	}
}
