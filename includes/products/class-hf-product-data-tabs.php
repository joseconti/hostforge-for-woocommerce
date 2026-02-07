<?php
/**
 * Product Data Tabs for WooCommerce Admin.
 *
 * Adds HostForge-specific data panels to the WooCommerce product editor.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Product_Data_Tabs
 */
class HF_Product_Data_Tabs {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_product_tabs' ) );
		add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'render_panels' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Add HostForge tab to product data tabs.
	 *
	 * @param array<string, array> $tabs Product data tabs.
	 * @return array<string, array>
	 */
	public static function add_product_tabs( array $tabs ): array {
		$tabs['hf_hosting'] = array(
			'label'    => esc_html__( 'Hosting Settings', 'hostforge' ),
			'target'   => 'hf_hosting_data',
			'class'    => array( 'show_if_hf_shared_hosting', 'show_if_hf_reseller_hosting' ),
			'priority' => 21,
		);

		$tabs['hf_server'] = array(
			'label'    => esc_html__( 'Server Settings', 'hostforge' ),
			'target'   => 'hf_server_data',
			'class'    => array( 'show_if_hf_vps_server', 'show_if_hf_dedicated_server' ),
			'priority' => 21,
		);

		$tabs['hf_domain'] = array(
			'label'    => esc_html__( 'Domain Settings', 'hostforge' ),
			'target'   => 'hf_domain_data',
			'class'    => array( 'show_if_hf_domain' ),
			'priority' => 21,
		);

		$tabs['hf_ssl'] = array(
			'label'    => esc_html__( 'SSL Settings', 'hostforge' ),
			'target'   => 'hf_ssl_data',
			'class'    => array( 'show_if_hf_ssl_certificate' ),
			'priority' => 21,
		);

		$tabs['hf_license'] = array(
			'label'    => esc_html__( 'License Settings', 'hostforge' ),
			'target'   => 'hf_license_data',
			'class'    => array( 'show_if_hf_software_license' ),
			'priority' => 21,
		);

		return $tabs;
	}

	/**
	 * Render all HostForge product data panels.
	 *
	 * @return void
	 */
	public static function render_panels(): void {
		global $post;

		$product_id = $post ? $post->ID : 0;

		self::render_hosting_panel( $product_id );
		self::render_server_panel( $product_id );
		self::render_domain_panel( $product_id );
		self::render_ssl_panel( $product_id );
		self::render_license_panel( $product_id );
	}

