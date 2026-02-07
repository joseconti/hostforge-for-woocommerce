<?php
use PHPUnit\Framework\TestCase;

if ( ! interface_exists( 'WC_Logger_Interface' ) ) {
    interface WC_Logger_Interface {
        public function emergency( $message, array $context = array() );
        public function alert( $message, array $context = array() );
        public function critical( $message, array $context = array() );
        public function error( $message, array $context = array() );
        public function warning( $message, array $context = array() );
        public function notice( $message, array $context = array() );
        public function info( $message, array $context = array() );
        public function debug( $message, array $context = array() );
        public function log( $level, $message, array $context = array() );
    }
}

class FailedActionManagerAdminNoticeTest extends TestCase {
    /**
     * @runInSeparateProcess
     */
    public function test_maybe_show_admin_notice_displays_notice_with_failed_actions() {
        if ( ! function_exists( 'current_user_can' ) ) {
            function current_user_can( $cap ) { return true; }
        }
        if ( ! function_exists( 'get_option' ) ) {
            function get_option( $name, $default = false ) {
                return array(
                    1 => array( 'args' => array( 'subscription_id' => 10 ), 'type' => 'payment' ),
                    2 => array( 'args' => array( 'order_id' => 20 ), 'type' => 'expiration' ),
                );
            }
        }
        if ( ! function_exists( 'aswc_display_failed_actions_notice' ) ) {
            function aswc_display_failed_actions_notice( $failed_actions, $events ) {
                $GLOBALS['aswc_failed_actions'] = $failed_actions;
                $GLOBALS['aswc_failed_events'] = $events;
            }
        }
        if ( ! function_exists( 'aswc_get_edit_post_link' ) ) {
            function aswc_get_edit_post_link( $id ) { return 'edit.php?post=' . $id; }
        }
        if ( ! function_exists( 'aswc_is_subscription' ) ) {
            function aswc_is_subscription( $id ) { return true; }
        }
        if ( ! function_exists( 'aswc_get_order' ) ) {
            function aswc_get_order( $id ) { return (object) array( 'id' => $id ); }
        }
        if ( ! function_exists( 'add_action' ) ) {
            function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
        }

        require_once __DIR__ . '/../scheduler.php';

        $logger = new class implements WC_Logger_Interface {
            public function emergency( $message, array $context = array() ) {}
            public function alert( $message, array $context = array() ) {}
            public function critical( $message, array $context = array() ) {}
            public function error( $message, array $context = array() ) {}
            public function warning( $message, array $context = array() ) {}
            public function notice( $message, array $context = array() ) {}
            public function info( $message, array $context = array() ) {}
            public function debug( $message, array $context = array() ) {}
            public function log( $level, $message, array $context = array() ) {}
        };

        $manager = new ASWC_Scheduler_Failed_Action_Manager( $logger );
        $manager->maybe_show_admin_notice();

        $this->assertNotEmpty( $GLOBALS['aswc_failed_actions'] );
        $this->assertStringContainsString( 'payment for <a href="edit.php?post=10">#10</a>', $GLOBALS['aswc_failed_events'] );
        $this->assertStringContainsString( 'expiration for <a href="edit.php?post=20">#20</a>', $GLOBALS['aswc_failed_events'] );
    }

    /**
     * @runInSeparateProcess
     */
    public function test_maybe_show_admin_notice_deletes_option_when_ignored() {
        if ( ! function_exists( 'current_user_can' ) ) {
            function current_user_can( $cap ) { return true; }
        }
        if ( ! function_exists( 'get_option' ) ) {
            function get_option( $name, $default = false ) {
                return array( 1 => array( 'args' => array(), 'type' => 'payment' ) );
            }
        }
        if ( ! function_exists( 'delete_option' ) ) {
            function delete_option( $name ) { $GLOBALS['aswc_deleted_option'] = $name; }
        }
        if ( ! function_exists( 'wp_verify_nonce' ) ) {
            function wp_verify_nonce( $nonce, $action ) { return true; }
        }
        if ( ! function_exists( 'wc_clean' ) ) {
            function wc_clean( $value ) { return $value; }
        }
        if ( ! function_exists( 'wp_unslash' ) ) {
            function wp_unslash( $value ) { return $value; }
        }
        if ( ! function_exists( 'aswc_display_failed_actions_notice' ) ) {
            function aswc_display_failed_actions_notice( $failed_actions, $events ) {}
        }
        if ( ! function_exists( 'add_action' ) ) {
            function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
        }

        $_GET['_aswcnonce'] = 'nonce';
        $_GET['aswc_scheduled_action_timeout_error_notice'] = 'ignore';

        require_once __DIR__ . '/../scheduler.php';

        $logger = new class implements WC_Logger_Interface {
            public function emergency( $message, array $context = array() ) {}
            public function alert( $message, array $context = array() ) {}
            public function critical( $message, array $context = array() ) {}
            public function error( $message, array $context = array() ) {}
            public function warning( $message, array $context = array() ) {}
            public function notice( $message, array $context = array() ) {}
            public function info( $message, array $context = array() ) {}
            public function debug( $message, array $context = array() ) {}
            public function log( $level, $message, array $context = array() ) {}
        };

        $manager = new ASWC_Scheduler_Failed_Action_Manager( $logger );
        $manager->maybe_show_admin_notice();

        $this->assertSame( 'advanced_subscriptions_woocommerce_failed_scheduled_actions', $GLOBALS['aswc_deleted_option'] );
    }
}
