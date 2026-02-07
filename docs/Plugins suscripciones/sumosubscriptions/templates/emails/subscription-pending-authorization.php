<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: subscription number 2: pay url 3: due date 4: pending status */
	printf( wp_kses_post( __( 'Your Subscription #%1$s is in Pending Authorization status because we couldn\'t charge your account for Subscription Renewal as your bank have declined the authorization which you have previously given. Please pay using another card or else using any other payment gateway <a href="%2$s">pay</a>. If payment is not made for the Subscription Renewal by <b>%3$s</b>. Your Subscription will move to <b>%4$s</b> status.', 'sumosubscriptions' ) ), esc_html( sumo_get_subscription_number( $post_id ) ), esc_url( $order->get_checkout_payment_url() ), esc_html( $upcoming_mail_date ), esc_html( $upcoming_mail_status ) ) ;
	?>
</p>

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
