<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://plugins.joseconti.com
 * @since 1.0.0
 *
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce/admin
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Advanced_Subscriptions_For_Woocommerce
 * @subpackage Advanced_Subscriptions_For_Woocommerce/admin
 */
class ASWC_Admin {

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
		if ( isset( $screen->id ) ) {
			$pagescreen = $screen->id;
		}

				$scree_orders = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'aswc_subscriptions' ) : 'aswc_subscriptions';

		$new_action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( isset( $screen->id ) && $screen->id === $scree_orders && ( isset( $_GET['id'] ) || isset( $_GET['post'] ) || 'new' === $new_action || ( isset( $_GET['post_type'] ) && 'aswc_subscriptions' === $_GET['post_type'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			wp_enqueue_style( 'selectWoo' );
			wp_enqueue_style( $this->plugin_name . '-admin-global', ASWC_DIR_URL . 'admin/css/aswc-admin-global.css', array(), time(), 'all' );
			wp_enqueue_style( $this->plugin_name, ASWC_DIR_URL . 'admin/css/aswc-admin.css', array(), time(), 'all' );
		}

		if ( ( isset( $pagescreen ) && 'plugins' === $pagescreen ) ) {

			wp_enqueue_style( $this->plugin_name, ASWC_DIR_URL . 'admin/css/aswc-admin.css', array(), time(), 'all' );
		}

		if ( isset( $screen->id ) && ( 'product' === $screen->id || 'wp-swings_page_home' === $screen->id ) ) {
						wp_enqueue_style( 'subscription-for-woocommerce-product-edit', ASWC_DIR_URL . 'admin/css/aswc-product-edit.css', array(), time(), 'all' );

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

				$recurring_payment_icon = ASWC_DIR_URL . 'admin/images/recurring-payment.svg';

		$aswc_branner_notice = array(
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			'aswc_nonce' => wp_create_nonce( 'aswc-verify-notice-nonce' ),
		);
		wp_register_script( $this->plugin_name . 'admin-notice', ASWC_DIR_URL . 'admin/js/aswc-subscription-card-notices.js', array( 'jquery' ), $this->version, false );

		wp_localize_script( $this->plugin_name . 'admin-notice', 'aswc_branner_notice', $aswc_branner_notice );
		wp_enqueue_script( $this->plugin_name . 'admin-notice' );

		if ( isset( $screen->id ) && ( in_array( $screen->id, $aswc_screen_ids, true ) || 'wp-swings_page_home' === $screen->id || 'woocommerce_page_wc-settings' === $screen->id || 'aswc_subscriptions' === $screen->id ) ) {

			wp_enqueue_script( 'selectWoo' );

			wp_register_script( $this->plugin_name . 'admin-js', ASWC_DIR_URL . 'admin/js/aswc-admin.js', array( 'jquery', 'selectWoo' ), $this->version, false );

			wp_localize_script(
				$this->plugin_name . 'admin-js',
				'aswc_admin_param',
				array(
					'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
					'reloadurl'                   => admin_url(),
					'sfw_gen_tab_enable'          => get_option( 'sfw_radio_switch_demo' ),
					'sfw_auth_nonce'              => wp_create_nonce( 'aswc_admin_nonce' ),
					'empty_fields'                => esc_html__( 'Make Sure, You have filled the Client ID and Client secret keys', 'advanced-subscriptions-for-woocommerce' ),
					'recurring_payment_icon'      => $recurring_payment_icon,
					'Supported_recurring_payment' => esc_html__( 'Supported Recurring Payment', 'advanced-subscriptions-for-woocommerce' ),
				)
			);

			wp_enqueue_script( $this->plugin_name . 'admin-js' );
		}

		if ( ( isset( $screen->id ) && 'product' === $screen->id ) || 'aswc_subscriptions' === $screen->id ) {
						wp_register_script( 'aswc-admin-single-product-js', ASWC_DIR_URL . 'admin/js/aswc-product-edit.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'aswc-admin-single-product-js' );

			$aswc_data = array(
				'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
				'reloadurl'                => admin_url(),
				'day'                      => __( 'Days', 'advanced-subscriptions-for-woocommerce' ),
				'week'                     => __( 'Weeks', 'advanced-subscriptions-for-woocommerce' ),
				'month'                    => __( 'Months', 'advanced-subscriptions-for-woocommerce' ),
				'year'                     => __( 'Years', 'advanced-subscriptions-for-woocommerce' ),
				'expiry_notice'            => __( 'Expiry Interval must be greater than subscription interval', 'advanced-subscriptions-for-woocommerce' ),
				'expiry_days_notice'       => __( 'Expiry Interval must not be greater than 90 Days', 'advanced-subscriptions-for-woocommerce' ),
				'expiry_week_notice'       => __( 'Expiry Interval must not be greater than 52 Weeks', 'advanced-subscriptions-for-woocommerce' ),
				'expiry_month_notice'      => __( 'Expiry Interval must not be greater than 24 Months', 'advanced-subscriptions-for-woocommerce' ),
				'expiry_year_notice'       => __( 'Expiry Interval must not be greater than 5 Years', 'advanced-subscriptions-for-woocommerce' ),
				'trial_days_notice'        => __( 'Trial period must not be greater than 90 Days', 'advanced-subscriptions-for-woocommerce' ),
				'trial_week_notice'        => __( 'Trial period must not be greater than 52 Weeks', 'advanced-subscriptions-for-woocommerce' ),
				'trial_month_notice'       => __( 'Trial period must not be greater than 24 Months', 'advanced-subscriptions-for-woocommerce' ),
				'trial_year_notice'        => __( 'Trial period must not be greater than 5 Years', 'advanced-subscriptions-for-woocommerce' ),
				'fist_subscription_box_id' => get_option( 'aswc_first_subscription_box_id', false ),
			);
			wp_localize_script(
				'aswc-admin-single-product-js',
				'aswc_product_param',
				$aswc_data
			);
			wp_enqueue_script( 'jquery-ui-datepicker' );

		}
	}
		/**
		 * Previously this class registered a "Jose Conti" top level menu under
		 * the slug `aswc-plugins`. The menu has been removed to declutter the
		 * WordPress admin.
		 */

		/**
		 * Subscriptions For Woocommerce admin menu page.
		 *
		 * @since 1.0.0
		 * @param array $aswc_settings_general Settings fields.
		 */
	public function aswc_admin_general_settings_page( $aswc_settings_general ) {

		$aswc_settings_general = array(
			array(
				'title'       => __( 'Enable/Disable Subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Check this box to enable the subscription.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_plugin',
				'class'       => 'aswc-checkbox-class',
				'value'       => 'on',
				'checked'     => ( aswc_option_is_true( 'aswc_enable_plugin' ) ? 'on' : 'off' ),
			),
			array(
				'title'       => __( 'Add to cart text', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Use this option to change add to cart button text.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_add_to_cart_text',
				'value'       => get_option( 'aswc_add_to_cart_text', '' ),
				'class'       => 'aswc-text-class',
				'placeholder' => __( 'Add to cart button text', 'advanced-subscriptions-for-woocommerce' ),
			),
			array(
				'title'       => __( 'Place order text', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Use this option to change place order button text.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_place_order_button_text',
				'value'       => get_option( 'aswc_place_order_button_text', '' ),
				'class'       => 'aswc-text-class',
				'placeholder' => __( 'Place order button text', 'advanced-subscriptions-for-woocommerce' ),
			),
			array(
				'title'       => __( 'Allow Customer to cancel Subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable this option to allow the customer to cancel the subscription.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_cancel_subscription_for_customer',
				'checked'     => ( aswc_option_is_true( 'aswc_cancel_subscription_for_customer' ) ? 'on' : 'off' ),
				'value'       => 'on',
				'class'       => 'aswc-checkbox-class',
			),
			array(
				'title'       => __( 'Enable Log', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable Log.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_subscription_log',
				'value'       => 'on',
				'checked'     => ( aswc_option_is_true( 'aswc_enable_subscription_log' ) ? 'on' : 'off' ),
				'class'       => 'aswc-checkbox-class',
			),
			array(
				'type'        => 'button',
				'id'          => 'aswc_save_general_settings',
				'button_text' => __( 'Save Settings', 'advanced-subscriptions-for-woocommerce' ),
				'class'       => 'aswc-button-class',
			),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$woocommerce_version = WC()->version;
			if ( version_compare( $woocommerce_version, '8.8.3', '>' ) ) {
				unset( $aswc_settings_general[5] );
			}
		}
		// Add general settings.
		return apply_filters( 'aswc_add_general_settings_fields', $aswc_settings_general );
	}

	/**
	 * Api settings fields.
	 *
	 * @since 1.0.0
	 * @param array $aswc_api_settings Api fields.
	 */
	public function aswc_admin_api_settings_fields( $aswc_api_settings ) {

		$aswc_api_secret_key = get_option( 'aswc_api_secret_key', '' );

		$aswc_btn_txt = ! empty( $aswc_api_secret_key ) ? __( 'Save Settings', 'advanced-subscriptions-for-woocommerce' ) : __( 'Generate & Save', 'advanced-subscriptions-for-woocommerce' );

		$aswc_api_settings = array(
			array(
				'title'       => __( 'Enable API Features', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable this to allow API functionality to view subscription.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_api_features',
				'value'       => get_option( 'aswc_enable_api_features' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'API secret key', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'API secret key.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_api_secret_key',
				'value'       => get_option( 'aswc_api_secret_key', '' ),
				'class'       => 'aswc-text-class',
				'placeholder' => __( 'API secret key', 'advanced-subscriptions-for-woocommerce' ),
			),
		);

		$aswc_api_settings[] = array(
			'type'        => 'button',
			'id'          => 'aswc_save_api_settings',
			'button_text' => $aswc_btn_txt,
			'class'       => 'aswc-button-class',
		);
		$aswc_api_settings   = array_merge( $aswc_api_settings );

		return $aswc_api_settings;
	}


	/**
	 * Subscriptions For Woocommerce save tab settings.
	 *
	 * @name sfw_admin_save_tab_settings.
	 * @since 1.0.0
	 */
	public function sfw_admin_save_tab_settings() {
		global $aswc_obj;
		global $aswc_notices;
		if ( isset( $_POST['aswc_save_general_settings'] ) && isset( $_POST['aswc-general-nonce-field'] ) ) {
			$aswc_geberal_nonce = sanitize_text_field( wp_unslash( $_POST['aswc-general-nonce-field'] ) );
			if ( wp_verify_nonce( $aswc_geberal_nonce, 'aswc-general-nonce' ) ) {
				$aswc_gen_flag = false;
				// General settings.
				$aswc_genaral_settings             = apply_filters( 'aswc_general_settings_array', array() );
								$aswc_button_index = array_search( 'submit', array_column( $aswc_genaral_settings, 'type' ), true );
				if ( isset( $aswc_button_index ) && ( null === $aswc_button_index || '' === $aswc_button_index ) ) {
										$aswc_button_index = array_search( 'button', array_column( $aswc_genaral_settings, 'type' ), true );
				}
				if ( isset( $aswc_button_index ) && '' !== $aswc_button_index ) {

					unset( $aswc_genaral_settings[ $aswc_button_index ] );
					if ( is_array( $aswc_genaral_settings ) && ! empty( $aswc_genaral_settings ) ) {
						foreach ( $aswc_genaral_settings as $aswc_genaral_setting ) {
							if ( isset( $aswc_genaral_setting['id'] ) && '' !== $aswc_genaral_setting['id'] ) {

								if ( isset( $_POST[ $aswc_genaral_setting['id'] ] ) && ! empty( $_POST[ $aswc_genaral_setting['id'] ] ) ) {

									$posted_value = sanitize_text_field( wp_unslash( $_POST[ $aswc_genaral_setting['id'] ] ) );
									update_option( $aswc_genaral_setting['id'], $posted_value );
								} else {
									update_option( $aswc_genaral_setting['id'], '' );
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
		} elseif ( isset( $_POST['aswc_save_api_settings'] ) && isset( $_POST['aswc-api-nonce-field'] ) ) {
			if ( ! isset( $_POST['aswc-api-nonce-field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aswc-api-nonce-field'] ) ), 'aswc-api-nonce' ) ) {
				return;
			}
			$aswc_gen_flag                 = false;
			$aswc_api_settings             = apply_filters( 'aswc_api_settings_array', array() );
						$aswc_button_index = array_search( 'submit', array_column( $aswc_api_settings, 'type' ), true );
			if ( isset( $aswc_button_index ) && ( null === $aswc_button_index || '' === $aswc_button_index ) ) {
								$aswc_button_index = array_search( 'button', array_column( $aswc_api_settings, 'type' ), true );
			}
			if ( isset( $aswc_button_index ) && '' !== $aswc_button_index ) {
				unset( $aswc_api_settings[ $aswc_button_index ] );
				if ( is_array( $aswc_api_settings ) && ! empty( $aswc_api_settings ) ) {
					foreach ( $aswc_api_settings as $aswc_api_setting ) {
						if ( isset( $aswc_api_setting['id'] ) && '' !== $aswc_api_setting['id'] ) {
							if ( 'aswc_api_secret_key' === $aswc_api_setting['id'] && empty( $_POST[ $aswc_api_setting['id'] ] ) ) {
								$_POST[ $aswc_api_setting['id'] ] = 'aswc_' . wc_rand_hash();
							}
							if ( isset( $_POST[ $aswc_api_setting['id'] ] ) ) {
								$posted_value = sanitize_text_field( wp_unslash( $_POST[ $aswc_api_setting['id'] ] ) );
								update_option( $aswc_api_setting['id'], $posted_value );
							} else {
								update_option( $aswc_api_setting['id'], '' );
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
		} elseif ( isset( $_POST['aswc_save_subscription_box_settings'] ) && isset( $_POST['aswc-subscription-box-nonce-field'] ) ) {
			$aswc_subscription_box_nonce = sanitize_text_field( wp_unslash( $_POST['aswc-subscription-box-nonce-field'] ) );
			if ( wp_verify_nonce( $aswc_subscription_box_nonce, 'aswc-subscription-box-nonce' ) ) {
				$aswc_sub_box_flag = false;
				// General settings.
				$aswc_subscription_box_settings = apply_filters( 'aswc_subscription_box_settings_array', array() );

								$aswc_button_index = array_search( 'submit', array_column( $aswc_subscription_box_settings, 'type' ), true );
				if ( isset( $aswc_button_index ) && ( null === $aswc_button_index || '' === $aswc_button_index ) ) {
										$aswc_button_index = array_search( 'button', array_column( $aswc_subscription_box_settings, 'type' ), true );
				}
				if ( isset( $aswc_button_index ) && '' !== $aswc_button_index ) {

					unset( $aswc_subscription_box_settings[ $aswc_button_index ] );
					if ( is_array( $aswc_subscription_box_settings ) && ! empty( $aswc_subscription_box_settings ) ) {
						foreach ( $aswc_subscription_box_settings as $aswc_subscription_box_setting ) {
							if ( isset( $aswc_subscription_box_setting['id'] ) && '' !== $aswc_subscription_box_setting['id'] ) {

								if ( isset( $_POST[ $aswc_subscription_box_setting['id'] ] ) && ! empty( $_POST[ $aswc_subscription_box_setting['id'] ] ) ) {

									$posted_value = sanitize_text_field( wp_unslash( $_POST[ $aswc_subscription_box_setting['id'] ] ) );
									update_option( $aswc_subscription_box_setting['id'], $posted_value );
								} else {
									update_option( $aswc_subscription_box_setting['id'], '' );
								}
							} else {

								$aswc_sub_box_flag = true;
							}
						}
					}

					if ( $aswc_sub_box_flag ) {
						$aswc_error_text = esc_html__( 'Id of some field is missing', 'advanced-subscriptions-for-woocommerce' );
						$aswc_obj->aswc_plug_admin_notice( $aswc_error_text, 'error' );
					} else {
						$aswc_notices = true;
					}
				}
			}
		}
		if ( isset( $_POST['aswc_track_button'] ) && isset( $_POST['aswc-general-nonce-field'] ) ) {
				$aswc_geberal_nonce = sanitize_text_field( wp_unslash( $_POST['aswc-general-nonce-field'] ) );
			if ( wp_verify_nonce( $aswc_geberal_nonce, 'aswc-general-nonce' ) ) {
						$aswc_notices = true;
			}
		}
	}

	/**
	 * This function is used Subscription type checkobox for simple products
	 *
	 * @name aswc_create_subscription_product_type
	 * @since 1.0.0
	 * @param    Array $products_type Products type.
	 * @return   Array  $products_type.
	 */
	public function aswc_create_subscription_product_type( $products_type ) {
		$products_type['aswc_product'] = array(
			'id'            => '_aswc_product',
			'wrapper_class' => 'show_if_simple show_if_mwb_booking',
			'label'         => __( 'Subscription', 'advanced-subscriptions-for-woocommerce' ),
			'description'   => __( 'This is the Subscriptions type product.', 'advanced-subscriptions-for-woocommerce' ),
			'default'       => 'no',
		);
		return $products_type;
	}


	/**
	 * This function is used to add subscription settings for product.
	 *
	 * @name aswc_custom_product_tab_for_subscription
	 * @since 1.0.0
	 * @param    Array $tabs Products tabs array.
	 * @return   Array  $tabs
	 */
	public function aswc_custom_product_tab_for_subscription( $tabs ) {
		$tabs['aswc_product'] = array(
			'label'    => __( 'Subscription Settings', 'advanced-subscriptions-for-woocommerce' ),
			'target'   => 'aswc_product_target_section',
			// Add class for product.
			'class'    => apply_filters( 'aswc_swf_settings_tabs_class', array() ),
			'priority' => 80,
		);
		// Add tb for product.
		return apply_filters( 'aswc_swf_settings_tabs', $tabs );
	}



	/**
	 * This function is used to add custom fileds for subscription products.
	 *
	 * @name aswc_custom_product_fields_for_subscription
	 * @since 1.0.0
	 */
	public function aswc_custom_product_fields_for_subscription() {
		global $post;
		$post_id = $post->ID;
		$product = wc_get_product( $post_id );

		$aswc_subscription_number = aswc_get_meta_data( $post_id, 'aswc_subscription_number', true );
		if ( empty( $aswc_subscription_number ) ) {
			$aswc_subscription_number = 1;
		}
		$aswc_subscription_interval = aswc_get_meta_data( $post_id, 'aswc_subscription_interval', true );
		if ( empty( $aswc_subscription_interval ) ) {
			$aswc_subscription_interval = 'day';
		}

		$aswc_subscription_expiry_number        = aswc_get_meta_data( $post_id, 'aswc_subscription_expiry_number', true );
		$aswc_subscription_expiry_interval      = aswc_get_meta_data( $post_id, 'aswc_subscription_expiry_interval', true );
		$aswc_subscription_initial_signup_price = aswc_get_meta_data( $post_id, 'aswc_subscription_initial_signup_price', true );
		$aswc_subscription_free_trial_number    = aswc_get_meta_data( $post_id, 'aswc_subscription_free_trial_number', true );
		$aswc_subscription_free_trial_interval  = aswc_get_meta_data( $post_id, 'aswc_subscription_free_trial_interval', true );
		?>
		<div id="aswc_product_target_section" class="panel woocommerce_options_panel hidden">

			<p class="form-field aswc_subscription_number_field ">
				<label for="aswc_subscription_number">
				<?php esc_html_e( 'Subscriptions Per Interval', 'advanced-subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" required name="aswc_subscription_number" id="aswc_subscription_number" value="<?php echo esc_attr( $aswc_subscription_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription interval', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
				<select id="aswc_subscription_interval" name="aswc_subscription_interval" class="aswc_subscription_interval" >
					<?php foreach ( aswc_subscription_period() as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_subscription_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the subscriptions time interval for the product "for example 10 days"', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>
			<p class="form-field aswc_subscription_expiry_field ">
				<label for="aswc_subscription_expiry_number">
				<?php esc_html_e( 'Subscriptions Expiry Interval', 'advanced-subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" name="aswc_subscription_expiry_number" id="aswc_subscription_expiry_number" value="<?php echo esc_attr( $aswc_subscription_expiry_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription expiry', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
				<select id="aswc_subscription_expiry_interval" name="aswc_subscription_expiry_interval" class="aswc_subscription_expiry_interval" >
					<?php foreach ( aswc_subscription_expiry_period( $aswc_subscription_interval ) as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_subscription_expiry_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the subscriptions expiry time interval for the product "leave empty for unlimited"', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>
			<p class="form-field aswc_subscription_initial_signup_field ">
				<label for="aswc_subscription_initial_signup_price">
				<?php
				esc_html_e( 'Initial Signup fee', 'advanced-subscriptions-for-woocommerce' );
				echo esc_html( '(' . get_woocommerce_currency_symbol() . ')' );
				?>
				</label>
				<input type="number" class="short wc_input_price"  min="0" step="any" name="aswc_subscription_initial_signup_price" id="aswc_subscription_initial_signup_price" value="<?php echo esc_attr( $aswc_subscription_initial_signup_price ); ?>" placeholder="<?php esc_html_e( 'Enter signup fee', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
				
			<?php
				$description_text = __( 'Choose the subscriptions initial fee for the product "leave empty for no initial fee"', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>
			<p class="form-field aswc_subscription_free_trial_field">
				<label for="aswc_subscription_free_trial_number">
				<?php esc_html_e( 'Free trial interval', 'advanced-subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" name="aswc_subscription_free_trial_number" id="aswc_subscription_free_trial_number" value="<?php echo esc_attr( $aswc_subscription_free_trial_number ); ?>" placeholder="<?php esc_html_e( 'Enter free trial interval', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
				<select id="aswc_subscription_free_trial_interval" name="aswc_subscription_free_trial_interval" class="aswc_subscription_free_trial_interval" >
					<?php foreach ( aswc_subscription_period() as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_subscription_free_trial_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the trial period for subscription "leave empty for no trial period"', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>

		<?php
			wp_nonce_field( 'aswc_edit_nonce', 'aswc_edit_nonce_filed' );
			// Add filed on product edit page.
			do_action( 'aswc_product_edit_field', $post_id );
		if ( function_exists( 'learn_press_get_all_courses' ) ) {
			$courses                   = learn_press_get_all_courses();
						$saved_courses = get_post_meta( $post_id, '_aswc_learnpress_course', true ) ? get_post_meta( $post_id, '_aswc_learnpress_course', true ) : array();
			?>
				<p class="form-field aswc_learnpress_course_field">
				<?php
				if ( ! empty( $courses ) && is_array( $courses ) ) {
					?>
						<label for="aswc_learnpress_course">
						<?php esc_html_e( 'Attach LearnPress Courses', 'advanced-subscriptions-for-woocommerce' ); ?>
						</label>
						<select id="aswc_learnpress_course" class="aswc_learnpress_course" name="aswc_learnpress_course[]" multiple>
						<?php
						foreach ( $courses as $course_id ) {
							$course = learn_press_get_course( $course_id );
							?>
												<option value="<?php echo esc_attr( $course_id ); ?>" <?php selected( true, in_array( $course_id, $saved_courses, true ) ); ?> ><?php echo esc_attr( $course->get_title() ); ?></option>
							<?php
						}
						?>
						</select>
						<?php
				}
				?>
			<?php
		}
		?>
		</div>
		<?php
	}


	/**
	 * This function is used to save custom fields for subscription products.
	 *
	 * @name aswc_save_custom_product_fields_data_for_subscription
	 * @since 1.0.0
	 * @param    int    $post_id Post ID.
	 * @param    object $post post.
	 */
	public function aswc_save_custom_product_fields_data_for_subscription( $post_id, $post ) {

		if ( ! isset( $_POST['aswc_edit_nonce_filed'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aswc_edit_nonce_filed'] ) ), 'aswc_edit_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}
		$aswc_product = isset( $_POST['_aswc_product'] ) ? 'yes' : 'no';
		aswc_update_meta_data( $post_id, '_aswc_product', $aswc_product );
		if ( isset( $_POST['_aswc_product'] ) && ! empty( $_POST['_aswc_product'] ) ) {

			$aswc_subscription_number               = isset( $_POST['aswc_subscription_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_number'] ) ) : '';
			$aswc_subscription_interval             = isset( $_POST['aswc_subscription_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_interval'] ) ) : '';
			$aswc_subscription_expiry_number        = isset( $_POST['aswc_subscription_expiry_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_expiry_number'] ) ) : '';
			$aswc_subscription_expiry_interval      = isset( $_POST['aswc_subscription_expiry_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_expiry_interval'] ) ) : '';
			$aswc_subscription_initial_signup_price = isset( $_POST['aswc_subscription_initial_signup_price'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_initial_signup_price'] ) ) : '';
			$aswc_subscription_free_trial_number    = isset( $_POST['aswc_subscription_free_trial_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_free_trial_number'] ) ) : '';
			$aswc_subscription_free_trial_interval  = isset( $_POST['aswc_subscription_free_trial_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_free_trial_interval'] ) ) : '';

			aswc_update_meta_data( $post_id, 'aswc_subscription_number', $aswc_subscription_number );
			aswc_update_meta_data( $post_id, 'aswc_subscription_interval', $aswc_subscription_interval );
			aswc_update_meta_data( $post_id, 'aswc_subscription_expiry_number', $aswc_subscription_expiry_number );
			aswc_update_meta_data( $post_id, 'aswc_subscription_expiry_interval', $aswc_subscription_expiry_interval );
			aswc_update_meta_data( $post_id, 'aswc_subscription_initial_signup_price', $aswc_subscription_initial_signup_price );
			aswc_update_meta_data( $post_id, 'aswc_subscription_free_trial_number', $aswc_subscription_free_trial_number );
			aswc_update_meta_data( $post_id, 'aswc_subscription_free_trial_interval', $aswc_subscription_free_trial_interval );

			$learnpress_courses = isset( $_POST['aswc_learnpress_course'] ) ? wp_unslash( $_POST['aswc_learnpress_course'] ) : ''; //phpcs:ignore
			if ( is_array( $learnpress_courses ) ) {
				$learnpress_courses = array_map( 'sanitize_text_field', $learnpress_courses );
			} else {
				$learnpress_courses = sanitize_text_field( $learnpress_courses );
			}
			$all_attached_courses             = get_option( 'aswc_learnpress_course', array() );
			$all_attached_courses[ $post_id ] = $learnpress_courses;
			update_option( 'aswc_learnpress_course', $all_attached_courses );
			aswc_update_meta_data( $post_id, 'aswc_learnpress_course', $learnpress_courses );

			do_action( 'aswc_save_simple_subscription_field', $post_id, $_POST );
		}
	}

	/**
	 * This function is used to cancel susbcription.
	 *
	 * @name aswc_admin_cancel_susbcription
	 * @since 1.0.0
	 */
	public function aswc_admin_cancel_susbcription() {

		if ( isset( $_GET['aswc_subscription_status_admin'] ) && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$aswc_status          = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_status_admin'] ) );
			$aswc_subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) );
			if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				// Cancel subscription.
				$aswc_payment_type = aswc_get_meta_data( $aswc_subscription_id, 'aswc_payment_type', true );
				if ( 'aswc_manual_method' === $aswc_payment_type ) {
					aswc_update_meta_data( $aswc_subscription_id, 'aswc_subscription_status', 'cancelled' );
					aswc_update_meta_data( $aswc_subscription_id, 'aswc_subscription_cancelled_by', 'by_admin' );
					aswc_update_meta_data( $aswc_subscription_id, 'aswc_subscription_cancelled_date', current_time( 'timestamp' ) );

				} else {

					do_action( 'aswc_subscription_cancel', $aswc_subscription_id, 'Cancel' );
					aswc_update_meta_data( $aswc_subscription_id, 'aswc_subscription_cancelled_by', 'by_admin' );
					aswc_update_meta_data( $aswc_subscription_id, 'aswc_subscription_cancelled_date', current_time( 'timestamp' ) );
				}
				$redirect_url = admin_url() . 'admin.php?page=subscriptions_jc_for_woocommerce_menu&sfw_tab=aswc-subscriptions-table';
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * This function is used to custom field compatibility with WPML.
	 *
	 * @name aswc_add_lock_custom_fields_ids.
	 * @since 1.0.0
	 * @param array $ids ids.
	 */
	public function aswc_add_lock_custom_fields_ids( $ids ) {

		$ids[] = '_aswc_product';
		$ids[] = 'aswc_subscription_number';
		$ids[] = 'aswc_subscription_interval';
		$ids[] = 'aswc_subscription_expiry_number';
		$ids[] = 'aswc_subscription_expiry_interval';
		$ids[] = 'aswc_subscription_initial_signup_price';
		$ids[] = 'aswc_subscription_free_trial_number';
		$ids[] = 'aswc_subscription_free_trial_interval';

		return apply_filters( 'aswc_add_lock_fields_ids_pro', $ids );
	}



	/**
	 * Get Count
	 *
	 * @param string  $status .
	 * @param string  $action .
	 * @param boolean $type .
	 * @return $result .
	 */
	public function aswc_get_count( $status = 'all', $action = 'count', $type = false ) {
		return 0;
	}

	/**
	 * Previously we removed the automatically generated menu and added our
	 * own, which caused duplicate entries after recent changes. The removal
	 * callback is no longer required.
	 */
	public function aswc_remove_subscription_custom_menu() {}

	/**
	 * Highlight WooCommerce menu when viewing subscriptions list.
	 *
	 * @param string $parent_file Parent file slug.
	 * @return string
	 */
	public function fix_advanced_subscriptions_menu_parent( $parent_file ) {
		global $submenu_file, $current_screen;
		if ( ( isset( $current_screen->post_type ) && 'aswc_subscriptions' === $current_screen->post_type ) ||
		( isset( $current_screen->id ) && 'woocommerce_page_wc-orders--aswc_subscriptions' === $current_screen->id ) ) {
			$parent_file = 'woocommerce';
			if ( isset( $current_screen->id ) && 'woocommerce_page_wc-orders--aswc_subscriptions' === $current_screen->id ) {
				$submenu_file = 'wc-orders--aswc_subscriptions'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				$submenu_file = 'edit.php?post_type=aswc_subscriptions'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}
		return $parent_file;
	}

	/**
	 * Api settings fields.
	 *
	 * @since 1.0.0
	 * @param array $aswc_subscription_box_settings Api fields.
	 */
	public function aswc_subscription_box_settings_fields( $aswc_subscription_box_settings ) {

		$pro_group_tag                  = '';
		$aswc_subscription_box_settings = array(
			array(
				'title'       => __( 'Enable Multi-Product Subscription Feature', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Enable this to Create and Sell Multi-Product Subscription', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_enable_subscription_box_features',
				'value'       => get_option( 'aswc_enable_subscription_box_features' ),
				'class'       => 'aswc-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'advanced-subscriptions-for-woocommerce' ),
					'no'  => __( 'NO', 'advanced-subscriptions-for-woocommerce' ),
				),
			),

			array(
				'title'       => __( 'Add to cart text For Multi-Product Subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Use this option to change add to cart button text For Multi-Product Subscription Product.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_subscription_box_add_to_cart_text',
				'value'       => get_option( 'aswc_subscription_box_add_to_cart_text', '' ),
				'class'       => 'aswc-text-class',
				'placeholder' => __( 'Multi-Product Subscription Add to cart button text', 'advanced-subscriptions-for-woocommerce' ),
			),
			array(
				'title'       => __( 'Place order text For Multi-Product Subscription', 'advanced-subscriptions-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Use this option to change place order button text For Multi-Product Subscription Product.', 'advanced-subscriptions-for-woocommerce' ),
				'id'          => 'aswc_subscription_box_place_order_button_text',
				'value'       => get_option( 'aswc_subscription_box_place_order_button_text', '' ),
				'class'       => 'aswc-text-class',
				'placeholder' => __( 'Multi-Product Subscription Place order button text', 'advanced-subscriptions-for-woocommerce' ),
			),

			array(
				'name'  => __( 'To Create Multiple Multi-Product Subscription Feature Need Use Pro Version', 'advanced-subscriptions-for-woocommerce' ),
				'type'  => 'information',
				'id'    => 'aswc_enable_subscription_box_muti_features',
				'class' => 'aswc-information-class ' . $pro_group_tag,

			),

			array(
				'type'        => 'button',
				'id'          => 'aswc_save_subscription_box_settings',
				'button_text' => esc_html__( 'Save Settings', 'advanced-subscriptions-for-woocommerce' ),
				'class'       => 'aswc-button-class',
			),
		);

		return $aswc_subscription_box_settings;
	}

	/**
	 * This function is used to add multi-product subscription product type in inventory tab.
	 *
	 * @param array $tabs as tabs.
	 * @return $tabs .
	 */
	public function aswc_subscription_box_product_data_tabs( $tabs ) {
		$tabs['inventory']['class'][] = 'show_if_subscription_box';
		return $tabs;
	}

	/**
	 * Register multi-product subscription product type in product dropdown.
	 *
	 * @param array $types as type.
	 * @return array
	 */
	public function aswc_register_subscription_box_product_type( $types ) {
		$enable_subscription_box = get_option( 'aswc_enable_subscription_box_features' );
		if ( aswc_is_true( $enable_subscription_box ) ) {
			$types['subscription_box'] = esc_html__( 'Multi-Product Subscription', 'advanced-subscriptions-for-woocommerce' );
		}
		return $types;
	}

	/**
	 * This function is used to add multi-product subscription settings for product.
	 *
	 * @name aswc_custom_product_tab_for_subscription
	 * @since 1.0.0
	 * @param    Array $tabs Products tabs array.
	 * @return   Array  $tabs
	 */
	public function aswc_custom_product_tab_for_subscription_box( $tabs ) {

		$tabs['aswc_subscription_box_product'] = array(
			'label'    => __( 'Multi-Product Subscription Settings', 'advanced-subscriptions-for-woocommerce' ),
			'target'   => 'aswc_subscription_box_product_target_section',
			'class'    => '',
			'priority' => 80,
		);

		return $tabs;
	}

	/**
	 * Function to show multi-product subscription html.
	 *
	 * @return void
	 */
	public function aswc_custom_product_fields_for_subscription_box() {
		global $post;
		$post_id = $post->ID;
		$product = wc_get_product( $post_id );

		$aswc_subscription_box_number = aswc_get_meta_data( $post_id, 'aswc_subscription_number', true );
		if ( empty( $aswc_subscription_box_number ) ) {
			$aswc_subscription_box_number = 1;
		}
		$aswc_subscription_box_interval = aswc_get_meta_data( $post_id, 'aswc_subscription_interval', true );
		if ( empty( $aswc_subscription_box_interval ) ) {
			$aswc_subscription_box_interval = 'day';
		}

		$aswc_subscription_box_expiry_number   = aswc_get_meta_data( $post_id, 'aswc_subscription_expiry_number', true );
		$aswc_subscription_box_expiry_interval = aswc_get_meta_data( $post_id, 'aswc_subscription_expiry_interval', true );
		$aswc_subscription_box_price           = aswc_get_meta_data( $post_id, 'aswc_subscription_box_price', true );
		$aswc_subscription_box_setup           = aswc_get_meta_data( $post_id, 'aswc_subscription_box_setup', true );
		$aswc_subscription_box_products        = aswc_get_meta_data( $post_id, 'aswc_subscription_box_products', true );
		$aswc_subscription_box_categories      = aswc_get_meta_data( $post_id, 'aswc_subscription_box_categories', true );
		$aswc_manage_subscription_box_price    = aswc_get_meta_data( $post_id, 'aswc_manage_subscription_box_price', true );

		// Ensure it's an array.
		$aswc_subscription_box_categories = is_array( $aswc_subscription_box_categories ) ? $aswc_subscription_box_categories : array();
		$selected_category_ids            = array();

		// Convert slugs to term IDs.
		if ( ! empty( $aswc_subscription_box_categories ) ) {
			foreach ( $aswc_subscription_box_categories as $slug ) {
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				if ( $term ) {
					$selected_category_ids[] = $term->name; // Store term IDs.
				}
			}
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		$aswc_subscription_box_step_label = aswc_get_meta_data( $post_id, 'aswc_subscription_box_step_label', true );

		?>
		<div id="aswc_subscription_box_product_target_section" class="panel woocommerce_options_panel hidden">

			<strong><?php esc_html_e( 'Subscriptions Setting For Multi-Product', 'advanced-subscriptions-for-woocommerce' ); ?></strong>

			<p class="form-field aswc_subscription_box_price_field ">
					<label for="aswc_subscription_box_price">
					<?php esc_html_e( 'Multi-Product Subscriptions Price', 'advanced-subscriptions-for-woocommerce' ); ?>
					</label>
					<input type="number" class="short wc_input_number"  min="1"  name="aswc_subscription_box_price" id="aswc_subscription_box_price" value="<?php echo esc_attr( $aswc_subscription_box_price ); ?>" placeholder="<?php esc_html_e( 'Enter multi-product subscription price', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
				
			</p>
			<p class="form-field aswc_manage_subscription_box_price_field aswc_subscription_box_price_field_pro <?php echo esc_attr( $pro_group_tag ); ?>">
				<label for="aswc_manage_subscription_box_price"><?php esc_html_e( 'Manage multi-product subscription Price through all selected products', 'advanced-subscriptions-for-woocommerce' ); ?></label>
								<input type="checkbox" id="aswc_manage_subscription_box_price" name="aswc_manage_subscription_box_price" value="on"  <?php echo esc_attr( aswc_is_true( $aswc_manage_subscription_box_price ) ? 'checked' : null ); ?> />
			</p>
			<p class="form-field aswc_subscription_box_number_field ">
				<label for="aswc_subscription_box_number">
				<?php esc_html_e( 'Subscriptions Per Interval', 'advanced-subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" required name="aswc_subscription_box_number" id="aswc_subscription_box_number" value="<?php echo esc_attr( $aswc_subscription_box_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription interval', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
				<select id="aswc_subscription_box_interval" name="aswc_subscription_box_interval" class="aswc_subscription_box_interval" >
					<?php foreach ( aswc_subscription_period() as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_subscription_box_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the subscriptions time interval for the product "for example 10 days"', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>
			<p class="form-field aswc_subscription_box_expiry_field ">
				<label for="aswc_subscription_box_expiry_number">
				<?php esc_html_e( 'Subscriptions Expiry Interval', 'advanced-subscriptions-for-woocommerce' ); ?>
				</label>
				<input type="number" class="short wc_input_number"  min="1" name="aswc_subscription_box_expiry_number" id="aswc_subscription_box_expiry_number" value="<?php echo esc_attr( $aswc_subscription_box_expiry_number ); ?>" placeholder="<?php esc_html_e( 'Enter subscription expiry', 'advanced-subscriptions-for-woocommerce' ); ?>"> 
				<select id="aswc_subscription_box_expiry_interval" name="aswc_subscription_box_expiry_interval" class="aswc_subscription_box_expiry_interval" >
					<?php foreach ( aswc_subscription_expiry_period( $aswc_subscription_box_interval ) as $value => $label ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $aswc_subscription_box_expiry_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php } ?>
					</select>
			<?php
				$description_text = __( 'Choose the subscriptions expiry time interval for the product "leave empty for unlimited"', 'advanced-subscriptions-for-woocommerce' );
				echo wp_kses_post( wc_help_tip( $description_text ) ); // WPCS: XSS ok.
			?>
			</p>

			<strong><?php esc_html_e( 'Setup For Multi-Product Subscription', 'advanced-subscriptions-for-woocommerce' ); ?></strong>
				<p class="form-field aswc_subscription_box_setup_field ">
					<label for="aswc_subscription_box_setup">
						<?php esc_html_e( 'Apply Multi-Product Subscription To', 'advanced-subscriptions-for-woocommerce' ); ?>
					</label>
					<select id="aswc_subscription_box_setup" name="aswc_subscription_box_setup">
						<option value="specific_products" <?php selected( $aswc_subscription_box_setup, 'specific_products' ); ?>><?php esc_html_e( 'Specific Products', 'advanced-subscriptions-for-woocommerce' ); ?></option>
						<option value="specific_categories" <?php selected( $aswc_subscription_box_setup, 'specific_categories' ); ?>><?php esc_html_e( 'Specific Categories', 'advanced-subscriptions-for-woocommerce' ); ?></option>
					</select>
				</p>

				<p class="form-field aswc_subscription_box_products_field" style="display: none;">
					<label for="aswc_subscription_box_products">
						<?php esc_html_e( 'Select Products', 'advanced-subscriptions-for-woocommerce' ); ?>
					</label>
					<select id="aswc_subscription_box_products" name="aswc_subscription_box_products[]" class="wc-product-search" multiple="multiple" style="width: 100%;"
						data-placeholder="<?php esc_attr_e( 'Search for a product...', 'advanced-subscriptions-for-woocommerce' ); ?>"
						data-action="woocommerce_json_search_products_and_variations">
						<?php
						if ( ! empty( $aswc_subscription_box_products ) ) {
							foreach ( $aswc_subscription_box_products as $product_id ) {
								$product = wc_get_product( $product_id );
								if ( $product ) {
									echo '<option value="' . esc_attr( $product_id ) . '" selected>' . esc_html( $product->get_name() ) . '</option>';
								}
							}
						}
						?>
					</select>
				</p>

				<p class="form-field aswc_subscription_box_categories_field" style="display: none;">
					<label for="aswc_subscription_box_categories">
						<?php esc_html_e( 'Select Categories', 'advanced-subscriptions-for-woocommerce' ); ?>
					</label>
					<select id="aswc_subscription_box_categories" name="aswc_subscription_box_categories[]" class="wc-category-search" multiple="multiple" style="width: 100%;"data-placeholder="<?php esc_attr_e( 'Search for categories...', 'advanced-subscriptions-for-woocommerce' ); ?>"data-action="woocommerce_json_search_categories"> 
						
						<?php
						if ( ! empty( $categories ) ) {
							foreach ( $categories as $category ) {
								if ( in_array( $category->name, $selected_category_ids, true ) ) {

										$selected = in_array( (int) $category->name, $selected_category_ids, true ) ? 'selected="selected"' : '';
									echo '<option value="' . esc_attr( $category->name ) . '" ' . esc_html( $selected ) . '>' . esc_html( $category->name ) . '</option>';
								}
							}
						}
						?>

					</select>
								</p>
								<p class="form-field aswc_subscription_box_setup">
					<label for="aswc_subscription_box_step_label">
					<?php esc_html_e( 'Multi-Product Step Label', 'advanced-subscriptions-for-woocommerce' ); ?>
					</label>
					<input type="text" class="short" name="aswc_subscription_box_step_label" id="aswc_subscription_box_step_label" value="<?php echo esc_attr( $aswc_subscription_box_step_label ); ?>" placeholder="<?php esc_html_e( 'Enter step label', 'advanced-subscriptions-for-woocommerce' ); ?>">
								</p>

						<?php
						wp_nonce_field( 'aswc_edit_nonce', 'aswc_edit_nonce_filed' );
						// Add filed on product edit page.
						do_action( 'aswc_subscription_box_product_edit_field', $post_id );

						?>
		</div>
		<?php
	}

	/**
	 * Function to save multi-product subscription settings.
	 *
	 * @param int    $post_id as post id.
	 * @param object $post as post.
	 * @return void
	 */
	public function aswc_save_subscription_box_data_for_subscription( $post_id, $post ) {
		if ( ! isset( $_POST['aswc_edit_nonce_filed'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aswc_edit_nonce_filed'] ) ), 'aswc_edit_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}
		$product      = wc_get_product( $post_id );
		$product_type = isset( $_POST['product-type'] ) ? sanitize_text_field( wp_unslash( $_POST['product-type'] ) ) : '';

		if ( 'subscription_box' === $product_type ) {

			$aswc_subscription_box_price                = isset( $_POST['aswc_subscription_box_price'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_box_price'] ) ) : '';
			$aswc_subscription_box_number               = isset( $_POST['aswc_subscription_box_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_box_number'] ) ) : '';
			$aswc_subscription_box_interval             = isset( $_POST['aswc_subscription_box_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_box_interval'] ) ) : '';
			$aswc_subscription_box_expiry_number        = isset( $_POST['aswc_subscription_box_expiry_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_box_expiry_number'] ) ) : '';
			$aswc_subscription_box_expiry_interval      = isset( $_POST['aswc_subscription_box_expiry_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_box_expiry_interval'] ) ) : '';
			$aswc_subscription_box_setup                = isset( $_POST['aswc_subscription_box_setup'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_box_setup'] ) ) : '';
			$aswc_subscription_box_step_label           = isset( $_POST['aswc_subscription_box_step_label'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_subscription_box_step_label'] ) ) : '';
						$aswc_subscription_box_products = isset( $_POST['aswc_subscription_box_products'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['aswc_subscription_box_products'] ) ) : array();
			$aswc_manage_subscription_box_price         = isset( $_POST['aswc_manage_subscription_box_price'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_manage_subscription_box_price'] ) ) : '';

			if ( $aswc_subscription_box_products ) {
				aswc_update_meta_data( $post_id, 'aswc_subscription_box_products', $aswc_subscription_box_products );
			}
						$selected_categories = isset( $_POST['aswc_subscription_box_categories'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['aswc_subscription_box_categories'] ) ) : array();
			if ( $selected_categories ) {
				aswc_update_meta_data( $post_id, 'aswc_subscription_box_categories', $selected_categories );
			}

			aswc_update_meta_data( $post_id, 'aswc_subscription_box_price', $aswc_subscription_box_price );
			aswc_update_meta_data( $post_id, '_price', $aswc_subscription_box_price );
			aswc_update_meta_data( $post_id, 'aswc_subscription_number', $aswc_subscription_box_number );
			aswc_update_meta_data( $post_id, 'aswc_subscription_interval', $aswc_subscription_box_interval );
			aswc_update_meta_data( $post_id, 'aswc_subscription_expiry_number', $aswc_subscription_box_expiry_number );
			aswc_update_meta_data( $post_id, 'aswc_subscription_expiry_interval', $aswc_subscription_box_expiry_interval );
			aswc_update_meta_data( $post_id, 'aswc_subscription_box_setup', $aswc_subscription_box_setup );
			aswc_update_meta_data( $post_id, 'aswc_subscription_box_step_label', $aswc_subscription_box_step_label );
			aswc_update_meta_data( $post_id, 'aswc_subscription_box_products', $aswc_subscription_box_products );

			if ( aswc_is_true( $aswc_manage_subscription_box_price ) ) {
				aswc_update_meta_data( $post_id, 'aswc_manage_subscription_box_price', $aswc_manage_subscription_box_price );
			} else {
				aswc_update_meta_data( $post_id, 'aswc_manage_subscription_box_price', '' );
			}

			if ( ! get_option( 'aswc_first_subscription_box_id', false ) ) {
				update_option( 'aswc_first_subscription_box_id', $post_id );
			}
		}
	}

	/**
	 * This function is used to cancel susbcription.
	 *
	 * @name aswc_admin_reactivate_onhold_susbcription
	 * @since 1.0.0
	 */
	public function aswc_admin_reactivate_onhold_susbcription() {

		if ( isset( $_GET['aswc_subscription_status_admin_reactivate'] ) && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$aswc_status          = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_status_admin_reactivate'] ) );
			$aswc_subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) );
			if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				// reactivate subscription.

				if ( 'on-hold' === $aswc_status ) {
					aswc_update_meta_data( $aswc_subscription_id, 'aswc_subscription_status', 'active' );
					aswc_send_email_for_active_susbcription( $aswc_subscription_id );
				}
				$redirect_url = admin_url() . 'admin.php?page=subscriptions_jc_for_woocommerce_menu&sfw_tab=aswc-subscriptions-table';
				wp_safe_redirect( $redirect_url );
				exit;
			}
		}
	}

	/**
	 * Display the subscription ID in admin order details.
	 *
	 * @name aswc_show_subscription_id_in_order_details
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function aswc_show_subscription_id_in_order_details( $order ) {
		if ( ! $order instanceof WC_Order ) {
				return;
		}

		if ( ! function_exists( 'aswc_is_subscription' ) || ! function_exists( 'aswc_order_contains_subscription' ) || ! function_exists( 'aswc_get_subscriptions_for_order' ) || ! function_exists( 'aswc_get_edit_post_link' ) ) {
					return;
		}

		if ( aswc_is_subscription( $order ) ) {
					return;
		}

		if ( ! aswc_order_contains_subscription( $order, array( 'parent', 'renewal' ) ) ) {
				return;
		}

		$subscriptions = aswc_get_subscriptions_for_order(
			$order,
			array(
				'order_type' => array( 'parent', 'renewal' ),
			)
		);

		if ( empty( $subscriptions ) ) {
				return;
		}

			$subscription_links = array();

		foreach ( $subscriptions as $subscription ) {

					$link = aswc_get_edit_post_link( $subscription );

			if ( $link ) {
				$subscription_links[] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( $link ),
					sprintf(
												/* translators: %s: subscription ID */
						esc_html__( '#%s', 'advanced-subscriptions-for-woocommerce' ),
						esc_html( $subscription->get_id() )
					)
				);
			}
		}

		if ( empty( $subscription_links ) ) {
					return;
		}
		printf(
			'<p><strong>%1$s:</strong> %2$s</p>',
			esc_html__( 'Subscription ID', 'advanced-subscriptions-for-woocommerce' ),
			wp_kses_post( implode( ', ', $subscription_links ) )
		);
	}


	/**
	 * Add 'Subscription Support' column on payment gateways page.
	 *
	 * @param mixed $default_columns default_columns.
	 * @since 1.0.0
	 */
	public function aswc_subscription_support_in_payment_gateway( $default_columns ) {
		$new_column['aswc_sub_renewal'] = esc_html__( 'Subscription And Renewal Supported', 'advanced-subscriptions-for-woocommerce' );
		// Place at second last position.
		$position        = count( $default_columns ) - 1;
		$default_columns = array_slice( $default_columns, 0, $position, true ) + $new_column + array_slice( $default_columns, $position, count( $default_columns ) - $position, true );
		return $default_columns;
	}

	/**
	 * 'Subscription Support' content on payment gateways page.
	 *
	 * @param mixed $gateway gateway.
	 * @since 1.0.0
	 */
	public function aswc_subscription_content_in_payment_gateway( $gateway ) {

		echo '<td class="aswc_subs_renewal_supported">';

		if ( 'stripe' === $gateway->id || 'payfast' === $gateway->id || 'amazon_payments_advanced' === $gateway->id || 'woocommerce_payments' === $gateway->id || 'ppcp-gateway' === $gateway->id || 'authnet' === $gateway->id || 'braintree_credit_card' === $gateway->id || 'eway' === $gateway->id || 'mollie_wc_gateway_' === $gateway->id || 'mollie_stand_in' === $gateway->id || 'multisafepay_' === $gateway->id || 'payhere' === $gateway->id || 'stripe_' === $gateway->id ) {
			echo '<span class="status-enabled">' . esc_html__( 'Yes', 'advanced-subscriptions-for-woocommerce' ) . '</span>';
		} else {
			echo '<span class="status-disabled">' . esc_html__( 'No', 'advanced-subscriptions-for-woocommerce' ) . '</span>';
		}
		echo '</td>';
	}
}

