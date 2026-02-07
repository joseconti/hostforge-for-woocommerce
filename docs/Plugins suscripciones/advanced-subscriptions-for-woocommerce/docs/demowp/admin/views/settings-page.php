<?php
/**
 * Settings Page View
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap demowp-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-welcome-view-site"></span>
        <?php esc_html_e( 'DemoWP Settings', 'demowp' ); ?>
    </h1>

    <div class="demowp-admin-content">
        <div class="demowp-admin-main">
            <form method="post" action="options.php">
                <?php settings_fields( 'demowp_settings' ); ?>

                <div class="demowp-card">
                    <h2><?php esc_html_e( 'License', 'demowp' ); ?></h2>
                    <?php
                    $license_key    = get_option( DEMOWP_LICENSE_PREFIX . '_license_key', '' );
                    $license_status = get_option( DEMOWP_LICENSE_PREFIX . '_license_status', '' );
                    ?>
                    <?php if ( 'valid' === $license_status ) : ?>
                        <div class="notice notice-success inline" style="margin: 0 0 16px;">
                            <p>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <strong><?php esc_html_e( 'License is active.', 'demowp' ); ?></strong>
                                <?php esc_html_e( 'You will receive automatic updates.', 'demowp' ); ?>
                            </p>
                        </div>
                    <?php elseif ( ! empty( $license_key ) ) : ?>
                        <div class="notice notice-error inline" style="margin: 0 0 16px;">
                            <p>
                                <span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
                                <strong><?php esc_html_e( 'License is not valid.', 'demowp' ); ?></strong>
                                <?php esc_html_e( 'Please check your license key.', 'demowp' ); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr( DEMOWP_LICENSE_PREFIX ); ?>_license_key">
                                    <?php esc_html_e( 'License Key', 'demowp' ); ?>
                                </label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr( DEMOWP_LICENSE_PREFIX ); ?>_license_key"
                                    name="<?php echo esc_attr( DEMOWP_LICENSE_PREFIX ); ?>_license_key"
                                    value="<?php echo esc_attr( $license_key ); ?>"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e( 'Enter your license key', 'demowp' ); ?>"
                                >
                                <p class="description">
                                    <?php esc_html_e( 'Enter your license key to enable automatic updates.', 'demowp' ); ?>
                                    <a href="https://plugins.joseconti.com" target="_blank"><?php esc_html_e( 'Get a license', 'demowp' ); ?></a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="demowp-card">
                    <h2><?php esc_html_e( 'Demo Endpoint', 'demowp' ); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="demowp_endpoint_slug">
                                    <?php esc_html_e( 'Endpoint URL', 'demowp' ); ?>
                                </label>
                            </th>
                            <td>
                                <div class="demowp-input-group">
                                    <span class="demowp-input-prefix"><?php echo esc_url( home_url( '/' ) ); ?></span>
                                    <input
                                        type="text"
                                        id="demowp_endpoint_slug"
                                        name="demowp_endpoint_slug"
                                        value="<?php echo esc_attr( get_option( 'demowp_endpoint_slug', 'demo' ) ); ?>"
                                        class="regular-text"
                                    >
                                </div>
                                <p class="description">
                                    <?php esc_html_e( 'The URL where users can create demos.', 'demowp' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="demowp-card">
                    <h2><?php esc_html_e( 'Demo Settings', 'demowp' ); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="demowp_demo_lifetime">
                                    <?php esc_html_e( 'Demo Lifetime', 'demowp' ); ?>
                                </label>
                            </th>
                            <td>
                                <select id="demowp_demo_lifetime" name="demowp_demo_lifetime">
                                    <option value="1800" <?php selected( get_option( 'demowp_demo_lifetime' ), 1800 ); ?>>
                                        30 <?php esc_html_e( 'minutes', 'demowp' ); ?>
                                    </option>
                                    <option value="3600" <?php selected( get_option( 'demowp_demo_lifetime', 3600 ), 3600 ); ?>>
                                        1 <?php esc_html_e( 'hour', 'demowp' ); ?>
                                    </option>
                                    <option value="7200" <?php selected( get_option( 'demowp_demo_lifetime' ), 7200 ); ?>>
                                        2 <?php esc_html_e( 'hours', 'demowp' ); ?>
                                    </option>
                                    <option value="14400" <?php selected( get_option( 'demowp_demo_lifetime' ), 14400 ); ?>>
                                        4 <?php esc_html_e( 'hours', 'demowp' ); ?>
                                    </option>
                                    <option value="28800" <?php selected( get_option( 'demowp_demo_lifetime' ), 28800 ); ?>>
                                        8 <?php esc_html_e( 'hours', 'demowp' ); ?>
                                    </option>
                                    <option value="86400" <?php selected( get_option( 'demowp_demo_lifetime' ), 86400 ); ?>>
                                        24 <?php esc_html_e( 'hours', 'demowp' ); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e( 'How long each demo will remain active before automatic deletion.', 'demowp' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="demowp_max_concurrent_demos">
                                    <?php esc_html_e( 'Max Demos per IP', 'demowp' ); ?>
                                </label>
                            </th>
                            <td>
                                <input
                                    type="number"
                                    id="demowp_max_concurrent_demos"
                                    name="demowp_max_concurrent_demos"
                                    value="<?php echo esc_attr( get_option( 'demowp_max_concurrent_demos', 3 ) ); ?>"
                                    min="1"
                                    max="10"
                                    class="small-text"
                                >
                                <p class="description">
                                    <?php esc_html_e( 'Maximum concurrent demos per IP address to prevent abuse.', 'demowp' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="demowp-card">
                    <h2><?php esc_html_e( 'Customization', 'demowp' ); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="demowp_welcome_message">
                                    <?php esc_html_e( 'Welcome Message', 'demowp' ); ?>
                                </label>
                            </th>
                            <td>
                                <textarea
                                    id="demowp_welcome_message"
                                    name="demowp_welcome_message"
                                    rows="3"
                                    class="large-text"
                                    placeholder="<?php esc_attr_e( 'Enter a custom message to show in demo installations...', 'demowp' ); ?>"
                                ><?php echo esc_textarea( get_option( 'demowp_welcome_message', '' ) ); ?></textarea>
                                <p class="description">
                                    <?php esc_html_e( 'Custom message shown in the admin area of demo installations.', 'demowp' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="demowp_email_mode">
                                    <?php esc_html_e( 'Demo Email', 'demowp' ); ?>
                                </label>
                            </th>
                            <td>
                                <select id="demowp_email_mode" name="demowp_email_mode">
                                    <option value="admin" <?php selected( get_option( 'demowp_email_mode', 'admin' ), 'admin' ); ?>>
                                        <?php esc_html_e( 'Use site admin email', 'demowp' ); ?>
                                    </option>
                                    <option value="user" <?php selected( get_option( 'demowp_email_mode' ), 'user' ); ?>>
                                        <?php esc_html_e( 'Ask user for email', 'demowp' ); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e( 'Choose whether demos use the site admin email or request one from the user.', 'demowp' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="demowp-card">
                    <h2><?php esc_html_e( 'Maintenance Mode', 'demowp' ); ?></h2>
                    <?php if ( DemoWP_Maintenance::is_enabled() ) : ?>
                        <div class="notice notice-warning inline" style="margin: 0 0 16px;">
                            <p>
                                <strong><?php esc_html_e( 'Maintenance mode is currently active.', 'demowp' ); ?></strong>
                                <?php esc_html_e( 'Visitors cannot access the front-end of your site.', 'demowp' ); ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php esc_html_e( 'Enable Maintenance', 'demowp' ); ?>
                            </th>
                            <td>
                                <label for="demowp_maintenance_mode">
                                    <input
                                        type="checkbox"
                                        id="demowp_maintenance_mode"
                                        name="demowp_maintenance_mode"
                                        value="1"
                                        <?php checked( get_option( 'demowp_maintenance_mode', false ) ); ?>
                                    >
                                    <?php esc_html_e( 'Put the main site in maintenance mode', 'demowp' ); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e( 'When enabled, visitors will see a maintenance page. Administrators can browse normally. Demo creation will still work.', 'demowp' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="demowp_maintenance_message">
                                    <?php esc_html_e( 'Maintenance Message', 'demowp' ); ?>
                                </label>
                            </th>
                            <td>
                                <textarea
                                    id="demowp_maintenance_message"
                                    name="demowp_maintenance_message"
                                    rows="3"
                                    class="large-text"
                                    placeholder="<?php esc_attr_e( 'We are currently performing scheduled maintenance. Please check back soon.', 'demowp' ); ?>"
                                ><?php echo esc_textarea( get_option( 'demowp_maintenance_message', '' ) ); ?></textarea>
                                <p class="description">
                                    <?php esc_html_e( 'Custom message shown on the maintenance page.', 'demowp' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <div class="demowp-admin-sidebar">
            <div class="demowp-card demowp-info-card">
                <h3><?php esc_html_e( 'Quick Links', 'demowp' ); ?></h3>
                <ul>
                    <li>
                        <a href="<?php echo esc_url( DemoWP_Public::get_endpoint_url() ); ?>" target="_blank">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e( 'View Demo Page', 'demowp' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=demowp-active' ) ); ?>">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php esc_html_e( 'Active Demos', 'demowp' ); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=demowp-stats' ) ); ?>">
                            <span class="dashicons dashicons-chart-bar"></span>
                            <?php esc_html_e( 'Statistics', 'demowp' ); ?>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="demowp-card demowp-help-card">
                <h3><?php esc_html_e( 'How It Works', 'demowp' ); ?></h3>
                <ol>
                    <li><?php esc_html_e( 'Configure your template WordPress installation', 'demowp' ); ?></li>
                    <li><?php esc_html_e( 'Share the demo endpoint URL', 'demowp' ); ?></li>
                    <li><?php esc_html_e( 'Users solve captcha to create a demo', 'demowp' ); ?></li>
                    <li><?php esc_html_e( 'Demos are auto-deleted after expiration', 'demowp' ); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
