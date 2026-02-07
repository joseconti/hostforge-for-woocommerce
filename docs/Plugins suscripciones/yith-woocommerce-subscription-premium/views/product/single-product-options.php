<?php
/**
 * Single product template options
 *
 * @package YITH\Subscription
 * @since   2.0.0
 * @author  YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Vars used on this template.
 *
 * @var WC_Product $product                               The product object instance.
 * @var string     $_ywsbs_price_time_option              Period (days, weeks ..).
 * @var int        $_ywsbs_price_is_per                   Duration.
 * @var array      $max_lengths                           Limit of time foreach period.
 * @var int        $_ywsbs_max_length                     Max duration of the subscription.
 * @var bool       $_ywsbs_enable_trial                   Check is enable the trial.
 * @var int        $_ywsbs_trial_per                      Duration of the trial.
 * @var int        $_ywsbs_trial_time_option              Period (days, weeks ..).
 * @var bool       $_ywsbs_enable_fee                     Check if enable the fee
 * @var float      $_ywsbs_fee                            Fee value.
 * @var int        $_ywsbs_max_pause                      Max number of pause.
 * @var int        $_ywsbs_max_pause_duration             Max period of pause.
 * @var string     $_ywsbs_enable_max_length              Enable or not the max length.
 * @var string     $_ywsbs_enable_pause                   Enable or not the pause.
 * @var bool       $_ywsbs_enable_limit                   Enable or not the limit.
 * @var string     $_ywsbs_limit                          Subscription limit.
 * @var string     $_ywsbs_override_pause_settings        Subscription override pause settings.
 * @var string     $_ywsbs_override_cancellation_settings Subscription override cancelling settings.
 * @var string     $_ywsbs_can_be_cancelled               Subscription can be cancelled.
 * @var array      $_ywsbs_cancellation_limit             Subscription cancelling limit.
 */

$time_opt     = $_ywsbs_price_time_option ? $_ywsbs_price_time_option : 'days';
$time_options = ywsbs_get_time_options();

?>

