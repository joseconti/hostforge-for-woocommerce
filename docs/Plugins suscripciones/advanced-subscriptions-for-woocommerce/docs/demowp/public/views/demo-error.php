<?php
/**
 * Demo Error Template
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
    <title><?php esc_html_e( 'Error', 'demowp' ); ?> - <?php bloginfo( 'name' ); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url( DEMOWP_PLUGIN_URL . 'public/css/demowp-public.css?v=' . DEMOWP_VERSION ); ?>">
    <?php wp_head(); ?>
</head>
<body class="demowp-page demowp-error-page">
    <div class="demowp-container">
        <div class="demowp-card">
            <div class="demowp-card-header">
                <div class="demowp-error-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                </div>
                <h1><?php esc_html_e( 'Oops!', 'demowp' ); ?></h1>
            </div>

            <div class="demowp-card-body">
                <div class="demowp-alert demowp-alert-error">
                    <span><?php echo esc_html( $message ); ?></span>
                </div>

                <a href="<?php echo esc_url( DemoWP_Public::get_endpoint_url() ); ?>" class="demowp-button demowp-button-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="demowp-button-icon">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    <?php esc_html_e( 'Try Again', 'demowp' ); ?>
                </a>
            </div>
        </div>

        <footer class="demowp-footer">
            <p>
                <?php esc_html_e( 'Powered by', 'demowp' ); ?>
                <a href="https://plugins.joseconti.com/product/demowp-crea-demos-sandbox-temporales-en-wordpress-para-tus-productos/" target="_blank" rel="noopener noreferrer">DemoWP</a>
            </p>
        </footer>
    </div>
    <?php wp_footer(); ?>
</body>
</html>
