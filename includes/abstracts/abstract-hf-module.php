<?php
/**
 * Abstract Module base class.
 *
 * All HostForge modules must extend this class and implement
 * the required abstract methods.
 *
 * @package HostForge\Abstracts
 */

namespace HostForge\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Module
 */
abstract class HF_Module {

	/**
	 * Get the module identifier (slug).
	 *
	 * @return string E.g. 'server-manager'.
	 */
	abstract public function get_id(): string;

	/**
	 * Get the module display name.
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Get the module description.
	 *
	 * @return string
	 */
	abstract public function get_description(): string;

	/**
	 * Get required module dependencies (IDs of other modules).
	 *
	 * @return array<string>
	 */
	abstract public function get_dependencies(): array;

	/**
	 * Initialize the module: register hooks, load components.
	 *
	 * @return void
	 */
	abstract public function init(): void;

	/**
	 * Called when the module is activated.
	 *
	 * Override to create DB tables, schedule actions, etc.
	 *
	 * @return void
	 */
	public function activate(): void {}

	/**
	 * Called when the module is deactivated.
	 *
	 * Override to unschedule actions, etc.
	 * Do NOT delete data here — only on uninstall.
	 *
	 * @return void
	 */
	public function deactivate(): void {}

	/**
	 * Get admin menu items for this module.
	 *
	 * @return array<array{title: string, slug: string, capability: string, callback: callable}>
	 */
	public function get_admin_menu_items(): array {
		return array();
	}

	/**
	 * Get My Account endpoints for this module.
	 *
	 * @return array<array{endpoint: string, title: string}>
	 */
	public function get_myaccount_endpoints(): array {
		return array();
	}

	/**
	 * Register REST API routes for this module.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {}

	/**
	 * Register scheduled actions for this module.
	 *
	 * @return void
	 */
	public function register_scheduled_actions(): void {}

	/**
	 * Get dashboard widgets for this module.
	 *
	 * @return array<array{id: string, title: string, callback: callable}>
	 */
	public function get_dashboard_widgets(): array {
		return array();
	}

	/**
	 * Check if this module is currently active.
	 *
	 * @return bool
	 */
	final public function is_active(): bool {
		$active = get_option( 'hf_active_modules', array() );
		return is_array( $active ) && in_array( $this->get_id(), $active, true );
	}

	/**
	 * Get the module's base directory path.
	 *
	 * @return string
	 */
	final public function get_module_dir(): string {
		return HOSTFORGE_PLUGIN_DIR . 'modules/' . $this->get_id() . '/';
	}

	/**
	 * Get the module's base URL.
	 *
	 * @return string
	 */
	final public function get_module_url(): string {
		return HOSTFORGE_PLUGIN_URL . 'modules/' . $this->get_id() . '/';
	}
}
