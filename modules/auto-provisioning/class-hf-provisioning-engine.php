<?php
/**
 * Provisioning Engine.
 *
 * Handles the full lifecycle of hosting services:
 * - Listens to WooCommerce order/subscription hooks
 * - Creates hf_service CPT entries
 * - Enqueues provisioning actions via Action Scheduler
 * - Executes panel operations (create, suspend, unsuspend, terminate)
 * - Manages the provisioning queue with retry/backoff
 *
 * @package HostForge\Modules\AutoProvisioning
 */

namespace HostForge\Modules\AutoProvisioning;

use HostForge\HF_Encryption;
use HostForge\Subscriptions\HF_Subscription_Factory;
use HostForge\Traits\HF_Has_Logs;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Provisioning_Engine
 */
class HF_Provisioning_Engine {

	use HF_Has_Logs;

	/**
	 * Module reference.
	 *
	 * @var HF_Auto_Provisioning_Module
	 */
	private HF_Auto_Provisioning_Module $module;

	/**
	 * Constructor.
	 *
	 * @param HF_Auto_Provisioning_Module $module Module instance.
	 */
	public function __construct( HF_Auto_Provisioning_Module $module ) {
		$this->module = $module;
	}

	/**
	 * Get the module ID for the logging trait.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'auto-provisioning';
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// WooCommerce order status hooks.
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ), 10, 1 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_order_processing' ), 10, 1 );

		// Subscription status hooks (via adapter).
		$this->register_subscription_hooks();
	}

	/**
	 * Register subscription status change hooks.
	 *
	 * @return void
	 */
	private function register_subscription_hooks(): void {
		$adapter = HF_Subscription_Factory::get_adapter();

		if ( ! $adapter ) {
			return;
		}

		$hooks = $adapter->get_status_hooks();

		// Hook into subscription expired/on-hold for suspend.
		if ( isset( $hooks['expired'] ) ) {
			add_action( $hooks['expired'], array( $this, 'on_subscription_expired' ), 10, 1 );
		}

		if ( isset( $hooks['on-hold'] ) ) {
			add_action( $hooks['on-hold'], array( $this, 'on_subscription_on_hold' ), 10, 1 );
		}

		// Hook into subscription reactivated for unsuspend.
		if ( isset( $hooks['active'] ) ) {
			add_action( $hooks['active'], array( $this, 'on_subscription_reactivated' ), 10, 1 );
		}

		// Hook into subscription cancelled.
		if ( isset( $hooks['cancelled'] ) ) {
			add_action( $hooks['cancelled'], array( $this, 'on_subscription_cancelled' ), 10, 1 );
		}

		// Hook into subscription payment completed for auto-reactivate.
		if ( isset( $hooks['renewal_payment_complete'] ) ) {
			add_action( $hooks['renewal_payment_complete'], array( $this, 'on_renewal_payment' ), 10, 1 );
		}
	}

