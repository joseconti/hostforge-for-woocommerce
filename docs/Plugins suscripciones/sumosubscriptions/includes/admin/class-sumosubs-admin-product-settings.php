<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handle Admin subscription settings in product backend. 
 * 
 * @class SUMOSubs_Admin_Product_Settings
 */
class SUMOSubs_Admin_Product_Settings {

	/**
	 * Subscription fields.
	 * 
	 * @var array
	 */
	protected static $subscription_fields = array(
		'susbcription_status'                     => 'select',
		'susbcription_trial_enable_disable'       => 'select',
		'susbcription_signusumoee_enable_disable' => 'select',
		'susbcription_period'                     => 'select',
		'trial_period'                            => 'select',
		'synchronize_period'                      => 'select',
		'susbcription_period_value'               => 'select',
		'trial_period_value'                      => 'select',
		'synchronize_period_value'                => 'select',
		'synchronize_start_year'                  => 'number',
		'subscribed_after_sync_date_type'         => 'select',
		'xtra_time_to_charge_full_fee'            => 'number',
		'cutoff_time_to_not_renew_nxt_subs_cycle' => 'number',
		'susbcription_fee_type_selector'          => 'select',
		'trial_price'                             => 'price',
		'signup_price'                            => 'price',
		'recurring_period_value'                  => 'select',
		'enable_additional_digital_downloads'     => 'checkbox',
		'choose_downloadable_products'            => 'search',
	);

	/**
	 * Init SUMOSubs_Admin_Product_Settings.
	 */
	public static function init() {
		add_action( 'woocommerce_product_options_general_product_data', __CLASS__ . '::subscription_product_settings' );
		add_action( 'woocommerce_process_product_meta', __CLASS__ . '::save_subscription_product_data' );
		add_action( 'woocommerce_product_after_variable_attributes', __CLASS__ . '::subscription_variation_product_settings', 10, 3 );
		add_action( 'woocommerce_save_product_variation', __CLASS__ . '::save_subscription_variation_data', 10, 2 );
	}

	public static function get_subscription_fields() {
		return self::$subscription_fields;
	}

