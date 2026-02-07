<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce/includes
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASWC_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

                load_plugin_textdomain(
                        'advanced-subscriptions-for-woocommerce',
                        false,
                        dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
                );
        }
}
