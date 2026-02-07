<?php

defined( 'ABSPATH' ) || exit;

/**
 * Emails class.
 */
class SUMOSubs_Emails {

	/**
	 * Email notification classes
	 *
	 * @var WC_Email[]
	 */
	protected static $emails = array();

	/**
	 * Available email notification classes to load
	 * 
	 * @var WC_Email::$key => WC_Email class
	 */
	protected static $email_classes = array(
		'subscription_new_order'                     => 'SUMOSubs_Subscription_New_Order_Email',
		'subscription_new_order_old_subscribers'     => 'SUMOSubs_Subscription_New_Order_Old_Subscribers_Email',
		'subscription_order_processing'              => 'SUMOSubs_Subscription_Order_Processing_Email',
		'subscription_order_completed'               => 'SUMOSubs_Subscription_Order_Completed_Email',
		'subscription_paused'                        => 'SUMOSubs_Subscription_Paused_Email',
		'subscription_invoice'                       => 'SUMOSubs_Subscription_Invoice_Email',
		'subscription_expiry_reminder'               => 'SUMOSubs_Subscription_Expiry_Reminder_Email',
		'subscription_auto_renewal_reminder'         => 'SUMOSubs_Subscription_Auto_Renewal_Reminder_Email',
		'subscription_auto_renewal_success'          => 'SUMOSubs_Subscription_Auto_Renewal_Success_Email',
		'subscription_overdue_automatic'             => 'SUMOSubs_Subscription_Overdue_Automatic_Email',
		'subscription_overdue_manual'                => 'SUMOSubs_Subscription_Overdue_Manual_Email',
		'subscription_suspended_automatic'           => 'SUMOSubs_Subscription_Suspended_Automatic_Email',
		'subscription_suspended_manual'              => 'SUMOSubs_Subscription_Suspended_Manual_Email',
		'subscription_turnoff_auto_payments_success' => 'SUMOSubs_Subscription_Turnoff_Auto_Payments_Success_Email',
		'subscription_pending_authorization'         => 'SUMOSubs_Subscription_Pending_Authorization_Email',
		'subscription_cancelled'                     => 'SUMOSubs_Subscription_Cancelled_Email',
		'subscription_cancel_request_submitted'      => 'SUMOSubs_Subscription_Cancel_Request_Submitted_Email',
		'subscription_cancel_request_revoked'        => 'SUMOSubs_Subscription_Cancel_Request_Revoked_Email',
		'subscription_expired'                       => 'SUMOSubs_Subscription_Expired_Email',
	);

	/**
	 * Mapped new email classes.
	 * 
	 * @var Legacy WC_Email::$id => New WC_Email::$key
	 */
	protected static $mapped_emails = array(
		'subscription_processing_order'                   => 'subscription_order_processing',
		'subscription_completed_order'                    => 'subscription_order_completed',
		'subscription_pause_order'                        => 'subscription_paused',
		'subscription_invoice_order_manual'               => 'subscription_invoice',
		'subscription_automatic_charging_reminder'        => 'subscription_auto_renewal_reminder',
		'subscription_renewed_order_automatic'            => 'subscription_auto_renewal_success',
		'subscription_overdue_order_automatic'            => 'subscription_overdue_automatic',
		'subscription_overdue_order_manual'               => 'subscription_overdue_manual',
		'subscription_suspended_order_automatic'          => 'subscription_suspended_automatic',
		'subscription_suspended_order_manual'             => 'subscription_suspended_manual',
		'subscription_turnoff_automatic_payments_success' => 'subscription_turnoff_auto_payments_success',
		'turnoff_automatic_payments_success'              => 'subscription_turnoff_auto_payments_success',
		'subscription_cancel_order'                       => 'subscription_cancelled',
		'subscription_cancelled_order'                    => 'subscription_cancelled',
		'subscription_expired_order'                      => 'subscription_expired',
	);

	/**
	 * Init the email class hooks in all emails that can be sent.
	 */
	public static function init() {
		add_filter( 'woocommerce_email_classes', __CLASS__ . '::add_email_classes' );
		add_filter( 'woocommerce_template_directory', __CLASS__ . '::set_template_directory', 10, 2 );
		add_filter( 'woocommerce_template_path', __CLASS__ . '::set_template_path' );
		add_filter( 'woocommerce_email_enabled_new_order', __CLASS__ . '::wc_email_handler', 99, 2 );
		add_filter( 'woocommerce_email_enabled_customer_completed_order', __CLASS__ . '::wc_email_handler', 99, 2 );
		add_filter( 'woocommerce_email_enabled_customer_processing_order', __CLASS__ . '::wc_email_handler', 99, 2 );
	}

