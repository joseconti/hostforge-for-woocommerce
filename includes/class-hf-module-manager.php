<?php
/**
 * Module Manager.
 *
 * Registers, activates, deactivates and loads modules.
 * Active modules are stored in the hf_active_modules option.
 *
 * @package HostForge
 */

namespace HostForge;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Module_Manager
 */
class HF_Module_Manager {

	/**
	 * Option name for active modules.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'hf_active_modules';

	/**
	 * Registered modules (id => class name).
	 *
	 * @var array<string, string>
	 */
	private array $registered = array();

	/**
	 * Loaded module instances.
	 *
	 * @var array<string, Abstracts\HF_Module>
	 */
	private array $loaded = array();

	/**
	 * Register a module.
	 *
	 * @param string $module_id    Module identifier (e.g. 'server-manager').
	 * @param string $class_name   Fully-qualified class name.
	 * @return void
	 */
	public function register_module( string $module_id, string $class_name ): void {
		$this->registered[ $module_id ] = $class_name;
	}

	/**
	 * Register all core modules.
	 *
	 * @return void
	 */
	public function register_core_modules(): void {
		$modules = array(
			'server-manager'    => 'HostForge\\Modules\\ServerManager\\HF_Server_Manager_Module',
			'auto-provisioning' => 'HostForge\\Modules\\AutoProvisioning\\HF_Auto_Provisioning_Module',
			'support-desk'      => 'HostForge\\Modules\\SupportDesk\\HF_Support_Desk_Module',
			'domain-manager'    => 'HostForge\\Modules\\DomainManager\\HF_Domain_Manager_Module',
			'security'          => 'HostForge\\Modules\\Security\\HF_Security_Module',
			'notifications'     => 'HostForge\\Modules\\Notifications\\HF_Notifications_Module',
			'reports'           => 'HostForge\\Modules\\Reports\\HF_Reports_Module',
		);

		/**
		 * Filter the list of registered modules.
		 *
		 * @param array $modules Associative array of module_id => class_name.
		 */
		$modules = apply_filters( 'hostforge_registered_modules', $modules );

		foreach ( $modules as $id => $class ) {
			$this->register_module( $id, $class );
		}
	}

	/**
	 * Load all active modules.
	 *
	 * @return void
	 */
	public function load_active_modules(): void {
		$active = $this->get_active_module_ids();

		foreach ( $active as $module_id ) {
			$this->load_module( $module_id );
		}
	}

	/**
	 * Load a single module by ID.
	 *
	 * @param string $module_id Module identifier.
	 * @return bool True if loaded successfully.
	 */
	public function load_module( string $module_id ): bool {
		if ( isset( $this->loaded[ $module_id ] ) ) {
			return true;
		}

		if ( ! isset( $this->registered[ $module_id ] ) ) {
			return false;
		}

		$class_name = $this->registered[ $module_id ];

		if ( ! class_exists( $class_name ) ) {
			return false;
		}

		$module = new $class_name();

		if ( ! $module instanceof Abstracts\HF_Module ) {
			return false;
		}

		// Verify dependencies are active.
		foreach ( $module->get_dependencies() as $dep_id ) {
			if ( ! $this->is_module_active( $dep_id ) ) {
				return false;
			}
		}

		$module->init();
		$this->loaded[ $module_id ] = $module;

		/**
		 * Fires after a module has been loaded and initialized.
		 *
		 * @since 1.0.0
		 *
		 * @param string                    $module_id The module identifier.
		 * @param Abstracts\HF_Module       $module    The loaded module instance.
		 */
		do_action( 'hostforge_module_loaded', $module_id, $module );

		return true;
	}

	/**
	 * Activate a module.
	 *
	 * @param string $module_id Module identifier.
	 * @return bool True if activated.
	 */
	public function activate_module( string $module_id ): bool {
		if ( ! isset( $this->registered[ $module_id ] ) ) {
			return false;
		}

		$class_name = $this->registered[ $module_id ];

		if ( ! class_exists( $class_name ) ) {
			return false;
		}

		$module = new $class_name();

		// Check dependencies.
		foreach ( $module->get_dependencies() as $dep_id ) {
			if ( ! $this->is_module_active( $dep_id ) ) {
				return false;
			}
		}

		/**
		 * Fires before a module is activated.
		 *
		 * Allows third-party code to perform setup tasks or validation
		 * before a module is activated.
		 *
		 * @since 1.0.0
		 *
		 * @param string $module_id  The module identifier.
		 * @param string $class_name The fully-qualified module class name.
		 */
		do_action( 'hostforge_before_module_activate', $module_id, $class_name );

		$active = $this->get_active_module_ids();

		if ( ! in_array( $module_id, $active, true ) ) {
			$active[] = $module_id;
			update_option( self::OPTION_NAME, $active );
		}

		$module->activate();

		/**
		 * Fires when a module is activated.
		 *
		 * @param string $module_id The module identifier.
		 */
		do_action( 'hostforge_module_activated', $module_id );

		return true;
	}

