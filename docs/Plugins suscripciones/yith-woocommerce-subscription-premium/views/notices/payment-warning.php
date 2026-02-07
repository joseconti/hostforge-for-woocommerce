<?php
/**
 * Warning notice when no valid payment method detected
 *
 * @since   3.6.0
 * @package YITH\Subscription
 * @var array $supported_gateways An array of supported gateways.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<div id="ywsbs-payment-methods-warning" class="yith-plugin-ui">
	<div class="ywsbs-payment-methods-warning__icon">
		<svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<path clip-rule="evenodd" fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z"></path>
		</svg>
	</div>
	<div class="ywsbs-payment-methods-warning__content">
		<h2><?Php echo esc_html__( "Please note: you don't have any payment options available for supporting automatic recurring payments.", 'yith-woocommerce-subscription' ); ?></h2>
		<p>
			<?php echo esc_html__( 'If you want to enable the automatic subscription charges, you must enable a valid payment method.', 'yith-woocommerce-subscription' ); ?>
			<a href="https://docs.yithemes.com/yith-woocommerce-subscription/premium-settings/automatic-payment-methods/" class="open-modal" target="_blank">
				<?php echo esc_html__( 'Read more about it', 'yith-woocommerce-subscription' ); ?> >
			</a>
		</p>
	</div>
</div>

<script type="text/template" id="tmpl-ywsbs-payment-methods-warning-modal">

	<div class="ywsbs-payment-methods-warning-modal__icon">
		<svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<path clip-rule="evenodd" fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z"></path>
		</svg>
	</div>
	<h2><?php echo esc_html__( "You don't have any payment options available for supporting automatic recurring payments", 'yith-woocommerce-subscription' ); ?></h2>
	<p><?php echo wp_kses_post( __( 'The plugin supports several automatic payment methods, and <b>you need at least one of the above</b> if you want your customers to get charged automatically when each recurring payment is due:', 'yith-woocommerce-subscription' ) ); ?></p>
	<ul>
		<?php foreach ( $supported_gateways as $gateway_id => $gateway ) : ?>
			<li>- <?php echo esc_html( $gateway['title'] ?? $gateway_id ); ?></li>
		<?php endforeach; ?>
	</ul>
	<p><?php echo esc_html__( 'To understand how this works, we suggest you read the plugin documentation.', 'yith-woocommerce-subscription' ); ?></p>
	<a href="https://docs.yithemes.com/yith-woocommerce-subscription/premium-settings/automatic-payment-methods/" class="yith-plugin-fw__button--secondary" target="_blank">
		<?php echo esc_html__( 'Read our documentation', 'yith-woocommerce-subscription' ); ?>
	</a>
	<a href="#" class="ywsbs-payment-methods-warning-modal__close">
		<?php echo esc_html__( 'Got it, close', 'yith-woocommerce-subscription' ); ?>
	</a>
</script>