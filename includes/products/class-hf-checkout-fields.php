<?php
/**
 * Checkout Fields for HostForge Products.
 *
 * Injects custom fields into WooCommerce checkout based on product type.
 * Compatible with classic checkout and block-based checkout.
 *
 * @package HostForge\Products
 */

namespace HostForge\Products;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Checkout_Fields
 */
class HF_Checkout_Fields {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Classic checkout.
		add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'render_checkout_fields' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate_checkout_fields' ) );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_checkout_fields_to_order' ), 10, 2 );

		// Block checkout — register integration.
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'register_block_checkout_integration' ) );
	}

	/**
	 * Render custom checkout fields based on cart contents.
	 *
	 * @param \WC_Checkout $checkout Checkout instance.
	 * @return void
	 */
	public static function render_checkout_fields( $checkout ): void {
		$fields = self::get_fields_for_cart();

		if ( empty( $fields ) ) {
			return;
		}

		echo '<div id="hf_checkout_fields">';
		echo '<h3>' . esc_html__( 'Service Configuration', 'hostforge' ) . '</h3>';

		foreach ( $fields as $field ) {
			$field_id    = 'hf_' . $field['id'];
			$field_value = '';

			if ( ! empty( $checkout ) && method_exists( $checkout, 'get_value' ) ) {
				$field_value = $checkout->get_value( $field_id );
			}

			switch ( $field['type'] ) {
				case 'text':
				case 'email':
				case 'url':
					woocommerce_form_field(
						$field_id,
						array(
							'type'        => $field['type'],
							'label'       => $field['label'],
							'required'    => $field['required'],
							'class'       => array( 'form-row-wide' ),
							'placeholder' => $field['placeholder'] ?? '',
						),
						$field_value
					);
					break;

				case 'select':
					woocommerce_form_field(
						$field_id,
						array(
							'type'     => 'select',
							'label'    => $field['label'],
							'required' => $field['required'],
							'class'    => array( 'form-row-wide' ),
							'options'  => $field['options'],
						),
						$field_value
					);
					break;

				case 'textarea':
					woocommerce_form_field(
						$field_id,
						array(
							'type'        => 'textarea',
							'label'       => $field['label'],
							'required'    => $field['required'],
							'class'       => array( 'form-row-wide' ),
							'placeholder' => $field['placeholder'] ?? '',
						),
						$field_value
					);
					break;

				case 'password':
					woocommerce_form_field(
						$field_id,
						array(
							'type'     => 'password',
							'label'    => $field['label'],
							'required' => $field['required'],
							'class'    => array( 'form-row-wide' ),
						),
						''
					);
					break;
			}
		}

		echo '</div>';
	}

	/**
	 * Validate custom checkout fields.
	 *
	 * @return void
	 */
	public static function validate_checkout_fields(): void {
		$fields = self::get_fields_for_cart();

		foreach ( $fields as $field ) {
			$field_id = 'hf_' . $field['id'];

			if ( $field['required'] ) {
				$value = isset( $_POST[ $field_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( empty( $value ) ) {
					/* translators: %s: field label */
					wc_add_notice( sprintf( __( '%s is a required field.', 'hostforge' ), '<strong>' . esc_html( $field['label'] ) . '</strong>' ), 'error' );
				}
			}

			// Type-specific validation.
			if ( isset( $_POST[ $field_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$value = sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( ! empty( $value ) ) {
					self::validate_field_value( $field, $value );
				}
			}

			/**
			 * Filters the validation result for a single checkout field.
			 *
			 * Allows third-party code to add custom validation rules for
			 * HostForge checkout fields. Return false or add wc_add_notice()
			 * errors to fail validation.
			 *
			 * @since 1.0.0
			 *
			 * @param bool   $is_valid Whether the field value is considered valid so far.
			 * @param array  $field    The field definition array.
			 * @param string $value    The submitted field value (sanitized).
			 */
			$value = isset( $_POST[ $field_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			apply_filters( 'hostforge_checkout_field_validation', true, $field, $value );
		}
	}

	/**
	 * Validate a single field value by type.
	 *
	 * @param array  $field Field definition.
	 * @param string $value Field value.
	 * @return void
	 */
	private static function validate_field_value( array $field, string $value ): void {
		if ( ! empty( $field['validate'] ) ) {
			switch ( $field['validate'] ) {
				case 'domain':
					if ( ! preg_match( '/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?\.[a-zA-Z]{2,}$/', $value ) ) {
						/* translators: %s: field label */
						wc_add_notice( sprintf( __( '%s must be a valid domain name.', 'hostforge' ), '<strong>' . esc_html( $field['label'] ) . '</strong>' ), 'error' );
					}
					break;

				case 'hostname':
					if ( ! preg_match( '/^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?\.[a-zA-Z]{2,}$/', $value ) ) {
						/* translators: %s: field label */
						wc_add_notice( sprintf( __( '%s must be a valid hostname.', 'hostforge' ), '<strong>' . esc_html( $field['label'] ) . '</strong>' ), 'error' );
					}
					break;

				case 'ipv4':
					if ( ! filter_var( $value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
						/* translators: %s: field label */
						wc_add_notice( sprintf( __( '%s must be a valid IPv4 address.', 'hostforge' ), '<strong>' . esc_html( $field['label'] ) . '</strong>' ), 'error' );
					}
					break;
			}
		}
	}

	/**
	 * Save custom checkout fields to order meta (HPOS compatible).
	 *
	 * @param \WC_Order           $order Order object.
	 * @param array<string,mixed> $data  Posted data.
	 * @return void
	 */
	public static function save_checkout_fields_to_order( \WC_Order $order, array $data ): void {
		$fields = self::get_fields_for_cart();

		foreach ( $fields as $field ) {
			$field_id = 'hf_' . $field['id'];

			if ( isset( $_POST[ $field_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$value = sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				if ( ! empty( $value ) ) {
					$order->update_meta_data( '_' . $field_id, $value );
				}
			}
		}

		/**
		 * Fires after HostForge checkout field meta has been saved to the order.
		 *
		 * Use this hook to save additional meta, trigger provisioning tasks,
		 * or perform post-checkout processing based on the submitted fields.
		 *
		 * @since 1.0.0
		 *
		 * @param \WC_Order $order  The WooCommerce order object.
		 * @param array     $fields The checkout fields that were processed.
		 * @param array     $data   The posted checkout data.
		 */
		do_action( 'hostforge_checkout_meta_saved', $order, $fields, $data );
	}

	/**
	 * Register block checkout integration.
	 *
	 * @return void
	 */
	public static function register_block_checkout_integration(): void {
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
			return;
		}

		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new HF_Block_Checkout_Integration() );
			}
		);
	}

	/**
	 * Get checkout fields based on current cart contents.
	 *
	 * @return array<int, array{id: string, type: string, label: string, required: bool, validate?: string, placeholder?: string, options?: array}>
	 */
	public static function get_fields_for_cart(): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}

		$fields        = array();
		$types_in_cart = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( $product && HF_Product_Types::is_hf_type( $product->get_type() ) ) {
				$types_in_cart[ $product->get_type() ] = $product;
			}
		}

		foreach ( $types_in_cart as $type => $product ) {
			$fields = array_merge( $fields, self::get_fields_for_type( $type, $product ) );
		}

		/**
		 * Filter the checkout fields for HostForge products.
		 *
		 * @param array $fields    Checkout fields.
		 * @param array $types_in_cart Product types in cart.
		 */
		return apply_filters( 'hostforge_checkout_fields', $fields, $types_in_cart );
	}

	/**
	 * Get checkout fields for a specific product type.
	 *
	 * @param string      $type    Product type slug.
	 * @param \WC_Product $product Product object.
	 * @return array<int, array>
	 */
	private static function get_fields_for_type( string $type, \WC_Product $product ): array {
		$fields = array();

		switch ( $type ) {
			case 'hf_shared_hosting':
			case 'hf_reseller_hosting':
				if ( method_exists( $product, 'get_hf_require_domain' ) && $product->get_hf_require_domain() ) {
					$fields[] = array(
						'id'          => 'hosting_domain',
						'type'        => 'text',
						'label'       => __( 'Domain Name', 'hostforge' ),
						'required'    => true,
						'validate'    => 'domain',
						'placeholder' => 'example.com',
					);
				}
				break;

			case 'hf_vps_server':
			case 'hf_dedicated_server':
				if ( method_exists( $product, 'get_hf_require_hostname' ) && $product->get_hf_require_hostname() ) {
					$fields[] = array(
						'id'          => 'server_hostname',
						'type'        => 'text',
						'label'       => __( 'Hostname', 'hostforge' ),
						'required'    => true,
						'validate'    => 'hostname',
						'placeholder' => 'server.example.com',
					);
				}

				if ( method_exists( $product, 'get_hf_require_root_password' ) && $product->get_hf_require_root_password() ) {
					$fields[] = array(
						'id'       => 'server_root_password',
						'type'     => 'password',
						'label'    => __( 'Root Password', 'hostforge' ),
						'required' => true,
					);
				}

				if ( method_exists( $product, 'get_hf_os_choices' ) ) {
					$os_choices = $product->get_hf_os_choices();
					if ( ! empty( $os_choices ) ) {
						$options = array( '' => __( 'Select an OS', 'hostforge' ) );
						foreach ( $os_choices as $os ) {
							$options[ sanitize_title( $os ) ] = $os;
						}
						$fields[] = array(
							'id'       => 'server_os',
							'type'     => 'select',
							'label'    => __( 'Operating System', 'hostforge' ),
							'required' => true,
							'options'  => $options,
						);
					}
				}
				break;

			case 'hf_domain':
				$fields[] = array(
					'id'          => 'domain_name',
					'type'        => 'text',
					'label'       => __( 'Domain Name', 'hostforge' ),
					'required'    => true,
					'validate'    => 'domain',
					'placeholder' => 'example.com',
				);
				break;

			case 'hf_ssl_certificate':
				if ( method_exists( $product, 'get_hf_require_domain' ) && $product->get_hf_require_domain() ) {
					$fields[] = array(
						'id'          => 'ssl_domain',
						'type'        => 'text',
						'label'       => __( 'Domain for SSL', 'hostforge' ),
						'required'    => true,
						'validate'    => 'domain',
						'placeholder' => 'example.com',
					);
				}

				if ( method_exists( $product, 'get_hf_require_csr' ) && $product->get_hf_require_csr() ) {
					$fields[] = array(
						'id'          => 'ssl_csr',
						'type'        => 'textarea',
						'label'       => __( 'CSR (Certificate Signing Request)', 'hostforge' ),
						'required'    => true,
						'placeholder' => '-----BEGIN CERTIFICATE REQUEST-----',
					);
				}
				break;

			case 'hf_software_license':
				if ( method_exists( $product, 'get_hf_require_server_ip' ) && $product->get_hf_require_server_ip() ) {
					$fields[] = array(
						'id'          => 'license_server_ip',
						'type'        => 'text',
						'label'       => __( 'Server IP Address', 'hostforge' ),
						'required'    => true,
						'validate'    => 'ipv4',
						'placeholder' => '192.168.1.1',
					);
				}
				break;
		}

		return $fields;
	}
}
