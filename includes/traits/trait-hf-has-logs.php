<?php
/**
 * Logging trait.
 *
 * Provides a reusable log() method for any class that needs
 * to write entries to the hf_logs table.
 *
 * @package HostForge\Traits
 */

namespace HostForge\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait HF_Has_Logs
 */
trait HF_Has_Logs {

	/**
	 * Write a log entry to the hf_logs table.
	 *
	 * @param string $level   Log level: debug, info, notice, warning, error, critical.
	 * @param string $message Human-readable message.
	 * @param array  $context Optional contextual data (stored as JSON).
	 * @return void
	 */
	protected function log( string $level, string $message, array $context = array() ): void {
		global $wpdb;

		// Determine module name from the using class.
		$module = 'core';
		if ( method_exists( $this, 'get_id' ) ) {
			$module = $this->get_id();
		}

		// Skip debug logs unless debug mode is enabled.
		if ( 'debug' === $level && 'yes' !== get_option( 'hf_debug_mode', 'no' ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$wpdb->prefix . 'hf_logs',
			array(
				'module'     => $module,
				'level'      => $level,
				'message'    => $message,
				'context'    => ! empty( $context ) ? wp_json_encode( $context ) : null,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	protected function log_debug( string $message, array $context = array() ): void {
		$this->log( 'debug', $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	protected function log_info( string $message, array $context = array() ): void {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	protected function log_warning( string $message, array $context = array() ): void {
		$this->log( 'warning', $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	protected function log_error( string $message, array $context = array() ): void {
		$this->log( 'error', $message, $context );
	}

	/**
	 * Log a critical message.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 * @return void
	 */
	protected function log_critical( string $message, array $context = array() ): void {
		$this->log( 'critical', $message, $context );
	}
}
