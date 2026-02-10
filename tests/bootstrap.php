<?php
/**
 * PHPUnit bootstrap file for HostForge for WooCommerce.
 *
 * @package HostForge\Tests
 */

// Composer autoloader.
$composer_autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $composer_autoloader ) ) {
	require_once $composer_autoloader;
}

// Determine the tests directory (WordPress test library).
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Try wp-phpunit via composer.
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	$_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress test library at {$_tests_dir}\n"; // phpcs:ignore
	echo "Set WP_TESTS_DIR environment variable or install wp-phpunit.\n"; // phpcs:ignore
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested and WooCommerce.
 */
tests_add_filter(
	'muplugins_loaded',
	function () {
		// Load WooCommerce if available.
		$wc_file = getenv( 'WC_DIR' );

		if ( ! $wc_file || ! file_exists( $wc_file ) ) {
			// Try common paths.
			$candidates = array(
				dirname( __DIR__, 3 ) . '/woocommerce/woocommerce.php',
				dirname( __DIR__, 3 ) . '/woocommerce.latest-stable/woocommerce.php',
			);

			// Search the plugins directory dynamically.
			$plugins_dir = dirname( __DIR__, 2 );
			if ( is_dir( $plugins_dir ) ) {
				$dirs = glob( $plugins_dir . '/woocommerce*/woocommerce.php' );
				if ( ! empty( $dirs ) ) {
					$candidates = array_merge( $dirs, $candidates );
				}
			}

			foreach ( $candidates as $candidate ) {
				if ( file_exists( $candidate ) ) {
					$wc_file = $candidate;
					break;
				}
			}
		}

		if ( $wc_file && file_exists( $wc_file ) ) {
			require $wc_file;
		}

		// Load HostForge.
		require dirname( __DIR__ ) . '/hostforge-for-woocommerce.php';
	}
);

/**
 * Install WooCommerce tables and initialize HostForge for testing.
 */
tests_add_filter(
	'setup_theme',
	function () {
		if ( class_exists( 'WC_Install' ) ) {
			// Ensure WC tables exist.
			\WC_Install::install();
		}

		// Activate HostForge (DB tables + capabilities).
		if ( class_exists( 'HostForge\HF_Activator' ) ) {
			\HostForge\HF_Activator::activate();
		}

		// Initialize the plugin (normally runs on plugins_loaded).
		if ( function_exists( 'hostforge_init' ) ) {
			hostforge_init();
		}
	}
);

// Define testing constant.
if ( ! defined( 'HOSTFORGE_TESTING' ) ) {
	define( 'HOSTFORGE_TESTING', true );
}

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
