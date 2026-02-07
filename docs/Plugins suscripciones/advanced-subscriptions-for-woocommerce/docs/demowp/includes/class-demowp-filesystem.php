<?php
/**
 * Filesystem Operations
 *
 * Handles file and directory operations for demo instances.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Filesystem
 *
 * Manages file system operations for cloning WordPress installations.
 *
 * @since 1.0.0
 */
class DemoWP_Filesystem {

    /**
     * Progress callback function
     *
     * @var callable|null
     */
    private $progress_callback;

    /**
     * Set progress callback
     *
     * @param callable $callback Progress callback function.
     */
    public function set_progress_callback( $callback ) {
        $this->progress_callback = $callback;
    }

    /**
     * Report progress
     *
     * @param string $step    Current step.
     * @param int    $percent Percentage complete.
     */
    private function report_progress( $step, $percent ) {
        if ( is_callable( $this->progress_callback ) ) {
            call_user_func( $this->progress_callback, $step, $percent );
        }
    }

    /**
     * Generate a unique clone ID
     *
     * @return string 32-character hexadecimal string.
     */
    public static function generate_clone_id() {
        return DemoWP_Utils::generate_random_string( 32 );
    }

    /**
     * Create the clone directory
     *
     * @param string $clone_id The clone ID.
     * @return string|WP_Error The clone path or error.
     */
    public function create_clone_directory( $clone_id ) {
        if ( ! DemoWP_Utils::is_valid_clone_id( $clone_id ) ) {
            return new WP_Error( 'invalid_clone_id', __( 'Invalid clone ID format.', 'demowp' ) );
        }

        $clone_path = ABSPATH . $clone_id;

        if ( file_exists( $clone_path ) ) {
            return new WP_Error( 'dir_exists', __( 'Clone directory already exists.', 'demowp' ) );
        }

        // Create main directory with proper permissions (755 for web server access).
        if ( ! wp_mkdir_p( $clone_path ) ) {
            return new WP_Error( 'mkdir_failed', __( 'Could not create clone directory.', 'demowp' ) );
        }

        // Ensure directory has correct permissions (755) for web server access.
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $wp_filesystem->chmod( $clone_path, 0755 );

        // Create marker file to identify this as a clone
        $marker_content = wp_json_encode(
            array(
                'created'   => time(),
                'clone_id'  => $clone_id,
                'template'  => home_url(),
            )
        );
        file_put_contents( $clone_path . '/.demowp-clone', $marker_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

        return $clone_path;
    }

    /**
     * Setup WordPress files in clone directory
     *
     * Copies necessary WordPress files to the clone.
     *
     * @param string $clone_path The clone directory path.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public function setup_wordpress_files( $clone_path ) {
        $this->report_progress( 'files_start', 0 );

        // Create wp-content directory structure
        $wp_content_dirs = array(
            'wp-content',
            'wp-content/uploads',
            'wp-content/cache',
        );

        foreach ( $wp_content_dirs as $dir ) {
            wp_mkdir_p( $clone_path . '/' . $dir );
        }

        $this->report_progress( 'files_structure', 10 );

        // Copy WordPress core directories
        $core_dirs = array(
            'wp-admin',
            'wp-includes',
        );

        $progress = 10;
        foreach ( $core_dirs as $dir ) {
            $source = ABSPATH . $dir;
            $dest   = $clone_path . '/' . $dir;

            if ( is_dir( $source ) ) {
                $result = $this->recursive_copy( $source, $dest );
                if ( is_wp_error( $result ) ) {
                    return $result;
                }
            }

            $progress += 20;
            $this->report_progress( 'files_core', $progress );
        }

        // Copy plugins directory (excluding DemoWP plugin - not needed in clones).
        $plugins_source = WP_PLUGIN_DIR;
        $plugins_dest   = $clone_path . '/wp-content/plugins';
        $exclude_plugins = array( 'demowp' ); // DemoWP is handled by mu-plugin.
        $this->copy_plugins_directory( $plugins_source, $plugins_dest, $exclude_plugins );
        $this->report_progress( 'files_plugins', 60 );

        // Copy themes directory
        $themes_source = get_theme_root();
        $themes_dest   = $clone_path . '/wp-content/themes';
        $this->recursive_copy( $themes_source, $themes_dest );
        $this->report_progress( 'files_themes', 75 );

        // Copy mu-plugins if exists
        $mu_plugins_source = WPMU_PLUGIN_DIR;
        $mu_plugins_dest   = $clone_path . '/wp-content/mu-plugins';

        if ( is_dir( $mu_plugins_source ) ) {
            $this->recursive_copy( $mu_plugins_source, $mu_plugins_dest );
        } else {
            // Create mu-plugins directory if it doesn't exist.
            wp_mkdir_p( $mu_plugins_dest );
        }

        // Copy DemoWP restrictions mu-plugin to ensure restrictions are always active.
        $demowp_mu_source = DEMOWP_PLUGIN_DIR . 'includes/mu-plugin/demowp-restrictions-loader.php';
        if ( file_exists( $demowp_mu_source ) ) {
            copy( $demowp_mu_source, $mu_plugins_dest . '/demowp-restrictions-loader.php' );
        }

        // Copy root WordPress files
        $root_files = array(
            'index.php',
            'wp-blog-header.php',
            'wp-load.php',
            'wp-login.php',
            'wp-settings.php',
            'wp-cron.php',
            'wp-links-opml.php',
            'wp-trackback.php',
            'xmlrpc.php',
            'wp-activate.php',
            'wp-comments-post.php',
            'wp-mail.php',
            'wp-signup.php',
        );

        foreach ( $root_files as $file ) {
            $source = ABSPATH . $file;
            $dest   = $clone_path . '/' . $file;

            if ( file_exists( $source ) ) {
                copy( $source, $dest );
            }
        }

        $this->report_progress( 'files_root', 85 );

        // Create proper .htaccess for subdirectory
        $this->create_htaccess( $clone_path );

        // Copy uploads (as symlink for space efficiency, or copy if symlink fails)
        $uploads_source = wp_upload_dir()['basedir'];
        $uploads_dest   = $clone_path . '/wp-content/uploads';

        // Remove empty uploads dir first
        if ( is_dir( $uploads_dest ) && $this->is_dir_empty( $uploads_dest ) ) {
            rmdir( $uploads_dest );
        }

        // Try symlink first, fall back to copy
        if ( ! @symlink( $uploads_source, $uploads_dest ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            // If symlink fails, copy the uploads
            $this->recursive_copy( $uploads_source, $uploads_dest );
        }

        $this->report_progress( 'files_complete', 100 );

        return true;
    }

    /**
     * Create .htaccess for the clone subdirectory
     *
     * @param string $clone_path The clone directory path.
     */
    private function create_htaccess( $clone_path ) {
        // Get the subdirectory name from the path
        $clone_id = basename( $clone_path );

        // Create .htaccess with proper RewriteBase for subdirectory
        $htaccess_content = "# BEGIN WordPress\n";
        $htaccess_content .= "<IfModule mod_rewrite.c>\n";
        $htaccess_content .= "RewriteEngine On\n";
        $htaccess_content .= "RewriteBase /{$clone_id}/\n";
        $htaccess_content .= "RewriteRule ^index\\.php$ - [L]\n";
        $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
        $htaccess_content .= "RewriteRule . /{$clone_id}/index.php [L]\n";
        $htaccess_content .= "</IfModule>\n";
        $htaccess_content .= "# END WordPress\n\n";

        // Protect the marker file
        $htaccess_content .= "<Files \".demowp-clone\">\n";
        $htaccess_content .= "    Order allow,deny\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "</Files>\n";

        file_put_contents( $clone_path . '/.htaccess', $htaccess_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    }

    /**
     * Create wp-config.php for the clone
     *
     * @param string $clone_path The clone directory path.
     * @param string $db_prefix  The database prefix for this clone.
     * @param string $clone_id   The clone ID.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public function create_wp_config( $clone_path, $db_prefix, $clone_id ) {
        $original_config_path = ABSPATH . 'wp-config.php';

        if ( ! file_exists( $original_config_path ) ) {
            return new WP_Error( 'no_config', __( 'Original wp-config.php not found.', 'demowp' ) );
        }

        $config_content = file_get_contents( $original_config_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        // Replace table prefix
        $config_content = preg_replace(
            '/\$table_prefix\s*=\s*[\'"][^\'"]+[\'"]\s*;/',
            "\$table_prefix = '{$db_prefix}';",
            $config_content
        );

        // Prepare extra constants.
        // Note: We only use DISALLOW_FILE_EDIT to block the theme/plugin editor.
        // We do NOT use DISALLOW_FILE_MODS as it hides the Plugins/Themes menus entirely.
        // Instead, restrictions are handled by DemoWP_Restrictions class.
        $extra_config = "\n// DemoWP Clone Configuration\n";
        $extra_config .= "define( 'DEMOWP_IS_CLONE', true );\n";
        $extra_config .= "define( 'DEMOWP_CLONE_ID', '{$clone_id}' );\n";
        $extra_config .= "define( 'DISALLOW_FILE_EDIT', true );\n";

        // Insert before "That's all, stop editing!"
        if ( strpos( $config_content, "/* That's all, stop editing!" ) !== false ) {
            $config_content = str_replace(
                "/* That's all, stop editing!",
                $extra_config . "\n/* That's all, stop editing!",
                $config_content
            );
        } elseif ( strpos( $config_content, "/** That's all, stop editing!" ) !== false ) {
            $config_content = str_replace(
                "/** That's all, stop editing!",
                $extra_config . "\n/** That's all, stop editing!",
                $config_content
            );
        } else {
            // Fallback: insert before require_once ABSPATH
            $config_content = preg_replace(
                '/(require_once.*ABSPATH.*wp-settings\.php.*)/',
                $extra_config . "\n$1",
                $config_content
            );
        }

        // Write new wp-config.php
        $result = file_put_contents( $clone_path . '/wp-config.php', $config_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

        if ( false === $result ) {
            return new WP_Error( 'config_write_failed', __( 'Could not write wp-config.php', 'demowp' ) );
        }

        return true;
    }

    /**
     * Delete a clone directory
     *
     * @param string $clone_path The clone directory path.
     * @return bool True on success, false on failure.
     */
    public function delete_clone_directory( $clone_path ) {
        // Verify this is a clone directory (safety check)
        if ( ! file_exists( $clone_path . '/.demowp-clone' ) ) {
            DemoWP_Utils::log( 'Attempted to delete non-clone directory: ' . $clone_path, 'error' );
            return false;
        }

        // Safety: don't delete ABSPATH
        if ( realpath( $clone_path ) === realpath( ABSPATH ) ) {
            DemoWP_Utils::log( 'Attempted to delete ABSPATH - blocked', 'error' );
            return false;
        }

        // Remove symlinks first
        $this->remove_symlinks_recursive( $clone_path );

        // Delete directory recursively
        $this->recursive_delete( $clone_path );

        return ! file_exists( $clone_path );
    }

    /**
     * Recursively copy a directory
     *
     * @param string $source Source directory.
     * @param string $dest   Destination directory.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    private function recursive_copy( $source, $dest ) {
        if ( is_link( $source ) ) {
            // It's a symlink, just create a new symlink
            return symlink( readlink( $source ), $dest );
        }

        if ( is_file( $source ) ) {
            return copy( $source, $dest );
        }

        if ( ! is_dir( $dest ) ) {
            wp_mkdir_p( $dest );
        }

        $dir = dir( $source );

        if ( ! $dir ) {
            return new WP_Error( 'dir_open_failed', sprintf( 'Could not open directory: %s', $source ) );
        }

        while ( false !== ( $entry = $dir->read() ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
            if ( '.' === $entry || '..' === $entry ) {
                continue;
            }

            $source_path = $source . '/' . $entry;
            $dest_path   = $dest . '/' . $entry;

            if ( is_link( $source_path ) ) {
                // Copy symlink as symlink
                $link_target = readlink( $source_path );
                if ( ! file_exists( $dest_path ) ) {
                    @symlink( $link_target, $dest_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                }
            } elseif ( is_dir( $source_path ) ) {
                $this->recursive_copy( $source_path, $dest_path );
            } else {
                copy( $source_path, $dest_path );
            }
        }

        $dir->close();

        return true;
    }

    /**
     * Copy plugins directory excluding specific plugins
     *
     * @param string $source  Source plugins directory.
     * @param string $dest    Destination plugins directory.
     * @param array  $exclude Array of plugin folder names to exclude.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    private function copy_plugins_directory( $source, $dest, $exclude = array() ) {
        if ( ! is_dir( $dest ) ) {
            wp_mkdir_p( $dest );
        }

        $dir = dir( $source );

        if ( ! $dir ) {
            return new WP_Error( 'dir_open_failed', sprintf( 'Could not open directory: %s', $source ) );
        }

        while ( false !== ( $entry = $dir->read() ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
            if ( '.' === $entry || '..' === $entry ) {
                continue;
            }

            // Skip excluded plugins.
            if ( in_array( $entry, $exclude, true ) ) {
                continue;
            }

            $source_path = $source . '/' . $entry;
            $dest_path   = $dest . '/' . $entry;

            if ( is_link( $source_path ) ) {
                $link_target = readlink( $source_path );
                if ( ! file_exists( $dest_path ) ) {
                    @symlink( $link_target, $dest_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
                }
            } elseif ( is_dir( $source_path ) ) {
                $this->recursive_copy( $source_path, $dest_path );
            } else {
                copy( $source_path, $dest_path );
            }
        }

        $dir->close();

        return true;
    }

    /**
     * Recursively delete a directory
     *
     * @param string $path Directory path.
     */
    private function recursive_delete( $path ) {
        if ( ! file_exists( $path ) ) {
            return;
        }

        if ( is_link( $path ) ) {
            unlink( $path );
            return;
        }

        if ( is_file( $path ) ) {
            unlink( $path );
            return;
        }

        $items = scandir( $path );

        foreach ( $items as $item ) {
            if ( '.' === $item || '..' === $item ) {
                continue;
            }

            $item_path = $path . '/' . $item;

            if ( is_link( $item_path ) ) {
                unlink( $item_path );
            } elseif ( is_dir( $item_path ) ) {
                $this->recursive_delete( $item_path );
            } else {
                unlink( $item_path );
            }
        }

        rmdir( $path );
    }

    /**
     * Remove symlinks recursively from a directory
     *
     * @param string $path Directory path.
     */
    private function remove_symlinks_recursive( $path ) {
        if ( ! is_dir( $path ) ) {
            return;
        }

        $items = scandir( $path );

        foreach ( $items as $item ) {
            if ( '.' === $item || '..' === $item ) {
                continue;
            }

            $item_path = $path . '/' . $item;

            if ( is_link( $item_path ) ) {
                unlink( $item_path );
            } elseif ( is_dir( $item_path ) ) {
                $this->remove_symlinks_recursive( $item_path );
            }
        }
    }

    /**
     * Check if a directory is empty
     *
     * @param string $path Directory path.
     * @return bool True if empty.
     */
    private function is_dir_empty( $path ) {
        if ( ! is_dir( $path ) ) {
            return true;
        }

        $items = scandir( $path );
        return count( $items ) <= 2; // Only . and ..
    }

    /**
     * Get the clone path from clone ID
     *
     * @param string $clone_id The clone ID.
     * @return string The clone path.
     */
    public static function get_clone_path( $clone_id ) {
        return ABSPATH . $clone_id;
    }

    /**
     * Verify a clone directory exists and is valid
     *
     * @param string $clone_id The clone ID.
     * @return bool True if valid clone exists.
     */
    public static function clone_exists( $clone_id ) {
        if ( ! DemoWP_Utils::is_valid_clone_id( $clone_id ) ) {
            return false;
        }

        $clone_path = self::get_clone_path( $clone_id );

        return file_exists( $clone_path . '/.demowp-clone' );
    }
}