	/**
	 * Render Hosting Settings panel (Shared + Reseller).
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	private static function render_hosting_panel( int $product_id ): void {
		?>
		<div id="hf_hosting_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<h4 style="padding-left: 12px;"><?php esc_html_e( 'Server Assignment', 'hostforge' ); ?></h4>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_hf_server_group',
						'label'       => esc_html__( 'Server Group', 'hostforge' ),
						'description' => esc_html__( 'Server group for auto-assignment.', 'hostforge' ),
						'desc_tip'    => true,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => '_hf_plan',
						'label'       => esc_html__( 'Hosting Plan / Package', 'hostforge' ),
						'description' => esc_html__( 'Package name on the panel server.', 'hostforge' ),
						'desc_tip'    => true,
					)
				);
				?>
			</div>

			<div class="options_group">
				<h4 style="padding-left: 12px;"><?php esc_html_e( 'Resource Limits', 'hostforge' ); ?></h4>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_disk_limit',
						'label'             => esc_html__( 'Disk Space (MB)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'description'       => esc_html__( '0 = unlimited.', 'hostforge' ),
						'desc_tip'          => true,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_bandwidth_limit',
						'label'             => esc_html__( 'Bandwidth (MB)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'description'       => esc_html__( '0 = unlimited.', 'hostforge' ),
						'desc_tip'          => true,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_email_limit',
						'label'             => esc_html__( 'Email Accounts', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_db_limit',
						'label'             => esc_html__( 'Databases', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_subdomain_limit',
						'label'             => esc_html__( 'Subdomains', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_parked_domains_limit',
						'label'             => esc_html__( 'Parked Domains', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_addon_domains_limit',
						'label'             => esc_html__( 'Addon Domains', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);
				?>
			</div>

			<div class="options_group show_if_hf_reseller_hosting" style="display:none;">
				<h4 style="padding-left: 12px;"><?php esc_html_e( 'Reseller Settings', 'hostforge' ); ?></h4>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_max_accounts',
						'label'             => esc_html__( 'Max cPanel Accounts', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_aggregate_disk_limit',
						'label'             => esc_html__( 'Aggregate Disk (MB)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_aggregate_bandwidth_limit',
						'label'             => esc_html__( 'Aggregate Bandwidth (MB)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => '_hf_reseller_plan',
						'label'       => esc_html__( 'Reseller Plan Name', 'hostforge' ),
						'description' => esc_html__( 'Reseller plan on the server.', 'hostforge' ),
						'desc_tip'    => true,
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_whm_access',
						'label'       => esc_html__( 'WHM Access', 'hostforge' ),
						'description' => esc_html__( 'Enable WHM access for reseller.', 'hostforge' ),
					)
				);
				?>
			</div>

			<div class="options_group">
				<h4 style="padding-left: 12px;"><?php esc_html_e( 'Checkout Options', 'hostforge' ); ?></h4>
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_require_domain',
						'label'       => esc_html__( 'Require Domain', 'hostforge' ),
						'description' => esc_html__( 'Customer must provide a domain at checkout.', 'hostforge' ),
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_auto_username',
						'label'       => esc_html__( 'Auto Username', 'hostforge' ),
						'description' => esc_html__( 'Generate username from domain automatically.', 'hostforge' ),
						'value'       => get_post_meta( $product_id, '_hf_auto_username', true ) ?: 'yes',
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_setup_fee',
						'label'             => esc_html__( 'Setup Fee', 'hostforge' ) . ' (' . get_woocommerce_currency_symbol() . ')',
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_trial_days',
						'label'             => esc_html__( 'Trial Days', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'description'       => esc_html__( '0 = no trial.', 'hostforge' ),
						'desc_tip'          => true,
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Server Settings panel (VPS + Dedicated).
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	private static function render_server_panel( int $product_id ): void {
		?>
		<div id="hf_server_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<h4 style="padding-left: 12px;"><?php esc_html_e( 'Hardware Specifications', 'hostforge' ); ?></h4>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_cpu_cores',
						'label'             => esc_html__( 'CPU Cores', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_ram',
						'label'             => esc_html__( 'RAM (MB)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '256',
							'step' => '256',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_disk',
						'label'             => esc_html__( 'Disk Space (GB)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_select(
					array(
						'id'      => '_hf_disk_type',
						'label'   => esc_html__( 'Disk Type', 'hostforge' ),
						'options' => array(
							'ssd'  => esc_html__( 'SSD', 'hostforge' ),
							'nvme' => esc_html__( 'NVMe', 'hostforge' ),
							'hdd'  => esc_html__( 'HDD', 'hostforge' ),
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_bandwidth',
						'label'             => esc_html__( 'Bandwidth (GB)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
						'description'       => esc_html__( '0 = unlimited.', 'hostforge' ),
						'desc_tip'          => true,
					)
				);
				?>
			</div>

			<div class="options_group">
				<h4 style="padding-left: 12px;"><?php esc_html_e( 'Network', 'hostforge' ); ?></h4>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_ipv4_count',
						'label'             => esc_html__( 'IPv4 Addresses', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_ipv6_count',
						'label'             => esc_html__( 'IPv6 Addresses', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);
				?>
			</div>

			<div class="options_group show_if_hf_dedicated_server" style="display:none;">
				<h4 style="padding-left: 12px;"><?php esc_html_e( 'Dedicated Hardware', 'hostforge' ); ?></h4>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_hf_processor',
						'label'       => esc_html__( 'Processor', 'hostforge' ),
						'description' => esc_html__( 'e.g. Intel Xeon E-2288G', 'hostforge' ),
						'desc_tip'    => true,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'    => '_hf_raid',
						'label' => esc_html__( 'RAID Configuration', 'hostforge' ),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => '_hf_uplink',
						'label'       => esc_html__( 'Uplink Speed', 'hostforge' ),
						'description' => esc_html__( 'e.g. 1Gbps, 10Gbps', 'hostforge' ),
						'desc_tip'    => true,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'    => '_hf_datacenter',
						'label' => esc_html__( 'Datacenter', 'hostforge' ),
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_ipmi_access',
						'label'       => esc_html__( 'IPMI/KVM Access', 'hostforge' ),
						'description' => esc_html__( 'Include remote management access.', 'hostforge' ),
					)
				);
				?>
			</div>

			<div class="options_group">
				<h4 style="padding-left: 12px;"><?php esc_html_e( 'OS & Provisioning', 'hostforge' ); ?></h4>
				<?php
				woocommerce_wp_textarea_input(
					array(
						'id'          => '_hf_os_choices',
						'label'       => esc_html__( 'Available OS', 'hostforge' ),
						'description' => esc_html__( 'One OS per line (e.g. Ubuntu 22.04, CentOS 9, Debian 12).', 'hostforge' ),
						'desc_tip'    => true,
						'value'       => implode( "\n", self::get_os_choices( $product_id ) ),
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_require_hostname',
						'label'       => esc_html__( 'Require Hostname', 'hostforge' ),
						'description' => esc_html__( 'Customer must provide hostname at checkout.', 'hostforge' ),
						'value'       => get_post_meta( $product_id, '_hf_require_hostname', true ) ?: 'yes',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_require_root_password',
						'label'       => esc_html__( 'Require Root Password', 'hostforge' ),
						'description' => esc_html__( 'Customer sets root password at checkout.', 'hostforge' ),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'          => '_hf_server_group',
						'label'       => esc_html__( 'Server Group', 'hostforge' ),
						'description' => esc_html__( 'For auto-assignment.', 'hostforge' ),
						'desc_tip'    => true,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_setup_fee',
						'label'             => esc_html__( 'Setup Fee', 'hostforge' ) . ' (' . get_woocommerce_currency_symbol() . ')',
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Domain Settings panel.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	private static function render_domain_panel( int $product_id ): void {
		?>
		<div id="hf_domain_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_hf_registrar',
						'label'       => esc_html__( 'Registrar', 'hostforge' ),
						'description' => esc_html__( 'Registrar module to use for this product.', 'hostforge' ),
						'desc_tip'    => true,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_registration_years',
						'label'             => esc_html__( 'Registration Period (years)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'max'  => '10',
							'step' => '1',
						),
						'value'             => get_post_meta( $product_id, '_hf_registration_years', true ) ?: '1',
					)
				);

				woocommerce_wp_textarea_input(
					array(
						'id'          => '_hf_allowed_tlds',
						'label'       => esc_html__( 'Allowed TLDs', 'hostforge' ),
						'description' => esc_html__( 'One TLD per line (e.g. .com, .net, .org). Leave empty for all.', 'hostforge' ),
						'desc_tip'    => true,
						'value'       => implode( "\n", self::get_json_array_meta( $product_id, '_hf_allowed_tlds' ) ),
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_auto_renew_default',
						'label'       => esc_html__( 'Auto-Renew Default', 'hostforge' ),
						'description' => esc_html__( 'Enable auto-renewal by default.', 'hostforge' ),
						'value'       => get_post_meta( $product_id, '_hf_auto_renew_default', true ) ?: 'yes',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_allow_transfer',
						'label'       => esc_html__( 'Allow Transfer', 'hostforge' ),
						'description' => esc_html__( 'Allow domain transfer option at checkout.', 'hostforge' ),
						'value'       => get_post_meta( $product_id, '_hf_allow_transfer', true ) ?: 'yes',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_id_protection',
						'label'       => esc_html__( 'ID Protection', 'hostforge' ),
						'description' => esc_html__( 'WHOIS privacy available.', 'hostforge' ),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render SSL Settings panel.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	private static function render_ssl_panel( int $product_id ): void {
		?>
		<div id="hf_ssl_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				woocommerce_wp_select(
					array(
						'id'      => '_hf_ssl_type',
						'label'   => esc_html__( 'SSL Type', 'hostforge' ),
						'options' => array(
							'dv'       => esc_html__( 'Domain Validated (DV)', 'hostforge' ),
							'ov'       => esc_html__( 'Organization Validated (OV)', 'hostforge' ),
							'ev'       => esc_html__( 'Extended Validation (EV)', 'hostforge' ),
							'wildcard' => esc_html__( 'Wildcard', 'hostforge' ),
						),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'    => '_hf_ssl_brand',
						'label' => esc_html__( 'SSL Brand', 'hostforge' ),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_validity_months',
						'label'             => esc_html__( 'Validity (months)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
						'value'             => get_post_meta( $product_id, '_hf_validity_months', true ) ?: '12',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_multi_domain',
						'label'       => esc_html__( 'Multi-Domain (SAN)', 'hostforge' ),
						'description' => esc_html__( 'Support multiple domains.', 'hostforge' ),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_max_san_domains',
						'label'             => esc_html__( 'Max SAN Domains', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '1',
						),
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_require_domain',
						'label'       => esc_html__( 'Require Domain', 'hostforge' ),
						'description' => esc_html__( 'Domain required at checkout.', 'hostforge' ),
						'value'       => get_post_meta( $product_id, '_hf_require_domain', true ) ?: 'yes',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_require_csr',
						'label'       => esc_html__( 'Require CSR', 'hostforge' ),
						'description' => esc_html__( 'Customer must provide CSR at checkout.', 'hostforge' ),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_warranty',
						'label'             => esc_html__( 'Warranty (USD)', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '0',
							'step' => '0.01',
						),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render License Settings panel.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	private static function render_license_panel( int $product_id ): void {
		?>
		<div id="hf_license_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<?php
				woocommerce_wp_text_input(
					array(
						'id'          => '_hf_license_type',
						'label'       => esc_html__( 'License Type', 'hostforge' ),
						'description' => esc_html__( 'e.g. cpanel, plesk, litespeed, cloudlinux', 'hostforge' ),
						'desc_tip'    => true,
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'    => '_hf_license_provider',
						'label' => esc_html__( 'License Provider', 'hostforge' ),
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_require_server_ip',
						'label'       => esc_html__( 'Require Server IP', 'hostforge' ),
						'description' => esc_html__( 'Customer must provide server IP at checkout.', 'hostforge' ),
						'value'       => get_post_meta( $product_id, '_hf_require_server_ip', true ) ?: 'yes',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_auto_generate_key',
						'label'       => esc_html__( 'Auto-Generate Key', 'hostforge' ),
						'description' => esc_html__( 'Generate license key automatically.', 'hostforge' ),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'    => '_hf_key_prefix',
						'label' => esc_html__( 'Key Prefix', 'hostforge' ),
					)
				);

				woocommerce_wp_text_input(
					array(
						'id'                => '_hf_max_activations',
						'label'             => esc_html__( 'Max Activations', 'hostforge' ),
						'type'              => 'number',
						'custom_attributes' => array(
							'min'  => '1',
							'step' => '1',
						),
						'value'             => get_post_meta( $product_id, '_hf_max_activations', true ) ?: '1',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_hf_allow_ip_change',
						'label'       => esc_html__( 'Allow IP Change', 'hostforge' ),
						'description' => esc_html__( 'Customer can change server IP.', 'hostforge' ),
						'value'       => get_post_meta( $product_id, '_hf_allow_ip_change', true ) ?: 'yes',
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save HostForge product meta fields.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public static function save_fields( int $product_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WC handles nonce.
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$type = $product->get_type();

		// Text/number fields — sanitize and save.
		$text_fields = self::get_saveable_fields( $type );

		foreach ( $text_fields as $field_id ) {
			if ( isset( $_POST[ $field_id ] ) ) {
				$product->update_meta_data( $field_id, sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) ) );
			}
		}

		// Checkbox fields.
		$checkbox_fields = self::get_checkbox_fields( $type );

		foreach ( $checkbox_fields as $field_id ) {
			$value = isset( $_POST[ $field_id ] ) ? 'yes' : 'no';
			$product->update_meta_data( $field_id, $value );
		}

		// Textarea → JSON array fields.
		$textarea_json_fields = self::get_textarea_json_fields( $type );

		foreach ( $textarea_json_fields as $field_id ) {
			if ( isset( $_POST[ $field_id ] ) ) {
				$raw   = sanitize_textarea_field( wp_unslash( $_POST[ $field_id ] ) );
				$lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
				$product->update_meta_data( $field_id, wp_json_encode( array_values( $lines ) ) );
			}
		}

		$product->save();
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Get text/number fields for a product type.
	 *
	 * @param string $type Product type.
	 * @return array<string>
	 */
	private static function get_saveable_fields( string $type ): array {
		$fields = array();

		if ( in_array( $type, array( 'hf_shared_hosting', 'hf_reseller_hosting' ), true ) ) {
			$fields = array_merge(
				$fields,
				array(
					'_hf_server_group',
					'_hf_plan',
					'_hf_disk_limit',
					'_hf_bandwidth_limit',
					'_hf_email_limit',
					'_hf_db_limit',
					'_hf_subdomain_limit',
					'_hf_parked_domains_limit',
					'_hf_addon_domains_limit',
					'_hf_setup_fee',
					'_hf_trial_days',
				)
			);
		}

		if ( 'hf_reseller_hosting' === $type ) {
			$fields = array_merge(
				$fields,
				array(
					'_hf_max_accounts',
					'_hf_aggregate_disk_limit',
					'_hf_aggregate_bandwidth_limit',
					'_hf_reseller_plan',
				)
			);
		}

		if ( in_array( $type, array( 'hf_vps_server', 'hf_dedicated_server' ), true ) ) {
			$fields = array_merge(
				$fields,
				array(
					'_hf_cpu_cores',
					'_hf_ram',
					'_hf_disk',
					'_hf_disk_type',
					'_hf_bandwidth',
					'_hf_ipv4_count',
					'_hf_ipv6_count',
					'_hf_server_group',
					'_hf_setup_fee',
				)
			);
		}

		if ( 'hf_dedicated_server' === $type ) {
			$fields = array_merge(
				$fields,
				array(
					'_hf_processor',
					'_hf_raid',
					'_hf_uplink',
					'_hf_datacenter',
				)
			);
		}

		if ( 'hf_domain' === $type ) {
			$fields = array_merge( $fields, array( '_hf_registrar', '_hf_registration_years' ) );
		}

		if ( 'hf_ssl_certificate' === $type ) {
			$fields = array_merge(
				$fields,
				array(
					'_hf_ssl_type',
					'_hf_ssl_brand',
					'_hf_validity_months',
					'_hf_max_san_domains',
					'_hf_warranty',
				)
			);
		}

		if ( 'hf_software_license' === $type ) {
			$fields = array_merge(
				$fields,
				array(
					'_hf_license_type',
					'_hf_license_provider',
					'_hf_key_prefix',
					'_hf_max_activations',
				)
			);
		}

		return $fields;
	}

