<?php
/**
 * Autoloader class. This is used to decrease memory consumption
 *
 * @package YITH\Subscription
 * @since   2.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Autoloader' ) ) {
	/**
	 * Class YITH_WC_Subscription_Autoloader
	 *
	 * @since 2.0.0
	 */
	class YITH_WC_Subscription_Autoloader {


		/**
		 * Constructor
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );
		}

		/**
		 * Get an array of registered paths
		 *
		 * @since  3.0.0
		 * @return array
		 */
		protected function get_registered_paths() {
			return apply_filters( 'yith_ywsbs_autoload_registered_path', array() );
		}

		/**
		 * Autoload callback
		 *
		 * @since  2.0.0
		 * @param string $classname Load the class.
		 */
		public function autoload( $classname ) {

			$path      = YITH_YWSBS_INC;
			$classname = str_replace( '_', '-', strtolower( $classname ) );
			$file      = "class-{$classname}.php";

			$registered_path = $this->get_registered_paths();

			if ( array_key_exists( $file, $registered_path ) ) {
				$path = $registered_path[ $file ];
			} elseif ( false !== strpos( $classname, 'trait' ) ) {
				$file  = 'trait-' . str_replace( '-trait', '', $classname ) . '.php';
				$path .= 'traits/';
			} elseif ( false !== strpos( $classname, 'legacy' ) ) {
				$file  = 'abstract-' . str_replace( '_', '-', $classname ) . '.php';
				$path .= 'legacy/';
			} elseif ( false !== strpos( $classname, 'module' ) ) {
				$path .= 'modules/';
			} elseif ( false !== strpos( $classname, 'privacy' ) ) {
				$path .= 'privacy/';
			} elseif ( false !== strpos( $classname, 'admin' ) ) {
				$path .= 'admin/';
			}

			if ( file_exists( $path . $file ) && is_readable( $path . $file ) ) {
				include_once $path . $file;
			}
		}
	}
}

new YITH_WC_Subscription_Autoloader();
