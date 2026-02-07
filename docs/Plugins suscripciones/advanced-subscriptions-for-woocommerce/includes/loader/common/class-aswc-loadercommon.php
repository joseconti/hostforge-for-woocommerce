<?php
/**
 * The common functionality of the plugin.
 *
 * @link       https://plugins.joseconti.com
 * @since 1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/common
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * The common functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the common stylesheet and JavaScript.
 * namespace woocommerce_subscriptions_pro_common.
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/common
 */
class Aswc_LoaderCommon {
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
	 * Register the stylesheets for the common side of the site.
	 *
	 * @since 1.0.0
	 */
	public function aswc_common_enqueue_styles() {

                wp_enqueue_style( 'aswc-loader-common', ASWC_INCLUDES_DIR_URL . 'common/css/aswc-loader-common.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the common side of the site.
	 *
	 * @since 1.0.0
	 */
	public function aswc_common_enqueue_scripts() {

                wp_register_script( 'aswc-loader-common', ASWC_INCLUDES_DIR_URL . 'common/js/aswc-loader-common.js', array( 'jquery' ), $this->version, false );
                wp_localize_script(
                        'aswc-loader-common',
			'aswc_common_param',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'aswc_onetime_nonce' ),
			)
		);
                        wp_enqueue_script( 'aswc-loader-common' );
	}
		/**
		 * Create subscription type coupon.
		 *
		 * @name aswc_subscription_coupon_discount_types
		 * @param array $discount_types discount_types.
		 * @since 1.0.0
		 */
	public function aswc_subscription_coupon_discount_types( $discount_types ) {
		$aswc_discount_types = array(
			'initial_fee_discount'               => __( 'Initial Signup Fee Discount', 'advanced-subscriptions-for-woocommerce' ),
			'initial_fee_percent_discount'       => __( 'Initial Signup Fee Percent Discount', 'advanced-subscriptions-for-woocommerce' ),
			'recurring_product_discount'         => __( 'Recurring And Product Discount', 'advanced-subscriptions-for-woocommerce' ),
			'recurring_product_percent_discount' => __( 'Recurring And Product Percent Discount', 'advanced-subscriptions-for-woocommerce' ),
		);
		return array_merge( $discount_types, $aswc_discount_types );
	}

	/**
	 * This function is used to validate subscription coupon.
	 *
	 * @name aswc_validate_subscription_coupon_for_product
	 * @param bool   $is_valid is_valid.
	 * @param object $product product.
	 * @param object $coupon coupon.
	 * @since 1.0.0
	 */
	public function aswc_validate_subscription_coupon_for_product( $is_valid, $product, $coupon ) {
		if ( ! $is_valid ) {
			return $is_valid;
		}
		$coupon_type                        = $coupon->get_discount_type();
		$aswc_recurring_subscription_coupon = aswc_get_subscription_discount_type();
		$aswc_signup_subscription_coupon    = aswc_get_subscription_signup_discount_type();
		$aswc_is_recurring_coupon           = isset( $aswc_recurring_subscription_coupon[ $coupon_type ] );
		$aswc_is_sign_up_fee_coupon         = isset( $aswc_signup_subscription_coupon[ $coupon_type ] );

		if ( ( $aswc_is_recurring_coupon || $aswc_is_sign_up_fee_coupon ) && ! aswc_check_product_is_subscription( $product ) ) {
			$this->coupon_error = __( 'Sorry, this coupon is only valid for subscription products.', 'advanced-subscriptions-for-woocommerce' );
			$is_valid           = false;
		} elseif ( $aswc_is_sign_up_fee_coupon && '' === aswc_get_signup_fee( $product ) ) {
			$this->coupon_error = __( 'Sorry, this coupon is only valid for subscription products with a sign-up fee.', 'advanced-subscriptions-for-woocommerce' );
			$is_valid           = false;
		}

		return $is_valid;
	}

	/**
	 * This function is used to set subscription type coupon.
	 *
	 * @name aswc_woocommerce_product_coupon_types
	 * @param array $coupon_types coupon_types.
	 * @since 1.0.0
	 */
	public function aswc_woocommerce_product_coupon_types( $coupon_types ) {

		if ( is_array( $coupon_types ) ) {
			$aswc_discount_type = array(
				'initial_fee_discount',
				'initial_fee_percent_discount',
				'recurring_product_discount',
				'recurring_product_percent_discount',
			);

			$coupon_types = array_merge( $coupon_types, $aswc_discount_type );

		}

		return $coupon_types;
	}

	/**
	 * Get discount amount.
	 *
	 * @name aswc_get_discount_amount
	 * @param string $discount discount.
	 * @param int    $discounting_amount discounting_amount.
	 * @param object $item item.
	 * @param string $single single.
	 * @param object $coupon coupon.
	 * @since 1.0.0
	 */
	public function aswc_get_discount_amount( $discount, $discounting_amount, $item, $single, $coupon ) {

		if ( is_a( $item, 'WC_Order_Item' ) ) {

			$discount = $this->aswc_get_discount_amount_for_line_item( $item, $discount, $discounting_amount, $single, $coupon );
		} else {

			$discount = $this->aswc_get_discount_amount_for_cart_item( $item, $discount, $discounting_amount, $single, $coupon );
		}
		return $discount;
	}

	/**
	 * Get discount amount.
	 *
	 * @name aswc_get_discount_amount_for_line_item
	 * @param object $line_item line_item.
	 * @param int    $discount discount.
	 * @param int    $discounting_amount discounting_amount.
	 * @param string $single single.
	 * @param object $coupon coupon.
	 * @since 1.0.0
	 */
	public function aswc_get_discount_amount_for_line_item( $line_item, $discount, $discounting_amount, $single, $coupon ) {

		if ( ! is_callable( array( $line_item, 'get_order' ) ) ) {
			return $discount;
		}

		$coupon_type = $coupon->get_discount_type();

		$order   = $line_item->get_order();
		$product = $line_item->get_product();

		if ( in_array( $coupon_type, array( 'recurring_product_discount', 'recurring_product_percent_discount' ) ) && ( aswc_check_valid_subscription( $order->get_id() ) || aswc_check_product_is_subscription( $product ) ) ) {
			if ( 'recurring_product_percent_discount' === $coupon_type ) {
				$discount = (float) $coupon->get_amount() * ( $discounting_amount / 100 );
			} else {
				$discount = min( $coupon->get_amount(), $discounting_amount );
				$discount = $single ? $discount : $discount * $line_item->get_quantity();
			}
			$discount = $this->aswc_get_discount_amount_for_currency_switchers( $discount, $order );
		} elseif ( in_array( $coupon_type, array( 'initial_fee_discount', 'initial_fee_percent_discount' ) ) && aswc_check_product_is_subscription( $product ) && 0 !== aswc_get_signup_fee( $product ) ) {
			if ( 'initial_fee_discount' === $coupon_type ) {
				$discount = min( $coupon->get_amount(), aswc_get_signup_fee( $product ) );
				$discount = $single ? $discount : $discount * $line_item->get_quantity();
			} else {
				$discount = (float) $coupon->get_amount() * ( aswc_get_signup_fee( $product ) / 100 );
			}
		}

		return $discount;
	}

