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

class Race_Test_Core_Scheduler extends ASWC_Scheduler_Core {
    public $scheduled      = array();
    public $schedule_calls = 0;

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $this->schedule_calls++;
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

class ExternalIntegrationRaceConditionTest extends TestCase {
    protected $scheduler;

    protected function setUp(): void {
        $this->scheduler = new Race_Test_Core_Scheduler();
    }

    public function test_external_actions_survive_core_unschedule() {
        $hook      = 'aswc_test_hook';
        $args      = array( 'id' => 1 );
        $timestamp = time() + 10;

        // External plugin schedules in a custom group while core schedules the same hook.
        $this->scheduler->schedule_action( $timestamp, $hook, $args, false, 'external' );
        $this->scheduler->schedule_action( $timestamp, $hook, $args );

        // Core unschedules its default group.
        $this->scheduler->unschedule_group( ASWC_Scheduler_Core::ACTION_GROUP );

        $this->assertArrayHasKey( 'external', $this->scheduler->scheduled );
        $this->assertArrayNotHasKey( ASWC_Scheduler_Core::ACTION_GROUP, $this->scheduler->scheduled );
    }

    public function test_reschedule_is_idempotent_under_race_conditions() {
        $hook = 'aswc_test_hook';
        $args = array( 'id' => 1 );
        $t1   = time() + 60;

        // Two processes attempt to reschedule with the same timestamp.
        $this->scheduler->reschedule_action( $t1, $hook, $args );
        $this->scheduler->reschedule_action( $t1, $hook, $args );

        $this->assertSame( 1, $this->scheduler->schedule_calls );

        // A different timestamp should trigger a new schedule.
        $t2 = $t1 + 30;
        $this->scheduler->reschedule_action( $t2, $hook, $args );

        $this->assertSame( 2, $this->scheduler->schedule_calls );
    }

    public function test_unschedule_across_all_groups_removes_external_actions() {
        $hook      = 'aswc_test_hook';
        $args      = array( 'id' => 1 );
        $timestamp = time() + 10;

        $this->scheduler->schedule_action( $timestamp, $hook, $args, false, 'external' );
        $this->scheduler->schedule_action( $timestamp, $hook, $args );

        $this->scheduler->unschedule_group( false );

        $this->assertEmpty( $this->scheduler->scheduled );
    }

    public function test_unschedule_action_across_all_groups_removes_external_and_core_actions() {
        $hook      = 'aswc_test_hook';
        $args      = array( 'id' => 1 );
        $timestamp = time() + 10;

        $this->scheduler->schedule_action( $timestamp, $hook, $args, false, 'external' );
        $this->scheduler->schedule_action( $timestamp, $hook, $args );

        $this->scheduler->unschedule_actions( $hook, $args, false );

        $this->assertFalse( $this->scheduler->next_scheduled_action( $hook, $args, false ) );
    }

    public function test_unschedule_single_external_group_preserves_other_groups() {
        $hook      = 'aswc_test_hook';
        $args      = array( 'id' => 1 );
        $timestamp = time() + 10;

        $this->scheduler->schedule_action( $timestamp, $hook, $args, false, 'plugin_a' );
        $this->scheduler->schedule_action( $timestamp, $hook, $args, false, 'plugin_b' );
        $this->scheduler->schedule_action( $timestamp, $hook, $args );

        $this->scheduler->unschedule_group( 'plugin_a' );

        $this->assertFalse( $this->scheduler->next_scheduled_action( $hook, $args, 'plugin_a' ) );
        $this->assertSame( $timestamp, $this->scheduler->next_scheduled_action( $hook, $args, 'plugin_b' ) );
        $this->assertSame( $timestamp, $this->scheduler->next_scheduled_action( $hook, $args ) );
    }

    public function test_concurrent_reschedules_keep_latest_timestamp() {
        $hook     = 'aswc_test_hook';
        $args     = array( 'id' => 1 );
        $initial  = time() + 10;
        $t1       = $initial + 30;
        $t2       = $t1 + 30;

        $this->scheduler->schedule_action( $initial, $hook, $args );

        // Process A begins reschedule to $t1 (unschedules original action).
        $this->scheduler->unschedule_actions( $hook, $args );

        // Process B reschedules to $t2 while A is still in progress.
        $this->scheduler->reschedule_action( $t2, $hook, $args );

        // Process A completes scheduling with its own timestamp.
        $this->scheduler->schedule_action( $t1, $hook, $args );

        $this->assertSame( $t1, $this->scheduler->next_scheduled_action( $hook, $args ) );
    }

