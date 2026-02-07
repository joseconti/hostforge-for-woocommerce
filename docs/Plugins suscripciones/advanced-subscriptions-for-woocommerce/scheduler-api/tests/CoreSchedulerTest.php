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

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';

class Mock_Subscription_Core {
    private $id;
    private $last_order_id;

    public function __construct( $id = 10, $last_order_id = 5 ) {
        $this->id            = $id;
        $this->last_order_id = $last_order_id;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_last_order( $type = 'ids', $context = 'renewal' ) {
        return $this->last_order_id;
    }
}

class Test_Core_Scheduler extends ASWC_Scheduler_Core {
    public $scheduled      = array();
    public $schedule_calls = 0;

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $this->schedule_calls++;
        $this->scheduled[ $this->build_key( $action_hook, $action_args ) ] = $timestamp;
        return 1;
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        unset( $this->scheduled[ $this->build_key( $action_hook, $action_args ) ] );
    }

    public function unschedule_action( $action_hook, $action_args, $group = null ) {
        $this->unschedule_actions( $action_hook, $action_args, $group );
    }


    public function last_scheduled_action( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );
        return $this->scheduled[ $key ] ?? false;
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );
        return $this->scheduled[ $key ] ?? false;
    }

    public function has_scheduled_action( $action_hook, $action_args, $group = null ) {
        return isset( $this->scheduled[ $this->build_key( $action_hook, $action_args ) ] );
    }

    public function get_scheduled_actions( $action_hook, $action_args = array(), $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );

        if ( ! isset( $this->scheduled[ $key ] ) ) {
            return array();
        }

        $action = (object) array(
            'hook'      => $action_hook,
            'args'      => $action_args,
            'timestamp' => $this->scheduled[ $key ],
        );

        return array( $action );
    }

    public function get_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        $actions = $this->get_scheduled_actions( $action_hook, $action_args, $group );
        return reset( $actions ) ?: false;
    }

    public function get_last_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        $actions = $this->get_scheduled_actions( $action_hook, $action_args, $group );
        return end( $actions ) ?: false;
    }
}

class CoreSchedulerTest extends TestCase {
    protected $scheduler;

    protected function setUp(): void {
        $this->scheduler = new Test_Core_Scheduler();
    }

    public function test_reschedule_action_only_updates_when_timestamp_changes() {
        $hook = 'aswc_test_hook';
        $args = array( 'id' => 1 );

        $t1 = time() + 60;
        $this->scheduler->reschedule_action( $t1, $hook, $args );
        $key = $hook . ':' . json_encode( $args );
        $this->assertSame( $t1, $this->scheduler->scheduled[ $key ] );
        $this->assertSame( 1, $this->scheduler->schedule_calls );

        // Rescheduling with the same timestamp should be a no-op.
        $this->scheduler->reschedule_action( $t1, $hook, $args );
        $this->assertSame( 1, $this->scheduler->schedule_calls );
        $this->assertSame( $t1, $this->scheduler->scheduled[ $key ] );

        // A new timestamp should trigger a reschedule.
        $t2 = $t1 + 120;
        $this->scheduler->reschedule_action( $t2, $hook, $args );
        $this->assertSame( 2, $this->scheduler->schedule_calls );
        $this->assertSame( $t2, $this->scheduler->scheduled[ $key ] );
    }

    public function test_get_action_args() {
        $subscription = new Mock_Subscription_Core( 20, 99 );

        $payment_args = $this->scheduler->get_action_args( 'next_payment', $subscription );
        $this->assertSame( array( 'subscription_id' => 20 ), $payment_args );

        $retry_args = $this->scheduler->get_action_args( 'payment_retry', $subscription );
        $this->assertSame( array( 'order_id' => 99 ), $retry_args );
    }

    public function test_get_and_unschedule_action() {
        $hook = 'aswc_core_hook';
        $args = array( 'id' => 42 );

        $timestamp = time() + 30;
        $this->scheduler->schedule_action( $timestamp, $hook, $args );

        $this->assertSame( $timestamp, $this->scheduler->last_scheduled_action( $hook, $args ) );

        $action = $this->scheduler->get_scheduled_action( $hook, $args );
        $this->assertIsObject( $action );
        $this->assertSame( $timestamp, $action->timestamp );

        $actions = $this->scheduler->get_scheduled_actions( $hook, $args );
        $this->assertCount( 1, $actions );

        $this->scheduler->unschedule_action( $hook, $args );
        $this->assertFalse( $this->scheduler->has_scheduled_action( $hook, $args ) );
    }

    public function test_get_group_returns_default_action_group() {
        $this->assertSame( ASWC_Scheduler_Core::ACTION_GROUP, $this->scheduler->get_group() );
    }

    public function test_get_last_scheduled_action_returns_action_object() {
        $hook = 'aswc_core_hook';
        $args = array( 'id' => 99 );

        $timestamp = time() + 45;
        $this->scheduler->schedule_action( $timestamp, $hook, $args );

        $action = $this->scheduler->get_last_scheduled_action( $hook, $args );

        $this->assertIsObject( $action );
        $this->assertSame( $timestamp, $action->timestamp );
    }
}