	/**
	 * Deactivate a module.
	 *
	 * Also deactivates dependent modules.
	 *
	 * @param string $module_id Module identifier.
	 * @return bool True if deactivated.
	 */
	public function deactivate_module( string $module_id ): bool {
		$active = $this->get_active_module_ids();

		if ( ! in_array( $module_id, $active, true ) ) {
			return false;
		}

		/**
		 * Fires before a module is deactivated.
		 *
		 * Allows third-party code to perform cleanup tasks or prevent
		 * deactivation side-effects before a module is deactivated.
		 *
		 * @since 1.0.0
		 *
		 * @param string $module_id The module identifier.
		 */
		do_action( 'hostforge_before_module_deactivate', $module_id );

		// Deactivate dependent modules first.
		foreach ( $this->get_dependent_modules( $module_id ) as $dep_module_id ) {
			$this->deactivate_module( $dep_module_id );
		}

		// Call module's deactivate hook.
		if ( isset( $this->loaded[ $module_id ] ) ) {
			$this->loaded[ $module_id ]->deactivate();
			unset( $this->loaded[ $module_id ] );
		}

		$active = array_values( array_diff( $this->get_active_module_ids(), array( $module_id ) ) );
		update_option( self::OPTION_NAME, $active );

		/**
		 * Fires when a module is deactivated.
		 *
		 * @param string $module_id The module identifier.
		 */
		do_action( 'hostforge_module_deactivated', $module_id );

		return true;
	}

	/**
	 * Get IDs of active modules.
	 *
	 * @return array<string>
	 */
	public function get_active_module_ids(): array {
		$active = get_option( self::OPTION_NAME, array() );
		$active = is_array( $active ) ? $active : array();

		/**
		 * Filters the list of active module IDs.
		 *
		 * Allows third-party code to programmatically enable or disable modules
		 * without changing the database option.
		 *
		 * @since 1.0.0
		 *
		 * @param array $active Array of active module identifier strings.
		 */
		return apply_filters( 'hostforge_active_module_ids', $active );
	}

	/**
	 * Check if a module is active.
	 *
	 * @param string $module_id Module identifier.
	 * @return bool
	 */
	public function is_module_active( string $module_id ): bool {
		return in_array( $module_id, $this->get_active_module_ids(), true );
	}

	/**
	 * Get all registered modules with their info.
	 *
	 * @return array<string, array{id: string, name: string, description: string, dependencies: array, active: bool}>
	 */
	public function get_all_modules_info(): array {
		$info   = array();
		$active = $this->get_active_module_ids();

		foreach ( $this->registered as $id => $class_name ) {
			if ( ! class_exists( $class_name ) ) {
				continue;
			}

			$module = isset( $this->loaded[ $id ] ) ? $this->loaded[ $id ] : new $class_name();

			$info[ $id ] = array(
				'id'           => $module->get_id(),
				'name'         => $module->get_name(),
				'description'  => $module->get_description(),
				'dependencies' => $module->get_dependencies(),
				'active'       => in_array( $id, $active, true ),
			);
		}

		return $info;
	}

	/**
	 * Get a loaded module instance.
	 *
	 * @param string $module_id Module identifier.
	 * @return Abstracts\HF_Module|null
	 */
	public function get_module( string $module_id ): ?Abstracts\HF_Module {
		return $this->loaded[ $module_id ] ?? null;
	}

	/**
	 * Register REST routes for all loaded modules.
	 *
	 * @return void
	 */
	public function register_module_rest_routes(): void {
		foreach ( $this->loaded as $module ) {
			$module->register_rest_routes();
		}
	}

	/**
	 * Get modules that depend on a given module.
	 *
	 * @param string $module_id Module identifier.
	 * @return array<string> Module IDs that depend on the given module.
	 */
	private function get_dependent_modules( string $module_id ): array {
		$dependents = array();
		$active     = $this->get_active_module_ids();

		foreach ( $active as $active_id ) {
			if ( ! isset( $this->registered[ $active_id ] ) ) {
				continue;
			}

			$class_name = $this->registered[ $active_id ];

			if ( ! class_exists( $class_name ) ) {
				continue;
			}

			$module = isset( $this->loaded[ $active_id ] ) ? $this->loaded[ $active_id ] : new $class_name();

			if ( in_array( $module_id, $module->get_dependencies(), true ) ) {
				$dependents[] = $active_id;
			}
		}

		return $dependents;
	}
}
