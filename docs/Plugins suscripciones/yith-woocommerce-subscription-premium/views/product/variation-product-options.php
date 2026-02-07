<?php
/**
 * Variable product template options
 *
 * @package YITH\Subscription
 * @since   2.0.0
 * @author YITH
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Vars used on this template.
 *
 * @var string $_ywsbs_price_time_option              Period (days, weeks ..).
 * @var int    $_ywsbs_price_is_per                   Duration.
 * @var array  $max_lengths                           Limit of time foreach period.
 * @var int    $_ywsbs_max_length                     Max duration of the subscrition.
 * @var int    $_ywsbs_trial_per                      Duration of the trial.
 * @var int    $_ywsbs_trial_time_option              Period (days, weeks ..).
 * @var float  $_ywsbs_fee                            Fee value.
 * @var int    $_ywsbs_max_pause                      Max number of pause.
 * @var int    $_ywsbs_max_pause_duration             Max period of pause.
 * @var string $_ywsbs_enable_max_length              Enable or not the max length.
 * @var string $_ywsbs_enable_pause                   Enable or not the pause.
 * @var string $_ywsbs_limit                          Subscription limit.
 * @var string $_ywsbs_switchable                     Switchable option value.
 * @var string $_ywsbs_prorate_length                 Prorate option value.
 * @var string $_ywsbs_gap_payment                    Gap payment option value.
 * @var int    $loop                                  Current variation.
 * @var string $_ywsbs_override_pause_settings        Subscription override pause settings.
 * @var string $_ywsbs_override_cancellation_settings Subscription override cancelling settings.
 * @var string $_ywsbs_can_be_cancelled Subscription can be cancelled.
 * @var int    $num_variations Variations total number.
 * @var int    $_ywsbs_switchable_priority Priority number.
 * @var string $_ywsbs_prorate_recurring_payment Recurring payment.
 * @var string $_ywsbs_prorate_fee Charge Fee.
 * @var bool   $_ywsbs_enable_limit Enable or not the limit.
 * @var bool   $_ywsbs_enable_fee Check if enable the fee
 * @var bool   $_ywsbs_enable_trial Check is enable the trial.
 */

$time_opt     = ( $_ywsbs_price_time_option ) ? $_ywsbs_price_time_option : 'days';
$time_options = ywsbs_get_time_options();
?>

