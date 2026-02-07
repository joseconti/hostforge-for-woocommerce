<?php
/**
 * Ticket closed notification email template (sent to customer).
 *
 * Override: copy to theme/hostforge/emails/ticket-closed.php
 *
 * @package HostForge\Templates\Emails
 * @var WP_Post $ticket        Ticket post object.
 * @var WP_User $customer      Customer user object.
 * @var string  $email_heading Email heading text.
 * @var object  $email         WC_Email instance (if available).
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: %s: customer name */
		esc_html__( 'Hello %s,', 'hostforge' ),
		esc_html( $customer->display_name )
	);
	?>
</p>

<p>
	<?php
	printf(
		/* translators: 1: ticket ID, 2: ticket subject */
		esc_html__( 'Your support ticket #%1$d: %2$s has been closed.', 'hostforge' ),
		absint( $ticket->ID ),
		esc_html( $ticket->post_title )
	);
	?>
</p>

<p><?php esc_html_e( 'If you need further assistance, you can reopen this ticket by replying to it from your account.', 'hostforge' ); ?></p>

<p>
	<?php
	$view_url = wc_get_account_endpoint_url( 'support-tickets' );
	$view_url = add_query_arg( 'ticket_id', absint( $ticket->ID ), $view_url );
	?>
	<a href="<?php echo esc_url( $view_url ); ?>" style="display:inline-block; padding:10px 20px; background:#0073aa; color:#ffffff; text-decoration:none; border-radius:3px;">
		<?php esc_html_e( 'View Ticket', 'hostforge' ); ?>
	</a>
</p>

<p><?php esc_html_e( 'Thank you for contacting us.', 'hostforge' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
