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
        // No-op for tests.
    }
}

if ( ! isset( $test_options ) ) {
    $test_options = array();
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = false ) {
        global $test_options;
        return $test_options[ $name ] ?? $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $name, $value ) {
        global $test_options;
        $test_options[ $name ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $name ) {
        global $test_options;
        unset( $test_options[ $name ] );
        return true;
    }
}


require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/../payments/class-aswc-scheduler-payments.php';
require_once __DIR__ . '/../scheduler.php';

class Mock_Subscription_Payments {
    private $id;
    private $times;
    private $last_order_id;

    public function __construct( $id = 10, $times = array(), $last_order_id = 5 ) {
        $this->id            = $id;
        $this->times         = $times;
        $this->last_order_id = $last_order_id;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_time( $type ) {
        return $this->times[ $type ] ?? time();
    }

    public function get_last_order( $type = 'ids', $context = 'renewal' ) {
        return $this->last_order_id;
    }
}

class Mock_Retry_Rule {
    private $interval;

    public function __construct( $interval ) {
        $this->interval = $interval;
    }

    public function get_retry_interval() {
        return $this->interval;
    }
}

class Test_Core_Scheduler_Payments extends ASWC_Scheduler_Core {
    public $scheduled = array();

    protected function build_key( $hook, $args, $group ) {
        ksort( $args );
        return ( $group ?? self::ACTION_GROUP ) . '|' . $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args, $group ?? self::ACTION_GROUP );
        $this->scheduled[ $key ] = $timestamp;
        return 1;
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        if ( false === $group ) {
            foreach ( array_keys( $this->scheduled ) as $key ) {
                if ( str_contains( $key, '|' . $action_hook . ':' . json_encode( $action_args ) ) ) {
                    unset( $this->scheduled[ $key ] );
                }
            }
            return;
        }

        $key = $this->build_key( $action_hook, $action_args, $group );
        unset( $this->scheduled[ $key ] );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        if ( false === $group ) {
            $matches = array();
            foreach ( $this->scheduled as $key => $timestamp ) {
                if ( str_contains( $key, '|' . $action_hook . ':' . json_encode( $action_args ) ) ) {
                    $matches[] = $timestamp;
                }
            }
            return empty( $matches ) ? false : min( $matches );
        }

        $key = $this->build_key( $action_hook, $action_args, $group );
        return $this->scheduled[ $key ] ?? false;
    }

    public function has_scheduled_action( $action_hook, $action_args, $group = null ) {
        return false !== $this->next_scheduled_action( $action_hook, $action_args, $group );
    }

    public function last_scheduled_action( $action_hook, $action_args, $group = null ) {
        if ( false === $group ) {
            $matches = array();
            foreach ( $this->scheduled as $key => $timestamp ) {
                if ( str_contains( $key, '|' . $action_hook . ':' . json_encode( $action_args ) ) ) {
                    $matches[] = $timestamp;
                }
            }
            return empty( $matches ) ? false : max( $matches );
        }

        $key = $this->build_key( $action_hook, $action_args, $group );
        return $this->scheduled[ $key ] ?? false;
    }

    public function get_scheduled_actions( $action_hook, $action_args = array(), $group = null ) {
        $actions = array();

        if ( false === $group ) {
            foreach ( $this->scheduled as $key => $timestamp ) {
                if ( str_contains( $key, '|' . $action_hook . ':' . json_encode( $action_args ) ) ) {
                    $actions[] = (object) array( 'timestamp' => $timestamp );
                }
            }

            return $actions;
        }

        $key = $this->build_key( $action_hook, $action_args, $group );
        if ( isset( $this->scheduled[ $key ] ) ) {
            $actions[] = (object) array( 'timestamp' => $this->scheduled[ $key ] );
        }

        return $actions;
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

class PaymentSchedulerTest extends TestCase {
    protected $scheduler;
    protected $core;

    protected function setUp(): void {
        $this->core      = new Test_Core_Scheduler_Payments();
        $this->scheduler = new ASWC_Scheduler_Payments( $this->core );
    }

    protected function tearDown(): void {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        $p   = $ref->getProperty( 'payments' );
        $p->setAccessible( true );
        $p->setValue( null, null );
        global $test_options;
        $test_options = array();
    }

    protected function inject_api() {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        $p   = $ref->getProperty( 'payments' );
        $p->setAccessible( true );
        $p->setValue( null, $this->scheduler );
    }

    public function test_schedule_and_unschedule_payment() {
        $subscription = new Mock_Subscription_Payments();
        $timestamp    = time() + 60;

        $this->scheduler->schedule_payment( $subscription, $timestamp );
        $this->assertSame( $timestamp, $this->scheduler->get_scheduled_payment( $subscription ) );

        $this->scheduler->unschedule_payment( $subscription );
        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription ) );
    }

