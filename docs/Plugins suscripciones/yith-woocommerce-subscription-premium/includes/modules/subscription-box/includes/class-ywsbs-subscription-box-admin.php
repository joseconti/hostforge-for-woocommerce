<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_Admin Class.
 * Handle the admin section for module "subscription box"
 *
 * @class   YWSBS_Subscription_Box_Admin
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.


if ( ! class_exists( 'YWSBS_Subscription_Box_Admin' ) ) {
	/**
	 * Class YWSBS_Subscription_Synchronization_Admin
	 */
	class YWSBS_Subscription_Box_Admin extends YWSBS_Subscription_Module_Admin {

		/**
		 * The module ID
		 *
		 * @since 4.0.0
		 * @var string
		 */
		protected $module_id = 'subscription-box';

		/**
		 * Constructor
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'init', array( $this, 'register_scripts' ), 5 );
			add_action( 'init', array( $this, 'init_hooks' ), 5 );
		}

		/**
		 * Register admin scripts
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function register_scripts() {

			YITH_WC_Subscription_Assets::get_instance()->add_admin_script(
				'ywsbs-subscription-box-admin',
				YWSBS_SUBSCRIPTION_BOX_MODULE_URL . 'assets/js/product-admin.js',
				array( 'jquery', 'wp-util' ),
				array( $this, 'check_script_enqueue' )
			);

			YITH_WC_Subscription_Assets::get_instance()->localize_script(
				'ywsbs-subscription-box-admin',
				'ywsbsProductAdmin',
				array(
					'buttonLabel' => _x( 'Save', '[Admin]button label', 'yith-woocommerce-subscription' ),
				)
			);

			YITH_WC_Subscription_Assets::get_instance()->add_admin_style(
				'ywsbs-subscription-box-admin',
				YWSBS_SUBSCRIPTION_BOX_MODULE_URL . 'assets/css/product-admin.css',
				array(),
				array( $this, 'check_script_enqueue' )
			);
		}

		/**
		 * Init class hooks and filters
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function init_hooks() {
			// Add product tabs.
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'hide_product_general_tab' ), 99 );
			add_filter( 'ywsbs_subscription_product_settings_tabs', array( $this, 'add_product_data_tab' ), 20, 1 );
			add_filter( 'woocommerce_product_data_panels', array( $this, 'add_product_data_panel' ) );
			// Add box options to subscription settings.
			add_action( 'ywsbs_before_single_product_options', array( $this, 'add_box_price_options' ) );
			add_action( 'ywsbs_after_single_product_options', array( $this, 'add_box_editing_options' ), 5 );
			// Sync custom meta with default product data.
			add_action( 'ywsbs_subscription_module_updated_product_meta', array( $this, 'sync_product_data' ), 10, 1 );
			// Add subscription box content list.
			add_action( 'woocommerce_order_item_line_item_html', array( $this, 'list_order_item_box_content' ), 10, 3 );
			add_action( 'ywsbs_admin_table_after_subscription_item', array( $this, 'list_subscription_box_content' ), 10, 1 );

			add_filter( 'mce_buttons', array( $this, 'remove_fullscreen_from_modal_editor' ), 10, 2 );
		}

		/**
		 * Check if admin script must be enqueued
		 *
		 * @since  4.0.0
		 * @return boolean
		 */
		public function check_script_enqueue() {
			return ywsbs_check_valid_admin_page( 'product' );
		}

		/**
		 * Get module tabs
		 *
		 * @since  4.0.0
		 * @return array
		 */
		protected function get_tabs() {
			return array(
				'subscription-box' => array(
					'title' => __( 'Subscription box', 'yith-woocommerce-subscription' ),
					'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>',
				),
			);
		}

		/**
		 * Filter product data tabs
		 *
		 * @since  4.0.0
		 * @param array $tabs An array of product data tabs.
		 * @return array
		 */
		public function hide_product_general_tab( $tabs ) {
			// Hide general tab.
			$tabs['general']['class'][] = 'hide_if_ywsbs-subscription-box';
			return $tabs;
		}

		/**
		 * Add product data tab to subscription tabs
		 *
		 * @since 4.0.0
		 * @param array $tabs The subscription product tabs.
		 * @return array
		 */
		public function add_product_data_tab( $tabs ) {
			$tabs['subscription-settings']['class'][] = 'show_if_ywsbs-subscription-box';
			$tabs                                     = array_merge(
				$tabs,
				array(
					'subscription-box-options' => array(
						'label'    => _x( 'Box', 'Product options tab title', 'yith-woocommerce-subscription' ),
						'target'   => 'subscription_box_data',
						'class'    => array( 'show_if_ywsbs-subscription-box' ),
						'priority' => 12,
					),
				)
			);
			return $tabs;
		}

		/**
		 * Add product data panel for subscription box option
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function add_product_data_panel() {
			global $product_object;

			$box_steps = array_filter( (array) $product_object->get_meta( '_ywsbs_box_steps' ) );
			// Map box steps and format options.
			$box_steps              = array_map(
				function ( $step ) {
					if ( ! empty( $step['products'] ) ) {
						$products = array();
						foreach ( $step['products'] as $product_id ) {
							$products[ $product_id ] = trim( yith_plugin_fw_get_post_formatted_name( $product_id ) );
						}
						$step['products'] = wp_json_encode( $products );
					}

					if ( ! empty( $step['categories'] ) ) {
						$categories = array();
						foreach ( $step['categories'] as $category_id ) {
							$category                   = get_term( $category_id, 'product_cat' );
							$categories[ $category_id ] = trim( $category->name );
						}
						$step['categories'] = wp_json_encode( $categories );
					}

					return $step;
				},
				$box_steps
			);
			$enable_price_threshold = $product_object->get_meta( '_ywsbs_box_enable_price_threshold' );
			$price_threshold        = array_filter( (array) $product_object->get_meta( '_ywsbs_box_price_threshold' ) );
			$price_threshold        = array_map( 'wc_format_localized_price', $price_threshold );

			include YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'views/product/data-panel.php';

			$step_content = array(
				'all_products'        => __( 'All products', 'yith-woocommerce-subscription' ),
				'specific_products'   => __( 'Specific products', 'yith-woocommerce-subscription' ),
				'specific_categories' => __( 'Specific categories', 'yith-woocommerce-subscription' ),
			);

			include YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'views/product/box-step-template.php';
		}

		/**
		 * Get product meta keys
		 *
		 * @since  4.0.0
		 * @return string[]
		 */
		protected function get_product_meta() {
			return array(
				'_ywsbs_box_price_type',
				'_ywsbs_box_price',
				'_ywsbs_box_discount',
				'_ywsbs_box_discount_type',
				'_ywsbs_box_discount_value',
				'_ywsbs_box_email_day_schedule',
				'_ywsbs_box_editing_until',
				'_ywsbs_box_steps',
				'_ywsbs_box_enable_price_threshold',
				'_ywsbs_box_price_threshold',
			);
		}

		/**
		 * Add box prices options
		 *
		 * @since  4.0.0
		 * @param WC_Product $product The product object.
		 * @return void
		 */
		public function add_box_price_options( $product ) {
			$price_types = apply_filters(
				'ywsbs_subscription_box_price_options',
				array(
					'sum'   => __( 'Add up product prices', 'yith-woocommerce-subscription' ),
					'fixed' => __( 'Fixed price', 'yith-woocommerce-subscription' ),
				)
			);

			$discount_types = apply_filters(
				'ywsbs_subscription_box_price_discount_options',
				array(
					// translators: %s stand for the currency symbol.
					'fixed'      => sprintf( _x( '%s - fixed', '[Admin]product discount label.', 'yith-woocommerce-subscription' ), get_woocommerce_currency_symbol() ),
					'percentage' => _x( '% - percentage', '[Admin]product discount label', 'yith-woocommerce-subscription' ),
				)
			);

			$price_type       = $product->get_meta( '_ywsbs_box_price_type' );
			$price            = wc_format_localized_price( $product->get_meta( '_ywsbs_box_price' ) );
			$discount_enabled = $product->get_meta( '_ywsbs_box_discount' );
			$discount_type    = $product->get_meta( '_ywsbs_box_discount_type' );
			$discount_value   = wc_format_localized_price( $product->get_meta( '_ywsbs_box_discount_value' ) );

			include YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'views/product/box-price-options.php';
		}

		/**
		 * Add box editing options
		 *
		 * @since  4.0.0
		 * @param WC_Product $product The product object.
		 * @return void
		 */
		public function add_box_editing_options( $product ) {
			$email_day_schedule = $product->get_meta( '_ywsbs_box_email_day_schedule' ) ?: 1; // phpcs:ignore
			$box_editing_until  = $product->get_meta( '_ywsbs_box_editing_until' ) ?: 1; // phpcs:ignore

			include YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'views/product/box-editing-options.php';
		}

		/**
		 * Get posted meta value
		 *
		 * @since  4.0.0
		 * @param array   $posted   The posted data.
		 * @param string  $key The meta key to get value for.
		 * @param integer $index    (Optional) The index for array posted data. Default is false.
		 * @return mixed|null The meta posted value, null if not found.
		 */
		protected function get_product_meta_posted_value( $posted, $key, $index = false ) {

			if ( '_ywsbs_box_steps' === $key && isset( $posted[ $key ] ) ) {
				$box_steps = (array) $posted[ $key ];

				$formatted_box_steps = array();
				$step_index          = 0;

				foreach ( $box_steps as $step ) {

					// Do not change ID if label or text change.
					$id_params = array_diff_key( $step, array_flip( array( 'label', 'text' ) ) );
					$id        = substr( md5( $step_index . '|' . wp_json_encode( $id_params ) ), -12, 10 );

					foreach ( $step as $k => $value ) {
						$formatted_box_steps[ $id ][ $k ] = 'text' === $k ? wp_kses_post( $value ) : wc_clean( $value );
					}

					$formatted_box_steps[ $id ]['enabled_threshold'] = isset( $step['enabled_threshold'] ) ? 'yes' : 'no';
					++$step_index;
				}

				return $formatted_box_steps;
			}

			if ( in_array( $key, array( '_ywsbs_box_price', '_ywsbs_box_discount_value' ), true ) && isset( $posted[ $key ] ) ) {
				return wc_format_decimal( wc_clean( $posted[ $key ] ) );
			}

			if ( in_array( $key, array( '_ywsbs_box_price_threshold' ), true ) && isset( $posted[ $key ] ) ) {
				return array_map( 'wc_format_decimal', wc_clean( $posted[ $key ] ) );
			}

			return parent::get_product_meta_posted_value( $posted, $key, $index );
		}

		/**
		 * Sync default product data with custom meta
		 * Since default meta are set before custom one, we need to set again product default price to avoid mismatch in DB values.
		 *
		 * @since  4.0.0
		 * @param WC_Product $product The product object.
		 * @return void
		 */
		public function sync_product_data( $product ) {
			// Skip for no subscription-box product.
			if ( ! $product->is_type( YWSBS_Subscription_Box::PRODUCT_TYPE ) ) {
				return;
			}

			$product->set_date_on_sale_from();
			$product->set_date_on_sale_to();

			$price_type    = $product->get_meta( '_ywsbs_box_price_type' );
			$regular_price = 'fixed' === $price_type ? $product->get_meta( '_ywsbs_box_price' ) : '';
			$product->set_regular_price( $regular_price );

			$sale_price = $product->get_discounted_price( $regular_price );
			$product->set_sale_price( $sale_price );
		}

		/**
		 * Remove fullscreen button from modal editor.
		 *
		 * @since  4.0.0
		 * @param array  $buttons   The buttons array.
		 * @param string $editor_id The editor id.
		 * @return array
		 */
		public function remove_fullscreen_from_modal_editor( $buttons, $editor_id ) {
			if ( 'ywsbs_box_steps_text_editor' === $editor_id ) {
				$buttons = array_filter(
					$buttons,
					function ( $button ) {
						return 'fullscreen' !== $button;
					}
				);
			}

			return $buttons;
		}

		/**
		 * List order item box content
		 *
		 * @since  4.0.0
		 * @param string        $item_id The order item ID.
		 * @param WC_Order_Item $item    The order item object.
		 * @param WC_Order      $order   The order object.
		 * @return void
		 */
		public function list_order_item_box_content( $item_id, $item, $order ) {
			$subscription_info = wc_get_order_item_meta( $item_id, '_subscription_info' );
			if ( empty( $subscription_info ) || empty( $subscription_info['box_data']['content'] ) ) {
				return;
			}

			$this->output_box_content_list( $subscription_info['box_data']['content'] );
		}

		/**
		 * List subscription box content
		 *
		 * @since  4.0.0
		 * @param YWSBS_Subscription $subscription Subscription object.
		 * @return void
		 */
		public function list_subscription_box_content( $subscription ) {
			if ( ! ywsbs_is_a_box_subscription( $subscription ) ) {
				return;
			}

			$box_content = $subscription->get( 'box_content' );

			echo '<tbody class="ywsbs-box-content-items">';
			$this->output_box_content_list( $box_content );
			echo '</tbody>';
		}

		/**
		 * Output box content list
		 *
		 * @since  4.0.0
		 * @param array $box_content The box content to output.
		 * @return void
		 */
		protected function output_box_content_list( $box_content ) {
			$placeholder_image = wc_placeholder_img( 'thumbnail' );
			$can_edit_product  = current_user_can( 'edit_products' ); // phpcs:ignore

			// Plain box content.
			$plain_box_content = array();
			foreach ( $box_content as $step_id => $step_items ) {
				foreach ( $step_items as $item ) {
					$product_id = $item['product'];

					if ( isset( $plain_box_content[ $product_id ] ) ) {
						$plain_box_content[ $product_id ] += $item['quantity'];
					} else {

						$product                               = wc_get_product( $product_id );
						$plain_box_content[ $item['product'] ] = $item['quantity'];
					}
				}
			}

			include YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'views/subscription/box-content.php';
		}
	}
}