	/**
	 * Get discount amount.
	 *
	 * @name aswc_get_discount_amount_for_currency_switchers
	 * @param int    $discount discount.
	 * @param object $order order.
	 * @since 1.0.0
	 */
	public function aswc_get_discount_amount_for_currency_switchers( $discount, $order ) {
		if ( is_object( $order ) ) {
			$order_id        = $order->get_id();
			$subscription_id = aswc_get_meta_data( $order_id, 'aswc_subscription', true );
			if ( aswc_check_valid_subscription( $subscription_id ) ) {
				if ( function_exists( 'aswc_mmcsfw_admin_fetch_currency_rates_from_base_currency' ) ) {

					if ( aswc_loader_is_hpos_enabled() ) {
						$subscription = new ASWC_Subscription( $subscription_id );
					} else {
						$subscription = wc_get_order( $subscription_id );
					}
					$order_currency = $subscription->get_currency();
					$discount       = aswc_mmcsfw_admin_fetch_currency_rates_from_base_currency( $discount, $order_currency );
				}
			}
		}

		return $discount;
	}

	/**
	 * Get discount amount.
	 *
	 * @name aswc_get_discount_amount_for_line_item
	 * @param array  $cart_item cart_item.
	 * @param int    $discount discount.
	 * @param int    $discounting_amount discounting_amount.
	 * @param string $single single.
	 * @param object $coupon coupon.
	 * @since 1.0.0
	 */
	public function aswc_get_discount_amount_for_cart_item( $cart_item, $discount, $discounting_amount, $single, $coupon ) {

		$coupon_type = $coupon->get_discount_type();

		if ( ! in_array( $coupon_type, array( 'initial_fee_discount', 'initial_fee_percent_discount', 'recurring_product_discount', 'recurring_product_percent_discount' ) ) ) {
			return $discount;
		}

		// If not a subscription product return the default discount.
		if ( ! aswc_check_product_is_subscription( $cart_item['data'] ) ) {
			return $discount;
		}
		$aswc_apply_initial_coupon         = false;
		$aswc_apply_initial_percent_coupon = false;
		$discount_amount                   = 0;
		$cart_item_qty                     = is_null( $cart_item ) ? 1 : $cart_item['quantity'];
		if ( aswc_get_signup_fee( $cart_item['data'] ) > 0 ) {

			if ( 'initial_fee_discount' === $coupon_type ) {
				$aswc_apply_initial_coupon = true;
			}

			if ( 'initial_fee_percent_discount' === $coupon_type ) {
				$aswc_apply_initial_percent_coupon = true;
			}

			if ( in_array( $coupon_type, array( 'initial_fee_discount', 'initial_fee_percent_discount' ) ) ) {
				$discounting_amount = aswc_get_signup_fee( $cart_item['data'] );
			}
		}
		if ( aswc_get_recurring_total( $cart_item['data'] ) > 0 ) {
			if ( 'recurring_product_discount' === $coupon_type ) {
				$aswc_apply_initial_coupon = true;

			}

			if ( 'recurring_product_percent_discount' === $coupon_type ) {
				$aswc_apply_initial_percent_coupon = true;
			}

			if ( in_array( $coupon_type, array( 'recurring_product_discount', 'recurring_product_percent_discount' ) ) ) {
				$discounting_amount = aswc_get_recurring_total( $cart_item['data'] );
			}
		}

		// Calculate our discount.
		if ( $aswc_apply_initial_coupon ) {

			// Recurring coupons only apply when there is no free trial (carts can have a mix of free trial and non free trial items).
			if ( $aswc_apply_initial_coupon && 'recurring_product_discount' === $coupon_type && aswc_get_get_trial_period( $cart_item['data'] ) > 0 ) {
				$discounting_amount = 0;
			}
			$discount_amount = min( $coupon->get_amount(), $discounting_amount );

			$discount_amount = $single ? $discount_amount : $discount_amount * $cart_item_qty;

		} elseif ( $aswc_apply_initial_percent_coupon ) {

			if ( $aswc_apply_initial_percent_coupon && 'recurring_product_percent_discount' === $coupon_type && aswc_get_get_trial_period( $cart_item['data'] ) > 0 ) {
				$discounting_amount = 0;
			}

			$discount_amount = ( $discounting_amount / 100 ) * $coupon->get_amount();
		}

		$discount_amount = round( $discount_amount, wc_get_price_decimals() );
		return $discount_amount;
	}

	/**
	 * Validate coupon.
	 *
	 * @name aswc_validate_subscription_coupon
	 * @param bool   $valid valid.
	 * @param string $aswc_coupon aswc_coupon.
	 * @param object $discount discount.
	 * @since 1.0.0
	 */
	public function aswc_validate_subscription_coupon( $valid, $aswc_coupon, $discount ) {
		if ( is_a( $discount, 'WC_Discounts' ) ) {
			$discount_items = $discount->get_items();
			if ( is_array( $discount_items ) && ! empty( $discount_items ) ) {
				$item = reset( $discount_items );

				if ( isset( $item->object ) && is_a( $item->object, 'WC_Order_Item' ) ) {

					$valid = $this->aswc_validate_coupon_for_order( $valid, $aswc_coupon, $item->object->get_order() );
				} else {
					$valid = $this->aswc_validate_coupon_for_cart( $valid, $aswc_coupon );
				}
			}
		} else {
			$valid = $this->aswc_validate_coupon_for_cart( $valid, $aswc_coupon );
		}
		return $valid;
	}

	/**
	 * Validate coupon.
	 *
	 * @name aswc_validate_coupon_for_cart
	 * @param bool   $valid valid.
	 * @param string $aswc_coupon aswc_coupon.
	 * @since 1.0.0
	 */
	public function aswc_validate_coupon_for_cart( $valid, $aswc_coupon ) {
		$coupon_type = $aswc_coupon->get_discount_type();

		if ( ! in_array( $coupon_type, array( 'initial_fee_discount', 'initial_fee_percent_discount', 'recurring_product_discount', 'recurring_product_percent_discount' ) ) ) {
			return $valid;
		} elseif ( ! aswc_check_is_cart_subscription() ) {
			// prevent subscription coupons from being applied to non-subscription products.
			$this->coupon_error = __( 'Sorry, this coupon is only valid for subscription products.', 'advanced-subscriptions-for-woocommerce' );
			$valid              = false;
		}
		return $valid;
	}

	/**
	 * Validate coupon.
	 *
	 * @name aswc_coupon_error
	 * @param string $error error.
	 * @since 1.0.0
	 */
	public function aswc_coupon_error( $error ) {

		if ( ! empty( $this->coupon_error ) ) {
			return $this->coupon_error;
		} else {
			return $error;
		}
	}

	/**
	 * Validate coupon.
	 *
	 * @name aswc_validate_coupon_for_order
	 * @param bool   $valid valid.
	 * @param string $aswc_coupon aswc_coupon.
	 * @param object $order order.
	 * @throws Exception When not able to apply coupon.
	 * @since 1.0.0
	 */
	public function aswc_validate_coupon_for_order( $valid, $aswc_coupon, $order ) {
		$coupon_type        = $aswc_coupon->get_discount_type();
		$aswc_error_message = '';

		if ( ! in_array( $coupon_type, array( 'initial_fee_discount', 'initial_fee_percent_discount', 'recurring_product_discount', 'recurring_product_percent_discount' ) ) ) {

			return $valid;
		} elseif ( ! ( aswc_check_is_order_subscription( $order ) || aswc_check_is_renewal_order( $order ) ) ) {
			// prevent subscription coupons from being applied to non-subscription products.
			$aswc_error_message = __( 'Sorry, this coupon is only valid for subscription products.', 'advanced-subscriptions-for-woocommerce' );
		}

		if ( ! empty( $aswc_error_message ) ) {
			throw new Exception( esc_html( $aswc_error_message ) );
		}

		return $valid;
	}

