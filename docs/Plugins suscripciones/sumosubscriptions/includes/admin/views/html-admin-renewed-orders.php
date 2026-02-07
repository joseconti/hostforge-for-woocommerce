<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>
<div class="sumo_subscription_related_orders">
	<table>
		<thead>
			<tr>
				<td><?php esc_html_e( 'Order Number', 'sumosubscriptions' ) ; ?></td>
				<td><?php esc_html_e( 'Order Status Updated On', 'sumosubscriptions' ) ; ?></td>
			</tr>
		</thead>
		<?php
		if ( ! empty( $renewal_orders ) ) {
			foreach ( $renewal_orders as $renewal_order_id ) :
				$renewal_order = wc_get_order( $renewal_order_id ) ;
				if ( $renewal_order && sumosubs_is_order_paid( $renewal_order ) ) :
					?>
					<tr>
						<td>
							<?php echo '<a href=post.php?post=' . esc_attr( $renewal_order_id ) . '&action=edit>#' . esc_html( $renewal_order_id ) . '</a><br>' ; ?>
						</td>
						<td>
							<?php echo esc_html( sumo_display_subscription_date( $renewal_order->get_date_modified()->date_i18n( 'Y-m-d H:i:s' ) ) ) . '<br>' ; ?>
						</td>
					</tr>
					<?php
				endif ;
			endforeach ;
		}
		?>
	</table>
</div>
<?php
