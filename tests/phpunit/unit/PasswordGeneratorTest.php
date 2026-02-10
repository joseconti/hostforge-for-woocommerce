<?php
/**
 * Tests for HF_Password_Generator class.
 *
 * @package HostForge\Tests\Unit
 */

namespace HostForge\Tests\Unit;

use HostForge\Modules\AutoProvisioning\HF_Password_Generator;
use WP_UnitTestCase;

/**
 * Class PasswordGeneratorTest
 */
class PasswordGeneratorTest extends WP_UnitTestCase {

	/**
	 * Test default password generation.
	 */
	public function test_generate_default_password(): void {
		$password = HF_Password_Generator::generate();

		$this->assertNotEmpty( $password );
		$this->assertEquals( 16, strlen( $password ), 'Default password should be 16 chars.' );
	}

	/**
	 * Test password complexity — must contain all character types.
	 */
	public function test_password_contains_all_character_types(): void {
		// Run multiple times since passwords are random.
		for ( $i = 0; $i < 10; $i++ ) {
			$password = HF_Password_Generator::generate();

			$this->assertMatchesRegularExpression( '/[a-z]/', $password, "Iteration {$i}: Must contain lowercase." );
			$this->assertMatchesRegularExpression( '/[A-Z]/', $password, "Iteration {$i}: Must contain uppercase." );
			$this->assertMatchesRegularExpression( '/[0-9]/', $password, "Iteration {$i}: Must contain digit." );
			$this->assertMatchesRegularExpression( '/[!@#$%^&*()\-_=+]/', $password, "Iteration {$i}: Must contain special char." );
		}
	}

	/**
	 * Test minimum length enforcement (min 12).
	 */
	public function test_minimum_length_enforced(): void {
		update_option( 'hf_password_length', 5 ); // Below minimum.

		$password = HF_Password_Generator::generate();

		$this->assertGreaterThanOrEqual( 12, strlen( $password ), 'Minimum length is 12.' );

		delete_option( 'hf_password_length' );
	}

	/**
	 * Test maximum length enforcement (max 32).
	 */
	public function test_maximum_length_enforced(): void {
		update_option( 'hf_password_length', 64 ); // Above maximum.

		$password = HF_Password_Generator::generate();

		$this->assertLessThanOrEqual( 32, strlen( $password ), 'Maximum length is 32.' );

		delete_option( 'hf_password_length' );
	}

	/**
	 * Test custom length from option.
	 */
	public function test_custom_length_from_option(): void {
		update_option( 'hf_password_length', 24 );

		$password = HF_Password_Generator::generate();

		$this->assertEquals( 24, strlen( $password ), 'Should respect configured length.' );

		delete_option( 'hf_password_length' );
	}

	/**
	 * Test that each generation produces unique passwords.
	 */
	public function test_generates_unique_passwords(): void {
		$passwords = array();

		for ( $i = 0; $i < 20; $i++ ) {
			$passwords[] = HF_Password_Generator::generate();
		}

		$unique = array_unique( $passwords );
		$this->assertCount( 20, $unique, 'All 20 passwords should be unique.' );
	}

	/**
	 * Test the filter hook.
	 */
	public function test_generated_password_filter(): void {
		add_filter(
			'hostforge_generated_password',
			function ( $password, $service_id ) {
				return 'FILTERED_' . $password;
			},
			10,
			2
		);

		$password = HF_Password_Generator::generate();

		$this->assertStringStartsWith( 'FILTERED_', $password );

		remove_all_filters( 'hostforge_generated_password' );
	}

	/**
	 * Test password with service_id parameter.
	 */
	public function test_generate_with_service_id(): void {
		$password = HF_Password_Generator::generate( 42 );

		$this->assertNotEmpty( $password );
		$this->assertGreaterThanOrEqual( 12, strlen( $password ) );
	}
}
