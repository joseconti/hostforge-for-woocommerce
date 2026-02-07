<?php
use PHPUnit\Framework\TestCase;

// Minimal WordPress-like stubs required to load the scheduler API.
if ( ! class_exists( 'WC_Subscription' ) ) {
    class WC_Subscription {
        public function get_time( $type ) { return time(); }
        public function get_id() { return 1; }
    }
}

// Basic filter system to mimic WordPress' behaviour during tests.
if ( ! isset( $test_filters ) ) {
    $test_filters = array();
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
        global $test_filters;
        $test_filters[ $tag ][] = $callback;
    }
}

if ( ! function_exists( 'remove_all_filters' ) ) {
    function remove_all_filters( $tag ) {
        global $test_filters;
        $test_filters[ $tag ] = array();
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        global $test_filters;
        $args = func_get_args();

        if ( isset( $test_filters[ $tag ] ) ) {
            foreach ( $test_filters[ $tag ] as $callback ) {
                $args[1] = call_user_func_array( $callback, array_slice( $args, 1 ) );
                $args   = array_merge( array( $args[0], $args[1] ), array_slice( $args, 2 ) );
            }
        }

        return $args[1];
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) {
        return time();
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        global $test_actions;
        if ( isset( $test_actions ) && is_array( $test_actions ) ) {
            $test_actions[] = $hook;
        }
    }
}

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/../background/class-aswc-scheduler-background.php';
require_once __DIR__ . '/../scheduler.php';

class Test_Background_Scheduler extends ASWC_Scheduler_Background {
    public $scheduled = array();
    public $last_next_group;
    public $last_unschedule_group;
    public $last_schedule_group;
    public $last_get_last_group;

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $group = $group ?? static::ACTION_GROUP;
        $this->last_schedule_group = $group;
        $this->scheduled[ $this->build_key( $action_hook, $action_args ) ] = $timestamp;
        return count( $this->scheduled );
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        $group = $group ?? static::ACTION_GROUP;
        $this->last_unschedule_group = $group;
        unset( $this->scheduled[ $this->build_key( $action_hook, $action_args ) ] );
    }

    public function has_scheduled_action( $action_hook, $action_args, $group = null ) {
        $group = $group ?? static::ACTION_GROUP;
        return isset( $this->scheduled[ $this->build_key( $action_hook, $action_args ) ] );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        $group = $group ?? static::ACTION_GROUP;
        $this->last_next_group = $group;
        $key = $this->build_key( $action_hook, $action_args );
        return $this->scheduled[ $key ] ?? false;
    }

    public function last_scheduled_action( $action_hook, $action_args, $group = null ) {
        $group = $group ?? static::ACTION_GROUP;
        $key = $this->build_key( $action_hook, $action_args );
        return $this->scheduled[ $key ] ?? false;
    }

    public function get_last_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        $group = $group ?? static::ACTION_GROUP;
        $this->last_get_last_group = $group;
        $key = $this->build_key( $action_hook, $action_args );

        if ( ! isset( $this->scheduled[ $key ] ) ) {
            return false;
        }

        return (object) array(
            'hook'      => $action_hook,
            'args'      => $action_args,
            'timestamp' => $this->scheduled[ $key ],
        );
    }

    public function get_scheduled_actions( $action_hook, $action_args = array(), $group = null ) {
        $group = $group ?? static::ACTION_GROUP;
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

    public function get_last_scheduled_actions( $action_hook, $action_args = array(), $group = null ) {
        $group   = $group ?? static::ACTION_GROUP;
        $matches = array();

        foreach ( $this->scheduled as $key => $timestamp ) {
            list( $hook, $json_args ) = explode( ':', $key, 2 );
            $args = json_decode( $json_args, true );

            if ( $hook !== $action_hook ) {
                continue;
            }

            if ( ! empty( $action_args ) && $args !== $action_args ) {
                continue;
            }

            $matches[] = (object) array(
                'hook'      => $hook,
                'args'      => $args,
                'timestamp' => $timestamp,
            );
        }

        if ( empty( $matches ) ) {
            return array();
        }

        $latest = max( array_map( function( $action ) { return $action->timestamp; }, $matches ) );

        return array_values( array_filter( $matches, function( $action ) use ( $latest ) {
            return $action->timestamp === $latest;
        } ) );
    }

    public function get_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        $group = $group ?? static::ACTION_GROUP;
        $actions = $this->get_scheduled_actions( $action_hook, $action_args, $group );

        return reset( $actions ) ?: false;
    }

    public function unschedule_group( $group = null ) {
        $group = $group ?? static::ACTION_GROUP;
        $this->scheduled = array();
    }
}

class BackgroundSchedulerTest extends TestCase {
    protected $scheduler;

