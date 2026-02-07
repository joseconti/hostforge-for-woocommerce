<?php
/**
 * YITH WooCommerce Subscription Install. Perform actions on install plugin
 *
 * @author  YITH
 * @package YITH\Subscription
 * @version 3.0.0
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Install' ) ) {
	/**
	 * YITH WooCommerce Subscription Install class
	 */
	final class YITH_WC_Subscription_Install {

		/**
		 * Updates and callbacks that need to be run per version.
		 *
		 * @var array
		 */
		private static $updates = array(
			'2.0.0' => array(
				'update_200',
			),
			'3.0.0' => array(
				'update_300',
			),
		);

		/**
		 * Install plugin process
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function install() {

			add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ), 12 );
			add_action( 'init', array( __CLASS__, 'register_post_type' ), 5 );

			self::define_class_aliases();
			// Define tables alias.
			self::define_tables();
			add_action( 'switch_blog', array( __CLASS__, 'define_tables' ), 0 );

			self::maybe_do_activation();

			// Register plugin to licence/update system.
			add_action( 'wp_loaded', array( __CLASS__, 'register_plugin_for_activation' ), 99 );
			add_action( 'wp_loaded', array( __CLASS__, 'register_plugin_for_updates' ), 99 );

			// Declare support with HPOS system for WooCommerce 8.
			add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_hpos_support' ) );

			do_action( 'ywsbs_after_installation_process' );
		}

		/**
		 * Define plugin class aliases for backward compatibility
		 *
		 * @since  3.0.0
		 */
		protected static function define_class_aliases() {
			class_alias( 'YITH_WC_Subscription_DB_Legacy', 'YITH_WC_Subscription_DB' );
		}

		/**
		 * Load plugin texdomain
		 *
		 * @since 4.4.0
		 * @return void
		 */
		public static function load_textdomain() {
			if ( function_exists( 'yith_plugin_fw_load_plugin_textdomain' ) ) {
				yith_plugin_fw_load_plugin_textdomain( 'yith-woocommerce-subscription', dirname( plugin_basename( YITH_YWSBS_FILE ) ) . '/languages/' );
			}
		}

		/**
		 * Define plugin tables aliases
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function define_tables() {
			global $wpdb;

			$wpdb->ywsbs_activities_log = $wpdb->prefix . 'yith_ywsbs_activities_log';
			$wpdb->tables[]             = 'yith_ywsbs_activities_log';
		}

		/**
		 * Register ywsbs_subscription post type
		 *
		 * @since 1.0.0
		 */
		public static function register_post_type() {

			$supports = false;
			if ( apply_filters( 'ywsbs_test_on', YITH_YWSBS_TEST_ON ) ) {
				$supports = array( 'custom-fields' );
			}

			$args = array(
				'label'               => esc_html__( 'ywsbs_subscription', 'yith-woocommerce-subscription' ),
				'labels'              => array(
					'name'               => esc_html_x( 'Subscriptions', 'Post Type General Name', 'yith-woocommerce-subscription' ),
					'singular_name'      => esc_html_x( 'Subscription', 'Post Type Singular Name', 'yith-woocommerce-subscription' ),
					'menu_name'          => esc_html__( 'Subscription', 'yith-woocommerce-subscription' ),
					'parent_item_colon'  => esc_html__( 'Parent item:', 'yith-woocommerce-subscription' ),
					'all_items'          => esc_html__( 'All subscriptions', 'yith-woocommerce-subscription' ),
					'view_item'          => esc_html__( 'View subscriptions', 'yith-woocommerce-subscription' ),
					'add_new_item'       => esc_html__( 'Add new subscription', 'yith-woocommerce-subscription' ),
					'add_new'            => esc_html__( 'Add new subscription', 'yith-woocommerce-subscription' ),
					'edit_item'          => esc_html__( 'Edit subscription', 'yith-woocommerce-subscription' ),
					'update_item'        => esc_html__( 'Update subscription', 'yith-woocommerce-subscription' ),
					'search_items'       => esc_html__( 'Search by subscription ID', 'yith-woocommerce-subscription' ),
					'not_found'          => esc_html__( 'Not found', 'yith-woocommerce-subscription' ),
					'not_found_in_trash' => esc_html__( 'Not found in trash', 'yith-woocommerce-subscription' ),
				),
				'supports'            => $supports,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'capability_type'     => 'ywsbs_sub',
				'capabilities'        => array(
					'read_post'          => 'read_ywsbs_sub',
					'read_private_posts' => 'read_ywsbs_sub',
					'edit_post'          => 'edit_ywsbs_sub',
					'edit_posts'         => 'edit_ywsbs_subs',
					'edit_others_post'   => 'edit_others_ywsbs_subs',
					'delete_post'        => 'delete_ywsbs_sub',
					'delete_others_post' => 'delete_others_ywsbs_subs',
				),
				'map_meta_cap'        => false,
			);

			register_post_type( YITH_YWSBS_POST_TYPE, $args );

			do_action( 'ywsbs_after_register_post_type' );
		}

		/**
		 * Must execute activation process?
		 * Conditions:
		 *  - current version installed is older than current one;
		 *  - forced by query string;
		 *
		 * @since  3.0.0
		 * @return void
		 */
		protected static function maybe_do_activation() {
			if ( version_compare( self::get_installed_version(), YITH_YWSBS_VERSION, '<' ) || ! empty( $_GET['ywsbs_force_activation_process'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				self::activate();
			}
		}

		/**
		 * Register plugins for activation tab
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public static function register_plugin_for_activation() {
			if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
				include_once YITH_YWSBS_DIR . 'plugin-fw/licence/lib/yit-licence.php';
				include_once YITH_YWSBS_DIR . 'plugin-fw/licence/lib/yit-plugin-licence.php';
			}

			YIT_Plugin_Licence()->register( YITH_YWSBS_INIT, YITH_YWSBS_SECRET_KEY, YITH_YWSBS_SLUG );
		}


		/**
		 * Register plugins for update tab
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public static function register_plugin_for_updates() {
			if ( ! class_exists( 'YIT_Upgrade' ) ) {
				include_once YITH_YWSBS_DIR . 'plugin-fw/lib/yit-upgrade.php';
			}

			YIT_Upgrade()->register( YITH_YWSBS_SLUG, YITH_YWSBS_INIT );
		}

		/**
		 * Get current installed plugin version.
		 * If it's first installation get the current version to avoid processing version migration actions.
		 *
		 * @since  3.0.0
		 * @return string
		 */
		protected static function get_installed_version() {
			// Check for old option in order to avoid processing update action again.
			if ( get_option( 'ywsbs_update_2_0', false ) ) {
				return '2.0.0';
			}

			return get_option( 'yith_ywsbs_version', YITH_YWSBS_VERSION );
		}

		/**
		 * Load the plugin fw
		 *
		 * @since  3.0.0
		 * @return void
		 */
		protected static function load_plugin_framework() {
			if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if ( ! empty( $plugin_fw_data ) ) {
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once $plugin_fw_file;
				}
			}
		}

		/**
		 * Activation plugin process
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function activate() {

			// Make sure plugin FW is loaded.
			self::load_plugin_framework();
			self::create_tables();

			// Set subscription capabilities.
			YWSBS_Subscription_Capabilities::add_capabilities();

			// Update callbacks.
			foreach ( self::$updates as $version => $callbacks ) {
				if ( version_compare( self::get_installed_version(), $version, '<' ) ) {
					foreach ( $callbacks as $callback ) {
						self::$callback();
					}
				}
			}

			// Regenerate permalink on custom post type registration.
			flush_rewrite_rules();

			update_option( 'yith_ywsbs_version', YITH_YWSBS_VERSION );

			do_action( 'yith_ywsbs_plugin_activation_process_completed' );
		}

		/**
		 * Deactivation plugin process
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function deactivate() {
			YWSBS_Subscription_Capabilities::remove_capabilities();
		}

		/**
		 * Declare HPOS support
		 *
		 * @since 3.2.0
		 * @return void
		 */
		public static function declare_hpos_support() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YITH_YWSBS_INIT );
			}
		}

		/**
		 * Create plugin tables
		 *
		 * @since 3.0.0
		 * @return void
		 */
		public static function create_tables() {
			global $wpdb;

			$wpdb->hide_errors();

			$table_name         = $wpdb->prefix . 'yith_ywsbs_activities_log';
			$subscription_stats = $wpdb->prefix . 'yith_ywsbs_stats';
			$order_lookup       = $wpdb->prefix . 'yith_ywsbs_order_lookup';
			$revenue_lookup     = $wpdb->prefix . 'yith_ywsbs_revenue_lookup';
			$charset_collate    = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
							`id` int(11) NOT NULL AUTO_INCREMENT,
							`activity` varchar(255) NOT NULL,
							`status` varchar(255) NOT NULL,
							`subscription` int(11) NOT NULL,
							`order` int(11) NOT NULL,
							`description` varchar(255) NOT NULL,
							`timestamp_date` datetime NOT NULL,
							PRIMARY KEY (id)
						) $charset_collate;";

			$sql .= "CREATE TABLE $subscription_stats (
                    `subscription_id` bigint(20) NOT NULL,
                    `status` varchar(200) NOT NULL,
                    `customer_id`  bigint(20) NOT NULL,
                    `date_created` datetime NOT NULL,
                    `date_created_gmt` datetime NOT NULL,
                    `product_name` varchar(200) NOT NULL,
                    `product_id` bigint(20) NOT NULL,
  					`variation_id` bigint(20) DEFAULT NULL,
  					`currency` varchar(20) NOT NULL,
  					`quantity` int(20) NOT NULL,
  					`fee` double,
  					`total` double,
  					`tax_total` double,
  					`shipping_total` double,
  					`net_total` double,
  					`mrr` double,
  					`arr` double,
  					`next_payment_due_date` varchar(200),
  					`trial` tinyint(1),
  					`conversion_date` datetime,
  					`cancelled_date` datetime,
  					`orders_paid` bigint(20),
                    PRIMARY KEY (`subscription_id`)
                    ) $charset_collate;";

			$sql .= "CREATE TABLE $order_lookup (
                    `order_id` bigint(20) NOT NULL,
                    `subscription_id` bigint(20) NOT NULL,
                    `status` varchar(200) NOT NULL,
                    `customer_id`  bigint(20) NOT NULL,
                    `date_created` datetime NOT NULL,
                    `date_paid` datetime NOT NULL,
  					`total` double,
  					`tax_total` double,
  					`shipping_total` double,
  					`net_total` double,
  					`renew` tinyint(1),
                     PRIMARY KEY (`order_id`,`subscription_id`)
                    ) $charset_collate;";

			$sql .= "CREATE TABLE $revenue_lookup (
                    `subscription_id` bigint(20) NOT NULL,
                    `update_date` datetime NOT NULL,
                    `mrr` double NOT NULL,
                    `arr` double NOT NULL,
                     PRIMARY KEY ( `subscription_id`,`update_date`)
                    ) $charset_collate;";

			if ( ! function_exists( 'dbDelta' ) ) {
				include_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}
			dbDelta( $sql );
		}

		/**
		 * Do update to version 2.0.0
		 *
		 * @since 3.0.0
		 * @return void
		 */
		protected static function update_200() {
			// porting changing subscription status.
			$old_enable_overdue                = get_option( 'ywsbs_enable_overdue_period', 'no' );
			$old_ywsbs_overdue_start_period    = get_option( 'ywsbs_overdue_start_period', 0 );
			$old_ywsbs_overdue_period          = get_option( 'ywsbs_overdue_period', 0 );
			$old_enable_suspend                = get_option( 'ywsbs_enable_suspension_period', 'no' );
			$old_ywsbs_suspension_start_period = get_option( 'ywsbs_suspension_start_period', 'no' );
			$old_ywsbs_suspension_period       = get_option( 'ywsbs_suspension_period', 0 );
			$old_ywsbs_cancel_start_period     = get_option( 'ywsbs_cancel_start_period', 48 );

			$ywsbs_change_status_after_renew_order_creation        = array();
			$ywsbs_change_status_after_renew_order_creation_step_2 = array(
				'status'   => 'cancelled',
				'wait_for' => 48,
				'length'   => 0,
			);

			if ( 'yes' === $old_enable_overdue ) {
				$ywsbs_change_status_after_renew_order_creation['status']   = 'overdue';
				$ywsbs_change_status_after_renew_order_creation['wait_for'] = $old_ywsbs_overdue_start_period;
				$ywsbs_change_status_after_renew_order_creation['length']   = $old_ywsbs_overdue_period;
			} elseif ( 'yes' === $old_enable_suspend ) {
				$ywsbs_change_status_after_renew_order_creation['status']   = 'suspended';
				$ywsbs_change_status_after_renew_order_creation['wait_for'] = $old_ywsbs_suspension_start_period;
				$ywsbs_change_status_after_renew_order_creation['length']   = $old_ywsbs_suspension_period;
			} else {
				$ywsbs_change_status_after_renew_order_creation['status']   = 'cancelled';
				$ywsbs_change_status_after_renew_order_creation['wait_for'] = $old_ywsbs_cancel_start_period;
				$ywsbs_change_status_after_renew_order_creation['length']   = 20;
			}

			if ( 'overdue' === $ywsbs_change_status_after_renew_order_creation['status'] && 'yes' === $old_enable_suspend ) {
				$ywsbs_change_status_after_renew_order_creation_step_2['status']   = 'suspended';
				$ywsbs_change_status_after_renew_order_creation_step_2['wait_for'] = 0;
				$ywsbs_change_status_after_renew_order_creation_step_2['length']   = $old_ywsbs_suspension_period;
			}

			update_option( 'ywsbs_change_status_after_renew_order_creation', $ywsbs_change_status_after_renew_order_creation );
			update_option( 'ywsbs_change_status_after_renew_order_creation_step_2', $ywsbs_change_status_after_renew_order_creation_step_2 );

			// Delete old and unused options.
			delete_option( 'ywsbs_enabled' );
			delete_option( 'ywsbs_enable_overdue_period' );
			delete_option( 'ywsbs_overdue_start_period' );
			delete_option( 'ywsbs_overdue_period' );
			delete_option( 'ywsbs_enable_suspension_period' );
			delete_option( 'ywsbs_suspension_start_period' );
			delete_option( 'ywsbs_suspension_period' );
			delete_option( 'ywsbs_cancel_start_period' );
		}

		/**
		 * Do update to version 3.0.0
		 *
		 * @since 3.0.0
		 * @return void
		 */
		protected static function update_300() {
			delete_option( 'ywsbs_queue_flush_rewrite_rules' );
			delete_option( 'yith_ywsbs_db_version' );
			delete_option( 'ywsbs_update_2_0' );

			$modules_options = array(
				'synchronization'    => 'ywsbs_enable_sync',
				'delivery-schedules' => 'ywsbs_enable_delivery',
			);

			foreach ( $modules_options as $module => $option ) {
				// Switch to module options.
				$enabled = get_option( $option, 'no' );
				if ( 'no' !== $enabled ) {
					YWSBS_Subscription_Modules::activate( $module );
				} else {
					delete_option( $option );
				}
			}
		}
	}
}
