<?php
/**
 * Username Generator.
 *
 * Generates unique hosting panel usernames from domain names.
 * Follows cPanel/Plesk username rules: max 8 chars, lowercase
 * alphanumeric, must start with a letter.
 *
 * @package HostForge\Modules\AutoProvisioning
 */

namespace HostForge\Modules\AutoProvisioning;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Username_Generator
 */
class HF_Username_Generator {

	/**
	 * Maximum username length (cPanel limit).
	 *
	 * @var int
	 */
	private const MAX_LENGTH = 8;

	/**
	 * Generate a unique username from a domain.
	 *
	 * @param string $domain  Domain name.
	 * @param int    $user_id WordPress user ID.
	 * @return string Generated username.
	 */
	public static function generate( string $domain, int $user_id = 0 ): string {
		$base = self::create_base( $domain );

		/**
		 * Filter the generated username.
		 *
		 * @param string $username Generated username.
		 * @param string $domain   Original domain.
		 * @param int    $user_id  WordPress user ID.
		 */
		$username = apply_filters( 'hostforge_generated_username', $base, $domain, $user_id );

		// Ensure uniqueness.
		$username = self::ensure_unique( $username );

		return $username;
	}

	/**
	 * Create a base username from a domain.
	 *
	 * @param string $domain Domain name (e.g. "example.com").
	 * @return string Base username (max 8 chars, lowercase alpha start).
	 */
	private static function create_base( string $domain ): string {
		// Remove TLD and www prefix.
		$domain = strtolower( $domain );
		$domain = preg_replace( '/^www\./', '', $domain );
		$parts  = explode( '.', $domain );
		$name   = $parts[0] ?? '';

		// Keep only lowercase letters and digits.
		$name = preg_replace( '/[^a-z0-9]/', '', $name );

		// Must start with a letter.
		if ( empty( $name ) || ! ctype_alpha( $name[0] ) ) {
			$name = 'hf' . $name;
		}

		// Truncate to max length.
		if ( strlen( $name ) > self::MAX_LENGTH ) {
			$name = substr( $name, 0, self::MAX_LENGTH );
		}

		// Minimum length: pad with random chars.
		if ( strlen( $name ) < 3 ) {
			$name .= substr( wp_generate_password( 5, false, false ), 0, self::MAX_LENGTH - strlen( $name ) );
		}

		return strtolower( $name );
	}

	/**
	 * Ensure the username is unique across all servers.
	 *
	 * Checks existing hf_service posts for duplicate usernames.
	 * Appends incrementing numbers if needed.
	 *
	 * @param string $username Base username.
	 * @return string Unique username.
	 */
	private static function ensure_unique( string $username ): string {
		$original = $username;
		$counter  = 1;

		while ( self::username_exists( $username ) ) {
			$suffix   = (string) $counter;
			$max_base = self::MAX_LENGTH - strlen( $suffix );
			$username = substr( $original, 0, $max_base ) . $suffix;
			++$counter;

			// Safety valve.
			if ( $counter > 999 ) {
				$username = 'hf' . substr( wp_generate_password( 6, false, false ), 0, 6 );
				break;
			}
		}

		return $username;
	}

	/**
	 * Check if a username already exists in any service.
	 *
	 * @param string $username Username to check.
	 * @return bool
	 */
	private static function username_exists( string $username ): bool {
		$existing = get_posts(
			array(
				'post_type'      => 'hf_service',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_hf_panel_username',
						'value' => $username,
					),
				),
			)
		);

		return ! empty( $existing );
	}
}
