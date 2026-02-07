<?php
/**
 * Assets class. This is used to load script and styles.
 *
 * @package YITH\Subscription
 * @since   2.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Assets' ) ) {
	/**
	 * Class that handles the assets
	 *
	 * @class  YITH_WC_Subscription_Assets
	 */
	class YITH_WC_Subscription_Assets extends YITH_WC_Subscription_Assets_Legacy {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * An array of JS assets
		 *
		 * @var array
		 */
		protected $scripts = array();

		/**
		 * An array of CSS assets
		 *
		 * @var array
		 */
		protected $styles = array();

		/**
		 * An array of translatable script
		 *
		 * @var array
		 */
		protected $translatable_scripts = array();

		/**
		 * YITH_WC_Subscription_Assets constructor.
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'init' ), 0 );
			// Register screen for WC scripts.
			add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_ids' ), 10, 1 );
			// Register.
			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 15 );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ), 15 );
			// // Enqueue.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
			// Script Translations.
			add_filter( 'pre_load_script_translations', array( $this, 'script_translations' ), 10, 4 );

			add_action( 'template_redirect', array( $this, 'check_blocks' ) );
		}


		/**
		 * Check if the checkout block is set to enqueue scripts
		 *
		 * @return void
		 */
		public function check_blocks() {
			if ( has_block( 'woocommerce/checkout-actions-block' ) ) {
				wp_enqueue_script( 'yith_ywsbs_wc_blocks' );
			}
		}

		/**
		 * Init class variables
		 *
		 * @since  3.0.0
		 */
		public function init() {
			$deps          = include YITH_YWSBS_DIR . '/dist/wc-blocks/index.asset.php';
			$this->scripts = array(
				'admin'    => array(
					'yith-ywsbs-timepicker'    => array(
						'src'  => 'jquery-ui-timepicker-addon.min.js',
						'deps' => array( 'jquery-ui-datepicker' ),
					),
					'yith-ywsbs-admin'         => array(
						'src'      => 'ywsbs-admin.js',
						'deps'     => array( 'jquery', \YIT_Assets::wc_script_handle( 'wc-jquery-blockui' ), 'jquery-ui-datepicker', 'jquery-ui-dialog', 'selectWoo', 'wc-enhanced-select', 'yith-plugin-fw-fields', 'wp-util' ),
						'localize' => array(
							'ywsbs_admin',
							array(
								'ajaxurl'       => admin_url( 'admin-ajax.php' ),
								'modules_nonce' => wp_create_nonce( 'ywsbs_module_activation_switch' ),
							),
						),
					),
					'ywsbs-subscription-admin' => array(
						'src'      => 'ywsbs-subscription-admin.js',
						'deps'     => array( 'jquery', 'yith-ywsbs-timepicker', \YIT_Assets::wc_script_handle( 'wc-jquery-blockui' ), 'woocommerce_admin', 'jquery-ui-dialog', 'selectWoo', 'yith-plugin-fw-fields', 'wc-enhanced-select' ),
						'localize' => array(
							'ywsbs_subscription_admin',
							array(
								'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
								'block_loader'             => apply_filters( 'yith_ywsbs_block_loader_admin', YITH_YWSBS_ASSETS_URL . '/images/block-loader.gif' ),
								'time_format'              => apply_filters( 'ywsbs_time_format', 'Y-m-d H:i:s' ),
								'copy_billing'             => esc_html__( 'Are you sure to copy the billing information into the shipping details? This will remove any current shipping information.', 'yith-woocommerce-subscription' ),
								'load_billing'             => esc_html__( 'Are you sure to load the customer\'s billing information? This will remove the current billing information.', 'yith-woocommerce-subscription' ),
								'no_customer_selected'     => esc_html__( 'Unregistered user', 'yith-woocommerce-subscription' ),
								'get_customer_details_nonce' => wp_create_nonce( 'get-customer-details' ),
								'save_item_nonce'          => wp_create_nonce( 'save-item-nonce' ),
								'recalculate_nonce'        => wp_create_nonce( 'recalculate_nonce' ),
								'load_shipping'            => esc_html__( 'Are you sure to load the customer\'s shipping information? This will remove the current shipping information.', 'yith-woocommerce-subscription' ),
								'back_to_all_subscription' => esc_html__( 'back to all subscriptions', 'yith-woocommerce-subscription' ),
								'url_back_to_all_subscription' => add_query_arg( array( 'post_type' => YITH_YWSBS_POST_TYPE ), admin_url( 'edit.php' ) ),
								'add_coupon_text'          => esc_html_x( 'Enter a coupon code to apply. Discounts are applied to line totals, before taxes.', 'text displayed on a popup in administrator subscription detail', 'yith-woocommerce-subscription' ),
							),
						),
					),
					'yith-ywsbs-product'       => array(
						'src'  => 'ywsbs-product-editor.js',
						'deps' => array( 'jquery', 'yith-plugin-fw-fields' ),
					),
					'yith-ywsbs-order'         => array(
						'src'      => 'ywsbs-order-editor.js',
						'deps'     => array( 'jquery' ),
						'localize' => array(
							'ywsbs_order_admin',
							array(
								'order_label'       => esc_html__( 'Subscription main order', 'yith-woocommerce-subscription' ),
								'order_label_renew' => esc_html__( 'Subscription renew', 'yith-woocommerce-subscription' ),
								'warning_message'   => esc_html__( 'Attention! Changing the order status of a subscription in pending renewal will prevent the subscription from functioning correctly.', 'yith-woocommerce-subscription' ),
							),
						),
					),
					'yith-ywsbs-admin-coupon'  => array(
						'src'  => 'ywsbs-subscription-coupon.js',
						'deps' => array( 'jquery' ),
					),
				),
				'frontend' => array(
					'yith_ywsbs_frontend'  => array(
						'src'      => 'ywsbs-frontend.js',
						'deps'     => array( 'jquery', 'wc-add-to-cart-variation', \YIT_Assets::wc_script_handle( 'wc-jquery-blockui' ), 'wp-util' ),
						'localize' => array(
							'yith_ywsbs_frontend',
							array(
								'ajaxurl'            => admin_url( 'admin-ajax.php' ),
								'add_to_cart_label'  => apply_filters( 'ywsbs_add_to_cart_variation_label', get_option( 'ywsbs_add_to_cart_label' ) ),
								'default_cart_label' => apply_filters( 'ywsbs_add_to_cart_default_label', __( 'Add to cart', 'yith-woocommerce-subscription' ) ),
							),
						),
					),
					'yith_ywsbs_wc_blocks' => array(
						'src'      => 'index.js',
						'url'      => YITH_YWSBS_URL . 'dist/wc-blocks/',
						'deps'     => $deps['dependencies'],
						'localize' => array(
							'yith_ywsbs_wc_blocks',
							array(
								'checkout_label' => get_option( 'ywsbs_place_order_label', apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'yith-woocommerce-subscription' ) ) ),
							),
						),
					),
				),
			);

			$this->styles = array(
				'admin'    => array(
					'yith-ywsbs-backend' => array(
						'src'    => 'backend.css',
						'deps'   => array( 'woocommerce_admin_styles', 'jquery-ui-style' ),
						'inline' => $this->get_subscription_status_inline_style(),
					),
					'yith-ywsbs-product' => array(
						'src'  => 'ywsbs-product-editor.css',
						'deps' => array( 'yith-plugin-fw-fields' ),
					),
					'yith-ywsbs-order'   => array(
						'src'    => 'ywsbs-order-editor.css',
						'inline' => $this->get_subscription_status_inline_style(),
					),
				),
				'frontend' => array(
					'yith_ywsbs_frontend' => array(
						'src'    => 'frontend.css',
						'inline' => $this->get_frontend_inline_style(),
					),
				),
			);
		}

		/**
		 * Add screen ID to the array of WC admin screen ids
		 *
		 * @since  3.0.0
		 * @param array $screen_ids An array of screen ids.
		 * @return array
		 */
		public function add_screen_ids( $screen_ids ) {
			$screen_ids = array_merge(
				$screen_ids,
				array(
					'yith-plugins_page_' . YITH_WC_Subscription_Admin::get_instance()->get_panel_page_slug(),
					YITH_YWSBS_POST_TYPE,
					'edit-' . YITH_YWSBS_POST_TYPE,
				)
			);

			return $screen_ids;
		}

		/**
		 * Add a new admin script
		 *
		 * @since  3.0.0
		 * @param string  $handle       The script handler.
		 * @param string  $src          The script src.
		 * @param array   $deps         (Optional) The script deps array. If empty array 'jquery' is added as dep. Default empty.
		 * @param mixed   $check        (Optional) The check method for the script. Default is null.
		 * @param boolean $translatable (Optional) Whether the script must be set for translations. Default 'false'.
		 */
		public function add_admin_script( $handle, $src, $deps = array(), $check = null, $translatable = false ) {
			$this->scripts['admin'][ $handle ] = array_filter( compact( 'src', 'deps', 'check', 'translatable' ) );
		}

		/**
		 * Add a new frontend script
		 *
		 * @since  3.0.0
		 * @param string  $handle       The script handler.
		 * @param string  $src          The script src.
		 * @param array   $deps         (Optional) The script deps array. If empty array 'jquery' is added as dep, if null no dep is added.
		 * @param mixed   $check        (Optional) The check method for the script, must be 'callable'. If set, the script is enqueued only if the check returns true. Default is null.
		 * @param boolean $translatable (Optional) Whether the script must be set for translations. Default 'false'.
		 */
		public function add_frontend_script( $handle, $src, $deps = array(), $check = null, $translatable = false ) {
			$this->scripts['frontend'][ $handle ] = array_filter( compact( 'src', 'deps', 'check', 'translatable' ) );
		}

		/**
		 * Add script dependencies
		 *
		 * @since 3.0.0
		 * @param string          $handle The script handle.
		 * @param string|string[] $deps   A single dep or an array of deps to add to the script.
		 * @return void
		 */
		public function add_script_deps( $handle, $deps ) {
			foreach ( $this->scripts as $section => &$section_scripts ) {
				if ( ! isset( $section_scripts[ $handle ] ) ) {
					continue;
				}

				$this->add_deps( $section_scripts[ $handle ], $deps );
			}
		}

		/**
		 * Remove a script from main array
		 *
		 * @since  3.0.0
		 * @param string $handle The script handle to remove.
		 * @return void
		 */
		public function remove_script( $handle ) {
			unset( $this->scripts['admin'][ $handle ], $this->scripts['frontend'][ $handle ] );
		}

		/**
		 * Localize script
		 *
		 * @since  3.0.0
		 * @param string $handle        Script handle the data will be attached to.
		 * @param string $localize_key  Name for the JavaScript object. Passed directly, so it should be qualified JS variable.
		 *                              Example: '/[a-zA-Z0-9_]+/'.
		 * @param array  $localize_data The data itself. The data can be either a single or multi-dimensional array.
		 */
		public function localize_script( $handle, $localize_key, $localize_data ) {
			foreach ( $this->scripts as $section => &$section_scripts ) {
				if ( ! isset( $section_scripts[ $handle ] ) ) {
					continue;
				}

				if ( did_action( 'wp_enqueue_scripts' ) && wp_style_is( $handle, 'registered' ) ) {
					wp_localize_script( $handle, $localize_key, $localize_data );
				} else {
					$section_scripts[ $handle ]['localize'] = array( $localize_key, $localize_data );
				}
			}
		}

		/**
		 * Get admin scripts
		 *
		 * @since  3.0.0
		 * @return array
		 */
		public function get_admin_scripts() {
			return $this->get_scripts( 'admin' );
		}

		/**
		 * Get frontend scripts
		 *
		 * @since  3.0.0
		 * @return array
		 */
		public function get_frontend_scripts() {
			return $this->get_scripts( 'frontend' );
		}

		/**
		 * Get scripts
		 *
		 * @since  3.0.0
		 * @param string $section The section to return.
		 * @return array
		 */
		protected function get_scripts( $section ) {
			return isset( $this->scripts[ $section ] ) ? $this->scripts[ $section ] : array();
		}

		/**
		 * Add a new admin style
		 *
		 * @since  3.0.0
		 * @param string $handle The script handler.
		 * @param string $src    The script src.
		 * @param array  $deps   (Optional) The style deps array. Default empty array.
		 * @param mixed  $check  (Optional) The check method for the style, must be 'callable'. If set, the style is enqueued only if the check returns true. Default is null.
		 */
		public function add_admin_style( $handle, $src, $deps = array(), $check = null ) {
			$this->styles['admin'][ $handle ] = array_filter( compact( 'src', 'deps', 'check' ) );
		}

		/**
		 * Add a new frontend style
		 *
		 * @since  3.0.0
		 * @param string $handle The script handler.
		 * @param string $src    The script src.
		 * @param array  $deps   (Optional) The style deps array. Default empty array.
		 * @param mixed  $check  (Optional) The check method for the style, must be 'callable'. If set, the style is enqueued only if the check returns true. Default is null.
		 */
		public function add_frontend_style( $handle, $src, $deps = array(), $check = null ) {
			$this->styles['frontend'][ $handle ] = array_filter( compact( 'src', 'deps', 'check' ) );
		}

		/**
		 * Add style dependencies
		 *
		 * @since 3.0.0
		 * @param string          $handle The style handle.
		 * @param string|string[] $deps   A single dep or an array of deps to add to the style.
		 * @return void
		 */
		public function add_style_deps( $handle, $deps ) {
			foreach ( $this->styles as $section => &$section_scripts ) {
				if ( ! isset( $section_scripts[ $handle ] ) ) {
					continue;
				}

				$this->add_deps( $section_scripts[ $handle ], $deps );
			}
		}

		/**
		 * Add inline style script
		 *
		 * @since  3.0.0
		 * @param string $handle Style handle the custom CSS will be attached to.
		 * @param string $style  The style to add inline.
		 */
		public function inline_style( $handle, $style ) {
			foreach ( $this->styles as $section => &$section_stiles ) {
				if ( ! isset( $section_stiles[ $handle ] ) ) {
					continue;
				}

				if ( wp_style_is( $handle, 'enqueued' ) ) {
					wp_add_inline_style( $handle, $style );
				} else {
					$section_stiles[ $handle ]['inline'] = $style;
				}
			}
		}

		/**
		 * Remove a style from main array
		 *
		 * @since  3.0.0
		 * @param string $handle The style handle to remove.
		 * @return void
		 */
		public function remove_style( $handle ) {
			unset( $this->styles['admin'][ $handle ], $this->styles['frontend'][ $handle ] );
		}

		/**
		 * Get admin stylesheets
		 *
		 * @since  3.0.0
		 * @return array
		 */
		public function get_admin_styles() {
			return $this->get_styles( 'admin' );
		}

		/**
		 * Get frontend stylesheets
		 *
		 * @since  3.0.0
		 * @return array
		 */
		public function get_frontend_styles() {
			return $this->get_styles( 'frontend' );
		}

		/**
		 * Get scripts
		 *
		 * @since  3.0.0
		 * @param string $section The section to return.
		 * @return array
		 */
		protected function get_styles( $section ) {
			return isset( $this->styles[ $section ] ) ? $this->styles[ $section ] : array();
		}

		/**
		 * Add deps to a predefined array of deps
		 *
		 * @since  3.0.0
		 * @param array           $script Current script data.
		 * @param string|string[] $deps   A single dep or an array of deps to add to the script.
		 * @return void
		 */
		protected function add_deps( &$script, $deps ) {
			$script['deps'] = isset( $script['deps'] ) ? array_unique( array_merge( $script['deps'], (array) $deps ) ) : (array) $deps;
		}

		/**
		 * Return the script src URL
		 *
		 * @since  3.0.0
		 * @param array  $script The script data.
		 * @param string $type The script type.
		 * @return string
		 */
		protected function get_script_src( $script, $type ) {

			if ( empty( $script['src'] ) ) {
				return false;
			}
			$url = $script['url'] ?? YITH_YWSBS_ASSETS_URL . "/{$type}/";

			$file_url = false !== strpos( $script['src'], 'http' ) ? $script['src'] : $url . $script['src'];
			// Maybe minify for JS.
			if ( 'js' === $type && false !== strpos( '.min.js', $file_url ) ) {
				$file_url = yit_load_js_file( $file_url );
			}

			return $file_url;
		}

		/**
		 * Check assets function
		 *
		 * @since  3.0.0
		 * @param string $handle The check script handle.
		 * @param mixed  $check  The check function.
		 * @return boolean
		 */
		protected function do_check( $handle, $check ) {

			switch ( $handle ) {
				case 'yith-ywsbs-admin-coupon':
					$response = ywsbs_check_valid_admin_page( 'shop_coupon' );
					break;

				case 'ywsbs-subscription-admin':
					$response = ywsbs_check_valid_admin_page( YITH_YWSBS_POST_TYPE );
					break;

				case 'yith-ywsbs-admin':
				case 'yith-ywsbs-backend':
					$response = ywsbs_check_valid_admin_page( YITH_YWSBS_POST_TYPE ) || ywsbs_is_admin_panel_page();
					break;

				case 'yith-ywsbs-product':
					$response = ywsbs_check_valid_admin_page( array( 'product', 'product_variable' ) );
					break;

				case 'yith-ywsbs-order':
					$response = ywsbs_check_valid_admin_page( 'shop_order' );
					break;

				default:
					$response = is_callable( $check ) ? ! ! call_user_func( $check, $handle ) : true;
					break;
			}

			return apply_filters( 'ywsbs_assets_check_response', $response, $handle );
		}

		/**
		 * Register scripts
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public function register_scripts() {

			$section = 'admin_enqueue_scripts' === current_action() ? 'admin' : 'frontend';

			foreach ( $this->get_scripts( $section ) as $handle => $script ) {
				$src = $this->get_script_src( $script, 'js' );
				if ( empty( $src ) ) {
					continue;
				}

				wp_register_script( $handle, $src, $script['deps'] ?? array(), $script['version'] ?? YITH_YWSBS_VERSION, $script['footer'] ?? true );

				if ( ! empty( $script['translatable'] ) && function_exists( 'wp_set_script_translations' ) ) {
					$this->translatable_scripts[] = $handle;
					wp_set_script_translations( $handle, 'yith-woocommerce-subscription', YITH_YWSBS_DIR . 'languages' );
				}
			}

			foreach ( $this->get_styles( $section ) as $handle => $style ) {
				$src = $this->get_script_src( $style, 'css' );
				if ( empty( $src ) ) {
					continue;
				}

				wp_register_style( $handle, $src, $style['deps'] ?? array(), $style['version'] ?? YITH_YWSBS_VERSION );
			}
		}

		/**
		 * Enqueue scripts
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public function enqueue_scripts() {

			$section = 'admin_enqueue_scripts' === current_action() ? 'admin' : 'frontend';

			foreach ( $this->get_scripts( $section ) as $handle => $script ) {
				// Check for conditions.
				if ( ! wp_script_is( $handle, 'registered' ) || ! $this->do_check( $handle, $script['check'] ?? '' ) ) {
					continue;
				}

				wp_enqueue_script( $handle );
				// Add localize if any.
				if ( ! empty( $script['localize'] ) && is_array( $script['localize'] ) ) {
					list( $localize_key, $localize_data ) = $script['localize'];
					wp_localize_script( $handle, $localize_key, $localize_data );
				}
			}

			foreach ( $this->get_styles( $section ) as $handle => $style ) {
				// Check for conditions.
				if ( ! wp_style_is( $handle, 'registered' ) || ! $this->do_check( $handle, $style['check'] ?? '' ) ) {
					continue;
				}

				wp_enqueue_style( $handle );
				if ( ! empty( $style['inline'] ) ) {
					wp_add_inline_style( $handle, $style['inline'] );
				}
			}
		}

		/**
		 * Generate custom css.
		 *
		 * @return string
		 */
		public function get_frontend_inline_style() {
			$status_colors = ywsbs_get_status_colors();
			$trial_color   = get_option( 'ywsbs_show_trial_period_color', '#467484' );
			$fee_color     = get_option( 'ywsbs_show_fee_color_color', '#467484' );

			$css  = '.ywsbs-signup-fee{color:' . $fee_color . ';}';
			$css .= '.ywsbs-trial-period{color:' . $trial_color . ';}';

			foreach ( $status_colors as $status => $colors ) {
				$css .= 'span.status.' . $status . '{ color:' . $colors['background-color'] . ';} ';
			}
			return $css;
		}

		/**
		 * Generate custom css.
		 *
		 * @return string
		 */
		public function get_subscription_status_inline_style() {
			$status_colors = ywsbs_get_status_colors();
			$css           = '';

			foreach ( $status_colors as $status => $colors ) {
				$css .= 'span.status.' . $status . '{ color:' . $colors['color'] . ';background-color:' . $colors['background-color'] . ';} ';
			}
			return $css;
		}

		/**
		 * Create the json translation through the PHP file
		 * so it's possible using normal translations (with PO files) also for JS translations
		 *
		 * @param string|null $json_translations Json translation.
		 * @param string      $file              File.
		 * @param string      $handle            Handle.
		 * @param string      $domain            Domain.
		 *
		 * @return string|null
		 */
		public function script_translations( $json_translations, $file, $handle, $domain ) {
			if ( 'yith-woocommerce-subscription' === $domain && in_array( $handle, $this->translatable_scripts, true ) ) {
				$path = YITH_YWSBS_DIR . 'languages/yith-woocommerce-subscription.php';
				if ( file_exists( $path ) ) {
					$translations = include $path;

					$json_translations = wp_json_encode(
						array(
							'domain'      => 'yith-woocommerce-subscription',
							'locale_data' => array(
								'messages' =>
									array(
										'' => array(
											'domain'       => 'yith-woocommerce-subscription',
											'lang'         => get_locale(),
											'plural-forms' => 'nplurals=2; plural=(n != 1);',
										),
									)
									+
									$translations,
							),
						)
					);

				}
			}

			return $json_translations;
		}
	}
}
