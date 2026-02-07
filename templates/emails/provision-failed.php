<?php
/**
 * Provisioning failed email template (admin notification).
 *
 * Override: copy to theme/hostforge/emails/provision-failed.php
 *
 * @package HostForge\Templates\Emails
 * @var int    $service_id    Service post ID.
 * @var string $domain        Service domain.
 * @var string $error_message Error details.
 * @var string $server_name   Server name.
 * @var string $panel_type    Panel type.
 * @var string $admin_url     Admin URL for the service.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php esc_html_e( 'A hosting service provisioning has failed and requires attention.', 'hostforge' ); ?></p>

<table cellspacing="0" cellpadding="6" border="1" style="border-collapse:collapse; border:1px solid #e5e5e5; width:100%; margin-bottom:20px;">
	<tr>
		<th style="text-align:left; padding:12px; background:#f8f8f8; width:140px;"><?php esc_html_e( 'Service ID', 'hostforge' ); ?></th>
		<td style="padding:12px;">#<?php echo esc_html( $service_id ); ?></td>
	</tr>
	<?php if ( ! empty( $domain ) ) : ?>
		<tr>
			<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Domain', 'hostforge' ); ?></th>
			<td style="padding:12px;"><?php echo esc_html( $domain ); ?></td>
		</tr>
	<?php endif; ?>
	<?php if ( ! empty( $server_name ) ) : ?>
		<tr>
			<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Server', 'hostforge' ); ?></th>
			<td style="padding:12px;"><?php echo esc_html( $server_name ); ?></td>
		</tr>
	<?php endif; ?>
	<?php if ( ! empty( $panel_type ) ) : ?>
		<tr>
			<th style="text-align:left; padding:12px; background:#f8f8f8;"><?php esc_html_e( 'Panel', 'hostforge' ); ?></th>
			<td style="padding:12px;"><?php echo esc_html( strtoupper( $panel_type ) ); ?></td>
		</tr>
	<?php endif; ?>
	<tr>
		<th style="text-align:left; padding:12px; background:#fce4e4;"><?php esc_html_e( 'Error', 'hostforge' ); ?></th>
		<td style="padding:12px; color:#721c24;"><?php echo esc_html( $error_message ); ?></td>
	</tr>
</table>

<?php if ( ! empty( $admin_url ) ) : ?>
	<p>
		<a href="<?php echo esc_url( $admin_url ); ?>" style="background:#0073aa; color:#fff; padding:10px 20px; text-decoration:none; border-radius:3px; display:inline-block;">
			<?php esc_html_e( 'View Service Details', 'hostforge' ); ?>
		</a>
	</p>
<?php endif; ?>

<p><?php esc_html_e( 'Please investigate the error and retry provisioning manually if needed.', 'hostforge' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
