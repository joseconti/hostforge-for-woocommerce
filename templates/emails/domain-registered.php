<?php
/**
 * Email template: Domain Registered.
 *
 * Sent to the customer when a domain has been successfully registered.
 *
 * @package HostForge
 * @var string $domain_name       Domain name.
 * @var string $customer_name     Customer display name.
 * @var string $registrar         Registrar name.
 * @var string $registration_date Registration date (formatted).
 * @var string $expiry_date       Expiry date (formatted).
 * @var string $nameservers       Comma-separated nameservers.
 * @var string $manage_url        URL to My Account > My Domains > detail.
 * @var object $email             WC_Email object.
 * @var string $email_heading     Email heading.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
	<?php
	printf(
		/* translators: %s: customer first name */
		esc_html__( 'Hi %s,', 'hostforge' ),
		esc_html( $customer_name )
	);
	?>
</p>

<p>
	<?php
	printf(
		/* translators: %s: domain name */
		esc_html__( 'Your domain %s has been successfully registered!', 'hostforge' ),
		'<strong>' . esc_html( $domain_name ) . '</strong>'
	);
	?>
</p>

<h2 style="color: #7f54b3; display: block; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 18px; font-weight: bold; line-height: 130%; margin: 0 0 18px; text-align: left;">
	<?php esc_html_e( 'Domain Details', 'hostforge' ); ?>
</h2>

<table cellspacing="0" cellpadding="6" border="1" style="border: 1px solid #e5e5e5; border-collapse: collapse; width: 100%; margin-bottom: 20px;">
	<tbody>
		<tr>
			<th style="text-align: left; padding: 12px; background: #f8f8f8; border: 1px solid #e5e5e5;">
				<?php esc_html_e( 'Domain Name', 'hostforge' ); ?>
			</th>
			<td style="text-align: left; padding: 12px; border: 1px solid #e5e5e5;">
				<strong><?php echo esc_html( $domain_name ); ?></strong>
			</td>
		</tr>
		<tr>
			<th style="text-align: left; padding: 12px; background: #f8f8f8; border: 1px solid #e5e5e5;">
				<?php esc_html_e( 'Registrar', 'hostforge' ); ?>
			</th>
			<td style="text-align: left; padding: 12px; border: 1px solid #e5e5e5;">
				<?php echo esc_html( $registrar ); ?>
			</td>
		</tr>
		<tr>
			<th style="text-align: left; padding: 12px; background: #f8f8f8; border: 1px solid #e5e5e5;">
				<?php esc_html_e( 'Registration Date', 'hostforge' ); ?>
			</th>
			<td style="text-align: left; padding: 12px; border: 1px solid #e5e5e5;">
				<?php echo esc_html( $registration_date ); ?>
			</td>
		</tr>
		<tr>
			<th style="text-align: left; padding: 12px; background: #f8f8f8; border: 1px solid #e5e5e5;">
				<?php esc_html_e( 'Expiry Date', 'hostforge' ); ?>
			</th>
			<td style="text-align: left; padding: 12px; border: 1px solid #e5e5e5;">
				<?php echo esc_html( $expiry_date ); ?>
			</td>
		</tr>
		<?php if ( ! empty( $nameservers ) ) : ?>
		<tr>
			<th style="text-align: left; padding: 12px; background: #f8f8f8; border: 1px solid #e5e5e5;">
				<?php esc_html_e( 'Nameservers', 'hostforge' ); ?>
			</th>
			<td style="text-align: left; padding: 12px; border: 1px solid #e5e5e5;">
				<?php echo esc_html( $nameservers ); ?>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>

<p>
	<?php esc_html_e( 'You can manage your domain (DNS records, nameservers, auto-renewal) from your account:', 'hostforge' ); ?>
</p>

<p style="margin: 20px 0;">
	<a href="<?php echo esc_url( $manage_url ); ?>"
	   style="background-color: #7f54b3; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
		<?php esc_html_e( 'Manage Domain', 'hostforge' ); ?>
	</a>
</p>

<?php
do_action( 'woocommerce_email_footer', $email );
