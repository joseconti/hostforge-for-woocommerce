<?php
/**
 * Fraud Detection.
 *
 * Hooks into WooCommerce checkout to detect potentially fraudulent orders
 * based on IP country, email patterns and other signals.
 *
 * @package HostForge\Modules\Security
 */

namespace HostForge\Modules\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Fraud_Detection
 */
class HF_Fraud_Detection {

	/**
	 * Module instance.
	 *
	 * @var HF_Security_Module
	 */
	private HF_Security_Module $module;

	/**
	 * Constructor.
	 *
	 * @param HF_Security_Module $module Module instance.
	 */
	public function __construct( HF_Security_Module $module ) {
		$this->module = $module;
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		$settings = $this->module->get_security_settings();

		if ( 'yes' !== ( ! empty( $settings['fraud_enabled'] ) ? $settings['fraud_enabled'] : 'yes' ) ) {
			return;
		}

		// Validate checkout.
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'check_checkout_fraud' ), 10, 2 );

		// Flag order after creation.
		add_action( 'woocommerce_checkout_order_created', array( $this, 'assess_order_risk' ) );

		// Block registration from disposable emails.
		add_filter( 'registration_errors', array( $this, 'check_registration_email' ), 10, 3 );
	}

	/**
	 * Check for fraud signals during checkout validation.
	 *
	 * @param array     $data   Checkout data.
	 * @param \WP_Error $errors Validation errors.
	 * @return void
	 */
	public function check_checkout_fraud( array $data, \WP_Error $errors ): void {
		$settings = $this->module->get_security_settings();

		// Check blocked countries.
		$blocked_countries = ! empty( $settings['fraud_block_countries'] ) ? $settings['fraud_block_countries'] : '';

		if ( ! empty( $blocked_countries ) && ! empty( $data['billing_country'] ) ) {
			$blocked = array_map( 'trim', explode( ',', strtoupper( $blocked_countries ) ) );
			$blocked = array_filter( $blocked );

			if ( in_array( strtoupper( $data['billing_country'] ), $blocked, true ) ) {
				$errors->add(
					'hf_blocked_country',
					__( 'Orders from your country are not currently accepted. Please contact support.', 'hostforge' )
				);
				return;
			}
		}

		// Check blocked email patterns.
		$blocked_emails = ! empty( $settings['fraud_block_emails'] ) ? $settings['fraud_block_emails'] : '';

		if ( ! empty( $blocked_emails ) && ! empty( $data['billing_email'] ) ) {
			$patterns = array_map( 'trim', explode( "\n", $blocked_emails ) );
			$patterns = array_filter( $patterns );
			$email    = strtolower( $data['billing_email'] );

			foreach ( $patterns as $pattern ) {
				$pattern = strtolower( $pattern );

				// Domain pattern (e.g. @tempmail.com).
				if ( strpos( $pattern, '@' ) === 0 ) {
					$domain = substr( $email, strpos( $email, '@' ) );
					if ( $domain === $pattern ) {
						$errors->add(
							'hf_blocked_email',
							__( 'This email provider is not accepted. Please use a different email address.', 'hostforge' )
						);
						return;
					}
				}

				// Exact email match.
				if ( $email === $pattern ) {
					$errors->add(
						'hf_blocked_email',
						__( 'This email address is not accepted. Please contact support.', 'hostforge' )
					);
					return;
				}
			}
		}

		// Check for IP in blocklist.
		$ip = $this->get_visitor_ip();

		if ( ! empty( $ip ) ) {
			global $wpdb;

			$table = $wpdb->prefix . 'hf_ip_blocks';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$blocked = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table}
					WHERE ip_address = %s
					AND (expires_at IS NULL OR expires_at > %s)",
					$ip,
					current_time( 'mysql', true )
				)
			);

			if ( $blocked ) {
				$errors->add(
					'hf_blocked_ip',
					__( 'Your order could not be processed. Please contact support.', 'hostforge' )
				);
			}
		}
	}

	/**
	 * Assess order risk after creation and add notes.
	 *
	 * @param \WC_Order $order WooCommerce order.
	 * @return void
	 */
	public function assess_order_risk( \WC_Order $order ): void {
		$risk_score = 0;
		$risk_flags = array();

		// Check if billing and shipping countries differ.
		$billing_country  = $order->get_billing_country();
		$shipping_country = $order->get_shipping_country();

		if ( ! empty( $billing_country ) && ! empty( $shipping_country ) && $billing_country !== $shipping_country ) {
			$risk_score += 10;
			$risk_flags[] = __( 'Billing and shipping countries differ.', 'hostforge' );
		}

		// Check for free email providers on high-value orders.
		$email       = strtolower( $order->get_billing_email() );
		$order_total = (float) $order->get_total();
		$free_emails = array( 'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'mail.com', 'yandex.com', 'protonmail.com' );

		$email_domain = substr( $email, strpos( $email, '@' ) + 1 );

		if ( $order_total > 500 && in_array( $email_domain, $free_emails, true ) ) {
			$risk_score += 5;
			$risk_flags[] = __( 'High-value order with free email provider.', 'hostforge' );
		}

		// Check for multiple failed orders from same email.
		$recent_failed = $this->count_recent_failed_orders( $email );

		if ( $recent_failed >= 3 ) {
			$risk_score += 20;
			$risk_flags[] = sprintf(
				/* translators: %d: number of failed orders */
				__( '%d recent failed orders from same email.', 'hostforge' ),
				$recent_failed
			);
		}

		// Store risk data.
		if ( $risk_score > 0 ) {
			$order->update_meta_data( '_hf_risk_score', $risk_score );
			$order->update_meta_data( '_hf_risk_flags', $risk_flags );
			$order->save();

			// Add order note if risk is elevated.
			if ( $risk_score >= 15 ) {
				$note = __( 'HostForge Fraud Alert — Risk signals detected:', 'hostforge' ) . "\n";
				foreach ( $risk_flags as $flag ) {
					$note .= '- ' . $flag . "\n";
				}
				$note .= sprintf(
					/* translators: %d: risk score */
					__( 'Risk score: %d', 'hostforge' ),
					$risk_score
				);

				$order->add_order_note( $note );
			}
		}
	}

	/**
	 * Check registration email against blocked patterns.
	 *
	 * @param \WP_Error $errors   Registration errors.
	 * @param string    $sanitized_user_login User login.
	 * @param string    $user_email User email.
	 * @return \WP_Error
	 */
	public function check_registration_email( \WP_Error $errors, string $sanitized_user_login, string $user_email ): \WP_Error {
		$settings       = $this->module->get_security_settings();
		$blocked_emails = ! empty( $settings['fraud_block_emails'] ) ? $settings['fraud_block_emails'] : '';

		if ( empty( $blocked_emails ) || empty( $user_email ) ) {
			return $errors;
		}

		$patterns = array_map( 'trim', explode( "\n", $blocked_emails ) );
		$patterns = array_filter( $patterns );
		$email    = strtolower( $user_email );

		foreach ( $patterns as $pattern ) {
			$pattern = strtolower( $pattern );

			if ( strpos( $pattern, '@' ) === 0 ) {
				$domain = substr( $email, strpos( $email, '@' ) );
				if ( $domain === $pattern ) {
					$errors->add(
						'hf_blocked_email',
						__( '<strong>Error:</strong> This email provider is not accepted for registration.', 'hostforge' )
					);
					return $errors;
				}
			}

			if ( $email === $pattern ) {
				$errors->add(
					'hf_blocked_email',
					__( '<strong>Error:</strong> This email address is not accepted.', 'hostforge' )
				);
				return $errors;
			}
		}

		return $errors;
	}

	/**
	 * Count recent failed orders from the same email.
	 *
	 * @param string $email Email address.
	 * @return int
	 */
	private function count_recent_failed_orders( string $email ): int {
		$args = array(
			'status'       => array( 'wc-failed', 'wc-cancelled' ),
			'limit'        => -1,
			'return'       => 'ids',
			'date_created' => '>' . gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
		);

		// Use billing_email if HPOS supports it.
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			$args['billing_email'] = $email;
		}

		$orders = wc_get_orders( $args );

		return is_array( $orders ) ? count( $orders ) : 0;
	}

	/**
	 * Get the visitor's IP address.
	 *
	 * @return string IP address.
	 */
	private function get_visitor_ip(): string {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '';
		}

		return $ip;
	}
}
