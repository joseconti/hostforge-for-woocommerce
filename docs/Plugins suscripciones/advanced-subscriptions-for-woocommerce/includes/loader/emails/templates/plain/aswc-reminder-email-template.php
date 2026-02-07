<?php
/**
 * Reactivate Email template
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo esc_html( $email_heading ) . "\n\n"; // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<?php /* translators: %s: search term */ ?>
<p><?php printf( esc_html__( 'We will cut your recurring payment for this subscription [#%s]. Their subscription\'s details are as follows:', 'advanced-subscriptions-for-woocommerce' ), esc_html( $aswc_subscription ) ); ?></p>

<?php
$aswc_product_name      = aswc_get_meta_data( $aswc_subscription, 'product_name', true );
$product_qty            = aswc_get_meta_data( $aswc_subscription, 'product_qty', true );
$aswc_next_payment_date = aswc_get_meta_data( $aswc_subscription, 'aswc_next_payment_date', true );

?>
<table>
	<tr>
		<td><?php esc_html_e( 'Product', 'advanced-subscriptions-for-woocommerce' ); ?></td>
		<td><?php echo esc_html( $aswc_product_name ); ?> </td>
	</tr>
	<tr>
		<td> <?php esc_html_e( 'Quantity', 'advanced-subscriptions-for-woocommerce' ); ?> </td>
		<td> <td><?php echo esc_html( $product_qty ); ?> </td> </td>
	</tr>
	<tr>
		<td> <?php esc_html_e( 'Price', 'advanced-subscriptions-for-woocommerce' ); ?> </td>
		<td> <?php do_action( 'aswc_display_susbcription_recerring_total_account_page', $aswc_subscription ); ?> </td>
	</tr>
	<tr>
				<td> <?php esc_html_e( 'Recurring Payment Date', 'advanced-subscriptions-for-woocommerce' ); ?> </td>
								<td> <?php echo esc_html( aswc_date( 'Y-m-d', $aswc_next_payment_date ) ); ?> </td>
	</tr>
</table>
<?php
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped

