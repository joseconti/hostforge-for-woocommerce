<?php
/**
 * SUMO Subscriptions Adapter.
 *
 * Implements the subscription adapter interface for SUMO Subscriptions plugin.
 *
 * @package HostForge\Subscriptions
 */

namespace HostForge\Subscriptions;

use HostForge\Interfaces\HF_Subscription_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_SUMO_Adapter
 */
class HF_SUMO_Adapter implements HF_Subscription_Adapter {

	/**
	 * Check if SUMO Subscriptions is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'SUMO_SUBSCRIPTIONS_VERSION' ) || class_exists( 'SUMOSubs_Subscription' );
	}

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'SUMO Subscriptions';
	}

	/**
	 * Create a subscription.
	 *
	 * SUMO Subscriptions creates subscriptions on order completion.
	 * This is a programmatic fallback via direct post creation.
	 *
	 * @param array $params Subscription parameters.
	 * @return int|false Subscription ID or false on failure.
	 */
	public function create_subscription( array $params ): int|false {
		$user_id    = isset( $params['user_id'] ) ? absint( $params['user_id'] ) : 0;
		$order_id   = isset( $params['order_id'] ) ? absint( $params['order_id'] ) : 0;
		$product_id = isset( $params['product_id'] ) ? absint( $params['product_id'] ) : 0;

		if ( ! $user_id ) {
			return false;
		}

		$subscription_id = wp_insert_post(
			array(
				'post_type'   => 'sumosubscriptions',
				'post_status' => 'publish',
				'post_author' => $user_id,
				'post_title'  => sprintf(
					/* translators: %d: user ID */
					__( 'Subscription #%d', 'hostforge' ),
					$user_id
				),
			)
		);

		if ( is_wp_error( $subscription_id ) || ! $subscription_id ) {
			return false;
		}

		update_post_meta( $subscription_id, 'sumo_get_status', 'Active' );

		if ( $order_id ) {
			update_post_meta( $subscription_id, 'sumo_get_parent_order_id', $order_id );
		}

		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				update_post_meta( $subscription_id, 'sumo_product_name', $product->get_name() );
			}
		}

		return (int) $subscription_id;
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

		if ( function_exists( 'sumosubs_cancel_subscription' ) ) {
			return (bool) sumosubs_cancel_subscription(
				$subscription_id,
				array(
					'request_by' => 'system',
					'when'       => 'immediate',
				)
			);
		}

		update_post_meta( $subscription_id, 'sumo_get_status', 'Cancelled' );

		return true;
	}

	/**
	 * Suspend (pause) a subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function suspend_subscription( int $subscription_id ): bool {
		$subscription = $this->get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return false;
		}

		if ( function_exists( 'sumo_pause_subscription' ) ) {
			return (bool) sumo_pause_subscription( $subscription_id, '', 'admin' );
		}

		update_post_meta( $subscription_id, 'sumo_get_status', 'Pause' );

		return true;
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

		if ( function_exists( 'sumo_resume_subscription' ) ) {
			return (bool) sumo_resume_subscription( $subscription_id, 'admin' );
		}

		update_post_meta( $subscription_id, 'sumo_get_status', 'Active' );

		return true;
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

		$status = get_post_meta( $subscription_id, 'sumo_get_status', true );

		if ( empty( $status ) ) {
			return 'unknown';
		}

		return $this->normalize_status( $status );
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

		if ( function_exists( 'sumosubs_get_next_payment_date' ) ) {
			$timestamp = sumosubs_get_next_payment_date(
				$subscription_id,
				0,
				array( 'get_as_timestamp' => true )
			);

			if ( ! empty( $timestamp ) && is_numeric( $timestamp ) ) {
				return wp_date( 'Y-m-d H:i:s', (int) $timestamp );
			}
		}

		$next_payment = get_post_meta( $subscription_id, 'sumo_get_next_payment_date', true );

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
		if ( function_exists( 'sumosubs_get_subscriptions_by_user' ) ) {
			$subscriptions = sumosubs_get_subscriptions_by_user( $user_id );

			if ( is_array( $subscriptions ) ) {
				return array_map( 'absint', $subscriptions );
			}
		}

		$posts = get_posts(
			array(
				'post_type'      => 'sumosubscriptions',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'author'         => $user_id,
				'fields'         => 'ids',
			)
		);

		return array_map( 'absint', $posts );
	}

	/**
	 * Get SUMO status change hooks mapped to HostForge events.
	 *
	 * @return array<string, string> HostForge event => SUMO hook.
	 */
	public function get_status_hooks(): array {
		return array(
			'activated'   => 'sumosubscriptions_subscription_resumed',
			'suspended'   => 'sumosubscriptions_subscription_paused',
			'cancelled'   => 'sumosubscriptions_before_cancel_subscription',
			'expired'     => 'sumosubscriptions_subscription_expired',
			'reactivated' => 'sumosubscriptions_subscription_resumed',
			'renewed'     => 'sumosubscriptions_renewal_order_created',
		);
	}

	/**
	 * Get a subscription post object.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return \WP_Post|null
	 */
	private function get_subscription( int $subscription_id ): ?\WP_Post {
		$post = get_post( $subscription_id );

		if ( ! $post || 'sumosubscriptions' !== $post->post_type ) {
			return null;
		}

		return $post;
	}

	/**
	 * Normalize SUMO status to HostForge standard status.
	 *
	 * @param string $status SUMO status (capitalized).
	 * @return string Normalized status.
	 */
	private function normalize_status( string $status ): string {
		$map = array(
			'Active'                => 'active',
			'Trial'                 => 'active',
			'Pause'                 => 'on-hold',
			'Suspended'             => 'on-hold',
			'Cancelled'             => 'cancelled',
			'Pending_Cancellation'  => 'cancelled',
			'Expired'               => 'expired',
			'Pending'               => 'pending',
			'Overdue'               => 'pending',
			'Pending_Authorization' => 'pending',
			'Failed'                => 'pending',
		);

		return $map[ $status ] ?? 'unknown';
	}
}
