<?php

/**
 * Advanced Settings.
 * 
 * @class SUMOSubs_Admin_Settings_Advanced
 */
class SUMOSubs_Admin_Settings_Advanced extends SUMOSubs_Abstract_Admin_Settings {

	/**
	 * SUMOSubs_Admin_Settings_Advanced constructor.
	 */
	public function __construct() {
		$this->id            = 'advanced';
		$this->label         = __( 'Advanced', 'sumosubscriptions' );
		$this->custom_fields = array(
			'set_subscription_as_regular_product',
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
				'name' => __( 'Advanced Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'advanced_section',
			),
			array(
				'name'     => __( 'Subscription Price for Old Subscribers', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'update_old_subscription_price_to' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'update_old_subscription_price_to' ),
				'type'     => 'select',
				'options'  => array(
					'old-price' => __( 'Old price', 'sumosubscriptions' ),
					'new-price' => __( 'New price', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'If "New price" is selected, then for the old subscribers of the product, updated price will be set as subscription price if the subscription price for the product has been updated on the site.', 'sumosubscriptions' ),
			),
			array(
				'name'     => __( 'Activate Subscription', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'activate_subscription' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'activate_subscription' ),
				'type'     => 'select',
				'options'  => array(
					'auto'                 => __( 'Automatically', 'sumosubscriptions' ),
					'after-admin-approval' => __( 'After admin approval', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'If "After Admin Approval" is selected, admin needs to manually activate subscription in edit subscription page of the respective subscription. Note: This option is not applicable for trial/synchronization enabled subscriptions.', 'sumosubscriptions' ),
			),
			array(
				'name'     => __( 'Activate Free Trial', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'activate_free_trial' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'activate_free_trial' ),
				'type'     => 'select',
				'options'  => array(
					'auto'                 => __( 'Automatically', 'sumosubscriptions' ),
					'after-admin-approval' => __( 'After admin approval', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'If "After Admin Approval" is selected, admin needs to manually activate free trial in edit subscription page of the respective subscription.', 'sumosubscriptions' ),
			),
			array(
				'name'              => __( 'Subscription Number Prefix', 'sumosubscriptions' ),
				'id'                => SUMOSubs_Admin_Options::prepare_option_name( 'subscription_number_prefix' ),
				'default'           => SUMOSubs_Admin_Options::get_option_default( 'subscription_number_prefix' ),
				'type'              => 'text',
				'desc'              => __( 'Prefix can be alpha-numeric', 'sumosubscriptions' ),
				'custom_attributes' => array(
					'maxlength' => 30,
				),
			),
			array(
				'type' => $this->get_custom_field_type( 'set_subscription_as_regular_product' ),
			),
			array( 'type' => 'sectionend', 'id' => 'advanced_section' ),
			array(
				'name' => __( 'Display Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'display_section',
			),
			array(
				'name'     => __( 'Display Price Message for Variable Products', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'variable_product_price_display_as' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'variable_product_price_display_as' ),
				'type'     => 'select',
				'options'  => array(
					'subscription-message'     => __( 'Subscription message', 'sumosubscriptions' ),
					'woocommerce-message'      => __( 'WooCommerce message', 'sumosubscriptions' ),
					'non-subscription-message' => __( 'Price range for non subscription products', 'sumosubscriptions' ),
				),
				'desc'     => __( 'Use this option to display the price message for variable products when the variable product has both subscription and non-subscription variations', 'sumosubscriptions' ),
				'desc_tip' => true,
			),
			array(
				'name'    => __( 'Show Timezone', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'show_timezone_in_frontend' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'show_timezone_in_frontend' ),
				'type'    => 'checkbox',
			),
			array(
				'name'    => __( 'Timezone', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'show_timezone_in_frontend_as' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'show_timezone_in_frontend_as' ),
				'type'    => 'select',
				'options' => array(
					'default'   => __( 'UTC+0', 'sumosubscriptions' ),
					'wordpress' => __( 'WordPress timezone', 'sumosubscriptions' ),
				),
				'desc'    => __( 'Note: Only for display purpose in frontend.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Show Time', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'show_time_in_frontend' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'show_time_in_frontend' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, time will be displayed in frontend.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Custom CSS', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'inline_style' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'inline_style' ),
				'type'    => 'textarea',
			),
			array( 'type' => 'sectionend', 'id' => 'display_section' ),
			array(
				'name' => __( 'Email Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'emails_section',
			),
			array(
				'name'    => __( 'Disable WooCommerce Emails for Subscription Orders', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'disabled_wc_order_emails' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'disabled_wc_order_emails' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => array(
					'new'        => __( 'New order', 'sumosubscriptions' ),
					'processing' => __( 'Processing order', 'sumosubscriptions' ),
					'completed'  => __( 'Completed order', 'sumosubscriptions' ),
					'cancelled'  => __( 'Cancelled order', 'sumosubscriptions' ),
				),
			),
			array(
				'name'     => __( 'New Order Email Template to be Used for Old Subscribers', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'new_subscription_order_email_for_old_subscribers' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'new_subscription_order_email_for_old_subscribers' ),
				'type'     => 'select',
				'options'  => array(
					'default'         => __( 'Subscription New Order', 'sumosubscriptions' ),
					'old-subscribers' => __( 'Subscription New Order - Old Subscribers', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'This option controls, which email template to be sent for the old subscribers if they purchase a new subscription.', 'sumosubscriptions' ),
			),
			array( 'type' => 'sectionend', 'id' => 'emails_section' ),
			array(
				'name' => __( 'Troubleshoot Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'troubleshoot_section',
			),
			array(
				'name'     => __( 'Use Subscription Variation Form Template', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'variation_data_template' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'variation_data_template' ),
				'type'     => 'select',
				'options'  => array(
					'from-woocommerce' => __( 'From WooCommerce', 'sumosubscriptions' ),
					'from-plugin'      => __( 'From plugin', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'If the subscription variations data not displaying in single product page, then try using "From plugin" option.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Load Ajax Synchronously for Order Subscription', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'order_subscription_load_ajax_synchronously' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'order_subscription_load_ajax_synchronously' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If you are getting any errors while using order subscription, try enabling this option.', 'sumosubscriptions' ),
			),
			array( 'type' => 'sectionend', 'id' => 'troubleshoot_section' ),
				) );
	}

	/**
	 * Custom type field.
	 */
	public function set_subscription_as_regular_product() {
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php esc_html_e( 'Set Subscription Product as Regular Product for Specific User Roles', 'sumosubscriptions' ); ?></th>
			<td class="forminp">
				<span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Using this option, you can set specific subscription products as regular products for specific user roles.', 'sumosubscriptions' ); ?>"></span>
				<table class="widefat sortable striped sumosubs_set_subscription_as_regular_product">
					<thead>
						<tr>
							<th class="sort" style="width: 0.1%">&nbsp;</th>
							<th><?php esc_html_e( 'Select Subscription Product(s)', 'sumosubscriptions' ); ?></th>
							<th><?php esc_html_e( 'Select User Role(s)', 'sumosubscriptions' ); ?></th>
							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody class="defined_rules">
						<?php
						$i             = 0;
						$defined_rules = SUMOSubs_Admin_Options::get_option( 'subscription_as_regular_product_defined_rules' );

						if ( $defined_rules ) {
							foreach ( $defined_rules as $rule ) {
								if ( ! isset( $rule[ 'selected_subscription' ] ) ) {
									continue;
								}

								$i++;
								?>
								<tr class="defined_rule">
									<td class="sort"></td>
									<td>
										<?php
										sumosubs_wc_search_field( array(
											'class'       => 'wc-product-search',
											'action'      => 'sumosubscription_json_search_subscription_products_and_variations',
											'id'          => 'selected_subscription_' . $i,
											'name'        => 'selected_subscription[' . $i . ']',
											'type'        => 'product',
											'selected'    => empty( $rule[ 'selected_subscription' ] ) ? false : true,
											'options'     => $rule[ 'selected_subscription' ],
											'placeholder' => __( 'Search for a subscription product&hellip;', 'sumosubscriptions' ),
												), true );
										?>
									</td>
									<td>
										<?php
										sumosubs_wc_enhanced_select_field( array(
											'id'       => 'selected_userrole_' . $i,
											'name'     => 'selected_userrole[' . $i . ']',
											'selected' => $rule[ 'selected_userrole' ],
											'options'  => sumosubs_get_user_roles( true ),
												), true );
										?>
									</td>
									<td><a href="#" class="remove_row button">X</a></td>    
								</tr>
								<?php
							}
						}
						?>
					</tbody>
					<tfoot>
						<tr>
							<th><span class="spinner"></span></th>
							<th colspan="3"><a href="#" class="add button"><?php esc_html_e( 'Add Rule', 'sumosubscriptions' ); ?></a></th>
						</tr>
					</tfoot>
				</table>                
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the custom options once.
	 */
	public function custom_types_add_options() {
		SUMOSubs_Admin_Options::add_option( 'subscription_as_regular_product_defined_rules' );
	}

	/**
	 * Delete the custom options.
	 */
	public function custom_types_delete_options() {
		SUMOSubs_Admin_Options::delete_option( 'subscription_as_regular_product_defined_rules' );
	}

	/**
	 * Save custom settings.
	 */
	public function custom_types_save( $posted ) {
		$data = array();

		if ( isset( $posted[ 'selected_subscription' ] ) ) {
			$selected_subscription = array_map( 'wc_clean', $posted[ 'selected_subscription' ] );
			$selected_userrole     = array_map( 'wc_clean', $posted[ 'selected_userrole' ] );

			foreach ( $selected_subscription as $i => $name ) {
				if ( ! isset( $selected_subscription[ $i ] ) || ! isset( $selected_userrole[ $i ] ) ) {
					continue;
				}

				$data[] = array(
					'selected_subscription' => $selected_subscription[ $i ],
					'selected_userrole'     => $selected_userrole[ $i ],
				);
			}
		}

		update_option( SUMOSubs_Admin_Options::prepare_option_name( 'subscription_as_regular_product_defined_rules' ), $data );
	}
}

return new SUMOSubs_Admin_Settings_Advanced();
