<?php
/**
 * Settings registration.
 *
 * Registers settings sections and fields for HostForge > Settings > General.
 *
 * @package HostForge\Admin
 */

namespace HostForge\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Settings
 */
class HF_Settings {

	/**
	 * Option group.
	 *
	 * @var string
	 */
	private const OPTION_GROUP = 'hf_settings';

	/**
	 * Initialize settings.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_init', array( static::class, 'register' ) );
	}

	/**
	 * Register settings, sections and fields.
	 *
	 * @return void
	 */
	public static function register(): void {
		// --- Company Info Section ---
		add_settings_section(
			'hf_company_section',
			__( 'Company Information', 'hostforge' ),
			null,
			self::OPTION_GROUP
		);

		self::add_field( 'hf_company_name', __( 'Company Name', 'hostforge' ), 'text', 'hf_company_section' );
		self::add_field( 'hf_company_email', __( 'Support Email', 'hostforge' ), 'email', 'hf_company_section' );
		self::add_field( 'hf_company_phone', __( 'Phone', 'hostforge' ), 'text', 'hf_company_section' );
		self::add_field( 'hf_company_address', __( 'Address', 'hostforge' ), 'textarea', 'hf_company_section' );

		// --- General Section ---
		add_settings_section(
			'hf_general_section',
			__( 'General Settings', 'hostforge' ),
			null,
			self::OPTION_GROUP
		);

		self::add_field( 'hf_debug_mode', __( 'Debug Mode', 'hostforge' ), 'checkbox', 'hf_general_section', __( 'Enable debug logging', 'hostforge' ) );
		self::add_field( 'hf_delete_data_on_uninstall', __( 'Delete Data on Uninstall', 'hostforge' ), 'checkbox', 'hf_general_section', __( 'Remove all HostForge data when the plugin is uninstalled', 'hostforge' ) );
		self::add_field( 'hf_log_retention_days', __( 'Log Retention (days)', 'hostforge' ), 'number', 'hf_general_section', '', '30' );

		// --- License Section ---
		add_settings_section(
			'hf_license_section',
			__( 'License', 'hostforge' ),
			null,
			self::OPTION_GROUP
		);

		self::add_field( 'hf_license_key', __( 'License Key', 'hostforge' ), 'text', 'hf_license_section' );
	}

	/**
	 * Add a settings field.
	 *
	 * @param string $id          Option name.
	 * @param string $title       Field label.
	 * @param string $type        Field type: text, email, textarea, checkbox, number.
	 * @param string $section     Section ID.
	 * @param string $description Optional description.
	 * @param string $default     Default value.
	 * @return void
	 */
	private static function add_field(
		string $id,
		string $title,
		string $type,
		string $section,
		string $description = '',
		string $default = ''
	): void {
		register_setting(
			self::OPTION_GROUP,
			$id,
			array(
				'type'              => self::get_wp_type( $type ),
				'sanitize_callback' => self::get_sanitize_callback( $type ),
				'default'           => $default,
			)
		);

		add_settings_field(
			$id,
			$title,
			function () use ( $id, $type, $description, $default ) {
				self::render_field( $id, $type, $description, $default );
			},
			self::OPTION_GROUP,
			$section
		);
	}

	/**
	 * Render a settings field.
	 *
	 * @param string $id          Option name.
	 * @param string $type        Field type.
	 * @param string $description Description.
	 * @param string $default     Default value.
	 * @return void
	 */
	private static function render_field( string $id, string $type, string $description, string $default ): void {
		$value = get_option( $id, $default );

		switch ( $type ) {
			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%1$s" rows="3" cols="50" class="large-text">%2$s</textarea>',
					esc_attr( $id ),
					esc_textarea( $value )
				);
				break;

			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%1$s" value="yes" %2$s /> %3$s</label>',
					esc_attr( $id ),
					checked( $value, 'yes', false ),
					esc_html( $description )
				);
				return;

			case 'number':
				printf(
					'<input type="number" id="%1$s" name="%1$s" value="%2$s" min="1" class="small-text" />',
					esc_attr( $id ),
					esc_attr( $value )
				);
				break;

			default:
				printf(
					'<input type="%1$s" id="%2$s" name="%2$s" value="%3$s" class="regular-text" />',
					esc_attr( $type ),
					esc_attr( $id ),
					esc_attr( $value )
				);
				break;
		}

		if ( ! empty( $description ) && 'checkbox' !== $type ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}
	}

	/**
	 * Get WordPress setting type.
	 *
	 * @param string $type Field type.
	 * @return string
	 */
	private static function get_wp_type( string $type ): string {
		return match ( $type ) {
			'number'   => 'integer',
			'checkbox' => 'string',
			default    => 'string',
		};
	}

	/**
	 * Get sanitize callback.
	 *
	 * @param string $type Field type.
	 * @return callable
	 */
	private static function get_sanitize_callback( string $type ): callable {
		return match ( $type ) {
			'email'  => 'sanitize_email',
			'number' => 'absint',
			default  => 'sanitize_text_field',
		};
	}

	/**
	 * Get the option group name.
	 *
	 * @return string
	 */
	public static function get_option_group(): string {
		return self::OPTION_GROUP;
	}
}