<div id="ywsbs_subscription_settings" class="panel woocommerce_options_panel">
	<div class="ywsbs-product-metabox-options-panel yith-plugin-ui options_group">
		<h3 class="ywsbs-title-section"><?php esc_html_e( 'Subscription Settings', 'yith-woocommerce-subscription' ); ?></h3>

		<?php do_action( 'ywsbs_before_single_product_options', $product ); ?>

		<div class="ywsbs-product-metabox-field">
			<label for="_ywsbs_price_is_per"><?php esc_html_e( 'Users will pay every', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<input type="number" class="ywsbs-short" name="_ywsbs_price_is_per" id="_ywsbs_price_is_per" min="0" value="<?php echo esc_attr( $_ywsbs_price_is_per ); ?>"/>
					<select id="_ywsbs_price_time_option" name="_ywsbs_price_time_option" class="select ywsbs-with-margin yith-short-select ywsbs_price_time_option">
						<?php foreach ( $time_options as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $_ywsbs_price_time_option, $key, true ); ?> data-max="<?php echo esc_attr( $max_lengths[ $key ] ); ?>"
								data-text="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Set the length of each recurring subscription period to daily, weekly, monthly or annually.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<?php do_action( 'ywsbs_single_product_options_after_recurring_price', $product ); ?>

		<div class="ywsbs-product-metabox-field">
			<label for="_ywsbs_enable_max_length"><?php esc_html_e( 'Subscription ends', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="_ywsbs_enable_max_length" id="_ywsbs_enable_max_length_no" <?php checked( $_ywsbs_enable_max_length, 'no', true ); ?> value="no">
					<label for="_ywsbs_enable_max_length_no"><?php echo esc_html__( 'Never', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="_ywsbs_enable_max_length" id="_ywsbs_enable_max_length_yes" <?php checked( $_ywsbs_enable_max_length, 'yes', true ); ?> value="yes">
					<label for="_ywsbs_enable_max_length_yes"><?php echo esc_html__( 'Set an end time', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-content" data-deps-on="_ywsbs_enable_max_length" data-deps-val="yes">
					<span><?php esc_html_e( 'Subscription will end after', 'yith-woocommerce-subscription' ); ?></span>
					<input type="number" class="ywsbs-short" name="_ywsbs_max_length" id="_ywsbs_max_length" value="<?php echo esc_attr( $_ywsbs_max_length ); ?>" min="0"/>
					<span class="max-length-time-opt"><?php echo esc_html( $time_options[ $time_opt ] ); ?></span>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Choose if the subscription has an end time or not.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field">
			<label for="_ywsbs_enable_trial"><?php esc_html_e( 'Offer a trial period', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'id'    => '_ywsbs_enable_trial',
						'name'  => '_ywsbs_enable_trial',
						'value' => $_ywsbs_enable_trial,
					),
					true
				);
				?>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Enable to offer a trial period when the subscription is purchased.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_enable_trial" data-deps-val="yes">
			<label for="_ywsbs_trial_time_option"><?php esc_html_e( 'Offer a free trial of', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<input type="number" class="ywsbs-short" name="_ywsbs_trial_per" id="_ywsbs_trial_per" min="0" value="<?php echo esc_attr( $_ywsbs_trial_per ); ?>"/>
					<select id="_ywsbs_trial_time_option" name="_ywsbs_trial_time_option" class="select ywsbs-with-margin yith-short-select">
						<?php foreach ( $time_options as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $_ywsbs_trial_time_option, $key ); ?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'You can offer a free trial of this subscription. In this way, the user can subscribe and pay when the trial period ends.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field">
			<label for="_ywsbs_enable_fee"><?php esc_html_e( 'Request a signup fee', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'id'    => '_ywsbs_enable_fee',
						'name'  => '_ywsbs_enable_fee',
						'value' => $_ywsbs_enable_fee,
					),
					true
				);
				?>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Enable to request a signup fee when the subscription is purchased.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_enable_fee" data-deps-val="yes">
			<label for="_ywsbs_fee"><?php echo esc_html__( 'Signup fee', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<input type="text" class="ywsbs-short ywsbs_fee wc_input_price" style="" name="_ywsbs_fee" id="_ywsbs_fee" value="<?php echo esc_attr( wc_format_localized_price( $_ywsbs_fee ) ); ?>" placeholder="0">
					<span><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'The signup fee will be charged when the subscription is purchased.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field">
			<label for="_ywsbs_enable_limit"><?php esc_html_e( 'Apply subscription limits', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'id'    => '_ywsbs_enable_limit',
						'name'  => '_ywsbs_enable_limit',
						'value' => $_ywsbs_enable_limit,
					),
					true
				);
				?>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Enable to apply limits to the customer purchasing this subscription.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_enable_limit" data-deps-val="yes">
			<label for="_ywsbs_limit"><?php esc_html_e( 'Limit subscription', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="_ywsbs_limit" id="_ywsbs_limit_one_active" <?php checked( $_ywsbs_limit, 'one-active', true ); ?> value="one-active">
					<label for="_ywsbs_limit_one_active"><?php echo esc_html__( 'Limit user to allow only one active subscription', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="_ywsbs_limit" id="_ywsbs_limit_one" <?php checked( $_ywsbs_limit, 'one', true ); ?> value="one">
					<label for="_ywsbs_limit_one"><?php echo esc_html__( 'Limit user to allow only one subscription of any status, either active or not', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Set optional limits for this product subscription.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field">
			<label for="_ywsbs_override_pause_settings"><?php esc_html_e( 'Override global pausing settings', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'id'    => '_ywsbs_override_pause_settings',
						'name'  => '_ywsbs_override_pause_settings',
						'value' => $_ywsbs_override_pause_settings,
					),
					true
				);
				?>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Enable to set custom pausing rules for this product. This will override the general settings option.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div data-deps-on="_ywsbs_override_pause_settings" data-deps-val="yes">

			<div class="ywsbs-product-metabox-field">
				<label for="_ywsbs_enable_pause"><?php esc_html_e( 'Allow the user to pause this subscription', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<input type="radio" name="_ywsbs_enable_pause" id="_ywsbs_enable_pause_no" <?php checked( $_ywsbs_enable_pause, 'no', true ); ?> value="no">
						<label for="_ywsbs_enable_pause_no"><?php echo esc_html__( 'No, never', 'yith-woocommerce-subscription' ); ?></label>
					</div>
					<div class="ywsbs-product-metabox-field-content">
						<input type="radio" name="_ywsbs_enable_pause" id="_ywsbs_enable_pause_yes" <?php checked( $_ywsbs_enable_pause, 'yes', true ); ?> value="yes">
						<label for="_ywsbs_enable_pause_yes"><?php echo esc_html__( 'Yes, user can pause without limits', 'yith-woocommerce-subscription' ); ?></label>
					</div>
					<div class="ywsbs-product-metabox-field-content">
						<input type="radio" name="_ywsbs_enable_pause" id="_ywsbs_enable_pause_limited" <?php checked( $_ywsbs_enable_pause, 'limited', true ); ?> value="limited">
						<label for="_ywsbs_enable_pause_limited"><?php echo esc_html__( 'Yes, user can pause with certain limits', 'yith-woocommerce-subscription' ); ?></label>
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Choose if a user can pause the subscription. And, if so, to do it with or without limits.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>

			<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_enable_pause" data-deps-val="limited">
				<label for="_ywsbs_max_pause"><?php esc_html_e( 'Subscription pausing limits', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<span><?php esc_html_e( 'The user can pause this subscription for a maximum of', 'yith-woocommerce-subscription' ); ?></span>
						<input type="number" class="ywsbs-short" name="_ywsbs_max_pause" id="_ywsbs_max_pause" min="0" value="<?php echo esc_attr( $_ywsbs_max_pause ); ?>"/>
						<span><?php esc_html_e( 'times', 'yith-woocommerce-subscription' ); ?>;</span>
					</div>
					<div class="ywsbs-product-metabox-field-content">
						<span><?php esc_html_e( 'Each pause can have a maximum duration of', 'yith-woocommerce-subscription' ); ?></span>
						<input type="number" class="ywsbs-short" name="_ywsbs_max_pause_duration" id="_ywsbs_max_pause_duration" min="0" value="<?php echo esc_attr( $_ywsbs_max_pause_duration ); ?>"/>
						<span><?php esc_html_e( 'days.', 'yith-woocommerce-subscription' ); ?></span>
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Then the subscription will be automatically reactivated.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>

		</div>

		<div class="ywsbs-product-metabox-field">
			<label for="_ywsbs_override_cancellation_settings"><?php esc_html_e( 'Override global cancellation settings', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'id'    => '_ywsbs_override_cancellation_settings',
						'name'  => '_ywsbs_override_cancellation_settings',
						'value' => $_ywsbs_override_cancellation_settings,
					),
					true
				);
				?>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Enable to set specific cancellation options for this product. It will override the cancellation options in the general settings.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div data-deps-on="_ywsbs_override_cancellation_settings" data-deps-val="yes">

			<div class="ywsbs-product-metabox-field">
				<label for="_ywsbs_can_be_cancelled"><?php esc_html_e( 'Allow the user to cancel this subscription', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<input type="radio" name="_ywsbs_can_be_cancelled" id="_ywsbs_can_be_cancelled_no" <?php checked( $_ywsbs_can_be_cancelled, 'no', true ); ?> value="no">
						<label for="_ywsbs_can_be_cancelled_no"><?php echo esc_html__( 'No, never', 'yith-woocommerce-subscription' ); ?></label>
					</div>
					<div class="ywsbs-product-metabox-field-content">
						<input type="radio" name="_ywsbs_can_be_cancelled" id="_ywsbs_can_be_cancelled_yes" <?php checked( $_ywsbs_can_be_cancelled, 'yes', true ); ?> value="yes">
						<label for="_ywsbs_can_be_cancelled_yes"><?php echo esc_html__( 'Yes, user can cancel without limits', 'yith-woocommerce-subscription' ); ?></label>
					</div>
					<div class="ywsbs-product-metabox-field-content">
						<input type="radio" name="_ywsbs_can_be_cancelled" id="_ywsbs_can_be_cancelled_limited" <?php checked( $_ywsbs_can_be_cancelled, 'limited', true ); ?> value="limited">
						<label for="_ywsbs_can_be_cancelled_limited"><?php echo esc_html__( 'Yes, user can cancel with certain limits', 'yith-woocommerce-subscription' ); ?></label>
					</div>
					<div class="ywsbs-product-metabox-field-description">
						<?php esc_html_e( 'Choose if a user can cancel this subscription. And, if so, to do it with or without limits.', 'yith-woocommerce-subscription' ); ?>
					</div>
				</div>
			</div>

			<div class="ywsbs-product-metabox-field" data-deps-on="_ywsbs_can_be_cancelled" data-deps-val="limited">
				<label for="_ywsbs_cancellation_limit"><?php esc_html_e( 'Subscription cancel limits', 'yith-woocommerce-subscription' ); ?></label>
				<div class="ywsbs-product-metabox-field-container">
					<div class="ywsbs-product-metabox-field-content">
						<span><?php esc_html_e( 'Allow the user to cancel this subscription', 'yith-woocommerce-subscription' ); ?></span>
						<input type="number" class="ywsbs-short" name="_ywsbs_cancellation_limit[days_after_creation]" id="_ywsbs_cancellation_limit_days_after_creation" min="0" value="<?php echo esc_attr( $_ywsbs_cancellation_limit['days_after_creation'] ?? 0 ); ?>"/>
						<span><?php esc_html_e( 'day(s) after creation', 'yith-woocommerce-subscription' ); ?>;</span>
					</div>
					<div class="ywsbs-product-metabox-field-content">
						<span><?php esc_html_e( 'The user can cancel this subscription within', 'yith-woocommerce-subscription' ); ?></span>
						<input type="number" class="ywsbs-short" name="_ywsbs_cancellation_limit[days_before_renew]" id="_ywsbs_cancellation_limit_days_before_renew" min="0" value="<?php echo esc_attr( $_ywsbs_cancellation_limit['days_before_renew'] ?? 0 ); ?>"/>
						<span><?php esc_html_e( 'day(s) before renew date.', 'yith-woocommerce-subscription' ); ?></span>
					</div>
				</div>
			</div>

		</div>

		<?php do_action( 'ywsbs_after_single_product_options', $product ); ?>

	</div>
</div>
