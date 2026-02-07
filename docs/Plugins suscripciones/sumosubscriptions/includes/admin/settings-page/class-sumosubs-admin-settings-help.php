<?php

/**
 * Help.
 * 
 * @class SUMOSubs_Admin_Settings_Help
 */
class SUMOSubs_Admin_Settings_Help extends SUMOSubs_Abstract_Admin_Settings {

	/**
	 * SUMOSubs_Admin_Settings_Help constructor.
	 */
	public function __construct() {
		$this->id            = 'help';
		$this->label         = __( 'Help', 'sumosubscriptions' );
		$this->custom_fields = array(
			'get_compatible_plugins',
				);
		$this->settings      = $this->get_settings();
		$this->init();

		add_action( 'sumosubscriptions_submit_' . $this->id, '__return_false' );
		add_action( 'sumosubscriptions_reset_' . $this->id, '__return_false' );
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
				'name' => __( 'Documentation', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'documentation_section',
				'desc' => __( 'The documentation file can be found inside the documentation folder  which you will find when you unzip the downloaded zip file.', 'sumosubscriptions' ),
			),
			array( 'type' => 'sectionend', 'id' => 'documentation_section' ),
			array(
				'name' => __( 'Compatibility', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'compatibility_section',
			),
			array(
				'type' => $this->get_custom_field_type( 'get_compatible_plugins' ),
			),
			array( 'type' => 'sectionend', 'id' => 'compatibility_section' ),
			array(
				'name' => __( 'Help', 'sumosubscriptions' ),
				'type' => 'title',
				'id'   => 'help_section',
				'desc' => __( 'If you need Help, please <a href="http://support.fantasticplugins.com" target="_blank" > register and open a support ticket</a>', 'sumosubscriptions' ),
			),
			array( 'type' => 'sectionend', 'id' => 'help_section' ),
				) );
	}

	/**
	 * Custom type field.
	 */
	public function get_compatible_plugins() {
		$compatible_plugins = array(
			"<a href='http://fantasticplugins.com/sumo-memberships/'>SUMO Memberships</a>",
			"<a href='http://fantasticplugins.com/sumo-donations/'>SUMO Donations</a>",
			"<a href='http://fantasticplugins.com/sumo-reward-points/'>SUMO Reward Points</a>",
			"<a href='http://fantasticplugins.com/sumo-affiliates/'>SUMO Affiliates</a>",
				);
		esc_html_e( 'The following Plugins are compatible with SUMO Subscriptions Plugin.', 'sumosubscriptions' );
		?>

		<?php foreach ( $compatible_plugins as $index => $plugin ) { ?>
			<ol>
				<?php
				$i = ++$index;
				echo wp_kses_post( "$i. $plugin" ) . '<br>';
				?>
			</ol>
			<?php
		}
	}
}

return new SUMOSubs_Admin_Settings_Help();
