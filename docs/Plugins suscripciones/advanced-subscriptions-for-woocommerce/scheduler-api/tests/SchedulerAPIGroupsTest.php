<?php
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // No-op for tests.
    }
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
    define( 'DAY_IN_SECONDS', 24 * 60 * 60 );
}
if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
    define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
}
if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
    define( 'MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS );
}
if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
    define( 'YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS );
}

require_once __DIR__ . '/../scheduler.php';

class Test_Core_Group_Scheduler extends ASWC_Scheduler_Core {
    public $scheduled = array();

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
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
}

class Test_Notification_Group_Scheduler extends ASWC_Scheduler_Notifications {
    public $scheduled_actions = array();

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $target = $group ?? static::ACTION_GROUP;
        if ( ! isset( $this->scheduled_actions[ $target ] ) ) {
            $this->scheduled_actions[ $target ] = array();
        }
        $this->scheduled_actions[ $target ][ $this->build_key( $action_hook, $action_args ) ] = $timestamp;
        return 1;
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );

        if ( false === $group ) {
            foreach ( $this->scheduled_actions as $g => $actions ) {
                unset( $this->scheduled_actions[ $g ][ $key ] );
            }
            return;
        }

        $target = $group ?? static::ACTION_GROUP;
        unset( $this->scheduled_actions[ $target ][ $key ] );
    }

    public function unschedule_group( $group = null ) {
        if ( false === $group ) {
            $this->scheduled_actions = array();
            return;
        }

        $target = $group ?? static::ACTION_GROUP;
        unset( $this->scheduled_actions[ $target ] );
    }
}

class Test_Background_Group_Scheduler extends ASWC_Scheduler_Background {
    public $scheduled = array();

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
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
}

class SchedulerAPIGroupsTest extends TestCase {
    protected $core;
    protected $notifications;
    protected $background;

    protected function setUp(): void {
        $this->core          = new Test_Core_Group_Scheduler();
        $this->notifications = new Test_Notification_Group_Scheduler();
        $this->background    = new Test_Background_Group_Scheduler();

        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );

