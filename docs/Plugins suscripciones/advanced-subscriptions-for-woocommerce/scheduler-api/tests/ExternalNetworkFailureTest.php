<?php
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) {
        return time();
    }
}

if ( ! class_exists( 'ASWC_Scheduler_API' ) ) {
    class ASWC_Scheduler_API {
        public static $logger;
        public static function get_logger() {
            return self::$logger;
        }
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = false ) {
        return $default;
    }
}

class Dummy_Logger {
    public $errors = array();
    public function error( $message, $context = array() ) {
        $this->errors[] = array( 'message' => $message, 'context' => $context );
    }
}

class ExternalNetworkFailureTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_schedule_action_returns_zero_on_network_error() {
        if ( ! function_exists( 'aswc_get_latest_action_scheduler_version' ) ) {
            function aswc_get_latest_action_scheduler_version() {
                return '3.6.0';
            }
        }
        if ( ! function_exists( 'aswc_schedule_single_action' ) ) {
            function aswc_schedule_single_action( $timestamp, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
                throw new Exception( 'Network failure' );
            }
        }

        require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';

        ASWC_Scheduler_API::$logger = new Dummy_Logger();
        $scheduler                  = new ASWC_Scheduler_Core();
        $result                     = $scheduler->schedule_action( time() + 10, 'aswc_test_hook', array() );

        $this->assertSame( 0, $result );
        $this->assertNotEmpty( ASWC_Scheduler_API::$logger->errors );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_reschedule_action_logs_network_error() {
        require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';

        ASWC_Scheduler_API::$logger = new Dummy_Logger();
        $scheduler                  = new class extends ASWC_Scheduler_Core {
            public $schedule_attempts = 0;
            public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
                $this->schedule_attempts++;
                throw new Exception( 'Network failure' );
            }
            public function unschedule_actions( $action_hook, $action_args, $group = null ) {
                // no-op
            }
            public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
                return false;
            }
        };

        // Should not throw even though schedule_action fails.
        $scheduler->reschedule_action( time() + 10, 'aswc_test_hook', array() );

        $this->assertSame( 1, $scheduler->schedule_attempts );
        $this->assertNotEmpty( ASWC_Scheduler_API::$logger->errors );
    }
}
