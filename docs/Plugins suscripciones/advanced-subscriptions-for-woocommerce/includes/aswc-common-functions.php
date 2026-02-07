<?php // phpcs:ignoreFile
/**
 * Exit if accessed directly
 *
 * @since 1.0.0
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/admin/partials
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'aswc_get_the_wordpress_date_format' ) ) {

		/**
		 * Retrieve a formatted date using the WordPress date settings.
		 *
		 * @param int $saved_date Timestamp to format.
		 *
		 * @return string
		 */
	function aswc_get_the_wordpress_date_format( $saved_date ) {
			$return_date = '---';
		if ( isset( $saved_date ) && ! empty( $saved_date ) ) {
				$date_format = get_option( 'date_format', 'Y-m-d' );
				$time_format = get_option( 'time_format', 'g:i a' );
				$wp_date     = date_i18n( $date_format, $saved_date );
				$return_date = $wp_date;
		}

			return $return_date;
	}
}

if ( ! function_exists( 'aswc_get_wp_date' ) ) {

				/**
				 * Retrieve the current date using WordPress timezone.
				 *
				 * @since 1.0.0
				 *
				 * @param string $format Date format. Defaults to Y-m-d.
				 * @return string
				 */
	function aswc_get_wp_date( $format = 'Y-m-d' ) {
			$timestamp = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
		if ( function_exists( 'wp_date' ) ) {
						return wp_date( $format, $timestamp );
		}

					return date_i18n( $format, $timestamp );
	}
}

if ( ! function_exists( 'aswc_get_wp_timestamp' ) ) {

				/**
				 * Retrieve the current timestamp using the Scheduler API when available.
				 *
				 * Falls back to WordPress's current_time() when the API is not loaded.
				 *
				 * @since 1.0.0
				 *
				 * @return int Current Unix timestamp.
				 */
	function aswc_get_wp_timestamp() {
		if ( class_exists( 'ASWC_Scheduler_API' ) ) {
				$timestamp = ASWC_Scheduler_API::date_to_time( 'now' );
		} else {
				$timestamp = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
		}

			return (int) $timestamp;
	}
}

if ( ! function_exists( 'aswc_date' ) ) {

				/**
				 * Format a timestamp using the WordPress timezone.
				 *
				 * @since 1.0.0
				 *
				 * @param string $format    Date format.
				 * @param int    $timestamp Unix timestamp.
				 * @return string
				 */
	function aswc_date( $format, $timestamp ) {
		if ( function_exists( 'wp_date' ) ) {
						return wp_date( $format, $timestamp );
		}

					return date_i18n( $format, $timestamp );
	}
}

if ( ! function_exists( 'aswc_next_payment_date' ) ) {

	/**
	 * Retrieve next payment timestamp for a subscription.
	 *
	 * @name aswc_next_payment_date
	 * @since 1.0.0
	 *
	 * @param int $subscription_id            Subscription ID.
	 * @param int $current_time               Current timestamp.
	 * @param int $aswc_susbcription_trial_end Trial end timestamp.
	 * @return int Next payment timestamp.
	 */
	function aswc_next_payment_date( $subscription_id, $current_time, $aswc_susbcription_trial_end ) {

                $logger  = wc_get_logger();
                $context = array( 'source' => 'aswc-renewal' );
                $logger->info( sprintf( 'Calculating next payment for subscription %d', $subscription_id ), $context );

                $aswc_recurring_number   = (int) aswc_get_meta_data( $subscription_id, 'aswc_subscription_number', true );
                $aswc_recurring_interval = (string) aswc_get_meta_data( $subscription_id, 'aswc_subscription_interval', true );
                $existing_next_payment   = (int) aswc_get_meta_data( $subscription_id, 'aswc_next_payment_date', true );

                if ( 0 === $aswc_recurring_number || empty( $aswc_recurring_interval ) ) {
                        $product_id = (int) aswc_get_meta_data( $subscription_id, 'product_id', true );

                        if ( 0 !== $product_id ) {
                                if ( 0 === $aswc_recurring_number ) {
                                        $aswc_recurring_number = (int) aswc_get_meta_data( $product_id, 'aswc_subscription_number', true );
                                }

                                if ( empty( $aswc_recurring_interval ) ) {
                                        $aswc_recurring_interval = (string) aswc_get_meta_data( $product_id, 'aswc_subscription_interval', true );
                                }
                        }

                        if ( 0 === $aswc_recurring_number ) {
                                $aswc_recurring_number = 1;
                        }

                        if ( empty( $aswc_recurring_interval ) ) {
                                $aswc_recurring_interval = 'day';
                        }
                }

                $logger->info(
                        sprintf(
                                'Inputs — recurring_number:%1$d interval:%2$s existing_next:%3$d current_time:%4$d trial_end:%5$d',
                                $aswc_recurring_number,
                                $aswc_recurring_interval,
                                $existing_next_payment,
                                $current_time,
                                $aswc_susbcription_trial_end
                        ),
                        $context
                );

                if ( 0 !== $aswc_susbcription_trial_end && 0 === $existing_next_payment ) {
                        $logger->info( sprintf( 'Next payment timestamp: %d', $aswc_susbcription_trial_end ), $context );
                        return $aswc_susbcription_trial_end;
                }

                $base_time = $current_time;

                if ( $existing_next_payment > $base_time ) {
                        $base_time = $existing_next_payment;
                }

                $logger->info( sprintf( 'Base time: %d', $base_time ), $context );

                $aswc_next_pay_date = aswc_susbcription_calculate_time( $base_time, $aswc_recurring_number, $aswc_recurring_interval );

                $logger->info( sprintf( 'Next payment timestamp: %d', $aswc_next_pay_date ), $context );
                return $aswc_next_pay_date;
	}
}