	/**
	 * Get checkbox fields for a product type.
	 *
	 * @param string $type Product type.
	 * @return array<string>
	 */
	private static function get_checkbox_fields( string $type ): array {
		$fields = array();

		if ( in_array( $type, array( 'hf_shared_hosting', 'hf_reseller_hosting' ), true ) ) {
			$fields = array_merge( $fields, array( '_hf_require_domain', '_hf_auto_username' ) );
		}

		if ( 'hf_reseller_hosting' === $type ) {
			$fields[] = '_hf_whm_access';
		}

		if ( in_array( $type, array( 'hf_vps_server', 'hf_dedicated_server' ), true ) ) {
			$fields = array_merge( $fields, array( '_hf_require_hostname', '_hf_require_root_password' ) );
		}

		if ( 'hf_dedicated_server' === $type ) {
			$fields[] = '_hf_ipmi_access';
		}

		if ( 'hf_domain' === $type ) {
			$fields = array_merge( $fields, array( '_hf_auto_renew_default', '_hf_allow_transfer', '_hf_id_protection' ) );
		}

		if ( 'hf_ssl_certificate' === $type ) {
			$fields = array_merge( $fields, array( '_hf_multi_domain', '_hf_require_domain', '_hf_require_csr' ) );
		}

		if ( 'hf_software_license' === $type ) {
			$fields = array_merge(
				$fields,
				array(
					'_hf_require_server_ip',
					'_hf_auto_generate_key',
					'_hf_allow_ip_change',
				)
			);
		}

		return $fields;
	}