    protected function setUp(): void {
        $this->scheduler = new Test_Background_Scheduler();
        $this->scheduler->last_next_group      = null;
        $this->scheduler->last_unschedule_group = null;
        $this->scheduler->last_schedule_group  = null;
        $this->scheduler->last_get_last_group  = null;

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'background' );
        $prop->setAccessible( true );
        $prop->setValue( null, $this->scheduler );
    }

    protected function tearDown(): void {
        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'background' );
        $prop->setAccessible( true );
        $prop->setValue( null, null );
    }

    public function test_schedule_and_unschedule_background_action() {
        $timestamp = time() + 60;
        ASWC_Scheduler_API::schedule_background_action( $timestamp, 'aswc_test_hook', array( 'id' => 1 ) );

        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_background_action( 'aswc_test_hook', array( 'id' => 1 ) ) );

        ASWC_Scheduler_API::unschedule_background_action( 'aswc_test_hook', array( 'id' => 1 ) );

        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_background_action( 'aswc_test_hook', array( 'id' => 1 ) ) );
    }

    public function test_unschedule_background_group_clears_actions() {
        ASWC_Scheduler_API::schedule_background_action( time() + 60, 'hook_one', array() );
        ASWC_Scheduler_API::schedule_background_action( time() + 120, 'hook_two', array( 'a' => 1 ) );

        $this->assertTrue( $this->scheduler->has_scheduled_action( 'hook_one', array() ) );
        $this->assertTrue( $this->scheduler->has_scheduled_action( 'hook_two', array( 'a' => 1 ) ) );

        ASWC_Scheduler_API::unschedule_background_group();

        $this->assertFalse( $this->scheduler->has_scheduled_action( 'hook_one', array() ) );
        $this->assertFalse( $this->scheduler->has_scheduled_action( 'hook_two', array( 'a' => 1 ) ) );
    }

    public function test_next_and_last_scheduled_background_action() {
        $timestamp = time() + 30;
        ASWC_Scheduler_API::schedule_background_action( $timestamp, 'aswc_hook', array( 'id' => 2 ) );

        $this->assertSame( $timestamp, ASWC_Scheduler_API::next_scheduled_background_action( 'aswc_hook', array( 'id' => 2 ) ) );
        $this->assertSame( $timestamp, ASWC_Scheduler_API::last_scheduled_background_action( 'aswc_hook', array( 'id' => 2 ) ) );
    }

    public function test_get_scheduled_background_action() {
        $timestamp = time() + 45;
        ASWC_Scheduler_API::schedule_background_action( $timestamp, 'aswc_single', array( 'b' => 3 ) );

        $action = ASWC_Scheduler_API::get_scheduled_background_action( 'aswc_single', array( 'b' => 3 ) );

        $this->assertIsObject( $action );
        $this->assertSame( $timestamp, $action->timestamp );
    }

    public function test_get_scheduled_background_actions() {
        $t1 = time() + 10;
        ASWC_Scheduler_API::schedule_background_action( $t1, 'hook_list', array( 'id' => 1 ) );

        $actions = ASWC_Scheduler_API::get_scheduled_background_actions( 'hook_list', array( 'id' => 1 ) );

        $this->assertCount( 1, $actions );
        $this->assertSame( $t1, $actions[0]->timestamp );
    }

    public function test_get_last_scheduled_background_action() {
        $timestamp = time() + 40;
        ASWC_Scheduler_API::schedule_background_action( $timestamp, 'aswc_last', array(), false, 'external' );

        $action = ASWC_Scheduler_API::get_last_scheduled_background_action( 'aswc_last', array(), 'external' );

        $this->assertIsObject( $action );
        $this->assertSame( $timestamp, $action->timestamp );
        $this->assertSame( 'external', $this->scheduler->last_get_last_group );
    }

    public function test_get_last_scheduled_background_actions() {
        $t1 = time() + 30;
        $t2 = $t1 + 30;

        ASWC_Scheduler_API::schedule_background_action( $t1, 'aswc_multi', array( 'id' => 1 ) );
        ASWC_Scheduler_API::schedule_background_action( $t2, 'aswc_multi', array( 'id' => 2 ) );
        ASWC_Scheduler_API::schedule_background_action( $t2, 'aswc_multi', array( 'id' => 3 ) );

        $actions = ASWC_Scheduler_API::get_last_scheduled_background_actions( 'aswc_multi' );

        $this->assertCount( 2, $actions );
        foreach ( $actions as $action ) {
            $this->assertSame( $t2, $action->timestamp );
        }
    }

    public function test_reschedule_background_action_ignores_group() {
        $timestamp = time() + 60;
        ASWC_Scheduler_API::schedule_background_action( $timestamp, 'aswc_old', array( 'id' => 3 ) );

        ASWC_Scheduler_API::reschedule_background_action( $timestamp + 30, 'aswc_old', array( 'id' => 3 ), false );

        $this->assertFalse( $this->scheduler->last_next_group );
        $this->assertFalse( $this->scheduler->last_unschedule_group );
    }

    public function test_schedule_background_action_with_custom_group() {
        $timestamp = time() + 20;
        ASWC_Scheduler_API::schedule_background_action( $timestamp, 'aswc_group', array(), false, 'external' );

        $this->assertSame( 'external', $this->scheduler->last_schedule_group );
    }

    public function test_schedule_background_action_uses_default_group() {
        $timestamp = time() + 25;
        ASWC_Scheduler_API::schedule_background_action( $timestamp, 'aswc_default', array() );

        $this->assertSame( Test_Background_Scheduler::ACTION_GROUP, $this->scheduler->last_schedule_group );
    }

    public function test_get_group_returns_background_action_group() {
        $scheduler = new ASWC_Scheduler_Background();
        $this->assertSame( ASWC_Scheduler_Background::ACTION_GROUP, $scheduler->get_group() );
    }

    public function test_reschedule_background_action_replaces_existing_action() {
        $hook = 'aswc_reschedule';
        $args = array( 'id' => 5 );
        $t1   = time() + 10;
        ASWC_Scheduler_API::schedule_background_action( $t1, $hook, $args );

        $t2 = $t1 + 10;
        ASWC_Scheduler_API::reschedule_background_action( $t2, $hook, $args );

        $this->assertSame( $t2, ASWC_Scheduler_API::next_scheduled_background_action( $hook, $args ) );
    }
}
