<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
	/* translators: 1: subscription number */
	printf( esc_html__( 'Your Subscription #%s is currently Paused', 'sumosubscriptions' ), esc_html( sumo_get_subscription_number( $post_id ) ) ) ;

	if ( ! empty( $_REQUEST[ 'auto_resume_on' ] ) ) {
		/* translators: 1: resume scheduled date */
		printf( wp_kses_post( __( ' your Subscription will be automatically resume on <b>%s</b>.', 'sumosubscriptions' ) ), esc_html( wc_clean( wp_unslash( $_REQUEST[ 'auto_resume_on' ] ) ) ) ) ;
	}
	?>
</p>

<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<p><?php esc_html_e( 'Subscription Details are shown below for your reference', 'sumosubscriptions' ) ; ?></p>

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

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<?php do_action( 'woocommerce_email_footer', $email ) ; ?>
