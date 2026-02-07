<?php
/**
 * Formatting helper functions.
 *
 * @package HostForge\Helpers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Format bytes into a human-readable string.
 *
 * @param int $bytes    Number of bytes.
 * @param int $decimals Number of decimal places.
 * @return string Formatted string (e.g. "1.5 GB").
 */
function hf_format_bytes( int $bytes, int $decimals = 2 ): string {
	if ( $bytes <= 0 ) {
		return '0 B';
	}

	$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	$index = (int) floor( log( $bytes, 1024 ) );
	$index = min( $index, count( $units ) - 1 );

	return round( $bytes / pow( 1024, $index ), $decimals ) . ' ' . $units[ $index ];
}

/**
 * Format a status string with badge HTML.
 *
 * @param string $status Status slug (active, suspended, terminated, pending, etc.).
 * @return string HTML badge.
 */
function hf_format_status_badge( string $status ): string {
	$classes = array(
		'active'     => 'hf-badge--success',
		'pending'    => 'hf-badge--warning',
		'suspended'  => 'hf-badge--error',
		'terminated' => 'hf-badge--inactive',
		'cancelled'  => 'hf-badge--inactive',
		'open'       => 'hf-badge--info',
		'closed'     => 'hf-badge--inactive',
	);

	$class = $classes[ $status ] ?? 'hf-badge--inactive';

	return sprintf(
		'<span class="hf-badge %s">%s</span>',
		esc_attr( $class ),
		esc_html( ucfirst( $status ) )
	);
}

/**
 * Sanitize and validate a domain name.
 *
 * @param string $domain Domain name input.
 * @return string Sanitized domain or empty string if invalid.
 */
function hf_sanitize_domain( string $domain ): string {
	$domain = strtolower( trim( $domain ) );
	$domain = preg_replace( '/^https?:\/\//', '', $domain );
	$domain = preg_replace( '/\/.*$/', '', $domain );
	$domain = rtrim( $domain, '.' );

	if ( ! preg_match( '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\.[a-z]{2,}$/', $domain ) ) {
		return '';
	}

	return $domain;
}

/**
 * Generate a random secure password.
 *
 * @param int $length Password length.
 * @return string
 */
function hf_generate_password( int $length = 16 ): string {
	return wp_generate_password( $length, true, false );
}

/**
 * Generate a username from a domain name.
 *
 * Creates an 8-character alphanumeric username from the domain.
 *
 * @param string $domain Domain name.
 * @return string
 */
function hf_generate_username( string $domain ): string {
	// Remove TLD and special characters.
	$parts = explode( '.', $domain );
	$name  = $parts[0] ?? $domain;
	$name  = preg_replace( '/[^a-z0-9]/', '', strtolower( $name ) );

	// Ensure minimum length.
	if ( strlen( $name ) < 4 ) {
		$name .= wp_generate_password( 4, false, false );
	}

	// Truncate to 8 characters.
	return substr( $name, 0, 8 );
}
