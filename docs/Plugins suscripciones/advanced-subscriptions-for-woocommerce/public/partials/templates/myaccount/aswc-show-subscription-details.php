<?php
/**
 * The add new payment.
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! function_exists( 'aswc_cancel_url' ) ) {
		/**
		 * Generates the cancel URL.
		 *
		 * @name aswc_cancel_url
		 * @param int    $aswc_subscription_id Subscription ID.
		 * @param string $aswc_status          Subscription status.
		 * @since 1.0.0
		 */
	function aswc_cancel_url( $aswc_subscription_id, $aswc_status ) {

			$aswc_link = add_query_arg(
				array(
					'aswc_subscription_id'     => $aswc_subscription_id,
					'aswc_subscription_status' => $aswc_status,
				)
			);
			$aswc_link = wp_nonce_url( $aswc_link, $aswc_subscription_id . $aswc_status );

			return $aswc_link;
	}
}

?>
<div class="aswc_details_wrap">
	<div class="aswc_account_additional_wrap">
		<h3><?php esc_html_e( 'Subscription Details', 'advanced-subscriptions-for-woocommerce' ); ?></h3>
		<table class="shop_table aswc_details">
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Status', 'advanced-subscriptions-for-woocommerce' ); ?></td>
									<?php $aswc_status = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_status', true ); ?>
					<td class="woocommerce-orders-table__cell-order-status <?php echo esc_html( 'aswc_' . $aswc_status ); ?>"><span>
					<?php
					if ( 'active' === $aswc_status ) {
						$aswc_status = esc_html__( 'active', 'advanced-subscriptions-for-woocommerce' );
					} elseif ( 'on-hold' === $aswc_status ) {
						$aswc_status = esc_html__( 'on-hold', 'advanced-subscriptions-for-woocommerce' );
					} elseif ( 'cancelled' === $aswc_status ) {
						$aswc_status = esc_html__( 'cancelled', 'advanced-subscriptions-for-woocommerce' );
					} elseif ( 'paused' === $aswc_status ) {
						$aswc_status = esc_html__( 'paused', 'advanced-subscriptions-for-woocommerce' );
					} elseif ( 'pending' === $aswc_status ) {
						$aswc_status = esc_html__( 'pending', 'advanced-subscriptions-for-woocommerce' );
					} elseif ( 'expired' === $aswc_status ) {
						$aswc_status = esc_html__( 'expired', 'advanced-subscriptions-for-woocommerce' );
					}
						echo esc_html( $aswc_status );
					?>
					</span>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Created', 'advanced-subscriptions-for-woocommerce' ); ?></td>
					<td>
					<?php
											$aswc_schedule_start = aswc_get_meta_data( $aswc_subscription_id, 'aswc_schedule_start', true );
											echo esc_html( aswc_get_the_wordpress_date_format( $aswc_schedule_start ) );
					?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Next Payment', 'advanced-subscriptions-for-woocommerce' ); ?></td>
					
					<td>
					<?php
											$aswc_next_payment_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_next_payment_date', true );
                                        if ( 'cancelled' === $aswc_status ) {
                                                $aswc_next_payment_date = '';
                                                $aswc_susbcription_end  = '';
                                                $aswc_recurring_total   = '---';
                                        } elseif ( 'paused' === $aswc_status ) {
                                                $aswc_next_payment_date = '';
                                        }
											echo esc_html( aswc_get_the_wordpress_date_format( $aswc_next_payment_date ) );
					?>
					</td>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Expiry', 'advanced-subscriptions-for-woocommerce' ); ?></td>
					<td>
					<?php
											$aswc_subscription_expire_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_susbcription_end', true );
					if ( 0 === $aswc_subscription_expire_date ) {
							$aswc_subscription_expire_date = '---';
							echo esc_html( $aswc_subscription_expire_date );
					} else {

											echo esc_html( aswc_get_the_wordpress_date_format( $aswc_subscription_expire_date ) );
					}
					?>
					</td>
				</tr>
				<?php
							$aswc_trail_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_susbcription_trial_end', true );

				if ( ! empty( $aswc_trail_date ) ) {
					?>
					<tr>
						<td><?php esc_html_e( 'Trial End Date', 'advanced-subscriptions-for-woocommerce' ); ?></td>
						<td>
						<?php
													echo esc_html( aswc_get_the_wordpress_date_format( $aswc_trail_date ) );
						?>
						</td>
					</tr>
					<?php
				}


				if ( 'cancel' !== $aswc_status ) {
					?>
					<tr>
						<td><?php esc_html_e( 'Next Recurring', 'advanced-subscriptions-for-woocommerce' ); ?></td>
						<td>
                                                        <?php
                                                                                                        $aswc_next_payment_date = aswc_get_meta_data( $aswc_subscription_id, 'aswc_next_payment_date', true );
                                                        if ( 'paused' === $aswc_status ) {
                                                                $aswc_next_payment_date = '';
                                                        }
                                                        if ( $aswc_next_payment_date ) {
																$time_difference = (int) $aswc_next_payment_date - current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp

								// Convert the difference from seconds to days.
								$days_left = ceil( $time_difference / ( 60 * 60 * 24 ) );
								if ( $days_left > 1 ) {
									$day_text = esc_attr__( 'Days', 'advanced-subscriptions-for-woocommerce' );
									echo esc_attr( $days_left . ' ' . $day_text );
								} else {
									echo esc_attr__( 'Tomorrow', 'advanced-subscriptions-for-woocommerce' );
								}
							} else {
								echo esc_attr( '---' );
							}
							?>
						</td>
					</tr>
					<?php
				}
				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$subscription = new ASWC_Subscription( $aswc_subscription_id );
				} else {
					$subscription = wc_get_order( $aswc_subscription_id );
				}
				$aswc_next_payment_date = $subscription->get_payment_method();
				$get_shipping_total     = $subscription->get_shipping_total();
				if ( empty( $aswc_next_payment_date ) ) {
					$subscription         = wc_get_order( $aswc_subscription_id );
					$aswc_add_payment_url = wp_nonce_url( add_query_arg( array( 'aswc_add_payment_method' => $aswc_subscription_id ), $subscription->get_checkout_payment_url() ) );
					?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $aswc_add_payment_url ); ?>" class="button aswc_add_payment_url"><?php esc_html_e( 'Add Payment Method', 'advanced-subscriptions-for-woocommerce' ); ?></a>
						</td>
					</tr>
					<?php
				}
				?>
