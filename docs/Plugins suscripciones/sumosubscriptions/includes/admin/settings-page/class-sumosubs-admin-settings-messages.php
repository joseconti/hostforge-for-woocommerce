<?php

/**
 * Messages Settings.
 * 
 * @class SUMOSubs_Admin_Settings_Messages
 */
class SUMOSubs_Admin_Settings_Messages extends SUMOSubs_Abstract_Admin_Settings {

	/**
	 * SUMOSubs_Admin_Settings_Messages constructor.
	 */
	public function __construct() {
		$this->id            = 'messages';
		$this->label         = __( 'Messages', 'sumosubscriptions' );
		$this->custom_fields = array(
			'get_signup_fee_message',
			'get_paid_trial_message',
			'get_subscription_price_message',
			'get_number_of_installments_message',
			'get_optional_paid_trial_label',
			'get_optional_signup_fee_label',
			'get_renewal_fee_after_discount_message',
			'get_sync_prorate_payment_message',
			'get_sync_prorate_payment_during_first_renewal_message',
		);
		$this->settings      = $this->get_settings();
		$this->init();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		global $current_section;

		/**
		 * Get the admin settings.
		 * 
		 * @since 1.0
		 */
		return apply_filters( 'sumosubscriptions_get_' . $this->id . '_settings', array(
			array(
				'name' => __( 'Subscription Plan Message Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'plan_message_section',
			),
			array(
				'type' => $this->get_custom_field_type( 'get_signup_fee_message' ),
			),
			array(
				'name'    => __( 'Free Trial Message', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'free_trial_strings' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'free_trial_strings' ),
				'type'    => 'textarea',
			),
			array(
				'type' => $this->get_custom_field_type( 'get_paid_trial_message' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_subscription_price_message' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_number_of_installments_message' ),
			),
			array(
				'name'    => __( 'Variable Product Price Range Prefix', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'variable_product_price_strings' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'variable_product_price_strings' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Optional Trial - Free Trial', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'optional_free_trial_strings' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'optional_free_trial_strings' ),
				'type'    => 'textarea',
			),
			array(
				'type' => $this->get_custom_field_type( 'get_optional_paid_trial_label' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_optional_signup_fee_label' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_renewal_fee_after_discount_message' ),
			),
			array( 'type' => 'sectionend', 'id' => 'plan_message_section' ),
			array(
				'name' => __( 'Synchronization Message Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'synchronization_section',
			),
			array(
				'name'    => __( 'Synchronization Plan Message', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'synced_plan_strings' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'synced_plan_strings' ),
				'type'    => 'textarea',
			),
			array(
				'type' => $this->get_custom_field_type( 'get_sync_prorate_payment_message' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_sync_prorate_payment_during_first_renewal_message' ),
			),
			array( 'type' => 'sectionend', 'id' => 'synchronization_section' ),
			array(
				'name' => __( 'Subscription Values Singular/Plural Customization', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'period_section',
			),
			array(
				'name'    => __( 'Day|Days Customization', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'period_day_r_days_label' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'period_day_r_days_label' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Week|Weeks Customization', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'period_week_r_weeks_label' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'period_week_r_weeks_label' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Month|Months Customization', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'period_month_r_months_label' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'period_month_r_months_label' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Year|Years Customization', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'period_year_r_years_label' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'period_year_r_years_label' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Installment|Installments Customization', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'period_length_r_lengths_label' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'period_length_r_lengths_label' ),
				'type'    => 'textarea',
			),
			array( 'type' => 'sectionend', 'id' => 'period_section' ),
			array(
				'name' => __( 'Error Message Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'error_section',
			),
			array(
				'name'    => __( 'Display Subscription Restriction Error Messages in Single Product Page', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'show_error_messages_in_product_page' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'show_error_messages_in_product_page' ),
				'type'    => 'checkbox',
			),
			array(
				'name'    => __( 'When One Active Subscription Per Product Restriction is Applied', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'product_wide_subscription_limit_error_message' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'product_wide_subscription_limit_error_message' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'When One Active Subscription Throughout the Site Restriction is Applied', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'site_wide_subscription_limit_error_message' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'site_wide_subscription_limit_error_message' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'When Mixed Checkout Restriction is Applied', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'cannot_purchase_regular_with_subscription_product_error_message' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'cannot_purchase_regular_with_subscription_product_error_message' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'When adding Subscription Product along with another Subscription Product', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'cannot_purchase_multiple_subscriptions_error_message' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'cannot_purchase_multiple_subscriptions_error_message' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'When adding Subscription Product along with Non-Subscription Product', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'cannot_purchase_subscription_with_regular_product_error_message' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'cannot_purchase_subscription_with_regular_product_error_message' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Display Trial Restriction Error Messages in Cart Page', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'show_error_messages_in_cart_page' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'show_error_messages_in_cart_page' ),
				'type'    => 'checkbox',
			),
			array(
				'name'    => __( 'When One Trial Per Product Restriction is Applied', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'product_wide_trial_limit_error_message' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'product_wide_trial_limit_error_message' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'When One Trial Throughout the Site Restriction is Applied', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'site_wide_trial_limit_error_message' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'site_wide_trial_limit_error_message' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Display Error Messages in Pay for Order Page', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'show_error_messages_in_pay_for_order_page' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'show_error_messages_in_pay_for_order_page' ),
				'type'    => 'checkbox',
			),
			array(
				'name'    => __( 'When User Tries to Pay for the Renewal when Subscription is in Pause Status', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'renewal_order_payment_in_paused_error_message' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'renewal_order_payment_in_paused_error_message' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'When User Tries to Pay for the Renewal Order when Subscription is in Pending for Cancellation Status', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'renewal_order_payment_in_pending_cancel_error_message' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'renewal_order_payment_in_pending_cancel_error_message' ),
				'type'    => 'textarea',
			),
			array( 'type' => 'sectionend', 'id' => 'error_section' ),
				) );
	}

	/**
	 * Custom type field.
	 */
	public function get_signup_fee_message() {
		$id = SUMOSubs_Admin_Options::prepare_option_name( 'signup_fee_strings' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="signup_fee_message"><?php esc_html_e( 'Sign Up Fee Message', 'sumosubscriptions' ); ?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					><?php echo esc_textarea( SUMOSubs_Admin_Options::get_option( 'signup_fee_strings' ) ); ?></textarea><br>
					<?php echo wp_kses_post( '<b>Shortcodes: <br>[sumo_initial_fee]</b> - To display the sum of sign up fee and trial/subscription price.', 'sumosubscriptions' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_paid_trial_message() {
		$id = SUMOSubs_Admin_Options::prepare_option_name( 'trial_fee_and_duration_strings' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="paid_trial_message"><?php esc_html_e( 'Paid Trial Message', 'sumosubscriptions' ); ?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					><?php echo esc_textarea( SUMOSubs_Admin_Options::get_option( 'trial_fee_and_duration_strings' ) ); ?></textarea><br>
					<?php echo wp_kses_post( '<b>Shortcodes: <br>[sumo_trial_fee]</b> - To display trial fee. <br><b>[sumo_trial_period_value]</b> - To display trial duration value. <br><b>[sumo_trial_period]</b> - To display trial duration.', 'sumosubscriptions' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_subscription_price_message() {
		$id = SUMOSubs_Admin_Options::prepare_option_name( 'subscription_price_and_duration_strings' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="subscription_price_message"><?php esc_html_e( 'Subscription Price Message', 'sumosubscriptions' ); ?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					><?php echo esc_textarea( SUMOSubs_Admin_Options::get_option( 'subscription_price_and_duration_strings' ) ); ?></textarea><br>
					<?php echo wp_kses_post( '<b>Shortcodes: <br>[sumo_subscription_fee]</b> - To display subscription price. <br><b>[sumo_subscription_period_value]</b> - To display subscription duration value. <br><b>[sumo_subscription_period]</b> - To display subscription duration. That is, day(s) / week(s) / month(s) / year(s).', 'sumosubscriptions' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_number_of_installments_message() {
		$id = SUMOSubs_Admin_Options::prepare_option_name( 'subscription_length_strings' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="number_of_installments_message"><?php esc_html_e( 'Number of Installments Message', 'sumosubscriptions' ); ?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					><?php echo esc_textarea( SUMOSubs_Admin_Options::get_option( 'subscription_length_strings' ) ); ?></textarea><br>
					<?php echo wp_kses_post( '<b>Shortcodes: <br>[sumo_instalment_period_value]</b> - To display subscription recurring cycle. That is, Indefinite / 1 to 52. <br><b>[sumo_instalment_period]</b> - To display subscription installment. That is, installment(s).', 'sumosubscriptions' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_optional_paid_trial_label() {
		$id = SUMOSubs_Admin_Options::prepare_option_name( 'optional_paid_trial_strings' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="optional_paid_trial_label"><?php esc_html_e( 'Optional Trial - Paid Trial', 'sumosubscriptions' ); ?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					><?php echo esc_textarea( SUMOSubs_Admin_Options::get_option( 'optional_paid_trial_strings' ) ); ?></textarea><br>
					<?php echo wp_kses_post( '<b>Shortcodes: <br>[sumo_trial_fee]</b> - To display trial fee.', 'sumosubscriptions' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_optional_signup_fee_label() {
		$id = SUMOSubs_Admin_Options::prepare_option_name( 'optional_signup_fee_strings' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="optional_signup_fee_label"><?php esc_html_e( 'Optional Sign Up Fee', 'sumosubscriptions' ); ?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					><?php echo esc_textarea( SUMOSubs_Admin_Options::get_option( 'optional_signup_fee_strings' ) ); ?></textarea><br>
					<?php echo wp_kses_post( '<b>Shortcodes: <br>[sumo_signup_fee_only]</b> - To display only sign up fee.', 'sumosubscriptions' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_renewal_fee_after_discount_message() {
		$id = SUMOSubs_Admin_Options::prepare_option_name( 'discounted_renewal_amount_strings' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="renewal_fee_after_discount_message"><?php esc_html_e( 'Renewal Fee After Discount', 'sumosubscriptions' ); ?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					><?php echo esc_textarea( SUMOSubs_Admin_Options::get_option( 'discounted_renewal_amount_strings' ) ); ?></textarea><br>
					<?php echo wp_kses_post( '<b>Shortcodes: <br>[renewal_fee_after_discount]</b> - To display subscription renewal fee after the discount is applied.', 'sumosubscriptions' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_sync_prorate_payment_message() {
		$id = SUMOSubs_Admin_Options::prepare_option_name( 'synced_prorated_amount_strings' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="sync_prorate_payment_message"><?php esc_html_e( 'Subscription Plan Message – Prorate Payment During First Payment', 'sumosubscriptions' ); ?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					><?php echo esc_textarea( SUMOSubs_Admin_Options::get_option( 'synced_prorated_amount_strings' ) ); ?></textarea><br>
					<?php echo wp_kses_post( '<b>Shortcodes: <br>[sumo_prorated_fee]</b> - To display subscription prorated fee. <br><b>[sumo_synchronized_prorated_date]</b> - To display subscription prorated date.', 'sumosubscriptions' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_sync_prorate_payment_during_first_renewal_message() {
		$id = SUMOSubs_Admin_Options::prepare_option_name( 'synced_prorated_amount_during_first_renewal_strings' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="sync_prorate_payment_during_first_renewal_message"><?php esc_html_e( 'Subscription Plan Message – Prorate Payment During First Renewal', 'sumosubscriptions' ); ?></label>
			</th>
			<td class="forminp forminp-textarea">
				<textarea
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					><?php echo esc_textarea( SUMOSubs_Admin_Options::get_option( 'synced_prorated_amount_during_first_renewal_strings' ) ); ?></textarea><br>
					<?php echo wp_kses_post( '<b>Shortcodes: <br>[sumo_synchronized_prorated_date]</b> - To display subscription prorated date. <br><b>[sumo_synchronized_next_payment_date]</b> - To display synchronized next payment date in single product page / cart page if synchronization is enabled in the site for the specified subscription products.', 'sumosubscriptions' ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the custom options once.
	 */
	public function custom_types_add_options() {
		SUMOSubs_Admin_Options::add_option( 'signup_fee_strings' );
		SUMOSubs_Admin_Options::add_option( 'trial_fee_and_duration_strings' );
		SUMOSubs_Admin_Options::add_option( 'subscription_price_and_duration_strings' );
		SUMOSubs_Admin_Options::add_option( 'subscription_length_strings' );
		SUMOSubs_Admin_Options::add_option( 'optional_paid_trial_strings' );
		SUMOSubs_Admin_Options::add_option( 'optional_signup_fee_strings' );
		SUMOSubs_Admin_Options::add_option( 'discounted_renewal_amount_strings' );
		SUMOSubs_Admin_Options::add_option( 'synced_prorated_amount_strings' );
		SUMOSubs_Admin_Options::add_option( 'synced_prorated_amount_during_first_renewal_strings' );
	}

	/**
	 * Delete the custom options.
	 */
	public function custom_types_delete_options() {
		SUMOSubs_Admin_Options::delete_option( 'signup_fee_strings' );
		SUMOSubs_Admin_Options::delete_option( 'trial_fee_and_duration_strings' );
		SUMOSubs_Admin_Options::delete_option( 'subscription_price_and_duration_strings' );
		SUMOSubs_Admin_Options::delete_option( 'subscription_length_strings' );
		SUMOSubs_Admin_Options::delete_option( 'optional_paid_trial_strings' );
		SUMOSubs_Admin_Options::delete_option( 'optional_signup_fee_strings' );
		SUMOSubs_Admin_Options::delete_option( 'discounted_renewal_amount_strings' );
		SUMOSubs_Admin_Options::delete_option( 'synced_prorated_amount_strings' );
		SUMOSubs_Admin_Options::delete_option( 'synced_prorated_amount_during_first_renewal_strings' );
	}

	/**
	 * Save custom settings.
	 */
	public function custom_types_save( $posted ) {
		$keys_to_update = array(
			'signup_fee_strings',
			'trial_fee_and_duration_strings',
			'subscription_price_and_duration_strings',
			'subscription_length_strings',
			'optional_paid_trial_strings',
			'optional_signup_fee_strings',
			'discounted_renewal_amount_strings',
			'synced_prorated_amount_strings',
			'synced_prorated_amount_during_first_renewal_strings',
		);

		foreach ( $keys_to_update as $key ) {
			$meta_key = 'sumosubs_' . $key;

			if ( isset( $posted[ $meta_key ] ) ) {
				update_option( $meta_key, sanitize_textarea_field( $posted[ $meta_key ] ) );
			}
		}
	}
}

return new SUMOSubs_Admin_Settings_Messages();
