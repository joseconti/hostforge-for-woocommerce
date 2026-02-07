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

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';

class InMemoryDistributedQueue {
    public $scheduled = array();

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule( $timestamp, $hook, $args, $group ) {
        $target = $group ?? ASWC_Scheduler_Core::ACTION_GROUP;
        if ( ! isset( $this->scheduled[ $target ] ) ) {
            $this->scheduled[ $target ] = array();
        }
        $this->scheduled[ $target ][ $this->build_key( $hook, $args ) ] = $timestamp;
    }

    public function unschedule( $hook, $args, $group ) {
        $key = $this->build_key( $hook, $args );

        if ( false === $group ) {
            foreach ( $this->scheduled as $g => $actions ) {
                unset( $this->scheduled[ $g ][ $key ] );
            }
            return;
        }

        $target = $group ?? ASWC_Scheduler_Core::ACTION_GROUP;
        unset( $this->scheduled[ $target ][ $key ] );
    }

    public function clear_group( $group ) {
        unset( $this->scheduled[ $group ] );
    }

    public function clear_all() {
        $this->scheduled = array();
    }

    public function next( $hook, $args, $group ) {
        $key = $this->build_key( $hook, $args );

        if ( false === $group ) {
            foreach ( $this->scheduled as $actions ) {
                if ( isset( $actions[ $key ] ) ) {
                    return $actions[ $key ];
                }
            }
            return false;
        }

        $target = $group ?? ASWC_Scheduler_Core::ACTION_GROUP;
        return $this->scheduled[ $target ][ $key ] ?? false;
    }
}

class Distributed_Queue_Scheduler extends ASWC_Scheduler_Core {
    protected $queue;

    public function __construct( InMemoryDistributedQueue $queue ) {
        $this->queue = $queue;
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $this->queue->schedule( $timestamp, $action_hook, $action_args, $group );
        return 1;
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        $target = ( null === $group ) ? static::ACTION_GROUP : $group;
        $this->queue->unschedule( $action_hook, $action_args, $target );
    }

    public function unschedule_group( $group = null ) {
        if ( false === $group ) {
            $this->queue->clear_all();
            return;
        }

        $this->queue->clear_group( $group ?? static::ACTION_GROUP );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        $target = ( null === $group ) ? static::ACTION_GROUP : $group;
        return $this->queue->next( $action_hook, $action_args, $target );
    }
}

class ExternalDistributedQueueIntegrationTest extends TestCase {
    public function test_multiple_nodes_share_queue_and_last_reschedule_wins() {
        $queue = new InMemoryDistributedQueue();
        $nodeA = new Distributed_Queue_Scheduler( $queue );
        $nodeB = new Distributed_Queue_Scheduler( $queue );

        $hook  = 'aswc_test_hook';
        $args  = array( 'id' => 1 );
        $t1    = time() + 10;
        $t2    = $t1 + 20;
        $group = 'dist';

        $nodeA->schedule_action( $t1, $hook, $args, false, $group );
        $nodeB->reschedule_action( $t2, $hook, $args, $group );

        $this->assertSame( $t2, $nodeA->next_scheduled_action( $hook, $args, $group ) );
        $this->assertSame( $t2, $nodeB->next_scheduled_action( $hook, $args, $group ) );
    }

    public function test_global_cleanup_clears_actions_across_nodes() {
        $queue = new InMemoryDistributedQueue();
        $nodeA = new Distributed_Queue_Scheduler( $queue );
        $nodeB = new Distributed_Queue_Scheduler( $queue );

        $hook = 'aswc_test_hook';
        $args = array( 'id' => 1 );
        $t    = time() + 10;

        $nodeA->schedule_action( $t, $hook, $args, false, 'node_a' );
        $nodeB->schedule_action( $t, $hook, $args, false, 'node_b' );

        $nodeA->unschedule_group( false );

        $this->assertFalse( $nodeA->next_scheduled_action( $hook, $args, 'node_a' ) );
        $this->assertFalse( $nodeB->next_scheduled_action( $hook, $args, 'node_b' ) );
    }

    public function test_reschedule_after_remote_cleanup_is_visible_to_all_nodes() {
        $queue = new InMemoryDistributedQueue();
        $nodeA = new Distributed_Queue_Scheduler( $queue );
        $nodeB = new Distributed_Queue_Scheduler( $queue );

        $hook = 'aswc_test_hook';
        $args = array( 'id' => 1 );
        $t1   = time() + 10;
        $t2   = $t1 + 20;

        $nodeA->schedule_action( $t1, $hook, $args, false, 'node_a' );
        $nodeB->unschedule_group( false );
        $nodeA->reschedule_action( $t2, $hook, $args, 'node_a' );

        $this->assertSame( $t2, $nodeA->next_scheduled_action( $hook, $args, 'node_a' ) );
        $this->assertSame( $t2, $nodeB->next_scheduled_action( $hook, $args, 'node_a' ) );
    }
}
