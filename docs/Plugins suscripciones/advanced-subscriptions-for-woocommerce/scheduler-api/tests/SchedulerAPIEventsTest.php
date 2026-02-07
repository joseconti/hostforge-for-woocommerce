<?php
use PHPUnit\Framework\TestCase;

// Minimal WordPress-like environment for tests.
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

// Simple stores for hooks and options used in tests.
if ( ! isset( $test_options ) ) {
    $test_options = array();
}

if ( ! isset( $test_actions ) ) {
    $test_actions = array();
}

if ( ! isset( $test_filters ) ) {
    $test_filters = array();
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        global $test_actions;
        $test_actions[] = $hook;
    }
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

        if ( isset( $test_filters[ $tag ] ) ) {
            ksort( $test_filters[ $tag ] );

            foreach ( $test_filters[ $tag ] as $callbacks ) {
                foreach ( (array) $callbacks as $callback ) {
                    $value = call_user_func( $callback, $value );
                }
            }
        }

        return $value;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = false ) {
        global $test_options;
        return $test_options[ $name ] ?? $default;
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) {
        return time();
    }
}

require_once __DIR__ . '/../scheduler.php';

class Dummy_Payment_Scheduler {
    public $events = array();
    public $schedule_all_called = false;
    public $unschedule_all_called = false;
    public $last_group = null;

    public function __construct( $events = array() ) {
        $this->events = $events;
    }

    public function get_scheduled_payments( $subscription, $group = null ) {
        $this->last_group = $group;
        return $this->events;
    }

    public function get_last_scheduled_payments( $subscription, $group = null ) {
        $this->last_group = $group;
        return $this->events;
    }

    public function has_scheduled_payments( $subscription, $group = null ) {
        $this->last_group = $group;
        return ! empty( $this->events );
    }

    public function schedule_all( $subscription, $group = null ) {
        $this->schedule_all_called = true;
        $this->last_group          = $group;
    }

    public function unschedule_all( $subscription, $group = null ) {
        $this->unschedule_all_called = true;
        $this->last_group          = $group;
    }
}

class Dummy_Lifecycle_Scheduler {
    public $events = array();
    public $actions = array();
    public $schedule_all_called = false;
    public $unschedule_all_called = false;
    public $last_group = null;

    public function __construct( $events = array(), $actions = array() ) {
        $this->events  = $events;
        $this->actions = $actions;
    }

    public function get_scheduled_events( $subscription, $group = null ) {
        $this->last_group = $group;
        return $this->events;
    }

    public function get_scheduled_actions( $subscription, $group = null ) {
        $this->last_group = $group;
        return $this->actions;
    }

    public function get_last_scheduled_events( $subscription, $group = null ) {
        $this->last_group = $group;
        return $this->events;
    }

    public function get_last_scheduled_actions( $subscription, $group = null ) {
        $this->last_group = $group;
        return $this->actions;
    }

    public function has_scheduled_events( $subscription, $group = null ) {
        $this->last_group = $group;
        return ! empty( $this->events );
    }

    public function schedule_all( $subscription, $group = null ) {
        $this->schedule_all_called = true;
        $this->last_group          = $group;
    }

    public function unschedule_all( $subscription, $group = null ) {
        $this->unschedule_all_called = true;
        $this->last_group          = $group;
    }
}

class Dummy_Notification_Scheduler {
    public $events = array();
    public $schedule_all_called = false;
    public $unschedule_all_called = false;
    public $last_callback = null;
    public $last_date_types = array();
    public $last_group = null;

    public function __construct( $events = array() ) {
        $this->events = $events;
    }

    public function get_scheduled_notifications( $subscription, $date_types = array(), $group = null ) {
        $this->last_group = $group;
        return $this->events;
    }

    public function get_last_scheduled_notifications( $subscription, $date_types = array(), $group = null ) {
        $this->last_group = $group;
        return $this->events;
    }

    public function has_scheduled_notifications( $subscription, $date_types = array(), $group = null ) {
        $this->last_group = $group;
        return ! empty( $this->events );
    }

    public function schedule_all( $subscription, $callback = null, $date_types = array(), $group = null ) {
        $this->schedule_all_called = true;
        $this->last_callback       = $callback;
        $this->last_group          = $group;
    }

    public function unschedule_all( $subscription, $date_types = array(), $exceptions = array(), $group = null ) {
        $this->unschedule_all_called = true;
        $this->last_date_types       = $date_types;
        $this->last_group            = $group;
    }
}

class SchedulerAPIEventsTest extends TestCase {
    protected function setUp(): void {
        $this->reset_api();
    }

