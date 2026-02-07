<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/includes
 */
class Aswc_Include {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Aswc_LoaderLoader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $aswc_onboard    To initializsed the object of class onboard.
	 */
	protected $aswc_onboard;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area,
	 * the public-facing side of the site and common side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		if ( defined( 'ASWC_VERSION' ) ) {

				$this->version = ASWC_VERSION;
		} else {

					$this->version = '2.4.6';
		}

			$this->plugin_name = 'advanced-subscriptions-for-woocommerce';

				$this->aswc_loader_dependencies();
				$this->aswc_loader_locale();
		if ( is_admin() ) {
			$this->aswc_loader_admin_hooks();
		} else {
			$this->aswc_loader_public_hooks();
		}
				$this->aswc_loader_common_hooks();

				$this->aswc_loader_api_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Aswc_LoaderLoader. Orchestrates the hooks of the plugin.
	 * - Aswc_Loaderi18n. Defines internationalization functionality.
	 * - Aswc_LoaderAdmin. Defines all hooks for the admin area.
	 * - Aswc_LoaderCommon. Defines all hooks for the common area.
	 * - Aswc_LoaderPublic. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function aswc_loader_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-aswc-loaderloader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-aswc-loaderi18n.php';

		if ( is_admin() ) {

			// The class responsible for defining all actions that occur in the admin area.
			require_once plugin_dir_path( __DIR__ ) . 'admin/class-aswc-loaderadmin.php';
			require_once plugin_dir_path( __DIR__ ) . 'admin/partials/class-aswc-loaderoverview.php';

		} else {

			// The class responsible for defining all actions that occur in the public-facing side of the site.
			require_once plugin_dir_path( __DIR__ ) . 'public/class-aswc-loaderpublic.php';

		}

		require_once ASWC_DIR_PATH . 'includes/rest-api/class-aswc-rest-api.php';