	/**
	 * Load our email classes.
	 */
	public static function add_email_classes( $emails ) {
		if ( ! empty( self::$emails ) ) {
			return $emails + self::$emails;
		}

		// Include email classes.
		include_once 'abstracts/abstract-sumosubs-email.php';

		foreach ( self::$email_classes as $key => $class ) {
			$file_name = 'class-' . strtolower( str_replace( '_', '-', $class ) );
			$path      = SUMO_SUBSCRIPTIONS_PLUGIN_DIR . "includes/emails/{$file_name}.php";

			if ( is_readable( $path ) ) {
				self::$emails[ $class ] = include $path ;
			}
		}

		return $emails + self::$emails;
	}

	/**
	 * Check if we need to send WC emails
	 */
	public static function wc_email_handler( $bool, $order ) {
		$order = sumosubs_maybe_get_order_instance( $order );
		if ( ! $order ) {
			return $bool;
		}

		$disabled_wc_order_emails = SUMOSubs_Admin_Options::get_option( 'disabled_wc_order_emails' );
		if ( ! empty( $disabled_wc_order_emails ) && sumo_order_contains_subscription( $order ) ) {
			if ( 'woocommerce_email_enabled_new_order' === current_filter() ) {
				if ( in_array( 'new', $disabled_wc_order_emails ) ) {
					return false;
				}
			} else if ( $order->has_status( $disabled_wc_order_emails ) ) {
				return false;
			}
		}

		return $bool;
	}

	/**
	 * Set our email templates directory.
	 * 
	 * @return string
	 */
	public static function set_template_directory( $template_directory, $template ) {
		$legacy_email_class_keys = array_keys( self::$mapped_emails );
		$email_class_keys        = array_keys( self::$email_classes );
		$email_class_keys        = array_merge( $email_class_keys, $legacy_email_class_keys );
		$templates               = array_map( array( 'SUMOSubs_Emails', 'get_template_name' ), $email_class_keys );

		foreach ( $templates as $name ) {
			if ( in_array( $template, array(
						"emails/{$name}.php",
						"emails/plain/{$name}.php",
					) )
			) {
				return untrailingslashit( SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR );
			}
		}

		return $template_directory;
	}

	/**
	 * Set our template path.
	 *
	 * @return string
	 */
	public static function set_template_path( $path ) {
		if ( isset( $_REQUEST[ '_wpnonce' ] ) && ( isset( $_POST[ 'template_html_code' ] ) || isset( $_POST[ 'template_plain_code' ] ) ) && wp_verify_nonce( wc_clean( wp_unslash( $_REQUEST[ '_wpnonce' ] ) ), 'woocommerce-settings' ) ) {
			if ( isset( $_GET[ 'section' ] ) && in_array( wc_clean( wp_unslash( $_GET[ 'section' ] ) ), array_map( 'strtolower', array_values( self::$email_classes ) ) ) ) {
				$path = SUMO_SUBSCRIPTIONS_PLUGIN_BASENAME_DIR;
			}
		}

		return $path;
	}

	/**
	 * Get the template name from email ID
	 * 
	 * @param WC_Email::$key $key 
	 * @return string
	 */
	public static function get_template_name( $key ) {
		return str_replace( '_', '-', $key );
	}

	/**
	 * Load WC Mailer.
	 */
	public static function load_mailer() {
		WC()->mailer();
	}

	/**
	 * Are emails available
	 *
	 * @return WC_Email class
	 */
	public static function available() {
		self::load_mailer();
		return ! empty( self::$emails ) ? true : false;
	}

	/**
	 * Return the email class
	 *
	 * @param WC_Email::$key $key 
	 * @return WC_Email class
	 */
	public static function get_email_class( $key ) {
		$key = strtolower( $key );

		// Map legacy key to new key.
		if ( isset( self::$mapped_emails[ $key ] ) ) {
			$key = self::$mapped_emails[ $key ];
		}

		return isset( self::$email_classes[ $key ] ) ? self::$email_classes[ $key ] : null;
	}

	/**
	 * Return the emails
	 *
	 * @return WC_Email[]
	 */
	public static function get_emails() {
		self::load_mailer();
		return self::$emails;
	}

	/**
	 * Return the email by the corresponding class key.
	 *
	 * @param WC_Email::$key $key 
	 * @return WC_Email
	 */
	public static function get_email( $key ) {
		self::load_mailer();
		$class = self::get_email_class( $key );
		return isset( self::$emails[ $class ] ) ? self::$emails[ $class ] : null;
	}
}

SUMOSubs_Emails::init();