	/**
	 * Handle order completed status.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function on_order_completed( int $order_id ): void {
		$this->provision_from_order( $order_id );
	}

	/**
	 * Handle order processing status.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function on_order_processing( int $order_id ): void {
		$auto_provision = get_option( 'hf_provision_on_processing', 'no' );

		if ( 'yes' !== $auto_provision ) {
			return;
		}

		$this->provision_from_order( $order_id );
	}

	/**
	 * Create services from a WooCommerce order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	private function provision_from_order( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Skip if already provisioned.
		if ( 'yes' === $order->get_meta( '_hf_provisioned' ) ) {
			return;
		}

		$hosting_types = self::get_hosting_product_types();

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$product_type = $product->get_type();

			if ( ! in_array( $product_type, $hosting_types, true ) ) {
				continue;
			}

			$this->create_service_from_item( $order, $item, $product );
		}

		$order->update_meta_data( '_hf_provisioned', 'yes' );
		$order->save();
	}

	/**
	 * Create a service from an order line item.
	 *
	 * @param \WC_Order          $order   Order object.
	 * @param \WC_Order_Item     $item    Order line item.
	 * @param \WC_Product        $product Product object.
	 * @return int|false Service post ID or false on failure.
	 */
	private function create_service_from_item( \WC_Order $order, \WC_Order_Item $item, \WC_Product $product ): int|false {
		$domain = $this->get_domain_from_order( $order, $item );

		if ( empty( $domain ) ) {
			$this->log_error(
				'No domain found for service provisioning.',
				array(
					'order_id'   => $order->get_id(),
					'product_id' => $product->get_id(),
				)
			);
			return false;
		}

		// Generate username and password.
		$username = HF_Username_Generator::generate( $domain, $order->get_customer_id() );
		$password = HF_Password_Generator::generate();

		// Select server.
		$server_id = HF_Server_Selector::select( $product );

		if ( ! $server_id ) {
			$this->log_error(
				'No available server found for provisioning.',
				array(
					'order_id'     => $order->get_id(),
					'product_id'   => $product->get_id(),
					'product_type' => $product->get_type(),
				)
			);
			return false;
		}

		$panel_type = get_post_meta( $server_id, '_hf_panel_type', true );

		// Get hosting package/plan from product.
		$package = '';
		if ( method_exists( $product, 'get_meta' ) ) {
			$package = $product->get_meta( '_hf_hosting_plan' );
		}

		// Get subscription ID if applicable.
		$subscription_id = $this->find_subscription_id( $order );

		// Create the service CPT.
		$service_id = wp_insert_post(
			array(
				'post_type'   => 'hf_service',
				'post_status' => 'publish',
				'post_title'  => $domain,
				'post_author' => $order->get_customer_id(),
			)
		);

		if ( is_wp_error( $service_id ) ) {
			$this->log_error(
				'Failed to create service post.',
				array(
					'order_id' => $order->get_id(),
					'error'    => $service_id->get_error_message(),
				)
			);
			return false;
		}

		// Store service meta.
		update_post_meta( $service_id, '_hf_order_id', $order->get_id() );
		update_post_meta( $service_id, '_hf_subscription_id', $subscription_id );
		update_post_meta( $service_id, '_hf_product_id', $product->get_id() );
		update_post_meta( $service_id, '_hf_user_id', $order->get_customer_id() );
		update_post_meta( $service_id, '_hf_server_id', $server_id );
		update_post_meta( $service_id, '_hf_panel_username', $username );
		update_post_meta( $service_id, '_hf_panel_password', HF_Encryption::encrypt( $password ) );
		update_post_meta( $service_id, '_hf_domain', $domain );
		update_post_meta( $service_id, '_hf_status', 'pending' );
		update_post_meta( $service_id, '_hf_panel_type', $panel_type );
		update_post_meta( $service_id, '_hf_package', $package );

		// Calculate next due date from subscription.
		if ( $subscription_id ) {
			$adapter = HF_Subscription_Factory::get_adapter();
			if ( $adapter ) {
				$next_payment = $adapter->get_next_payment_date( $subscription_id );
				if ( $next_payment ) {
					update_post_meta( $service_id, '_hf_next_due_date', $next_payment );
				}
			}
		}

		// Add to provisioning queue.
		$this->enqueue_action(
			$service_id,
			'provision',
			array(
				'username' => $username,
				'password' => $password,
				'domain'   => $domain,
				'package'  => $package,
				'email'    => $order->get_billing_email(),
			)
		);

		/**
		 * Fires before a service is provisioned.
		 *
		 * @param int $service_id Service post ID.
		 * @param int $order_id   WooCommerce order ID.
		 */
		do_action( 'hostforge_before_provision', $service_id, $order->get_id() );

		// Enqueue async provisioning via Action Scheduler.
		as_enqueue_async_action(
			'hostforge_provision_service',
			array( $service_id ),
			'hostforge-provisioning'
		);

		$this->log_info(
			'Service created and provisioning enqueued.',
			array(
				'service_id' => $service_id,
				'order_id'   => $order->get_id(),
				'domain'     => $domain,
				'server_id'  => $server_id,
			)
		);

		return $service_id;
	}

