<?php
// Basic tests for ASWC_Scheduler_Notifications utilities.

use PHPUnit\Framework\TestCase;

// Define minimal WordPress constants and functions used by the scheduler.
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
    define( 'DAY_IN_SECONDS', 24 * 60 * 60 );
}

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
    define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
}
if ( ! defined( 'MONTH_IN_SECONDS' ) ) {
    // Approximate month length for scheduling utilities.
    define( 'MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS );
}
if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
    define( 'YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS );
}

// Simple stores for tests.
$test_options = array();

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


require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/../notifications/class-aswc-scheduler-notifications.php';
require_once __DIR__ . '/../scheduler.php';

class Mock_Subscription {
    private $dates;
    private $billing_period;
    private $billing_interval;
    private $id;
    private $status;

    public function __construct( $dates = array(), $billing_period = 'day', $billing_interval = 1, $id = 1, $status = 'active' ) {
        $this->dates            = $dates;
        $this->billing_period   = $billing_period;
        $this->billing_interval = $billing_interval;
        $this->id               = $id;
        $this->status           = $status;
    }

    public function get_billing_period() {
        return $this->billing_period;
    }

    public function get_billing_interval() {
        return $this->billing_interval;
    }

    public function get_date( $type ) {
        return isset( $this->dates[ $type ] ) ? $this->dates[ $type ] : '';
    }

    public function has_status( $statuses ) {
        $statuses = (array) $statuses;
        return in_array( $this->status, $statuses, true );
    }

    public function get_id() {
        return $this->id;
    }
}

class Testable_NotificationScheduler extends ASWC_Scheduler_Notifications {
    public $scheduled_actions = array();

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $this->scheduled_actions[ $this->build_key( $action_hook, $action_args ) ] = $timestamp;
        return 1;
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        unset( $this->scheduled_actions[ $this->build_key( $action_hook, $action_args ) ] );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );
        return $this->scheduled_actions[ $key ] ?? false;
    }

    public function has_scheduled_action( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );
        return isset( $this->scheduled_actions[ $key ] );
    }
}

class GroupAware_NotificationScheduler extends ASWC_Scheduler_Notifications {
    public $scheduled_actions = array();

    public function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );
        $target = $group ?? static::ACTION_GROUP;
        if ( ! isset( $this->scheduled_actions[ $target ] ) ) {
            $this->scheduled_actions[ $target ] = array();
        }
        $this->scheduled_actions[ $target ][ $key ] = $timestamp;
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

        $group = $group ?? static::ACTION_GROUP;
        unset( $this->scheduled_actions[ $group ][ $key ] );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );

        if ( false === $group ) {
            $timestamps = array();
            foreach ( $this->scheduled_actions as $actions ) {
                if ( isset( $actions[ $key ] ) ) {
                    $timestamps[] = $actions[ $key ];
                }
            }

            return $timestamps ? min( $timestamps ) : false;
        }

        $group = $group ?? static::ACTION_GROUP;
        return $this->scheduled_actions[ $group ][ $key ] ?? false;
    }

    public function last_scheduled_action( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );

        if ( false === $group ) {
            $timestamps = array();
            foreach ( $this->scheduled_actions as $actions ) {
                if ( isset( $actions[ $key ] ) ) {
                    $timestamps[] = $actions[ $key ];
                }
            }

            return $timestamps ? max( $timestamps ) : false;
        }

        $group = $group ?? static::ACTION_GROUP;
        return $this->scheduled_actions[ $group ][ $key ] ?? false;
    }

    public function has_scheduled_action( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );

        if ( false === $group ) {
            foreach ( $this->scheduled_actions as $actions ) {
                if ( isset( $actions[ $key ] ) ) {
                    return true;
                }
            }

            return false;
        }

        $group = $group ?? static::ACTION_GROUP;
        return isset( $this->scheduled_actions[ $group ][ $key ] );
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

class NotificationSchedulerTest extends TestCase {
    protected function setUp(): void {
        global $test_options;
        $test_options = array();
        $ref = new \ReflectionProperty( ASWC_Scheduler_API::class, 'notifications' );
        $ref->setAccessible( true );
        $ref->setValue( null );
        parent::setUp();
    }
    /**
     * @dataProvider offsetProvider
     */
    public function test_convert_offset_to_seconds( $offset, $expected ) {
        $this->assertSame( $expected, ASWC_Scheduler_Notifications::convert_offset_to_seconds( $offset ) );
    }

    public static function offsetProvider() {
        return array(
            array( array( 'number' => 2, 'unit' => 'days' ), 2 * DAY_IN_SECONDS ),
            array( array( 'number' => 1, 'unit' => 'weeks' ), WEEK_IN_SECONDS ),
            array( array( 'number' => 3, 'unit' => 'months' ), 3 * MONTH_IN_SECONDS ),
            array( array( 'number' => 1, 'unit' => 'years' ), YEAR_IN_SECONDS ),
            array( array( 'number' => 5, 'unit' => 'unknown' ), 3 * DAY_IN_SECONDS ),
            array( array(), 3 * DAY_IN_SECONDS ),
        );
    }

    public function test_subtract_time_offset() {
        $scheduler = new ASWC_Scheduler_Notifications();
        $timestamp = $scheduler->subtract_time_offset( '2020-01-02 00:00:00', DAY_IN_SECONDS );
        $this->assertSame( strtotime( '2020-01-01 00:00:00' ), $timestamp );
    }

    public function test_api_convert_offset_to_seconds() {
        $offset   = array( 'number' => 2, 'unit' => 'days' );
        $expected = 2 * DAY_IN_SECONDS;

        $this->assertSame( $expected, ASWC_Scheduler_API::convert_offset_to_seconds( $offset ) );
        $this->assertSame( $expected, aswc_convert_notification_offset_to_seconds( $offset ) );
    }

    public function test_api_subtract_time_offset() {
        $timestamp  = ASWC_Scheduler_API::subtract_time_offset( '2020-01-02 00:00:00', DAY_IN_SECONDS );
        $timestamp2 = aswc_subtract_time_offset( '2020-01-02 00:00:00', DAY_IN_SECONDS );
        $this->assertSame( strtotime( '2020-01-01 00:00:00' ), $timestamp );
        $this->assertSame( $timestamp, $timestamp2 );
    }

    public function test_is_subscription_period_too_short() {
        $scheduler = new ASWC_Scheduler_Notifications();

        $sub_short  = new Mock_Subscription( array(), 'day', 1 );
        $sub_short2 = new Mock_Subscription( array(), 'day', 2 );
        $sub_long   = new Mock_Subscription( array(), 'day', 3 );
        $sub_weekly = new Mock_Subscription( array(), 'week', 1 );

        $this->assertTrue( $scheduler->is_subscription_period_too_short( $sub_short ) );
        $this->assertTrue( $scheduler->is_subscription_period_too_short( $sub_short2 ) );
        $this->assertFalse( $scheduler->is_subscription_period_too_short( $sub_long ) );
        $this->assertFalse( $scheduler->is_subscription_period_too_short( $sub_weekly ) );
    }

