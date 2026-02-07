<?php
/**
 * CAPTCHA Integration.
 *
 * Adds Cloudflare Turnstile or Google reCAPTCHA to login, registration,
 * checkout and ticket forms.
 *
 * @package HostForge\Modules\Security
 */

namespace HostForge\Modules\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Class HF_Captcha
 */
class HF_Captcha {

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

		if ( 'yes' !== ( ! empty( $settings['captcha_enabled'] ) ? $settings['captcha_enabled'] : 'no' ) ) {
			return;
		}

		$site_key   = ! empty( $settings['captcha_site_key'] ) ? $settings['captcha_site_key'] : '';
		$secret_key = ! empty( $settings['captcha_secret_key'] ) ? $settings['captcha_secret_key'] : '';

		if ( empty( $site_key ) || empty( $secret_key ) ) {
			return;
		}

		// Login form.
		if ( 'yes' === ( ! empty( $settings['captcha_on_login'] ) ? $settings['captcha_on_login'] : 'no' ) ) {
			add_action( 'login_form', array( $this, 'render_captcha_widget' ) );
			add_action( 'woocommerce_login_form', array( $this, 'render_captcha_widget' ) );
			add_filter( 'authenticate', array( $this, 'verify_login_captcha' ), 99, 3 );
		}

		// Registration form.
		if ( 'yes' === ( ! empty( $settings['captcha_on_register'] ) ? $settings['captcha_on_register'] : 'yes' ) ) {
			add_action( 'register_form', array( $this, 'render_captcha_widget' ) );
			add_action( 'woocommerce_register_form', array( $this, 'render_captcha_widget' ) );
			add_filter( 'registration_errors', array( $this, 'verify_registration_captcha' ), 99, 3 );
			add_filter( 'woocommerce_process_registration_errors', array( $this, 'verify_wc_registration_captcha' ), 99 );
		}

		// Checkout form.
		if ( 'yes' === ( ! empty( $settings['captcha_on_checkout'] ) ? $settings['captcha_on_checkout'] : 'no' ) ) {
			add_action( 'woocommerce_review_order_before_submit', array( $this, 'render_captcha_widget' ) );
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'verify_checkout_captcha' ), 10, 2 );
		}

		// Enqueue scripts.
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_captcha_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_captcha_scripts' ) );
	}

	/**
	 * Enqueue the CAPTCHA provider's JavaScript.
	 *
	 * @return void
	 */
	public function enqueue_captcha_scripts(): void {
		$settings = $this->module->get_security_settings();
		$provider = ! empty( $settings['captcha_provider'] ) ? $settings['captcha_provider'] : 'turnstile';

		if ( 'turnstile' === $provider ) {
			wp_enqueue_script(
				'hf-turnstile',
				'https://challenges.cloudflare.com/turnstile/v0/api.js',
				array(),
				null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
				true
			);
		} else {
			wp_enqueue_script(
				'hf-recaptcha',
				'https://www.google.com/recaptcha/api.js',
				array(),
				null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
				true
			);
		}
	}

	/**
	 * Render the CAPTCHA widget HTML.
	 *
	 * @return void
	 */
	public function render_captcha_widget(): void {
		$settings = $this->module->get_security_settings();
		$provider = ! empty( $settings['captcha_provider'] ) ? $settings['captcha_provider'] : 'turnstile';
		$site_key = ! empty( $settings['captcha_site_key'] ) ? $settings['captcha_site_key'] : '';

		if ( empty( $site_key ) ) {
			return;
		}

		echo '<div class="hf-captcha-wrapper" style="margin: 10px 0;">';

		if ( 'turnstile' === $provider ) {
			printf(
				'<div class="cf-turnstile" data-sitekey="%s" data-theme="auto"></div>',
				esc_attr( $site_key )
			);
		} else {
			printf(
				'<div class="g-recaptcha" data-sitekey="%s"></div>',
				esc_attr( $site_key )
			);
		}

		echo '</div>';
	}

	/**
	 * Verify CAPTCHA on login.
	 *
	 * @param null|\WP_User|\WP_Error $user     User or error.
	 * @param string                  $username Username.
	 * @param string                  $password Password.
	 * @return null|\WP_User|\WP_Error
	 */
	public function verify_login_captcha( $user, string $username, string $password ) {
		if ( empty( $username ) ) {
			return $user;
		}

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( ! $this->verify_captcha_response() ) {
			return new \WP_Error(
				'hf_captcha_failed',
				__( 'CAPTCHA verification failed. Please try again.', 'hostforge' )
			);
		}

		return $user;
	}

	/**
	 * Verify CAPTCHA on WP registration.
	 *
	 * @param \WP_Error $errors   Errors.
	 * @param string    $sanitized_user_login Login.
	 * @param string    $user_email Email.
	 * @return \WP_Error
	 */
	public function verify_registration_captcha( \WP_Error $errors, string $sanitized_user_login, string $user_email ): \WP_Error {
		if ( ! $this->verify_captcha_response() ) {
			$errors->add(
				'hf_captcha_failed',
				__( '<strong>Error:</strong> CAPTCHA verification failed. Please try again.', 'hostforge' )
			);
		}

		return $errors;
	}

	/**
	 * Verify CAPTCHA on WooCommerce registration.
	 *
	 * @param \WP_Error $errors Errors.
	 * @return \WP_Error
	 */
	public function verify_wc_registration_captcha( \WP_Error $errors ): \WP_Error {
		if ( ! $this->verify_captcha_response() ) {
			$errors->add(
				'hf_captcha_failed',
				__( 'CAPTCHA verification failed. Please try again.', 'hostforge' )
			);
		}

		return $errors;
	}

	/**
	 * Verify CAPTCHA on checkout.
	 *
	 * @param array     $data   Checkout data.
	 * @param \WP_Error $errors Errors.
	 * @return void
	 */
	public function verify_checkout_captcha( array $data, \WP_Error $errors ): void {
		if ( ! $this->verify_captcha_response() ) {
			$errors->add(
				'hf_captcha_failed',
				__( 'CAPTCHA verification failed. Please try again.', 'hostforge' )
			);
		}
	}

	/**
	 * Verify the CAPTCHA response with the provider's API.
	 *
	 * @return bool True if verification passed.
	 */
	private function verify_captcha_response(): bool {
		$settings   = $this->module->get_security_settings();
		$provider   = ! empty( $settings['captcha_provider'] ) ? $settings['captcha_provider'] : 'turnstile';
		$secret_key = ! empty( $settings['captcha_secret_key'] ) ? $settings['captcha_secret_key'] : '';

		if ( empty( $secret_key ) ) {
			return true;
		}

		// Get the response token.
		$token = '';

		if ( 'turnstile' === $provider ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$token = ! empty( $_POST['cf-turnstile-response'] )
				? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) )
				: '';
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$token = ! empty( $_POST['g-recaptcha-response'] )
				? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) )
				: '';
		}

		if ( empty( $token ) ) {
			return false;
		}

		// Verify with provider API.
		$verify_url = 'turnstile' === $provider
			? 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
			: 'https://www.google.com/recaptcha/api/siteverify';

		$response = wp_remote_post(
			$verify_url,
			array(
				'body'    => array(
					'secret'   => $secret_key,
					'response' => $token,
					'remoteip' => $this->get_visitor_ip(),
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			// On API error, allow the request through to avoid blocking legitimate users.
			return true;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return ! empty( $body['success'] ) && true === $body['success'];
	}

	/**
	 * Get the visitor's IP address.
	 *
	 * @return string
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
