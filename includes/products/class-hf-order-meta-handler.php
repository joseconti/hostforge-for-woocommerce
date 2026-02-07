<?php
/**
 * Order Meta Handler.
 *
 * Saves product hosting metadata to the WooCommerce order (HPOS compatible).
 * Displays hosting info in admin order details and customer emails.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Order_Meta_Handler
 */
class HF_Order_Meta_Handler {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'save_product_meta_to_order_item' ), 10, 4 );
		add_filter( 'woocommerce_order_item_display_meta_key', array( __CLASS__, 'display_meta_key' ), 10, 3 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hidden_order_itemmeta' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_hosting_info_admin' ) );
	}

	/**
	 * Save relevant product meta to the order line item.
	 *
	 * @param \WC_Order_Item_Product $item          Order item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values        Cart item values.
	 * @param \WC_Order              $order         Order object.
	 * @return void
	 */
	public static function save_product_meta_to_order_item( \WC_Order_Item_Product $item, string $cart_item_key, array $values, \WC_Order $order ): void {
		$product = $item->get_product();

		if ( ! $product || ! HF_Product_Types::is_hf_type( $product->get_type() ) ) {
			return;
		}

		$type = $product->get_type();

		// Save product type.
		$item->add_meta_data( '_hf_product_type', $type );

		// Save type-specific meta.
		$meta_keys = self::get_meta_keys_for_type( $type );

		foreach ( $meta_keys as $key ) {
			$value = $product->get_meta( $key, true );
			if ( '' !== $value && null !== $value ) {
				$item->add_meta_data( $key, $value );
			}
		}
	}

	/**
	 * Get the meta keys to copy for a product type.
	 *
	 * @param string $type Product type slug.
	 * @return array<string>
	 */
	private static function get_meta_keys_for_type( string $type ): array {
		$keys = array();

		switch ( $type ) {
			case 'hf_shared_hosting':
			case 'hf_reseller_hosting':
				$keys = array(
					'_hf_server_group',
					'_hf_plan',
					'_hf_disk_limit',
					'_hf_bandwidth_limit',
					'_hf_email_limit',
					'_hf_db_limit',
					'_hf_setup_fee',
				);
				if ( 'hf_reseller_hosting' === $type ) {
					$keys = array_merge(
						$keys,
						array(
							'_hf_max_accounts',
							'_hf_reseller_plan',
						)
					);
				}
				break;

			case 'hf_vps_server':
			case 'hf_dedicated_server':
				$keys = array(
					'_hf_cpu_cores',
					'_hf_ram',
					'_hf_disk',
					'_hf_disk_type',
					'_hf_bandwidth',
					'_hf_ipv4_count',
					'_hf_server_group',
					'_hf_setup_fee',
				);
				if ( 'hf_dedicated_server' === $type ) {
					$keys = array_merge(
						$keys,
						array(
							'_hf_processor',
							'_hf_datacenter',
						)
					);
				}
				break;

			case 'hf_domain':
				$keys = array( '_hf_registrar', '_hf_registration_years' );
				break;

			case 'hf_ssl_certificate':
				$keys = array( '_hf_ssl_type', '_hf_ssl_brand', '_hf_validity_months' );
				break;

			case 'hf_software_license':
				$keys = array( '_hf_license_type', '_hf_license_provider', '_hf_max_activations' );
				break;
		}

		return $keys;
	}

	/**
	 * Display-friendly meta key labels.
	 *
	 * @param string                $display_key Display key.
	 * @param \WC_Meta_Data         $meta        Meta object.
	 * @param \WC_Order_Item_Product $item       Order item.
	 * @return string
	 */
	public static function display_meta_key( string $display_key, $meta, $item ): string {
		$labels = array(
			'_hf_product_type'       => __( 'Service Type', 'hostforge' ),
			'_hf_server_group'       => __( 'Server Group', 'hostforge' ),
			'_hf_plan'               => __( 'Hosting Plan', 'hostforge' ),
			'_hf_disk_limit'         => __( 'Disk Space (MB)', 'hostforge' ),
			'_hf_bandwidth_limit'    => __( 'Bandwidth (MB)', 'hostforge' ),
			'_hf_email_limit'        => __( 'Email Accounts', 'hostforge' ),
			'_hf_db_limit'           => __( 'Databases', 'hostforge' ),
			'_hf_cpu_cores'          => __( 'CPU Cores', 'hostforge' ),
			'_hf_ram'                => __( 'RAM (MB)', 'hostforge' ),
			'_hf_disk'               => __( 'Disk (GB)', 'hostforge' ),
			'_hf_disk_type'          => __( 'Disk Type', 'hostforge' ),
			'_hf_bandwidth'          => __( 'Bandwidth (GB)', 'hostforge' ),
			'_hf_ipv4_count'         => __( 'IPv4 Addresses', 'hostforge' ),
			'_hf_max_accounts'       => __( 'Max Accounts', 'hostforge' ),
			'_hf_reseller_plan'      => __( 'Reseller Plan', 'hostforge' ),
			'_hf_processor'          => __( 'Processor', 'hostforge' ),
			'_hf_datacenter'         => __( 'Datacenter', 'hostforge' ),
			'_hf_registrar'          => __( 'Registrar', 'hostforge' ),
			'_hf_registration_years' => __( 'Registration Years', 'hostforge' ),
			'_hf_ssl_type'           => __( 'SSL Type', 'hostforge' ),
			'_hf_ssl_brand'          => __( 'SSL Brand', 'hostforge' ),
			'_hf_validity_months'    => __( 'Validity (months)', 'hostforge' ),
			'_hf_license_type'       => __( 'License Type', 'hostforge' ),
			'_hf_license_provider'   => __( 'License Provider', 'hostforge' ),
			'_hf_max_activations'    => __( 'Max Activations', 'hostforge' ),
			'_hf_setup_fee'          => __( 'Setup Fee', 'hostforge' ),
		);

		return $labels[ $display_key ] ?? $display_key;
	}

	/**
	 * Hide internal meta keys from the order item display.
	 *
	 * @param array<string> $hidden_meta Hidden meta keys.
	 * @return array<string>
	 */
	public static function hidden_order_itemmeta( array $hidden_meta ): array {
		$hidden_meta[] = '_hf_product_type';

		/**
		 * Filters the list of hidden order item meta keys for HostForge products.
		 *
		 * Allows adding additional meta keys to hide from the order item display,
		 * or removing keys to make them visible in admin order views.
		 *
		 * @since 1.0.0
		 *
		 * @param array $hidden_meta Array of meta key strings to hide.
		 */
		return apply_filters( 'hostforge_order_meta_keys', $hidden_meta );
	}

	/**
	 * Display hosting configuration info in admin order details.
	 *
	 * @param \WC_Order $order Order object.
	 * @return void
	 */
	public static function display_hosting_info_admin( \WC_Order $order ): void {
		$hosting_fields = array(
			'_hf_hosting_domain'    => __( 'Domain', 'hostforge' ),
			'_hf_server_hostname'   => __( 'Hostname', 'hostforge' ),
			'_hf_server_os'         => __( 'Operating System', 'hostforge' ),
			'_hf_domain_name'       => __( 'Domain Name', 'hostforge' ),
			'_hf_ssl_domain'        => __( 'SSL Domain', 'hostforge' ),
			'_hf_license_server_ip' => __( 'Server IP', 'hostforge' ),
		);

		/**
		 * Filters the hosting meta fields displayed in the admin order detail.
		 *
		 * Allows adding, removing, or modifying the hosting-related fields
		 * shown after the billing address in admin order views.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $hosting_fields Associative array of meta_key => label.
		 * @param \WC_Order $order         The WooCommerce order object.
		 */
		$hosting_fields = apply_filters( 'hostforge_order_hosting_meta', $hosting_fields, $order );

		$has_data = false;

		foreach ( $hosting_fields as $key => $label ) {
			$value = $order->get_meta( $key );
			if ( ! empty( $value ) ) {
				if ( ! $has_data ) {
					echo '<h3>' . esc_html__( 'Service Configuration', 'hostforge' ) . '</h3>';
					$has_data = true;
				}
				echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
			}
		}
	}
}
