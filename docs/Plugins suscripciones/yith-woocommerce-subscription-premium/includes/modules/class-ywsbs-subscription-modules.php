<?php
/**
 * Handle YITH WooCommerce Subscription Modules
 *
 * @class   YWSBS_Subscription_Modules
 * @since   3.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_VERSION' ) || exit;

if ( ! class_exists( 'YWSBS_Subscription_Modules' ) ) {
	/**
	 * Class YWSBS_Subscription_Modules
	 *
	 * @since  3.0.0
	 */
	class YWSBS_Subscription_Modules {

		/**
		 * Active modules option
		 *
		 * @const string
		 */
		const ACTIVE_MODULES = 'ywsbs_modules_active';

		/**
		 * Modules base path.
		 *
		 * @const string
		 */
		const BASE_PATH = YITH_YWSBS_INC . 'modules/';

		/**
		 * An array of modules paths to register.
		 *
		 * @var array
		 */
		protected static $modules_paths = array();

		/**
		 * Constructor
		 * Initialize plugin and registers actions and filters to be used.
		 *
		 * @since  3.0.0
		 */
		public static function init() {
			add_filter( 'yith_ywsbs_autoload_registered_path', array( __CLASS__, 'register_modules_paths' ), 10, 1 );
			add_filter( 'ywsbs_register_panel_tabs', array( __CLASS__, 'register_admin_tab' ), 10, 1 );
			add_action( 'yith_ywsbs_modules_tab', array( __CLASS__, 'output_modules_tab' ) );

			add_action( 'init', array( __CLASS__, 'load_modules' ), 0 );
		}

		/**
		 * Get loaded modules list.
		 *
		 * @since  3.0.0
		 * @return array
		 */
		public static function get_modules() {
			static $modules;
			if ( is_null( $modules ) && file_exists( self::BASE_PATH . 'modules.php' ) ) {
				$modules = include self::BASE_PATH . 'modules.php';
			}

			return apply_filters( 'ywsbs_modules_list', (array) $modules );
		}

		/**
		 * Load available modules.
		 *
		 * @since  3.0.0
		 */
		public static function load_modules() {
			foreach ( self::get_modules() as $module_id => $module ) {
				if ( ! self::is_module_active( $module_id ) ) { // do not go further with init.
					continue;
				}

				// Load init class.
				$init = self::get_module_init( $module_id );
				if ( $init ) {
					include_once $init;

					do_action( "ywsbs_module_{$module_id}_loaded" );
				}
			}
		}

		/**
		 * Get module init file
		 *
		 * @since  3.0.0
		 * @param string $module_id The module id.
		 * @return string
		 */
		protected static function get_module_init( $module_id ) {
			// Load init class.
			$init = self::BASE_PATH . $module_id . DIRECTORY_SEPARATOR . 'init.php';

			return file_exists( $init ) ? $init : '';
		}

		/**
		 * Check if given module is active.
		 *
		 * @since  3.0.0
		 * @param string $module_id The module ID to check.
		 * @return boolean
		 */
		public static function is_module_active( $module_id ) {
			static $active_modules;
			if ( is_null( $active_modules ) ) {
				$active_modules = get_option( self::ACTIVE_MODULES, array() );
			}

			return in_array( $module_id, $active_modules, true );
		}

		/**
		 * Check if given module is available in modules list.
		 *
		 * @since  3.0.0
		 * @param string $module_id The module ID to check.
		 * @return boolean
		 */
		public static function is_module_available( $module_id ) {
			return array_key_exists( $module_id, self::get_modules() );
		}

		/**
		 * Activate given module ID.
		 *
		 * @since  3.0.0
		 * @param string $module_id The module ID to activate.
		 * @return boolean
		 * @throws Exception Error on module switch activation.
		 */
		public static function activate( $module_id ) {
			if ( ! self::is_module_available( $module_id ) ) {
				// translators: %s stand for the module ID.
				throw new Exception( sprintf( _x( 'Module #%s is not a valid module ID.', 'Module activation switch error', 'yith-woocommerce-subscription' ), $module_id ) ); // phpcs:ignore
			}

			$active_modules   = get_option( self::ACTIVE_MODULES, array() );
			$active_modules[] = $module_id;

			if ( update_option( self::ACTIVE_MODULES, array_unique( $active_modules ) ) ) {

				// Make sure to load module init.
				$init = self::get_module_init( $module_id );
				if ( empty( $init ) ) {
					// translators: %s is the module id.
					throw new Exception( sprintf( _x( 'No valid init file found for module %s.', 'Module init file not found', 'yith-woocommerce-subscription' ), $module_id ) ); // phpcs:ignore
				}

				include_once $init;

				do_action( "ywsbs_module_{$module_id}_activated" );
				return true;
			}

			return false;
		}

		/**
		 * Deactivate given module ID.
		 *
		 * @since  3.0.0
		 * @param string $module_id The module ID to activate.
		 * @return boolean
		 * @throws Exception Error on module switch activation.
		 */
		public static function deactivate( $module_id ) {
			// It's not needed to check if key is valid.
			$active_modules = get_option( self::ACTIVE_MODULES, array() );
			$index          = array_search( $module_id, $active_modules, true );
			if ( false !== $index ) {
				unset( $active_modules[ $index ] );

				if ( update_option( self::ACTIVE_MODULES, $active_modules ) ) {
					// In this case we do not need to load init since the module is already loaded.
					do_action( "ywsbs_module_{$module_id}_deactivated" );
					return true;
				}
			}

			return false;
		}

		/**
		 * Switch module activation
		 *
		 * @since  3.0.0
		 * @param string $module_id The module ID to activate.
		 * @return boolean
		 * @throws Exception Error on module switch activation.
		 */
		public static function switch_activation( $module_id ) {
			if ( ! self::is_module_available( $module_id ) ) {
				// translators: %s stand for the module ID.
				throw new Exception( sprintf( _x( 'Module #%s is not a valid module ID.', 'Module activation switch error', 'yith-woocommerce-subscription' ), $module_id ) ); //phpcs:ignore
			}

			$method = self::is_module_active( $module_id ) ? 'deactivate' : 'activate';
			return self::$method( $module_id );
		}

		/**
		 * Register single module paths for autoload
		 *
		 * @since  3.0.0
		 * @param array $paths An array of registered paths for autoload.
		 * @return void
		 */
		public static function register_module_paths( $paths ) {
			self::$modules_paths = array_merge( self::$modules_paths, $paths );
		}

		/**
		 * Register module paths for autoload
		 *
		 * @since  3.0.0
		 * @param array $paths An array of registered paths for autoload.
		 * @return array
		 */
		public static function register_modules_paths( $paths ) {
			return array_merge( $paths, self::$modules_paths );
		}

		/**
		 * Gets the module ID from the init file.
		 * This method extracts the ID of a module from its filename.
		 *
		 * @since 3.0.0
		 *
		 * @param string $file The filename of module.
		 * @return string The module key.
		 */
		protected static function module_basename( $file ) {
			$file       = wp_normalize_path( $file );
			$plugin_dir = wp_normalize_path( self::BASE_PATH );

			// Get relative path from plugins directory.
			$file = str_replace( array( $plugin_dir, 'init.php' ), '', $file );
			$file = trim( $file, '/' );
			return $file;
		}

		/**
		 * Register a module activation hook
		 *
		 * @since  3.0.0
		 * @param string   $file     The filename init of module.
		 * @param callable $callback The function hooked to the 'ywsbs_module_{$module_id}_activated' action.
		 * @return void
		 */
		public static function register_module_activation_hook( $file, $callback ) {
			$module_id = self::module_basename( $file );
			add_action( "ywsbs_module_{$module_id}_activated", $callback );
		}

		/**
		 * Register a module deactivation hook
		 *
		 * @since  3.0.0
		 * @param string   $file     The filename init of module.
		 * @param callable $callback The function hooked to the 'ywsbs_module_{$module_id}_deactivated' action.
		 * @return void
		 */
		public static function register_module_deactivation_hook( $file, $callback ) {
			$module_id = self::module_basename( $file );
			add_action( "ywsbs_module_{$module_id}_deactivated", $callback );
		}


		/**
		 * Register admin tab to the main plugin panel tabs array
		 *
		 * @since  3.0.0
		 * @param array $tabs The array of panel tabs.
		 * @return array
		 */
		public static function register_admin_tab( $tabs ) {
			if ( ! empty( self::get_modules() ) ) {
				$tabs['modules'] = array(
					'title'       => __( 'Modules', 'yith-woocommerce-subscription' ),
					'description' => __( 'Extra modules to unlock advanced features for your subscription products.', 'yith-woocommerce-subscription' ),
					'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z"></path></svg>',
				);
			}

			return $tabs;
		}

		/**
		 * Modules list tab
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function output_modules_tab() {
			$modules_tab = YITH_YWSBS_VIEWS_PATH . '/panel/modules.php';
			if ( file_exists( $modules_tab ) ) {
				$modules = self::get_modules();
				include_once $modules_tab;
			}
		}
	}
}