	/**
	 * Execute the provisioning action on the server.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function execute_provision( int $service_id ): void {
		$server_id = absint( get_post_meta( $service_id, '_hf_server_id', true ) );
		$provider  = $this->get_provider( $server_id );

		if ( ! $provider ) {
			$this->handle_failure( $service_id, 'provision', 'No panel provider available for server.' );
			return;
		}

		$username = get_post_meta( $service_id, '_hf_panel_username', true );
		$password = HF_Encryption::decrypt( get_post_meta( $service_id, '_hf_panel_password', true ) );
		$domain   = get_post_meta( $service_id, '_hf_domain', true );
		$package  = get_post_meta( $service_id, '_hf_package', true );
		$order_id = absint( get_post_meta( $service_id, '_hf_order_id', true ) );
		$order    = wc_get_order( $order_id );
		$email    = $order ? $order->get_billing_email() : '';

		/**
		 * Filter provision parameters before execution.
		 *
		 * @param array $params   Provision parameters.
		 * @param int   $order_id WooCommerce order ID.
		 */
		$params = apply_filters(
			'hostforge_provision_params',
			array(
				'domain'   => $domain,
				'username' => $username,
				'password' => $password,
				'plan'     => $package,
				'email'    => $email,
			),
			$order_id
		);

		$result = $provider->create_account( $params );

