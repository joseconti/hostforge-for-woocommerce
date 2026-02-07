<?php
/**
 * Gateway Payment Mode.
 *
 * This template can be overridden by copying it to yourtheme/sumosubscriptions/gateway-payment-mode.php.
 */
defined( 'ABSPATH' ) || exit ;
?>
<div class="sumosubs_payment_mode_switcher">
	<input type="checkbox" id="<?php echo esc_attr( $gateway_id ) ; ?>_auto_payment_mode_enabled" name="<?php echo esc_attr( $gateway_id ) ; ?>_auto_payment_mode_enabled" value="yes"/>
	<label for="<?php echo esc_attr( $gateway_id ) ; ?>_auto_payment_mode_enabled"><?php esc_html_e( 'Enable Automatic Payments', 'sumosubscriptions' ) ; ?></label>
</div>
