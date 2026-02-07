<?php
/**
 * Advanced Subscriptions for WooCommerce Adapter.
 *
 * Implements the subscription adapter interface for Advanced Subscriptions plugin.
 *
 * @package HostForge\Subscriptions
 */

namespace HostForge\Subscriptions;

use HostForge\Interfaces\HF_Subscription_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Advanced_Subs_Adapter
 */
class HF_Advanced_Subs_Adapter implements HF_Subscription_Adapter {

	/**
	 * Check if Advanced Subscriptions for WooCommerce is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'ASWC_VERSION' ) || class_exists( 'Advanced_Subscriptions_WC' );
	}

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Advanced Subscriptions for WooCommerce';
	}

	/**
	 * Create a subscription.
	 *
	 * Advanced Subs creates subscriptions on order completion.
	 * This is a programmatic fallback.
	 *
	 * @param array $params Subscription parameters.
	 * @return int|false Subscription ID or false on failure.
	 */
	public function create_subscription( array $params ): int|false {
		if ( ! function_exists( 'aswc_create_subscription' ) ) {
			return false;
		}

		$subscription_id = aswc_create_subscription( $params );

		return $subscription_id ? (int) $subscription_id : false;
	}

	/**
	 * Cancel a subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function cancel_subscription( int $subscription_id ): bool {
		return $this->update_status( $subscription_id, 'cancelled' );
	}

	/**
	 * Suspend a subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function suspend_subscription( int $subscription_id ): bool {
		return $this->update_status( $subscription_id, 'on-hold' );
	}

	/**
	 * Reactivate a suspended subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function reactivate_subscription( int $subscription_id ): bool {
		return $this->update_status( $subscription_id, 'active' );
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

		$status = get_post_meta( $subscription_id, '_aswc_status', true );

		return $this->normalize_status( $status ?: 'unknown' );
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

		$next_payment = get_post_meta( $subscription_id, '_aswc_next_payment', true );

		if ( empty( $next_payment ) ) {
			return null;
		}

		return is_numeric( $next_payment )
			? wp_date( 'Y-m-d H:i:s', (int) $next_payment )
			: $next_payment;
	}

	/**
	 * Get all subscriptions for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array<int> Subscription IDs.
	 */
	public function get_subscriptions_by_user( int $user_id ): array {
		$posts = get_posts(
			array(
				'post_type'      => 'aswc_subscription',
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_aswc_user_id',
						'value' => $user_id,
						'type'  => 'NUMERIC',
					),
				),
				'fields'         => 'ids',
			)
		);

		return array_map( 'absint', $posts );
	}

	/**
	 * Get Advanced Subs status change hooks mapped to HostForge events.
	 *
	 * @return array<string, string> HostForge event => Advanced Subs hook.
	 */
	public function get_status_hooks(): array {
		return array(
			'activated'   => 'aswc_subscription_status_active',
			'suspended'   => 'aswc_subscription_status_on-hold',
			'cancelled'   => 'aswc_subscription_status_cancelled',
			'expired'     => 'aswc_subscription_status_expired',
			'reactivated' => 'aswc_subscription_status_on-hold_to_active',
			'renewed'     => 'aswc_subscription_renewal_complete',
		);
	}

	/**
	 * Update a subscription status.
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param string $status          New status.
	 * @return bool
	 */
	private function update_status( int $subscription_id, string $status ): bool {
		$subscription = $this->get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return false;
		}

		if ( function_exists( 'aswc_update_subscription_status' ) ) {
			return aswc_update_subscription_status( $subscription_id, $status );
		}

		update_post_meta( $subscription_id, '_aswc_status', sanitize_text_field( $status ) );

		return true;
	}

	/**
	 * Get a subscription post object.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return \WP_Post|null
	 */
	private function get_subscription( int $subscription_id ): ?\WP_Post {
		$post = get_post( $subscription_id );

		if ( ! $post || 'aswc_subscription' !== $post->post_type ) {
			return null;
		}

		return $post;
	}

	/**
	 * Normalize Advanced Subs status to HostForge standard status.
	 *
	 * @param string $status Advanced Subs status.
	 * @return string Normalized status.
	 */
	private function normalize_status( string $status ): string {
		$map = array(
			'active'    => 'active',
			'on-hold'   => 'on-hold',
			'cancelled' => 'cancelled',
			'expired'   => 'expired',
			'pending'   => 'pending',
			'trial'     => 'active',
		);

		return $map[ $status ] ?? 'unknown';
	}
}
