<?php

/**
 * Order Subscription Settings.
 * 
 * @class SUMOSubs_Admin_Settings_Order_Subscription
 */
class SUMOSubs_Admin_Settings_Order_Subscription extends SUMOSubs_Abstract_Admin_Settings {

	/**
	 * SUMOSubs_Admin_Settings_Order_Subscription constructor.
	 */
	public function __construct() {
		$this->id            = 'order_subscription';
		$this->label         = __( 'Order Subscription', 'sumosubscriptions' );
		$this->custom_fields = array(
			'get_predefined_subscription_duration',
			'get_userdefined_subscription_period_intervals',
			'get_userdefined_subscription_length',
			'get_allowed_product_ids',
			'get_restricted_product_ids',
			'get_allowed_product_cat_ids',
			'get_restricted_product_cat_ids',
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
				'name' => __( 'General Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'general_section',
			),
			array(
				'name'    => __( 'Enable Order Subscription', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_allow_in_checkout' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_allow_in_checkout' ),
				'type'    => 'checkbox',
				'desc'    => __( 'When enabled, "Enable Order Subscription" checkbox will be displayed in checkout page using which user can purchase the whole cart items as a single subscription. Note: Order Subscription won’t work if the cart contains product level subscription products.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Display Order Subscription in Cart Page', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_allow_in_cart' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_allow_in_cart' ),
				'type'    => 'checkbox',
				'desc'    => __( 'When enabled, "Enable Order Subscription" checkbox will be displayed in cart page using which user can purchase the whole cart items as a single subscription. Note: Order Subscription won’t work if the cart contains product level subscription products.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Order Subscription Checkbox Default Value', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_subscribed_default' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_subscribed_default' ),
				'type'    => 'checkbox',
				'desc'    => __( 'When enabled, the "order subscription" checkbox will be enabled by default. If the user doesn\'t want to purchase the order as a subscription, they can uncheck the "order subscription" checkbox and place the order.', 'sumosubscriptions' ),
			),
			array(
				'name'     => __( 'Order Subscription Values', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_subscribe_values' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'order_subs_subscribe_values' ),
				'type'     => 'select',
				'options'  => array(
					'predefined'  => __( 'Predefined by admin', 'sumosubscriptions' ),
					'userdefined' => __( 'Can be chosen by user', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'If "Predefined by admin" option is selected, admin has to define the subscription values. If "Can be chosen by user" is selected, user can choose their subscription values as per their needs.', 'sumosubscriptions' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_predefined_subscription_duration' ),
			),
			array(
				'name'     => __( 'Number of Installments', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_predefined_subscription_length' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'order_subs_predefined_subscription_length' ),
				'class'    => 'sumosubs-select',
				'type'     => 'select',
				'options'  => sumo_get_subscription_recurring_options(),
				'desc_tip' => __( 'Set the number of installments the subscriptions renewal will happen. Note: Parent order will also be added as an installment.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Renewal Frequency Values User can Select', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_userdefined_subscription_periods' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_userdefined_subscription_periods' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => sumosubs_get_duration_period_selector(),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_userdefined_subscription_period_intervals' ),
			),
			array(
				'name'    => __( 'Allow User to Select Number of Installments', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_userdefined_allow_indefinite_subscription_length' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_userdefined_allow_indefinite_subscription_length' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If disabled, "Number of Installments" will be automatically set as "Indefinite"', 'sumosubscriptions' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_userdefined_subscription_length' ),
			),
			array(
				'name'    => __( 'Charge Sign Up Fee', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_charge_signupfee' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_charge_signupfee' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Enable this option if you want to charge sign up fee for order subscription', 'sumosubscriptions' ),
			),
			array(
				/* translators: 1: currency symbol */
				'name'    => sprintf( __( 'Sign Up Fee(%s)', 'sumosubscriptions' ), get_woocommerce_currency_symbol() ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_signupfee' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_signupfee' ),
				'type'    => 'text',
			),
			array( 'type' => 'sectionend', 'id' => 'general_section' ),
			array(
				'name' => __( 'Restriction Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'restriction_section',
			),
			array(
				'name'    => __( 'Product/Category Filter', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_product_wide_selection' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_product_wide_selection' ),
				'type'    => 'select',
				'options' => array(
					'all-products'               => __( 'All products', 'sumosubscriptions' ),
					'allowed-product-ids'        => __( 'Include products', 'sumosubscriptions' ),
					'restricted-product-ids'     => __( 'Exclude products', 'sumosubscriptions' ),
					'allowed-product-cat-ids'    => __( 'Include categories', 'sumosubscriptions' ),
					'restricted-product-cat-ids' => __( 'Exclude categories', 'sumosubscriptions' ),
				),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_allowed_product_ids' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_restricted_product_ids' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_allowed_product_cat_ids' ),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_restricted_product_cat_ids' ),
			),
			array(
				'name'              => __( 'Minimum Order Total to Display Order Subscription', 'sumosubscriptions' ),
				'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_min_order_total' ),
				'default'           => SUMOSubs_Admin_Options::get_option_default( 'order_subs_min_order_total' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '0.01',
				),
			),
			array(
				'name'    => __( 'Consider Minimum Order Total Based on', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_min_order_total_consideration' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_min_order_total_consideration' ),
				'type'    => 'select',
				'options' => array(
					'total'    => __( 'Cart Total', 'sumosubscriptions' ),
					'subtotal' => __( 'Cart Subtotal', 'sumosubscriptions' ),
				),
			),
			array( 'type' => 'sectionend', 'id' => 'restriction_section' ),
			array(
				'name' => __( 'Display Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'display_section',
			),
			array(
				'name'    => __( 'Order Subscription Position in Cart Page', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_cart_position' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_cart_position' ),
				'type'    => 'select',
				'options' => array(
					'before_cart_totals'  => 'Woocommerce Before Cart Totals',
					'after_cart_contents' => 'Woocommerce After Cart Contents',
					'proceed_to_checkout' => 'Woocommerce Proceed to Checkout',
				),
				'desc'    => __( 'Some themes doesn’t support all the positions. If the option is not displayed, then try changing to a different position and check.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Order Subscription Position in Checkout Page', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_checkout_position' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_checkout_position' ),
				'type'    => 'select',
				'options' => array(
					'checkout_order_review'           => 'Woocommerce Checkout Order Review',
					'checkout_after_customer_details' => 'Woocommerce Checkout After Customer Details',
					'before_checkout_form'            => 'Woocommerce Before Checkout Form',
					'checkout_before_order_review'    => 'Woocommerce Checkout Before Order Review',
				),
				'desc'    => __( 'Some themes doesn’t support all the positions. If the option is not displayed, then try changing to a different position and check.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Order Subscription Checkbox Label', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_subscribe_label' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_subscribe_label' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Renewal Frequency Label in Frontend', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_subscription_duration_label' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_subscription_duration_label' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Number of Installments Label in Frontend', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_subscription_length_label' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_subscription_length_label' ),
				'type'    => 'textarea',
			),
			array(
				'name'    => __( 'Custom CSS', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_inline_style' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subs_inline_style' ),
				'type'    => 'textarea',
			),
			array( 'type' => 'sectionend', 'id' => 'display_section' ),
				) );
	}

	/**
	 * Custom type field.
	 */
	public function get_predefined_subscription_duration() {
		$chosen_interval = SUMOSubs_Admin_Options::get_option( 'order_subs_predefined_subscription_period_interval' );
		$chosen_period   = SUMOSubs_Admin_Options::get_option( 'order_subs_predefined_subscription_period' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Renewal Frequency', 'sumosubscriptions' ); ?></th>
			<td class="forminp">
				<span class="sumosubs-duration-every"><?php esc_html_e( 'every ', 'sumosubscriptions' ); ?></span>
				<select class="sumosubs-duration-interval" id="sumosubs_order_subs_predefined_subscription_period_interval" name="sumosubs_order_subs_predefined_subscription_period_interval">
					<?php foreach ( sumo_get_subscription_duration_options( SUMOSubs_Admin_Options::get_option( 'order_subs_predefined_subscription_period' ), false ) as $interval => $label ) : ?>
						<option value="<?php echo esc_attr( $interval ); ?>" <?php selected( $interval, $chosen_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<select class="sumosubs-duration-period" id="sumosubs_order_subs_predefined_subscription_period" name="sumosubs_order_subs_predefined_subscription_period">
					<?php foreach ( sumosubs_get_duration_period_selector() as $period => $label ) : ?>
						<option value="<?php echo esc_attr( $period ); ?>" <?php selected( $period, $chosen_period, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_userdefined_subscription_period_intervals() {
		$min         = SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_min_subscription_period_intervals' );
		$max         = SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_max_subscription_period_intervals' );
		$default_min = SUMOSubs_Admin_Options::get_option_default( 'order_subs_userdefined_min_subscription_period_intervals' );
		$default_max = SUMOSubs_Admin_Options::get_option_default( 'order_subs_userdefined_max_subscription_period_intervals' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Renewal Frequency Minimum and Maximum Values User can Select', 'sumosubscriptions' ); ?></th>
			<td class="forminp">
				<table id="sumosubs_order_subsc_userdefined_subscription_period_intervals">
					<?php foreach ( sumosubs_get_duration_period_selector() as $period => $label ) : ?>
						<tr>
							<th><?php /* translators: 1: duration period label */ printf( esc_html__( '%s between', 'sumosubscriptions' ), esc_html( $label ) ); ?></th>
							<td>
								<input id="sumosubs_order_subs_userdefined_min_subscription_period_intervals_<?php echo esc_attr( $period ); ?>" name="sumosubs_order_subs_userdefined_min_subscription_period_intervals[<?php echo esc_attr( $period ); ?>]" type="number" min="<?php echo esc_attr( $default_min[ $period ] ); ?>" max="<?php echo esc_attr( $default_max[ $period ] ); ?>" value="<?php echo esc_attr( $min[ $period ] ); ?>"></input>
								<input id="sumosubs_order_subs_userdefined_max_subscription_period_intervals_<?php echo esc_attr( $period ); ?>" name="sumosubs_order_subs_userdefined_max_subscription_period_intervals[<?php echo esc_attr( $period ); ?>]" type="number" min="<?php echo esc_attr( $default_min[ $period ] ); ?>" max="<?php echo esc_attr( $default_max[ $period ] ); ?>" value="<?php echo esc_attr( $max[ $period ] ); ?>"></input>
							</td>
						</tr>   
					<?php endforeach; ?>
				</table>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_userdefined_subscription_length() {
		$min = SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_min_subscription_length' );
		$max = SUMOSubs_Admin_Options::get_option( 'order_subs_userdefined_max_subscription_length' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Number of Installments User can Select', 'sumosubscriptions' ); ?></th>
			<td class="forminp">
				<select class="sumosubs-duration-length" id="sumosubs_order_subs_userdefined_min_subscription_length" name="sumosubs_order_subs_userdefined_min_subscription_length">
					<?php foreach ( sumo_get_subscription_recurring_options( false ) as $length => $label ) : ?>
						<option value="<?php echo esc_attr( $length ); ?>" <?php selected( $length, $min, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<select class="sumosubs-duration-length" id="sumosubs_order_subs_userdefined_max_subscription_length" name="sumosubs_order_subs_userdefined_max_subscription_length">
					<?php foreach ( sumo_get_subscription_recurring_options( 'first' ) as $length => $label ) : ?>
						<option value="<?php echo esc_attr( $length ); ?>" <?php selected( $length, $max, true ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_allowed_product_ids() {
		sumosubs_wc_search_field( array(
			'class'       => 'wc-product-search',
			'id'          => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_allowed_product_ids' ),
			'type'        => 'product',
			'action'      => 'woocommerce_json_search_products_and_variations',
			'title'       => __( 'Select Product(s)', 'sumosubscriptions' ),
			'placeholder' => __( 'Search for a product&hellip;', 'sumosubscriptions' ),
			'options'     => SUMOSubs_Admin_Options::get_option( 'order_subs_allowed_product_ids' ),
		) );
	}

	/**
	 * Custom type field.
	 */
	public function get_restricted_product_ids() {
		sumosubs_wc_search_field( array(
			'class'       => 'wc-product-search',
			'id'          => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_restricted_product_ids' ),
			'type'        => 'product',
			'action'      => 'woocommerce_json_search_products_and_variations',
			'title'       => __( 'Select Product(s) to Exclude', 'sumosubscriptions' ),
			'placeholder' => __( 'Search for a product&hellip;', 'sumosubscriptions' ),
			'options'     => SUMOSubs_Admin_Options::get_option( 'order_subs_restricted_product_ids' ),
		) );
	}

	/**
	 * Custom type field.
	 */
	public function get_allowed_product_cat_ids() {
		?>
		<tr>
			<th>
				<?php esc_html_e( 'Select Categories', 'sumosubscriptions' ); ?>
			</th>
			<td> 
				<?php
				sumosubs_wc_enhanced_select_field( array(
					'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_allowed_product_cat_ids' ),
					'selected' => SUMOSubs_Admin_Options::get_option( 'order_subs_allowed_product_cat_ids' ),
					'options'  => sumosubs_get_product_categories(),
				) );
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Custom type field.
	 */
	public function get_restricted_product_cat_ids() {
		?>
		<tr>
			<th>
				<?php esc_html_e( 'Select Categorie(s) to Exclude', 'sumosubscriptions' ); ?>
			</th>
			<td>       
				<?php
				sumosubs_wc_enhanced_select_field( array(
					'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'order_subs_restricted_product_cat_ids' ),
					'selected' => SUMOSubs_Admin_Options::get_option( 'order_subs_restricted_product_cat_ids' ),
					'options'  => sumosubs_get_product_categories(),
				) );
				?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the custom options once.
	 */
	public function custom_types_add_options() {
		SUMOSubs_Admin_Options::add_option( 'order_subs_predefined_subscription_period_interval' );
		SUMOSubs_Admin_Options::add_option( 'order_subs_predefined_subscription_period' );
		SUMOSubs_Admin_Options::add_option( 'order_subs_userdefined_min_subscription_period_intervals' );
		SUMOSubs_Admin_Options::add_option( 'order_subs_userdefined_max_subscription_period_intervals' );
		SUMOSubs_Admin_Options::add_option( 'order_subs_userdefined_min_subscription_length' );
		SUMOSubs_Admin_Options::add_option( 'order_subs_userdefined_max_subscription_length' );
		SUMOSubs_Admin_Options::add_option( 'order_subs_allowed_product_ids' );
		SUMOSubs_Admin_Options::add_option( 'order_subs_restricted_product_ids' );
		SUMOSubs_Admin_Options::add_option( 'order_subs_allowed_product_cat_ids' );
		SUMOSubs_Admin_Options::add_option( 'order_subs_restricted_product_cat_ids' );
	}

	/**
	 * Delete the custom options.
	 */
	public function custom_types_delete_options() {
		SUMOSubs_Admin_Options::delete_option( 'order_subs_predefined_subscription_period_interval' );
		SUMOSubs_Admin_Options::delete_option( 'order_subs_predefined_subscription_period' );
		SUMOSubs_Admin_Options::delete_option( 'order_subs_userdefined_min_subscription_period_intervals' );
		SUMOSubs_Admin_Options::delete_option( 'order_subs_userdefined_max_subscription_period_intervals' );
		SUMOSubs_Admin_Options::delete_option( 'order_subs_userdefined_min_subscription_length' );
		SUMOSubs_Admin_Options::delete_option( 'order_subs_userdefined_max_subscription_length' );
		SUMOSubs_Admin_Options::delete_option( 'order_subs_allowed_product_ids' );
		SUMOSubs_Admin_Options::delete_option( 'order_subs_restricted_product_ids' );
		SUMOSubs_Admin_Options::delete_option( 'order_subs_allowed_product_cat_ids' );
		SUMOSubs_Admin_Options::delete_option( 'order_subs_restricted_product_cat_ids' );
	}

	/**
	 * Save custom settings.
	 */
	public function custom_types_save( $posted ) {
		if ( isset( $posted[ 'sumosubs_order_subs_predefined_subscription_period_interval' ] ) ) {
			update_option( 'sumosubs_order_subs_predefined_subscription_period_interval', wc_clean( $posted[ 'sumosubs_order_subs_predefined_subscription_period_interval' ] ) );
		}
		if ( isset( $posted[ 'sumosubs_order_subs_predefined_subscription_period' ] ) ) {
			update_option( 'sumosubs_order_subs_predefined_subscription_period', wc_clean( $posted[ 'sumosubs_order_subs_predefined_subscription_period' ] ) );
		}
		if ( isset( $posted[ 'sumosubs_order_subs_userdefined_min_subscription_period_intervals' ] ) ) {
			update_option( 'sumosubs_order_subs_userdefined_min_subscription_period_intervals', array_map( 'wc_clean', $posted[ 'sumosubs_order_subs_userdefined_min_subscription_period_intervals' ] ) );
		}
		if ( isset( $posted[ 'sumosubs_order_subs_userdefined_max_subscription_period_intervals' ] ) ) {
			update_option( 'sumosubs_order_subs_userdefined_max_subscription_period_intervals', array_map( 'wc_clean', $posted[ 'sumosubs_order_subs_userdefined_max_subscription_period_intervals' ] ) );
		}
		if ( isset( $posted[ 'sumosubs_order_subs_userdefined_min_subscription_length' ] ) ) {
			update_option( 'sumosubs_order_subs_userdefined_min_subscription_length', wc_clean( $posted[ 'sumosubs_order_subs_userdefined_min_subscription_length' ] ) );
		}
		if ( isset( $posted[ 'sumosubs_order_subs_userdefined_max_subscription_length' ] ) ) {
			update_option( 'sumosubs_order_subs_userdefined_max_subscription_length', wc_clean( $posted[ 'sumosubs_order_subs_userdefined_max_subscription_length' ] ) );
		}
		if ( isset( $posted[ 'sumosubs_order_subs_allowed_product_ids' ] ) ) {
			update_option( 'sumosubs_order_subs_allowed_product_ids', array_map( 'wc_clean', $posted[ 'sumosubs_order_subs_allowed_product_ids' ] ) );
		}
		if ( isset( $posted[ 'sumosubs_order_subs_restricted_product_ids' ] ) ) {
			update_option( 'sumosubs_order_subs_restricted_product_ids', array_map( 'wc_clean', $posted[ 'sumosubs_order_subs_restricted_product_ids' ] ) );
		}
		if ( isset( $posted[ 'sumosubs_order_subs_allowed_product_cat_ids' ] ) ) {
			update_option( 'sumosubs_order_subs_allowed_product_cat_ids', array_map( 'wc_clean', $posted[ 'sumosubs_order_subs_allowed_product_cat_ids' ] ) );
		}
		if ( isset( $posted[ 'sumosubs_order_subs_restricted_product_cat_ids' ] ) ) {
			update_option( 'sumosubs_order_subs_restricted_product_cat_ids', array_map( 'wc_clean', $posted[ 'sumosubs_order_subs_restricted_product_cat_ids' ] ) );
		}
	}
}

return new SUMOSubs_Admin_Settings_Order_Subscription();
