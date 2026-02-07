<?php
/**
 * Server Selector.
 *
 * Automatically selects the best server for provisioning based on
 * the product's server group and current server load (fewest accounts).
 *
 * @package HostForge\Modules\AutoProvisioning
 */

namespace HostForge\Modules\AutoProvisioning;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Server_Selector
 */
class HF_Server_Selector {

	/**
	 * Select the best server for a product.
	 *
	 * Finds an active server in the product's server group with the
	 * fewest current accounts and available capacity.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return int Server post ID or 0 if none available.
	 */
	public static function select( \WC_Product $product ): int {
		$server_group = '';

		if ( method_exists( $product, 'get_meta' ) ) {
			$server_group = $product->get_meta( '_hf_server_group' );
		}

		// Build query args.
		$args = array(
			'post_type'      => 'hf_server',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_hf_status',
					'value' => 'active',
				),
			),
		);

		// Filter by server group taxonomy if specified.
		if ( ! empty( $server_group ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'hf_server_group',
					'field'    => 'slug',
					'terms'    => $server_group,
				),
			);
		}

		/**
		 * Filter the query args used to find available servers.
		 *
		 * Allows modification of the WP_Query arguments for server
		 * selection, e.g. to add custom meta queries or change ordering.
		 *
		 * @since 1.0.0
		 *
		 * @param array       $args         WP_Query arguments.
		 * @param \WC_Product $product      Product instance.
		 * @param string      $server_group Server group slug.
		 */
		$args = apply_filters( 'hostforge_server_selector_query', $args, $product, $server_group );

		$servers = get_posts( $args );

		if ( empty( $servers ) ) {
			return 0;
		}

		// Score servers by available capacity (fewest accounts = best).
		$best_server = 0;
		$best_score  = PHP_INT_MAX;

		foreach ( $servers as $server_id ) {
			$max_accounts     = absint( get_post_meta( $server_id, '_hf_max_accounts', true ) );
			$current_accounts = absint( get_post_meta( $server_id, '_hf_current_accounts', true ) );

			// Check capacity: skip servers at capacity (0 = unlimited).
			$has_capacity = true;
			if ( $max_accounts > 0 && $current_accounts >= $max_accounts ) {
				$has_capacity = false;
			}

			/**
			 * Filter whether a server has capacity for a new account.
			 *
			 * Allows overriding the default capacity check to implement
			 * custom capacity logic (e.g. based on CPU, RAM, or custom limits).
			 *
			 * @since 1.0.0
			 *
			 * @param bool $has_capacity     Whether the server has capacity.
			 * @param int  $server_id        Server post ID.
			 * @param int  $max_accounts     Maximum accounts configured.
			 * @param int  $current_accounts Current number of accounts.
			 */
			$has_capacity = apply_filters(
				'hostforge_server_capacity_check',
				$has_capacity,
				$server_id,
				$max_accounts,
				$current_accounts
			);

			if ( ! $has_capacity ) {
				continue;
			}

			// Lower current_accounts = better score.
			if ( $current_accounts < $best_score ) {
				$best_score  = $current_accounts;
				$best_server = $server_id;
			}
		}

		/**
		 * Filter the selected server for provisioning.
		 *
		 * @param int         $server_id  Selected server post ID.
		 * @param int         $product_id Product ID.
		 * @param string      $group      Server group slug.
		 */
		return apply_filters( 'hostforge_select_server', $best_server, $product->get_id(), $server_group );
	}
}
