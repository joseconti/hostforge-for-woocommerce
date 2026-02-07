<?php
/**
 * Exit if accessed directly
 *
 * @since 1.0.0
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! function_exists( 'aswc_loader_is_hpos_enabled' ) ) {

        /**
         * Determine whether WooCommerce HPOS support is available and enabled.
         *
         * @since 1.0.0
         *
         * @return bool
         */
        function aswc_loader_is_hpos_enabled() {
                if ( ! class_exists( 'Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
                        return false;
                }

                return OrderUtil::custom_orders_table_usage_is_enabled();
        }
}

if ( ! function_exists( 'aswc_get_free_trial_period_html_for_variable' ) ) {

	/**
	 * This function is used to show free trial period on subscription product page.
	 *
	 * @name aswc_get_free_trial_period_html_for_variable
	 * @param int    $product_id Product ID.
	 * @param string $price Product Price.
	 * @since 1.0.0
	 */
	function aswc_get_free_trial_period_html_for_variable( $product_id, $price ) {

		$aswc_subscription_free_trial_number   = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_number', true );
		$aswc_subscription_free_trial_interval = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_interval', true );
		if ( isset( $aswc_subscription_free_trial_number ) && ! empty( $aswc_subscription_free_trial_number ) ) {
			$aswc_price_html = aswc_get_time_interval( $aswc_subscription_free_trial_number, $aswc_subscription_free_trial_interval );
			/* translators: %s: search term */
			$price .= '<span class="aswc_free_trial">' . sprintf( esc_html__( ' and %s  free trial', 'advanced-subscriptions-for-woocommerce' ), $aswc_price_html ) . '</span>';

		}
		return $price;
	}
}

if ( ! function_exists( 'aswc_get_initial_signup_fee_html_for_variable' ) ) {
	/**
	 * This function is used to show initial signup fee on subscription product page.
	 *
	 * @name aswc_get_initial_signup_fee_html_for_variable
	 * @param int    $product_id Product ID.
	 * @param string $price Product Price.
	 * @since 1.0.0
	 */
	function aswc_get_initial_signup_fee_html_for_variable( $product_id, $price ) {
		$aswc_subscription_initial_signup_price = aswc_get_meta_data( $product_id, 'aswc_subscription_initial_signup_price', true );

		if ( isset( $aswc_subscription_initial_signup_price ) && ! empty( $aswc_subscription_initial_signup_price ) ) {
			/* translators: %s: search term */

			$price .= '<span class="aswc_signup_fee">' . sprintf( esc_html__( ' and %s  Sign up fee', 'advanced-subscriptions-for-woocommerce' ), wc_price( $aswc_subscription_initial_signup_price ) ) . '</span>';
		}
		return $price;
	}
}
if ( ! function_exists( 'aswc_check_variable_product_is_subscription' ) ) {
	/**
	 * This function is used to check susbcripton product.
	 *
	 * @name aswc_check_variable_product_is_subscription
	 * @param object $product product.
	 * @since 1.0.0
	 */
	function aswc_check_variable_product_is_subscription( $product ) {

		$aswc_is_subscription = false;
		if ( is_object( $product ) ) {
			$product_id                = $product->get_id();
			$aswc_subscription_product = aswc_get_meta_data( $product_id, 'aswc_variable_product', true );
			if ( 'yes' === $aswc_subscription_product ) {
				$aswc_is_subscription = true;
			}
		}

		return $aswc_is_subscription;
	}
}
if ( ! function_exists( 'aswc_get_valid_subscription_expiry' ) ) {
	/**
	 * This function is used to check susbcripton product.
	 *
	 * @name aswc_get_valid_subscription_expiry
	 * @param int    $aswc_expiry_number aswc_expiry_number.
	 * @param string $aswc_expiry_interval aswc_expiry_interval.
	 * @since 1.0.0
	 */
	function aswc_get_valid_subscription_expiry( $aswc_expiry_number, $aswc_expiry_interval ) {

		if ( isset( $aswc_expiry_number ) && ! empty( $aswc_expiry_number ) ) {
			if ( 'day' == $aswc_expiry_interval ) {
				if ( $aswc_expiry_number > 90 ) {
					$aswc_expiry_number = 90;
				}
			} elseif ( 'week' == $aswc_expiry_interval ) {
				if ( $aswc_expiry_number > 52 ) {
					$aswc_expiry_number = 52;
				}
			} elseif ( 'month' == $aswc_expiry_interval ) {
				if ( $aswc_expiry_number > 24 ) {
					$aswc_expiry_number = 24;
				}
			} elseif ( 'year' == $aswc_expiry_interval ) {
				if ( $aswc_expiry_number > 5 ) {
					$aswc_expiry_number = 5;
				}
			}
		}

		return $aswc_expiry_number;
	}
}

if ( ! function_exists( 'aswc_check_allow_expiry_by_customer' ) ) {
	/**
	 * This function is used to check allow subscription expiry enable.
	 *
	 * @name aswc_check_allow_expiry_by_customer
	 * @since 1.0.0
	 */
	function aswc_check_allow_expiry_by_customer() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_allow_subscription_expiry_customer', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}
if ( ! function_exists( 'aswc_allow_start_date_subscription' ) ) {
	/**
	 * This function is used to check allow subscription expiry enable.
	 *
	 * @name aswc_allow_start_date_subscription
	 * @since 1.0.0
	 */
	function aswc_allow_start_date_subscription() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_allow_start_date_subscription', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( 'aswc_allow_start_date_subscription ' . $aswc_enable );
		}
		return $is_enable;
	}
}


if ( ! function_exists( 'aswc_enable_automatic_retry_failed_attempts' ) ) {
	/**
	 * This function is used to check allow subscription expiry enable.
	 *
	 * @name aswc_enable_automatic_retry_failed_attempts
	 * @since 1.0.0
	 */
	function aswc_enable_automatic_retry_failed_attempts() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_enable_automatic_retry_failed_attempts', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'aswc_after_no_failed_attempt_cancel' ) ) {
	/**
	 * This function is used to get no of failed attempt cancel.
	 *
	 * @name aswc_after_no_failed_attempt_cancel
	 * @since 1.0.0
	 */
	function aswc_after_no_failed_attempt_cancel() {

		$aswc_enable = get_option( 'aswc_after_no_failed_attempt_cancel', '3' );

		return $aswc_enable;
	}
}

if ( ! function_exists( 'aswc_enable_pause_susbcription_by_customer' ) ) {
	/**
	 * This function is used to check enable pause subcription.
	 *
	 * @name aswc_enable_pause_susbcription_by_customer
	 * @since 1.0.0
	 */
	function aswc_enable_pause_susbcription_by_customer() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_enable_pause_susbcription_by_customer', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'aswc_start_pause_susbcription_by_customer' ) ) {
	/**
	 * This function is used to check enable start pause subcription.
	 *
	 * @name aswc_start_pause_susbcription_by_customer
	 * @since 1.0.0
	 */
	function aswc_start_pause_susbcription_by_customer() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_start_pause_susbcription_by_customer', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'aswc_start_susbcription_from_certain_date_of_month' ) ) {
	/**
	 * This function is used to check enable start subcription from certain month.
	 *
	 * @name aswc_start_susbcription_from_certain_date_of_month
	 * @since 1.0.0
	 */
	function aswc_start_susbcription_from_certain_date_of_month() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_start_susbcription_from_certain_date_of_month', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'aswc_enbale_accept_manual_payment' ) ) {
	/**
	 * This function is used to check enable accept manual payment.
	 *
	 * @name aswc_enbale_accept_manual_payment
	 * @since 1.0.0
	 */
	function aswc_enbale_accept_manual_payment() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_enbale_accept_manual_payment', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'aswc_get_subscription_discount_type' ) ) {
	/**
	 * This function is used to check enable accept manual payment.
	 *
	 * @name aswc_get_subscription_discount_type
	 * @since 1.0.0
	 */
	function aswc_get_subscription_discount_type() {
		$coupon_type = array(
			'recurring_product_discount'         => 1,
			'recurring_product_percent_discount' => 1,
		);
		return $coupon_type;
	}
}
if ( ! function_exists( 'aswc_get_subscription_signup_discount_type' ) ) {
	/**
	 * This function is used to check enable accept manual payment.
	 *
	 * @name aswc_get_subscription_signup_discount_type
	 * @since 1.0.0
	 */
	function aswc_get_subscription_signup_discount_type() {
		$coupon_type = array(
			'initial_fee_discount'         => 1,
			'initial_fee_percent_discount' => 1,
		);
		return $coupon_type;
	}
}

