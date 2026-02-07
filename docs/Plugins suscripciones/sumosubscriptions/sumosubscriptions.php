<?php

/**
 * Plugin Name: SUMO Subscriptions
 * Description: SUMO Subscriptions is a WooCommerce Subscription System.
 * Version: 15.7.0
 * Author: Fantastic Plugins
 * Author URI: http://fantasticplugins.com
 * Requires Plugins: woocommerce
 * WC requires at least: 3.0.0
 * WC tested up to: 9.2.3
 * Copyright: © 2024 FantasticPlugins
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: sumosubscriptions
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Initiate Plugin Core class.
 * 
 * @class SUMOSubscriptions
 * @package Class
 */
final class SUMOSubscriptions {

    /**
     * SUMO Subscriptions version.
     * 
     * @var string 
     */
    public $version = '15.7.0';

    /**
     * Get Query instance.
     *
     * @var SUMOSubs_Query object 
     */
    public $query;

    /**
     * The single instance of the class.
     */
    protected static $instance = null;

    /**
     * SUMOSubscriptions constructor.
     */
    public function __construct() {
        $this->init_plugin_dependencies();

        if ( true !== $this->plugin_dependencies_met() ) {
            return; // Return to stop the existing function to be call 
        }

        $this->define_constants();
        $this->include_files();
        $this->init_hooks();
    }

    /**
     * Cloning is forbidden.
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'sumosubscriptions' ), '1.0' );
    }

    /**
     * Unserializing instances of this class is forbidden.
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'sumosubscriptions' ), '1.0' );
    }

    /**
     * Main SUMOSubscriptions Instance.
     * Ensures only one instance of SUMOSubscriptions is loaded or can be loaded.
     * 
     * @return SUMOSubscriptions - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Init plugin dependencies.
     */
    private function init_plugin_dependencies() {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        add_action( 'init', array( $this, 'prevent_header_sent_problem' ), 1 );
        add_action( 'admin_notices', array( $this, 'plugin_dependencies_notice' ) );
    }

    /**
     * Prevent header problem while plugin activates.
     */
    public function prevent_header_sent_problem() {
        ob_start();
    }