	/**
	 * Display subscription product setting fields.
	 *
	 * @global object $post The Product post ID
	 */
	public static function subscription_product_settings() {
		global $post;

		$product = wc_get_product( $post );
		if (
				! $product ||
				in_array( $product->get_type(), array( 'variable' ) ) ||
				is_array( get_post_meta( $post->ID, 'sumo_susbcription_status', true ) )
		) {
			return;
		}

		$subscription_duration       = get_post_meta( $post->ID, 'sumo_susbcription_period', true );
		$subscription_duration_value = get_post_meta( $post->ID, 'sumo_susbcription_period_value', true );
		$trial_duration              = get_post_meta( $post->ID, 'sumo_trial_period', true );
		$trial_duration_value        = get_post_meta( $post->ID, 'sumo_trial_period_value', true );
		$synchronize_duration        = get_post_meta( $post->ID, 'sumo_synchronize_period', true );
		$synchronize_duration_value  = get_post_meta( $post->ID, 'sumo_synchronize_period_value', true );
		$synchronize_start_year      = get_post_meta( $post->ID, 'sumo_synchronize_start_year', true );

		$bckwrd_optional_signup_status = 'yes' === get_post_meta( $post->ID, 'sumo_susbcription_signup_fee_is_optional_for_user', true );
		$bckwrd_optional_trial_status  = 'yes' === get_post_meta( $post->ID, 'sumo_susbcription_trial_is_optional_for_user', true );

		$signup_status = $bckwrd_optional_signup_status ? '3' : get_post_meta( $post->ID, 'sumo_susbcription_signusumoee_enable_disable', true );
		$trial_status  = $bckwrd_optional_trial_status ? '3' : get_post_meta( $post->ID, 'sumo_susbcription_trial_enable_disable', true );

		$xtra_time_to_charge_full_fee = get_post_meta( $post->ID, 'sumo_xtra_time_to_charge_full_fee', true );
		$xtra_time_to_charge_full_fee = is_numeric( $xtra_time_to_charge_full_fee ) ? $xtra_time_to_charge_full_fee : get_post_meta( $post->ID, 'sumo_xtra_duration_to_charge_full_fee', true ); //BKWD CMPT

		woocommerce_wp_select( array(
			'id'            => 'sumo_susbcription_status',
			'label'         => __( 'SUMO Subscriptions', 'sumosubscriptions' ),
			'wrapper_class' => 'sumo_subscription_fields',
			'class'         => 'sumosubs-select',
			'options'       => array(
				'2' => __( 'Disable', 'sumosubscriptions' ),
				'1' => __( 'Enable', 'sumosubscriptions' ),
			),
		) );
		?>
		<p class="form-field sumo_susbcription_period_value_field sumo_subscription_fields sumosubscription_simple">
			<label for="sumo_susbcription_period_value"><?php esc_html_e( 'Renewal frequency', 'sumosubscriptions' ); ?></label>
			<span class="sumosubs-duration-every"><?php esc_html_e( 'every ', 'sumosubscriptions' ); ?></span>
			<select class="sumosubs-duration-interval" id="sumo_susbcription_period_value" name="sumo_susbcription_period_value">
				<?php foreach ( sumo_get_subscription_duration_options( $subscription_duration, false ) as $each_key => $each_value ) { ?>
					<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $subscription_duration_value == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
				<?php } ?>
			</select>
			<select class="sumosubs-duration-period" id="sumo_susbcription_period" name="sumo_susbcription_period">
				<?php foreach ( sumosubs_get_duration_period_selector() as $each_key => $each_value ) { ?>
					<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $subscription_duration == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
				<?php } ?>
			</select>
		</p>
		<?php
		if ( SUMOSubs_Synchronization::$sync_enabled_site_wide ) {
			?>
			<p class="form-field sumo_synchronize_duration_fields sumo_subscription_fields sumosubscription_simple">
				<label for="sumo_synchronize_duration"><?php esc_html_e( 'Synchronize renewals', 'sumosubscriptions' ); ?></label>
				<select id="sumo_synchronize_period_value" name="sumo_synchronize_period_value" class="sumosubscription_simple sumosubs-select">
					<?php foreach ( SUMOSubs_Synchronization::get_duration_options( $subscription_duration ) as $each_key => $each_value ) { ?>
						<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $synchronize_duration_value == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
					<?php } ?>
				</select>
				<select id="sumo_synchronize_period" name="sumo_synchronize_period" class="sumosubscription_simple sumosubs-select">
					<?php foreach ( SUMOSubs_Synchronization::get_duration_options( $subscription_duration, true ) as $each_key => $each_value ) { ?>
						<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $synchronize_duration == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
					<?php } ?>
				</select>                    
			</p>
			<?php if ( 'exact-date-r-day' === SUMOSubs_Synchronization::$sync_mode ) { ?>
				<p class="form-field sumo_synchronize_start_year_fields sumo_subscription_fields sumosubscription_simple">
					<label for="sumo_synchronize_start_year"><?php esc_html_e( 'Synchronization starting year', 'sumosubscriptions' ); ?></label>
					<input type="number" min="2016" id="sumo_synchronize_start_year" name="sumo_synchronize_start_year" class="sumosubscription_simple sumosubs-input-number" value="<?php echo ! empty( $synchronize_start_year ) ? esc_attr( $synchronize_start_year ) : '2017'; ?>">
				</p>
			<?php } ?>
			<p class="form-field sumo_subscribed_after_sync_date_type_fields sumo_subscription_fields sumosubscription_simple">
				<label for="sumo_subscribed_after_sync_date_type"><?php esc_html_e( 'If subscription purchased after synchronization date', 'sumosubscriptions' ); ?></label>
				<select id="sumo_subscribed_after_sync_date_type" name="sumo_subscribed_after_sync_date_type" class="sumosubscription_simple sumosubs-select">
					<option value="xtra-time-to-charge-full-fee" <?php selected( get_post_meta( $post->ID, 'sumo_subscribed_after_sync_date_type', true ), 'xtra-time-to-charge-full-fee', true ); ?>><?php esc_html_e( 'Charge full subscription price for specific number of days', 'sumosubscriptions' ); ?></option>
					<option value="cutoff-time-to-not-renew-nxt-subs-cycle" <?php selected( get_post_meta( $post->ID, 'sumo_subscribed_after_sync_date_type', true ), 'cutoff-time-to-not-renew-nxt-subs-cycle', true ); ?>><?php esc_html_e( 'Skip the upcoming renewal', 'sumosubscriptions' ); ?></option>
				</select>
			</p>
			<p class="form-field sumo_xtra_time_to_charge_full_fee_in_sync_fields sumo_subscription_fields sumosubscription_simple">
				<label for="sumo_xtra_time_to_charge_full_fee_in_sync"><?php esc_html_e( 'Duration to charge full subscription price', 'sumosubscriptions' ); ?></label>
				<input type="number" min="0" max="<?php echo esc_attr( SUMOSubs_Synchronization::get_xtra_duration_options( $subscription_duration, $subscription_duration_value ) ); ?>" id="sumo_xtra_time_to_charge_full_fee" name="sumo_xtra_time_to_charge_full_fee" class="sumosubscription_simple sumosubs-input-number" value="<?php echo esc_attr( $xtra_time_to_charge_full_fee ); ?>">
				<?php
				echo wc_help_tip( __( 'Full subscription price will be charged from the user if they purchase the subscription after the synchronization date until the number of days set here. If user purchase during the period after the set number of days and before the next synchronization date, then they will be charged 0 and the subscription status will be in "Pending" until the next synchronization date.
                                       Note: If this option is updated, "Payment for Synchronized Period" settings configured in "Synchronization Settings" will not work.'
								, 'sumosubscriptions' ) );
				?>
			</p>
			<p class="form-field sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields sumo_subscription_fields sumosubscription_simple">                
				<label for="sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync"><?php esc_html_e( 'Duration to skip the upcoming renewal', 'sumosubscriptions' ); ?></label>
				<input type="number" min="0" max="<?php echo esc_attr( SUMOSubs_Synchronization::get_xtra_duration_options( $subscription_duration, $subscription_duration_value ) ); ?>" id="sumo_cutoff_time_to_not_renew_nxt_subs_cycle" name="sumo_cutoff_time_to_not_renew_nxt_subs_cycle" class="sumosubscription_simple sumosubs-input-number" value="<?php echo esc_attr( get_post_meta( $post->ID, 'sumo_cutoff_time_to_not_renew_nxt_subs_cycle', true ) ); ?>">
				<?php
				echo wc_help_tip( __( 'If user purchase the subscription after synchronization date and after specific number of days set here, then for those subscriptions the upcoming renewal will be skipped and the renewal will be processed during the billing date coming after the upcoming synchronization date.
                                       Note: If this option is updated, initial payable amount will be charged based on "Payment for Synchronized Period" settings in "Synchronization Settings"'
								, 'sumosubscriptions' ) );
				?>
			</p>
			<?php
		}
		woocommerce_wp_select( array(
			'id'            => 'sumo_susbcription_trial_enable_disable',
			'wrapper_class' => 'sumo_subscription_fields sumosubscription_simple',
			'class'         => 'sumosubs-select',
			'label'         => __( 'Trial', 'sumosubscriptions' ),
			'options'       => array(
				'2' => __( 'Disable', 'sumosubscriptions' ),
				'1' => __( 'Forced trial', 'sumosubscriptions' ),
				'3' => __( 'Optional trial', 'sumosubscriptions' ),
			),
			'value'         => $trial_status,
		) );
		woocommerce_wp_select( array(
			'id'            => 'sumo_susbcription_fee_type_selector',
			'wrapper_class' => 'sumo_subscription_fields sumosubscription_simple',
			'class'         => 'sumosubs-select',
			'label'         => __( 'Trial type', 'sumosubscriptions' ),
			'options'       => array(
				'free' => __( 'Free trial', 'sumosubscriptions' ),
				'paid' => __( 'Paid trial', 'sumosubscriptions' ),
			),
		) );
		woocommerce_wp_text_input( array(
			'id'            => 'sumo_trial_price',
			'label'         => __( 'Trial fee', 'sumosubscriptions' ) . '(' . get_woocommerce_currency_symbol() . ')',
			'wrapper_class' => 'sumo_subscription_fields sumosubscription_simple',
			'class'         => 'sumosubs-input-price',
			'placeholder'   => __( 'Enter the trial fee', 'sumosubscriptions' ),
			'data_type'     => 'price',
		) );
		?>
		<p class="form-field sumo_trial_period_value_field sumo_subscription_fields sumosubscription_simple">
			<label for="sumo_trial_period_value"><?php esc_html_e( 'Trial duration', 'sumosubscriptions' ); ?></label>
			<select class="sumosubs-trial-duration-interval" id="sumo_trial_period_value" name="sumo_trial_period_value">
				<?php foreach ( sumo_get_subscription_duration_options( $trial_duration, false ) as $each_key => $each_value ) { ?>
					<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $trial_duration_value == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
				<?php } ?>
			</select>
			<select class="sumosubs-trial-duration-period" id="sumo_trial_period" name="sumo_trial_period">
				<?php foreach ( sumosubs_get_duration_period_selector() as $each_key => $each_value ) { ?>
					<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $trial_duration == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
				<?php } ?>
			</select>
		</p>
		<?php
		woocommerce_wp_select( array(
			'id'            => 'sumo_susbcription_signusumoee_enable_disable',
			'wrapper_class' => 'sumo_subscription_fields sumosubscription_simple',
			'class'         => 'sumosubs-select',
			'label'         => __( 'Charge sign up fee', 'sumosubscriptions' ),
			'options'       => array(
				'2' => __( 'Disable', 'sumosubscriptions' ),
				'1' => __( 'Forced sign up', 'sumosubscriptions' ),
				'3' => __( 'Optional sign up', 'sumosubscriptions' ),
			),
			'value'         => $signup_status,
		) );
		woocommerce_wp_text_input( array(
			'id'            => 'sumo_signup_price',
			'label'         => __( 'Sign up fee', 'sumosubscriptions' ) . '(' . get_woocommerce_currency_symbol() . ')',
			'wrapper_class' => 'sumo_subscription_fields sumosubscription_simple',
			'class'         => 'sumosubs-input-price',
			'placeholder'   => __( 'Enter the sign up fee', 'sumosubscriptions' ),
			'data_type'     => 'price',
		) );
		woocommerce_wp_select( array(
			'id'            => 'sumo_recurring_period_value',
			'wrapper_class' => 'sumo_subscription_fields sumosubscription_simple',
			'class'         => 'sumosubs-select',
			'label'         => __( 'Number of installments', 'sumosubscriptions' ),
			'options'       => sumo_get_subscription_recurring_options(),
		) );

		if ( 'yes' === SUMOSubs_Admin_Options::get_option( 'enable_additional_digital_downloads' ) ) {
			woocommerce_wp_checkbox( array(
				'id'            => 'sumo_enable_additional_digital_downloads',
				'wrapper_class' => 'sumo_subscription_fields sumosubscription_simple',
				'label'         => __( 'Enable additional digital downloads', 'sumosubscriptions' ),
			) );
			?>
			<p class="form-field sumo_choose_downloadable_products_field sumo_subscription_fields sumosubscription_simple">
				<?php
				sumosubs_wc_search_field( array(
					'class'       => 'wc-product-search sumosubscription_simple',
					'id'          => 'sumo_choose_downloadable_products',
					'type'        => 'product',
					'action'      => 'sumosubscription_json_search_downloadable_products_and_variations',
					'title'       => __( 'Choose the downloadable product(s)', 'sumosubscriptions' ),
					'placeholder' => __( 'Search for a product&hellip;', 'sumosubscriptions' ),
					'options'     => ( array ) get_post_meta( $post->ID, 'sumo_choose_downloadable_products', true ),
				) );
				?>
			</p>
			<?php
		}
	}