if ( ! function_exists( 'aswc_susbcription_expiry_date' ) ) {

	/**
	 * This function is used to get expiry date.
	 *
	 * @name aswc_susbcription_expiry_date
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param int $current_time current_time.
	 * @param int $trial_end trial_end.
	 */
	function aswc_susbcription_expiry_date( $subscription_id, $current_time, $trial_end = 0 ) {
			$logger  = wc_get_logger();
			$context = array( 'source' => 'aswc-renewal' );
			$logger->info( sprintf( 'Calculating expiry for subscription %d', $subscription_id ), $context );

			$aswc_expiry_date = 0;
			$expiry_number    = (int) aswc_get_meta_data( $subscription_id, 'aswc_subscription_expiry_number', true );
			$expiry_interval  = (string) aswc_get_meta_data( $subscription_id, 'aswc_subscription_expiry_interval', true );
		if ( isset( $expiry_number ) && ! empty( $expiry_number ) ) {
			if ( 0 !== $trial_end ) {
					$aswc_expiry_date = aswc_susbcription_calculate_time( $trial_end, $expiry_number, $expiry_interval );
			} else {
						$aswc_expiry_date = aswc_susbcription_calculate_time( $current_time, $expiry_number, $expiry_interval );
			}
		}

			$logger->info( sprintf( 'Expiry timestamp: %d', $aswc_expiry_date ), $context );
			return $aswc_expiry_date;
	}
}

if ( ! function_exists( 'aswc_susbcription_trial_date' ) ) {

	/**
	 * This function is used to get trial date.
	 *
	 * @name aswc_susbcription_trial_date
	 * @since 1.0.0
	 * @param int $subscription_id subscription_id.
	 * @param int $current_time current_time.
	 */
	function aswc_susbcription_trial_date( $subscription_id, $current_time ) {
		$aswc_trial_date = 0;
		$trial_number    = (int) aswc_get_meta_data( $subscription_id, 'aswc_subscription_free_trial_number', true );
		$trial_interval  = (string) aswc_get_meta_data( $subscription_id, 'aswc_subscription_free_trial_interval', true );

		if ( isset( $trial_number ) && ! empty( $trial_number ) ) {
			$aswc_trial_date = aswc_susbcription_calculate_time( $current_time, $trial_number, $trial_interval );

		}

		return $aswc_trial_date;
	}
}

if ( ! function_exists( 'aswc_susbcription_calculate_time' ) ) {

	/**
	 * This function is used to calculate time.
	 *
	 * @name aswc_susbcription_calculate_time
	 * @since 1.0.0
	 * @param int    $aswc_curr_time aswc_curr_time.
	 * @param int    $aswc_interval_count aswc_interval_count.
	 * @param string $aswc_interval aswc_interval.
	 */
	function aswc_susbcription_calculate_time( $aswc_curr_time, $aswc_interval_count, $aswc_interval ) {

			$logger  = wc_get_logger();
			$context = array( 'source' => 'aswc-renewal' );
			$logger->info(
				sprintf(
					'Calculating time: current %d, count %d, interval %s',
					$aswc_curr_time,
					$aswc_interval_count,
					$aswc_interval
				),
				$context
			);

			$aswc_next_date = 0;
		switch ( $aswc_interval ) {
			case 'day':
					$aswc_next_date = aswc_get_timestamp( $aswc_curr_time, $aswc_interval_count );
				break;
			case 'week':
					$aswc_next_date = aswc_get_timestamp( $aswc_curr_time, $aswc_interval_count * 7 );
				break;
			case 'month':
					$aswc_next_date = aswc_get_timestamp( $aswc_curr_time, 0, $aswc_interval_count );
				break;
			case 'year':
					$aswc_next_date = aswc_get_timestamp( $aswc_curr_time, 0, 0, $aswc_interval_count );
				break;
			default:
		}

			$logger->info( sprintf( 'Calculated timestamp: %d', $aswc_next_date ), $context );
			return $aswc_next_date;
	}
}

if ( ! function_exists( 'aswc_get_timestamp' ) ) {
		/**
		 * Calculate a timestamp by adding time intervals.
		 *
		 * @name aswc_get_timestamp
		 * @since 1.0.0
		 * @param int $aswc_curr_time Current timestamp.
		 * @param int $aswc_days      Optional. Number of days to add.
		 * @param int $aswc_months    Optional. Number of months to add.
		 * @param int $aswc_years     Optional. Number of years to add.
		 * @return int Modified timestamp.
		 */
	function aswc_get_timestamp( $aswc_curr_time, $aswc_days = 0, $aswc_months = 0, $aswc_years = 0 ) {

			$logger  = wc_get_logger();
			$context = array( 'source' => 'aswc-renewal' );
			$logger->info(
				sprintf(
					'Get timestamp from %d with days %d, months %d, years %d',
					$aswc_curr_time,
					$aswc_days,
					$aswc_months,
					$aswc_years
				),
				$context
			);

		if ( $aswc_days ) {
			$aswc_curr_time = strtotime( '+' . (int) $aswc_days . ' days', $aswc_curr_time );
		} elseif ( $aswc_months ) {
				$aswc_curr_time = strtotime( '+' . (int) $aswc_months . ' month', $aswc_curr_time );
		} elseif ( $aswc_years ) {
					$aswc_curr_time = strtotime( '+' . (int) $aswc_years . ' year', $aswc_curr_time );
		}

			$logger->info( sprintf( 'Resulting timestamp: %d', $aswc_curr_time ), $context );
			return $aswc_curr_time;
	}
}

