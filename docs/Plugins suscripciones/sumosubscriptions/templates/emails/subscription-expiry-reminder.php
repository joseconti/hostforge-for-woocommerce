<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
} // Exit if accessed directly 
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: subscription number 2: expiry date */
	printf( esc_html__( 'Your Subscription #%1$s is going to expire on %2$s.', 'sumosubscriptions' ), esc_html( sumo_get_subscription_number( $post_id ) ), esc_html( sumo_display_subscription_date( get_post_meta( $post_id, 'sumo_get_saved_due_date', true ) ) ) ) ;
	?>
</p>

<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<h2>
	<?php
	/* translators: 1: subscription number */
	printf( esc_html__( 'Subscription #%s', 'sumosubscriptions' ), esc_html( sumo_get_subscription_number( $post_id ) ) ) ;
	?>
	 

	(<time datetime="<?php echo esc_attr( date_i18n( 'c', strtotime( $order->get_date_created() ) ) ) ; ?>"><?php echo esc_html( date_i18n( wc_date_format(), strtotime( $order->get_date_created() ) ) ) ; ?></time>)
</h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
	<thead>
		<tr>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Product', 'sumosubscriptions' ) ; ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Quantity', 'sumosubscriptions' ) ; ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php esc_html_e( 'Price', 'sumosubscriptions' ) ; ?></th>
		</tr>
	</thead>
	<tbody>
		<?php do_action( 'sumosubscriptions_email_order_details', $order, $post_id, $email ) ; ?>
	</tbody>
	<tfoot>
		<?php do_action( 'sumosubscriptions_email_order_meta', $order, $post_id, $email ) ; ?>
	</tfoot>
</table>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
