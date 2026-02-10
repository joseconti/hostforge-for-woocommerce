<?php
/**
 * Auto Provisioning Module.
 *
 * Automates the full lifecycle of hosting services: provisioning on order
 * completion, suspension/reactivation on subscription changes, and
 * termination after grace period.
 *
 * @package HostForge\Modules\AutoProvisioning
 */

namespace HostForge\Modules\AutoProvisioning;

use HostForge\Abstracts\HF_Module;
use HostForge\Traits\HF_Has_Logs;
use HostForge\Traits\HF_Has_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Auto_Provisioning_Module
 */
class HF_Auto_Provisioning_Module extends HF_Module {

	use HF_Has_Logs;
	use HF_Has_Settings;

	/**
	 * Get the module identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'auto-provisioning';
	}

	/**
	 * Get the module display name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Auto Provisioning', 'hostforge' );
	}

	/**
	 * Get the module description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return __( 'Automatically provision, suspend and terminate hosting accounts based on WooCommerce orders and subscriptions.', 'hostforge' );
	}

	/**
	 * Get required dependencies.
	 *
	 * @return array<string>
	 */
	public function get_dependencies(): array {
		return array( 'server-manager' );
	}

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register CPT.
		add_action( 'init', array( $this, 'register_post_type' ) );

		// Provisioning engine.
		$engine = new HF_Provisioning_Engine( $this );
		$engine->init();

		// Admin hooks.
		if ( is_admin() ) {
			$admin = new Admin\HF_Service_Admin( $this );
			$admin->init();
		}

		// Frontend hooks.
		if ( ! is_admin() || wp_doing_ajax() ) {
			$frontend = new HF_Service_Frontend();
			$frontend->init();
		}

		// Scheduled actions.
		$this->register_scheduled_actions();

