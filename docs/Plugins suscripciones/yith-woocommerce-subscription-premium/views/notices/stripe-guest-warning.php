<?php
/**
 * Warning notice when no valid payment method detected
 *
 * @since   3.6.0
 * @package YITH WooCommerce Subscription
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="ywsbs-payment-methods-warning" class="yith-plugin-ui">
	<div class="ywsbs-payment-methods-warning__icon">
		<svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<path clip-rule="evenodd" fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z"></path>
		</svg>
	</div>
	<div class="ywsbs-payment-methods-warning__content">
		<h2><?Php echo esc_html__( "Please note: Using Stripe as a payment method and guest checkout doesn't allow handling recurring payments.", 'yith-woocommerce-subscription' ); ?></h2>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					// translators: %1$s and %2$s are the anchor tag open and close html.
					__( 'To manage the subscription recurring charges, you must enable the option to force account registration in %1$sYITH > Subscription > General settings%2$s.', 'yith-woocommerce-subscription' ),
					'<a href="' . ywsbs_get_admin_panel_page_url( 'general' ) . '">',
					'</a>'
				)
			);
			?>
		</p>
	</div>
</div>