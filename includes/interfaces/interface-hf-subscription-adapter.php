<?php
/**
 * Subscription Adapter Interface.
 *
 * Contract for subscription plugin adapters (WCS, YITH, Advanced Subs).
 *
 * @package HostForge\Interfaces
 */

namespace HostForge\Interfaces;

defined( 'ABSPATH' ) || exit;

/**
 * Interface HF_Subscription_Adapter
 */
interface HF_Subscription_Adapter {

	/**
	 * Check if the subscription plugin is available and active.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Get the display name of the subscription plugin.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Create a subscription.
	 *
	 * @param array $params {
	 *     Subscription parameters.
	 *     @type int    $order_id   WooCommerce order ID.
	 *     @type int    $product_id Product ID.
	 *     @type int    $user_id    User ID.
	 * }
	 * @return int|false Subscription ID or false on failure.
	 */
	public function create_subscription( array $params ): int|false;

	/**
	 * Cancel a subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function cancel_subscription( int $subscription_id ): bool;

	/**
	 * Suspend (put on hold) a subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function suspend_subscription( int $subscription_id ): bool;

	/**
	 * Reactivate a suspended subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function reactivate_subscription( int $subscription_id ): bool;

	/**
	 * Get the subscription status.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return string Status string (active, on-hold, cancelled, expired, pending).
	 */
	public function get_status( int $subscription_id ): string;

	/**
	 * Get the next payment date.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return string|null Date string or null if no next payment.
	 */
	public function get_next_payment_date( int $subscription_id ): ?string;

	/**
	 * Get all subscriptions for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array<int> List of subscription IDs.
	 */
	public function get_subscriptions_by_user( int $user_id ): array;

	/**
	 * Get the hook name fired when a subscription status changes.
	 *
	 * Each adapter maps its plugin's status-change hooks to HostForge events.
	 *
	 * @return array<string, string> Map of HostForge event => plugin hook.
	 */
	public function get_status_hooks(): array;
}
