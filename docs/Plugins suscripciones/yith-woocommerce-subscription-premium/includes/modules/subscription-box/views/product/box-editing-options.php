<?php
/**
 * Subscription boc product price options template
 *
 * @since 4.0.0
 * @package YITH\Subscription
 * @var string $email_day_schedule The number of days for scheduled email.
 * @var string $box_editing_until The number of days for let customer edit box content.
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

?>

<div class="ywsbs-subscription-box-editing-options show_if_ywsbs-subscription-box">

	<div class="ywsbs-product-metabox-field">
		<label for="_ywsbs_box_email_day_schedule"><?php esc_html_e( 'Send email related to next box content', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<input type="number" id="_ywsbs_box_email_day_schedule" name="_ywsbs_box_email_day_schedule" class="ywsbs-short" min="1" step="1" value="<?php echo esc_attr( $email_day_schedule ); ?>"/>
				<span class="box-email-day-schedule"><?php esc_html_e( 'day(s) before the renewal date', 'yith-woocommerce-subscription' ); ?></span>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Choose when to send the email to inform your customers about their next box content.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<?php if ( 'yes' === get_option( 'ywsbs_subscription_box_editable', 'yes' ) ) : ?>

		<div class="ywsbs-product-metabox-field">
			<label for="_ywsbs_box_editing_until"><?php esc_html_e( 'Allow box editing up until', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<input type="number" id="_ywsbs_box_editing_until" name="_ywsbs_box_editing_until" class="ywsbs-short" min="1" step="1" value="<?php echo esc_attr( $box_editing_until ); ?>" />
					<span class="box-editing-until"><?php esc_html_e( 'day(s) before the renewal date', 'yith-woocommerce-subscription' ); ?></span>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Choose how long before the renewal date customers can edit the box content from their accounts.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

	<?php endif; ?>
</div>