	/**
	 * Display subscription variation product setting fields.
	 *
	 * @param int $loop
	 * @param mixed $variation_data
	 * @param object $variation The Variation post ID
	 */
	public static function subscription_variation_product_settings( $loop, $variation_data, $variation ) {
		$variation_data              = get_post_meta( $variation->ID );
		$subscription_duration       = get_post_meta( $variation->ID, 'sumo_susbcription_period', true );
		$subscription_duration_value = get_post_meta( $variation->ID, 'sumo_susbcription_period_value', true );
		$trial_duration              = get_post_meta( $variation->ID, 'sumo_trial_period', true );
		$trial_duration_value        = get_post_meta( $variation->ID, 'sumo_trial_period_value', true );
		$synchronize_duration        = get_post_meta( $variation->ID, 'sumo_synchronize_period', true );
		$synchronize_duration_value  = get_post_meta( $variation->ID, 'sumo_synchronize_period_value', true );
		$synchronize_start_year      = get_post_meta( $variation->ID, 'sumo_synchronize_start_year', true );

		$bckwrd_optional_signup_status = 'yes' === get_post_meta( $variation->ID, 'sumo_susbcription_signup_fee_is_optional_for_user', true );
		$bckwrd_optional_trial_status  = 'yes' === get_post_meta( $variation->ID, 'sumo_susbcription_trial_is_optional_for_user', true );

		$signup_status = $bckwrd_optional_signup_status ? '3' : get_post_meta( $variation->ID, 'sumo_susbcription_signusumoee_enable_disable', true );
		$trial_status  = $bckwrd_optional_trial_status ? '3' : get_post_meta( $variation->ID, 'sumo_susbcription_trial_enable_disable', true );

		$xtra_time_to_charge_full_fee = get_post_meta( $variation->ID, 'sumo_xtra_time_to_charge_full_fee', true );
		$xtra_time_to_charge_full_fee = is_numeric( $xtra_time_to_charge_full_fee ) ? $xtra_time_to_charge_full_fee : get_post_meta( $variation->ID, 'sumo_xtra_duration_to_charge_full_fee', true ); //BKWD CMPT

		woocommerce_wp_select( array(
			'id'            => 'sumo_susbcription_status[' . $loop . ']',
			'label'         => __( 'SUMO Subscriptions', 'sumosubscriptions' ),
			'wrapper_class' => "sumo_subscription_fields{$loop} sumo_subscription_fields_wrapper",
			'class'         => 'sumosubs-select',
			'value'         => isset( $variation_data[ 'sumo_susbcription_status' ][ 0 ] ) ? $variation_data[ 'sumo_susbcription_status' ][ 0 ] : '',
			'options'       => array(
				'2' => __( 'Disable', 'sumosubscriptions' ),
				'1' => __( 'Enable', 'sumosubscriptions' ),
			),
		) );
		?>
		<p class="form-field sumo_susbcription_period_value[<?php echo esc_attr( $loop ); ?>]_field  sumo_subscription_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields_wrapper sumosubscription_variable<?php echo esc_attr( $loop ); ?>">
			<label for="sumo_susbcription_period_value[<?php echo esc_attr( $loop ); ?>]"><?php esc_html_e( 'Renewal frequency', 'sumosubscriptions' ); ?></label>
			<span class="sumosubs-duration-every"><?php esc_html_e( 'every ', 'sumosubscriptions' ); ?></span>
			<select class="sumosubs-duration-interval" id="sumo_susbcription_period_value[<?php echo esc_attr( $loop ); ?>]" name="sumo_susbcription_period_value[<?php echo esc_attr( $loop ); ?>]">
				<?php foreach ( sumo_get_subscription_duration_options( $subscription_duration, false ) as $each_key => $each_value ) { ?>
					<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $subscription_duration_value == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
				<?php } ?>
			</select>
			<select  class="sumosubs-duration-period" id="sumo_susbcription_period[<?php echo esc_attr( $loop ); ?>]" name="sumo_susbcription_period[<?php echo esc_attr( $loop ); ?>]">
				<?php foreach ( sumosubs_get_duration_period_selector() as $each_key => $each_value ) { ?>
					<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $subscription_duration == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
				<?php } ?>
			</select>
		</p>
		<?php
		if ( SUMOSubs_Synchronization::$sync_enabled_site_wide ) {
			?>
			<p class="form-field sumo_synchronize_duration_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields_wrapper sumosubscription_variable<?php echo esc_attr( $loop ); ?>">
				<label for="sumo_synchronize_duration[<?php echo esc_attr( $loop ); ?>]"><?php esc_html_e( 'Synchronize renewals', 'sumosubscriptions' ); ?></label>
				<select id="sumo_synchronize_period_value[<?php echo esc_attr( $loop ); ?>]" name="sumo_synchronize_period_value[<?php echo esc_attr( $loop ); ?>]" class="sumosubscription_variable<?php echo esc_attr( $loop ); ?> sumosubs-select">
					<?php foreach ( SUMOSubs_Synchronization::get_duration_options( $subscription_duration ) as $each_key => $each_value ) { ?>
						<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $synchronize_duration_value == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
					<?php } ?>
				</select>
				<select id="sumo_synchronize_period[<?php echo esc_attr( $loop ); ?>]" name="sumo_synchronize_period[<?php echo esc_attr( $loop ); ?>]" class="sumosubscription_variable<?php echo esc_attr( $loop ); ?> sumosubs-select" >
					<?php foreach ( SUMOSubs_Synchronization::get_duration_options( $subscription_duration, true ) as $each_key => $each_value ) { ?>
						<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $synchronize_duration == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
					<?php } ?>
				</select>
			</p>
			<?php if ( 'exact-date-r-day' === SUMOSubs_Synchronization::$sync_mode ) { ?>
				<p class="form-field sumo_synchronize_start_year_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields_wrapper sumosubscription_variable<?php echo esc_attr( $loop ); ?>">
					<label for="sumo_synchronize_start_year[<?php echo esc_attr( $loop ); ?>]"><?php esc_html_e( 'Synchronization starting year', 'sumosubscriptions' ); ?></label>
					<input type="number" min="2016" id="sumo_synchronize_start_year<?php echo esc_attr( $loop ); ?>" name="sumo_synchronize_start_year[<?php echo esc_attr( $loop ); ?>]" class="sumosubscription_variable<?php echo esc_attr( $loop ); ?> sumosubs-input-number" value="<?php echo ! empty( $synchronize_start_year ) ? esc_attr( $synchronize_start_year ) : '2017'; ?>">
				</p>
			<?php } ?>
			<p class="form-field sumo_subscribed_after_sync_date_type_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields_wrapper sumosubscription_variable<?php echo esc_attr( $loop ); ?>">
				<label for="sumo_subscribed_after_sync_date_type[<?php echo esc_attr( $loop ); ?>]"><?php esc_html_e( 'If subscription purchased after synchronization date', 'sumosubscriptions' ); ?></label>
				<select id="sumo_subscribed_after_sync_date_type<?php echo esc_attr( $loop ); ?>" name="sumo_subscribed_after_sync_date_type[<?php echo esc_attr( $loop ); ?>]" class="sumosubscription_variable<?php echo esc_attr( $loop ); ?> sumosubs-select">
					<option value="xtra-time-to-charge-full-fee" <?php selected( get_post_meta( $variation->ID, 'sumo_subscribed_after_sync_date_type', true ), 'xtra-time-to-charge-full-fee', true ); ?>><?php esc_html_e( 'Charge full subscription price for specific number of days', 'sumosubscriptions' ); ?></option>
					<option value="cutoff-time-to-not-renew-nxt-subs-cycle" <?php selected( get_post_meta( $variation->ID, 'sumo_subscribed_after_sync_date_type', true ), 'cutoff-time-to-not-renew-nxt-subs-cycle', true ); ?>><?php esc_html_e( 'Skip the upcoming renewal', 'sumosubscriptions' ); ?></option>
				</select>
			</p>
			<p class="form-field sumo_xtra_time_to_charge_full_fee_in_sync_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields_wrapper sumosubscription_variable<?php echo esc_attr( $loop ); ?>">
				<label for="sumo_xtra_time_to_charge_full_fee_in_sync[<?php echo esc_attr( $loop ); ?>]"><?php esc_html_e( 'Duration to charge full subscription price', 'sumosubscriptions' ); ?></label>
				<input type="number" min="0" max="<?php echo esc_attr( SUMOSubs_Synchronization::get_xtra_duration_options( $subscription_duration, $subscription_duration_value ) ); ?>" id="sumo_xtra_time_to_charge_full_fee<?php echo esc_attr( $loop ); ?>" name="sumo_xtra_time_to_charge_full_fee[<?php echo esc_attr( $loop ); ?>]" class="sumosubscription_variable<?php echo esc_attr( $loop ); ?> sumosubs-input-number" value="<?php echo esc_attr( $xtra_time_to_charge_full_fee ); ?>">
				<?php
				echo wc_help_tip( __( 'Full subscription price will be charged from the user if they purchase the subscription after the synchronization date until the number of days set here. If user purchase during the period after the set number of days and before the next synchronization date, then they will be charged 0 and the subscription status will be in "Pending" until the next synchronization date.
                                       Note: If this option is updated, "Payment for Synchronized Period" settings configured in "Synchronization Settings" will not work.'
								, 'sumosubscriptions' ) );
				?>
			</p>
			<p class="form-field sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields_wrapper sumosubscription_variable<?php echo esc_attr( $loop ); ?>">               
				<label for="sumo_cutoff_time_to_not_renew_nxt_subs_cycle_in_sync[<?php echo esc_attr( $loop ); ?>]"><?php esc_html_e( 'Duration to skip the upcoming renewal', 'sumosubscriptions' ); ?></label>
				<input type="number" min="0" max="<?php echo esc_attr( SUMOSubs_Synchronization::get_xtra_duration_options( $subscription_duration, $subscription_duration_value ) ); ?>" id="sumo_cutoff_time_to_not_renew_nxt_subs_cycle<?php echo esc_attr( $loop ); ?>" name="sumo_cutoff_time_to_not_renew_nxt_subs_cycle[<?php echo esc_attr( $loop ); ?>]" class="sumosubscription_variable<?php echo esc_attr( $loop ); ?> sumosubs-input-number" value="<?php echo esc_attr( get_post_meta( $variation->ID, 'sumo_cutoff_time_to_not_renew_nxt_subs_cycle', true ) ); ?>">
				<?php
				echo wc_help_tip( __( 'If user purchase the subscription after synchronization date and after specific number of days set here, then for those subscriptions the upcoming renewal will be skipped and the renewal will be processed during the billing date coming after the upcoming synchronization date.
                                       Note: If this option is updated, initial payable amount will be charged based on "Payment for Synchronized Period" settings in "Synchronization Settings"'
								, 'sumosubscriptions' ) );
				?>
			</p>
			<?php
		}
		woocommerce_wp_select( array(
			'id'            => 'sumo_susbcription_trial_enable_disable[' . $loop . ']',
			'wrapper_class' => "sumo_subscription_fields{$loop} sumosubscription_variable{$loop} sumo_subscription_fields_wrapper",
			'class'         => 'sumosubs-select',
			'label'         => __( 'Trial', 'sumosubscriptions' ),
			'value'         => isset( $variation_data[ 'sumo_susbcription_trial_enable_disable' ][ 0 ] ) ? $variation_data[ 'sumo_susbcription_trial_enable_disable' ][ 0 ] : '',
			'options'       => array(
				'2' => __( 'Disable', 'sumosubscriptions' ),
				'1' => __( 'Forced trial', 'sumosubscriptions' ),
				'3' => __( 'Optional trial', 'sumosubscriptions' ),
			),
			'value'         => $trial_status,
		) );
		woocommerce_wp_select( array(
			'id'            => 'sumo_susbcription_fee_type_selector[' . $loop . ']',
			'wrapper_class' => "sumo_subscription_fields{$loop} sumosubscription_variable{$loop} sumo_subscription_fields_wrapper",
			'class'         => 'sumosubs-select',
			'label'         => __( 'Trial type', 'sumosubscriptions' ),
			'value'         => isset( $variation_data[ 'sumo_susbcription_fee_type_selector' ][ 0 ] ) ? $variation_data[ 'sumo_susbcription_fee_type_selector' ][ 0 ] : '',
			'options'       => array(
				'free' => __( 'Free trial', 'sumosubscriptions' ),
				'paid' => __( 'Paid trial', 'sumosubscriptions' ),
			),
		) );
		woocommerce_wp_text_input( array(
			'id'            => 'sumo_trial_price[' . $loop . ']',
			'label'         => __( 'Trial fee', 'sumosubscriptions' ) . '(' . get_woocommerce_currency_symbol() . ')',
			'wrapper_class' => "sumo_subscription_fields{$loop} sumosubscription_variable{$loop} sumo_subscription_fields_wrapper",
			'class'         => 'sumosubs-input-price',
			'data_type'     => 'price',
			'placeholder'   => __( 'Enter the trial fee', 'sumosubscriptions' ),
			'value'         => isset( $variation_data[ 'sumo_trial_price' ][ 0 ] ) ? $variation_data[ 'sumo_trial_price' ][ 0 ] : '',
		) );
		?>
		<p class="form-field sumo_trial_period_value[<?php echo esc_attr( $loop ); ?>]_field  sumo_subscription_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields_wrapper sumosubscription_variable<?php echo esc_attr( $loop ); ?>">
			<label for="sumo_trial_period_value[<?php echo esc_attr( $loop ); ?>]"><?php esc_html_e( 'Trial duration', 'sumosubscriptions' ); ?></label>
			<select class="sumosubs-trial-duration-interval" id="sumo_trial_period_value[<?php echo esc_attr( $loop ); ?>]" name="sumo_trial_period_value[<?php echo esc_attr( $loop ); ?>]">
				<?php foreach ( sumo_get_subscription_duration_options( $trial_duration, false ) as $each_key => $each_value ) { ?>
					<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $trial_duration_value == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
				<?php } ?>
			</select>
			<select class="sumosubs-trial-duration-period" id="sumo_trial_period[<?php echo esc_attr( $loop ); ?>]" name="sumo_trial_period[<?php echo esc_attr( $loop ); ?>]">
				<?php foreach ( sumosubs_get_duration_period_selector() as $each_key => $each_value ) { ?>
					<option value="<?php echo esc_attr( $each_key ); ?>" <?php selected( $trial_duration == $each_key, true, true ); ?>><?php echo esc_html( $each_value ); ?></option>
				<?php } ?>
			</select>
		</p>
		<?php
		woocommerce_wp_select( array(
			'id'            => 'sumo_susbcription_signusumoee_enable_disable[' . $loop . ']',
			'wrapper_class' => "sumo_subscription_fields{$loop} sumosubscription_variable{$loop} sumo_subscription_fields_wrapper",
			'class'         => 'sumosubs-select',
			'label'         => __( 'Charge sign up fee', 'sumosubscriptions' ),
			'value'         => isset( $variation_data[ 'sumo_susbcription_signusumoee_enable_disable' ][ 0 ] ) ? $variation_data[ 'sumo_susbcription_signusumoee_enable_disable' ][ 0 ] : '',
			'options'       => array(
				'2' => __( 'Disable', 'sumosubscriptions' ),
				'1' => __( 'Forced sign up', 'sumosubscriptions' ),
				'3' => __( 'Optional sign up', 'sumosubscriptions' ),
			),
			'value'         => $signup_status,
		) );
		woocommerce_wp_text_input( array(
			'id'            => 'sumo_signup_price[' . $loop . ']',
			'label'         => __( 'Sign up fee', 'sumosubscriptions' ) . '(' . get_woocommerce_currency_symbol() . ')',
			'wrapper_class' => "sumo_subscription_fields{$loop} sumosubscription_variable{$loop} sumo_subscription_fields_wrapper",
			'class'         => 'sumosubs-input-price',
			'data_type'     => 'price',
			'placeholder'   => __( 'Enter the sign up fee', 'sumosubscriptions' ),
			'value'         => isset( $variation_data[ 'sumo_signup_price' ][ 0 ] ) ? $variation_data[ 'sumo_signup_price' ][ 0 ] : '',
		) );
		woocommerce_wp_select( array(
			'id'            => 'sumo_recurring_period_value[' . $loop . ']',
			'wrapper_class' => "sumo_subscription_fields{$loop} sumosubscription_variable{$loop} sumo_subscription_fields_wrapper",
			'class'         => 'sumosubs-select',
			'label'         => __( 'Number of installments', 'sumosubscriptions' ),
			'value'         => isset( $variation_data[ 'sumo_recurring_period_value' ][ 0 ] ) ? $variation_data[ 'sumo_recurring_period_value' ][ 0 ] : '',
			'options'       => sumo_get_subscription_recurring_options(),
		) );

		if ( 'yes' === SUMOSubs_Admin_Options::get_option( 'enable_additional_digital_downloads' ) ) {
			woocommerce_wp_checkbox( array(
				'id'            => 'sumo_enable_additional_digital_downloads[' . $loop . ']',
				'wrapper_class' => "sumo_subscription_fields{$loop} sumosubscription_variable{$loop} sumo_subscription_fields_wrapper",
				'label'         => __( 'Enable additional digital downloads', 'sumosubscriptions' ),
				'value'         => isset( $variation_data[ 'sumo_enable_additional_digital_downloads' ][ 0 ] ) ? $variation_data[ 'sumo_enable_additional_digital_downloads' ][ 0 ] : '',
			) );
			?>
			<p class="form-field sumo_choose_downloadable_products_field<?php echo esc_attr( $loop ); ?> sumo_subscription_fields<?php echo esc_attr( $loop ); ?> sumo_subscription_fields_wrapper sumosubscription_variable<?php echo esc_attr( $loop ); ?>">
				<?php
				sumosubs_wc_search_field( array(
					'class'       => "wc-product-search sumosubscription_variable{$loop}",
					'id'          => "sumo_choose_downloadable_products[{$loop}]",
					'type'        => 'product',
					'action'      => 'sumosubscription_json_search_downloadable_products_and_variations',
					'title'       => __( 'Choose the downloadable product(s)', 'sumosubscriptions' ),
					'placeholder' => __( 'Search for a product&hellip;', 'sumosubscriptions' ),
					'options'     => ( array ) get_post_meta( $variation->ID, 'sumo_choose_downloadable_products', true ),
				) );
				?>
			</p>
			<?php
		}
	}

