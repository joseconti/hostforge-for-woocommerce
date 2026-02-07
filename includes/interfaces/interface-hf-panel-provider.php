<?php
/**
 * Panel Provider Interface.
 *
 * Contract for hosting panel providers (cPanel, Plesk).
 *
 * @package HostForge\Interfaces
 */

namespace HostForge\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface HF_Panel_Provider
 */
interface HF_Panel_Provider {

	/**
	 * Test the connection to the server.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function test_connection(): array;

	/**
	 * Create a hosting account.
	 *
	 * @param array $params {
	 *     Account parameters.
	 *     @type string $domain   Primary domain.
	 *     @type string $username Account username.
	 *     @type string $password Account password.
	 *     @type string $plan     Hosting plan/package name.
	 *     @type string $email    Contact email.
	 * }
	 * @return array{success: bool, message: string, data: array}
	 */
	public function create_account( array $params ): array;

	/**
	 * Suspend a hosting account.
	 *
	 * @param string $username Account username.
	 * @param string $reason   Reason for suspension.
	 * @return array{success: bool, message: string}
	 */
	public function suspend_account( string $username, string $reason = '' ): array;

	/**
	 * Unsuspend a hosting account.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, message: string}
	 */
	public function unsuspend_account( string $username ): array;

	/**
	 * Terminate (permanently delete) a hosting account.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, message: string}
	 */
	public function terminate_account( string $username ): array;

	/**
	 * Change the password for a hosting account.
	 *
	 * @param string $username     Account username.
	 * @param string $new_password New password.
	 * @return array{success: bool, message: string}
	 */
	public function change_password( string $username, string $new_password ): array;

	/**
	 * Change the hosting plan/package for an account.
	 *
	 * @param string $username Account username.
	 * @param string $plan     New plan/package name.
	 * @return array{success: bool, message: string}
	 */
	public function change_package( string $username, string $plan ): array;

	/**
	 * Get available hosting packages from the server.
	 *
	 * @return array{success: bool, packages: array}
	 */
	public function get_packages(): array;

	/**
	 * Get resource usage for an account.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, data: array}
	 */
	public function get_account_usage( string $username ): array;

	/**
	 * Get server statistics (load, memory, disk, etc.).
	 *
	 * @return array{success: bool, data: array}
	 */
	public function get_server_stats(): array;

	/**
	 * Get SSO URL for a user to access the panel.
	 *
	 * @param string $username Account username.
	 * @return array{success: bool, url: string}
	 */
	public function get_sso_url( string $username ): array;
}
