<?php
/**
 * Deprecated functions from past YITH WooCommerce Subscription versions. You shouldn't use these
 * functions and look for the alternatives instead. The functions will be
 * removed in a later version.
 *
 * @package YITH\Subscription
 * @since   2.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

global $yith_ywsbs_db_version;
$yith_ywsbs_db_version = '1.0.0';

if ( ! function_exists( 'yith_ywsbs_db_install' ) ) {

	/**
	 * Install the table.
	 *
	 * @since      1.0.0
	 * @return     void
	 * @deprecated 3.0.0
	 */
	function yith_ywsbs_db_install() {
		_deprecated_function( __FUNCTION__, '3.0.0', 'YITH_WC_Subscription_Install::create_tables' );
		YITH_WC_Subscription_Install::create_tables();
	}
}

if ( ! function_exists( 'yith_ywsbs_update_db_check' ) ) {

	/**
	 * Check if the function yith_ywsbs_db_install must be installed or updated.
	 *
	 * @since      1.0.0
	 * @return     void
	 * @deprecated 3.0.0
	 */
	function yith_ywsbs_update_db_check() {
		_deprecated_function( __FUNCTION__, '3.0.0', 'YITH_WC_Subscription_Install::create_tables' );
		YITH_WC_Subscription_Install::create_tables();
	}
}

if ( ! function_exists( 'yith_check_privacy_enabled' ) ) {
	/**
	 * Check if the tool for export and erase personal data are enabled.
	 *
	 * @since      1.0.0
	 * @param bool $wc Tell if WooCommerce privacy is needed.
	 * @return     bool
	 * @deprecated 2.0.0
	 */
	function yith_check_privacy_enabled( $wc = false ) {
		global $wp_version;
		_deprecated_function( __FUNCTION__, '2.0.0' );
		$enabled = $wc ? version_compare( WC()->version, '3.4.0', '>=' ) && version_compare( $wp_version, '4.9.5', '>' ) : version_compare( $wp_version, '4.9.5', '>' );
		return apply_filters( 'yith_check_privacy_enabled', $enabled, $wc );
	}
}

if ( ! function_exists( 'yith_ywsbs_remove_flush_rewrite_rule_option' ) ) {
	/**
	 * Remove option ywsbs_queue_flush_rewrite_rules
	 *
	 * @deprecated 3.0.0
	 */
	function yith_ywsbs_remove_flush_rewrite_rule_option() {
		_deprecated_function( __FUNCTION__, '3.0.0' );
		delete_option( 'ywsbs_queue_flush_rewrite_rules' );
	}
}

if ( ! function_exists( 'ywsbs_update_2_0' ) ) {
	/**
	 * Update script.
	 *
	 * @deprecated 3.0.0
	 */
	function ywsbs_update_2_0() {
		_deprecated_function( __FUNCTION__, '3.0.0' );
	}
}

if ( ! function_exists( 'yith_ywsbs_check_wc_admin_min_version' ) ) {
	/**
	 * Check min version for WC Admin
	 *
	 * @return boolean
	 * @deprecated 3.2.0
	 */
	function yith_ywsbs_check_wc_admin_min_version() {
		_deprecated_function( __FUNCTION__, '3.2.0' );
		return true;
	}
}
