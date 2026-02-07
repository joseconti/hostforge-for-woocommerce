<?php
/**
 * Related Subscriptions.
 *
 * This template can be overridden by copying it to yourtheme/sumosubscriptions/related-subscriptions.php.
 * @since 15.6.0
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="sumo_related_subscriptions">
		<?php 
		/**
		 * Before related subscriptions table.
		 * 
		 * @since 15.6.0
		 */
		do_action( 'sumosubscriptions_before_related_subscriptions_table' ); 
		if( $has_subscription ) : ?>
		<h3><?php esc_html_e( 'Related Subscriptions', 'sumosubscriptions' ); ?></h3>
		<table class="orders-details-table woocommerce-orders-table  shop_table shop_table_responsive">
			<thead>
				<tr>
					<th class="sumo-subscription-id woocommerce-orders-table__header woocommerce-orders-table__header-subscription-id"><span class="nobr"><?php esc_html_e( 'ID', 'sumosubscriptions' ); ?></span></th>
					<th class="sumo-subscription-product woocommerce-orders-table__header woocommerce-orders-table__header-subscription-product"><span class="nobr"><?php esc_html_e( 'Product', 'sumosubscriptions' ); ?></span></th>
					<th class="sumo-subscription-plan woocommerce-orders-table__header woocommerce-orders-table__header-subscription-plan"><span class="nobr"><?php esc_html_e( 'Plan', 'sumosubscriptions' ); ?></span></th>
					<th class="sumo-subscription-status woocommerce-orders-table__header woocommerce-orders-table__header-subscription-status"><span class="nobr"><?php esc_html_e( 'Status', 'sumosubscriptions' ); ?></span></th>
					<th class="sumo-subscription-actions woocommerce-orders-table__header woocommerce-orders-table__header-subscription-actions">&nbsp;</th>
				</tr>
			</thead>			
			<tbody>
				<?php
				foreach ( $subscriptions as $subscription_id ) :
					?>
					<tr class="woocommerce-orders-table__row sumo-subscription woocommerce-orders-table__row--status-<?php echo esc_attr( strtolower( get_post_meta( $subscription_id, 'sumo_get_status', true ) ) ); ?>">
						<td class="sumo-subscription-id woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-id" data-title="<?php esc_attr_e( 'ID', 'sumosubscriptions' ); ?>">
							<?php echo wp_kses_post( sumo_display_subscription_ID( $subscription_id ) ); ?>
						</td>
						<td class="sumo-subscription-product woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-product" data-title="<?php esc_attr_e( 'Product', 'sumosubscriptions' ); ?>">
							<?php echo wp_kses_post( sumo_display_subscription_name( $subscription_id, false, true ) ); ?>
						</td>
						<td class="sumo-subscription-plan woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-plan" data-title="<?php esc_attr_e( 'Plan', 'sumosubscriptions' ); ?>">
							<?php echo wp_kses_post( sumo_display_subscription_plan( $subscription_id ) ); ?>
							<?php
							$subscription_plan = sumo_get_subscription_plan( $subscription_id, 0, 0, true );
							if ( SUMOSubs_Coupons::subscription_contains_recurring_coupon( $subscription_plan ) ) {
								$parent_order = wc_get_order( get_post_meta( $subscription_id, 'sumo_get_parent_order_id', true ) );
								$currency     = $parent_order ? $parent_order->get_currency() : '';

								echo '<p>' . wp_kses_post( SUMOSubs_Coupons::get_recurring_discount_amount_to_display( $subscription_plan['subscription_discount']['coupon_code'], $subscription_plan['subscription_fee'], $subscription_plan['subscription_product_qty'], $currency, $subscription_id ) ) . '</p>';
							}
							?>
						</td>
						<td class="sumo-subscription-status woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-status" data-title="<?php esc_attr_e( 'Status', 'sumosubscriptions' ); ?>">
							<?php echo wp_kses_post( sumo_display_subscription_status( $subscription_id ) ); ?>
						</td>
						<td class="sumo-subscription-actions woocommerce-orders-table__cell woocommerce-orders-table__cell-subscription-actions">
							<a href="<?php echo esc_url( sumo_get_subscription_endpoint_url( $subscription_id ) ); ?>" class="woocommerce-button button view"><?php esc_html_e( 'View', 'sumosubscriptions' ); ?></a>
							<?php do_action( 'woocommerce_my_sumo_subscriptions_actions', $subscription_id ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<?php
		/**
		 * After related subscriptions table.
		 * 
		 * @since 15.6.0
		 */
		 do_action( 'sumosubscriptions_after_related_subscriptions_table' ); ?>		
</div>
