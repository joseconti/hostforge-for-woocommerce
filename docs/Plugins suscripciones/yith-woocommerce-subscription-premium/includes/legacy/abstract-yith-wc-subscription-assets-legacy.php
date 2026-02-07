<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Assets class. This is used to load script and styles.
 *
 * @since   2.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Assets_Legacy' ) ) {
	/**
	 * Class that handles the assets
	 *
	 * @class  YITH_WC_Subscription_Assets
	 */
	abstract class YITH_WC_Subscription_Assets_Legacy {

		/**
		 * Return the suffix of script.
		 *
		 * @return string
		 */
		protected function get_suffix() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			return $suffix;
		}

		/**
		 * Register admin scripts
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public function register_admin_scripts() {}

		/**
		 * Register frontend scripts
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public function register_frontend_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_register_style( 'yith_ywsbs_frontend', YITH_YWSBS_ASSETS_URL . '/css/frontend.css', false, YITH_YWSBS_VERSION );
			wp_register_script( 'yith_ywsbs_frontend', YITH_YWSBS_ASSETS_URL . '/js/ywsbs-frontend' . $suffix . '.js', array( 'jquery', 'wc-add-to-cart-variation', \YIT_Assets::wc_script_handle( 'wc-jquery-blockui' ) ), YITH_YWSBS_VERSION, true );

			wp_localize_script(
				'yith_ywsbs_frontend',
				'yith_ywsbs_frontend',
				array(
					'ajaxurl'            => admin_url( 'admin-ajax.php' ),
					'add_to_cart_label'  => apply_filters( 'ywsbs_add_to_cart_variation_label', get_option( 'ywsbs_add_to_cart_label' ) ),
					'default_cart_label' => apply_filters( 'ywsbs_add_to_cart_default_label', __( 'Add to cart', 'yith-woocommerce-subscription' ) ),
				)
			);
		}

		/**
		 * Register admin scripts
		 */
		public function register_common_scripts() {}

		/**
		 * Enqueue admin scripts
		 */
		public function enqueue_admin_scripts() {

			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			if ( 'edit-shop_coupon' === $screen_id || ywsbs_check_valid_admin_page( 'shop_coupon' ) ) {
				wp_enqueue_script( 'yith-ywsbs-admin-coupon' );
			}

			if ( 'edit-' . YITH_YWSBS_POST_TYPE === $screen_id || ywsbs_check_valid_admin_page( YITH_YWSBS_POST_TYPE ) ) {
				wp_enqueue_style( 'yith-ywsbs-backend' );
				wp_enqueue_script( 'selectWoo' );
				wp_enqueue_script( 'wc-enhanced-select' );
				wp_enqueue_script( 'ywsbs-subscription-admin' );
				wp_enqueue_script( 'yith-plugin-fw-fields' );
				wp_enqueue_script( 'datatables' );

				$locale  = localeconv();
				$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

				$params = array(
					/* translators: %s: decimal */
					'i18n_decimal_error'                => sprintf( __( 'Please enter a single decimal point (%s) and not multiple separators.', 'yith-woocommerce-subscription' ), $decimal ),
					/* translators: %s: price decimal separator */
					'i18n_mon_decimal_error'            => sprintf( __( 'Please enter a single monetary decimal point (%s) instead of multiple separators and currency symbols.', 'yith-woocommerce-subscription' ), wc_get_price_decimal_separator() ),
					'i18n_country_iso_error'            => __( 'Please enter the country code with two capital letters.', 'yith-woocommerce-subscription' ),
					'i18n_sale_less_than_regular_error' => __( 'Please enter a value that is lower than the regular price.', 'yith-woocommerce-subscription' ),
					'i18n_delete_product_notice'        => __( 'This product has produced sales and may be linked to existing orders. Are you sure you want to delete it?', 'yith-woocommerce-subscription' ),
					'i18n_remove_personal_data_notice'  => __( 'This action cannot be reversed. Are you sure you wish to erase personal data from the selected orders?', 'yith-woocommerce-subscription' ),
					'decimal_point'                     => $decimal,
					'mon_decimal_point'                 => wc_get_price_decimal_separator(),
					'ajax_url'                          => admin_url( 'admin-ajax.php' ),
					'strings'                           => array(
						'import_products' => __( 'Import', 'yith-woocommerce-subscription' ),
						'export_products' => __( 'Export', 'yith-woocommerce-subscription' ),
					),
					'nonces'                            => array(
						'gateway_toggle' => wp_create_nonce( 'woocommerce-toggle-payment-gateway-enabled' ),
					),
					'urls'                              => array(
						'import_products' => current_user_can( 'import' ) ? esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ) : null,
						'export_products' => current_user_can( 'export' ) ? esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ) : null,
					),
				);

				wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $params );
			}

			if ( ( isset( $_GET['page'] ) && $_GET['page'] === 'yith_woocommerce_subscription' ) ) { //phpcs:ignore
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'yith-ywsbs-admin' );
				wp_enqueue_script( 'jquery-blockui' );
				wp_enqueue_script( 'selectWoo' );
				wp_enqueue_script( 'wc-enhanced-select' );
				wp_enqueue_script( 'yith-plugin-fw-fields' );
				wp_enqueue_style( 'yith-ywsbs-backend' );
				wp_enqueue_style( 'yit-plugin-style' );
			}

			if ( ywsbs_check_valid_admin_page( 'product' ) || ywsbs_check_valid_admin_page( 'product_variable' ) ) {
				wp_enqueue_style( 'yith-ywsbs-product' );
				wp_enqueue_script( 'yith-ywsbs-product' );
				wp_enqueue_script( 'yith-plugin-fw-fields' );
			}

			if ( ywsbs_check_valid_admin_page( 'shop_order' ) ) {

				global $post;
				$order = wc_get_order( $post->ID );
				if ( $order ) {
					$subscriptions = $order->get_meta( 'subscriptions' );
					if ( ! empty( $subscriptions ) ) {
						$is_a_renew = $order->get_meta( 'is_a_renew' );
						$args       = array(
							'order_label'     => $is_a_renew ? esc_html__( 'Subscription renew', 'yith-woocommerce-subscription' ) : esc_html__( 'Subscription main order', 'yith-woocommerce-subscription' ),
							'warning_message' => esc_html__( 'Attention! Changing the order status of a subscription in pending renewal will prevent the subscription from functioning correctly.', 'yith-woocommerce-subscription' ),
						);

						wp_localize_script( 'yith-ywsbs-order', 'ywsbs_order_admin', $args );
						wp_enqueue_script( 'yith-ywsbs-order' );
						wp_enqueue_style( 'yith-ywsbs-order' );
						wp_add_inline_style( 'yith-ywsbs-order', $this->get_subscription_status_inline_style() );
					}
				}
			}

			if ( apply_filters( 'ywsbs_enable_report', true ) ) {
				$is_dashboard_page = 'yith-plugins_page_yith_woocommerce_subscription' === $screen_id && ! isset( $_GET['tab'] ) || ( isset( $_GET['tab'] ) && 'dashboard' === $_GET['tab'] ); //phpcs:ignore

				if ( $is_dashboard_page && yith_ywsbs_is_wc_admin_enabled() ) {
					wp_enqueue_style( 'wc-components' );
					wp_enqueue_style( defined( 'WC_ADMIN_APP' ) ? WC_ADMIN_APP : 'wc-admin-app' );
					wp_enqueue_script( 'yith-ywsbs-admin-dashboard' );
					wp_enqueue_script( 'wc-material-icons' );

					wp_localize_script( 'yith-ywsbs-admin-dashboard', 'ywsbsSettings', $this->get_dashboard_settings() );

				}
			}

			wp_add_inline_style( 'yith-ywsbs-backend', $this->get_subscription_status_inline_style() );
		}

		/**
		 * Enqueue frontend scripts
		 */
		public function enqueue_frontend_scripts() {

			if ( ! apply_filters( 'ywsbs_load_assets', true ) ) {
				return;
			}

			wp_enqueue_style( 'yith_ywsbs_frontend' );
			wp_enqueue_script( 'yith_ywsbs_frontend' );

			wp_add_inline_style( 'yith_ywsbs_frontend', $this->get_frontend_inline_style() );
		}

		/**
		 * Return the list of settings useful to the Subscription Dashboard
		 */
		protected function get_dashboard_settings() {
			$settings = array(
				'wc' => self::get_wc_data(),
			);

			return apply_filters( 'ywsbs_dashboard_settings', $settings );
		}

		/** -------------------------------------------------------
		 * Public Static Getters - to get specific settings
		 */

		/**
		 * Get the WC data
		 *
		 * @return array
		 */
		public static function get_wc_data() {
			$currency_code = get_woocommerce_currency();

			$wc_settings = array(
				'currency'      => array(
					'code'               => $currency_code,
					'precision'          => wc_get_price_decimals(),
					'symbol'             => html_entity_decode( get_woocommerce_currency_symbol( $currency_code ) ),
					'position'           => get_option( 'woocommerce_currency_pos' ),
					'decimal_separator'  => wc_get_price_decimal_separator(),
					'thousand_separator' => wc_get_price_thousand_separator(),
					'price_format'       => html_entity_decode( get_woocommerce_price_format() ),
				),
				'date_format'   => wc_date_format(),
				'status_labels' => ywsbs_get_status_label_counter(),

			);

			return $wc_settings;
		}
	}
}
