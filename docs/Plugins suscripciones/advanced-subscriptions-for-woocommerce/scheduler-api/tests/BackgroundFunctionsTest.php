<?php
use PHPUnit\Framework\TestCase;

// Minimal WordPress-like stubs required to load the scheduler API.
if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) { return $value; }
}
if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) { return time(); }
}
if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) { /* no-op */ }
}
if ( ! class_exists( 'WC_Subscription' ) ) {
    class WC_Subscription { public function get_time( $type ) { return time(); } public function get_id() { return 1; } }
}

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/../background/class-aswc-scheduler-background.php';
require_once __DIR__ . '/../scheduler.php';

class BackgroundFunctionsTest extends TestCase {
    protected $scheduler;

    protected function setUp(): void {
        $this->scheduler = new class extends ASWC_Scheduler_Background {
            public $scheduled = array();

            protected function build_key( $hook, $args, $group = null ) {
                ksort( $args );
                $group = $group ?? self::ACTION_GROUP;
                return $group . '|' . $hook . ':' . json_encode( $args );
            }

            public function schedule_action( $timestamp, $hook, $args, $unique = false, $group = null ) {
                $key                        = $this->build_key( $hook, $args, $group );
                $this->scheduled[ $key ][]   = $this->create_action( $timestamp, $hook, $args, $group );
                return $timestamp;
            }

            protected function create_action( $timestamp, $hook, $args, $group ) {
                return new class( $timestamp, $hook, $args, $group ) {
                    public $timestamp;
                    public $hook;
                    public $args;
                    public $group;

                    public function __construct( $timestamp, $hook, $args, $group ) {
                        $this->timestamp = $timestamp;
                        $this->hook      = $hook;
                        $this->args      = $args;
                        $this->group     = $group;
                    }

                    public function get_schedule() {
                        return new class( $this->timestamp ) {
                            public $timestamp;
                            public function __construct( $timestamp ) { $this->timestamp = $timestamp; }
                            public function get_date() { return new DateTime( '@' . $this->timestamp ); }
                        };
                    }

                    public function get_hook() { return $this->hook; }
                    public function get_args() { return $this->args; }
                    public function get_group() { return $this->group; }
                };
            }

            public function unschedule_actions( $hook, $args, $group = null ) {
                unset( $this->scheduled[ $this->build_key( $hook, $args, $group ) ] );
            }

            public function has_scheduled_action( $hook, $args, $group = null ) {
                return ! empty( $this->scheduled[ $this->build_key( $hook, $args, $group ) ] );
            }

            public function reschedule_action( $timestamp, $hook, $args, $group = null ) {
                $key = $this->build_key( $hook, $args, $group );
                if ( empty( $this->scheduled[ $key ] ) ) {
                    $this->scheduled[ $key ][] = $this->create_action( $timestamp, $hook, $args, $group );
                } else {
                    $this->scheduled[ $key ][0]->timestamp = $timestamp;
                }
            }

            public function next_scheduled_action( $hook, $args, $group = null ) {
                $key = $this->build_key( $hook, $args, $group );
                if ( empty( $this->scheduled[ $key ] ) ) {
                    return false;
                }
                $timestamps = array_map( fn( $a ) => $a->timestamp, $this->scheduled[ $key ] );
                return min( $timestamps );
            }

            public function last_scheduled_action( $hook, $args, $group = null ) {
                $key = $this->build_key( $hook, $args, $group );
                if ( empty( $this->scheduled[ $key ] ) ) {
                    return false;
                }
                $timestamps = array_map( fn( $a ) => $a->timestamp, $this->scheduled[ $key ] );
                return max( $timestamps );
            }

            public function get_scheduled_action( $hook, $args = array(), $group = null ) {
                $key = $this->build_key( $hook, $args, $group );
                return $this->scheduled[ $key ][0] ?? false;
            }

            public function get_scheduled_actions( $hook, $args = array(), $group = null ) {
                $key = $this->build_key( $hook, $args, $group );
                return $this->scheduled[ $key ] ?? array();
            }

            public function get_last_scheduled_action( $hook, $args = array(), $group = null ) {
                $key = $this->build_key( $hook, $args, $group );
                if ( empty( $this->scheduled[ $key ] ) ) {
                    return false;
                }
                $actions   = $this->scheduled[ $key ];
                $max_time  = max( array_map( fn( $a ) => $a->timestamp, $actions ) );
                foreach ( array_reverse( $actions ) as $action ) {
                    if ( $action->timestamp === $max_time ) {
                        return $action;
                    }
                }
                return false;
            }

            public function get_last_scheduled_actions( $hook, $args = array(), $group = null ) {
                $key = $this->build_key( $hook, $args, $group );
                if ( empty( $this->scheduled[ $key ] ) ) {
                    return array();
                }
                $actions  = $this->scheduled[ $key ];
                $max_time = max( array_map( fn( $a ) => $a->timestamp, $actions ) );
                return array_values( array_filter( $actions, fn( $a ) => $a->timestamp === $max_time ) );
            }

            public function unschedule_group( $group = null ) {
                if ( false === $group ) {
                    $this->scheduled = array();
                    return;
                }
                $group = $group ?? self::ACTION_GROUP;
                foreach ( array_keys( $this->scheduled ) as $key ) {
                    if ( str_starts_with( $key, $group . '|' ) ) {
                        unset( $this->scheduled[ $key ] );
                    }
                }
            }
        };

        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'background' );
        $prop->setAccessible( true );
        $prop->setValue( null, $this->scheduler );
    }

    protected function tearDown(): void {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'background' );
        $prop->setAccessible( true );
        $prop->setValue( null, null );
    }

    public function test_schedule_and_unschedule_background_action_wrappers() {
        $timestamp = time() + 60;
        aswc_schedule_background_action( $timestamp, 'aswc_test_hook', array( 'id' => 1 ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_background_action( 'aswc_test_hook', array( 'id' => 1 ) ) );
        aswc_unschedule_background_action( 'aswc_test_hook', array( 'id' => 1 ) );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_background_action( 'aswc_test_hook', array( 'id' => 1 ) ) );
    }

    public function test_unschedule_background_group_wrapper_clears_actions() {
        aswc_schedule_background_action( time() + 60, 'hook_one', array() );
        aswc_schedule_background_action( time() + 120, 'hook_two', array( 'a' => 1 ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_background_action( 'hook_one', array() ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_background_action( 'hook_two', array( 'a' => 1 ) ) );
        aswc_unschedule_background_group();
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_background_action( 'hook_one', array() ) );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_background_action( 'hook_two', array( 'a' => 1 ) ) );
    }

    public function test_reschedule_and_next_background_action_wrappers() {
        $hook      = 'aswc_reschedule_hook';
        $args      = array( 'id' => 10 );
        $timestamp = time() + 30;

        aswc_schedule_background_action( $timestamp, $hook, $args );
        $this->assertSame( $timestamp, aswc_next_scheduled_background_action( $hook, $args ) );

        $new_time = $timestamp + 30;
        aswc_reschedule_background_action( $new_time, $hook, $args );

        $this->assertSame( $new_time, aswc_next_scheduled_background_action( $hook, $args ) );
    }

    public function test_get_scheduled_background_action_wrappers() {
        $hook = 'aswc_get_hook';
        $args = array( 'a' => 2 );
        $time = time() + 90;

        aswc_schedule_background_action( $time, $hook, $args );

        $action  = ASWC_Scheduler_API::get_scheduled_background_action( $hook, $args );
        $actions = ASWC_Scheduler_API::get_scheduled_background_actions( $hook, $args );

        $this->assertIsObject( $action );
        $this->assertSame( $time, $action->timestamp );
        $this->assertCount( 1, $actions );
        $this->assertSame( $action, $actions[0] );

        aswc_schedule_background_action( $time, $hook, $args, false, 'custom_group' );
        $this->assertCount( 1, ASWC_Scheduler_API::get_scheduled_background_actions( $hook, $args ) );
        $this->assertCount( 1, ASWC_Scheduler_API::get_scheduled_background_actions( $hook, $args, 'custom_group' ) );
    }

    public function test_last_scheduled_background_action_wrapper() {
        $hook = 'aswc_last_hook';
        $args = array( 'k' => 'v' );

        $first  = time() + 45;
        $second = $first + 30;
        $third  = $second;

        aswc_schedule_background_action( $first, $hook, $args );
        aswc_schedule_background_action( $second, $hook, $args );
        aswc_schedule_background_action( $third, $hook, $args );

        $last_action  = ASWC_Scheduler_API::get_last_scheduled_background_action( $hook, $args );
        $last_actions = ASWC_Scheduler_API::get_last_scheduled_background_actions( $hook, $args );
        $last_time    = ASWC_Scheduler_API::last_scheduled_background_action( $hook, $args );

        $this->assertSame( $second, $last_time );
        $this->assertSame( $second, $last_action->timestamp );
        $this->assertCount( 2, $last_actions );

        aswc_schedule_background_action( $first, $hook, $args, false, 'alt_group' );
        $this->assertFalse( ASWC_Scheduler_API::get_last_scheduled_background_action( $hook, $args, 'missing' ) );
        $this->assertSame( $first, ASWC_Scheduler_API::get_last_scheduled_background_action( $hook, $args, 'alt_group' )->timestamp );
    }
}

