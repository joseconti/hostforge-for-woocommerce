<?php
/**
 * Domain Engine.
 *
 * Handles domain registration, transfer and renewal execution
 * triggered by WooCommerce orders and Action Scheduler.
 *
 * @package HostForge\Modules\DomainManager
 */

namespace HostForge\Modules\DomainManager;

use HostForge\Traits\HF_Has_Logs;
use HostForge\HF_Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Domain_Engine
 */
class HF_Domain_Engine {

	use HF_Has_Logs;

	/**
	 * Module instance.
	 *
	 * @var HF_Domain_Manager_Module
	 */
	private HF_Domain_Manager_Module $module;

	/**
	 * Constructor.
	 *
	 * @param HF_Domain_Manager_Module $module Module instance.
	 */
	public function __construct( HF_Domain_Manager_Module $module ) {
		$this->module = $module;
	}

	/**
	 * Get the module ID for logging.
	 *
	 * @return string
	 */
	protected function get_id(): string {
		return 'domain-manager';
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_order_completed' ) );
	}

	/**
	 * Process domains from a completed order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function on_order_completed( int $order_id ): void {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Prevent duplicate processing.
		if ( 'yes' === $order->get_meta( '_hf_domains_processed' ) ) {
			return;
		}

		$has_domain_items = false;

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! $product || 'hf_domain' !== $product->get_type() ) {
				continue;
			}

			$has_domain_items = true;
			$this->create_domain_from_order( $order, $item, $product );
		}

		if ( $has_domain_items ) {
			$order->update_meta_data( '_hf_domains_processed', 'yes' );
			$order->save();
		}
	}

	/**
	 * Create a domain CPT from an order item.
	 *
	 * @param \WC_Order      $order   Order object.
	 * @param \WC_Order_Item $item    Order item.
	 * @param \WC_Product    $product Product object.
	 * @return int|false Domain post ID or false on failure.
	 */
	private function create_domain_from_order( \WC_Order $order, \WC_Order_Item $item, \WC_Product $product ) {
		$domain_name   = $order->get_meta( '_hf_domain_name' );
		$raw_action    = $order->get_meta( '_hf_domain_action' );
		$domain_action = ! empty( $raw_action ) ? $raw_action : 'registration';

		if ( empty( $domain_name ) ) {
			$this->log_error(
				'No domain name found in order meta.',
				array( 'order_id' => $order->get_id() )
			);
			return false;
		}

		$auto_register = 'yes' === get_option( 'hf_domain_auto_register', 'yes' );

		// Create hf_domain post.
		$domain_id = wp_insert_post(
			array(
				'post_type'   => 'hf_domain',
				'post_title'  => $domain_name,
				'post_status' => 'publish',
				'post_author' => $order->get_customer_id(),
			)
		);

		if ( is_wp_error( $domain_id ) || ! $domain_id ) {
			$this->log_error(
				'Failed to create domain post.',
				array(
					'order_id'    => $order->get_id(),
					'domain_name' => $domain_name,
				)
			);
			return false;
		}

		// Default nameservers.
		$default_ns  = get_option( 'hf_domain_default_nameservers', '' );
		$nameservers = array_filter( array_map( 'trim', explode( "\n", $default_ns ) ) );

		// Registration years from product meta.
		$raw_years = absint( $product->get_meta( '_hf_registration_years' ) );
		$years     = $raw_years > 0 ? $raw_years : 1;

		// Set all meta.
		$meta = array(
			'_hf_domain_name'   => strtolower( $domain_name ),
			'_hf_registrar_id'  => get_option( 'hf_active_registrar', 'namecheap' ),
			'_hf_user_id'       => $order->get_customer_id(),
			'_hf_order_id'      => $order->get_id(),
			'_hf_product_id'    => $product->get_id(),
			'_hf_status'        => 'pending',
			'_hf_auto_renew'    => 'yes',
			'_hf_locked'        => 'no',
			'_hf_id_protection' => 'yes',
			'_hf_nameservers'   => wp_json_encode( $nameservers ),
			'_hf_type'          => $domain_action,
		);

		/**
		 * Filters the data extracted from a WooCommerce order for domain creation.
		 *
		 * @since 1.0.0
		 *
		 * @param array       $meta          Domain meta key => value pairs.
		 * @param \WC_Order   $order         The WooCommerce order.
		 * @param \WC_Product $product       The domain product.
		 * @param string      $domain_action The domain action (registration, transfer, existing).
		 */
		$meta = apply_filters( 'hostforge_domain_order_data', $meta, $order, $product, $domain_action );

		foreach ( $meta as $key => $value ) {
			update_post_meta( $domain_id, $key, $value );
		}

		// Store EPP code for transfers.
		if ( 'transfer' === $domain_action ) {
			$epp_code = $order->get_meta( '_hf_domain_epp' );
			if ( ! empty( $epp_code ) ) {
				update_post_meta( $domain_id, '_hf_epp_code', $epp_code );
			}
		}

		$this->log_info(
			'Domain created from order.',
			array(
				'domain_id'   => $domain_id,
				'domain_name' => $domain_name,
				'action'      => $domain_action,
				'order_id'    => $order->get_id(),
			)
		);

		// Queue async action based on domain action type.
		if ( $auto_register && 'existing' !== $domain_action ) {
			$hook = 'register' === $domain_action
				? 'hostforge_register_domain'
				: 'hostforge_transfer_domain';

			as_enqueue_async_action(
				$hook,
				array( $domain_id ),
				'hostforge-domain-manager'
			);
		}

		/**
		 * Fires when a domain is created from an order.
		 *
		 * @param int      $domain_id Domain post ID.
		 * @param \WC_Order $order    WooCommerce order.
		 */
		do_action( 'hostforge_domain_created', $domain_id, $order );

		return $domain_id;
	}

