<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Module_Admin Class.
 * Collection of common hooks and methods for the admin section of a module
 *
 * @class   YWSBS_Subscription_Module_Admin
 * @since   3.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Module_Admin' ) ) {
	/**
	 * Class YWSBS_Subscription_Module_Admin
	 */
	abstract class YWSBS_Subscription_Module_Admin {

		/**
		 * The module ID
		 *
		 * @var string
		 */
		protected $module_id = '';

		/**
		 * Constructor
		 *
		 * @since  3.0.0
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Init
		 *
		 * @since  3.0.0
		 */
		protected function init() {
			if ( empty( $this->module_id ) ) {
				return;
			}

			add_filter( 'ywsbs_register_panel_tabs', array( $this, 'add_tabs' ), 10, 1 );
			add_filter( 'yith_plugin_panel_item_options_path', array( $this, 'module_options_path' ), 10, 4 );
			add_filter( 'yith_plugin_panel_sub_tab_item_options_path', array( $this, 'module_sub_tab_options_path' ), 10, 4 );
			// Handle product meta.
			add_action( 'ywsbs_before_save_custom_product_meta', array( $this, 'save_product_meta' ), 10, 2 );
			add_action( 'ywsbs_before_save_custom_product_variation_meta', array( $this, 'save_product_variation_meta' ), 10, 3 );
			add_action( 'ywsbs_before_reset_custom_product_meta', array( $this, 'reset_product_meta' ), 10, 1 );
		}

		/**
		 * Register the module options path
		 *
		 * @since  3.0.0
		 * @param string                       $path         Absolute options path.
		 * @param string                       $options_path Relative options path.
		 * @param string                       $item         Current options item.
		 * @param YIT_Plugin_Panel_WooCommerce $panel        Current panel.
		 * @return string
		 */
		public function module_options_path( $path, $options_path, $item, $panel ) {
			if ( ywsbs_is_admin_panel_page() && array_key_exists( $item, $this->get_tabs() ) ) {
				$path = $this->get_module_path() . "options/{$item}-options.php";
			}

			return $path;
		}

		/**
		 * Register the module options path
		 *
		 * @since  3.0.0
		 * @param string                       $sub_tab_path Absolute options path.
		 * @param array                        $sub_tabs     The sub tabs array.
		 * @param string                       $sub_item     Current options item.
		 * @param YIT_Plugin_Panel_WooCommerce $panel        Current panel.
		 * @return string
		 */
		public function module_sub_tab_options_path( $sub_tab_path, $sub_tabs, $sub_item, $panel ) {
			if ( ywsbs_is_admin_panel_page() ) {
				// Get the parent item name.
				$item = str_replace( array( $panel->settings['options-path'], "{$sub_item}-options.php", '/' ), '', $sub_tab_path );
				if ( array_key_exists( $item, $this->get_tabs() ) ) {
					$sub_tab_path = $this->get_module_path() . "options/{$item}/{$sub_item}.php";
				}
			}


			return $sub_tab_path;
		}

		/**
		 * Add module tabs to the plugin panel
		 *
		 * @since  3.0.0
		 * @param array $tabs The panel tabs array.
		 * @return array
		 */
		public function add_tabs( $tabs ) {
			$module_tabs = $this->get_tabs();
			if ( ! empty( $module_tabs ) ) {
				$ref_pos = array_search( 'customization', array_keys( $tabs ), true );
				$tabs    = array_slice( $tabs, 0, $ref_pos + 1, true ) + $module_tabs + array_slice( $tabs, $ref_pos + 1, count( $tabs ) - 1, true );
			}

			return $tabs;
		}

		/**
		 * Save custom meta for product variation
		 *
		 * @since  3.0.0
		 * @param WC_Product_Variation $variation The product variation object.
		 * @param integer              $index     The index variation.
		 * @param array                $posted    THe posted data.
		 * @return void
		 */
		public function save_product_variation_meta( $variation, $index, $posted ) {
			foreach ( $this->get_product_meta() as $meta_key ) {
				$variation->update_meta_data( $meta_key, $this->get_product_meta_posted_value( $posted, 'variable' . $meta_key, $index ) );
			}

			do_action( 'ywsbs_subscription_module_updated_product_variation_meta', $variation, $index );
		}

		/**
		 * Save custom meta for product
		 *
		 * @since  3.0.0
		 * @param WC_Product_Variation $product The product object.
		 * @param array                $posted  THe posted data.
		 * @return void
		 */
		public function save_product_meta( $product, $posted ) {
			foreach ( $this->get_product_meta() as $meta_key ) {
				$product->update_meta_data( $meta_key, $this->get_product_meta_posted_value( $posted, $meta_key ) );
			}

			do_action( 'ywsbs_subscription_module_updated_product_meta', $product );
		}

		/**
		 * Get posted meta value
		 *
		 * @since  3.0.0
		 * @param array   $posted The posted data.
		 * @param string  $key    The meta key to get value for.
		 * @param integer $index  (Optional) The index for array posted data. Default is false.
		 * @return mixed|null The meta posted value, null if not found.
		 */
		protected function get_product_meta_posted_value( $posted, $key, $index = false ) {
			if ( false === $index ) {
				return isset( $posted[ $key ] ) ? wc_clean( $posted[ $key ] ) : null;
			} else {
				return isset( $posted[ $key ][ $index ] ) ? wc_clean( $posted[ $key ][ $index ] ) : null;
			}
		}

		/**
		 * Reset custom meta for product
		 *
		 * @since  3.0.0
		 * @param WC_Product_Variation $product The product object.
		 * @return void
		 */
		public function reset_product_meta( $product ) {
			foreach ( $this->get_product_meta() as $meta_key ) {
				$product->delete_meta_data( $meta_key );
			}
		}

		/**
		 * Get product meta keys
		 *
		 * @since  3.0.0
		 * @return string|string[]
		 */
		protected function get_product_meta() {
			return array();
		}

		/**
		 * Get the module path
		 *
		 * @since  3.0.0
		 * @return string
		 */
		protected function get_module_path() {
			$key      = strtoupper( str_replace( '-', '_', $this->module_id ) );
			$constant = "YWSBS_{$key}_MODULE_PATH";
			return defined( $constant ) ? constant( $constant ) : YITH_YWSBS_INC . "modules/{$this->module_id}/";
		}

		/**
		 * Get module tabs
		 *
		 * @since  3.0.0
		 * @return array
		 */
		protected function get_tabs() {
			return array();
		}
	}
}
