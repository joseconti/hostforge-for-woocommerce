<?php

/**
 * Subscription Switcher Tab.
 * 
 * @class SUMOSubs_Admin_Settings_Switcher
 */
class SUMOSubs_Admin_Settings_Switcher extends SUMOSubs_Abstract_Admin_Settings {

	/**
	 * SUMOSubs_Admin_Settings_Switcher constructor.
	 */
	public function __construct() {
		$this->id            = 'upgrade_r_downgrade';
		$this->label         = __( 'Upgrade/Downgrade', 'sumosubscriptions' );
		$this->custom_fields = array(
			'get_tab_note',
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
				'type' => $this->get_custom_field_type( 'get_tab_note' ),
			),
			array(
				'name' => __( 'Upgrade/Downgrade Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'switcher_section',
			),
			array(
				'name'    => __( 'Enable Upgrade/Downgrade', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_switcher' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_switcher' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, subscribers can switch between other subscription variations of a variable subscription product and between other simple subscriptions of a grouped subscription product.', 'sumosubscriptions' ),
			),
			array(
				'name'     => __( 'Deciding Factor for Upgrade/Downgrade', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'switch_based_on' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'switch_based_on' ),
				'type'     => 'select',
				'options'  => array(
					'price'    => __( 'Subscription price', 'sumosubscriptions' ),
					'duration' => __( 'Subscription renewal frequency', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'In what way the switching is an upgrade or else it is a downgrade con be controlled here', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Allow Users to', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_user_to_switch' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_user_to_switch' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => array(
					'up-grade'    => __( 'Upgrade', 'sumosubscriptions' ),
					'down-grade'  => __( 'Downgrade', 'sumosubscriptions' ),
					'cross-grade' => __( 'Crossgrade', 'sumosubscriptions' ),
				),
			),
			array(
				'name'    => __( 'Allow Upgrade/Downgrade', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_switch_between' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_switch_between' ),
				'class'   => 'wc-enhanced-select',
				'type'    => 'multiselect',
				'options' => array(
					'variations' => __( 'Between variations of a variable subscription product', 'sumosubscriptions' ),
					'grouped'    => __( 'Between simple subscriptions of a grouped subscription product', 'sumosubscriptions' ),
				),
			),
			array(
				'name'     => __( 'Payment During Upgrade/Downgrade', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'payment_mode_when_switch' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'payment_mode_when_switch' ),
				'type'     => 'select',
				'options'  => array(
					'prorate'      => __( 'Prorate payment', 'sumosubscriptions' ),
					'full-payment' => __( 'Full subscription price', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'While switching the subscription, payment to be charged from the user can be controlled here', 'sumosubscriptions' ),
			),
			array(
				'name'     => __( 'Prorate payment', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'when_switch_prorate_recurring_payment_for' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'when_switch_prorate_recurring_payment_for' ),
				'class'    => 'wc-enhanced-select',
				'type'     => 'multiselect',
				'options'  => array(
					'virtual-upgrades'   => __( 'For upgrades of virtual subscriptions', 'sumosubscriptions' ),
					'all-upgrades'       => __( 'For upgrades of all subscriptions', 'sumosubscriptions' ),
					'virtual-downgrades' => __( 'For downgrades of virtual subscriptions', 'sumosubscriptions' ),
					'all-downgrades'     => __( 'For downgrades of all subscriptions', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'While switching, which type of subscriptions has to be prorated and charged can be controlled here', 'sumosubscriptions' ),
			),
			array(
				'name'     => __( 'Charge Sign Up Fee', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'when_switch_charge_signup_fee' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'when_switch_charge_signup_fee' ),
				'type'     => 'select',
				'options'  => array(
					'no'       => __( 'Do not charge', 'sumosubscriptions' ),
					'gap-fee'  => __( 'Charge gap sign up fee', 'sumosubscriptions' ),
					'full-fee' => __( 'Charge full sign up fee', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'If the subscription user going to be switched has sign up fee, then how much fee you need to charge from the user while switching can be controlled here', 'sumosubscriptions' ),
			),
			array(
				'name'     => __( 'Prorate Remaining Number of Installments', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'when_switch_prorate_subscription_length' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'when_switch_prorate_subscription_length' ),
				'type'     => 'select',
				'options'  => array(
					'no'                    => __( 'Do not prorate', 'sumosubscriptions' ),
					'virtual-subscriptions' => __( 'For virtual subscriptions', 'sumosubscriptions' ),
					'all-subscriptions'     => __( 'For all subscriptions', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'Remaining number of installments of the original subscription can be compared with the switched subscription and can be prorated using this option.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Upgrade/Downgrade Button Label', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'switch_button_text' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'switch_button_text' ),
				'type'    => 'text',
			),
			array( 'type' => 'sectionend', 'id' => 'switcher_section' ),
				) );
	}

	/**
	 * Custom type field.
	 */
	public function get_tab_note() {
		?>
		<tr>           
			<?php
			echo wp_kses_post( __( '<b>Note:</b><br>'
							. '1. Upgrade/Downgrade feature will not work for synchronization enabled subscription products.<br>'
							. '2. If the subscription order is placed using PayPal Subscription API payment gateway, then upgrade/downgrade option will not be displayed for the user.<br>'
							. '3. Subscribers can switch only when Subscription status is Active.', 'sumosubscriptions' ) );
			?>
		</tr>
		<?php
	}
}

return new SUMOSubs_Admin_Settings_Switcher();
