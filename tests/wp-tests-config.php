<?php
/**
 * WordPress test configuration.
 *
 * This file is used by the WordPress test library when running tests locally.
 * When running inside wp-env, the database is pre-configured.
 *
 * @package HostForge\Tests
 */

// Path to WordPress codebase.
define( 'ABSPATH', getenv( 'WP_CORE_DIR' ) ? getenv( 'WP_CORE_DIR' ) . '/' : '/tmp/wordpress/' );

// Test database settings — use a separate database!
define( 'DB_NAME', getenv( 'WP_DB_NAME' ) ? getenv( 'WP_DB_NAME' ) : 'wordpress_test' );
define( 'DB_USER', getenv( 'WP_DB_USER' ) ? getenv( 'WP_DB_USER' ) : 'root' );
define( 'DB_PASSWORD', getenv( 'WP_DB_PASS' ) ? getenv( 'WP_DB_PASS' ) : '' );
define( 'DB_HOST', getenv( 'WP_DB_HOST' ) ? getenv( 'WP_DB_HOST' ) : 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
$table_prefix = 'wptests_';

// Test-specific settings.
define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'HostForge Tests' );
define( 'WP_PHP_BINARY', 'php' );

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );

// Authentication keys and salts.
define( 'AUTH_KEY', 'test-auth-key-for-hostforge-testing' );
define( 'SECURE_AUTH_KEY', 'test-secure-auth-key' );
define( 'LOGGED_IN_KEY', 'test-logged-in-key' );
define( 'NONCE_KEY', 'test-nonce-key' );
define( 'AUTH_SALT', 'test-auth-salt-for-encryption-tests' );
define( 'SECURE_AUTH_SALT', 'test-secure-auth-salt' );
define( 'LOGGED_IN_SALT', 'test-logged-in-salt' );
define( 'NONCE_SALT', 'test-nonce-salt' );
