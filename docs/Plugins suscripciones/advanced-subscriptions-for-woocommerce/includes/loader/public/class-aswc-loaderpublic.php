<?php // phpcs:ignoreFile
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://plugins.joseconti.com
 * @since 1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/public
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 * namespace woocommerce_subscriptions_pro_public.
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/public
 */
class Aswc_LoaderPublic {

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
	 * The coupon error of this plugin.
	 *
	 * @since 1.0.0
	 * @access   private
	 * @var      string    $coupon_error    The current version of this plugin.
	 */
	private $coupon_error;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function aswc_public_enqueue_styles() {
                if ( is_product() || is_shop() ) {

                        wp_enqueue_style( 'aswc-loader-public', ASWC_INCLUDES_DIR_URL . 'public/css/aswc-loader-public.css', array(), $this->version, 'all' );
                }
                if ( is_account_page() ) {
                        wp_enqueue_style( 'aswc-loader-account-style', ASWC_INCLUDES_DIR_URL . 'public/css/aswc-loader-my-account-page.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function aswc_public_enqueue_scripts() {

		wp_register_script( $this->plugin_name . '-min', ASWC_INCLUDES_DIR_URL . 'public/js/aswc-public.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name . '-min' );

		wp_localize_script(
			$this->plugin_name . '-min',
			'aswc_public_param',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'aswc_public_nonce' ),
			)
		);

		if ( is_product() ) {

                        wp_register_script( 'aswc-loader-single-product', ASWC_INCLUDES_DIR_URL . 'public/js/aswc-loader-single-product.js', array( 'jquery' ), $this->version, false );

			$aswc_array = array(
				'ajaxurl'              => admin_url( 'admin-ajax.php' ),
                                'aswc_nonce'        => wp_create_nonce( 'aswc-verify-nonce' ),
				'aswc_is_expiry_enable' => aswc_check_allow_expiry_by_customer(),
				'day'                  => __( 'Days', 'advanced-subscriptions-for-woocommerce' ),
				'week'                 => __( 'Weeks', 'advanced-subscriptions-for-woocommerce' ),
				'month'                => __( 'Months', 'advanced-subscriptions-for-woocommerce' ),
				'year'                 => __( 'Years', 'advanced-subscriptions-for-woocommerce' ),

			);
                                                wp_localize_script( 'aswc-loader-single-product', 'aswc_pro_public_param', $aswc_array );
                                                wp_enqueue_script( 'aswc-loader-single-product' );

		}
		if ( is_account_page() ) {
                        wp_register_script( 'aswc-loader-account', ASWC_INCLUDES_DIR_URL . 'public/js/aswc-loader-account.js', array( 'jquery' ), $this->version, false );
			$aswc_array = array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
                                'aswc_nonce' => wp_create_nonce( 'aswc-verify-nonce' ),
				'error_text'    => __( 'Please enter coupon code', 'advanced-subscriptions-for-woocommerce' ),
			);

                        wp_localize_script( 'aswc-loader-account', 'aswc_pro_public_param', $aswc_array );
                        wp_enqueue_script( 'aswc-loader-account' );
		}
	}