	/**
	 * Get textarea fields that should be saved as JSON arrays.
	 *
	 * @param string $type Product type.
	 * @return array<string>
	 */
	private static function get_textarea_json_fields( string $type ): array {
		$fields = array();

		if ( in_array( $type, array( 'hf_vps_server', 'hf_dedicated_server' ), true ) ) {
			$fields[] = '_hf_os_choices';
		}

		if ( 'hf_domain' === $type ) {
			$fields[] = '_hf_allowed_tlds';
		}

		return $fields;
	}

	/**
	 * Enqueue product editor scripts.
	 *
	 * @return void
	 */
	public static function enqueue_scripts(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}

		wp_enqueue_style(
			'hf-product-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/css/product-admin.css',
			array(),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hf-product-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/js/product-admin.js',
			array( 'jquery' ),
			HOSTFORGE_VERSION,
			true
		);
	}

	/**
	 * Get OS choices from meta as array.
	 *
	 * @param int $product_id Product ID.
	 * @return array<string>
	 */
	private static function get_os_choices( int $product_id ): array {
		return self::get_json_array_meta( $product_id, '_hf_os_choices' );
	}

	/**
	 * Get a JSON-encoded array meta value.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $meta_key   Meta key.
	 * @return array<string>
	 */
	private static function get_json_array_meta( int $product_id, string $meta_key ): array {
		$value = get_post_meta( $product_id, $meta_key, true );
		if ( is_string( $value ) && ! empty( $value ) ) {
			$decoded = json_decode( $value, true );
			return is_array( $decoded ) ? $decoded : array();
		}
		return is_array( $value ) ? $value : array();
	}
}