if ( ! function_exists( 'aswc_get_signup_fee' ) ) {
	/**
	 * This function is used to check signup fee.
	 *
	 * @name aswc_get_signup_fee
	 * @param object $product product.
	 * @since 1.0.0
	 */
	function aswc_get_signup_fee( $product ) {
		$aswc_signup_fee = 0;
		if ( is_object( $product ) ) {
			$product_id          = $product->get_id();
			$aswc_get_signup_fee = aswc_get_meta_data( $product_id, 'aswc_subscription_initial_signup_price', true );
			if ( isset( $aswc_get_signup_fee ) && ! empty( $aswc_get_signup_fee ) ) {
				$aswc_signup_fee = $aswc_get_signup_fee;
			}
		}
		return $aswc_signup_fee;
	}
}
if ( ! function_exists( 'aswc_get_recurring_total' ) ) {
	/**
	 * This function is used to get recurring total.
	 *
	 * @name aswc_get_recurring_total
	 * @param object $product product.
	 * @since 1.0.0
	 */
	function aswc_get_recurring_total( $product ) {

		$price = 0;
		if ( $product->is_on_sale() ) {
			$price = $product->get_sale_price();
		} else {
			$price = $product->get_regular_price();
		}

		return $price;
	}
}

if ( ! function_exists( 'aswc_get_get_trial_period' ) ) {
	/**
	 * This function is used to get trial period.
	 *
	 * @name aswc_get_get_trial_period
	 * @param object $product product.
	 * @since 1.0.0
	 */
	function aswc_get_get_trial_period( $product ) {
		$trial_length = 0;

		$product_id             = $product->get_id();
		$aswc_free_trial_number = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_number', true );
		if ( isset( $aswc_free_trial_number ) && ! empty( $aswc_free_trial_number ) ) {
			$trial_length = $aswc_free_trial_number;
		}
		return $trial_length;
	}
}

if ( ! function_exists( 'aswc_check_is_cart_subscription' ) ) {
	/**
	 * This function is used to check product subscription.
	 *
	 * @name aswc_check_is_cart_subscription
	 * @since 1.0.0
	 */
	function aswc_check_is_cart_subscription() {
		$aswc_has_subscription = false;

		if ( ! empty( WC()->cart->cart_contents ) ) {
			foreach ( WC()->cart->cart_contents as $cart_item ) {
				if ( aswc_check_product_is_subscription( $cart_item['data'] ) ) {
					$aswc_has_subscription = true;
					break;
				}
			}
		}
		return $aswc_has_subscription;
	}
}

if ( ! function_exists( 'aswc_check_is_order_subscription' ) ) {
		/**
		 * This function is used to order has subscription.
		 *
		 * @name aswc_check_is_order_subscription
		 * @param object $order order.
		 * @since 1.0.0
		 */
	function aswc_check_is_order_subscription( $order ) {
			$aswc_has_subscription = false;
		if ( isset( $order ) && ! empty( $order ) ) {
			foreach ( $order->get_items() as $key => $order_item ) {
				$product_id = ( $order_item['variation_id'] ) ? $order_item['variation_id'] : $order_item['product_id'];
				$product    = wc_get_product( $product_id );
				if ( aswc_check_product_is_subscription( $product ) ) {
					$aswc_has_subscription = true;
				}
			}
		}
			return $aswc_has_subscription;
	}
}

if ( ! function_exists( 'aswc_check_is_renewal_order' ) ) {
	/**
	 * This function is used to check renewal order.
	 *
	 * @name aswc_check_is_renewal_order
	 * @param object $order order.
	 * @since 1.0.0
	 */
	function aswc_check_is_renewal_order( $order ) {
		$aswc_is_renewal_order = false;
		if ( isset( $order ) && ! empty( $order ) ) {
			$order_id        = $order->get_id();
			$aswc_is_renewal = aswc_get_meta_data( $order_id, 'aswc_renewal_order', true );
			if ( 'yes' == $aswc_is_renewal ) {
				$aswc_is_renewal_order = true;
			}
		}
		return $aswc_is_renewal_order;
	}
}

if ( ! function_exists( 'aswc_no_of_time_retry_failed_order' ) ) {
	/**
	 * This function is used to update retry attempt.
	 *
	 * @name aswc_no_of_time_retry_failed_order
	 * @param int $order_id order_id.
	 * @since 1.0.0
	 */
	function aswc_no_of_time_retry_failed_order( $order_id ) {
		$aswc_retry_attempt = aswc_get_meta_data( $order_id, 'aswc_no_of_retry_attempt', true );
		if ( empty( $aswc_retry_attempt ) ) {
			aswc_update_order_meta( $order_id, 'aswc_no_of_retry_attempt', 1 );
		} else {
			$aswc_retry_attempt = ++$aswc_retry_attempt;
			aswc_update_order_meta( $order_id, 'aswc_no_of_retry_attempt', $aswc_retry_attempt );
		}
	}
}

