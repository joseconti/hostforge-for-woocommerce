<?php //phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase
/**
 * Metabox for Subscription Delivery Shipping
 *
 * @package YITH\Subscription
 * @since   1.0.0
 * @author YITH
 *
 * @var YWSBS_Subscription $subscription Current subscription.
 * @var array              $delivery_schedules Current delivery shipping list.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<div class="ywsbs-subscription-delivery-schedules">
	<?php if ( ! $delivery_schedules ) : ?>
		<p><?php esc_html_e( 'No delivery schedules found for this subscription.', 'yith-woocommerce-subscription' ); ?></p>
		<?php
	else :
		$status = YWSBS_Subscription_Delivery_Schedules()->get_status(); //phpcs:ignore

		?>
		<div class="ywsbs-delivery-schedules-filters-wrapper">
			<label><?php echo esc_html_x( 'Filter for status:', 'Label to filter the delivery schedules by status', 'yith-woocommerce-subscription' ); ?></label>
			<select id="ywsbs-delivery-schedules-status">
				<option
					value=""><?php echo esc_html_x( 'All status', 'Option of a select', 'yith-woocommerce-subscription' ); ?></option>
				<?php foreach ( $status as $key => $single_status ) : ?>
					<option
						value="<?php echo esc_attr( $single_status ); ?>"><?php echo esc_html( $single_status ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<table class="ywsbs-delivery-schedules-table widefat striped">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Shipping on:', 'yith-woocommerce-subscription' ); ?></th>
				<th><?php esc_html_e( 'Status:', 'yith-woocommerce-subscription' ); ?></th>
				<th><?php esc_html_e( 'Shipped on:', 'yith-woocommerce-subscription' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $delivery_schedules as $delivery ) :
				$status_label = YWSBS_Subscription_Delivery_Schedules()->get_status_label( $delivery->status );
				?>
				<tr>
					<td>
						<?php echo esc_html( ywsbs_get_formatted_date( $delivery->scheduled_date, '-' ) ); ?>
					</td>
					<td class="delivery-status" data-id="<?php echo esc_attr( $delivery->id ); ?>">
						<div class="status-label"
							data-value="<?php esc_attr( $delivery->status ); ?>"><?php echo wp_kses_post( $status_label ); ?></div>
						<select class="status-change">
							<?php foreach ( $status as $key => $single_status ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"
									data-label="<?php echo esc_attr( $single_status ); ?>" <?php selected( $delivery->status, $key ); ?>><?php echo esc_html( $single_status ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
					<td class="sent_on">
						<?php echo esc_html( ywsbs_get_formatted_date( $delivery->sent_on, '-' ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>

		</table>
		<div class="yith-delivery-schedules-shipped-confirm" title="<?php esc_html_e( 'You are going to set this item as "Shipped". ', 'yith-woocommerce-subscription' ); ?>" style="display:none;">
			<p><?php printf( wp_kses_post( __( 'This will automatically send a confirmation email to the customer.%sDo you want to continue?', 'yith-woocommerce-subscription' ) ), '<br/>' ); ?></p>
		</div>
	<?php endif; ?>
</div>