	/**
	 * Save subscription product data.
	 *
	 * @param int $product_id The Product post ID
	 */
	public static function save_subscription_product_data( $product_id ) {
		if ( empty( $_POST[ 'woocommerce_meta_nonce' ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ 'woocommerce_meta_nonce' ] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		global $sitepress;
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && is_object( $sitepress ) ) {
			$trid         = $sitepress->get_element_trid( $product_id );
			$translations = $sitepress->get_element_translations( $trid );

			if ( ! empty( $translations ) ) {
				foreach ( $translations as $translation ) {
					self::save_meta( $translation->element_id, '', $_POST );
				}
			} else {
				self::save_meta( $product_id, '', $_POST );
			}
		} else {
			self::save_meta( $product_id, '', $_POST );
		}
	}

	/**
	 * Save subscription variation product data.
	 *
	 * @param int $variation_id The Variation post ID
	 * @param int $loop
	 */
	public static function save_subscription_variation_data( $variation_id, $loop ) {
		check_ajax_referer( 'save-variations', 'security' );

		global $sitepress;
		if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && is_object( $sitepress ) ) {
			$trid         = $sitepress->get_element_trid( $variation_id );
			$translations = $sitepress->get_element_translations( $trid );

			if ( ! empty( $translations ) ) {
				foreach ( $translations as $translation ) {
					self::save_meta( $translation->element_id, $loop, $_POST );
				}
			} else {
				self::save_meta( $variation_id, $loop, $_POST );
			}
		} else {
			self::save_meta( $variation_id, $loop, $_POST );
		}
	}

