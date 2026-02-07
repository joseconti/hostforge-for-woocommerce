<?php
use PHPUnit\Framework\TestCase;

if ( ! isset( $test_actions ) ) {
    $test_actions = array();
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        global $test_actions;
        $test_actions[] = $hook;
    }
}

if ( ! isset( $test_filters ) ) {
    $test_filters = array();
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        global $test_filters;
        $test_filters[ $hook ][ $priority ][] = $callback;
    }
}

if ( ! function_exists( 'remove_all_filters' ) ) {
    function remove_all_filters( $hook ) {
        global $test_filters;
        unset( $test_filters[ $hook ] );
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        global $test_filters;

        $args = func_get_args();

        if ( isset( $test_filters[ $tag ] ) ) {
            ksort( $test_filters[ $tag ] );

            foreach ( $test_filters[ $tag ] as $callbacks ) {
                foreach ( (array) $callbacks as $callback ) {
                    $args[1] = $value;
                    $value   = call_user_func_array( $callback, array_slice( $args, 1 ) );
                }
            }
        }

        return $value;
    }
}

if ( ! function_exists( 'aswc_get_subscription_date_types' ) ) {
    function aswc_get_subscription_date_types() {
        return array(
            'start'        => '',
            'trial_end'    => '',
            'next_payment' => '',
            'end'          => '',
        );
    }
}

require_once __DIR__ . '/../scheduler.php';

// Minimal wrapper to proxy last scheduled events through the API without
// loading the full plugin classes.
class ASWC_Action_Scheduler {
    public function get_last_scheduled_events( $subscription ) {
        return ASWC_Scheduler_API::get_all_last_scheduled_events( $subscription );
    }
}

class Dummy_Payment_Scheduler_Last {
    public $events;
    public function __construct( $events ) { $this->events = $events; }
    public function get_last_scheduled_payments( $subscription, $group = null ) {
        return $this->events;
    }
}

class Dummy_Lifecycle_Scheduler_Last {
    public $events;
    public function __construct( $events ) { $this->events = $events; }
    public function get_last_scheduled_events( $subscription, $group = null ) {
        return $this->events;
    }
}

class Dummy_Notification_Scheduler_Last {
    public $events;
    public function __construct( $events ) { $this->events = $events; }
    public function get_last_scheduled_notifications( $subscription, $date_types = array(), $group = null ) {
        return $this->events;
    }
}

class ActionSchedulerLastEventsTest extends TestCase {
    protected function setUp(): void {
        $this->reset_api();
    }

    protected function tearDown(): void {
        $this->reset_api();
        global $test_filters;
        $test_filters = array();
    }

    protected function reset_api() {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        foreach ( array( 'payments', 'lifecycle', 'notifications' ) as $prop ) {
            $p = $ref->getProperty( $prop );
            $p->setAccessible( true );
            $p->setValue( null, null );
        }
    }

    protected function inject_modules( $payment_events, $lifecycle_events, $notification_events ) {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );

        $p = $ref->getProperty( 'payments' );
        $p->setAccessible( true );
        $p->setValue( null, new Dummy_Payment_Scheduler_Last( $payment_events ) );

        $p = $ref->getProperty( 'lifecycle' );
        $p->setAccessible( true );
        $p->setValue( null, new Dummy_Lifecycle_Scheduler_Last( $lifecycle_events ) );

        $p = $ref->getProperty( 'notifications' );
        $p->setAccessible( true );
        $p->setValue( null, new Dummy_Notification_Scheduler_Last( $notification_events ) );
    }

    public function test_wrapper_returns_combined_last_events() {
        $this->inject_modules( array( 'next_payment' => 10 ), array( 'end' => 20 ), array( 'trial_end' => 5 ) );
        $wrapper      = new ASWC_Action_Scheduler();
        $subscription = new stdClass();

        $this->assertSame(
            array( 'next_payment' => 10, 'end' => 20, 'trial_end' => 5 ),
            $wrapper->get_last_scheduled_events( $subscription )
        );
    }
}
