<?php
/**
 * YITH WooCommerce Subscription Adapter.
 *
 * Implements the subscription adapter interface for YITH WooCommerce Subscription plugin.
 *
 * @package HostForge\Subscriptions
 */

namespace HostForge\Subscriptions;

use HostForge\Interfaces\HF_Subscription_Adapter;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_YITH_Adapter
 */
class HF_YITH_Adapter implements HF_Subscription_Adapter {

	/**
	 * Check if YITH WooCommerce Subscription is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'YITH_YWSBS_VERSION' ) || class_exists( 'YITH_WC_Subscription' );
	}

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'YITH WooCommerce Subscription';
	}

	/**
	 * Create a subscription.
	 *
	 * YITH creates subscriptions on order processing.
	 * This is a programmatic fallback.
	 *
	 * @param array $params Subscription parameters.
	 * @return int|false Subscription ID or false on failure.
	 */
	public function create_subscription( array $params ): int|false {
		if ( ! class_exists( 'YWSBS_Subscription' ) ) {
			return false;
		}

		$subscription = new \YWSBS_Subscription();

		if ( ! empty( $params['order_id'] ) ) {
			$subscription->set( 'order_id', absint( $params['order_id'] ) );
		}

		if ( ! empty( $params['product_id'] ) ) {
			$subscription->set( 'product_id', absint( $params['product_id'] ) );
		}

		if ( ! empty( $params['user_id'] ) ) {
			$subscription->set( 'user_id', absint( $params['user_id'] ) );
		}

		$subscription->set( 'status', 'active' );

		$sub_id = $subscription->save();

		return $sub_id ? (int) $sub_id : false;
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

		$subscription->set( 'status', 'cancelled' );
		$subscription->save();

		return 'cancelled' === $subscription->get( 'status' );
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

		$subscription->set( 'status', 'paused' );
		$subscription->save();

		return 'paused' === $subscription->get( 'status' );
	}

	/**
	 * Reactivate a paused subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return bool
	 */
	public function reactivate_subscription( int $subscription_id ): bool {
		$subscription = $this->get_subscription( $subscription_id );

		if ( ! $subscription ) {
			return false;
		}

		$subscription->set( 'status', 'active' );
		$subscription->save();

		return 'active' === $subscription->get( 'status' );
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

		return $this->normalize_status( $subscription->get( 'status' ) );
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

		$payment_due = $subscription->get( 'payment_due_date' );

		if ( empty( $payment_due ) ) {
			return null;
		}

		return wp_date( 'Y-m-d H:i:s', (int) $payment_due );
	}

	/**
	 * Get all subscriptions for a user.
	 *
	 * @param int $user_id User ID.
	 * @return array<int> Subscription IDs.
	 */
	public function get_subscriptions_by_user( int $user_id ): array {
		if ( ! function_exists( 'YITH_WC_Subscription' ) ) {
			$posts = get_posts(
				array(
					'post_type'      => 'ywsbs_subscription',
					'posts_per_page' => -1,
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => 'user_id',
							'value' => $user_id,
							'type'  => 'NUMERIC',
						),
					),
					'fields'         => 'ids',
				)
			);

			return array_map( 'absint', $posts );
		}

		return array();
	}

	/**
	 * Get YITH status change hooks mapped to HostForge events.
	 *
	 * @return array<string, string> HostForge event => YITH hook.
	 */
	public function get_status_hooks(): array {
		return array(
			'activated'   => 'ywsbs_subscription_status_active',
			'suspended'   => 'ywsbs_subscription_status_paused',
			'cancelled'   => 'ywsbs_subscription_status_cancelled',
			'expired'     => 'ywsbs_subscription_status_expired',
			'reactivated' => 'ywsbs_subscription_status_resumed',
			'renewed'     => 'ywsbs_subscription_renewed',
		);
	}

	/**
	 * Get a YITH subscription object.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return \YWSBS_Subscription|null
	 */
	private function get_subscription( int $subscription_id ): ?\YWSBS_Subscription {
		if ( ! class_exists( 'YWSBS_Subscription' ) ) {
			return null;
		}

		$subscription = new \YWSBS_Subscription( $subscription_id );

		if ( ! $subscription->get( 'id' ) ) {
			return null;
		}

		return $subscription;
	}

	/**
	 * Normalize YITH status to HostForge standard status.
	 *
	 * @param string $status YITH status.
	 * @return string Normalized status.
	 */
	private function normalize_status( string $status ): string {
		$map = array(
			'active'    => 'active',
			'paused'    => 'on-hold',
			'cancelled' => 'cancelled',
			'expired'   => 'expired',
			'trial'     => 'active',
			'overdue'   => 'on-hold',
			'pending'   => 'pending',
		);

		return $map[ $status ] ?? 'unknown';
	}
}
