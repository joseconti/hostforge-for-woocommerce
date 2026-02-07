<?php
/**
 * Settings trait.
 *
 * Provides reusable methods for reading/writing module-specific settings.
 *
 * @package HostForge\Traits
 */

namespace HostForge\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait HF_Has_Settings
 */
trait HF_Has_Settings {

	/**
	 * Get a module setting.
	 *
	 * @param string $key     Setting key (without prefix).
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	protected function get_setting( string $key, mixed $default = '' ): mixed {
		$option_name = $this->get_setting_key( $key );
		return get_option( $option_name, $default );
	}

	/**
	 * Update a module setting.
	 *
	 * @param string $key   Setting key (without prefix).
	 * @param mixed  $value Value to store.
	 * @return bool True if updated.
	 */
	protected function update_setting( string $key, mixed $value ): bool {
		$option_name = $this->get_setting_key( $key );
		return update_option( $option_name, $value );
	}

	/**
	 * Delete a module setting.
	 *
	 * @param string $key Setting key (without prefix).
	 * @return bool True if deleted.
	 */
	protected function delete_setting( string $key ): bool {
		$option_name = $this->get_setting_key( $key );
		return delete_option( $option_name );
	}

	/**
	 * Build the full option name for a setting.
	 *
	 * @param string $key Setting key.
	 * @return string Full option name (e.g. hf_server_manager_some_setting).
	 */
	private function get_setting_key( string $key ): string {
		$prefix = 'hf_';

		if ( method_exists( $this, 'get_id' ) ) {
			$prefix .= str_replace( '-', '_', $this->get_id() ) . '_';
		}

		return $prefix . $key;
	}
}
