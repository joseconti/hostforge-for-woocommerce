<?php // phpcs:ignoreFile
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://plugins.joseconti.com
 * @since 1.0.0
 *
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce/includes
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
 * @since 1.0.0
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce/includes
 */
class ASWC {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since 1.0.0
	 * @access   protected
		 * @var      ASWC_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @access   protected
	 * @var      string    $aswc_onboard    To initializsed the object of class onboard.
	 */
	protected $aswc_onboard;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( defined( 'ASWC_VERSION' ) ) {
				$this->version = ASWC_VERSION;
		} else {

				$this->version = '1.0.0';
		}

				$this->plugin_name = 'advanced-subscriptions-for-woocommerce';

		$this->subscriptions_for_woocommerce_dependencies();
		$this->subscriptions_for_woocommerce_locale();
		if ( is_admin() ) {
				$this->aswc_admin_hooks();
		}
		$this->subscriptions_for_woocommerce_public_hooks();
		$this->aswc_logging_hooks();
		$this->aswc_api_hooks();
		$this->init();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - ASWC_Loader. Orchestrates the hooks of the plugin.
	 * - ASWC_i18n. Defines internationalization functionality.
	 * - ASWC_Admin. Defines all hooks for the admin area.
	 * - ASWC_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since 1.0.0
	 * @access   private
	 */
	private function subscriptions_for_woocommerce_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
							   require_once plugin_dir_path( __DIR__ ) . 'includes/class-aswc-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-aswc-i18n.php';

		if ( is_admin() ) {

				// The class responsible for defining all actions that occur in the admin area.
							   require_once plugin_dir_path( __DIR__ ) . 'admin/class-aswc-admin.php';

				// WooCommerce settings integration.
				require_once plugin_dir_path( __DIR__ ) . 'admin/class-aswc-wc-settings.php';
		}

				// The class responsible for defining all actions that occur in the public-facing side of the site.
								require_once plugin_dir_path( __DIR__ ) . 'public/class-aswc-public.php';

								require_once plugin_dir_path( __DIR__ ) . 'includes/rest-api/class-aswc-rest-api.php';

require_once plugin_dir_path( __DIR__ ) . 'includes/aswc-common-functions.php';
require_once plugin_dir_path( __DIR__ ) . 'includes/aswc-manual-renewal-handler.php';

$this->loader = new ASWC_Loader();

