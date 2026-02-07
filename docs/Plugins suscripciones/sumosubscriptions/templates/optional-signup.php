<?php
/**
 * Optional Signup.
 *
 * This template can be overridden by copying it to yourtheme/sumosubscriptions/optional-signup.php.
 */
defined( 'ABSPATH' ) || exit ;
?>
<div class="sumosubs_subscribe_optional_trial_r_signup_fields">
	<input id="sumosubs_subscribe_optional_signup" 
		   data-product_id="<?php echo esc_attr( $product_id ) ; ?>" 
		   data-product_type="<?php echo esc_attr( $product->get_type() ) ; ?>" 
		   data-plan="set_signup_fee" 
		   type="checkbox" 
		   name="sumosubs_optional_signup_subscribed" 
		   value="yes"
		   >
	&nbsp;
	<?php echo wp_kses_post( $label ) ; ?>
</div>
