<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle Admin menus and settings.
 * 
 * @class SUMOSubs_Admin
 */
class SUMOSubs_Admin {

	/**
	 * Setting pages.
	 *
	 * @var array
	 */
	private static $settings = array();

	/**
	 * Init SUMOSubs_Admin.
	 */
	public static function init() {
		add_action( 'init', __CLASS__ . '::includes' );
		add_action( 'admin_menu', __CLASS__ . '::add_menus' );
		add_action( 'sumosubscriptions_reset_options', __CLASS__ . '::reset_options' );
		add_filter( 'woocommerce_account_settings', __CLASS__ . '::add_note_to_subscription_order_data_retention_settings' );
		add_filter( 'woocommerce_account_settings', __CLASS__ . '::add_wc_account_settings' );
		add_action( 'admin_notices', __CLASS__ . '::background_updates_notice' );
		add_action( 'admin_init', __CLASS__ . '::dismiss_background_updates_notice' );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public static function includes() {
		include_once 'sumosubs-admin-functions.php';
		include_once 'class-sumosubs-admin-post-types.php';
		include_once 'class-sumosubs-admin-meta-boxes.php';
		include_once 'class-sumosubs-admin-product-settings.php';
		include_once 'class-sumosubs-admin-subscriptions-exporter.php';
	}

	/**
	 * Add admin menu pages.
	 */
	public static function add_menus() {
		add_menu_page( __( 'SUMO Subscriptions', 'sumosubscriptions' ), __( 'SUMO Subscriptions', 'sumosubscriptions' ), 'manage_woocommerce', 'sumosubscriptions', null, 'dashicons-backup', '56.6' );
		add_submenu_page( 'sumosubscriptions', __( 'Settings', 'sumosubscriptions' ), __( 'Settings', 'sumosubscriptions' ), 'manage_woocommerce', 'sumosubs-settings', __CLASS__ . '::output' );
		add_submenu_page( 'sumosubscriptions', __( 'Subscription Export', 'sumosubscriptions' ), __( 'Subscription Export', 'sumosubscriptions' ), 'manage_woocommerce', SUMOSubs_Admin_Subscriptions_Exporter::$exporter_page, 'SUMOSubs_Admin_Subscriptions_Exporter::render_exporter_html_fields' );
	}

	/**
	 * Include the settings page classes.
	 */
	public static function get_settings_pages() {
		if ( empty( self::$settings ) ) {
			self::$settings[] = include 'settings-page/class-sumosubs-admin-settings-general.php' ;
			self::$settings[] = include 'settings-page/class-sumosubs-admin-settings-order-subscription.php' ;
			self::$settings[] = include 'settings-page/class-sumosubs-admin-settings-synchronization.php' ;
			self::$settings[] = include 'settings-page/class-sumosubs-admin-settings-switcher.php' ;
			self::$settings[] = include 'settings-page/class-sumosubs-admin-settings-my-account.php' ;
			self::$settings[] = include 'settings-page/class-sumosubs-admin-settings-advanced.php' ;
			self::$settings[] = include 'settings-page/class-sumosubs-admin-settings-bulk-action.php' ;
			self::$settings[] = include 'settings-page/class-sumosubs-admin-settings-messages.php' ;
			self::$settings[] = include 'settings-page/class-sumosubs-admin-settings-help.php' ;
		}

		return self::$settings;
	}

	/**
	 * Settings page.
	 *
	 * Handles the display of the main subscription settings page in admin.
	 */
	public static function output() {
		global $current_section, $current_tab;

		/**
		 * Plugin settings start.
		 * 
		 * @since 1.0
		 */
		do_action( 'sumosubscriptions_settings_start' );

		$current_tab     = ( empty( $_GET[ 'tab' ] ) ) ? 'general' : urldecode( sanitize_text_field( $_GET[ 'tab' ] ) );
		$current_section = ( empty( $_REQUEST[ 'section' ] ) ) ? '' : urldecode( sanitize_text_field( $_REQUEST[ 'section' ] ) );

		// Include settings pages
		self::get_settings_pages();

		/**
		 * Add plugin default options based on tab requested.
		 * 
		 * @since 1.0
		 */
		do_action( 'sumosubscriptions_add_options_' . $current_tab );

		/**
		 * Add plugin default options.
		 * 
		 * @since 1.0
		 */
		do_action( 'sumosubscriptions_add_options' );

		if ( $current_section ) {
			/**
			 * Add plugin default options based on tab and section requested.
			 * 
			 * @since 1.0
			 */
			do_action( 'sumosubscriptions_add_options_' . $current_tab . '_' . $current_section );
		}

		if ( ! empty( $_POST[ 'save' ] ) ) {
			if ( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST[ '_wpnonce' ] ), 'sumosubscriptions-settings' ) ) {
				die( esc_html__( 'Action failed. Please refresh the page and retry.', 'sumosubscriptions' ) );
			}

			/**
			 * Save plugin options when saved based on tab requested.
			 * 
			 * @param mixed $_POST
			 * @since 1.0
			 */
			do_action( 'sumosubscriptions_update_options_' . $current_tab, $_POST );

			/**
			 * Save plugin options when saved.
			 * 
			 * @param mixed $_POST
			 * @since 1.0
			 */
			do_action( 'sumosubscriptions_update_options', $_POST );

			if ( $current_section ) {
				/**
				 * Save plugin options when saved based on tab and section requested.
				 * 
				 * @param mixed $_POST
				 * @since 1.0
				 */
				do_action( 'sumosubscriptions_update_options_' . $current_tab . '_' . $current_section, $_POST );
			}

			wp_safe_redirect( esc_url_raw( add_query_arg( array( 'saved' => 'true' ) ) ) );
			exit;
		}
		if ( ! empty( $_POST[ 'reset' ] ) || ! empty( $_POST[ 'reset_all' ] ) ) {
			if ( empty( $_REQUEST[ '_wpnonce' ] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST[ '_wpnonce' ] ), 'sumosubscriptions-reset_settings' ) ) {
				die( esc_html__( 'Action failed. Please refresh the page and retry.', 'sumosubscriptions' ) );
			}

