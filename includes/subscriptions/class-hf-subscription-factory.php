<?php
/**
 * Subscription Adapter Factory.
 *
 * Auto-detects the active subscription plugin and returns the appropriate adapter.
 *
 * @package HostForge\Subscriptions
 */

namespace HostForge\Subscriptions;

use HostForge\Interfaces\HF_Subscription_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Subscription_Factory
 */
class HF_Subscription_Factory {

	/**
	 * Cached adapter instance.
	 *
	 * @var HF_Subscription_Adapter|null
	 */
	private static ?HF_Subscription_Adapter $adapter = null;

	/**
	 * Whether detection has been attempted.
	 *
	 * @var bool
	 */
	private static bool $detected = false;

	/**
	 * Get the active subscription adapter.
	 *
	 * Returns the adapter for the first detected subscription plugin.
	 * Returns null if no supported subscription plugin is active.
	 *
	 * @return HF_Subscription_Adapter|null
	 */
	public static function get_adapter(): ?HF_Subscription_Adapter {
		if ( self::$detected ) {
			return self::$adapter;
		}

		self::$detected = true;

		$adapters = self::get_registered_adapters();

		foreach ( $adapters as $adapter_class ) {
			if ( ! class_exists( $adapter_class ) ) {
				continue;
			}

			$adapter = new $adapter_class();

			if ( $adapter instanceof HF_Subscription_Adapter && $adapter->is_available() ) {
				self::$adapter = $adapter;
				break;
			}
		}

		return self::$adapter;
	}

	/**
	 * Get registered adapter class names in priority order.
	 *
	 * @return array<string> Fully-qualified class names.
	 */
	private static function get_registered_adapters(): array {
		$adapters = array(
			'HostForge\\Subscriptions\\HF_WCS_Adapter',
			'HostForge\\Subscriptions\\HF_YITH_Adapter',
			'HostForge\\Subscriptions\\HF_Advanced_Subs_Adapter',
		);

		/**
		 * Filter the list of subscription adapter classes.
		 *
		 * Allows third-party plugins to register their own subscription adapter.
		 *
		 * @param array<string> $adapters Fully-qualified class names.
		 */
		return apply_filters( 'hostforge_subscription_adapters', $adapters );
	}

	/**
	 * Check if any subscription plugin is available.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return null !== self::get_adapter();
	}

	/**
	 * Get the name of the active subscription plugin.
	 *
	 * @return string Plugin name or empty string if none active.
	 */
	public static function get_active_plugin_name(): string {
		$adapter = self::get_adapter();
		return $adapter ? $adapter->get_name() : '';
	}

	/**
	 * Reset the cached adapter (useful for testing).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$adapter  = null;
		self::$detected = false;
	}
}
