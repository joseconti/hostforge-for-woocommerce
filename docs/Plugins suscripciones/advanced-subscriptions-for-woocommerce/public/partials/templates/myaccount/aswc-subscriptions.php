<?php
/**
 * The add show susbcription page.
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    advanced-subscriptions-for-woocommerce
 * @subpackage advanced-subscriptions-for-woocommerce/public
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
	<div class="aswc_account_wrap aswc_account_additional_wrap">
		<?php
		if ( ! empty( $aswc_subscriptions ) && is_array( $aswc_subscriptions ) ) {
			?>
				<table>
					<thead>
						<tr>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr"><?php esc_html_e( 'ID', 'advanced-subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr"><?php esc_html_e( 'Parent ID', 'advanced-subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-status"><span class="nobr"><?php esc_html_e( 'Status', 'advanced-subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr"><?php echo esc_html_e( 'Next Payment', 'advanced-subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr"><?php echo esc_html_e( 'Total', 'advanced-subscriptions-for-woocommerce' ); ?></span></th>
							<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><?php esc_html_e( 'Action', 'advanced-subscriptions-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $aswc_subscriptions as $key => $aswc_subscription ) {
						if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
							$subcription_id = $aswc_subscription;
						} else {
							$subcription_id = $aswc_subscription->ID;
						}
												$parent_order_id = aswc_get_meta_data( $subcription_id, 'aswc_parent_order', true );
						$aswc_wsfw_is_order                      = false;
						if ( function_exists( 'aswc_check_valid_order' ) && ! aswc_check_valid_order( $parent_order_id ) ) {
							$aswc_wsfw_is_order = apply_filters( 'aswc_wsfw_check_parent_order', $aswc_wsfw_is_order, $parent_order_id );
							if ( false === $aswc_wsfw_is_order ) {
								continue;
							}
						}
						?>
						<tr class="aswc_account_row woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order">
							<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number">
								<?php echo esc_html( $subcription_id ); ?>
							</td>
							<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-parent-id">
								<?php
																$parent_order_id = aswc_get_meta_data( $subcription_id, 'aswc_parent_order', true );
								$parent_order                                    = wc_get_order( $parent_order_id );
								?>
									<a target="_blank" href="<?php echo esc_url( $parent_order->get_view_order_url() ); ?>">
									<?php echo '#' . esc_html( $parent_order_id ); ?>
									</a>
							</td>
														<?php $aswc_status = aswc_get_meta_data( $subcription_id, 'aswc_subscription_status', true ); ?>
							<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-status aswc_<?php echo esc_html( $aswc_status ); ?>"><span>
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
							<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date">
						<?php
														$aswc_next_payment_date = aswc_get_meta_data( $subcription_id, 'aswc_next_payment_date', true );
						if ( 'cancelled' === $aswc_status || 'paused' === $aswc_status ) {
								$aswc_next_payment_date = '';
						}
														echo esc_html( aswc_get_the_wordpress_date_format( $aswc_next_payment_date ) );
						?>
							</td>
							<td class="aswc_account_col woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total">
							<?php
							do_action( 'aswc_display_susbcription_recerring_total_account_page', $subcription_id );
							?>
							</td>
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
						<?php
					}
					?>
					</tbody>
				</table>
				<?php
				if ( 1 < $aswc_num_pages ) {
					?>
			<div class="aswc_pagination woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
					<?php if ( 1 !== $aswc_current_page ) { ?>
								<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( 'aswc-subscriptions', $aswc_current_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'advanced-subscriptions-for-woocommerce' ); ?></a>
			<?php } ?>

					<?php if ( intval( $aswc_num_pages ) !== $aswc_current_page ) { ?>
								<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( 'aswc-subscriptions', $aswc_current_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'advanced-subscriptions-for-woocommerce' ); ?></a>
			<?php } ?>
			</div>
		<?php } ?>
		<?php } else { ?>
			<?php esc_html_e( 'You do not have subscriptions.', 'advanced-subscriptions-for-woocommerce' ); ?>
		<?php } ?>
	</div>
