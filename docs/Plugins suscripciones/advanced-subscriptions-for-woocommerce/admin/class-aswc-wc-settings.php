<?php
/**
 * WooCommerce settings integration.
 *
 * @package Advanced_Subscriptions_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
		exit;
}

if ( ! class_exists( 'ASWC_WC_Settings' ) ) {
		/**
		 * Handles WooCommerce settings integration.
		 */
	class ASWC_WC_Settings {
			/**
			 * Initialize hooks for settings page.
			 *
			 * @return void
			 */
		public static function init() {
				add_filter( 'woocommerce_settings_tabs_array', array( __CLASS__, 'add_settings_tab' ), 50 );
				add_action( 'woocommerce_settings_tabs_aswc_subscriptions', array( __CLASS__, 'settings_tab' ) );
				add_action( 'woocommerce_settings_save_aswc_subscriptions', array( __CLASS__, 'save_settings' ) );
				add_action( 'woocommerce_sections_aswc_subscriptions', array( __CLASS__, 'output_sections' ) );
		}

			/**
			 * Add plugin settings tab to WooCommerce.
			 *
			 * @param array $tabs Existing tabs.
			 *
			 * @return array
			 */
		public static function add_settings_tab( $tabs ) {
				$tabs['aswc_subscriptions'] = __( 'Advanced Subscriptions', 'advanced-subscriptions-for-woocommerce' );
				return $tabs;
		}

			/**
			 * Output settings sections navigation.
			 *
			 * @return void
			 */
		public static function output_sections() {
				global $current_section;
				$sections = self::get_sections();
			if ( empty( $sections ) ) {
					return;
			}
				echo '<ul class="subsubsub">';
				$array_keys = array_keys( $sections );
			foreach ( $array_keys as $id ) {
				echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=aswc_subscriptions&section=' . sanitize_title( $id ) ) ) . '" class="' . ( $current_section === $id ? 'current' : '' ) . '">' . esc_html( $sections[ $id ] ) . '</a> ' . ( end( $array_keys ) === $id ? '' : '|' ) . ' </li>';
			}
				echo '</ul><br class="clear" />';
		}

			/**
			 * Render settings for the current section.
			 *
			 * @return void
			 */
		public static function settings_tab() {
				global $current_section;
				woocommerce_admin_fields( self::get_settings( $current_section ) );
		}

			/**
			 * Save settings for the current section.
			 *
			 * @return void
			 */
		public static function save_settings() {
				global $current_section;
				woocommerce_update_options( self::get_settings( $current_section ) );
		}

			/**
			 * Get available settings sections.
			 *
			 * @return array
			 */
		public static function get_sections() {
				return array(
					''                 => __( 'General Settings', 'advanced-subscriptions-for-woocommerce' ),
					'subscription_box' => __( 'Multi-Product Subscription', 'advanced-subscriptions-for-woocommerce' ),
					'advanced'         => __( 'Advanced Settings', 'advanced-subscriptions-for-woocommerce' ),
					'license'          => __( 'License', 'advanced-subscriptions-for-woocommerce' ),
				);
		}

			/**
			 * Retrieve settings for the provided section.
			 *
			 * @param string $section Section ID.
			 *
			 * @return array
			 */
		public static function get_settings( $section = '' ) {
			switch ( $section ) {
				case 'subscription_box':
					$settings = array(
						array(
							'title' => __( 'Multi-Product Subscription Settings', 'advanced-subscriptions-for-woocommerce' ),
							'type'  => 'title',
							'id'    => 'jc_subscription_box_options',
						),
						array(
							'title'   => __( 'Enable Multi-Product Subscription Feature', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Enable this to Create and Sell Multi-Product Subscription', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enable_subscription_box_features',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enable_subscription_box_features', 'no' ),
						),
						array(
							'title'   => __( 'Add to cart text For Multi-Product Subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Text displayed on the Add to cart button for multi-product subscription products.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_subscription_box_add_to_cart_text',
							'type'    => 'text',
							'default' => get_option( 'aswc_subscription_box_add_to_cart_text', '' ),
						),
						array(
							'title'   => __( 'Place order text For Multi-Product Subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Text displayed on the Place order button for multi-product subscription checkout.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_subscription_box_place_order_button_text',
							'type'    => 'text',
							'default' => get_option( 'aswc_subscription_box_place_order_button_text', '' ),
						),
						array(
							'type' => 'sectionend',
							'id'   => 'jc_subscription_box_options',
						),
					);
					break;
				case 'advanced':
					$settings = array(
						array(
							'title' => __( 'Advanced Settings', 'advanced-subscriptions-for-woocommerce' ),
							'type'  => 'title',
							'id'    => 'jc_advanced_options',
						),
						array(
							'title'   => __( 'Allow customer to select subscription expiry date', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Let customers choose an expiry date for their subscription.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_allow_subscription_expiry_customer',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_allow_subscription_expiry_customer', 'no' ),
						),
						array(
							'title'   => __( 'Enable automatic retry subscription on failed attempts', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Automatically retry failed renewal payments.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enable_automatic_retry_failed_attempts',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enable_automatic_retry_failed_attempts', 'no' ),
						),
						array(
							'title'             => __( 'Enter the number after certain failed attempts subscription will be canceled', 'advanced-subscriptions-for-woocommerce' ),
							'desc'              => __( 'Cancel the subscription after this many consecutive failed payments.', 'advanced-subscriptions-for-woocommerce' ),
							'id'                => 'aswc_after_no_failed_attempt_cancel',
							'type'              => 'number',
							'custom_attributes' => array( 'min' => 1 ),
							'default'           => get_option( 'aswc_after_no_failed_attempt_cancel', '3' ),
						),
						array(
							'title'   => __( 'Ability to pause the subscription for a certain time by customer', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Allow customers to temporarily pause their subscription.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enable_pause_susbcription_by_customer',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enable_pause_susbcription_by_customer', 'no' ),
						),
						array(
							'title'   => __( 'Ability to start paused subscription by customer', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Allow customers to resume a paused subscription themselves.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_start_pause_susbcription_by_customer',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_start_pause_susbcription_by_customer', 'no' ),
						),
						array(
							'title'   => __( 'Ability to accept manual payment for subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Enable manual payments for subscriptions.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enbale_accept_manual_payment',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enbale_accept_manual_payment', 'no' ),
						),
						array(
							'title'   => __( 'Ability to send subscription is going to expire email notification', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Send an email before the subscription expires.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enable_plan_going_to_expire',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enable_plan_going_to_expire', 'no' ),
						),
						array(
							'title'             => __( 'Enter the number of days before subscription expire email send', 'advanced-subscriptions-for-woocommerce' ),
							'desc'              => __( 'Number of days in advance to send the expiration email.', 'advanced-subscriptions-for-woocommerce' ),
							'id'                => 'aswc_plan_going_to_expire_before_days',
							'type'              => 'number',
							'custom_attributes' => array( 'min' => 1 ),
							'default'           => get_option( 'aswc_plan_going_to_expire_before_days', '7' ),
						),
						array(
							'title'   => __( 'Ability to upgrade/downgrade variable subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Allow customers to upgrade or downgrade variable subscription products.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enbale_downgrade_upgrade_subscription',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enbale_downgrade_upgrade_subscription', 'no' ),
						),
						array(
							'title'   => __( 'Allow only for same interval in upgrade/downgrade', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Restrict upgrades or downgrades to the same billing interval.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enable_allow_same_interval',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enable_allow_same_interval', 'no' ),
						),
						array(
							'title'   => __( 'Stop Downgrade variable subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Prevent customers from downgrading variable subscriptions.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_downgrade_variable_subscription',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_downgrade_variable_subscription', 'no' ),
						),
						array(
							'title'   => __( 'Upgrade and Downgrade button text', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Custom label for the upgrade and downgrade action button.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_upgrade_downgrade_btn_text',
							'type'    => 'text',
							'default' => get_option( 'aswc_upgrade_downgrade_btn_text', 'Upgrade and Downgrade' ),
						),
						array(
							'title'   => __( 'Ability to accept prorate signup fee upgrade/downgrade variable subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Prorate the signup fee when upgrading or downgrading.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enable_signup_fee_downgrade_upgrade_subscription',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enable_signup_fee_downgrade_upgrade_subscription', 'no' ),
						),
						array(
							'title'   => __( 'Ability to accept prorate price on upgrade/downgrade variable subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Prorate the remaining subscription price during upgrades or downgrades.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enable_prorate_on_price_downgrade_upgrade_subscription',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enable_prorate_on_price_downgrade_upgrade_subscription', 'no' ),
						),
						array(
							'title'   => __( 'Manage prorate amount during upgrade/downgrade subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Choose how prorated amounts are handled when a subscription changes.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_manage_prorate_amount',
							'type'    => 'select',
							'default' => get_option( 'aswc_manage_prorate_amount', 'aswc_manage_prorate_next_payment_date' ),
							'options' => array(
								'aswc_manage_prorate_next_payment_date' => __( 'Extend next payment date', 'advanced-subscriptions-for-woocommerce' ),
								'aswc_manage_prorate_using_wallet' => __( 'Put left amount in the user wallet', 'advanced-subscriptions-for-woocommerce' ),
							),
						),
						array(
							'title'   => __( 'Ability to take renewal subscription payment from the certain date', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Start taking renewal payments from a specific day of the month.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_start_susbcription_from_certain_date_of_month',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_start_susbcription_from_certain_date_of_month', 'no' ),
						),
						array(
							'title'   => __( 'Prorate amount for certain date of month subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Define how prorate charges are applied when syncing to a date.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_prorate_price_on_sync',
							'type'    => 'select',
							'default' => get_option( 'aswc_prorate_price_on_sync', 'aswc_prorate_no' ),
							'options' => array(
								'aswc_prorate_no'     => __( 'Do not charge prorate amount', 'advanced-subscriptions-for-woocommerce' ),
								'aswc_prorate_simple' => __( 'Charge prorate amount for subscription', 'advanced-subscriptions-for-woocommerce' ),
								'aswc_prorate_if_free_trial' => __( 'Charge prorate amount for subscription even free trial', 'advanced-subscriptions-for-woocommerce' ),
							),
						),
						array(
							'title'   => __( 'Ability to allow the customer to add multiple subscriptions in cart', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Permit customers to purchase multiple subscriptions at once.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_allow_to_add_multiple_subscription_cart',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_allow_to_add_multiple_subscription_cart', 'no' ),
						),
						array(
							'title'   => __( 'Allow multiple quantities on subscription products.', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Allow customers to purchase multiple quantities of a subscription.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_allow_multiple_quantity_subscription',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_allow_multiple_quantity_subscription', 'no' ),
						),
						array(
							'title'   => __( 'Allow start date on subscription products.', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Let customers select a start date for their subscription.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_allow_start_date_subscription',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_allow_start_date_subscription', 'no' ),
						),
						array(
							'title'             => __( 'Enter the number of days before which you want to send the recurring payment reminder.', 'advanced-subscriptions-for-woocommerce' ),
							'desc'              => __( 'Number of days before renewal to send a reminder email.', 'advanced-subscriptions-for-woocommerce' ),
							'id'                => 'aswc_send_before_recurring_reminder',
							'type'              => 'number',
							'custom_attributes' => array( 'min' => 1 ),
							'default'           => get_option( 'aswc_send_before_recurring_reminder', '5' ),
						),
						array(
							'title'   => __( 'Allow the Time duration for the Subscription cancellation', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Enable a grace period before customers can cancel.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_allow_time_subscription_cancellation',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_allow_time_subscription_cancellation', 'no' ),
						),
						array(
							'title'             => __( 'Enter the number of days after that, user should be able to cancel their subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'              => __( 'Number of days after purchase before cancellation is allowed.', 'advanced-subscriptions-for-woocommerce' ),
							'id'                => 'aswc_time_duration_subscription_cancellation',
							'type'              => 'number',
							'custom_attributes' => array( 'min' => 1 ),
							'default'           => get_option( 'aswc_time_duration_subscription_cancellation', '' ),
						),
						array(
							'type' => 'sectionend',
							'id'   => 'jc_advanced_options',
						),
					);
					break;
				case 'license':
					$license_status_html = '';
					if ( class_exists( 'ASWC_License' ) ) {
						$status = ASWC_License::get_license_status();
						if ( 'valid' === $status ) {
							$license_status_html = '<br><strong>' . esc_html__( 'Status:', 'advanced-subscriptions-for-woocommerce' ) . '</strong> <span style="color: green;">' . esc_html__( 'Active', 'advanced-subscriptions-for-woocommerce' ) . ' &#10003;</span>';
						} elseif ( ! empty( $status ) ) {
							$license_status_html = '<br><strong>' . esc_html__( 'Status:', 'advanced-subscriptions-for-woocommerce' ) . '</strong> <span style="color: red;">' . esc_html__( 'Invalid', 'advanced-subscriptions-for-woocommerce' ) . '</span>';
						} else {
							$license_status_html = '<br><strong>' . esc_html__( 'Status:', 'advanced-subscriptions-for-woocommerce' ) . '</strong> <span style="color: orange;">' . esc_html__( 'Not activated', 'advanced-subscriptions-for-woocommerce' ) . '</span>';
						}
					}
					$settings = array(
						array(
							'title' => __( 'License Settings', 'advanced-subscriptions-for-woocommerce' ),
							'type'  => 'title',
							'desc'  => __( 'Enter your license key to enable automatic updates and support.', 'advanced-subscriptions-for-woocommerce' ),
							'id'    => 'aswc_license_options',
						),
						array(
							'title'       => __( 'License Key', 'advanced-subscriptions-for-woocommerce' ),
							'desc'        => __( 'Enter your license key from your purchase confirmation email.', 'advanced-subscriptions-for-woocommerce' ) . $license_status_html,
							'id'          => 'aswc_lic_license_key',
							'type'        => 'text',
							'default'     => '',
							'placeholder' => __( 'Enter your license key', 'advanced-subscriptions-for-woocommerce' ),
							'css'         => 'min-width: 400px;',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'aswc_license_options',
						),
					);
					break;
				default:
					$settings = array(
						array(
							'title' => __( 'General Settings', 'advanced-subscriptions-for-woocommerce' ),
							'type'  => 'title',
							'id'    => 'jc_general_options',
						),
						array(
							'title'   => __( 'Add to cart text', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Text displayed on the Add to cart button for subscription products.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_add_to_cart_text',
							'type'    => 'text',
							'default' => get_option( 'aswc_add_to_cart_text', '' ),
						),
						array(
							'title'   => __( 'Place order text', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Text displayed on the Place order button during checkout.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_place_order_button_text',
							'type'    => 'text',
							'default' => get_option( 'aswc_place_order_button_text', '' ),
						),
						array(
							'title'   => __( 'Allow Customer to cancel Subscription', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Allow customers to cancel their subscriptions from their account.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_cancel_subscription_for_customer',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_cancel_subscription_for_customer', 'no' ),
						),
						array(
							'title'   => __( 'Enable Log', 'advanced-subscriptions-for-woocommerce' ),
							'desc'    => __( 'Enable logging of subscription actions for debugging purposes.', 'advanced-subscriptions-for-woocommerce' ),
							'id'      => 'aswc_enable_subscription_log',
							'type'    => 'checkbox',
							'default' => get_option( 'aswc_enable_subscription_log', 'no' ),
						),
						array(
							'type' => 'sectionend',
							'id'   => 'jc_general_options',
						),
					);
					break;
			}
				return $settings;
		}
	}

	add_action( 'init', array( 'ASWC_WC_Settings', 'init' ) );
}
