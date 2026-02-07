<?php
/**
 * New ticket notification email template (sent to staff).
 *
 * Override: copy to theme/hostforge/emails/ticket-new-staff.php
 *
 * @package HostForge\Templates\Emails
 * @var WP_Post $ticket     Ticket post object.
 * @var WP_User $customer   Customer user object.
 * @var string  $department  Ticket department.
 * @var string  $priority    Ticket priority.
 * @var string  $email_heading Email heading text.
 * @var object  $email       WC_Email instance (if available).
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php esc_html_e( 'A new support ticket has been submitted.', 'hostforge' ); ?></p>

<table cellspacing="0" cellpadding="6" border="1" style="border-collapse:collapse; border:1px solid #e5e5e5; width:100%; margin-bottom:20px;">
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8; width:140px;"><?php esc_html_e( 'Ticket', 'hostforge' ); ?></th>
		<td style="padding:12px;">
			<?php
			printf(
				/* translators: 1: ticket ID, 2: ticket subject */
				esc_html__( '#%1$d: %2$s', 'hostforge' ),
				absint( $ticket->ID ),
				esc_html( $ticket->post_title )
			);
			?>
		</td>
	</tr>
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Customer', 'hostforge' ); ?></th>
		<td style="padding:12px;">
			<?php
			printf(
				/* translators: 1: customer name, 2: customer email */
				esc_html__( '%1$s (%2$s)', 'hostforge' ),
				esc_html( $customer->display_name ),
				esc_html( $customer->user_email )
			);
			?>
		</td>
	</tr>
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Department', 'hostforge' ); ?></th>
		<td style="padding:12px;"><?php echo esc_html( $department ); ?></td>
	</tr>
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Priority', 'hostforge' ); ?></th>
		<td style="padding:12px;"><?php echo esc_html( $priority ); ?></td>
	</tr>
</table>

<h3 style="margin:20px 0 10px;"><?php esc_html_e( 'Message:', 'hostforge' ); ?></h3>

<div style="padding:15px; background:#f9f9f9; border:1px solid #e5e5e5; margin-bottom:20px;">
	<?php echo wp_kses_post( $ticket->post_content ); ?>
</div>

<p>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=hf-support-desk&action=view&ticket_id=' . absint( $ticket->ID ) ) ); ?>" style="display:inline-block; padding:10px 20px; background:#0073aa; color:#ffffff; text-decoration:none; border-radius:3px;">
		<?php esc_html_e( 'View Ticket', 'hostforge' ); ?>
	</a>
</p>

<?php
do_action( 'woocommerce_email_footer', $email );
