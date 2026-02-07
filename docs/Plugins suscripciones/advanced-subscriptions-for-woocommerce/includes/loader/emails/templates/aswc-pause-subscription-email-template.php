<?php
/**
 * Paused Email template
 *
 * @link       https://plugins.joseconti.com
 * @since      1.0.0
 *
 * @package    Aswc_Loader
 * @subpackage Aswc_Loader/email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<?php /* translators: %s: search term */ ?>
<p><?php printf( esc_html__( 'A subscription [#%s] has been paused. Their subscription\'s details are as follows:', 'advanced-subscriptions-for-woocommerce' ), esc_html( $aswc_subscription ) ); ?></p>

<?php
aswc_email_subscriptions_details( $aswc_subscription );

do_action( 'woocommerce_email_footer', $email );