if ( ! function_exists( 'aswc_send_email_for_pause_susbcription' ) ) {
	/**
	 * This function is used to send pause email.
	 *
	 * @name aswc_send_email_for_pause_susbcription
	 * @since 1.0.0
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 */
	function aswc_send_email_for_pause_susbcription( $aswc_subscription_id ) {

		if ( isset( $aswc_subscription_id ) && ! empty( $aswc_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "pause" notification.
			if ( isset( $mailer['aswc_pause_subscription'] ) ) {
				$mailer['aswc_pause_subscription']->trigger( $aswc_subscription_id );
			}
		}
	}
}

if ( ! function_exists( 'aswc_send_email_for_reactivate_susbcription' ) ) {
	/**
	 * This function is used to send reactivate email.
	 *
	 * @name aswc_send_email_for_reactivate_susbcription
	 * @since 1.0.0
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 */
	function aswc_send_email_for_reactivate_susbcription( $aswc_subscription_id ) {

		if ( isset( $aswc_subscription_id ) && ! empty( $aswc_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "reactivate" notification.
			if ( isset( $mailer['aswc_reactivate_subscription'] ) ) {
				$mailer['aswc_reactivate_subscription']->trigger( $aswc_subscription_id );
			}
		}
	}
}

if ( ! function_exists( 'aswc_get_the_csv_date_format' ) ) {

	/**
	 * This function is used to get date format.
	 *
	 * @name aswc_get_the_csv_date_format
	 * @since 1.0.0
	 * @param int $saved_date saved_date.
	 */
	function aswc_get_the_csv_date_format( $saved_date ) {
		$return_date = '---';
		if ( isset( $saved_date ) && ! empty( $saved_date ) ) {
			$return_date = date_i18n( 'Y-m-d', $saved_date );
		}
		return $return_date;
	}
}

if ( ! function_exists( 'aswc_support_manual_payment' ) ) {

	/**
	 * This function is used to get manual payment gateway.
	 *
	 * @name aswc_support_manual_payment
	 * @since 1.0.0
	 */
	function aswc_support_manual_payment() {
		$aswc_manual_payment_gateway = array(
			'bacs',
			'cheque',
			'cod',
		);
		return $aswc_manual_payment_gateway;
	}
}
if ( ! function_exists( 'aswc_check_upgrade_downgrade' ) ) {
	/**
	 * This function is used to check upgrade/downgrade enbale.
	 *
	 * @name aswc_check_upgrade_downgrade
	 * @since 1.0.0
	 */
	function aswc_check_upgrade_downgrade() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_enbale_downgrade_upgrade_subscription', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'aswc_get_upgrade_downgrade_text' ) ) {
	/**
	 * This function is used to get upgrade/downgrade button text.
	 *
	 * @name aswc_get_upgrade_downgrade_text
	 * @since 1.0.0
	 */
	function aswc_get_upgrade_downgrade_text() {

		$aswc_btn_text = get_option( 'aswc_upgrade_downgrade_btn_text', 'Upgrade and Downgrade' );

		return $aswc_btn_text;
	}
}

if ( ! function_exists( 'aswc_check_enable_singup_upgrade_downgrade' ) ) {
	/**
	 * This function is used to check upgrade/downgrade enbale.
	 *
	 * @name aswc_check_enable_singup_upgrade_downgrade
	 * @since 1.0.0
	 */
	function aswc_check_enable_singup_upgrade_downgrade() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_enable_signup_fee_downgrade_upgrade_subscription', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'aswc_check_enable_prorate_price_upgrade_downgrade' ) ) {
	/**
	 * This function is used to check upgrade/downgrade enbale.
	 *
	 * @name aswc_check_enable_prorate_price_upgrade_downgrade
	 * @since 1.0.0
	 */
	function aswc_check_enable_prorate_price_upgrade_downgrade() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_enable_prorate_on_price_downgrade_upgrade_subscription', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}


if ( ! function_exists( 'aswc_check_enable_plan_going_expire_email_notification' ) ) {
	/**
	 * This function is used to check plan going expire email notification enbale.
	 *
	 * @name aswc_check_enable_plan_going_expire_email_notification
	 * @since 1.0.0
	 */
	function aswc_check_enable_plan_going_expire_email_notification() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_enable_signup_fee_downgrade_upgrade_subscription', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'aswc_plan_going_to_expire_before_days' ) ) {
	/**
	 * This function is used to get upgrade/downgrade button text.
	 *
	 * @name aswc_plan_going_to_expire_before_days
	 * @since 1.0.0
	 */
	function aswc_plan_going_to_expire_before_days() {

		$aswc_no_of_days = get_option( 'aswc_plan_going_to_expire_before_days', '7' );

		return (int) $aswc_no_of_days;
	}
}

if ( ! function_exists( 'aswc_send_plan_going_to_expire_email' ) ) {
	/**
	 * This function is used to send expire email.
	 *
	 * @name aswc_send_plan_going_to_expire_email
	 * @since 1.0.0
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 */
	function aswc_send_plan_going_to_expire_email( $aswc_subscription_id ) {

		if ( isset( $aswc_subscription_id ) && ! empty( $aswc_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "plan expire" notification.
			if ( isset( $mailer['aswc_plan_going_expire'] ) ) {
				$mailer['aswc_plan_going_expire']->trigger( $aswc_subscription_id );
			}
		}
	}
}

if ( ! function_exists( 'aswc_proprate_price_calculate' ) ) {

	/**
	 * This function is used to calculate proprate price.
	 *
	 * @name aswc_proprate_price_calculate
	 * @since 1.0.0
	 * @param int     $aswc_subscription_id aswc_subscription_id.
	 * @param int     $product_id product_id.
	 * @param int     $new_price new_price.
	 * @param mixed   $cart_data cart data.
	 * @param boolean $set set.
	 * @return $new_price
	 */
	function aswc_proprate_price_calculate( $aswc_subscription_id, $product_id, $new_price, $cart_data, $set ) {
		$last_order_date_time = aswc_get_last_renewal_order_date( $aswc_subscription_id );

		if ( empty( $last_order_date_time ) ) {
			return $new_price;
		}
		$current_time = current_time( 'timestamp' );

		$aswc_no_of_days_paid = ceil( ( $current_time - $last_order_date_time ) / DAY_IN_SECONDS );

		$aswc_old_number          = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_number', true );
		$aswc_old_interval        = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_interval', true );
		$aswc_old_recurring_total = aswc_get_meta_data( $aswc_subscription_id, 'aswc_recurring_total', true );

		$aswc_new_interval = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );

		$aswc_new_subs_number = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );

		if ( 'day' == $aswc_new_interval ) {
			$new_per_day_price = $new_price / $aswc_new_subs_number;

		} elseif ( 'week' == $aswc_new_interval ) {
			$aswc_new_subs_number = $aswc_new_subs_number * 7;
			$new_per_day_price    = $new_price / $aswc_new_subs_number;
		} elseif ( 'month' == $aswc_new_interval ) {
			$aswc_new_subs_number = $aswc_new_subs_number * 30;
			$new_per_day_price    = $new_price / $aswc_new_subs_number;
		} elseif ( 'year' == $aswc_new_interval ) {
			$aswc_new_subs_number = $aswc_new_subs_number * 365;
			$new_per_day_price    = $new_price / $aswc_new_subs_number;
		}
		if ( 'day' == $aswc_old_interval ) {
			$per_day_price = ( $aswc_old_recurring_total / $aswc_old_number );

			$aswc_interval_left = $aswc_old_number - $aswc_no_of_days_paid;

			$aswc_price_paid = $per_day_price * $aswc_interval_left;

			$new_price = $new_price - $aswc_price_paid;

		} elseif ( 'week' == $aswc_old_interval ) {
			$aswc_old_sub_number = $aswc_old_number * 7;
			$per_day_price       = ( $aswc_old_recurring_total / $aswc_old_sub_number );
			$aswc_interval_left  = ( 7 * $aswc_old_number ) - $aswc_no_of_days_paid;

			$aswc_price_paid = $per_day_price * $aswc_interval_left;

			$new_price = $new_price - $aswc_price_paid;

		} elseif ( 'month' == $aswc_old_interval ) {

			$aswc_old_sub_number = $aswc_old_number * 30;
			$per_day_price       = ( $aswc_old_recurring_total / $aswc_old_sub_number );
			$aswc_interval_left  = ( 30 * $aswc_old_number ) - $aswc_no_of_days_paid;
			$aswc_price_paid     = $per_day_price * $aswc_interval_left;

			$new_price = $new_price - $aswc_price_paid;

		} elseif ( 'year' == $aswc_old_interval ) {
			$aswc_old_sub_number = $aswc_old_number * 365;
			$per_day_price       = ( $aswc_old_recurring_total / $aswc_old_sub_number );
			$aswc_interval_left  = ( 365 * $aswc_old_number ) - $aswc_no_of_days_paid;

			$aswc_price_paid = $per_day_price * $aswc_interval_left;

			$new_price = $new_price - $aswc_price_paid;
		}
		if ( get_option( 'aswc_enable_prorate_on_price_downgrade_upgrade_subscription', false ) ) {
			if ( 0 > $new_price ) {
				$set       = true;
				$new_price = abs( $new_price );
				wc_clear_notices();
				$aswc_wsf_manage_prorate_upgrade_downgrade = get_option( 'aswc_manage_prorate_amount', false );
				if ( $aswc_wsf_manage_prorate_upgrade_downgrade ) {

					if ( 'aswc_manage_prorate_next_payment_date' === $aswc_wsf_manage_prorate_upgrade_downgrade ) {
						$adjust_next_payment_date = ceil( $new_price / $new_per_day_price );

						$aswc_final_updated_next_payment_data = $adjust_next_payment_date + $aswc_new_subs_number;
						if ( 'day' === $aswc_new_interval ) {
							$aswc_final_show_msg_data = $aswc_final_updated_next_payment_data;
						}
						if ( 'week' === $aswc_new_interval ) {
							$aswc_final_show_msg_data = $aswc_final_updated_next_payment_data / 7;
						}
						if ( 'month' === $aswc_new_interval ) {
							$aswc_final_show_msg_data = $aswc_final_updated_next_payment_data / 30;

						}
						if ( 'year' === $aswc_new_interval ) {
							$aswc_final_show_msg_data = $aswc_final_updated_next_payment_data / 365;

						}
						aswc_update_order_meta( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_date', $aswc_final_updated_next_payment_data );

						/* translators: Placeholder 1: Message, placeholder 2: interval, Placeholder 3: Message, placeholder 4: amount */

						$downgrade_upgrade_notice = sprintf( __( 'Your next recurring payment will be taken after %1$s %2$s because your %3$s %4$s amount has been left from previous product', 'advanced-subscriptions-for-woocommerce' ), round( (float) $aswc_final_show_msg_data, 1 ), $aswc_new_interval, get_woocommerce_currency_symbol(), round( (float) $new_price, 1 ) );

						if ( $set ) {
							WC()->session->set( 'downgrade_upgrade_notice', $downgrade_upgrade_notice );
						}
					} elseif ( 'aswc_manage_prorate_using_wallet' === $aswc_wsf_manage_prorate_upgrade_downgrade ) {
						/* translators: Placeholder 1: currrency symbol, placeholder 2: amount */
						$downgrade_upgrade_notice = sprintf( __( 'Your left amount %1$s %2$s will be added to your wallet, You can check that from', 'advanced-subscriptions-for-woocommerce' ), get_woocommerce_currency_symbol(), round( (float) $new_price, 1 ) ) . ' <a href="' . get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) . '/aswc-wallet/" target="_blank" class="button alt" >' . __( 'Wallet', 'advanced-subscriptions-for-woocommerce' ) . '</a>';
						if ( $set ) {
							WC()->session->set( 'downgrade_upgrade_notice', $downgrade_upgrade_notice );
						}
						aswc_update_order_meta( $aswc_subscription_id, 'aswc_wsf_manage_prorate_negativ_amount_wallet', $new_price );

					}
				}

				$new_price = 0;
			}
		}

		return $new_price;
	}
}

if ( ! function_exists( 'aswc_email_subscriptions_details_recurring_reminder' ) ) {
	/**
	 * This function is used to create html for susbcription details.
	 *
	 * @name aswc_email_subscriptions_details
	 *
	 * @since 1.0.0
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 * @return void
	 */
	function aswc_email_subscriptions_details_recurring_reminder( $aswc_subscription_id ) {
		$aswc_text_align = is_rtl() ? 'right' : 'left';
		?>
		<div style="margin-bottom: 40px;">
			<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
				<thead>
					<tr>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $aswc_text_align ); ?>;"><?php esc_html_e( 'Product', 'advanced-subscriptions-for-woocommerce' ); ?></th>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $aswc_text_align ); ?>;"><?php esc_html_e( 'Quantity', 'advanced-subscriptions-for-woocommerce' ); ?></th>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $aswc_text_align ); ?>;"><?php esc_html_e( 'Price', 'advanced-subscriptions-for-woocommerce' ); ?></th>
						<th class="td" scope="col" style="text-align:<?php echo esc_attr( $aswc_text_align ); ?>;"><?php esc_html_e( 'Recurring Payment Date', 'advanced-subscriptions-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<?php
								$aswc_product_name = aswc_get_meta_data( $aswc_subscription_id, 'product_name', true );
								echo esc_html( $aswc_product_name );
							?>
							</td>
						<td>
						<?php
							$product_qty = aswc_get_meta_data( $aswc_subscription_id, 'product_qty', true );
							echo esc_html( $product_qty );
						?>
						</td>
						<td>
						<?php
							do_action( 'aswc_display_susbcription_recerring_total_account_page', $aswc_subscription_id );
						?>
						</td>
						<td>
							<?php
							$aswc_next_payment_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_next_payment_date', true );
														echo esc_html( aswc_date( 'Y-m-d', $aswc_next_payment_date ) );
							?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}
}

if ( ! function_exists( 'aswc_get_last_renewal_order_date' ) ) {
	/**
	 * This function is used to last order date.
	 *
	 * @name aswc_get_last_renewal_order_date
	 * @since 1.0.0
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 */
	function aswc_get_last_renewal_order_date( $aswc_subscription_id ) {

		$aswc_last_order_timestamp = 0;

		$aswc_last_order_id = aswc_get_meta_data( $aswc_subscription_id, 'aswc_last_renewal_order_id', true );

		$aswc_parent_order_id = aswc_get_meta_data( $aswc_subscription_id, 'aswc_parent_order', true );

		$aswc_start_time = aswc_get_meta_data( $aswc_subscription_id, 'aswc_schedule_start', true );

		if ( $aswc_last_order_id ) {
			$aswc_last_order = wc_get_order( $aswc_last_order_id );

			if ( $aswc_last_order instanceof \WC_Order ) {
				$aswc_order_paid_date = $aswc_last_order->get_date_paid();
				if ( $aswc_order_paid_date ) {
					$aswc_last_order_timestamp = $aswc_order_paid_date->getTimestamp();
				}
			}
		} elseif ( $aswc_parent_order_id ) {
			$aswc_parent_order = wc_get_order( $aswc_parent_order_id );

			if ( $aswc_parent_order instanceof \WC_Order ) {
				$aswc_order_paid_date = $aswc_parent_order->get_date_paid();
				if ( $aswc_order_paid_date ) {
					$aswc_last_order_timestamp = $aswc_order_paid_date->getTimestamp();
				}
			}
		} else {
			$aswc_last_order_timestamp = $aswc_start_time;
		}

		return $aswc_last_order_timestamp;
	}
}

if ( ! function_exists( 'aswc_subscription_week_period' ) ) {
	/**
	 * This function is used to get billing period.
	 *
	 * @name aswc_subscription_week_period
	 * @since 1.0.0
	 */
	function aswc_subscription_week_period() {
		$aswc_billing_array = array();

		$aswc_billing_array = array(
			'1' => __( 'Monday', 'advanced-subscriptions-for-woocommerce' ),
			'2' => __( 'Tuesday', 'advanced-subscriptions-for-woocommerce' ),
			'3' => __( 'Wednesday', 'advanced-subscriptions-for-woocommerce' ),
			'4' => __( 'Thursday', 'advanced-subscriptions-for-woocommerce' ),
			'5' => __( 'Friday', 'advanced-subscriptions-for-woocommerce' ),
			'6' => __( 'Saturday', 'advanced-subscriptions-for-woocommerce' ),
			'7' => __( 'Sunday', 'advanced-subscriptions-for-woocommerce' ),
		);

		return $aswc_billing_array;
	}
}
if ( ! function_exists( 'aswc_subscription_month_period' ) ) {
	/**
	 * This function is used to get billing period.
	 *
	 * @name aswc_subscription_month_period
	 * @since 1.0.0
	 */
	function aswc_subscription_month_period() {
		$aswc_billing_array = array();
		for ( $i = 1; $i <= 28; $i++ ) {
			/* translators: %s: search term */
			$aswc_billing_array[ $i ] = sprintf( __( 'Day %s', 'advanced-subscriptions-for-woocommerce' ), $i );
		}
			$aswc_billing_array['end'] = __( 'Last day of month', 'advanced-subscriptions-for-woocommerce' );
		return $aswc_billing_array;
	}
}

if ( ! function_exists( 'aswc_subscription_syn_year_period' ) ) {
	/**
	 * This function is used to get billing period.
	 *
	 * @name aswc_subscription_syn_year_period
	 * @since 1.0.0
	 */
	function aswc_subscription_syn_year_period() {
		$aswc_billing_array = array();

			$aswc_billing_array = array(
				'01' => __( 'January', 'advanced-subscriptions-for-woocommerce' ),
				'02' => __( 'February', 'advanced-subscriptions-for-woocommerce' ),
				'03' => __( 'March', 'advanced-subscriptions-for-woocommerce' ),
				'04' => __( 'April', 'advanced-subscriptions-for-woocommerce' ),
				'05' => __( 'May', 'advanced-subscriptions-for-woocommerce' ),
				'06' => __( 'June', 'advanced-subscriptions-for-woocommerce' ),
				'07' => __( 'July', 'advanced-subscriptions-for-woocommerce' ),
				'08' => __( 'August', 'advanced-subscriptions-for-woocommerce' ),
				'09' => __( 'September', 'advanced-subscriptions-for-woocommerce' ),
				'10' => __( 'October', 'advanced-subscriptions-for-woocommerce' ),
				'11' => __( 'November', 'advanced-subscriptions-for-woocommerce' ),
				'12' => __( 'December', 'advanced-subscriptions-for-woocommerce' ),
			);

			return $aswc_billing_array;
	}
}

if ( ! function_exists( 'aswc_subscription_syn_enable_per_product' ) ) {
	/**
	 * This function is used to get billing period.
	 *
	 * @name aswc_subscription_syn_enable_per_product.
	 * @param int $product_id product_id.
	 * @since 1.0.0
	 */
	function aswc_subscription_syn_enable_per_product( $product_id ) {
		$aswc_is_enable = false;
		if ( isset( $product_id ) && ! empty( $product_id ) ) {
			$aswc_check_enable = aswc_get_meta_data( $product_id, 'aswc_enbale_certain_month', true );
			$aswc_interval     = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );

			if ( 'yes' == $aswc_check_enable && 'day' != $aswc_interval ) {
				$aswc_is_enable = true;
			}
		}

		return $aswc_is_enable;
	}
}

