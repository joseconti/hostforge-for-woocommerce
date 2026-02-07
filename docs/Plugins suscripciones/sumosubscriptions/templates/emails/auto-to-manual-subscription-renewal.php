<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<?php if ( $order->has_status( 'pending' ) ) : ?>

	<p>
		<?php
		/* translators: 1: subscription number 2: subscription number 3: pay url 4: due date 5: pending status  */
		printf( wp_kses_post( __( 'Your Subscription #%1$s has been changed to Manual Subscription Renewal because we couldn\'t charge for the Subscription Renewal using your preapproved Payment method. <br>An Invoice has been created for you to renew your Subscription #%2$s. To pay for this invoice, please use the following link: %3$s. Please make the payment on or before <b>%4$s</b>. If payment is not made, Subscription will go to <b>%5$s</b> status', 'sumosubscriptions' ) ), esc_html( sumo_get_subscription_number( $post_id ) ), esc_html( sumo_get_subscription_number( $post_id ) ), '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'pay', 'sumosubscriptions' ) . '</a>', esc_html( $upcoming_mail_date ), esc_html( $upcoming_mail_status ) ) ;
		?>
	</p>

<?php endif ; ?>

<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<h2>
	<?php
	/* translators: 1: subscription number */
	printf( esc_html__( 'Subscription #%s', 'sumosubscriptions' ), esc_html( sumo_get_subscription_number( $post_id ) ) ) ;
	?>
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

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
