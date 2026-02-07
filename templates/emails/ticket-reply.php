<?php
/**
 * Ticket reply notification email template.
 *
 * Used for both staff-to-customer and customer-to-staff replies.
 *
 * Override: copy to theme/hostforge/emails/ticket-reply.php
 *
 * @package HostForge\Templates\Emails
 * @var WP_Post $ticket        Ticket post object.
 * @var string  $reply_content Reply message content.
 * @var string  $reply_author  Reply author display name.
 * @var bool    $is_staff_reply Whether the reply is from staff.
 * @var WP_User $recipient     Recipient user object.
 * @var string  $email_heading Email heading text.
 * @var object  $email         WC_Email instance (if available).
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: %s: recipient name */
		esc_html__( 'Hello %s,', 'hostforge' ),
		esc_html( $recipient->display_name )
	);
	?>
</p>

<p>
	<?php
	printf(
		/* translators: 1: ticket ID, 2: ticket subject */
		esc_html__( 'There is a new reply on ticket #%1$d: %2$s', 'hostforge' ),
		absint( $ticket->ID ),
		esc_html( $ticket->post_title )
	);
	?>
</p>

<p>
	<strong>
	<?php
	printf(
		/* translators: %s: reply author name */
		esc_html__( '%s wrote:', 'hostforge' ),
		esc_html( $reply_author )
	);
	?>
	</strong>
</p>

<div style="padding:15px; background:#f9f9f9; border:1px solid #e5e5e5; border-left:4px solid #0073aa; margin-bottom:20px;">
	<?php echo wp_kses_post( $reply_content ); ?>
</div>

<p>
	<?php if ( $is_staff_reply ) : ?>
		<?php
		// Recipient is a customer — link to My Account.
		$view_url = wc_get_account_endpoint_url( 'support-tickets' );
		$view_url = add_query_arg( 'ticket_id', absint( $ticket->ID ), $view_url );
		?>
	<?php else : ?>
		<?php
		// Recipient is staff — link to admin.
		$view_url = admin_url( 'admin.php?page=hf-support-desk&action=view&ticket_id=' . absint( $ticket->ID ) );
		?>
	<?php endif; ?>

	<a href="<?php echo esc_url( $view_url ); ?>" style="display:inline-block; padding:10px 20px; background:#0073aa; color:#ffffff; text-decoration:none; border-radius:3px;">
		<?php esc_html_e( 'View Ticket', 'hostforge' ); ?>
	</a>
</p>

<?php
do_action( 'woocommerce_email_footer', $email );
