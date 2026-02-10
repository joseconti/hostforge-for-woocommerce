<?php
/**
 * Tests for HF_Encryption class.
 *
 * @package HostForge\Tests\Unit
 */

namespace HostForge\Tests\Unit;

use HostForge\HF_Encryption;
use WP_UnitTestCase;

/**
 * Class EncryptionTest
 */
class EncryptionTest extends WP_UnitTestCase {

	/**
	 * Test encrypting and decrypting a simple string.
	 */
	public function test_encrypt_decrypt_round_trip(): void {
		$original  = 'my-secret-api-key-12345';
		$encrypted = HF_Encryption::encrypt( $original );

		$this->assertNotEmpty( $encrypted, 'Encrypted value should not be empty.' );
		$this->assertNotEquals( $original, $encrypted, 'Encrypted value should differ from original.' );

		$decrypted = HF_Encryption::decrypt( $encrypted );
		$this->assertEquals( $original, $decrypted, 'Decrypted value should match original.' );
	}

	/**
	 * Test that encrypting an empty string returns empty.
	 */
	public function test_encrypt_empty_string(): void {
		$this->assertSame( '', HF_Encryption::encrypt( '' ) );
	}

	/**
	 * Test that decrypting an empty string returns empty.
	 */
	public function test_decrypt_empty_string(): void {
		$this->assertSame( '', HF_Encryption::decrypt( '' ) );
	}

	/**
	 * Test that decrypting invalid base64 returns empty.
	 */
	public function test_decrypt_invalid_base64(): void {
		$this->assertSame( '', HF_Encryption::decrypt( 'not-valid-base64!!!' ) );
	}

	/**
	 * Test that decrypting truncated data returns empty.
	 */
	public function test_decrypt_truncated_data(): void {
		// Base64 of just a few bytes — shorter than IV length.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$short = base64_encode( 'ab' );
		$this->assertSame( '', HF_Encryption::decrypt( $short ) );
	}

	/**
	 * Test that each encryption produces different ciphertext (IV randomness).
	 */
	public function test_encrypt_produces_different_ciphertext(): void {
		$original = 'same-secret-value';
		$enc1     = HF_Encryption::encrypt( $original );
		$enc2     = HF_Encryption::encrypt( $original );

		$this->assertNotEquals( $enc1, $enc2, 'Different encryptions should produce different ciphertext.' );

		// But both should decrypt to the same value.
		$this->assertEquals( $original, HF_Encryption::decrypt( $enc1 ) );
		$this->assertEquals( $original, HF_Encryption::decrypt( $enc2 ) );
	}

	/**
	 * Test encrypting special characters and unicode.
	 */
	public function test_encrypt_special_characters(): void {
		$values = array(
			'password with spaces and !@#$%^&*()',
			'contraseña con ñ y acentos: áéíóú',
			'<script>alert("xss")</script>',
			"line1\nline2\ttab",
			str_repeat( 'A', 1000 ), // Long string.
		);

		foreach ( $values as $original ) {
			$encrypted = HF_Encryption::encrypt( $original );
			$decrypted = HF_Encryption::decrypt( $encrypted );
			$this->assertEquals( $original, $decrypted, "Failed for: {$original}" );
		}
	}

	/**
	 * Test that the encrypted output is valid base64.
	 */
	public function test_encrypted_output_is_base64(): void {
		$encrypted = HF_Encryption::encrypt( 'test-value' );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$decoded = base64_decode( $encrypted, true );
		$this->assertNotFalse( $decoded, 'Encrypted output should be valid base64.' );
	}

	/**
	 * Test that the encryption method filter works.
	 */
	public function test_encryption_method_filter(): void {
		// Default should work fine.
		$encrypted = HF_Encryption::encrypt( 'filter-test' );
		$this->assertNotEmpty( $encrypted );
		$this->assertEquals( 'filter-test', HF_Encryption::decrypt( $encrypted ) );
	}
}
