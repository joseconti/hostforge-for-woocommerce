<?php

/**
 * My Account Settings.
 * 
 * @class SUMOSubs_Admin_Settings_My_Account
 */
class SUMOSubs_Admin_Settings_My_Account extends SUMOSubs_Abstract_Admin_Settings {

	/**
	 * SUMOSubs_Admin_Settings_My_Account constructor.
	 */
	public function __construct() {
		$this->id            = 'my_account';
		$this->label         = __( 'My Account', 'sumosubscriptions' );
		$this->custom_fields = array(
			'get_user_ids_for_pause',
			'get_user_ids_for_cancel',
			'get_product_ids_for_cancel',
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
				'name' => __( 'Pause Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'pause_section',
			),
			array(
				'name'    => __( 'Allow Subscribers to Pause Subscriptions', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_pause' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_pause' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, subscribers can pause their subscriptions.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Allow Subscribers to Pause Synchronization Enabled Subscriptions', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_pause_synced' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_pause_synced' ),
				'type'    => 'checkbox',
				'desc'    => __( 'Note: If you have allowed the subscribers to pause synchronization enabled subscriptions, then they might not get the extended number of days based on the pause duration once the subscription gets resumed.', 'sumosubscriptions' ),
			),
			array(
				'name'              => __( 'Maximum Pause Count', 'sumosubscriptions' ),
				'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'max_pause_times_for_subscribers' ),
				'default'           => SUMOSubs_Admin_Options::get_option_default( 'max_pause_times_for_subscribers' ),
				'type'              => 'number',
				'desc'              => __( 'times', 'sumosubscriptions' ),
				'desc_tip'          => __( 'This option controls the number of times a subscriber can pause a subscription. If left empty or set 0 then, they can pause their subscription infinite times.', 'sumosubscriptions' ),
				'custom_attributes' => array(
					'min'      => 0,
					'required' => 'required',
				),
			),
			array(
				'name'              => __( 'Maximum Pause Duration', 'sumosubscriptions' ),
				'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'max_pause_duration_for_subscribers' ),
				'default'           => SUMOSubs_Admin_Options::get_option_default( 'max_pause_duration_for_subscribers' ),
				'type'              => 'number',
				'desc'              => __( 'day(s)', 'sumosubscriptions' ),
				'desc_tip'          => __( 'This option controls how long the subscription can be in pause status. After the set duration, the subscription will be resumed automatically.', 'sumosubscriptions' ),
				'custom_attributes' => array(
					'min'      => 1,
					'required' => 'required',
				),
			),
			array(
				'name'    => __( 'Allow Subscribers to Select the Resume Date', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_select_resume_date' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_select_resume_date' ),
				'type'    => 'checkbox',
			),
			array(
				'name'    => __( 'User/User Role Filter', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'user_wide_pause_for' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'user_wide_pause_for' ),
				'type'    => 'select',
				'options' => array(
					'all-users'             => __( 'All users', 'sumosubscriptions' ),
					'allowed-user-ids'      => __( 'Include user(s)', 'sumosubscriptions' ),
					'restricted-user-ids'   => __( 'Exclude user(s)', 'sumosubscriptions' ),
					'allowed-user-roles'    => __( 'Include user role(s)', 'sumosubscriptions' ),
					'restricted-user-roles' => __( 'Exclude user role(s)', 'sumosubscriptions' ),
				),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_user_ids_for_pause' ),
			),
			array(
				'name'    => __( 'Select User Role(s)', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'user_roles_for_pause' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'user_roles_for_pause' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => sumosubs_get_user_roles(),
			),
			array( 'type' => 'sectionend', 'id' => 'pause_section' ),
			array(
				'name' => __( 'Cancel Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'cancel_section',
			),
			array(
				'name'    => __( 'Allow Subscribers to Cancel Subscriptions', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_cancel' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_cancel' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, subscribers can cancel their subscriptions.', 'sumosubscriptions' ),
			),
			array(
				'name'              => __( 'Allow Subscribers to Cancel Subscriptions After', 'sumosubscriptions' ),
				'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_cancel_after_schedule' ),
				'default'           => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_cancel_after_schedule' ),
				'type'              => 'number',
				'desc'              => __( 'day(s) of subscription start date', 'sumosubscriptions' ),
				'desc_tip'          => __( 'This option controls after how many day(s) of subscription purchase, subscriber can cancel their subscriptions.', 'sumosubscriptions' ),
				'custom_attributes' => array(
					'min' => 0,
				),
			),
			array(
				'name'    => __( 'Cancel Methods to be Shown for Subscribers', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'cancel_options_for_subscriber' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'cancel_options_for_subscriber' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => array(
					'immediate'            => __( 'Cancel immediately', 'sumosubscriptions' ),
					'end_of_billing_cycle' => __( 'Cancel at the end of billing cycle', 'sumosubscriptions' ),
					'scheduled_date'       => __( 'Cancel on a scheduled date', 'sumosubscriptions' ),
				),
			),
			array(
				'name'    => __( 'Product/Category Filter', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'product_wide_cancellation_for' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'product_wide_cancellation_for' ),
				'type'    => 'select',
				'options' => array(
					'all-products'               => __( 'All subscription product(s)', 'sumosubscriptions' ),
					'allowed-product-ids'        => __( 'Include subscription product(s)', 'sumosubscriptions' ),
					'restricted-product-ids'     => __( 'Exclude subscription product(s)', 'sumosubscriptions' ),
					'allowed-product-cat-ids'    => __( 'Include subscription categories', 'sumosubscriptions' ),
					'restricted-product-cat-ids' => __( 'Exclude subscription categories', 'sumosubscriptions' ),
				),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_product_ids_for_cancel' ),
			),
			array(
				'name'    => __( 'Select Categories', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'product_cat_ids_for_cancel' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'product_cat_ids_for_cancel' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => sumosubs_get_product_categories(),
			),
			array(
				'name'    => __( 'User/User Role Filter', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'user_wide_cancellation_for' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'user_wide_cancellation_for' ),
				'type'    => 'select',
				'options' => array(
					'all-users'             => __( 'All users', 'sumosubscriptions' ),
					'allowed-user-ids'      => __( 'Include user(s)', 'sumosubscriptions' ),
					'restricted-user-ids'   => __( 'Exclude user(s)', 'sumosubscriptions' ),
					'allowed-user-roles'    => __( 'Include user role(s)', 'sumosubscriptions' ),
					'restricted-user-roles' => __( 'Exclude user role(s)', 'sumosubscriptions' ),
				),
			),
			array(
				'type' => $this->get_custom_field_type( 'get_user_ids_for_cancel' ),
			),
			array(
				'name'    => __( 'Select User Role(s)', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'user_roles_for_cancel' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'user_roles_for_cancel' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => sumosubs_get_user_roles(),
			),
			array( 'type' => 'sectionend', 'id' => 'cancel_section' ),
			array(
				'name' => __( 'Miscellaneous Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'miscellaneous_section',
			),
			array(
				'name'    => __( 'Allow Subscribers to Switch Between Identical Variations', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_switch_bw_identical_variations' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_switch_bw_identical_variations' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, subscriber can switch between one variation to another variation of a variable subscription product if the price, subscription values are similar between both the variations.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Allow Subscribers to Update Subscription Quantity', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_update_subscription_qty' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_update_subscription_qty' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, subscriber can update the purchased subscription product quantity. From the next renewal onward, recurring will happen with the updated quantity(and updated price based on the quantity selected).', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Allow Subscribers to Resubscribe', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_resubscribe' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_resubscribe' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, subscriber can resubscribe to the expired/cancelled subscription with the same price even if the subscription price has been updated(new price will be charged if you have selected “New price” in “SUMO Subscriptions > Settings > Advanced > Subscription Price for Old Subscribers”). Note: Resubscribe option won’t be displayed if the subscriber already have any active subscription with the same product.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Hide Resubscribe Button when', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'hide_resubscribe_to_subscribers_when' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'hide_resubscribe_to_subscribers_when' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => array(
					'admin_cancel'  => __( 'Admin cancels the subscription', 'sumosubscriptions' ),
					'user_cancel'   => __( 'User cancels the subscription', 'sumosubscriptions' ),
					'auto_cancel'   => __( 'Automatic subscription gets cancelled', 'sumosubscriptions' ),
					'manual_cancel' => __( 'Manual subscription gets cancelled', 'sumosubscriptions' ),
					'auto_expire'   => __( 'Automatic subscription gets expired', 'sumosubscriptions' ),
					'manual_expire' => __( 'Manual subscription gets expired', 'sumosubscriptions' ),
				),
			),
			array(
				'name'    => __( 'Allow Subscribers to Turn Off Automatic Payments', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_turnoff_auto_renewals' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_turnoff_auto_renewals' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, subscriber can turn off their automatic payment for the specific subscription which means they have to pay the upcoming renewal in manual mode.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Allow Subscribers to Update Shipping Address', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_subscribers_to_change_shipping_address' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_subscribers_to_change_shipping_address' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, subscriber can update their shipping address so that the upcoming renewals will be shipped to the updated address.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Drip Downloadable Content', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'drip_downloadable_content' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'drip_downloadable_content' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, an old subscriber can access the newly attached file only after successful renewal of the subscription.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Enable Additional Digital Downloads', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'enable_additional_digital_downloads' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'enable_additional_digital_downloads' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, you can attach a downloadable product to a subscription product so that when a user purchase the subscription product, they will also get access to the attachments in the downloadable product.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Show Activitiy Logs', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'show_subscription_activities' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'show_subscription_activities' ),
				'type'    => 'checkbox',
			),
			array( 'type' => 'sectionend', 'id' => 'miscellaneous_section' ),
			array(
				'name' => __( 'Endpoints Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'endpoints_section',
			),
			array(
				'name'              => __( 'My Subscriptions', 'sumosubscriptions' ),
				'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'my_account_subscriptions_endpoint' ),
				'default'           => SUMOSubs_Admin_Options::get_option_default( 'my_account_subscriptions_endpoint' ),
				'type'              => 'text',
				'custom_attributes' => array(
					'required' => 'required',
				),
			),
			array(
				'name'              => __( 'View Subscription', 'sumosubscriptions' ),
				'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'my_account_view_subscription_endpoint' ),
				'default'           => SUMOSubs_Admin_Options::get_option_default( 'my_account_view_subscription_endpoint' ),
				'type'              => 'text',
				'custom_attributes' => array(
					'required' => 'required',
				),
			),
			array( 'type' => 'sectionend', 'id' => 'endpoints_section' ),
				) );
	}

	/**
	 * Custom type field.
	 */
	public function get_user_ids_for_pause() {
		sumosubs_wc_search_field( array(
			'class'       => 'wc-customer-search',
			'id'          => SUMOSubs_Admin_Options::prepare_option_name( 'user_ids_for_pause' ),
			'type'        => 'customer',
			'title'       => __( 'Select User(s)', 'sumosubscriptions' ),
			'placeholder' => __( 'Search for a user&hellip;', 'sumosubscriptions' ),
			'options'     => ( array ) SUMOSubs_Admin_Options::get_option( 'user_ids_for_pause' ),
		) );
	}

	/**
	 * Custom type field.
	 */
	public function get_user_ids_for_cancel() {
		sumosubs_wc_search_field( array(
			'class'       => 'wc-customer-search',
			'id'          => SUMOSubs_Admin_Options::prepare_option_name( 'user_ids_for_cancel' ),
			'type'        => 'customer',
			'title'       => __( 'Select User(s)', 'sumosubscriptions' ),
			'placeholder' => __( 'Search for a user&hellip;', 'sumosubscriptions' ),
			'options'     => ( array ) SUMOSubs_Admin_Options::get_option( 'user_ids_for_cancel' ),
		) );
	}

	/**
	 * Custom type field.
	 */
	public function get_product_ids_for_cancel() {
		sumosubs_wc_search_field( array(
			'class'       => 'wc-product-search',
			'id'          => SUMOSubs_Admin_Options::prepare_option_name( 'product_ids_for_cancel' ),
			'type'        => 'product',
			'action'      => 'sumosubscription_json_search_subscription_products_and_variations',
			'title'       => __( 'Select Product(s)', 'sumosubscriptions' ),
			'placeholder' => __( 'Search for a product&hellip;', 'sumosubscriptions' ),
			'options'     => ( array ) SUMOSubs_Admin_Options::get_option( 'product_ids_for_cancel' ),
		) );
	}

	/**
	 * Save the custom options once.
	 */
	public function custom_types_add_options() {
		SUMOSubs_Admin_Options::add_option( 'user_ids_for_pause' );
		SUMOSubs_Admin_Options::add_option( 'user_ids_for_cancel' );
		SUMOSubs_Admin_Options::add_option( 'product_ids_for_cancel' );
	}

	/**
	 * Delete the custom options.
	 */
	public function custom_types_delete_options() {
		SUMOSubs_Admin_Options::delete_option( 'user_ids_for_pause' );
		SUMOSubs_Admin_Options::delete_option( 'user_ids_for_cancel' );
		SUMOSubs_Admin_Options::delete_option( 'product_ids_for_cancel' );
	}

	/**
	 * Save custom settings.
	 */
	public function custom_types_save( $posted ) {
		if ( isset( $posted[ 'sumosubs_user_ids_for_pause' ] ) ) {
			update_option( 'sumosubs_user_ids_for_pause', ! is_array( $posted[ 'sumosubs_user_ids_for_pause' ] ) ? array_filter( array_map( 'absint', explode( ',', $posted[ 'sumosubs_user_ids_for_pause' ] ) ) ) : $posted[ 'sumosubs_user_ids_for_pause' ]  );
		}
		if ( isset( $posted[ 'sumosubs_user_ids_for_cancel' ] ) ) {
			update_option( 'sumosubs_user_ids_for_cancel', ! is_array( $posted[ 'sumosubs_user_ids_for_cancel' ] ) ? array_filter( array_map( 'absint', explode( ',', $posted[ 'sumosubs_user_ids_for_cancel' ] ) ) ) : $posted[ 'sumosubs_user_ids_for_cancel' ]  );
		}
		if ( isset( $posted[ 'sumosubs_product_ids_for_cancel' ] ) ) {
			update_option( 'sumosubs_product_ids_for_cancel', ! is_array( $posted[ 'sumosubs_product_ids_for_cancel' ] ) ? array_filter( array_map( 'absint', explode( ',', $posted[ 'sumosubs_product_ids_for_cancel' ] ) ) ) : $posted[ 'sumosubs_product_ids_for_cancel' ]  );
		}
	}
}

return new SUMOSubs_Admin_Settings_My_Account();
