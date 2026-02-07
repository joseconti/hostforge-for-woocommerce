<?php
/**
 * Maintenance Mode
 *
 * Handles maintenance mode for the main site (template installation).
 * Allows administrators to browse the front-end while visitors see a maintenance page.
 *
 * @package DemoWP
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DemoWP_Maintenance
 *
 * Manages maintenance mode functionality for the main site.
 *
 * @since 1.0.0
 */
class DemoWP_Maintenance {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Only run on the main site, not on clones.
		if ( defined( 'DEMOWP_IS_CLONE' ) && DEMOWP_IS_CLONE ) {
			return;
		}

		// Check maintenance mode on template_redirect (front-end only).
		add_action( 'template_redirect', array( $this, 'check_maintenance_mode' ) );

		// Add admin bar indicator.
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_indicator' ), 100 );

		// Add inline styles for admin bar indicator.
		add_action( 'wp_head', array( $this, 'admin_bar_styles' ) );
		add_action( 'admin_head', array( $this, 'admin_bar_styles' ) );
	}

	/**
	 * Check if maintenance mode is enabled and show maintenance page
	 */
	public function check_maintenance_mode() {
		// Skip if maintenance mode is not enabled.
		if ( ! self::is_enabled() ) {
			return;
		}

		// Allow logged-in administrators to browse normally.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		// Allow access to wp-login.php and wp-admin.
		if ( is_admin() ) {
			return;
		}

		// Allow AJAX requests.
		if ( wp_doing_ajax() ) {
			return;
		}

		// Allow REST API requests (for admin functionality).
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		// Allow cron requests.
		if ( wp_doing_cron() ) {
			return;
		}

		// Allow access to the demo endpoint so demos can still be created.
		$demo_endpoint = get_option( 'demowp_endpoint_slug', 'demo' );
		$request_uri   = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( strpos( $request_uri, '/' . $demo_endpoint ) !== false ) {
			return;
		}

		// Show maintenance page.
		$this->show_maintenance_page();
	}

	/**
	 * Display the maintenance page
	 */
	private function show_maintenance_page() {
		// Set 503 Service Unavailable header.
		status_header( 503 );
		header( 'Retry-After: 3600' );

		// Get custom message.
		$custom_message = get_option( 'demowp_maintenance_message', '' );

		if ( empty( $custom_message ) ) {
			$custom_message = __( 'We are currently performing scheduled maintenance. Please check back soon.', 'demowp' );
		}

		// Get site info.
		$site_name = get_bloginfo( 'name' );

		// Output maintenance page.
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<meta name="robots" content="noindex, nofollow">
			<title><?php echo esc_html( $site_name ); ?> - <?php esc_html_e( 'Maintenance', 'demowp' ); ?></title>
			<style>
				* {
					margin: 0;
					padding: 0;
					box-sizing: border-box;
				}
				body {
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
					background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
					min-height: 100vh;
					display: flex;
					align-items: center;
					justify-content: center;
					padding: 20px;
				}
				.maintenance-container {
					background: white;
					border-radius: 16px;
					padding: 60px 40px;
					max-width: 500px;
					width: 100%;
					text-align: center;
					box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
				}
				.maintenance-icon {
					font-size: 64px;
					margin-bottom: 24px;
				}
				.maintenance-title {
					font-size: 28px;
					font-weight: 700;
					color: #1a202c;
					margin-bottom: 16px;
				}
				.maintenance-message {
					font-size: 16px;
					color: #4a5568;
					line-height: 1.6;
					margin-bottom: 32px;
				}
				.maintenance-footer {
					font-size: 14px;
					color: #718096;
				}
				@media (max-width: 480px) {
					.maintenance-container {
						padding: 40px 24px;
					}
					.maintenance-title {
						font-size: 24px;
					}
				}
			</style>
		</head>
		<body>
			<div class="maintenance-container">
				<div class="maintenance-icon">&#128736;</div>
				<h1 class="maintenance-title"><?php esc_html_e( 'Under Maintenance', 'demowp' ); ?></h1>
				<p class="maintenance-message"><?php echo esc_html( $custom_message ); ?></p>
				<p class="maintenance-footer"><?php echo esc_html( $site_name ); ?></p>
			</div>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Add maintenance mode indicator to admin bar
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function add_admin_bar_indicator( $wp_admin_bar ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'demowp-maintenance-indicator',
				'title' => '<span class="demowp-maintenance-badge">' . esc_html__( 'Maintenance Mode', 'demowp' ) . '</span>',
				'href'  => admin_url( 'admin.php?page=demowp' ),
				'meta'  => array(
					'title' => __( 'Maintenance mode is active. Click to go to settings.', 'demowp' ),
				),
			)
		);
	}

	/**
	 * Output admin bar styles for maintenance indicator
	 */
	public function admin_bar_styles() {
		if ( ! self::is_enabled() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! is_admin_bar_showing() ) {
			return;
		}

		?>
		<style>
			#wp-admin-bar-demowp-maintenance-indicator .demowp-maintenance-badge {
				background: #dc3545;
				color: white;
				padding: 2px 8px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
			}
			#wp-admin-bar-demowp-maintenance-indicator:hover .demowp-maintenance-badge {
				background: #c82333;
			}
		</style>
		<?php
	}

	/**
	 * Check if maintenance mode is enabled
	 *
	 * @return bool True if maintenance mode is enabled.
	 */
	public static function is_enabled() {
		return (bool) get_option( 'demowp_maintenance_mode', false );
	}

	/**
	 * Enable maintenance mode
	 */
	public static function enable() {
		update_option( 'demowp_maintenance_mode', true );
	}

	/**
	 * Disable maintenance mode
	 */
	public static function disable() {
		update_option( 'demowp_maintenance_mode', false );
	}

	/**
	 * Toggle maintenance mode
	 *
	 * @return bool New state (true = enabled, false = disabled).
	 */
	public static function toggle() {
		$current = self::is_enabled();
		$new_state = ! $current;

		update_option( 'demowp_maintenance_mode', $new_state );

		return $new_state;
	}
}