if ( ! function_exists( 'aswc_get_sync_subscription_details' ) ) {
	/**
	 * This function is used to get billing period.
	 *
	 * @name aswc_get_sync_subscription_details.
	 * @param int    $product_id product_id.
	 * @param string $aswc_price_html aswc_price_html.
	 * @since 1.0.0
	 */
	function aswc_get_sync_subscription_details( $product_id, $aswc_price_html ) {

		$aswc_frequency = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );
		$aswc_number    = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );
		if ( 'month' == $aswc_frequency ) {
			$aswc_month_sync_value = aswc_get_meta_data( $product_id, 'aswc_month_sync', true );
			$aswc_month_sync_value = aswc_get_sync_month_text( $aswc_month_sync_value, $aswc_number );
			/* translators: %s: search term */
			$aswc_price_html = '<span class="aswc_interval">' . sprintf( esc_html__( ' payment on  %s month', 'advanced-subscriptions-for-woocommerce' ), $aswc_month_sync_value ) . '</span>';
		} elseif ( 'week' == $aswc_frequency ) {
			$aswc_week_sync_value = aswc_get_meta_data( $product_id, 'aswc_week_sync', true );
			$aswc_week_sync_value = aswc_get_sync_text_week( $aswc_week_sync_value, $aswc_number );

			/* translators: %s: search term */
			$aswc_price_html = '<span class="aswc_interval">' . sprintf( esc_html__( ' payment on %s ', 'advanced-subscriptions-for-woocommerce' ), $aswc_week_sync_value ) . '</span>';
		} elseif ( 'year' == $aswc_frequency ) {
			$aswc_year_sync_value = aswc_get_meta_data( $product_id, 'aswc_year_sync', true );
			$aswc_year_number     = aswc_get_meta_data( $product_id, 'aswc_year_number', true );
			$aswc_year_sync_value = aswc_get_sync_year_text( $aswc_year_sync_value, $aswc_number, $aswc_year_number );
			/* translators: %s: search term */
			$aswc_price_html = '<span class="aswc_interval">' . sprintf( esc_html__( ' payment on %s', 'advanced-subscriptions-for-woocommerce' ), $aswc_year_sync_value ) . '</span>';
		}

		return $aswc_price_html;
	}
}