if ( ! function_exists( 'aswc_check_valid_subscription' ) ) {
	/**
	 * This function is used to check susbcription post type.
	 *
	 * @name aswc_check_valid_subscription
	 * @since 1.0.0
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 */
	function aswc_check_valid_subscription( $aswc_subscription_id ) {
		$aswc_is_subscription = false;

		if ( isset( $aswc_subscription_id ) && ! empty( $aswc_subscription_id ) ) {
			if ( 'shop_order_placehold' === get_post_type( absint( $aswc_subscription_id ) ) || 'aswc_subscriptions' === get_post_type( absint( $aswc_subscription_id ) ) ) {
				$aswc_is_subscription = true;
			}
		}

		return $aswc_is_subscription;
	}
}

if ( ! function_exists( 'aswc_update_meta_key_for_susbcription' ) ) {
	/**
	 * This function is used to check susbcription post type.
	 *
	 * @name aswc_update_meta_key_for_susbcription
	 * @since 1.0.0
	 * @param int   $subscription_id subscription_id.
	 * @param array $aswc_args aswc_args.
	 */
	function aswc_update_meta_key_for_susbcription( $subscription_id, $aswc_args ) {
		if ( isset( $aswc_args ) && ! empty( $aswc_args ) && is_array( $aswc_args ) ) {
			foreach ( $aswc_args as $key => $value ) {
				aswc_update_meta_data( $subscription_id, $key, $value );
			}
		}
	}
}