		// Action Scheduler callbacks.
		add_action( 'hostforge_provision_service', array( $this, 'process_provision' ) );
		add_action( 'hostforge_suspend_service', array( $this, 'process_suspend' ) );
		add_action( 'hostforge_unsuspend_service', array( $this, 'process_unsuspend' ) );
		add_action( 'hostforge_terminate_service', array( $this, 'process_terminate' ) );
		add_action( 'hostforge_change_package_service', array( $this, 'process_change_package' ) );
		add_action( 'hostforge_auto_suspend_check', array( $this, 'run_auto_suspend' ) );
		add_action( 'hostforge_auto_terminate_check', array( $this, 'run_auto_terminate' ) );
	}

	/**
	 * Called when the module is activated.
	 *
	 * @return void
	 */
	public function activate(): void {
		$this->create_tables();
		$this->register_post_type();
		flush_rewrite_rules();

		// Schedule recurring automation tasks.
		if ( function_exists( 'as_has_scheduled_action' ) && did_action( 'action_scheduler_init' ) ) {
			if ( ! as_has_scheduled_action( 'hostforge_auto_suspend_check' ) ) {
				as_schedule_recurring_action(
					time() + 300,
					6 * HOUR_IN_SECONDS,
					'hostforge_auto_suspend_check',
					array(),
					'hostforge-provisioning'
				);
			}

			if ( ! as_has_scheduled_action( 'hostforge_auto_terminate_check' ) ) {
				as_schedule_recurring_action(
					time() + 600,
					DAY_IN_SECONDS,
					'hostforge_auto_terminate_check',
					array(),
					'hostforge-provisioning'
				);
			}
		}

		$this->log_info( 'Auto Provisioning module activated.' );
	}

	/**
	 * Called when the module is deactivated.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'hostforge_auto_suspend_check', array(), 'hostforge-provisioning' );
			as_unschedule_all_actions( 'hostforge_auto_terminate_check', array(), 'hostforge-provisioning' );
		}

		$this->log_info( 'Auto Provisioning module deactivated.' );
	}

	/**
	 * Register the hf_service custom post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		$labels = array(
			'name'               => __( 'Services', 'hostforge' ),
			'singular_name'      => __( 'Service', 'hostforge' ),
			'add_new'            => __( 'Add New Service', 'hostforge' ),
			'add_new_item'       => __( 'Add New Service', 'hostforge' ),
			'edit_item'          => __( 'Edit Service', 'hostforge' ),
			'new_item'           => __( 'New Service', 'hostforge' ),
			'view_item'          => __( 'View Service', 'hostforge' ),
			'search_items'       => __( 'Search Services', 'hostforge' ),
			'not_found'          => __( 'No services found.', 'hostforge' ),
			'not_found_in_trash' => __( 'No services found in Trash.', 'hostforge' ),
			'all_items'          => __( 'Services', 'hostforge' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'capability_type'     => 'post',
			'capabilities'        => array(
				'edit_post'          => 'manage_hostforge_services',
				'read_post'          => 'manage_hostforge_services',
				'delete_post'        => 'manage_hostforge_services',
				'edit_posts'         => 'manage_hostforge_services',
				'edit_others_posts'  => 'manage_hostforge_services',
				'publish_posts'      => 'manage_hostforge_services',
				'read_private_posts' => 'manage_hostforge_services',
			),
			'map_meta_cap'        => false,
			'hierarchical'        => false,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => false,
			'exclude_from_search' => true,
		);

		register_post_type( 'hf_service', $args );
	}

	/**
	 * Create the provisioning queue table.
	 *
	 * @return void
	 */
	private function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}hf_provisioning_queue (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			service_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(20) NOT NULL,
			params LONGTEXT,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			attempts INT UNSIGNED NOT NULL DEFAULT 0,
			max_attempts INT UNSIGNED NOT NULL DEFAULT 3,
			last_error TEXT,
			scheduled_at DATETIME NOT NULL,
			completed_at DATETIME,
			PRIMARY KEY (id),
			KEY service_id (service_id),
			KEY status (status),
			KEY status_scheduled (status, scheduled_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Register scheduled actions.
	 *
	 * @return void
	 */
	public function register_scheduled_actions(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		if ( ! did_action( 'action_scheduler_init' ) ) {
			add_action( 'action_scheduler_init', array( $this, 'register_scheduled_actions' ) );
			return;
		}

		if ( ! as_has_scheduled_action( 'hostforge_auto_suspend_check' ) ) {
			as_schedule_recurring_action(
				time() + 300,
				6 * HOUR_IN_SECONDS,
				'hostforge_auto_suspend_check',
				array(),
				'hostforge-provisioning'
			);
		}

		if ( ! as_has_scheduled_action( 'hostforge_auto_terminate_check' ) ) {
			as_schedule_recurring_action(
				time() + 600,
				DAY_IN_SECONDS,
				'hostforge_auto_terminate_check',
				array(),
				'hostforge-provisioning'
			);
		}
	}

	/**
	 * Process a provisioning action for a service.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function process_provision( int $service_id ): void {
		$engine = new HF_Provisioning_Engine( $this );
		$engine->execute_provision( $service_id );
	}

	/**
	 * Process a suspend action for a service.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function process_suspend( int $service_id ): void {
		$engine = new HF_Provisioning_Engine( $this );
		$engine->execute_suspend( $service_id );
	}

	/**
	 * Process an unsuspend action for a service.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function process_unsuspend( int $service_id ): void {
		$engine = new HF_Provisioning_Engine( $this );
		$engine->execute_unsuspend( $service_id );
	}

	/**
	 * Process a terminate action for a service.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function process_terminate( int $service_id ): void {
		$engine = new HF_Provisioning_Engine( $this );
		$engine->execute_terminate( $service_id );
	}

	/**
	 * Process a package change action for a service.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	public function process_change_package( int $service_id ): void {
		$engine = new HF_Provisioning_Engine( $this );
		$engine->execute_change_package( $service_id );
	}

	/**
	 * Run auto-suspend check on services with expired subscriptions.
	 *
	 * @return void
	 */
	public function run_auto_suspend(): void {
		$grace_days = absint( get_option( 'hf_auto_suspend_days', 3 ) );

		if ( 0 === $grace_days ) {
			return;
		}

		$services = get_posts(
			array(
				'post_type'      => 'hf_service',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_hf_status',
						'value' => 'active',
					),
				),
			)
		);

		$adapter = \HostForge\Subscriptions\HF_Subscription_Factory::get_adapter();

		if ( ! $adapter ) {
			return;
		}

		foreach ( $services as $service ) {
			$subscription_id = absint( get_post_meta( $service->ID, '_hf_subscription_id', true ) );

			if ( ! $subscription_id ) {
				continue;
			}

			$status = $adapter->get_status( $subscription_id );

			if ( ! in_array( $status, array( 'expired', 'on-hold', 'cancelled' ), true ) ) {
				continue;
			}

			$next_due = get_post_meta( $service->ID, '_hf_next_due_date', true );

			if ( empty( $next_due ) ) {
				continue;
			}

			$due_time   = strtotime( $next_due );
			$grace_time = $due_time + ( $grace_days * DAY_IN_SECONDS );

			if ( time() > $grace_time ) {
				$this->log_info(
					'Auto-suspending service due to expired subscription.',
					array(
						'service_id'      => $service->ID,
						'subscription_id' => $subscription_id,
					)
				);

				as_enqueue_async_action(
					'hostforge_suspend_service',
					array( $service->ID ),
					'hostforge-provisioning'
				);
			}
		}
	}

	/**
	 * Run auto-terminate check on suspended services.
	 *
	 * @return void
	 */
	public function run_auto_terminate(): void {
		$terminate_days = absint( get_option( 'hf_auto_terminate_days', 30 ) );

		if ( 0 === $terminate_days ) {
			return;
		}

		$services = get_posts(
			array(
				'post_type'      => 'hf_service',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_hf_status',
						'value' => 'suspended',
					),
				),
			)
		);

		foreach ( $services as $service ) {
			$suspended_at = get_post_meta( $service->ID, '_hf_suspended_at', true );

			if ( empty( $suspended_at ) ) {
				continue;
			}

			$suspended_time  = strtotime( $suspended_at );
			$terminate_after = $suspended_time + ( $terminate_days * DAY_IN_SECONDS );

			if ( time() > $terminate_after ) {
				$this->log_info(
					'Auto-terminating suspended service.',
					array(
						'service_id'   => $service->ID,
						'suspended_at' => $suspended_at,
					)
				);

				as_enqueue_async_action(
					'hostforge_terminate_service',
					array( $service->ID ),
					'hostforge-provisioning'
				);
			}
		}
	}

	/**
	 * Get admin menu items.
	 *
	 * @return array
	 */
	public function get_admin_menu_items(): array {
		return array(
			array(
				'title'      => __( 'Services', 'hostforge' ),
				'slug'       => 'hostforge-services',
				'capability' => 'manage_hostforge_services',
				'callback'   => array( new Admin\HF_Service_Admin( $this ), 'render_services_page' ),
			),
		);
	}

	/**
	 * Get My Account endpoints for this module.
	 *
	 * @return array
	 */
	public function get_myaccount_endpoints(): array {
		return array(
			array(
				'endpoint' => 'hosting-services',
				'title'    => __( 'Hosting Services', 'hostforge' ),
			),
		);
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$controller = new Api\HF_REST_Service_Controller();
		$controller->register_routes();
	}

	/**
	 * Get dashboard widgets.
	 *
	 * @return array
	 */
	public function get_dashboard_widgets(): array {
		return array(
			array(
				'id'       => 'hf-services-count',
				'title'    => __( 'Services Overview', 'hostforge' ),
				'callback' => array( $this, 'render_dashboard_widget' ),
			),
		);
	}

	/**
	 * Render the services dashboard widget.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS status, COUNT(*) AS total
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
				WHERE p.post_type = %s AND p.post_status = 'publish'
				GROUP BY pm.meta_value",
				'_hf_status',
				'hf_service'
			)
		);

		$counts = array(
			'active'     => 0,
			'pending'    => 0,
			'suspended'  => 0,
			'terminated' => 0,
			'cancelled'  => 0,
		);

		if ( $results ) {
			foreach ( $results as $row ) {
				if ( isset( $counts[ $row->status ] ) ) {
					$counts[ $row->status ] = absint( $row->total );
				}
			}
		}

		$total = array_sum( $counts );

		?>
		<div class="hf-widget-stats">
			<div class="hf-stat">
				<span class="hf-stat__number"><?php echo esc_html( $total ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Total Services', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--success"><?php echo esc_html( $counts['active'] ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Active', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--warning"><?php echo esc_html( $counts['pending'] ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Pending', 'hostforge' ); ?></span>
			</div>
			<div class="hf-stat">
				<span class="hf-stat__number hf-stat__number--error"><?php echo esc_html( $counts['suspended'] ); ?></span>
				<span class="hf-stat__label"><?php esc_html_e( 'Suspended', 'hostforge' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Get all meta keys for the hf_service CPT.
	 *
	 * @return array<string, string> Key => description.
	 */
	public static function get_service_meta_keys(): array {
		return array(
			'_hf_order_id'            => 'WooCommerce order ID',
			'_hf_subscription_id'     => 'Subscription ID (from subscription plugin)',
			'_hf_product_id'          => 'WooCommerce product ID',
			'_hf_user_id'             => 'WordPress user ID',
			'_hf_server_id'           => 'Server post ID (hf_server)',
			'_hf_panel_username'      => 'Panel account username',
			'_hf_panel_password'      => 'Panel account password (encrypted)',
			'_hf_domain'              => 'Primary domain name',
			'_hf_status'              => 'pending, active, suspended, terminated, cancelled',
			'_hf_provisioned_at'      => 'DateTime when provisioned',
			'_hf_suspended_at'        => 'DateTime when suspended',
			'_hf_terminated_at'       => 'DateTime when terminated',
			'_hf_next_due_date'       => 'Next payment due date',
			'_hf_panel_type'          => 'cpanel or plesk',
			'_hf_package'             => 'Hosting plan/package name',
			'_hf_cancel_reason'       => 'Cancellation reason from customer',
			'_hf_cancel_requested_at' => 'DateTime of cancellation request',
		);
	}

	/**
	 * Get valid service statuses.
	 *
	 * @return array<string, string>
	 */
	public static function get_statuses(): array {
		return array(
			'pending'    => __( 'Pending', 'hostforge' ),
			'active'     => __( 'Active', 'hostforge' ),
			'suspended'  => __( 'Suspended', 'hostforge' ),
			'terminated' => __( 'Terminated', 'hostforge' ),
			'cancelled'  => __( 'Cancelled', 'hostforge' ),
		);
	}
}
