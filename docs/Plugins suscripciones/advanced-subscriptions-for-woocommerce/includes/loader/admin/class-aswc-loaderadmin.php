<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://plugins.joseconti.com
 * @since 1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/admin
 */

use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/admin
 */
// phpcs:disable PEAR.NamingConventions.ValidClassName.Invalid, Squiz.Commenting.ClassComment.Missing
class Aswc_LoaderAdmin {
	/**
	 * Build the inline HTML for the current subscription retry attempts.
	 *
	 * Usage from the Schedule template (just after the Timezone line):
	 * echo Aswc_LoaderAdmin::aswc_get_retry_attempts_html( (int) $post->ID );
	 *
	 * @param int $subscription_id Subscription (post) ID.
	 * @return string HTML paragraph with the retry info.
	 */
	public static function aswc_get_retry_attempts_html( $subscription_id ) {
		$subscription_id = (int) $subscription_id;
		if ( $subscription_id <= 0 ) {
			return '';
		}

		$attempts = (int) aswc_get_meta_data( $subscription_id, '_aswc_retry_attempts', true );
		if ( $attempts < 0 ) {
			$attempts = 0;
		}
		$max_attempts = (int) get_option( 'aswc_after_no_failed_attempt_cancel', '3' );
		if ( $max_attempts < 1 ) {
			$max_attempts = 1;
		}

		if ( 0 === $attempts ) {
			// No retries yet.
			$detail = __( 'No retries', 'advanced-subscriptions-for-woocommerce' );
		} elseif ( $attempts >= $max_attempts ) {
			// Max reached.
			$detail = sprintf(
				/* translators: 1: attempts done, 2: max attempts */
				__( 'Maximum retries reached (%1$d of %2$d)', 'advanced-subscriptions-for-woocommerce' ),
				$attempts,
				$max_attempts
			);
		} else {
			// In progress.
			$detail = sprintf(
				/* translators: 1: attempts done, 2: max attempts */
				__( '%1$d of %2$d', 'advanced-subscriptions-for-woocommerce' ),
				$attempts,
				$max_attempts
			);
		}

		return '<p class="aswc-retry-status-inline"><strong>' . esc_html__( 'Retries:', 'advanced-subscriptions-for-woocommerce' ) . '</strong> ' . esc_html( $detail ) . '</p>';
	}
// phpcs:enable PEAR.NamingConventions.ValidClassName.Invalid, Squiz.Commenting.ClassComment.Missing

	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 * @param    string $hook      The plugin page slug.
	 */
	public function aswc_admin_enqueue_styles( $hook ) {

		$aswc_screen_ids = aswc_get_page_screen();
		$screen          = get_current_screen();

		if ( ( isset( $screen->id ) && in_array( $screen->id, $aswc_screen_ids, true ) ) || 'aswc_subscriptions' === $screen->id || 'edit-aswc_subscriptions' === $screen->id || 'woocommerce_page_wc-orders--aswc_subscriptions' === $screen->id || 'shop_order' === $screen->id || 'woocommerce_page_wc-orders--shop-order' === $screen->id ) {

			wp_enqueue_style( $this->plugin_name, ASWC_INCLUDES_DIR_URL . 'admin/css/subscriptions-jc-for-wc-include-admin.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-orderfix', ASWC_INCLUDES_DIR_URL . 'admin/css/subscriptions-jc-for-wc-orderfix.css', array(), $this->version, 'all' );
			wp_add_inline_style(
				$this->plugin_name,
				'.aswc-retry-status-inline{margin-top:12px;font-size:12px;opacity:.95;display:block}' .
				'.aswc-retry-status-inline strong{font-weight:600}' .
				'.date-fields{margin-top:14px}' .
				'p:has(#wcs-timezone){font-weight:600;margin-top:14px;margin-bottom:8px}' .
				'#wcs-timezone{font-weight:400}'
			);
		}

                if ( isset( $screen->id ) && 'product' === $screen->id ) {

                        wp_enqueue_style( 'aswc-loader-product-edit', ASWC_INCLUDES_DIR_URL . 'admin/css/aswc-loader-product-edit.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 * @param    string $hook      The plugin page slug.
	 */
	public function aswc_admin_enqueue_scripts( $hook ) {

		$aswc_screen_ids = aswc_get_page_screen();
		$screen          = get_current_screen();

                if ( ( isset( $screen->id ) && in_array( $screen->id, $aswc_screen_ids, true ) ) || 'aswc_subscriptions' === $screen->id || 'edit-aswc_subscriptions' === $screen->id || 'woocommerce_page_wc-orders--aswc_subscriptions' === $screen->id ) {

                        wp_register_script( 'aswc-loader-admin', ASWC_INCLUDES_DIR_URL . 'admin/js/aswc-loader-admin.js', array( 'jquery' ), $this->version, false );

                        wp_localize_script(
                                'aswc-loader-admin',
				'aswc_admin_param',
				array(
					'ajaxurl'                              => admin_url( 'admin-ajax.php' ),
					'aswc_auth_nonce'                      => wp_create_nonce( 'aswc_admin_nonce' ),
					'reloadurl'                            => admin_url(),
					'screen_id'                            => $screen->id,
					'subscription_price_error'             => __( 'Subscription price must be greater than 0.', 'advanced-subscriptions-for-woocommerce' ),
					'subscription_next_payment_date_error' => __( 'Next payment date cannot be in the past date. Kindly check!', 'advanced-subscriptions-for-woocommerce' ),
				)
			);

                        wp_enqueue_script( 'aswc-loader-admin' );
                        wp_add_inline_script(
                                'aswc-loader-admin',
				'var aswc_admin_blank_state = ' . wp_json_encode(
					array(
						'message' => __( 'When you receive a new subscription, it will appear here.', 'advanced-subscriptions-for-woocommerce' ),
						'button'  => __( 'Learn more about subscriptions', 'advanced-subscriptions-for-woocommerce' ),
						'url'     => 'https://plugins.joseconit.com',
					)
				) . ';',
				'before'
			);

			if ( 'edit-aswc_subscriptions' === $screen->id && ! aswc_loader_is_hpos_enabled() ) {
				wp_enqueue_script( 'wc-orders' );
				add_action( 'admin_footer', array( $this, 'aswc_render_order_preview_template' ) );
			}
		}
		if ( isset( $screen->id ) && 'product' === $screen->id ) {

                        wp_register_script( 'aswc-loader-product-edit', ASWC_INCLUDES_DIR_URL . 'admin/js/aswc-loader-product-edit.js', array( 'jquery' ), $this->version, false );
                        wp_enqueue_script( 'aswc-loader-product-edit' );
			$aswc_data = array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'reloadurl' => admin_url(),
				'day'       => __( 'Days', 'advanced-subscriptions-for-woocommerce' ),
				'week'      => __( 'Weeks', 'advanced-subscriptions-for-woocommerce' ),
				'month'     => __( 'Months', 'advanced-subscriptions-for-woocommerce' ),
				'year'      => __( 'Years', 'advanced-subscriptions-for-woocommerce' ),
			);
			wp_localize_script(
                                'aswc-loader-product-edit',
				'aswc_product_param',
				$aswc_data
			);
		}
	}

		/**
		 * Render the order preview template for the subscriptions list table.
		 *
		 * Ensures the preview modal works when HPOS is disabled.
		 *
		 * @return void
		 */
	public function aswc_render_order_preview_template() {
		if ( function_exists( 'wc_get_container' ) && class_exists( '\\Automattic\\WooCommerce\\Internal\\Admin\\Orders\\ListTable' ) ) {
				$list_table = wc_get_container()->get( \Automattic\WooCommerce\Internal\Admin\Orders\ListTable::class );
				echo $list_table->get_order_preview_template(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( class_exists( 'WC_Admin_List_Table_Orders' ) ) {
				$orders_table = new WC_Admin_List_Table_Orders();
				$orders_table->order_preview_template();
		}
	}

		/**
		 * Advanced Subscriptions For WooCommerce admin menu page.
		 *
		 * @param array $aswc_tabs aswc_tabs.
		 * @since 1.0.0
		 */
	public function aswc_admin_other_settings_page( $aswc_tabs ) {

				$aswc_tabs['advanced-subscriptions-for-woocommerce-others'] = array(
					'title'     => esc_html__( 'Advance Settings', 'advanced-subscriptions-for-woocommerce' ),
					'name'      => 'advanced-subscriptions-for-woocommerce-others',
					'file_path' => ASWC_INCLUDES_PATH,
				);
				return $aswc_tabs;
	}


	/**
	 * Advanced Subscriptions For WooCommerce admin menu page.
	 *
	 * @since 1.0.0
	 * @param array $aswc_settings_general Settings fields.
	 */
	public function aswc_admin_other_settings_fields( $aswc_settings_general ) {

		$aswc_settings_general = array(
			array(
				'title'       => __( 'Allow customer to select subscription expiry date', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable Allow customer to select subscription expiry date.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_allow_subscription_expiry_customer',
				'value'       => get_option( 'aswc_allow_subscription_expiry_customer' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),

			array(
				'title'       => __( 'Enable automatic retry subscription on failed attempts', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable automatic retry subscription on failed attempts.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_automatic_retry_failed_attempts',
				'value'       => get_option( 'aswc_enable_automatic_retry_failed_attempts' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Enter the number after certain failed attempts subscription will be canceled', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'number',
				'description' => __( 'Enter the number after certain failed attempts subscription will be canceled.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_after_no_failed_attempt_cancel',
				'value'       => get_option( 'aswc_after_no_failed_attempt_cancel', '3' ),
				'class'       => 'aswc-number-class',
				'placeholder' => __( 'Enter the number after certain failed attempts subscription will be canceled', 'advanced-subscriptions-for-woocommerce' ),
				'min'         => 1,
			),
			array(
				'title'       => __( 'Ability to pause the subscription for a certain time by customer', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Ability to pause the subscription for a certain time by the customer.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_pause_susbcription_by_customer',
				'value'       => get_option( 'aswc_enable_pause_susbcription_by_customer' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Ability to start paused subscription by customer', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Ability to start paused subscription by customer.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_start_pause_susbcription_by_customer',
				'value'       => get_option( 'aswc_start_pause_susbcription_by_customer' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Ability to accept manual payment for subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable this to accept Manual payment for subscription.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enbale_accept_manual_payment',
				'value'       => get_option( 'aswc_enbale_accept_manual_payment' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),

			array(
				'title'       => __( 'Ability to send subscription is going to expire email notification', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable this to send subscription is going to expire email notification.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_plan_going_to_expire',
				'value'       => get_option( 'aswc_enable_plan_going_to_expire' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Enter the number of days before subscription expire email send', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'number',
				'description' => __( 'Enter the number of days before subscription expire email send.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_plan_going_to_expire_before_days',
				'value'       => get_option( 'aswc_plan_going_to_expire_before_days', '7' ),
				'class'       => 'aswc-number-class',
				'placeholder' => __( 'Enter the number of days before subscription expire email send', 'advanced-subscriptions-for-woocommerce' ),
				'min'         => 1,
			),

			array(
				'title'       => __( 'Ability to upgrade/downgrade variable subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable this to upgrade/downgrade variable subscription.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enbale_downgrade_upgrade_subscription',
				'value'       => get_option( 'aswc_enbale_downgrade_upgrade_subscription' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Allow only for same interval in upgrade/downgrade.', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable this to allow only for same interval in upgrade/downgrade.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_allow_same_interval',
				'value'       => get_option( 'aswc_enable_allow_same_interval' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Stop Downgrade variable subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable this to downgrade variable subscription.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_downgrade_variable_subscription',
				'value'       => get_option( 'aswc_downgrade_variable_subscription' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Upgrade and Downgrade button text', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Use this option to change Upgrade and Downgrade button text.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_upgrade_downgrade_btn_text',
				'value'       => get_option( 'aswc_upgrade_downgrade_btn_text', 'Upgrade and Downgrade' ),
				'class'       => 'aswc-text-class',
				'placeholder' => __( 'Upgrade and Downgrade button text', 'advanced-subscriptions-for-woocommerce' ),
			),

			array(
				'title'       => __( 'Ability to accept prorate signup fee upgrade/downgrade variable subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable this to accept signup upgrade/downgrade variable subscription.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_signup_fee_downgrade_upgrade_subscription',
				'value'       => get_option( 'aswc_enable_signup_fee_downgrade_upgrade_subscription' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),

			array(
				'title'       => __( 'Ability to accept prorate price on upgrade/downgrade variable subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable this to accept signup upgrade/downgrade variable subscription.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_prorate_on_price_downgrade_upgrade_subscription',
				'value'       => get_option( 'aswc_enable_prorate_on_price_downgrade_upgrade_subscription' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Manage prorate amount during upgrade/downgrade subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio',
				'description' => __( 'Enable this to downgrade variable subscription.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_manage_prorate_amount',
				'value'       => get_option( 'aswc_manage_prorate_amount' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'aswc_manage_prorate_next_payment_date' => __( 'Extend next payment date', 'advanced-subscriptions-for-woocommerce' ),
					'aswc_manage_prorate_using_wallet' => __( 'Put left amount in the user wallet', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Ability to take renewal subscription payment from the certain date', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Ability to start subscription from the certain date of the month.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_start_susbcription_from_certain_date_of_month',
				'value'       => get_option( 'aswc_start_susbcription_from_certain_date_of_month' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'   => __( 'Prorate amount for certain date of month subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'    => 'select',
				'id'      => 'aswc_prorate_price_on_sync',
				'name'    => 'aswc_prorate_price_on_sync',
				'value'   => get_option( 'aswc_prorate_price_on_sync', 'aswc_prorate_no' ),
				'class'   => 'aswc-select-class',
				'options' => array(
					'aswc_prorate_no'            => __( 'Do not charge prorate amount', 'advanced-subscriptions-for-woocommerce' ),
					'aswc_prorate_simple'        => __( 'Charge prorate amount for subscription', 'advanced-subscriptions-for-woocommerce' ),
					'aswc_prorate_if_free_trial' => __( 'Charge prorate amount for subscription even free trial', 'advanced-subscriptions-for-woocommerce' ),
				),
			),

			array(
				'title'       => __( 'Ability to allow the customer to add multiple subscriptions in cart', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Ability to allow the customer to add multiple subscriptions.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_allow_to_add_multiple_subscription_cart',
				'value'       => get_option( 'aswc_allow_to_add_multiple_subscription_cart' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),


			array(
				'title'       => __( 'Allow multiple quantities on subscription products.', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Allow multiple quantities on subscription products.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_allow_multiple_quantity_subscription',
				'value'       => get_option( 'aswc_allow_multiple_quantity_subscription' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Allow start date on subscription products.', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Allow start date on subscription products.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_allow_start_date_subscription',
				'value'       => get_option( 'aswc_allow_start_date_subscription' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Enter the number of days before which you want to send the recurring payment reminder.', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'number',
				'description' => __( 'Enter the number of days before which you want to send the recurring payment reminder.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_send_before_recurring_reminder',
				'value'       => get_option( 'aswc_send_before_recurring_reminder', '5' ),
				'class'       => 'aswc-number-class',
				'placeholder' => __( 'Enter the number of days before which you want to send the recurring payment reminder.', 'advanced-subscriptions-for-woocommerce' ),
				'min'         => 1,
			),

		);
		$aswc_settings_general[] = array(
			'title'       => __( 'Allow the Time duration for the Subscription cancellation', 'advanced-subscriptions-for-woocommerce' ),
			'type'        => 'radio-switch',
			'description' => '',
			'id'          => 'aswc_allow_time_subscription_cancellation',
			'value'       => get_option( 'aswc_allow_time_subscription_cancellation' ),
			'class'       => 'aswc-radio-switch-class',
			'options'     => array(
				'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
				'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
			),
		);
		$aswc_settings_general[] = array(
			'title'       => __( 'Enter the number of days after that, user should be able to cancel their subscription', 'advanced-subscriptions-for-woocommerce' ),
			'type'        => 'number',
			'description' => '',
			'id'          => 'aswc_time_duration_subscription_cancellation',
			'value'       => get_option( 'aswc_time_duration_subscription_cancellation', null ),
			'class'       => 'aswc-number-class',
			'placeholder' => '',
			'min'         => 1,
		);

		$aswc_settings_general[] = array(
			'type'        => 'button',
			'id'          => 'aswc_save_other_settings',
			'button_text' => __( 'Save Settings', 'advanced-subscriptions-for-woocommerce' ),
			'class'       => 'aswc-button-class',
		);

		return apply_filters( 'aswc_add_advance_settings_fields', $aswc_settings_general );
	}

	/**
	 * Advanced Subscriptions For WooCommerce save tab settings.
	 *
	 * @name aswc_admin_save_tab_settings
	 * @since 1.0.0
	 */
	public function aswc_admin_save_tab_settings() {
				global $aswc_obj;
		global $aswc_notices;
		if ( isset( $_POST['aswc_save_other_settings'] ) && isset( $_POST['aswc-others-nonce-field'] ) ) {
			if ( ! isset( $_POST['aswc-others-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aswc-others-nonce-field'] ) ), 'aswc-others-nonce' ) ) {
				return;
			}
			$aswc_gen_flag         = false;
			$aswc_genaral_settings = apply_filters( 'aswc_others_settings_array', array() );
			$aswc_button_index     = array_search( 'submit', array_column( $aswc_genaral_settings, 'type' ) );
			if ( isset( $aswc_button_index ) && ( null === $aswc_button_index || '' === $aswc_button_index ) ) {
				$aswc_button_index = array_search( 'button', array_column( $aswc_genaral_settings, 'type' ) );
			}
			if ( isset( $aswc_button_index ) && '' !== $aswc_button_index ) {
				unset( $aswc_genaral_settings[ $aswc_button_index ] );
				if ( is_array( $aswc_genaral_settings ) && ! empty( $aswc_genaral_settings ) ) {
					foreach ( $aswc_genaral_settings as $aswc_genaral_setting ) {
						if ( isset( $aswc_genaral_setting['id'] ) && '' !== $aswc_genaral_setting['id'] ) {
							if ( isset( $_POST[ $aswc_genaral_setting['id'] ] ) ) {
								$posted_value = sanitize_text_field( wp_unslash( $_POST[ $aswc_genaral_setting['id'] ] ) );
								if ( 'radio-switch' === $aswc_genaral_setting['type'] ) {
										$posted_value = ( 'on' === $posted_value ) ? 'yes' : 'no';
								}
								update_option( $aswc_genaral_setting['id'], $posted_value );
							} else {
								update_option( $aswc_genaral_setting['id'], ( 'radio-switch' === $aswc_genaral_setting['type'] ) ? 'no' : '' );
							}
						} else {
							$aswc_gen_flag = true;
						}
					}
				}
				if ( $aswc_gen_flag ) {
					$aswc_error_text = esc_html__( 'Id of some field is missing', 'advanced-subscriptions-for-woocommerce' );
										$aswc_obj->aswc_plug_admin_notice( $aswc_error_text, 'error' );
				} else {
					$aswc_notices = true;
				}
			}
		}
	}

	/**
	 * This function is used to create variable susbcription.
	 *
	 * @name aswc_sfw_woocommerce_variation_options
	 * @param int    $loop loop.
	 * @param array  $variation_data variation_data.
	 * @param object $variation variation.
	 * @since 1.0.0
	 */
	public function aswc_sfw_woocommerce_variation_options( $loop, $variation_data, $variation ) {
		$variation_id   = $variation->ID;
		$aswc_is_enable = aswc_get_meta_data( $variation_id, 'aswc_variable_product', true );

		?>
		<label class="tips" data-tip="<?php esc_attr_e( 'Enable this option to make subscription type variation', 'advanced-subscriptions-for-woocommerce' ); ?>">
			<?php esc_html_e( 'Subscription', 'advanced-subscriptions-for-woocommerce' ); ?>:
			<input type="checkbox" class="checkbox aswc_variation_enable" name="aswc_variation_enable[<?php echo esc_attr( $loop ); ?>]" <?php checked( $aswc_is_enable, 'yes' ); ?> />
		</label>
		<?php
		wp_nonce_field( 'aswc_variation_field', 'aswc_variation_field_nonce', false );
	}

	/**
	 * This function is used to save variable susbcription.
	 *
	 * @name aswc_sfw_save_product_variation
	 * @param int $variation_id variation_id.
	 * @param int $loop loop.
	 * @since 1.0.0
	 */
	public function aswc_sfw_save_product_variation( $variation_id, $loop ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$aswc_product = isset( $_POST['aswc_variation_enable'][ $loop ] ) ? 'yes' : 'no';

		if ( ! isset( $_POST['aswc_variation_field_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['aswc_variation_field_nonce'] ) ), 'aswc_variation_field' ) ) {
			return;
		}

		if ( 'yes' === $aswc_product ) {

			$aswc_variation_subscription_number          = isset( $_POST['aswc_variation_subscription_number'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_subscription_number'][ $loop ] ) ) : '';
			$aswc_variation_subscription_interval        = isset( $_POST['aswc_variation_subscription_interval'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_subscription_interval'][ $loop ] ) ) : '';
			$aswc_variation_subscription_expiry_number   = isset( $_POST['aswc_variation_subscription_expiry_number'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_subscription_expiry_number'][ $loop ] ) ) : '';
			$aswc_variation_subscription_expiry_interval = isset( $_POST['aswc_variation_subscription_expiry_interval'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_subscription_expiry_interval'][ $loop ] ) ) : '';

			$aswc_variation_subscription_start_date = isset( $_POST['aswc_variation_subscription_start_date'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_subscription_start_date'][ $loop ] ) ) : '';

			/*get valid subscription expiry*/
			$aswc_variation_subscription_expiry_number        = aswc_get_valid_subscription_expiry( $aswc_variation_subscription_expiry_number, $aswc_variation_subscription_expiry_interval );
			$aswc_variation_subscription_initial_signup_price = isset( $_POST['aswc_variation_subscription_initial_signup_price'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_subscription_initial_signup_price'][ $loop ] ) ) : '';

			$aswc_variation_subscription_free_trial_number = isset( $_POST['aswc_variation_subscription_free_trial_number'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_subscription_free_trial_number'][ $loop ] ) ) : '';

			$aswc_variation_subscription_free_trial_interval = isset( $_POST['aswc_variation_subscription_free_trial_interval'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_subscription_free_trial_interval'][ $loop ] ) ) : '';
			/*get valid subscription expiry*/
			$aswc_variation_subscription_free_trial_number = aswc_get_valid_subscription_expiry( $aswc_variation_subscription_free_trial_number, $aswc_variation_subscription_free_trial_interval );

			$aswc_variation_subscription_limit_for_trial = isset( $_POST['aswc_variation_subscription_limit_for_trial'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_subscription_limit_for_trial'][ $loop ] ) ) : '';
			$aswc_free_trials_limit                      = isset( $_POST['aswc_free_trials_limit'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_free_trials_limit'][ $loop ] ) ) : '';

			/*certain date of month*/

			$aswc_enbale_certain_month = isset( $_POST['aswc_variation_enbale_certain_month'][ $loop ] ) ? 'yes' : 'no';

			$aswc_week_sync = isset( $_POST['aswc_variation_week_sync'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_week_sync'][ $loop ] ) ) : '';

			$aswc_month_sync = isset( $_POST['aswc_variation_month_sync'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_month_sync'][ $loop ] ) ) : '';

			$aswc_year_sync = isset( $_POST['aswc_variation_year_sync'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_year_sync'][ $loop ] ) ) : '';

			$aswc_year_number = isset( $_POST['aswc_variation_certain_date_enable_year_number'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_variation_certain_date_enable_year_number'][ $loop ] ) ) : 1;

			aswc_update_order_meta( $variation_id, 'aswc_enbale_certain_month', $aswc_enbale_certain_month );
			aswc_update_order_meta( $variation_id, 'aswc_week_sync', $aswc_week_sync );
			aswc_update_order_meta( $variation_id, 'aswc_month_sync', $aswc_month_sync );
			aswc_update_order_meta( $variation_id, 'aswc_year_sync', $aswc_year_sync );
			aswc_update_order_meta( $variation_id, 'aswc_year_number', $aswc_year_number );

			/*certain date of month end*/

			aswc_update_order_meta( $variation_id, 'aswc_subscription_number', $aswc_variation_subscription_number );
			aswc_update_order_meta( $variation_id, 'aswc_subscription_interval', $aswc_variation_subscription_interval );
			aswc_update_order_meta( $variation_id, 'aswc_subscription_expiry_number', $aswc_variation_subscription_expiry_number );
			aswc_update_order_meta( $variation_id, 'aswc_subscription_expiry_interval', $aswc_variation_subscription_expiry_interval );
			aswc_update_order_meta( $variation_id, 'aswc_subscription_initial_signup_price', $aswc_variation_subscription_initial_signup_price );
			aswc_update_order_meta( $variation_id, 'aswc_subscription_free_trial_number', $aswc_variation_subscription_free_trial_number );
			aswc_update_order_meta( $variation_id, 'aswc_subscription_free_trial_interval', $aswc_variation_subscription_free_trial_interval );
			if ( aswc_allow_start_date_subscription() ) {
				aswc_update_order_meta( $variation_id, 'aswc_subscription_start_date', $aswc_variation_subscription_start_date );
			}

			/*Set variable meta*/
			$variation = wc_get_product( $variation_id );
			if ( isset( $variation ) && ! empty( $variation ) ) {
				$product_id = $variation->get_parent_id();
				aswc_update_order_meta( $product_id, 'aswc_subscription_number', $aswc_variation_subscription_number );
				aswc_update_order_meta( $product_id, 'aswc_subscription_interval', $aswc_variation_subscription_interval );
				aswc_update_order_meta( $product_id, 'aswc_subscription_expiry_number', $aswc_variation_subscription_expiry_number );
				aswc_update_order_meta( $product_id, 'aswc_subscription_expiry_interval', $aswc_variation_subscription_expiry_interval );
				aswc_update_order_meta( $product_id, 'aswc_subscription_initial_signup_price', $aswc_variation_subscription_initial_signup_price );
				aswc_update_order_meta( $product_id, 'aswc_subscription_free_trial_number', $aswc_variation_subscription_free_trial_number );
				aswc_update_order_meta( $product_id, 'aswc_subscription_free_trial_interval', $aswc_variation_subscription_free_trial_interval );
				aswc_update_order_meta( $product_id, 'aswc_variable_product', $aswc_product );
				if ( aswc_allow_start_date_subscription() ) {
					aswc_update_order_meta( $product_id, 'aswc_subscription_start_date', $aswc_variation_subscription_start_date );
				}
			}

			// Save variable one time meta.
			$aswc_one_time_purchase           = isset( $_POST['aswc_one_time_purchase'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_one_time_purchase'][ $loop ] ) ) : '';
			$aswc_subscription_one_time_price = isset( $_POST['aswc_subscription_one_time_price'][ $loop ] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_one_time_price'][ $loop ] ) ) : '';
			aswc_update_order_meta( $variation_id, 'aswc_one_time_purchase', $aswc_one_time_purchase );
			aswc_update_order_meta( $variation_id, 'aswc_onetime_price', $aswc_subscription_one_time_price );

			aswc_update_order_meta( $variation_id, 'aswc_subscription_limit_for_trial', $aswc_variation_subscription_limit_for_trial );
			aswc_update_order_meta( $variation_id, 'aswc_free_trials_limit', $aswc_free_trials_limit );

		}

		aswc_update_order_meta( $variation_id, 'aswc_variable_product', $aswc_product );

		do_action( 'aswc_save_variation_field', $variation_id );

		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * This function is used create variable susbcription field.
	 *
	 * @name aswc_sfw_variation_options_pricing
	 * @param int    $loop loop.
	 * @param array  $variation_data variation_data.
	 * @param object $variation variation.
	 * @since 1.0.0
	 */
	public function aswc_sfw_variation_options_pricing( $loop, $variation_data, $variation ) {
		$variation_id          = $variation->ID;
		$aswc_variation_number = aswc_get_meta_data( $variation_id, 'aswc_subscription_number', true );

		if ( empty( $aswc_variation_number ) ) {
			$aswc_variation_number = 1;
		}
		$aswc_variation_subscription_interval = aswc_get_meta_data( $variation_id, 'aswc_subscription_interval', true );

		if ( empty( $aswc_variation_subscription_interval ) ) {
			$aswc_variation_subscription_interval = 'day';
		}

		$aswc_variation_expiry_number           = aswc_get_meta_data( $variation_id, 'aswc_subscription_expiry_number', true );
		$aswc_variation_expiry_interval         = aswc_get_meta_data( $variation_id, 'aswc_subscription_expiry_interval', true );
		$aswc_variation_initial_fee             = aswc_get_meta_data( $variation_id, 'aswc_subscription_initial_signup_price', true );
		$aswc_variation_free_trial              = aswc_get_meta_data( $variation_id, 'aswc_subscription_free_trial_number', true );
		$aswc_variation_free_trial_interval     = aswc_get_meta_data( $variation_id, 'aswc_subscription_free_trial_interval', true );
		$aswc_variation_subscription_start_date = aswc_get_meta_data( $variation_id, 'aswc_subscription_start_date', true );

		$aswc_subscription_limit_for_trial = aswc_get_meta_data( $variation_id, 'aswc_subscription_limit_for_trial', true );
		$aswc_free_trials_limit            = aswc_get_meta_data( $variation_id, 'aswc_free_trials_limit', true );

		?>
		<div class="aswc_product" style="display: none;">
			<p class="form-field form-row form-row-first aswc_variation_subscription_number_field ">
				<label for="aswc_variation_subscription_number<?php echo esc_attr( $loop ); ?>">
				<?php esc_html_e( 'Subscriptions Per Interval', 'advanced-subscriptions-for-woocommerce' ); ?>
				</label>
				<?php
					$description_text = __( 'Choose the subscriptions time interval for the product "for example 10 days"', 'advanced-subscriptions-for-woocommerce' );
					echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
				?>
				<input type="number" class="short wc_input_price aswc_variation_subscription_number"  min="1" required name="aswc_variation_subscription_number[<?php echo esc_attr( $loop ); ?>]" id="aswc_variation_subscription_number<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_attr( $aswc_variation_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription interval', 'advanced-subscriptions-for-woocommerce' ); ?>" data-attr="<?php echo esc_attr( $loop ); ?>">
				<select id="aswc_variation_subscription_interval<?php echo esc_attr( $loop ); ?>" name="aswc_variation_subscription_interval[<?php echo esc_attr( $loop ); ?>]" class="aswc_variation_subscription_interval" data-attr="<?php echo esc_attr( $loop ); ?>">
					<?php foreach ( aswc_subscription_period() as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_variation_subscription_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>

			</p>
			<?php
			if ( aswc_allow_start_date_subscription() ) {

				?>
				
			<p class="form-field form-row form-row-last aswc_variation_subscription_start_date_field">
				<label for="aswc_variation_subscription_start_date<?php echo esc_attr( $loop ); ?>">
				<?php esc_html_e( 'Choose subscription start date', 'advanced-subscriptions-for-woocommerce' ); ?>
				</label>
				<?php
					$description_text = __( 'Choose subscription start date', 'advanced-subscriptions-for-woocommerce' );
					echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
				?>
				<input type="text" class="aswc_subscription_start_date aswc_variation_subscription_start_date" name="aswc_variation_subscription_start_date[<?php echo esc_attr( $loop ); ?>]" id="aswc_variation_subscription_start_date<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_attr( $aswc_variation_subscription_start_date ); ?>" placeholder="<?php esc_html_e( 'Choose Start Date', 'advanced-subscriptions-for-woocommerce' ); ?>" data-attr="<?php echo esc_attr( $loop ); ?>">

			</p>
				<?php
			}
			?>
			<p class="form-field form-row form-row-first aswc_variation_subscription_expiry_field ">
				<label for="aswc_variation_subscription_expiry_number<?php echo esc_attr( $loop ); ?>">
				<?php esc_html_e( 'Subscriptions Expiry Interval', 'advanced-subscriptions-for-woocommerce' ); ?>
				</label>
				<?php
				$description_text = __( 'Choose the subscriptions expiry time interval for the product "leave empty for unlimited"', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
				?>
				<input type="number" class="short wc_input_price aswc_variation_subscription_expiry_number"  min="1" name="aswc_variation_subscription_expiry_number[<?php echo esc_attr( $loop ); ?>]" id="aswc_variation_subscription_expiry_number<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_attr( $aswc_variation_expiry_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription expiry', 'advanced-subscriptions-for-woocommerce' ); ?>" data-attr="<?php echo esc_attr( $loop ); ?>">
				<select id="aswc_variation_subscription_expiry_interval<?php echo esc_attr( $loop ); ?>" name="aswc_variation_subscription_expiry_interval[<?php echo esc_attr( $loop ); ?>]" class="aswc_variation_subscription_expiry_interval" data-attr="<?php echo esc_attr( $loop ); ?>">
					<?php foreach ( aswc_subscription_expiry_period( $aswc_variation_subscription_interval ) as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_variation_expiry_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			</p>
			<p class="form-field form-row form-row-last aswc_variation_subscription_initial_signup_field ">
				<label for="aswc_variation_subscription_initial_signup_price<?php echo esc_attr( $loop ); ?>">
				<?php
				esc_html_e( 'Initial Signup fee', 'advanced-subscriptions-for-woocommerce' );
				echo esc_html( '(' . get_woocommerce_currency_symbol() . ')' );
				?>
				</label>
				<?php
					$description_text = __( 'Choose the subscriptions initial fee for the product "leave empty for no initial fee"', 'advanced-subscriptions-for-woocommerce' );
					echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
				?>
				<input type="number" class="short wc_input_price aswc_variation_subscription_initial_signup_price"  min="0" step="any" name="aswc_variation_subscription_initial_signup_price[<?php echo esc_attr( $loop ); ?>]" id="aswc_variation_subscription_initial_signup_price<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_attr( $aswc_variation_initial_fee ); ?>" placeholder="<?php esc_html_e( 'Enter signup fee', 'advanced-subscriptions-for-woocommerce' ); ?>" data-attr="<?php echo esc_attr( $loop ); ?>">

			</p>
			<p class="form-field form-row form-row-first aswc_variation_subscription_free_trial_field ">
				<label for="aswc_variation_subscription_free_trial_number<?php echo esc_attr( $loop ); ?>">
				<?php esc_html_e( 'Free trial interval', 'advanced-subscriptions-for-woocommerce' ); ?>
				</label>
				<?php
				$description_text = __( 'Choose the trial period for subscription "leave empty for no trial period"', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
				?>
				<input type="number" class="short wc_input_number aswc_variation_subscription_free_trial_number"  min="1" name="aswc_variation_subscription_free_trial_number[<?php echo esc_attr( $loop ); ?>]" id="aswc_variation_subscription_free_trial_number<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_attr( $aswc_variation_free_trial ); ?>" placeholder="<?php esc_html_e( 'Enter free trial interval', 'advanced-subscriptions-for-woocommerce' ); ?>" data-attr="<?php echo esc_attr( $loop ); ?>">
				<select id="aswc_variation_subscription_free_trial_interval<?php echo esc_attr( $loop ); ?>" name="aswc_variation_subscription_free_trial_interval[<?php echo esc_attr( $loop ); ?>]" class="aswc_variation_subscription_free_trial_interval" >
					<?php foreach ( aswc_subscription_period() as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_variation_free_trial_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>

			</p>
			<!-- limit for variation -->
			<p class="form-field form-row form-row-first aswc_variation_subscription_free_trial__limit_field ">
				<input type="checkbox" class="checkbox aswc_free_trials_limit" name="aswc_free_trials_limit[<?php echo esc_attr( $loop ); ?>]" <?php checked( $aswc_free_trials_limit, 'on' ); ?> />
					<label for="aswc_free_trials_limit<?php echo esc_attr( $loop ); ?>">
					<span><?php esc_html_e( 'Free trial Limit', 'advanced-subscriptions-for-woocommerce' ); ?></span>
				</label>
				<?php
				$description_text = __( 'This Limit Restrict User to puchase multiple subscription"', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
				?>
				<input type="number" class="short wc_input_number aswc_variation_subscription_free_trial_limit"  min="1" name="aswc_variation_subscription_limit_for_trial[<?php echo esc_attr( $loop ); ?>]" id="aswc_variation_subscription_limit_for_trial<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_html( $aswc_subscription_limit_for_trial ); ?>" placeholder="<?php esc_html_e( 'Enter free trial Limit', 'advanced-subscriptions-for-woocommerce' ); ?>" data-attr="<?php echo esc_attr( $loop ); ?>">
			</p>
			<!-- limit for variation -->
			<?php
			if ( aswc_start_susbcription_from_certain_date_of_month() ) {

				$aswc_is_enable_certain_date = aswc_get_meta_data( $variation_id, 'aswc_enbale_certain_month', true );
				$aswc_year_number            = aswc_get_meta_data( $variation_id, 'aswc_year_number', true );
				if ( empty( $aswc_year_number ) ) {
					$aswc_year_number = 1;
				}
				$aswc_billing_period   = $aswc_variation_subscription_interval;
				$aswc_week_sync_value  = aswc_get_meta_data( $variation_id, 'aswc_week_sync', true );
				$aswc_month_sync_value = aswc_get_meta_data( $variation_id, 'aswc_month_sync', true );
				$aswc_year_sync_value  = aswc_get_meta_data( $variation_id, 'aswc_year_sync', true );

				$aswc_show_week = ( 'week' === $aswc_billing_period ) ? '' : 'display: none;';

				$aswc_show_month = ( 'month' === $aswc_billing_period ) ? '' : 'display: none;';

				$aswc_show_year = ( 'year' === $aswc_billing_period ) ? '' : 'display: none;';

				$aswc_show_week_month_year_check = ( ! in_array( $aswc_billing_period, array( 'month', 'week', 'year' ) ) ) ? 'display: none;' : '';
				?>
				<div class="form-field form-row form-row-full options aswc_certain_date_enable<?php echo esc_attr( $loop ); ?>" style="<?php echo esc_attr( $aswc_show_week_month_year_check ); ?>">
					<label class="tips" data-tip="<?php esc_attr_e( 'Enable subscription renewal on certain day/ date', 'advanced-subscriptions-for-woocommerce' ); ?>">
				<?php esc_html_e( 'Enable subscription renewal on certain day/ date', 'advanced-subscriptions-for-woocommerce' ); ?>:
						<input type="checkbox" class="checkbox aswc_variation_enbale_certain_month" name="aswc_variation_enbale_certain_month[<?php echo esc_attr( $loop ); ?>]" <?php checked( $aswc_is_enable_certain_date, 'yes' ); ?> data-attr="<?php echo esc_attr( $loop ); ?>" />
					</label>
				</div>
				<div class="aswc_certain_date_enable_wrap<?php echo esc_attr( $loop ); ?>">
					<div class="form-field form-row aswc-enable-week aswc_certain_date_enable_week<?php echo esc_attr( $loop ); ?>" style="<?php echo esc_attr( $aswc_show_week ); ?>">
				<?php
						woocommerce_wp_select(
							array(
								'id'      => "aswc_variation_week_sync{$loop}",
								'name'    => "aswc_variation_week_sync[{$loop}]",
								'class'   => 'select short',
								'label'   => __( 'Week for Synchronisation', 'advanced-subscriptions-for-woocommerce' ),
								'options' => aswc_subscription_week_period(),
								'value'   => $aswc_week_sync_value,
							)
						);
				?>
					</div>
					<div class="form-field form-row aswc-enable-month aswc_certain_date_enable_month<?php echo esc_attr( $loop ); ?>" style="<?php echo esc_attr( $aswc_show_month ); ?>">
					<?php
					woocommerce_wp_select(
						array(
							'id'      => "aswc_variation_month_sync{$loop}",
							'name'    => "aswc_variation_month_sync[{$loop}]",
							'class'   => 'select short',
							'label'   => __( 'Month for Synchronisation', 'advanced-subscriptions-for-woocommerce' ),
							'options' => aswc_subscription_month_period(),
							'value'   => $aswc_month_sync_value,
						)
					);
					?>
					</div>
					<div class="form-field form-row aswc-enable-year aswc_certain_date_enable_year<?php echo esc_attr( $loop ); ?>" style="<?php echo esc_attr( $aswc_show_year ); ?>">
						<label class="tips" data-tip="<?php esc_attr_e( 'Year for Synchronisation', 'advanced-subscriptions-for-woocommerce' ); ?>">
							<?php esc_html_e( 'Year for Synchronisation', 'advanced-subscriptions-for-woocommerce' ); ?>
						</label>
						<select id="aswc_variation_year_sync<?php echo esc_attr( $loop ); ?>" name="aswc_variation_year_sync[<?php echo esc_attr( $loop ); ?>]" class="select short aswc_variation_year_sync" >
						<?php foreach ( aswc_subscription_syn_year_period() as $value => $label ) { ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_year_sync_value, true ); ?>><?php echo esc_html( $label ); ?></option>
						<?php } ?>
						</select>

					<?php
										$aswc_current_time                                       = current_time( 'timestamp' );
																			$aswc_max_no_of_days = aswc_date( 't', $aswc_current_time );
					?>
					<input type="number" class="short wc_input_number aswc_certain_date_enable_year_num"  min="1" max="<?php echo esc_attr( $aswc_max_no_of_days ); ?>" name="aswc_variation_certain_date_enable_year_number[<?php echo esc_attr( $loop ); ?>]" id="aswc_variation_certain_date_enable_year_number<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_attr( $aswc_year_number ); ?>">
					</div>
				</div>
				<?php
			}
			// one time purchase value.
			$aswc_one_time_purchase           = aswc_get_meta_data( $variation_id, 'aswc_one_time_purchase', true );
			$aswc_subscription_one_time_price = aswc_get_meta_data( $variation_id, 'aswc_onetime_price', true );
			?>
			<!-- one time purchase for variation -->
			<p class="form-field form-row form-row-full options aswc_one_time_purchase_field">
				<input type="checkbox" class="checkbox aswc_one_time_purchase" name="aswc_one_time_purchase[<?php echo esc_attr( $loop ); ?>]" <?php checked( $aswc_one_time_purchase, 'on' ); ?> />
				<label for="aswc_one_time_purchase[<?php echo esc_attr( $loop ); ?>]">
					<span><?php esc_html_e( 'Enable one-time purchase', 'advanced-subscriptions-for-woocommerce' ); ?></span>
				</label>
				<?php
				$description_text = __( 'Please enter the One Time Purchase amount and make sure you have set the one time purchase amount is greater than subscription price otherwise this will not work', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
				?>
				<input type="number" class="short wc_input_number aswc_subscription_one_time_price"  min="1" data-prod_id="<?php echo esc_html( $variation_id ); ?>" name="aswc_subscription_one_time_price[<?php echo esc_attr( $loop ); ?>]" id="aswc_subscription_one_time_price<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_html( $aswc_subscription_one_time_price ); ?>" placeholder="<?php esc_html_e( 'Enter One Time Purchase Subscription Price', 'advanced-subscriptions-for-woocommerce' ); ?>" data-attr="<?php echo esc_attr( $loop ); ?>">
				<i><?php esc_html_e( 'Make sure you have set the one time purchase amount is greater than subscription price otherwise this will not work', 'advanced-subscriptions-for-woocommerce' ); ?></i>
			</p>
			<!-- one time purchase for variation -->
			<?php
			do_action( 'aswc_create_variation_field', $loop, $variation_data, $variation, $variation_id );
			?>
		</div>
		<?php
	}

	/**
	 * This function is used add option in subscription table.
	 *
	 * @name aswc_add_option_details
	 * @param array $actions actions.
	 * @param bool  $subscription_id subscription_id.
	 * @since 1.0.0
	 */
	public function aswc_add_option_details( $actions, $subscription_id ) {
		$aswc_status = aswc_get_meta_data( $subscription_id, 'aswc_subscription_status', true );
		if ( 'active' === $aswc_status ) {
			$aswc_link = add_query_arg(
				array(
					'aswc_subscription_id'                 => $subscription_id,
					'aswc_subscription_status_admin_pause' => $aswc_status,
				)
			);

			$aswc_link       = wp_nonce_url( $aswc_link, $subscription_id . $aswc_status );
			$aswc_pause_link = array(
				'aswc_pause' => '<a href="' . $aswc_link . '">' . __( 'Pause', 'advanced-subscriptions-for-woocommerce' ) . '</a>',

			);
			$actions = array_merge( $aswc_pause_link, $actions );
		} elseif ( 'paused' === $aswc_status ) {
			$aswc_link = add_query_arg(
				array(
					'aswc_subscription_id' => $subscription_id,
					'aswc_subscription_status_admin_reactivate' => $aswc_status,
				)
			);

			$aswc_link            = wp_nonce_url( $aswc_link, $subscription_id . $aswc_status );
			$aswc_reactivate_link = array(
				'aswc_reactivate' => '<a href="' . $aswc_link . '">' . __( 'Reactivate', 'advanced-subscriptions-for-woocommerce' ) . '</a>',

			);
			$actions = array_merge( $aswc_reactivate_link, $actions );
		}
		if ( ! empty( $aswc_status ) ) {

			$aswc_link = add_query_arg(
				array(
					'aswc_subscription_id'                 => $subscription_id,
					'aswc_subscription_view_renewal_order' => $aswc_status,
				)
			);

			$aswc_link      = wp_nonce_url( $aswc_link, $subscription_id . $aswc_status );
			$aswc_view_link = array(
				'aswc_view' => '<a href="' . $aswc_link . '">' . __( 'View Renewals', 'advanced-subscriptions-for-woocommerce' ) . '</a>',

			);
			$actions = array_merge( $aswc_view_link, $actions );
		}

		if ( 'active' === $aswc_status ) {
			$interval_type = aswc_get_meta_data( $subscription_id, 'aswc_subscription_interval', true );
			$interval_freq = aswc_get_meta_data( $subscription_id, 'aswc_subscription_number', true );
			if ( 'day' === $interval_type && 1 === $interval_freq ) {
				$aswc_link = add_query_arg(
					array(
						'aswc_subscription_id' => $subscription_id,
						'aswc_subscription_status_admin_create_recurring' => true,
					)
				);

				$aswc_link                                     = wp_nonce_url( $aswc_link, $subscription_id . $aswc_status );
				$aswc_view_link['aswc_initiate_recurring_now'] = '<a href="' . $aswc_link . '">' . __( 'Create Renewal', 'advanced-subscriptions-for-woocommerce' ) . '</a>';
				$actions                                       = array_merge( $actions, $aswc_view_link );
			}
		}

		return $actions;
	}


	/**
	 * This function allow to create a renewal for the subscription .
	 */
	public function aswc_create_manually_recurring() {
		if ( isset( $_GET['aswc_subscription_status_admin_create_recurring'] ) && 1 === $_GET['aswc_subscription_status_admin_create_recurring'] && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {

		// SECURITY: Only admins can create manual recurring orders.
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You do not have permission to create recurring orders.', 'advanced-subscriptions-for-woocommerce' ), 403 );
		}

			$aswc_subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) );
			aswc_update_order_meta( $aswc_subscription_id, 'aswc_next_payment_date', current_time( 'timestamp' ) - 100 );
			if ( class_exists( 'ASWC_Scheduler' ) ) {
				$scheduler_object = new ASWC_Scheduler();
				if ( aswc_loader_is_hpos_enabled() ) {
					$scheduler_object->aswc_renewal_order_on_scheduler_hpos();
				} else {
					$scheduler_object->aswc_renewal_order_on_scheduler();
				}
				$redirect_url = admin_url() . 'admin.php?page=aswc_subscriptions_for_woocommerce_menu&sfw_tab=aswc-subscriptions-table';
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * This function is used pause susbcription.
	 *
	 * @name aswc_admin_pause_susbcription
	 * @since 1.0.0
	 */
	public function aswc_admin_pause_susbcription() {

		if ( isset( $_GET['aswc_subscription_status_admin_pause'] ) && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {

		// SECURITY: Only admins can pause subscriptions.
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You do not have permission to pause subscriptions.', 'advanced-subscriptions-for-woocommerce' ), 403 );
		}

			$aswc_status          = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_status_admin_pause'] ) );
			$aswc_subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) );
			if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				aswc_update_order_meta( $aswc_subscription_id, 'aswc_subscription_status', 'paused' );
				aswc_set_pause_subscription_timestamp( $aswc_subscription_id );
				aswc_send_email_for_pause_susbcription( $aswc_subscription_id );
				$redirect_url = admin_url() . 'admin.php?page=aswc_subscriptions_for_woocommerce_menu&sfw_tab=aswc-subscriptions-table';
				wp_safe_redirect( $redirect_url );
				exit;
			}
		} elseif ( isset( $_GET['aswc_subscription_status_admin_reactivate'] ) && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$aswc_status          = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_status_admin_reactivate'] ) );
			$aswc_subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) );
			if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				aswc_reactivate_time_calculation( $aswc_subscription_id );
				aswc_update_order_meta( $aswc_subscription_id, 'aswc_subscription_status', 'active' );
				aswc_send_email_for_reactivate_susbcription( $aswc_subscription_id );
				$redirect_url = admin_url() . 'admin.php?page=aswc_subscriptions_for_woocommerce_menu&sfw_tab=aswc-subscriptions-table';
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * This function is used status susbcription.
	 *
	 * @name aswc_status_array
	 * @param array $status status.
	 * @since 1.0.0
	 */
	public function aswc_status_array( $status ) {
			return array(
				'active',
				'on-hold',
				'cancelled',
				'expired',
				'pending',
				'paused',
				'pending-cancel',  // Fixed: was 'pending-cancellation' which doesn't match rest of codebase.
			);
	}

	/**
	 * This function is used create csv file.
	 *
	 * @name aswc_export_csv_report
	 * @since 1.0.0
	 */
	public function aswc_export_csv_report() {

		// Only proceed if we're actually trying to export (not just viewing any admin page).
		if ( ! isset( $_GET['aswc_csv_export'] ) || empty( $_GET['aswc_csv_export'] ) ) {
			return;
		}

		// SECURITY: Verify capability first.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export subscription data.', 'advanced-subscriptions-for-woocommerce' ), 403 );
		}

		// SECURITY: Verify nonce.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'aswc_export_csv' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'advanced-subscriptions-for-woocommerce' ), 403 );
		}

		$aswc_export_csv = sanitize_text_field( wp_unslash( $_GET['aswc_csv_export'] ) );
		if ( 'aswc_csv_report' === $aswc_export_csv ) {

				if ( aswc_loader_is_hpos_enabled() ) {
					$args               = array(
						'limit'      => -1,
						'return'     => 'ids',
						'post_type'  => 'aswc_subscriptions',
						'status'     => array( 'active' ),
					);
					$aswc_subscriptions = wc_get_orders( $args );
				} else {
					$args               = array(
						'numberposts' => -1,
						'post_type'   => 'aswc_subscriptions',
						'post_status' => array( 'wc-active' ),
					);
					$aswc_subscriptions = get_posts( $args );
				}

				if ( isset( $aswc_subscriptions ) && ! empty( $aswc_subscriptions ) && is_array( $aswc_subscriptions ) ) {
					foreach ( $aswc_subscriptions as $key => $value ) {

						if ( aswc_loader_is_hpos_enabled() ) {
							$subscription_id = $value;
						} else {
							$subscription_id = $value->ID;
						}
						$parent_order_id = aswc_get_meta_data( $subscription_id, 'aswc_parent_order', true );
						if ( function_exists( 'aswc_check_valid_order' ) && ! aswc_check_valid_order( $parent_order_id ) ) {
							continue;
						}
						$aswc_subscription_status = aswc_get_meta_data( $subscription_id, 'aswc_subscription_status', true );
						$product_name             = aswc_get_meta_data( $subscription_id, 'product_name', true );
						$aswc_recurring_total     = aswc_get_meta_data( $subscription_id, 'aswc_recurring_total', true );
						$aswc_next_payment_date   = aswc_get_meta_data( $subscription_id, 'aswc_next_payment_date', true );
						$aswc_susbcription_end    = aswc_get_meta_data( $subscription_id, 'aswc_susbcription_end', true );

						$aswc_customer_id = aswc_get_meta_data( $subscription_id, 'aswc_customer_id', true );
						$user             = get_user_by( 'id', $aswc_customer_id );

						$user_nicename = isset( $user->user_nicename ) ? $user->user_nicename : '';
						$content[]     = apply_filters(
							'aswc_csv_file_data',
							array(
								'subs_id'           => $subscription_id,
								'parent_order_id'   => $parent_order_id,
								'status'            => $aswc_subscription_status,
								'product_name'      => $product_name,
								'recurring_amount'  => $aswc_recurring_total,
								'username'          => $user_nicename,
								'next_payment_date' => aswc_get_the_csv_date_format( $aswc_next_payment_date ),
								'expiry_date'       => aswc_get_the_csv_date_format( $aswc_susbcription_end ),
							)
						);
					}
					$filename = 'aswc_active_report.csv';
					$title    = apply_filters(
						'aswc_subs_csv_title',
						array(
							'subs_id'           => __( 'Subscription ID', 'advanced-subscriptions-for-woocommerce' ),
							'parent_order_id'   => __( 'Parent Order ID', 'advanced-subscriptions-for-woocommerce' ),
							'status'            => __( 'Status', 'advanced-subscriptions-for-woocommerce' ),
							'product_name'      => __( 'Product Name', 'advanced-subscriptions-for-woocommerce' ),
							'recurring_amount'  => __( 'Recurring Amount', 'advanced-subscriptions-for-woocommerce' ),
							'username'          => __( 'User Name', 'advanced-subscriptions-for-woocommerce' ),
							'next_payment_date' => __( 'Next Payment Date', 'advanced-subscriptions-for-woocommerce' ),
							'expiry_date'       => __( 'Expiry Date', 'advanced-subscriptions-for-woocommerce' ),
						)
					);

					$upload_dir_path  = wp_upload_dir()['basedir'] . '/';
					$error_log_folder = 'aswc_csv_report/';

					$import_error_dir = $upload_dir_path . $error_log_folder;
					if ( ! is_dir( $import_error_dir ) ) {
						mkdir( $import_error_dir, $permissions = 0777 );
					}

					$output = fopen( $import_error_dir . $filename, 'w' );
					fputcsv( $output, $title, ',' );
					foreach ( $content as $con ) {
						fputcsv( $output, $con, ',' );
					}
					$file_name                = sanitize_text_field( $filename );
					$upload_dir_path          = wp_upload_dir()['basedir'] . '/';
					$error_log_folder         = 'aswc_csv_report/';
					$path_of_file_to_download = $upload_dir_path . $error_log_folder . $file_name;

					if ( file_exists( $path_of_file_to_download ) ) {
						header( 'Content-Description: File Transfer' );
						header( 'Content-Type: application/csv' );
						header( 'Content-Disposition: attachment; filename="' . basename( $path_of_file_to_download ) . '"' );
						header( 'Expires: 0' );
						header( 'Cache-Control: must-revalidate' );
						header( 'Pragma: public' );
						header( 'Content-Length: ' . filesize( $path_of_file_to_download ) );
						readfile( $path_of_file_to_download );
						exit;
					}
				}
			}
	}


	/**
	 * This function is used add export csv button.
	 *
	 * @name aswc_export_button_html
	 * @since 1.0.0
	 */
	public function aswc_export_button_html() {
		$export_url = admin_url( 'admin.php?page=aswc_subscriptions_for_woocommerce_menu&aswc_csv_export=aswc_csv_report' );
		$export_url = wp_nonce_url( $export_url, 'aswc_export_csv' );
		?>
		<a href="<?php echo esc_url( $export_url ); ?>" class="aswc_export_button button action" target="_blank"><?php esc_html_e( 'Export CSV', 'advanced-subscriptions-for-woocommerce' ); ?> </a>
		<?php
	}

	/**
	 * This function is used process manual renewal order.
	 *
	 * @name aswc_other_payment_gateway_renewal_order
	 * @param object $aswc_new_order aswc_new_order.
	 * @param int    $susbcription_id susbcription_id.
	 * @param string $payment_method payment_method.
	 * @since 1.0.0
	 */
	public function aswc_other_payment_gateway_renewal_order( $aswc_new_order, $susbcription_id, $payment_method ) {
		if ( aswc_enbale_accept_manual_payment() ) {
			$aswc_manual_supported_gateway = aswc_support_manual_payment();
			if ( in_array( $payment_method, $aswc_manual_supported_gateway ) ) {
				$aswc_new_order->update_status( 'pending' );
				aswc_update_order_meta( $susbcription_id, 'aswc_subscription_status', 'on-hold' );
				$order_id = $aswc_new_order->get_id();
				aswc_update_order_meta( $order_id, 'aswc_manual_renewal_order', 'pending' );

				$mailer = WC()->mailer()->get_emails();
				// Send the "renewal invoive" notification.
				if ( isset( $mailer['aswc_renewal_subscription_invoice'] ) ) {
					$mailer['aswc_renewal_subscription_invoice']->trigger( $order_id );
				}
			}
		}

		// wallet custom.
		if ( 'aswc_wcb_wallet_payment_gateway' === $payment_method ) {
			$order_id           = $aswc_new_order->get_id();
			$aswc_renewal_order = aswc_get_meta_data( $order_id, 'aswc_renewal_order', true );

			if ( 'yes' === $aswc_renewal_order ) {
				$amount                          = $aswc_new_order->get_total();
				$user_id                         = $aswc_new_order->get_user_id();
								$aswc_wallet_bal = get_user_meta( $user_id, '_aswc_wallet', true );
				if ( (float) $aswc_wallet_bal >= (float) $amount ) {

					$aswc_wallet_bal = (float) $aswc_wallet_bal - (float) $amount;
										update_user_meta( $user_id, '_aswc_wallet', $aswc_wallet_bal );

					$wallet_payment_gateway = new Wallet_System_For_Woocommerce();

					$transaction_type = __( 'Wallet Debited through Subscription Renewal', 'advanced-subscriptions-for-woocommerce' ) . ' <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" >#' . $order_id . '</a>';
					$transaction_data = array(
						'user_id'            => $user_id,
						'amount'             => $amount,
						'currency'           => $aswc_new_order->get_currency(),
						'payment_method'     => 'Subscription Renewal Payment',
						'transaction_type'   => htmlentities( $transaction_type ),
						'transaction_type_1' => 'debit',
						'order_id'           => $order_id,
						'note'               => '',
					);

					$wallet_payment_gateway->insert_transaction_data_in_table( $transaction_data );

					/* translators: %s: amount */
					$aswc_new_order->update_status( 'processing', sprintf( __( 'Payment Recieve From Customer Wallet of Amount - : %s.', 'advanced-subscriptions-for-woocommerce' ), $amount ) );
					$aswc_new_order->save();

				} else {
					$aswc_new_order->add_order_note( esc_html( ' order payment is not completed due to insufficient funds in customer wallet ', 'advanced-subscriptions-for-woocommerce' ) );
					$aswc_new_order->update_status( 'pending' );
					aswc_update_order_meta( $susbcription_id, 'aswc_subscription_status', 'on-hold' );
					aswc_update_order_meta( $order_id, 'aswc_manual_renewal_order', 'pending' );

					$mailer = WC()->mailer()->get_emails();
					// Send the "renewal invoive" notification.
					if ( isset( $mailer['aswc_renewal_subscription_invoice'] ) ) {
							$mailer['aswc_renewal_subscription_invoice']->trigger( $order_id );
					}
				}
			}
		}
		// wallet custom.
	}

	/**
	 * This function is used to add renewal sync field.
	 *
	 * @name aswc_product_edit_renewal_on_certain_date
	 * @param int $product_id product_id.
	 * @since 1.0.0
	 */
	public function aswc_product_edit_renewal_on_certain_date( $product_id ) {

		if ( aswc_allow_start_date_subscription() ) {
			$aswc_subscription_start_date = aswc_get_meta_data( $product_id, 'aswc_subscription_start_date', true );
			?>
			<p class="form-field aswc_subscription_start_date_field ">
			<label for="aswc_subscription_start_date">
			<?php esc_html_e( 'Choose Subscription Start Date', 'advanced-subscriptions-for-woocommerce' ); ?>
			</label>
			<input type="text" class="short wc_input_text aswc_subscription_start_date" name="aswc_subscription_start_date" value="<?php echo esc_attr( $aswc_subscription_start_date ); ?>" id="aswc_subscription_start_date"  placeholder="<?php esc_html_e( 'Enter start date', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
			<?php
			$description_text = __( 'Choose subscription start date"', 'advanced-subscriptions-for-woocommerce' );
			echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
		</p>
			<?php
		}
		if ( ! aswc_start_susbcription_from_certain_date_of_month() ) {
			return;
		}
		$aswc_year_number = aswc_get_meta_data( $product_id, 'aswc_year_number', true );
		if ( empty( $aswc_year_number ) ) {
			$aswc_year_number = 1;
		}
		$aswc_billing_period   = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );
		$aswc_week_sync_value  = aswc_get_meta_data( $product_id, 'aswc_week_sync', true );
		$aswc_month_sync_value = aswc_get_meta_data( $product_id, 'aswc_month_sync', true );
		$aswc_year_sync_value  = aswc_get_meta_data( $product_id, 'aswc_year_sync', true );

		if ( empty( $aswc_billing_period ) ) {
			$aswc_billing_period = 'day';
		}

		$aswc_show_week = ( 'week' === $aswc_billing_period ) ? '' : 'display: none;';

		$aswc_show_month = ( 'month' === $aswc_billing_period ) ? '' : 'display: none;';

		$aswc_show_year = ( 'year' === $aswc_billing_period ) ? '' : 'display: none;';

		$aswc_show_week_month_year_check = ( ! in_array( $aswc_billing_period, array( 'month', 'week', 'year' ) ) ) ? 'display: none;' : '';

		?>
		<div class="form-field aswc_certain_date_enable" style="<?php echo esc_attr( $aswc_show_week_month_year_check ); ?>">
		<?php
		woocommerce_wp_checkbox(
			array(
				'id'                => 'aswc_enbale_certain_month',
				'class'             => 'aswc_enbale_certain_month',
				'label'             => __( 'Enable subscription renewal on certain day/ date', 'advanced-subscriptions-for-woocommerce' ),
				'name'              => 'aswc_enbale_certain_month',
				'custom_attributes' => array( 'aswc_billing_period' => $aswc_billing_period ),
			)
		);
		?>
		</div>
		<div class="aswc_certain_date_enable_wrap">
			<div class="form-field aswc_certain_date_enable_week" style="<?php echo esc_attr( $aswc_show_week ); ?>">
			<?php
			woocommerce_wp_select(
				array(
					'id'      => 'aswc_week_sync',
					'class'   => 'select short',
					'label'   => __( 'Week for Synchronisation', 'advanced-subscriptions-for-woocommerce' ),
					'options' => aswc_subscription_week_period(),
					'value'   => $aswc_week_sync_value,
				)
			);
			?>
			</div>
			<div class="form-field aswc_certain_date_enable_month" style="<?php echo esc_attr( $aswc_show_month ); ?>">
			<?php
			woocommerce_wp_select(
				array(
					'id'      => 'aswc_month_sync',
					'class'   => 'select short',
					'label'   => __( 'Month for Synchronisation', 'advanced-subscriptions-for-woocommerce' ),
					'options' => aswc_subscription_month_period(),
					'value'   => $aswc_month_sync_value,
				)
			);
			?>
			</div>
			<div class="form-field aswc_certain_date_enable_year" style="<?php echo esc_attr( $aswc_show_year ); ?>">

				<p class="aswc_year_sync_label"><?php esc_html_e( 'Year for Synchronisation', 'advanced-subscriptions-for-woocommerce' ); ?></p>
				<select id="aswc_year_sync" name="aswc_year_sync" class="select short aswc_year_sync" >
					<?php foreach ( aswc_subscription_syn_year_period() as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_year_sync_value, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
				</select>

			<?php

						$aswc_current_time                       = current_time( 'timestamp' );
											$aswc_max_no_of_days = aswc_date( 't', $aswc_current_time );
			?>
			<input type="number" class="short wc_input_number"  min="1" max="<?php echo esc_attr( $aswc_max_no_of_days ); ?>" name="aswc_certain_date_enable_year_number" id="aswc_certain_date_enable_year_number" value="<?php echo esc_attr( $aswc_year_number ); ?>">
			</div>
		</div>
		<?php
	}

	/**
	 * This function is used to save field.
	 *
	 * @name aswc_save_simple_subscription_field
	 * @param int   $product_id product_id.
	 * @param array $post post.
	 * @since 1.0.0
	 */
	public function aswc_save_simple_subscription_field( $product_id, $post ) {

		// one time purchase setting save.
		$aswc_one_time_purchase = isset( $_POST['aswc_one_time_purchase'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_one_time_purchase'] ) ) : '';
		aswc_update_order_meta( $product_id, 'aswc_one_time_purchase', $aswc_one_time_purchase );

		$aswc_onetime_price = isset( $_POST['aswc_subscription_one_time_price'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_one_time_price'] ) ) : '';
		aswc_update_order_meta( $product_id, 'aswc_onetime_price', $aswc_onetime_price );
		// one time purchase setting save.

		// saving free trial limit.

		$aswc_limt_for_free_trial = isset( $_POST['aswc_subscription_limit_for_trial'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_limit_for_trial'] ) ) : '';
		aswc_update_order_meta( $product_id, 'aswc_limt_for_free_trial', $aswc_limt_for_free_trial );

		$aswc_free_trails_limit_checkbox = isset( $_POST['aswc_free_trails_limit_checkbox'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_free_trails_limit_checkbox'] ) ) : '';
		aswc_update_order_meta( $product_id, 'aswc_free_trails_limit_checkbox', $aswc_free_trails_limit_checkbox );

		// saving free trial limit.

		if ( aswc_allow_start_date_subscription() ) {
			$aswc_subscription_start_date = ( ! empty( $_POST['aswc_subscription_start_date'] ) || isset( $_POST['aswc_subscription_start_date'] ) ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_start_date'] ) ) : '';
			aswc_update_order_meta( $product_id, 'aswc_subscription_start_date', $aswc_subscription_start_date );
		}

		if ( ! aswc_start_susbcription_from_certain_date_of_month() ) {
			return;
		}
		if ( ! isset( $_POST['aswc_edit_nonce_filed'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aswc_edit_nonce_filed'] ) ), 'aswc_edit_nonce' ) ) {
			return;
		}

		$aswc_enbale_certain_month = isset( $_POST['aswc_enbale_certain_month'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_enbale_certain_month'] ) ) : 'no';

		$aswc_week_sync = isset( $_POST['aswc_week_sync'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_week_sync'] ) ) : '';

		$aswc_month_sync = isset( $_POST['aswc_month_sync'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_month_sync'] ) ) : '';

		$aswc_year_sync = isset( $_POST['aswc_year_sync'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_year_sync'] ) ) : '';

		$aswc_year_number = isset( $_POST['aswc_certain_date_enable_year_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_certain_date_enable_year_number'] ) ) : 1;
		if ( empty( $aswc_year_number ) ) {
			$aswc_year_number = 1;
		}
		aswc_update_order_meta( $product_id, 'aswc_enbale_certain_month', $aswc_enbale_certain_month );
		aswc_update_order_meta( $product_id, 'aswc_week_sync', $aswc_week_sync );
		aswc_update_order_meta( $product_id, 'aswc_month_sync', $aswc_month_sync );
		aswc_update_order_meta( $product_id, 'aswc_year_sync', $aswc_year_sync );
		aswc_update_order_meta( $product_id, 'aswc_year_number', $aswc_year_number );
	}


	/**
	 * Add Coupon type settings.
	 *
	 * @name aswc_additional_coupon_setting
	 * @param array $settings settings.
	 * @since 1.0.0
	 */
	public function aswc_additional_coupon_setting( $settings ) {
		$additional_other_settings = array(
			array(
				'title'    => __( 'Enable subscription coupon type', 'advanced-subscriptions-for-woocommerce' ),
				'id'       => 'aswc_wgm_addition_subscription_coupon_option_enable',
				'type'     => 'checkbox',
				'class'    => 'input-text',
				'desc_tip' => __( 'Enable subscription coupon type', 'advanced-subscriptions-for-woocommerce' ),
				'desc'     => __( 'Enable subscription coupon type', 'advanced-subscriptions-for-woocommerce' ),
			),
			array(
				'title'            => __( 'Select coupon type', 'advanced-subscriptions-for-woocommerce' ),
				'id'               => 'aswc_wgm_addition_subscription_coupon_type',
				'type'             => 'singleSelectDropDownWithKeyvalue',
				'class'            => 'input-text',
				'desc_tip'         => __( 'Select coupon type', 'advanced-subscriptions-for-woocommerce' ),
				'custom_attribute' => array(
					array(
						'id'   => 'fixed_cart',
						'name' => __( 'Fixed cart discount( Gift card )', 'advanced-subscriptions-for-woocommerce' ),
					),
				),
			),
		);
			return ( array_merge( $settings, $additional_other_settings ) );
	}


	/**
	 * This function is used to custom field compatibility with WPML.
	 *
	 * @name aswc_add_lock_custom_fields_pro.
	 * @since 1.0.0
	 * @param array $ids ids.
	 */
	public function aswc_add_lock_custom_fields_pro( $ids ) {

		$ids[] = 'aswc_enbale_certain_month';
		$ids[] = 'aswc_week_sync';
		$ids[] = 'aswc_month_sync';
		$ids[] = 'aswc_year_sync';
		$ids[] = 'aswc_variation_enable';
		$ids[] = 'aswc_variation_subscription_number';
		$ids[] = 'aswc_variation_subscription_interval';
		$ids[] = 'aswc_variation_subscription_initial_signup_price';
		$ids[] = 'aswc_variation_subscription_free_trial_number';
		$ids[] = 'aswc_variation_subscription_free_trial_interval';
		$ids[] = 'aswc_variation_enbale_certain_month';
		$ids[] = 'aswc_variation_week_sync';
		$ids[] = 'aswc_variation_month_sync';
		$ids[] = 'aswc_variation_year_sync';
		$ids[] = 'aswc_variation_certain_date_enable_year_number';

		return apply_filters( 'aswc_add_lock_fields', $ids );
	}

	/**
	 * This function is used to show notice to activate the wallet plugin.
	 *
	 * @return void
	 */
	public function aswc_wallet_activation_notice() {
		if ( ! in_array( 'wallet-system-for-woocommerce/wallet-system-for-woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
						$aswc_enable = get_option( 'aswc_enbale_downgrade_upgrade_subscription', '' );
			if ( 'on' === $aswc_enable ) {
				?>
				<div class="notice notice-warning">
					<p>
						<strong><?php esc_html_e( 'Please Activate', 'advanced-subscriptions-for-woocommerce' ); ?>
						<a href="https://wordpress.org/plugins/wallet-system-for-woocommerce/"><?php esc_html_e( 'Wallet System For Woocommerce', 'advanced-subscriptions-for-woocommerce' ); ?></a>
				<?php esc_html_e( 'to manage prorate amount while downgrade', 'advanced-subscriptions-for-woocommerce' ); ?>
					</strong>
					</p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Function name aswc_cancel_recurring_payment.
	 * this function is used to cancel the recurring payment.
	 *
	 * @return void
	 */
	public function aswc_cancel_recurring_payment() {

			check_ajax_referer( 'aswc_public_nonce', 'nonce' );

		$aswc_subscription_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $aswc_subscription_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid subscription ID.', 'advanced-subscriptions-for-woocommerce' ) ) );
		}

		// SECURITY: Verify subscription exists.
		$subscription = wc_get_order( $aswc_subscription_id );
		if ( ! $subscription ) {
			wp_send_json_error( array( 'message' => __( 'Subscription not found.', 'advanced-subscriptions-for-woocommerce' ) ) );
		}

		// SECURITY: Verify ownership - user must own this subscription or be admin.
		$current_user_id = get_current_user_id();
		$subscription_customer_id = $subscription->get_customer_id();

		if ( $current_user_id !== $subscription_customer_id && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to cancel this subscription.', 'advanced-subscriptions-for-woocommerce' ) ) );
		}

		// Update subscription status.
		aswc_update_order_meta( $aswc_subscription_id, 'aswc_user_cancelled_recurring', 'yes' );

		// Log the action for audit trail.
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( 'User %d cancelled recurring payment for subscription %d', $current_user_id, $aswc_subscription_id ) );
		}

		wp_send_json_success( array( 'message' => __( 'Recurring payment cancelled successfully.', 'advanced-subscriptions-for-woocommerce' ) ) );
	}

		/**
		 * Display related orders for a subscription.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post|WC_Order|null $post_obj Current post or order object.
		 * @return void
		 */
	public function aswc_related_orders_meta_box( $post_obj = null ) {
		if ( null === $post_obj ) {
				global $post;
				$post_obj = $post;
		}

		if ( empty( $post_obj ) ) {
					return;
		}

			$is_renewal = false;
			$post_id    = 0;
			$post_type  = '';

			// Support both WP_Post and WC_Order objects.
		if ( is_object( $post_obj ) && isset( $post_obj->ID ) ) {
					$post_id   = $post_obj->ID;
					$post_type = isset( $post_obj->post_type ) ? $post_obj->post_type : '';
		} elseif ( is_object( $post_obj ) && method_exists( $post_obj, 'get_id' ) ) {
				$post_id   = $post_obj->get_id();
				$post_type = method_exists( $post_obj, 'get_type' ) ? $post_obj->get_type() : '';
		}

		if ( ! $post_id ) {
				return;
		}

				$subscription_id = $post_id;

		if ( 'shop_order' === $post_type ) {
				$aswc_subscription = aswc_get_meta_data( $post_id, 'aswc_subscription', true );
				$is_renewal        = 'yes' === aswc_get_meta_data( $post_id, 'aswc_renewal_order', true );
			if ( $aswc_subscription ) {
					$subscription_id = (int) $aswc_subscription;
			}
		}

		if ( ! $subscription_id ) {
				return;
		}

				$parent_order_id = aswc_get_meta_data( $subscription_id, 'aswc_parent_order', true );
				$renewal_orders  = aswc_get_meta_data( $subscription_id, 'aswc_renewal_order_data', true );
		if ( ! is_array( $renewal_orders ) ) {
				$renewal_orders = array();
		}

				echo '<div class="woocommerce_subscriptions_related_orders">';
				echo '<table class="widefat fixed">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<thead><tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<th>' . esc_html__( 'Order Number', 'advanced-subscriptions-for-woocommerce' ) . '</th>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<th>' . esc_html__( 'Relationship', 'advanced-subscriptions-for-woocommerce' ) . '</th>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<th>' . esc_html__( 'Date', 'advanced-subscriptions-for-woocommerce' ) . '</th>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<th>' . esc_html__( 'Status', 'advanced-subscriptions-for-woocommerce' ) . '</th>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<th>' . esc_html__( 'Total', 'advanced-subscriptions-for-woocommerce' ) . '</th>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</tr></thead><tbody>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $is_renewal && $subscription_id ) {
				$subscription = aswc_get_subscription( $subscription_id );
			if ( $subscription ) {
					$link = admin_url( 'post.php?post=' . $subscription_id . '&action=edit' );
					echo '<tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<td><a href="' . esc_url( $link ) . '">#' . esc_html( $subscription_id ) . '</a></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<td>' . esc_html__( 'Subscription', 'advanced-subscriptions-for-woocommerce' ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$timestamp = $subscription->get_date_created() ? $subscription->get_date_created()->getTimestamp() : 0;
					echo '<td><abbr title="' . esc_attr( aswc_date( 'd F, Y g:i a', $timestamp ) ) . '">' . esc_html( human_time_diff( $timestamp, current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'advanced-subscriptions-for-woocommerce' ) . '</abbr></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<td><mark class="order-status status-' . esc_attr( $subscription->get_status() ) . '"><span>' . esc_html( wc_get_order_status_name( $subscription->get_status() ) ) . '</span></mark></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<td>' . wp_kses_post( $subscription->get_formatted_order_total() ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '</tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		if ( $parent_order_id ) {
					$order = wc_get_order( $parent_order_id );
			if ( $order ) {
						$link = admin_url( 'post.php?post=' . $parent_order_id . '&action=edit' );
				if ( aswc_loader_is_hpos_enabled() ) {
					$link = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $parent_order_id );
				}
						echo '<tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<td><a href="' . esc_url( $link ) . '">#' . esc_html( $parent_order_id ) . '</a></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<td>' . esc_html__( 'Parent Order', 'advanced-subscriptions-for-woocommerce' ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
																					echo '<td><abbr title="' . esc_attr( aswc_date( 'd F, Y g:i a', $order->get_date_created()->getTimestamp() ) ) . '">' . esc_html( human_time_diff( $order->get_date_created()->getTimestamp(), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'advanced-subscriptions-for-woocommerce' ) . '</abbr></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<td><mark class="order-status status-' . esc_attr( $order->get_status() ) . '"><span>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</span></mark></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<td>' . wp_kses_post( $order->get_formatted_order_total() ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '</tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		foreach ( $renewal_orders as $order_id ) {
				$order = wc_get_order( $order_id );
			if ( ! $order ) {
					continue;
			}
				$link = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
			if ( aswc_loader_is_hpos_enabled() ) {
					$link = admin_url( 'admin.php?page=wc-orders&action=edit&id=' . $order_id );
			}
				echo '<tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<td><a href="' . esc_url( $link ) . '">#' . esc_html( $order_id ) . '</a></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<td>' . esc_html__( 'Renewal Order', 'advanced-subscriptions-for-woocommerce' ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													echo '<td><abbr title="' . esc_attr( aswc_date( 'd F, Y g:i a', $order->get_date_created()->getTimestamp() ) ) . '">' . esc_html( human_time_diff( $order->get_date_created()->getTimestamp(), current_time( 'timestamp' ) ) ) . ' ' . esc_html__( 'ago', 'advanced-subscriptions-for-woocommerce' ) . '</abbr></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<td><mark class="order-status status-' . esc_attr( $order->get_status() ) . '"><span>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</span></mark></td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<td>' . wp_kses_post( $order->get_formatted_order_total() ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

				echo '</tbody></table></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Add onetime purchase field in the product in the backend
	 *
	 * @return void
	 */
	public function aswc_custom_product_fields_for_onetime_purchase_subscription() {
		global $post;
		$post_id = $post->ID;
		$product = wc_get_product( $post_id );

		$aswc_onetime_price     = aswc_get_meta_data( $post_id, 'aswc_onetime_price', true );
		$aswc_one_time_purchase = aswc_get_meta_data( $post_id, 'aswc_one_time_purchase', true );

		$aswc_limt_for_free_trial        = aswc_get_meta_data( $post_id, 'aswc_limt_for_free_trial', true );
		$aswc_free_trails_limit_checkbox = aswc_get_meta_data( $post_id, 'aswc_free_trails_limit_checkbox', true );
		?>
		<div class="aswc_one_time_purchase-wrap">
		<span><?php esc_html_e( 'Enable Free Trials Limit', 'advanced-subscriptions-for-woocommerce' ); ?></span>
		<input type="checkbox" class="checkbox aswc_free_trials_limit" name="aswc_free_trails_limit_checkbox" <?php checked( $aswc_free_trails_limit_checkbox, 'on' ); ?> />
		</div>
		<p class="form-field">
			<label for="aswc_subscription_free_trails">
			<?php
			esc_html_e( 'Limit Value', 'advanced-subscriptions-for-woocommerce' );
			?>
			</label>
			<input type="number" class="short wc_input_limit"  min="1" step="any" data-prod_id="<?php echo esc_html( $post_id ); ?>" name="aswc_subscription_limit_for_trial" id="aswc_subscription_limit_for_trial" value="<?php echo esc_html( $aswc_limt_for_free_trial ); ?>" placeholder="<?php esc_html_e( 'Enter Limit Value', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
		<?php
			$description_text = __( 'If the Subscription Cancellation Exceed the Set Limit, User Will Not Able To Cancel Their Subscription', 'advanced-subscriptions-for-woocommerce' );
			echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
		?>
		</p>
			
		<div class="aswc_one_time_purchase-wrap">
		<span><?php esc_html_e( 'Enable one-time purchase', 'advanced-subscriptions-for-woocommerce' ); ?></span>
		<input type="checkbox" class="checkbox aswc_one_time_purchase" name="aswc_one_time_purchase" <?php checked( $aswc_one_time_purchase, 'on' ); ?> />
		</div>
		<p class="form-field">
			<label for="aswc_subscription_onetime_purchase_price">
			<?php
			esc_html_e( 'One Time Purchase Price', 'advanced-subscriptions-for-woocommerce' );
			echo esc_html( '(' . get_woocommerce_currency_symbol() . ')' );
			?>
			</label>
			<input type="number" class="short wc_input_price"  min="1" step="any" data-prod_id="<?php echo esc_html( $post_id ); ?>" name="aswc_subscription_one_time_price" id="aswc_subscription_one_time_price" value="<?php echo esc_html( $aswc_onetime_price ); ?>" placeholder="<?php esc_html_e( 'Enter One Time Purchase Price', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
		<?php
			$description_text = __( 'Please enter the One Time Purchase amount and make sure you have set the one time purchase amount is greater than subscription price otherwise this will not work', 'advanced-subscriptions-for-woocommerce' );
			echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
		?>
		<p><i><?php esc_html_e( 'Make sure you have set the one time purchase amount is greater than subscription price otherwise this will not work', 'advanced-subscriptions-for-woocommerce' ); ?></i></p>
		</p>
		<?php
	}

	/**
	 * Change the plugin title for Pro
	 *
	 * @param string $plugin_title as plugin title.
	 * @return string
	 */
	public function aswc_dashboard_plugin_title_callback( $plugin_title ) {
			return 'AWC Subscriptions For WooCommerce';
	}

	/**
	 * Function To Add Manual Subscription Button.
	 *
	 * @return void
	 */
	public function aswc_add_button_manual_subscription_callback() {
		?>
				<a href="<?php echo esc_url( admin_url( '/post-new.php?post_type=aswc_subscriptions' ) ); ?>" class="aswc_add_subscription_button button action" target="_blank"><?php esc_html_e( 'Add Manual Subscription', 'advanced-subscriptions-for-woocommerce' ); ?> </a>
		<?php
	}

	/**
	 * Function to List parebt order for manual subscription.
	 *
	 * @param array $order as order.
	 * @return void
	 */
	public function aswc_add_dropdown_for_manual_subscription_parent_order( $order ) {

		$screen = get_current_screen();

		if ( 'aswc_subscriptions' === $screen->id || 'woocommerce_page_wc-orders--aswc_subscriptions' === $screen->id ) {
			$order_id          = $order->get_id();
			$aswc_parent_order = aswc_get_meta_data( $order_id, 'aswc_parent_order', true );

			?>
			<p class="form-field form-field-wide">
			<?php
			if ( ! empty( $aswc_parent_order ) ) {

				?>
								<label for="parent-order-id"><?php printf( '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">#%3$s</a>', esc_html__( 'Parent order:', 'advanced-subscriptions-for-woocommerce' ), esc_url( aswc_get_edit_post_link( absint( $aswc_parent_order ) ) ), esc_html( absint( $aswc_parent_order ) ) ); ?></label>
								<?php

			} else {

				?>
				<label for="parent-order-id"><?php esc_html_e( 'Parent order:', 'advanced-subscriptions-for-woocommerce' ); ?> </label>
				<?php
				echo '<select id="aswc_parent_order_selection" name="aswc_parent_order" required>';
				echo '<option value="">Select an order</option>';
				echo '</select>';
			}
			?>

			</p>


			<?php
		}
	}

	/**
	 * Function To Default order status for manual subscription.
	 *
	 * @param array $statuses as status.
	 * @return array
	 */
	public function aswc_remove_default_status_manual_subscription( $statuses ) {

		// Check if we're on a subscription edit/create screen.
		// For CPT: post_type=aswc_subscriptions
		// For HPOS: page=wc-orders--aswc_subscriptions
		$is_subscription_screen = false;

		if ( isset( $_GET['post_type'] ) && 'aswc_subscriptions' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) {
			$is_subscription_screen = true;
		}

		if ( isset( $_GET['page'] ) && 'wc-orders--aswc_subscriptions' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			$is_subscription_screen = true;
		}

		// Also check for HPOS edit screen with 'id' parameter.
		if ( isset( $_GET['page'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'wc-orders' ) !== false &&
		     isset( $_GET['id'] ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( absint( $_GET['id'] ) );
			if ( $order && method_exists( $order, 'get_type' ) && 'aswc_subscriptions' === $order->get_type() ) {
				$is_subscription_screen = true;
			}
		}

		if ( $is_subscription_screen ) {
			// Remove all WooCommerce order statuses - subscriptions only use subscription-specific statuses.
			unset( $statuses['wc-completed'] );
			unset( $statuses['wc-pending'] );
			unset( $statuses['wc-on-hold'] );
			unset( $statuses['wc-cancelled'] );
			unset( $statuses['wc-refunded'] );
			unset( $statuses['wc-failed'] );
			unset( $statuses['wc-processing'] );

			// Add subscription-specific statuses only.
			$subscription_statuses = apply_filters( 'aswc_status_array', array() );

			foreach ( $subscription_statuses as $status ) {
				$key              = 'wc-' . str_replace( '_', '-', $status );
				$statuses[ $key ] = ucfirst( $status );
			}
		}

		return $statuses;
	}

	/**
	 * Function to show parent order Manual Subscription order.
	 *
	 * @return void
	 */
	public function aswc_show_parent_order_for_custom_manual_callback() {

		check_ajax_referer( 'aswc_admin_nonce', 'nonce' );

		// SECURITY: Only administrators can view customer orders.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'advanced-subscriptions-for-woocommerce' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID.', 'advanced-subscriptions-for-woocommerce' ) ) );
		}

		$customer_orders = wc_get_orders(
			array(
				'customer' => $user_id,
				'status'   => array( 'wc-completed', 'wc-processing' ),
				'limit'    => -1,
			)
		);

		$html = '';
		if ( empty( $customer_orders ) ) {
			$html .= '<option value=""> ' . esc_html__( 'No Order Exist For Selected Customer', 'advanced-subscriptions-for-woocommerce' ) . ' </option>';
		} else {

			foreach ( $customer_orders as $order ) {
				$payment_method_title = $order->get_payment_method();
				if ( 'stripe' === $payment_method_title ) {
					$html .= '<option value="' . esc_attr( absint( $order->get_id() ) ) . '">' . esc_html( absint( $order->get_id() ) ) . '</option>';
				}
			}
		}

		wp_send_json_success( array( 'html' => $html ) );
	}

		/**
		 * Add meta boxes for manual subscriptions.
		 *
		 * The first parameter can be either the post type string or the post
		 * object depending on the hook fired. Handle both scenarios to ensure
		 * compatibility with HPOS screens.
		 *
		 * @param mixed $post_type_or_post Post type slug or post object.
		 * @param mixed $post              Optional. Post object when available.
		 * @return void
		 */
	public function aswc_add_meta_boxes( $post_type_or_post, $post = null ) {

							$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'aswc_subscriptions' ) : 'aswc_subscriptions';

			$post_type = '';
		if ( is_string( $post_type_or_post ) ) {
				$post_type = $post_type_or_post;
		} elseif ( is_object( $post_type_or_post ) && isset( $post_type_or_post->post_type ) ) {
					$post_type = $post_type_or_post->post_type;
		} elseif ( is_object( $post ) && isset( $post->post_type ) ) {
				$post_type = $post->post_type;
		}

		if ( is_object( $post ) && ! isset( $post->post_type ) && method_exists( $post, 'get_type' ) ) {
			$post_type = $post->get_type();
		}

			$order_id = 0;
		if ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$order_id = absint( wp_unslash( $_GET['id'] ) );
		} elseif ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$order_id = absint( wp_unslash( $_GET['post'] ) );
		} elseif ( is_object( $post ) && method_exists( $post, 'get_id' ) ) {
				$order_id = $post->get_id();
		}

		if ( 'aswc_subscriptions' === $post_type ) {
				add_meta_box(
					'aswc-subscriptions-meta-box',
					_x( 'Subscriptions Schedule Data', 'meta box title', 'advanced-subscriptions-for-woocommerce' ),
					array( $this, 'aswc_subscriptions_order_meta_box_data' ),
					$screen,
					'side',
					'high'
				);
				add_meta_box(
					'aswc-related-orders-meta-box',
					_x( 'Related Orders', 'meta box title', 'advanced-subscriptions-for-woocommerce' ),
					array( $this, 'aswc_related_orders_meta_box' ),
					$screen,
					'normal',
					'default'
				);
		}

		if ( $order_id && 'shop_order' === $post_type ) {
				$subscription_id = aswc_get_meta_data( $order_id, 'aswc_subscription', true );
			if ( $subscription_id ) {
					$order_screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
					add_meta_box(
						'aswc-related-orders-meta-box',
						_x( 'Related Orders', 'meta box title', 'advanced-subscriptions-for-woocommerce' ),
						array( $this, 'aswc_related_orders_meta_box' ),
						$order_screen,
						'normal',
						'default'
					);
			}
		}
	}

	/**
	 * Function To show Html in Metabox for Manual subscription.
	 *
	 * @return void
	 */
	public function aswc_subscriptions_order_meta_box_data() {

		$post_id = 0;
		if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = absint( $_GET['post'] );
		} elseif ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = absint( $_GET['id'] );
		}

		$aswc_subscription_number = aswc_get_meta_data( $post_id, 'aswc_subscription_number', true );
		if ( empty( $aswc_subscription_number ) ) {
			$aswc_subscription_number = 1;
		}
		$aswc_subscription_interval = aswc_get_meta_data( $post_id, 'aswc_subscription_interval', true );
		if ( empty( $aswc_subscription_interval ) ) {
			$aswc_subscription_interval = 'day';
		}
				$aswc_subscription_expiry_number   = aswc_get_meta_data( $post_id, 'aswc_subscription_expiry_number', true );
				$aswc_subscription_expiry_interval = aswc_get_meta_data( $post_id, 'aswc_subscription_expiry_interval', true );
		if ( empty( $aswc_subscription_expiry_interval ) ) {
				$aswc_subscription_expiry_interval = 'day';
		}

				wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
		?>
								<div id="woocommerce-subscription-schedule" class="postbox">
												<div class="inside">
																<div class="wc-metaboxes-wrapper">

										<div id="billing-schedule">
												<div class="billing-schedule-edit wcs-date-input">
														<p class="form-field _billing_interval_field">
																<label for="aswc_subscription_number"><?php esc_html_e( 'Payment:', 'advanced-subscriptions-for-woocommerce' ); ?></label>
																<input type="text" id="aswc_subscription_number" name="aswc_subscription_number" class="billing_interval" value="<?php echo esc_attr( $aswc_subscription_number ); ?>" />
														</p>
														<p class="form-field _billing_period_field">
																<label for="aswc_subscription_interval"><?php esc_html_e( 'Billing Period', 'advanced-subscriptions-for-woocommerce' ); ?></label>
																<select id="aswc_subscription_interval" name="aswc_subscription_interval" class="billing_period">
		<?php foreach ( aswc_subscription_period() as $value => $label ) { ?>
																		<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_subscription_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
		<?php } ?>
																</select>
														</p>
												</div>
										</div>
		<?php
										$start_date   = (int) aswc_get_meta_data( $post_id, 'aswc_schedule_start', true );
										$trial_end    = (int) aswc_get_meta_data( $post_id, 'aswc_susbcription_trial_end', true );
										$next_payment = (int) aswc_get_meta_data( $post_id, 'aswc_next_payment_date', true );
										$end_date     = (int) aswc_get_meta_data( $post_id, 'aswc_susbcription_end', true );
		?>
										<div id="subscription-start-date" class="date-fields">
												<strong><?php esc_html_e( 'Start Date:', 'advanced-subscriptions-for-woocommerce' ); ?></strong>
								<div class="wcs-date-input"><input type="text" class="date-picker woocommerce-subscriptions" placeholder="YYYY-MM-DD" name="start_date" id="start_date" maxlength="10" value="<?php echo esc_attr( 0 !== $start_date ? gmdate( 'Y-m-d', $start_date ) : '' ); ?>" pattern="([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])" />@<input type="text" class="hour" placeholder="HH" name="start_hour" id="start_hour" value="<?php echo esc_attr( 0 !== $start_date ? gmdate( 'H', $start_date ) : '' ); ?>" maxlength="2" size="2" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:<input type="text" class="minute" placeholder="MM" name="start_minute" id="start_minute" value="<?php echo esc_attr( 0 !== $start_date ? gmdate( 'i', $start_date ) : '' ); ?>" maxlength="2" size="2" pattern="[0-5]{1}[0-9]{1}" /></div>
						</div>
										<div id="subscription-trial_end-date" class="date-fields">
												<strong><?php esc_html_e( 'Trial End:', 'advanced-subscriptions-for-woocommerce' ); ?></strong>
												<input type="hidden" name="trial_end_timestamp_utc" id="trial_end_timestamp_utc" value="<?php echo esc_attr( $trial_end ); ?>" />
												<div class="wcs-date-input"><input type="text" class="date-picker woocommerce-subscriptions" placeholder="YYYY-MM-DD" name="trial_end" id="trial_end" maxlength="10" value="<?php echo esc_attr( 0 !== $trial_end ? gmdate( 'Y-m-d', $trial_end ) : '' ); ?>" pattern="([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])" />@<input type="text" class="hour" placeholder="HH" name="trial_end_hour" id="trial_end_hour" value="<?php echo esc_attr( 0 !== $trial_end ? gmdate( 'H', $trial_end ) : '' ); ?>" maxlength="2" size="2" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:<input type="text" class="minute" placeholder="MM" name="trial_end_minute" id="trial_end_minute" value="<?php echo esc_attr( 0 !== $trial_end ? gmdate( 'i', $trial_end ) : '' ); ?>" maxlength="2" size="2" pattern="[0-5]{1}[0-9]{1}" /></div>
										</div>
										<div id="subscription-next_payment-date" class="date-fields">
												<strong><?php esc_html_e( 'Next Payment:', 'advanced-subscriptions-for-woocommerce' ); ?></strong>
												<input type="hidden" name="next_payment_timestamp_utc" id="next_payment_timestamp_utc" value="<?php echo esc_attr( $next_payment ); ?>" />
												<div class="wcs-date-input"><input type="text" class="date-picker woocommerce-subscriptions" placeholder="YYYY-MM-DD" name="next_payment" id="next_payment" maxlength="10" value="<?php echo esc_attr( 0 !== $next_payment ? gmdate( 'Y-m-d', $next_payment ) : '' ); ?>" pattern="([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])" />@<input type="text" class="hour" placeholder="HH" name="next_payment_hour" id="next_payment_hour" value="<?php echo esc_attr( 0 !== $next_payment ? gmdate( 'H', $next_payment ) : '' ); ?>" maxlength="2" size="2" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:<input type="text" class="minute" placeholder="MM" name="next_payment_minute" id="next_payment_minute" value="<?php echo esc_attr( 0 !== $next_payment ? gmdate( 'i', $next_payment ) : '' ); ?>" maxlength="2" size="2" pattern="[0-5]{1}[0-9]{1}" /></div>
										</div>
										<div id="subscription-end-date" class="date-fields">
												<strong><?php esc_html_e( 'End Date:', 'advanced-subscriptions-for-woocommerce' ); ?></strong>
												<input type="hidden" name="end_timestamp_utc" id="end_timestamp_utc" value="<?php echo esc_attr( $end_date ); ?>" />
												<div class="wcs-date-input"><input type="text" class="date-picker woocommerce-subscriptions" placeholder="YYYY-MM-DD" name="end" id="end" maxlength="10" value="<?php echo esc_attr( 0 !== $end_date ? gmdate( 'Y-m-d', $end_date ) : '' ); ?>" pattern="([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])" />@<input type="text" class="hour" placeholder="HH" name="end_hour" id="end_hour" value="<?php echo esc_attr( 0 !== $end_date ? gmdate( 'H', $end_date ) : '' ); ?>" maxlength="2" size="2" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:<input type="text" class="minute" placeholder="MM" name="end_minute" id="end_minute" value="<?php echo esc_attr( 0 !== $end_date ? gmdate( 'i', $end_date ) : '' ); ?>" maxlength="2" size="2" pattern="[0-5]{1}[0-9]{1}" /></div>
										</div>
										<p><?php esc_html_e( 'Timezone:', 'advanced-subscriptions-for-woocommerce' ); ?> <span id="wcs-timezone"><?php echo esc_html( wp_timezone_string() ); ?></span></p>
										<?php
										// Mostrar estado de reintentos bajo "Timezone".
										if ( method_exists( __CLASS__, 'aswc_get_retry_status_text' ) ) {
											echo '<p>' . esc_html( self::aswc_get_retry_status_text( $post_id ) ) . '</p>';
										}
										?>
								</div>
						</div>
				</div>
				<?php
	}

	/**
	 * Function To save and create manual subscription.
	 *
	 * @param object $post_id as post id.
	 * @param object $post as post.
	 * @return void
	 */
	public function aswc_save_manual_subscription_order_details( $post_id, $post ) {

		if ( ! function_exists( 'get_current_screen' ) ) {
				return;
		}
			$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return;
		}
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( 'aswc_subscriptions' === $screen->id && $_POST ) {
				ASWC_Log::log( 'Saving manual subscription for ID: ' . $post_id );

			$aswc_parent_order = aswc_get_meta_data( $post_id, 'aswc_parent_order', true );
			// check subscription already created so no further saving need.
			if ( empty( $aswc_parent_order ) ) {

				$aswc_parent_order                 = isset( $_POST['aswc_parent_order'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_parent_order'] ) ) : '';
				$parent_order                      = wc_get_order( $aswc_parent_order );

				// If parent order doesn't exist, skip the manual subscription creation.
				if ( ! $parent_order ) {
					return;
				}
				$aswc_subscription_number          = isset( $_POST['aswc_subscription_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_number'] ) ) : '';
				$aswc_subscription_interval        = isset( $_POST['aswc_subscription_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_interval'] ) ) : '';
				$aswc_subscription_expiry_number   = isset( $_POST['aswc_subscription_expiry_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_expiry_number'] ) ) : '';
				$aswc_subscription_expiry_interval = isset( $_POST['aswc_subscription_expiry_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_expiry_interval'] ) ) : '';

				aswc_update_order_meta( $aswc_parent_order, 'aswc_subscription', $post_id );

								$start_date_input   = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
								$start_hour_input   = isset( $_POST['start_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['start_hour'] ) ) : '';
								$start_minute_input = isset( $_POST['start_minute'] ) ? sanitize_text_field( wp_unslash( $_POST['start_minute'] ) ) : '';
								$start_timestamp    = $start_date_input ? strtotime( $start_date_input . ' ' . $start_hour_input . ':' . $start_minute_input ) : apply_filters( 'aswc_subs_curent_time', current_time( 'timestamp' ), $post_id );

								$trial_end_date              = isset( $_POST['trial_end'] ) ? sanitize_text_field( wp_unslash( $_POST['trial_end'] ) ) : '';
								$trial_end_hour              = isset( $_POST['trial_end_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['trial_end_hour'] ) ) : '';
								$trial_end_min               = isset( $_POST['trial_end_minute'] ) ? sanitize_text_field( wp_unslash( $_POST['trial_end_minute'] ) ) : '';
								$aswc_susbcription_trial_end = $trial_end_date ? strtotime( $trial_end_date . ' ' . $trial_end_hour . ':' . $trial_end_min ) : 0;
								aswc_update_order_meta( $post_id, 'aswc_susbcription_trial_end', $aswc_susbcription_trial_end );

								aswc_update_order_meta( $post_id, 'aswc_parent_order', $aswc_parent_order );
								aswc_update_order_meta( $post_id, '_order_key', wc_generate_order_key() );
								aswc_update_order_meta( $aswc_parent_order, 'aswc_order_has_subscription', 'yes' );
				aswc_update_order_meta( $post_id, 'aswc_subscription_number', $aswc_subscription_number );
				aswc_update_order_meta( $post_id, 'aswc_subscription_interval', $aswc_subscription_interval );
				aswc_update_order_meta( $post_id, 'aswc_subscription_expiry_number', $aswc_subscription_expiry_number );
				aswc_update_order_meta( $post_id, 'aswc_subscription_expiry_interval', $aswc_subscription_expiry_interval );

				if ( empty( $aswc_subscription_expiry_interval ) ) {
					$aswc_subscription_expiry_interval = 0;
				} else {
					$aswc_subscription_expiry_interval = (int) $aswc_subscription_expiry_interval;
				}

								$current_time          = $start_timestamp;
								$end_date_input        = isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '';
								$end_hour              = isset( $_POST['end_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['end_hour'] ) ) : '';
								$end_minute            = isset( $_POST['end_minute'] ) ? sanitize_text_field( wp_unslash( $_POST['end_minute'] ) ) : '';
								$aswc_susbcription_end = $end_date_input ? strtotime( $end_date_input . ' ' . $end_hour . ':' . $end_minute ) : aswc_susbcription_expiry_date( $post_id, $current_time, $aswc_subscription_expiry_interval );
								aswc_update_order_meta( $post_id, 'aswc_susbcription_end', $aswc_susbcription_end );
								aswc_update_order_meta( $post_id, 'aswc_order_currency', $parent_order->get_currency() );

				$aswc_manual_customer_id = isset( $_POST['customer_user'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_user'] ) ) : '';
				aswc_update_order_meta( $post_id, 'aswc_customer_id', $aswc_manual_customer_id );

				$status = 'active';

								aswc_update_order_meta( $post_id, 'aswc_subscription_status', $status );
								aswc_update_order_meta( $post_id, 'aswc_schedule_start', $start_timestamp );

				if ( isset( $_POST['next_payment'] ) ) {
						$next_payment_date      = sanitize_text_field( wp_unslash( $_POST['next_payment'] ) );
						$next_payment_hour      = isset( $_POST['next_payment_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['next_payment_hour'] ) ) : '';
						$next_payment_min       = isset( $_POST['next_payment_minute'] ) ? sanitize_text_field( wp_unslash( $_POST['next_payment_minute'] ) ) : '';
						$aswc_next_payment_date = strtotime( $next_payment_date . ' ' . $next_payment_hour . ':' . $next_payment_min );
				} elseif ( function_exists( 'aswc_next_payment_date' ) ) {
						$aswc_next_payment_date = aswc_next_payment_date( $post_id, $current_time, $aswc_susbcription_trial_end );
				}

								aswc_update_order_meta( $post_id, 'aswc_next_payment_date', $aswc_next_payment_date );

				if ( aswc_loader_is_hpos_enabled() ) {
					$order = new ASWC_Subscription( $post_id );
				} else {
					$order = wc_get_order( $post_id );
				}
				if ( $order ) {
					$items = $order->get_items();

					foreach ( $items as $item_id => $item_data ) {
						$product_id = $item_data->get_product_id();
					}
					$product_post = get_post( $product_id );
					if ( $product_post ) {
						// Get the product name.
						$product_name = get_the_title( $product_post );
						aswc_update_order_meta( $post_id, 'product_name', $product_name );
						aswc_update_order_meta( $post_id, 'product_qty', 1 );
						aswc_update_order_meta( $post_id, 'product_id', $product_id );
					}

					$line_subtotal     = 0;
					$line_total        = 0;
					$line_subtotal_tax = 0;
					$line_tax          = 0;

					foreach ( $items as $item_id => $item_data ) {
						$line_subtotal += $item_data->get_subtotal();
						$line_total    += $item_data->get_total();

						$tax_data = $item_data->get_taxes();
						if ( ! empty( $tax_data ) ) {
							foreach ( $tax_data as $tax_id => $tax ) {

								$line_subtotal_tax = $tax[1];
								$line_tax          = $tax[1];

							}
						}
					}

					aswc_update_order_meta( $post_id, 'line_total', $line_total );
					aswc_update_order_meta( $post_id, 'line_subtotal', $line_subtotal );
					aswc_update_order_meta( $post_id, 'line_subtotal_tax', $line_subtotal_tax );
					aswc_update_order_meta( $post_id, 'line_tax', $line_tax );

					$payment_method       = $parent_order->get_payment_method();
					$payment_method_title = $parent_order->get_payment_method_title();

					aswc_update_order_meta( $post_id, 'payment_method', $payment_method );
					aswc_update_order_meta( $post_id, 'payment_method_title', $payment_method_title );

					$payment_method       = $parent_order->get_payment_method();
					$payment_method_title = $parent_order->get_payment_method_title();

					$order->set_currency( $parent_order->get_currency() );
					$order->save();
					aswc_update_order_meta( $post_id, 'aswc_payment_type', 'aswc_manual_method' );

				}

				aswc_update_order_meta( $aswc_parent_order, 'aswc_subscription_activated', 'yes' );

							do_action( 'aswc_after_created_subscription', $post_id, $aswc_parent_order );
							ASWC_Log::log( 'Manual subscription saved for ID: ' . $post_id );
			}
		}
	}


	/**
	 *  Function To save and create manual subscription.
	 *
	 * @param [type] $order_id is the subscription order id.
	 * @return void
	 */
	public function aswc_save_manual_subscription_order_details_hpos( $order_id ) {

			$screen = get_current_screen();
		if ( empty( $screen ) ) {
				return;
		}
			ASWC_Log::log( 'Saving manual subscription (HPOS) for ID: ' . $order_id );

		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
				return;
		}

		if ( aswc_loader_is_hpos_enabled() ) {

			if ( 'woocommerce_page_wc-orders--aswc_subscriptions' === $screen->id ) {
				$post_id           = $order_id;
				$aswc_parent_order = aswc_get_meta_data( $post_id, 'aswc_parent_order', true );
				// check subscription already created so no further saving need.
				if ( empty( $aswc_parent_order ) ) {

					$aswc_parent_order                      = isset( $_POST['aswc_parent_order'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_parent_order'] ) ) : '';
					$aswc_subscription_number               = isset( $_POST['aswc_subscription_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_number'] ) ) : '';
					$aswc_subscription_interval             = isset( $_POST['aswc_subscription_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_interval'] ) ) : '';
					$aswc_subscription_expiry_number        = isset( $_POST['aswc_subscription_expiry_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_expiry_number'] ) ) : '';
					$aswc_subscription_expiry_interval      = isset( $_POST['aswc_subscription_expiry_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_expiry_interval'] ) ) : '';
										$start_date_input   = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
										$start_hour_input   = isset( $_POST['start_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['start_hour'] ) ) : '';
										$start_minute_input = isset( $_POST['start_minute'] ) ? sanitize_text_field( wp_unslash( $_POST['start_minute'] ) ) : '';
										$start_timestamp    = $start_date_input ? strtotime( $start_date_input . ' ' . $start_hour_input . ':' . $start_minute_input ) : apply_filters( 'aswc_subs_curent_time', current_time( 'timestamp' ), $post_id );
					$parent_order                           = wc_get_order( $aswc_parent_order );

										$trial_end_date              = isset( $_POST['trial_end'] ) ? sanitize_text_field( wp_unslash( $_POST['trial_end'] ) ) : '';
										$trial_end_hour              = isset( $_POST['trial_end_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['trial_end_hour'] ) ) : '';
										$trial_end_min               = isset( $_POST['trial_end_minute'] ) ? sanitize_text_field( wp_unslash( $_POST['trial_end_minute'] ) ) : '';
										$aswc_susbcription_trial_end = $trial_end_date ? strtotime( $trial_end_date . ' ' . $trial_end_hour . ':' . $trial_end_min ) : 0;
										aswc_update_order_meta( $post_id, 'aswc_susbcription_trial_end', $aswc_susbcription_trial_end );
					aswc_update_order_meta( $aswc_parent_order, 'aswc_subscription', $post_id );
					aswc_update_order_meta( $post_id, 'aswc_parent_order', $aswc_parent_order );
					aswc_update_order_meta( $post_id, '_order_key', wc_generate_order_key() );
					aswc_update_order_meta( $aswc_parent_order, 'aswc_order_has_subscription', 'yes' );
					aswc_update_order_meta( $post_id, 'aswc_subscription_number', $aswc_subscription_number );
					aswc_update_order_meta( $post_id, 'aswc_subscription_interval', $aswc_subscription_interval );
					aswc_update_order_meta( $post_id, 'aswc_subscription_expiry_number', $aswc_subscription_expiry_number );
					aswc_update_order_meta( $post_id, 'aswc_subscription_expiry_interval', $aswc_subscription_expiry_interval );

					if ( empty( $aswc_subscription_expiry_interval ) ) {
						$aswc_subscription_expiry_interval = 0;
					} else {
						$aswc_subscription_expiry_interval = (int) $aswc_subscription_expiry_interval;
					}

										$current_time          = $start_timestamp;
										$end_date_input        = isset( $_POST['end'] ) ? sanitize_text_field( wp_unslash( $_POST['end'] ) ) : '';
										$end_hour              = isset( $_POST['end_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['end_hour'] ) ) : '';
										$end_minute            = isset( $_POST['end_minute'] ) ? sanitize_text_field( wp_unslash( $_POST['end_minute'] ) ) : '';
										$aswc_susbcription_end = $end_date_input ? strtotime( $end_date_input . ' ' . $end_hour . ':' . $end_minute ) : aswc_susbcription_expiry_date( $post_id, $current_time, $aswc_subscription_expiry_interval );
										aswc_update_order_meta( $post_id, 'aswc_susbcription_end', $aswc_susbcription_end );

					$aswc_manual_customer_id = isset( $_POST['customer_user'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_user'] ) ) : '';
					aswc_update_order_meta( $post_id, 'aswc_customer_id', $aswc_manual_customer_id );

					$status = 'active';

					aswc_update_order_meta( $post_id, 'aswc_subscription_status', $status );
										aswc_update_order_meta( $post_id, 'aswc_schedule_start', $start_timestamp );
					if ( isset( $_POST['next_payment'] ) ) {
							$next_payment_date      = sanitize_text_field( wp_unslash( $_POST['next_payment'] ) );
							$next_payment_hour      = isset( $_POST['next_payment_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['next_payment_hour'] ) ) : '';
							$next_payment_min       = isset( $_POST['next_payment_minute'] ) ? sanitize_text_field( wp_unslash( $_POST['next_payment_minute'] ) ) : '';
							$aswc_next_payment_date = strtotime( $next_payment_date . ' ' . $next_payment_hour . ':' . $next_payment_min );
					} elseif ( function_exists( 'aswc_next_payment_date' ) ) {
							$aswc_next_payment_date = aswc_next_payment_date( $post_id, $current_time, $aswc_susbcription_trial_end );
					}

										aswc_update_order_meta( $post_id, 'aswc_next_payment_date', $aswc_next_payment_date );

					if ( aswc_loader_is_hpos_enabled() ) {
						$order = new ASWC_Subscription( $post_id );
					} else {
						$order = wc_get_order( $post_id );
					}
					if ( $order ) {
						$items = $order->get_items();

						foreach ( $items as $item_id => $item_data ) {
							$product_id = $item_data->get_product_id();
						}
						$product_post = get_post( $product_id );
						if ( $product_post ) {
							// Get the product name.
							$product_name = get_the_title( $product_post );
							aswc_update_order_meta( $post_id, 'product_name', $product_name );
							aswc_update_order_meta( $post_id, 'product_qty', 1 );
							aswc_update_order_meta( $post_id, 'product_id', $product_id );
						}

						$line_subtotal     = 0;
						$line_total        = 0;
						$line_subtotal_tax = 0;
						$line_tax          = 0;

						foreach ( $items as $item_id => $item_data ) {
							$line_subtotal += $item_data->get_subtotal();
							$line_total    += $item_data->get_total();

							$tax_data = $item_data->get_taxes();
							if ( ! empty( $tax_data ) ) {
								foreach ( $tax_data as $tax_id => $tax ) {

									$line_subtotal_tax = $tax[1];
									$line_tax          = $tax[1];

								}
							}
						}

						aswc_update_order_meta( $post_id, 'line_total', $line_total );
						aswc_update_order_meta( $post_id, 'line_subtotal', $line_subtotal );
						aswc_update_order_meta( $post_id, 'line_subtotal_tax', $line_subtotal_tax );
						aswc_update_order_meta( $post_id, 'line_tax', $line_tax );

						$payment_method       = $parent_order->get_payment_method();
						$payment_method_title = $parent_order->get_payment_method_title();

						$order->set_payment_method( $payment_method );
						$order->set_payment_method_title( $payment_method_title );
						$order->set_currency( $parent_order->get_currency() );
						$order->save();

					}
									aswc_update_order_meta( $aswc_parent_order, 'aswc_subscription_activated', 'yes' );

									do_action( 'aswc_after_created_subscription', $post_id, $aswc_parent_order );
									ASWC_Log::log( 'Manual subscription (HPOS) saved for ID: ' . $post_id );
				}
			}
		}
	}


	/**
	 * Remove the actions
	 *
	 * @param array() $actions .
	 * @param integer $aswc_subscription_id .
	 */
	public function aswc_add_action_details_callack( $actions, $aswc_subscription_id ) {
		return $actions;
	}

	/**
	 * Add custom order actions to the order actions dropdown.
	 *
	 * For subscriptions:
	 * - If no pending renewal order exists: "Create Pending Renewal Order"
	 * - If a pending renewal order exists: "Retry Pending Order Payment"
	 *
	 * For renewal orders:
	 * - If order is pending: "Retry Renewal Payment"
	 *
	 * @param mixed $actions .
	 * @return mixed
	 */
	public function aswc_add_renewal_payment_actions( $actions ) {
		global $theorder;

		if ( ! is_object( $theorder ) ) {
			return $actions;
		}

		// Check if this is a subscription.
		$order_type = '';
		if ( method_exists( $theorder, 'get_type' ) ) {
			$order_type = $theorder->get_type();
		} elseif ( isset( $theorder->post_type ) ) {
			$order_type = $theorder->post_type;
		}

		// Actions for subscriptions.
		if ( 'aswc_subscriptions' === $order_type || aswc_is_subscription( $theorder ) ) {
			$subscription_id = $theorder->get_id();

			// Check for pending renewal orders.
			$pending_order_id = 0;
			$renewal_orders   = aswc_get_meta_data( $subscription_id, 'aswc_renewal_order_data', true );

			if ( is_array( $renewal_orders ) && ! empty( $renewal_orders ) ) {
				// Check from most recent to oldest for a pending order.
				$reversed_orders = array_reverse( $renewal_orders );
				foreach ( $reversed_orders as $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order && $order->has_status( array( 'pending', 'on-hold', 'failed' ) ) ) {
						$pending_order_id = $order_id;
						break;
					}
				}
			}

			if ( $pending_order_id ) {
				// There's a pending order - show retry action.
				$actions['aswc_retry_pending_order'] = __( 'Retry Pending Order Payment', 'advanced-subscriptions-for-woocommerce' );
			} else {
				// No pending order - show create action.
				$actions['aswc_create_renewal_order'] = __( 'Create Pending Renewal Order', 'advanced-subscriptions-for-woocommerce' );
			}

			return $actions;
		}

		// Actions for renewal orders.
		if ( 'yes' === $theorder->get_meta( 'aswc_renewal_order' ) && $theorder->has_status( array( 'pending' ) ) ) {
			$actions['aswc_retry_renewal'] = __( 'Retry Renewal Payment', 'advanced-subscriptions-for-woocommerce' );
		}

		return $actions;
	}

	/**
	 * Handle the custom order action.
	 *
	 * @param mixed $order .
	 * @return void
	 */
	public function aswc_handle_renewal_payment( $order ) {
		$order_id = $order->get_id();

		$subcription_id = $order->get_meta( 'aswc_subscription' );

		// Initiate the renewal payment.
		do_action( 'aswc_other_payment_gateway_renewal', $order, $subcription_id, $order->get_payment_method() );
	}

	/**
	 * New Column to show update subscrition.
	 *
	 * @param array $columns as columns.
	 * @return array
	 */
	public function aswc_add_update_subscription_column( $columns ) {
		// Add a new column named 'Update Subscription'.
		$columns['update_subscription'] = __( 'Update Subscription', 'advanced-subscriptions-for-woocommerce' );
		return $columns;
	}

	/**
	 * Update subscription coloumn values function
	 *
	 * @param array $output as output.
	 * @param array $column_name as column name.
	 * @param array $item as item.
	 * @return array
	 */
	public function aswc_add_update_button_to_subscription_column( $output, $column_name, $item ) {
		if ( 'update_subscription' === $column_name ) {
			$output = '<button class="button update-subscription" data-subscription_id="' . esc_attr( $item['subscription_id'] ) . '">' . __( 'Update', 'advanced-subscriptions-for-woocommerce' ) . '</button>';
			?>
			<div id="update-subscription-popup" class="aswc-aswc_popup-overlay" style="display: none;">

				<div class="popup-content">
					<span class="close-popup">&times;</span>
					<h4><?php esc_html_e( 'Update Subscription', 'advanced-subscriptions-for-woocommerce' ); ?></h4>
					<form id="update-subscription-form">
						<input type="hidden" id="subscription-id" name="subscription_id">
						
						<div class="aswc_sub_popup_wrapper">
						<label for="next-payment-date"><?php esc_html_e( 'Next Payment Date:', 'advanced-subscriptions-for-woocommerce' ); ?></label>
						<input type="date" id="next-payment-date" name="next_payment_date" placeholder="Select date">
						</div>

						<div class="aswc_sub_popup_wrapper">
						<label for="subscription-price"><?php esc_html_e( 'Subscription Item Total:', 'advanced-subscriptions-for-woocommerce' ); ?></label>
						<input type="number" id="subscription-price" name="subscription_price" placeholder="Enter new price" step="0.01" min="0">
						<span><?php esc_html_e( 'final price will based on all taxes calculation and shipping charges if applicable previously on subscription', 'advanced-subscriptions-for-woocommerce' ); ?></span>
						</div>
						
						<button type="button" id="update-subscription-btn"><?php esc_html_e( 'Update Subscription', 'advanced-subscriptions-for-woocommerce' ); ?></button>
					</form>
				</div>
			</div>

			<?php
		}

			return $output;
	}

	/**
	 * Ajax Function to update item of existing subscription.
	 *
	 * @return void
	 */
	public function aswc_update_subscription_items_callback() {

		check_ajax_referer( 'aswc_admin_nonce', 'nonce' );

	// SECURITY: Only administrators can modify subscription items and prices.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'advanced-subscriptions-for-woocommerce' ) ) );
	}
		$subscription_id    = isset( $_POST['subscription_id'] ) ? sanitize_text_field( wp_unslash( $_POST['subscription_id'] ) ) : '';
		$next_payment_date  = isset( $_POST['next_payment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['next_payment_date'] ) ) : '';
		$subscription_price = isset( $_POST['subscription_price'] ) ? sanitize_text_field( wp_unslash( $_POST['subscription_price'] ) ) : '';
		$subscription_price = floatval( $subscription_price );

	// SECURITY: Validate price is not negative and within reasonable bounds.
	if ( $subscription_price < 0 ) {
		wp_send_json_error( array( 'message' => __( 'Invalid price: cannot be negative.', 'advanced-subscriptions-for-woocommerce' ) ) );
	}

	// Optional: Add maximum price validation.
	$max_price = apply_filters( 'aswc_max_subscription_price', 999999 );
	if ( $subscription_price > $max_price ) {
		wp_send_json_error( array( 'message' => __( 'Invalid price: exceeds maximum allowed.', 'advanced-subscriptions-for-woocommerce' ) ) );
	}
		$update             = false;
		if ( $next_payment_date ) {

			$next_payment_timestamp = strtotime( $next_payment_date );
			if ( false === $next_payment_timestamp ) {
				wp_send_json_error( array( 'message' => 'Invalid date format.' ) );
			} else {

								aswc_update_order_meta( $subscription_id, 'aswc_next_payment_date', $next_payment_timestamp );

				if ( class_exists( 'ASWC_Subscription' ) && function_exists( 'aswc_unschedule_payment' ) && function_exists( 'aswc_schedule_payment' ) ) {
						$subscription_obj = new ASWC_Subscription( $subscription_id );
						aswc_unschedule_payment( $subscription_obj );
						aswc_schedule_payment( $subscription_obj, $next_payment_timestamp );
				}

								$update = true;
			}
		}
		if ( $subscription_price ) {
			$subscription = wc_get_order( $subscription_id );
			if ( $subscription ) {
				$subscription_updated = false;

				// Loop through the subscription line items.
				foreach ( $subscription->get_items() as $item_id => $item ) {
					// Set the item prices.
					$quantity      = $item->get_quantity();
					$line_subtotal = $subscription_price * $quantity;
					$line_total    = $subscription_price * $quantity;

					// Calculate taxes dynamically.
					$tax_data          = WC_Tax::calc_tax( $line_total, WC_Tax::get_rates( $item->get_tax_class() ) );
					$line_total_tax    = array_sum( $tax_data ); // Total tax for the line.
					$line_subtotal_tax = array_sum( WC_Tax::calc_tax( $line_subtotal, WC_Tax::get_rates( $item->get_tax_class() ) ) );

					// Update item totals.

					$include = get_option( 'woocommerce_prices_include_tax' );
					if ( 'yes' === $include ) {
						$item->set_subtotal( $line_subtotal - $line_subtotal_tax );
						$item->set_total( $line_total - $line_total_tax );
						$item->set_subtotal_tax( $line_subtotal_tax );
						$item->set_total_tax( $line_total_tax );

						aswc_update_meta_data( $subscription_id, 'line_subtotal', $line_subtotal - $line_subtotal_tax );
						aswc_update_meta_data( $subscription_id, 'line_total', $line_total - $line_total_tax );
						aswc_update_meta_data( $subscription_id, 'line_subtotal_tax', $line_subtotal_tax );
						aswc_update_meta_data( $subscription_id, 'line_tax', $line_total_tax );

					} else {

						$item->set_subtotal( $line_subtotal );
						$item->set_total( $line_total );
						$item->set_subtotal_tax( $line_subtotal_tax );
						$item->set_total_tax( $line_total_tax );

						aswc_update_meta_data( $subscription_id, 'line_subtotal', $line_subtotal );
						aswc_update_meta_data( $subscription_id, 'line_total', $line_total );
						aswc_update_meta_data( $subscription_id, 'line_subtotal_tax', $line_subtotal_tax );
						aswc_update_meta_data( $subscription_id, 'line_tax', $line_total_tax );
					}

					// Save the updated line item.
					$item->save();

					// Flag that the subscription was updated.
					$subscription_updated = true;
				}

				if ( $subscription_updated ) {
					$subscription->calculate_totals();
					$subscription->save();

					// Optionally, add a note to the subscription about the price update.
					$subscription->add_order_note( 'Subscrition Price update by admin' );
					$update = true;
				}
			}
		}
		if ( empty( $next_payment_date ) && empty( $subscription_price ) ) {
			wp_send_json_success(
				array(
					'sucess'  => $update,
					'message' => 'Kindly Enter either next payment date or subscription item Price or Both !.',
				)
			);
		}
		if ( $update ) {
			wp_send_json_success(
				array(
					'sucess'  => $update,
					'message' => 'Subscription updated!',
				)
			);
		} else {
			wp_send_json_success(
				array(
					'sucess'  => $update,
					'message' => 'NO Changes',
				)
			);
		}

		// Return a success response.
		wp_send_json_success(
			array(
				'message'   => 'Subscription updated!',
				'timestamp' => $next_payment_timestamp,
				'price'     => $subscription_price,
			)
		);

			wp_die();
	}

	/**
	 * Handle the "Create Pending Renewal Order" action from the subscription actions dropdown.
	 *
	 * @param WC_Order $subscription The subscription object.
	 * @return void
	 */
	public function aswc_handle_create_renewal_order_action( $subscription ) {
		$subscription_id = $subscription->get_id();

		// Create the renewal order.
		$renewal_order = null;
		if ( function_exists( 'aswc_create_renewal_order' ) ) {
			$renewal_order = aswc_create_renewal_order( $subscription );
		} elseif ( function_exists( 'wcs_create_renewal_order' ) ) {
			$renewal_order = wcs_create_renewal_order( $subscription );
		}

		if ( is_wp_error( $renewal_order ) ) {
			$subscription->add_order_note(
				sprintf(
					/* translators: %s: error message */
					__( 'Failed to create renewal order: %s', 'advanced-subscriptions-for-woocommerce' ),
					$renewal_order->get_error_message()
				)
			);
			return;
		}

		if ( ! $renewal_order instanceof WC_Order ) {
			$subscription->add_order_note( __( 'Failed to create renewal order.', 'advanced-subscriptions-for-woocommerce' ) );
			return;
		}

		// Set the order status to pending.
		$renewal_order->update_status( 'pending', __( 'Pending renewal order created manually from subscription.', 'advanced-subscriptions-for-woocommerce' ) );

		// Add a note to the subscription.
		$subscription->add_order_note(
			sprintf(
				/* translators: %s: renewal order ID */
				__( 'Pending renewal order #%s created manually.', 'advanced-subscriptions-for-woocommerce' ),
				$renewal_order->get_id()
			)
		);
	}

	/**
	 * Handle the "Retry Pending Order Payment" action from the subscription actions dropdown.
	 *
	 * @param WC_Order $subscription The subscription object.
	 * @return void
	 */
	public function aswc_handle_retry_pending_order_action( $subscription ) {
		$subscription_id = $subscription->get_id();

		// Find the pending renewal order.
		$pending_order_id = 0;
		$renewal_orders   = aswc_get_meta_data( $subscription_id, 'aswc_renewal_order_data', true );

		if ( is_array( $renewal_orders ) && ! empty( $renewal_orders ) ) {
			$reversed_orders = array_reverse( $renewal_orders );
			foreach ( $reversed_orders as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order && $order->has_status( array( 'pending', 'on-hold', 'failed' ) ) ) {
					$pending_order_id = $order_id;
					break;
				}
			}
		}

		if ( ! $pending_order_id ) {
			$subscription->add_order_note( __( 'No pending renewal order found to retry payment.', 'advanced-subscriptions-for-woocommerce' ) );
			return;
		}

		$order = wc_get_order( $pending_order_id );
		if ( ! $order ) {
			$subscription->add_order_note( __( 'Pending order not found.', 'advanced-subscriptions-for-woocommerce' ) );
			return;
		}

		// Get payment method from order, or fallback to subscription if not set.
		$payment_method = $order->get_payment_method();
		if ( empty( $payment_method ) ) {
			// Copy payment method from subscription to the order.
			$payment_method = $subscription->get_payment_method();
			if ( $payment_method ) {
				$order->set_payment_method( $payment_method );
				$order->set_payment_method_title( $subscription->get_payment_method_title() );
			}
		}

		// Copy payment tokens from subscription (required for automatic payments like Redsys).
		$payment_tokens = $subscription->get_payment_tokens();
		if ( ! empty( $payment_tokens ) && method_exists( $order->get_data_store(), 'update_payment_token_ids' ) ) {
			$order->get_data_store()->update_payment_token_ids( $order, $payment_tokens );
		}

		// Save order after copying payment data.
		$order->save();

		if ( empty( $payment_method ) ) {
			$subscription->add_order_note( __( 'Cannot retry payment: No payment method found on subscription.', 'advanced-subscriptions-for-woocommerce' ) );
			return;
		}

		// Add notes.
		$order->add_order_note( __( 'Payment retry initiated manually from subscription.', 'advanced-subscriptions-for-woocommerce' ) );
		$subscription->add_order_note(
			sprintf(
				/* translators: %s: order ID */
				__( 'Payment retry initiated for order #%s.', 'advanced-subscriptions-for-woocommerce' ),
				$pending_order_id
			)
		);

		// Check if this is a manual payment gateway.
		$is_manual_gateway = false;
		if ( aswc_enbale_accept_manual_payment() ) {
			$manual_gateways = aswc_support_manual_payment();
			$is_manual_gateway = in_array( $payment_method, $manual_gateways, true );
		}

		if ( $is_manual_gateway ) {
			// For manual payment gateways, trigger the manual renewal action.
			do_action( 'aswc_other_payment_gateway_renewal', $order, $subscription_id, $payment_method );
		} else {
			// For automatic payment gateways (Redsys, Stripe, etc.), use the Scheduler API.
			if ( class_exists( 'ASWC_Scheduler_API' ) ) {
				ASWC_Scheduler_API::payments()->trigger_gateway_renewal_payment_hook( $order );
			} else {
				// Fallback: trigger the gateway-specific hook directly.
				$advanced_hook   = 'advanced_scheduled_subscription_payment_' . $payment_method;
				$deprecated_hook = 'woocommerce_scheduled_subscription_payment_' . $payment_method;

				if ( has_action( $advanced_hook ) ) {
					do_action( $advanced_hook, $order->get_total(), $order );
				} elseif ( has_action( $deprecated_hook ) ) {
					do_action( $deprecated_hook, $order->get_total(), $order );
				}
			}
		}

		// Check if the order status changed after the payment attempt.
		$order      = wc_get_order( $pending_order_id );
		$new_status = $order->get_status();

		if ( in_array( $new_status, array( 'processing', 'completed' ), true ) ) {
			$subscription->add_order_note(
				sprintf(
					/* translators: 1: order ID, 2: new status */
					__( 'Payment successful for order #%1$s. New status: %2$s', 'advanced-subscriptions-for-woocommerce' ),
					$pending_order_id,
					wc_get_order_status_name( $new_status )
				)
			);
		} else {
			$subscription->add_order_note(
				sprintf(
					/* translators: 1: order ID, 2: status */
					__( 'Payment retry completed for order #%1$s. Status: %2$s', 'advanced-subscriptions-for-woocommerce' ),
					$pending_order_id,
					wc_get_order_status_name( $new_status )
				)
			);
		}
	}

		/**
		 * Update next payment meta and reschedule payment when a subscription is saved.
		 *
		 * @param int     $post_id Post ID.
		 * @param WP_Post $post    Post object.
		 *
		 * @return void
		 */
	public function aswc_reschedule_next_payment_on_save( $post_id, $post ) {
		if ( 'aswc_subscriptions' !== $post->post_type ) {
				return;
		}

		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
				return;
		}

				$next_payment = isset( $_POST['next_payment'] ) ? sanitize_text_field( wp_unslash( $_POST['next_payment'] ) ) : '';
		if ( empty( $next_payment ) ) {
				return;
		}

				$next_hour   = isset( $_POST['next_payment_hour'] ) ? sanitize_text_field( wp_unslash( $_POST['next_payment_hour'] ) ) : '00';
				$next_minute = isset( $_POST['next_payment_minute'] ) ? sanitize_text_field( wp_unslash( $_POST['next_payment_minute'] ) ) : '00';
				$timestamp   = strtotime( $next_payment . ' ' . $next_hour . ':' . $next_minute );

		if ( false === $timestamp ) {
					return;
		}

		aswc_update_meta_data( $post_id, 'aswc_next_payment_date', $timestamp );

		if ( class_exists( 'ASWC_Subscription' ) && function_exists( 'aswc_unschedule_payment' ) && function_exists( 'aswc_schedule_payment' ) ) {
				$subscription = new ASWC_Subscription( $post_id );
				aswc_unschedule_payment( $subscription );
				aswc_schedule_payment( $subscription, $timestamp );
		}
	}
		/**
		 * Get retry status text for a subscription (localized).
		 *
		 * @param int $subscription_id
		 * @return string
		 */
	public static function aswc_get_retry_status_text( $subscription_id ) {
		$attempts_raw = aswc_get_meta_data( $subscription_id, '_aswc_retry_attempts', true );
		$attempts     = ( is_numeric( $attempts_raw ) ? (int) $attempts_raw : 0 );

		$max_raw = get_option( 'aswc_after_no_failed_attempt_cancel', '3' );
		$max     = ( is_numeric( $max_raw ) ? (int) $max_raw : 3 );

		$enabled = ( 'yes' === get_option( 'aswc_enable_automatic_retry_failed_attempts', 'no' ) );

		// Ningún reintento todavía.
		if ( $attempts <= 0 ) {
			return __( 'Sin reintentos', 'advanced-subscriptions-for-woocommerce' );
		}

		// Máximo alcanzado (si la función está activa).
		if ( $enabled && $attempts >= $max ) {
			return __( 'Máximos reintentos alcanzado', 'advanced-subscriptions-for-woocommerce' );
		}

		/* translators: 1: current attempts, 2: max attempts */
		return sprintf(
			__( 'Reintentos: %1$d de %2$d', 'advanced-subscriptions-for-woocommerce' ),
			$attempts,
			$max
		);
	}

	/**
	 * Echo small HTML block with the retry status (to be used bajo "Time Zone").
	 *
	 * @param int $subscription_id
	 * @return void
	 */
	public static function aswc_retry_status_html( $subscription_id ) {
		$text = self::aswc_get_retry_status_text( $subscription_id );
		echo '<div class="aswc-retry-status">' . esc_html( $text ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
