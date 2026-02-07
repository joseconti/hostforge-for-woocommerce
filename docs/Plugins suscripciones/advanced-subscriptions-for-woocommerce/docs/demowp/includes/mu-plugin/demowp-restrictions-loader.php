<?php
/**
 * DemoWP Restrictions Loader (MU-Plugin)
 *
 * This file is copied to wp-content/mu-plugins/ in clone installations
 * to ensure restrictions are always active and cannot be disabled.
 *
 * @package DemoWP
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only run in clone installations.
if ( ! defined( 'DEMOWP_IS_CLONE' ) || ! DEMOWP_IS_CLONE ) {
	return;
}

/**
 * DemoWP Clone Autologin
 *
 * Handles automatic login for demo users.
 * Tokens are stored in user_meta and are reusable (like Temporary Login Without Password).
 * This eliminates race conditions with parallel requests.
 *
 * @since 1.0.0
 */
class DemoWP_Clone_Autologin {

	/**
	 * User meta key for autologin token
	 *
	 * @var string
	 */
	const TOKEN_META_KEY = '_demowp_autologin_token';

	/**
	 * Initialize autologin system
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		// Process on init hook (works for wp-admin and frontend).
		add_action( 'init', array( __CLASS__, 'process_autologin' ), 1 );
	}

	/**
	 * Process autologin token
	 *
	 * Looks up the user by token in user_meta table.
	 * Token is reusable - not deleted after use.
	 *
	 * @since 1.0.0
	 */
	public static function process_autologin() {
		// Check for token in URL.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['demowp_token'] ) || empty( $_GET['demowp_token'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token = sanitize_text_field( wp_unslash( $_GET['demowp_token'] ) );

		self::log( 'Token found in URL, processing autologin...' );

		// Validate token format (128 hex characters).
		if ( ! preg_match( '/^[a-f0-9]{128}$/', $token ) ) {
			self::log( 'Invalid token format: ' . substr( $token, 0, 20 ) . '...' );
			return;
		}

		// If user is already logged in, just redirect without processing token.
		if ( is_user_logged_in() ) {
			self::log( 'User already logged in (ID: ' . get_current_user_id() . '), redirecting to clean URL...' );
			wp_safe_redirect( admin_url() );
			exit;
		}

		// Find user by token in user_meta.
		$user = self::get_user_by_token( $token );

		if ( ! $user ) {
			self::log( 'No user found for token: ' . substr( $token, 0, 16 ) . '...' );
			return;
		}

		self::log( 'User found: ' . $user->user_login . ' (ID: ' . $user->ID . ')' );

		// Check if headers already sent (would prevent cookies from being set).
		if ( headers_sent( $file, $line ) ) {
			self::log( 'ERROR: Headers already sent in ' . $file . ' on line ' . $line );
			return;
		}

		// Perform login.
		wp_set_current_user( $user->ID, $user->user_login );

		self::log( 'Setting auth cookie for user ' . $user->ID );
		wp_set_auth_cookie( $user->ID, false );

		// Fire login action for compatibility.
		do_action( 'wp_login', $user->user_login, $user );

		// Set flag to show welcome notice.
		set_transient( 'demowp_show_welcome_' . $user->ID, true, 60 );

		self::log( 'Login successful! Redirecting to admin...' );

		// Redirect to admin without token in URL.
		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Get user by autologin token
	 *
	 * Searches user_meta for the token and returns the user if found and valid.
	 *
	 * @param string $token The autologin token.
	 * @return WP_User|false User object or false if not found.
	 */
	private static function get_user_by_token( $token ) {
		global $wpdb;

		// Query user by token in usermeta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$user_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				self::TOKEN_META_KEY,
				$token
			)
		);

		if ( empty( $user_id ) ) {
			return false;
		}

		// Get user object.
		$user = get_user_by( 'ID', $user_id );

		return $user ? $user : false;
	}

	/**
	 * Log autologin messages
	 *
	 * @param string $message The message to log.
	 */
	private static function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[DemoWP Autologin] ' . $message );
		}
	}
}

// Initialize autologin early.
DemoWP_Clone_Autologin::init();

/**
 * DemoWP Clone Restrictions
 *
 * Applies security restrictions in clone installations.
 * This is a standalone version that doesn't require the main plugin.
 *
 * @since 1.0.0
 */
class DemoWP_Clone_Restrictions {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Remove restricted menus.
		add_action( 'admin_menu', array( $this, 'remove_restricted_menus' ), 999 );

		// Block restricted pages.
		add_action( 'admin_init', array( $this, 'block_restricted_pages' ) );

		// Filter capabilities.
		add_filter( 'user_has_cap', array( $this, 'filter_capabilities' ), 10, 4 );

		// Show demo notice.
		add_action( 'admin_notices', array( $this, 'show_demo_notice' ) );