	/**
	 * Save subscription product meta.
	 *
	 * @param int $product_id The Product post ID
	 * @param int $loop The Variation loop
	 */
	public static function save_meta( $product_id, $loop = '', $props = array() ) {
		foreach ( self::$subscription_fields as $field_name => $type ) {
			$meta_key         = "sumo_{$field_name}";
			$posted_meta_data = isset( $props[ "$meta_key" ] ) ? $props[ "$meta_key" ] : '';

			update_post_meta( $product_id, 'sumo_subscription_version', SUMO_SUBSCRIPTIONS_VERSION );

			if ( is_numeric( $loop ) ) {
				if ( in_array( $type, array( 'checkbox', 'search' ) ) ) {
					delete_post_meta( $product_id, "$meta_key" );
				}

				if ( isset( $posted_meta_data[ $loop ] ) ) {
					if ( 'price' === $type ) {
						$posted_meta_data[ $loop ] = wc_format_decimal( $posted_meta_data[ $loop ] );
					} else if ( 'search' === $type ) {
						$posted_meta_data[ $loop ] = ! is_array( $posted_meta_data[ $loop ] ) ? array_filter( array_map( 'absint', explode( ',', $posted_meta_data ) ) ) : $posted_meta_data[ $loop ];
					}
					update_post_meta( $product_id, "$meta_key", wc_clean( $posted_meta_data[ $loop ] ) );

					//backward compatible
					switch ( $meta_key ) {
						case 'sumo_susbcription_signusumoee_enable_disable':
							delete_post_meta( $product_id, 'sumo_susbcription_signup_fee_is_optional_for_user' );
							break;
						case 'sumo_susbcription_trial_enable_disable':
							delete_post_meta( $product_id, 'sumo_susbcription_trial_is_optional_for_user' );
							break;
						case 'sumo_xtra_time_to_charge_full_fee':
							delete_post_meta( $product_id, 'sumo_xtra_duration_to_charge_full_fee' );
							break;
					}
				}
			} else {
				if ( 'price' === $type ) {
					$posted_meta_data = wc_format_decimal( $posted_meta_data );
				} else if ( 'search' === $type ) {
					$posted_meta_data = ! is_array( $posted_meta_data ) ? array_filter( array_map( 'absint', explode( ',', $posted_meta_data ) ) ) : $posted_meta_data;
				}
				update_post_meta( $product_id, "$meta_key", $posted_meta_data );

				//backward compatible
				switch ( $meta_key ) {
					case 'sumo_susbcription_signusumoee_enable_disable':
						delete_post_meta( $product_id, 'sumo_susbcription_signup_fee_is_optional_for_user' );
						break;
					case 'sumo_susbcription_trial_enable_disable':
						delete_post_meta( $product_id, 'sumo_susbcription_trial_is_optional_for_user' );
						break;
					case 'sumo_xtra_time_to_charge_full_fee':
						delete_post_meta( $product_id, 'sumo_xtra_duration_to_charge_full_fee' );
						break;
				}
			}
		}

		$products                  = isset( $props[ 'sumo_subscription_product_ids' ] ) ? wc_clean( wp_unslash( $props[ 'sumo_subscription_product_ids' ] ) ) : '';
		$reminder_emails           = isset( $props[ 'sumosubs_send_payment_reminder_email' ] ) ? wc_clean( wp_unslash( $props[ 'sumosubs_send_payment_reminder_email' ] ) ) : '';
		$order_confirmation_emails = isset( $props[ 'sumosubs_send_order_confirmation_email' ] ) ? wc_clean( wp_unslash( $props[ 'sumosubs_send_order_confirmation_email' ] ) ) : '';

		if ( is_array( $products ) ) {
			foreach ( $products as $id ) {
				update_post_meta( $id, 'sumosubs_send_payment_reminder_email', array(
					'auto'   => 'no',
					'manual' => 'no',
				) );

				update_post_meta( $id, 'sumosubs_send_order_confirmation_email', array(
					'processing' => 'no',
					'completed'  => 'no',
				) );

				if ( isset( $reminder_emails[ $id ] ) ) {
					update_post_meta( $id, 'sumosubs_send_payment_reminder_email', wp_parse_args( $reminder_emails[ $id ], array(
						'auto'   => 'no',
						'manual' => 'no',
					) ) );
				}

				if ( isset( $order_confirmation_emails[ $id ] ) ) {
					update_post_meta( $id, 'sumosubs_send_order_confirmation_email', wp_parse_args( $order_confirmation_emails[ $id ], array(
						'processing' => 'no',
						'completed'  => 'no',
					) ) );
				}
			}
		}
	}
}

SUMOSubs_Admin_Product_Settings::init();
