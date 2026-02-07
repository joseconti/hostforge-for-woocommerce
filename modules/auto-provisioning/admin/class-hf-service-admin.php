<?php
/**
 * Service Admin.
 *
 * Handles admin screens for the Auto Provisioning module:
 * service list, service detail, manual actions, automation settings.
 *
 * @package HostForge\Modules\AutoProvisioning\Admin
 */

namespace HostForge\Modules\AutoProvisioning\Admin;

use HostForge\Modules\AutoProvisioning\HF_Auto_Provisioning_Module;
use HostForge\HF_Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Service_Admin
 */
class HF_Service_Admin {

	/**
	 * Module instance.
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
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_hf_service_action', array( $this, 'ajax_service_action' ) );
		add_action( 'wp_ajax_hf_save_automation_settings', array( $this, 'ajax_save_automation_settings' ) );
	}

	/**
	 * Enqueue assets on service admin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		$screen = get_current_screen();
		if ( ! $screen || ! str_contains( $screen->id, 'hostforge-services' ) ) {
			return;
		}

		wp_enqueue_style(
			'hostforge-service-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/css/service-admin.css',
			array( 'hostforge-admin' ),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hostforge-service-admin',
			HOSTFORGE_PLUGIN_URL . 'assets/js/service-admin.js',
			array( 'hostforge-admin' ),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hostforge-service-admin',
			'hostforgeService',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_service_nonce' ),
				'i18n'    => array(
					'confirmSuspend'   => __( 'Are you sure you want to suspend this service?', 'hostforge' ),
					'confirmUnsuspend' => __( 'Are you sure you want to reactivate this service?', 'hostforge' ),
					'confirmTerminate' => __( 'Are you sure you want to TERMINATE this service? This cannot be undone.', 'hostforge' ),
					'processing'       => __( 'Processing...', 'hostforge' ),
					'success'          => __( 'Action completed successfully.', 'hostforge' ),
					'error'            => __( 'An error occurred.', 'hostforge' ),
					'saving'           => __( 'Saving...', 'hostforge' ),
					'saved'            => __( 'Settings saved.', 'hostforge' ),
				),
			)
		);
	}

	/**
	 * Render the main services page.
	 *
	 * @return void
	 */
	public function render_services_page(): void {
		if ( ! current_user_can( 'manage_hostforge_services' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'hostforge' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$service_id = isset( $_GET['service_id'] ) ? absint( $_GET['service_id'] ) : 0;

		if ( 'detail' === $action && $service_id > 0 ) {
			$this->render_service_detail( $service_id );
		} elseif ( 'settings' === $action ) {
			$this->render_automation_settings();
		} else {
			$this->render_service_list();
		}
	}

	/**
	 * Render the service list.
	 *
	 * @return void
	 */
	private function render_service_list(): void {
		$list_table = new HF_Service_List_Table();
		$list_table->prepare_items();

		$template = $this->module->get_module_dir() . 'admin/templates/service-list.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the service detail page.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	private function render_service_detail( int $service_id ): void {
		$service = get_post( $service_id );
		if ( ! $service || 'hf_service' !== $service->post_type ) {
			wp_die( esc_html__( 'Service not found.', 'hostforge' ) );
		}

		$meta = array();
		foreach ( array_keys( HF_Auto_Provisioning_Module::get_service_meta_keys() ) as $key ) {
			$meta[ $key ] = get_post_meta( $service_id, $key, true );
		}

		// Get related data.
		$order   = $meta['_hf_order_id'] ? wc_get_order( absint( $meta['_hf_order_id'] ) ) : null;
		$user    = $meta['_hf_user_id'] ? get_user_by( 'id', absint( $meta['_hf_user_id'] ) ) : null;
		$server  = $meta['_hf_server_id'] ? get_post( absint( $meta['_hf_server_id'] ) ) : null;
		$product = $meta['_hf_product_id'] ? wc_get_product( absint( $meta['_hf_product_id'] ) ) : null;

		// Queue history.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$queue_items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}hf_provisioning_queue WHERE service_id = %d ORDER BY id DESC LIMIT 20", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$service_id
			)
		);

		/**
		 * Filter the sections displayed on the admin service detail page.
		 *
		 * Allows adding, removing, or modifying the sections shown
		 * on the service detail page in the admin area.
		 *
		 * @since 1.0.0
		 *
		 * @param array $sections {
		 *     Sections data available to the template.
		 *
		 *     @type array         $meta        Service meta values.
		 *     @type \WC_Order|null $order       WooCommerce order or null.
		 *     @type \WP_User|null  $user        WordPress user or null.
		 *     @type \WP_Post|null  $server      Server post or null.
		 *     @type \WC_Product|null $product   WooCommerce product or null.
		 *     @type array         $queue_items Provisioning queue history.
		 * }
		 * @param int   $service_id Service post ID.
		 */
		$sections = apply_filters(
			'hostforge_service_detail_sections',
			array(
				'meta'        => $meta,
				'order'       => $order,
				'user'        => $user,
				'server'      => $server,
				'product'     => $product,
				'queue_items' => $queue_items,
			),
			$service_id
		);

		$meta        = $sections['meta'];
		$order       = $sections['order'];
		$user        = $sections['user'];
		$server      = $sections['server'];
		$product     = $sections['product'];
		$queue_items = $sections['queue_items'];

		$template = $this->module->get_module_dir() . 'admin/templates/service-detail.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render the automation settings page.
	 *
	 * @return void
	 */
	private function render_automation_settings(): void {
		$settings = array(
			'provision_on_processing' => get_option( 'hf_provision_on_processing', 'no' ),
			'auto_suspend_days'       => get_option( 'hf_auto_suspend_days', 3 ),
			'auto_terminate_days'     => get_option( 'hf_auto_terminate_days', 30 ),
			'password_length'         => get_option( 'hf_password_length', 16 ),
		);

		$template = $this->module->get_module_dir() . 'admin/templates/automation-settings.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * AJAX: Execute a manual service action (suspend, unsuspend, terminate).
	 *
	 * @return void
	 */
	public function ajax_service_action(): void {
		check_ajax_referer( 'hf_service_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_services' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$action     = isset( $_POST['service_action'] ) ? sanitize_text_field( wp_unslash( $_POST['service_action'] ) ) : '';

		if ( $service_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid service ID.', 'hostforge' ) ) );
		}

		$service = get_post( $service_id );
		if ( ! $service || 'hf_service' !== $service->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Service not found.', 'hostforge' ) ) );
		}

		$valid_actions = array( 'suspend', 'unsuspend', 'terminate' );

		if ( ! in_array( $action, $valid_actions, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'hostforge' ) ) );
		}

		$hook = 'hostforge_' . $action . '_service';

		as_enqueue_async_action(
			$hook,
			array( $service_id ),
			'hostforge-provisioning'
		);

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %s: action name */
					__( '%s action enqueued. It will be processed shortly.', 'hostforge' ),
					ucfirst( $action )
				),
			)
		);
	}

	/**
	 * AJAX: Save automation settings.
	 *
	 * @return void
	 */
	public function ajax_save_automation_settings(): void {
		check_ajax_referer( 'hf_service_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_hostforge_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'hostforge' ) ) );
		}

		$provision_on_processing = isset( $_POST['provision_on_processing'] ) ? sanitize_text_field( wp_unslash( $_POST['provision_on_processing'] ) ) : 'no';
		$auto_suspend_days       = isset( $_POST['auto_suspend_days'] ) ? absint( $_POST['auto_suspend_days'] ) : 3;
		$auto_terminate_days     = isset( $_POST['auto_terminate_days'] ) ? absint( $_POST['auto_terminate_days'] ) : 30;
		$password_length         = isset( $_POST['password_length'] ) ? absint( $_POST['password_length'] ) : 16;

		update_option( 'hf_provision_on_processing', $provision_on_processing );
		update_option( 'hf_auto_suspend_days', $auto_suspend_days );
		update_option( 'hf_auto_terminate_days', $auto_terminate_days );
		update_option( 'hf_password_length', $password_length );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'hostforge' ) ) );
	}
}