		/**
		 * This class responsible for defining common functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'common/class-aswc-loadercommon.php';

		require_once plugin_dir_path( __DIR__ ) . 'includes/aswc-loader-common-functions.php';

		$this->loader = new Aswc_LoaderLoader();

		// WCFM.
		require_once plugin_dir_path( __DIR__ ) . 'package/wcfm-compatibility/class-aswc-loaderwcfm-compatibility.php';
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Aswc_LoaderI18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function aswc_loader_locale() {

		$plugin_i18n = new Aswc_LoaderI18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function aswc_loader_admin_hooks() {

		$aswc_plugin_admin = new Aswc_LoaderAdmin( $this->aswc_get_plugin_name(), $this->aswc_get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $aswc_plugin_admin, 'aswc_admin_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $aswc_plugin_admin, 'aswc_admin_enqueue_scripts' );

				// Removed Advance Settings tab from admin dashboard.
				// $this->loader->add_filter( 'aswc_sfw_plugin_standard_admin_settings_tabs_before', $aswc_plugin_admin, 'aswc_admin_other_settings_page', 10 );
		// Add settings menu for Advanced Subscriptions For WooCommerce.
		$callname_lic         = self::$lic_callback_function;
		$callname_lic_initial = self::$lic_ini_callback_function;
		$day_count            = self::$callname_lic_initial();

		// Condition for validating.
		if ( self::$callname_lic() || 0 <= $day_count ) {
			// Saving tab settings.
						$this->loader->add_action( 'admin_init', $aswc_plugin_admin, 'aswc_admin_save_tab_settings' );
						// Removed Advance Settings fields from admin dashboard.
						// $this->loader->add_filter( 'aswc_others_settings_array', $aswc_plugin_admin, 'aswc_admin_other_settings_fields', 10 );.

			if ( aswc_check_plugin_enable() ) {

				$this->loader->add_action( 'woocommerce_variation_options', $aswc_plugin_admin, 'aswc_sfw_woocommerce_variation_options', 10, 3 );
				$this->loader->add_action( 'woocommerce_save_product_variation', $aswc_plugin_admin, 'aswc_sfw_save_product_variation', 10, 2 );
				$this->loader->add_action( 'woocommerce_variation_options_pricing', $aswc_plugin_admin, 'aswc_sfw_variation_options_pricing', 10, 3 );

				$this->loader->add_action( 'aswc_add_action_details', $aswc_plugin_admin, 'aswc_add_option_details', 10, 2 );

				$this->loader->add_action( 'init', $aswc_plugin_admin, 'aswc_admin_pause_susbcription' );

				$this->loader->add_filter( 'aswc_status_array', $aswc_plugin_admin, 'aswc_status_array' );

				$this->loader->add_action( 'admin_init', $aswc_plugin_admin, 'aswc_export_csv_report' );

				$this->loader->add_action( 'aswc_extra_tablenav_html', $aswc_plugin_admin, 'aswc_export_button_html' );

				$this->loader->add_action( 'aswc_other_payment_gateway_renewal', $aswc_plugin_admin, 'aswc_other_payment_gateway_renewal_order', 10, 3 );

				$this->loader->add_action( 'aswc_product_edit_field', $aswc_plugin_admin, 'aswc_product_edit_renewal_on_certain_date' );

				// one time purchase.
				$this->loader->add_action( 'aswc_product_edit_field', $aswc_plugin_admin, 'aswc_custom_product_fields_for_onetime_purchase_subscription' );

				$this->loader->add_action( 'aswc_save_simple_subscription_field', $aswc_plugin_admin, 'aswc_save_simple_subscription_field', 10, 2 );

				// For Giftcard.
				$this->loader->add_filter( 'aswc_wgm_other_setting', $aswc_plugin_admin, 'aswc_additional_coupon_setting' );
				// WPLM Translation.
				$this->loader->add_filter( 'aswc_add_lock_fields_ids_pro', $aswc_plugin_admin, 'aswc_add_lock_custom_fields_pro' );
				$this->loader->add_action( 'aswc_notice_message', $aswc_plugin_admin, 'aswc_wallet_activation_notice', 15 );

								$this->loader->add_action( 'wp_ajax_aswc_cancel_recurring_payment', $aswc_plugin_admin, 'aswc_cancel_recurring_payment' );

				$this->loader->add_filter( 'aswc_dashboard_plugin_title', $aswc_plugin_admin, 'aswc_dashboard_plugin_title_callback' );

				$this->loader->add_action( 'admin_init', $aswc_plugin_admin, 'aswc_create_manually_recurring' );

				// manual subscription feature.
				$this->loader->add_action( 'aswc_add_button_manual_subscription', $aswc_plugin_admin, 'aswc_add_button_manual_subscription_callback' );
				$this->loader->add_action( 'woocommerce_admin_order_data_after_order_details', $aswc_plugin_admin, 'aswc_add_dropdown_for_manual_subscription_parent_order', 10, 1 );
								$this->loader->add_filter( 'wp_ajax_aswc_show_parent_order_for_custom_manual', $aswc_plugin_admin, 'aswc_show_parent_order_for_custom_manual_callback', 10 );
								$this->loader->add_action( 'add_meta_boxes', $aswc_plugin_admin, 'aswc_add_meta_boxes', 10, 2 );

								// Hook for the subscriptions edit screen meta boxes.
				if ( function_exists( 'wc_get_page_screen_id' ) ) {
						$screen_id = wc_get_page_screen_id( 'aswc_subscriptions' );
						$this->loader->add_action( 'add_meta_boxes_' . $screen_id, $aswc_plugin_admin, 'aswc_add_meta_boxes', 10, 2 );
				}
								$this->loader->add_action( 'save_post', $aswc_plugin_admin, 'aswc_save_manual_subscription_order_details', 10, 2 );
								$this->loader->add_action( 'save_post', $aswc_plugin_admin, 'aswc_reschedule_next_payment_on_save', 20, 2 );
								$this->loader->add_filter( 'wc_order_statuses', $aswc_plugin_admin, 'aswc_remove_default_status_manual_subscription', 999, 1 );
								$this->loader->add_filter( 'woocommerce_new_order', $aswc_plugin_admin, 'aswc_save_manual_subscription_order_details_hpos', 10, 1 );

				$this->loader->add_action( 'aswc_add_action_details', $aswc_plugin_admin, 'aswc_add_action_details_callack', 10, 2 );

				$this->loader->add_filter( 'woocommerce_order_actions', $aswc_plugin_admin, 'aswc_add_renewal_payment_actions', 10, 1 );
								$this->loader->add_action( 'woocommerce_order_action_aswc_retry_renewal', $aswc_plugin_admin, 'aswc_handle_renewal_payment', 10, 1 );

				// update existing subscription meta via admin.
				$this->loader->add_filter( 'aswc_column_subscription_table', $aswc_plugin_admin, 'aswc_add_update_subscription_column', 10, 1 );
				$this->loader->add_filter( 'aswc_add_case_column', $aswc_plugin_admin, 'aswc_add_update_button_to_subscription_column', 10, 3 );
								$this->loader->add_filter( 'wp_ajax_aswc_update_subscription_items', $aswc_plugin_admin, 'aswc_update_subscription_items_callback', 10 );

				// Subscription actions from the dropdown.
				$this->loader->add_action( 'woocommerce_order_action_aswc_create_renewal_order', $aswc_plugin_admin, 'aswc_handle_create_renewal_order_action', 10, 1 );
				$this->loader->add_action( 'woocommerce_order_action_aswc_retry_pending_order', $aswc_plugin_admin, 'aswc_handle_retry_pending_order_action', 10, 1 );

			}
		}
	}

	/**
	 * Register all of the hooks related to the common functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function aswc_loader_common_hooks() {

		$aswc_plugin_common = new Aswc_LoaderCommon( $this->aswc_get_plugin_name(), $this->aswc_get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $aswc_plugin_common, 'aswc_common_enqueue_styles' );

		$this->loader->add_action( 'wp_enqueue_scripts', $aswc_plugin_common, 'aswc_common_enqueue_scripts' );

		$callname_lic         = self::$lic_callback_function;
		$callname_lic_initial = self::$lic_ini_callback_function;
		$day_count            = self::$callname_lic_initial();

		// Condition for validating.
		if ( self::$callname_lic() || 0 <= $day_count ) {
			if ( aswc_check_plugin_enable() ) {
				$this->loader->add_filter( 'aswc_check_subscription_product_type', $aswc_plugin_common, 'aswc_is_variable_subscription_product_type', 10, 2 );

				$this->loader->add_filter( 'woocommerce_coupon_discount_types', $aswc_plugin_common, 'aswc_subscription_coupon_discount_types' );

				$this->loader->add_filter( 'woocommerce_product_coupon_types', $aswc_plugin_common, 'aswc_woocommerce_product_coupon_types' );

				$this->loader->add_filter( 'woocommerce_coupon_error', $aswc_plugin_common, 'aswc_coupon_error', 10 );
				$this->loader->add_filter( 'woocommerce_coupon_is_valid', $aswc_plugin_common, 'aswc_validate_subscription_coupon', 10, 3 );

				$this->loader->add_filter( 'woocommerce_coupon_is_valid_for_product', $aswc_plugin_common, 'aswc_validate_subscription_coupon_for_product', 10, 3 );

				$this->loader->add_filter( 'woocommerce_coupon_get_discount_amount', $aswc_plugin_common, 'aswc_get_discount_amount', 10, 5 );

									$this->loader->add_filter( 'aswc_email_classes', $aswc_plugin_common, 'aswc_woocommerce_email_classes' );

				$this->loader->add_action( 'woocommerce_order_status_changed', $aswc_plugin_common, 'aswc_woocommerce_order_status_changed', 99, 3 );

				$this->loader->add_action( 'woocommerce_order_status_changed', $aswc_plugin_common, 'aswc_upgrade_downgrade_order_status_changed', 100, 3 );

				$this->loader->add_filter( 'woocommerce_get_order_item_totals', $aswc_plugin_common, 'aswc_reordering_order_item_totals', 10, 3 );

				$this->loader->add_filter( 'aswc_next_payment_date', $aswc_plugin_common, 'aswc_first_payment_date_for_sync', 10, 2 );

								$this->loader->add_action( 'wp_ajax_aswc_variation_expiry', $aswc_plugin_common, 'aswc_variation_expiry' );
								$this->loader->add_action( 'wp_ajax_nopriv_aswc_variation_expiry', $aswc_plugin_common, 'aswc_variation_expiry' );
				// For Giftcard.
				$this->loader->add_filter( 'aswc_wgm_discount_type', $aswc_plugin_common, 'aswc_discount_type_for_giftcard' );
								$this->loader->add_action( 'wp_ajax_aswc_apply_giftcard_coupon', $aswc_plugin_common, 'aswc_apply_giftcard_coupon' );
								$this->loader->add_action( 'wp_ajax_nopriv_aswc_apply_giftcard_coupon', $aswc_plugin_common, 'aswc_apply_giftcard_coupon' );

				$this->loader->add_filter( 'aswc_wgm_subscription_renewal_order_coupon', $aswc_plugin_common, 'aswc_subscription_renewal_order_coupon', 10, 3 );
				$this->loader->add_action( 'woocommerce_order_status_changed', $aswc_plugin_common, 'aswc_update_giftcard_coupon_amount', 100, 3 );
				$this->loader->add_filter( 'aswc_currency_switcher_set_coupon_discount_percentage', $aswc_plugin_common, 'aswc_currency_switcher_set_supported_coupon_type', 10, 2 );

				// For one time purchase.
								$this->loader->add_action( 'wp_ajax_aswc_onetime_purchase', $aswc_plugin_common, 'aswc_onetime_purchase_callback' );
								$this->loader->add_action( 'wp_ajax_nopriv_aswc_onetime_purchase', $aswc_plugin_common, 'aswc_onetime_purchase_callback' );

				// hook for bundle product working on subscription and renewal.
				$this->loader->add_action( 'aswc_subscription_bundle_addition', $aswc_plugin_common, 'aswc_subscription_bundle_addition_callback', 10, 3 );

				// new feature hook.
				$this->loader->add_action( 'aswc_add_new_product_for_manual_subscription', $aswc_plugin_common, 'aswc_add_new_product_for_manual_subscription_callback', 10, 2 );

				$this->loader->add_action( 'aswc_renewal_order_creation', $aswc_plugin_common, 'aswc_renewal_order_apply_coupon', 10, 2 );

				$this->loader->add_action( 'aswc_cancel_failed_susbcription', $aswc_plugin_common, 'aswc_cancel_failed_susbcription_callback', 10, 3 );

				$this->loader->add_action( 'cartflows_offer_product_processed', $aswc_plugin_common, 'cartflow_subscription_creation_while_upselling', 10, 3 );

				$this->loader->add_action( 'aswc_recurring_allow_on_scheduler', $aswc_plugin_common, 'aswc_recurring_allow_on_scheduler_callback', 10, 2 );
			}
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function aswc_loader_public_hooks() {

		$aswc_plugin_public = new Aswc_LoaderPublic( $this->aswc_get_plugin_name(), $this->aswc_get_version() );

		$callname_lic         = self::$lic_callback_function;
		$callname_lic_initial = self::$lic_ini_callback_function;
		$day_count            = self::$callname_lic_initial();

		/*Condition for validating.*/
		if ( self::$callname_lic() || 0 <= $day_count ) {

			if ( aswc_check_plugin_enable() ) {

				$this->loader->add_action( 'wp_enqueue_scripts', $aswc_plugin_public, 'aswc_public_enqueue_styles' );
				$this->loader->add_action( 'wp_enqueue_scripts', $aswc_plugin_public, 'aswc_public_enqueue_scripts' );
				$this->loader->add_action( 'woocommerce_before_add_to_cart_button', $aswc_plugin_public, 'aswc_woocommerce_before_add_to_cart_button' );
				$this->loader->add_filter( 'woocommerce_add_cart_item_data', $aswc_plugin_public, 'aswc_woocommerce_add_cart_item_data', 10, 3 );

				$this->loader->add_filter( 'aswc_show_time_interval', $aswc_plugin_public, 'aswc_show_time_interval_on_cart', 10, 3 );

				$this->loader->add_filter( 'aswc_cart_data_for_susbcription', $aswc_plugin_public, 'aswc_change_subscription_expiry_by_customer', 10, 2 );

				$this->loader->add_filter( 'aswc_supported_payment_gateway_for_woocommerce', $aswc_plugin_public, 'aswc_manual_payment_gateway_for_woocommerce', 10, 2 );

				$this->loader->add_action( 'aswc_order_details_html_after_cancel_button', $aswc_plugin_public, 'aswc_order_details_html_for_paused_subscription' );

				$this->loader->add_action( 'init', $aswc_plugin_public, 'aswc_pause_susbcription' );

				$this->loader->add_action( 'aswc_product_details_html', $aswc_plugin_public, 'aswc_product_details_downgrade_upgrade' );

				$this->loader->add_filter( 'post_type_link', $aswc_plugin_public, 'aswc_downgrade_upgrade_link', 15, 2 );

				$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $aswc_plugin_public, 'aswc_upgrade_downgrade_add_to_cart_validation', 10, 3 );

				$this->loader->add_filter( 'woocommerce_add_cart_item_data', $aswc_plugin_public, 'aswc_upgrade_downgrade_cart_details', 10, 3 );

				$this->loader->add_filter( 'aswc_is_upgrade_downgrade_order', $aswc_plugin_public, 'aswc_is_upgrade_downgrade_order', 10, 5 );

				$this->loader->add_filter( 'woocommerce_cart_item_subtotal', $aswc_plugin_public, 'aswc_is_upgrade_downgrade_text', 10, 3 );

				$this->loader->add_action( 'aswc_did_woocommerce_before_calculate_totals', $aswc_plugin_public, 'aswc_add_switch_subscription_price_and_sigup_fee' );

				$this->loader->add_filter( 'aswc_show_sync_interval', $aswc_plugin_public, 'aswc_show_sync_interval_price', 10, 2 );
				$this->loader->add_action( 'woocommerce_single_product_summary', $aswc_plugin_public, 'aswc_show_first_payment_date_for_sync_subscription', 15 );

				$this->loader->add_action( 'aswc_cart_price_subscription', $aswc_plugin_public, 'aswc_cart_price_for_sync_subscription', 10, 2 );

				$this->loader->add_filter( 'woocommerce_available_variation', $aswc_plugin_public, 'aswc_variation_descriptions', 10, 3 );

				$this->loader->add_filter( 'aswc_add_to_cart_validation', $aswc_plugin_public, 'aswc_add_to_cart_validation', 10, 3 );

				$this->loader->add_action( 'aswc_after_subscription_details', $aswc_plugin_public, 'aswc_add_gift_card_coupon_apply_html' );

				$this->loader->add_filter( 'aswc_expiry_add_to_cart_validation', $aswc_plugin_public, 'aswc_expiry_add_to_cart_validation', 10, 3 );

				$this->loader->add_filter( 'aswc_show_quantity_fields_for_susbcriptions', $aswc_plugin_public, 'aswc_show_quantity_fields_for_susbcriptions', 10, 2 );

				$this->loader->add_filter( 'aswc_recurring_data', $aswc_plugin_public, 'aswc_add_start_date_recurring', 20, 2 );

				$this->loader->add_filter( 'aswc_set_subscription_status', $aswc_plugin_public, 'aswc_set_subscription_status', 20, 2 );
				$this->loader->add_filter( 'aswc_subs_curent_time', $aswc_plugin_public, 'aswc_set_current_time_with_start_time', 20, 2 );

				$this->loader->add_action( 'woocommerce_before_cart', $aswc_plugin_public, 'aswc_show_downgrade_upgrade_msg' );
				$this->loader->add_action( 'woocommerce_before_checkout_form', $aswc_plugin_public, 'aswc_show_downgrade_upgrade_msg' );

				$this->loader->add_action( 'aswc_product_details_html', $aswc_plugin_public, 'aswc_add_cancel_recurring_button', 10, 1 );
				$this->loader->add_action( 'woocommerce_cart_updated', $aswc_plugin_public, 'aswc_remove_cart_notice' );

				// subscription info on thanku.
				$this->loader->add_action( 'woocommerce_after_order_details', $aswc_plugin_public, 'aswc_show_related_subscription_on_order', 10, 1 );
				$this->loader->add_action( 'aswc_after_subscription_details', $aswc_plugin_public, 'aswc_show_renewal_order_for_customer', 10, 1 );

				// One time subscription.
				$this->loader->add_filter( 'aswc_skip_creating_subscription', $aswc_plugin_public, 'aswc_skip_creating_subscription', 20, 2 );
				$this->loader->add_filter( 'aswc_show_one_time_subscription_price', $aswc_plugin_public, 'aswc_price_html_onetime_subscription_product', 20, 2 );

				$this->loader->add_action( 'aswc_did_woocommerce_before_calculate_totals', $aswc_plugin_public, 'aswc_add_to_cart_one_time_add_price', 50, 1 );
				$this->loader->add_action( 'woocommerce_thankyou_order_received_text', $aswc_plugin_public, 'aswc_remove_onetime_session', 10, 2 );

				$this->loader->add_action( 'woocommerce_show_variation_price', $aswc_plugin_public, 'aswc_woocommerce_show_variation_price', 99, 3 );

				$this->loader->add_action( 'aswc_cancel_susbcription', $aswc_plugin_public, 'aswc_restrict_customer_to_cancel_before_trial_ended', 10, 2 );
				$this->loader->add_action( 'aswc_customer_cancel_button', $aswc_plugin_public, 'aswc_customer_cancel_button_callback', 10, 2 );

				// git fixes.
				$this->loader->add_filter( 'aswc_product_args_for_renewal_order_propate_amount', $aswc_plugin_public, 'aswc_remove_propate_amount_for_subscripition_renewal', 10, 2 );
				$this->loader->add_filter( 'aswc_fix_recurring_info_price', $aswc_plugin_public, 'aswc_fix_recurring_info_price', 10, 2 );

				// wc block.
				$this->loader->add_filter( 'woocommerce_get_item_data', $aswc_plugin_public, 'aswc_get_subscription_meta_on_cart', 10, 2 );

				$this->loader->add_filter( 'aswc_manage_line_total_for_plan_switch', $aswc_plugin_public, 'aswc_manage_line_total_for_plan_switch_callback', 10, 3 );

				$this->loader->add_filter( 'aswc_check_one_time_product', $aswc_plugin_public, 'aswc_check_one_time_product_callback', 10, 3 );

				$this->loader->add_filter( 'aswc_show_one_time_subscription_price_block', $aswc_plugin_public, 'aswc_show_one_time_subscription_price_block_callback', 20, 2 );

				$this->loader->add_filter( 'woocommerce_cart_item_subtotal', $aswc_plugin_public, 'woocommerce_cart_item_prorate_subtotal', 10, 3 );

				// Handle the Manual payment method update for stripe and stripe sepa.
				$this->loader->add_action( 'wc_stripe_add_payment_method_stripe_success', $aswc_plugin_public, 'aswc_update_payment_method_for_subscription', 10, 2 );
				$this->loader->add_action( 'wc_stripe_add_payment_method_stripe_sepa_success', $aswc_plugin_public, 'aswc_update_payment_method_for_subscription', 10, 1 );
				$this->loader->add_action( 'wc_stripe_payment_fields_stripe', $aswc_plugin_public, 'aswc_display_a_notice', 10, 1 );
				$this->loader->add_filter( 'aswc_supported_payment_gateway_for_woocommerce', $aswc_plugin_public, 'aswc_wallet_payment_gateway_for_subscription', 10, 2 );
			}
		}
	}
	/**
	 * Register all of the hooks related to the api functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function aswc_loader_api_hooks() {

		$aswc_plugin_api = new ASWC_Rest_Api( $this->aswc_get_plugin_name(), $this->aswc_get_version() );

		$callname_lic         = self::$lic_callback_function;
		$callname_lic_initial = self::$lic_ini_callback_function;
		$day_count            = self::$callname_lic_initial();

		/*Condition for validating.*/
		if ( self::$callname_lic() || 0 <= $day_count ) {
			$this->loader->add_action( 'rest_api_init', $aswc_plugin_api, 'aswc_add_endpoint' );
		}
	}

	/**
	 * Public static variable to be accessed in this plugin.
	 *
	 * @var string lic_callback_function
	 */
	public static $lic_callback_function = 'check_lcns_validity';

	/**
	 * Public static variable to be accessed in this plugin.
	 *
	 * @var string lic_callback_function
	 */
	public static $lic_ini_callback_function = 'check_lcns_initial_days';

	/**
	 * Validate the use of features of this plugin.
	 *
	 * @since    1.0.0
	 */
	public static function check_lcns_validity() {
			// Always return true to keep all features active.
			return true;
	}

	/**
	 * Validate the use of features of this plugin for initial days.
	 *
	 * @since    1.0.0
	 */
	public static function check_lcns_initial_days() {
			// Always return a positive value to bypass license checks.
			return 30;
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function aswc_run() {
		$this->loader->aswc_run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function aswc_get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Aswc_LoaderLoader    Orchestrates the hooks of the plugin.
	 */
	public function aswc_get_loader() {
		return $this->loader;
	}


	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Aswc_LoaderOnboard    Orchestrates the hooks of the plugin.
	 */
	public function aswc_get_onboard() {
		return $this->aswc_onboard;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function aswc_get_version() {
		return $this->version;
	}

	/**
	 * Show admin notices.
	 *
	 * @param  string $aswc_message    Message to display.
	 * @param  string $type       notice type, accepted values - error/update/update-nag.
	 * @since  1.0.0
	 */
	public static function aswc_plug_admin_notice( $aswc_message, $type = 'error' ) {

		$aswc_classes = 'notice ';

		switch ( $type ) {

			case 'update':
				$aswc_classes .= 'updated is-dismissible';
				break;

			case 'update-nag':
				$aswc_classes .= 'update-nag is-dismissible';
				break;

			case 'success':
				$aswc_classes .= 'notice-success is-dismissible';
				break;

			default:
				$aswc_classes .= 'notice-error is-dismissible';
		}

		$aswc_notice  = '<div class="' . esc_attr( $aswc_classes ) . ' aswc-errorr-8">';
		$aswc_notice .= '<p>' . esc_html( $aswc_message ) . '</p>';
		$aswc_notice .= '</div>';

		echo wp_kses_post( $aswc_notice );
	}

	/**
	 * Generate html components.
	 *
	 * @param  string $aswc_components    html to display.
	 * @since  1.0.0
	 */
	public function aswc_plug_generate_html( $aswc_components = array() ) {
		if ( is_array( $aswc_components ) && ! empty( $aswc_components ) ) {
			foreach ( $aswc_components as $aswc_component ) {
				if ( ! empty( $aswc_component['type'] ) && ! empty( $aswc_component['id'] ) ) {
					switch ( $aswc_component['type'] ) {

						case 'hidden':
						case 'number':
						case 'email':
						case 'text':
							?>
				<div class="aswc-form-group aswc-<?php echo esc_attr( $aswc_component['type'] ); ?>">
							<div class="aswc-form-group__label">
								<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo ( isset( $aswc_component['title'] ) ? esc_html( $aswc_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="aswc-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__notch">
											<?php if ( 'number' !== $aswc_component['type'] ) { ?>
												<span class="mdc-floating-label" id="my-label-id" style=""><?php echo ( isset( $aswc_component['placeholder'] ) ? esc_attr( $aswc_component['placeholder'] ) : '' ); ?></span>
											<?php } ?>
										</span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
									<input
									class="mdc-text-field__input <?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>" 
									name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>"
									id="<?php echo esc_attr( $aswc_component['id'] ); ?>"
									type="<?php echo esc_attr( $aswc_component['type'] ); ?>"
									value="<?php echo ( isset( $aswc_component['value'] ) ? esc_attr( $aswc_component['value'] ) : '' ); ?>"
									placeholder="<?php echo ( isset( $aswc_component['placeholder'] ) ? esc_attr( $aswc_component['placeholder'] ) : '' ); ?>"
									min="<?php echo ( isset( $aswc_component['min'] ) ? esc_attr( $aswc_component['min'] ) : '' ); ?>"

									>
								</label>
								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent aswc-helper-text" id="" aria-hidden="true"><?php echo ( isset( $aswc_component['description'] ) ? esc_attr( $aswc_component['description'] ) : '' ); ?></div>
								</div>
							</div>
						</div>
							<?php
							break;

						case 'password':
							?>
						<div class="aswc-form-group">
							<div class="aswc-form-group__label">
								<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo ( isset( $aswc_component['title'] ) ? esc_html( $aswc_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="aswc-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__notch">
										</span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
									<input 
									class="mdc-text-field__input <?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?> aswc-form__password" 
									name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>"
									id="<?php echo esc_attr( $aswc_component['id'] ); ?>"
									type="<?php echo esc_attr( $aswc_component['type'] ); ?>"
									value="<?php echo ( isset( $aswc_component['value'] ) ? esc_attr( $aswc_component['value'] ) : '' ); ?>"
									placeholder="<?php echo ( isset( $aswc_component['placeholder'] ) ? esc_attr( $aswc_component['placeholder'] ) : '' ); ?>"
									>
									<i class="material-icons mdc-text-field__icon mdc-text-field__icon--trailing aswc-password-hidden" tabindex="0" role="button">visibility</i>
								</label>
								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent aswc-helper-text" id="" aria-hidden="true"><?php echo ( isset( $aswc_component['description'] ) ? esc_attr( $aswc_component['description'] ) : '' ); ?></div>
								</div>
							</div>
						</div>
							<?php
							break;

						case 'textarea':
							?>
						<div class="aswc-form-group">
							<div class="aswc-form-group__label">
								<label class="aswc-form-label" for="<?php echo esc_attr( $aswc_component['id'] ); ?>"><?php echo ( isset( $aswc_component['title'] ) ? esc_html( $aswc_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="aswc-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea"  	for="text-field-hero-input">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__notch">
											<span class="mdc-floating-label"><?php echo ( isset( $aswc_component['placeholder'] ) ? esc_attr( $aswc_component['placeholder'] ) : '' ); ?></span>
										</span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
									<span class="mdc-text-field__resizer">
										<textarea class="mdc-text-field__input <?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>" rows="2" cols="25" aria-label="Label" name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>" id="<?php echo esc_attr( $aswc_component['id'] ); ?>" placeholder="<?php echo ( isset( $aswc_component['placeholder'] ) ? esc_attr( $aswc_component['placeholder'] ) : '' ); ?>"><?php echo ( isset( $aswc_component['value'] ) ? esc_textarea( $aswc_component['value'] ) : '' ); // WPCS: XSS ok. ?></textarea>
									</span>
								</label>

							</div>
						</div>

							<?php
							break;

						case 'select':
						case 'multiselect':
							?>
						<div class="aswc-form-group">
							<div class="aswc-form-group__label">
								<label class="aswc-form-label" for="<?php echo esc_attr( $aswc_component['id'] ); ?>"><?php echo ( isset( $aswc_component['title'] ) ? esc_html( $aswc_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="aswc-form-group__control">
								<div class="aswc-form-select">
									<select id="<?php echo esc_attr( $aswc_component['id'] ); ?>" name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : '' ); ?><?php echo ( 'multiselect' === $aswc_component['type'] ) ? '[]' : ''; ?>" id="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="mdl-textfield__input <?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>" <?php echo 'multiselect' === $aswc_component['type'] ? 'multiple="multiple"' : ''; ?> >
										<?php
										foreach ( $aswc_component['options'] as $aswc_key => $aswc_val ) {
											?>
											<option value="<?php echo esc_attr( $aswc_key ); ?>"
												<?php
												if ( is_array( $aswc_component['value'] ) ) {
													selected( in_array( (string) $aswc_key, $aswc_component['value'], true ), true );
												} else {
													selected( $aswc_component['value'], (string) $aswc_key );
												}
												?>
												>
												<?php echo esc_html( $aswc_val ); ?>
											</option>
											<?php
										}
										?>
									</select>
									<label class="mdl-textfield__label" for="octane"><?php echo ( isset( $aswc_component['description'] ) ? esc_html( $aswc_component['description'] ) : '' ); ?><?php echo ( isset( $aswc_component['description'] ) ? esc_attr( $aswc_component['description'] ) : '' ); ?></label>
								</div>
							</div>
						</div>

							<?php
							break;

						case 'checkbox':
							?>
						<div class="aswc-form-group">
							<div class="aswc-form-group__label">
								<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo ( isset( $aswc_component['title'] ) ? esc_html( $aswc_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="aswc-form-group__control aswc-pl-4">
								<div class="mdc-form-field">
									<div class="mdc-checkbox">
										<input 
										name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>"
										id="<?php echo esc_attr( $aswc_component['id'] ); ?>"
										type="checkbox"
										class="mdc-checkbox__native-control <?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>"
										value="<?php echo ( isset( $aswc_component['value'] ) ? esc_attr( $aswc_component['value'] ) : '' ); ?>"
										<?php checked( $aswc_component['value'], '1' ); ?>
										/>
										<div class="mdc-checkbox__background">
											<svg class="mdc-checkbox__checkmark" viewBox="0 0 24 24">
												<path class="mdc-checkbox__checkmark-path" fill="none" d="M1.73,12.91 8.1,19.28 22.79,4.59"/>
											</svg>
											<div class="mdc-checkbox__mixedmark"></div>
										</div>
										<div class="mdc-checkbox__ripple"></div>
									</div>
									<label for="checkbox-1"><?php echo ( isset( $aswc_component['description'] ) ? esc_attr( $aswc_component['description'] ) : '' ); ?></label>
								</div>
							</div>
						</div>
							<?php
							break;

						case 'radio':
							?>
						<div class="aswc-form-group">
							<div class="aswc-form-group__label">
								<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo ( isset( $aswc_component['title'] ) ? esc_html( $aswc_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="aswc-form-group__control aswc-pl-4">
								<div class="aswc-flex-col">
									<?php
									foreach ( $aswc_component['options'] as $aswc_radio_key => $aswc_radio_val ) {
										?>
										<div class="mdc-form-field">
											<div class="mdc-radio">
												<input
												name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>"
												value="<?php echo esc_attr( $aswc_radio_key ); ?>"
												type="radio"
												class="mdc-radio__native-control <?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>"
												<?php checked( $aswc_radio_key, $aswc_component['value'] ); ?>
												>
												<div class="mdc-radio__background">
													<div class="mdc-radio__outer-circle"></div>
													<div class="mdc-radio__inner-circle"></div>
												</div>
												<div class="mdc-radio__ripple"></div>
											</div>
											<label for="radio-1"><?php echo esc_html( $aswc_radio_val ); ?></label>
										</div>	
										<?php
									}
									?>
								</div>
							</div>
						</div>
							<?php
							break;

						case 'radio-switch':
							?>

						<div class="aswc-form-group">
							<div class="aswc-form-group__label">
								<label for="" class="aswc-form-label"><?php echo ( isset( $aswc_component['title'] ) ? esc_html( $aswc_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="aswc-form-group__control">
								<div>
									<div class="mdc-switch">
										<div class="mdc-switch__track"></div>
																		<div class="mdc-switch__thumb-underlay">
																				<div class="mdc-switch__thumb"></div>
							<?php
												$aswc_radio_value = $aswc_component['value'];
							if ( 'yes' === $aswc_radio_value ) {
									$aswc_radio_value = 'on';
							}
							?>
																				<input name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>" type="checkbox" id="<?php echo esc_html( $aswc_component['id'] ); ?>" value="on" class="mdc-switch__native-control <?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>" role="switch" aria-checked="<?php echo ( 'on' === $aswc_radio_value ) ? 'true' : 'false'; ?>"<?php checked( $aswc_radio_value, 'on' ); ?>
											>
										</div>
									</div>
								</div>
							</div>
						</div>
							<?php
							break;

						case 'button':
							?>
						<div class="aswc-form-group">
							<div class="aswc-form-group__label"></div>
							<div class="aswc-form-group__control">
								<button class="mdc-button mdc-button--raised" name= "<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>"
									id="<?php echo esc_attr( $aswc_component['id'] ); ?>"> <span class="mdc-button__ripple"></span>
									<span class="mdc-button__label <?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>"><?php echo ( isset( $aswc_component['button_text'] ) ? esc_html( $aswc_component['button_text'] ) : '' ); ?></span>
								</button>
							</div>
						</div>

							<?php
							break;

						case 'multi':
							?>
							<div class="aswc-form-group aswc-<?php echo esc_attr( $aswc_component['type'] ); ?>">
								<div class="aswc-form-group__label">
									<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo ( isset( $aswc_component['title'] ) ? esc_html( $aswc_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
									</div>
									<div class="aswc-form-group__control">
									<?php
									foreach ( $aswc_component['value'] as $component ) {
										?>
											<label class="mdc-text-field mdc-text-field--outlined">
												<span class="mdc-notched-outline">
													<span class="mdc-notched-outline__leading"></span>
													<span class="mdc-notched-outline__notch">
														<?php if ( 'number' !== $component['type'] ) { ?>
															<span class="mdc-floating-label" id="my-label-id" style=""><?php echo ( isset( $aswc_component['placeholder'] ) ? esc_attr( $aswc_component['placeholder'] ) : '' ); ?></span>
														<?php } ?>
													</span>
													<span class="mdc-notched-outline__trailing"></span>
												</span>
												<input 
												class="mdc-text-field__input <?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>" 
												name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>"
												id="<?php echo esc_attr( $component['id'] ); ?>"
												type="<?php echo esc_attr( $component['type'] ); ?>"
												value="<?php echo ( isset( $aswc_component['value'] ) ? esc_attr( $aswc_component['value'] ) : '' ); ?>"
												placeholder="<?php echo ( isset( $aswc_component['placeholder'] ) ? esc_attr( $aswc_component['placeholder'] ) : '' ); ?>"
												<?php echo esc_attr( ( 'number' === $component['type'] ) ? 'max=10 min=0' : '' ); ?>
												>
											</label>
								<?php } ?>
									<div class="mdc-text-field-helper-line">
										<div class="mdc-text-field-helper-text--persistent aswc-helper-text" id="" aria-hidden="true"><?php echo ( isset( $aswc_component['description'] ) ? esc_attr( $aswc_component['description'] ) : '' ); ?></div>
									</div>
								</div>
							</div>
								<?php
							break;
						case 'color':
						case 'date':
						case 'file':
							?>
							<div class="aswc-form-group aswc-<?php echo esc_attr( $aswc_component['type'] ); ?>">
								<div class="aswc-form-group__label">
									<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo ( isset( $aswc_component['title'] ) ? esc_html( $aswc_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
								</div>
								<div class="aswc-form-group__control">
									<label class="mdc-text-field mdc-text-field--outlined">
										<input 
										class="<?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>" 
										name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>"
										id="<?php echo esc_attr( $aswc_component['id'] ); ?>"
										type="<?php echo esc_attr( $aswc_component['type'] ); ?>"
										value="<?php echo ( isset( $aswc_component['value'] ) ? esc_attr( $aswc_component['value'] ) : '' ); ?>"
							<?php echo esc_html( ( 'date' === $aswc_component['type'] ) ? 'max=' . aswc_date( 'Y-m-d', strtotime( aswc_date( 'Y-m-d', aswc_get_wp_timestamp() ) . ' + 365 day' ) ) . 'min=' . aswc_date( 'Y-m-d', aswc_get_wp_timestamp() ) : '' ); ?>
										>
									</label>
									<div class="mdc-text-field-helper-line">
										<div class="mdc-text-field-helper-text--persistent aswc-helper-text" id="" aria-hidden="true"><?php echo ( isset( $aswc_component['description'] ) ? esc_attr( $aswc_component['description'] ) : '' ); ?></div>
									</div>
								</div>
							</div>
							<?php
							break;

						case 'submit':
							?>
						<tr valign="top">
							<td scope="row">
								<input type="submit" class="button button-primary" 
								name="<?php echo ( isset( $aswc_component['name'] ) ? esc_html( $aswc_component['name'] ) : esc_html( $aswc_component['id'] ) ); ?>"
								id="<?php echo esc_attr( $aswc_component['id'] ); ?>"
								class="<?php echo ( isset( $aswc_component['class'] ) ? esc_attr( $aswc_component['class'] ) : '' ); ?>"
								value="<?php echo esc_attr( $aswc_component['button_text'] ); ?>"
								/>
							</td>
						</tr>
							<?php
							break;

						default:
							break;
					}
				}
			}
		}
	}
}

