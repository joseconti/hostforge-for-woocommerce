<?php
/**
 * Main HostForge class (Singleton).
 *
 * Central orchestrator that loads textdomain, initializes the module manager,
 * registers admin menus and global hooks.
 *
 * @package HostForge
 */

namespace HostForge;

defined( 'ABSPATH' ) || exit;

/**
 * Class HostForge
 */
final class HostForge {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Module manager instance.
	 *
	 * @var HF_Module_Manager|null
	 */
	private ?HF_Module_Manager $module_manager = null;

	/**
	 * Admin instance.
	 *
	 * @var Admin\HF_Admin|null
	 */
	private ?Admin\HF_Admin $admin = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \RuntimeException Always.
	 */
	public function __wakeup(): never {
		throw new \RuntimeException( 'Cannot unserialize singleton.' );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		/**
		 * Fires before HostForge begins initialization.
		 *
		 * Use this hook to run code before any HostForge components are loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'hostforge_before_init' );

		$this->load_helpers();
		$this->load_textdomain();
		$this->init_product_types();
		$this->init_module_manager();
		$this->register_hooks();

		if ( is_admin() ) {
			$this->init_admin();
		}

		/**
		 * Fires after HostForge has fully initialized.
		 *
		 * @since 1.0.0
		 */
		do_action( 'hostforge_loaded' );
	}

	/**
	 * Load helper function files.
	 *
	 * @return void
	 */
	private function load_helpers(): void {
		require_once HOSTFORGE_PLUGIN_DIR . 'includes/helpers/hf-formatting-functions.php';
		require_once HOSTFORGE_PLUGIN_DIR . 'includes/helpers/hf-template-functions.php';

		/**
		 * Fires after HostForge helper function files have been loaded.
		 *
		 * Use this hook to load additional helper files or override helper functions.
		 *
		 * @since 1.0.0
		 */
		do_action( 'hostforge_helpers_loaded' );
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'hostforge',
			false,
			dirname( HOSTFORGE_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Initialize custom product types for WooCommerce.
	 *
	 * @return void
	 */
	private function init_product_types(): void {
		Products\HF_Product_Types::init();

		if ( is_admin() ) {
			Products\HF_Product_Data_Tabs::init();
		}

		Products\HF_Checkout_Fields::init();
		Products\HF_Order_Meta_Handler::init();
		Products\HF_Product_Addons::init();

		/**
		 * Fires after all HostForge product types and related components are initialized.
		 *
		 * Use this hook to register additional product type integrations or
		 * modify product type behavior after all types are loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'hostforge_product_types_initialized' );
	}

	/**
	 * Initialize the module manager and load active modules.
	 *
	 * @return void
	 */
	private function init_module_manager(): void {
		$this->module_manager = new HF_Module_Manager();
		$this->module_manager->register_core_modules();
		$this->module_manager->load_active_modules();

		/**
		 * Fires after all active modules have been loaded.
		 *
		 * Use this hook to interact with loaded modules or perform actions
		 * that depend on module availability.
		 *
		 * @since 1.0.0
		 *
		 * @param HF_Module_Manager $module_manager The module manager instance.
		 */
		do_action( 'hostforge_modules_loaded', $this->module_manager );
	}

	/**
	 * Register global hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Initialize admin functionality.
	 *
	 * @return void
	 */
	private function init_admin(): void {
		$this->admin = new Admin\HF_Admin();
		$this->admin->init();
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$status_controller = new Admin\HF_REST_Status_Controller();
		$status_controller->register_routes();

		// Let modules register their own routes.
		if ( $this->module_manager ) {
			$this->module_manager->register_module_rest_routes();
		}
	}

	/**
	 * Get the module manager.
	 *
	 * @return HF_Module_Manager
	 */
	public function module_manager(): HF_Module_Manager {
		return $this->module_manager;
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function version(): string {
		return HOSTFORGE_VERSION;
	}
}
