<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ) ; ?>

<?php if ( $admin_template ) { ?>
	<p>
		<?php
		/* translators: 1: billing first name 2: billing last name */
		printf( esc_html__( 'You have received a Subscription order from %1$s %2$s. The Subscription order is as follows:', 'sumosubscriptions' ), esc_html( $order->get_billing_first_name() ), esc_html( $order->get_billing_last_name() ) ) ;
		?>
	</p>
<?php } else { ?>
	<p>
		<?php
		/* translators: 1: blog name */
		printf( esc_html__( 'You have placed a new Subscription order on %s. The Subscription order is as follows:', 'sumosubscriptions' ), esc_html( get_option( 'blogname' ) ) ) ;
		?>
	</p>
<?php } ?>

<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text, $email ) ; ?>

<?php if ( $admin_template ) { ?>
	<h2>
		<a class="link" href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) ; ?>">
			<?php
			/* translators: 1: order number */
			printf( esc_html__( 'Order #%s', 'sumosubscriptions' ), esc_html( $order->get_order_number() ) ) ;
			?>
		</a> 

		(<time datetime="<?php echo esc_attr( date_i18n( 'c', strtotime( $order->get_date_created() ) ) ) ; ?>"><?php echo esc_html( date_i18n( wc_date_format(), strtotime( $order->get_date_created() ) ) ) ; ?></time>)
	</h2>
<?php } else { ?>
	<h2>
		<?php
		/* translators: 1: order number */
		printf( esc_html__( 'Order #%s', 'sumosubscriptions' ), esc_html( $order->get_order_number() ) ) ;
		?>
	</h2>
<?php } ?>

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
