<?php

defined( 'ABSPATH' ) || exit;

/**
 * Display notices in admin.
 * 
 * @class SUMOSubs_Admin_Notices
 * @package Class
 */
class SUMOSubs_Admin_Notices {

	/**
	 * Stores notices.
	 *
	 * @var array
	 */
	private static $notices = array();

	/**
	 * Constructor.
	 */
	public static function init() {
		self::$notices = get_option( 'sumosubs_admin_notices', array() );

		add_action( 'admin_notices', __CLASS__ . '::output_notices' );
		add_action( 'admin_init', __CLASS__ . '::hide_notices', 20 );
		add_action( 'shutdown', __CLASS__ . '::store_notices' );
	}

	/**
	 * Store notices to DB
	 */
	public static function store_notices() {
		update_option( 'sumosubs_admin_notices', self::get_notices() );
	}

	/**
	 * Get notices
	 *
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * Remove all notices.
	 */
	public static function remove_all_notices() {
		self::$notices = array();
	}

	/**
	 * Show a notice.
	 *
	 * @param string $name Notice name.
	 * @param string $notice_html Notice HTML.
	 * @param bool   $force_save Force saving inside this method instead of at the 'shutdown'.
	 */
	public static function add_notice( $name, $notice_html, $force_save = false ) {
		self::$notices[ $name ] = $notice_html;

		if ( $force_save ) {
			self::store_notices();
		}
	}

	/**
	 * Remove a notice from being displayed.
	 *
	 * @param string $name Notice name.
	 * @param bool   $force_save Force saving inside this method instead of at the 'shutdown'.
	 */
	public static function remove_notice( $name, $force_save = false ) {
		unset( self::$notices[ $name ] );

		if ( $force_save ) {
			self::store_notices();
		}
	}

	/**
	 * See if a notice is being shown.
	 *
	 * @param string $name Notice name.
	 *
	 * @return boolean
	 */
	public static function has_notice( $name ) {
		return isset( self::$notices[ $name ] );
	}

	/**
	 * Hide a notice if the GET variable is set.
	 */
	public static function hide_notices() {
		if ( isset( $_GET[ 'sumosubs-hide-notice' ], $_GET[ '_sumosubs_notice_nonce' ] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET[ '_sumosubs_notice_nonce' ] ) ), 'sumosubs_hide_notices_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'sumosubscriptions' ) );
			}

			$hide_notice = sanitize_text_field( wp_unslash( $_GET[ 'sumosubs-hide-notice' ] ) );

			self::remove_notice( $hide_notice );
		}
	}

	/**
	 * Output any stored notices.
	 */
	public static function output_notices() {
		$notices = self::get_notices();

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice_name => $notice_html ) {
				echo '<div id="message" class="updated woocommerce-message">';
				echo '<a class="woocommerce-message-close notice-dismiss" href="' . esc_url( wp_nonce_url( add_query_arg( 'sumosubs-hide-notice', $notice_name ), 'sumosubs_hide_notices_nonce', '_sumosubs_notice_nonce' ) ) . '">' . __( 'Dismiss', 'sumosubscriptions' ) . '</a>';
				echo '<p>' . wp_kses_post( wpautop( $notice_html ) ) . '</p>';
				echo '</div>';
			}
		}
	}
}

SUMOSubs_Admin_Notices::init();
