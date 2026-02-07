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

if ( ! class_exists( 'External_Distributed_Logger' ) ) {
    class External_Distributed_Logger {
        public $errors = array();
        public function error( $message, $context = array() ) {
            $this->errors[] = array( 'message' => $message, 'context' => $context );
        }
    }
}

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';

class Network_Race_Scheduler extends ASWC_Scheduler_Core {
    public $scheduled = array();
    protected $fail_group = 'node_a';

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        if ( $group === $this->fail_group ) {
            throw new Exception( 'Network failure' );
        }

        $target = $group ?? static::ACTION_GROUP;
        if ( ! isset( $this->scheduled[ $target ] ) ) {
            $this->scheduled[ $target ] = array();
        }
        $this->scheduled[ $target ][ $this->build_key( $action_hook, $action_args ) ] = $timestamp;
        return 1;
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );
        if ( false === $group ) {
            foreach ( $this->scheduled as $g => $actions ) {
                unset( $this->scheduled[ $g ][ $key ] );
            }
            return;
        }

        $target = $group ?? static::ACTION_GROUP;
        unset( $this->scheduled[ $target ][ $key ] );
    }

    public function unschedule_group( $group = null ) {
        if ( false === $group ) {
            $this->scheduled = array();
            return;
        }

        $target = $group ?? static::ACTION_GROUP;
        unset( $this->scheduled[ $target ] );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );
        if ( false === $group ) {
            foreach ( $this->scheduled as $actions ) {
                if ( isset( $actions[ $key ] ) ) {
                    return $actions[ $key ];
                }
            }
            return false;
        }
        $target = $group ?? static::ACTION_GROUP;
        return $this->scheduled[ $target ][ $key ] ?? false;
    }
}

class ExternalDistributedQueueNetworkFailureTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_network_failure_on_one_node_does_not_block_other_reschedule() {
        ASWC_Scheduler_API::$logger = new External_Distributed_Logger();
        $scheduler                 = new Network_Race_Scheduler();

        $hook  = 'aswc_test_hook';
        $args  = array( 'id' => 1 );
        $t1    = time() + 10;
        $t2    = $t1 + 20;

        $scheduler->reschedule_action( $t1, $hook, $args, 'node_a' );
        $scheduler->reschedule_action( $t2, $hook, $args, 'node_b' );

        $this->assertArrayNotHasKey( 'node_a', $scheduler->scheduled );
        $this->assertSame( $t2, $scheduler->next_scheduled_action( $hook, $args, 'node_b' ) );
        $this->assertNotEmpty( ASWC_Scheduler_API::$logger->errors );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_global_cleanup_after_network_failure_allows_reschedule() {
        ASWC_Scheduler_API::$logger = new External_Distributed_Logger();
        $scheduler                 = new Network_Race_Scheduler();

        $hook = 'aswc_test_hook';
        $args = array( 'id' => 1 );
        $t1   = time() + 10;
        $t2   = $t1 + 20;

        // Node B schedules successfully while node A fails to reschedule.
        $scheduler->schedule_action( $t1, $hook, $args, false, 'node_b' );
        $scheduler->reschedule_action( $t1, $hook, $args, 'node_a' );

        // Global cleanup clears every group before node B reschedules.
        $scheduler->unschedule_group( false );
        $this->assertEmpty( $scheduler->scheduled );

        $scheduler->reschedule_action( $t2, $hook, $args, 'node_b' );

        $this->assertArrayNotHasKey( 'node_a', $scheduler->scheduled );
        $this->assertSame( $t2, $scheduler->next_scheduled_action( $hook, $args, 'node_b' ) );
        $this->assertNotEmpty( ASWC_Scheduler_API::$logger->errors );
    }
}

