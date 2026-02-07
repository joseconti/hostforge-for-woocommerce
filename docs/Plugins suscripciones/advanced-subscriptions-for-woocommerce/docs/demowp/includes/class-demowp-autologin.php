<?php
/**
 * Auto-login System
 *
 * Handles automatic login token generation for demo users.
 *
 * @package DemoWP
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DemoWP_Autologin
 *
 * Manages automatic login tokens and authentication.
 * Tokens are stored in user_meta and are reusable (not one-time use).
 * The actual login processing is handled by the MU-Plugin.
 *
 * @since 1.0.0
 */
class DemoWP_Autologin {

	/**
	 * User meta key for autologin token
	 *
	 * @var string
	 */
	const TOKEN_META_KEY = '_demowp_autologin_token';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Show welcome notice with credentials (only in clones).
		add_action( 'admin_notices', array( $this, 'show_welcome_notice' ) );
	}

	/**
	 * Generate an autologin token
	 *
	 * Stores token in the clone's user_meta table.
	 * The token is linked to the user and is reusable.
	 *
	 * @param string $clone_id  The clone ID.
	 * @param int    $user_id   The user ID.
	 * @param string $db_prefix The clone's database prefix.
	 * @return string The generated token.
	 */
	public static function generate_token( $clone_id, $user_id, $db_prefix = '' ) {
		global $wpdb;

		// Generate a secure token (128 hex characters).
		$token = self::generate_secure_token();

		// Store token in clone's usermeta table.
		if ( ! empty( $db_prefix ) ) {
			$usermeta_table = $db_prefix . 'usermeta';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert(
				$usermeta_table,
				array(
					'user_id'    => $user_id,
					'meta_key'   => self::TOKEN_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => $token, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				),
				array( '%d', '%s', '%s' )
			);

			// Log result for debugging.
			if ( false === $result ) {
				DemoWP_Utils::log(
					sprintf(
						'Failed to insert autologin token in clone usermeta. Table: %s, Error: %s',
						$usermeta_table,
						$wpdb->last_error
					),
					'error'
				);
			} else {
				DemoWP_Utils::log(
					sprintf(
						'Autologin token stored in clone usermeta. User: %d, Token: %s...',
						$user_id,
						substr( $token, 0, 16 )
					),
					'info'
				);
			}
		}

		return $token;
	}

	/**
	 * Generate a secure random token
	 *
	 * @return string 128-character hex token.
	 */
	private static function generate_secure_token() {
		$byte_length = 64;

		if ( function_exists( 'random_bytes' ) ) {
			try {
				return bin2hex( random_bytes( $byte_length ) );
			} catch ( \Exception $e ) {
				DemoWP_Utils::log( 'random_bytes failed: ' . $e->getMessage(), 'error' );
			}
		}

		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$crypto_strong = false;
			$bytes         = openssl_random_pseudo_bytes( $byte_length, $crypto_strong );
			if ( true === $crypto_strong ) {
				return bin2hex( $bytes );
			}
		}

		// Fallback to WordPress function.
		return DemoWP_Utils::generate_random_string( 128 );
	}

	/**
	 * Get the autologin URL
	 *
	 * @param string $clone_id The clone ID.
	 * @param string $token    The autologin token.
	 * @return string The full autologin URL.
	 */
	public static function get_autologin_url( $clone_id, $token ) {
		// Send directly to clone's wp-admin with token.
		// The MU-Plugin will process the token on init hook.
		$clone_admin_url = home_url( $clone_id . '/wp-admin/' );

		return add_query_arg(
			array(
				'demowp_token' => $token,
			),
			$clone_admin_url
		);
	}

	/**
	 * Show welcome notice with credentials
	 */
	public function show_welcome_notice() {
		// Only in clones.
		if ( ! DEMOWP_IS_CLONE ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		// Check if we should show welcome.
		$show_welcome = get_transient( 'demowp_show_welcome_' . $user_id );

		if ( ! $show_welcome ) {
			return;
		}

		// Delete transient (show only once).
		delete_transient( 'demowp_show_welcome_' . $user_id );

		// Get expiration time.
		$expires_at     = get_option( 'demowp_clone_expires' );
		$time_remaining = '';

		if ( $expires_at ) {
			$time_remaining = DemoWP_Utils::get_time_remaining( strtotime( $expires_at ) );
		}

		// Get custom welcome message.
		$custom_message = get_option( 'demowp_welcome_message', '' );

		?>
		<div class="notice notice-success demowp-welcome-notice is-dismissible">
			<h3><?php esc_html_e( 'Welcome to Your Demo!', 'demowp' ); ?></h3>

			<?php if ( ! empty( $custom_message ) ) : ?>
				<p><?php echo esc_html( $custom_message ); ?></p>
			<?php endif; ?>

			<p>
				<?php esc_html_e( 'This is a temporary demo installation. Feel free to explore and test all features.', 'demowp' ); ?>
			</p>

			<?php if ( $time_remaining ) : ?>
				<p>
					<strong><?php esc_html_e( 'Time remaining:', 'demowp' ); ?></strong>
					<?php echo esc_html( $time_remaining ); ?>
				</p>
			<?php endif; ?>

			<p class="demowp-notice-restrictions">
				<em><?php esc_html_e( 'Note: Installing or editing plugins and themes is disabled in demo mode.', 'demowp' ); ?></em>
			</p>
		</div>
		<?php
	}
}