if ( ! function_exists( 'aswc_get_sync_month_text' ) ) {
	/**
	 * This function is used to show month billing interval.
	 *
	 * @name aswc_get_sync_month_text
	 * @param int $aswc_sync_value aswc_sync_value.
	 * @param int $aswc_number aswc_number.
	 * @since 1.0.0
	 */
	function aswc_get_sync_month_text( $aswc_sync_value, $aswc_number ) {
		if ( 1 == $aswc_number ) {
			$aswc_number_text = __( 'every', 'advanced-subscriptions-for-woocommerce' );
		} else {
			/* translators: %s: search term */
			$aswc_number_text = sprintf( __( 'every %s', 'advanced-subscriptions-for-woocommerce' ), $aswc_number );
		}
		$aswc_sync_string = '';
		switch ( $aswc_sync_value ) {
			case 1:
				/* translators: %s: search term */
				$aswc_sync_string = sprintf( __( '%1$1sst of %2$2s', 'advanced-subscriptions-for-woocommerce' ), $aswc_sync_value, $aswc_number_text );
				break;
			case 2:
				/* translators: %s: search term */
				$aswc_sync_string = sprintf( __( '%1$snd of %2$2s', 'advanced-subscriptions-for-woocommerce' ), $aswc_sync_value, $aswc_number_text );
				break;
			case 3:
				/* translators: %s: search term */
				$aswc_sync_string = sprintf( __( '%1$srd of %2$2s', 'advanced-subscriptions-for-woocommerce' ), $aswc_sync_value, $aswc_number_text );
				break;
			case 'end':
				/* translators: %s: search term */
				$aswc_sync_string = sprintf( __( 'last day of %s', 'advanced-subscriptions-for-woocommerce' ), $aswc_number_text );
				break;
			default:
				/* translators: %s: search term */
				$aswc_sync_string = sprintf( __( '%1$sth of %2$2s', 'advanced-subscriptions-for-woocommerce' ), $aswc_sync_value, $aswc_number_text );
				break;
		}

		return $aswc_sync_string;
	}
}
if ( ! function_exists( 'aswc_get_sync_text_week' ) ) {
	/**
	 * This function is used to show week billing interval.
	 *
	 * @name aswc_get_sync_text_week
	 * @param int $aswc_sync_value aswc_sync_value.
	 * @param int $aswc_number aswc_number.
	 * @since 1.0.0
	 */
	function aswc_get_sync_text_week( $aswc_sync_value, $aswc_number ) {

		if ( 1 == $aswc_number ) {
			$aswc_number_text = __( 'every', 'advanced-subscriptions-for-woocommerce' );
		} else {
			/* translators: %s: number's of week */
			$aswc_number_text = sprintf( __( 'every %s week on', 'advanced-subscriptions-for-woocommerce' ), $aswc_number );
		}

		$aswc_sync_string = '';
		switch ( $aswc_sync_value ) {
			case 1:
				/* translators: %s: number's of week */
				$aswc_sync_string = sprintf( __( '%s Monday', 'advanced-subscriptions-for-woocommerce' ), $aswc_number_text );
				break;
			case 2:
				/* translators: %s: number's of week */
				$aswc_sync_string = sprintf( __( '%s Tuesday', 'advanced-subscriptions-for-woocommerce' ), $aswc_number_text );
				break;
			case 3:
				/* translators: %s: number's of week */
				$aswc_sync_string = sprintf( __( '%s Wednesday', 'advanced-subscriptions-for-woocommerce' ), $aswc_number_text );
				break;
			case 4:
				/* translators: %s: number's of week */
				$aswc_sync_string = sprintf( __( '%s Thursday', 'advanced-subscriptions-for-woocommerce' ), $aswc_number_text );
				break;
			case 5:
				/* translators: %s: number's of week */
				$aswc_sync_string = sprintf( __( '%s Friday', 'advanced-subscriptions-for-woocommerce' ), $aswc_number_text );
				break;
			case 6:
				/* translators: %s: number's of week */
				$aswc_sync_string = sprintf( __( '%s Saturday', 'advanced-subscriptions-for-woocommerce' ), $aswc_number_text );
				break;
			case 7:
				/* translators: %s: number's of week */
				$aswc_sync_string = sprintf( __( '%s Sunday', 'advanced-subscriptions-for-woocommerce' ), $aswc_number_text );
				break;
			default:
				break;
		}

		return $aswc_sync_string;
	}
}

if ( ! function_exists( 'aswc_get_sync_year_text' ) ) {
	/**
	 * This function is used to show year billing interval.
	 *
	 * @name aswc_get_sync_year_text
	 * @param int $aswc_sync_value aswc_sync_value.
	 * @param int $aswc_number aswc_number.
	 * @param int $aswc_year_number aswc_year_number.
	 * @since 1.0.0
	 */
	function aswc_get_sync_year_text( $aswc_sync_value, $aswc_number, $aswc_year_number ) {
		$aswc_selected_month = '';
		$aswc_sync_string    = '';
		$aswc_years          = aswc_subscription_syn_year_period();

		if ( ! empty( $aswc_years ) && is_array( $aswc_years ) ) {
			if ( array_key_exists( $aswc_sync_value, $aswc_years ) ) {
				$aswc_selected_month = $aswc_years[ $aswc_sync_value ];
			}
		}

		if ( 1 == $aswc_number ) {
			$aswc_number_text = __( 'every year', 'advanced-subscriptions-for-woocommerce' );
		} else {
			/* translators: %s: search term */
			$aswc_number_text = sprintf( __( 'every %s year', 'advanced-subscriptions-for-woocommerce' ), $aswc_number );
		}
		switch ( $aswc_year_number ) {
			case 1:
				/* translators: %s: search term */
				$aswc_sync_string = sprintf( __( '%1$1sst of %2$2s', 'advanced-subscriptions-for-woocommerce' ), $aswc_year_number, $aswc_number_text );
				break;
			case 2:
				/* translators: %s: search term */
				$aswc_sync_string = sprintf( __( '%1$snd of %2$2s', 'advanced-subscriptions-for-woocommerce' ), $aswc_year_number, $aswc_number_text );
				break;
			case 3:
				/* translators: %s: search term */
				$aswc_sync_string = sprintf( __( '%1$srd of %2$2s', 'advanced-subscriptions-for-woocommerce' ), $aswc_year_number, $aswc_number_text );
				break;
			default:
				/* translators: %s: search term */
				$aswc_sync_string = sprintf( __( '%1$sth of %2$2s', 'advanced-subscriptions-for-woocommerce' ), $aswc_year_number, $aswc_number_text );
				break;
		}

		/* translators: %s: search term */
		$aswc_sync_string = sprintf( __( '%1$1s %2$2s', 'advanced-subscriptions-for-woocommerce' ), $aswc_selected_month, $aswc_sync_string );

		return $aswc_sync_string;
	}
}
if ( ! function_exists( 'aswc_get_sync_start_payment_date' ) ) {
	/**
	 * This function is used to sync the start date.
	 *
	 * @param int $product_id product_id.
	 * @return int
	 */
	function aswc_get_sync_start_payment_date( $product_id ) {
		$aswc_start_date = aswc_get_meta_data( $product_id, 'aswc_subscription_start_date', true );
		if ( $aswc_start_date ) {
			$aswc_start_date = '<div class="aswc_start_date"><span>' . esc_html__( 'Your subscription will start on: ', 'advanced-subscriptions-for-woocommerce' ) . '</span>' . aswc_get_the_wordpress_date_format( strtotime( $aswc_start_date ) ) . '</div>';
			return $aswc_start_date;
		}
	}
}

