<?php
/**
 * Fired during plugin activation
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/includes
 */
class Aswc_LoaderActivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @param String $network_wide as network wide.
	 *
	 * @since    1.0.0
	 */
	public static function activate( $network_wide ) {
		global $wpdb;
		if ( is_multisite() && $network_wide ) {
			// Get all blogs in the network and activate plugins on each one.
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );

								restore_current_blog();
			}
		} else {
				// Nothing to do when plugin is activated on single site.
		}
	}
}