    public function test_schedule_and_unschedule_retry() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 99 );
        $timestamp    = time() + 120;

        $this->scheduler->schedule_retry( $subscription, $timestamp );
        $this->assertSame( $timestamp, $this->scheduler->get_scheduled_retry( $subscription ) );

        $this->scheduler->unschedule_retry( $subscription );
        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription ) );
    }

    public function test_schedule_retry_with_rule_helper() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 99 );
        $rule         = new Mock_Retry_Rule( 60 );

        $scheduled = $this->scheduler->schedule_retry_with_rule( $subscription, $rule );

        $this->assertSame( $scheduled, $this->scheduler->get_scheduled_retry( $subscription ) );
        $this->assertGreaterThan( time(), $scheduled );
    }

    public function test_schedule_retry_with_rule_with_invalid_interval_unschedules() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 99 );

        // Schedule an initial retry to verify it gets cleared.
        $this->scheduler->schedule_retry_with_rule( $subscription, new Mock_Retry_Rule( 60 ) );
        $this->assertTrue( $this->scheduler->has_scheduled_retry( $subscription ) );

        $scheduled = $this->scheduler->schedule_retry_with_rule( $subscription, new Mock_Retry_Rule( 0 ) );

        $this->assertSame( 0, $scheduled );
        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription ) );
    }

    public function test_schedule_retry_after_helper() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 99 );
        $interval     = 120;

        $scheduled = $this->scheduler->schedule_retry_after( $subscription, $interval );

        $this->assertSame( $scheduled, $this->scheduler->get_scheduled_retry( $subscription ) );
        $this->assertGreaterThan( time(), $scheduled );
        $this->assertLessThan( time() + $interval + 2, $scheduled );
    }

    public function test_schedule_retry_after_with_invalid_interval_unschedules() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 99 );

        // Schedule an initial retry to confirm it gets cleared.
        $this->scheduler->schedule_retry_after( $subscription, 60 );
        $this->assertTrue( $this->scheduler->has_scheduled_retry( $subscription ) );

        $scheduled = $this->scheduler->schedule_retry_after( $subscription, 0 );

        $this->assertSame( 0, $scheduled );
        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription ) );
    }

    public function test_schedule_retry_after_in_custom_group() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 99 );
        $interval     = 60;
        $group        = 'custom';

        $scheduled = $this->scheduler->schedule_retry_after( $subscription, $interval, $group );
        $key       = $group . '|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 99 ) );

        $this->assertArrayHasKey( $key, $this->core->scheduled );
        $this->assertSame( $scheduled, $this->core->scheduled[ $key ] );

        $this->inject_api();
        $api_group     = 'api-group';
        $api_scheduled = ASWC_Scheduler_API::schedule_retry_after( $subscription, 30, $api_group );
        $api_key       = $api_group . '|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 99 ) );

        $this->assertArrayHasKey( $api_key, $this->core->scheduled );
        $this->assertSame( $api_scheduled, $this->core->scheduled[ $api_key ] );
    }

    public function test_schedule_retry_for_attempt_uses_configured_interval() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 99 );
        update_option( 'advanced_subscriptions_woocommerce_retry_intervals', array( 30, 90 ) );
        $start     = time();
        $scheduled = $this->scheduler->schedule_retry_for_attempt( $subscription, 1 );

        $this->assertSame( $scheduled, $this->scheduler->get_scheduled_retry( $subscription ) );
        $this->assertGreaterThan( $start, $scheduled );
        $this->assertLessThan( $start + 90 + 2, $scheduled );
    }

    public function test_schedule_retry_for_attempt_out_of_range_unschedules() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 99 );

        // Schedule an initial retry to confirm it gets cleared when attempt is invalid.
        $this->scheduler->schedule_retry_for_attempt( $subscription, 0 );
        $this->assertTrue( $this->scheduler->has_scheduled_retry( $subscription ) );

        $scheduled = $this->scheduler->schedule_retry_for_attempt( $subscription, 5 );

        $this->assertSame( 0, $scheduled );
        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription ) );
    }

    public function test_get_retry_interval_from_rule_wrappers() {
        $rule = new Mock_Retry_Rule( 45 );

        $this->assertSame( 45, aswc_get_retry_interval_from_rule( $rule ) );
        $this->assertSame( 45, ASWC_Scheduler_API::get_retry_interval_from_rule( $rule ) );
        $this->assertSame( 0, aswc_get_retry_interval_from_rule( new stdClass() ) );
    }

    public function test_schedule_manual_payment_helper() {
        $times        = array( 'next_payment' => time() + 30 );
        $subscription = new Mock_Subscription_Payments( 10, $times );

        $this->scheduler->schedule_manual_payment( $subscription );
        $this->assertSame( $times['next_payment'], $this->scheduler->get_scheduled_payment( $subscription ) );
    }

    public function test_schedule_manual_payment_with_custom_group() {
        $times        = array( 'next_payment' => time() + 30 );
        $subscription = new Mock_Subscription_Payments( 10, $times );
        $group        = 'manual-group';

        $this->scheduler->schedule_manual_payment( $subscription, null, $group );

        $key = $group . '|advanced_scheduled_subscription_payment:' . json_encode( array( 'subscription_id' => 10 ) );

        $this->assertArrayHasKey( $key, $this->core->scheduled );
        $this->assertSame( $times['next_payment'], $this->core->scheduled[ $key ] );
    }

    public function test_schedule_manual_payment_with_false_group_unschedules_all() {
        $times        = array( 'next_payment' => time() + 30 );
        $subscription = new Mock_Subscription_Payments( 10, $times );
        $custom_group = 'external-group';

        // Manually schedule a payment in a custom group.
        $this->core->schedule_action(
            time() + 60,
            'advanced_scheduled_subscription_payment',
            $this->core->get_action_args( 'next_payment', $subscription ),
            false,
            $custom_group
        );
        $this->assertTrue( $this->scheduler->has_scheduled_payment( $subscription, $custom_group ) );

        // Scheduling with group false should clear all groups before scheduling in the default group.
        $this->scheduler->schedule_manual_payment( $subscription, null, false );

        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription, $custom_group ) );
        $this->assertTrue( $this->scheduler->has_scheduled_payment( $subscription ) );
    }

    public function test_schedule_manual_payment_with_non_positive_timestamp_unschedules() {
        $subscription = new Mock_Subscription_Payments();

        $this->scheduler->schedule_manual_payment( $subscription, time() + 60 );
        $this->assertTrue( $this->scheduler->has_scheduled_payment( $subscription ) );

        $this->scheduler->schedule_manual_payment( $subscription, 0 );
        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription ) );

        $this->scheduler->schedule_manual_payment( $subscription, time() + 60 );
        $this->scheduler->schedule_manual_payment( $subscription, -10 );
        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription ) );
    }

    public function test_schedule_payment_without_timestamp_unschedules() {
        $subscription = new Mock_Subscription_Payments( 10, array( 'next_payment' => time() + 30 ) );
        $timestamp    = time() + 30;

        $this->scheduler->schedule_payment( $subscription, $timestamp );
        $this->assertNotEmpty( $this->core->scheduled );

        $subscription_no_time = new Mock_Subscription_Payments( 10, array( 'next_payment' => 0 ) );
        $this->scheduler->schedule_payment( $subscription_no_time );

        $this->assertEmpty( $this->core->scheduled );
    }

    public function test_schedule_payment_with_non_positive_timestamp_unschedules() {
        $subscription = new Mock_Subscription_Payments();

        $this->scheduler->schedule_payment( $subscription, time() + 60 );
        $this->assertTrue( $this->scheduler->has_scheduled_payment( $subscription ) );

        $this->scheduler->schedule_payment( $subscription, 0 );
        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription ) );

        $this->scheduler->schedule_payment( $subscription, time() + 60 );
        $this->scheduler->schedule_payment( $subscription, -10 );
        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription ) );
    }

    public function test_schedule_retry_without_timestamp_unschedules() {
        $subscription = new Mock_Subscription_Payments( 10, array( 'payment_retry' => time() + 60 ) );
        $timestamp    = time() + 60;

        $this->scheduler->schedule_retry( $subscription, $timestamp );
        $this->assertNotEmpty( $this->core->scheduled );

        $subscription_no_time = new Mock_Subscription_Payments( 10, array( 'payment_retry' => 0 ) );
        $this->scheduler->schedule_retry( $subscription_no_time );

        $this->assertEmpty( $this->core->scheduled );
    }

    public function test_schedule_retry_with_non_positive_timestamp_unschedules() {
        $subscription = new Mock_Subscription_Payments();

        $this->scheduler->schedule_retry( $subscription, time() + 60 );
        $this->assertTrue( $this->scheduler->has_scheduled_retry( $subscription ) );

        $this->scheduler->schedule_retry( $subscription, 0 );
        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription ) );

        $this->scheduler->schedule_retry( $subscription, time() + 60 );
        $this->scheduler->schedule_retry( $subscription, -10 );
        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription ) );
    }

    public function test_schedule_retry_with_rule_in_custom_group() {
        $subscription = new Mock_Subscription_Payments();
        $group        = 'retry-group';
        $rule         = new Mock_Retry_Rule( 60 );
        $before       = time();

        $scheduled = $this->scheduler->schedule_retry_with_rule( $subscription, $rule, $group );
        $key       = $group . '|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 5 ) );

        $this->assertArrayHasKey( $key, $this->core->scheduled );
        $this->assertSame( $scheduled, $this->core->scheduled[ $key ] );
        $this->assertGreaterThanOrEqual( $before + 60, $scheduled );
        $this->assertLessThanOrEqual( $before + 62, $scheduled );
    }

    public function test_schedule_in_custom_group() {
        $subscription = new Mock_Subscription_Payments();
        $payment_ts   = time() + 60;
        $retry_ts     = time() + 120;
        $group        = 'custom-group';

        $this->scheduler->schedule_payment( $subscription, $payment_ts, $group );
        $this->scheduler->schedule_retry( $subscription, $retry_ts, $group );

        $payment_key = $group . '|advanced_scheduled_subscription_payment:' . json_encode( array( 'subscription_id' => 10 ) );
        $retry_key   = $group . '|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 5 ) );

        $this->assertSame( $payment_ts, $this->core->scheduled[ $payment_key ] );
        $this->assertSame( $retry_ts, $this->core->scheduled[ $retry_key ] );

        $this->scheduler->unschedule_payment( $subscription, $group );
        $this->scheduler->unschedule_retry( $subscription, $group );

        $this->assertArrayNotHasKey( $payment_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $retry_key, $this->core->scheduled );
    }

    public function test_group_parameter_allows_cross_group_operations() {
        $subscription = new Mock_Subscription_Payments();
        $timestamp    = time() + 60;

        $this->scheduler->schedule_payment( $subscription, $timestamp );
        $external_key = 'external|advanced_scheduled_subscription_payment:' . json_encode( array( 'subscription_id' => 10 ) );
        $this->core->scheduled[ $external_key ] = $timestamp + 30;

        $this->scheduler->unschedule_payment( $subscription, false );
        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription, false ) );

        $this->core->scheduled[ $external_key ] = $timestamp + 30;
        $this->assertSame( $timestamp + 30, $this->scheduler->get_scheduled_payment( $subscription, false ) );
    }

    public function test_group_parameter_allows_cross_group_operations_for_retries() {
        $subscription = new Mock_Subscription_Payments( 10, array( 'payment_retry' => time() + 60 ), 55 );

        // Schedule retry in the default group.
        $this->scheduler->schedule_retry( $subscription );

        // Manually schedule a retry in an external group.
        $external_group = 'external_retry_group';
        $this->core->schedule_action(
            time() + 120,
            'advanced_scheduled_subscription_payment_retry',
            $this->core->get_action_args( 'payment_retry', $subscription ),
            false,
            $external_group
        );

        // Unschedule across all groups and confirm nothing remains.
        $this->scheduler->unschedule_retry( $subscription, false );
        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription, false ) );

        // Re-add the external action and confirm it can be retrieved.
        $this->core->schedule_action(
            time() + 180,
            'advanced_scheduled_subscription_payment_retry',
            $this->core->get_action_args( 'payment_retry', $subscription ),
            false,
            $external_group
        );

        $this->assertNotFalse( $this->scheduler->get_scheduled_retry( $subscription, false ) );
    }

    public function test_schedule_payment_removes_existing_actions_across_groups() {
        $subscription   = new Mock_Subscription_Payments();
        $external_group = 'external_group';

        $this->core->schedule_action(
            time() + 120,
            'advanced_scheduled_subscription_payment',
            $this->core->get_action_args( 'next_payment', $subscription ),
            false,
            $external_group
        );
        $this->assertTrue( $this->scheduler->has_scheduled_payment( $subscription, $external_group ) );

        $this->scheduler->schedule_payment( $subscription, time() + 60 );

        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription, $external_group ) );
        $this->assertTrue( $this->scheduler->has_scheduled_payment( $subscription ) );
    }

    public function test_schedule_payment_in_custom_group_overwrites_default() {
        $subscription = new Mock_Subscription_Payments();

        $this->scheduler->schedule_payment( $subscription, time() + 60 );
        $this->assertTrue( $this->scheduler->has_scheduled_payment( $subscription ) );

        $custom_group = 'custom_group';
        $this->scheduler->schedule_payment( $subscription, time() + 120, $custom_group );

        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription ) );
        $this->assertTrue( $this->scheduler->has_scheduled_payment( $subscription, $custom_group ) );
    }

    public function test_schedule_retry_removes_existing_actions_across_groups() {
        $subscription   = new Mock_Subscription_Payments( 10, array(), 55 );
        $external_group = 'external_retry_group';

        $this->core->schedule_action(
            time() + 120,
            'advanced_scheduled_subscription_payment_retry',
            $this->core->get_action_args( 'payment_retry', $subscription ),
            false,
            $external_group
        );
        $this->assertTrue( $this->scheduler->has_scheduled_retry( $subscription, $external_group ) );

        $this->scheduler->schedule_retry( $subscription, time() + 60 );

        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription, $external_group ) );
        $this->assertTrue( $this->scheduler->has_scheduled_retry( $subscription ) );
    }

    public function test_schedule_retry_in_custom_group_overwrites_default() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 55 );

        $this->scheduler->schedule_retry( $subscription, time() + 60 );
        $this->assertTrue( $this->scheduler->has_scheduled_retry( $subscription ) );

        $custom_group = 'custom_retry_group';
        $this->scheduler->schedule_retry( $subscription, time() + 120, $custom_group );

        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription ) );
        $this->assertTrue( $this->scheduler->has_scheduled_retry( $subscription, $custom_group ) );
    }

    public function test_schedule_payment_with_false_group_defaults_to_core_group() {
        $subscription = new Mock_Subscription_Payments();
        $timestamp    = time() + 60;

        $this->scheduler->schedule_payment( $subscription, $timestamp, false );

        $key = ASWC_Scheduler_Core::ACTION_GROUP . '|advanced_scheduled_subscription_payment:' . json_encode( array( 'subscription_id' => 10 ) );

        $this->assertArrayHasKey( $key, $this->core->scheduled );
    }

    public function test_schedule_retry_with_false_group_defaults_to_core_group() {
        $subscription = new Mock_Subscription_Payments( 10, array( 'payment_retry' => time() + 60 ), 55 );

        $this->scheduler->schedule_retry( $subscription, null, false );

        $key = ASWC_Scheduler_Core::ACTION_GROUP . '|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 55 ) );

        $this->assertArrayHasKey( $key, $this->core->scheduled );
    }

    public function test_schedule_all_and_unschedule_all() {
        $times        = array(
            'next_payment'   => time() + 60,
            'payment_retry'  => time() + 120,
        );
        $subscription = new Mock_Subscription_Payments( 10, $times, 77 );

        $this->scheduler->schedule_all( $subscription );
        $scheduled = $this->scheduler->get_scheduled_payments( $subscription );

        $this->assertArrayHasKey( 'next_payment', $scheduled );
        $this->assertArrayHasKey( 'payment_retry', $scheduled );
        $this->assertTrue( $this->scheduler->has_scheduled_payments( $subscription ) );

        $this->scheduler->unschedule_all( $subscription );
        $this->assertFalse( $this->scheduler->has_scheduled_payments( $subscription ) );
    }

    public function test_last_scheduled_payment_and_retry() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 77 );
        $payment_ts   = time() + 60;
        $retry_ts     = time() + 120;

        $this->scheduler->schedule_payment( $subscription, $payment_ts );
        $this->scheduler->schedule_retry( $subscription, $retry_ts );

        $this->assertSame( $payment_ts, $this->scheduler->last_scheduled_payment( $subscription ) );
        $this->assertSame( $retry_ts, $this->scheduler->last_scheduled_retry( $subscription ) );
        $this->assertSame(
            array( 'next_payment' => $payment_ts, 'payment_retry' => $retry_ts ),
            $this->scheduler->get_last_scheduled_payments( $subscription )
        );

        $this->inject_api();
        $this->assertSame( $payment_ts, ASWC_Scheduler_API::last_scheduled_payment( $subscription ) );
        $this->assertSame( $retry_ts, ASWC_Scheduler_API::last_scheduled_retry( $subscription ) );
        $this->assertSame(
            array( 'next_payment' => $payment_ts, 'payment_retry' => $retry_ts ),
            ASWC_Scheduler_API::get_last_scheduled_payments( $subscription )
        );

        // Global helper wrappers.
        $this->assertSame( $payment_ts, aswc_last_scheduled_payment( $subscription ) );
        $this->assertSame( $retry_ts, aswc_last_scheduled_retry( $subscription ) );
        }

    public function test_scheduler_last_scheduled_payment_and_retry_accepts_group() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_ts   = time() + 60;
        $retry_ts     = time() + 120;
        $group        = 'custom-group';

        // Schedule payment and retry in a custom group.
        $this->scheduler->schedule_payment( $subscription, $payment_ts, $group );
        $this->scheduler->schedule_retry( $subscription, $retry_ts, $group );

        // Default group should have no events.
        $this->assertFalse( $this->scheduler->last_scheduled_payment( $subscription ) );
        $this->assertFalse( $this->scheduler->last_scheduled_retry( $subscription ) );

        // Retrieving with the custom group returns the expected timestamps.
        $this->assertSame( $payment_ts, $this->scheduler->last_scheduled_payment( $subscription, $group ) );
        $this->assertSame( $retry_ts, $this->scheduler->last_scheduled_retry( $subscription, $group ) );

        // Passing false searches across all groups and finds the custom actions.
        $this->assertSame( $payment_ts, $this->scheduler->last_scheduled_payment( $subscription, false ) );
        $this->assertSame( $retry_ts, $this->scheduler->last_scheduled_retry( $subscription, false ) );
    }

    public function test_get_scheduled_payment_action() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_ts   = time() + 30;
        $retry_ts     = time() + 90;

        // Schedule payment and retry.
        $this->scheduler->schedule_payment( $subscription, $payment_ts );
        $this->scheduler->schedule_retry( $subscription, $retry_ts );

        $payment_action = $this->scheduler->get_scheduled_payment_action( $subscription );
        $this->assertIsObject( $payment_action );
        $this->assertSame( $payment_ts, $payment_action->timestamp );

        $retry_action = $this->scheduler->get_scheduled_retry_action( $subscription );
        $this->assertIsObject( $retry_action );
        $this->assertSame( $retry_ts, $retry_action->timestamp );

        // Verify API wrappers return the same objects.
        $this->inject_api();
        $api_payment_action = ASWC_Scheduler_API::get_scheduled_payment_action( $subscription );
        $api_retry_action   = ASWC_Scheduler_API::get_scheduled_retry_action( $subscription );

        $this->assertIsObject( $api_payment_action );
        $this->assertSame( $payment_action->timestamp, $api_payment_action->timestamp );

        $this->assertIsObject( $api_retry_action );
        $this->assertSame( $retry_action->timestamp, $api_retry_action->timestamp );
    }

    public function test_get_last_scheduled_payment_action() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_ts   = time() + 30;
        $retry_ts     = time() + 90;

        // Schedule payment and retry.
        $this->scheduler->schedule_payment( $subscription, $payment_ts );
        $this->scheduler->schedule_retry( $subscription, $retry_ts );

        $payment_action = $this->scheduler->get_last_scheduled_payment_action( $subscription );
        $this->assertIsObject( $payment_action );
        $this->assertSame( $payment_ts, $payment_action->timestamp );

        $retry_action = $this->scheduler->get_last_scheduled_retry_action( $subscription );
        $this->assertIsObject( $retry_action );
        $this->assertSame( $retry_ts, $retry_action->timestamp );

        $this->inject_api();
        $this->assertSame(
            $payment_action->timestamp,
            ASWC_Scheduler_API::get_last_scheduled_payment_action( $subscription )->timestamp
        );
        $this->assertSame(
            $retry_action->timestamp,
            ASWC_Scheduler_API::get_last_scheduled_retry_action( $subscription )->timestamp
        );
    }

    public function test_global_helpers_return_action_objects() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_ts   = time() + 30;
        $retry_ts     = time() + 90;

        $this->scheduler->schedule_payment( $subscription, $payment_ts );
        $this->scheduler->schedule_retry( $subscription, $retry_ts );

        $this->inject_api();

        $payment_action = aswc_get_scheduled_payment_action( $subscription );
        $retry_action   = aswc_get_scheduled_retry_action( $subscription );

        $this->assertIsObject( $payment_action );
        $this->assertIsObject( $retry_action );
        $this->assertSame( $payment_ts, $payment_action->timestamp );
        $this->assertSame( $retry_ts, $retry_action->timestamp );

        $last_payment_action = aswc_get_last_scheduled_payment_action( $subscription );
        $last_retry_action   = aswc_get_last_scheduled_retry_action( $subscription );

        $this->assertIsObject( $last_payment_action );
        $this->assertIsObject( $last_retry_action );
        $this->assertSame( $payment_ts, $last_payment_action->timestamp );
        $this->assertSame( $retry_ts, $last_retry_action->timestamp );

        $actions      = aswc_get_scheduled_payment_actions( $subscription );
        $last_actions = aswc_get_last_scheduled_payment_actions( $subscription );

        $this->assertArrayHasKey( 'next_payment', $actions );
        $this->assertArrayHasKey( 'payment_retry', $actions );
        $this->assertSame( $payment_ts, $actions['next_payment']->timestamp );
        $this->assertSame( $retry_ts, $actions['payment_retry']->timestamp );

        $this->assertArrayHasKey( 'next_payment', $last_actions );
        $this->assertArrayHasKey( 'payment_retry', $last_actions );
        $this->assertSame( $payment_ts, $last_actions['next_payment']->timestamp );
        $this->assertSame( $retry_ts, $last_actions['payment_retry']->timestamp );
    }

    public function test_api_get_last_scheduled_payment_and_retry_action_accepts_group() {
        $subscription    = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_default = time() + 30;
        $payment_custom  = $payment_default + 30;
        $retry_default   = time() + 60;
        $retry_custom    = $retry_default + 30;

        // Schedule in the default group and verify retrieval.
        $this->scheduler->schedule_payment( $subscription, $payment_default );
        $this->scheduler->schedule_retry( $subscription, $retry_default );

        $this->inject_api();

        $this->assertSame(
            $payment_default,
            ASWC_Scheduler_API::get_last_scheduled_payment_action( $subscription )->timestamp
        );
        $this->assertSame(
            $retry_default,
            ASWC_Scheduler_API::get_last_scheduled_retry_action( $subscription )->timestamp
        );

        // Reschedule in a custom group and confirm the default group was cleared.
        $this->scheduler->schedule_payment( $subscription, $payment_custom, 'custom' );
        $this->scheduler->schedule_retry( $subscription, $retry_custom, 'custom' );

        $this->assertFalse( ASWC_Scheduler_API::get_last_scheduled_payment_action( $subscription ) );
        $this->assertFalse( ASWC_Scheduler_API::get_last_scheduled_retry_action( $subscription ) );
        $this->assertSame(
            $payment_custom,
            ASWC_Scheduler_API::get_last_scheduled_payment_action( $subscription, 'custom' )->timestamp
        );
        $this->assertSame(
            $retry_custom,
            ASWC_Scheduler_API::get_last_scheduled_retry_action( $subscription, 'custom' )->timestamp
        );

        // Ignoring groups returns the custom action as it is the only one queued.
        $this->assertSame(
            $payment_custom,
            ASWC_Scheduler_API::get_last_scheduled_payment_action( $subscription, false )->timestamp
        );
        $this->assertSame(
            $retry_custom,
            ASWC_Scheduler_API::get_last_scheduled_retry_action( $subscription, false )->timestamp
        );
    }

    public function test_api_last_scheduled_payment_and_retry_accepts_group() {
        $subscription    = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_default = time() + 30;
        $payment_custom  = $payment_default + 30;
        $retry_default   = time() + 60;
        $retry_custom    = $retry_default + 30;

        // Schedule in the default group and verify retrieval.
        $this->scheduler->schedule_payment( $subscription, $payment_default );
        $this->scheduler->schedule_retry( $subscription, $retry_default );

        $this->inject_api();

        $this->assertSame(
            $payment_default,
            ASWC_Scheduler_API::last_scheduled_payment( $subscription )
        );
        $this->assertSame(
            $retry_default,
            ASWC_Scheduler_API::last_scheduled_retry( $subscription )
        );

        // Reschedule in a custom group and confirm the default group was cleared.
        $this->scheduler->schedule_payment( $subscription, $payment_custom, 'custom' );
        $this->scheduler->schedule_retry( $subscription, $retry_custom, 'custom' );

        $this->assertFalse( ASWC_Scheduler_API::last_scheduled_payment( $subscription ) );
        $this->assertFalse( ASWC_Scheduler_API::last_scheduled_retry( $subscription ) );
        $this->assertSame(
            $payment_custom,
            ASWC_Scheduler_API::last_scheduled_payment( $subscription, 'custom' )
        );
        $this->assertSame(
            $retry_custom,
            ASWC_Scheduler_API::last_scheduled_retry( $subscription, 'custom' )
        );

        // Ignoring groups returns the custom timestamps as they are the only ones queued.
        $this->assertSame(
            $payment_custom,
            ASWC_Scheduler_API::last_scheduled_payment( $subscription, false )
        );
        $this->assertSame(
            $retry_custom,
            ASWC_Scheduler_API::last_scheduled_retry( $subscription, false )
        );
    }

    public function test_get_scheduled_payment_and_retry_action_accepts_group() {
        $subscription    = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_default = time() + 60;
        $payment_custom  = $payment_default - 30;
        $retry_default   = time() + 120;
        $retry_custom    = $retry_default - 60;

        // Schedule actions in the default group and verify retrieval.
        $this->scheduler->schedule_payment( $subscription, $payment_default );
        $this->scheduler->schedule_retry( $subscription, $retry_default );
        $this->assertSame( $payment_default, $this->scheduler->get_scheduled_payment_action( $subscription )->timestamp );
        $this->assertSame( $retry_default, $this->scheduler->get_scheduled_retry_action( $subscription )->timestamp );

        // Reschedule in a custom group; default group should be cleared.
        $this->scheduler->schedule_payment( $subscription, $payment_custom, 'custom' );
        $this->scheduler->schedule_retry( $subscription, $retry_custom, 'custom' );

        $this->assertFalse( $this->scheduler->get_scheduled_payment_action( $subscription ) );
        $this->assertFalse( $this->scheduler->get_scheduled_retry_action( $subscription ) );
        $this->assertSame( $payment_custom, $this->scheduler->get_scheduled_payment_action( $subscription, 'custom' )->timestamp );
        $this->assertSame( $retry_custom, $this->scheduler->get_scheduled_retry_action( $subscription, 'custom' )->timestamp );

        // Searching across all groups returns the custom actions.
        $this->assertSame( $payment_custom, $this->scheduler->get_scheduled_payment_action( $subscription, false )->timestamp );
        $this->assertSame( $retry_custom, $this->scheduler->get_scheduled_retry_action( $subscription, false )->timestamp );

        // API wrappers respect group parameter.
        $this->inject_api();
        $this->assertSame( $payment_custom, ASWC_Scheduler_API::get_scheduled_payment_action( $subscription, false )->timestamp );
        $this->assertSame( $retry_custom, ASWC_Scheduler_API::get_scheduled_retry_action( $subscription, false )->timestamp );
    }

    public function test_get_scheduled_payment_actions_returns_action_objects() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_ts   = time() + 30;
        $retry_ts     = time() + 90;

        // Schedule payment and retry.
        $this->scheduler->schedule_payment( $subscription, $payment_ts );
        $this->scheduler->schedule_retry( $subscription, $retry_ts );

        $actions = $this->scheduler->get_scheduled_payment_actions( $subscription );
        $this->assertArrayHasKey( 'next_payment', $actions );
        $this->assertArrayHasKey( 'payment_retry', $actions );
        $this->assertSame( $payment_ts, $actions['next_payment']->timestamp );
        $this->assertSame( $retry_ts, $actions['payment_retry']->timestamp );

        // Verify API wrapper returns equivalent objects.
        $this->inject_api();
        $api_actions = ASWC_Scheduler_API::get_scheduled_payment_actions( $subscription );
        $this->assertSame( $actions['next_payment']->timestamp, $api_actions['next_payment']->timestamp );
        $this->assertSame( $actions['payment_retry']->timestamp, $api_actions['payment_retry']->timestamp );
    }

    public function test_get_last_scheduled_payment_actions_returns_latest_objects() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 55 );
        $first_payment = time() + 30;
        $second_payment = $first_payment + 30;
        $first_retry = time() + 90;
        $second_retry = $first_retry + 30;

        // Schedule multiple actions to ensure the latest ones are returned.
        $this->scheduler->schedule_payment( $subscription, $first_payment );
        $this->scheduler->schedule_payment( $subscription, $second_payment );
        $this->scheduler->schedule_retry( $subscription, $first_retry );
        $this->scheduler->schedule_retry( $subscription, $second_retry );

        $actions = $this->scheduler->get_last_scheduled_payment_actions( $subscription );
        $this->assertSame( $second_payment, $actions['next_payment']->timestamp );
        $this->assertSame( $second_retry, $actions['payment_retry']->timestamp );

        $this->inject_api();
        $api_actions = ASWC_Scheduler_API::get_last_scheduled_payment_actions( $subscription );
        $this->assertSame( $second_payment, $api_actions['next_payment']->timestamp );
        $this->assertSame( $second_retry, $api_actions['payment_retry']->timestamp );
    }

    public function test_get_scheduled_payment_actions_accepts_group() {
        $subscription    = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_default = time() + 30;
        $payment_custom  = $payment_default + 30;
        $retry_default   = time() + 90;
        $retry_custom    = $retry_default + 30;

        // Schedule actions in the default group and verify retrieval.
        $this->scheduler->schedule_payment( $subscription, $payment_default );
        $this->scheduler->schedule_retry( $subscription, $retry_default );
        $default_actions = $this->scheduler->get_scheduled_payment_actions( $subscription );
        $this->assertSame( $payment_default, $default_actions['next_payment']->timestamp );
        $this->assertSame( $retry_default, $default_actions['payment_retry']->timestamp );

        // Reschedule in a custom group; default group should be cleared.
        $this->scheduler->schedule_payment( $subscription, $payment_custom, 'custom' );
        $this->scheduler->schedule_retry( $subscription, $retry_custom, 'custom' );

        $custom_actions = $this->scheduler->get_scheduled_payment_actions( $subscription, 'custom' );
        $this->assertSame( $payment_custom, $custom_actions['next_payment']->timestamp );
        $this->assertSame( $retry_custom, $custom_actions['payment_retry']->timestamp );

        // Across all groups returns the custom actions as they are the only ones queued.
        $all_actions = $this->scheduler->get_scheduled_payment_actions( $subscription, false );
        $this->assertSame( $payment_custom, $all_actions['next_payment']->timestamp );
        $this->assertSame( $retry_custom, $all_actions['payment_retry']->timestamp );

        // API wrapper respects group parameter.
        $this->inject_api();
        $api_custom_actions = ASWC_Scheduler_API::get_scheduled_payment_actions( $subscription, 'custom' );
        $this->assertSame( $payment_custom, $api_custom_actions['next_payment']->timestamp );
        $this->assertSame( $retry_custom, $api_custom_actions['payment_retry']->timestamp );
    }

    public function test_get_last_scheduled_payment_actions_accepts_group() {
        $subscription        = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_default_one = time() + 30;
        $payment_default_two = $payment_default_one + 30;
        $payment_custom_one  = $payment_default_one + 15;
        $payment_custom_two  = $payment_custom_one + 30;
        $retry_default_one   = time() + 90;
        $retry_default_two   = $retry_default_one + 30;
        $retry_custom_one    = $retry_default_one + 15;
        $retry_custom_two    = $retry_custom_one + 30;

        // Schedule multiple actions in the default group.
        $this->scheduler->schedule_payment( $subscription, $payment_default_one );
        $this->scheduler->schedule_payment( $subscription, $payment_default_two );
        $this->scheduler->schedule_retry( $subscription, $retry_default_one );
        $this->scheduler->schedule_retry( $subscription, $retry_default_two );

        $default_actions = $this->scheduler->get_last_scheduled_payment_actions( $subscription );
        $this->assertSame( $payment_default_two, $default_actions['next_payment']->timestamp );
        $this->assertSame( $retry_default_two, $default_actions['payment_retry']->timestamp );

        // Reschedule multiple actions in a custom group; default group should be cleared.
        $this->scheduler->schedule_payment( $subscription, $payment_custom_one, 'custom' );
        $this->scheduler->schedule_payment( $subscription, $payment_custom_two, 'custom' );
        $this->scheduler->schedule_retry( $subscription, $retry_custom_one, 'custom' );
        $this->scheduler->schedule_retry( $subscription, $retry_custom_two, 'custom' );

        $custom_actions = $this->scheduler->get_last_scheduled_payment_actions( $subscription, 'custom' );
        $this->assertSame( $payment_custom_two, $custom_actions['next_payment']->timestamp );
        $this->assertSame( $retry_custom_two, $custom_actions['payment_retry']->timestamp );

        // Across all groups returns the custom actions as they are the only ones queued.
        $all_actions = $this->scheduler->get_last_scheduled_payment_actions( $subscription, false );
        $this->assertSame( $payment_custom_two, $all_actions['next_payment']->timestamp );
        $this->assertSame( $retry_custom_two, $all_actions['payment_retry']->timestamp );

        // API wrapper respects group parameter.
        $this->inject_api();
        $api_custom = ASWC_Scheduler_API::get_last_scheduled_payment_actions( $subscription, 'custom' );
        $this->assertSame( $payment_custom_two, $api_custom['next_payment']->timestamp );
        $this->assertSame( $retry_custom_two, $api_custom['payment_retry']->timestamp );
    }

    public function test_get_scheduled_retry_actions_accepts_group() {
        $subscription  = new Mock_Subscription_Payments( 10, array(), 55 );
        $retry_default = time() + 60;
        $retry_custom  = $retry_default + 30;

        // Schedule retry in the default group and verify retrieval.
        $this->scheduler->schedule_retry( $subscription, $retry_default );
        $default_actions = $this->scheduler->get_scheduled_retry_actions( $subscription );
        $this->assertCount( 1, $default_actions );
        $this->assertSame( $retry_default, reset( $default_actions )->timestamp );

        // Reschedule in a custom group; default group should be cleared.
        $this->scheduler->schedule_retry( $subscription, $retry_custom, 'custom' );

        $custom_actions = $this->scheduler->get_scheduled_retry_actions( $subscription, 'custom' );
        $this->assertCount( 1, $custom_actions );
        $this->assertSame( $retry_custom, reset( $custom_actions )->timestamp );

        // Across all groups returns the custom action as it is the only one queued.
        $all_actions = $this->scheduler->get_scheduled_retry_actions( $subscription, false );
        $this->assertCount( 1, $all_actions );

        // API wrapper respects group parameter.
        $this->inject_api();
        $api_custom = ASWC_Scheduler_API::get_scheduled_retry_actions( $subscription, 'custom' );
        $this->assertCount( 1, $api_custom );
        $this->assertSame( $retry_custom, reset( $api_custom )->timestamp );

        // Global helper wrapper mirrors API output.
        $func_custom = aswc_get_scheduled_retry_actions( $subscription, 'custom' );
        $this->assertCount( 1, $func_custom );
        $this->assertSame( $retry_custom, reset( $func_custom )->timestamp );
    }

    public function test_get_scheduled_payments_accepts_group() {
        $subscription    = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_default = time() + 60;
        $payment_custom  = $payment_default - 30;
        $retry_default   = time() + 150;
        $retry_custom    = $retry_default - 60;

        // Schedule in the default group and verify retrieval.
        $this->scheduler->schedule_payment( $subscription, $payment_default );
        $this->scheduler->schedule_retry( $subscription, $retry_default );

        $this->assertSame(
            array( 'next_payment' => $payment_default, 'payment_retry' => $retry_default ),
            $this->scheduler->get_scheduled_payments( $subscription )
        );

        // Reschedule in a custom group; default group should be cleared.
        $this->scheduler->schedule_payment( $subscription, $payment_custom, 'custom' );
        $this->scheduler->schedule_retry( $subscription, $retry_custom, 'custom' );

        $this->assertSame( array(), $this->scheduler->get_scheduled_payments( $subscription ) );
        $this->assertSame(
            array( 'next_payment' => $payment_custom, 'payment_retry' => $retry_custom ),
            $this->scheduler->get_scheduled_payments( $subscription, 'custom' )
        );

        $this->assertSame(
            array( 'next_payment' => $payment_custom, 'payment_retry' => $retry_custom ),
            $this->scheduler->get_scheduled_payments( $subscription, false )
        );

        $this->inject_api();
        $this->assertSame(
            array( 'next_payment' => $payment_custom, 'payment_retry' => $retry_custom ),
            ASWC_Scheduler_API::get_scheduled_payments( $subscription, 'custom' )
        );
        $this->assertSame(
            array( 'next_payment' => $payment_custom, 'payment_retry' => $retry_custom ),
            ASWC_Scheduler_API::get_scheduled_payments( $subscription, false )
        );
    }

    public function test_get_last_scheduled_payments_accepts_group() {
        $subscription    = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_default = time() + 60;
        $payment_custom  = $payment_default + 30;
        $retry_default   = time() + 150;
        $retry_custom    = $retry_default + 60;

        // Schedule in the default group and verify retrieval.
        $this->scheduler->schedule_payment( $subscription, $payment_default );
        $this->scheduler->schedule_retry( $subscription, $retry_default );

        $this->assertSame(
            array( 'next_payment' => $payment_default, 'payment_retry' => $retry_default ),
            $this->scheduler->get_last_scheduled_payments( $subscription )
        );

        // Reschedule in a custom group; default group should be cleared.
        $this->scheduler->schedule_payment( $subscription, $payment_custom, 'custom' );
        $this->scheduler->schedule_retry( $subscription, $retry_custom, 'custom' );

        $this->assertSame( array(), $this->scheduler->get_last_scheduled_payments( $subscription ) );
        $this->assertSame(
            array( 'next_payment' => $payment_custom, 'payment_retry' => $retry_custom ),
            $this->scheduler->get_last_scheduled_payments( $subscription, 'custom' )
        );

        $this->assertSame(
            array( 'next_payment' => $payment_custom, 'payment_retry' => $retry_custom ),
            $this->scheduler->get_last_scheduled_payments( $subscription, false )
        );

        $this->inject_api();
        $this->assertSame(
            array( 'next_payment' => $payment_custom, 'payment_retry' => $retry_custom ),
            ASWC_Scheduler_API::get_last_scheduled_payments( $subscription, 'custom' )
        );
        $this->assertSame(
            array( 'next_payment' => $payment_custom, 'payment_retry' => $retry_custom ),
            ASWC_Scheduler_API::get_last_scheduled_payments( $subscription, false )
        );
    }

    public function test_has_scheduled_payments_accepts_group() {
        $subscription = new Mock_Subscription_Payments();

        $this->scheduler->schedule_payment( $subscription, time() + 60, 'custom' );

        $this->assertFalse( $this->scheduler->has_scheduled_payments( $subscription ) );
        $this->assertTrue( $this->scheduler->has_scheduled_payments( $subscription, 'custom' ) );
        $this->assertTrue( $this->scheduler->has_scheduled_payments( $subscription, false ) );

        $this->inject_api();
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_payments( $subscription, 'custom' ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_payments( $subscription, false ) );

        $this->scheduler->unschedule_all( $subscription, 'custom' );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_payments( $subscription, false ) );
    }

    public function test_api_wrappers_schedule_and_unschedule() {
        $subscription    = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_ts      = time() + 30;
        $retry_ts        = time() + 90;

        $this->inject_api();

        ASWC_Scheduler_API::schedule_payment( $subscription, $payment_ts );
        ASWC_Scheduler_API::schedule_retry( $subscription, $retry_ts );

        $retry_after_ts = ASWC_Scheduler_API::schedule_retry_after( $subscription, 30 );
        $this->assertSame( $retry_after_ts, ASWC_Scheduler_API::get_scheduled_retry( $subscription ) );

        $rule         = new Mock_Retry_Rule( 45 );
        $retry_rule_ts = ASWC_Scheduler_API::schedule_retry_with_rule( $subscription, $rule );

        $manual_ts = time() + 15;
        ASWC_Scheduler_API::schedule_manual_payment( $subscription, $manual_ts );

        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_payment( $subscription ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_retry( $subscription ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_payments( $subscription ) );

        $this->assertSame( $manual_ts, ASWC_Scheduler_API::get_scheduled_payment( $subscription ) );
        $this->assertSame( $retry_rule_ts, ASWC_Scheduler_API::get_scheduled_retry( $subscription ) );
        $this->assertSame(
            array( 'next_payment' => $manual_ts, 'payment_retry' => $retry_rule_ts ),
            ASWC_Scheduler_API::get_scheduled_payments( $subscription )
        );

        ASWC_Scheduler_API::unschedule_payment( $subscription );
        ASWC_Scheduler_API::unschedule_retry( $subscription );

        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_payments( $subscription ) );
    }

    public function test_api_schedule_retry_after_invalid_interval_unschedules() {
        $subscription = new Mock_Subscription_Payments();
        $this->inject_api();

        ASWC_Scheduler_API::schedule_retry_after( $subscription, 60 );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_retry( $subscription ) );

        $scheduled = ASWC_Scheduler_API::schedule_retry_after( $subscription, 0 );
        $this->assertSame( 0, $scheduled );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_retry( $subscription ) );
    }

    public function test_api_schedule_retry_with_rule_invalid_interval_unschedules() {
        $subscription = new Mock_Subscription_Payments();
        $this->inject_api();

        ASWC_Scheduler_API::schedule_retry_with_rule( $subscription, new Mock_Retry_Rule( 60 ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_retry( $subscription ) );

        $scheduled = ASWC_Scheduler_API::schedule_retry_with_rule( $subscription, new Mock_Retry_Rule( 0 ) );

        $this->assertSame( 0, $scheduled );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_retry( $subscription ) );
    }

    public function test_api_schedule_payment_with_non_positive_timestamp_unschedules() {
        $subscription = new Mock_Subscription_Payments();
        $this->inject_api();

        ASWC_Scheduler_API::schedule_payment( $subscription, time() + 60 );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_payment( $subscription ) );

        ASWC_Scheduler_API::schedule_payment( $subscription, 0 );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_payment( $subscription ) );

        ASWC_Scheduler_API::schedule_payment( $subscription, time() + 60 );
        ASWC_Scheduler_API::schedule_payment( $subscription, -10 );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_payment( $subscription ) );
    }

    public function test_api_schedule_retry_with_non_positive_timestamp_unschedules() {
        $subscription = new Mock_Subscription_Payments();
        $this->inject_api();

        ASWC_Scheduler_API::schedule_retry( $subscription, time() + 60 );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_retry( $subscription ) );

        ASWC_Scheduler_API::schedule_retry( $subscription, 0 );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_retry( $subscription ) );

        ASWC_Scheduler_API::schedule_retry( $subscription, time() + 60 );
        ASWC_Scheduler_API::schedule_retry( $subscription, -10 );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_retry( $subscription ) );
    }

    public function test_api_schedule_manual_payment_with_non_positive_timestamp_unschedules() {
        $subscription = new Mock_Subscription_Payments();
        $this->inject_api();

        ASWC_Scheduler_API::schedule_manual_payment( $subscription, time() + 60 );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_payment( $subscription ) );

        $returned = ASWC_Scheduler_API::schedule_manual_payment( $subscription, 0 );
        $this->assertSame( 0, $returned );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_payment( $subscription ) );

        ASWC_Scheduler_API::schedule_manual_payment( $subscription, time() + 60 );
        $returned = ASWC_Scheduler_API::schedule_manual_payment( $subscription, -1 );
        $this->assertSame( -1, $returned );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_payment( $subscription ) );
    }

    public function test_api_schedule_manual_payment_with_false_group_unschedules_all() {
        $times        = array( 'next_payment' => time() + 40 );
        $subscription = new Mock_Subscription_Payments( 10, $times );
        $custom_group = 'external-group';
        $this->inject_api();

        // Manually schedule a payment in a custom group.
        $this->core->schedule_action(
            time() + 80,
            'advanced_scheduled_subscription_payment',
            $this->core->get_action_args( 'next_payment', $subscription ),
            false,
            $custom_group
        );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_payment( $subscription, $custom_group ) );

        // Scheduling via API with group false should clear all groups before scheduling in the default group.
        ASWC_Scheduler_API::schedule_manual_payment( $subscription, null, false );

        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_payment( $subscription, $custom_group ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_payment( $subscription ) );
    }

    public function test_api_schedule_with_custom_group() {
        $subscription = new Mock_Subscription_Payments( 10, array(), 55 );
        $payment_ts   = time() + 30;
        $retry_ts     = time() + 90;
        $group        = 'api-group';

        $this->inject_api();

        ASWC_Scheduler_API::schedule_payment( $subscription, $payment_ts, $group );
        ASWC_Scheduler_API::schedule_retry( $subscription, $retry_ts, $group );

        $payment_key = $group . '|advanced_scheduled_subscription_payment:' . json_encode( array( 'subscription_id' => 10 ) );
        $retry_key   = $group . '|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 55 ) );

        $this->assertSame( $payment_ts, $this->core->scheduled[ $payment_key ] );
        $this->assertSame( $retry_ts, $this->core->scheduled[ $retry_key ] );

        ASWC_Scheduler_API::unschedule_payment( $subscription, $group );
        ASWC_Scheduler_API::unschedule_retry( $subscription, $group );

        $this->assertArrayNotHasKey( $payment_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $retry_key, $this->core->scheduled );
    }

    public function test_api_schedule_and_unschedule_all_payments() {
        $times        = array(
            'next_payment'  => time() + 30,
            'payment_retry' => time() + 60,
        );
        $subscription = new Mock_Subscription_Payments( 10, $times, 55 );

        $this->inject_api();

        ASWC_Scheduler_API::schedule_all_payments( $subscription );

        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_payment( $subscription ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_retry( $subscription ) );

        ASWC_Scheduler_API::unschedule_all_payments( $subscription );

        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_payments( $subscription ) );
    }

    public function test_schedule_all_and_unschedule_all_with_custom_group() {
        $times        = array(
            'next_payment'  => time() + 60,
            'payment_retry' => time() + 120,
        );
        $subscription = new Mock_Subscription_Payments( 10, $times, 77 );
        $group        = 'custom-group';

        $this->scheduler->schedule_all( $subscription, $group );

        $payment_key = $group . '|advanced_scheduled_subscription_payment:' . json_encode( array( 'subscription_id' => 10 ) );
        $retry_key   = $group . '|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 77 ) );

        $this->assertSame( $times['next_payment'], $this->core->scheduled[ $payment_key ] );
        $this->assertSame( $times['payment_retry'], $this->core->scheduled[ $retry_key ] );

        $this->scheduler->unschedule_all( $subscription, $group );

        $this->assertArrayNotHasKey( $payment_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $retry_key, $this->core->scheduled );
    }

    public function test_api_schedule_and_unschedule_all_payments_with_custom_group() {
        $times        = array(
            'next_payment'  => time() + 30,
            'payment_retry' => time() + 60,
        );
        $subscription = new Mock_Subscription_Payments( 10, $times, 55 );
        $group        = 'api-group';

        $this->inject_api();

        ASWC_Scheduler_API::schedule_all_payments( $subscription, $group );

        $payment_key = $group . '|advanced_scheduled_subscription_payment:' . json_encode( array( 'subscription_id' => 10 ) );
        $retry_key   = $group . '|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 55 ) );

        $this->assertSame( $times['next_payment'], $this->core->scheduled[ $payment_key ] );
        $this->assertSame( $times['payment_retry'], $this->core->scheduled[ $retry_key ] );

        ASWC_Scheduler_API::unschedule_all_payments( $subscription, $group );

        $this->assertArrayNotHasKey( $payment_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $retry_key, $this->core->scheduled );
    }

    public function test_unschedule_all_cross_group_operations() {
        $subscription = new Mock_Subscription_Payments();
        $payment_ts   = time() + 60;
        $retry_ts     = time() + 120;

        $this->scheduler->schedule_payment( $subscription, $payment_ts );
        $this->scheduler->schedule_retry( $subscription, $retry_ts );

        $external_payment_key = 'external|advanced_scheduled_subscription_payment:' . json_encode( array( 'subscription_id' => 10 ) );
        $external_retry_key   = 'external|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 5 ) );
        $this->core->scheduled[ $external_payment_key ] = $payment_ts + 30;
        $this->core->scheduled[ $external_retry_key ]   = $retry_ts + 30;

        $this->scheduler->unschedule_all( $subscription, false );

        $this->assertFalse( $this->scheduler->has_scheduled_payment( $subscription, false ) );
        $this->assertFalse( $this->scheduler->has_scheduled_retry( $subscription, false ) );
        $this->assertArrayNotHasKey( $external_payment_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $external_retry_key, $this->core->scheduled );
    }

    public function test_api_unschedule_all_payments_cross_group_operations() {
        $subscription = new Mock_Subscription_Payments();
        $payment_ts   = time() + 60;
        $retry_ts     = time() + 120;
        $external     = 'external';

        $this->inject_api();

        ASWC_Scheduler_API::schedule_payment( $subscription, $payment_ts );
        ASWC_Scheduler_API::schedule_retry( $subscription, $retry_ts );

        $external_payment_key = $external . '|advanced_scheduled_subscription_payment:' . json_encode( array( 'subscription_id' => 10 ) );
        $external_retry_key   = $external . '|advanced_scheduled_subscription_payment_retry:' . json_encode( array( 'order_id' => 5 ) );
        $this->core->scheduled[ $external_payment_key ] = $payment_ts + 30;
        $this->core->scheduled[ $external_retry_key ]   = $retry_ts + 30;

        ASWC_Scheduler_API::unschedule_all_payments( $subscription, false );

        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_payment( $subscription, false ) );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_retry( $subscription, false ) );
        $this->assertArrayNotHasKey( $external_payment_key, $this->core->scheduled );
        $this->assertArrayNotHasKey( $external_retry_key, $this->core->scheduled );
    }
}