if ( ! function_exists( 'aswc_get_sync_first_payment_date' ) ) {
	/**
	 * This function is used to get first payment date.
	 *
	 * @name aswc_get_sync_first_payment_date.
	 * @param int $product_id product_id.
	 * @since 1.0.0
	 */
	function aswc_get_sync_first_payment_date( $product_id ) {
		$aswc_first_payment_date = '';
		if ( ! aswc_subscription_syn_enable_per_product( $product_id ) ) {
			return $aswc_first_payment_date;
		}
		if ( isset( $product_id ) && ! empty( $product_id ) ) {
			$aswc_current_time = current_time( 'timestamp' );

			$aswc_frequency = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );
			$aswc_number    = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );

			$aswc_get_get_trial_period = aswc_get_trial_sync_time( $product_id, $aswc_current_time );
			if ( $aswc_get_get_trial_period > 0 ) {
				$aswc_current_time = $aswc_get_get_trial_period;
			}
			if ( 'week' == $aswc_frequency ) {
				$aswc_week_sync_value = aswc_get_meta_data( $product_id, 'aswc_week_sync', true );

				$aswc_week = aswc_subscription_week_period();

				if ( ! empty( $aswc_week ) && is_array( $aswc_week ) ) {
					if ( array_key_exists( $aswc_week_sync_value, $aswc_week ) ) {
						$aswc_selected_week = $aswc_week[ $aswc_week_sync_value ];
					}
				}

								$aswc_no_day = aswc_date( 'N', $aswc_current_time );

				$aswc_add_days = $aswc_week_sync_value < $aswc_no_day ? 0 : 7;
				$aswc_no_day   = $aswc_no_day + $aswc_add_days;

				$aswc_first_payment_date = aswc_get_the_next_year_sync_time( $aswc_selected_week, $aswc_current_time );

			} elseif ( 'month' == $aswc_frequency ) {
				$aswc_month_sync_value = aswc_get_meta_data( $product_id, 'aswc_month_sync', true );
				if ( 'end' == $aswc_month_sync_value ) {
										$aswc_month_sync_value = aswc_date( 't', $aswc_current_time );
				}

								$aswc_no_of_days     = aswc_date( 't', $aswc_current_time );
								$aswc_no_of_cur_date = aswc_date( 'j', $aswc_current_time );

				if ( $aswc_no_of_cur_date <= $aswc_month_sync_value ) {
					$aswc_day_diff = $aswc_no_of_days - $aswc_no_of_cur_date;
					$aswc_day_diff = $aswc_day_diff + $aswc_month_sync_value;

				} else {
					$aswc_day_diff = $aswc_no_of_days + $aswc_month_sync_value - $aswc_no_of_cur_date;
				}

				$aswc_first_payment_date = aswc_get_timestamp( $aswc_current_time, intval( $aswc_day_diff ) );
			} elseif ( 'year' == $aswc_frequency ) {

				$aswc_year_sync_value = aswc_get_meta_data( $product_id, 'aswc_year_sync', true );
				$aswc_year_number     = aswc_get_meta_data( $product_id, 'aswc_year_number', true );

								$aswc_curr_year = aswc_date( 'Y', $aswc_current_time );

								$aswc_curr_month_day = aswc_date( 'md', $aswc_current_time );

				$aswc_years = aswc_subscription_syn_year_period();

				if ( ! empty( $aswc_years ) && is_array( $aswc_years ) ) {
					if ( array_key_exists( $aswc_year_sync_value, $aswc_years ) ) {
						$aswc_selected_month = $aswc_years[ $aswc_year_sync_value ];
					}
				}

				$aswc_selected_month_day = sprintf( '%02d%02d', $aswc_year_sync_value, $aswc_year_number );
				if ( $aswc_curr_month_day > $aswc_selected_month_day ) {
					++$aswc_curr_year;
				}

				$aswc_first_payment_date = aswc_get_the_next_year_sync_time( "{$aswc_year_number} {$aswc_selected_month} {$aswc_curr_year}" );
			}
		}
		if ( ! empty( $aswc_first_payment_date ) ) {
			/* translators: %s: search term */
			$aswc_first_payment_date = '<span class="aswc_fist_payment_date">' . sprintf( __( 'You have to pay first recurring payment on: %s ', 'advanced-subscriptions-for-woocommerce' ), aswc_get_the_date_format( $aswc_first_payment_date ) ) . '</span>';
		}
		return $aswc_first_payment_date;
	}
}
if ( ! function_exists( 'aswc_get_sync_first_payment_date_for_price' ) ) {
	/**
	 * This function is used to get first payment date.
	 *
	 * @name aswc_get_sync_first_payment_date_for_price.
	 * @param int $product_id product_id.
	 * @since 1.0.0
	 */
	function aswc_get_sync_first_payment_date_for_price( $product_id ) {
		$aswc_first_payment_date = '';
		if ( ! aswc_subscription_syn_enable_per_product( $product_id ) ) {
			return $aswc_first_payment_date;
		}
		if ( isset( $product_id ) && ! empty( $product_id ) ) {
			$aswc_current_time = current_time( 'timestamp' );

			$aswc_frequency = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );
			$aswc_number    = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );

			$aswc_get_get_trial_period = aswc_get_trial_sync_time( $product_id, $aswc_current_time );
			if ( $aswc_get_get_trial_period > 0 ) {
				$aswc_current_time = $aswc_get_get_trial_period;
			}
			if ( 'week' == $aswc_frequency ) {
				$aswc_week_sync_value = aswc_get_meta_data( $product_id, 'aswc_week_sync', true );

				$aswc_week = aswc_subscription_week_period();

				if ( ! empty( $aswc_week ) && is_array( $aswc_week ) ) {
					if ( array_key_exists( $aswc_week_sync_value, $aswc_week ) ) {
						$aswc_selected_week = $aswc_week[ $aswc_week_sync_value ];
					}
				}

								$aswc_no_day = aswc_date( 'N', $aswc_current_time );

				$aswc_add_days = $aswc_week_sync_value < $aswc_no_day ? 0 : 7;
				$aswc_no_day   = $aswc_no_day + $aswc_add_days;

				$aswc_first_payment_date = aswc_get_the_next_year_sync_time( $aswc_selected_week, $aswc_current_time );

			} elseif ( 'month' == $aswc_frequency ) {
				$aswc_month_sync_value = aswc_get_meta_data( $product_id, 'aswc_month_sync', true );
				if ( 'end' == $aswc_month_sync_value ) {
										$aswc_month_sync_value = aswc_date( 't', $aswc_current_time );
				}

								$aswc_no_of_days     = aswc_date( 't', $aswc_current_time );
								$aswc_no_of_cur_date = aswc_date( 'j', $aswc_current_time );

				if ( $aswc_no_of_cur_date <= $aswc_month_sync_value ) {

					$aswc_day_diff = $aswc_no_of_days - $aswc_no_of_cur_date;
					$aswc_day_diff = $aswc_day_diff + $aswc_month_sync_value;

				} else {
					$aswc_day_diff = $aswc_no_of_days + $aswc_month_sync_value - $aswc_no_of_cur_date;

				}

				$aswc_first_payment_date = aswc_get_timestamp( $aswc_current_time, intval( $aswc_day_diff ) );
			} elseif ( 'year' == $aswc_frequency ) {

				$aswc_year_sync_value = aswc_get_meta_data( $product_id, 'aswc_year_sync', true );
				$aswc_year_number     = aswc_get_meta_data( $product_id, 'aswc_year_number', true );

								$aswc_curr_year = aswc_date( 'Y', $aswc_current_time );

								$aswc_curr_month_day = aswc_date( 'md', $aswc_current_time );

				$aswc_years = aswc_subscription_syn_year_period();

				if ( ! empty( $aswc_years ) && is_array( $aswc_years ) ) {
					if ( array_key_exists( $aswc_year_sync_value, $aswc_years ) ) {
						$aswc_selected_month = $aswc_years[ $aswc_year_sync_value ];
					}
				}

				$aswc_selected_month_day = sprintf( '%02d%02d', $aswc_year_sync_value, $aswc_year_number );
				if ( $aswc_curr_month_day > $aswc_selected_month_day ) {
					++$aswc_curr_year;
				}

				$aswc_first_payment_date = aswc_get_the_next_year_sync_time( "{$aswc_year_number} {$aswc_selected_month} {$aswc_curr_year}" );
			}
		}
		return $aswc_first_payment_date;
	}
}