    protected function tearDown(): void {
        $this->reset_api();
    }

    protected function reset_api() {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        foreach ( array( 'payments', 'lifecycle', 'notifications' ) as $prop ) {
            $p = $ref->getProperty( $prop );
            $p->setAccessible( true );
            $p->setValue( null, null );
        }
    }

    protected function inject_modules( $payment_events, $lifecycle_events, $notification_events, $lifecycle_actions = array() ) {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );

        $payments = new Dummy_Payment_Scheduler( $payment_events );
        $p = $ref->getProperty( 'payments' );
        $p->setAccessible( true );
        $p->setValue( null, $payments );

        $lifecycle = new Dummy_Lifecycle_Scheduler( $lifecycle_events, $lifecycle_actions );
        $p = $ref->getProperty( 'lifecycle' );
        $p->setAccessible( true );
        $p->setValue( null, $lifecycle );

        $notifications = new Dummy_Notification_Scheduler( $notification_events );
        $p = $ref->getProperty( 'notifications' );
        $p->setAccessible( true );
        $p->setValue( null, $notifications );

        return array( $payments, $lifecycle, $notifications );
    }

    public function test_get_scheduled_events_combines_payment_and_lifecycle() {
        $this->inject_modules( array( 'next_payment' => 10 ), array( 'end' => 20 ), array() );
        $subscription = new stdClass();

        $events = ASWC_Scheduler_API::get_scheduled_events( $subscription );

        $this->assertSame( array( 'next_payment' => 10, 'end' => 20 ), $events );
    }

    public function test_get_scheduled_events_passes_group_to_modules() {
        list( $payments, $lifecycle ) = $this->inject_modules( array(), array(), array() );
        $subscription = new stdClass();

        ASWC_Scheduler_API::get_scheduled_events( $subscription, false );

        $this->assertSame( false, $payments->last_group );
        $this->assertSame( false, $lifecycle->last_group );
    }

    public function test_get_all_scheduled_events_includes_notifications() {
        $this->inject_modules( array( 'next_payment' => 10 ), array( 'end' => 20 ), array( 'trial_end' => 5 ) );
        $subscription = new stdClass();

        $events = ASWC_Scheduler_API::get_all_scheduled_events( $subscription );

        $this->assertSame( array( 'next_payment' => 10, 'end' => 20, 'trial_end' => 5 ), $events );
    }

    public function test_get_all_scheduled_events_passes_group_to_modules() {
        list( $payments, $lifecycle, $notifications ) = $this->inject_modules( array(), array(), array() );
        $subscription = new stdClass();

        ASWC_Scheduler_API::get_all_scheduled_events( $subscription, array(), false );

        $this->assertSame( false, $payments->last_group );
        $this->assertSame( false, $lifecycle->last_group );
        $this->assertSame( false, $notifications->last_group );
    }

    public function test_get_last_scheduled_events_combines_payment_and_lifecycle() {
        $this->inject_modules( array( 'next_payment' => 10 ), array( 'end' => 20 ), array() );
        $subscription = new stdClass();

        $events = ASWC_Scheduler_API::get_last_scheduled_events( $subscription );

        $this->assertSame( array( 'next_payment' => 10, 'end' => 20 ), $events );
    }

    public function test_get_last_scheduled_events_passes_group_to_modules() {
        list( $payments, $lifecycle ) = $this->inject_modules( array(), array(), array() );
        $subscription = new stdClass();

        ASWC_Scheduler_API::get_last_scheduled_events( $subscription, false );

        $this->assertSame( false, $payments->last_group );
        $this->assertSame( false, $lifecycle->last_group );
    }

    public function test_get_all_last_scheduled_events_includes_notifications() {
        $this->inject_modules( array( 'next_payment' => 10 ), array( 'end' => 20 ), array( 'trial_end' => 5 ) );
        $subscription = new stdClass();

        $events = ASWC_Scheduler_API::get_all_last_scheduled_events( $subscription );

        $this->assertSame( array( 'next_payment' => 10, 'end' => 20, 'trial_end' => 5 ), $events );
    }

    public function test_get_all_last_scheduled_events_passes_group_to_modules() {
        list( $payments, $lifecycle, $notifications ) = $this->inject_modules( array(), array(), array() );
        $subscription = new stdClass();

        ASWC_Scheduler_API::get_all_last_scheduled_events( $subscription, array(), false );

        $this->assertSame( false, $payments->last_group );
        $this->assertSame( false, $lifecycle->last_group );
        $this->assertSame( false, $notifications->last_group );
    }

    public function test_has_scheduled_events_checks_all_modules() {
        $subscription = new stdClass();

        // No events scheduled.
        $this->inject_modules( array(), array(), array() );
        $this->assertFalse( ASWC_Scheduler_API::has_scheduled_events( $subscription ) );

        // Payment scheduled.
        $this->inject_modules( array( 'next_payment' => 10 ), array(), array() );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_events( $subscription ) );

        // Lifecycle scheduled.
        $this->inject_modules( array(), array( 'end' => 20 ), array() );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_events( $subscription ) );

        // Notification scheduled.
        $this->inject_modules( array(), array(), array( 'trial_end' => 5 ) );
        $this->assertTrue( ASWC_Scheduler_API::has_scheduled_events( $subscription ) );
    }

    public function test_has_scheduled_events_passes_group_to_modules() {
        list( $payments, $lifecycle, $notifications ) = $this->inject_modules( array(), array(), array() );
        $subscription = new stdClass();

        ASWC_Scheduler_API::has_scheduled_events( $subscription, array(), false );

        $this->assertSame( false, $payments->last_group );
        $this->assertSame( false, $lifecycle->last_group );
        $this->assertSame( false, $notifications->last_group );
    }

    public function test_schedule_all_delegates_to_modules() {
        list( $payments, $lifecycle, $notifications ) = $this->inject_modules( array(), array(), array() );
        $subscription = new stdClass();
        $cb = function() { return 0; };

        ASWC_Scheduler_API::schedule_all( $subscription, $cb );

        $this->assertTrue( $payments->schedule_all_called );
        $this->assertTrue( $lifecycle->schedule_all_called );
        $this->assertTrue( $notifications->schedule_all_called );
        $this->assertSame( $cb, $notifications->last_callback );
        $this->assertNull( $payments->last_group );
        $this->assertNull( $lifecycle->last_group );
        $this->assertNull( $notifications->last_group );
    }

    public function test_schedule_all_passes_group_to_modules() {
        list( $payments, $lifecycle, $notifications ) = $this->inject_modules( array(), array(), array() );
        $subscription = new stdClass();
        $cb = function() { return 0; };

        ASWC_Scheduler_API::schedule_all( $subscription, $cb, null, false );

        $this->assertSame( false, $payments->last_group );
        $this->assertSame( false, $lifecycle->last_group );
        $this->assertSame( false, $notifications->last_group );
    }

    public function test_unschedule_all_delegates_to_modules() {
        list( $payments, $lifecycle, $notifications ) = $this->inject_modules( array(), array(), array() );
        $subscription = new stdClass();
        $types = array( 'trial_end', 'next_payment' );

        ASWC_Scheduler_API::unschedule_all( $subscription, $types );

        $this->assertTrue( $payments->unschedule_all_called );
        $this->assertTrue( $lifecycle->unschedule_all_called );
        $this->assertTrue( $notifications->unschedule_all_called );
        $this->assertNull( $payments->last_group );
        $this->assertNull( $lifecycle->last_group );
        $this->assertSame( $types, $notifications->last_date_types );
        $this->assertNull( $notifications->last_group );
    }

    public function test_unschedule_all_passes_group_to_modules() {
        list( $payments, $lifecycle, $notifications ) = $this->inject_modules( array(), array(), array() );
        $subscription = new stdClass();
        $types = array( 'trial_end' );

        ASWC_Scheduler_API::unschedule_all( $subscription, $types, false );

        $this->assertSame( false, $payments->last_group );
        $this->assertSame( false, $lifecycle->last_group );
        $this->assertSame( false, $notifications->last_group );
    }

    public function test_get_scheduled_lifecycle_actions_passes_group_to_module() {
        list( $payments, $lifecycle, $notifications ) = $this->inject_modules( array(), array(), array(), array( 'trial_end' => (object) array( 'timestamp' => 1 ) ) );
        $subscription = new stdClass();

        ASWC_Scheduler_API::get_scheduled_lifecycle_actions( $subscription, false );

        $this->assertSame( false, $lifecycle->last_group );
    }

    public function test_get_last_scheduled_lifecycle_actions_passes_group_to_module() {
        list( $payments, $lifecycle, $notifications ) = $this->inject_modules( array(), array(), array(), array( 'trial_end' => (object) array( 'timestamp' => 1 ) ) );
        $subscription = new stdClass();

        ASWC_Scheduler_API::get_last_scheduled_lifecycle_actions( $subscription, false );

        $this->assertSame( false, $lifecycle->last_group );
    }
}
