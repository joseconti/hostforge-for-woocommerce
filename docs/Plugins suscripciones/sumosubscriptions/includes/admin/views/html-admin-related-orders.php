<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

$columns = apply_filters( 'sumosubscriptions_admin_related_orders_table_columns', array(
	'order_id' => __( 'Order Number', 'sumosubscriptions' ),
	'relation' => __( 'Relationship', 'sumosubscriptions' ),
	'date'     => __( 'Date', 'sumosubscriptions' ),
	'status'   => __( 'Status', 'sumosubscriptions' ),
	'total'    => __( 'Total', 'sumosubscriptions' ),
		) ) ;
?>
<div class="sumo_subscription_related_orders">
	<table>
		<thead>
			<tr>
				<?php foreach ( $columns as $column_title ) { ?>
					<th><?php echo esc_html( $column_title ) ; ?></th> 
				<?php } ?>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $related_orders as $_id => $_order ) {
				?>
				<tr>
					<td><a href="<?php echo esc_url( admin_url( "post.php?post={$_id}&action=edit" ) ) ; ?>">#<?php echo esc_html( $_order[ 'order_id' ] ) ; ?></a></td>
					<td><?php echo wp_kses_post( $_order[ 'relation' ] ) ; ?></td>
					<td><?php echo wp_kses_post( $_order[ 'date' ] ) ; ?></td>
					<td>
						<?php
						if ( isset( $_order[ 'status_label' ] ) ) {
							/* translators: 1: order status 2: order status label */
							printf( '<mark class="order-status status-%s"/><span>%s</span></mark>', esc_attr( $_order[ 'status' ] ), esc_attr( $_order[ 'status_label' ] ) ) ;
						} else {
							echo wp_kses_post( $_order[ 'status' ] ) ;
						}
						?>
					</td>
					<td><?php echo wp_kses_post( $_order[ 'total' ] ) ; ?></td>
				</tr>
				<?php
			}
			?>
		</tbody>
	</table>
</div>
