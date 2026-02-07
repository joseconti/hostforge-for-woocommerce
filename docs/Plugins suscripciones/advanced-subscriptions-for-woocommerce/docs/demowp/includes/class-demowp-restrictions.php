<?php
/**
 * Security Restrictions
 *
 * Handles restrictions for demo installations to prevent plugin/theme modifications.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Restrictions
 *
 * Applies security restrictions in clone installations.
 *
 * @since 1.0.0
 */
class DemoWP_Restrictions {

    /**
     * Constructor
     */
    public function __construct() {
        // Only apply restrictions in clones
        if ( ! DEMOWP_IS_CLONE ) {
            return;
        }

        // Remove restricted menus
        add_action( 'admin_menu', array( $this, 'remove_restricted_menus' ), 999 );

        // Block restricted pages
        add_action( 'admin_init', array( $this, 'block_restricted_pages' ) );

        // Filter capabilities
        add_filter( 'user_has_cap', array( $this, 'filter_capabilities' ), 10, 4 );

        // Show demo notice
        add_action( 'admin_notices', array( $this, 'show_demo_notice' ) );

        // Block plugin/theme uploads
        add_filter( 'wp_handle_upload_prefilter', array( $this, 'block_plugin_theme_uploads' ) );

        // Disable update checks
        add_filter( 'pre_site_transient_update_plugins', array( $this, 'disable_updates' ) );
        add_filter( 'pre_site_transient_update_themes', array( $this, 'disable_updates' ) );
        add_filter( 'pre_site_transient_update_core', array( $this, 'disable_updates' ) );

        // Block plugin activation/deactivation
        add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 4 );
        add_filter( 'network_admin_plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 4 );

        // Block theme switching
        add_filter( 'theme_action_links', array( $this, 'filter_theme_action_links' ), 10, 4 );

        // Add demo mode body class
        add_filter( 'admin_body_class', array( $this, 'add_demo_body_class' ) );

        // Enqueue admin styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

        // Block AJAX plugin actions
        add_action( 'admin_init', array( $this, 'block_plugin_ajax_actions' ) );
    }

    /**
     * Remove restricted menu items
     */
    public function remove_restricted_menus() {
        // Plugins submenu items
        remove_submenu_page( 'plugins.php', 'plugin-install.php' );
        remove_submenu_page( 'plugins.php', 'plugin-editor.php' );

        // Themes submenu items
        remove_submenu_page( 'themes.php', 'theme-install.php' );
        remove_submenu_page( 'themes.php', 'theme-editor.php' );

        // Updates
        remove_submenu_page( 'index.php', 'update-core.php' );

        // Remove update count from menu
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

        // Block plugin deletion (but allow activation/deactivation)
        if ( 'plugins.php' === $pagenow ) {
            $blocked_actions = array(
                'delete-selected',
            );

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

        // Theme switching is now allowed, no blocking needed
    }

    /**
     * Block plugin-related AJAX actions
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
     * @param array   $allcaps All capabilities.
     * @param array   $caps    Required capabilities.
     * @param array   $args    Additional arguments.
     * @param WP_User $user    User object.
     * @return array Modified capabilities.
     */
    public function filter_capabilities( $allcaps, $caps, $args, $user ) {
        // Block install, delete, edit, and update capabilities.
        // Allow activate_plugins and switch_themes so users can activate/deactivate.
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
     */
    public function show_demo_notice() {
        // Only show on main admin pages, not AJAX
        if ( wp_doing_ajax() ) {
            return;
        }

        // Get expiration info
        $expires_at     = get_option( 'demowp_clone_expires' );
        $time_remaining = '';

        if ( $expires_at ) {
            $time_remaining = DemoWP_Utils::get_time_remaining( strtotime( $expires_at ) );
        }

        // Get custom message
        $custom_message = get_option( 'demowp_welcome_message', '' );

        ?>
        <div class="notice notice-warning demowp-demo-notice" style="border-left-color: #f59e0b;">
            <div style="display: flex; align-items: flex-start; gap: 12px; padding: 8px 0;">
                <span style="font-size: 20px;">🧪</span>
                <div>
                    <p style="margin: 0 0 4px; font-weight: 600;">
                        <?php esc_html_e( 'Demo Mode Active', 'demowp' ); ?>
                    </p>
                    <?php if ( ! empty( $custom_message ) ) : ?>
                        <p style="margin: 0 0 4px;"><?php echo esc_html( $custom_message ); ?></p>
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
     * @param array $file File data.
     * @return array Modified file data.
     */
    public function block_plugin_theme_uploads( $file ) {
        // Check if it's a plugin or theme upload
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
     * @param array  $actions     Plugin actions.
     * @param string $plugin_file Plugin file.
     * @param array  $plugin_data Plugin data.
     * @param string $context     Context.
     * @return array Modified actions.
     */
    public function filter_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
        // Remove delete and edit links, but keep activate/deactivate
        unset( $actions['delete'] );
        unset( $actions['edit'] );

        return $actions;
    }

    /**
     * Filter theme action links
     *
     * @param array    $actions Theme actions.
     * @param WP_Theme $theme   Theme object.
     * @param string   $context Context.
     * @param string   $status  Status.
     * @return array Modified actions.
     */
    public function filter_theme_action_links( $actions, $theme = null, $context = '', $status = '' ) {
        // Remove delete link, but keep activate for theme switching
        unset( $actions['delete'] );

        return $actions;
    }

    /**
     * Add demo mode body class
     *
     * @param string $classes Body classes.
     * @return string Modified classes.
     */
    public function add_demo_body_class( $classes ) {
        return $classes . ' demowp-demo-mode';
    }

    /**
     * Enqueue admin styles for demo mode
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