<div class="ywsbs-product-metabox-options-panel yith-plugin-ui options_group ywsbs_subscription_variation_products">
	<h3 class="ywsbs-title-section"><?php esc_html_e( 'Subscription Settings', 'yith-woocommerce-subscription' ); ?></h3>

	<div class="ywsbs-product-metabox-field">
		<label for="_ywsbs_price_is_per_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Users will pay every', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<input type="number" class="ywsbs-short" name="variable_ywsbs_price_is_per[<?php echo esc_attr( $loop ); ?>]" id="_ywsbs_price_is_per_<?php echo esc_attr( $loop ); ?>" min="0" value="<?php echo esc_attr( $_ywsbs_price_is_per ); ?>"/>
				<select id="variable_ywsbs_price_time_option_<?php echo esc_attr( $loop ); ?>" name="variable_ywsbs_price_time_option[<?php echo esc_attr( $loop ); ?>]" class="select yith-short-select ywsbs_price_time_option">
					<?php foreach ( $time_options as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $_ywsbs_price_time_option, $key, true ); ?>  data-max="<?php echo esc_attr( $max_lengths[ $key ] ); ?>" data-text="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Set the length of each recurring subscription period to daily, weekly, monthly or annually.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<?php do_action( 'ywsbs_product_variation_options_after_recurring_price', $variation, $loop ); ?>

	<div class="ywsbs-product-metabox-field">
		<label for="variable_ywsbs_enable_max_length_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Subscription ends', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<input type="radio" name="variable_ywsbs_enable_max_length[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_enable_max_length_<?php echo esc_attr( $loop ); ?>_no" <?php checked( $_ywsbs_enable_max_length, 'no', true ); ?> value="no">
				<label for="variable_ywsbs_enable_max_length_<?php echo esc_attr( $loop ); ?>_no"><?php echo esc_html__( 'Never', 'yith-woocommerce-subscription' ); ?></label>
			</div>
			<div class="ywsbs-product-metabox-field-content">
				<input type="radio" name="variable_ywsbs_enable_max_length[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_enable_max_length_<?php echo esc_attr( $loop ); ?>_yes" <?php checked( $_ywsbs_enable_max_length, 'yes', true ); ?> value="yes">
				<label for="variable_ywsbs_enable_max_length_<?php echo esc_attr( $loop ); ?>_yes"><?php echo esc_html__( 'Set an end time', 'yith-woocommerce-subscription' ); ?></label>
			</div>
			<div class="ywsbs-product-metabox-field-content" data-deps-on="variable_ywsbs_enable_max_length[<?php echo esc_attr( $loop ); ?>]" data-deps-val="yes">
				<span><?php esc_html_e( 'Subscription will end after', 'yith-woocommerce-subscription' ); ?></span>
				<input type="number" class="ywsbs-short" name="variable_ywsbs_max_length[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_max_length_<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_attr( $_ywsbs_max_length ); ?>" min="0"/>
				<span class="max-length-time-opt"><?php echo esc_html( $time_options[ $time_opt ] ); ?></span>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Choose if the subscription has an end time or not.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field">
		<label for="variable_ywsbs_enable_trial_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Offer a trial period', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<?php
			yith_plugin_fw_get_field(
				array(
					'type'  => 'onoff',
					'id'    => 'variable_ywsbs_enable_trial_' . $loop,
					'name'  => 'variable_ywsbs_enable_trial[' . $loop . ']',
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

	<div class="ywsbs-product-metabox-field" data-deps-on="variable_ywsbs_enable_trial[<?php echo esc_attr( $loop ); ?>]" data-deps-val="yes">
		<label for="variable_ywsbs_trial_per_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Offer a free trial of', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<input type="number" class="ywsbs-short" name="variable_ywsbs_trial_per[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_trial_per_<?php echo esc_attr( $loop ); ?>" min="0" value="<?php echo esc_attr( $_ywsbs_trial_per ); ?>"/>
				<select id="variable_ywsbs_trial_time_option_<?php echo esc_attr( $loop ); ?>" name="variable_ywsbs_trial_time_option[<?php echo esc_attr( $loop ); ?>]" class="select yith-short-select">
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
		<label for="variable_ywsbs_enable_fee_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Request a signup fee', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<?php
			yith_plugin_fw_get_field(
				array(
					'type'  => 'onoff',
					'id'    => 'variable_ywsbs_enable_fee_' . $loop,
					'name'  => 'variable_ywsbs_enable_fee[' . $loop . ']',
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

	<div class="ywsbs-product-metabox-field" data-deps-on="variable_ywsbs_enable_fee[<?php echo esc_attr( $loop ); ?>]" data-deps-val="yes">
		<label for="variable_ywsbs_fee_<?php echo esc_attr( $loop ); ?>"><?php echo esc_html__( 'Signup fee', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<input type="text" class="ywsbs-short ywsbs_fee wc_input_price" style="" name="variable_ywsbs_fee[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_fee_<?php echo esc_attr( $loop ); ?>" value="<?php echo esc_attr( wc_format_localized_price( $_ywsbs_fee ) ); ?>" placeholder="0">
				<span><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'The signup fee will be charged when the subscription is purchased.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field">
		<label for="variable_ywsbs_enable_limit_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Apply subscription limits', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<?php
			yith_plugin_fw_get_field(
				array(
					'type'  => 'onoff',
					'id'    => 'variable_ywsbs_enable_limit_' . $loop,
					'name'  => 'variable_ywsbs_enable_limit[' . $loop . ']',
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


	<div class="ywsbs-product-metabox-field" data-deps-on="variable_ywsbs_enable_limit[<?php echo esc_attr( $loop ); ?>]" data-deps-val="yes">
		<label for="variable_ywsbs_limit_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Limit subscription', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<input type="radio" name="variable_ywsbs_limit[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_limit_<?php echo esc_attr( $loop ); ?>_one_active" <?php checked( $_ywsbs_limit, 'one-active', true ); ?> value="one-active">
				<label for="variable_ywsbs_limit_<?php echo esc_attr( $loop ); ?>_one_active"><?php echo esc_html__( 'Limit user to allow only one active subscription', 'yith-woocommerce-subscription' ); ?></label>
			</div>
			<div class="ywsbs-product-metabox-field-content">
				<input type="radio" name="variable_ywsbs_limit[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_limit_<?php echo esc_attr( $loop ); ?>_one" <?php checked( $_ywsbs_limit, 'one', true ); ?> value="one">
				<label for="variable_ywsbs_limit_<?php echo esc_attr( $loop ); ?>_one"><?php echo esc_html__( 'Limit user to allow only one subscription of any status, either active or not', 'yith-woocommerce-subscription' ); ?></label>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Set optional limits for this product subscription.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field">
		<label for="variable_ywsbs_override_pause_settings_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Override global pausing settings', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<?php
			yith_plugin_fw_get_field(
				array(
					'type'  => 'onoff',
					'id'    => 'variable_ywsbs_override_pause_settings_' . $loop,
					'name'  => 'variable_ywsbs_override_pause_settings[' . $loop . ']',
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

	<div data-deps-on="variable_ywsbs_override_pause_settings[<?php echo esc_attr( $loop ); ?>]" data-deps-val="yes">

		<div class="ywsbs-product-metabox-field">
			<label for="variable_ywsbs_enable_pause_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Allow the user to pause this subscription', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="variable_ywsbs_enable_pause[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_enable_pause_<?php echo esc_attr( $loop ); ?>_no" <?php checked( $_ywsbs_enable_pause, 'no', true ); ?> value="no">
					<label for="variable_ywsbs_enable_pause_<?php echo esc_attr( $loop ); ?>_no"><?php echo esc_html__( 'No, never', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="variable_ywsbs_enable_pause[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_enable_pause_<?php echo esc_attr( $loop ); ?>_yes" <?php checked( $_ywsbs_enable_pause, 'yes', true ); ?> value="yes">
					<label for="variable_ywsbs_enable_pause_<?php echo esc_attr( $loop ); ?>_yes"><?php echo esc_html__( 'Yes, user can pause without limits', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="variable_ywsbs_enable_pause[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_enable_pause_<?php echo esc_attr( $loop ); ?>_limited" <?php checked( $_ywsbs_enable_pause, 'limited', true ); ?> value="limited">
					<label for="variable_ywsbs_enable_pause_<?php echo esc_attr( $loop ); ?>_limited"><?php echo esc_html__( 'Yes, user can pause with certain limits', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Choose if a user can pause the subscription. And, if so, to do it with or without limits.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field" data-deps-on="variable_ywsbs_enable_pause[<?php echo esc_attr( $loop ); ?>]" data-deps-val="limited">
			<label for="variable_ywsbs_max_pause_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Subscription pausing limits', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<span><?php esc_html_e( 'The user can pause this subscription for a maximum of', 'yith-woocommerce-subscription' ); ?></span>
					<input type="number" class="ywsbs-short" name="variable_ywsbs_max_pause[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_max_pause_<?php echo esc_attr( $loop ); ?>" min="0" value="<?php echo esc_attr( $_ywsbs_max_pause ); ?>"/>
					<span><?php esc_html_e( 'times', 'yith-woocommerce-subscription' ); ?>;</span>
				</div>
				<div class="ywsbs-product-metabox-field-content">
					<span><?php esc_html_e( 'Each pause can have a maximum duration of', 'yith-woocommerce-subscription' ); ?></span>
					<input type="number" class="ywsbs-short" name="variable_ywsbs_max_pause_duration[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_max_pause_duration_<?php echo esc_attr( $loop ); ?>" min="0" value="<?php echo esc_attr( $_ywsbs_max_pause_duration ); ?>"/>
					<span><?php esc_html_e( 'days.', 'yith-woocommerce-subscription' ); ?></span>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Then the subscription will be automatically reactivated.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

	</div>

	<div class="ywsbs-product-metabox-field">
		<label for="variable_ywsbs_override_cancellation_settings_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Override global cancellation settings', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<?php
			yith_plugin_fw_get_field(
				array(
					'type'  => 'onoff',
					'id'    => 'variable_ywsbs_override_cancellation_settings_' . $loop,
					'name'  => 'variable_ywsbs_override_cancellation_settings[' . $loop . ']',
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

	<div data-deps-on="variable_ywsbs_override_cancellation_settings[<?php echo esc_attr( $loop ); ?>]" data-deps-val="yes">

		<div class="ywsbs-product-metabox-field">
			<label for="variable_ywsbs_can_be_cancelled_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Allow the user to cancel this subscription', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="variable_ywsbs_can_be_cancelled[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_can_be_cancelled_<?php echo esc_attr( $loop ); ?>_no" <?php checked( $_ywsbs_can_be_cancelled, 'no', true ); ?> value="no">
					<label for="variable_ywsbs_can_be_cancelled_<?php echo esc_attr( $loop ); ?>_no"><?php echo esc_html__( 'No, never', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="variable_ywsbs_can_be_cancelled[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_can_be_cancelled_<?php echo esc_attr( $loop ); ?>_yes" <?php checked( $_ywsbs_can_be_cancelled, 'yes', true ); ?> value="yes">
					<label for="variable_ywsbs_can_be_cancelled_<?php echo esc_attr( $loop ); ?>_yes"><?php echo esc_html__( 'Yes, user can cancel without limits', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-content">
					<input type="radio" name="variable_ywsbs_can_be_cancelled[<?php echo esc_attr( $loop ); ?>]" id="variable_ywsbs_can_be_cancelled_<?php echo esc_attr( $loop ); ?>_limited" <?php checked( $_ywsbs_can_be_cancelled, 'limited', true ); ?> value="limited">
					<label for="variable_ywsbs_can_be_cancelled_<?php echo esc_attr( $loop ); ?>_limited"><?php echo esc_html__( 'Yes, user can cancel with certain limits', 'yith-woocommerce-subscription' ); ?></label>
				</div>
				<div class="ywsbs-product-metabox-field-description">
					<?php esc_html_e( 'Choose if a user can cancel this subscription. And, if so, to do it with or without limits.', 'yith-woocommerce-subscription' ); ?>
				</div>
			</div>
		</div>

		<div class="ywsbs-product-metabox-field" data-deps-on="variable_ywsbs_can_be_cancelled[<?php echo esc_attr( $loop ); ?>]" data-deps-val="limited">
			<label for="variable_ywsbs_cancellation_limit_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Subscription cancel limits', 'yith-woocommerce-subscription' ); ?></label>
			<div class="ywsbs-product-metabox-field-container">
				<div class="ywsbs-product-metabox-field-content">
					<span><?php esc_html_e( 'Allow the user to cancel this subscription', 'yith-woocommerce-subscription' ); ?></span>
					<input type="number" class="ywsbs-short" name="variable_ywsbs_cancellation_limit[<?php echo esc_attr( $loop ); ?>][days_after_creation]" id="variable_ywsbs_cancellation_limit_days_after_creation_[<?php echo esc_attr( $loop ); ?>]" min="0" value="<?php echo esc_attr( $_ywsbs_cancellation_limit['days_after_creation'] ?? 0 ); ?>"/>
					<span><?php esc_html_e( 'day(s) after creation', 'yith-woocommerce-subscription' ); ?>;</span>
				</div>
				<div class="ywsbs-product-metabox-field-content">
					<span><?php esc_html_e( 'The user can cancel this subscription within', 'yith-woocommerce-subscription' ); ?></span>
					<input type="number" class="ywsbs-short" name="variable_ywsbs_cancellation_limit[<?php echo esc_attr( $loop ); ?>][days_before_renew]" id="variable_ywsbs_cancellation_limit_days_before_renew_[<?php echo esc_attr( $loop ); ?>]" min="0" value="<?php echo esc_attr( $_ywsbs_cancellation_limit['days_before_renew'] ?? 0 ); ?>"/>
					<span><?php esc_html_e( 'day(s) before renew date.', 'yith-woocommerce-subscription' ); ?></span>
				</div>
			</div>
		</div>

	</div>

	<h3 class="ywsbs-title-section"><?php esc_html_e( 'Upgrade/Switch/Downgrade subscription settings', 'yith-woocommerce-subscription' ); ?></h3>

	<div class="ywsbs-product-metabox-field">
		<label for="variable_ywsbs_switchable_priority_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Variation priority', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<select id="variable_ywsbs_switchable_priority_<?php echo esc_attr( $loop ); ?>" name="variable_ywsbs_switchable_priority[<?php echo esc_attr( $loop ); ?>]" class="select yith-short-select switchable_priority">
					<?php
					$found_value = false;
					for ( $i = 0; $i < $num_variations; $i++ ) :
						?>
						<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $_ywsbs_switchable_priority, true ); ?> ><?php echo esc_html( $i + 1 ); ?></option>
					<?php endfor; ?>
					<?php if ( (int) $_ywsbs_switchable_priority >= (int) $num_variations ) : ?>
						<option value="<?php echo esc_attr( $_ywsbs_switchable_priority ); ?>" selected="selected"> <?php echo esc_html( $_ywsbs_switchable_priority + 1 ); ?></option>
					<?php endif; ?>
				</select>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Use this option to set the hierarchy of this variation and define when the user upgrades to a higher priority variation or downgrades to a lower priority one.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field">
		<label for="variable_ywsbs_switchable_priority_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Allow switching to this variation', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<select id="variable_ywsbs_switchable_<?php echo esc_attr( $loop ); ?>" name="variable_ywsbs_switchable[<?php echo esc_attr( $loop ); ?>]" class="select yith-short-select">
					<option value="no" <?php selected( $_ywsbs_switchable, 'no' ); ?> ><?php echo esc_html__( 'Never', 'yith-woocommerce-subscription' ); ?></option>
					<option value="upgrade" <?php selected( $_ywsbs_switchable, 'upgrade' ); ?> ><?php echo esc_html__( 'Yes, only to a variation with a lower priority', 'yith-woocommerce-subscription' ); ?></option>
					<option value="yes" <?php selected( $_ywsbs_switchable, 'yes' ); ?> ><?php echo esc_html__( 'Yes, without limits', 'yith-woocommerce-subscription' ); ?></option>
				</select>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Choose if the user who purchased a variation can switch to a different one.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field">
		<label for="variable_ywsbs_prorate_fee_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'When a plan changes, charge the signup fee', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<select id="variable_ywsbs_prorate_fee_<?php echo esc_attr( $loop ); ?>" name="variable_ywsbs_prorate_fee[<?php echo esc_attr( $loop ); ?>]" class="select yith-short-select">
					<option value="no" <?php selected( $_ywsbs_prorate_fee, 'no' ); ?> ><?php echo esc_html__( 'No', 'yith-woocommerce-subscription' ); ?></option>
					<option value="yes" <?php selected( $_ywsbs_prorate_fee, 'yes' ); ?> ><?php echo esc_html__( 'Yes, charge the full signup fee', 'yith-woocommerce-subscription' ); ?></option>
					<option value="difference" <?php selected( $_ywsbs_prorate_fee, 'difference' ); ?> ><?php echo esc_html__( 'Yes, but only charge the difference', 'yith-woocommerce-subscription' ); ?></option>
				</select>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Choose whether to charge the signup fee when a user changes the subscription plan.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<div class="ywsbs-product-metabox-field">
		<label for="variable_ywsbs_prorate_recurring_payment_<?php echo esc_attr( $loop ); ?>"><?php esc_html_e( 'Prorate recurring payment', 'yith-woocommerce-subscription' ); ?></label>
		<div class="ywsbs-product-metabox-field-container">
			<div class="ywsbs-product-metabox-field-content">
				<select id="variable_ywsbs_prorate_recurring_payment_<?php echo esc_attr( $loop ); ?>" name="variable_ywsbs_prorate_recurring_payment[<?php echo esc_attr( $loop ); ?>]" class="select yith-short-select">
					<option value="no" <?php selected( $_ywsbs_prorate_recurring_payment, 'no' ); ?> ><?php echo esc_html__( 'Never', 'yith-woocommerce-subscription' ); ?></option>
					<option value="upgrade" <?php selected( $_ywsbs_prorate_recurring_payment, 'upgrade' ); ?> ><?php echo esc_html__( 'Yes, but only for upgrades', 'yith-woocommerce-subscription' ); ?></option>
					<option value="downgrade" <?php selected( $_ywsbs_prorate_recurring_payment, 'downgrade' ); ?> ><?php echo esc_html__( 'Yes, but only for downgrades', 'yith-woocommerce-subscription' ); ?></option>
					<option value="yes" <?php selected( $_ywsbs_prorate_recurring_payment, 'yes' ); ?> ><?php echo esc_html__( 'Yes, for all plans changes', 'yith-woocommerce-subscription' ); ?></option>
				</select>
			</div>
			<div class="ywsbs-product-metabox-field-description">
				<?php esc_html_e( 'Choose how to manage the price difference between the plans when the user switch.', 'yith-woocommerce-subscription' ); ?>
			</div>
		</div>
	</div>

	<?php do_action( 'ywsbs_after_variation_product_options', $variation, $loop ); ?>

</div>
