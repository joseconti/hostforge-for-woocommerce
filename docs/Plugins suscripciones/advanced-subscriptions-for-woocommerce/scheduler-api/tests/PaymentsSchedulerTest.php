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

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/../payments/class-aswc-scheduler-payments.php';

class Test_Payments_Core_Scheduler extends ASWC_Scheduler_Core {
    public $scheduled = array();

    protected function build_key( $hook, $args, $group ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args ) . ':' . (string) $group;
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $this->scheduled[ $this->build_key( $action_hook, $action_args, $group ) ] = $timestamp;
        return 1;
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        if ( false === $group ) {
            $pattern = '/^' . preg_quote( $action_hook, '/' ) . ':' . preg_quote( json_encode( $action_args ), '/' ) . ':/';
            foreach ( array_keys( $this->scheduled ) as $key ) {
                if ( preg_match( $pattern, $key ) ) {
                    unset( $this->scheduled[ $key ] );
                }
            }
            return;
        }

        unset( $this->scheduled[ $this->build_key( $action_hook, $action_args, $group ) ] );
    }

    public function unschedule_action( $action_hook, $action_args, $group = null ) {
        $this->unschedule_actions( $action_hook, $action_args, $group );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        if ( false === $group ) {
            $pattern = '/^' . preg_quote( $action_hook, '/' ) . ':' . preg_quote( json_encode( $action_args ), '/' ) . ':/';
            foreach ( $this->scheduled as $key => $timestamp ) {
                if ( preg_match( $pattern, $key ) ) {
                    return $timestamp;
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
}

class Dummy_Subscription_Payments {
    private $id;
    private $times;
    private $last_order_id;

    public function __construct( $id, $times, $last_order_id = 0 ) {
        $this->id            = $id;
        $this->times         = $times;
        $this->last_order_id = $last_order_id;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_time( $type ) {
        return $this->times[ $type ] ?? 0;
    }

    public function get_last_order( $type = 'ids', $context = 'renewal' ) {
        return $this->last_order_id;
    }
}

class PaymentsSchedulerTest extends TestCase {
    protected $core;
    protected $payments;

    protected function setUp(): void {
        $this->core     = new Test_Payments_Core_Scheduler();
        $this->payments = new ASWC_Scheduler_Payments( $this->core );
    }

    public function test_schedule_and_unschedule_payment() {
        $timestamp    = time() + 60;
        $subscription = new Dummy_Subscription_Payments( 10, array( 'next_payment' => $timestamp ), 99 );

        $this->payments->schedule_payment( $subscription );
        $this->assertSame( $timestamp, $this->payments->get_scheduled_payment( $subscription ) );

        $this->payments->unschedule_payment( $subscription );
        $this->assertFalse( $this->payments->has_scheduled_payment( $subscription ) );
    }

    public function test_schedule_and_unschedule_retry() {
        $timestamp    = time() + 120;
        $subscription = new Dummy_Subscription_Payments( 20, array( 'payment_retry' => $timestamp ), 55 );

        $this->payments->schedule_retry( $subscription );
        $this->assertSame( $timestamp, $this->payments->get_scheduled_retry( $subscription ) );

        $this->payments->unschedule_retry( $subscription );
        $this->assertFalse( $this->payments->has_scheduled_retry( $subscription ) );
    }

    public function test_schedule_all_and_unschedule_all() {
        $times        = array(
            'next_payment'   => time() + 30,
            'payment_retry'  => time() + 90,
        );
        $subscription = new Dummy_Subscription_Payments( 30, $times, 77 );

        $this->payments->schedule_all( $subscription );
        $this->assertTrue( $this->payments->has_scheduled_payment( $subscription ) );
        $this->assertTrue( $this->payments->has_scheduled_retry( $subscription ) );

        $this->payments->unschedule_all( $subscription );
        $this->assertFalse( $this->payments->has_scheduled_payments( $subscription ) );
    }

    public function test_schedule_all_with_custom_group() {
        $times        = array(
            'next_payment'  => time() + 50,
            'payment_retry' => time() + 80,
        );
        $subscription = new Dummy_Subscription_Payments( 40, $times );
        $group        = 'custom_group';

        $this->payments->schedule_all( $subscription, $group );
        $this->assertFalse( $this->payments->has_scheduled_payment( $subscription ) );
        $this->assertTrue( $this->payments->has_scheduled_payment( $subscription, $group ) );
        $this->assertTrue( $this->payments->has_scheduled_retry( $subscription, $group ) );

        $this->payments->unschedule_all( $subscription, $group );
        $this->assertFalse( $this->payments->has_scheduled_payments( $subscription, $group ) );
    }

    public function test_group_parameter_allows_cross_group_operations() {
        $subscription = new Dummy_Subscription_Payments( 50, array( 'next_payment' => time() + 60 ) );

        // Schedule a payment in the default group.
        $this->payments->schedule_payment( $subscription );

        // Manually schedule a payment in an external group.
        $external_group = 'external_group';
        $this->core->schedule_action(
            time() + 120,
            'advanced_scheduled_subscription_payment',
            $this->core->get_action_args( 'next_payment', $subscription ),
            false,
            $external_group
        );

        // Unschedule across all groups and confirm nothing remains.
        $this->payments->unschedule_payment( $subscription, false );
        $this->assertFalse( $this->payments->has_scheduled_payment( $subscription, false ) );

        // Re-add the external action and confirm it can be retrieved.
        $this->core->schedule_action(
            time() + 180,
            'advanced_scheduled_subscription_payment',
            $this->core->get_action_args( 'next_payment', $subscription ),
            false,
            $external_group
        );

        $this->assertNotFalse( $this->payments->get_scheduled_payment( $subscription, false ) );
    }

    public function test_has_scheduled_payments_accepts_group() {
        $subscription = new Dummy_Subscription_Payments( 80, array(), 100 );
        $this->payments->schedule_payment( $subscription, time() + 60, 'custom' );

        $this->assertFalse( $this->payments->has_scheduled_payments( $subscription ) );
        $this->assertTrue( $this->payments->has_scheduled_payments( $subscription, 'custom' ) );
        $this->assertTrue( $this->payments->has_scheduled_payments( $subscription, false ) );
    }

    public function test_schedule_payment_with_false_group_clears_all_groups() {
        $subscription   = new Dummy_Subscription_Payments( 90, array( 'next_payment' => time() + 30 ) );
        $external_group = 'external_group';

        $this->core->schedule_action(
            time() + 100,
            'advanced_scheduled_subscription_payment',
            $this->core->get_action_args( 'next_payment', $subscription ),
            false,
            $external_group
        );

        $new_timestamp = time() + 200;
        $this->payments->schedule_payment( $subscription, $new_timestamp, false );

        $this->assertSame( $new_timestamp, $this->payments->get_scheduled_payment( $subscription ) );
        $this->assertFalse( $this->payments->has_scheduled_payment( $subscription, $external_group ) );
    }

    public function test_schedule_retry_with_false_group_clears_all_groups() {
        $subscription   = new Dummy_Subscription_Payments( 95, array( 'payment_retry' => time() + 60 ), 123 );
        $external_group = 'external_group';

        $this->core->schedule_action(
            time() + 300,
            'advanced_scheduled_subscription_payment_retry',
            $this->core->get_action_args( 'payment_retry', $subscription ),
            false,
            $external_group
        );

        $new_timestamp = time() + 400;
        $this->payments->schedule_retry( $subscription, $new_timestamp, false );

        $this->assertSame( $new_timestamp, $this->payments->get_scheduled_retry( $subscription ) );
        $this->assertFalse( $this->payments->has_scheduled_retry( $subscription, $external_group ) );
    }
}
