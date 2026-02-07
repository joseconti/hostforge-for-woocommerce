<?php

/**
 * Synchronization Settings.
 * 
 * @class SUMOSubs_Admin_Settings_Synchronization
 */
class SUMOSubs_Admin_Settings_Synchronization extends SUMOSubs_Abstract_Admin_Settings {

	/**
	 * SUMOSubs_Admin_Settings_Synchronization constructor.
	 */
	public function __construct() {
		$this->id       = 'synchronization';
		$this->label    = __( 'Synchronization', 'sumosubscriptions' );
		$this->settings = $this->get_settings();
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
				'name' => __( 'Synchronization Settings', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'synchronization_section',
			),
			array(
				'name'    => __( 'Enable Synchronization', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'allow_synchronization' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'allow_synchronization' ),
				'type'    => 'checkbox',
				'desc'    => __( 'If enabled, you can get subscription renewal payments of a specific subscription on a specific date/day. You can configure synchronized date/day for each subscription product in the edit product page.', 'sumosubscriptions' ),
			),
			array(
				'name'     => __( 'Synchronization Behaviour', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'sync_based_on' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'sync_based_on' ),
				'type'     => 'select',
				'options'  => array(
					'exact-date-r-day' => __( 'Exact date/day', 'sumosubscriptions' ),
					'first-occurrence' => __( 'First occurrence', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'If you need to charge the renewal on a specific date of the month then, select "Exact Date/Day" option. If you need to charge the renewal on a specific date without considering the month then, select “First Occurrence” option.', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Show Next Payment Date in Single Product Page', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'when_sync_show_next_due_date_in_product_page' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'when_sync_show_next_due_date_in_product_page' ),
				'type'    => 'checkbox',
			),
			array(
				'name'     => __( 'Payment for Synchronized Period', 'sumosubscriptions' ),
				'id'       => SUMOSubs_Admin_Options::prepare_option_name( 'payment_mode_when_sync' ),
				'default'  => SUMOSubs_Admin_Options::get_option_default( 'payment_mode_when_sync' ),
				'type'     => 'select',
				'options'  => array(
					'free'         => __( 'Free', 'sumosubscriptions' ),
					'prorate'      => __( 'Prorate payment', 'sumosubscriptions' ),
					'full-payment' => __( 'Full subscription price', 'sumosubscriptions' ),
				),
				'desc_tip' => __( 'Between the purchase date and next renewal date, subscription price to be charged can be controlled using this option', 'sumosubscriptions' ),
			),
			array(
				'name'    => __( 'Prorate Payment for', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'when_sync_prorate_payment_for' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'when_sync_prorate_payment_for' ),
				'type'    => 'select',
				'options' => array(
					'all-subscriptions' => __( 'All subscription products', 'sumosubscriptions' ),
					'all-virtual'       => __( 'Only for virtual subscription products', 'sumosubscriptions' ),
				),
			),
			array(
				'name'    => __( 'Prorate payment during', 'sumosubscriptions' ),
				'id'      => SUMOSubs_Admin_Options::prepare_option_name( 'when_sync_prorate_payment_during' ),
				'default' => SUMOSubs_Admin_Options::get_option_default( 'when_sync_prorate_payment_during' ),
				'type'    => 'radio',
				'options' => array(
					'first-payment' => __( 'First payment', 'sumosubscriptions' ),
					'first-renewal' => __( 'First renewal', 'sumosubscriptions' ),
				),
			),
			array( 'type' => 'sectionend', 'id' => 'synchronization_section' ),
				) );
	}
}

return new SUMOSubs_Admin_Settings_Synchronization();
