<?php // phpcs:ignoreFile
/**
 * Plugin Name: Advanced Subscriptions For WooCommerce
 * Plugin URI: https://plugins.joseconti.com
 * Description: Advanced Subscriptions for WooCommerce gives you full control over recurring payments, subscription plans, and renewals for your online store.
 * Version: 0.23.0
 * Author: Jose Conti
 * Author URI: https://plugins.joseconti.com
 * Text Domain: advanced-subscriptions-for-woocommerce
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 *
 * Requires at least: 5.1
 * Tested up to: 6.8
 * WC requires at least: 5.1
 * WC tested up to: 9.9
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Define plugin constants.
 *
 * @since 1.0.0
 */
define( 'ASWC_VERSION', '0.23.0' );
define( 'ASWC_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'ASWC_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'ASWC_INCLUDES_PATH', ASWC_DIR_PATH . 'includes/loader/' );
define( 'ASWC_INCLUDES_DIR_URL', ASWC_DIR_URL . 'includes/loader/' );
define( 'ASWC_PLUGIN_FILE', __FILE__ );
define( 'ASWC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// License constants.
define( 'ASWC_LICENSE_ITEM_NAME', 'advanced-subscriptions-for-woocommerce' );
define( 'ASWC_LICENSE_API', 'https://plugins.joseconti.com' );
define( 'ASWC_LICENSE_PREFIX', 'aswc_lic' );

// Load the scheduler API.
require_once __DIR__ . '/scheduler-api/scheduler.php';

// Load and initialize the license manager.
require_once __DIR__ . '/includes/loader/includes/class-aswc-license.php';

/**
 * Initialize the license manager.
 *
 * @since 1.0.0
 * @return void
 */
function aswc_init_license() {
	new ASWC_License();
}
add_action( 'plugins_loaded', 'aswc_init_license', 5 );

/**
 * Retry attempts helpers and hooks.
 */
if ( ! defined( 'ASWC_RETRY_MAX_OPTION' ) ) {
	define( 'ASWC_RETRY_MAX_OPTION', 'aswc_payment_retry_max_attempts' );
}
if ( ! defined( 'ASWC_RETRY_ATTEMPTS_META' ) ) {
	define( 'ASWC_RETRY_ATTEMPTS_META', 'aswc_retry_attempts' );
}

/**
 * Get configured maximum retry attempts.
 *
 * @since 1.0.0
 *
 * @return int Maximum retry attempts allowed.
 */
function aswc_get_max_retry_attempts() {
	$max_attempts = (int) get_option( ASWC_RETRY_MAX_OPTION, 3 );

	return max( 0, $max_attempts );
}

/**
 * Get current retry attempts for a subscription.
 *
 * @since 1.0.0
 *
 * @param int $subscription_id Subscription ID.
 *
 * @return int Number of recorded retry attempts.
 */
function aswc_get_retry_attempts( $subscription_id ) {
	$attempts = (int) aswc_get_meta_data( $subscription_id, ASWC_RETRY_ATTEMPTS_META, true );

	return max( 0, $attempts );
}

/**
 * Reset retry attempts for a subscription.
 *
 * @since 1.0.0
 *
 * @param int $subscription_id Subscription ID.
 *
 * @return void
 */
function aswc_reset_retry_attempts( $subscription_id ) {
	$subscription_id = absint( $subscription_id );

	if ( 0 === $subscription_id ) {
		return;
	}

	if ( class_exists( 'ASWC_Log' ) ) {
		ASWC_Log::log( sprintf( '[retry] Reset attempts for subscription %d', $subscription_id ) );
	}

	aswc_update_meta_data( $subscription_id, '_' . ASWC_RETRY_ATTEMPTS_META, 0 );
}


/**
 * Maybe reset retries when a subscription is manually re-activated in admin.
 *
 * We listen to post status transitions and, when the custom order type
 * 'aswc_subscriptions' goes to wc-active, we reset the attempts counter.
 *
 * @since 1.0.0
 *
 * @param string  $new_status New post status (prefixed with 'wc-').
 * @param string  $old_status Old post status (prefixed with 'wc-').
 * @param WP_Post $post       Post object.
 *
 * @return void
 */
function aswc_maybe_reset_retries_on_status( $new_status, $old_status, $post ) {
	if ( ! ( $post instanceof WP_Post ) || 'aswc_subscriptions' !== $post->post_type ) {
		return;
	}

	// Only react when moving into active.
	if ( 'wc-active' === $new_status && 'wc-active' !== $old_status ) {
		aswc_reset_retry_attempts( $post->ID );
	}
}
add_action( 'transition_post_status', 'aswc_maybe_reset_retries_on_status', 10, 3 );

add_action( 'plugins_loaded', 'aswc_init_failed_action_manager' );
add_action( 'plugins_loaded', 'aswc_init_scheduler_notifications' );

/**
 * Initialise the failed scheduled action manager.
 *
 * Ensures failed subscription actions are logged and retried using the
 * scheduler API's dedicated manager.
 *
 * @since 1.0.0
 *
 * @return void
 */
function aswc_init_failed_action_manager() {
	ASWC_Scheduler_API::init_failed_action_manager( wc_get_logger() );
}

/**
 * Initialize scheduler notifications.
 *
 * Ensures customer notification hooks are registered through the scheduler API.
 *
 * @since 1.0.0
 *
 * @return void
 */
function aswc_init_scheduler_notifications() {
	ASWC_Scheduler_API::notifications();
}

/**
 * Run plugin activation tasks.
 *
 * This action is documented in includes/class-aswc-activator.php.
 *
 * @since 1.0.0
 *
 * @param bool $network_wide Whether the plugin is being network activated.
 *
 * @return void
 */
function aswc_activate_subscriptions_for_woocommerce( $network_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aswc-activator.php';

	ASWC_Activator::subscriptions_for_woocommerce_activate( (bool) $network_wide );

	$active_plugins = get_option( 'aswc_all_plugins_active', array() );
	if ( ! is_array( $active_plugins ) ) {
		$active_plugins = array();
	}

	$active_plugins['subscriptions-for-woocommerce'] = array(
		'plugin_name' => __( 'Subscriptions for WooCommerce', 'advanced-subscriptions-for-woocommerce' ),
		'active'      => '1',
	);

	update_option( 'aswc_all_plugins_active', $active_plugins );
}

/**
 * Run plugin deactivation tasks.
 *
 * This action is documented in includes/class-aswc-deactivator.php.
 *
 * @since 1.0.0
 *
 * @param bool $network_wide Whether the plugin is being network deactivated.
 *
 * @return void
 */
function aswc_deactivate_subscriptions_for_woocommerce( $network_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aswc-deactivator.php';

	ASWC_Deactivator::subscriptions_for_woocommerce_deactivate( (bool) $network_wide );

	$active_plugins = get_option( 'aswc_all_plugins_active', array() );
	if ( is_array( $active_plugins ) && isset( $active_plugins['subscriptions-for-woocommerce'] ) ) {
		$active_plugins['subscriptions-for-woocommerce']['active'] = '0';
	}

	update_option( 'aswc_all_plugins_active', $active_plugins );
}

add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}

		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