<tr>
<td><?php esc_html_e( 'Actions', 'advanced-subscriptions-for-woocommerce' ); ?></td>
<td class="aswc_actions">
<?php do_action( 'aswc_order_details_html_before_cancel', $aswc_subscription_id ); ?>
<?php
$aswc_cancel_subscription = get_option( 'aswc_cancel_subscription_for_customer', '' );
$aswc_cancel_subscription = apply_filters( 'aswc_customer_cancel_button', $aswc_cancel_subscription, $aswc_subscription_id );
if ( aswc_is_true( $aswc_cancel_subscription ) ) {
	$aswc_status = aswc_get_meta_data( $aswc_subscription_id, 'aswc_subscription_status', true );
	if ( 'active' === $aswc_status ) {
		$aswc_cancel_url = aswc_cancel_url( $aswc_subscription_id, $aswc_status );
		?>
<a href="<?php echo esc_url( $aswc_cancel_url ); ?>" class="button aswc_cancel_subscription"><?php esc_html_e( 'Cancel', 'advanced-subscriptions-for-woocommerce' ); ?></a>
		<?php
	}
}
?>
<?php do_action( 'aswc_order_details_html_after_cancel_button', $aswc_subscription_id ); ?>
<?php do_action( 'aswc_order_details_html_after_cancel', $aswc_subscription_id ); ?>
</td>
</tr>
				<?php
				do_action( 'aswc_subscription_details_html', $aswc_subscription_id );
				?>
			</tbody>
		</table>
	</div>
	<div class="aswc_account_additional_wrap">
		<h3><?php esc_html_e( 'Subscription Order Details', 'advanced-subscriptions-for-woocommerce' ); ?></h3>
		<table class="shop_table aswc_order_details">
			<tbody>
				<tr>
					<td>
						<?php esc_html_e( 'Product Name', 'advanced-subscriptions-for-woocommerce' ); ?>
					</td>
					<td>
						<?php
													$aswc_product_name = aswc_get_meta_data( $aswc_subscription_id, 'product_name', true );
													$product_qty       = aswc_get_meta_data( $aswc_subscription_id, 'product_qty', true );

						if ( is_array( $aswc_product_name ) ) {
							$product_name = implode( ', ', $product_name );
						}
							echo esc_html( $aswc_product_name ) . ' x ' . esc_html( $product_qty );
							do_action( 'aswc_product_details_html', $aswc_subscription_id );
						?>
						
					</td>
				</tr>
				<tr>
					<td>
						<?php esc_html_e( 'Subtotal', 'advanced-subscriptions-for-woocommerce' ); ?>
					</td>
					<td>
					<?php
											$price = aswc_get_meta_data( $aswc_subscription_id, 'line_subtotal', true );
						echo wp_kses_post( wc_price( $price ) );
					?>
					</td>
				</tr>
				<?php

				$tax_total = $subscription->get_total_tax();
				if ( $tax_total > 0 ) {
					?>
					<tr>
						<td>
							<?php esc_html_e( 'Tax', 'advanced-subscriptions-for-woocommerce' ); ?>
						</td>
						<td>
						<?php
							echo wp_kses_post( wc_price( $tax_total ) );
						?>
						</td>
					</tr>
					<?php

				}
				if ( $get_shipping_total ) {
					?>
					<tr>
						<td>
							<?php esc_html_e( 'Shipping', 'advanced-subscriptions-for-woocommerce' ); ?>
						</td>
						<td>
						<?php
							echo wp_kses_post( wc_price( $get_shipping_total ) );
						?>
						</td>
					</tr>
					<?php
				}
				?>

				<tr>
					<td>
						<strong><?php esc_html_e( 'Total', 'advanced-subscriptions-for-woocommerce' ); ?></strong>
					</td>
					<td>
					<strong>
					<?php
						do_action( 'aswc_display_susbcription_recerring_total_account_page', $aswc_subscription_id );
					?>
					</strong>
					</td>
				</tr>
				<?php
							$aswc_renewal_order_data = aswc_get_meta_data( $aswc_subscription_id, 'aswc_renewal_order_data', true );
				if ( $aswc_renewal_order_data ) {
					$aswc_points_earned = 0;
					if ( ! empty( $aswc_renewal_order_data ) ) {
						foreach ( $aswc_renewal_order_data as $key => $value ) {
													$aswc_wpr_subscription_renewal_awarded_points = aswc_get_meta_data( $value, 'aswc_wpr_subscription_renewal_awarded_points', true );
							if ( $aswc_wpr_subscription_renewal_awarded_points ) {
								$aswc_points_earned += $aswc_wpr_subscription_renewal_awarded_points;
							}
						}
						if ( $aswc_points_earned ) {
							?>
							<tr>
								<td>
									<strong><?php esc_html_e( 'Total Subscription Points Collected', 'advanced-subscriptions-for-woocommerce' ); ?></strong>
								</td>
								<td>
									<?php echo esc_html( $aswc_points_earned ); ?>
								</td>
							</tr>
							<?php
						}
					}
				}
				?>
			</tbody>
		</table>
	</div>
<?php do_action( 'aswc_after_subscription_details', $aswc_subscription_id ); ?>
</div>

