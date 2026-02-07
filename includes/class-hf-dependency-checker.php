<?php
/**
 * Dependency checker.
 *
 * Verifies PHP version, WordPress version and WooCommerce availability.
 *
 * @package HostForge
 */

namespace HostForge;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Dependency_Checker
 */
class HF_Dependency_Checker {

	/**
	 * Check all dependencies.
	 *
	 * @return array<string> List of error messages. Empty if all OK.
	 */
	public static function check(): array {
		$errors = array();

		if ( version_compare( PHP_VERSION, HOSTFORGE_MIN_PHP, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: required PHP version, 2: current PHP version */
				__( 'PHP %1$s or higher is required. Current version: %2$s.', 'hostforge' ),
				HOSTFORGE_MIN_PHP,
				PHP_VERSION
			);
		}

		if ( version_compare( get_bloginfo( 'version' ), HOSTFORGE_MIN_WP, '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: required WordPress version */
				__( 'WordPress %s or higher is required.', 'hostforge' ),
				HOSTFORGE_MIN_WP
			);
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			$errors[] = __( 'WooCommerce must be installed and active.', 'hostforge' );
		} elseif ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, HOSTFORGE_MIN_WC, '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: required WooCommerce version */
				__( 'WooCommerce %s or higher is required.', 'hostforge' ),
				HOSTFORGE_MIN_WC
			);
		}

		return $errors;
	}

	/**
	 * Check if Action Scheduler is available.
	 *
	 * @return bool
	 */
	public static function has_action_scheduler(): bool {
		return function_exists( 'as_schedule_single_action' );
	}

	/**
	 * Check if a subscription plugin is active.
	 *
	 * @return string|null The detected plugin slug or null.
	 */
	public static function detect_subscription_plugin(): ?string {
		if ( class_exists( 'WC_Subscriptions' ) ) {
			return 'wcs';
		}

		if ( defined( 'YITH_YWSBS_PREMIUM' ) || class_exists( 'YITH_WC_Subscription' ) ) {
			return 'yith';
		}

		if ( class_exists( 'Advanced_Subscriptions_For_WooCommerce' ) ) {
			return 'advanced-subs';
		}

		return null;
	}

	/**
	 * Check if all dependencies are met.
	 *
	 * @return bool
	 */
	public static function is_compatible(): bool {
		return empty( self::check() );
	}
}
