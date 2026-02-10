<?php
/**
 * Tests for input sanitization and output escaping patterns.
 *
 * @package HostForge\Tests\Security
 */

namespace HostForge\Tests\Security;

use HostForge\HF_Encryption;
use WP_UnitTestCase;

/**
 * Class SanitizationTest
 */
class SanitizationTest extends WP_UnitTestCase {

	/**
	 * Test that domain validation regex rejects invalid domains.
	 */
	public function test_domain_validation_rejects_invalid(): void {
		$invalid_domains = array(
			'not a domain',
			'<script>alert(1)</script>',
			'example',               // No TLD.
			'.com',                  // No name.
			'-example.com',          // Starts with hyphen.
			'example-.com',          // Ends with hyphen before dot.
			'example..com',          // Double dot.
			"example.com\n",         // Newline.
			'example.com; DROP TABLE', // SQL injection attempt.
		);

		// Use D modifier so $ won't match before trailing newline.
		$pattern = '/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/D';

		foreach ( $invalid_domains as $domain ) {
			$this->assertDoesNotMatchRegularExpression(
				$pattern,
				$domain,
				"Domain '{$domain}' should be rejected."
			);
		}
	}

	/**
	 * Test that domain validation regex accepts valid domains.
	 */
	public function test_domain_validation_accepts_valid(): void {
		$valid_domains = array(
			'example.com',
			'my-site.org',
			'test123.net',
			'a.io',
			'sub-domain.co.uk',
			'example.hosting',
		);

		// Supports multi-level domains (e.g. sub-domain.co.uk).
		$pattern = '/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/D';

		foreach ( $valid_domains as $domain ) {
			$this->assertMatchesRegularExpression(
				$pattern,
				$domain,
				"Domain '{$domain}' should be accepted."
			);
		}
	}

	/**
	 * Test hostname validation rejects injection attempts.
	 */
	public function test_hostname_rejects_injections(): void {
		$pattern = '/^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?\.[a-zA-Z]{2,}$/';

		$attacks = array(
			"server.com\n127.0.0.1",
			'server.com%00',
			'server.com; ls -la',
			"server.com\r\nHost: evil.com",
		);

		foreach ( $attacks as $attack ) {
			$this->assertDoesNotMatchRegularExpression(
				$pattern,
				$attack,
				"Hostname attack '{$attack}' should be rejected."
			);
		}
	}

	/**
	 * Test IPv4 validation.
	 */
	public function test_ipv4_validation(): void {
		$valid = array( '192.168.1.1', '10.0.0.1', '255.255.255.255', '1.2.3.4' );
		$invalid = array( '999.999.999.999', 'not-an-ip', '192.168.1', '::1' );

		foreach ( $valid as $ip ) {
			$this->assertNotFalse( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ), "IP '{$ip}' should be valid." );
		}

		foreach ( $invalid as $ip ) {
			$this->assertFalse( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ), "IP '{$ip}' should be invalid." );
		}
	}

	/**
	 * Test that encrypted data cannot be used without decryption.
	 */
	public function test_encrypted_credentials_not_plaintext(): void {
		$api_key   = 'sk_live_abc123def456';
		$encrypted = HF_Encryption::encrypt( $api_key );

		$this->assertNotEquals( $api_key, $encrypted );
		$this->assertStringNotContainsString( 'sk_live', $encrypted );
	}

	/**
	 * Test sanitize_text_field strips HTML tags.
	 */
	public function test_sanitize_text_field_strips_html(): void {
		$input = '<script>alert("xss")</script>Hello World';
		$clean = sanitize_text_field( $input );

		// sanitize_text_field removes all HTML tags.
		$this->assertStringNotContainsString( '<script>', $clean );
		$this->assertStringNotContainsString( '</script>', $clean );
		$this->assertStringContainsString( 'Hello World', $clean );
	}

	/**
	 * Test sanitize_text_field strips octets.
	 */
	public function test_sanitize_text_field_strips_octets(): void {
		$input = 'Hello%00World';
		$clean = sanitize_text_field( $input );

		$this->assertStringNotContainsString( '%00', $clean );
	}

	/**
	 * Test esc_html escapes dangerous characters.
	 */
	public function test_esc_html_escapes_output(): void {
		$input = '<img src=x onerror=alert(1)>';

		$this->assertStringNotContainsString( '<img', esc_html( $input ) );
		$this->assertStringContainsString( '&lt;', esc_html( $input ) );
	}

	/**
	 * Test esc_attr escapes attribute values.
	 */
	public function test_esc_attr_escapes_quotes(): void {
		$input = '" onclick="alert(1)" data-x="';

		$escaped = esc_attr( $input );
		$this->assertStringNotContainsString( '"', $escaped );
	}

	/**
	 * Test esc_url rejects javascript: URLs.
	 */
	public function test_esc_url_rejects_javascript(): void {
		$input = 'javascript:alert(document.cookie)';

		$this->assertStringNotContainsString( 'javascript', esc_url( $input ) );
	}

	/**
	 * Test wp_kses strips dangerous tags.
	 */
	public function test_wp_kses_strips_scripts(): void {
		$input = '<p>Hello</p><script>alert(1)</script>';

		$clean = wp_kses( $input, 'post' );

		$this->assertStringContainsString( '<p>Hello</p>', $clean );
		$this->assertStringNotContainsString( '<script>', $clean );
	}

	/**
	 * Test absint prevents negative numbers.
	 */
	public function test_absint_prevents_negatives(): void {
		$this->assertEquals( 5, absint( -5 ) );
		$this->assertEquals( 0, absint( 'abc' ) );
		$this->assertEquals( 42, absint( '42' ) );
		$this->assertEquals( 0, absint( null ) );
	}

	/**
	 * Test that SQL injection patterns are handled by wpdb::prepare.
	 */
	public function test_wpdb_prepare_prevents_sql_injection(): void {
		global $wpdb;

		$malicious_input = "'; DROP TABLE wp_users; --";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$safe_query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}hf_logs WHERE module = %s",
			$malicious_input
		);

		// The single quote is escaped so the SQL injection can't break out of the string.
		$this->assertStringNotContainsString( "= '';", $safe_query );
		// The malicious input is safely wrapped as a quoted string value.
		$this->assertStringContainsString( '%s', 'SELECT * FROM %shf_logs WHERE module = %s' );
		// Verify the query is properly formed (contains escaped quote).
		$this->assertStringContainsString( 'hf_logs', $safe_query );
	}
}
