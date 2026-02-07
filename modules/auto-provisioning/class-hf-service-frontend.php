<?php
/**
 * Service Frontend.
 *
 * Handles WooCommerce My Account integration:
 * - Registers the hosting-services endpoint
 * - Displays service list and detail pages
 * - Handles cancellation and upgrade/downgrade requests
 * - SSO, password change and usage stats
 *
 * @package HostForge\Modules\AutoProvisioning
 */

namespace HostForge\Modules\AutoProvisioning;

use HostForge\HF_Encryption;
use HostForge\Subscriptions\HF_Subscription_Factory;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Service_Frontend
 */
class HF_Service_Frontend {

	/**
	 * Endpoint slug.
	 *
	 * @var string
	 */
	private const ENDPOINT = 'hosting-services';

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Register endpoint.
		add_action( 'init', array( $this, 'register_endpoint' ) );

		// My Account menu item.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ), 40 );

		// Endpoint content.
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );

		// Endpoint title.
		add_filter( 'the_title', array( $this, 'endpoint_title' ), 10, 2 );

		// Enqueue frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_hf_service_sso', array( $this, 'ajax_sso' ) );
		add_action( 'wp_ajax_hf_service_change_password', array( $this, 'ajax_change_password' ) );
		add_action( 'wp_ajax_hf_service_cancel_request', array( $this, 'ajax_cancel_request' ) );
		add_action( 'wp_ajax_hf_service_upgrade_request', array( $this, 'ajax_upgrade_request' ) );
		add_action( 'wp_ajax_hf_service_usage', array( $this, 'ajax_get_usage' ) );
	}

	/**
	 * Register the rewrite endpoint.
	 *
	 * @return void
	 */
	public function register_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add menu item to My Account.
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public function add_menu_item( array $items ): array {
		// Insert before logout.
		$new_items = array();

		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) {
				$new_items[ self::ENDPOINT ] = __( 'Hosting Services', 'hostforge' );
			}
			$new_items[ $key ] = $label;
		}

		return $new_items;
	}

	/**
	 * Set the endpoint page title.
	 *
	 * @param string $title Page title.
	 * @param int    $id    Page ID.
	 * @return string
	 */
	public function endpoint_title( string $title, int $id = 0 ): string {
		global $wp_query;

		if ( ! is_admin() && is_main_query() && in_the_loop() && is_account_page() && isset( $wp_query->query_vars[ self::ENDPOINT ] ) ) {
			$value = $wp_query->query_vars[ self::ENDPOINT ];

			if ( empty( $value ) ) {
				return __( 'Hosting Services', 'hostforge' );
			}

			return __( 'Service Details', 'hostforge' );
		}

		return $title;
	}

	/**
	 * Render the endpoint content.
	 *
	 * @param string $value Endpoint value (service ID or empty for list).
	 * @return void
	 */
	public function render_endpoint( string $value = '' ): void {
		$service_id = absint( $value );

		if ( $service_id > 0 ) {
			$this->render_service_detail( $service_id );
		} else {
			$this->render_service_list();
		}
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( ! is_account_page() ) {
			return;
		}

		wp_enqueue_style(
			'hostforge-service-frontend',
			HOSTFORGE_PLUGIN_URL . 'assets/css/service-frontend.css',
			array(),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hostforge-service-frontend',
			HOSTFORGE_PLUGIN_URL . 'assets/js/service-frontend.js',
			array(),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hostforge-service-frontend',
			'hostforgeServiceFront',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_service_frontend_nonce' ),
				'i18n'    => array(
					'loading'           => __( 'Loading...', 'hostforge' ),
					'confirmCancel'     => __( 'Are you sure you want to request cancellation of this service?', 'hostforge' ),
					'cancelSuccess'     => __( 'Cancellation request submitted. Our team will review it shortly.', 'hostforge' ),
					'passwordChanged'   => __( 'Password changed successfully!', 'hostforge' ),
					'passwordMinLength' => __( 'Password must be at least 8 characters.', 'hostforge' ),
					'upgradeSuccess'    => __( 'Upgrade/downgrade request submitted.', 'hostforge' ),
					'error'             => __( 'An error occurred. Please try again.', 'hostforge' ),
				),
			)
		);
	}

	/**
	 * Render the service list for current user.
	 *
	 * @return void
	 */
	private function render_service_list(): void {
		$user_id = get_current_user_id();

		$query_args = array(
			'post_type'      => 'hf_service',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_hf_user_id',
					'value' => $user_id,
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		/**
		 * Filter the query args for listing user services on the frontend.
		 *
		 * Allows modification of the WP_Query arguments used to
		 * fetch services in the My Account hosting-services page.
		 *
		 * @since 1.0.0
		 *
		 * @param array $query_args WP_Query arguments.
		 * @param int   $user_id   Current user ID.
		 */
		$query_args = apply_filters( 'hostforge_service_list_query', $query_args, $user_id );

		$services = get_posts( $query_args );

		$template = hf_locate_template( 'frontend/service-list.php' );
		if ( $template ) {
			include $template;
		}
	}

	/**
	 * Render a single service detail page.
	 *
	 * @param int $service_id Service post ID.
	 * @return void
	 */
	private function render_service_detail( int $service_id ): void {
		$user_id = get_current_user_id();
		$service = get_post( $service_id );

		if ( ! $service || 'hf_service' !== $service->post_type ) {
			echo '<p>' . esc_html__( 'Service not found.', 'hostforge' ) . '</p>';
			return;
		}

		// Verify ownership.
		$service_user = absint( get_post_meta( $service_id, '_hf_user_id', true ) );
		if ( $service_user !== $user_id ) {
			echo '<p>' . esc_html__( 'You do not have access to this service.', 'hostforge' ) . '</p>';
			return;
		}

		$meta = array();
		foreach ( array_keys( HF_Auto_Provisioning_Module::get_service_meta_keys() ) as $key ) {
			$meta[ $key ] = get_post_meta( $service_id, $key, true );
		}

		$product = ! empty( $meta['_hf_product_id'] ) ? wc_get_product( absint( $meta['_hf_product_id'] ) ) : null;

		/**
		 * Filter service data before rendering the frontend detail page.
		 *
		 * Allows modification or enrichment of the service meta values
		 * passed to the service-detail template.
		 *
		 * @since 1.0.0
		 *
		 * @param array    $meta       Service meta values.
		 * @param int      $service_id Service post ID.
		 * @param \WP_Post $service    Service post object.
		 */
		$meta = apply_filters( 'hostforge_service_detail_data', $meta, $service_id, $service );

		// Determine available actions for this service.
		$status          = ! empty( $meta['_hf_status'] ) ? $meta['_hf_status'] : 'pending';
		$service_actions = array();

		if ( 'active' === $status ) {
			$service_actions = array(
				'sso'      => __( 'Login to Panel', 'hostforge' ),
				'password' => __( 'Change Password', 'hostforge' ),
				'upgrade'  => __( 'Upgrade/Downgrade', 'hostforge' ),
				'cancel'   => __( 'Request Cancellation', 'hostforge' ),
			);
		}

		/**
		 * Filter the available service actions on the frontend detail page.
		 *
		 * Allows adding, removing, or modifying the action buttons
		 * shown to the customer on their service detail page.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $service_actions Associative array of action_slug => label.
		 * @param int    $service_id      Service post ID.
		 * @param string $status          Current service status.
		 * @param array  $meta            Service meta values.
		 */
		$service_actions = apply_filters( 'hostforge_service_actions', $service_actions, $service_id, $status, $meta );

		$template = hf_locate_template( 'frontend/service-detail.php' );
		if ( $template ) {
			include $template;
		}
	}

	/**
	 * AJAX: Get SSO URL for a service.
	 *
	 * @return void
	 */
	public function ajax_sso(): void {
		check_ajax_referer( 'hf_service_frontend_nonce', 'nonce' );

		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$user_id    = get_current_user_id();

		if ( ! $this->verify_ownership( $service_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'hostforge' ) ) );
		}

		$server_id = absint( get_post_meta( $service_id, '_hf_server_id', true ) );
		$username  = get_post_meta( $service_id, '_hf_panel_username', true );

		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$server_module  = $module_manager->get_module( 'server-manager' );

		if ( ! $server_module || ! method_exists( $server_module, 'get_provider' ) ) {
			wp_send_json_error( array( 'message' => __( 'Server module not available.', 'hostforge' ) ) );
		}

		$provider = $server_module->get_provider( $server_id );

		if ( ! $provider ) {
			wp_send_json_error( array( 'message' => __( 'Server provider not available.', 'hostforge' ) ) );
		}

		$result = $provider->get_sso_url( $username );

		if ( $result['success'] ) {
			/**
			 * Filter the SSO URL before returning it to the frontend.
			 *
			 * Allows modification of the panel login URL, e.g. to append
			 * tracking parameters or redirect through a proxy.
			 *
			 * @since 1.0.0
			 *
			 * @param string $url        SSO URL from the panel provider.
			 * @param int    $service_id Service post ID.
			 * @param string $username   Panel username.
			 * @param int    $user_id    WordPress user ID.
			 */
			$sso_url = apply_filters( 'hostforge_service_sso_url', $result['url'], $service_id, $username, $user_id );

			wp_send_json_success( array( 'url' => $sso_url ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Could not generate SSO URL.', 'hostforge' ) ) );
		}
	}

	/**
	 * AJAX: Change panel password.
	 *
	 * @return void
	 */
	public function ajax_change_password(): void {
		check_ajax_referer( 'hf_service_frontend_nonce', 'nonce' );

		$service_id   = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$new_password = isset( $_POST['new_password'] ) ? sanitize_text_field( wp_unslash( $_POST['new_password'] ) ) : '';
		$user_id      = get_current_user_id();

		if ( ! $this->verify_ownership( $service_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'hostforge' ) ) );
		}

		if ( strlen( $new_password ) < 8 ) {
			wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters.', 'hostforge' ) ) );
		}

		$server_id = absint( get_post_meta( $service_id, '_hf_server_id', true ) );
		$username  = get_post_meta( $service_id, '_hf_panel_username', true );

		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$server_module  = $module_manager->get_module( 'server-manager' );

		if ( ! $server_module || ! method_exists( $server_module, 'get_provider' ) ) {
			wp_send_json_error( array( 'message' => __( 'Server module not available.', 'hostforge' ) ) );
		}

		$provider = $server_module->get_provider( $server_id );

		if ( ! $provider ) {
			wp_send_json_error( array( 'message' => __( 'Server provider not available.', 'hostforge' ) ) );
		}

		$result = $provider->change_password( $username, $new_password );

		if ( $result['success'] ) {
			// Update encrypted password.
			update_post_meta( $service_id, '_hf_panel_password', HF_Encryption::encrypt( $new_password ) );
			wp_send_json_success( array( 'message' => __( 'Password changed successfully.', 'hostforge' ) ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ?? __( 'Could not change password.', 'hostforge' ) ) );
		}
	}

	/**
	 * AJAX: Submit cancellation request.
	 *
	 * @return void
	 */
	public function ajax_cancel_request(): void {
		check_ajax_referer( 'hf_service_frontend_nonce', 'nonce' );

		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$reason     = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
		$user_id    = get_current_user_id();

		if ( ! $this->verify_ownership( $service_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'hostforge' ) ) );
		}

		$status = get_post_meta( $service_id, '_hf_status', true );

		if ( in_array( $status, array( 'terminated', 'cancelled' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'This service is already terminated or cancelled.', 'hostforge' ) ) );
		}

		// Check if already requested.
		if ( get_post_meta( $service_id, '_hf_cancel_requested_at', true ) ) {
			wp_send_json_error( array( 'message' => __( 'A cancellation request is already pending.', 'hostforge' ) ) );
		}

		update_post_meta( $service_id, '_hf_cancel_requested_at', current_time( 'mysql' ) );
		update_post_meta( $service_id, '_hf_cancel_reason', $reason );

		wp_send_json_success(
			array( 'message' => __( 'Cancellation request submitted. Our team will review it shortly.', 'hostforge' ) )
		);
	}

	/**
	 * AJAX: Submit upgrade/downgrade request.
	 *
	 * @return void
	 */
	public function ajax_upgrade_request(): void {
		check_ajax_referer( 'hf_service_frontend_nonce', 'nonce' );

		$service_id  = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$new_package = isset( $_POST['new_package'] ) ? sanitize_text_field( wp_unslash( $_POST['new_package'] ) ) : '';
		$user_id     = get_current_user_id();

		if ( ! $this->verify_ownership( $service_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'hostforge' ) ) );
		}

		if ( empty( $new_package ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a package.', 'hostforge' ) ) );
		}

		$status = get_post_meta( $service_id, '_hf_status', true );

		if ( 'active' !== $status ) {
			wp_send_json_error( array( 'message' => __( 'Only active services can be upgraded or downgraded.', 'hostforge' ) ) );
		}

		update_post_meta( $service_id, '_hf_pending_package', $new_package );

		// Enqueue the change.
		as_enqueue_async_action(
			'hostforge_change_package_service',
			array( $service_id ),
			'hostforge-provisioning'
		);

		wp_send_json_success(
			array( 'message' => __( 'Package change request submitted. It will be processed shortly.', 'hostforge' ) )
		);
	}

	/**
	 * AJAX: Get usage stats for a service.
	 *
	 * @return void
	 */
	public function ajax_get_usage(): void {
		check_ajax_referer( 'hf_service_frontend_nonce', 'nonce' );

		$service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
		$user_id    = get_current_user_id();

		if ( ! $this->verify_ownership( $service_id, $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'hostforge' ) ) );
		}

		// Check cache (15 minutes).
		$cache_key = '_hf_usage_cache_' . $service_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			wp_send_json_success( array( 'usage' => $cached ) );
		}

		$server_id = absint( get_post_meta( $service_id, '_hf_server_id', true ) );
		$username  = get_post_meta( $service_id, '_hf_panel_username', true );

		$module_manager = \HostForge\HostForge::instance()->module_manager();
		$server_module  = $module_manager->get_module( 'server-manager' );

		if ( ! $server_module || ! method_exists( $server_module, 'get_provider' ) ) {
			wp_send_json_error( array( 'message' => __( 'Server module not available.', 'hostforge' ) ) );
		}

		$provider = $server_module->get_provider( $server_id );

		if ( ! $provider ) {
			wp_send_json_error( array( 'message' => __( 'Server provider not available.', 'hostforge' ) ) );
		}

		$result = $provider->get_account_usage( $username );

		if ( $result['success'] ) {
			set_transient( $cache_key, $result['data'], 15 * MINUTE_IN_SECONDS );
			wp_send_json_success( array( 'usage' => $result['data'] ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Could not fetch usage data.', 'hostforge' ) ) );
		}
	}

	/**
	 * Verify that the current user owns the service.
	 *
	 * @param int $service_id Service post ID.
	 * @param int $user_id    User ID.
	 * @return bool
	 */
	private function verify_ownership( int $service_id, int $user_id ): bool {
		if ( $service_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		$service = get_post( $service_id );
		if ( ! $service || 'hf_service' !== $service->post_type ) {
			return false;
		}

		$service_user = absint( get_post_meta( $service_id, '_hf_user_id', true ) );

		return $service_user === $user_id;
	}
}