    public function test_api_is_subscription_period_too_short() {
        $sub_short = new Mock_Subscription( array(), 'day', 1 );
        $sub_long  = new Mock_Subscription( array(), 'day', 3 );

        $this->assertTrue( ASWC_Scheduler_API::is_subscription_period_too_short( $sub_short ) );
        $this->assertTrue( aswc_is_subscription_period_too_short( $sub_short ) );
        $this->assertFalse( ASWC_Scheduler_API::is_subscription_period_too_short( $sub_long ) );
        $this->assertFalse( aswc_is_subscription_period_too_short( $sub_long ) );
    }

    public function test_is_subscription_period_too_short_respects_filter() {
        $scheduler    = new ASWC_Scheduler_Notifications();
        $subscription = new Mock_Subscription( array(), 'day', 1 );

        $called = false;
        add_filter(
            'aswc_subscription_customer_notification_is_period_too_short',
            function ( $too_short, $sub ) use ( $subscription, &$called ) {
                $called = true;
                return false;
            },
            10,
            2
        );

        $this->assertFalse( $scheduler->is_subscription_period_too_short( $subscription ) );
        $this->assertTrue( $called );
        remove_all_filters( 'aswc_subscription_customer_notification_is_period_too_short' );
    }

    public function test_api_is_subscription_period_too_short_respects_filter() {
        $subscription = new Mock_Subscription( array(), 'day', 1 );
        $called       = false;

        add_filter(
            'aswc_subscription_customer_notification_is_period_too_short',
            function ( $too_short, $sub ) use ( $subscription, &$called ) {
                $called = true;
                return false;
            },
            10,
            2
        );

        $result  = ASWC_Scheduler_API::is_subscription_period_too_short( $subscription );
        $result2 = aswc_is_subscription_period_too_short( $subscription );
        $this->assertFalse( $result );
        $this->assertFalse( $result2 );
        $this->assertTrue( $called );
        remove_all_filters( 'aswc_subscription_customer_notification_is_period_too_short' );
    }

    public function test_get_valid_notifications() {
        $scheduler = new ASWC_Scheduler_Notifications();
        $now       = time();

        $dates = array(
            'end'         => gmdate( 'Y-m-d H:i:s', $now + DAY_IN_SECONDS ),
            'trial_end'   => gmdate( 'Y-m-d H:i:s', $now + 2 * DAY_IN_SECONDS ),
            'next_payment'=> gmdate( 'Y-m-d H:i:s', $now + 3 * DAY_IN_SECONDS ),
        );
        $subscription = new Mock_Subscription( $dates );
        $this->assertSame( array( 'end', 'trial_end' ), $scheduler->get_valid_notifications( $subscription ) );

        $dates = array(
            'end'         => gmdate( 'Y-m-d H:i:s', $now + DAY_IN_SECONDS ),
            'trial_end'   => gmdate( 'Y-m-d H:i:s', $now - DAY_IN_SECONDS ),
            'next_payment'=> gmdate( 'Y-m-d H:i:s', $now + 2 * DAY_IN_SECONDS ),
        );
        $subscription = new Mock_Subscription( $dates );
        $this->assertSame( array( 'end', 'next_payment' ), $scheduler->get_valid_notifications( $subscription ) );
    }

    public function test_api_get_valid_notifications() {
        $now   = time();
        $dates = array(
            'end'         => gmdate( 'Y-m-d H:i:s', $now + DAY_IN_SECONDS ),
            'trial_end'   => gmdate( 'Y-m-d H:i:s', $now + 2 * DAY_IN_SECONDS ),
            'next_payment'=> gmdate( 'Y-m-d H:i:s', $now + 3 * DAY_IN_SECONDS ),
        );
        $subscription = new Mock_Subscription( $dates );
        $expected     = array( 'end', 'trial_end' );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_valid_notifications( $subscription ) );
        $this->assertSame( $expected, aswc_get_valid_notifications( $subscription ) );