	/**
	 * This function is used to create susbcription expiry field in product page.
	 *
	 * @name aswc_woocommerce_before_add_to_cart_button
	 * @since 1.0.0
	 */
	public function aswc_woocommerce_before_add_to_cart_button() {
		global $product;
		if ( isset( $product ) && ! empty( $product ) ) {
			$product_id = $product->get_id();

			if ( aswc_check_product_is_subscription( $product ) && aswc_check_allow_expiry_by_customer() ) {
				$subscription_interval = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );
				if ( empty( $subscription_interval ) ) {
					$subscription_interval = 'day';
				}
				?>
					<div class="aswc_expiry_interval_field_wrap">
						<p class="aswc_expiry_interval_field">
							<label for="aswc_expiry_number" class="aswc_label"><?php esc_html_e( 'Subscriptions Expiry Interval', 'advanced-subscriptions-for-woocommerce' ); ?></label>
							<input type="hidden" name="aswc_before_atc_nonce" value=<?php echo esc_html( wp_create_nonce( 'aswc_before_atc_nonce' ) ); ?>>

							<input type="number" min="1" name="aswc_expiry_number" id="aswc_expiry_number" class="aswc_expiry_number" placeholder="<?php esc_attr_e( 'Enter subscription expiry', 'advanced-subscriptions-for-woocommerce' ); ?>">
							<select id="aswc_expiry_number_interval" name="aswc_expiry_number_interval" class="aswc_expiry_number_interval">
							<?php foreach ( aswc_subscription_expiry_period( $subscription_interval ) as $value => $label ) { ?>
								<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php } ?>
							</select>
						</p>
					</div>
				<?php
			}
		}
	}

	/**
	 * This function is used to add subscription expiry date by customer.
	 *
	 * @name aswc_woocommerce_add_cart_item_data
	 * @param array $the_cart_data the_cart_data.
	 * @param int   $product_id product_id.
	 * @param int   $variation_id variation_id.
	 * @since 1.0.0
	 */
	public function aswc_woocommerce_add_cart_item_data( $the_cart_data, $product_id, $variation_id ) {

		$product_id = empty( $variation_id ) ? $product_id : $variation_id;
		$product    = wc_get_product( $product_id );
		if ( aswc_check_product_is_subscription( $product ) ) {

			if ( isset( $_POST['aswc_expiry_number'] ) && ! empty( $_POST['aswc_expiry_number'] ) ) {
				if ( ! isset( $_POST['aswc_before_atc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aswc_before_atc_nonce'] ) ), 'aswc_before_atc_nonce' ) ) {
					return;
				}
				$aswc_expiry_number = isset( $_POST['aswc_expiry_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_expiry_number'] ) ) : '';

				$aswc_subscription_number = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );

				if ( $aswc_expiry_number < $aswc_subscription_number ) {
					return $the_cart_data;
				}
				$aswc_expiry_number_interval = isset( $_POST['aswc_expiry_number_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_expiry_number_interval'] ) ) : '';

				$item_meta['aswc_expiry_number']          = $aswc_expiry_number;
				$item_meta['aswc_expiry_number_interval'] = $aswc_expiry_number_interval;

				$item_meta                      = apply_filters( 'aswc_add_cart_item_data', $item_meta, $the_cart_data, $product_id, $variation_id );
				$the_cart_data ['product_meta'] = array( 'meta_data' => $item_meta );
			}
		}

		return $the_cart_data;
	}

	/**
	 * This function is used to restrict expiry.
	 *
	 * @name aswc_expiry_add_to_cart_validation
	 * @param bool $validate validate.
	 * @param int  $product_id product_id.
	 * @param int  $quantity quantity.
	 * @since 1.0.0
	 */
	public function aswc_expiry_add_to_cart_validation( $validate, $product_id, $quantity ) {
		$product = wc_get_product( $product_id );
		if ( aswc_check_product_is_subscription( $product ) ) {
			if ( isset( $_POST['aswc_expiry_number'] ) && ! empty( $_POST['aswc_expiry_number'] ) ) {
				if ( ! isset( $_POST['aswc_before_atc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aswc_before_atc_nonce'] ) ), 'aswc_before_atc_nonce' ) ) {
					return;
				}

				$aswc_expiry_number = isset( $_POST['aswc_expiry_number'] ) ? sanitize_text_field( wp_unslash( $_POST['aswc_expiry_number'] ) ) : '';

				$aswc_subscription_number = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );

				if ( $aswc_expiry_number < $aswc_subscription_number ) {
					$validate = false;
					wc_add_notice( __( 'Expiry Interval must be greater than subscription interval.', 'advanced-subscriptions-for-woocommerce' ), 'error' );

				}
			}
		}
		return $validate;
	}

	/**
	 * This function is used to show subscription expiry date by customer in cart page.
	 *
	 * @name aswc_show_time_interval_on_cart
	 * @param string $aswc_price_html price.
	 * @param int    $product_id product_id.
	 * @param array  $cart_item cart_item.
	 * @since 1.0.0
	 */
	public function aswc_show_time_interval_on_cart( $aswc_price_html, $product_id, $cart_item ) {
		if ( is_cart() || is_checkout() ) {
			if ( isset( $cart_item ) && ! empty( $cart_item ) && is_array( $cart_item ) ) {
				if ( aswc_check_product_is_subscription( $cart_item['data'] ) ) {
					$aswc_product_id = $cart_item['data']->get_id();
					if ( $product_id == $aswc_product_id ) {
						if ( isset( $cart_item['product_meta']['meta_data'] ) ) {
							if ( isset( $cart_item['product_meta']['meta_data']['aswc_expiry_number'] ) ) {
								$aswc_expiry_number          = $cart_item['product_meta']['meta_data']['aswc_expiry_number'];
								$aswc_expiry_number_interval = $cart_item['product_meta']['meta_data']['aswc_expiry_number_interval'];
								switch ( $aswc_expiry_number_interval ) {
									case 'day':
										/* translators: %s: search term */
										$aswc_price_html = sprintf( _n( '%s Day', '%s Days', $aswc_expiry_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_expiry_number );
										break;
									case 'week':
										/* translators: %s: search term */
										$aswc_price_html = sprintf( _n( '%s Week', '%s Weeks', $aswc_expiry_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_expiry_number );
										break;
									case 'month':
										/* translators: %s: search term */
										$aswc_price_html = sprintf( _n( '%s Month', '%s Months', $aswc_expiry_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_expiry_number );
										break;
									case 'year':
										/* translators: %s: search term */
										$aswc_price_html = sprintf( _n( '%s Year', '%s Years', $aswc_expiry_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_expiry_number );
										break;
								}
							}
						}
					}
				}
			}
		}
		return $aswc_price_html;
	}

	/**
	 * This function is used to show subscription expiry date by customer in cart page.
	 *
	 * @name aswc_change_subscription_expiry_by_customer
	 * @param array $aswc_recurring_data aswc_recurring_data.
	 * @param array $cart_item cart_item.
	 * @since 1.0.0
	 */
	public function aswc_change_subscription_expiry_by_customer( $aswc_recurring_data, $cart_item ) {

		if ( isset( $cart_item['product_meta']['meta_data']['aswc_expiry_number'] ) ) {
			$aswc_expiry_number                                      = $cart_item['product_meta']['meta_data']['aswc_expiry_number'];
			$aswc_expiry_number_interval                             = $cart_item['product_meta']['meta_data']['aswc_expiry_number_interval'];
			$aswc_recurring_data['aswc_subscription_expiry_number']   = $aswc_expiry_number;
			$aswc_recurring_data['aswc_subscription_expiry_interval'] = $aswc_expiry_number_interval;
		}
		$aswc_availabe_coupon = WC()->cart->get_applied_coupons();
		if ( ! empty( $aswc_availabe_coupon ) && is_array( $aswc_availabe_coupon ) ) {
			foreach ( WC()->cart->get_applied_coupons() as $code ) {
				$coupon      = new WC_Coupon( $code );
				$coupon_type = $coupon->get_discount_type();
				$coupon_id   = $coupon->get_id();

				$giftcardcoupon = aswc_get_meta_data( $coupon_id, 'aswc_wgm_giftcard_coupon', true );

				if ( 'initial_fee_discount' == $coupon_type ) {
					$aswc_recurring_data['initial_fee_discount'] = $code;
				} elseif ( 'initial_fee_percent_discount' == $coupon_type ) {
					$aswc_recurring_data['initial_fee_percent_discount'] = $code;
				} elseif ( 'recurring_product_discount' == $coupon_type ) {
					$aswc_recurring_data['recurring_product_discount'] = $code;
				} elseif ( 'recurring_product_percent_discount' == $coupon_type ) {
					$aswc_recurring_data['recurring_product_percent_discount'] = $code;
				} elseif ( ! empty( $giftcardcoupon ) && 'fixed_cart' == $coupon_type ) {
					if ( aswc_get_subscription_coupon_enable_for_gc() ) {
						$aswc_recurring_data['aswc_wgm_giftcard_coupon'] = $code;
					}
				}
			}
		}

		return $aswc_recurring_data;
	}

	/**
	 * This function is used to get available payment method.
	 *
	 * @name aswc_manual_payment_gateway_for_woocommerce
	 * @param array  $supported_payment_method supported_payment_method.
	 * @param string $payment_method payment_method.
	 * @since 1.0.0
	 */
	public function aswc_manual_payment_gateway_for_woocommerce( $supported_payment_method, $payment_method ) {

		// No manual restrictions required for removed gateways.
		if ( aswc_enbale_accept_manual_payment() ) {
			if ( 'bacs' == $payment_method ) {
				$supported_payment_method[] = $payment_method;
			} elseif ( 'cheque' == $payment_method ) {
				$supported_payment_method[] = $payment_method;
			} elseif ( 'cod' == $payment_method ) {
				$supported_payment_method[] = $payment_method;
			}
		}
		return $supported_payment_method;
	}

        /**
         * Outputs pause or reactivate buttons for the subscription.
         *
         * @name aswc_order_details_html_for_paused_subscription
         * @param int $aswc_subscription_id aswc_subscription_id.
         * @since 1.0.0
         */
       public function aswc_order_details_html_for_paused_subscription( $aswc_subscription_id ) {
               $buttons = array();

               if ( aswc_enable_pause_susbcription_by_customer() ) {
                       $aswc_status = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_status', true );
                       if ( 'active' === $aswc_status ) {
                               $buttons[] = sprintf(
                                       '<a href="%1$s" class="button aswc_pause_subscription">%2$s</a>',
                                       esc_url( $this->aswc_pause_url( $aswc_subscription_id, $aswc_status ) ),
                                       esc_html__( 'Pause', 'advanced-subscriptions-for-woocommerce' )
                               );
                       }
               }

               if ( aswc_enable_pause_susbcription_by_customer() ) {
                       $aswc_status = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_status', true );
                       if ( 'paused' === $aswc_status ) {
                               $buttons[] = sprintf(
                                       '<a href="%1$s" class="button aswc_reactivate_subscription">%2$s</a>',
                                       esc_url( $this->aswc_reactivate_url( $aswc_subscription_id, $aswc_status ) ),
                                       esc_html__( 'Reactivate', 'advanced-subscriptions-for-woocommerce' )
                               );
                       }
               }

               $parent_id = aswc_get_meta_data( $aswc_subscription_id, 'aswc_parent_order', true );
               if ( $parent_id && wc_get_order( $parent_id ) && in_array( wc_get_order( $parent_id )->get_payment_method(), array( 'stripe', 'stripe_sepa' ), true ) && defined( 'WC_STRIPE_VERSION' ) ) {
                       $buttons[] = sprintf(
                               '<a href="%1$s" class="button aswc_payment_method_change">%2$s</a>',
                               esc_url( site_url( 'my-account/add-payment-method/?aswc_subscription_id=' . $aswc_subscription_id ) ),
                               esc_html__( 'Change Payment Method', 'advanced-subscriptions-for-woocommerce' )
                       );
               }

               if ( ! empty( $buttons ) ) {
                       echo implode( ' ', $buttons ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
               }
       }

	/**
	 * This function is used to add paused url.
	 *
	 * @name aswc_pause_url
	 * @param int    $aswc_subscription_id aswc_subscription_id.
	 * @param status $aswc_status aswc_status.
	 * @since 1.0.0
	 */
	public function aswc_pause_url( $aswc_subscription_id, $aswc_status ) {

		$aswc_link = add_query_arg(
			array(
				'aswc_subscription_id'           => $aswc_subscription_id,
				'aswc_subscription_status_pause' => $aswc_status,
			)
		);
		$aswc_link = wp_nonce_url( $aswc_link, $aswc_subscription_id . $aswc_status );

		return $aswc_link;
	}

	/**
	 * This function is used to add reactivate url.
	 *
	 * @name aswc_reactivate_url
	 * @param int    $aswc_subscription_id aswc_subscription_id.
	 * @param status $aswc_status aswc_status.
	 * @since 1.0.0
	 */
	public function aswc_reactivate_url( $aswc_subscription_id, $aswc_status ) {

		$aswc_link = add_query_arg(
			array(
				'aswc_subscription_id'                => $aswc_subscription_id,
				'aswc_subscription_status_reactivate' => $aswc_status,
			)
		);
		$aswc_link = wp_nonce_url( $aswc_link, $aswc_subscription_id . $aswc_status );

		return $aswc_link;
	}

	/**
	 * This function is used to pause susbcription.
	 *
	 * @name aswc_pause_susbcription
	 * @since 1.0.0
	 */
	public function aswc_pause_susbcription() {

		if ( isset( $_GET['aswc_subscription_status_pause'] ) && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$user_id = get_current_user_id();

			$aswc_status          = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_status_pause'] ) );
			$aswc_subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) );
			if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				$this->aswc_pause_susbcription_by_customer( $aswc_subscription_id, $aswc_status, $user_id );
			}
		} elseif ( isset( $_GET['aswc_subscription_status_reactivate'] ) && isset( $_GET['aswc_subscription_id'] ) && isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$user_id = get_current_user_id();

			$aswc_status          = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_status_reactivate'] ) );
			$aswc_subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_subscription_id'] ) );
			if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				$this->aswc_reactivate_susbcription_by_customer( $aswc_subscription_id, $aswc_status, $user_id );
			}
		}
	}

	/**
	 * This function is used to pause susbcription.
	 *
	 * @name aswc_pause_susbcription_by_customer
	 * @param int    $aswc_subscription_id aswc_subscription_id.
	 * @param string $aswc_status aswc_status.
	 * @param int    $user_id user_id.
	 * @since 1.0.0
	 */
	public function aswc_pause_susbcription_by_customer( $aswc_subscription_id, $aswc_status, $user_id ) {

		$aswc_customer_id = aswc_get_meta_data( $aswc_subscription_id, 'aswc_customer_id', true );
		if ( 'active' == $aswc_status && $aswc_customer_id == $user_id ) {

			aswc_update_order_meta( $aswc_subscription_id, 'aswc_subscription_status', 'paused' );
			aswc_set_pause_subscription_timestamp( $aswc_subscription_id );
			aswc_send_email_for_pause_susbcription( $aswc_subscription_id );
			wc_add_notice( __( 'Subscription Paused Successfully', 'advanced-subscriptions-for-woocommerce' ), 'success' );
			$redirect_url = wc_get_endpoint_url( 'show-subscription', $aswc_subscription_id, wc_get_page_permalink( 'myaccount' ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * This function is used to reactivate susbcription.
	 *
	 * @name aswc_reactivate_susbcription_by_customer
	 * @param int    $aswc_subscription_id aswc_subscription_id.
	 * @param string $aswc_status aswc_status.
	 * @param int    $user_id user_id.
	 * @since 1.0.0
	 */
	public function aswc_reactivate_susbcription_by_customer( $aswc_subscription_id, $aswc_status, $user_id ) {

		$aswc_customer_id = aswc_get_meta_data( $aswc_subscription_id, 'aswc_customer_id', true );
		if ( 'paused' == $aswc_status && $aswc_customer_id == $user_id ) {

			aswc_reactivate_time_calculation( $aswc_subscription_id );
			aswc_update_order_meta( $aswc_subscription_id, 'aswc_subscription_status', 'active' );
			aswc_send_email_for_reactivate_susbcription( $aswc_subscription_id );
			wc_add_notice( __( 'Subscription Reactivated Successfully', 'advanced-subscriptions-for-woocommerce' ), 'success' );
			$redirect_url = wc_get_endpoint_url( 'show-subscription', $aswc_subscription_id, wc_get_page_permalink( 'myaccount' ) );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * This function is used to add upgrade/downgrade button.
	 *
	 * @name aswc_product_details_downgrade_upgrade
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 * @since 1.0.0
	 */
	public function aswc_product_details_downgrade_upgrade( $aswc_subscription_id ) {
		if ( aswc_check_upgrade_downgrade() ) {
			if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				$product_id = aswc_get_meta_data( $aswc_subscription_id, 'product_id', true );
				$product    = wc_get_product( $product_id );

				if ( aswc_check_variable_product_is_subscription( $product ) ) {
					$product_url = get_permalink( $product_id );

					$aswc_upgrade_downgrade = $this->aswc_get_upgrade_downgrade_url( $aswc_subscription_id, $product_id, $product_url );
					$aswc_text          = aswc_get_upgrade_downgrade_text();

										$aswc_upgrade_downgrade_url = sprintf( '<a href="%s" class="aswc_upgrade_downgrade btn button">%s</a>', esc_url( $aswc_upgrade_downgrade ), esc_html( $aswc_text ) );
										echo wp_kses_post( apply_filters( 'aswc_upgrade_downgrade_button', '<br/>' . $aswc_upgrade_downgrade_url, $aswc_subscription_id ) );
				}
			}
		}
	}

	/**
	 * This function is used to add upgrade/downgrade url.
	 *
	 * @name aswc_get_upgrade_downgrade_url
	 * @param int    $aswc_subscription_id aswc_subscription_id.
	 * @param int    $product_id product_id.
	 * @param string $product_url product_url.
	 * @since 1.0.0
	 */
	public function aswc_get_upgrade_downgrade_url( $aswc_subscription_id, $product_id, $product_url ) {
		$aswc_query_args =
					array(
						'aswc_downgrade_upgrade_subscription' => absint( $aswc_subscription_id ),
						'product_id'       => absint( $product_id ),
						'aswc_switch_nonce' => wp_create_nonce( 'aswc_downgrade_upgrade_nonce' ),
					);

		$aswc_upgrade_downgrade_url = add_query_arg( $aswc_query_args, $product_url );

		return $aswc_upgrade_downgrade_url;
	}

	/**
	 * This function is used to validate upgrade/downgrade product.
	 *
	 * @name aswc_upgrade_downgrade_add_to_cart_validation
	 * @param bool $valid valid.
	 * @param int  $product_id product_id.
	 * @param int  $quantity quantity.
	 * @since 1.0.0
	 */
	public function aswc_upgrade_downgrade_add_to_cart_validation( $valid, $product_id, $quantity ) {

		if ( ! isset( $_GET['aswc_downgrade_upgrade_subscription'] ) ) {
			return $valid;
		}
		if ( ! isset( $_POST['variation_id'] ) ) {
			return $valid;
		}
		if ( ! isset( $_GET['aswc_switch_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['aswc_switch_nonce'] ) ), 'aswc_downgrade_upgrade_nonce' ) ) {
			return false;
		}

		if ( isset( $_GET['product_id'] ) ) {
			$product = wc_get_product( sanitize_text_field( wp_unslash( $_POST['variation_id'] ) ) );

			if ( ! aswc_check_variable_product_is_subscription( $product ) ) {
				$aswc_error = __( 'You can switch only with subscription products', 'advanced-subscriptions-for-woocommerce' );
				wc_add_notice( $aswc_error, 'error' );
				$valid = false;
			} elseif ( $_GET['product_id'] == $_POST['variation_id'] ) {
				$aswc_error = __( 'You can not switch with same variation', 'advanced-subscriptions-for-woocommerce' );
				wc_add_notice( $aswc_error, 'error' );
				$valid = false;
			}
		}

		$product_id = isset( $_POST['variation_id'] ) ? sanitize_text_field( wp_unslash( $_POST['variation_id'] ) ) : '';

		$_product = wc_get_product( $product_id );

		$aswc_subscription_id = isset( $_GET['aswc_downgrade_upgrade_subscription'] ) ? sanitize_text_field( wp_unslash( $_GET['aswc_downgrade_upgrade_subscription'] ) ) : '';
		$aswc_recurring_total = $_product->get_price();

		$aswc_old_price = aswc_get_meta_data( $aswc_subscription_id, 'aswc_recurring_total', true );

		$aswc_old_interval   = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_interval', true );
		$aswc_new_interval   = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );
		$aswc_old_number = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_number', true );

		if ( 'day' == $aswc_old_interval ) {
			$per_day_old_price = ( $aswc_old_price / $aswc_old_number );

		} elseif ( 'week' == $aswc_old_interval ) {
			$aswc_old_div_number = $aswc_old_number * 7;

			$per_day_old_price = ( $aswc_old_price / $aswc_old_div_number );

		} elseif ( 'month' == $aswc_old_interval ) {
			$aswc_old_div_number = $aswc_old_number * 30;

			$per_day_old_price = ( $aswc_old_price / $aswc_old_div_number );

		} elseif ( 'year' == $aswc_old_interval ) {
			$aswc_old_div_number = $aswc_old_number * 365;

			$per_day_old_price = ( $aswc_old_price / $aswc_old_div_number );

		}

		$aswc_new_subs_number = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );

		if ( 'day' == $aswc_new_interval ) {
			$per_day_new_price = ( $aswc_recurring_total / $aswc_new_subs_number );

		} elseif ( 'week' == $aswc_new_interval ) {
			$aswc_new_div_number = $aswc_new_subs_number * 7;

			$per_day_new_price = ( $aswc_recurring_total / $aswc_new_div_number );

		} elseif ( 'month' == $aswc_new_interval ) {
			$aswc_new_div_number = $aswc_new_subs_number * 30;

			$per_day_new_price = ( $aswc_recurring_total / $aswc_new_div_number );

		} elseif ( 'year' == $aswc_new_interval ) {
			$aswc_new_div_number = $aswc_new_subs_number * 365;

			$per_day_new_price = ( $aswc_recurring_total / $aswc_new_div_number );

		}
		if ( get_option( 'aswc_enable_allow_same_interval', false ) ) {

			if ( $aswc_new_interval !== $aswc_old_interval ) {
				/* translators: Placeholder 1: Plan, placeholder 2: plan */
				wc_add_notice( sprintf( __( 'You can not upgrade into %1$2s from %2$1s', 'advanced-subscriptions-for-woocommerce' ), $aswc_new_interval, $aswc_old_interval ), 'error' );
				return false;
			}
		}

		if ( get_option( 'aswc_downgrade_variable_subscription' ) ) {
			if ( $per_day_old_price > $per_day_new_price ) {
				wc_add_notice( __( 'You can not downgrade the plan', 'advanced-subscriptions-for-woocommerce' ), 'error' );
				return false;
			}
		}

		return $valid;
	}

	/**
	 * This function is used to get upgrade/downgrade link.
	 *
	 * @name aswc_downgrade_upgrade_link
	 * @param string $permalink permalink.
	 * @param object $post post.
	 * @since 1.0.0
	 */
	public function aswc_downgrade_upgrade_link( $permalink, $post ) {

		if ( ! isset( $_GET['aswc_downgrade_upgrade_subscription'] ) || ! is_product() || 'product' !== $post->post_type ) {
			return $permalink;
		}
		$product = wc_get_product( $post );

		if ( $product ) {
			$product_type = $product->get_type();
			if ( 'variable' == $product_type ) {
				$product_id = isset( $_GET['product_id'] ) ? sanitize_text_field( wp_unslash( $_GET['product_id'] ) ) : '';
				$permalink  = $this->aswc_get_upgrade_downgrade_url( sanitize_text_field( wp_unslash( $_GET['aswc_downgrade_upgrade_subscription'] ) ), $product_id, $permalink );
			}
		}

		return $permalink;
	}

	/**
	 * This function is used to get upgrade/downgrade cart data.
	 *
	 * @name aswc_upgrade_downgrade_cart_details
	 * @param array $cart_item_data cart_item_data.
	 * @param int   $product_id product_id.
	 * @param int   $variation_id variation_id.
	 * @since 1.0.0
	 */
	public function aswc_upgrade_downgrade_cart_details( $cart_item_data, $product_id, $variation_id ) {

		if ( isset( $_GET['aswc_downgrade_upgrade_subscription'] ) && ! empty( $_GET['aswc_downgrade_upgrade_subscription'] ) ) {
			$user_id = get_current_user_id();

			$aswc_subscription_id = sanitize_text_field( wp_unslash( $_GET['aswc_downgrade_upgrade_subscription'] ) );
			if ( aswc_check_upgrade_downgrade() ) {
				if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
					$exiting_product_id = aswc_get_meta_data( $aswc_subscription_id, 'product_id', true );
					$aswc_existing_user  = aswc_get_meta_data( $aswc_subscription_id, 'aswc_customer_id', true );
					if ( $aswc_existing_user != $user_id ) {
						wc_add_notice( __( 'You can not switch others user subscriptions.', 'advanced-subscriptions-for-woocommerce' ), 'error' );
						WC()->cart->empty_cart( true );
						wp_redirect( get_permalink( $product_id ) );
						exit();
					}
					$aswc_next_payment_date                        = aswc_get_meta_data( $aswc_subscription_id, 'aswc_next_payment_date', true );
					$product_id                                   = isset( $_GET['product_id'] ) ? sanitize_text_field( wp_unslash( $_GET['product_id'] ) ) : '';
					$cart_item_data['aswc_upgrade_downgrade_data'] = array(
						'aswc_subscription_id'   => $aswc_subscription_id,
						'product_id'            => absint( $product_id ),
						'aswc_next_payment_date' => $aswc_next_payment_date,
					);

				}
			}
		}
		return $cart_item_data;
	}

	/**
	 * This function is used to get upgrade/downgrade cart data.
	 *
	 * @name aswc_is_upgrade_downgrade_text
	 * @param string $product_subtotal product_subtotal.
	 * @param array  $cart_item cart_item.
	 * @param int    $cart_item_key cart_item_key.
	 * @since 1.0.0
	 */
	public function aswc_is_upgrade_downgrade_text( $product_subtotal, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['aswc_upgrade_downgrade_data'] ) ) {
			$aswc_subscription_id = $cart_item['aswc_upgrade_downgrade_data']['aswc_subscription_id'];
			if ( ! aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				return $product_subtotal;
			}
			$aswc_switch_type = $this->aswc_get_upgrade_downgrade_type( $aswc_subscription_id, $cart_item );
			if ( ! empty( $aswc_switch_type ) ) {

				if ( 'upgrade' == $aswc_switch_type ) {
					$aswc_switch_type = __( 'Upgrade', 'advanced-subscriptions-for-woocommerce' );
				} else {
					$aswc_switch_type = __( 'Downgrade', 'advanced-subscriptions-for-woocommerce' );
				}
				// translators: type.
				$product_subtotal = sprintf( '%1s %2$s(%3$s)%4$s', $product_subtotal, '<span class="subscription-switch-direction">', $aswc_switch_type, '</span>' );
			}
		}

		return $product_subtotal;
	}

	/**
	 * This function is used to get upgrade/downgrade type.
	 *
	 * @name aswc_get_upgrade_downgrade_type
	 * @param int   $aswc_subscription_id aswc_subscription_id.
	 * @param array $cart_item cart_item.
	 * @since 1.0.0
	 */
	public function aswc_get_upgrade_downgrade_type( $aswc_subscription_id, $cart_item ) {
		$aswc_switch_type = '';

		if ( isset( $cart_item ) && ! empty( $cart_item ) && is_array( $cart_item ) ) {
			if ( $cart_item['data']->is_on_sale() ) {
				$price = $cart_item['data']->get_sale_price();
			} else {
				$price = $cart_item['data']->get_regular_price();
			}
			$aswc_recurring_total = $price * $cart_item['quantity'];
			$aswc_old_price       = aswc_get_meta_data( $aswc_subscription_id, 'aswc_recurring_total', true );

			$product_id         = $cart_item['data']->get_id();
			$aswc_old_interval   = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_interval', true );
			$aswc_new_interval   = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );
			$aswc_old_number = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_number', true );

			if ( 'day' == $aswc_old_interval ) {
				$per_day_old_price = ( $aswc_old_price / $aswc_old_number );

			} elseif ( 'week' == $aswc_old_interval ) {
				$aswc_old_div_number = $aswc_old_number * 7;

				$per_day_old_price = ( $aswc_old_price / $aswc_old_div_number );

			} elseif ( 'month' == $aswc_old_interval ) {
				$aswc_old_div_number = $aswc_old_number * 30;

				$per_day_old_price = ( $aswc_old_price / $aswc_old_div_number );

			} elseif ( 'year' == $aswc_old_interval ) {
				$aswc_old_div_number = $aswc_old_number * 365;

				$per_day_old_price = ( $aswc_old_price / $aswc_old_div_number );

			}

			$aswc_new_subs_number = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );

			if ( 'day' == $aswc_new_interval ) {
				$per_day_new_price = ( $aswc_recurring_total / $aswc_new_subs_number );

			} elseif ( 'week' == $aswc_new_interval ) {
				$aswc_new_div_number = $aswc_new_subs_number * 7;

				$per_day_new_price = ( $aswc_recurring_total / $aswc_new_div_number );

			} elseif ( 'month' == $aswc_new_interval ) {
				$aswc_new_div_number = $aswc_new_subs_number * 30;

				$per_day_new_price = ( $aswc_recurring_total / $aswc_new_div_number );

			} elseif ( 'year' == $aswc_new_interval ) {
				$aswc_new_div_number = $aswc_new_subs_number * 365;

				$per_day_new_price = ( $aswc_recurring_total / $aswc_new_div_number );

			}
			if ( $per_day_new_price >= $per_day_old_price ) {
				$aswc_switch_type = 'upgrade';
			} elseif ( $per_day_new_price < $per_day_old_price ) {
				$aswc_switch_type = 'downgrade';
			}
		}
		return $aswc_switch_type;
	}

	/**
	 * This function is used to get upgrade/downgrade order.
	 *
	 * @name aswc_is_upgrade_downgrade_order
	 * @param bool   $valid valid.
	 * @param array  $aswc_recurring_data aswc_recurring_data.
	 * @param object $order order.
	 * @param array  $posted_data posted_data.
	 * @param array  $cart_item cart_item.
	 * @throws WC_Data_Exception Exception may be thrown if value is invalid.
	 * @since 1.0.0
	 */
	public function aswc_is_upgrade_downgrade_order( $valid, $aswc_recurring_data, $order, $posted_data, $cart_item ) {
		if ( isset( $cart_item['aswc_upgrade_downgrade_data'] ) && ! empty( $cart_item['aswc_upgrade_downgrade_data'] ) ) {
			$aswc_subscription_id = isset( $cart_item['aswc_upgrade_downgrade_data']['aswc_subscription_id'] ) ? $cart_item['aswc_upgrade_downgrade_data']['aswc_subscription_id'] : '';
			if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {

				$order_id        = $order->get_id();
				$aswc_switch_type = $this->aswc_get_upgrade_downgrade_type( $aswc_subscription_id, $cart_item );

				$aswc_data = array(
					'aswc_switch_recurring_data'     => $aswc_recurring_data,
					'aswc_switch_recurring_order_id' => $order_id,
					'aswc_switch_cart_data'          => $cart_item['aswc_upgrade_downgrade_data'],
					'aswc_switch_type'               => $aswc_switch_type,

				);
				aswc_update_order_meta( $aswc_subscription_id, 'aswc_upgrade_downgrade_data', $aswc_data );

				aswc_update_order_meta( $order_id, 'aswc_upgrade_downgrade_order', 'yes' );

			aswc_update_order_meta( $order_id, 'aswc_subscription', $aswc_subscription_id );

				$valid = true;

			}
		}
		return $valid;
	}

	/**
	 * This function is used to set upgrade/downgrade price.
	 *
	 * @name aswc_add_switch_subscription_price_and_sigup_fee
	 * @param object $cart cart.
	 * @since 1.0.0
	 */
	public function aswc_add_switch_subscription_price_and_sigup_fee( $cart ) {
		$set = false;

		if ( WC()->session->downgrade_upgrade_notice ) {
			WC()->session->__unset( 'downgrade_upgrade_notice' );
		}
		if ( isset( $cart ) && ! empty( $cart ) ) {

			foreach ( $cart->cart_contents as $key => $cart_data ) {
				// Remove susbcription products from cart if settings disable.
				$this->aswc_remove_susbcription_product_from_cart( $cart_data );

				$product_id = $cart_data['data']->get_id();

				if ( $this->aswc_check_cart_has_switch_subscription( $cart_data ) ) {

					$aswc_subscription_id = $cart_data['aswc_upgrade_downgrade_data']['aswc_subscription_id'];

					if ( ! aswc_check_valid_subscription( $aswc_subscription_id ) ) {
						return;
					}
					$aswc_switch_type = $this->aswc_get_upgrade_downgrade_type( $aswc_subscription_id, $cart_data );

					$product_id    = $cart_data['data']->get_id();
					$_product      = wc_get_product( $product_id );
					$product_price = $_product->get_price();
					$signup_fee    = aswc_get_meta_data( $product_id, 'aswc_subscription_initial_signup_price', true );
					if ( $signup_fee ) {
						$product_price = $product_price - $signup_fee;
					}

					if ( aswc_check_enable_prorate_price_upgrade_downgrade() && 'downgrade' == $aswc_switch_type ) {
                                                $product_price             = aswc_proprate_price_calculate( $aswc_subscription_id, $product_id, $product_price, $cart_data, $set );
                                                $aswc_free_trial_number = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_number', true );
                                                $aswc_signup_fee        = 0;
                                                if ( aswc_check_enable_singup_upgrade_downgrade() ) {

                                                        $aswc_signup_fee = aswc_get_meta_data( $product_id, 'aswc_subscription_initial_signup_price', true );
                                                        $aswc_signup_fee = is_numeric( $aswc_signup_fee ) ? (float) $aswc_signup_fee : 0;
                                                }

						if ( isset( $aswc_free_trial_number ) && ! empty( $aswc_free_trial_number ) ) {
							if ( 0 != $aswc_signup_fee ) {
								$cart_data['data']->set_price( $aswc_signup_fee );
							} else {
								$cart_data['data']->set_price( 0 );
							}
						} else {
							$cart_data['data']->set_price( $aswc_signup_fee + $product_price );
						}
					} elseif ( aswc_check_enable_prorate_price_upgrade_downgrade() && 'upgrade' == $aswc_switch_type ) {
                                                $product_price             = aswc_proprate_price_calculate( $aswc_subscription_id, $product_id, $product_price, $cart_data, $set );
                                                $aswc_free_trial_number = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_number', true );

                                                $aswc_signup_fee = aswc_get_meta_data( $product_id, 'aswc_subscription_initial_signup_price', true );
                                                $aswc_signup_fee = is_numeric( $aswc_signup_fee ) ? (float) $aswc_signup_fee : 0;

						if ( isset( $aswc_free_trial_number ) && ! empty( $aswc_free_trial_number ) ) {
							if ( 0 != $aswc_signup_fee ) {
								$cart_data['data']->set_price( $aswc_signup_fee );
							} else {
								$cart_data['data']->set_price( 0 );
							}
						} else {

							$cart_data['data']->set_price( $aswc_signup_fee + $product_price );

						}
					}
				}
			}
		}
	}

        /**
         * This function is used to check cart have switch subscription.
         *
         * @name aswc_check_cart_has_switch_subscription
         * @param array $cart_item cart_item.
	 * @since 1.0.0
	 */
	public function aswc_check_cart_has_switch_subscription( $cart_item ) {
		$valid = false;
		if ( isset( $cart_item['aswc_upgrade_downgrade_data'] ) && ! empty( $cart_item['aswc_upgrade_downgrade_data'] ) ) {
			$valid = true;
		}

		return $valid;
	}

	/**
	 * This function is used to check billing interval.
	 *
	 * @name aswc_check_billing_interval_for_downgrade_upgrade
	 * @param int   $aswc_subscription_id aswc_subscription_id.
	 * @param array $aswc_recurring_data aswc_recurring_data.
	 * @since 1.0.0
	 */
	public function aswc_check_billing_interval_for_downgrade_upgrade( $aswc_subscription_id, $aswc_recurring_data ) {
		$aswc_is_diff_interval          = false;
		$aswc_subscription_number   = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_number', true );
		$aswc_subscription_interval = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_interval', true );

		$aswc_cart_subscription_number   = $aswc_recurring_data['aswc_subscription_number'];
		$aswc_cart_subscription_interval = $aswc_recurring_data['aswc_subscription_interval'];

		if ( $aswc_subscription_number != $aswc_cart_subscription_number ) {
			$aswc_is_diff_interval = true;
		} elseif ( $aswc_subscription_interval != $aswc_cart_subscription_interval ) {
			$aswc_is_diff_interval = true;
		}
		return $aswc_is_diff_interval;
	}

	/**
	 * This function is used to check billing expiry.
	 *
	 * @name aswc_check_expiry_for_downgrade_upgrade
	 * @param int   $aswc_subscription_id aswc_subscription_id.
	 * @param array $aswc_recurring_data aswc_recurring_data.
	 * @since 1.0.0
	 */
	public function aswc_check_expiry_for_downgrade_upgrade( $aswc_subscription_id, $aswc_recurring_data ) {
		$aswc_is_diff_expiry                   = false;
		$aswc_subscription_expiry_number   = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_expiry_number', true );
		$aswc_subscription_expiry_interval = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_expiry_interval', true );

		$aswc_cart_subscription_expiry_number   = $aswc_recurring_data['aswc_subscription_expiry_number'];
		$aswc_cart_subscription_expiry_interval = $aswc_recurring_data['aswc_subscription_expiry_interval'];

		if ( $aswc_subscription_expiry_number != $aswc_cart_subscription_expiry_number ) {
			$aswc_is_diff_expiry = true;
		} elseif ( $aswc_subscription_expiry_interval != $aswc_cart_subscription_expiry_interval ) {
			$aswc_is_diff_expiry = true;
		}
		return $aswc_is_diff_expiry;
	}

	/**
	 * This function is used to show sync interval.
	 *
	 * @name aswc_show_sync_interval_price
	 * @param string $aswc_price_html aswc_price_html.
	 * @param int    $product_id product_id.
	 * @since 1.0.0
	 */
	public function aswc_show_sync_interval_price( $aswc_price_html, $product_id ) {

		if ( aswc_start_susbcription_from_certain_date_of_month() && aswc_subscription_syn_enable_per_product( $product_id ) ) {

			$aswc_price_html = aswc_get_sync_subscription_details( $product_id, $aswc_price_html );
		}
		return $aswc_price_html;
	}

	/**
	 * This function is used to show first payment on single product page.
	 *
	 * @name aswc_show_first_payment_date_for_sync_subscription
	 * @since 1.0.0
	 */
	public function aswc_show_first_payment_date_for_sync_subscription() {
		global $product;
		if ( aswc_check_product_is_subscription( $product ) ) {
			$product_id = $product->get_id();
			if ( aswc_start_susbcription_from_certain_date_of_month() ) {
				$aswc_first_payment = aswc_get_sync_first_payment_date( $product_id );
				?>
					<p class="aswc_sync_fist_payment_date">
						<?php
							echo wp_kses_post( $aswc_first_payment );
						?>
					</p>
					<?php
			}
			if ( aswc_allow_start_date_subscription() ) {

				$aswc_start_date = aswc_get_meta_data( $product_id, 'aswc_subscription_start_date', true );
				if ( $aswc_start_date ) {
					?>
					<div class="aswc_start_date">
					<span><?php esc_html_e( 'Your subscription will start on: ', 'advanced-subscriptions-for-woocommerce' ); ?>
					<?php echo esc_html( aswc_get_the_wordpress_date_format( strtotime( $aswc_start_date ) ) ); ?>
					</div>
					<?php
				}
			}
		}
	}

	/**
	 * This function is used to show cart price.
	 *
	 * @name aswc_cart_price_for_sync_subscription
	 * @param int   $price price.
	 * @param array $cart_data cart_data.
	 * @since 1.0.0
	 */
	public function aswc_cart_price_for_sync_subscription( $price, $cart_data ) {

		$product_id = $cart_data['data']->get_id();

		if ( aswc_start_susbcription_from_certain_date_of_month() && aswc_subscription_syn_enable_per_product( $product_id ) ) {

			$aswc_prorate_type = aswc_get_prorate_price_on_sync_enable();

			if ( 'aswc_prorate_no' == $aswc_prorate_type ) {
				return $price;
			} elseif ( 'aswc_prorate_simple' == $aswc_prorate_type || 'aswc_prorate_if_free_trial' == $aswc_prorate_type ) {
				$price = aswc_prorate_price_for_sync( $price, $product_id, $cart_data );
			}
		}
		return $price;
	}

	/**
	 * This function is used to add first payment date for variable product.
	 *
	 * @name aswc_variation_descriptions
	 * @param array  $variation_data variation_data.
	 * @param object $product product.
	 * @param object $variation variation.
	 * @since 1.0.0
	 */
	public function aswc_variation_descriptions( $variation_data, $product, $variation ) {
		if ( aswc_check_product_is_subscription( $variation ) ) {
			$product_id = $variation->get_id();
			if ( aswc_allow_start_date_subscription() ) {
				$variation_data['aswc_start_payment_html'] = aswc_get_sync_start_payment_date( $product_id );
			}
			if ( aswc_start_susbcription_from_certain_date_of_month() ) {
				if ( isset( $variation_data ) && ! empty( $variation_data ) && is_array( $variation_data ) ) {

					$variation_data['aswc_first_payment_html'] = aswc_get_sync_first_payment_date( $product_id );
				}
			}
		}
		return $variation_data;
	}

	/**
	 * This function is used to add to cart validation.
	 *
	 * @name aswc_add_to_cart_validation
	 * @param bool $validate validate.
	 * @param int  $product_id product_id.
	 * @param int  $quantity quantity.
	 * @since 1.0.0
	 */
	public function aswc_add_to_cart_validation( $validate, $product_id, $quantity ) {

		if ( aswc_check_enable_add_multiple_subscription_cart() ) {
			$validate = true;
		}
		return $validate;
	}


	/**
	 * This function is used to remove susbcription from cart.
	 *
	 * @name aswc_remove_susbcription_product_from_cart
	 * @param array $cart_data cart_data.
	 * @since 1.0.0
	 */
	public function aswc_remove_susbcription_product_from_cart( $cart_data ) {
		if ( ! aswc_check_enable_add_multiple_subscription_cart() ) {
			if ( aswc_check_product_is_subscription( $cart_data['data'] ) ) {
				if ( aswc_no_of_susbcription_in_cart() > 1 && isset( $cart_data['key'] ) ) {
					WC()->cart->remove_cart_item( $cart_data['key'] );
				}
			}
		}
	}

	/**
	 * This function is used to giftcard coupon apply html.
	 *
	 * @name aswc_add_gift_card_coupon_apply_html
	 * @param array $aswc_subscription_id aswc_subscription_id.
	 * @since 1.0.0
	 */
	public function aswc_add_gift_card_coupon_apply_html( $aswc_subscription_id ) {
		if ( aswc_get_subscription_coupon_enable_for_gc() ) {
			?>
			<table class="shop_table aswc_apply_gc_coupon">
				<h3><?php esc_html_e( 'Apply Gift card Coupon', 'advanced-subscriptions-for-woocommerce' ); ?></h3>
				<tr>
					<td>
						<p class="aswc_coupon_wrap aswc_coupon_error_<?php echo esc_attr( $aswc_subscription_id ); ?>" style="display: none"></p>
						<input type="text" placeholder="<?php esc_attr_e( 'Enter coupon code', 'advanced-subscriptions-for-woocommerce' ); ?>" id="aswc_gift_coupon_<?php echo esc_attr( $aswc_subscription_id ); ?>" class="aswc_gift_coupon" name="aswc_gift_coupon"/>
						<a href="#" class="button aswc_apply_giftcard_coupon" data-id="<?php echo esc_attr( $aswc_subscription_id ); ?>"><?php esc_html_e( 'Apply', 'advanced-subscriptions-for-woocommerce' ); ?></a>
                                           <p id="aswc-my-account-ajax-loading-gif" class="aswc-my-account-ajax-loading-gif" style="display: none;">
<img src="<?php echo esc_url( ASWC_INCLUDES_DIR_URL . 'admin/image/loading.gif' ); ?>">
						</p>
					</td>
				</tr>
			</table>
			<?php
		}
	}

	/**
	 * This function is used to set multiple quantity for susbcription product.
	 *
	 * @name aswc_show_quantity_fields_for_susbcriptions
	 * @param bool   $return return.
	 * @param object $product product.
	 * @since 1.0.0
	 */
	public function aswc_show_quantity_fields_for_susbcriptions( $return, $product ) {
		if ( $return ) {
			if ( aswc_enable_multiple_quantity_field() && aswc_check_product_is_subscription( $product ) ) {
				$return = false;
			}
		}
		return $return;
	}

	/**
	 * This function is used to add start date into recurring data.
	 *
	 * @param array $aswc_recurring_data aswc_recurring_data.
	 * @param int   $product_id product_id.
	 * @return array
	 */
	public function aswc_add_start_date_recurring( $aswc_recurring_data, $product_id ) {

		if ( aswc_allow_start_date_subscription() ) {
			$aswc_subscription_start_date = aswc_get_meta_data( $product_id, 'aswc_subscription_start_date', true );
			if ( isset( $aswc_subscription_start_date ) && ! empty( $aswc_subscription_start_date ) ) {
				$aswc_recurring_data['aswc_subscription_start_date'] = $aswc_subscription_start_date;
			}
		}

		return $aswc_recurring_data;
	}
	/**
	 * This function is used to set subscription status.
	 *
	 * @param string $status subscription status.
	 * @param int    $aswc_subscription_id subscription id.
	 * @return string
	 */
	public function aswc_set_subscription_status( $status, $aswc_subscription_id ) {
		if ( aswc_allow_start_date_subscription() ) {
				$aswc_subscription_start_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_start_date', true );
			if ( $aswc_subscription_start_date ) {
				if ( aswc_get_wp_date( 'Y-m-d' ) >= $aswc_subscription_start_date ) {
					$status = 'active';
				} else {
								$status = 'pending';
				}
				if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( 'Set subscription status for ' . $aswc_subscription_id . ' start date ' . $aswc_subscription_start_date . ' => ' . $status );
				}
			}
		}
			return $status;
	}

	/**
	 * This function is used to set subscription start time.
	 *
	 * @param int $timestamp timestamp.
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 * @return int
	 */
	public function aswc_set_current_time_with_start_time( $timestamp, $aswc_subscription_id ) {

		if ( aswc_allow_start_date_subscription() ) {
				$aswc_subscription_start_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_start_date', true );
			if ( $aswc_subscription_start_date ) {
				if ( aswc_get_wp_date( 'Y-m-d' ) >= $aswc_subscription_start_date ) {
						$timestamp = ASWC_Scheduler_API::date_to_time( 'now' );
				} else {
						$timestamp = ASWC_Scheduler_API::date_to_time( $aswc_subscription_start_date );
				}
				if ( class_exists( 'ASWC_Log' ) ) {
						ASWC_Log::log( 'Set start time for ' . $aswc_subscription_id . ' start date ' . $aswc_subscription_start_date . ' => ' . $timestamp );
				}
			}
		}
			return $timestamp;
	}

	/**
	 * Function name aswc_show_downgrade_upgrade_msg.
	 * this function is used to show downgrade upgrade msg.
	 *
	 * @return void
	 */
	public function aswc_show_downgrade_upgrade_msg() {
		if ( ( is_cart() || is_checkout() ) ) {
			if ( WC()->session->downgrade_upgrade_notice ) {
				$downgrade_upgrade_notice = WC()->session->downgrade_upgrade_notice;
				wc_add_notice( $downgrade_upgrade_notice, 'notice' );
			}
		}
	}
	/**
	 * This function is used to add checkbox to checkout page for recurring policy.
	/**
	 * Function name aswc_add_cancel_recurring_button.
	 * this function is used to add cancel recurring button to the frontend.
	 *
	 * @param int $aswc_subs_id aswc_subs_id.
	 * @return void
	 */
	public function aswc_add_cancel_recurring_button( $aswc_subs_id ) {

		if ( aswc_loader_is_hpos_enabled() ) {
			$subscription = new ASWC_Subscription( $aswc_subs_id );
		} else {
			$subscription = wc_get_order( $aswc_subs_id );
		}

		$aswc_payment_method = $subscription->get_payment_method();

		$get_parent_order_id = aswc_get_meta_data( $aswc_subs_id, 'aswc_parent_order', true );
		if ( 'multisafepay' == substr( $aswc_payment_method, 0, 12 ) ) {
			$aswc_allow_recurring_multisafepay = aswc_get_meta_data( $get_parent_order_id, 'aswc_allow_recurring_payment_multisafepay_checkout', true );
			if ( $aswc_allow_recurring_multisafepay && ( ! aswc_get_meta_data( $aswc_subs_id, 'aswc_user_cancelled_recurring', true ) ) ) {
				echo '<button id="aswc_cancel_recurring_multisafepay" data-id="' . esc_html( $aswc_subs_id ) . '">' . esc_html__( 'Cancel Recurring payment', 'advanced-subscriptions-for-woocommerce' ) . ' </button>';
			}
		}
	}

	/**
	 * This function is used to remove cart notice when upgrade downgrade cart is empty.
	 *
	 * @return void
	 */
	public function aswc_remove_cart_notice() {
		if ( WC()->cart->get_cart_contents_count() === 0 ) {
			if ( WC()->session->downgrade_upgrade_notice ) {
				WC()->session->__unset( 'downgrade_upgrade_notice' );
			}
		}
	}

	/**
	 * Skip subscription creating feature for one time subscription product function
	 *
	 * @param bool  $aswc_is_subscription .
	 * @param array $cart_item .
	 * @return $aswc_is_subscription
	 */
	public function aswc_skip_creating_subscription( $aswc_is_subscription, $cart_item ) {
		if ( is_array( $cart_item ) ) {
			$product_id = $cart_item['data']->get_id();
			if ( isset( WC()->session ) && 'on' === WC()->session->get( 'aswc_onetime_subscription_' . $product_id ) ) {
				$aswc_is_subscription = false;
			}
		}
		return $aswc_is_subscription;
	}

	/**
	 * Display the onetime price selection on the shop and product pages
	 *
	 * @param string $price .
	 * @param int    $product_id .
	 * @return $price
	 */
	public function aswc_price_html_onetime_subscription_product( $price, $product_id ) {
		if ( $product_id ) {

			$product                   = wc_get_product( $product_id );
			$aswc_onetime_price     = aswc_get_meta_data( $product_id, 'aswc_onetime_price', true );
			$aswc_one_time_purchase = aswc_get_meta_data( $product_id, 'aswc_one_time_purchase', true );

			if ( ! empty( $product->get_price() ) && 'on' === $aswc_one_time_purchase && ! empty( $aswc_onetime_price ) && $aswc_onetime_price > $product->get_price() && 'variable' != $product->get_type() ) {
				if ( is_cart() || is_checkout() ) {
					if ( isset( WC()->session ) && 'on' === WC()->session->get( 'aswc_onetime_subscription_' . $product_id ) ) {
						return wc_price( $aswc_onetime_price );
					} else {
						return $price;
					}
				}
				$price_discount_percentage    = abs( number_format( ( ( $aswc_onetime_price - $product->get_price() ) / $aswc_onetime_price ) * 100, 2 ) );
				$aswc_onetime_subscription = ( isset( WC()->session ) && WC()->session->has_session() ) ? WC()->session->get( 'aswc_onetime_subscription_' . $product_id ) : '';
				$one_time_check               = ( 'on' == $aswc_onetime_subscription ) ? 'checked' : '';
				$subscription_check           = ( 'checked' !== $one_time_check ) ? 'checked' : '';
				$aswc_price_html               = $price;
				$aswc_price_html              .= '<div class="aswc_subscription_wrapper">';
				// translators: placeholder is price_discount_percentage.
				$aswc_price_html .= '<label class="aswc_subscription_label" for="aswc_check_simple_cart_subscription_purchase"><input name="aswc_type_selection" type="radio" class="aswc_check_simple_cart_subscription_purchase" id ="aswc_check_simple_cart_subscription_purchase" data-pro_type="subscription" data-id="' . $product_id . '"' . $subscription_check . '>' . esc_html__( 'Enable for subscription', 'advanced-subscriptions-for-woocommerce' ) . '</label><p class="aswc_inner_description">' . sprintf( esc_html__( '%s Off Every Order, Guaranteed Delivery, Make Changes Any Time, Prompt VIP Support', 'advanced-subscriptions-for-woocommerce' ), $price_discount_percentage . '%' ) . '</p>';
				$aswc_price_html .= '<div class ="aswc_subscription_inner_wrapper">';
				$aswc_price_html .= '<div class ="aswc_onetimesimple_wrapper">';
				$aswc_price_html .= '<label for="aswc_check_simple_cart_one_time_purchase"><input name="aswc_type_selection" type="radio" class="aswc_check_simple_cart_one_time_purchase" data-pro_type="one_time" id="aswc_check_simple_cart_one_time_purchase" data-id="' . $product_id . '"' . $one_time_check . '>';
				$aswc_price_html .= esc_html__( 'Enable for one Time Subscription is', 'advanced-subscriptions-for-woocommerce' ) . ' ';
				$aswc_price_html .= wc_price( $aswc_onetime_price );
				$aswc_price_html .= '</label></div></div></div>';
				$price           = $aswc_price_html;
			}
		}
		return $price;
	}

	/**
	 * Set the onetime purcahse amount on the cart product price if product is onetime subscription
	 *
	 * @param object $cart cart.
	 * @since 1.0.0
	 */
	public function aswc_add_to_cart_one_time_add_price( $cart ) {
		if ( isset( $cart ) && ! empty( $cart ) ) {

			foreach ( $cart->cart_contents as $key => $cart_data ) {
				$product_id = $cart_data['data']->get_id();
				if ( isset( WC()->session ) && 'on' === WC()->session->get( 'aswc_onetime_subscription_' . $product_id ) ) {
					$aswc_onetime_price = aswc_get_meta_data( $product_id, 'aswc_onetime_price', true );
					$cart_data['data']->set_price( $aswc_onetime_price );
					$cart_data['data']->set_regular_price( $aswc_onetime_price );
				}
			}
		}
	}
	/**
	 * Remove the one-time subscription product tag
	 *
	 * @param array  $thank_you_title as thank you title.
	 * @param object $order as order.
	 * @return void
	 */
	public function aswc_remove_onetime_session( $thank_you_title, $order ) {

		if ( is_object( $order ) ) {
			$items = $order->get_items();
			foreach ( $items as $item ) {
				$product_id = $item->get_product_id();
				if ( isset( WC()->session ) && WC()->session->has_session( 'aswc_onetime_subscription_' . $product_id ) ) {
					WC()->session->__unset( 'aswc_onetime_subscription_' . $product_id );
				}
			}
		}
	}

	/**
	 * Show the price html for same variation price
	 *
	 * @param bool   $bool as bool.
	 * @param object $variable_product as variable product.
	 * @param object $variation as variation.
	 * @return bool
	 */
	public function aswc_woocommerce_show_variation_price( $bool, $variable_product, $variation ) {
		if ( ! $bool && function_exists( 'aswc_check_product_is_subscription' ) && aswc_check_product_is_subscription( $variation ) ) {
			return true;
		}
		return $bool;
	}


	/**
	 * Aswc_show_related_subscription_on_order function.
	 *
	 * @param object $order as variable order.
	 */
	public function aswc_show_related_subscription_on_order( $order ) {

		$order_id = $order->get_id();

                $subscription_statuses = array_keys( wc_get_order_statuses() );
                $parent_order_meta_key = function_exists( 'aswc_normalize_meta_key' ) ? aswc_normalize_meta_key( 'aswc_parent_order' ) : '_aswc_parent_order';

		if ( function_exists( 'wc_trim_order_status_prefix' ) ) {
			$normalized_statuses = array_map( 'wc_trim_order_status_prefix', $subscription_statuses );
		} else {
			$normalized_statuses = array();

			foreach ( $subscription_statuses as $subscription_status ) {
				if ( 0 === strpos( $subscription_status, 'wc-' ) ) {
					$normalized_statuses[] = substr( $subscription_status, 3 );
				} else {
					$normalized_statuses[] = $subscription_status;
				}
			}
		}

		if ( aswc_loader_is_hpos_enabled() ) {
			$args              = array(
				'return'     => 'ids',
				'type'       => 'aswc_subscriptions',
				'status'     => $normalized_statuses,
				'limit'      => -1,
                                'meta_query' => array(
                                        array(
                                                'key'   => $parent_order_meta_key,
                                                'value' => $order_id,
                                        ),
                                ),
			);
			$aswc_subscriptions = wc_get_orders( $args );
		} else {
			$args              = array(
				'numberposts' => -1,
				'post_type'   => 'aswc_subscriptions',
				'post_status' => $subscription_statuses,
                                'meta_query'  => array(
                                        array(
                                                'key'   => $parent_order_meta_key,
                                                'value' => $order_id,
                                        ),
                                ),
			);
			$aswc_subscriptions = get_posts( $args );
		}

		$switch_plan = aswc_get_meta_data( $order_id, 'aswc_upgrade_downgrade_order', true );

		if ( ! empty( $aswc_subscriptions ) && 'yes' !== $switch_plan ) {
			?>

			<header>
				<h2><?php esc_html_e( 'Related subscriptions', 'advanced-subscriptions-for-woocommerce' ); ?></h2>
			</header>

			<table class="shop_table shop_table_responsive my_account_orders woocommerce-orders-table woocommerce-MyAccount-subscriptions woocommerce-orders-table--subscriptions">
					<thead>
						<tr>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr"><?php esc_html_e( 'ID', 'advanced-subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr"><?php esc_html_e( 'Status', 'advanced-subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr"><?php echo esc_html_e( 'Next payment date', 'advanced-subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr"><?php echo esc_html_e( 'Recurring Total', 'advanced-subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><?php esc_html_e( 'Action', 'advanced-subscriptions-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php

						foreach ( $aswc_subscriptions as $key => $aswc_subscription ) {
							if ( aswc_loader_is_hpos_enabled() ) {
								$subcription_id = $aswc_subscription;
								$susbcription   = new ASWC_Subscription( $subcription_id );
							} else {
								$subcription_id = $aswc_subscription->ID;
								$susbcription   = wc_get_order( $subcription_id );
							}
							$parent_order_id          = aswc_get_meta_data( $subcription_id, 'aswc_parent_order', true );
							$aswc_subscription_status  = aswc_get_meta_data( $subcription_id, 'aswc_subscription_status', true );
							$aswc_next_payment_date    = aswc_get_meta_data( $subcription_id, 'aswc_next_payment_date', true );
							$aswc_show_recurring_total = aswc_get_meta_data( $subcription_id, 'aswc_show_recurring_total', true );

							$aswc_wsfw_is_order = false;
							if ( function_exists( 'aswc_check_valid_order' ) && ! aswc_check_valid_order( $parent_order_id ) ) {
								$aswc_wsfw_is_order = apply_filters( 'aswc_wsfw_check_parent_order', $aswc_wsfw_is_order, $parent_order_id );
								if ( false == $aswc_wsfw_is_order ) {
									continue;
								}
							}

							?>
							<tr class="aswc_account_row woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
									<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number">
								<?php echo esc_html( $subcription_id ); ?>
									</td>
									<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status">
								<?php
									$aswc_subscription_status = aswc_get_meta_data( $subcription_id, 'aswc_subscription_status', true );
									echo esc_html( $aswc_subscription_status );
								?>
									</td>
									<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date">
								<?php
								$aswc_next_payment_date = aswc_get_meta_data( $subcription_id, 'aswc_next_payment_date', true );
								if ( 'cancelled' === $aswc_subscription_status ) {
									$aswc_next_payment_date = '';
								}
									echo esc_html( aswc_get_the_wordpress_date_format( $aswc_next_payment_date ) );
								?>
									</td>
									<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total">
									<?php
									echo wp_kses_post( wc_price( $susbcription->get_total() ) );
									?>
									</td>
									<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions">
										<span class="aswc_account_show_subscription">
											<a href="
									<?php
									echo esc_url( wc_get_endpoint_url( 'show-subscription', $subcription_id, wc_get_page_permalink( 'myaccount' ) ) );
									?>
											">
									<?php
									esc_html_e( 'Show', 'advanced-subscriptions-for-woocommerce' );
									?>
											</a>
										</span>
									</td>
								</tr>
								<?php } ?>
								<tbody>
							</table>
								<?php

		}
	}


       /**
        * Aswc_show_renewal_order_for_customer function
        *
        * @param int $aswc_subscription_id Subscription id.
        * @return void
        */
       public function aswc_show_renewal_order_for_customer( $aswc_subscription_id ) {
               $renewal_orders  = aswc_get_meta_data( $aswc_subscription_id, 'aswc_renewal_order_data', true );
               $parent_order_id = (int) aswc_get_meta_data( $aswc_subscription_id, 'aswc_parent_order', true );
               $orders          = array();
               $orders_per_page = (int) apply_filters( 'woocommerce_my_account_my_orders_per_page', 10 );
               $page_query_arg  = 'renewal-orders-page';

               $orders_per_page = 0 < $orders_per_page ? $orders_per_page : 10;

               if ( 0 < $parent_order_id ) {
                       $parent_order = wc_get_order( $parent_order_id );
                       if ( $parent_order instanceof \WC_Order ) {
                               $orders[ $parent_order_id ] = $parent_order;
                       }
               }

               if ( is_array( $renewal_orders ) && ! empty( $renewal_orders ) ) {
                       foreach ( $renewal_orders as $renewal_order_id ) {
                               $renewal_order_id = absint( $renewal_order_id );

                               if ( 0 === $renewal_order_id || isset( $orders[ $renewal_order_id ] ) ) {
                                       continue;
                               }

                               $renewal_order = wc_get_order( $renewal_order_id );

                               if ( $renewal_order instanceof \WC_Order ) {
                                       $orders[ $renewal_order_id ] = $renewal_order;
                               }
                       }
               }

               if ( empty( $orders ) ) {
                       return;
               }

               uasort(
                       $orders,
                       static function ( $first_order, $second_order ) {
                               $first_timestamp  = $first_order->get_date_created() ? $first_order->get_date_created()->getTimestamp() : 0;
                               $second_timestamp = $second_order->get_date_created() ? $second_order->get_date_created()->getTimestamp() : 0;

                               if ( $first_timestamp === $second_timestamp ) {
                                       return 0;
                               }

                               return ( $first_timestamp > $second_timestamp ) ? -1 : 1;
                       }
               );

               $total_orders   = count( $orders );
               $max_num_pages  = (int) ceil( $total_orders / $orders_per_page );
               $current_page = 1;

               if ( isset( $_GET[ $page_query_arg ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                       $current_page = absint( wp_unslash( $_GET[ $page_query_arg ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
               }

               if ( 0 === $current_page ) {
                       $current_page = 1;
               }

               if ( 0 < $max_num_pages && $current_page > $max_num_pages ) {
                       $current_page = $max_num_pages;
               }

               $orders  = array_slice( $orders, ( $current_page - 1 ) * $orders_per_page, $orders_per_page, true );
               $base_url = wc_get_endpoint_url( 'show-subscription', $aswc_subscription_id, wc_get_page_permalink( 'myaccount' ) );

               ?>
               <div class="aswc_account_additional_wrap aswc_account_renewal_wrap">
                       <h3><?php esc_html_e( 'Renewal Order Details', 'advanced-subscriptions-for-woocommerce' ); ?></h3>
                       <table class="shop_table aswc_order_details">
                               <thead>
                                       <tr>
                                               <th>
                                                       <?php esc_html_e( 'Order Id', 'advanced-subscriptions-for-woocommerce' ); ?>
                                               </th>
                                               <th>
                                                       <?php esc_html_e( 'Status', 'advanced-subscriptions-for-woocommerce' ); ?>
                                               </th>
                                               <th>
                                                       <?php esc_html_e( 'Date', 'advanced-subscriptions-for-woocommerce' ); ?>
                                               </th>
                                               <th>
                                                       <?php esc_html_e( 'Order Total', 'advanced-subscriptions-for-woocommerce' ); ?>
                                               </th>
                                               <th>
                                                       <?php esc_html_e( 'Action', 'advanced-subscriptions-for-woocommerce' ); ?>
                                               </th>
                                       </tr>
                               </thead>
                               <tbody>

                                       <?php
                                      foreach ( $orders as $order ) {
                                               $order_timestamp      = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0;
                                               $order_status_slug   = $order->get_status();
                                               $order_status_label  = wc_get_order_status_name( 'wc-' . $order_status_slug );
                                               $order_status_class  = 'status-' . sanitize_html_class( $order_status_slug );

                                               if ( empty( $order_status_label ) ) {
                                                       $order_status_label = $order_status_slug;
                                               }
                                               ?>
                                               <tr>
                                                       <td><?php echo esc_html( $order->get_id() ); ?></td>
                                                       <td class="woocommerce-orders-table__cell-order-status">
                                                               <span class="woocommerce-orders-table__status order-status <?php echo esc_attr( $order_status_class ); ?>">
                                                                       <?php echo esc_html( $order_status_label ); ?>
                                                               </span>
                                                       </td>
                                                       <td><?php echo esc_html( aswc_get_the_wordpress_date_format( $order_timestamp ) ); ?></td>
                                                       <td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
                                                       <td><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                                                                       <?php esc_html_e( 'Show', 'advanced-subscriptions-for-woocommerce' ); ?>
                                                               </a>
                                                       </td>
                                               </tr>
                                               <?php
                                       }
                                       ?>
                               </tbody>
                       </table>
                       <?php if ( 1 < $max_num_pages ) { ?>
                               <div class="aswc_pagination woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
                                       <?php if ( 1 < $current_page ) { ?>
                                               <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( add_query_arg( $page_query_arg, $current_page - 1, $base_url ) ); ?>"><?php esc_html_e( 'Previous', 'advanced-subscriptions-for-woocommerce' ); ?></a>
                                       <?php } ?>
                                       <?php if ( $current_page < $max_num_pages ) { ?>
                                               <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( add_query_arg( $page_query_arg, $current_page + 1, $base_url ) ); ?>"><?php esc_html_e( 'Next', 'advanced-subscriptions-for-woocommerce' ); ?></a>
                                       <?php } ?>
                               </div>
                       <?php } ?>
               </div>
               <?php
       }


	/**
	 * This function used to save the numner of time, the particular product has been cancelled before the trial period ended.
	 *
	 * @param int $subscription_id .
	 * @param int $user_id .
	 */
	public function aswc_restrict_customer_to_cancel_before_trial_ended( int $subscription_id, $user_id ) {

		$product_id = aswc_get_meta_data( $subscription_id, 'product_id', true );

		$renewal_data    = aswc_get_meta_data( $subscription_id, 'aswc_renewal_order_data', true );
		$freq_free_trial = aswc_get_meta_data( $subscription_id, 'aswc_subscription_free_trial_number', true );

		if ( empty( $renewal_data ) && ! empty( $freq_free_trial ) ) {
                       $cancel_product_data      = get_user_meta( $user_id, '_aswc_cancel_before_trial_ended_data', true ) ? get_user_meta( $user_id, '_aswc_cancel_before_trial_ended_data', true ) : array();
			$cancel_ended_count_total = 1;
			if ( isset( $cancel_product_data[ $product_id ] ) ) {
				$cancel_ended_count_total = $cancel_product_data[ $product_id ] + 1;
			}
			$cancel_product_data[ $product_id ] = $cancel_ended_count_total;
                       update_user_meta( $user_id, '_aswc_cancel_before_trial_ended_data', $cancel_product_data );
		}
	}

	/**
	 * This function allow to visible or hide the cancel button for the customer.
	 *
	 * @param int   $default as deafult.
	 * @param array $subscription_id as subscription id.
	 */
	public function aswc_customer_cancel_button_callback( $default, $subscription_id ) {

		$aswc_product_id = aswc_get_meta_data( $subscription_id, 'product_id', true );

		$checkbox_trial_end  = aswc_get_meta_data( $aswc_product_id, 'aswc_free_trails_limit_checkbox', true );
		$get_trial_end_limit = aswc_get_meta_data( $aswc_product_id, 'aswc_limt_for_free_trial', true );
		if ( 'on' === $checkbox_trial_end && ! empty( $get_trial_end_limit ) ) {
			$customer_id           = aswc_get_meta_data( $subscription_id, 'aswc_customer_id', true );
                       $get_saved_cancel_data = get_user_meta( $customer_id, '_aswc_cancel_before_trial_ended_data', true );
			if ( isset( $get_saved_cancel_data[ $aswc_product_id ] ) ) {
				$cancel_count = $get_saved_cancel_data[ $aswc_product_id ];
				if ( $cancel_count >= $get_trial_end_limit ) {
					$default = 'no';
				}
			}
		}
		$after_days = get_option( 'aswc_time_duration_subscription_cancellation' );
		if ( 'yes' === get_option( 'aswc_allow_time_subscription_cancellation' ) && $after_days ) {
			$parent_id    = aswc_get_meta_data( $subscription_id, 'aswc_parent_order', true );
			$parent_order = wc_get_order( $parent_id );
			if ( is_object( $parent_order ) && ! empty( $parent_order ) ) {

				$order_date = $parent_order->get_date_created();

				// Get the current date.
				$current_date = new DateTime();

				// Calculate the difference in days.
				$date_difference = $current_date->diff( $order_date )->days;

				if ( $date_difference < $after_days ) {
					$default = 'no';
				}
			}
		}
		return $default;
	}


	/**
	 * Function to remove propate amount functionality for subscription renewal order.
	 *
         * @param array $aswc_args Subscription arguments.
         * @param array $cart_data Cart item data.
	 * @return array
	 */
	public function aswc_remove_propate_amount_for_subscripition_renewal( $aswc_args, $cart_data ) {

		$product_id = $aswc_args['product_id'];
		$main_price = $aswc_args['aswc_recurring_total'];
		if ( aswc_start_susbcription_from_certain_date_of_month() && aswc_subscription_syn_enable_per_product( $product_id ) ) {

			$aswc_prorate_type = aswc_get_prorate_price_on_sync_enable();
			if ( 'aswc_prorate_simple' == $aswc_prorate_type || 'aswc_prorate_if_free_trial' == $aswc_prorate_type ) {

				$price = aswc_prorate_price_for_sync( $main_price, $product_id, $cart_data );
				if ( $main_price == $price ) {
					return $aswc_args;
				} elseif ( $main_price != $price ) {
					$aswc_args['aswc_show_recurring_total'] = $main_price;
					$aswc_args['line_subtotal']            = $main_price;
					$aswc_args['line_total']               = $main_price;

					return $aswc_args;
				}
			}
		}
		return $aswc_args;
	}

	/**
	 * Function to fix recurring info on cart and checkout for propate amount.
	 *
	 * @param array $line_subtotal as line subtotal.
	 * @param array $cart_item as cart item.
	 * @return array
	 */
	public function aswc_fix_recurring_info_price( $line_subtotal, $cart_item ) {

		$product_id = $cart_item['product_id'];
		if ( aswc_start_susbcription_from_certain_date_of_month() && aswc_subscription_syn_enable_per_product( $product_id ) ) {
			$aswc_prorate_type = aswc_get_prorate_price_on_sync_enable();
			if ( 'aswc_prorate_simple' == $aswc_prorate_type || 'aswc_prorate_if_free_trial' == $aswc_prorate_type ) {
				$product = wc_get_product( $product_id );

				if ( is_a( $product, 'WC_Product' ) ) {

					$price         = $product->get_price();
					$line_subtotal = $price;
					return $line_subtotal;

				}
			} else {
				return $line_subtotal;
			}
		} else {
			return $line_subtotal;
		}
	}
	/**
	 * Function to add cart item meta data.
	 *
	 * @param array $data .
	 * @param array $cart_item .
	 * @return array
	 */
	public function aswc_get_subscription_meta_on_cart( $data = array(), $cart_item = array() ) {
		if ( isset( $cart_item['aswc_upgrade_downgrade_data'] ) ) {
			$aswc_subscription_id = $cart_item['aswc_upgrade_downgrade_data']['aswc_subscription_id'];
			if ( ! aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				return $product_subtotal;
			}
			$aswc_switch_type = $this->aswc_get_upgrade_downgrade_type( $aswc_subscription_id, $cart_item );
			if ( ! empty( $aswc_switch_type ) ) {

				if ( 'upgrade' == $aswc_switch_type ) {
					$aswc_switch_type = __( 'Upgrade', 'advanced-subscriptions-for-woocommerce' );
				} else {
					$aswc_switch_type = __( 'Downgrade', 'advanced-subscriptions-for-woocommerce' );
				}
				$data[] = array(
                                        'name'   => 'aswc-switch-direction',
					'hidden' => true,
					'value'  => html_entity_decode( '<span class="subscription-switch-direction">(' . $aswc_switch_type . ')</span>' ),
				);
			}
		}
		return $data;
	}
	/**
	 * Calculating correct recurring price.
	 *
	 * @param integer $price .
	 * @param array() $cart_data .
	 * @param bool    $bool will decide to show or create subscription.
	 */
	public function aswc_manage_line_total_for_plan_switch_callback( $price, $cart_data, $bool ) {
		// For the upgrade/downgrade process.
		$product_id = $cart_data['data']->get_id();
		if ( $this->aswc_check_cart_has_switch_subscription( $cart_data ) ) {
			$aswc_subscription_id = $cart_data['aswc_upgrade_downgrade_data']['aswc_subscription_id'];

			if ( ! aswc_check_valid_subscription( $aswc_subscription_id ) ) {
				return;
			}
			$price = wc_get_product( $product_id )->get_price() * $cart_data['quantity'];
			return $price;
		}

		// Manage prorate price for the recurring.
		if ( aswc_start_susbcription_from_certain_date_of_month() && aswc_subscription_syn_enable_per_product( $product_id ) ) {
			$price = wc_get_product( $product_id )->get_price() * $cart_data['quantity'];
			return $price;
		}

		return $price;
	}

	/**
	 * To check the product type
	 *
	 * @param boolean $bool .
	 * @param string  $price .
	 * @param integer $product_id .
	 */
	public function aswc_check_one_time_product_callback( $bool, $price, $product_id ) {
		if ( $product_id ) {
			$product                   = wc_get_product( $product_id );
			$aswc_onetime_price     = aswc_get_meta_data( $product_id, 'aswc_onetime_price', true );
			$aswc_one_time_purchase = aswc_get_meta_data( $product_id, 'aswc_one_time_purchase', true );
			if ( ! empty( $product->get_price() ) && 'on' === $aswc_one_time_purchase && ! empty( $aswc_onetime_price ) && $aswc_onetime_price > $product->get_price() && 'variable' != $product->get_type() ) {
				if ( isset( WC()->session ) && 'on' === WC()->session->get( 'aswc_onetime_subscription_' . $product_id ) ) {
					return false;
				}
			}
		}
		return $bool;
	}

	/**
	 * Display the onetime price for WC Cart block
	 *
	 * @param string $price .
	 * @param int    $product_id .
	 * @return $price
	 */
	public function aswc_show_one_time_subscription_price_block_callback( $price, $product_id ) {
		if ( $product_id ) {
			$product                   = wc_get_product( $product_id );
			$aswc_onetime_price     = aswc_get_meta_data( $product_id, 'aswc_onetime_price', true );
			$aswc_one_time_purchase = aswc_get_meta_data( $product_id, 'aswc_one_time_purchase', true );

			if ( ! empty( $product->get_price() ) && 'on' === $aswc_one_time_purchase && ! empty( $aswc_onetime_price ) && $aswc_onetime_price > $product->get_price() && 'variable' != $product->get_type() ) {
				if ( isset( WC()->session ) && 'on' === WC()->session->get( 'aswc_onetime_subscription_' . $product_id ) ) {
					return wc_price( $aswc_onetime_price );
				} else {
					return $price;
				}
			}
		}
		return $price;
	}


	/**
	 * Prorate price tooltip
	 *
	 * @param integer $price .
	 * @param array() $cart_item .
	 * @param string  $cart_item_key .
	 */
	public function woocommerce_cart_item_prorate_subtotal( $price, $cart_item, $cart_item_key ) {

		$product_id = $cart_item['data']->get_id();
		if ( aswc_start_susbcription_from_certain_date_of_month() && aswc_subscription_syn_enable_per_product( $product_id ) ) {
			$aswc_prorate_type = aswc_get_prorate_price_on_sync_enable();

			if ( 'aswc_prorate_simple' == $aswc_prorate_type || 'aswc_prorate_if_free_trial' == $aswc_prorate_type ) {
				$prorate_tooltip_text = esc_html__( 'Prorate pricing adjusts the price of a subscription depending on when it started and when it will be renewed.', 'advanced-subscriptions-for-woocommerce' );
				return $price . '<span class="aswc_prorate_price_tooltip" title="' . $prorate_tooltip_text . '"></span>';
			}
		}
		return $price;
	}

	/**
	 * Update payment method for subscription
	 *
	 * @param mixed $source_object_id .
	 * @param mixed $source_object .
	 * @return void
	 */
	public function aswc_update_payment_method_for_subscription( $source_object_id, $source_object ) {
		if ( isset( $_REQUEST['aswc_subscription_id'] ) && $source_object_id ) {
			$subscription_id = isset( $_REQUEST['aswc_subscription_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['aswc_subscription_id'] ) ) : 0;
			$parent_id       = aswc_get_meta_data( $subscription_id, 'aswc_parent_order', true );
			if ( $parent_id ) {
				aswc_update_order_meta( $parent_id, '_stripe_source_id', $source_object_id );
				$parent_order = wc_get_order( $parent_id );
				// translators: payment method id.
				$parent_order->add_order_note( sprintf( esc_attr__( 'Payment method %s updated', 'advanced-subscriptions-for-woocommerce' ), $source_object_id ) );
			}
		}
	}

	/**
	 * Display a notice
	 *
	 * @param mixed $id .
	 * @return void
	 */
	public function aswc_display_a_notice( $id ) {
		if ( isset( $_REQUEST['aswc_subscription_id'] ) && in_array( $id, array( 'stripe', 'stripe_sepa' ) ) ) {
			$subscription_id = isset( $_REQUEST['aswc_subscription_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['aswc_subscription_id'] ) ) : 0;
						// translators: %s: subscription ID.
						echo '<p>' . sprintf( esc_html__( 'This will update the Payment Method used for the subscription #%s.', 'advanced-subscriptions-for-woocommerce' ), esc_html( $subscription_id ) ) . '</p>';
		}
	}


	/**
	 * This function is used to support wallet payment for subscription.
	 *
	 * @name aswc_wallet_payment_gateway_for_subscription
	 * @param array  $supported_payment_method supported_payment_method.
	 * @param string $payment_method payment_method.
	 * @since 1.0.0
	 */
	public function aswc_wallet_payment_gateway_for_subscription( $supported_payment_method, $payment_method ) {

		$user_id       = get_current_user_id();
               $wallet_amount = get_user_meta( $user_id, '_aswc_wallet', true );

		$aswc_cart_total = WC()->cart->get_total( 'edit' );

		$cart_fee = WC()->cart->get_fee_total();

		$aswc_cart_total = intval( $aswc_cart_total ) + abs( $cart_fee );

		// wallet compatbility.
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'wallet-system-for-woocommerce/wallet-system-for-woocommerce.php' ) ) {
			$plug = get_plugins();
			if ( isset( $plug['wallet-system-for-woocommerce/wallet-system-for-woocommerce.php'] ) ) {
				if ( $plug['wallet-system-for-woocommerce/wallet-system-for-woocommerce.php']['Version'] > '2.5.16' ) {
					if ( $wallet_amount >= $aswc_cart_total ) {

						if ( 'aswc_wcb_wallet_payment_gateway' == $payment_method ) {
							$supported_payment_method[] = $payment_method;
						}
					}
				}
			}
		}
		// wallet compatbility.
		return $supported_payment_method;
	}
}

