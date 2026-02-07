<?php

defined( 'ABSPATH' ) || exit;

/**
 * Installation related functions and actions.
 * 
 * @class SUMOSubs_Install
 * @package Class
 */
class SUMOSubs_Install {

	/**
	 * Init Install.
	 */
	public static function init() {
		add_action( 'init', __CLASS__ . '::check_version', 9 );
		add_filter( 'plugin_action_links_' . SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME, __CLASS__ . '::plugin_action_links' );
		add_filter( 'plugin_row_meta', __CLASS__ . '::plugin_row_meta', 10, 2 );
	}

	/**
	 * Check SUMO Subscriptions version and run updater
	 */
	public static function check_version() {
		if ( get_option( 'sumosubscriptions_version' ) !== SUMO_SUBSCRIPTIONS_VERSION ) {
			self::install();

			/**
			 * Trigger after plugin is updated.
			 * 
			 * @since 1.0
			 */
			do_action( 'sumosubscriptions_updated' );
		}
	}

	/**
	 * Install SUMO Subscriptions.
	 */
	public static function install() {
		if ( ! defined( 'SUMO_SUBSCRIPTIONS_INSTALLING' ) ) {
			define( 'SUMO_SUBSCRIPTIONS_INSTALLING', true );
		}

		self::maybe_add_subscription_upgrade_notice();
		self::create_options();
		self::update_subscription_version();

		/**
		 * Trigger after plugin is installed.
		 * 
		 * @since 1.0
		 */
		do_action( 'sumosubscriptions_installed' );
	}

	/**
	 * Is this a brand new SUMO Subscriptions install?
	 * A brand new install has no version yet.
	 *
	 * @return bool
	 */
	private static function is_new_install() {
		return is_null( get_option( 'sumosubscriptions_version', null ) );
	}

	/**
	 * Maybe add notice when SUMO Subscriptions is upgraded.
	 */
	public static function maybe_add_subscription_upgrade_notice() {
		if ( self::is_new_install() ) {
			return;
		}

		$db_version = get_option( 'sumosubscriptions_version' );
		if ( version_compare( $db_version, 14.0, '<' ) && version_compare( $db_version, SUMO_SUBSCRIPTIONS_VERSION, '<' ) ) {
			$notice_html = '<div style="margin-bottom:50px;color:#d63638;">';
			$notice_html .= '<span class="dashicons dashicons-warning" style="font-size:40px;float: left;"></span>';
			$notice_html .= '<span style="font-size:20px;float:left;margin:10px 0px 0px 30px;">SUMO Subscriptions Alert</span>';
			$notice_html .= '</div>';
			$notice_html .= '<ol style="clear:both;">';
			$notice_html .= '<li>This version<b>(V14.0)</b> of SUMO Subscriptions plugin is not compatible with previous versions of the plugin<b>(V13.8 and below)</b>. So, it is highly recommended to not downgrade the plugin.</li>';
			$notice_html .= '<li>WooCommerce default coupon types are not supported anymore for giving discount for renewal orders. Instead, we recommend you to create subscription coupon types(Recurring fee discount, Recurring fee % discount).<p><b>Note:</b> Old subscriptions which were placed with coupon discount applied for the renewal orders will continue to renew with discount applied.</p></li>';
			$notice_html .= '</ol>';
			SUMOSubs_Admin_Notices::add_notice( 'subscription_upgrade_notice', $notice_html );
		}
	}

	/**
	 * Update SUMO Subscriptions version to current.
	 */
	private static function update_subscription_version() {
		delete_option( 'sumosubscriptions_version' );
		add_option( 'sumosubscriptions_version', SUMO_SUBSCRIPTIONS_VERSION );
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {
		// Include settings so that we can run through defaults.
		include_once __DIR__ . '/admin/class-sumosubs-admin.php';
		SUMOSubs_Admin::save_default_options();
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param   mixed $links Plugin Action links
	 * @return  array
	 */
	public static function plugin_action_links( $links ) {
		$setting_page_link = '<a  href="' . admin_url( 'admin.php?page=sumosubs-settings' ) . '">' . esc_html__( 'Settings', 'sumosubscriptions' ) . '</a>';
		array_unshift( $links, $setting_page_link );
		return $links;
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param   mixed $links Plugin Row Meta
	 * @param   mixed $file  Plugin Base file
	 * @return  array
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME == $file ) {
			$row_meta = array(
				'support' => '<a href="' . esc_url( 'http://fantasticplugins.com/support/' ) . '" aria-label="' . esc_attr__( 'Support', 'sumosubscriptions' ) . '">' . esc_html__( 'Support', 'sumosubscriptions' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return ( array ) $links;
	}
}

SUMOSubs_Install::init();