if ( ! function_exists( 'aswc_get_the_date_format' ) ) {

	/**
	 * This function is used to get date format.
	 *
	 * @name aswc_get_the_date_format
	 * @since 1.0.0
	 * @param int $saved_date saved_date.
	 */
	function aswc_get_the_date_format( $saved_date ) {
		$return_date = '---';
		if ( isset( $saved_date ) && ! empty( $saved_date ) ) {

			$date_format = get_option( 'date_format', 'Y-m-d' );
			$return_date = date_i18n( $date_format, $saved_date );
		}

		return $return_date;
	}
}

if ( ! function_exists( 'aswc_get_the_next_year_sync_time' ) ) {

	/**
	 * This function is used to get date format.
	 *
	 * @name aswc_get_the_next_year_sync_time.
	 * @since 1.0.0
	 * @param string $aswc_date_string aswc_date_string.
	 * @param int    $aswc_current_time aswc_current_time.
	 */
	function aswc_get_the_next_year_sync_time( $aswc_date_string, $aswc_current_time = '' ) {
		$aswc_next_time_stamp = '';

		if ( empty( $aswc_current_time ) ) {
			$aswc_next_time_stamp = strtotime( $aswc_date_string );
		} else {
			$aswc_next_time_stamp = strtotime( $aswc_date_string, $aswc_current_time );
		}
		return $aswc_next_time_stamp;
	}
}

if ( ! function_exists( 'aswc_get_trial_sync_time' ) ) {

	/**
	 * This function is used to get date format.
	 *
	 * @name aswc_get_the_next_year_sync_time
	 * @since 1.0.0
	 * @param int $product_id product_id.
	 * @param int $current_time current_time.
	 */
	function aswc_get_trial_sync_time( $product_id, $current_time ) {
		$aswc_trial_date = 0;
		$trial_number    = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_number', true );
		$trial_interval  = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_interval', true );

		if ( isset( $trial_number ) && ! empty( $trial_number ) ) {
			$aswc_trial_date = aswc_susbcription_calculate_time( $current_time, $trial_number, $trial_interval );

		}

		return $aswc_trial_date;
	}
}

if ( ! function_exists( 'aswc_get_prorate_price_on_sync_enable' ) ) {

	/**
	 * This function is used to get prorate price.
	 *
	 * @name aswc_get_prorate_price_on_sync_enable
	 * @since 1.0.0
	 */
	function aswc_get_prorate_price_on_sync_enable() {

		$aswc_prorate_price_on_sync = get_option( 'aswc_prorate_price_on_sync', 'aswc_prorate_no' );

		return $aswc_prorate_price_on_sync;
	}
}
if ( ! function_exists( 'aswc_check_is_today_date' ) ) {

	/**
	 * This function is used to check today date.
	 *
	 * @param int $aswc_timestamp aswc_timestamp.
	 * @name aswc_check_is_today_date
	 * @since 1.0.0
	 */
	function aswc_check_is_today_date( $aswc_timestamp ) {
		$aswc_is_today   = false;
		$aswc_timestamp += (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

				$aswc_today_date = aswc_date( 'Y-m-d', current_time( 'timestamp' ) );
		if ( aswc_date( 'Y-m-d', $aswc_timestamp ) == $aswc_today_date ) {
			$aswc_is_today = true;
		}

		return $aswc_is_today;
	}
}

if ( ! function_exists( 'aswc_prorate_price_for_sync' ) ) {

	/**
	 * This function is used to check prorate price.
	 *
	 * @param int   $price price.
	 * @param int   $product_id product_id.
	 * @param array $cart_data cart_data.
	 * @name aswc_prorate_price_for_sync
	 * @since 1.0.0
	 */
	function aswc_prorate_price_for_sync( $price, $product_id, $cart_data ) {

		$aswc_first_payment_date = aswc_get_sync_first_payment_date_for_price( $product_id );

		if ( aswc_check_is_today_date( $aswc_first_payment_date ) ) {
			return $price;
		}
		$aswc_frequency  = aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );
		$aswc_number     = aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );
		$aswc_no_of_days = aswc_get_no_of_days( $aswc_frequency, $aswc_number );

		$manage_time = current_time( 'timestamp' );
		if ( aswc_allow_start_date_subscription() ) {
			$aswc_subscription_start_date = aswc_get_meta_data( $product_id, 'aswc_subscription_start_date', true );
			if ( $aswc_subscription_start_date ) {
								$manage_time = ASWC_Scheduler_API::date_to_time( $aswc_subscription_start_date );
			}
		}
		$aswc_next_payment = ceil( ( $aswc_first_payment_date - $manage_time ) / ( DAY_IN_SECONDS ) );
		$aswc_prorate_type = aswc_get_prorate_price_on_sync_enable();
		if ( aswc_check_is_trial( $product_id ) && 'aswc_prorate_if_free_trial' == $aswc_prorate_type ) {
			$product_price             = $cart_data['data']->get_price();
			$aswc_free_trial_number    = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_number', true );
			$aswc_free_trial_frequency = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_interval', true );
			$aswc_no_of_free_days      = aswc_get_no_of_days( $aswc_free_trial_frequency, $aswc_free_trial_number );
			$aswc_no_of_days           = $aswc_no_of_days - $aswc_no_of_free_days;
			$product_price             = $aswc_next_payment * ( $product_price / $aswc_no_of_days );
			$price                     = $price + $product_price;

		} elseif ( aswc_check_is_trial( $product_id ) && 'aswc_prorate_if_free_trial' != $aswc_prorate_type ) {
			return $price;
		} else {
			$price = $aswc_next_payment * ( $price / $aswc_no_of_days );
		}
		$price = round( $price, wc_get_price_decimals() );
		if ( $price < 0 ) {
			$price = 0;
		}
		return $price;
	}
}
if ( ! function_exists( 'aswc_get_no_of_days' ) ) {

	/**
	 * This function is used to get no days.
	 *
	 * @param string $aswc_frequency aswc_frequency.
	 * @param int    $aswc_number aswc_number.
	 * @name aswc_get_no_of_days
	 * @since 1.0.0
	 */
	function aswc_get_no_of_days( $aswc_frequency, $aswc_number ) {

		switch ( $aswc_frequency ) {
			case 'week':
				$aswc_number = 7 * $aswc_number;
				break;
			case 'month':
					$aswc_number = aswc_date( 't', current_time( 'timestamp' ) ) * $aswc_number;
				break;
			case 'year':
					$aswc_number = ( 365 + aswc_date( 'L', current_time( 'timestamp' ) ) ) * $aswc_number;
				break;
		}
		return $aswc_number;
	}
}

if ( ! function_exists( 'aswc_check_is_trial' ) ) {

	/**
	 * This function is used to check is trial.
	 *
	 * @param int $product_id product_id.
	 * @name aswc_check_is_trial
	 * @since 1.0.0
	 */
	function aswc_check_is_trial( $product_id ) {
		$aswc_is_trial          = false;
		$aswc_free_trial_number = aswc_get_meta_data( $product_id, 'aswc_subscription_free_trial_number', true );
		if ( isset( $aswc_free_trial_number ) && ! empty( $aswc_free_trial_number ) ) {
			$aswc_is_trial = true;
		}
		return $aswc_is_trial;
	}
}