	/**
	 * Add Email Classes.
	 *
	 * @name aswc_woocommerce_email_classes
	 * @param array $email_class email_class.
	 * @since 1.0.0
	 */
	public function aswc_woocommerce_email_classes( $email_class ) {

		$email_class['aswc_pause_subscription']           = require_once ASWC_INCLUDES_PATH . 'emails/class-aswc-loaderpause-subscription-email.php';
		$email_class['aswc_reactivate_subscription']      = require_once ASWC_INCLUDES_PATH . 'emails/class-aswc-loaderreactivate-subscription-email.php';
		$email_class['aswc_renewal_subscription_invoice'] = require_once ASWC_INCLUDES_PATH . 'emails/class-aswc-loaderrenewal-subscription-invoice-email.php';
		$email_class['aswc_plan_going_expire']            = require_once ASWC_INCLUDES_PATH . 'emails/class-aswc-loaderplan-going-to-expire-email.php';
		$email_class['aswc_recurring_reminder']           = require_once ASWC_INCLUDES_PATH . 'emails/class-aswc-loaderreminder-email.php';

		return $email_class;
	}

	/**
	 * Order status change.
	 *
	 * @name aswc_woocommerce_order_status_changed
	 * @param int    $order_id order_id.
	 * @param string $old_status old_status.
	 * @param string $new_status new_status.
	 * @since 1.0.0
	 */
	public function aswc_woocommerce_order_status_changed( $order_id, $old_status, $new_status ) {
		$aswc_is_manual_renewal = aswc_get_meta_data( $order_id, 'aswc_manual_renewal_order', true );

		if ( 'pending' !== $aswc_is_manual_renewal ) {
			return;
		}

		if ( $old_status !== $new_status ) {
			if ( 'completed' === $new_status || 'processing' === $new_status ) {
				$aswc_subscription_id = aswc_get_meta_data( $order_id, 'aswc_subscription', true );
				if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
					$aswc_subscription_status = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_status', true );
					if ( 'on-hold' === $aswc_subscription_status ) {
						aswc_update_order_meta( $aswc_subscription_id, 'aswc_subscription_status', 'active' );
					}
					aswc_update_order_meta( $order_id, 'aswc_manual_renewal_order', 'success' );
				}
			}
		}
	}

	/**
	 * Unset payment method on rebewal order.
	 *
	 * @name aswc_reordering_order_item_totals
	 * @param array  $total_rows total_rows.
	 * @param object $order order.
	 * @param string $tax_display tax_display.
	 * @since 1.0.0
	 */
	public function aswc_reordering_order_item_totals( $total_rows, $order, $tax_display ) {

		$order_id               = $order->get_id();
		$aswc_is_manual_renewal = aswc_get_meta_data( $order_id, 'aswc_manual_renewal_order', true );

		if ( 'pending' === $aswc_is_manual_renewal ) {
			if ( isset( $total_rows['payment_method'] ) ) {
				unset( $total_rows['payment_method'] );
			}
		}
		return $total_rows;
	}


	/**
	 * Switch order.
	 *
	 * @name aswc_upgrade_downgrade_order_status_changed
	 * @param int    $order_id order_id.
	 * @param string $old_status old_status.
	 * @param string $new_status new_status.
	 * @since 1.0.0
	 */
	public function aswc_upgrade_downgrade_order_status_changed( $order_id, $old_status, $new_status ) {
		$aswc_is_switch = aswc_get_meta_data( $order_id, 'aswc_upgrade_downgrade_order', true );

		if ( 'yes' !== $aswc_is_switch ) {
			return;
		}
		$aswc_is_switch_succes = aswc_get_meta_data( $order_id, 'aswc_upgrade_downgrade_order_succes', true );

		if ( 'success' === $aswc_is_switch_succes ) {
			return;
		}

		if ( $old_status !== $new_status ) {
			if ( 'completed' === $new_status || 'processing' === $new_status ) {

				$aswc_subscription_id = aswc_get_meta_data( $order_id, 'aswc_subscription', true );

				if ( WC()->session->downgrade_upgrade_notice ) {
					WC()->session->__unset( 'downgrade_upgrade_notice' );
				}
				if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {

					$aswc_is_switch_data = aswc_get_meta_data( $aswc_subscription_id, 'aswc_upgrade_downgrade_data', true );
					$aswc_recurring_data = isset( $aswc_is_switch_data['aswc_switch_recurring_data'] ) ? $aswc_is_switch_data['aswc_switch_recurring_data'] : '';

					$aswc_switch_order_id = isset( $aswc_is_switch_data['aswc_switch_recurring_order_id'] ) ? $aswc_is_switch_data['aswc_switch_recurring_order_id'] : '';

					$aswc_switch_cart_data = isset( $aswc_is_switch_data['aswc_switch_cart_data'] ) ? $aswc_is_switch_data['aswc_switch_cart_data'] : '';

					$aswc_switch_type = isset( $aswc_is_switch_data['aswc_switch_type'] ) ? $aswc_is_switch_data['aswc_switch_type'] : '';

					/*check valid order*/
					if ( $aswc_switch_order_id !== $order_id ) {
						return;
					}
					if ( isset( $aswc_recurring_data ) && ! empty( $aswc_recurring_data ) && is_array( $aswc_recurring_data ) ) {
						if ( ! isset( $aswc_recurring_data['aswc_subscription_expiry_number'] ) ) {
							$aswc_recurring_data['aswc_subscription_expiry_number'] = 0;
						}
						if ( ! isset( $aswc_recurring_data['aswc_subscription_free_trial_number'] ) ) {
							$aswc_recurring_data['aswc_subscription_free_trial_number'] = 0;
						}
						$order = wc_get_order( $order_id );

						$aswc_recurring_data['line_subtotal'] = $aswc_recurring_data['aswc_recurring_total'];
						$aswc_recurring_data['line_total']    = $aswc_recurring_data['aswc_recurring_total'];

						if ( aswc_loader_is_hpos_enabled() ) {
							$new_order = new ASWC_Subscription( $aswc_subscription_id );
						} else {
							$new_order = new WC_Order( $aswc_subscription_id );
						}

						$_product = wc_get_product( $aswc_recurring_data['product_id'] );
						foreach ( $new_order->get_items() as $remove_item_id => $item ) {
							if ( $remove_item_id ) {
								$item->set_props(
									array(
										'name'       => $_product->get_name(),
										'product_id' => $aswc_recurring_data['product_id'],
										'quantity'   => $aswc_recurring_data['product_qty'],
										'subtotal'   => $aswc_recurring_data['line_subtotal'],
										'total'      => $aswc_recurring_data['line_total'],
									)
								);
							}
						}

						$billing_details  = $order->get_address( 'billing' );
						$shipping_details = $order->get_address( 'shipping' );

						$new_order->set_address( $billing_details, 'billing' );
						$new_order->set_address( $shipping_details, 'shipping' );

						$new_order->save();
						$new_order->update_taxes();
						$new_order->calculate_totals();

						aswc_update_meta_key_for_susbcription( $aswc_subscription_id, $aswc_recurring_data );

						/*calculate next payment date*/
						$current_time = current_time( 'timestamp' );
						if ( 'downgrade' === $aswc_switch_type ) {
							$aswc_susbcription_trial_end = aswc_susbcription_trial_date( $aswc_subscription_id, $current_time );
							aswc_update_order_meta( $aswc_subscription_id, 'aswc_susbcription_trial_end', $aswc_susbcription_trial_end );
							if ( empty( $aswc_susbcription_trial_end ) ) {
								$aswc_next_payment_date = aswc_next_payment_date( $aswc_subscription_id, $current_time, $aswc_susbcription_trial_end );
							} else {
								$aswc_next_payment_date = isset( $aswc_switch_cart_data['aswc_next_payment_date'] ) ? $aswc_switch_cart_data['aswc_next_payment_date'] : '';
							}
							$aswc_susbcription_end = aswc_susbcription_expiry_date( $aswc_subscription_id, $current_time, $aswc_susbcription_trial_end );

							aswc_update_order_meta( $aswc_subscription_id, 'aswc_susbcription_end', $aswc_susbcription_end );
							aswc_update_order_meta( $aswc_subscription_id, 'aswc_next_payment_date', $aswc_next_payment_date );

							$aswc_wsf_manage_prorate_upgrade_downgrade = get_option( 'aswc_manage_prorate_amount', false );
							if ( $aswc_wsf_manage_prorate_upgrade_downgrade ) {
								if ( 'aswc_manage_prorate_next_payment_date' === $aswc_wsf_manage_prorate_upgrade_downgrade ) {
									$get_updated_payment_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_date', true );
									if ( $get_updated_payment_date ) {
										$timestamp = strtotime( '+' . $get_updated_payment_date . 'days', $aswc_next_payment_date );
										aswc_update_order_meta( $aswc_subscription_id, 'aswc_next_payment_date', $timestamp );
										if ( $timestamp >= $aswc_susbcription_end && 0 !== $aswc_susbcription_end ) {
											aswc_update_order_meta( $aswc_subscription_id, 'aswc_next_payment_date', $aswc_susbcription_end );
											aswc_update_order_meta( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_date', 0 );
										}
									}
								} elseif ( 'aswc_manage_prorate_using_wallet' === $aswc_wsf_manage_prorate_upgrade_downgrade ) {
									$get_left_wallet_balance = aswc_get_meta_data( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_wallet', true );

									if ( $get_left_wallet_balance ) {

										$order   = wc_get_order( $order_id );
										$user_id = $order->get_user_id();
										if ( 0 !== $user_id ) {
																						$wallet_balance = get_user_meta( $user_id, '_aswc_wallet', true );

											$wallet_balance = empty( $wallet_balance ) ? 0 : $wallet_balance;
											$final_price    = $wallet_balance + $get_left_wallet_balance;

																						update_user_meta( $user_id, '_aswc_wallet', $final_price );
											aswc_update_order_meta( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_wallet', 0 );

										}
									}
								}
							}
						} elseif ( 'upgrade' === $aswc_switch_type ) {
							$aswc_susbcription_trial_end = aswc_susbcription_trial_date( $aswc_subscription_id, $current_time );
							aswc_update_order_meta( $aswc_subscription_id, 'aswc_susbcription_trial_end', $aswc_susbcription_trial_end );

							$aswc_next_payment_date   = aswc_next_payment_date( $aswc_subscription_id, $current_time, $aswc_susbcription_trial_end );
							$get_updated_payment_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_next_payment_date_updated', true );
							aswc_update_order_meta( $aswc_subscription_id, 'aswc_next_payment_date', $aswc_next_payment_date );
							$aswc_susbcription_end = aswc_susbcription_expiry_date( $aswc_subscription_id, $current_time, $aswc_susbcription_trial_end );
							aswc_update_order_meta( $aswc_subscription_id, 'aswc_susbcription_end', $aswc_susbcription_end );

							$aswc_wsf_manage_prorate_upgrade_downgrade = get_option( 'aswc_manage_prorate_amount', false );
							if ( $aswc_wsf_manage_prorate_upgrade_downgrade ) {
								if ( 'aswc_manage_prorate_next_payment_date' === $aswc_wsf_manage_prorate_upgrade_downgrade ) {
									$get_updated_payment_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_date', true );
									if ( $get_updated_payment_date ) {
										$timestamp = strtotime( '+' . $get_updated_payment_date . 'days', $aswc_next_payment_date );
										aswc_update_order_meta( $aswc_subscription_id, 'aswc_next_payment_date', $timestamp );
										if ( $timestamp >= $aswc_susbcription_end ) {
											aswc_update_order_meta( $aswc_subscription_id, 'aswc_next_payment_date', $aswc_susbcription_end );
											aswc_update_order_meta( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_date', 0 );
										}
									}
								} elseif ( 'aswc_manage_prorate_using_wallet' === $aswc_wsf_manage_prorate_upgrade_downgrade ) {
									$get_left_wallet_balance = aswc_get_meta_data( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_wallet', true );

									if ( $get_left_wallet_balance ) {

										$order   = wc_get_order( $order_id );
										$user_id = $order->get_user_id();
										if ( 0 !== $user_id ) {
																						$wallet_balance = get_user_meta( $user_id, '_aswc_wallet', true );

											$wallet_balance = empty( $wallet_balance ) ? 0 : $wallet_balance;
											$final_price    = $wallet_balance + $get_left_wallet_balance;

																						update_user_meta( $user_id, '_aswc_wallet', $final_price );
											aswc_update_order_meta( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_wallet', 0 );

										}
									}
								}
							}
						}

						$aswc_switch_order_data = aswc_get_meta_data( $aswc_subscription_id, 'aswc_switch_order_data', true );
						if ( empty( $aswc_switch_order_data ) ) {
							$aswc_switch_order_data = array( $order_id );
							aswc_update_order_meta( $aswc_subscription_id, 'aswc_switch_order_data', $aswc_switch_order_data );
						} else {
							$aswc_switch_order_data[] = $order_id;
							aswc_update_order_meta( $aswc_subscription_id, 'aswc_switch_order_data', $aswc_switch_order_data );
						}
						aswc_update_order_meta( $aswc_subscription_id, 'aswc_last_switch_order_id', $order_id );

						aswc_update_order_meta( $order_id, 'aswc_upgrade_downgrade_order_succes', 'success' );
					}
				}
			}
		}
	}

	/**
	 * First payment date.
	 *
	 * @name aswc_first_payment_date_for_sync
	 * @param int $aswc_next_payment_date aswc_next_payment_date.
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 * @since 1.0.0
	 */
	public function aswc_first_payment_date_for_sync( $aswc_next_payment_date, $aswc_subscription_id ) {

		if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {

			$product_id = aswc_get_meta_data( $aswc_subscription_id, 'product_id', true );
			if ( aswc_start_susbcription_from_certain_date_of_month() && aswc_subscription_syn_enable_per_product( $product_id ) ) {
				$aswc_first_payment_date = aswc_get_sync_first_payment_date_for_price( $product_id );

				if ( ! empty( $aswc_first_payment_date ) && ! aswc_check_is_today_date( $aswc_first_payment_date ) ) {
					$aswc_next_payment_date = $aswc_first_payment_date;
					aswc_update_order_meta( $aswc_subscription_id, 'aswc_first_payment_date', $aswc_first_payment_date );
				}
			}
		}
		return $aswc_next_payment_date;
	}

	/**
	 * Expiry interval for variation products.
	 *
	 * @name aswc_variation_expiry
	 * @since 1.0.0
	 */
	public function aswc_variation_expiry() {
            check_ajax_referer( 'aswc-verify-nonce', 'aswc_nonce' );
		$aswc_response           = array();
		$aswc_response['result'] = false;
		if ( isset( $_POST['variation_id'] ) && ! empty( $_POST['variation_id'] ) ) {
			$variation_id = sanitize_text_field( wp_unslash( $_POST['variation_id'] ) );
			$product      = wc_get_product( $variation_id );

			if ( aswc_check_product_is_subscription( $product ) ) {

				$subscription_interval = aswc_get_meta_data( $variation_id, 'aswc_subscription_interval', true );
				if ( empty( $subscription_interval ) ) {
					$subscription_interval = 'day';
				}

				$aswc_response['aswc_interval'] = $subscription_interval;
				$aswc_response['result']        = true;
			}
		}
		wp_send_json( $aswc_response );
	}


	/**
	 * Set discount type for giftcard.
	 *
	 * @name aswc_discount_type_for_giftcard
	 * @param string $discount_type discount_type.
	 * @since 1.0.0
	 */
	public function aswc_discount_type_for_giftcard( $discount_type ) {
		if ( aswc_get_subscription_coupon_enable_for_gc() ) {
			$discount_type = aswc_get_subscription_coupon_type_for_gc();
		}
		return $discount_type;
	}

	/**
	 * Apply giftcard.
	 *
	 * @name aswc_apply_giftcard_coupon
	 * @since 1.0.0
	 */
	public function aswc_apply_giftcard_coupon() {
            check_ajax_referer( 'aswc-verify-nonce', 'aswc_nonce' );
		$aswc_response           = array();
		$aswc_response['result'] = false;
		if ( isset( $_POST['subscription_id'] ) && ! empty( $_POST['subscription_id'] ) ) {
			$subscription_id = sanitize_text_field( wp_unslash( $_POST['subscription_id'] ) );
			$coupon_code     = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
			if ( aswc_check_valid_subscription( $subscription_id ) ) {
				$already_used_gift_card = aswc_get_meta_data( $subscription_id, 'aswc_wgm_giftcard_coupon', true );
				if ( empty( $already_used_gift_card ) ) {
					$coupon    = new WC_Coupon( $coupon_code );
					$coupon_id = $coupon->get_id();

					if ( '' !== $coupon_id && 0 !== $coupon_id ) {

						$giftcardcoupon_order_id = aswc_get_meta_data( $coupon_id, 'aswc_wgm_giftcard_coupon', true );
						if ( isset( $giftcardcoupon_order_id ) && '' !== $giftcardcoupon_order_id ) {

							$coupon_usage_count = aswc_get_meta_data( $coupon_id, 'usage_count', true );
							$coupon_usage_limit = aswc_get_meta_data( $coupon_id, 'usage_limit', true );

							if ( 0 === $coupon_usage_limit || $coupon_usage_limit > $coupon_usage_count ) {
								$coupon_expiry = aswc_get_meta_data( $coupon_id, 'date_expires', true );
								if ( '' === $coupon_expiry || $coupon_expiry > current_time( 'timestamp' ) ) {

									aswc_update_order_meta( $subscription_id, 'aswc_wgm_giftcard_coupon', strtolower( $coupon_code ) );
									$aswc_response['msg']    = __( 'Coupon Applied Successfully', 'advanced-subscriptions-for-woocommerce' );
									$aswc_response['result'] = true;
								} else {
									$aswc_response['msg'] = __( 'Coupon is expired', 'advanced-subscriptions-for-woocommerce' );
								}
							} else {
								$aswc_response['msg'] = __( 'Coupon is already used', 'advanced-subscriptions-for-woocommerce' );
							}
						} else {
							$aswc_response['msg'] = __( 'Coupon is not valid Giftcard', 'advanced-subscriptions-for-woocommerce' );
						}
					} else {
						$aswc_response['msg'] = __( 'Coupon is not valid Giftcard', 'advanced-subscriptions-for-woocommerce' );

					}
				} else {
					$aswc_response['msg'] = __( 'Gift Card already applied', 'advanced-subscriptions-for-woocommerce' );

				}
			}
		}
		wp_send_json( $aswc_response );
	}

	/**
	 * Validate giftcard coupon..
	 *
	 * @name aswc_subscription_renewal_order_coupon
	 * @param bool   $valid $valid.
	 * @param int    $order_id $order_id.
	 * @param object $coupon $coupon.
	 * @since 1.0.0
	 */
	public function aswc_subscription_renewal_order_coupon( $valid, $order_id, $coupon ) {
		// Return if renewal order.
		$order = wc_get_order( $order_id );
		if ( aswc_check_is_renewal_order( $order ) ) {
			$valid = true;
		} elseif ( isset( $coupon ) && is_object( $coupon ) ) {
			$coupon_type = $coupon->get_discount_type();
			if ( in_array( $coupon_type, apply_filters( 'aswc_validate_coupon_type_for_giftcard', array( 'initial_fee_discount', 'initial_fee_percent_discount', 'recurring_product_discount', 'recurring_product_percent_discount' ) ) ) ) {
				$valid = true;
			}
		}
		return $valid;
	}

	/**
	 * Order status change.
	 *
	 * @name aswc_update_giftcard_coupon_amount
	 * @param int    $order_id order_id.
	 * @param string $old_status old_status.
	 * @param string $new_status new_status.
	 * @since 1.0.0
	 */
	public function aswc_update_giftcard_coupon_amount( $order_id, $old_status, $new_status ) {

		$order = wc_get_order( $order_id );
		if ( ! aswc_check_is_renewal_order( $order ) ) {
			return;
		}

		$aswc_is_gc_amount = aswc_get_meta_data( $order_id, 'aswc_gc_coupon_updated', true );
		if ( 'yes' === $aswc_is_gc_amount ) {
			return;
		}
		if ( $old_status !== $new_status ) {
			if ( in_array( $new_status, apply_filters( 'aswc_check_order_status', array( 'processing', 'completed', 'on-hold' ) ) ) ) {

				$order = wc_get_order( $order_id );

				$subscription_id = aswc_get_meta_data( $order_id, 'aswc_subscription', true );

				if ( aswc_loader_is_hpos_enabled() ) {
					$subscription = new ASWC_Subscription( $subscription_id );
				} else {
					$subscription = wc_get_order( $subscription_id );
				}
				$order_currency = $subscription->get_currency();

				$coupon_itmes = $order->get_items( 'coupon' );
				if ( isset( $coupon_itmes ) && ! empty( $coupon_itmes ) && is_array( $coupon_itmes ) ) {

					foreach ( $coupon_itmes as $item_id => $item ) {
						$coupon_code = $item->get_code();
						$the_coupon  = new WC_Coupon( $coupon_code );
						$coupon_id   = $the_coupon->get_id();
						if ( isset( $coupon_id ) ) {
							$rate = 1;
							// price based on country.
							if ( class_exists( 'WCPBC_Pricing_Zone' ) ) {

								if ( wcpbc_the_zone() !== null && wcpbc_the_zone() ) {

									$rate = wcpbc_the_zone()->get_exchange_rate();
								}
							}
							$giftcardcoupon = aswc_get_meta_data( $coupon_id, 'aswc_wgm_giftcard_coupon', true );
							if ( ! empty( $giftcardcoupon ) ) {

								$coupon_type = $the_coupon->get_discount_type();

								if ( ! in_array( $coupon_type, apply_filters( 'aswc_validate_coupon_type_for_giftcard', array( 'initial_fee_discount', 'initial_fee_percent_discount', 'recurring_product_discount', 'recurring_product_percent_discount' ) ) ) ) {

									if ( class_exists( 'Woocommerce_Gift_Cards_Common_Function' ) ) {
										$aswc_wgm_discount     = $item->get_discount();
										$aswc_wgm_discount_tax = $item->get_discount_tax();
										$amount                = aswc_get_meta_data( $coupon_id, 'coupon_amount', true );
										$aswc_common_fun       = new Woocommerce_Gift_Cards_Common_Function();
										$total_discount        = $aswc_common_fun->aswc_wgm_calculate_coupon_discount( $aswc_wgm_discount, $aswc_wgm_discount_tax );
										$total_discount        = $total_discount / $rate;

										// For currency switchers.
										if ( aswc_check_valid_subscription( $subscription_id ) ) {
											if ( function_exists( 'aswc_mmcsfw_admin_fetch_currency_rates_to_base_currency' ) ) {

												$total_discount = aswc_mmcsfw_admin_fetch_currency_rates_to_base_currency( $order_currency, $total_discount );
											}
										}
										if ( $amount < $total_discount ) {
											$remaining_amount = 0;
										} else {
											$remaining_amount = $amount - $total_discount;
											$remaining_amount = round( $remaining_amount, 2 );
										}
										aswc_update_order_meta( $coupon_id, 'coupon_amount', $remaining_amount );
										aswc_update_order_meta( $order_id, 'aswc_gc_coupon_updated', 'yes' );
										do_action( 'aswc_wgm_send_mail_remaining_amount', $coupon_id, $remaining_amount );
										do_action( 'aswc_wgm_coupon_reporting_with_order', $coupon_id, $item, $total_discount, $remaining_amount );
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Add subscription type coupon for currency switchers.
	 *
	 * @name aswc_currency_switcher_set_supported_coupon_type
	 * @param int    $discount discount.
	 * @param object $coupon coupon.
	 * @since 1.0.0
	 */
	public function aswc_currency_switcher_set_supported_coupon_type( $discount, $coupon ) {

		if ( get_option( 'mmcsfw_radio_switch_demo' ) !== 'on' ) {
			return $discount;
		}

		$aswc_discount_types = array(
			'initial_fee_discount',
			'initial_fee_percent_discount',
			'recurring_product_discount',
			'recurring_product_percent_discount',
		);
		if ( in_array( $coupon->get_discount_type(), $aswc_discount_types ) ) {
			$default_price = '';
			if ( WC()->session->__isset( 's_selected_currency' ) ) {
				$default_price = WC()->session->get( 's_selected_currency' );
			}
			if ( ! empty( $default_price ) ) {
				$mcs_price = get_option( 'aswc_mmcsfw_text_rate_' . $default_price );

				$decimal = get_option( 'aswc_mmcsfw_decimial_' . $default_price );
				$cents   = get_option( 'aswc_mmcsfw_cents_' . $default_price );
				if ( empty( $decimal ) ) {
					$decimal = 0;
				}
				if ( 'hide' === $cents ) {
					$decimal = 0;
				}
				if ( 0 === $decimal ) {
					$discount = floatval( $discount * round( $mcs_price, 2 ) );
				} else {
					$discount = floatval( $discount * round( $mcs_price, $decimal ) );
				}
				return $discount;
			}
		}
		return $discount;
	}

	/**
	 * Check Subscription type products.
	 *
	 * @name aswc_is_variable_subscription_product_type
	 * @param int    $aswc_is_subscription aswc_is_subscription.
	 * @param object $product product.
	 * @since 1.0.0
	 */
	public function aswc_is_variable_subscription_product_type( $aswc_is_subscription, $product ) {
		if ( is_object( $product ) ) {
			$product_id                = $product->get_id();
			$aswc_subscription_product = aswc_get_meta_data( $product_id, 'aswc_variable_product', true );
			if ( 'yes' === $aswc_subscription_product ) {
				$aswc_is_subscription = true;
			}
		}
		return apply_filters( 'aswc_subscription_product_type', $aswc_is_subscription, $product );
	}
	/**
	 * Set/unset onetime subscription product
	 *
	 * @return void
	 */
	public function aswc_onetime_purchase_callback() {
		check_ajax_referer( 'aswc_onetime_nonce', 'security' );
		$product_id   = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$product_type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$check_enable = isset( $_POST['checked'] ) ? sanitize_text_field( wp_unslash( $_POST['checked'] ) ) : '';

		if ( isset( WC()->session ) && WC()->session->has_session() ) {

			if ( 'true' === $check_enable && 'one_time' === $product_type ) {
				WC()->session->set( 'aswc_onetime_subscription_' . $product_id, 'on' );
			} else {
				WC()->session->set( 'aswc_onetime_subscription_' . $product_id, '' );
			}
		}
		wp_die();
	}

		/**
		 * Function to add bundle in subscription and renewal order.
		 *
		 * @param array $new_order_id as new order id.
		 * @param array $aswc_old_id as old order id.
		 * @param array $product as product.
		 * @return void
		 */
	public function aswc_subscription_bundle_addition_callback( $new_order_id, $aswc_old_id, $product ) {

		$order = wc_get_order( $aswc_old_id );
		if ( empty( $order ) || empty( $product ) ) {
			return;
		}

		$order_items  = $order->get_items();
		$temp         = 0;
		$product_type = $product->get_type();

		foreach ( $order_items as $items_key => $items_value ) {
			if ( 0 !== $temp && 'bundle' === $product_type ) {
				$order_item_id = wc_add_order_item(
					$new_order_id,
					array(
						'order_item_name' => $items_value['name'], // may differ from the product name.
						'order_item_type' => 'line_item', // product.
					)
				);
				if ( $order_item_id ) {
					// provide its meta information.
					wc_add_order_item_meta( $order_item_id, '_qty', $items_value['qty'], true ); // quantity.
					wc_add_order_item_meta( $order_item_id, '_product_id', $items_value['product_id'], true ); // ID of the product.
				}
			}
			++$temp;
		}
	}

	/**
	 * Function To save product for renewal order based on manual subscription.
	 *
	 * @param int $renewal_order_id as renewal order id.
	 * @param int $subscription_id as subscription order id.
	 * @return void
	 */
	public function aswc_add_new_product_for_manual_subscription_callback( $renewal_order_id, $subscription_id ) {

		$payment_type = aswc_get_meta_data( $subscription_id, 'aswc_payment_type', true );
		if ( 'aswc_manual_method' === $payment_type ) {

			if ( aswc_loader_is_hpos_enabled() ) {
				$order = new ASWC_Subscription( $subscription_id );
			} else {
				$order = wc_get_order( $subscription_id );
			}
			$renewal_order = wc_get_order( $renewal_order_id );
			$order_items   = $order->get_items();

			foreach ( $order_items as $items_key => $items_value ) {

				$product   = wc_get_product( $items_value['product_id'] );
				$quantity  = $items_value['qty'];
				$aswc_args = array(
					'variation' => array(),
					'totals'    => array(
						'subtotal'     => $items_value['subtotal'],
						'subtotal_tax' => $items_value['subtotal_tax'],
						'total'        => $items_value['total'],
						'tax'          => $items_value['line_tax'],
						'tax_data'     => maybe_unserialize( $items_value['line_tax_data'] ),
					),
				);
				$renewal_order->add_product( $product, $quantity, $aswc_args );
			}
		}
	}


	/**
	 * This function is used create variable susbcription field.
	 *
	 * @name aswc_renewal_order_apply_coupon
	 * @param object $aswc_renewal_order aswc_renewal_order.
	 * @param int    $aswc_subscription_id aswc_subscription_id.
	 * @since 1.0.0
	 */
	public function aswc_renewal_order_apply_coupon( $aswc_renewal_order, $aswc_subscription_id ) {

		if ( isset( $aswc_renewal_order ) && ! empty( $aswc_renewal_order ) ) {
			$order_id                       = $aswc_renewal_order->get_id();
						$is_renewal_success = aswc_get_meta_data( $order_id, '_aswc_is_renewal_success', true );
			Subscriptions_For_Woocommerce_Log::log( 'Wps Coupon log: ' . wc_print_r( 'Check1', true ) );
			if ( 'yes' === $is_renewal_success ) {
				return;
			}
			Subscriptions_For_Woocommerce_Log::log( 'Wps Coupon log: ' . wc_print_r( 'Check2', true ) );

			$recurring_product_discount         = aswc_get_meta_data( $aswc_subscription_id, 'recurring_product_discount', true );
			$recurring_product_percent_discount = aswc_get_meta_data( $aswc_subscription_id, 'recurring_product_percent_discount', true );
			// Apply giftcard coupon.
			$aswc_wgm_giftcard_coupon = aswc_get_meta_data( $aswc_subscription_id, 'aswc_wgm_giftcard_coupon', true );

			Subscriptions_For_Woocommerce_Log::log( 'Wps Coupon log: ' . wc_print_r( 'Check3', true ) );
			Subscriptions_For_Woocommerce_Log::log( 'Wps Coupon log: ' . wc_print_r( 'Check4', true ) );

			if ( isset( $recurring_product_discount ) && ! empty( $recurring_product_discount ) ) {
				Subscriptions_For_Woocommerce_Log::log( 'Wps Coupon log: ' . wc_print_r( 'Check5', true ) );
				$result = $aswc_renewal_order->apply_coupon( $recurring_product_discount );
				if ( ! is_wp_error( $result ) ) {
					$aswc_renewal_order->get_data_store()->set_recorded_coupon_usage_counts( $aswc_renewal_order, true );
				}
			} elseif ( isset( $recurring_product_percent_discount ) && ! empty( $recurring_product_percent_discount ) ) {
				Subscriptions_For_Woocommerce_Log::log( 'Wps Coupon log: ' . wc_print_r( 'Check6', true ) );
				$result = $aswc_renewal_order->apply_coupon( $recurring_product_percent_discount );
				if ( ! is_wp_error( $result ) ) {
					$aswc_renewal_order->get_data_store()->set_recorded_coupon_usage_counts( $aswc_renewal_order, true );

				}
			} elseif ( isset( $aswc_wgm_giftcard_coupon ) && ! empty( $aswc_wgm_giftcard_coupon ) ) {
				Subscriptions_For_Woocommerce_Log::log( 'Wps Coupon log: ' . wc_print_r( 'Check7', true ) );
				if ( aswc_get_subscription_coupon_enable_for_gc() ) {
					$the_coupon = new WC_Coupon( $aswc_wgm_giftcard_coupon );
					$coupon_id  = $the_coupon->get_id();
					if ( isset( $coupon_id ) ) {
						$amount = aswc_get_meta_data( $coupon_id, 'coupon_amount', true );
						if ( 0 < $amount ) {
							$result = $aswc_renewal_order->apply_coupon( $aswc_wgm_giftcard_coupon );
							if ( ! is_wp_error( $result ) ) {
								$aswc_renewal_order->get_data_store()->set_recorded_coupon_usage_counts( $aswc_renewal_order, true );

							}
						}
					}
				}
			}
						aswc_update_order_meta( $order_id, '_aswc_is_renewal_success', 'yes' );

		}
	}

	/**
	 * This function is used cancel failed subscription.
	 *
	 * @name aswc_cancel_failed_susbcription_callback
	 * @param bool $result result.
	 * @param int  $order_id order_id.
	 * @param bool $subscription_id subscription_id.
	 * @since 1.0.0
	 */
	public function aswc_cancel_failed_susbcription_callback( $result, $order_id, $subscription_id ) {
		if ( ! $result ) {

			$aswc_failed_attemp = aswc_get_meta_data( $subscription_id, 'aswc_failed_attemp_for_subscription', true );
			if ( empty( $aswc_failed_attemp ) ) {

				aswc_update_order_meta( $subscription_id, 'aswc_failed_attemp_for_subscription', 1 );
			} else {
				$aswc_failed_attemp = ++$aswc_failed_attemp;

				aswc_update_order_meta( $subscription_id, 'aswc_failed_attemp_for_subscription', $aswc_failed_attemp );
			}

			$aswc_failed_order = aswc_get_meta_data( $subscription_id, 'aswc_failed_order_for_subscription', true );
			if ( empty( $aswc_failed_order ) ) {
				$aswc_failed_order = array( $order_id );
				aswc_update_order_meta( $subscription_id, 'aswc_failed_order_for_subscription', $aswc_failed_order );
			} else {
				$aswc_failed_order[] = $order_id;
				aswc_update_order_meta( $subscription_id, 'aswc_failed_order_for_subscription', $aswc_failed_order );
			}
			$aswc_failed_attemp       = aswc_get_meta_data( $subscription_id, 'aswc_failed_attemp_for_subscription', true );
			$aswc_cancel_subscription = aswc_after_no_failed_attempt_cancel();

			if ( $aswc_failed_attemp >= $aswc_cancel_subscription ) {
				aswc_update_order_meta( $subscription_id, 'aswc_subscription_status', 'cancelled' );
			}
		}
	}

	/**
	 * Creating subscription based on upselling products
	 *
	 * @param object  $order .
	 * @param array() $product_data .
	 * @param object  $child_order .
	 */
	public function cartflow_subscription_creation_while_upselling( $order, $product_data, $child_order ) {
		foreach ( $child_order->get_items() as $item_id => $item ) {
			$product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();

			if ( function_exists( 'aswc_check_product_is_subscription' ) && ! aswc_check_product_is_subscription( wc_get_product( $product_id ) ) ) {
				continue;
			}

			$product         = $item->get_product();
			$product_name    = $item->get_name();
			$quantity        = $item->get_quantity();
			$subtotal        = $item->get_subtotal();
			$total           = $item->get_total();
			$subtotal_tax    = $item->get_subtotal_tax();
			$tax             = $item->get_total_tax();
			$parent_order_id = $order->get_id();
			$current_date    = current_time( 'timestamp' );

			$aswc_args = array(
				'aswc_parent_order'   => $parent_order_id,
				'aswc_customer_id'    => $order->get_user_id(),
				'aswc_schedule_start' => $current_date,
				'product_id'          => $product_id,
				'product_name'        => $product_name,
				'product_qty'         => $quantity,
			);

			$aswc_recurring_data = array();

			$aswc_subscription_number   = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );
			$aswc_subscription_interval = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );

			$aswc_recurring_data['aswc_subscription_number']   = $aswc_subscription_number;
			$aswc_recurring_data['aswc_subscription_interval'] = $aswc_subscription_interval;
			$aswc_subscription_expiry_number                   = aswc_get_meta_data( $product_id, 'aswc_subscription_expiry_number', true );

			if ( isset( $aswc_subscription_expiry_number ) && ! empty( $aswc_subscription_expiry_number ) ) {
				$aswc_recurring_data['aswc_subscription_expiry_number'] = $aswc_subscription_expiry_number;
			}

			$aswc_subscription_expiry_interval = aswc_get_meta_data( $product_id, 'aswc_subscription_expiry_interval', true );

			if ( isset( $aswc_subscription_expiry_interval ) && ! empty( $aswc_subscription_expiry_interval ) ) {
				$aswc_recurring_data['aswc_subscription_expiry_interval'] = $aswc_subscription_expiry_interval;
			}
			$aswc_subscription_initial_signup_price = aswc_get_meta_data( $product_id, 'aswc_subscription_initial_signup_price', true );

			if ( isset( $aswc_subscription_expiry_interval ) && ! empty( $aswc_subscription_expiry_interval ) ) {
				$aswc_recurring_data['aswc_subscription_initial_signup_price'] = $aswc_subscription_initial_signup_price;
			}

			$aswc_subscription_free_trial_number = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_number', true );

			if ( isset( $aswc_subscription_free_trial_number ) && ! empty( $aswc_subscription_free_trial_number ) ) {
				$aswc_recurring_data['aswc_subscription_free_trial_number'] = $aswc_subscription_free_trial_number;
			}
			$aswc_subscription_free_trial_interval = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_interval', true );
			if ( isset( $aswc_subscription_free_trial_interval ) && ! empty( $aswc_subscription_free_trial_interval ) ) {
				$aswc_recurring_data['aswc_subscription_free_trial_interval'] = $aswc_subscription_free_trial_interval;
			}
			$aswc_recurring_data = apply_filters( 'aswc_recurring_data', $aswc_recurring_data, $product_id );

			$show_price = $total + $tax;

			$aswc_recurring_data['aswc_recurring_total']      = $show_price;
			$aswc_recurring_data['aswc_show_recurring_total'] = $show_price;
			$aswc_recurring_data['product_id']                = $product_id;
			$aswc_recurring_data['product_name']              = $product_name;
			$aswc_recurring_data['product_qty']               = $quantity;

			$aswc_recurring_data['line_tax_data']     = array(
				'subtotal' => array( $subtotal_tax ),
				'total'    => array( $tax ),
			);
			$aswc_recurring_data['line_subtotal']     = $subtotal;
			$aswc_recurring_data['line_subtotal_tax'] = $subtotal_tax;
			$aswc_recurring_data['line_total']        = $total;
			$aswc_recurring_data['line_tax']          = $tax;

			$aswc_recurring_data = apply_filters( 'aswc_cart_data_for_susbcription', $aswc_recurring_data, $cart_item );

			if ( apply_filters( 'aswc_is_upgrade_downgrade_order', false, $aswc_recurring_data, $order, $posted_data, $cart_item ) ) {
				return;
			}
			// Subscription creation code start.

			if ( ! empty( $order ) ) {
				$order_id     = $order->get_id();
				$current_date = current_time( 'timestamp' );

				$aswc_default_args = array(
					'aswc_parent_order'   => $order_id,
					'aswc_customer_id'    => $order->get_user_id(),
					'aswc_schedule_start' => $current_date,
				);

				$aswc_args                             = wp_parse_args( $aswc_recurring_data, $aswc_default_args );
				$aswc_args['aswc_order_currency']      = $order->get_currency();
				$aswc_args['aswc_subscription_status'] = 'pending';

				$aswc_args = apply_filters( 'aswc_new_subscriptions_data', $aswc_args );
				// translators: post title date parsed by strftime.
								$post_title_date     = aswc_date( _x( '%1$b %2$d, %Y @ %I:%M %p', 'subscription post title. "Subscriptions order - <this>"', 'advanced-subscriptions-for-woocommerce' ), current_time( 'timestamp' ) );
				$aswc_subscription_data              = array();
				$aswc_subscription_data['post_type'] = 'aswc_subscriptions';

				$aswc_subscription_data['post_status']                 = 'wc-pending';
								$aswc_subscription_data['post_author'] = $order->get_user_id();
				$aswc_subscription_data['post_parent']                 = $order_id;
								/* translators: %s: post title date */
								$aswc_subscription_data['post_title'] = sprintf( _x( 'AWC Subscriptions &ndash; %s', 'Subscription post title', 'advanced-subscriptions-for-woocommerce' ), $post_title_date );
				$created_date                            = $order->get_date_created();
				$aswc_subscription_data['post_date']     = $created_date->date( 'Y-m-d H:i:s' );
				$aswc_subscription_data['post_date_gmt'] = get_gmt_from_date( $aswc_subscription_data['post_date'] );

				if ( aswc_loader_is_hpos_enabled() ) {

					$subscription_order = aswc_create_subscription();
					$subscription_id    = $subscription_order->get_id();

					$subscription_order->set_customer_id( $order->get_user_id() );

					$new_order = new ASWC_Subscription( $subscription_id );
					$new_order->update_status( 'pending' );
				} else {
					$subscription_id = wp_insert_post( $aswc_subscription_data, true );
					$new_order       = wc_get_order( $subscription_id );
					$new_order->set_customer_id( $order->get_user_id() );
				}
				if ( ! $subscription_id ) {
					return;
				}

				aswc_update_order_meta( $order_id, 'aswc_subscription', $subscription_id );
				aswc_update_order_meta( $subscription_id, 'aswc_susbcription_trial_end', 0 );
				aswc_update_order_meta( $subscription_id, 'aswc_susbcription_end', 0 );
				aswc_update_order_meta( $subscription_id, 'aswc_next_payment_date', 0 );
				aswc_update_order_meta( $subscription_id, '_order_key', wc_generate_order_key() );

				$_product = wc_get_product( $product_id );

				$billing_details  = $order->get_address( 'billing' );
				$shipping_details = $order->get_address( 'shipping' );

				$new_order->set_address( $billing_details, 'billing' );
				$new_order->set_address( $shipping_details, 'shipping' );

				$new_order->set_payment_method( $order->get_payment_method() );
				$new_order->set_payment_method_title( $order->get_payment_method_title() );

				$new_order->set_currency( $order->get_currency() );

				$line_subtotal   = $aswc_args['line_subtotal'];
				$line_total      = $aswc_args['line_total'];
				$total_taxes     = $aswc_args['line_tax'];
				$substotal_taxes = $aswc_args['line_subtotal_tax'];

				$aswc_pro_args = array(
					'variation' => array(),
					'totals'    => array(
						'subtotal'     => $line_subtotal,
						'subtotal_tax' => $substotal_taxes,
						'total'        => $line_total,
						'tax'          => $total_taxes,
						'tax_data'     => array(
							'subtotal' => array( $substotal_taxes ),
							'total'    => array( $total_taxes ),
						),
					),
				);

				$aswc_pro_args = apply_filters( 'aswc_product_args_for_order', $aswc_pro_args );

				$aswc_args = apply_filters( 'aswc_product_args_for_renewal_order_propate_amount', $aswc_args, $cart_item );

				$item_id = $new_order->add_product(
					$_product,
					$aswc_args['product_qty'],
					$aswc_pro_args
				);
				$new_order->update_taxes();
				$new_order->calculate_totals();
				$new_order->save();

				do_action( 'aswc_subscription_bundle_addition', $subscription_id, $order_id, $_product );

				// After susbcription order created.
				do_action( 'aswc_subscription_order', $new_order, $order_id );

				// new subscription meta from the version  1.5.8.
				aswc_update_order_meta( $subscription_id, 'aswc_new_sub', 'yes' );

				aswc_update_meta_key_for_susbcription( $subscription_id, $aswc_args );
				// After susbcription order created.
				do_action( 'aswc_after_created_subscription', $subscription_id, $order_id );
				// After susbcription created.

				$aswc_has_susbcription = aswc_get_meta_data( $order_id, 'aswc_order_has_subscription', true );
				if ( 'yes' !== $aswc_has_susbcription ) {
					aswc_update_order_meta( $order_id, 'aswc_order_has_subscription', 'yes' );
				}
			}
		}
	}
}