    /**
     * Check whether the plugin dependencies met.
     * 
     * @return bool|string True on Success
     */
    private function plugin_dependencies_met( $return_dep_notice = false ) {
        $return = false;

        if ( is_multisite() && is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) && is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            $is_wc_active = true;
        } else if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            $is_wc_active = true;
        } else {
            $is_wc_active = false;
        }

        // WC check.
        if ( ! $is_wc_active ) {
            if ( $return_dep_notice ) {
                $return = 'SUMO Subscriptions Plugin requires WooCommerce Plugin should be Active !!!';
            }

            return $return;
        }

        return true;
    }

    /**
     * Output a admin notice when plugin dependencies not met.
     */
    public function plugin_dependencies_notice() {
        $return = $this->plugin_dependencies_met( true );

        if ( true !== $return && current_user_can( 'activate_plugins' ) ) {
            $dependency_notice = $return;
            printf( '<div class="error"><p>%s</p></div>', wp_kses_post( $dependency_notice ) );
        }
    }

    /**
     * Define constants.
     */
    private function define_constants() {
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_FILE', __FILE__ );
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME', plugin_basename( SUMO_SUBSCRIPTIONS_PLUGIN_FILE ) );
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR', trailingslashit( dirname( SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME ) ) );
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_DIR', plugin_dir_path( SUMO_SUBSCRIPTIONS_PLUGIN_FILE ) );
        $this->define( 'SUMO_SUBSCRIPTIONS_PLUGIN_URL', untrailingslashit( plugins_url( '/', SUMO_SUBSCRIPTIONS_PLUGIN_FILE ) ) );
        $this->define( 'SUMO_SUBSCRIPTIONS_TEMPLATE_PATH', SUMO_SUBSCRIPTIONS_PLUGIN_DIR . 'templates/' );
        $this->define( 'SUMO_SUBSCRIPTIONS_VERSION', $this->version );
        $this->define( 'SUMO_SUBSCRIPTIONS_CRON_INTERVAL', 300 ); //in seconds
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * Is frontend request ?
     *
     * @return bool
     */
    private function is_frontend() {
        return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    private function include_files() {
        /**
         * Abstract classes.
         */
        include_once 'includes/abstracts/abstract-sumosubs-admin-settings.php';
        include_once 'includes/abstracts/abstract-sumosubs-cron-event.php';

        include_once 'includes/class-sumosubs-subscription-factory.php';
        include_once 'includes/class-sumosubs-subscription.php';
        include_once 'includes/class-sumosubs-product.php';
        include_once 'includes/class-sumosubs-cron-event.php';

        /**
         * Core functions.
         */
        include_once 'includes/sumosubs-core-functions.php';
        include_once 'includes/admin/class-sumosubs-admin-notices.php';
        include_once 'includes/admin/class-sumosubs-admin-options.php';

        /**
         * Init Query
         */
        $this->query = include_once 'includes/class-sumosubs-query.php';

        /**
         * Core classes.
         */
        include_once 'includes/class-sumosubs-post-types.php';
        include_once 'includes/class-sumosubs-install.php';
        include_once 'includes/class-sumosubs-emails.php';
        include_once 'includes/class-sumosubs-ajax.php';
        include_once 'includes/class-sumosubs-enqueues.php';
        include_once 'includes/privacy/class-sumosubs-privacy.php';
        include_once 'includes/class-sumosubs-comments.php';
        include_once 'includes/class-sumosubs-coupons.php';
        include_once 'includes/class-sumosubs-order-subscription.php';
        include_once 'includes/class-sumosubs-synchronization.php';
        include_once 'includes/class-sumosubs-switcher.php';
        include_once 'includes/class-sumosubs-resubscribe.php';
        include_once 'includes/class-sumosubs-preapproval.php';
        include_once 'includes/class-sumosubs-shipping.php';
        include_once 'includes/class-sumosubs-order.php';
        include_once 'includes/class-sumosubs-payment-gateways.php';
        include_once 'includes/class-sumosubs-blocks-compatibility.php';

        if ( is_admin() ) {
            include_once 'includes/admin/class-sumosubs-admin.php';
        }

        if ( $this->is_frontend() ) {
            include_once 'includes/class-sumosubs-restrictions.php';
            include_once 'includes/class-sumosubs-frontend.php';
        }
    }

    /**
     * Hook into actions and filters.
     */
    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'upon_activation' ) );
        register_deactivation_hook( __FILE__, array( $this, 'upon_deactivation' ) );
        add_action( 'before_woocommerce_init', array( $this, 'add_HPOS_support' ) );
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), 5 );
        add_action( 'init', array( $this, 'init' ), 5 );
        add_action( 'init', array( $this, 'load_rest_api' ) );
        add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ), 9999 );
    }

    /**
     * Fire upon activating SUMO Subscriptions
     */
    public function upon_activation() {
        SUMOSubs_Install::install();
    }

    /**
     * Fire upon deactivating SUMO Subscriptions
     */
    public function upon_deactivation() {
        update_option( 'sumosubs_flush_rewrite_rules', 1 );
        wp_clear_scheduled_hook( 'sumosubscriptions_background_updater' );
    }

    /**
     * Add HPOS support.
     */
    public function add_HPOS_support() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }

    /**
     * When WP has loaded all plugins, trigger the `sumosubscriptions_loaded` hook.
     */
    public function on_plugins_loaded() {
        $this->load_plugin_textdomain();
        $this->other_plugin_support_includes();

        /**
         * Trigger after plugin is loaded.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_loaded' );
    }

    /**
     * Load Localization files.
     */
    public function load_plugin_textdomain() {
        if ( function_exists( 'determine_locale' ) ) {
            $locale = determine_locale();
        } else {
            $locale = is_admin() ? get_user_locale() : get_locale();
        }

        /**
         * Get the plugin locale.
         * 
         * @since 1.0
         */
        $locale = apply_filters( 'plugin_locale', $locale, 'sumosubscriptions' );

        unload_textdomain( 'sumosubscriptions' );
        load_textdomain( 'sumosubscriptions', WP_LANG_DIR . '/sumosubscriptions/sumosubscriptions-' . $locale . '.mo' );
        load_plugin_textdomain( 'sumosubscriptions', false, SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR . 'languages' );
    }

    /**
     * Init SUMO Subscriptions when WordPress Initializes. 
     */
    public function init() {
        include_once 'includes/background-process/class-sumosubs-background-process.php';
        include_once 'includes/compatibilities/sumosubs-compatibility-functions.php';
        include_once 'includes/deprecated/sumosubs-deprecated-functions.php';

        /**
         * Init plugin.
         * 
         * @since 1.0
         */
        do_action( 'sumosubscriptions_init' );
    }

    /**
     * Load REST API.
     */
    public function load_rest_api() {
        include_once 'includes/rest-api/class-sumosubs-rest-server.php';
        SUMOSubs_REST_Server::init();
    }

    /**
     * Include classes for plugin support.
     */
    private function other_plugin_support_includes() {
        if ( class_exists( 'TM_Extra_Product_Options' ) || class_exists( 'THEMECOMPLETE_Extra_Product_Options' ) ) {
            include_once 'includes/compatibilities/class-sumosubs-wc-tm-extra-product-options.php';
        }
    }

    /**
     * Schedule cron healthcheck
     *
     * @param mixed $schedules Schedules.
     * @return mixed
     */
    public function schedule_cron_healthcheck( $schedules ) {
        $schedules[ 'sumosubscriptions_cron_interval' ] = array(
            'interval' => SUMO_SUBSCRIPTIONS_CRON_INTERVAL,
            /* translators: 1: cron interval */
            'display'  => sprintf( __( 'Every %d Minutes', 'sumosubscriptions' ), SUMO_SUBSCRIPTIONS_CRON_INTERVAL / 60 ),
        );

        return $schedules;
    }
}

/**
 * Main instance of SUMOSubscriptions.
 * Returns the main instance of SUMOSubscriptions.
 *
 * @return SUMOSubscriptions
 */
function sumosubscriptions() {
    return SUMOSubscriptions::instance();
}

/**
 * Run SUMO Subscriptions
 */
sumosubscriptions();