	/**
	 * Execute domain registration via registrar API.
	 *
	 * @param int $domain_id Domain post ID.
	 * @return void
	 */
	public function execute_register( int $domain_id ): void {
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );
		$order_id    = absint( get_post_meta( $domain_id, '_hf_order_id', true ) );

		if ( empty( $domain_name ) ) {
			$this->log_error( 'Cannot register: missing domain name.', array( 'domain_id' => $domain_id ) );
			$this->update_queue_error( $domain_id, 'register', __( 'Missing domain name.', 'hostforge' ) );
			return;
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			$this->log_error( 'Cannot register: no registrar configured.', array( 'domain_id' => $domain_id ) );
			$this->update_queue_error( $domain_id, 'register', __( 'No registrar configured.', 'hostforge' ) );
			return;
		}

		// Build contact from order billing info.
		$contact = $this->get_contact_from_order( $order_id );

		// Get nameservers.
		$ns_json     = get_post_meta( $domain_id, '_hf_nameservers', true );
		$nameservers = json_decode( ! empty( $ns_json ) ? $ns_json : '[]', true );

		// Get registration years from product.
		$product_id = absint( get_post_meta( $domain_id, '_hf_product_id', true ) );
		$product    = wc_get_product( $product_id );
		$raw_yrs    = $product ? absint( $product->get_meta( '_hf_registration_years' ) ) : 0;
		$years      = $raw_yrs > 0 ? $raw_yrs : 1;

		$register_params = array(
			'domain'      => $domain_name,
			'period'      => $years,
			'nameservers' => $nameservers,
			'contact'     => $contact,
		);

		/**
		 * Filters domain registration parameters before sending to the registrar API.
		 *
		 * @since 1.0.0
		 *
		 * @param array $register_params Registration parameters (domain, period, nameservers, contact).
		 * @param int   $domain_id       The domain post ID.
		 */
		$register_params = apply_filters( 'hostforge_domain_register_params', $register_params, $domain_id );

		$result = $registrar->register_domain( $register_params );