				/**
				 * Include the log file.
				 */
												require_once plugin_dir_path( __DIR__ ) . 'includes/class-aswc-log.php';
				/**
				* Include subscription schedule verifier.
				*/
												require_once plugin_dir_path( __DIR__ ) . 'includes/class-aswc-subscription-schedule-verifier.php';
// Helper to handle per-subscription scheduled actions.
require_once plugin_dir_path( __DIR__ ) . 'includes/class-aswc-schedule-helper.php';
// Restore and verify scheduled actions for existing subscriptions.
require_once plugin_dir_path( __DIR__ ) . 'includes/class-aswc-schedule-restorer.php';

// Event scheduler using the helper.
require_once plugin_dir_path( __DIR__ ) . 'includes/class-aswc-subscription-event-scheduler.php';
}
	/**
	 * The function is used to include email class.
	 */
	public function init() {
				add_filter( 'woocommerce_email_classes', array( $this, 'aswc_woocommerce_email_classes' ) );
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the ASWC_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since 1.0.0
	 * @access   private
	 */
	private function subscriptions_for_woocommerce_locale() {

		$plugin_i18n = new ASWC_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 * @access   private
	 */
	private function aswc_admin_hooks() {

		$aswc_plugin_admin = new ASWC_Admin( $this->aswc_get_plugin_name(), $this->aswc_get_version() );

		if ( class_exists( 'ASWC_WC_Settings' ) ) {
			ASWC_WC_Settings::init();
		}

		$this->loader->add_action( 'admin_enqueue_scripts', $aswc_plugin_admin, 'aswc_admin_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $aswc_plugin_admin, 'aswc_admin_enqueue_scripts' );
		$this->loader->add_filter( 'aswc_general_settings_array', $aswc_plugin_admin, 'aswc_admin_general_settings_page', 10 );

		// Saving tab settings.
		$this->loader->add_action( 'admin_init', $aswc_plugin_admin, 'sfw_admin_save_tab_settings' );

		// subscritpion box listing.
		$this->loader->add_filter( 'aswc_subscription_box_settings_array', $aswc_plugin_admin, 'aswc_subscription_box_settings_fields', 10 );
		$this->loader->add_filter( 'woocommerce_product_data_tabs', $aswc_plugin_admin, 'aswc_subscription_box_product_data_tabs', 10, 1 );

		if ( aswc_check_plugin_enable() ) {
			$this->loader->add_action( 'product_type_options', $aswc_plugin_admin, 'aswc_create_subscription_product_type' );
			$this->loader->add_filter( 'woocommerce_product_data_tabs', $aswc_plugin_admin, 'aswc_custom_product_tab_for_subscription' );
			$this->loader->add_action( 'woocommerce_product_data_panels', $aswc_plugin_admin, 'aswc_custom_product_fields_for_subscription' );
			$this->loader->add_action( 'woocommerce_process_product_meta', $aswc_plugin_admin, 'aswc_save_custom_product_fields_data_for_subscription', 10, 2 );
			$this->loader->add_action( 'init', $aswc_plugin_admin, 'aswc_admin_cancel_susbcription', 99 );
			$this->loader->add_action( 'init', $aswc_plugin_admin, 'aswc_admin_reactivate_onhold_susbcription', 99 );
			$this->loader->add_action( 'woocommerce_admin_order_data_after_order_details', $aswc_plugin_admin, 'aswc_show_subscription_id_in_order_details', 10, 1 );

			// WPLM Translation.
			$this->loader->add_filter( 'wcml_js_lock_fields_ids', $aswc_plugin_admin, 'aswc_add_lock_custom_fields_ids' );

			// subscription box working.
			$this->loader->add_filter( 'product_type_selector', $aswc_plugin_admin, 'aswc_register_subscription_box_product_type', 10, 1 );
			$this->loader->add_filter( 'woocommerce_product_data_tabs', $aswc_plugin_admin, 'aswc_custom_product_tab_for_subscription_box' );
			$this->loader->add_action( 'woocommerce_product_data_panels', $aswc_plugin_admin, 'aswc_custom_product_fields_for_subscription_box' );
			$this->loader->add_action( 'woocommerce_process_product_meta', $aswc_plugin_admin, 'aswc_save_subscription_box_data_for_subscription', 999, 2 );
		}

		// Highlight WooCommerce menu when viewing subscriptions list.
		$this->loader->add_filter( 'parent_file', $aswc_plugin_admin, 'fix_advanced_subscriptions_menu_parent' );

		// Add 'Upsell Support' column on payment gateways page.
		$this->loader->add_filter( 'woocommerce_payment_gateways_setting_columns', $aswc_plugin_admin, 'aswc_subscription_support_in_payment_gateway' );
		// 'Upsell Support' content on payment gateways page.
		$this->loader->add_action( 'woocommerce_payment_gateways_setting_column_aswc_sub_renewal', $aswc_plugin_admin, 'aswc_subscription_content_in_payment_gateway' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 * @access   private
	 */
	private function subscriptions_for_woocommerce_public_hooks() {

		$aswc_plugin_public = new ASWC_Public( $this->aswc_get_plugin_name(), $this->aswc_get_version() );

		if ( aswc_check_plugin_enable() ) {
			$this->loader->add_action( 'wp_enqueue_scripts', $aswc_plugin_public, 'aswc_public_enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $aswc_plugin_public, 'aswc_public_enqueue_scripts' );
			$this->loader->add_filter( 'woocommerce_get_price_html', $aswc_plugin_public, 'aswc_price_html_subscription_product', 10, 2 );
			$this->loader->add_filter( 'woocommerce_product_single_add_to_cart_text', $aswc_plugin_public, 'aswc_product_add_to_cart_text', 10, 2 );
			$this->loader->add_filter( 'woocommerce_product_add_to_cart_text', $aswc_plugin_public, 'aswc_product_add_to_cart_text', 10, 2 );
			$this->loader->add_filter( 'woocommerce_order_button_text', $aswc_plugin_public, 'aswc_woocommerce_order_button_text' );
			$this->loader->add_filter( 'woocommerce_cart_item_price', $aswc_plugin_public, 'aswc_show_subscription_price_on_cart', 99, 3 );
			$this->loader->add_action( 'woocommerce_before_calculate_totals', $aswc_plugin_public, 'aswc_add_subscription_price_and_sigup_fee', 999 );
			$this->loader->add_action( 'woocommerce_checkout_order_processed', $aswc_plugin_public, 'aswc_process_checkout', 999, 2 );
			$this->loader->add_action( 'woocommerce_available_payment_gateways', $aswc_plugin_public, 'aswc_unset_offline_payment_gateway_for_subscription' );
			$this->loader->add_action( 'init', $aswc_plugin_public, 'aswc_add_subscription_tab_on_myaccount_page' );
			$this->loader->add_filter( 'query_vars', $aswc_plugin_public, 'aswc_custom_endpoint_query_vars' );
			$this->loader->add_filter( 'woocommerce_account_menu_items', $aswc_plugin_public, 'aswc_add_subscription_dashboard_on_myaccount_page' );
                        $this->loader->add_action( 'woocommerce_account_aswc-subscriptions_endpoint', $aswc_plugin_public, 'aswc_subscription_dashboard_content' );
			$this->loader->add_action( 'woocommerce_before_checkout_form', $aswc_plugin_public, 'aswc_subscription_before_checkout_form' );
			$this->loader->add_action( 'aswc_display_susbcription_recerring_total_account_page', $aswc_plugin_public, 'aswc_display_susbcription_recerring_total_account_page_callback' );
			$this->loader->add_action( 'woocommerce_account_show-subscription_endpoint', $aswc_plugin_public, 'aswc_shwo_subscription_details' );
			$this->loader->add_action( 'init', $aswc_plugin_public, 'aswc_cancel_susbcription', 99 );
			$this->loader->add_action( 'init', $aswc_plugin_public, 'aswc_disable_subscription_cart_validator', 20 );
			$this->loader->add_action( 'woocommerce_order_status_processing', $aswc_plugin_public, 'aswc_activate_subscription_on_payment', 10, 1 );
			$this->loader->add_action( 'woocommerce_order_status_completed', $aswc_plugin_public, 'aswc_activate_subscription_on_payment', 10, 1 );
			$this->loader->add_action( 'after_woocommerce_pay', $aswc_plugin_public, 'aswc_after_woocommerce_pay', 100 );
			$this->loader->add_action( 'wp_loaded', $aswc_plugin_public, 'aswc_change_payment_method_form', 20 );
			$this->loader->add_filter( 'woocommerce_order_get_total', $aswc_plugin_public, 'aswc_set_susbcription_total', 11, 2 );
			$this->loader->add_filter( 'woocommerce_is_sold_individually', $aswc_plugin_public, 'aswc_hide_quantity_fields_for_subscription', 10, 2 );
			$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $aswc_plugin_public, 'aswc_woocommerce_add_to_cart_validation', 10, 5 );
			$this->loader->add_filter( 'woocommerce_cart_needs_payment', $aswc_plugin_public, 'aswc_woocommerce_cart_needs_payment', 99, 2 );
			$this->loader->add_action( 'woocommerce_order_status_changed', $aswc_plugin_public, 'aswc__cancel_subs_woocommerce_order_status_changed', 150, 3 );
			$this->loader->add_filter( 'woocommerce_checkout_registration_required', $aswc_plugin_public, 'aswc_registration_required', 900 );
			$this->loader->add_filter( 'woocommerce_gateway_description', $aswc_plugin_public, 'aswc_change_payment_gateway_description', 10, 2 );
			$this->loader->add_action( 'woocommerce_review_order_after_order_total', $aswc_plugin_public, 'aswc_show_recurring_information', 10, 1 );

			// WC block.
			$this->loader->add_action( 'template_redirect', $aswc_plugin_public, 'aswc_to_cart_and_checkout_blocks' );
			$this->loader->add_filter( 'woocommerce_get_item_data', $aswc_plugin_public, 'aswc_get_subscription_meta_on_cart', 10, 2 );
			$this->loader->add_action( 'woocommerce_store_api_checkout_update_order_from_request', $aswc_plugin_public, 'aswc_validate_block_checkout_payment', 10, 2 );
			$this->loader->add_action( 'woocommerce_store_api_checkout_order_processed', $aswc_plugin_public, 'aswc_process_checkout_hpos', 100 );
			$this->loader->add_action( 'aswc_subscription_cancel', $aswc_plugin_public, 'aswc_cancel_manual_subscription', 10, 2 );

			// Change the subject, heading and content for the failed renewal order.
			$this->loader->add_action( 'woocommerce_email_before_order_table', $aswc_plugin_public, 'aswc_add_custom_failed_order_section', 10, 4 );
			$this->loader->add_filter( 'woocommerce_email_subject_failed_order', $aswc_plugin_public, 'aswc_custom_woocommerce_email_subject_failed_order', 10, 2 );
			$this->loader->add_filter( 'woocommerce_email_heading_failed_order', $aswc_plugin_public, 'aswc_custom_woocommerce_email_heading_failed_order', 10, 2 );

			// Learnpress Compatibility.
			$this->loader->add_action( 'woocommerce_single_product_summary', $aswc_plugin_public, 'aswc_course_description', 20 );
			$this->loader->add_filter( 'learnpress/course/item/can-view', $aswc_plugin_public, 'aswc_course_can_view', 10, 3 );

			// Manage the zero checkout for the stripe .
			$this->loader->add_filter( 'woocommerce_order_needs_payment', $aswc_plugin_public, 'aswc_woocommerce_order_needs_payment', 10, 3 );

			// subscription box.
			$this->loader->add_action( 'woocommerce_single_product_summary', $aswc_plugin_public, 'aswc_subscription_box_info_above_add_to_cart', 20 );
			$this->loader->add_action( 'woocommerce_subscription_box_add_to_cart', $aswc_plugin_public, 'aswc_subscription_box_create_button', 20 );
			$this->loader->add_action( 'aswc_subscription_subscription_box_addtion', $aswc_plugin_public, 'aswc_subscription_subscription_box_addtion_callback', 10, 3 );
			$this->loader->add_action( 'wp_ajax_aswc_handle_subscription_box', $aswc_plugin_public, 'aswc_handle_subscription_box' );
			$this->loader->add_action( 'wp_ajax_nopriv_aswc_handle_subscription_box', $aswc_plugin_public, 'aswc_handle_subscription_box' );
			$this->loader->add_action( 'woocommerce_before_calculate_totals', $aswc_plugin_public, 'aswc_update_subscription_box_prices', 99 );
			$this->loader->add_filter( 'woocommerce_get_item_data', $aswc_plugin_public, 'aswc_subscription_box_meta_on_cart', 10, 2 );
			$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $aswc_plugin_public, 'aswc_add_order_line_item_for_subscription_box', 10, 4 );
			$this->loader->add_action( 'wp_ajax_aswc_get_cart_item', $aswc_plugin_public, 'aswc_get_cart_item' );
			$this->loader->add_action( 'wp_ajax_nopriv_aswc_get_cart_item', $aswc_plugin_public, 'aswc_get_cart_item' );
			$this->loader->add_filter( 'woocommerce_get_item_data', $aswc_plugin_public, 'aswc_add_item_data_cart_block_subscription_box', 10, 2 );
			$this->loader->add_filter( 'woocommerce_cart_item_name', $aswc_plugin_public, 'aswc_show_attached_product_html_subscription_box', 10, 3 );
			$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $aswc_plugin_public, 'aswc_subscription_box_woocommerce_add_to_cart_validation', 10, 5 );
			$this->loader->add_filter( 'woocommerce_is_sold_individually', $aswc_plugin_public, 'aswc_hide_quantity_fields_for_subscription_box', 10, 2 );
			$this->loader->add_filter( 'woocommerce_email_preview_dummy_order', $aswc_plugin_public, 'aswc_woocommerce_email_preview_dummy_order_callback', 10, 2 );
			$this->loader->add_filter( 'body_class', $aswc_plugin_public, 'aswc_subscription_custom_add_body_class', 10, 1 );
			$this->loader->add_filter( 'woocommerce_register_shop_order_post_statuses', $aswc_plugin_public, 'aswc_register_new_order_statuses' );
			$this->loader->add_filter( 'wc_order_statuses', $aswc_plugin_public, 'aswc_new_wc_order_statuses' );

		}
	}

	/**
	 * Register debug logging hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return void
	 */
	private function aswc_logging_hooks() {
		$this->loader->add_action( 'transition_post_status', $this, 'aswc_log_subscription_status', 10, 3 );
		$this->loader->add_action( 'transition_post_status', $this, 'aswc_sync_subscription_status', 20, 3 );
		$this->loader->add_action( 'woocommerce_order_status_changed', $this, 'aswc_sync_subscription_status_on_order_change', 20, 4 );
		$this->loader->add_action( 'wp_trash_post', $this, 'aswc_log_subscription_trashed', 10, 1 );
	}

	/**
	 * Log status transitions for subscriptions.
	 *
	 * @since 1.0.0
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function aswc_log_subscription_status( $new_status, $old_status, $post ) {
		if ( 'aswc_subscriptions' !== $post->post_type ) {
			return;
		}
		ASWC_Log::log( 'Subscription ' . $post->ID . ' status changed from ' . $old_status . ' to ' . $new_status );
	}

	/**
	 * Handle subscription status changes when the post status changes.
	 *
	 * @since 1.0.0
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function aswc_sync_subscription_status( $new_status, $old_status, $post ) {
		if ( 'aswc_subscriptions' !== $post->post_type ) {
			return;
		}

		$new_status = str_replace( 'wc-', '', $new_status );
		$old_status = str_replace( 'wc-', '', $old_status );

		if ( $old_status === $new_status ) {
			return;
		}

		if ( function_exists( 'aswc_handle_subscription_status_change' ) ) {
			aswc_handle_subscription_status_change( $post->ID, $new_status, $old_status );
		}
	}

	/**
	 * Handle subscription order status changes (HPOS and CPT).
	 *
	 * @since 1.0.0
	 * @param int      $order_id   Order ID.
	 * @param string   $old_status Previous status (without wc- prefix).
	 * @param string   $new_status New status (without wc- prefix).
	 * @param WC_Order $order      Order object.
	 * @return void
	 */
	public function aswc_sync_subscription_status_on_order_change( $order_id, $old_status, $new_status, $order ) {
		$order_id = absint( $order_id );
		if ( 0 === $order_id ) {
			return;
		}

		$old_status = isset( $old_status ) ? sanitize_text_field( $old_status ) : '';
		$new_status = isset( $new_status ) ? sanitize_text_field( $new_status ) : '';

		if ( empty( $new_status ) ) {
			return;
		}

		if ( 0 === strcmp( $old_status, $new_status ) ) {
			return;
		}

		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order || ! method_exists( $order, 'get_type' ) ) {
			return;
		}

		if ( 'aswc_subscriptions' !== $order->get_type() ) {
			return;
		}

		$new_status = str_replace( 'wc-', '', $new_status );
		$old_status = str_replace( 'wc-', '', $old_status );

		if ( 0 === strcmp( $old_status, $new_status ) ) {
			return;
		}

		if ( function_exists( 'aswc_handle_subscription_status_change' ) ) {
			aswc_handle_subscription_status_change( $order_id, $new_status, $old_status );
		}
	}

	/**
	 * Log when a subscription is moved to trash.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID being trashed.
	 * @return void
	 */
	public function aswc_log_subscription_trashed( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'aswc_subscriptions' !== $post->post_type ) {
			return;
		}

		ASWC_Log::log( 'Subscription ' . $post_id . ' moved to trash' );
	}

	/**
	 * The function include email class.
	 *
	 * @name aswc_woocommerce_email_classes.
	 * @since 1.0.0
	 * @param Array $emails emails.
	 */
	public function aswc_woocommerce_email_classes( $emails ) {

		$cancel_path                               = plugin_dir_path( __DIR__ ) . 'emails/class-aswc-cancel-subscription-email.php';
		$emails['aswc_cancel_subscription']        = require_once $cancel_path;
		$expired_path                              = plugin_dir_path( __DIR__ ) . 'emails/class-aswc-expired-subscription-email.php';
		$emails['aswc_expired_subscription']       = require_once $expired_path;
		$onhold_path                               = plugin_dir_path( __DIR__ ) . 'emails/class-aswc-onhold-active-subscription-email.php';
		$emails['aswc_onhold_active_subscription'] = require_once $onhold_path;

		return apply_filters( 'aswc_email_classes', $emails );
	}
	/**
	 * Register all of the hooks related to the api functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 * @access   private
	 */
	private function aswc_api_hooks() {

		$aswc_plugin_api = new ASWC_Rest_Api( $this->aswc_get_plugin_name(), $this->aswc_get_version() );
		$this->loader->add_action( 'rest_api_init', $aswc_plugin_api, 'aswc_add_endpoint' );
	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function aswc_run() {
		$this->loader->aswc_run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since 1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function aswc_get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since 1.0.0
	 * @return    ASWC_Loader    Orchestrates the hooks of the plugin.
	 */
	public function aswc_get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since 1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function aswc_get_version() {
		return $this->version;
	}

	/**
	 * Predefined default aswc_plug tabs.
	 *
	 * @return  Array       An key=>value pair of Subscriptions For Woocommerce tabs.
	 */
	public function aswc_plug_default_tabs() {

		$aswc_default_tabs = array();

		$aswc_default_tabs = apply_filters( 'aswc_sfw_plugin_standard_admin_settings_tabs', $aswc_default_tabs );

		$aswc_default_tabs['aswc-subscriptions-table'] = array(
			'title'     => esc_html__( 'Subscription Table', 'advanced-subscriptions-for-woocommerce' ),
			'name'      => 'aswc-subscriptions-table',
			'file_path' => ASWC_DIR_PATH,
		);

		$aswc_default_tabs = apply_filters( 'aswc_sfw_plugin_standard_admin_settings_tabs_before', $aswc_default_tabs );
		// Removed System Status and Developer tabs.
		$aswc_default_tabs = apply_filters( 'aswc_sfw_plugin_standard_admin_settings_tabs_end', $aswc_default_tabs );

		return $aswc_default_tabs;
	}

	/**
	 * Locate and load appropriate tempate.
	 *
	 * @since 1.0.0
	 * @param string $content_path content_path file for inclusion.
	 */
	public function aswc_plug_load_template( $content_path ) {

		if ( file_exists( $content_path ) ) {

			include $content_path;
		} else {

			/* translators: %s: file path */
			$aswc_notice = sprintf( esc_html__( 'Unable to locate file at location "%s". Some features may not work properly in this plugin. Please contact us!', 'advanced-subscriptions-for-woocommerce' ), $content_path );
			$this->aswc_plug_admin_notice( $aswc_notice, 'error' );
		}
	}

	/**
	 * Show admin notices.
	 *
	 * @param  string $aswc_message    Message to display.
	 * @param  string $type       notice type, accepted values - error/update/update-nag.
	 * @since 1.0.0
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
	 * Show WordPress and server info.
	 *
	 * @return  Array $aswc_system_data       returns array of all WordPress and server related information.
	 * @since 1.0.0
	 */

	/**
	 * Generate html components.
	 *
	 * @param  string $aswc_components    html to display.
	 * @since 1.0.0
	 */
	public function aswc_plug_generate_html( $aswc_components = array() ) {
		if ( is_array( $aswc_components ) && ! empty( $aswc_components ) ) {
			foreach ( $aswc_components as $aswc_component ) {
				$aswc_name = array_key_exists( 'name', $aswc_component ) ? $aswc_component['name'] : $aswc_component['id'];

				$pro_group_tag = '';

				switch ( $aswc_component['type'] ) {

					case 'hidden':
					case 'number':
					case 'email':
					case 'text':
						?>
					<div class="aswc-form-group aswc-<?php echo esc_attr( $aswc_component['type'] ); ?>">
						<div class="aswc-form-group__label">
							<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo esc_html( $aswc_component['title'] ); // WPCS: XSS ok. ?></label>
						</div>
						<div class="aswc-form-group__control">
							<label class="mdc-text-field mdc-text-field--outlined">
								<span class="mdc-notched-outline">
									<span class="mdc-notched-outline__leading"></span>
									<span class="mdc-notched-outline__notch">
										<?php if ( 'number' !== $aswc_component['type'] ) { ?>
											<span class="mdc-floating-label" id="my-label-id" style=""><?php echo esc_attr( $aswc_component['placeholder'] ); ?></span>
										<?php } ?>
									</span>
									<span class="mdc-notched-outline__trailing"></span>
								</span>
								<input 
								class="mdc-text-field__input <?php echo esc_attr( $aswc_component['class'] ); ?>" 
								name="<?php echo esc_attr( $aswc_name ); ?>"
								id="<?php echo esc_attr( $aswc_component['id'] ); ?>"
								type="<?php echo esc_attr( $aswc_component['type'] ); ?>"
								value="<?php echo esc_attr( $aswc_component['value'] ); ?>"
								placeholder="<?php echo esc_attr( $aswc_component['placeholder'] ); ?>"
								<?php echo ( isset( $aswc_component['required'] ) && 'yes' === $aswc_component['required'] ) ? 'required' : ''; ?>
								>
							</label>
							<div class="mdc-text-field-helper-line">
								<div class="mdc-text-field-helper-text--persistent aswc-helper-text" id="" aria-hidden="true"><?php echo esc_attr( $aswc_component['description'] ); ?></div>
							</div>
						</div>
					</div>
						<?php
						break;

					case 'password':
						?>
					<div class="aswc-form-group">
						<div class="aswc-form-group__label">
							<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo esc_html( $aswc_component['title'] ); // WPCS: XSS ok. ?></label>
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
								class="mdc-text-field__input <?php echo esc_attr( $aswc_component['class'] ); ?> aswc-form__password" 
								name="<?php echo esc_attr( $aswc_name ); ?>"
								id="<?php echo esc_attr( $aswc_component['id'] ); ?>"
								type="<?php echo esc_attr( $aswc_component['type'] ); ?>"
								value="<?php echo esc_attr( $aswc_component['value'] ); ?>"
								placeholder="<?php echo esc_attr( $aswc_component['placeholder'] ); ?>"
								>
								<i class="material-icons mdc-text-field__icon mdc-text-field__icon--trailing aswc-password-hidden" tabindex="0" role="button">visibility</i>
							</label>
							<div class="mdc-text-field-helper-line">
								<div class="mdc-text-field-helper-text--persistent aswc-helper-text" id="" aria-hidden="true"><?php echo esc_attr( $aswc_component['description'] ); ?></div>
							</div>
						</div>
					</div>
						<?php
						break;

					case 'textarea':
						?>
					<div class="aswc-form-group">
						<div class="aswc-form-group__label">
							<label class="aswc-form-label" for="<?php echo esc_attr( $aswc_component['id'] ); ?>"><?php echo esc_attr( $aswc_component['title'] ); ?></label>
						</div>
						<div class="aswc-form-group__control">
							<label class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea"  	for="text-field-hero-input">
								<span class="mdc-notched-outline">
									<span class="mdc-notched-outline__leading"></span>
									<span class="mdc-notched-outline__notch">
										<span class="mdc-floating-label"><?php echo esc_attr( $aswc_component['placeholder'] ); ?></span>
									</span>
									<span class="mdc-notched-outline__trailing"></span>
								</span>
								<span class="mdc-text-field__resizer">
									<textarea class="mdc-text-field__input <?php echo esc_attr( $aswc_component['class'] ); ?>" rows="2" cols="25" aria-label="Label" name="<?php echo esc_attr( $aswc_name ); ?>" id="<?php echo esc_attr( $aswc_component['id'] ); ?>" placeholder="<?php echo esc_attr( $aswc_component['placeholder'] ); ?>"<?php echo ( isset( $aswc_component['required'] ) && 'yes' === $aswc_component['required'] ) ? 'required' : ''; ?>><?php echo esc_textarea( $aswc_component['value'] ); // WPCS: XSS ok. ?></textarea>
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
							<label class="aswc-form-label" for="<?php echo esc_attr( $aswc_component['id'] ); ?>"><?php echo esc_html( $aswc_component['title'] ); ?></label>
						</div>
						<div class="aswc-form-group__control">
							<div class="aswc-form-select">
								<select name="<?php echo esc_attr( $aswc_name ); ?><?php echo ( 'multiselect' === $aswc_component['type'] ) ? '[]' : ''; ?>" id="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="mdl-textfield__input <?php echo esc_attr( $aswc_component['class'] ); ?>" <?php echo 'multiselect' === $aswc_component['type'] ? 'multiple="multiple"' : ''; ?> >
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
								<label class="mdl-textfield__label" for="octane"><?php echo esc_html( $aswc_component['description'] ); ?></label>
							</div>
						</div>
					</div>

						<?php
						break;

					case 'checkbox':
						?>
					<div class="aswc-form-group">
						<div class="aswc-form-group__label">
							<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo esc_html( $aswc_component['title'] ); ?></label>
						</div>
						<div class="aswc-form-group__control aswc-pl-4">
							<div class="mdc-form-field">
								<div class="mdc-checkbox">
									<input 
									name="<?php echo esc_attr( $aswc_name ); ?>"
									id="<?php echo esc_attr( $aswc_component['id'] ); ?>"
									type="checkbox"
									class="mdc-checkbox__native-control <?php echo esc_attr( isset( $aswc_component['class'] ) ? $aswc_component['class'] : '' ); ?>"
									value="<?php echo esc_attr( $aswc_component['value'] ); ?>"
																		<?php
																		if ( aswc_is_true( $aswc_component['checked'] ) ) {
																				checked( $aswc_component['checked'], 'on' );
																		}
																		?>
									/>
									<div class="mdc-checkbox__background">
										<svg class="mdc-checkbox__checkmark" viewBox="0 0 24 24">
											<path class="mdc-checkbox__checkmark-path" fill="none" d="M1.73,12.91 8.1,19.28 22.79,4.59"/>
										</svg>
										<div class="mdc-checkbox__mixedmark"></div>
									</div>
									<div class="mdc-checkbox__ripple"></div>
								</div>
								<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>"><?php echo wp_kses_post( $aswc_component['description'] ); // WPCS: XSS ok. ?></label>
							</div>
						</div>
					</div>
						<?php
						break;

					case 'radio':
						?>
					<div class="aswc-form-group">
						<div class="aswc-form-group__label">
							<label for="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="aswc-form-label"><?php echo esc_html( $aswc_component['title'] ); ?></label>
						</div>
						<div class="aswc-form-group__control aswc-pl-4">
							<div class="aswc-flex-col">
								<?php
								foreach ( $aswc_component['options'] as $aswc_radio_key => $aswc_radio_val ) {
									?>
									<div class="mdc-form-field">
										<div class="mdc-radio">
											<input
											id = "<?php echo esc_attr( $aswc_component['id'] ); ?>"
											name="<?php echo esc_attr( $aswc_name ); ?>"
											value="<?php echo esc_attr( $aswc_radio_key ); ?>"
											type="radio"
											class="mdc-radio__native-control <?php echo esc_attr( $aswc_component['class'] ); ?>"
											<?php checked( $aswc_radio_key, $aswc_component['value'] ); ?>
											<?php echo ( isset( $aswc_component['required'] ) && 'yes' === $aswc_component['required'] ) ? 'required' : ''; ?>
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

					<div class="aswc-form-group <?php echo esc_attr( $pro_group_tag ); ?>">
						<div class="aswc-form-group__label">
							<label for="" class="aswc-form-label"><?php echo esc_html( $aswc_component['title'] ); ?></label>
						</div>
						<div class="aswc-form-group__control">
							<div>
								<div class="mdc-switch">
									<div class="mdc-switch__track"></div>
									<div class="mdc-switch__thumb-underlay">
										<div class="mdc-switch__thumb"></div>
										<input name="<?php echo esc_attr( $aswc_name ); ?>" type="checkbox" id="basic-switch" value="on" class="mdc-switch__native-control" role="switch" aria-checked="
																<?php
																if ( aswc_is_true( $aswc_component['value'] ) ) {
																	echo 'true';
																} else {
																	echo 'false';
																}
																?>
										"
										<?php checked( aswc_is_true( $aswc_component['value'] ), true ); ?>
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
							<button class="mdc-button mdc-button--raised" name="<?php echo esc_attr( $aswc_name ); ?>"
								id="<?php echo esc_attr( $aswc_component['id'] ); ?>"> <span class="mdc-button__ripple"></span>
								<span class="mdc-button__label"><?php echo esc_attr( $aswc_component['button_text'] ); ?></span>
							</button>
						</div>
					</div>

						<?php
						break;

					case 'submit':
						?>
					<tr valign="top">
						<td scope="row">
							<input type="submit" class="button button-primary" 
							name="<?php echo esc_attr( $aswc_name ); ?>"
							id="<?php echo esc_attr( $aswc_component['id'] ); ?>"
							value="<?php echo esc_attr( $aswc_component['button_text'] ); ?>"
							/>
						</td>
					</tr>
						<?php
						break;
					case 'information':
						?>
						<p id="<?php echo esc_attr( $aswc_component['id'] ); ?>" class="<?php echo esc_attr( $aswc_component['class'] ); ?>" >
						<?php echo esc_attr( $aswc_name ); ?>
						</p>
						<?php
						break;
					default:
						break;

				}
			}
		}
	}
}
