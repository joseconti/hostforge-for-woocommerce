<?php
/**
 * Demo Progress Template
 *
 * Shows real-time progress during demo creation.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e( 'Creating Your Demo...', 'demowp' ); ?> - <?php bloginfo( 'name' ); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url( DEMOWP_PLUGIN_URL . 'public/css/demowp-public.css?v=' . DEMOWP_VERSION ); ?>">
    <link rel="stylesheet" href="<?php echo esc_url( DEMOWP_PLUGIN_URL . 'public/css/demowp-progress.css?v=' . DEMOWP_VERSION ); ?>">
    <?php wp_head(); ?>
</head>
<body class="demowp-page demowp-progress-page">
    <div class="demowp-container">
        <div class="demowp-card demowp-progress-card">
            <div class="demowp-card-header">
                <div class="demowp-spinner-wrapper">
                    <div class="demowp-spinner"></div>
                </div>
                <h1 id="demowp-progress-title"><?php esc_html_e( 'Creating Your Demo', 'demowp' ); ?></h1>
                <p class="demowp-subtitle" id="demowp-progress-subtitle">
                    <?php esc_html_e( 'Please wait while we set up your environment...', 'demowp' ); ?>
                </p>
            </div>

            <div class="demowp-card-body">
                <!-- Progress Bar -->
                <div class="demowp-progress-bar-wrapper">
                    <div class="demowp-progress-bar">
                        <div class="demowp-progress-bar-fill" id="demowp-progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="demowp-progress-percent" id="demowp-progress-percent">0%</div>
                </div>

                <!-- Steps List -->
                <div class="demowp-steps-list" id="demowp-steps-list">
                    <div class="demowp-step" data-step="0">
                        <div class="demowp-step-icon">
                            <svg class="demowp-step-pending" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <svg class="demowp-step-active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                            </svg>
                            <svg class="demowp-step-complete" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="demowp-step-label"><?php esc_html_e( 'Initializing demo environment...', 'demowp' ); ?></div>
                    </div>
                    <div class="demowp-step" data-step="1">
                        <div class="demowp-step-icon">
                            <svg class="demowp-step-pending" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <svg class="demowp-step-active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                            </svg>
                            <svg class="demowp-step-complete" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="demowp-step-label"><?php esc_html_e( 'Creating directory structure...', 'demowp' ); ?></div>
                    </div>
                    <div class="demowp-step" data-step="2">
                        <div class="demowp-step-icon">
                            <svg class="demowp-step-pending" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <svg class="demowp-step-active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                            </svg>
                            <svg class="demowp-step-complete" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="demowp-step-label"><?php esc_html_e( 'Copying WordPress files...', 'demowp' ); ?></div>
                    </div>
                    <div class="demowp-step" data-step="3">
                        <div class="demowp-step-icon">
                            <svg class="demowp-step-pending" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <svg class="demowp-step-active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                            </svg>
                            <svg class="demowp-step-complete" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="demowp-step-label"><?php esc_html_e( 'Setting up database...', 'demowp' ); ?></div>
                    </div>
                    <div class="demowp-step" data-step="4">
                        <div class="demowp-step-icon">
                            <svg class="demowp-step-pending" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <svg class="demowp-step-active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                            </svg>
                            <svg class="demowp-step-complete" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="demowp-step-label"><?php esc_html_e( 'Creating your demo account...', 'demowp' ); ?></div>
                    </div>
                    <div class="demowp-step" data-step="5">
                        <div class="demowp-step-icon">
                            <svg class="demowp-step-pending" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <svg class="demowp-step-active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                            </svg>
                            <svg class="demowp-step-complete" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="demowp-step-label"><?php esc_html_e( 'Finalizing configuration...', 'demowp' ); ?></div>
                    </div>
                    <div class="demowp-step" data-step="6">
                        <div class="demowp-step-icon">
                            <svg class="demowp-step-pending" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                            </svg>
                            <svg class="demowp-step-active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                            </svg>
                            <svg class="demowp-step-complete" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <div class="demowp-step-label"><?php esc_html_e( 'Ready! Redirecting...', 'demowp' ); ?></div>
                    </div>
                </div>

                <!-- Error Message (hidden by default) -->
                <div class="demowp-error-message" id="demowp-error-message" style="display: none;">
                    <div class="demowp-alert demowp-alert-error">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="demowp-alert-icon">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span id="demowp-error-text"></span>
                    </div>
                    <a href="<?php echo esc_url( DemoWP_Public::get_endpoint_url() ); ?>" class="demowp-button demowp-button-secondary">
                        <?php esc_html_e( 'Try Again', 'demowp' ); ?>
                    </a>
                </div>

                <!-- Success Credentials (hidden by default) -->
                <div class="demowp-credentials" id="demowp-credentials" style="display: none;">
                    <div class="demowp-alert demowp-alert-success">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="demowp-alert-icon">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span><?php esc_html_e( 'Your demo is ready!', 'demowp' ); ?></span>
                    </div>
                    <div class="demowp-credentials-box">
                        <h3><?php esc_html_e( 'Your Credentials', 'demowp' ); ?></h3>
                        <p class="demowp-credentials-note"><?php esc_html_e( 'Save these if needed - you will be logged in automatically.', 'demowp' ); ?></p>
                        <div class="demowp-credential-row">
                            <span class="demowp-credential-label"><?php esc_html_e( 'Username:', 'demowp' ); ?></span>
                            <code id="demowp-username"></code>
                        </div>
                        <div class="demowp-credential-row">
                            <span class="demowp-credential-label"><?php esc_html_e( 'Password:', 'demowp' ); ?></span>
                            <code id="demowp-password"></code>
                        </div>
                    </div>
                    <p class="demowp-redirecting">
                        <span class="demowp-redirecting-spinner"></span>
                        <?php esc_html_e( 'Redirecting to your demo in', 'demowp' ); ?>
                        <span id="demowp-countdown">5</span>
                        <?php esc_html_e( 'seconds...', 'demowp' ); ?>
                    </p>
                </div>

                <div class="demowp-progress-note" id="demowp-progress-note">
                    <p><?php esc_html_e( 'This usually takes about 30 seconds.', 'demowp' ); ?></p>
                </div>
            </div>
        </div>

        <footer class="demowp-footer">
            <p>
                <?php esc_html_e( 'Powered by', 'demowp' ); ?>
                <a href="https://plugins.joseconti.com/product/demowp-crea-demos-sandbox-temporales-en-wordpress-para-tus-productos/" target="_blank" rel="noopener noreferrer">DemoWP</a>
            </p>
        </footer>
    </div>

    <script>
        var demowpProgressKey = '<?php echo esc_js( $progress_key ); ?>';
    </script>
    <script src="<?php echo esc_url( includes_url( 'js/jquery/jquery.min.js' ) ); ?>"></script>
    <script src="<?php echo esc_url( DEMOWP_PLUGIN_URL . 'public/js/demowp-progress.js?v=' . DEMOWP_VERSION ); ?>"></script>
    <script>
        // Localization
        var demowpProgress = {
            ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
            nonce: '<?php echo esc_js( wp_create_nonce( 'demowp_progress_nonce' ) ); ?>',
            strings: {
                error: '<?php echo esc_js( __( 'An error occurred. Please try again.', 'demowp' ) ); ?>',
                redirecting: '<?php echo esc_js( __( 'Redirecting to your demo...', 'demowp' ) ); ?>'
            }
        };
    </script>
    <?php wp_footer(); ?>
</body>
</html>
