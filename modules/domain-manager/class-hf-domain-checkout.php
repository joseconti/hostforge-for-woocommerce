<?php
/**
 * Domain Checkout Integration.
 *
 * Adds domain search widget and register/transfer/own flow
 * to the WooCommerce checkout when domain products are in cart.
 *
 * @package HostForge\Modules\DomainManager
 */

namespace HostForge\Modules\DomainManager;

use HostForge\Products\HF_Product_Types;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Domain_Checkout
 */
class HF_Domain_Checkout {

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
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'woocommerce_after_order_notes', array( $this, 'render_domain_fields' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_domain_fields' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_domain_fields' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ) );
	}

	/**
	 * Check if cart contains a domain product.
	 *
	 * @return bool
	 */
	private function cart_has_domain_product(): bool {
		if ( ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( $product && 'hf_domain' === $product->get_type() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render domain fields in checkout.
	 *
	 * @param \WC_Checkout $checkout Checkout instance.
	 * @return void
	 */
	public function render_domain_fields( $checkout ): void {
		if ( ! $this->cart_has_domain_product() ) {
			return;
		}

		$nonce = wp_create_nonce( 'hf_domain_search_nonce' );

		$checkout_fields = array(
			'register' => array(
				'label' => __( 'Register a new domain', 'hostforge' ),
			),
			'transfer' => array(
				'label' => __( 'Transfer an existing domain', 'hostforge' ),
			),
			'existing' => array(
				'label' => __( 'I already have a domain', 'hostforge' ),
			),
		);

		/**
		 * Filters the domain checkout form fields configuration.
		 *
		 * @since 1.0.0
		 *
		 * @param array         $checkout_fields Domain action fields with labels.
		 * @param \WC_Checkout  $checkout        The WooCommerce checkout instance.
		 */
		$checkout_fields = apply_filters( 'hostforge_domain_checkout_fields', $checkout_fields, $checkout );

		?>
		<div id="hf-domain-checkout" class="hf-domain-checkout">
			<h3><?php esc_html_e( 'Domain Configuration', 'hostforge' ); ?></h3>

			<div class="hf-domain-action-selector">
				<p class="form-row form-row-wide">
					<label><?php esc_html_e( 'What would you like to do with your domain?', 'hostforge' ); ?></label>
				</p>

				<p class="form-row form-row-wide">
					<label class="hf-radio-label">
						<input type="radio" name="hf_domain_action" value="register" checked="checked" />
						<span><?php esc_html_e( 'Register a new domain', 'hostforge' ); ?></span>
					</label>
				</p>
				<p class="form-row form-row-wide">
					<label class="hf-radio-label">
						<input type="radio" name="hf_domain_action" value="transfer" />
						<span><?php esc_html_e( 'Transfer an existing domain', 'hostforge' ); ?></span>
					</label>
				</p>
				<p class="form-row form-row-wide">
					<label class="hf-radio-label">
						<input type="radio" name="hf_domain_action" value="existing" />
						<span><?php esc_html_e( 'I already have a domain', 'hostforge' ); ?></span>
					</label>
				</p>
			</div>

			<!-- Register: Domain Search -->
			<div id="hf-domain-register-section" class="hf-domain-section">
				<div class="hf-domain-search-widget">
					<p class="form-row form-row-wide">
						<label for="hf_domain_keyword"><?php esc_html_e( 'Search for a domain', 'hostforge' ); ?></label>
						<span class="hf-domain-search-row">
							<input type="text"
								id="hf_domain_keyword"
								class="input-text"
								placeholder="<?php esc_attr_e( 'Enter your desired domain name...', 'hostforge' ); ?>"
							/>
							<button type="button"
								id="hf-domain-search-btn"
								class="button"
								data-nonce="<?php echo esc_attr( $nonce ); ?>"
							>
								<?php esc_html_e( 'Search', 'hostforge' ); ?>
							</button>
						</span>
					</p>
					<div id="hf-domain-search-results" class="hf-domain-search-results" style="display:none;"></div>
				</div>
			</div>

			<!-- Transfer: Domain + EPP Code -->
			<div id="hf-domain-transfer-section" class="hf-domain-section" style="display:none;">
				<?php
				woocommerce_form_field(
					'hf_transfer_domain',
					array(
						'type'        => 'text',
						'label'       => __( 'Domain to transfer', 'hostforge' ),
						'required'    => false,
						'class'       => array( 'form-row-wide' ),
						'placeholder' => 'example.com',
					)
				);

				woocommerce_form_field(
					'hf_domain_epp',
					array(
						'type'        => 'text',
						'label'       => __( 'EPP / Authorization Code', 'hostforge' ),
						'required'    => false,
						'class'       => array( 'form-row-wide' ),
						'placeholder' => __( 'Enter the EPP code from your current registrar', 'hostforge' ),
					)
				);
				?>
			</div>

			<!-- Existing: Just domain name -->
			<div id="hf-domain-existing-section" class="hf-domain-section" style="display:none;">
				<?php
				woocommerce_form_field(
					'hf_existing_domain',
					array(
						'type'        => 'text',
						'label'       => __( 'Your domain name', 'hostforge' ),
						'required'    => false,
						'class'       => array( 'form-row-wide' ),
						'placeholder' => 'example.com',
					)
				);
				?>
			</div>

			<!-- Hidden field for selected domain -->
			<input type="hidden" id="hf_domain_name" name="hf_domain_name" value="" />
		</div>
		<?php
	}

	/**
	 * Validate domain fields during checkout.
	 *
	 * @return void
	 */
	public function validate_domain_fields(): void {
		if ( ! $this->cart_has_domain_product() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
		$action = sanitize_text_field( wp_unslash( $_POST['hf_domain_action'] ?? '' ) );

		if ( ! in_array( $action, array( 'register', 'transfer', 'existing' ), true ) ) {
			wc_add_notice( __( 'Please select a domain action.', 'hostforge' ), 'error' );
			return;
		}

		$domain = '';

		switch ( $action ) {
			case 'register':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
				$domain = sanitize_text_field( wp_unslash( $_POST['hf_domain_name'] ?? '' ) );
				if ( empty( $domain ) ) {
					wc_add_notice( __( 'Please search and select a domain to register.', 'hostforge' ), 'error' );
					return;
				}
				break;

			case 'transfer':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
				$domain = sanitize_text_field( wp_unslash( $_POST['hf_transfer_domain'] ?? '' ) );
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
				$epp_code = sanitize_text_field( wp_unslash( $_POST['hf_domain_epp'] ?? '' ) );

				if ( empty( $domain ) ) {
					wc_add_notice( __( 'Please enter the domain name to transfer.', 'hostforge' ), 'error' );
					return;
				}
				if ( empty( $epp_code ) ) {
					wc_add_notice( __( 'EPP / Authorization code is required for domain transfers.', 'hostforge' ), 'error' );
					return;
				}
				break;

			case 'existing':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
				$domain = sanitize_text_field( wp_unslash( $_POST['hf_existing_domain'] ?? '' ) );
				if ( empty( $domain ) ) {
					wc_add_notice( __( 'Please enter your domain name.', 'hostforge' ), 'error' );
					return;
				}
				break;
		}

		// Validate domain format.
		if ( ! empty( $domain ) && ! preg_match( '/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?\.[a-zA-Z]{2,}$/', $domain ) ) {
			wc_add_notice( __( 'Please enter a valid domain name.', 'hostforge' ), 'error' );
		}

		$is_valid = true;

		/**
		 * Filters the domain checkout validation result.
		 *
		 * Return false to fail validation. Add notices via wc_add_notice() for error messages.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $is_valid Whether the validation passed so far.
		 * @param string $action   The domain action (register, transfer, existing).
		 * @param string $domain   The domain name entered.
		 */
		apply_filters( 'hostforge_domain_checkout_validation', $is_valid, $action, $domain );
	}

	/**
	 * Save domain fields to order meta.
	 *
	 * @param \WC_Order $order Order object.
	 * @param array     $data  Checkout data.
	 * @return void
	 */
	public function save_domain_fields( \WC_Order $order, array $data ): void {
		if ( ! $this->cart_has_domain_product() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
		$action = sanitize_text_field( wp_unslash( $_POST['hf_domain_action'] ?? '' ) );

		if ( ! in_array( $action, array( 'register', 'transfer', 'existing' ), true ) ) {
			return;
		}

		$order->update_meta_data( '_hf_domain_action', $action );

		switch ( $action ) {
			case 'register':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
				$domain = sanitize_text_field( wp_unslash( $_POST['hf_domain_name'] ?? '' ) );
				$order->update_meta_data( '_hf_domain_name', strtolower( $domain ) );
				break;

			case 'transfer':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
				$domain = sanitize_text_field( wp_unslash( $_POST['hf_transfer_domain'] ?? '' ) );
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
				$epp_code = sanitize_text_field( wp_unslash( $_POST['hf_domain_epp'] ?? '' ) );

				$order->update_meta_data( '_hf_domain_name', strtolower( $domain ) );
				$order->update_meta_data( '_hf_domain_epp', \HostForge\HF_Encryption::encrypt( $epp_code ) );
				break;

			case 'existing':
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
				$domain = sanitize_text_field( wp_unslash( $_POST['hf_existing_domain'] ?? '' ) );
				$order->update_meta_data( '_hf_domain_name', strtolower( $domain ) );
				break;
		}
	}

	/**
	 * Enqueue checkout assets when domain product is in cart.
	 *
	 * @return void
	 */
	public function enqueue_checkout_assets(): void {
		if ( ! is_checkout() || ! $this->cart_has_domain_product() ) {
			return;
		}

		wp_enqueue_style(
			'hf-domain-frontend',
			HOSTFORGE_PLUGIN_URL . 'assets/css/domain-frontend.css',
			array(),
			HOSTFORGE_VERSION
		);

		wp_enqueue_script(
			'hf-domain-frontend',
			HOSTFORGE_PLUGIN_URL . 'assets/js/domain-frontend.js',
			array(),
			HOSTFORGE_VERSION,
			true
		);

		wp_localize_script(
			'hf-domain-frontend',
			'hfDomainCheckout',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'hf_domain_search_nonce' ),
				'i18n'    => array(
					'searching'   => __( 'Searching...', 'hostforge' ),
					'available'   => __( 'Available', 'hostforge' ),
					'unavailable' => __( 'Unavailable', 'hostforge' ),
					'premium'     => __( 'Premium', 'hostforge' ),
					'select'      => __( 'Select', 'hostforge' ),
					'selected'    => __( 'Selected', 'hostforge' ),
					'perYear'     => __( '/year', 'hostforge' ),
					'noResults'   => __( 'No domains found. Try a different keyword.', 'hostforge' ),
					'error'       => __( 'An error occurred. Please try again.', 'hostforge' ),
				),
			)
		);
	}
}