    public function test_external_reschedule_after_global_unschedule() {
        $hook     = 'aswc_test_hook';
        $args     = array( 'id' => 1 );
        $initial  = time() + 10;
        $resched  = $initial + 20;

        // External plugin schedules and then a global cleanup runs.
        $this->scheduler->schedule_action( $initial, $hook, $args, false, 'external' );
        $this->scheduler->unschedule_group( false );

        // Plugin reschedules after the cleanup.
        $this->scheduler->reschedule_action( $resched, $hook, $args, 'external' );

        $this->assertSame( $resched, $this->scheduler->next_scheduled_action( $hook, $args, 'external' ) );
    }

    public function test_global_unschedule_after_external_reschedule_removes_action() {
        $hook     = 'aswc_test_hook';
        $args     = array( 'id' => 1 );
        $initial  = time() + 10;
        $resched  = $initial + 20;

        // External plugin reschedules to a new timestamp.
        $this->scheduler->schedule_action( $initial, $hook, $args, false, 'external' );
        $this->scheduler->reschedule_action( $resched, $hook, $args, 'external' );

        // A concurrent process clears all scheduled actions.
        $this->scheduler->unschedule_group( false );

        $this->assertFalse( $this->scheduler->next_scheduled_action( $hook, $args, 'external' ) );
    }

    public function test_multiple_external_plugins_with_global_cleanup() {
        $hook    = 'aswc_test_hook';
        $args    = array( 'id' => 1 );
        $t0      = time() + 10;
        $t1      = $t0 + 20;
        $t2      = $t1 + 20;

        // Two external plugins and core schedule the same hook.
        $this->scheduler->schedule_action( $t0, $hook, $args, false, 'plugin_a' );
        $this->scheduler->schedule_action( $t0, $hook, $args, false, 'plugin_b' );
        $this->scheduler->schedule_action( $t0, $hook, $args );

        // Plugin A unschedules its own group while plugin B reschedules.
        $this->scheduler->unschedule_group( 'plugin_a' );
        $this->scheduler->reschedule_action( $t1, $hook, $args, 'plugin_b' );

        // Global cleanup happens and core schedules again.
        $this->scheduler->unschedule_group( false );
        $this->scheduler->schedule_action( $t2, $hook, $args );

        $this->assertSame( $t2, $this->scheduler->next_scheduled_action( $hook, $args ) );
        $this->assertFalse( $this->scheduler->next_scheduled_action( $hook, $args, 'plugin_a' ) );
        $this->assertFalse( $this->scheduler->next_scheduled_action( $hook, $args, 'plugin_b' ) );
    }

    public function test_external_action_in_core_group_removed_by_core_unschedule() {
        $hook      = 'aswc_test_hook';
        $args      = array( 'id' => 1 );
        $timestamp = time() + 10;

        // External plugin schedules without a custom group (uses core group).
        $this->scheduler->schedule_action( $timestamp, $hook, $args, false );

        // Core unschedules its default group; external action should be removed.
        $this->scheduler->unschedule_group( ASWC_Scheduler_Core::ACTION_GROUP );

        $this->assertFalse( $this->scheduler->next_scheduled_action( $hook, $args ) );
    }

    public function test_concurrent_external_reschedules_latest_timestamp_wins() {
        $hook = 'aswc_test_hook';
        $args = array( 'id' => 1 );
        $t0   = time() + 10;
        $t1   = $t0 + 20;
        $t2   = $t1 + 20;

        // Shared group used by two external plugins.
        $group = 'shared';

        $this->scheduler->schedule_action( $t0, $hook, $args, false, $group );

        // Plugin A reschedules to $t1, then plugin B reschedules to $t2.
        $this->scheduler->reschedule_action( $t1, $hook, $args, $group );
        $this->scheduler->reschedule_action( $t2, $hook, $args, $group );

        $this->assertSame( $t2, $this->scheduler->next_scheduled_action( $hook, $args, $group ) );
    }

    public function test_distributed_queue_nodes_cleanup_and_reschedule() {
        $hook = 'aswc_test_hook';
        $args = array( 'id' => 1 );
        $t0   = time() + 10;
        $t1   = $t0 + 20;
        $t2   = $t1 + 20;

        // Nodes A and B in a distributed queue schedule the same hook.
        $this->scheduler->schedule_action( $t0, $hook, $args, false, 'node_a' );
        $this->scheduler->schedule_action( $t0, $hook, $args, false, 'node_b' );

        // Node A reschedules before a global cleanup occurs.
        $this->scheduler->reschedule_action( $t1, $hook, $args, 'node_a' );

        // Global cleanup clears all groups.
        $this->scheduler->unschedule_group( false );

        // Node B reschedules after the cleanup completes.
        $this->scheduler->reschedule_action( $t2, $hook, $args, 'node_b' );

        $this->assertFalse( $this->scheduler->next_scheduled_action( $hook, $args, 'node_a' ) );
        $this->assertSame( $t2, $this->scheduler->next_scheduled_action( $hook, $args, 'node_b' ) );
    }
}