		if ( $result['success'] ) {
			$account_data = isset( $result['data'] ) ? $result['data'] : array();

			/**
			 * Filter account data returned from the panel provider after creation.
			 *
			 * Allows modification or enrichment of the account data
			 * (e.g. adding custom fields) before the provisioned action fires.
			 *
			 * @since 1.0.0
			 *
			 * @param array $account_data Account data returned by the provider.
			 * @param int   $service_id   Service post ID.
			 * @param int   $server_id    Server post ID.
			 */
			$account_data = apply_filters( 'hostforge_provision_account_data', $account_data, $service_id, $server_id );

			update_post_meta( $service_id, '_hf_status', 'active' );
			update_post_meta( $service_id, '_hf_provisioned_at', current_time( 'mysql' ) );

			$this->complete_queue_item( $service_id, 'provision' );

			$this->log_info(
				'Service provisioned successfully.',
				array(
					'service_id' => $service_id,
					'domain'     => $domain,
					'username'   => $username,
				)
			);

			/**
			 * Fires after a service is successfully provisioned.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $service_id   Service post ID.
			 * @param array $account_data Account data from the provider.
			 */
			do_action( 'hostforge_after_provision', $service_id, $account_data );

			/**
			 * Filter the welcome email data sent to the customer after provisioning.
			 *
			 * Allows modification of the data passed to the welcome email,
			 * e.g. to add nameservers, extra instructions, or custom links.
			 *
			 * @since 1.0.0
			 *
			 * @param array $email_data {
			 *     Welcome email data.
			 *
			 *     @type int    $service_id   Service post ID.
			 *     @type string $domain       Domain name.
			 *     @type string $username     Panel username.
			 *     @type string $password     Panel password (plain text).
			 *     @type string $package      Hosting package name.
			 *     @type int    $server_id    Server post ID.
			 *     @type array  $account_data Account data from provider.
			 * }
			 * @param int   $order_id   WooCommerce order ID.
			 */
			apply_filters(
				'hostforge_service_welcome_email_data',
				array(
					'service_id'   => $service_id,
					'domain'       => $domain,
					'username'     => $username,
					'password'     => $password,
					'package'      => $package,
					'server_id'    => $server_id,
					'account_data' => $account_data,
				),
				$order_id
			);
		} else {
			$this->handle_failure( $service_id, 'provision', $result['message'] ?? 'Unknown error.' );
		}
	}

	/**
	 * Execute a suspend action on the server.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function execute_suspend( int $service_id ): void {
		$current_status = get_post_meta( $service_id, '_hf_status', true );

		if ( 'active' !== $current_status ) {
			return;
		}

		$server_id = absint( get_post_meta( $service_id, '_hf_server_id', true ) );
		$provider  = $this->get_provider( $server_id );

		if ( ! $provider ) {
			$this->handle_failure( $service_id, 'suspend', 'No panel provider available.' );
			return;
		}

		$username = get_post_meta( $service_id, '_hf_panel_username', true );

		/**
		 * Fires before a service is suspended.
		 *
		 * @param int    $service_id Service post ID.
		 * @param string $reason     Suspension reason.
		 */
		do_action( 'hostforge_before_suspend', $service_id, 'subscription_expired' );

		$result = $provider->suspend_account( $username, __( 'Subscription expired or on hold.', 'hostforge' ) );

		if ( $result['success'] ) {
			update_post_meta( $service_id, '_hf_status', 'suspended' );
			update_post_meta( $service_id, '_hf_suspended_at', current_time( 'mysql' ) );

			$this->log_info( 'Service suspended.', array( 'service_id' => $service_id ) );

			/**
			 * Fires after a service is suspended on the server.
			 *
			 * @since 1.0.0
			 *
			 * @param int $service_id Service post ID.
			 */
			do_action( 'hostforge_after_suspend', $service_id );
		} else {
			$this->handle_failure( $service_id, 'suspend', $result['message'] ?? 'Unknown error.' );
		}
	}

	/**
	 * Execute an unsuspend action on the server.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function execute_unsuspend( int $service_id ): void {
		$current_status = get_post_meta( $service_id, '_hf_status', true );

		if ( 'suspended' !== $current_status ) {
			return;
		}

		$server_id = absint( get_post_meta( $service_id, '_hf_server_id', true ) );
		$provider  = $this->get_provider( $server_id );

		if ( ! $provider ) {
			$this->handle_failure( $service_id, 'unsuspend', 'No panel provider available.' );
			return;
		}

		$username = get_post_meta( $service_id, '_hf_panel_username', true );

		/**
		 * Fires before a service is unsuspended on the server.
		 *
		 * @since 1.0.0
		 *
		 * @param int $service_id Service post ID.
		 */
		do_action( 'hostforge_before_unsuspend', $service_id );

		$result = $provider->unsuspend_account( $username );

		if ( $result['success'] ) {
			update_post_meta( $service_id, '_hf_status', 'active' );
			delete_post_meta( $service_id, '_hf_suspended_at' );

			$this->log_info( 'Service unsuspended.', array( 'service_id' => $service_id ) );

			/**
			 * Fires after a service is unsuspended on the server.
			 *
			 * @since 1.0.0
			 *
			 * @param int $service_id Service post ID.
			 */
			do_action( 'hostforge_after_unsuspend', $service_id );
		} else {
			$this->handle_failure( $service_id, 'unsuspend', $result['message'] ?? 'Unknown error.' );
		}
	}

	/**
	 * Execute a terminate action on the server.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function execute_terminate( int $service_id ): void {
		$current_status = get_post_meta( $service_id, '_hf_status', true );

		if ( in_array( $current_status, array( 'terminated', 'cancelled' ), true ) ) {
			return;
		}

		$server_id = absint( get_post_meta( $service_id, '_hf_server_id', true ) );
		$provider  = $this->get_provider( $server_id );

		if ( ! $provider ) {
			$this->handle_failure( $service_id, 'terminate', 'No panel provider available.' );
			return;
		}

		$username = get_post_meta( $service_id, '_hf_panel_username', true );

		/**
		 * Fires before a service is terminated on the server.
		 *
		 * @since 1.0.0
		 *
		 * @param int $service_id Service post ID.
		 */
		do_action( 'hostforge_before_terminate', $service_id );

		$result = $provider->terminate_account( $username );

		if ( $result['success'] ) {
			update_post_meta( $service_id, '_hf_status', 'terminated' );
			update_post_meta( $service_id, '_hf_terminated_at', current_time( 'mysql' ) );

			$this->log_info( 'Service terminated.', array( 'service_id' => $service_id ) );

			/**
			 * Fires after a service is terminated on the server.
			 *
			 * @since 1.0.0
			 *
			 * @param int $service_id Service post ID.
			 */
			do_action( 'hostforge_after_terminate', $service_id );
		} else {
			$this->handle_failure( $service_id, 'terminate', $result['message'] ?? 'Unknown error.' );
		}
	}

	/**
	 * Execute a package change on the server.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function execute_change_package( int $service_id ): void {
		$server_id = absint( get_post_meta( $service_id, '_hf_server_id', true ) );
		$provider  = $this->get_provider( $server_id );

		if ( ! $provider ) {
			$this->handle_failure( $service_id, 'change_package', 'No panel provider available.' );
			return;
		}

		$username    = get_post_meta( $service_id, '_hf_panel_username', true );
		$new_package = get_post_meta( $service_id, '_hf_pending_package', true );

		if ( empty( $new_package ) ) {
			return;
		}

		/**
		 * Filter the change package parameters before execution.
		 *
		 * Allows modification of the username and package name
		 * before the package change is sent to the panel provider.
		 *
		 * @since 1.0.0
		 *
		 * @param array $params {
		 *     Package change parameters.
		 *
		 *     @type string $username    Panel username.
		 *     @type string $new_package New package/plan name.
		 * }
		 * @param int   $service_id Service post ID.
		 * @param int   $server_id  Server post ID.
		 */
		$change_params = apply_filters(
			'hostforge_change_package_params',
			array(
				'username'    => $username,
				'new_package' => $new_package,
			),
			$service_id,
			$server_id
		);

		$username    = $change_params['username'];
		$new_package = $change_params['new_package'];

		$result = $provider->change_package( $username, $new_package );

		if ( $result['success'] ) {
			update_post_meta( $service_id, '_hf_package', $new_package );
			delete_post_meta( $service_id, '_hf_pending_package' );

			$this->log_info(
				'Service package changed.',
				array(
					'service_id'  => $service_id,
					'new_package' => $new_package,
				)
			);
		} else {
			$this->handle_failure( $service_id, 'change_package', $result['message'] ?? 'Unknown error.' );
		}
	}

	/**
	 * Handle subscription expired event.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return void
	 */
	public function on_subscription_expired( int $subscription_id ): void {
		$services = $this->get_services_by_subscription( $subscription_id );

		foreach ( $services as $service_id ) {
			$status = get_post_meta( $service_id, '_hf_status', true );

			if ( 'active' !== $status ) {
				continue;
			}

			as_enqueue_async_action(
				'hostforge_suspend_service',
				array( $service_id ),
				'hostforge-provisioning'
			);

			$this->log_info(
				'Subscription expired — service suspension enqueued.',
				array(
					'service_id'      => $service_id,
					'subscription_id' => $subscription_id,
				)
			);
		}
	}

	/**
	 * Handle subscription on-hold event.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return void
	 */
	public function on_subscription_on_hold( int $subscription_id ): void {
		// Same behavior as expired for now.
		$this->on_subscription_expired( $subscription_id );
	}

	/**
	 * Handle subscription reactivated event.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return void
	 */
	public function on_subscription_reactivated( int $subscription_id ): void {
		$services = $this->get_services_by_subscription( $subscription_id );

		foreach ( $services as $service_id ) {
			$status = get_post_meta( $service_id, '_hf_status', true );

			if ( 'suspended' !== $status ) {
				continue;
			}

			as_enqueue_async_action(
				'hostforge_unsuspend_service',
				array( $service_id ),
				'hostforge-provisioning'
			);

			$this->log_info(
				'Subscription reactivated — service unsuspension enqueued.',
				array(
					'service_id'      => $service_id,
					'subscription_id' => $subscription_id,
				)
			);
		}
	}

	/**
	 * Handle subscription cancelled event.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return void
	 */
	public function on_subscription_cancelled( int $subscription_id ): void {
		$services = $this->get_services_by_subscription( $subscription_id );

		foreach ( $services as $service_id ) {
			$status = get_post_meta( $service_id, '_hf_status', true );

			if ( in_array( $status, array( 'terminated', 'cancelled' ), true ) ) {
				continue;
			}

			update_post_meta( $service_id, '_hf_status', 'cancelled' );

			$this->log_info(
				'Subscription cancelled — service marked as cancelled.',
				array(
					'service_id'      => $service_id,
					'subscription_id' => $subscription_id,
				)
			);
		}
	}

	/**
	 * Handle renewal payment for auto-reactivation.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return void
	 */
	public function on_renewal_payment( int $subscription_id ): void {
		$services = $this->get_services_by_subscription( $subscription_id );

		foreach ( $services as $service_id ) {
			$status = get_post_meta( $service_id, '_hf_status', true );

			if ( 'suspended' !== $status ) {
				continue;
			}

			as_enqueue_async_action(
				'hostforge_unsuspend_service',
				array( $service_id ),
				'hostforge-provisioning'
			);

			// Update next due date.
			$adapter = HF_Subscription_Factory::get_adapter();
			if ( $adapter ) {
				$next_payment = $adapter->get_next_payment_date( $subscription_id );
				if ( $next_payment ) {
					update_post_meta( $service_id, '_hf_next_due_date', $next_payment );
				}
			}

			$this->log_info(
				'Renewal payment received — service auto-reactivation enqueued.',
				array(
					'service_id'      => $service_id,
					'subscription_id' => $subscription_id,
				)
			);
		}
	}

	/**
	 * Add an action to the provisioning queue.
	 *
	 * @param int    $service_id Service post ID.
	 * @param string $action     Action name (provision, suspend, unsuspend, terminate, change_package).
	 * @param array  $params     Action parameters.
	 * @return int|false Queue entry ID or false.
	 */
	private function enqueue_action( int $service_id, string $action, array $params = array() ): int|false {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$wpdb->prefix . 'hf_provisioning_queue',
			array(
				'service_id'   => $service_id,
				'action'       => $action,
				'params'       => wp_json_encode( $params ),
				'status'       => 'pending',
				'attempts'     => 0,
				'max_attempts' => 3,
				'scheduled_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Handle a failed provisioning action with retry logic.
	 *
	 * @param int    $service_id Service post ID.
	 * @param string $action     Action name.
	 * @param string $error      Error message.
	 * @return void
	 */
	private function handle_failure( int $service_id, string $action, string $error ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'hf_provisioning_queue';

		// Get current queue entry.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entry = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE service_id = %d AND action = %s AND status IN ('pending', 'processing') ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$service_id,
				$action
			)
		);

		if ( ! $entry ) {
			// Create entry if not exists.
			$this->enqueue_action( $service_id, $action );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$entry = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE service_id = %d AND action = %s ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$service_id,
					$action
				)
			);
		}

		if ( ! $entry ) {
			$this->log_error(
				'Failed to create queue entry for retry.',
				array(
					'service_id' => $service_id,
					'action'     => $action,
				)
			);
			return;
		}

		$new_attempts = absint( $entry->attempts ) + 1;

		if ( $new_attempts >= absint( $entry->max_attempts ) ) {
			// Max retries reached.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array(
					'status'     => 'failed',
					'attempts'   => $new_attempts,
					'last_error' => $error,
				),
				array( 'id' => $entry->id ),
				array( '%s', '%d', '%s' ),
				array( '%d' )
			);

			$this->log_error(
				'Provisioning action failed after max retries.',
				array(
					'service_id' => $service_id,
					'action'     => $action,
					'error'      => $error,
					'attempts'   => $new_attempts,
				)
			);

			/**
			 * Fires when provisioning permanently fails.
			 *
			 * @param int    $service_id Service post ID.
			 * @param string $error      Error message.
			 */
			do_action( 'hostforge_provision_failed', $service_id, $error );

			return;
		}

		// Schedule retry with exponential backoff: 5min * attempt.
		$delay = 5 * MINUTE_IN_SECONDS * $new_attempts;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array(
				'status'     => 'pending',
				'attempts'   => $new_attempts,
				'last_error' => $error,
			),
			array( 'id' => $entry->id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);

		$action_hook = 'hostforge_' . $action . '_service';

		as_schedule_single_action(
			time() + $delay,
			$action_hook,
			array( $service_id ),
			'hostforge-provisioning'
		);

		$this->log_warning(
			'Provisioning action failed, retry scheduled.',
			array(
				'service_id' => $service_id,
				'action'     => $action,
				'error'      => $error,
				'attempt'    => $new_attempts,
				'delay_sec'  => $delay,
			)
		);
	}

	/**
	 * Mark a queue item as completed.
	 *
	 * @param int    $service_id Service post ID.
	 * @param string $action     Action name.
	 * @return void
	 */
	private function complete_queue_item( int $service_id, string $action ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}hf_provisioning_queue SET status = 'completed', completed_at = %s WHERE service_id = %d AND action = %s AND status IN ('pending', 'processing') ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				current_time( 'mysql', true ),
				$service_id,
				$action
			)
		);
	}

	/**
	 * Get the panel provider for a server.
	 *
	 * @param int $server_id Server post ID.
	 * @return \HostForge\Interfaces\HF_Panel_Provider|null
	 */
	private function get_provider( int $server_id ): ?\HostForge\Interfaces\HF_Panel_Provider {
		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$server_module  = $module_manager->get_module( 'server-manager' );

		if ( ! $server_module || ! method_exists( $server_module, 'get_provider' ) ) {
			return null;
		}

		return $server_module->get_provider( $server_id );
	}

	/**
	 * Get services linked to a subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return array<int> Service post IDs.
	 */
	private function get_services_by_subscription( int $subscription_id ): array {
		$services = get_posts(
			array(
				'post_type'      => 'hf_service',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_hf_subscription_id',
						'value' => $subscription_id,
					),
				),
			)
		);

		return $services;
	}

	/**
	 * Get the domain from order meta.
	 *
	 * @param \WC_Order      $order Order object.
	 * @param \WC_Order_Item $item  Order line item.
	 * @return string
	 */
	private function get_domain_from_order( \WC_Order $order, \WC_Order_Item $item ): string {
		// Check line item meta first.
		$domain = wc_get_order_item_meta( $item->get_id(), '_hf_domain', true );

		if ( ! empty( $domain ) ) {
			return sanitize_text_field( $domain );
		}

		// Check order meta.
		$domain = $order->get_meta( '_hf_domain' );

		if ( ! empty( $domain ) ) {
			return sanitize_text_field( $domain );
		}

		// Fallback: check _hf_hostname (for VPS/dedicated).
		$hostname = wc_get_order_item_meta( $item->get_id(), '_hf_hostname', true );

		if ( ! empty( $hostname ) ) {
			return sanitize_text_field( $hostname );
		}

		return sanitize_text_field( $order->get_meta( '_hf_hostname' ) );
	}

	/**
	 * Find subscription ID linked to an order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return int Subscription ID or 0.
	 */
	private function find_subscription_id( \WC_Order $order ): int {
		$adapter = HF_Subscription_Factory::get_adapter();

		if ( ! $adapter ) {
			return 0;
		}

		$user_subs = $adapter->get_subscriptions_by_user( $order->get_customer_id() );

		// Try to find a subscription linked to this order.
		// This is a basic lookup — each adapter could have more specific methods.
		foreach ( $user_subs as $sub_id ) {
			// WCS stores parent_order_id on the subscription.
			$parent_order = get_post_meta( $sub_id, '_order_id', true );

			if ( absint( $parent_order ) === $order->get_id() ) {
				return $sub_id;
			}
		}

		return 0;
	}

	/**
	 * Get hosting product types that trigger provisioning.
	 *
	 * @return array<string>
	 */
	public static function get_hosting_product_types(): array {
		$types = array(
			'hf_shared_hosting',
			'hf_reseller_hosting',
			'hf_vps_server',
			'hf_dedicated_server',
		);

		/**
		 * Filter the product types that trigger auto-provisioning.
		 *
		 * @param array $types Product type slugs.
		 */
		return apply_filters( 'hostforge_provisioning_product_types', $types );
	}
}
