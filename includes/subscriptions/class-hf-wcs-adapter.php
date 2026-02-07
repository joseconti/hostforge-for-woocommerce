<?php
/**
 * WooCommerce Subscriptions Adapter.
 *
 * Implements the subscription adapter interface for WooCommerce Subscriptions plugin.
 *
 * @package HostForge\Subscriptions
 */

namespace HostForge\Subscriptions;

use HostForge\Interfaces\HF_Subscription_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_WCS_Adapter
 */
class HF_WCS_Adapter implements HF_Subscription_Adapter {

	/**
	 * Check if WooCommerce Subscriptions is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return class_exists( 'WC_Subscriptions' );
	}

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'WooCommerce Subscriptions';
	}

	/**
	 * Create a subscription.
	 *
	 * WCS creates subscriptions automatically on order completion,
	 * so this method is a fallback for programmatic creation.
	 *
	 * @param array $params Subscription parameters.
	 * @return int|false Subscription ID or false on failure.
	 */
	public function create_subscription( array $params ): int|false {
		if ( ! function_exists( 'wcs_create_subscription' ) ) {
			return false;
		}

		$defaults = array(
			'status'           => 'pending',
			'billing_period'   => 'month',
			'billing_interval' => 1,
		);

		$args = wp_parse_args( $params, $defaults );

		$subscription = wcs_create_subscription( $args );

		if ( is_wp_error( $subscription ) ) {
			return false;
		}

		return $subscription->get_id();
	}

	/**
	 * Cancel a subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function cancel_subscription( int $subscription_id ): bool {
		$subscription = $this->get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return false;
		}

		$subscription->update_status( 'cancelled' );
		return 'cancelled' === $subscription->get_status();
	}

	/**
	 * Suspend (put on hold) a subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function suspend_subscription( int $subscription_id ): bool {
		$subscription = $this->get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return false;
		}

		$subscription->update_status( 'on-hold' );
		return 'on-hold' === $subscription->get_status();
	}

	/**
	 * Reactivate a suspended subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function reactivate_subscription( int $subscription_id ): bool {
		$subscription = $this->get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return false;
		}

		$subscription->update_status( 'active' );
		return 'active' === $subscription->get_status();
	}

	/**
	 * Get the subscription status.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return string Normalized status.
	 */
	public function get_status( int $subscription_id ): string {
		$subscription = $this->get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return 'unknown';
		}

		return $this->normalize_status( $subscription->get_status() );
	}

	/**
	 * Get the next payment date.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return string|null Date in Y-m-d H:i:s format or null.
	 */
	public function get_next_payment_date( int $subscription_id ): ?string {
		$subscription = $this->get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return null;
		}

		$next_payment = $subscription->get_date( 'next_payment' );

		return $next_payment ? $next_payment : null;
	}

	/**
	 * Get all subscriptions for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array<int> Subscription IDs.
	 */
	public function get_subscriptions_by_user( int $user_id ): array {
		if ( ! function_exists( 'wcs_get_subscriptions' ) ) {
			return array();
		}

		$subscriptions = wcs_get_subscriptions(
			array(
				'customer_id'            => $user_id,
				'subscriptions_per_page' => -1,
			)
		);

		return array_map(
			function ( $subscription ) {
				return $subscription->get_id();
			},
			$subscriptions
		);
	}

	/**
	 * Get the WCS status change hooks mapped to HostForge events.
	 *
	 * @return array<string, string> HostForge event => WCS hook.
	 */
	public function get_status_hooks(): array {
		return array(
			'activated'   => 'woocommerce_subscription_status_active',
			'suspended'   => 'woocommerce_subscription_status_on-hold',
			'cancelled'   => 'woocommerce_subscription_status_cancelled',
			'expired'     => 'woocommerce_subscription_status_expired',
			'reactivated' => 'woocommerce_subscription_status_on-hold_to_active',
			'renewed'     => 'woocommerce_subscription_renewal_payment_complete',
		);
	}

	/**
	 * Get a WCS subscription object.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return \WC_Subscription|null
	 */
	private function get_subscription( int $subscription_id ): ?\WC_Subscription {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return null;
		}

		$subscription = wcs_get_subscription( $subscription_id );

		return $subscription instanceof \WC_Subscription ? $subscription : null;
	}

	/**
	 * Normalize WCS status to HostForge standard status.
	 *
	 * @param string $status WCS status.
	 * @return string Normalized status (active, on-hold, cancelled, expired, pending).
	 */
	private function normalize_status( string $status ): string {
		$map = array(
			'active'         => 'active',
			'on-hold'        => 'on-hold',
			'cancelled'      => 'cancelled',
			'expired'        => 'expired',
			'pending'        => 'pending',
			'pending-cancel' => 'cancelled',
			'switched'       => 'active',
		);

		return $map[ $status ] ?? 'unknown';
	}
}
