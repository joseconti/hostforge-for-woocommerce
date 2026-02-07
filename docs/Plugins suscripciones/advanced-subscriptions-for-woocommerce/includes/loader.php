<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://plugins.joseconti.com
 * @since             1.0.0
 * @package           Aswc_Loader
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
		die;
}
use Automattic\WooCommerce\Utilities\OrderUtil;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

		/**
		 * The code that runs during plugin activation.
		 * This action is documented in includes/class-aswc-loaderactivator.php
		 *
		 * @param String $network_wide as network wide.
		 */
	function aswc_loader_activate( $network_wide ) {
		require_once ASWC_INCLUDES_PATH . 'includes/class-aswc-loaderactivator.php';
                Aswc_LoaderActivator::activate( $network_wide );
                $aswc_active_plugin = get_option( 'aswc_all_plugins_active', array() );

                if ( isset( $aswc_active_plugin['woocommerce-subscriptions-pro'] ) ) {
                        $legacy_entry = $aswc_active_plugin['woocommerce-subscriptions-pro'];
                        unset( $aswc_active_plugin['woocommerce-subscriptions-pro'] );
                } else {
                        $legacy_entry = array();
                }

                $aswc_active_plugin['aswc-loader'] = wp_parse_args(
                        array(
                                'plugin_name' => __( 'Advanced Subscriptions For WooCommerce', 'advanced-subscriptions-for-woocommerce' ),
                                'active'      => '1',
                        ),
                        $legacy_entry
                );

                update_option( 'aswc_all_plugins_active', $aswc_active_plugin );
        }

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-aswc-loaderdeactivator.php
	 *
	 * @param String $network_wide as network wide.
	 */
	function aswc_loader_deactivate( $network_wide ) {
		require_once ASWC_INCLUDES_PATH . 'includes/class-aswc-loaderdeactivator.php';
                Aswc_LoaderDeactivator::deactivate( $network_wide );
                $aswc_deactive_plugin = get_option( 'aswc_all_plugins_active', array() );

                if ( isset( $aswc_deactive_plugin['woocommerce-subscriptions-pro'] ) && ! isset( $aswc_deactive_plugin['aswc-loader'] ) ) {
                        $aswc_deactive_plugin['aswc-loader'] = $aswc_deactive_plugin['woocommerce-subscriptions-pro'];
                }

                if ( isset( $aswc_deactive_plugin['aswc-loader'] ) ) {
                        $aswc_deactive_plugin['aswc-loader']['active'] = '0';
                }

                unset( $aswc_deactive_plugin['woocommerce-subscriptions-pro'] );
                update_option( 'aswc_all_plugins_active', $aswc_deactive_plugin );
        }

		register_activation_hook( __FILE__, 'aswc_loader_activate' );
		register_deactivation_hook( __FILE__, 'aswc_loader_deactivate' );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require ASWC_INCLUDES_PATH . 'includes/class-aswc-include.php';


        /**
         * Store the loader instance and expose it via a filter hook.
         *
         * @since 1.0.0
         *
         * @param Aswc_Include $instance Loader bootstrap instance.
         */
        function aswc_loader_set_instance( Aswc_Include $instance ) {
                aswc_loader_instance_store( $instance );

                add_filter( 'aswc_loader_instance', 'aswc_loader_instance_store' );
        }

        /**
         * Retrieve or register the loader instance without relying on globals.
         *
         * @since 1.0.0
         *
         * @param Aswc_Include|null $instance Optional instance to store.
         *
         * @return Aswc_Include|null
         */
        function aswc_loader_instance_store( $instance = null ) {
                static $stored_instance = null;

                if ( null !== $instance ) {
                        $stored_instance = $instance;
                }

                return $stored_instance;
        }
        /**
         * Begins execution of the plugin.
         *
         * Since everything within the plugin is registered via hooks,
         * then kicking off the plugin from this point in the file does
         * not affect the page life cycle.
         *
         * @since    1.0.0
         */
        function aswc_loader_run() {
                $aswc_plugin_standard = new Aswc_Include();
                $aswc_plugin_standard->aswc_run();

                aswc_loader_set_instance( $aswc_plugin_standard );
        }
        aswc_loader_run();



        // Add settings link on plugin page.
                add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'aswc_loader_settings_link' );

	/**
	 * Settings link.
	 *
	 * @since    1.0.0
	 * @param   Array $links    Settings link array.
	 */
	function aswc_loader_settings_link( $links ) {

		$my_link = array(
			'<a href="' . admin_url( 'admin.php?page=aswc_subscriptions_for_woocommerce_menu' ) . '">' . __( 'Settings', 'advanced-subscriptions-for-woocommerce' ) . '</a>',
		);
		return array_merge( $my_link, $links );
	}


	/**
	 * Adding custom setting links at the plugin activation list.
	 *
	 * @param array  $links_array array containing the links to plugin.
	 * @param string $plugin_file_name plugin file name.
	 * @return array
	 */
	// Removed custom plugin row meta links.

        add_action( 'woocommerce_init', 'aswc_loader_initialize_session' );

        /**
         * Ensure the WooCommerce session is ready for loader interactions.
         *
         * @since 1.0.0
         */
        function aswc_loader_initialize_session() {
                if ( ! function_exists( 'WC' ) ) {
                        return;
                }

                $wc = WC();

                if ( ! isset( $wc->session ) ) {
                        return;
                }

                if ( ! $wc->session->has_session() ) {
                        $wc->session->set_customer_session_cookie( true );
                }
        }

	/**
	 *
	 * Update the data into the order table if hpos enabled otherwise default working.
	 *
	 * @param int               $id .
	 * @param string            $key .
	 * @param init|array|object $value .
	 */
	function aswc_update_order_meta( $id, $key, $value ) {
									$normalized_key = ltrim( (string) $key, '_' );
									$meta_key       = aswc_normalize_meta_key( $key );

		if ( 'aswc_subscription_status' === $normalized_key && function_exists( 'aswc_update_meta_data' ) ) {
								aswc_update_meta_data( $id, $key, $value );
								return;
		}

                $hpos_enabled = aswc_loader_is_hpos_enabled();

                if ( $hpos_enabled ) {
                        $order_type = OrderUtil::get_order_type( $id );

                        if ( 'shop_order' === $order_type ) {
                                $order = wc_get_order( $id );
                        } elseif ( 'aswc_subscriptions' === $order_type ) {
                                $order = new ASWC_Subscription( $id );
                        } else {
                                $order = null;
                        }

                        if ( $order ) {
                                $order->update_meta_data( $meta_key, $value );

                                if ( ! doing_action( 'save_post' ) && ! doing_action( 'save_post_' . $order_type ) ) {
                                        $order->save();
                                }
                        } else {
                                update_post_meta( $id, $meta_key, $value );
                        }
                } else {
                        // Traditional CPT-based orders are in use.
                        update_post_meta( $id, $meta_key, $value );
                }

	}

} else {

				// WooCommerce is not active so deactivate this plugin.
				add_action( 'admin_init', 'aswc_activation_failure' );

		/**
		 * Deactivate this plugin.
		 *
		 * @since 1.0.0
		 */
	function aswc_activation_failure() {

			add_action( 'admin_notices', 'aswc_activation_failure_admin_notice' );
			add_action( 'network_admin_notices', 'aswc_activation_failure_admin_notice' );
	}

		/**
		 * Display admin error notice when WooCommerce is not active.
		 *
		 * @since 1.0.0
		 */
	function aswc_activation_failure_admin_notice() {

			// To hide Plugin activated notice.
			unset( $_GET['activate'] );

		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
			?>
						<div class="notice notice-error is-dismissible">
								<p><?php esc_html_e( 'WooCommerce is not activated, Please activate WooCommerce first to activate Advanced Subscriptions For WooCommerce.', 'advanced-subscriptions-for-woocommerce' ); ?></p>
						</div>
					<?php
		}
	}
}
