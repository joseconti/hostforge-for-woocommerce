<?php
/**
 * Plugin deactivator.
 *
 * Unschedules Action Scheduler tasks and flushes rewrite rules.
 * Does NOT delete any data.
 *
 * @package HostForge
 */

namespace HostForge;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Deactivator
 */
class HF_Deactivator {

	/**
	 * Run deactivation logic.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		self::unschedule_actions();
		flush_rewrite_rules();
	}

	/**
	 * Unschedule all HostForge Action Scheduler tasks.
	 *
	 * @return void
	 */
	private static function unschedule_actions(): void {
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			return;
		}

		$groups = array(
			'hostforge',
			'hostforge-provisioning',
			'hostforge-server-monitor',
			'hostforge-tickets',
			'hostforge-domains',
			'hostforge-affiliates',
			'hostforge-security',
			'hostforge-reports',
			'hostforge-logs',
		);

		foreach ( $groups as $group ) {
			as_unschedule_all_actions( '', array(), $group );
		}
	}
}
