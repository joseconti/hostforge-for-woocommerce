<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Box_Frontend Class.
 * Handle the frontend section for module "subscription box"
 *
 * @class   YWSBS_Subscription_Box_Frontend
 * @since   4.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Box_Frontend' ) ) {
	/**
	 * Class YWSBS_Subscription_Box_Frontend
	 */
	class YWSBS_Subscription_Box_Frontend {

		/**
		 * My Account class handler
		 *
		 * @since 4.0.0
		 * @var null|YWSBS_Subscription_Box_My_Account
		 */
		protected $my_account;

		/**
		 * Constructor
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function __construct() {
			$this->init_variables();
			$this->init_hooks();
		}

		/**
		 * Init class variables and deps
		 *
		 * @since 4.0.0
		 * @return void
		 */
		protected function init_variables() {
			if ( is_user_logged_in() ) {
				$this->my_account = new YWSBS_Subscription_Box_My_Account();
			}
		}

		/**
		 * Init class variables and deps
		 *
		 * @since 4.0.0
		 * @return void
		 */
		protected function init_hooks() {
			add_action( 'template_redirect', array( $this, 'register_scripts' ), 0 );

			add_filter( 'add_to_cart_text', array( $this, 'change_add_to_cart_label' ), 100 );
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'change_add_to_cart_label' ), 100, 2 );
			add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'change_add_to_cart_label' ), 100, 2 );

			// Single product page customization.
			add_action( 'woocommerce_' . YWSBS_Subscription_Box::PRODUCT_TYPE . '_add_to_cart', array( $this, 'output_add_to_cart' ), 10 );
			add_action( 'wp_footer', array( $this, 'box_setup_modal' ), 15 );
		}

		/**
		 * Get my account class handler
		 *
		 * @since 4.0.0
		 * @return null|YWSBS_Subscription_Box_My_Account
		 */
		public function get_my_account() {
			return $this->my_account;
		}

		/**
		 * Change add to cart label in subscription product.
		 *
		 * @param string          $label Current add to cart label.
		 * @param null|WC_Product $product Current product.
		 *
		 * @return string
		 * @since  2.0.0
		 */
		public function change_add_to_cart_label( $label, $product = null ) {
			// If product is not set, try to get it from globals.
			if ( is_null( $product ) ) {
				global $post, $product;

				$product = ( is_null( $product ) && $post instanceof WP_Post ) ? wc_get_product( $post->ID ) : $product;
			}

			if ( is_null( $product ) || ! $product instanceof WC_Product || ! $product->is_type( YWSBS_Subscription_Box::PRODUCT_TYPE ) ) {
				return $label;
			}

			return get_option( 'ywsbs_subscription_box_add_to_cart_label', _x( 'Create your box', 'Box button label', 'yith-woocommerce-subscription' ) );
		}

		/**
		 * Register frontend scripts
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function register_scripts() {
			// Load deps.
			$deps = array( \YIT_Assets::wc_script_handle( 'wc-accounting' ) );
			if ( file_exists( YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'dist/subscription-box.asset.php' ) ) {
				$asset_deps = include YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . 'dist/subscription-box.asset.php';
				if ( isset( $asset_deps['dependencies'] ) ) {
					$deps = array_merge( $deps, $asset_deps['dependencies'] );
				}
			}

			YITH_WC_Subscription_Assets::get_instance()->add_frontend_script(
				'ywsbs-subscription-box-frontend',
				YWSBS_SUBSCRIPTION_BOX_MODULE_URL . 'dist/subscription-box.js',
				$deps,
				array( $this, 'check_script_enqueue' ),
				true
			);

			YITH_WC_Subscription_Assets::get_instance()->localize_script(
				'ywsbs-subscription-box-frontend',
				'ywsbs_subscription_box',
				$this->get_localized_script_data()
			);

			YITH_WC_Subscription_Assets::get_instance()->add_frontend_style(
				'ywsbs-subscription-box-cart-frontend',
				YWSBS_SUBSCRIPTION_BOX_MODULE_URL . 'assets/css/subscription-box-cart.css',
				array(),
				array( $this, 'check_script_enqueue' )
			);

			YITH_WC_Subscription_Assets::get_instance()->add_frontend_style(
				'ywsbs-subscription-box-frontend',
				YWSBS_SUBSCRIPTION_BOX_MODULE_URL . 'assets/css/subscription-box.css',
				array(),
				array( $this, 'check_script_enqueue' )
			);

			YITH_WC_Subscription_Assets::get_instance()->inline_style(
				'ywsbs-subscription-box-frontend',
				$this->get_inline_style()
			);
		}

		/**
		 * Return localized script data
		 *
		 * @since  4.0.0
		 * @return array
		 */
		protected function get_localized_script_data() {
			// Add steps.
			$product = $this->get_product();
			if ( ! $product ) {
				return array();
			}

			$data = array(
				'boxId'           => $product->get_id(),
				'boxIcon'         => YWSBS_SUBSCRIPTION_BOX_MODULE_URL . 'assets/images/boxfood.svg',
				'backIcon'        => YWSBS_SUBSCRIPTION_BOX_MODULE_URL . 'assets/images/back.svg',
				'boxPrice'        => $product->get_price(),
				'boxDeliveryInfo' => class_exists( 'YWSBS_Subscription_Delivery_Schedules' ) ? YWSBS_Subscription_Delivery_Schedules::get_instance()->get_product_delivery_message( $product ) : '',
				'wcSettings'      => array(
					'decimalSeparator'  => esc_attr( wc_get_price_decimal_separator() ),
					'thousandSeparator' => esc_attr( wc_get_price_thousand_separator() ),
					'decimals'          => wc_get_price_decimals(),
					'priceFormat'       => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ),
					'currencySymbol'    => html_entity_decode( get_woocommerce_currency_symbol() ),
				),
			);

			// Add logo.
			$show_logo          = get_option( 'ywsbs_subscription_box_show_site_logo', 'yes' );
			$data['headerLogo'] = 'yes' === $show_logo ? get_option( 'ywsbs_subscription_box_site_logo', '' ) : '';
			// Set product steps.
			foreach ( $product->get_steps() as $step_id => $step ) {
				$data['steps'][] = array(
					'id'       => $step_id,
					'label'    => $step['label'],
					'text'     => $step['text'],
					'rules'    => $product->has_step_quantity_threshold( $step_id ) ? $step['threshold'] : array(),
					'products' => apply_filters( 'ywsbs_subscription_box_preload_step_products', false, $step_id ) ? $this->prepare_step_products( $step_id ) : array(),
				);
			}

			// Add box price rules if any.
			if ( $product->has_price_threshold() ) {
				$data['boxPriceRules'] = $product->get_price_threshold();
			}

			// Create a unique session ID for the box.
			$data['boxSessionId'] = md5( $product->get_id() . '|' . maybe_serialize( $data['steps'] ) . '|' . maybe_serialize( $data['boxPriceRules'] ?? array() ) );

			return apply_filters( 'ywsbs_subscription_box_frontend_script_data', $data );
		}

		/**
		 * Prepare step products for frontend scripts
		 *
		 * @since 4.0.0
		 * @param string $step_id The step ID.
		 * @return array
		 */
		protected function prepare_step_products( $step_id ) {
			// Add steps.
			$product = $this->get_product();
			if ( ! $product ) {
				return array();
			}

			$prepare_products = array();
			$step_products    = $product->get_step_products( $step_id );
			foreach ( $step_products as $step_product ) {
				if ( $step_product->get_image_id() ) {
					$image = wp_get_attachment_image_src( $step_product->get_image_id(), 'woocommerce_thumbnail' );
					$image = ! empty( $image[0] ) ? $image[0] : '';
				}

				if ( empty( $image ) ) {
					$image = wc_placeholder_img_src( 'woocommerce_thumbnail' );
				}

				$prepare_products[] = array(
					'id'    => $step_product->get_id(),
					'name'  => $step_product->get_name(),
					'price' => $step_product->get_price(),
					'image' => $image,
				);
			}

			return $prepare_products;
		}

		/**
		 * Return localized script data
		 *
		 * @since  4.0.0
		 * @return string
		 */
		protected function get_inline_style() {
			// Colors.
			$colors = get_option( 'ywsbs_subscription_box_colors', array() );
			$colors = array_merge(
				array(
					'primary'           => '#acaa00',
					'primary-darker'    => '#7c7b00',
					'button-bg'         => '#000000',
					'button-bg-hover'   => '#7c7b00',
					'button-text'       => '#ffffff',
					'button-text-hover' => '#ffffff',
					'header-bg'         => '#f0f0f0',
					'footer-bg'         => '#f0f0f0',
				),
				$colors
			);

			// Add additional colors.
			$colors['box-shadow']          = 'rgba(217, 217, 217, .5)';
			$colors['box-shadow-selected'] = $this->hex_to_rgba( $colors['primary'], '0.3' );

			$style = ':root{';
			foreach ( $colors as $key => $color ) {
				$style .= "--ywsbs-box-{$key}:{$color};";
			}
			$style .= '}';

			return $style;
		}

		/**
		 * Convert hex to rgba
		 *
		 * @since 4.0.0
		 * @param string $hex     The color in hex format.
		 * @param string $opacity (Optional) Color opacity. Default is 1.
		 * @return string
		 */
		protected function hex_to_rgba( $hex, $opacity = '1' ) {

			if ( false === stripos( $hex, '#' ) ) {
				return $hex;
			}

			$hex = trim( $hex, '#' );

			if ( strlen( $hex ) === 3 ) {
				$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
				$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
				$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
			} elseif ( strlen( $hex ) === 6 ) {
				$r = hexdec( substr( $hex, 0, 2 ) );
				$g = hexdec( substr( $hex, 2, 2 ) );
				$b = hexdec( substr( $hex, 4, 2 ) );
			} else {
				return '';
			}

			return "rgba({$r},{$g},{$b},{$opacity})";
		}

		/**
		 * Get current box product
		 *
		 * @since 4.0.0
		 * @return WC_Product
		 */
		protected function get_product() {
			static $product;
			if ( is_null( $product ) ) {
				// Set product once.
				$product = false;
				if ( is_product() ) {
					$product_id = get_queried_object_id();
					$product    = $product_id ? wc_get_product( $product_id ) : false;
				}

				// Let's filter product.
				$product = apply_filters( 'ywsbs_subscription_box_frontend_product', $product );
				// Validate type. Type must be subscription-box and it must be purchasable.
				$product = ( $product instanceof WC_Product && $product->is_type( YWSBS_Subscription_Box::PRODUCT_TYPE ) && $product->is_purchasable() ) ? $product : false;
			}

			return $product;
		}

		/**
		 * Check if frontend script must be enqueued
		 *
		 * @since  4.0.0
		 * @param string $handle JS script handle.
		 * @return boolean
		 */
		public function check_script_enqueue( $handle ) {
			switch ( $handle ) {
				case 'ywsbs-subscription-box-cart-frontend':
					return is_cart() || is_checkout();

				default:
					return ! ! $this->get_product();
			}
		}

		/**
		 * Output subscription box "add to cart" form
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function output_add_to_cart() {
			wc_get_template( 'single-product/add-to-cart/subscription-box.php', array(), '', YWSBS_SUBSCRIPTION_BOX_MODULE_PATH . '/templates/' );
		}

		/**
		 * Add box setup modal if needed
		 *
		 * @since  4.0.0
		 * @return void
		 */
		public function box_setup_modal() {
			$product = $this->get_product();
			if ( ! $product ) {
				return;
			}

			echo '<div id="ywsbs-box" data-product_id="' . esc_attr( $product->get_id() ) . '"></div>';
		}
	}
}
