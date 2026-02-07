<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * Loader class
 *
 * @class   YITH\Subscription\RestApi\Loader
 * @since   2.3.0
 * @author YITH
 * @package YITH\Subscription
 */

namespace YITH\Subscription\RestApi;

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Loader
 */
class Panel {
	use \YITH_WC_Subscription_Singleton_Trait;

	/**
	 * Loader constructor.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'load_scripts' ), 5 );
		add_filter( 'ywsbs_register_panel_tabs', array( $this, 'register_admin_tab' ), 10, 1 );
		add_action( 'yith_ywsbs_dashboard_tab', array( $this, 'dashboard_tab' ), 10, 2 );
		add_filter( 'woocommerce_analytics_report_menu_items', array( $this, 'add_dashboard_to_woocommerce_analytics_report_menu' ), 10 );
	}

	/**
	 * Register admin tab to the main plugin panel tabs array
	 *
	 * @since  3.0.0
	 * @param array $tabs The array of panel tabs.
	 * @return array
	 */
	public static function register_admin_tab( $tabs ) {
		return array_merge(
			array(
				'dashboard' => array(
					'title'       => __( 'Dashboard', 'yith-woocommerce-subscription' ),
					'description' => __( 'An overview of all subscriptions and recurring payments.', 'yith-woocommerce-subscription' ),
					'icon'        => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>',
				),
			),
			$tabs
		);
	}

	/**
	 * Dashboard tab
	 *
	 * @since  3.0.0
	 * @param array $options Options.
	 * @return void
	 */
	public function dashboard_tab( $options ) {
		if ( file_exists( YITH_YWSBS_VIEWS_PATH . '/panel/dashboard.php' ) ) {
			include YITH_YWSBS_VIEWS_PATH . '/panel/dashboard.php';
		}
	}

	/**
	 * Add the menu item Subscriptions inside the Analytic menu.
	 *
	 * @since 3.0.0
	 * @param array $report_pages Report pages.
	 * @return mixed
	 */
	public function add_dashboard_to_woocommerce_analytics_report_menu( array $report_pages ) {
		$new_report_page = array();
		foreach ( $report_pages as $page ) {
			array_push( $new_report_page, $page );
			if ( ! is_null( $page ) && 'woocommerce-analytics-orders' === $page['id'] ) {
				$new_report_page[] = array(
					'id'     => 'yith-woocommerce-subscription-dashboard',
					'title'  => _x( 'Subscriptions', 'Item inside WooCommerce Analytics menu', 'yith-woocommerce-subscription' ),
					'parent' => 'woocommerce-analytics',
					'path'   => '&page=yith_woocommerce_subscription&tab=dashboard',
				);
			}
		}

		return $new_report_page;
	}

	/**
	 * Load scripts for report dashboard
	 *
	 * @since  3.0.0
	 * @return void
	 */
	public function load_scripts() {

		if ( ! ywsbs_is_admin_panel_page( 'dashboard' ) ) {
			return;
		}

		\YITH_WC_Subscription_Assets::get_instance()->add_admin_script(
			'yith-ywsbs-admin-dashboard',
			YITH_YWSBS_URL . ( version_compare( WC()->version, '6.7.0', '>=' ) ? 'dist/dashboard/index.js' : 'dist/legacy/dashboard/index.js' ),
			array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-data', 'wc-components' ),
			null,
			true
		);

		$currency_code = get_woocommerce_currency();
		$settings      = array(
			'wc' => array(
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
			),
		);

		\YITH_WC_Subscription_Assets::get_instance()->localize_script( 'yith-ywsbs-admin-dashboard', 'ywsbsSettings', apply_filters( 'ywsbs_dashboard_settings', $settings ) );
		\YITH_WC_Subscription_Assets::get_instance()->add_style_deps( 'yith-ywsbs-backend', array( 'wc-components', 'wc-admin-app' ) );
	}
}
