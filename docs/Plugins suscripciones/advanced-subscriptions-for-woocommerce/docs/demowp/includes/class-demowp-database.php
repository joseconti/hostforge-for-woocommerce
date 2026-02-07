<?php
/**
 * Database Operations
 *
 * Handles cloning and managing database tables for demo instances.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Database
 *
 * Manages database operations for cloning WordPress installations.
 *
 * @since 1.0.0
 */
class DemoWP_Database {

    /**
     * WordPress database instance
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Template database prefix
     *
     * @var string
     */
    private $template_prefix;

    /**
     * Progress callback function
     *
     * @var callable|null
     */
    private $progress_callback;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb            = $wpdb;
        $this->template_prefix = $wpdb->prefix;
    }

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
     * Get list of WordPress core tables to clone
     *
     * @return array List of table names (without prefix).
     */
    private function get_core_tables() {
        return array(
            'commentmeta',
            'comments',
            'links',
            'options',
            'postmeta',
            'posts',
            'term_relationships',
            'term_taxonomy',
            'termmeta',
            'terms',
            'usermeta',
            'users',
        );
    }

    /**
     * Get all tables with the template prefix
     *
     * Includes both core and custom tables, but excludes:
     * - DemoWP tracking table (main site only)
     * - Demo clone tables (tables from previous demos with dw_ prefix)
     *
     * @return array List of full table names.
     */
    private function get_all_template_tables() {
        $tables = $this->wpdb->get_col(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $this->wpdb->esc_like( $this->template_prefix ) . '%'
            )
        );

        // Filter out DemoWP tracking table and demo clone tables.
        $tracker_table   = DemoWP_Demo_Tracker::get_table_name();
        $filtered_tables = array();
        $prefix_length   = strlen( $this->template_prefix );

		foreach ( $tables as $table ) {
			// Exclude DemoWP tracker table.
			if ( $table === $tracker_table ) {
				continue;
			}

			// Get the table name without the template prefix.
			$table_suffix = substr( $table, $prefix_length );

			// Exclude tables that belong to demo clones (start with 'dw_').
			// These are leftover tables from previous demos that weren't cleaned up,
			// or tables from demos created while running on the main site.
			if ( strpos( $table_suffix, 'dw_' ) === 0 ) {
				continue;
			}

			$filtered_tables[] = $table;
		}

        return $filtered_tables;
    }

    /**
     * Clone all tables with a new prefix
     *
     * @param string $new_prefix  The new table prefix.
     * @param string $user_email  Optional user email to use as admin email.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public function clone_tables( $new_prefix, $user_email = '' ) {
        $new_prefix = DemoWP_Utils::sanitize_db_prefix( $new_prefix );

        if ( empty( $new_prefix ) ) {
            return new WP_Error( 'invalid_prefix', __( 'Invalid database prefix.', 'demowp' ) );
        }

        $tables       = $this->get_all_template_tables();
        $total_tables = count( $tables );
        $cloned       = 0;

        $this->report_progress( 'database_start', 0 );

        foreach ( $tables as $source_table ) {
            // Extract table name without prefix.
            $table_name = substr( $source_table, strlen( $this->template_prefix ) );
            $dest_table = $new_prefix . $table_name;

            // Skip tables that would exceed MySQL's 64 character limit.
            if ( strlen( $dest_table ) > 64 ) {
                DemoWP_Utils::log( sprintf( 'Skipping table (name too long): %s', $dest_table ), 'warning' );
                continue;
            }

            // Create table structure.
            $create_sql = $this->wpdb->get_row( "SHOW CREATE TABLE `{$source_table}`", ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

            if ( ! $create_sql ) {
                continue;
            }

            // Modify the CREATE statement for new table name.
            $create_statement = str_replace(
                "CREATE TABLE `{$source_table}`",
                "CREATE TABLE `{$dest_table}`",
                $create_sql[1]
            );

            // Remove foreign key constraints to avoid conflicts.
            // Foreign keys reference constraint names that include the original prefix,
            // causing "Duplicate key" errors (errno: 121) when cloning.
            // This is safe for demo instances as data integrity is maintained by copying
            // all related data together.
            $create_statement = $this->remove_foreign_keys( $create_statement );

            // Create the table.
            $result = $this->wpdb->query( $create_statement ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

            if ( false === $result ) {
                return new WP_Error(
                    'table_create_failed',
                    sprintf(
                        /* translators: %s: table name */
                        __( 'Failed to create table: %s', 'demowp' ),
                        $dest_table
                    )
                );
            }

            // Copy data with exceptions:
            // - users/usermeta: we'll create a fresh user.
            // - actionscheduler tables: copy structure only (empty), they'll repopulate.
            $skip_data_tables   = array( 'users', 'usermeta' );
            $is_actionscheduler = ( strpos( $table_name, 'actionscheduler' ) === 0 );

            if ( ! in_array( $table_name, $skip_data_tables, true ) && ! $is_actionscheduler ) {
                $this->wpdb->query(
                    "INSERT INTO `{$dest_table}` SELECT * FROM `{$source_table}`" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                );
            }

            $cloned++;
            $percent = intval( ( $cloned / $total_tables ) * 100 );
            $this->report_progress( 'database_progress', $percent );
        }

        // Update clone-specific options.
        $this->update_clone_options( $new_prefix, $user_email );

        $this->report_progress( 'database_complete', 100 );

        return true;
    }

    /**
     * Update options specific to the clone
     *
     * @param string $new_prefix  The clone's table prefix.
     * @param string $user_email  Optional user email to use as admin email.
     */
    private function update_clone_options( $new_prefix, $user_email = '' ) {
        $options_table = $new_prefix . 'options';

        // Update user_roles option name to match new prefix.
        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE `{$options_table}` SET option_name = %s WHERE option_name = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $new_prefix . 'user_roles',
                $this->template_prefix . 'user_roles'
            )
        );

        // Remove transients and caches.
        $this->wpdb->query(
            "DELETE FROM `{$options_table}` WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        // Deactivate DemoWP plugin in clone (it's not copied, so avoid "plugin not found" error).
        $this->deactivate_demowp_in_clone( $options_table );

        // Store clone expiration info.
        $lifetime   = (int) get_option( 'demowp_demo_lifetime', 3600 );
        $expires_at = gmdate( 'Y-m-d H:i:s', time() + $lifetime );

        $this->wpdb->insert(
            $options_table,
            array(
                'option_name'  => 'demowp_clone_expires',
                'option_value' => $expires_at,
                'autoload'     => 'yes',
            ),
            array( '%s', '%s', '%s' )
        );

        // Update admin email if user provided one.
        if ( ! empty( $user_email ) && is_email( $user_email ) ) {
            // WordPress admin email.
            $this->wpdb->update(
                $options_table,
                array( 'option_value' => $user_email ),
                array( 'option_name' => 'admin_email' ),
                array( '%s' ),
                array( '%s' )
            );

            // WooCommerce simple email options.
            $woo_email_options = array(
                'woocommerce_email_from_address',
                'woocommerce_stock_email_recipient',
            );

            foreach ( $woo_email_options as $option_name ) {
                $this->wpdb->update(
                    $options_table,
                    array( 'option_value' => $user_email ),
                    array( 'option_name' => $option_name ),
                    array( '%s' ),
                    array( '%s' )
                );
            }

            // WooCommerce serialized email settings with 'recipient' field.
            $woo_email_settings = array(
                'woocommerce_new_order_settings',
                'woocommerce_cancelled_order_settings',
                'woocommerce_failed_order_settings',
            );

            foreach ( $woo_email_settings as $option_name ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $option_value = $this->wpdb->get_var(
                    $this->wpdb->prepare(
                        "SELECT option_value FROM `{$options_table}` WHERE option_name = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        $option_name
                    )
                );

                if ( $option_value ) {
                    $settings = maybe_unserialize( $option_value );
                    if ( is_array( $settings ) && isset( $settings['recipient'] ) ) {
                        $settings['recipient'] = $user_email;
                        $this->wpdb->update(
                            $options_table,
                            array( 'option_value' => maybe_serialize( $settings ) ),
                            array( 'option_name' => $option_name ),
                            array( '%s' ),
                            array( '%s' )
                        );
                    }
                }
            }
        }
    }

    /**
     * Deactivate DemoWP plugin in clone's database
     *
     * Since DemoWP is not copied to clones, we need to remove it from
     * the active_plugins list to avoid "plugin not found" errors.
     *
     * @param string $options_table The clone's options table name.
     */
    private function deactivate_demowp_in_clone( $options_table ) {
        // Get current active plugins from clone's database.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $active_plugins_serialized = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT option_value FROM `{$options_table}` WHERE option_name = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                'active_plugins'
            )
        );

        if ( empty( $active_plugins_serialized ) ) {
            return;
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
        $active_plugins = maybe_unserialize( $active_plugins_serialized );

        if ( ! is_array( $active_plugins ) ) {
            return;
        }

        // Find and remove DemoWP from active plugins.
        $demowp_key = array_search( DEMOWP_PLUGIN_BASENAME, $active_plugins, true );

        if ( false !== $demowp_key ) {
            unset( $active_plugins[ $demowp_key ] );
            // Re-index array.
            $active_plugins = array_values( $active_plugins );

            // Update the option in clone's database.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $this->wpdb->update(
                $options_table,
                array( 'option_value' => maybe_serialize( $active_plugins ) ),
                array( 'option_name' => 'active_plugins' ),
                array( '%s' ),
                array( '%s' )
            );
        }
    }

    /**
     * Update site URL and home URL for clone
     *
     * @param string $new_prefix The clone's table prefix.
     * @param string $clone_url  The clone's URL.
     */
    public function update_site_urls( $new_prefix, $clone_url ) {
        $options_table = $new_prefix . 'options';
        $clone_url     = untrailingslashit( $clone_url );

        // Update siteurl
        $this->wpdb->update(
            $options_table,
            array( 'option_value' => $clone_url ),
            array( 'option_name' => 'siteurl' ),
            array( '%s' ),
            array( '%s' )
        );

        // Update home
        $this->wpdb->update(
            $options_table,
            array( 'option_value' => $clone_url ),
            array( 'option_name' => 'home' ),
            array( '%s' ),
            array( '%s' )
        );
    }

    /**
     * Create a demo user with random credentials
     *
     * @param string $new_prefix The clone's table prefix.
     * @return array User data including username and password.
     */
    public function create_demo_user( $new_prefix ) {
        $users_table    = $new_prefix . 'users';
        $usermeta_table = $new_prefix . 'usermeta';

        // Generate random credentials
        $username = 'demo_' . DemoWP_Utils::generate_random_string( 8 );
        $password = wp_generate_password( 16, true, false );
        $email    = $username . '@demo.local';

        // Hash the password
        $hashed_password = wp_hash_password( $password );

        // Insert user
        $this->wpdb->insert(
            $users_table,
            array(
                'user_login'          => $username,
                'user_pass'           => $hashed_password,
                'user_nicename'       => $username,
                'user_email'          => $email,
                'user_url'            => '',
                'user_registered'     => current_time( 'mysql' ),
                'user_activation_key' => '',
                'user_status'         => 0,
                'display_name'        => 'Demo User',
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
        );

        $user_id = $this->wpdb->insert_id;

        // Insert user meta for administrator capabilities
        $capabilities_key = $new_prefix . 'capabilities';
        $user_level_key   = $new_prefix . 'user_level';

        // Administrator capabilities
        $this->wpdb->insert(
            $usermeta_table,
            array(
                'user_id'    => $user_id,
                'meta_key'   => $capabilities_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value' => serialize( array( 'administrator' => true ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            ),
            array( '%d', '%s', '%s' )
        );

        // User level 10 (admin)
        $this->wpdb->insert(
            $usermeta_table,
            array(
                'user_id'    => $user_id,
                'meta_key'   => $user_level_key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value' => '10', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            ),
            array( '%d', '%s', '%s' )
        );

        // Additional user meta
        $meta_entries = array(
            'nickname'              => $username,
            'first_name'            => 'Demo',
            'last_name'             => 'User',
            'rich_editing'          => 'true',
            'syntax_highlighting'   => 'true',
            'admin_color'           => 'fresh',
            'show_admin_bar_front'  => 'true',
            'locale'                => '',
        );

        foreach ( $meta_entries as $key => $value ) {
            $this->wpdb->insert(
                $usermeta_table,
                array(
                    'user_id'    => $user_id,
                    'meta_key'   => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                    'meta_value' => $value, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                ),
                array( '%d', '%s', '%s' )
            );
        }

        return array(
            'user_id'  => $user_id,
            'username' => $username,
            'password' => $password,
            'email'    => $email,
        );
    }

    /**
     * Remove foreign key constraints from CREATE TABLE statement
     *
     * Foreign keys cause issues when cloning because:
     * 1. Constraint names include the original table prefix
     * 2. They reference tables that may not exist yet during cloning
     * 3. MySQL error 121 (duplicate key) occurs when constraint names conflict
     *
     * @param string $create_statement The CREATE TABLE SQL statement.
     * @return string The modified statement without foreign keys.
     */
    private function remove_foreign_keys( $create_statement ) {
        // Remove CONSTRAINT ... FOREIGN KEY lines.
        // Pattern matches: CONSTRAINT `name` FOREIGN KEY ... REFERENCES ... ON DELETE/UPDATE ...
        $pattern          = '/,?\s*CONSTRAINT\s+`[^`]+`\s+FOREIGN\s+KEY\s*\([^)]+\)\s*REFERENCES\s*`[^`]+`\s*\([^)]+\)(\s+ON\s+(DELETE|UPDATE)\s+(CASCADE|SET\s+NULL|NO\s+ACTION|RESTRICT|SET\s+DEFAULT))*/i';
        $create_statement = preg_replace( $pattern, '', $create_statement );

        // Also remove standalone FOREIGN KEY lines (without CONSTRAINT keyword).
        $pattern          = '/,?\s*FOREIGN\s+KEY\s*\([^)]+\)\s*REFERENCES\s*`[^`]+`\s*\([^)]+\)(\s+ON\s+(DELETE|UPDATE)\s+(CASCADE|SET\s+NULL|NO\s+ACTION|RESTRICT|SET\s+DEFAULT))*/i';
        $create_statement = preg_replace( $pattern, '', $create_statement );

        // Clean up any trailing commas before the closing parenthesis.
        $create_statement = preg_replace( '/,(\s*)\)(\s*ENGINE)/i', '$1)$2ENGINE', $create_statement );

        return $create_statement;
    }

    /**
     * Delete all tables for a clone
     *
     * @param string $prefix The clone's table prefix.
     * @return int Number of tables deleted.
     */
    public function delete_clone_tables( $prefix ) {
        $prefix = DemoWP_Utils::sanitize_db_prefix( $prefix );

        if ( empty( $prefix ) ) {
            return 0;
        }

        // Safety check: don't delete template tables
        if ( $prefix === $this->template_prefix ) {
            DemoWP_Utils::log( 'Attempted to delete template tables - blocked', 'error' );
            return 0;
        }

        // Get all tables with this prefix
        $tables = $this->wpdb->get_col(
            $this->wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $this->wpdb->esc_like( $prefix ) . '%'
            )
        );

        $deleted = 0;

        foreach ( $tables as $table ) {
            $result = $this->wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            if ( false !== $result ) {
                $deleted++;
            }
        }

        DemoWP_Utils::log( sprintf( 'Deleted %d tables with prefix %s', $deleted, $prefix ), 'info' );

        return $deleted;
    }

	/**
	 * Generate a unique database prefix for a clone
	 *
	 * MySQL has a 64 character limit for table names. WooCommerce creates
	 * tables with long names like 'woocommerce_downloadable_product_permissions' (46 chars).
	 * We need to ensure the prefix + longest table name <= 64 characters.
	 *
	 * Max prefix length = 64 - 50 (safety margin for long table names) = 14 characters.
	 *
	 * We always generate a short prefix to be safe: 'dw_' + 8 char hash + '_' = 12 chars.
	 *
	 * @param string $clone_id The clone ID.
	 * @return string The unique prefix.
	 */
	public function generate_unique_prefix( $clone_id ) {
		// Always use a short prefix to avoid issues with long table names.
		// Format: 'dw_' + 8 char hash + '_' = 12 characters.
		// This leaves 52 characters for table names (MySQL limit is 64).
		$hash   = substr( md5( $clone_id . microtime() ), 0, 8 );
		$prefix = 'dw_' . $hash . '_';

		// Verify it doesn't exist.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepare() is used correctly.
		$exists = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$this->wpdb->esc_like( $prefix ) . '%'
			)
		);

		// If collision, generate another hash.
		$attempts = 0;
		while ( $exists && $attempts < 10 ) {
			$hash   = substr( md5( $clone_id . microtime() . wp_rand() ), 0, 8 );
			$prefix = 'dw_' . $hash . '_';

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$exists = $this->wpdb->get_var(
				$this->wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$this->wpdb->esc_like( $prefix ) . '%'
				)
			);
			$attempts++;
		}

		return $prefix;
	}
}
