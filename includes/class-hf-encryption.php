<?php
/**
 * Encryption utility.
 *
 * Encrypts and decrypts sensitive data (API credentials) using OpenSSL
 * with wp_salt('auth') as the encryption key.
 *
 * @package HostForge
 */

namespace HostForge;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Encryption
 */
class HF_Encryption {

	/**
	 * Cipher method.
	 *
	 * @var string
	 */
	private const CIPHER = 'aes-256-cbc';

	/**
	 * Encrypt a value.
	 *
	 * @param string $value Plain text value.
	 * @return string Base64-encoded encrypted value with IV prepended.
	 */
	public static function encrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$key       = self::get_key();
		$iv_length = openssl_cipher_iv_length( self::CIPHER );
		$iv        = openssl_random_pseudo_bytes( $iv_length );
		$encrypted = openssl_encrypt( $value, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $encrypted ) {
			return '';
		}

		// Prepend IV to encrypted data and base64 encode.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt a value.
	 *
	 * @param string $value Base64-encoded encrypted value.
	 * @return string Decrypted plain text or empty string on failure.
	 */
	public static function decrypt( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$key = self::get_key();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$data = base64_decode( $value, true );

		if ( false === $data ) {
			return '';
		}

		$iv_length = openssl_cipher_iv_length( self::CIPHER );

		if ( strlen( $data ) <= $iv_length ) {
			return '';
		}

		$iv        = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );
		$decrypted = openssl_decrypt( $encrypted, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

		return false !== $decrypted ? $decrypted : '';
	}

	/**
	 * Get the encryption key derived from wp_salt.
	 *
	 * @return string 32-byte key.
	 */
	private static function get_key(): string {
		return hash( 'sha256', wp_salt( 'auth' ), true );
	}
}