if ( ! function_exists( 'aswc_check_enable_add_multiple_subscription_cart' ) ) {
	/**
	 * This function is used to check enable to add multiple product in cart.
	 *
	 * @name aswc_check_enable_add_multiple_subscription_cart
	 * @since 1.0.0
	 */
	function aswc_check_enable_add_multiple_subscription_cart() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_allow_to_add_multiple_subscription_cart', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}

if ( ! function_exists( 'aswc_reactivate_time_calculation' ) ) {
	/**
	 * This function is used to calculate time for reactivate subscription.
	 *
	 * @name aswc_reactivate_time_calculation
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 * @since 1.0.0
	 */
	function aswc_reactivate_time_calculation( $aswc_subscription_id ) {
		if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
			$aswc_reactivate_time = current_time( 'timestamp' );
			aswc_update_order_meta( $aswc_subscription_id, 'aswc_subscription_reactive_time', $aswc_reactivate_time );
			$aswc_pause_time = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_pause_time', true );

			if ( aswc_check_is_today_date( $aswc_pause_time ) ) {
				return;
			}

			$aswc_next_payment_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_next_payment_date', true );
			$aswc_susbcription_end  = aswc_get_meta_data( $aswc_subscription_id, 'aswc_susbcription_end', true );

			if ( ! empty( $aswc_reactivate_time ) && ! empty( $aswc_pause_time ) ) {

				$aswc_no_of_days = round( ( $aswc_reactivate_time - $aswc_pause_time ) / ( DAY_IN_SECONDS ) );

				if ( ! empty( $aswc_no_of_days ) && $aswc_no_of_days >= 1 ) {
					if ( ! empty( $aswc_next_payment_date ) ) {
						$aswc_next_payment_date = aswc_get_timestamp( $aswc_next_payment_date, intval( $aswc_no_of_days ) );
						aswc_update_order_meta( $aswc_subscription_id, 'aswc_next_payment_date', $aswc_next_payment_date );
					}
					if ( ! empty( $aswc_susbcription_end ) ) {
						$aswc_susbcription_end = aswc_get_timestamp( $aswc_susbcription_end, intval( $aswc_no_of_days ) );
						aswc_update_order_meta( $aswc_subscription_id, 'aswc_susbcription_end', $aswc_susbcription_end );
					}
				}
			}
		}
	}
}


if ( ! function_exists( 'aswc_no_of_susbcription_in_cart' ) ) {
	/**
	 * This function is used to enable shipping on subscription.
	 *
	 * @name aswc_no_of_susbcription_in_cart
	 * @since 1.0.0
	 */
	function aswc_no_of_susbcription_in_cart() {
		$count = 0;
		if ( ! empty( WC()->cart->cart_contents ) ) {

			foreach ( WC()->cart->cart_contents as $cart_item ) {
				if ( aswc_check_product_is_subscription( $cart_item['data'] ) ) {
					$aswc_has_subscription = true;
					++$count;
				}
			}
		}
		return $count;
	}
}

if ( ! function_exists( 'aswc_set_pause_subscription_timestamp' ) ) {
	/**
	 * This function is used to set subscription pause timestamp.
	 *
	 * @name aswc_set_pause_subscription_timestamp
	 * @param int $aswc_subscription_id subscription id.
	 * @since 1.0.0
	 */
	function aswc_set_pause_subscription_timestamp( $aswc_subscription_id ) {
		$aswc_pause_time = current_time( 'timestamp' );
		aswc_update_order_meta( $aswc_subscription_id, 'aswc_subscription_pause_time', $aswc_pause_time );
	}
}

if ( ! function_exists( 'aswc_get_subscription_coupon_enable_for_gc' ) ) {
	/**
	 * This function is used to get coupon type for giftcard.
	 *
	 * @name aswc_get_subscription_coupon_enable_for_gc
	 * @since 1.0.0
	 */
	function aswc_get_subscription_coupon_enable_for_gc() {
		$aswc_is_enable   = false;
		$general_settings = get_option( 'aswc_wgm_other_settings', array() );
		if ( class_exists( 'Woocommerce_Gift_Cards_Common_Function' ) ) {
			$aswc_public_obj       = new Woocommerce_Gift_Cards_Common_Function();
			$aswc_gc_coupon_enable = $aswc_public_obj->aswc_wgm_get_template_data( $general_settings, 'aswc_wgm_addition_subscription_coupon_option_enable' );

			if ( isset( $aswc_gc_coupon_enable ) && 'on' == $aswc_gc_coupon_enable ) {
				$aswc_is_enable = true;
			}
		}

		return $aswc_is_enable;
	}
}

if ( ! function_exists( 'aswc_get_subscription_coupon_type_for_gc' ) ) {
	/**
	 * This function is used to get coupon type for giftcard.
	 *
	 * @name aswc_get_subscription_coupon_type_for_gc
	 * @since 1.0.0
	 */
	function aswc_get_subscription_coupon_type_for_gc() {
		$aswc_gc_coupon_type = '';

		$general_settings = get_option( 'aswc_wgm_other_settings', array() );
		if ( class_exists( 'Woocommerce_Gift_Cards_Common_Function' ) ) {
			$aswc_public_obj     = new Woocommerce_Gift_Cards_Common_Function();
			$aswc_gc_coupon_type = $aswc_public_obj->aswc_wgm_get_template_data( $general_settings, 'aswc_wgm_addition_subscription_coupon_type' );
		}
		if ( empty( $aswc_gc_coupon_type ) ) {
			$aswc_gc_coupon_type = 'fixed_cart';
		}

		return $aswc_gc_coupon_type;
	}
}

if ( ! function_exists( 'aswc_enable_multiple_quantity_field' ) ) {
	/**
	 * This function is used to enable multiple quantity on subscription.
	 *
	 * @name aswc_enable_multiple_quantity_field
	 * @since 1.0.0
	 */
	function aswc_enable_multiple_quantity_field() {
		$is_enable   = false;
		$aswc_enable = get_option( 'aswc_allow_multiple_quantity_subscription', '' );
		if ( 'yes' === $aswc_enable ) {
			$is_enable = true;
		}
		return $is_enable;
	}
}
if ( ! function_exists( 'aswc_set_shipping_fee' ) ) {
	/**
	 * Function for aswc_set_shipping_fee.
	 *
	 * @param [type] $get_shipping_fee_order_id for $get_shipping_fee_order_id.
	 * @param [type] $set_shipping_fee_order_id for $set_shipping_fee_order_id.
	 * @return bool
	 */
	function aswc_set_shipping_fee( $get_shipping_fee_order_id, $set_shipping_fee_order_id ) {
		$flag      = false;
		$get_order = wc_get_order( $get_shipping_fee_order_id );

		$order_shipping = $get_order->get_items( 'shipping' );
		$set_order      = wc_get_order( $set_shipping_fee_order_id );

		foreach ( $order_shipping as $item_id => $item ) {
			$item_data = $item->get_data();

			$shipping_data_name = $item_data['name'];

			$shipping_data_total = $item_data['total'];

			$shipping_data_method_id = $item_data['method_id'];
			if ( $shipping_data_name && $shipping_data_total ) {

				$set_order->add_shipping( $item );
				$shipping_fee = new WC_Order_Item_Shipping();
				$shipping_fee->set_method_title( $shipping_data_name );
				$shipping_fee->set_method_id( $shipping_data_method_id );
				$shipping_fee->set_total( wc_format_decimal( $shipping_data_total ) );
				$set_order->add_item( $shipping_fee );
				$set_order->calculate_totals();
				$set_order->update_taxes();
				$set_order->save();
				$flag = true;
			}
		}
		return $flag;
	}
}
if ( ! function_exists( 'aswc_check_plugin_enable' ) ) {
	/**
	 * This function is used to check plugin is enable.
	 *
	 * @name aswc_check_plugin_enable
	 * @since 1.0.0
	 */
	function aswc_check_plugin_enable() {
		// Plugin always enabled when active - no need for separate option.
		return true;
	}
}
if ( ! function_exists( 'aswc_get_page_screen' ) ) {
	/**
	 * This function is used to get current screen.
	 *
	 * @name aswc_get_page_screen
	 * @since 1.0.0
	 */
	function aswc_get_page_screen() {

		$aswc_screen_id = sanitize_title( 'Jose Conti' );
		$screen_ids     = array(
			'toplevel_page_' . $aswc_screen_id,
			$aswc_screen_id . '_page_aswc_subscriptions_for_woocommerce_menu',
		);

		return apply_filters( 'aswc_page_screen', $screen_ids );
	}
}

if ( ! function_exists( 'aswc_if_product_onetime' ) ) {
	/**
	 * This function allows us to check if the subscription is one time or not
	 *
	 * @param int $product_id .
	 */
	function aswc_if_product_onetime( $product_id ) {
		if ( isset( WC()->session ) && 'on' === WC()->session->get( 'aswc_onetime_subscription_' . $product_id ) ) {
			return true;
		} else {
			return false;
		}
	}
}

