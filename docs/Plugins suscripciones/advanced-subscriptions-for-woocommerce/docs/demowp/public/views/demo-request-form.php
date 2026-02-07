<?php
/**
 * Demo Request Form Template
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
    <title><?php esc_html_e( 'Try Demo', 'demowp' ); ?> - <?php bloginfo( 'name' ); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url( DEMOWP_PLUGIN_URL . 'public/css/demowp-public.css?v=' . DEMOWP_VERSION ); ?>">
    <?php wp_head(); ?>
</head>
<body class="demowp-page demowp-form-page">
    <div class="demowp-container">
        <div class="demowp-card">
            <div class="demowp-card-header">
                <div class="demowp-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="demowp-icon">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <h1><?php esc_html_e( 'Try Demo', 'demowp' ); ?></h1>
                <p class="demowp-subtitle">
                    <?php esc_html_e( 'Create a temporary installation to explore all features.', 'demowp' ); ?>
                </p>
            </div>

            <div class="demowp-card-body">
                <?php if ( ! empty( $error ) ) : ?>
                    <div class="demowp-alert demowp-alert-error">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="demowp-alert-icon">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?php echo esc_html( $error ); ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" class="demowp-form" id="demowp-create-form">
                    <?php wp_nonce_field( 'demowp_create_demo', 'demowp_nonce' ); ?>
                    <input type="hidden" name="captcha_token" value="<?php echo esc_attr( $captcha['token'] ); ?>">

                    <?php if ( 'user' === get_option( 'demowp_email_mode', 'admin' ) ) : ?>
                    <div class="demowp-form-group">
                        <label for="user_email" class="demowp-label">
                            <?php esc_html_e( 'Your Email', 'demowp' ); ?>
                        </label>
                        <input
                            type="email"
                            id="user_email"
                            name="user_email"
                            class="demowp-input"
                            required
                            autocomplete="email"
                            placeholder="<?php esc_attr_e( 'Enter your email address', 'demowp' ); ?>"
                        >
                        <p class="demowp-form-hint">
                            <?php esc_html_e( 'This email will only be used to receive test emails in your demo. It will be automatically deleted when the demo expires and will never be stored or used for any other purpose.', 'demowp' ); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="demowp-form-group">
                        <label for="captcha_answer" class="demowp-label">
                            <?php esc_html_e( 'Verify you are human', 'demowp' ); ?>
                        </label>
                        <div class="demowp-captcha-box">
                            <div class="demowp-captcha-question">
                                <span class="demowp-captcha-num"><?php echo esc_html( $captcha['num1'] ); ?></span>
                                <span class="demowp-captcha-operator">+</span>
                                <span class="demowp-captcha-num"><?php echo esc_html( $captcha['num2'] ); ?></span>
                                <span class="demowp-captcha-equals">=</span>
                            </div>
                            <input
                                type="number"
                                id="captcha_answer"
                                name="captcha_answer"
                                class="demowp-input demowp-captcha-input"
                                required
                                autocomplete="off"
                                min="0"
                                max="100"
                                placeholder="?"
                                autofocus
                            >
                        </div>
                    </div>

                    <button type="submit" class="demowp-button demowp-button-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="demowp-button-icon">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                        <?php esc_html_e( 'Create Demo', 'demowp' ); ?>
                    </button>
                </form>

                <div class="demowp-info">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="demowp-info-icon">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <p>
                        <?php
                        $lifetime = (int) get_option( 'demowp_demo_lifetime', 3600 );
                        printf(
                            /* translators: %s: time duration like "1 hour" */
                            esc_html__( 'The demo will be available for %s.', 'demowp' ),
                            esc_html( human_time_diff( 0, $lifetime ) )
                        );
                        ?>
                    </p>
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
    <?php wp_footer(); ?>
</body>
</html>
