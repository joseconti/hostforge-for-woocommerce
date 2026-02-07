<?php
/**
 * Password Generator.
 *
 * Generates secure random passwords for hosting panel accounts.
 *
 * @package HostForge\Modules\AutoProvisioning
 */

namespace HostForge\Modules\AutoProvisioning;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Password_Generator
 */
class HF_Password_Generator {

	/**
	 * Default password length.
	 *
	 * @var int
	 */
	private const DEFAULT_LENGTH = 16;

	/**
	 * Generate a secure password.
	 *
	 * @param int $service_id Optional service ID for filtering.
	 * @return string Generated password.
	 */
	public static function generate( int $service_id = 0 ): string {
		$length = absint( get_option( 'hf_password_length', self::DEFAULT_LENGTH ) );

		if ( $length < 12 ) {
			$length = 12;
		}

		if ( $length > 32 ) {
			$length = 32;
		}

		// Generate a password with special characters that are safe for most panels.
		$password = self::create_password( $length );

		/**
		 * Filter the generated password.
		 *
		 * @param string $password   Generated password.
		 * @param int    $service_id Service post ID.
		 */
		return apply_filters( 'hostforge_generated_password', $password, $service_id );
	}

	/**
	 * Create a random password meeting complexity requirements.
	 *
	 * Ensures at least one uppercase, one lowercase, one digit and one special char.
	 *
	 * @param int $length Password length.
	 * @return string
	 */
	private static function create_password( int $length ): string {
		$lowercase = 'abcdefghijklmnopqrstuvwxyz';
		$uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$digits    = '0123456789';
		$special   = '!@#$%^&*()-_=+';

		// Guarantee at least one of each type.
		$password  = $lowercase[ random_int( 0, strlen( $lowercase ) - 1 ) ];
		$password .= $uppercase[ random_int( 0, strlen( $uppercase ) - 1 ) ];
		$password .= $digits[ random_int( 0, strlen( $digits ) - 1 ) ];
		$password .= $special[ random_int( 0, strlen( $special ) - 1 ) ];

		// Fill the rest.
		$all = $lowercase . $uppercase . $digits . $special;

		for ( $i = 4; $i < $length; $i++ ) {
			$password .= $all[ random_int( 0, strlen( $all ) - 1 ) ];
		}

		// Shuffle to avoid predictable character positions.
		$chars    = str_split( $password );
		$shuffled = array();

		while ( ! empty( $chars ) ) {
			$index      = random_int( 0, count( $chars ) - 1 );
			$shuffled[] = $chars[ $index ];
			array_splice( $chars, $index, 1 );
		}

		return implode( '', $shuffled );
	}
}