		if ( $result['success'] ) {
			$now = current_time( 'mysql' );
			update_post_meta( $domain_id, '_hf_status', 'active' );
			update_post_meta( $domain_id, '_hf_registration_date', $now );
			update_post_meta( $domain_id, '_hf_expiry_date', gmdate( 'Y-m-d H:i:s', strtotime( "+{$years} years" ) ) );
			update_post_meta( $domain_id, '_hf_last_synced', $now );

			if ( ! empty( $result['data']['domain_id'] ) ) {
				update_post_meta( $domain_id, '_hf_registrar_domain_id', sanitize_text_field( $result['data']['domain_id'] ) );
			}

			$this->update_queue_completed( $domain_id, 'register' );

			$this->log_info(
				'Domain registered successfully.',
				array(
					'domain_id'   => $domain_id,
					'domain_name' => $domain_name,
				)
			);

			/**
			 * Fires when a domain has been registered.
			 *
			 * @param int $domain_id Domain post ID.
			 */
			do_action( 'hostforge_domain_registered', $domain_id );
		} else {
			$error_msg = $result['message'] ?? __( 'Registration failed.', 'hostforge' );

			$this->log_error(
				'Domain registration failed.',
				array(
					'domain_id'   => $domain_id,
					'domain_name' => $domain_name,
					'error'       => $error_msg,
				)
			);

			$this->update_queue_error( $domain_id, 'register', $error_msg );
		}
	}

	/**
	 * Execute domain transfer via registrar API.
	 *
	 * @param int $domain_id Domain post ID.
	 * @return void
	 */
	public function execute_transfer( int $domain_id ): void {
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );
		$epp_code    = HF_Encryption::decrypt( get_post_meta( $domain_id, '_hf_epp_code', true ) );
		$order_id    = absint( get_post_meta( $domain_id, '_hf_order_id', true ) );

		if ( empty( $domain_name ) || empty( $epp_code ) ) {
			$this->log_error( 'Cannot transfer: missing domain name or EPP code.', array( 'domain_id' => $domain_id ) );
			$this->update_queue_error( $domain_id, 'transfer', __( 'Missing domain name or EPP code.', 'hostforge' ) );
			return;
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			$this->log_error( 'Cannot transfer: no registrar configured.', array( 'domain_id' => $domain_id ) );
			$this->update_queue_error( $domain_id, 'transfer', __( 'No registrar configured.', 'hostforge' ) );
			return;
		}

		$contact = $this->get_contact_from_order( $order_id );

		$transfer_params = array(
			'domain'   => $domain_name,
			'epp_code' => $epp_code,
			'contact'  => $contact,
		);

		/**
		 * Filters domain transfer parameters before sending to the registrar API.
		 *
		 * @since 1.0.0
		 *
		 * @param array $transfer_params Transfer parameters (domain, epp_code, contact).
		 * @param int   $domain_id       The domain post ID.
		 */
		$transfer_params = apply_filters( 'hostforge_domain_transfer_params', $transfer_params, $domain_id );

		$result = $registrar->transfer_domain( $transfer_params );

		if ( $result['success'] ) {
			update_post_meta( $domain_id, '_hf_status', 'pending' );
			update_post_meta( $domain_id, '_hf_last_synced', current_time( 'mysql' ) );
			$this->update_queue_completed( $domain_id, 'transfer' );

			$this->log_info(
				'Domain transfer initiated.',
				array(
					'domain_id'   => $domain_id,
					'domain_name' => $domain_name,
				)
			);

			/**
			 * Fires when a domain transfer has been initiated.
			 *
			 * @param int $domain_id Domain post ID.
			 */
			do_action( 'hostforge_domain_transfer_initiated', $domain_id );
		} else {
			$error_msg = $result['message'] ?? __( 'Transfer failed.', 'hostforge' );

			$this->log_error(
				'Domain transfer failed.',
				array(
					'domain_id'   => $domain_id,
					'domain_name' => $domain_name,
					'error'       => $error_msg,
				)
			);

			$this->update_queue_error( $domain_id, 'transfer', $error_msg );
		}
	}

	/**
	 * Execute domain renewal via registrar API.
	 *
	 * @param int $domain_id Domain post ID.
	 * @return void
	 */
	public function execute_renew( int $domain_id ): void {
		$domain_name = get_post_meta( $domain_id, '_hf_domain_name', true );

		if ( empty( $domain_name ) ) {
			$this->log_error( 'Cannot renew: missing domain name.', array( 'domain_id' => $domain_id ) );
			return;
		}

		$registrar = $this->module->get_registrar();

		if ( ! $registrar ) {
			$this->log_error( 'Cannot renew: no registrar configured.', array( 'domain_id' => $domain_id ) );
			return;
		}

		$renew_params = array(
			'domain' => $domain_name,
			'period' => 1,
		);

		/**
		 * Filters domain renewal parameters before sending to the registrar API.
		 *
		 * @since 1.0.0
		 *
		 * @param array $renew_params Renewal parameters (domain, period).
		 * @param int   $domain_id    The domain post ID.
		 */
		$renew_params = apply_filters( 'hostforge_domain_renew_params', $renew_params, $domain_id );

		$result = $registrar->renew_domain( $renew_params['domain'], $renew_params['period'] );

		if ( $result['success'] ) {
			// Extend expiry by 1 year.
			$current_expiry = get_post_meta( $domain_id, '_hf_expiry_date', true );
			$base_time      = ! empty( $current_expiry ) ? strtotime( $current_expiry ) : time();
			$new_expiry     = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year', $base_time ) );

			update_post_meta( $domain_id, '_hf_expiry_date', $new_expiry );
			update_post_meta( $domain_id, '_hf_status', 'active' );
			update_post_meta( $domain_id, '_hf_last_synced', current_time( 'mysql' ) );

			$this->log_info(
				'Domain renewed successfully.',
				array(
					'domain_id'   => $domain_id,
					'domain_name' => $domain_name,
					'new_expiry'  => $new_expiry,
				)
			);

			/**
			 * Fires when a domain has been renewed.
			 *
			 * @param int $domain_id Domain post ID.
			 */
			do_action( 'hostforge_domain_renewed', $domain_id );
		} else {
			$this->log_error(
				'Domain renewal failed.',
				array(
					'domain_id'   => $domain_id,
					'domain_name' => $domain_name,
					'error'       => $result['message'] ?? '',
				)
			);
		}
	}

	/**
	 * Build contact information from a WooCommerce order.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array
	 */
	private function get_contact_from_order( int $order_id ): array {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array();
		}

		return array(
			'first_name'   => $order->get_billing_first_name(),
			'last_name'    => $order->get_billing_last_name(),
			'organization' => $order->get_billing_company(),
			'address1'     => $order->get_billing_address_1(),
			'address2'     => $order->get_billing_address_2(),
			'city'         => $order->get_billing_city(),
			'state'        => $order->get_billing_state(),
			'postal_code'  => $order->get_billing_postcode(),
			'country'      => $order->get_billing_country(),
			'phone'        => $order->get_billing_phone(),
			'email'        => $order->get_billing_email(),
		);
	}

	/**
	 * Update domain queue with error.
	 *
	 * @param int    $domain_id Domain post ID.
	 * @param string $action    Queue action.
	 * @param string $error     Error message.
	 * @return void
	 */
	private function update_queue_error( int $domain_id, string $action, string $error ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}hf_domain_queue
				SET status = 'failed', last_error = %s, attempts = attempts + 1
				WHERE domain_id = %d AND action = %s AND status IN ('pending', 'processing')
				ORDER BY id DESC LIMIT 1",
				$error,
				$domain_id,
				$action
			)
		);
	}

	/**
	 * Mark domain queue entry as completed.
	 *
	 * @param int    $domain_id Domain post ID.
	 * @param string $action    Queue action.
	 * @return void
	 */
	private function update_queue_completed( int $domain_id, string $action ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}hf_domain_queue
				SET status = 'completed', completed_at = %s
				WHERE domain_id = %d AND action = %s AND status IN ('pending', 'processing')
				ORDER BY id DESC LIMIT 1",
				current_time( 'mysql' ),
				$domain_id,
				$action
			)
		);
	}
}