if ( ! function_exists( 'aswc_send_email_for_renewal_susbcription' ) ) {
	/**
	 * This function is used to send renewal email.
	 *
	 * @name aswc_send_email_for_renewal_susbcription
	 * @since 1.0.0
	 * @param int $order_id order_id.
	 */
	function aswc_send_email_for_renewal_susbcription( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( isset( $order ) && is_object( $order ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "processing" notification.
			if ( isset( $mailer['WC_Email_New_Order'] ) ) {
				$mailer['WC_Email_New_Order']->trigger( $order_id );
			}
			do_action( 'aswc_renewal_email_notification', $order, $mailer );
		}
	}
}

if ( ! function_exists( 'aswc_send_email_for_cancel_susbcription' ) ) {
	/**
	 * This function is used to send cancel email.
	 *
	 * @name aswc_send_email_for_cancel_susbcription
	 * @since 1.0.0
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 */
	function aswc_send_email_for_cancel_susbcription( $aswc_subscription_id ) {

		if ( isset( $aswc_subscription_id ) && ! empty( $aswc_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "cancel" notification.
			if ( isset( $mailer['aswc_cancel_subscription'] ) ) {
					$mailer['aswc_cancel_subscription']->trigger( $aswc_subscription_id );
			}
		}
	}
}

if ( ! function_exists( 'aswc_send_email_for_expired_susbcription' ) ) {
	/**
	 * This function is used to send expired email.
	 *
	 * @name aswc_send_email_for_expired_susbcription
	 * @since 1.0.0
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 */
	function aswc_send_email_for_expired_susbcription( $aswc_subscription_id ) {

		if ( isset( $aswc_subscription_id ) && ! empty( $aswc_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "expired" notification.
			if ( isset( $mailer['aswc_expired_subscription'] ) ) {
					$mailer['aswc_expired_subscription']->trigger( $aswc_subscription_id );
			}
		}
	}
}


if ( ! function_exists( 'aswc_email_subscriptions_details' ) ) {
		/**
		 * Creates HTML for subscription details.
		 *
		 * @since 1.0.0
		 * @param int $aswc_subscription_id Subscription ID.
		 * @return void
		 */
	function aswc_email_subscriptions_details( $aswc_subscription_id ) {
			$aswc_text_align = is_rtl() ? 'right' : 'left';

		?>
				<div style="margin-bottom: 40px;">
						<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
								<thead>
										<tr>
												<th class="td" scope="col" style="text-align:<?php echo esc_attr( $aswc_text_align ); ?>;"><?php esc_html_e( 'Product', 'advanced-subscriptions-for-woocommerce' ); ?></th>
												<th class="td" scope="col" style="text-align:<?php echo esc_attr( $aswc_text_align ); ?>;"><?php esc_html_e( 'Quantity', 'advanced-subscriptions-for-woocommerce' ); ?></th>
												<th class="td" scope="col" style="text-align:<?php echo esc_attr( $aswc_text_align ); ?>;"><?php esc_html_e( 'Price', 'advanced-subscriptions-for-woocommerce' ); ?></th>
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
													$aswc_product_qty = aswc_get_meta_data( $aswc_subscription_id, 'product_qty', true );
													echo esc_html( $aswc_product_qty );
													?>
												</td>
												<td>
												<?php
													do_action( 'aswc_display_subscription_recurring_total_account_page', $aswc_subscription_id );
													do_action( 'aswc_display_susbcription_recerring_total_account_page', $aswc_subscription_id );
												?>
												</td>
										</tr>
								</tbody>
						</table>
				</div>
				<?php
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
if ( ! function_exists( 'aswc_is_true' ) ) {
	/**
	 * Normalize boolean-like option values.
	 *
	 * Accepts values stored as "on" or "yes" and returns a boolean.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Raw value from option or meta.
	 * @return bool
	 */
	function aswc_is_true( $value ) {
		return in_array( $value, array( 'yes', 'on', '1', 1, true ), true );
	}
}

if ( ! function_exists( 'aswc_option_is_true' ) ) {
	/**
	 * Retrieve an option and determine if it is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Name of the option to check.
	 * @return bool
	 */
	function aswc_option_is_true( $option_name ) {
		$value = get_option( $option_name, '' );
		return aswc_is_true( $value );
	}
}

if ( ! function_exists( 'mwb_sfw_check_plugin_enable' ) ) {
	/**
	 * This function is used to check plugin is enable.
	 *
	 * @name mwb_sfw_check_plugin_enable
	 * @since 1.0.0
	 */
	function mwb_sfw_check_plugin_enable() {
		// Plugin always enabled when active - no need for separate option.
		return true;
	}
}
if ( ! function_exists( 'aswc_validate_payment_request' ) ) {
	/**
	 * This function is used to check plugin is enable.
	 *
	 * @name aswc_check_plugin_enable
	 * @param Object $aswc_subscription aswc_subscription.
	 * @since 1.0.0
	 */
	function aswc_validate_payment_request( $aswc_subscription ) {
		$result     = true;
		$order_key  = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$aswc_nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( wp_verify_nonce( $aswc_nonce ) === false ) {
			$result = false;
			wc_add_notice( __( 'There was an error with your request.', 'advanced-subscriptions-for-woocommerce' ), 'error' );
		} elseif ( empty( $aswc_subscription ) ) {
			$result = false;
			wc_add_notice( __( 'Invalid Subscription.', 'advanced-subscriptions-for-woocommerce' ), 'error' );
		} elseif ( $aswc_subscription->get_order_key() !== $order_key ) {
			$result = false;
			wc_add_notice( __( 'Invalid subscription order.', 'advanced-subscriptions-for-woocommerce' ), 'error' );
		}
		return $result;
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
if ( ! function_exists( 'mwb_sfw_get_page_screen' ) ) {
	/**
	 * This function is used to get current screen.
	 *
	 * @name mwb_sfw_get_page_screen
	 * @since 1.0.0
	 */
	function mwb_sfw_get_page_screen() {

		$aswc_screen_id = sanitize_title( 'Jose Conti' );
		$screen_ids     = array(
			'toplevel_page_' . $aswc_screen_id,
			$aswc_screen_id . '_page_aswc_subscriptions_for_woocommerce_menu',
		);

		return apply_filters( 'aswc_page_screen', $screen_ids );
	}
}

if ( ! function_exists( 'aswc_check_product_is_subscription' ) ) {
		/**
		 * Check whether a product or variation is a subscription product.
		 *
		 * @name aswc_check_product_is_subscription
		 * @param object $product Product object.
		 * @since 1.0.0
		 */
	function aswc_check_product_is_subscription( $product ) {

			$aswc_is_subscription = false;
		if ( is_object( $product ) ) {
				$product_id                = $product->get_id();
				$aswc_subscription_product = aswc_get_meta_data( $product_id, '_aswc_product', true );
			if ( 'yes' === $aswc_subscription_product ) {
				$aswc_is_subscription = true;
			} elseif ( 'variation' === $product->get_type() ) {
					$parent_id = $product->get_parent_id();
				if ( ! empty( $parent_id ) ) {
					$parent_subscription = aswc_get_meta_data( $parent_id, '_aswc_product', true );
					if ( 'yes' === $parent_subscription ) {
							$aswc_is_subscription = true;
					}
				}
			} elseif ( 'subscription_box' === $product->get_type() ) {
					// subscription box.
					$aswc_is_subscription = true;

					// subscription box.
			}
		}

			return apply_filters( 'aswc_check_subscription_product_type', $aswc_is_subscription, $product );
	}
}

if ( ! function_exists( 'aswc_subscription_period' ) ) {

	/**
	 * This function is used to add subscription intervals.
	 *
	 * @name aswc_subscription_period
	 * @since 1.0.0
	 * @return   Array  $subscription_interval
	 */
	function aswc_subscription_period() {
		$subscription_interval = array(
			'day'   => __( 'Days', 'advanced-subscriptions-for-woocommerce' ),
			'week'  => __( 'Weeks', 'advanced-subscriptions-for-woocommerce' ),
			'month' => __( 'Months', 'advanced-subscriptions-for-woocommerce' ),
			'year'  => __( 'Years', 'advanced-subscriptions-for-woocommerce' ),
		);
		return apply_filters( 'aswc_subscription_intervals', $subscription_interval );
	}
}

if ( ! function_exists( 'aswc_subscription_expiry_period' ) ) {

	/**
	 * This function is used to add subscription intervals for expiry.
	 *
	 * @name aswc_subscription_expiry_period
	 * @since 1.0.0
	 * @param   string $aswc_subscription_interval aswc_subscription_interval.
	 */
	function aswc_subscription_expiry_period( $aswc_subscription_interval ) {

		$subscription_interval = array(
			'day'   => __( 'Days', 'advanced-subscriptions-for-woocommerce' ),
			'week'  => __( 'Weeks', 'advanced-subscriptions-for-woocommerce' ),
			'month' => __( 'Months', 'advanced-subscriptions-for-woocommerce' ),
			'year'  => __( 'Years', 'advanced-subscriptions-for-woocommerce' ),
		);
		if ( 'day' === $aswc_subscription_interval ) {
			unset( $subscription_interval['week'] );
			unset( $subscription_interval['month'] );
			unset( $subscription_interval['year'] );
		} elseif ( 'week' === $aswc_subscription_interval ) {
			unset( $subscription_interval['day'] );
			unset( $subscription_interval['month'] );
			unset( $subscription_interval['year'] );

		} elseif ( 'month' === $aswc_subscription_interval ) {
			unset( $subscription_interval['day'] );
			unset( $subscription_interval['week'] );
			unset( $subscription_interval['year'] );

		} elseif ( 'year' === $aswc_subscription_interval ) {
			unset( $subscription_interval['day'] );
			unset( $subscription_interval['week'] );
			unset( $subscription_interval['month'] );
		}
		return apply_filters( 'aswc_subscription_expiry_intervals', $subscription_interval );
	}
}



if ( ! function_exists( 'aswc_get_time_interval' ) ) {
	/**
	 * This function is used to show subscription price and interval on subscription product page.
	 *
	 * @name aswc_get_time_interval
	 * @param int    $aswc_subscription_number Subscription inteval number.
	 * @param string $aswc_subscription_interval Subscription Interval .
	 * @since 1.0.0
	 */
	function aswc_get_time_interval( $aswc_subscription_number, $aswc_subscription_interval ) {
		$aswc_subscription_number = (int) $aswc_subscription_number;
		$aswc_price_html          = '';
		switch ( $aswc_subscription_interval ) {
			case 'day':
				/* translators: %s: Day,%s: Days */
				$aswc_price_html = sprintf( _n( '%s Day', '%s Days', $aswc_subscription_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_subscription_number );
				break;
			case 'week':
				/* translators: %s: Week,%s: Weeks */
				$aswc_price_html = sprintf( _n( '%s Week', '%s Weeks', $aswc_subscription_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_subscription_number );
				break;
			case 'month':
				/* translators: %s: Month,%s: Months */
				$aswc_price_html = sprintf( _n( '%s Month', '%s Months', $aswc_subscription_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_subscription_number );
				break;
			case 'year':
				/* translators: %s: Year,%s: Years */
				$aswc_price_html = sprintf( _n( '%s Year', '%s Years', $aswc_subscription_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_subscription_number );
				break;
		}
		return apply_filters( 'aswc_display_time_interval', $aswc_price_html );
	}
}
if ( ! function_exists( 'aswc_get_time_interval_for_price' ) ) {
	/**
	 * This function is used to show subscription price and interval on subscription product page.
	 *
	 * @name aswc_get_time_interval_for_price
	 * @param int    $aswc_subscription_number Subscription inteval number.
	 * @param string $aswc_subscription_interval Subscription Interval .
	 * @since 1.0.0
	 */
	function aswc_get_time_interval_for_price( $aswc_subscription_number, $aswc_subscription_interval ) {
		$aswc_number = (int) $aswc_subscription_number;
		if ( 1 === $aswc_subscription_number ) {
			$aswc_subscription_number = '';
		}

		$aswc_price_html = '';
		switch ( $aswc_subscription_interval ) {
			case 'day':
				/* translators: %s: Day,%s: Days */
				$aswc_price_html = sprintf( _n( '%s Day', '%s Days', $aswc_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_subscription_number );
				break;
			case 'week':
				/* translators: %s: Week,%s: Weeks */
				$aswc_price_html = sprintf( _n( '%s Week', '%s Weeks', $aswc_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_subscription_number );
				break;
			case 'month':
				/* translators: %s: Month,%s: Months */
				$aswc_price_html = sprintf( _n( '%s Month', '%s Months', $aswc_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_subscription_number );
				break;
			case 'year':
				/* translators: %s: Year,%s: Years */
				$aswc_price_html = sprintf( _n( '%s Year', '%s Years', $aswc_number, 'advanced-subscriptions-for-woocommerce' ), $aswc_subscription_number );
				break;
		}
		return $aswc_price_html;
	}
}

if ( ! function_exists( 'aswc_pro_active' ) ) {
	/**
	 * This function is used to check if premium plugin is activated.
	 *
	 * @since 1.0.0
	 * @name aswc_pro_active
	 * @return boolean
	 */
	function aswc_pro_active() {
		return apply_filters( 'aswc_pro_active', false );
	}
}

if ( ! function_exists( 'aswc_delete_failed_subscription' ) ) {
	/**
	 * This function is used to delete faild subscription.
	 *
	 * @since 1.0.0
	 * @name aswc_delete_failed_subscription
	 * @param int $order_id order_id.
	 */
	function aswc_delete_failed_subscription( $order_id ) {
		if ( isset( $order_id ) && ! empty( $order_id ) ) {

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$args               = array(
					'return'     => 'ids',
					'type'       => 'aswc_subscriptions',
					'status'     => array( 'pending', 'on-hold', 'failed' ),
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
												'relation' => 'AND',
												array(
													'key' => 'aswc_parent_order',
													'value' => $order_id,
												),
					),
				);
				$aswc_subscriptions = wc_get_orders( $args );
			} else {
				$args               = array(
					'numberposts' => -1,
					'post_type'   => 'aswc_subscriptions',
					'post_status' => array( 'wc-pending', 'wc-on-hold', 'wc-failed' ),
					'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
												'relation' => 'AND',
												array(
													'key' => 'aswc_parent_order',
													'value' => $order_id,
												),
					),
				);
				$aswc_subscriptions = get_posts( $args );
			}

			if ( ! empty( $aswc_subscriptions ) && is_array( $aswc_subscriptions ) ) {
				foreach ( $aswc_subscriptions as $key => $value ) {
					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$subscription = new ASWC_Subscription( $value );
						$subscription->delete( true );
					} else {
						wp_delete_post( $value->ID, true );
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'aswc_recerring_total_price_list_table_callback' ) ) {
	/**
	 * This function is used show recuring interval on list.
	 *
	 * @name aswc_recerring_total_price_list_table_callback
	 * @param string $aswc_price aswc_price.
	 * @param int    $aswc_subscription_id aswc_subscription_id.
	 * @since 1.0.0
	 */
	function aswc_recerring_total_price_list_table_callback( $aswc_price, $aswc_subscription_id ) {
		if ( aswc_check_valid_subscription( $aswc_subscription_id ) ) {
			$aswc_recurring_number   = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_number', true );
			$aswc_recurring_interval = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_interval', true );
			$aswc_price_html         = aswc_get_time_interval_for_price( $aswc_recurring_number, $aswc_recurring_interval );

			/* translators: %s: frequency interval. */
			$aswc_price .= sprintf( esc_html( ' / %s ' ), $aswc_price_html );
		}
		return $aswc_price;
	}
}
if ( ! function_exists( 'aswc_get_file_content' ) ) {
	/**
	 * This function is used to get file content.
	 *
	 * @name aswc_get_file_content
	 * @param string $aswc_file_path aswc_file_path.
	 * @since 1.0.0
	 */
	function aswc_get_file_content( $aswc_file_path ) {
		global $wp_filesystem;

		WP_Filesystem();
		$aswc_file_content = $wp_filesystem->get_contents( $aswc_file_path );
		return $aswc_file_content;
	}
}
if ( ! function_exists( 'aswc_cart_has_subscription_product' ) ) {
		/**
		 * Check if the cart contains a subscription product.
		 *
		 * @name aswc_cart_has_subscription_product
		 * @since 1.0.0
		 */
	function aswc_cart_has_subscription_product() {
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

if ( ! function_exists( 'aswc_cart_contains_subscription' ) ) {
	/**
	 * Determine whether the cart contains a subscription product.
	 *
	 * Alias of aswc_cart_has_subscription_product() to mirror the naming
	 * used historically by the legacy implementation and ease gateway integrations.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True when a subscription product is present in the cart.
	 */
	function aswc_cart_contains_subscription() {
			return aswc_cart_has_subscription_product();
	}
}

if ( ! function_exists( 'aswc_order_contains_subscription' ) ) {
		/**
		 * Determine whether an order contains a subscription product.
		 *
		 * Accepts an order object or ID and takes product variations into account.
		 *
		 * @since 1.0.0
		 *
		 * @param WC_Order|int $order Order object or ID.
		 * @return bool True when a subscription product is present in the order.
		 */
	function aswc_order_contains_subscription( $order ) {
			$aswc_has_subscription = false;

			$order = wc_get_order( $order );

		if ( ! $order ) {
				return $aswc_has_subscription;
		}

		foreach ( $order->get_items() as $order_item ) {
				$product = $order_item->get_product();
			if ( $product && aswc_check_product_is_subscription( $product ) ) {
					$aswc_has_subscription = true;
					break;
			}
		}

			return $aswc_has_subscription;
	}
}


if ( ! function_exists( 'aswc_is_enable_usage_tracking' ) ) {
	/**
	 * This function is used to check tracking enable.
	 *
	 * @name aswc_is_enable_usage_tracking
	 * @since 1.0.0
	 */
	function aswc_is_enable_usage_tracking() {
			return false;
	}
}

if ( ! function_exists( 'aswc_check_valid_order' ) ) {
       /**
        * This function is used to check valid order.
        *
        * @name aswc_check_valid_order
        * @param string $order_id order_id.
        * @since 1.0.0
        */
       function aswc_check_valid_order( $order_id ) {
               $order_id = absint( $order_id );

               if ( true === empty( $order_id ) ) {
                       return false;
               }

               $order = wc_get_order( $order_id );

               if ( false === $order ) {
                       return false;
               }

               if ( true === OrderUtil::custom_orders_table_usage_is_enabled() ) {
                       $status = $order->get_status();

                       if ( 'trash' === $status ) {
                               return false;
                       }

                       return true;
               }

               $status = get_post_status( $order_id );

               if ( false === $status || 'trash' === $status ) {
                       return false;
               }

               return true;
       }


}
if ( ! function_exists( 'aswc_is_woocommerce_tax_enabled' ) ) {
	/**
	 * Check if WooCommerce taxes are enabled.
	 *
	 * @return bool
	 */
	function aswc_is_woocommerce_tax_enabled() {
		// Check if WooCommerce is active.
		if ( class_exists( 'WooCommerce' ) ) {
			// Get the tax options.
			$tax_options = get_option( 'woocommerce_calc_taxes' );

			// Check if taxes are enabled.
			if ( 'yes' === $tax_options ) {
				return true; // Taxes are enabled.
			}
		}
		return false; // Taxes are not enabled or WooCommerce is not active.
	}
}
if ( ! function_exists( 'aswc_order_has_subscription' ) ) {
	/**
	 * Check if order contain subscrption product.
	 *
	 * @param string $order_id order_id.
	 * @return bool
	 */
	function aswc_order_has_subscription( $order_id ) {

		$aswc_has_subscription = false;

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( $item->get_variation_id() ) {
				$product_id = $item->get_variation_id();
			}
			$product = wc_get_product( $product_id );
			if ( aswc_check_product_is_subscription( $product ) ) {
				$aswc_has_subscription = true;
				break;
			}
		}
		return $aswc_has_subscription;
	}
}

if ( ! function_exists( 'aswc_check_api_enable' ) ) {

		/**
		 * Determine if the API feature is enabled.
		 *
		 * @return bool
		 */
	function aswc_check_api_enable() {
			$is_enable               = false;
						$aswc_enable = get_option( 'aswc_enable_api_features', '' );
		if ( 'on' === $aswc_enable ) {
			$is_enable = true;
		}
			return $is_enable;
	}
}

if ( ! function_exists( 'aswc_api_get_secret_key' ) ) {

		/**
		 * Retrieve the stored API secret key.
		 *
		 * @return string
		 */
	function aswc_api_get_secret_key() {
			$aswc_api_secret_key = get_option( 'aswc_api_secret_key', '' );
			return $aswc_api_secret_key;
	}
}

if ( ! function_exists( 'aswc_add_attached_product_for_subscription_box' ) ) {

	/**
	 * Function to attached product into subscrpition order.
	 *
	 * @param object $order_id as order id.
	 * @return void
	 */
	function aswc_add_attached_product_for_subscription_box( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item_id => $item ) {
			$attached_products = wc_get_order_item_meta( $item_id, 'aswc_attached_products', true );

			if ( ! empty( $attached_products ) ) {
				foreach ( $attached_products as $attached_product ) {
					$product_id = $attached_product['product_id'];
					$product    = wc_get_product( $product_id );

					// Add attached product as a new order item with WooCommerce functions.
					$attached_item = new WC_Order_Item_Product();
					$attached_item->set_product_id( $product_id );
					$attached_item->set_name( $product->get_name() );
					$attached_item->set_quantity( $attached_product['quantity'] );
					$attached_item->set_subtotal( 0 );
					$attached_item->set_total( 0 );

					// Add custom meta.
					$attached_item->add_meta_data( '_is_attached_product', 'yes', true );

					// Add the item to the order.
					$order->add_item( $attached_item );
				}
			}
		}

		// Save the order to update all items properly.
		$order->save();
	}
}

if ( ! function_exists( 'aswc_send_email_for_active_susbcription' ) ) {
	/**
	 * This function is used to send cancel email.
	 *
	 * @name aswc_send_email_for_active_susbcription
	 * @since 1.0.0
	 * @param int $aswc_subscription_id aswc_subscription_id.
	 */
	function aswc_send_email_for_active_susbcription( $aswc_subscription_id ) {

		if ( isset( $aswc_subscription_id ) && ! empty( $aswc_subscription_id ) ) {
			$mailer = WC()->mailer()->get_emails();
			// Send the "active" notification.
			if ( isset( $mailer['aswc_onhold_active_subscription'] ) ) {
					$mailer['aswc_onhold_active_subscription']->trigger( $aswc_subscription_id );
			}
		}
	}
}

// ============================================================================
// SECURITY HELPER FUNCTIONS - Phase 1.2
// Added: 2026-01-06
// Purpose: Transaction locks, amount validation, and ownership verification
// ============================================================================

if ( ! function_exists( 'aswc_acquire_payment_lock' ) ) {
	/**
	 * Acquire a transactional lock for subscription payment processing.
	 *
	 * Prevents race conditions where multiple processes might try to charge
	 * the same subscription simultaneously.
	 *
	 * @since 1.0.0
	 * @param int $subscription_id Subscription ID.
	 * @param int $timeout         Lock timeout in seconds (default: 300 = 5 minutes).
	 * @return bool True if lock acquired, false if already locked.
	 */
	function aswc_acquire_payment_lock( $subscription_id, $timeout = 300 ) {
		$lock_key = 'aswc_payment_lock_' . absint( $subscription_id );
		$lock_value = time() + $timeout;

		// Try to set the lock.
		$result = add_option( $lock_key, $lock_value, '', 'no' );

		if ( ! $result ) {
			// Lock exists, check if it's expired.
			$existing_lock = get_option( $lock_key, 0 );
			if ( $existing_lock < time() ) {
				// Lock expired, delete and retry.
				delete_option( $lock_key );
				$result = add_option( $lock_key, $lock_value, '', 'no' );
			}
		}

		if ( $result && class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( 'Payment lock acquired for subscription %d', $subscription_id ) );
		}

		return $result;
	}
}

if ( ! function_exists( 'aswc_release_payment_lock' ) ) {
	/**
	 * Release a transactional lock for subscription payment processing.
	 *
	 * @since 1.0.0
	 * @param int $subscription_id Subscription ID.
	 * @return bool True if lock released.
	 */
	function aswc_release_payment_lock( $subscription_id ) {
		$lock_key = 'aswc_payment_lock_' . absint( $subscription_id );
		$result = delete_option( $lock_key );

		if ( $result && class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( 'Payment lock released for subscription %d', $subscription_id ) );
		}

		return $result;
	}
}

if ( ! function_exists( 'aswc_is_payment_locked' ) ) {
	/**
	 * Check if a payment lock exists for a subscription.
	 *
	 * @since 1.0.0
	 * @param int $subscription_id Subscription ID.
	 * @return bool True if locked.
	 */
	function aswc_is_payment_locked( $subscription_id ) {
		$lock_key = 'aswc_payment_lock_' . absint( $subscription_id );
		$lock_value = get_option( $lock_key, 0 );

		// Check if lock exists and hasn't expired.
		return $lock_value > time();
	}
}

if ( ! function_exists( 'aswc_validate_payment_amount' ) ) {
	/**
	 * Validate subscription payment amount before processing.
	 *
	 * Ensures the amount to be charged matches the expected subscription total
	 * and is within acceptable bounds.
	 *
	 * @since 1.0.0
	 * @param int   $subscription_id Subscription ID.
	 * @param float $amount_to_charge Amount about to be charged.
	 * @return array Array with 'valid' boolean and 'message' string.
	 */
	function aswc_validate_payment_amount( $subscription_id, $amount_to_charge ) {
		$subscription = wc_get_order( $subscription_id );

		if ( ! $subscription ) {
			return array(
				'valid'   => false,
				'message' => 'Subscription not found',
			);
		}

		// Get expected amount from subscription meta.
		$expected_amount = floatval( aswc_get_meta_data( $subscription_id, 'aswc_recurring_total', true ) );

		// Allow small floating point differences (1 cent).
		$difference = abs( $amount_to_charge - $expected_amount );
		$tolerance = 0.01;

		if ( $difference > $tolerance ) {
			// Amount mismatch - log and reject.
			if ( class_exists( 'ASWC_Log' ) ) {
				ASWC_Log::log( sprintf(
					'SECURITY WARNING: Payment amount mismatch for subscription %d. Expected: %s, Attempted: %s',
					$subscription_id,
					wc_price( $expected_amount ),
					wc_price( $amount_to_charge )
				) );
			}

			return array(
				'valid'   => false,
				'message' => sprintf(
					'Payment amount mismatch. Expected %s but got %s',
					wc_price( $expected_amount ),
					wc_price( $amount_to_charge )
				),
			);
		}

		// Validate amount is not negative.
		if ( $amount_to_charge < 0 ) {
			return array(
				'valid'   => false,
				'message' => 'Payment amount cannot be negative',
			);
		}

		// Validate amount doesn't exceed reasonable maximum.
		$max_amount = apply_filters( 'aswc_max_payment_amount', 999999 );
		if ( $amount_to_charge > $max_amount ) {
			return array(
				'valid'   => false,
				'message' => sprintf( 'Payment amount exceeds maximum allowed: %s', wc_price( $max_amount ) ),
			);
		}

		return array(
			'valid'   => true,
			'message' => 'Amount validated successfully',
		);
	}
}

if ( ! function_exists( 'aswc_verify_subscription_ownership' ) ) {
	/**
	 * Verify that current user owns the subscription.
	 *
	 * Reusable function for ownership validation across the plugin.
	 *
	 * @since 1.0.0
	 * @param int  $subscription_id Subscription ID.
	 * @param bool $allow_admin     Whether to allow admin users (default: true).
	 * @return array Array with 'valid' boolean, 'message' string, and 'user_id' int.
	 */
	function aswc_verify_subscription_ownership( $subscription_id, $allow_admin = true ) {
		$current_user_id = get_current_user_id();

		if ( ! $current_user_id ) {
			return array(
				'valid'   => false,
				'message' => __( 'You must be logged in to access subscriptions.', 'advanced-subscriptions-for-woocommerce' ),
				'user_id' => 0,
			);
		}

		// Get subscription.
		$subscription = wc_get_order( $subscription_id );

		if ( ! $subscription ) {
			return array(
				'valid'   => false,
				'message' => __( 'Subscription not found.', 'advanced-subscriptions-for-woocommerce' ),
				'user_id' => $current_user_id,
			);
		}

		// Get subscription owner.
		$subscription_customer_id = $subscription->get_customer_id();

		// Check if user owns subscription.
		if ( $current_user_id === $subscription_customer_id ) {
			return array(
				'valid'   => true,
				'message' => 'User owns subscription',
				'user_id' => $current_user_id,
			);
		}

		// Check if admin (if allowed).
		if ( $allow_admin && current_user_can( 'manage_woocommerce' ) ) {
			return array(
				'valid'   => true,
				'message' => 'Admin user - access granted',
				'user_id' => $current_user_id,
			);
		}

		// Access denied.
		return array(
			'valid'   => false,
			'message' => __( 'You do not have permission to access this subscription.', 'advanced-subscriptions-for-woocommerce' ),
			'user_id' => $current_user_id,
		);
	}
}