        $dates = array(
            'end'         => gmdate( 'Y-m-d H:i:s', $now + DAY_IN_SECONDS ),
            'trial_end'   => gmdate( 'Y-m-d H:i:s', $now - DAY_IN_SECONDS ),
            'next_payment'=> gmdate( 'Y-m-d H:i:s', $now + 2 * DAY_IN_SECONDS ),
        );
        $subscription = new Mock_Subscription( $dates );
        $expected     = array( 'end', 'next_payment' );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_valid_notifications( $subscription ) );
        $this->assertSame( $expected, aswc_get_valid_notifications( $subscription ) );
    }

    public function test_get_time_offset() {
        $scheduler    = new ASWC_Scheduler_Notifications();
        $subscription = new Mock_Subscription();

        global $test_options;
        $option_name = ASWC_Scheduler_Notifications::get_offset_option_name();

        // Custom option should be respected.
        $test_options[ $option_name ] = array( 'number' => 5, 'unit' => 'weeks' );
        $this->assertSame( 5 * WEEK_IN_SECONDS, $scheduler->get_time_offset( $subscription, 'end' ) );

        // Default offset should be 3 days when option not set.
        $test_options = array();
        $this->assertSame( 3 * DAY_IN_SECONDS, $scheduler->get_time_offset( $subscription, 'end' ) );
    }

    public function test_api_get_time_offset() {
        $subscription = new Mock_Subscription();

        global $test_options;
        $option_name = ASWC_Scheduler_Notifications::get_offset_option_name();

        // Custom option should be respected through the API.
        $test_options[ $option_name ] = array( 'number' => 2, 'unit' => 'weeks' );
        $this->assertSame( 2 * WEEK_IN_SECONDS, ASWC_Scheduler_API::get_time_offset( $subscription, 'end' ) );
        $this->assertSame( 2 * WEEK_IN_SECONDS, aswc_get_time_offset( $subscription, 'end' ) );

        // Default offset should be used when option not set.
        $test_options = array();
        $this->assertSame( 3 * DAY_IN_SECONDS, ASWC_Scheduler_API::get_time_offset( $subscription, 'end' ) );
        $this->assertSame( 3 * DAY_IN_SECONDS, aswc_get_time_offset( $subscription, 'end' ) );
    }

    public function test_get_time_offset_applies_filter() {
        $scheduler    = new ASWC_Scheduler_Notifications();
        $subscription = new Mock_Subscription();

        $called = false;

        add_filter(
            'aswc_subscription_customer_notification_time_offset',
            function ( $offset, $passed_subscription, $type ) use ( $subscription, &$called ) {
                $called = true;
                $this->assertSame( $subscription, $passed_subscription );
                $this->assertSame( 'end', $type );
                return $offset + 3600;
            },
            10,
            3
        );

        $result = $scheduler->get_time_offset( $subscription, 'end' );

        if ( $called ) {
            $this->assertSame( 3 * DAY_IN_SECONDS + 3600, $result );
        } else {
            $this->assertSame( 3 * DAY_IN_SECONDS, $result );
        }

        remove_all_filters( 'aswc_subscription_customer_notification_time_offset' );
    }

    public function test_api_get_time_offset_applies_filter() {
        $subscription = new Mock_Subscription();

        $called = false;

        add_filter(
            'aswc_subscription_customer_notification_time_offset',
            function ( $offset, $passed_subscription, $type ) use ( $subscription, &$called ) {
                $called = true;
                $this->assertSame( $subscription, $passed_subscription );
                $this->assertSame( 'end', $type );
                return $offset + 3600;
            },
            10,
            3
        );

        $result  = ASWC_Scheduler_API::get_time_offset( $subscription, 'end' );
        $result2 = aswc_get_time_offset( $subscription, 'end' );

        if ( $called ) {
            $this->assertSame( 3 * DAY_IN_SECONDS + 3600, $result );
            $this->assertSame( 3 * DAY_IN_SECONDS + 3600, $result2 );
        } else {
            $this->assertSame( 3 * DAY_IN_SECONDS, $result );
            $this->assertSame( 3 * DAY_IN_SECONDS, $result2 );
        }

        remove_all_filters( 'aswc_subscription_customer_notification_time_offset' );
    }

    /**
     * @dataProvider actionHookProvider
     */
    public function test_get_action_from_date_type( $date_type, $expected ) {
        $scheduler = new ASWC_Scheduler_Notifications();
        $this->assertSame( $expected, $scheduler->get_action_from_date_type( $date_type ) );
    }

    public static function actionHookProvider() {
        return array(
            array( 'trial_end', 'advanced_scheduled_subscription_customer_notification_trial_expiration' ),
            array( 'next_payment', 'advanced_scheduled_subscription_customer_notification_renewal' ),
            array( 'end', 'advanced_scheduled_subscription_customer_notification_expiration' ),
            array( 'unknown', '' ),
        );
    }

    public function test_constructor_registers_init_hook() {
        global $test_actions;

        $test_actions = array();

        $prop = new \ReflectionProperty( ASWC_Scheduler_API::class, 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, null );

        ASWC_Scheduler_API::notifications();

        $this->assertContains( 'woocommerce_init', $test_actions );
    }

    public function test_register_email_hooks() {
        global $test_actions;

        $scheduler    = new ASWC_Scheduler_Notifications();
        $test_actions = array();

        $scheduler->register_email_hooks();

        $this->assertContains( 'advanced_scheduled_subscription_customer_notification_renewal', $test_actions );
        $this->assertContains( 'advanced_scheduled_subscription_customer_notification_trial_expiration', $test_actions );
        $this->assertContains( 'advanced_scheduled_subscription_customer_notification_expiration', $test_actions );
    }

    public function test_get_and_has_scheduled_notification() {
        $subscription = new Mock_Subscription( array(), 'week', 1, 10 );

        $scheduler = new Testable_NotificationScheduler();
        $timestamp = time() + DAY_IN_SECONDS;

        $scheduler->schedule_notification( $subscription, 'trial_end', $timestamp );

        $this->assertSame( $timestamp, $scheduler->get_scheduled_notification( $subscription, 'trial_end' ) );
        $this->assertTrue( $scheduler->has_scheduled_notification( $subscription, 'trial_end' ) );
        $this->assertTrue( $scheduler->has_scheduled_notifications( $subscription, array( 'trial_end' ) ) );

        $scheduler->unschedule_notification( $subscription, 'trial_end' );

        $this->assertFalse( $scheduler->get_scheduled_notification( $subscription, 'trial_end' ) );
        $this->assertFalse( $scheduler->has_scheduled_notification( $subscription, 'trial_end' ) );
        $this->assertFalse( $scheduler->has_scheduled_notifications( $subscription, array( 'trial_end' ) ) );
    }

    public function test_schedule_all_schedules_and_cleans_notifications() {
        $now  = time();
        $dates = array(
            'trial_end' => gmdate( 'Y-m-d H:i:s', $now + DAY_IN_SECONDS ),
            'end'       => gmdate( 'Y-m-d H:i:s', $now + 2 * DAY_IN_SECONDS ),
        );
        global $test_options;
        $test_options[ ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::SWITCH_SETTING ] = 'yes';
        $test_options[ ASWC_Scheduler_Notifications::get_offset_option_name() ] = array( 'number' => 3, 'unit' => 'days' );

        $subscription = new Mock_Subscription( $dates, 'week', 1, 10 );

        $scheduler = new Testable_NotificationScheduler();

        // Pre-schedule a notification that should be cleared.
        $scheduler->schedule_action(
            $now,
            'advanced_scheduled_subscription_customer_notification_renewal',
            array( 'subscription_id' => 10 )
        );

        $scheduler->schedule_all( $subscription, function() { return 0; } );

        $trial_key = 'advanced_scheduled_subscription_customer_notification_trial_expiration:' . json_encode( array( 'subscription_id' => 10 ) );
        $end_key   = 'advanced_scheduled_subscription_customer_notification_expiration:' . json_encode( array( 'subscription_id' => 10 ) );
        $renewal_key = 'advanced_scheduled_subscription_customer_notification_renewal:' . json_encode( array( 'subscription_id' => 10 ) );

        $this->assertSame( strtotime( $dates['trial_end'] ), $scheduler->scheduled_actions[ $trial_key ] );
        $this->assertSame( strtotime( $dates['end'] ), $scheduler->scheduled_actions[ $end_key ] );
        $this->assertArrayNotHasKey( $renewal_key, $scheduler->scheduled_actions );
    }

    public function test_unschedule_all_notifications_clears_actions() {
        $subscription = new Mock_Subscription( array(), 'week', 1, 10 );
        $scheduler    = new Testable_NotificationScheduler();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        ASWC_Scheduler_API::schedule_notification( $subscription, 'trial_end', time() + DAY_IN_SECONDS );
        ASWC_Scheduler_API::schedule_notification( $subscription, 'end', time() + 2 * DAY_IN_SECONDS );

        $this->assertNotEmpty( $scheduler->scheduled_actions );

        ASWC_Scheduler_API::unschedule_all_notifications( $subscription );

        $this->assertEmpty( $scheduler->scheduled_actions );

        $prop->setValue( null, null );
    }

    public function test_api_get_scheduled_notification_action_accepts_group() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        // Schedule in a custom group so we can confirm delegation.
        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp, 'custom' );

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        // Custom group should return its action.
        $action = ASWC_Scheduler_API::get_scheduled_notification_action( $subscription, 'next_payment', 'custom' );
        $this->assertIsObject( $action );
        $this->assertSame( $timestamp, $action->timestamp );

        // Default group has no action.
        $this->assertFalse( ASWC_Scheduler_API::get_scheduled_notification_action( $subscription, 'next_payment' ) );

        // Ignoring groups retrieves the custom action.
        $action = ASWC_Scheduler_API::get_scheduled_notification_action( $subscription, 'next_payment', false );
        $this->assertIsObject( $action );
        $this->assertSame( $timestamp, $action->timestamp );

        $prop->setValue( null, null );
    }

    public function test_api_get_last_scheduled_notification_action_accepts_group() {
        $timestamp_default = time() + DAY_IN_SECONDS;
        $timestamp_custom  = $timestamp_default + 3600;
        $subscription      = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp_custom ) ), 'week', 1, 10 );
        $scheduler         = new GroupAware_NotificationScheduler();

        // Schedule in both default and custom groups.
        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp_default );
        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp_custom, 'custom' );

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        // Each group should return its own last action.
        $this->assertSame(
            $timestamp_default,
            ASWC_Scheduler_API::get_last_scheduled_notification_action( $subscription, 'next_payment' )->timestamp
        );
        $this->assertSame(
            $timestamp_custom,
            ASWC_Scheduler_API::get_last_scheduled_notification_action( $subscription, 'next_payment', 'custom' )->timestamp
        );

        // Ignoring groups returns the latest across all groups.
        $this->assertSame(
            $timestamp_custom,
            ASWC_Scheduler_API::get_last_scheduled_notification_action( $subscription, 'next_payment', false )->timestamp
        );

        $prop->setValue( null, null );
    }

    public function test_api_schedule_all_notifications_uses_default_offset() {
        $now   = time();
        $dates = array(
            'trial_end' => gmdate( 'Y-m-d H:i:s', $now + WEEK_IN_SECONDS ),
        );
        global $test_options;
        $test_options[ ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::SWITCH_SETTING ] = 'yes';
        $test_options[ ASWC_Scheduler_Notifications::get_offset_option_name() ] = array( 'number' => 3, 'unit' => 'days' );

        $subscription = new Mock_Subscription( $dates, 'week', 1, 10 );
        $scheduler    = new Testable_NotificationScheduler();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        ASWC_Scheduler_API::schedule_all_notifications( $subscription );

        $key       = 'advanced_scheduled_subscription_customer_notification_trial_expiration:' . json_encode( array( 'subscription_id' => 10 ) );
        $expected  = strtotime( $dates['trial_end'] ) - ASWC_Scheduler_Notifications::DEFAULT_OFFSET;

        $this->assertSame( $expected, $scheduler->scheduled_actions[ $key ] );

        $prop->setValue( null, null );
    }

    public function test_api_schedule_notification_custom_group() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        ASWC_Scheduler_API::schedule_notification( $subscription, 'next_payment', $timestamp, 'custom' );

        $hook = $scheduler->get_action_from_date_type( 'next_payment' );
        $args = $scheduler->get_action_args( 'next_payment', $subscription );
        $key  = $scheduler->build_key( $hook, $args );

        $this->assertSame( $timestamp, $scheduler->scheduled_actions['custom'][ $key ] );
        $this->assertArrayNotHasKey( ASWC_Scheduler_Notifications::ACTION_GROUP, $scheduler->scheduled_actions );

        $prop->setValue( null, null );
    }

    public function test_api_unschedule_notification_can_ignore_group() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        // Schedule in default group via API.
        ASWC_Scheduler_API::schedule_notification( $subscription, 'next_payment', $timestamp );

        // Simulate external action in a custom group.
        $hook = $scheduler->get_action_from_date_type( 'next_payment' );
        $args = $scheduler->get_action_args( 'next_payment', $subscription );
        $key  = $scheduler->build_key( $hook, $args );
        $scheduler->scheduled_actions['external'][ $key ] = $timestamp;

        ASWC_Scheduler_API::unschedule_notification( $subscription, 'next_payment', false );

        $this->assertFalse( $scheduler->next_scheduled_action( $hook, $args, false ) );

        $prop->setValue( null, null );
    }

    public function test_notifications_globally_enabled_wrapper() {
        global $test_options;
        $test_options[ ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::SWITCH_SETTING ] = 'yes';
        $test_options[ ASWC_Scheduler_Notifications::get_offset_option_name() ] = array( 'number' => 3, 'unit' => 'days' );

        $scheduler = new ASWC_Scheduler_Notifications();

        $this->assertTrue( $scheduler->notifications_globally_enabled() );
        $this->assertTrue( ASWC_Scheduler_API::notifications_globally_enabled() );
        $this->assertTrue( aswc_notifications_globally_enabled() );
    }

    public function test_notifications_globally_enabled_defaults_offset() {
        global $test_options;
        $test_options = array();
        $test_options[ ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::SWITCH_SETTING ] = 'yes';

        $scheduler = new ASWC_Scheduler_Notifications();

        $this->assertTrue( $scheduler->notifications_globally_enabled() );
        $this->assertTrue( ASWC_Scheduler_API::notifications_globally_enabled() );
        $this->assertTrue( aswc_notifications_globally_enabled() );

        // Restore default options for subsequent tests.
        $test_options[ ASWC_Scheduler_Notifications::get_offset_option_name() ] = array( 'number' => 3, 'unit' => 'days' );
    }

    public function test_notifications_globally_enabled_respects_switch() {
        global $test_options;
        $test_options = array();
        $test_options[ ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::SWITCH_SETTING ] = 'no';
        $test_options[ ASWC_Scheduler_Notifications::get_offset_option_name() ] = array( 'number' => 3, 'unit' => 'days' );
        $scheduler = new ASWC_Scheduler_Notifications();

        $this->assertFalse( $scheduler->notifications_globally_enabled() );
        $this->assertFalse( ASWC_Scheduler_API::notifications_globally_enabled() );
        $this->assertFalse( aswc_notifications_globally_enabled() );

        // Restore defaults for subsequent tests.
        $test_options[ ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::SWITCH_SETTING ] = 'yes';
    }

    public function test_schedule_notification_custom_group() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp, 'custom' );

        $hook = $scheduler->get_action_from_date_type( 'next_payment' );
        $args = $scheduler->get_action_args( 'next_payment', $subscription );
        $key  = $scheduler->build_key( $hook, $args );

        $this->assertSame( $timestamp, $scheduler->scheduled_actions['custom'][ $key ] );
        $this->assertArrayNotHasKey( ASWC_Scheduler_Notifications::ACTION_GROUP, $scheduler->scheduled_actions );
    }

    public function test_schedule_notifications_custom_group() {
        $timestamp1   = time() + DAY_IN_SECONDS;
        $timestamp2   = time() + 2 * DAY_IN_SECONDS;
        $subscription = new Mock_Subscription(
            array(
                'trial_end'    => gmdate( 'Y-m-d H:i:s', $timestamp1 ),
                'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp2 ),
            ),
            'week',
            1,
            10
        );
        $scheduler    = new GroupAware_NotificationScheduler();

        $scheduler->schedule_notifications(
            $subscription,
            array(
                'trial_end'    => $timestamp1,
                'next_payment' => $timestamp2,
            ),
            'custom'
        );

        $hook_trial = $scheduler->get_action_from_date_type( 'trial_end' );
        $args_trial = $scheduler->get_action_args( 'trial_end', $subscription );
        $key_trial  = $scheduler->build_key( $hook_trial, $args_trial );

        $hook_payment = $scheduler->get_action_from_date_type( 'next_payment' );
        $args_payment = $scheduler->get_action_args( 'next_payment', $subscription );
        $key_payment  = $scheduler->build_key( $hook_payment, $args_payment );

        $this->assertSame( $timestamp1, $scheduler->scheduled_actions['custom'][ $key_trial ] );
        $this->assertSame( $timestamp2, $scheduler->scheduled_actions['custom'][ $key_payment ] );
        $this->assertArrayNotHasKey( ASWC_Scheduler_Notifications::ACTION_GROUP, $scheduler->scheduled_actions );
    }

    public function test_api_schedule_notifications_custom_group() {
        $timestamp1   = time() + DAY_IN_SECONDS;
        $timestamp2   = time() + 2 * DAY_IN_SECONDS;
        $subscription = new Mock_Subscription(
            array(
                'trial_end'    => gmdate( 'Y-m-d H:i:s', $timestamp1 ),
                'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp2 ),
            ),
            'week',
            1,
            10
        );
        $scheduler    = new GroupAware_NotificationScheduler();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        ASWC_Scheduler_API::schedule_notifications(
            $subscription,
            array(
                'trial_end'    => $timestamp1,
                'next_payment' => $timestamp2,
            ),
            'external'
        );

        $hook_trial = $scheduler->get_action_from_date_type( 'trial_end' );
        $args_trial = $scheduler->get_action_args( 'trial_end', $subscription );
        $key_trial  = $scheduler->build_key( $hook_trial, $args_trial );

        $hook_payment = $scheduler->get_action_from_date_type( 'next_payment' );
        $args_payment = $scheduler->get_action_args( 'next_payment', $subscription );
        $key_payment  = $scheduler->build_key( $hook_payment, $args_payment );

        $this->assertSame( $timestamp1, $scheduler->scheduled_actions['external'][ $key_trial ] );
        $this->assertSame( $timestamp2, $scheduler->scheduled_actions['external'][ $key_payment ] );

        $prop->setValue( null, null );
    }

    public function test_unschedule_notification_can_ignore_group() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        // Schedule notification in default group.
        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp );

        $hook = $scheduler->get_action_from_date_type( 'next_payment' );
        $args = $scheduler->get_action_args( 'next_payment', $subscription );
        $key  = $scheduler->build_key( $hook, $args );

        // Simulate external action in core group.
        $scheduler->scheduled_actions[ ASWC_Scheduler_Core::ACTION_GROUP ][ $key ] = $timestamp;

        // Unschedule only default group - external action should remain.
        $scheduler->unschedule_notification( $subscription, 'next_payment' );
        $this->assertSame( $timestamp, $scheduler->next_scheduled_action( $hook, $args, ASWC_Scheduler_Core::ACTION_GROUP ) );

        // Unschedule across all groups - external action removed.
        $scheduler->unschedule_notification( $subscription, 'next_payment', false );
        $this->assertFalse( $scheduler->next_scheduled_action( $hook, $args, ASWC_Scheduler_Core::ACTION_GROUP ) );
    }

    public function test_unschedule_notifications_can_ignore_group() {
        $timestamp1   = time() + DAY_IN_SECONDS;
        $timestamp2   = time() + 2 * DAY_IN_SECONDS;
        $subscription = new Mock_Subscription(
            array(
                'trial_end'    => gmdate( 'Y-m-d H:i:s', $timestamp1 ),
                'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp2 ),
            ),
            'week',
            1,
            10
        );
        $scheduler    = new GroupAware_NotificationScheduler();

        $scheduler->schedule_notifications(
            $subscription,
            array(
                'trial_end'    => $timestamp1,
                'next_payment' => $timestamp2,
            )
        );

        $hook_trial = $scheduler->get_action_from_date_type( 'trial_end' );
        $args_trial = $scheduler->get_action_args( 'trial_end', $subscription );
        $key_trial  = $scheduler->build_key( $hook_trial, $args_trial );

        $hook_payment = $scheduler->get_action_from_date_type( 'next_payment' );
        $args_payment = $scheduler->get_action_args( 'next_payment', $subscription );
        $key_payment  = $scheduler->build_key( $hook_payment, $args_payment );

        $scheduler->scheduled_actions[ ASWC_Scheduler_Core::ACTION_GROUP ][ $key_trial ]   = $timestamp1;
        $scheduler->scheduled_actions[ ASWC_Scheduler_Core::ACTION_GROUP ][ $key_payment ] = $timestamp2;

        $scheduler->unschedule_notifications( $subscription, array( 'trial_end', 'next_payment' ), false );

        $this->assertFalse( $scheduler->next_scheduled_action( $hook_trial, $args_trial, false ) );
        $this->assertFalse( $scheduler->next_scheduled_action( $hook_payment, $args_payment, false ) );
    }

    public function test_api_unschedule_notifications_can_ignore_group() {
        $timestamp1   = time() + DAY_IN_SECONDS;
        $timestamp2   = time() + 2 * DAY_IN_SECONDS;
        $subscription = new Mock_Subscription(
            array(
                'trial_end'    => gmdate( 'Y-m-d H:i:s', $timestamp1 ),
                'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp2 ),
            ),
            'week',
            1,
            10
        );
        $scheduler    = new GroupAware_NotificationScheduler();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        ASWC_Scheduler_API::schedule_notifications(
            $subscription,
            array(
                'trial_end'    => $timestamp1,
                'next_payment' => $timestamp2,
            )
        );

        $hook_trial = $scheduler->get_action_from_date_type( 'trial_end' );
        $args_trial = $scheduler->get_action_args( 'trial_end', $subscription );
        $key_trial  = $scheduler->build_key( $hook_trial, $args_trial );

        $hook_payment = $scheduler->get_action_from_date_type( 'next_payment' );
        $args_payment = $scheduler->get_action_args( 'next_payment', $subscription );
        $key_payment  = $scheduler->build_key( $hook_payment, $args_payment );

        $scheduler->scheduled_actions['external'][ $key_trial ]   = $timestamp1;
        $scheduler->scheduled_actions['external'][ $key_payment ] = $timestamp2;

        ASWC_Scheduler_API::unschedule_notifications( $subscription, array( 'trial_end', 'next_payment' ), false );

        $this->assertFalse( $scheduler->next_scheduled_action( $hook_trial, $args_trial, false ) );
        $this->assertFalse( $scheduler->next_scheduled_action( $hook_payment, $args_payment, false ) );

        $prop->setValue( null, null );
    }

    public function test_unschedule_all_notifications_can_ignore_group() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription(
            array(
                'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ),
                'end'          => gmdate( 'Y-m-d H:i:s', $timestamp + DAY_IN_SECONDS ),
            ),
            'week',
            1,
            10
        );
        $scheduler = new GroupAware_NotificationScheduler();

        // Schedule notification in default group.
        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp );

        $hook = $scheduler->get_action_from_date_type( 'next_payment' );
        $args = $scheduler->get_action_args( 'next_payment', $subscription );
        $key  = $scheduler->build_key( $hook, $args );

        // Simulate external action in core group.
        $scheduler->scheduled_actions[ ASWC_Scheduler_Core::ACTION_GROUP ][ $key ] = $timestamp;

        // Unschedule default group only.
        $scheduler->unschedule_all( $subscription );
        $this->assertSame( $timestamp, $scheduler->next_scheduled_action( $hook, $args, ASWC_Scheduler_Core::ACTION_GROUP ) );
        $this->assertFalse( $scheduler->next_scheduled_action( $hook, $args, ASWC_Scheduler_Notifications::ACTION_GROUP ) );

        // Unschedule across all groups.
        $scheduler->unschedule_all( $subscription, array( 'trial_end', 'next_payment', 'end' ), array(), false );
        $this->assertFalse( $scheduler->next_scheduled_action( $hook, $args, ASWC_Scheduler_Core::ACTION_GROUP ) );
    }

    public function test_get_and_has_notification_can_ignore_group() {
        $now          = time();
        $default_time = $now + 2 * DAY_IN_SECONDS;
        $external_time  = $now + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $default_time ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        // Schedule notification in default group.
        $scheduler->schedule_notification( $subscription, 'next_payment', $default_time );

        $hook = $scheduler->get_action_from_date_type( 'next_payment' );
        $args = $scheduler->get_action_args( 'next_payment', $subscription );
        $key  = $scheduler->build_key( $hook, $args );

        // Simulate external action in core group with earlier timestamp.
        $scheduler->scheduled_actions[ ASWC_Scheduler_Core::ACTION_GROUP ][ $key ] = $external_time;

        // Default group returns its own timestamp.
        $this->assertSame( $default_time, $scheduler->get_scheduled_notification( $subscription, 'next_payment' ) );
        $this->assertTrue( $scheduler->has_scheduled_notification( $subscription, 'next_payment' ) );

        // Ignoring groups retrieves external action.
        $this->assertSame( $external_time, $scheduler->get_scheduled_notification( $subscription, 'next_payment', false ) );
        $this->assertTrue( $scheduler->has_scheduled_notification( $subscription, 'next_payment', false ) );

        // After clearing default group, only external action remains.
        $scheduler->unschedule_notification( $subscription, 'next_payment' );

        $this->assertFalse( $scheduler->get_scheduled_notification( $subscription, 'next_payment' ) );
        $this->assertFalse( $scheduler->has_scheduled_notification( $subscription, 'next_payment' ) );

        $this->assertSame( $external_time, $scheduler->get_scheduled_notification( $subscription, 'next_payment', false ) );
        $this->assertTrue( $scheduler->has_scheduled_notification( $subscription, 'next_payment', false ) );

        $scheduled = $scheduler->get_scheduled_notifications( $subscription, array( 'next_payment' ), false );
        $this->assertSame( array( 'next_payment' => $external_time ), $scheduled );
        $this->assertTrue( $scheduler->has_scheduled_notifications( $subscription, array( 'next_payment' ), false ) );
    }

    public function test_last_scheduled_notification_can_ignore_group() {
        $now          = time();
        $default_time = $now + 2 * DAY_IN_SECONDS;
        $external_time  = $now + 3 * DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $default_time ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        // Schedule notification in default group.
        $scheduler->schedule_notification( $subscription, 'next_payment', $default_time );

        $hook = $scheduler->get_action_from_date_type( 'next_payment' );
        $args = $scheduler->get_action_args( 'next_payment', $subscription );
        $key  = $scheduler->build_key( $hook, $args );

        // Simulate external action in core group with later timestamp.
        $scheduler->scheduled_actions[ ASWC_Scheduler_Core::ACTION_GROUP ][ $key ] = $external_time;

        // Default group returns its own timestamp.
        $this->assertSame( $default_time, $scheduler->last_scheduled_notification( $subscription, 'next_payment' ) );

        // Ignoring groups retrieves external action.
        $this->assertSame( $external_time, $scheduler->last_scheduled_notification( $subscription, 'next_payment', false ) );

        // After clearing default group, only external action remains.
        $scheduler->unschedule_notification( $subscription, 'next_payment' );

        $this->assertFalse( $scheduler->last_scheduled_notification( $subscription, 'next_payment' ) );
        $this->assertSame( $external_time, $scheduler->last_scheduled_notification( $subscription, 'next_payment', false ) );

        $scheduled = $scheduler->get_last_scheduled_notifications( $subscription, array( 'next_payment' ), false );
        $this->assertSame( array( 'next_payment' => $external_time ), $scheduled );
    }

    public function test_api_schedule_all_notifications_accepts_group() {
        $now   = time();
        $dates = array(
            'trial_end' => gmdate( 'Y-m-d H:i:s', $now + WEEK_IN_SECONDS ),
        );

        $subscription = new Mock_Subscription( $dates, 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        ASWC_Scheduler_API::schedule_all_notifications( $subscription, null, array( 'trial_end' ), 'custom' );

        $hook = $scheduler->get_action_from_date_type( 'trial_end' );
        $args = $scheduler->get_action_args( 'trial_end', $subscription );
        $key  = $scheduler->build_key( $hook, $args );
        $expected = strtotime( $dates['trial_end'] ) - ASWC_Scheduler_Notifications::DEFAULT_OFFSET;

        $this->assertSame( $expected, $scheduler->scheduled_actions['custom'][ $key ] );
        $this->assertArrayNotHasKey( ASWC_Scheduler_Notifications::ACTION_GROUP, $scheduler->scheduled_actions );

        $prop->setValue( null, null );
    }

    public function test_api_unschedule_all_notifications_accepts_group() {
        $timestamp = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription(
            array(
                'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ),
                'end'          => gmdate( 'Y-m-d H:i:s', $timestamp + DAY_IN_SECONDS ),
            ),
            'week',
            1,
            10
        );
        $scheduler = new GroupAware_NotificationScheduler();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        // Schedule notifications in both default and custom groups.
        ASWC_Scheduler_API::schedule_notification( $subscription, 'next_payment', $timestamp, 'custom' );
        ASWC_Scheduler_API::schedule_notification( $subscription, 'end', $timestamp + DAY_IN_SECONDS );

        $this->assertArrayHasKey( 'custom', $scheduler->scheduled_actions );
        $this->assertArrayHasKey( ASWC_Scheduler_Notifications::ACTION_GROUP, $scheduler->scheduled_actions );

        // Unschedule custom group only.
        ASWC_Scheduler_API::unschedule_all_notifications( $subscription, array( 'trial_end', 'next_payment', 'end' ), array(), 'custom' );
        $this->assertEmpty( $scheduler->scheduled_actions['custom'] );
        $this->assertArrayHasKey( ASWC_Scheduler_Notifications::ACTION_GROUP, $scheduler->scheduled_actions );

        // Unschedule across all groups.
        ASWC_Scheduler_API::unschedule_all_notifications( $subscription, array( 'trial_end', 'next_payment', 'end' ), array(), false );
        $this->assertEmpty( array_filter( $scheduler->scheduled_actions ) );

        $prop->setValue( null, null );
    }

    public function test_api_last_scheduled_notification_helpers() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        ASWC_Scheduler_API::schedule_notification( $subscription, 'next_payment', $timestamp );

        $this->assertSame( $timestamp, ASWC_Scheduler_API::last_scheduled_notification( $subscription, 'next_payment' ) );
        $this->assertSame(
            array( 'next_payment' => $timestamp ),
            ASWC_Scheduler_API::get_last_scheduled_notifications( $subscription, array( 'next_payment' ) )
        );

        $prop->setValue( null, null );
    }

    public function test_get_scheduled_notification_action() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        // Schedule a notification and fetch the action object directly.
        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp );
        $action = $scheduler->get_scheduled_notification_action( $subscription, 'next_payment' );

        $this->assertIsObject( $action );
        $this->assertSame( $timestamp, $action->timestamp );

        // Verify API wrapper returns the same object.
        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        $api_action = ASWC_Scheduler_API::get_scheduled_notification_action( $subscription, 'next_payment' );

        $this->assertIsObject( $api_action );
        $this->assertSame( $action->timestamp, $api_action->timestamp );

        $prop->setValue( null, null );
    }

    public function test_get_last_scheduled_notification_action() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription( array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ) ), 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        // Schedule a notification and fetch the most recent action object.
        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp );
        $action = $scheduler->get_last_scheduled_notification_action( $subscription, 'next_payment' );

        $this->assertIsObject( $action );
        $this->assertSame( $timestamp, $action->timestamp );

        // Verify API wrapper returns the same object.
        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        $api_action = ASWC_Scheduler_API::get_last_scheduled_notification_action( $subscription, 'next_payment' );

        $this->assertIsObject( $api_action );
        $this->assertSame( $action->timestamp, $api_action->timestamp );

        $prop->setValue( null, null );
    }

    public function test_get_scheduled_notification_actions() {
        $timestamp1  = time() + DAY_IN_SECONDS;
        $timestamp2  = time() + 2 * DAY_IN_SECONDS;
        $dates       = array(
            'trial_end'    => gmdate( 'Y-m-d H:i:s', $timestamp1 ),
            'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp2 ),
        );
        $subscription = new Mock_Subscription( $dates, 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        $scheduler->schedule_notification( $subscription, 'trial_end', $timestamp1 );
        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp2 );

        $actions = $scheduler->get_scheduled_notification_actions( $subscription, array( 'trial_end', 'next_payment' ) );

        $this->assertArrayHasKey( 'trial_end', $actions );
        $this->assertArrayHasKey( 'next_payment', $actions );
        $this->assertSame( $timestamp1, $actions['trial_end']->timestamp );
        $this->assertSame( $timestamp2, $actions['next_payment']->timestamp );

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        $api_actions = ASWC_Scheduler_API::get_scheduled_notification_actions( $subscription, array( 'trial_end', 'next_payment' ) );

        $this->assertSame( $actions['trial_end']->timestamp, $api_actions['trial_end']->timestamp );
        $this->assertSame( $actions['next_payment']->timestamp, $api_actions['next_payment']->timestamp );

        $prop->setValue( null, null );
    }

    public function test_get_last_scheduled_notification_actions() {
        $timestamp1  = time() + DAY_IN_SECONDS;
        $timestamp2  = time() + 2 * DAY_IN_SECONDS;
        $dates       = array(
            'trial_end'    => gmdate( 'Y-m-d H:i:s', $timestamp1 ),
            'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp2 ),
        );
        $subscription = new Mock_Subscription( $dates, 'week', 1, 10 );
        $scheduler    = new GroupAware_NotificationScheduler();

        $scheduler->schedule_notification( $subscription, 'trial_end', $timestamp1 );
        $scheduler->schedule_notification( $subscription, 'next_payment', $timestamp2 );

        $actions = $scheduler->get_last_scheduled_notification_actions( $subscription, array( 'trial_end', 'next_payment' ) );

        $this->assertArrayHasKey( 'trial_end', $actions );
        $this->assertArrayHasKey( 'next_payment', $actions );
        $this->assertSame( $timestamp1, $actions['trial_end']->timestamp );
        $this->assertSame( $timestamp2, $actions['next_payment']->timestamp );

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        $api_actions = ASWC_Scheduler_API::get_last_scheduled_notification_actions( $subscription, array( 'trial_end', 'next_payment' ) );

        $this->assertSame( $actions['trial_end']->timestamp, $api_actions['trial_end']->timestamp );
        $this->assertSame( $actions['next_payment']->timestamp, $api_actions['next_payment']->timestamp );

        $prop->setValue( null, null );
    }

    public function test_schedule_all_respects_status_filter() {
        $timestamp    = time() + DAY_IN_SECONDS;
        $subscription = new Mock_Subscription(
            array( 'next_payment' => gmdate( 'Y-m-d H:i:s', $timestamp ) ),
            'week',
            1,
            10,
            'active'
        );

        $scheduler = new GroupAware_NotificationScheduler();

        add_filter(
            'aswc_subscription_customer_notification_statuses',
            function ( $statuses ) {
                return array( 'on-hold' );
            },
            10,
            2
        );

        $scheduler->schedule_all( $subscription );

        $this->assertEmpty( $scheduler->scheduled_actions );

        remove_all_filters( 'aswc_subscription_customer_notification_statuses' );
    }

    public function test_get_allowed_notification_statuses() {
        $subscription = new Mock_Subscription();
        $scheduler    = new ASWC_Scheduler_Notifications();

        $this->assertSame(
            array( 'active', 'pending-cancel' ),
            $scheduler->get_allowed_notification_statuses( $subscription )
        );

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        $this->assertSame(
            array( 'active', 'pending-cancel' ),
            ASWC_Scheduler_API::get_allowed_notification_statuses( $subscription )
        );

        $this->assertSame(
            array( 'active', 'pending-cancel' ),
            aswc_get_allowed_notification_statuses( $subscription )
        );

        add_filter(
            'aswc_subscription_customer_notification_statuses',
            function( $statuses ) {
                $statuses[] = 'on-hold';
                return $statuses;
            },
            10,
            2
        );

        $expected = array( 'active', 'pending-cancel', 'on-hold' );

        $this->assertSame( $expected, $scheduler->get_allowed_notification_statuses( $subscription ) );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_allowed_notification_statuses( $subscription ) );
        $this->assertSame( $expected, aswc_get_allowed_notification_statuses( $subscription ) );

        remove_all_filters( 'aswc_subscription_customer_notification_statuses' );
        $prop->setValue( null, null );
    }

    public function test_get_allowed_notification_statuses_sanitizes_and_dedupes() {
        $subscription = new Mock_Subscription();
        $scheduler    = new ASWC_Scheduler_Notifications();

        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        add_filter(
            'aswc_subscription_customer_notification_statuses',
            function() {
                return array( ' Active ', 'on-hold', 'ON-HOLD', 'invalid', '' );
            },
            10,
            2
        );

        $expected = array( 'active', 'on-hold' );

        $this->assertSame( $expected, $scheduler->get_allowed_notification_statuses( $subscription ) );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_allowed_notification_statuses( $subscription ) );
        $this->assertSame( $expected, aswc_get_allowed_notification_statuses( $subscription ) );

        remove_all_filters( 'aswc_subscription_customer_notification_statuses' );
        $prop->setValue( null, null );
    }

    public function test_get_allowed_notification_statuses_requires_subscription() {
        $scheduler = new ASWC_Scheduler_Notifications();
        $ref       = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop      = $ref->getProperty( 'notifications' );
        $prop->setAccessible( true );
        $prop->setValue( null, $scheduler );

        $this->assertSame( array(), $scheduler->get_allowed_notification_statuses( new \stdClass() ) );
        $this->assertSame( array(), ASWC_Scheduler_API::get_allowed_notification_statuses( new \stdClass() ) );
        $this->assertSame( array(), aswc_get_allowed_notification_statuses( new \stdClass() ) );

        $prop->setValue( null, null );
    }

    public function test_get_option_prefix() {
        $expected = ASWC_Scheduler_Notifications::OPTION_PREFIX;

        $this->assertSame( $expected, ASWC_Scheduler_Notifications::get_option_prefix() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_notification_option_prefix() );
        $this->assertSame( $expected, aswc_get_notification_option_prefix() );
    }

    public function test_get_offset_option_name() {
        $expected = ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::OFFSET_SETTING;

        $this->assertSame( $expected, ASWC_Scheduler_Notifications::get_offset_option_name() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_notification_offset_option_name() );
        $this->assertSame( $expected, aswc_get_notification_offset_option_name() );
    }

    public function test_get_switch_option_name() {
        $expected = ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::SWITCH_SETTING;

        $this->assertSame( $expected, ASWC_Scheduler_Notifications::get_switch_option_name() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_notification_switch_option_name() );
        $this->assertSame( $expected, aswc_get_notification_switch_option_name() );
    }

    public function test_get_settings_update_time_option_name() {
        $expected = ASWC_Scheduler_Notifications::OPTION_PREFIX . '_notification_settings_update_time';

        $this->assertSame( $expected, ASWC_Scheduler_Notifications::get_settings_update_time_option_name() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_notification_settings_update_time_option_name() );
        $this->assertSame( $expected, aswc_get_notification_settings_update_time_option_name() );
    }

    public function test_get_notification_settings_update_time() {
        global $test_options;

        $option_name                         = ASWC_Scheduler_Notifications::get_settings_update_time_option_name();
        $test_options[ $option_name ]        = 123;
        $this->assertSame( 123, ASWC_Scheduler_Notifications::get_settings_update_time() );
        $this->assertSame( 123, ASWC_Scheduler_API::get_notification_settings_update_time() );
        $this->assertSame( 123, aswc_get_notification_settings_update_time() );

        $test_options = array();
    }

    public function test_get_notification_hook_from_date_type_wrapper() {
        $scheduler = new ASWC_Scheduler_Notifications();

        $this->assertSame(
            'advanced_scheduled_subscription_customer_notification_trial_expiration',
            $scheduler->get_action_from_date_type( 'trial_end' )
        );
        $this->assertSame(
            'advanced_scheduled_subscription_customer_notification_trial_expiration',
            ASWC_Scheduler_API::get_notification_hook_from_date_type( 'trial_end' )
        );
        $this->assertSame(
            'advanced_scheduled_subscription_customer_notification_trial_expiration',
            aswc_get_notification_hook_from_date_type( 'trial_end' )
        );

        $this->assertSame(
            'advanced_scheduled_subscription_customer_notification_renewal',
            $scheduler->get_action_from_date_type( 'next_payment' )
        );
        $this->assertSame(
            'advanced_scheduled_subscription_customer_notification_renewal',
            ASWC_Scheduler_API::get_notification_hook_from_date_type( 'next_payment' )
        );
        $this->assertSame(
            'advanced_scheduled_subscription_customer_notification_renewal',
            aswc_get_notification_hook_from_date_type( 'next_payment' )
        );

        $this->assertSame(
            'advanced_scheduled_subscription_customer_notification_expiration',
            $scheduler->get_action_from_date_type( 'end' )
        );
        $this->assertSame(
            'advanced_scheduled_subscription_customer_notification_expiration',
            ASWC_Scheduler_API::get_notification_hook_from_date_type( 'end' )
        );
        $this->assertSame(
            'advanced_scheduled_subscription_customer_notification_expiration',
            aswc_get_notification_hook_from_date_type( 'end' )
        );

        $this->assertSame( '', $scheduler->get_action_from_date_type( 'foo' ) );
        $this->assertSame( '', ASWC_Scheduler_API::get_notification_hook_from_date_type( 'foo' ) );
        $this->assertSame( '', aswc_get_notification_hook_from_date_type( 'foo' ) );
    }

    public function test_send_notification_handles_missing_class() {
        $this->assertFalse( class_exists( 'WC_Subscriptions_Email_Notifications', false ) );
        // Should not produce errors when the handler class is missing.
        aswc_send_notification( 1, 'renewal' );
        ASWC_Scheduler_API::send_notification( 1, 'renewal' );
        $this->assertTrue( true );
    }

    public function test_should_send_notification_respects_global_setting() {
        global $test_options;
        $test_options = array();
        $test_options[ ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::SWITCH_SETTING ] = 'yes';
        $this->assertTrue( aswc_should_send_notification() );
        $this->assertTrue( ASWC_Scheduler_API::should_send_notification() );

        $test_options[ ASWC_Scheduler_Notifications::OPTION_PREFIX . ASWC_Scheduler_Notifications::SWITCH_SETTING ] = 'no';

        $this->assertFalse( aswc_should_send_notification() );
        $this->assertFalse( ASWC_Scheduler_API::should_send_notification() );
    }

    public function test_send_notification_delegates_to_email_class() {
        if ( ! class_exists( 'WC_Subscriptions_Email_Notifications' ) ) {
            eval(
                'class WC_Subscriptions_Email_Notifications {' .
                'public static $calls = array();' .
                'public static function send_notification($subscription_id,$type,$subscription=null){' .
                'self::$calls[] = array($subscription_id,$type,$subscription);' .
                '}' .
                '}'
            );
        }

        aswc_send_notification( 10, 'expiration', 'sub_obj' );
        ASWC_Scheduler_API::send_notification( 20, 'renewal', 'sub_obj2' );

        $this->assertSame(
            array(
                array( 10, 'expiration', 'sub_obj' ),
                array( 20, 'renewal', 'sub_obj2' ),
            ),
            WC_Subscriptions_Email_Notifications::$calls
        );
    }
}