		// Block plugin/theme uploads.
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'block_plugin_theme_uploads' ) );

		// Disable update checks.
		add_filter( 'pre_site_transient_update_plugins', array( $this, 'disable_updates' ) );
		add_filter( 'pre_site_transient_update_themes', array( $this, 'disable_updates' ) );
		add_filter( 'pre_site_transient_update_core', array( $this, 'disable_updates' ) );

		// Filter plugin action links.
		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 4 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 4 );

		// Add demo mode body class.
		add_filter( 'admin_body_class', array( $this, 'add_demo_body_class' ) );

		// Enqueue admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Block AJAX plugin actions.
		add_action( 'admin_init', array( $this, 'block_plugin_ajax_actions' ) );
	}

	/**
	 * Remove restricted menu items
	 *
	 * @since 1.0.0
	 */
	public function remove_restricted_menus() {
		// Plugins submenu items - remove install and editor.
		remove_submenu_page( 'plugins.php', 'plugin-install.php' );
		remove_submenu_page( 'plugins.php', 'plugin-editor.php' );

		// Themes submenu items - remove install and editor.
		remove_submenu_page( 'themes.php', 'theme-install.php' );
		remove_submenu_page( 'themes.php', 'theme-editor.php' );

		// Updates.
		remove_submenu_page( 'index.php', 'update-core.php' );

		// Remove update count from menu.
		global $menu;
		if ( is_array( $menu ) ) {
			foreach ( $menu as $key => $item ) {
				if ( isset( $item[2] ) && 'update-core.php' === $item[2] ) {
					unset( $menu[ $key ] );
				}
			}
		}
	}

	/**
	 * Block access to restricted admin pages
	 *
	 * @since 1.0.0
	 */
	public function block_restricted_pages() {
		global $pagenow;

		$blocked_pages = array(
			'plugin-install.php',
			'plugin-editor.php',
			'theme-install.php',
			'theme-editor.php',
			'update-core.php',
			'update.php',
		);

		if ( in_array( $pagenow, $blocked_pages, true ) ) {
			wp_die(
				'<h1>' . esc_html__( 'Access Restricted', 'demowp' ) . '</h1>' .
				'<p>' . esc_html__( 'This feature is disabled in demo mode.', 'demowp' ) . '</p>',
				esc_html__( 'Demo Mode', 'demowp' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}

		// Block plugin deletion (but allow activation/deactivation).
		if ( 'plugins.php' === $pagenow ) {
			$blocked_actions = array( 'delete-selected' );

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';

			if ( in_array( $action, $blocked_actions, true ) ) {
				wp_die(
					'<h1>' . esc_html__( 'Access Restricted', 'demowp' ) . '</h1>' .
					'<p>' . esc_html__( 'You cannot delete plugins in demo mode.', 'demowp' ) . '</p>',
					esc_html__( 'Demo Mode', 'demowp' ),
					array(
						'response'  => 403,
						'back_link' => true,
					)
				);
			}
		}
	}

	/**
	 * Block plugin-related AJAX actions
	 *
	 * @since 1.0.0
	 */
	public function block_plugin_ajax_actions() {
		// Block install, delete, update, and edit AJAX actions.
		// Allow activation/deactivation AJAX actions.
		$blocked_ajax_actions = array(
			'delete-plugin',
			'delete-theme',
			'install-plugin',
			'install-theme',
			'update-plugin',
			'update-theme',
			'edit-theme-plugin-file',
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );

			if ( in_array( $action, $blocked_ajax_actions, true ) ) {
				wp_send_json_error(
					array( 'message' => __( 'This action is disabled in demo mode.', 'demowp' ) ),
					403
				);
			}
		}
	}

	/**
	 * Filter user capabilities
	 *
	 * @since 1.0.0
	 * @param array   $allcaps All capabilities.
	 * @param array   $caps    Required capabilities.
	 * @param array   $args    Additional arguments.
	 * @param WP_User $user    User object.
	 * @return array Modified capabilities.
	 */
	public function filter_capabilities( $allcaps, $caps, $args, $user ) {
		// Block install, delete, edit, and update capabilities.
		// Allow activate_plugins, switch_themes, and edit_theme_options
		// so users can activate/deactivate plugins and switch themes.
		$blocked_caps = array(
			'install_plugins',
			'delete_plugins',
			'install_themes',
			'delete_themes',
			'edit_plugins',
			'edit_themes',
			'update_plugins',
			'update_themes',
			'update_core',
			'edit_files',
		);

		foreach ( $blocked_caps as $cap ) {
			$allcaps[ $cap ] = false;
		}

		return $allcaps;
	}

	/**
	 * Show demo mode notice in admin
	 *
	 * @since 1.0.0
	 */
	public function show_demo_notice() {
		// Only show on main admin pages, not AJAX.
		if ( wp_doing_ajax() ) {
			return;
		}

		// Get expiration info.
		$expires_at     = get_option( 'demowp_clone_expires' );
		$time_remaining = '';

		if ( $expires_at ) {
			$remaining_seconds = strtotime( $expires_at ) - time();
			if ( $remaining_seconds > 0 ) {
				if ( $remaining_seconds >= 3600 ) {
					$hours          = floor( $remaining_seconds / 3600 );
					$time_remaining = sprintf(
						/* translators: %d: number of hours */
						_n( '%d hour', '%d hours', $hours, 'demowp' ),
						$hours
					);
				} elseif ( $remaining_seconds >= 60 ) {
					$minutes        = floor( $remaining_seconds / 60 );
					$time_remaining = sprintf(
						/* translators: %d: number of minutes */
						_n( '%d minute', '%d minutes', $minutes, 'demowp' ),
						$minutes
					);
				} else {
					$time_remaining = __( 'less than a minute', 'demowp' );
				}
			}
		}

		// Get custom welcome message.
		$welcome_message = get_option( 'demowp_welcome_message', '' );

		?>
		<div class="notice notice-warning demowp-demo-notice" style="border-left-color: #f59e0b;">
			<div style="display: flex; align-items: flex-start; gap: 12px; padding: 8px 0;">
				<span style="font-size: 20px;">&#129514;</span>
				<div>
					<p style="margin: 0 0 4px; font-weight: 600;">
						<?php esc_html_e( 'Demo Mode Active', 'demowp' ); ?>
					</p>
					<?php if ( ! empty( $welcome_message ) ) : ?>
						<p style="margin: 0 0 4px;">
							<?php echo esc_html( $welcome_message ); ?>
						</p>
					<?php else : ?>
						<p style="margin: 0 0 4px;">
							<?php esc_html_e( 'This is a temporary demo installation. Feel free to explore!', 'demowp' ); ?>
						</p>
					<?php endif; ?>
					<?php if ( $time_remaining ) : ?>
						<p style="margin: 0; font-size: 13px; color: #6b7280;">
							<?php
							printf(
								/* translators: %s: time remaining */
								esc_html__( 'Time remaining: %s', 'demowp' ),
								'<strong>' . esc_html( $time_remaining ) . '</strong>'
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Block plugin/theme file uploads
	 *
	 * @since 1.0.0
	 * @param array $file File data.
	 * @return array Modified file data.
	 */
	public function block_plugin_theme_uploads( $file ) {
		$blocked_types = array(
			'application/zip',
			'application/x-zip-compressed',
			'application/x-zip',
			'application/octet-stream',
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';

		$upload_actions = array( 'upload-plugin', 'upload-theme' );

		if ( in_array( $file['type'], $blocked_types, true ) && in_array( $action, $upload_actions, true ) ) {
			$file['error'] = __( 'Plugin and theme uploads are disabled in demo mode.', 'demowp' );
		}

		return $file;
	}

	/**
	 * Disable update checks
	 *
	 * @since 1.0.0
	 * @param mixed $value Transient value.
	 * @return object Empty update response.
	 */
	public function disable_updates( $value ) {
		return (object) array(
			'last_checked'    => time(),
			'version_checked' => get_bloginfo( 'version' ),
			'updates'         => array(),
			'translations'    => array(),
		);
	}

	/**
	 * Filter plugin action links
	 *
	 * @since 1.0.0
	 * @param array  $actions     Plugin actions.
	 * @param string $plugin_file Plugin file.
	 * @param array  $plugin_data Plugin data.
	 * @param string $context     Context.
	 * @return array Modified actions.
	 */
	public function filter_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		// Remove delete and edit links, but keep activate/deactivate.
		unset( $actions['delete'] );
		unset( $actions['edit'] );

		return $actions;
	}

	/**
	 * Add demo mode body class
	 *
	 * @since 1.0.0
	 * @param string $classes Body classes.
	 * @return string Modified classes.
	 */
	public function add_demo_body_class( $classes ) {
		return $classes . ' demowp-demo-mode';
	}

	/**
	 * Enqueue admin styles for demo mode
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_styles() {
		$css = '
			/* Hide restricted elements in demo mode */
			.demowp-demo-mode .plugin-install-tab-upload,
			.demowp-demo-mode .upload-view-toggle,
			.demowp-demo-mode #plugin-install-search ~ .wp-upload-form,
			.demowp-demo-mode .update-nag,
			.demowp-demo-mode #wp-admin-bar-updates,
			.demowp-demo-mode .plugins .delete a,
			.demowp-demo-mode .theme-browser .theme .theme-actions .delete-theme {
				display: none !important;
			}

			/* Style demo notice */
			.demowp-demo-notice {
				background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
			}
		';

		wp_add_inline_style( 'wp-admin', $css );
	}
}

// Initialize restrictions.
new DemoWP_Clone_Restrictions();
