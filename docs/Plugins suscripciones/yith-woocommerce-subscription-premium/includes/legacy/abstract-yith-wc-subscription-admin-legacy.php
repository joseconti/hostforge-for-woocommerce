<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YITH_WC_Subscription_Assets_Legacy Class Legacy.
 *
 * @class   YITH_WC_Subscription
 * @since   3.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Admin_Legacy' ) ) {

	/**
	 * Class YITH_WC_Subscription_Admin_Legacy
	 */
	abstract class YITH_WC_Subscription_Admin_Legacy {

		/**
		 * YITH_YWSBS_Activities_List_Table
		 *
		 * @var YITH_YWSBS_Activities_List_Table
		 */
		public $cpt_obj_activities;

		/**
		 * Maybe regenerate the capabilities if the shop manager is disabled.
		 *
		 * @since      2.0.0
		 * @param mixed $new_value New option value.
		 * @param array $option    Option info.
		 * @param mixed $raw_value Raw value.
		 * @return mixed
		 * @deprecated 3.0.0
		 */
		public static function maybe_regenerate_capabilities( $new_value, $option, $raw_value ) {
			_deprecated_function( __METHOD__, '3.0.0' );

			if ( isset( $option['id'] ) && 'ywsbs_enable_shop_manager' !== $option['id'] ) {
				$current_value = get_option( 'ywsbs_enable_shop_manager', 'yes' );
				if ( $current_value !== $new_value ) {
					$method = 'no' === $new_value ? 'remove_capabilities' : 'add_capabilities';
					YWSBS_Subscription_Capabilities::$method( 'shop_manager' );
				}
			}

			return $new_value;
		}

		/**
		 * Dashboard tab
		 *
		 * @access     public
		 *
		 * @since      2.3.0
		 * @param array $options Options.
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function dashboard_tab( $options ) {
			_deprecated_function( __METHOD__, '3.0.0' );
			// close the wrap div and open the Rood div.
			echo '</div><!-- /.wrap -->';
			echo "<div class='woocommerce-page' >";

			if ( file_exists( YITH_YWSBS_VIEWS_PATH . '/panel/dashboard.php' ) ) {
				include YITH_YWSBS_VIEWS_PATH . '/panel/dashboard.php';
			}
		}

		/**
		 * Export shipping list
		 *
		 * @throws \Mpdf\MpdfException Throws Exception.
		 * @deprecated
		 */
		public function export_shipping_list() {
			_deprecated_function( __METHOD__, '3.0.0' );
			if ( function_exists( 'YWSBS_Subscription_Delivery_Schedules' ) ) {
				YWSBS_Subscription_Delivery_Schedules()->admin->export_shipping_list();
			}
		}

		/**
		 * Add the menu item Subscriptions inside the Analytic menu.
		 *
		 * @since      2.3
		 * @param array $report_pages Report pages.
		 * @return mixed
		 * @deprecated 3.0.0
		 */
		public function add_dashboard_to_woocommerce_analytics_report_menu( array $report_pages ) {
			_deprecated_function( __METHOD__, '3.0.0' );
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
		 * Delivery Schedules List Table
		 *
		 * Load the delivery schedules on admin page
		 *
		 * @since  2.2.0
		 * @return void
		 * @deprecated
		 */
		public function delivery_status_tab() {
			_deprecated_function( __METHOD__, '3.0.0' );
			if ( function_exists( 'YWSBS_Subscription_Delivery_Schedules' ) ) {
				YWSBS_Subscription_Delivery_Schedules()->admin->delivery_status_tab();
			}
		}
	}
}