			/**
			 * Reset plugin to default options based on tab requested.
			 * 
			 * @param mixed $_POST
			 * @since 1.0
			 */
			do_action( 'sumosubscriptions_reset_options_' . $current_tab, $_POST );

			if ( ! empty( $_POST[ 'reset_all' ] ) ) {
				/**
				 * Reset plugin to default options.
				 * 
				 * @param mixed $_POST
				 * @since 1.0
				 */
				do_action( 'sumosubscriptions_reset_options', $_POST );
			}

			if ( $current_section ) {
				/**
				 * Reset plugin to default options based on tab and section requested.
				 * 
				 * @param mixed $_POST
				 * @since 1.0
				 */
				do_action( 'sumosubscriptions_reset_options_' . $current_tab . '_' . $current_section, $_POST );
			}

			wp_safe_redirect( esc_url_raw( add_query_arg( array( 'saved' => 'true' ) ) ) );
			exit;
		}
		// Get any returned messages
		$error   = ( empty( $_GET[ 'wc_error' ] ) ) ? '' : urldecode( stripslashes( sanitize_title( $_GET[ 'wc_error' ] ) ) );
		$message = ( empty( $_GET[ 'wc_message' ] ) ) ? '' : urldecode( stripslashes( sanitize_title( $_GET[ 'wc_message' ] ) ) );

		if ( $error || $message ) {
			if ( $error ) {
				echo '<div id="message" class="error fade"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
			} else {
				echo '<div id="message" class="updated fade"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
			}
		} elseif ( ! empty( $_GET[ 'saved' ] ) ) {
			echo '<div id="message" class="updated fade"><p><strong>' . esc_html__( 'Your settings have been saved.', 'sumosubscriptions' ) . '</strong></p></div>';
		}

		include 'views/html-admin-settings.php';
	}

	/**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	public static function save_default_options( $reset_all = false ) {
		if ( empty( self::$settings ) ) {
			self::get_settings_pages();
		}

		foreach ( self::$settings as $tab ) {
			if ( ! isset( $tab->settings ) || ! is_array( $tab->settings ) ) {
				continue;
			}

			$tab->add_options( $reset_all );
		}
	}

	/**
	 * Reset All settings
	 */
	public static function reset_options() {
		self::save_default_options( true );
	}

	/**
	 * Add notice to admin when data retention in SUMO Subscription orders
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function add_note_to_subscription_order_data_retention_settings( $settings ) {
		if ( is_array( $settings ) && ! empty( $settings ) ) {
			foreach ( $settings as $pos => $setting ) {
				if (
						isset( $setting[ 'id' ] ) &&
						isset( $setting[ 'type' ] ) &&
						'personal_data_retention' === $setting[ 'id' ] &&
						'title' === $setting[ 'type' ]
				) {
					$settings[ $pos ][ 'desc' ] .= __( '<br><strong>Note:</strong> This settings will not be applicable for SUMO Subscription orders.', 'sumosubscriptions' );
				}
			}
		}

		return $settings;
	}

	/**
	 * Add privacy settings under WooCommerce Privacy
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function add_wc_account_settings( $settings ) {
		$original_settings = $settings;

		if ( is_array( $original_settings ) && ! empty( $original_settings ) ) {
			$new_settings = array();

			foreach ( $original_settings as $pos => $setting ) {
				if ( ! isset( $setting[ 'id' ] ) ) {
					continue;
				}

				switch ( $setting[ 'id' ] ) {
					case 'woocommerce_erasure_request_removes_order_data':
						$new_settings[ $pos + 1 ] = array(
							'title'         => __( 'Account erasure requests', 'sumosubscriptions' ),
							'desc'          => __( 'Remove personal data from SUMO Subscriptions and its related Orders', 'sumosubscriptions' ),
							/* Translators: %s URL to erasure request screen. */
							'desc_tip'      => sprintf( __( 'When handling an <a href="%s">account erasure request</a>, should personal data within SUMO Subscriptions be retained or removed?', 'sumosubscriptions' ), esc_url( admin_url( 'tools.php?page=remove_personal_data' ) ) ),
							'id'            => 'sumo_erasure_request_removes_subscription_data',
							'type'          => 'checkbox',
							'default'       => 'no',
							'checkboxgroup' => '',
							'autoload'      => false,
						);
						break;
					case 'woocommerce_anonymize_completed_orders':
						$new_settings[ $pos + 1 ] = array(
							'title'       => __( 'Retain ended SUMO Subscription Orders', 'sumosubscriptions' ),
							'desc_tip'    => __( 'Retain ended SUMO Subscription Orders for a specified duration before anonymizing the personal data within them.', 'sumosubscriptions' ),
							'id'          => 'sumo_anonymize_ended_subscriptions',
							'type'        => 'relative_date_selector',
							'placeholder' => __( 'N/A', 'sumosubscriptions' ),
							'default'     => array(
								'number' => '',
								'unit'   => 'months',
							),
							'autoload'    => false,
						);
						break;
				}
			}

			if ( ! empty( $new_settings ) ) {
				foreach ( $new_settings as $pos => $new_setting ) {
					array_splice( $settings, $pos, 0, array( $new_setting ) );
				}
			}
		}

		return $settings;
	}

	/**
	 * Print the background updates notice to Admin.
	 */
	public static function background_updates_notice() {
		$background_updates = get_option( 'sumosubs_background_updates', array() );
		if ( empty( $background_updates ) ) {
			return;
		}

		$notice_updates = $background_updates;
		foreach ( $background_updates as $action_key => $action ) {
			if ( ! empty( $action[ 'current_action' ] ) && is_null( WC()->queue()->get_next( $action[ 'current_action' ], null, $action[ 'action_group' ] ) ) ) {
				$notice_updates[ $action_key ][ 'current_action' ] = '';
			}

			if ( ! empty( $action[ 'next_action' ] ) && WC()->queue()->get_next( $action[ 'next_action' ], null, $action[ 'action_group' ] ) ) {
				$notice_updates[ $action_key ][ 'action_status' ]  = 'in_progress';
				$notice_updates[ $action_key ][ 'current_action' ] = $action[ 'next_action' ];
				$notice_updates[ $action_key ][ 'next_action' ]    = '';
			}

			if ( empty( $notice_updates[ $action_key ][ 'current_action' ] ) ) {
				$notice_updates[ $action_key ][ 'action_status' ]  = 'completed';
				$notice_updates[ $action_key ][ 'current_action' ] = '';
				$notice_updates[ $action_key ][ 'next_action' ]    = '';
			}
		}

		update_option( 'sumosubs_background_updates', $notice_updates );

		if ( ! empty( $notice_updates ) ) {
			include_once 'views/html-admin-background-updates-notice.php';
		}
	}

	/**
	 * Redirect to current settings page.
	 */
	public static function dismiss_background_updates_notice() {
		if ( empty( $_GET[ 'sumosubs_action' ] ) || empty( $_GET[ 'sumosubs_nonce' ] ) ) {
			return;
		}

		if ( 'dismiss_background_updates_notice' === $_GET[ 'sumosubs_action' ] && wp_verify_nonce( wc_clean( wp_unslash( $_GET[ 'sumosubs_nonce' ] ) ), 'sumosubs-background-updates' ) ) {
			delete_option( 'sumosubs_background_updates' );
			wp_safe_redirect( esc_url_raw( remove_query_arg( array( 'sumosubs_action', 'sumosubs_nonce' ) ) ) );
			exit;
		}
	}
}

SUMOSubs_Admin::init();
