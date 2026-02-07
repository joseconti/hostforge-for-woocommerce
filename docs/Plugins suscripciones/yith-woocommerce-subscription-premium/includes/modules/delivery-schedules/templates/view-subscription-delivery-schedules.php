<?php
/**
 * Subscription delivery schedules details
 *
 * @package YITH\Subscription
 * @since   3.0.0
 * @author YITH
 *
 * @var array $ds An array of delivery schedules.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>
<div class="ywsbs-box ywsbs-subscription-info-box delivery-schedules">
	<h3><?php esc_html_e( 'Your next deliveries', 'yith-woocommerce-subscription' ); ?></h3>
	<table class="my_account_orders shop_table_responsive">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Shipped on:', 'yith-woocommerce-subscription' ); ?></th>
			<th><?php esc_html_e( 'Delivery status:', 'yith-woocommerce-subscription' ); ?></th>
			<th><?php esc_html_e( 'Delivery on:', 'yith-woocommerce-subscription' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $ds as $scheduled ) : ?>
			<tr>
				<td data-title="<?php esc_html_e( 'Shipped on', 'yith-woocommerce-subscription' ); ?>"><?php echo wp_kses_post( ywsbs_get_formatted_date( $scheduled->scheduled_date, '-' ) ); ?></td>
				<td data-title="<?php esc_html_e( 'Delivery status', 'yith-woocommerce-subscription' ); ?>">
					<span class="delivery-status <?php echo esc_attr( $scheduled->status ); ?>"><?php echo wp_kses_post( YWSBS_Subscription_Delivery_Schedules()->get_status_label( $scheduled->status ) ); ?></span>
				</td>
				<td data-title="<?php esc_html_e( 'Delivery on', 'yith-woocommerce-subscription' ); ?>"><?php echo wp_kses_post( ywsbs_get_formatted_date( $scheduled->sent_on, '-' ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