        foreach ( array( 'core' => $this->core, 'notifications' => $this->notifications, 'background' => $this->background ) as $prop => $instance ) {
            $p = $ref->getProperty( $prop );
            $p->setAccessible( true );
            $p->setValue( null, $instance );
        }
    }

    protected function tearDown(): void {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );

        foreach ( array( 'core', 'notifications', 'background' ) as $prop ) {
            $p = $ref->getProperty( $prop );
            $p->setAccessible( true );
            $p->setValue( null, null );
        }
    }

    public function test_unschedule_notification_group_clears_actions() {
        $this->notifications->schedule_action( time() + 60, 'hook_one', array() );

        $this->assertNotEmpty( $this->notifications->scheduled_actions );

        ASWC_Scheduler_API::unschedule_notification_group();

        $this->assertEmpty( $this->notifications->scheduled_actions );
    }

    public function test_unschedule_notification_group_wrapper_clears_actions() {
        $this->notifications->schedule_action( time() + 60, 'hook_two', array() );

        $this->assertNotEmpty( $this->notifications->scheduled_actions );

        aswc_unschedule_notification_group();

        $this->assertEmpty( $this->notifications->scheduled_actions );
    }

    public function test_unschedule_core_group_clears_actions() {
        $this->core->schedule_action( time() + 60, 'core_hook', array() );

        $this->assertNotEmpty( $this->core->scheduled );

        ASWC_Scheduler_API::unschedule_core_group();

        $this->assertEmpty( $this->core->scheduled );
    }

    public function test_unschedule_background_group_clears_actions() {
        $this->background->schedule_action( time() + 60, 'bg_hook', array() );

        $this->assertNotEmpty( $this->background->scheduled );

        ASWC_Scheduler_API::unschedule_background_group();

        $this->assertEmpty( $this->background->scheduled );
    }

    public function test_unschedule_all_groups_clears_all_actions() {
        $this->core->schedule_action( time() + 60, 'core_hook', array() );
        $this->notifications->schedule_action( time() + 60, 'notif_hook', array() );
        $this->background->schedule_action( time() + 60, 'bg_hook', array() );

        $this->assertNotEmpty( $this->core->scheduled );
        $this->assertNotEmpty( $this->notifications->scheduled_actions );
        $this->assertNotEmpty( $this->background->scheduled );

        ASWC_Scheduler_API::unschedule_all_groups();

        $this->assertEmpty( $this->core->scheduled );
        $this->assertEmpty( $this->notifications->scheduled_actions );
        $this->assertEmpty( $this->background->scheduled );
    }

    public function test_unschedule_actions_removes_specific_action() {
        $args_one = array( 'a' => 1 );
        $args_two = array( 'b' => 2 );

        $this->core->schedule_action( time() + 60, 'hook_one', $args_one );
        $this->core->schedule_action( time() + 60, 'hook_two', $args_two );

        $this->assertNotEmpty( $this->core->scheduled );

        ASWC_Scheduler_API::unschedule_actions( 'hook_one', $args_one );

        $group = ASWC_Scheduler_Core::ACTION_GROUP;
        $this->assertArrayNotHasKey( 'hook_one:' . json_encode( $args_one ), $this->core->scheduled[ $group ] );
        $this->assertArrayHasKey( 'hook_two:' . json_encode( $args_two ), $this->core->scheduled[ $group ] );
    }

    public function test_unschedule_notification_group_custom_group() {
        $this->notifications->schedule_action( time() + 60, 'hook_custom', array(), false, 'external' );
        $this->assertArrayHasKey( 'external', $this->notifications->scheduled_actions );
        ASWC_Scheduler_API::unschedule_notification_group( 'external' );
        $this->assertArrayNotHasKey( 'external', $this->notifications->scheduled_actions );
    }

    public function test_unschedule_notification_group_wrapper_custom_group() {
        $this->notifications->schedule_action( time() + 60, 'hook_wrapper', array(), false, 'external' );
        $this->assertArrayHasKey( 'external', $this->notifications->scheduled_actions );
        aswc_unschedule_notification_group( 'external' );
        $this->assertArrayNotHasKey( 'external', $this->notifications->scheduled_actions );
    }

    public function test_unschedule_core_group_custom_group() {
        $this->core->schedule_action( time() + 60, 'core_custom', array(), false, 'external' );
        $this->assertArrayHasKey( 'external', $this->core->scheduled );
        ASWC_Scheduler_API::unschedule_core_group( 'external' );
        $this->assertArrayNotHasKey( 'external', $this->core->scheduled );
    }

    public function test_unschedule_background_group_custom_group() {
        $this->background->schedule_action( time() + 60, 'bg_custom', array(), false, 'external' );
        $this->assertArrayHasKey( 'external', $this->background->scheduled );
        ASWC_Scheduler_API::unschedule_background_group( 'external' );
        $this->assertArrayNotHasKey( 'external', $this->background->scheduled );
    }

    public function test_unschedule_background_action_custom_group() {
        $this->background->schedule_action( time() + 60, 'bg_action', array(), false, 'external' );
        $this->assertArrayHasKey( 'bg_action:' . json_encode( array() ), $this->background->scheduled['external'] );
        ASWC_Scheduler_API::unschedule_background_action( 'bg_action', array(), 'external' );
        $this->assertArrayNotHasKey( 'bg_action:' . json_encode( array() ), $this->background->scheduled['external'] );
    }

    public function test_unschedule_background_action_ignores_group_when_false() {
        $this->background->schedule_action( time() + 60, 'bg_action', array(), false, 'external_one' );
        $this->background->schedule_action( time() + 60, 'bg_action', array(), false, 'external_two' );
        ASWC_Scheduler_API::unschedule_background_action( 'bg_action', array(), false );
        $this->assertArrayNotHasKey( 'bg_action:' . json_encode( array() ), $this->background->scheduled['external_one'] );
        $this->assertArrayNotHasKey( 'bg_action:' . json_encode( array() ), $this->background->scheduled['external_two'] );
    }
}