register_activation_hook( __FILE__, 'aswc_activate_subscriptions_for_woocommerce' );
register_deactivation_hook( __FILE__, 'aswc_deactivate_subscriptions_for_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-aswc.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 *
 * @return void
 */
function aswc_run_subscriptions_for_woocommerce() {
	$plugin = new ASWC();
	$plugin->aswc_run();

	$GLOBALS['aswc_obj']     = $plugin;
	$GLOBALS['aswc_notices'] = false;
}
aswc_run_subscriptions_for_woocommerce();

// Add settings link on plugin page.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'aswc_subscriptions_for_woocommerce_settings_link' );

/**
 * Filter plugin action links for the settings link.
 *
 * @since 1.0.0
 *
 * @param array $links Existing plugin action links.
 *
 * @return array Filtered plugin action links.
 */
function aswc_subscriptions_for_woocommerce_settings_link( $links ) {
	return $links;
}

register_activation_hook( __FILE__, 'aswc_flush_rewrite_rules' );
register_deactivation_hook( __FILE__, 'aswc_flush_rewrite_rules' );

/**
 * Register rewrite endpoints for subscription account pages.
 *
 * @since 1.0.0
 *
 * @return void
 */
function aswc_flush_rewrite_rules() {
	add_rewrite_endpoint( 'aswc-subscriptions', EP_PAGES );
	add_rewrite_endpoint( 'show-subscription', EP_PAGES );
	add_rewrite_endpoint( 'aswc-add-payment-method', EP_PAGES );
	flush_rewrite_rules();
}

add_action( 'init', 'aswc_register_custom_order_types', 5 );

/**
 * Register the subscription custom order type.
 *
 * @since 1.0.0
 *
 * @return void
 */
function aswc_register_custom_order_types() {
	if ( ! function_exists( 'wc_register_order_type' ) ) {
		return;
	}

	wc_register_order_type(
		'aswc_subscriptions',
		apply_filters(
			'aswc_register_custom_order_types',
			array(
				'labels'                          => array(
					'name'               => __( 'AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'singular_name'      => __( 'AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'add_new'            => __( 'Add AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'add_new_item'       => __( 'Add New AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'edit'               => __( 'Edit', 'advanced-subscriptions-for-woocommerce' ),
					'edit_item'          => __( 'Edit AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'new_item'           => __( 'New AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'view'               => __( 'View AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'view_item'          => __( 'View AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'search_items'       => __( 'Search AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'not_found'          => __( 'Not Found', 'advanced-subscriptions-for-woocommerce' ),
					'not_found_in_trash' => __( 'No AWC Subscriptions found in the trash', 'advanced-subscriptions-for-woocommerce' ),
					'parent'             => __( 'Parent AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
					'menu_name'          => __( 'AWC Subscriptions', 'advanced-subscriptions-for-woocommerce' ),
				),
				'description'                    => __( 'These AWC Subscriptions are stored.', 'advanced-subscriptions-for-woocommerce' ),
				'public'                         => false,
				'show_ui'                        => true,
				'capability_type'                => 'shop_order',
				'map_meta_cap'                   => true,
				'publicly_queryable'             => false,
				'exclude_from_search'            => true,
				'show_in_menu'                   => 'woocommerce',
				'menu_position'                  => 56,
				'hierarchical'                   => false,
				'show_in_nav_menus'              => true,
				'rewrite'                        => false,
				'query_var'                      => false,
				'supports'                       => array( 'title', 'comments', 'custom-fields', 'custom-statuses' ),
				'has_archive'                    => false,
				'exclude_from_orders_screen'     => true,
				'add_order_meta_boxes'           => true,
				'exclude_from_order_count'       => true,
				'exclude_from_order_views'       => true,
				'exclude_from_order_webhooks'    => true,
				'exclude_from_order_reports'     => true,
				'exclude_from_order_sales_reports' => true,
				'class_name'                     => 'ASWC_Subscription',
			)
		)
	);
}


	/**
	 * Load custom payment gateway.
	 *
	 * @param array $methods array containing the payment methods in WooCommerce.
	 * @since 1.0.0
	 * @return array
	 */


		/**
		 * Normalize a meta key ensuring a single leading underscore.
		 *
		 * @since 1.0.0
		 *
		 * @param string $key Meta key to normalize.
		 * @return string Normalized meta key.
		 */
function aswc_normalize_meta_key( $key ) {

								return '_' . ltrim( (string) $key, '_' );
				}

		/**
		 * Get subscription status slugs for queries.
		 *
		 * @since 1.0.0
		 *
		 * @return array Subscription status slugs.
		 */
function aswc_get_subscription_statuses_for_query() {
								$statuses = array();

								if ( function_exists( 'aswc_get_subscription_statuses' ) ) {
												$statuses = aswc_get_subscription_statuses();
								}

								if ( empty( $statuses ) ) {
												$statuses = array( 'active', 'on-hold', 'cancelled', 'expired', 'pending-cancel', 'pending', 'paused' );
								}

								$statuses = apply_filters( 'aswc_status_array', $statuses );
								$statuses = array_values(
												array_unique(
																array_filter(
																				array_map( 'sanitize_key', (array) $statuses )
																)
												)
								);

								return $statuses;
				}

		/**
		 * Get prefixed subscription post statuses for CPT queries.
		 *
		 * @since 1.0.0
		 *
		 * @return array Subscription post statuses with the `wc-` prefix.
		 */
function aswc_get_subscription_post_statuses_for_query() {
								$statuses      = aswc_get_subscription_statuses_for_query();
								$post_statuses = array();

								foreach ( $statuses as $status ) {
												if ( 'trash' === $status ) {
																$post_statuses[] = 'trash';
																continue;
												}
												$post_statuses[] = 'wc-' . $status;
								}

								return array_values( array_unique( $post_statuses ) );
				}

		/**
		 *
		 * Get the data from the order table if hpos enabled otherwise default working.
		 *
		 * @param int    $id .
		 * @param string $key .
		 * @param int    $v .
		 */
function aswc_get_meta_data( $id, $key, $v ) {

								$meta_key       = aswc_normalize_meta_key( $key );
								$normalized_key = ltrim( (string) $key, '_' );

								if ( class_exists( 'ASWC_Log' ) ) {
												ASWC_Log::log(
																sprintf(
																				'[aswc_get_meta_data] ID:%1$d Key:%2$s',
																				absint( $id ),
																				$meta_key
																)
												);
								}

								if ( 'aswc_subscription_status' === $normalized_key && function_exists( 'aswc_get_subscription' ) ) {
									$subscription = aswc_get_subscription( $id );
									if ( false !== $subscription && method_exists( $subscription, 'get_status' ) ) {
										return $subscription->get_status( 'view' );
									}
								}

								if ( 'shop_order' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {

												// HPOS usage is enabled.
												$order = wc_get_order( $id );
												$value = $order->get_meta( $meta_key );

								} elseif ( 'aswc_subscriptions' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
												// HPOS usage is enabled.
												$order = new ASWC_Subscription( $id );
												$value = $order->get_meta( $meta_key );
								} else {
												// Traditional CPT-based orders are in use.
												$value = get_post_meta( $id, $meta_key, $v );
								}

								if ( class_exists( 'ASWC_Log' ) ) {
												ASWC_Log::log(
																sprintf(
																				'[aswc_get_meta_data] Retrieved:%s',
																				wp_json_encode( $value )
																)
												);
								}

								return $value;
				}

	/**
	 * Handle subscription status change side effects.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $subscription_id Subscription ID.
	 * @param string $new_status New status slug (without wc- prefix).
	 * @param string $old_status Old status slug (without wc- prefix).
	 * @return void
	 */
function aswc_handle_subscription_status_change( $subscription_id, $new_status, $old_status = '' ) {
								$subscription_id = absint( $subscription_id );
								if ( 0 === $subscription_id ) {
												return;
								}

								if ( function_exists( 'aswc_sanitize_subscription_status_key' ) ) {
												$new_status = aswc_sanitize_subscription_status_key( $new_status );
												$old_status = aswc_sanitize_subscription_status_key( $old_status );
								} else {
												$new_status = sanitize_text_field( $new_status );
												$old_status = sanitize_text_field( $old_status );
								}

								if ( '' === $new_status ) {
												return;
								}

								if ( '' === $old_status && function_exists( 'aswc_get_subscription' ) ) {
												$subscription = aswc_get_subscription( $subscription_id );
												if ( false !== $subscription && method_exists( $subscription, 'get_status' ) ) {
																$old_status = $subscription->get_status( 'edit' );
												}
								}

								if ( '' !== $old_status && 0 === strcmp( $old_status, $new_status ) ) {
												return;
								}

								static $handled = array();
								$handle_key     = $subscription_id . '|' . $old_status . '|' . $new_status;
								if ( isset( $handled[ $handle_key ] ) ) {
												return;
								}
								$handled[ $handle_key ] = true;

								if ( class_exists( 'ASWC_Scheduler_API' ) && function_exists( 'aswc_get_subscription' ) ) {
												$subscription = aswc_get_subscription( $subscription_id );
												if ( false !== $subscription ) {
																if ( 'active' === $new_status ) {
																				aswc_reset_retry_attempts( $subscription_id );
																				aswc_unschedule_retry_actions( $subscription_id );
																}

																ASWC_Scheduler_API::update_status( $subscription, $new_status, $old_status );
												}
								}

								if ( 'active' === $new_status ) {
												aswc_unschedule_retry_actions( $subscription_id );
								}
				}
	/**
	 *
	 * Update the data into the order table if hpos enabled otherwise default working.
	 *
	 * @param int               $id .
	 * @param string            $key .
	 * @param init|array|object $value .
	 */
function aswc_update_meta_data( $id, $key, $value ) {

								$meta_key       = aswc_normalize_meta_key( $key );
								$normalized_key = ltrim( (string) $key, '_' );

								if ( class_exists( 'ASWC_Log' ) ) {
												ASWC_Log::log(
																sprintf(
																				'[aswc_update_meta_data] ID:%1$d Key:%2$s Value:%3$s',
																				absint( $id ),
																				$meta_key,
																				wp_json_encode( $value )
																)
												);
								}

								if ( 'aswc_subscription_status' === $normalized_key ) {
												if ( function_exists( 'aswc_sanitize_subscription_status_key' ) ) {
																$status_value = aswc_sanitize_subscription_status_key( $value );
												} else {
																$status_value = sanitize_text_field( $value );
												}

												if ( '' === $status_value || ! function_exists( 'aswc_get_subscription' ) ) {
																return;
												}

												$subscription = aswc_get_subscription( $id );
												if ( false === $subscription || ! method_exists( $subscription, 'set_status' ) ) {
																return;
												}

												$old_status = $subscription->get_status( 'edit' );
												if ( 0 === strcmp( $old_status, $status_value ) ) {
																return;
												}

												$subscription->set_status( $status_value );
												$subscription->save();

												aswc_handle_subscription_status_change( $id, $status_value, $old_status );

												return;
								}

				if ( 'shop_order' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {

							   // HPOS usage is enabled.
							   $order = wc_get_order( $id );

							   $order->update_meta_data( $meta_key, $value );
							   $order->save_meta_data();

							   if ( ! doing_action( 'save_post' ) && ! doing_action( 'save_post_shop_order' ) ) {
									$order->save();
							   }

				} elseif ( 'aswc_subscriptions' === OrderUtil::get_order_type( $id ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
							   // HPOS usage is enabled.
							   $order = new ASWC_Subscription( $id );

							   $order->update_meta_data( $meta_key, $value );
							   $order->save_meta_data();

							   if ( ! doing_action( 'save_post' ) && ! doing_action( 'save_post_aswc_subscriptions' ) ) {
									$order->save();
							   }
				} else {
						// Traditional CPT-based orders are in use.
						update_post_meta( $id, $meta_key, $value );
				}

								$stored = aswc_get_meta_data( $id, $key, true );
								if ( class_exists( 'ASWC_Log' ) ) {
												ASWC_Log::log(
																sprintf(
																				'[aswc_update_meta_data] Stored:%s',
																				wp_json_encode( $stored )
																)
												);
								}
				}

	/**
	 * Unschedule any pending retry actions for a subscription.
	 *
	 * Tries both the new (subscription-based) and legacy (order-based) args to be safe.
	 *
	 * @since 1.0.0
	 *
	 * @param int $subscription_id
	 * @return void
	 */
	function aswc_unschedule_retry_actions( $subscription_id ) {
		if ( empty( $subscription_id ) ) {
			return;
		}

		if ( class_exists( 'ASWC_Log' ) ) {
			ASWC_Log::log( sprintf( '[retry] unschedule retry actions for subscription %d', absint( $subscription_id ) ) );
		}

		// New subscription-based arg.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions(
				'advanced_scheduled_subscription_payment_retry',
				array( 'subscription_id' => (int) $subscription_id ),
				'aswc_subscription_scheduled_event'
			);
		}

		// Legacy order-based arg (keep for backwards compatibility while migrating).
		$last_order_id = (int) aswc_get_meta_data( $subscription_id, 'aswc_last_renewal_order_id', true );
		if ( $last_order_id > 0 && function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions(
				'advanced_scheduled_subscription_payment_retry',
				array( 'order_id' => $last_order_id ),
				'aswc_subscription_scheduled_event'
			);
		}
	}

	// The "wc-orders--aswc_subscriptions" menu entry is no longer added twice in
	   // recent WooCommerce versions, so the previous CSS that hid the entry has been
	   // removed to ensure the AWC Subscriptions submenu displays correctly.


	/**
	 * Function to Remove subscription menu.
	 *
	 * @return void
	 */
	function aswc_remove_custom_woocommerce_menu() {
		global $submenu, $pagenow;

		// Allow direct access.
		if ( isset( $_GET['page'] ) && 'wc-orders--aswc_subscriptions' === $_GET['page'] && isset( $_GET['action'] ) && 'new' === $_GET['action'] ) {
			return;
		}

		// Remove the submenu from WooCommerce.
		// remove_submenu_page( 'woocommerce', 'wc-orders--aswc_subscriptions' );
	}
		add_action( 'admin_menu', 'aswc_remove_custom_woocommerce_menu', 999 );

		/**
		 * Move the Subscriptions menu item after WooCommerce Orders.
		 *
		 * @return void
		 */
	   function aswc_reorder_menu() {
			   global $submenu;

			   if ( empty( $submenu['woocommerce'] ) ) {
					   return;
			   }

			   $subscription_item = null;
			   foreach ( $submenu['woocommerce'] as $index => $item ) {
					   if ( isset( $item[2] ) && in_array( $item[2], array( 'edit.php?post_type=aswc_subscriptions', 'wc-orders--aswc_subscriptions' ), true ) ) {
							   $subscription_item = $item;
							   unset( $submenu['woocommerce'][ $index ] );
				break;
					   }
			   }

		if ( ! $subscription_item ) {
					return;
		}

				$submenu['woocommerce'] = array_values( $submenu['woocommerce'] );

				$orders_index = null;
		foreach ( $submenu['woocommerce'] as $index => $item ) {
			if ( isset( $item[2] ) && ( 'wc-orders' === $item[2] || 'edit.php?post_type=shop_order' === $item[2] ) ) {
					$orders_index = $index;
					break;
			}
		}

		if ( null === $orders_index ) {
				$submenu['woocommerce'][] = $subscription_item;
		} else {
				array_splice( $submenu['woocommerce'], $orders_index + 1, 0, array( $subscription_item ) );
		}
	}
		add_action( 'admin_menu', 'aswc_reorder_menu', 999 );
	// HPOS Compatibility for Custom Order type i.e. ASWC_Subscription.
	add_action(
		'woocommerce_init',
		function () {
						require_once ASWC_DIR_PATH . 'includes/class-aswc-subscription.php';
				}
		);

	/**
	 * Create a new subscription
	 *
	 * @param array() $args .
	 */
	function aswc_create_subscription( $args = array() ) {
			$now   = current_time( 'timestamp' );
			$order = ( isset( $args['order_id'] ) ) ? wc_get_order( $args['order_id'] ) : null;

		$default_args = array(
			'status'             => 'pending',
			'order_id'           => 0,
			'customer_note'      => null,
			'customer_id'        => null,
			'date_created'       => $now,
			'created_via'        => '',
			'currency'           => get_woocommerce_currency(),
			'prices_include_tax' => get_option( 'woocommerce_prices_include_tax' ),
		);

		if ( $order instanceof \WC_Order ) {
			$default_args['customer_id']              = $order->get_user_id();
			$default_args['created_via']              = $order->get_created_via( 'edit' );
			$default_args['currency']                 = $order->get_currency( 'edit' );
			$default_args['prices_include_tax']       = $order->get_prices_include_tax( 'edit' ) ? 'yes' : 'no';
						$default_args['date_created'] = $order->get_date_created( 'edit' )->getTimestamp();
		}

		$args = wp_parse_args( $args, $default_args );

		$subscription = new \ASWC_Subscription();

		if ( $args['status'] ) {
			$subscription->set_status( $args['status'] );
		}

		$subscription->set_customer_id( $args['customer_id'] );
		$subscription->set_date_created( $args['date_created'] );
		$subscription->set_created_via( $args['created_via'] );
		$subscription->set_currency( $args['currency'] );
		$subscription->set_prices_include_tax( 'no' !== $args['prices_include_tax'] );

		$subscription->save();

		return $subscription;
	}
	// code to register subscription product type.
	add_action( 'init', 'register_subscription_box_product_type' );
	/**
	 * Function to Regsiter Subscription box type.
	 *
	 * @return string
	 */
	function register_subscription_box_product_type() {
		if ( 'yes' === get_option( 'aswc_enable_subscription_box_features' ) && class_exists( 'WC_Product' ) ) {
			/**
			 * Extend Product class.
			 */
			class WC_Product_Subscription_Box extends WC_Product {
				/**
				 * Construct.
				 *
				 * @param object $product as product.
				 */
				public function __construct( $product ) {
					parent::__construct( $product );
				}

				/**
				 * Get type function
				 */
				public function get_type() {
					return 'subscription_box';
				}
			}
		}
	}

		/**
		 * Add custom columns on the subscriptions list screen.
		 */
	function aswc_subscriptions_custom_columns( $columns ) {
				$columns = array(
					'cb'                        => '<input type="checkbox" />',
										'status'                    => __( 'Status', 'advanced-subscriptions-for-woocommerce' ),
										'subscription'              => __( 'Subscription', 'advanced-subscriptions-for-woocommerce' ),
										'items'                     => __( 'Items', 'advanced-subscriptions-for-woocommerce' ),
										'total'                     => __( 'Total', 'advanced-subscriptions-for-woocommerce' ),
										'start_date'                => __( 'Start Date', 'advanced-subscriptions-for-woocommerce' ),
										'trial_end'                 => __( 'Trial End', 'advanced-subscriptions-for-woocommerce' ),
										'next_payment_date'         => __( 'Next Payment', 'advanced-subscriptions-for-woocommerce' ),
										'last_order_date'           => __( 'Last Order Date', 'advanced-subscriptions-for-woocommerce' ),
										'subscriptions_expiry_date' => __( 'End Date', 'advanced-subscriptions-for-woocommerce' ),
				);

				return $columns;
	}
	add_filter( 'manage_edit-aswc_subscriptions_columns', 'aswc_subscriptions_custom_columns' );
	add_filter( 'manage_woocommerce_page_wc-orders--aswc_subscriptions_columns', 'aswc_subscriptions_custom_columns' );

		/**
		 * Render custom column content for subscriptions list screen.
		 *
		 * @param string $column   Column ID.
		 * @param int    $post_id  Subscription post ID.
		 */
	function aswc_subscriptions_custom_column_content( $column, $post_id ) {

			// Convert order object to ID when using HPOS list tables.
		if ( is_object( $post_id ) && method_exists( $post_id, 'get_id' ) ) {
			$post_id = $post_id->get_id();
		}
		switch ( $column ) {
						case 'subscription':
																		$customer_id  = aswc_get_meta_data( $post_id, 'aswc_customer_id', true );
					$user         = get_user_by( 'id', $customer_id );
					$display_name = isset( $user->display_name ) ? $user->display_name : '';
					$text         = '#' . $post_id;
				if ( $display_name ) {
										$text .= ' ' . __( 'by', 'advanced-subscriptions-for-woocommerce' ) . ' ' . $display_name;
				}
							   if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
									   $edit_link = admin_url( 'admin.php?page=wc-orders--aswc_subscriptions&action=edit&id=' . $post_id );
							   } else {
									   $edit_link = get_edit_post_link( $post_id );
							   }
							   $preview = '<a href="#" class="order-preview" data-order-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'Preview', 'advanced-subscriptions-for-woocommerce' ) . '">' . esc_html__( 'Preview', 'advanced-subscriptions-for-woocommerce' ) . '</a>';
							   $view    = '<a href="' . esc_url( $edit_link ) . '" class="order-view"><strong>' . esc_html( $text ) . '</strong></a>';
							   echo wp_kses_post( $preview . $view );
							   break;

						case 'status':
																						$status = aswc_get_meta_data( $post_id, 'aswc_subscription_status', true );
						$label  = ucfirst( $status );
						echo '<mark class="order-status status-' . esc_attr( $status ) . ' tips"><span>' . esc_html( $label ) . '</span></mark>';
				break;

						case 'items':
														$product_name = aswc_get_meta_data( $post_id, 'product_name', true );
				if ( is_array( $product_name ) ) {
					$product_name = implode( ', ', $product_name );
				}
				echo esc_html( $product_name ? $product_name : '-' );
				break;

			case 'total':
				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
							$subscription = new ASWC_Subscription( $post_id );
				} else {
							$subscription = wc_get_order( $post_id );
				}
						$amount        = '';
						$currency_args = array();
				if ( $subscription ) {
							$amount        = $subscription->get_total();
							$currency_args = array(
								'currency' => $subscription->get_currency(),
							);
				}
						$amount = aswc_recerring_total_price_list_table_callback( wc_price( $amount, $currency_args ), $post_id );
						$amount = apply_filters( 'aswc_display_recurring_price', $amount, $post_id );
						echo wp_kses_post( $amount );
				break;

						case 'start_date':
														$start_date = aswc_get_meta_data( $post_id, 'aswc_schedule_start', true );
				$date       = aswc_get_the_wordpress_date_format( $start_date );
				echo esc_html( '---' === $date ? '-' : $date );
				break;

						case 'trial_end':
														$trial_end = aswc_get_meta_data( $post_id, 'aswc_susbcription_trial_end', true );
				$date      = aswc_get_the_wordpress_date_format( $trial_end );
				echo esc_html( '---' === $date ? '-' : $date );
				break;

						case 'next_payment_date':
																						$next_payment_date = aswc_get_meta_data( $post_id, 'aswc_next_payment_date', true );
																						$expiry_date       = aswc_get_meta_data( $post_id, 'aswc_susbcription_end', true );
																						$status            = aswc_get_meta_data( $post_id, 'aswc_subscription_status', true );
								if ( $next_payment_date === $expiry_date || 'on-hold' === $status || 'cancelled' === $status || 'paused' === $status ) {
														$next_payment_date = '';
								}
				$date = aswc_get_the_wordpress_date_format( $next_payment_date );
				echo esc_html( '---' === $date ? '-' : $date );
				break;

			case 'last_order_date':
				$last_order = '';
				if ( true === function_exists( 'aswc_get_last_renewal_order_date' ) ) {
					$last_order = aswc_get_last_renewal_order_date( $post_id );
				}
				$date = aswc_get_the_wordpress_date_format( $last_order );
				echo esc_html( '---' === $date ? '-' : $date );
				break;

						case 'subscriptions_expiry_date':
																						$expiry_date = aswc_get_meta_data( $post_id, 'aswc_susbcription_end', true );
																						$status      = aswc_get_meta_data( $post_id, 'aswc_subscription_status', true );
				if ( 'cancelled' === $status ) {
							$expiry_date = '';
				}
				$date = aswc_get_the_wordpress_date_format( $expiry_date );
				echo esc_html( '---' === $date ? '-' : $date );
				break;
		}
	}
add_action( 'manage_aswc_subscriptions_posts_custom_column', 'aswc_subscriptions_custom_column_content', 10, 2 );
add_action( 'manage_woocommerce_page_wc-orders--aswc_subscriptions_custom_column', 'aswc_subscriptions_custom_column_content', 10, 2 );

/**
 * Append a status selector to the subscription preview modal.
 *
 * @param array    $data  Preview data.
 * @param WC_Order $order Order object.
 *
 * @return array
 */
function aswc_subscription_preview_status_dropdown( $data, $order ) {
	   if ( 'aswc_subscriptions' !== $order->get_type() ) {
			   return $data;
	   }

		   $current_status = aswc_get_meta_data( $order->get_id(), 'aswc_subscription_status', true );
	   $statuses       = array(
			   'active'    => __( 'Active', 'advanced-subscriptions-for-woocommerce' ),
			   'on-hold'   => __( 'On-hold', 'advanced-subscriptions-for-woocommerce' ),
			   'cancelled' => __( 'Cancelled', 'advanced-subscriptions-for-woocommerce' ),
			   'paused'    => __( 'Paused', 'advanced-subscriptions-for-woocommerce' ),
	   );

	   $buttons = '';
	   foreach ( $statuses as $status => $label ) {
			   $disabled = ( $current_status === $status ) ? ' disabled="disabled"' : '';
			   $classes  = 'button aswc-preview-status-btn' . ( $current_status === $status ? ' disabled' : '' );
			   $buttons .= '<button type="button" class="' . esc_attr( $classes ) . '" data-subscription-id="' . esc_attr( $order->get_id() ) . '" data-status="' . esc_attr( $status ) . '"' . $disabled . '>' . esc_html( $label ) . '</button>';
	   }

	   $data['actions_html'] .= '<div class="aswc-preview-status-container">' . $buttons . '</div>';

	   return $data;
}
add_filter( 'woocommerce_admin_order_preview_get_order_details', 'aswc_subscription_preview_status_dropdown', 10, 2 );

/**
 * Display product name in subscription preview modal.
 *
 * @param string      $content  Current cell content.
 * @param WC_Order_Item $item   Order item object.
 * @param int         $item_id Item ID.
 * @param WC_Order    $order   Order object.
 *
 * @return string
 */
function aswc_subscription_preview_product_column( $content, $item, $item_id, $order ) {
	   if ( 'aswc_subscriptions' !== $order->get_type() ) {
			   return $content;
	   }

	   if ( ! empty( $content ) ) {
			   return $content;
	   }

	   $name = $item->get_name();

	   if ( '' === $name ) {
			   $product = wc_get_product( $item->get_product_id() );
			   if ( $product ) {
					   $name = $product->get_name();
			   }
	   }

	   if ( '' === $name ) {
			   $name = __( 'N/A', 'advanced-subscriptions-for-woocommerce' );
	   }

	   return esc_html( $name );
}
add_filter( 'woocommerce_admin_order_preview_line_item_column_product', 'aswc_subscription_preview_product_column', 10, 4 );

/**
 * Ensure subscription products appear in the preview modal when no line items exist.
 *
 * @param array    $items  Order line items.
 * @param WC_Order $order  Order object.
 *
 * @return array
 */
function aswc_subscription_preview_line_items( $items, $order ) {
	   if ( 'aswc_subscriptions' !== $order->get_type() ) {
			   return $items;
	   }

	   if ( ! empty( $items ) ) {
			   return $items;
	   }

		$product_names = aswc_get_meta_data( $order->get_id(), 'product_name', true );
		$product_ids   = aswc_get_meta_data( $order->get_id(), 'product_id', true );
		$product_qtys  = aswc_get_meta_data( $order->get_id(), 'product_qty', true );

	   if ( empty( $product_names ) ) {
			   return $items;
	   }

	$product_names = is_array( $product_names ) ? $product_names : array( $product_names );
	$product_ids   = is_array( $product_ids ) ? $product_ids : array( $product_ids );
	$product_qtys  = is_array( $product_qtys ) ? $product_qtys : array( $product_qtys );

	   foreach ( $product_names as $index => $name ) {
			   if ( '' === $name ) {
					   continue;
			   }

		$item = new WC_Order_Item_Product();
		$item->set_name( $name );

		$product_id = isset( $product_ids[ $index ] ) ? (int) $product_ids[ $index ] : 0;
		if ( 0 !== $product_id ) {
			$item->set_product_id( $product_id );
		}

		$qty = isset( $product_qtys[ $index ] ) ? (int) $product_qtys[ $index ] : 1;
		$item->set_quantity( $qty );

		$items[] = $item;
	   }

	   return $items;
}
add_filter( 'woocommerce_admin_order_preview_line_items', 'aswc_subscription_preview_line_items', 10, 2 );

/**
 * Replace "Order" label with "Subscription" on subscription preview modal.
 *
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $domain     Text domain.
 *
 * @return string
 */
function aswc_subscription_preview_modal_title( $translated, $text, $domain ) {
	   if ( 'woocommerce' !== $domain || 'Order #%s' !== $text ) {
			   return $translated;
	   }

	   $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	   if ( $screen && 'woocommerce_page_wc-orders--aswc_subscriptions' === $screen->id ) {
			   $translated = __( 'Subscription #%s', 'advanced-subscriptions-for-woocommerce' );
	   }

	   return $translated;
}
add_filter( 'gettext', 'aswc_subscription_preview_modal_title', 10, 3 );

/**
 * AJAX handler to change subscription status from preview.
 */
function aswc_change_subscription_status() {
	   check_ajax_referer( 'aswc_admin_nonce', 'nonce' );

	   if ( ! current_user_can( 'manage_woocommerce' ) ) {
			   wp_send_json_error( array( 'message' => __( 'Permission denied.', 'advanced-subscriptions-for-woocommerce' ) ) );
	   }

	   $subscription_id = isset( $_POST['subscription_id'] ) ? absint( $_POST['subscription_id'] ) : 0;
	   $status          = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

	   if ( 0 === $subscription_id || empty( $status ) ) {
			   wp_send_json_error( array( 'message' => __( 'Invalid data.', 'advanced-subscriptions-for-woocommerce' ) ) );
	   }

	   $valid_statuses = array( 'pending', 'active', 'on-hold', 'cancelled', 'paused' );
	   if ( ! in_array( $status, $valid_statuses, true ) ) {
			   wp_send_json_error( array( 'message' => __( 'Invalid status.', 'advanced-subscriptions-for-woocommerce' ) ) );
	   }

	   aswc_update_meta_data( $subscription_id, 'aswc_subscription_status', $status );

	   wp_send_json_success( array( 'message' => __( 'Subscription status updated.', 'advanced-subscriptions-for-woocommerce' ) ) );
}
add_action( 'wp_ajax_aswc_change_subscription_status', 'aswc_change_subscription_status' );

		/**
		 * Make subscription list table columns sortable.
		 *
		 * @param array $columns List of sortable columns.
		 *
		 * @return array
		 */
	function aswc_subscriptions_sortable_columns( $columns ) {
					$columns['status']                    = 'status';
					$columns['subscription']              = 'subscription';
					$columns['items']                     = 'items';
					$columns['total']                     = 'total';
					$columns['start_date']                = 'start_date';
					$columns['trial_end']                 = 'trial_end';
					$columns['next_payment_date']         = 'next_payment_date';
					$columns['last_order_date']           = 'last_order_date';
					$columns['subscriptions_expiry_date'] = 'subscriptions_expiry_date';

					return $columns;
	}
	add_filter( 'manage_edit-aswc_subscriptions_sortable_columns', 'aswc_subscriptions_sortable_columns' );
	add_filter( 'manage_woocommerce_page_wc-orders--aswc_subscriptions_sortable_columns', 'aswc_subscriptions_sortable_columns' );

		/**
		 * Handle sorting of subscription list table columns.
		 *
		 * @param WP_Query $query Current query instance.
		 */
	function aswc_subscriptions_orderby( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
				return;
		}

		if ( 'aswc_subscriptions' !== $query->get( 'post_type' ) ) {
					return;
		}

		switch ( $query->get( 'orderby' ) ) {
			case 'subscription':
						$query->set( 'orderby', 'ID' );
				break;
			case 'status':
													$query->set( 'orderby', 'post_status' );
				break;
			case 'items':
								$query->set( 'meta_key', '_product_name' );
				$query->set( 'orderby', 'meta_value' );
				break;
			case 'total':
								$query->set( 'meta_key', '_aswc_recurring_total' );
				$query->set( 'orderby', 'meta_value_num' );
				break;
			case 'start_date':
								$query->set( 'meta_key', '_aswc_schedule_start' );
				$query->set( 'orderby', 'meta_value' );
				break;
			case 'trial_end':
								$query->set( 'meta_key', '_aswc_susbcription_trial_end' );
				$query->set( 'orderby', 'meta_value' );
				break;
						case 'next_payment_date':
																$query->set( 'meta_key', aswc_normalize_meta_key( 'aswc_next_payment_date' ) );
								$query->set( 'orderby', 'meta_value' );
								break;
			case 'last_order_date':
								$query->set( 'meta_key', '_aswc_last_renewal_order_id' );
				$query->set( 'orderby', 'meta_value' );
				break;
			case 'subscriptions_expiry_date':
								$query->set( 'meta_key', '_aswc_susbcription_end' );
				$query->set( 'orderby', 'meta_value' );
				break;
		}
	}
		add_action( 'pre_get_posts', 'aswc_subscriptions_orderby' );



	/**
	 * Filter order status to show subscription real status.
	 *
	 * This keeps subscription status output aligned with the core order status.
	 *
	 * @param string   $status Current order status.
	 * @param WC_Order $order  Order object.
	 *
	 * @return string
	 */
	function aswc_display_real_subscription_status_2( $status, $order ) {
		   if ( class_exists( 'ASWC_Log' ) ) {
						   ASWC_Log::log( 'Function aswc_display_real_subscription_status called' );
		}

		if ( ! is_a( $order, 'WC_Order' ) ) {
				   if ( class_exists( 'ASWC_Log' ) ) {
						   ASWC_Log::log( '! is_a( $order, "WC_Order" ): ' . $status );
			}

					return $status;
		}

		if ( 0 === $order->get_id() ) {
				   if ( class_exists( 'ASWC_Log' ) ) {
										   ASWC_Log::log( 'Order ID is 0. Skipping status modification.' );
			}

					return $status;
		}

		if ( 'aswc_subscriptions' === $order->get_type() ) {
				   if ( class_exists( 'ASWC_Log' ) ) {
								   ASWC_Log::log( 'Order type is aswc_subscriptions' );
			}

				   if ( class_exists( 'ASWC_Log' ) ) {
								   ASWC_Log::log( '$status: ' . $status );
			}

				return $status;
		}

		if ( ! method_exists( $order, 'get_object_read' ) || ! $order->get_object_read() ) {
				   if ( class_exists( 'ASWC_Log' ) ) {
								   ASWC_Log::log( '! method_exists( $order, "get_object_read" ) || ! $order->get_object_read() ): ' . $status );
			}

				return $status;
		}

				return $status;
	}
	// add_filter( 'woocommerce_order_get_status', 'aswc_display_real_subscription_status', 10, 2 );

require_once ASWC_DIR_PATH . 'includes/loader.php';
