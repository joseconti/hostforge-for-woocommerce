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

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // No-op in tests.
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( $hook, $arg = null ) {
        // No-op in tests.
    }
}

if ( ! function_exists( 'wcs_get_subscription' ) ) {
    function wcs_get_subscription( $id ) {
        return false;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/../lifecycle/class-aswc-scheduler-lifecycle.php';
require_once __DIR__ . '/../scheduler.php';

class Mock_Subscription_Lifecycle {
    private $id;
    private $times;
    private $status;

    public function __construct( $id = 10, $times = array(), $status = 'active' ) {
        $this->id     = $id;
        $this->times  = $times;
        $this->status = $status;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_time( $type ) {
        return $this->times[ $type ] ?? time();
    }

    public function has_status( $statuses ) {
        if ( is_array( $statuses ) ) {
            return in_array( $this->status, $statuses, true );
        }

        return $this->status === $statuses;
    }
}

class Test_Core_Scheduler_Lifecycle extends ASWC_Scheduler_Core {
    public $scheduled = array();

    protected function build_key( $hook, $args, $group ) {
        ksort( $args );
        return ( $group ?? self::ACTION_GROUP ) . '|' . $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $this->scheduled[ $this->build_key( $action_hook, $action_args, $group ) ] = $timestamp;
        return 1;
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        if ( false === $group ) {
            foreach ( array_keys( $this->scheduled ) as $key ) {
                if ( preg_match( '/^[^|]*\|' . preg_quote( $action_hook, '/' ) . ':' . preg_quote( json_encode( $action_args ), '/' ) . '$/', $key ) ) {
                    unset( $this->scheduled[ $key ] );
                }
            }
            return;
        }
        unset( $this->scheduled[ $this->build_key( $action_hook, $action_args, $group ) ] );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        if ( false === $group ) {
            $pattern = '/^[^|]*\|' . preg_quote( $action_hook, '/' ) . ':' . preg_quote( json_encode( $action_args ), '/' ) . '$/';
            foreach ( $this->scheduled as $key => $time ) {
                if ( preg_match( $pattern, $key ) ) {
                    return $time;
                }
            }
            return false;
        }
        $key = $this->build_key( $action_hook, $action_args, $group );
        return $this->scheduled[ $key ] ?? false;
    }

    public function last_scheduled_action( $action_hook, $action_args, $group = null ) {
        return $this->next_scheduled_action( $action_hook, $action_args, $group );
    }

    public function has_scheduled_action( $action_hook, $action_args, $group = null ) {
        return false !== $this->next_scheduled_action( $action_hook, $action_args, $group );
    }

    public function get_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        $timestamp = $this->next_scheduled_action( $action_hook, $action_args, $group );

        if ( false === $timestamp ) {
            return false;
        }

        return (object) array( 'timestamp' => $timestamp );
    }

    public function get_last_scheduled_action( $action_hook, $action_args = array(), $group = null ) {
        $timestamp = $this->last_scheduled_action( $action_hook, $action_args, $group );

        if ( false === $timestamp ) {
            return false;
        }

        return (object) array( 'timestamp' => $timestamp );
    }
}

class LifecycleSchedulerTest extends TestCase {
    protected $scheduler;
    protected $core;

    protected function setUp(): void {
        $this->core      = new Test_Core_Scheduler_Lifecycle();
        $this->scheduler = new ASWC_Scheduler_Lifecycle( $this->core );
    }

    protected function tearDown(): void {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        $p   = $ref->getProperty( 'lifecycle' );
        $p->setAccessible( true );
        $p->setValue( null, null );
    }

    protected function inject_api() {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        $p   = $ref->getProperty( 'lifecycle' );
        $p->setAccessible( true );
        $p->setValue( null, $this->scheduler );
    }

    public function test_schedule_and_unschedule_lifecycle_events() {
        $times        = array(
            'trial_end' => time() + 60,
            'end'       => time() + 120,
        );
        $subscription = new Mock_Subscription_Lifecycle( 10, $times );

        $this->scheduler->schedule_trial_end( $subscription, $times['trial_end'] );
        $this->scheduler->schedule_expiration( $subscription, $times['end'] );
        $this->scheduler->schedule_end_of_prepaid_term( $subscription, $times['end'] );

        $trial_key = 'aswc_subscription_scheduled_event|advanced_scheduled_subscription_trial_end:' . json_encode( array( 'subscription_id' => 10 ) );
        $exp_key   = 'aswc_subscription_scheduled_event|advanced_scheduled_subscription_expiration:' . json_encode( array( 'subscription_id' => 10 ) );
        $eopt_key  = 'aswc_subscription_scheduled_event|advanced_scheduled_subscription_end_of_prepaid_term:' . json_encode( array( 'subscription_id' => 10 ) );

        $this->assertSame( $times['trial_end'], $this->core->scheduled[ $trial_key ] );
        $this->assertSame( $times['end'], $this->core->scheduled[ $exp_key ] );
        $this->assertSame( $times['end'], $this->core->scheduled[ $eopt_key ] );

        $this->scheduler->unschedule_trial_end( $subscription );
        $this->scheduler->unschedule_expiration( $subscription );
        $this->scheduler->unschedule_end_of_prepaid_term( $subscription );

        $this->assertArrayNotHasKey( $trial_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $exp_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $eopt_key, $this->core->scheduled );
    }

    public function test_schedule_methods_accept_custom_group() {
        $times        = array(
            'trial_end' => time() + 60,
            'end'       => time() + 120,
        );
        $subscription = new Mock_Subscription_Lifecycle( 15, $times );

        $group = 'alt_group';
        $this->scheduler->schedule_trial_end( $subscription, $times['trial_end'], $group );
        $this->scheduler->schedule_expiration( $subscription, $times['end'], $group );
        $this->scheduler->schedule_end_of_prepaid_term( $subscription, $times['end'], $group );

        $trial_key = $group . '|advanced_scheduled_subscription_trial_end:' . json_encode( array( 'subscription_id' => 15 ) );
        $exp_key   = $group . '|advanced_scheduled_subscription_expiration:' . json_encode( array( 'subscription_id' => 15 ) );
        $eopt_key  = $group . '|advanced_scheduled_subscription_end_of_prepaid_term:' . json_encode( array( 'subscription_id' => 15 ) );

        $this->assertSame( $times['trial_end'], $this->core->scheduled[ $trial_key ] );
        $this->assertSame( $times['end'], $this->core->scheduled[ $exp_key ] );
        $this->assertSame( $times['end'], $this->core->scheduled[ $eopt_key ] );
    }

    public function test_schedule_all_respects_subscription_status() {
        $times = array(
            'trial_end' => time() + 60,
            'end'       => time() + 120,
        );

        $active = new Mock_Subscription_Lifecycle( 10, $times, 'active' );
        $group  = 'custom_group';
        $this->scheduler->schedule_all( $active, $group );

        $trial_key   = $group . '|advanced_scheduled_subscription_trial_end:' . json_encode( array( 'subscription_id' => 10 ) );
        $expire_key  = $group . '|advanced_scheduled_subscription_expiration:' . json_encode( array( 'subscription_id' => 10 ) );
        $prepaid_key = $group . '|advanced_scheduled_subscription_end_of_prepaid_term:' . json_encode( array( 'subscription_id' => 10 ) );

        $this->assertArrayHasKey( $trial_key, $this->core->scheduled );
        $this->assertArrayHasKey( $expire_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $prepaid_key, $this->core->scheduled );

        $this->scheduler->unschedule_all( $active, $group );

        $cancelled = new Mock_Subscription_Lifecycle( 11, $times, 'cancelled' );
        $this->scheduler->schedule_all( $cancelled, $group );

        $trial_key   = $group . '|advanced_scheduled_subscription_trial_end:' . json_encode( array( 'subscription_id' => 11 ) );
        $expire_key  = $group . '|advanced_scheduled_subscription_expiration:' . json_encode( array( 'subscription_id' => 11 ) );
        $prepaid_key = $group . '|advanced_scheduled_subscription_end_of_prepaid_term:' . json_encode( array( 'subscription_id' => 11 ) );

        $this->assertArrayHasKey( $trial_key, $this->core->scheduled );
        $this->assertArrayHasKey( $prepaid_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $expire_key, $this->core->scheduled );
    }

    public function test_api_wrappers_schedule_and_unschedule() {
        $times        = array(
            'trial_end' => time() + 30,
            'end'       => time() + 90,
        );
        $subscription = new Mock_Subscription_Lifecycle( 20, $times );

        $this->inject_api();

        $group = 'custom';
        ASWC_Scheduler_API::schedule_trial_end( $subscription, $times['trial_end'], $group );
        ASWC_Scheduler_API::schedule_expiration( $subscription, $times['end'], $group );
        ASWC_Scheduler_API::schedule_end_of_prepaid_term( $subscription, $times['end'], $group );

        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_trial_end( $subscription, $group ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_expiration( $subscription, $group ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_end_of_prepaid_term( $subscription, $group ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_lifecycle_events( $subscription, $group ) );

        $this->assertSame( $times['trial_end'], ASWC_Scheduler_API::get_scheduled_trial_end( $subscription, $group ) );
        $this->assertSame( $times['end'], ASWC_Scheduler_API::get_scheduled_expiration( $subscription, $group ) );
        $this->assertSame( $times['end'], ASWC_Scheduler_API::get_scheduled_end_of_prepaid_term( $subscription, $group ) );

        ASWC_Scheduler_API::unschedule_trial_end( $subscription, $group );
        ASWC_Scheduler_API::unschedule_expiration( $subscription, $group );
        ASWC_Scheduler_API::unschedule_end_of_prepaid_term( $subscription, $group );

        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_lifecycle_events( $subscription, $group ) );
    }

    public function test_get_scheduled_events_returns_all_events() {
        $times        = array(
            'trial_end' => time() + 30,
            'end'       => time() + 60,
        );
        $subscription = new Mock_Subscription_Lifecycle( 25, $times );

        $this->scheduler->schedule_trial_end( $subscription, $times['trial_end'] );
        $this->scheduler->schedule_expiration( $subscription, $times['end'] );
        $this->scheduler->schedule_end_of_prepaid_term( $subscription, $times['end'] );

        $events = $this->scheduler->get_scheduled_events( $subscription );

        $this->assertSame( $times['trial_end'], $events['trial_end'] );
        $this->assertSame( $times['end'], $events['expiration'] );
        $this->assertSame( $times['end'], $events['end_of_prepaid_term'] );
    }

    public function test_group_parameter_allows_cross_group_operations() {
        $subscription = new Mock_Subscription_Lifecycle();
        $timestamp    = time() + 60;

        $this->scheduler->schedule_expiration( $subscription, $timestamp );
        $external_key = 'external|advanced_scheduled_subscription_expiration:' . json_encode( array( 'subscription_id' => 10 ) );
        $this->core->scheduled[ $external_key ] = $timestamp + 30;

        $this->scheduler->unschedule_expiration( $subscription, false );
        $this->assertFalse( $this->scheduler->has_scheduled_expiration( $subscription, false ) );

        $this->core->scheduled[ $external_key ] = $timestamp + 30;
        $this->assertSame( $timestamp + 30, $this->scheduler->get_scheduled_expiration( $subscription, false ) );
    }

    public function test_unschedule_all_clears_events_across_groups() {
        $subscription = new Mock_Subscription_Lifecycle();
        $timestamp    = time() + 60;

        // Schedule a trial end in the default group.
        $this->scheduler->schedule_trial_end( $subscription, $timestamp );

        // Manually schedule a trial end in an external group.
        $external_group = 'external';
        $this->core->schedule_action(
            $timestamp + 30,
            'advanced_scheduled_subscription_trial_end',
            $this->core->get_action_args( 'trial_end', $subscription ),
            false,
            $external_group
        );

        // Confirm the event exists across groups.
        $this->assertTrue( $this->scheduler->has_scheduled_trial_end( $subscription, false ) );

        // Unschedule across all groups and verify nothing remains.
        $this->scheduler->unschedule_all( $subscription, false );
        $this->assertFalse( $this->scheduler->has_scheduled_events( $subscription, false ) );
        $this->assertSame( array(), $this->scheduler->get_scheduled_events( $subscription, false ) );
    }

    public function test_last_scheduled_event_helpers() {
        $times        = array(
            'trial_end' => time() + 40,
            'end'       => time() + 80,
        );
        $subscription = new Mock_Subscription_Lifecycle( 30, $times );

        $this->scheduler->schedule_trial_end( $subscription, $times['trial_end'] );
        $this->scheduler->schedule_expiration( $subscription, $times['end'] );
        $this->scheduler->schedule_end_of_prepaid_term( $subscription, $times['end'] );

        $this->assertSame( $times['trial_end'], $this->scheduler->last_scheduled_trial_end( $subscription ) );
        $this->assertSame( $times['end'], $this->scheduler->last_scheduled_expiration( $subscription ) );
        $this->assertSame( $times['end'], $this->scheduler->last_scheduled_end_of_prepaid_term( $subscription ) );

        $events = $this->scheduler->get_last_scheduled_events( $subscription );
        $this->assertSame( $times['trial_end'], $events['trial_end'] );
        $this->assertSame( $times['end'], $events['expiration'] );
        $this->assertSame( $times['end'], $events['end_of_prepaid_term'] );
    }

    public function test_api_wrappers_last_scheduled_events() {
        $times        = array(
            'trial_end' => time() + 50,
            'end'       => time() + 100,
        );
        $subscription = new Mock_Subscription_Lifecycle( 40, $times, 'cancelled' );

        $this->inject_api();

        ASWC_Scheduler_API::schedule_all_lifecycle_events( $subscription );

        $this->assertSame( $times['trial_end'], ASWC_Scheduler_API::last_scheduled_trial_end( $subscription ) );
        $this->assertSame( $times['end'], ASWC_Scheduler_API::last_scheduled_end_of_prepaid_term( $subscription ) );

        $events = ASWC_Scheduler_API::get_last_scheduled_lifecycle_events( $subscription );
        $this->assertSame( $times['trial_end'], $events['trial_end'] );
        $this->assertSame( $times['end'], $events['end_of_prepaid_term'] );
    }

    public function test_get_scheduled_action_helpers() {
        $subscription = new Mock_Subscription_Lifecycle();
        $trial_ts     = time() + 30;
        $exp_ts       = time() + 60;
        $eopt_ts      = time() + 90;

        $this->scheduler->schedule_trial_end( $subscription, $trial_ts );
        $this->scheduler->schedule_expiration( $subscription, $exp_ts );
        $this->scheduler->schedule_end_of_prepaid_term( $subscription, $eopt_ts );

        $trial_action = $this->scheduler->get_scheduled_trial_end_action( $subscription );
        $this->assertIsObject( $trial_action );
        $this->assertSame( $trial_ts, $trial_action->timestamp );

        $exp_action = $this->scheduler->get_scheduled_expiration_action( $subscription );
        $this->assertIsObject( $exp_action );
        $this->assertSame( $exp_ts, $exp_action->timestamp );

        $eopt_action = $this->scheduler->get_scheduled_end_of_prepaid_term_action( $subscription );
        $this->assertIsObject( $eopt_action );
        $this->assertSame( $eopt_ts, $eopt_action->timestamp );

        $actions = $this->scheduler->get_scheduled_actions( $subscription );
        $this->assertCount( 3, $actions );
        $this->assertArrayHasKey( 'trial_end', $actions );
        $this->assertArrayHasKey( 'expiration', $actions );
        $this->assertArrayHasKey( 'end_of_prepaid_term', $actions );

        $this->inject_api();
        $api_actions = ASWC_Scheduler_API::get_scheduled_lifecycle_actions( $subscription );
        $this->assertCount( 3, $api_actions );
        $this->assertSame( $trial_ts, $api_actions['trial_end']->timestamp );
        $this->assertSame( $exp_ts, $api_actions['expiration']->timestamp );
        $this->assertSame( $eopt_ts, $api_actions['end_of_prepaid_term']->timestamp );
    }

    public function test_get_last_scheduled_action_helpers() {
        $subscription = new Mock_Subscription_Lifecycle();
        $trial_ts     = time() + 30;
        $exp_ts       = time() + 60;
        $eopt_ts      = time() + 90;

        $this->scheduler->schedule_trial_end( $subscription, $trial_ts );
        $this->scheduler->schedule_expiration( $subscription, $exp_ts );
        $this->scheduler->schedule_end_of_prepaid_term( $subscription, $eopt_ts );

        $trial_action = $this->scheduler->get_last_scheduled_trial_end_action( $subscription );
        $this->assertIsObject( $trial_action );
        $this->assertSame( $trial_ts, $trial_action->timestamp );

        $exp_action = $this->scheduler->get_last_scheduled_expiration_action( $subscription );
        $this->assertIsObject( $exp_action );
        $this->assertSame( $exp_ts, $exp_action->timestamp );

        $eopt_action = $this->scheduler->get_last_scheduled_end_of_prepaid_term_action( $subscription );
        $this->assertIsObject( $eopt_action );
        $this->assertSame( $eopt_ts, $eopt_action->timestamp );

        $actions = $this->scheduler->get_last_scheduled_actions( $subscription );
        $this->assertCount( 3, $actions );
        $this->assertArrayHasKey( 'trial_end', $actions );
        $this->assertArrayHasKey( 'expiration', $actions );
        $this->assertArrayHasKey( 'end_of_prepaid_term', $actions );

        $this->inject_api();
        $api_trial_action = ASWC_Scheduler_API::get_last_scheduled_trial_end_action( $subscription );
        $this->assertSame( $trial_ts, $api_trial_action->timestamp );

        $api_actions = ASWC_Scheduler_API::get_last_scheduled_lifecycle_actions( $subscription );
        $this->assertSame( $exp_ts, $api_actions['expiration']->timestamp );
        $this->assertSame( $eopt_ts, $api_actions['end_of_prepaid_term']->timestamp );
    }

    public function test_schedule_methods_unschedule_on_invalid_timestamp() {
        $initial_times = array(
            'trial_end' => time() + 30,
            'end'       => time() + 60,
        );
        $subscription = new Mock_Subscription_Lifecycle( 30, $initial_times );

        $this->scheduler->schedule_trial_end( $subscription, $initial_times['trial_end'] );
        $this->scheduler->schedule_expiration( $subscription, $initial_times['end'] );
        $this->scheduler->schedule_end_of_prepaid_term( $subscription, $initial_times['end'] );

        $this->assertNotEmpty( $this->core->scheduled );

        $zero_times   = array( 'trial_end' => 0, 'end' => 0 );
        $zero_sub     = new Mock_Subscription_Lifecycle( 30, $zero_times );
        $this->scheduler->schedule_trial_end( $zero_sub );
        $this->scheduler->schedule_expiration( $zero_sub );
        $this->scheduler->schedule_end_of_prepaid_term( $zero_sub );

        $this->assertEmpty( $this->core->scheduled );
    }
}
