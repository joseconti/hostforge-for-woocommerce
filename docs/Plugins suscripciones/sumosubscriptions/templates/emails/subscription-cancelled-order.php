<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ;
} // Exit if accessed directly 
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<p>
	<?php
       if ( 'yes' === get_option( 'sumosubs_restrict_outofstock_product' ) && sumosubs_is_subscription_product_outofstock( $post_id ) ) {
            /* translators: 1: subscription number */
            printf( esc_html__( 'Your Subscription #%s has been Cancelled since the product is Out of Stock.', 'sumosubscriptions' ), esc_html( sumo_get_subscription_number( $post_id ) ) ) . "\n\n";
        } else {
            /* translators: 1: subscription number */	
            printf( esc_html__( 'Your Subscription #%s has been Cancelled.', 'sumosubscriptions' ), esc_html( sumo_get_subscription_number( $post_id ) ) ) ;
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
