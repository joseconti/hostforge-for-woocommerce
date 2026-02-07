<?php
/**
 * Captcha System
 *
 * Handles numeric captcha generation and validation.
 *
 * @package DemoWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DemoWP_Captcha
 *
 * Simple numeric captcha for demo creation forms.
 *
 * @since 1.0.0
 */
class DemoWP_Captcha {

    /**
     * Transient prefix for captcha tokens
     *
     * @var string
     */
    const TRANSIENT_PREFIX = 'demowp_captcha_';

    /**
     * Captcha expiration time in seconds
     *
     * @var int
     */
    const EXPIRATION = 300; // 5 minutes

    /**
     * Generate a new captcha
     *
     * @return array Captcha data including token and question.
     */
    public static function generate() {
        // Generate two random numbers for addition
        $num1 = wp_rand( 1, 15 );
        $num2 = wp_rand( 1, 15 );

        // Calculate the answer
        $answer = $num1 + $num2;

        // Generate unique token
        $token = DemoWP_Utils::generate_random_string( 32 );

        // Store answer in transient
        set_transient(
            self::TRANSIENT_PREFIX . $token,
            array(
                'answer'  => $answer,
                'created' => time(),
            ),
            self::EXPIRATION
        );

        return array(
            'token'    => $token,
            'question' => sprintf( '%d + %d', $num1, $num2 ),
            'num1'     => $num1,
            'num2'     => $num2,
        );
    }

    /**
     * Validate a captcha response
     *
     * @param string $token       The captcha token.
     * @param int    $user_answer The user's answer.
     * @return bool True if valid, false otherwise.
     */
    public static function validate( $token, $user_answer ) {
        if ( empty( $token ) ) {
            return false;
        }

        // Sanitize token
        $token = sanitize_text_field( $token );

        // Get stored data
        $data = get_transient( self::TRANSIENT_PREFIX . $token );

        // Token doesn't exist or expired
        if ( false === $data ) {
            return false;
        }

        // Delete transient immediately (one-time use)
        delete_transient( self::TRANSIENT_PREFIX . $token );

        // Compare answers
        return (int) $user_answer === (int) $data['answer'];
    }

    /**
     * Clean up expired captcha transients
     *
     * This is called periodically to clean up orphaned transients.
     */
    public static function cleanup_expired() {
        global $wpdb;

        // Delete expired captcha transients
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                WHERE option_name LIKE %s
                AND option_name LIKE %s",
                $wpdb->esc_like( '_transient_' . self::TRANSIENT_PREFIX ) . '%',
                $wpdb->esc_like( '_transient_timeout_' . self::TRANSIENT_PREFIX ) . '%'
            )
        );
    }

    /**
     * Get remaining time for a captcha token
     *
     * @param string $token The captcha token.
     * @return int Remaining seconds, or 0 if expired.
     */
    public static function get_remaining_time( $token ) {
        $data = get_transient( self::TRANSIENT_PREFIX . $token );

        if ( false === $data ) {
            return 0;
        }

        $elapsed = time() - $data['created'];
        $remaining = self::EXPIRATION - $elapsed;

        return max( 0, $remaining );
    }

    /**
     * Render the captcha HTML
     *
     * @param array $captcha Captcha data from generate().
     * @return string HTML output.
     */
    public static function render( $captcha ) {
        ob_start();
        ?>
        <div class="demowp-captcha-wrapper">
            <input type="hidden" name="captcha_token" value="<?php echo esc_attr( $captcha['token'] ); ?>">

            <div class="demowp-captcha-question">
                <label for="captcha_answer">
                    <?php
                    printf(
                        /* translators: %s: math question like "7 + 5" */
                        esc_html__( 'What is %s?', 'demowp' ),
                        '<strong>' . esc_html( $captcha['question'] ) . '</strong>'
                    );
                    ?>
                </label>
            </div>

            <div class="demowp-captcha-input">
                <input
                    type="number"
                    id="captcha_answer"
                    name="captcha_answer"
                    required
                    autocomplete="off"
                    min="0"
                    max="100"
                    placeholder="<?php esc_attr_e( 'Your answer', 'demowp' ); ?>"
                    class="demowp-captcha-field"
                >
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
