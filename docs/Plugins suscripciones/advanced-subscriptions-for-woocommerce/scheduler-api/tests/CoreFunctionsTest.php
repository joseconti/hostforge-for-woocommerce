<?php

use PHPUnit\Framework\TestCase;

// Define minimal WordPress-like helpers and constants used when loading the
// scheduler so tests can run in isolation.
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

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // No-op for tests.
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // No-op for tests.
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        return $value;
    }
}

if ( ! function_exists( 'get_edit_post_link' ) ) {
    function get_edit_post_link( $post_id ) {
        return 'link-' . $post_id;
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

if ( ! class_exists( 'WC_Subscription' ) ) {
    class WC_Subscription {
        public function get_id() {}
        public function has_status( $statuses ) {}
    }
}

class CoreFunctionsTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_date_types_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $expected = array(
            'trial_end'    => '',
            'next_payment' => '',
            'end'          => '',
        );
        $this->assertSame( $expected, aswc_get_subscription_date_types() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_subscription_date_types() );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_statuses_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $expected = array( 'active', 'on-hold', 'cancelled', 'expired', 'pending-cancel' );
        $this->assertSame( $expected, aswc_get_subscription_statuses() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_subscription_statuses() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_sanitize_subscription_status_key_fallback() {
        require_once __DIR__ . '/../scheduler.php';

        $this->assertSame( 'active', aswc_sanitize_subscription_status_key( ' ACTIVE ' ) );
        $this->assertSame( 'on-hold', ASWC_Scheduler_API::sanitize_subscription_status_key( ' On-Hold ' ) );
    }



    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_status_names_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $expected = array(
            'active'         => 'Active',
            'on-hold'        => 'On hold',
            'cancelled'      => 'Cancelled',
            'expired'        => 'Expired',
            'pending-cancel' => 'Pending Cancellation',
        );
        $this->assertSame( $expected, aswc_get_subscription_status_names() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_subscription_status_names() );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_is_subscription_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $sub = new WC_Subscription();
        $this->assertTrue( aswc_is_subscription( $sub ) );
        $this->assertTrue( ASWC_Scheduler_API::is_subscription( $sub ) );
        $this->assertFalse( aswc_is_subscription( new \stdClass() ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_date_to_time_fallback() {
        require_once __DIR__ . '/../scheduler.php';

        $expected = 1672531200; // 2023-01-01 00:00:00 UTC.

        $this->assertSame( $expected, aswc_date_to_time( '2023-01-01 00:00:00' ) );
        $this->assertSame( $expected, ASWC_Scheduler_API::date_to_time( '2023-01-01 00:00:00' ) );
        $this->assertSame( 0, aswc_date_to_time( 0 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_period_strings_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $expected = array(
            'day'   => 'day',
            'week'  => 'week',
            'month' => 'month',
            'year'  => 'year',
        );
        $this->assertSame( $expected, aswc_get_subscription_period_strings() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_subscription_period_strings() );
        $this->assertSame( '2 weeks', aswc_get_subscription_period_strings( 2, 'week' ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_trial_period_strings_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $expected = array(
            'day'   => '1 day',
            'week'  => '1 week',
            'month' => '1 month',
            'year'  => '1 year',
        );
        $this->assertSame( $expected, aswc_get_subscription_trial_period_strings() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_subscription_trial_period_strings() );
        $this->assertSame( '3 months', aswc_get_subscription_trial_period_strings( 3, 'month' ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_ranges_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $ranges = aswc_get_subscription_ranges( 'day' );
        $this->assertSame( 'Do not stop until cancelled', $ranges[0] );
        $this->assertSame( 'day', $ranges[1] );
        $this->assertSame( '2 days', $ranges[2] );
        $this->assertSame( $ranges, ASWC_Scheduler_API::get_subscription_ranges( 'day' ) );
        $all = ASWC_Scheduler_API::get_subscription_ranges();
        $this->assertArrayHasKey( 'week', $all );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_append_numeral_suffix_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( '1st', aswc_append_numeral_suffix( 1 ) );
        $this->assertSame( '12th', ASWC_Scheduler_API::append_numeral_suffix( 12 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_period_interval_strings_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $intervals = aswc_get_subscription_period_interval_strings();
        $this->assertSame( 'every', $intervals[1] );
        $this->assertSame( 'every 4th', $intervals[4] );
        $this->assertSame( 'every 3rd', ASWC_Scheduler_API::get_subscription_period_interval_strings( 3 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_available_time_periods_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $expected = array(
            'day'   => 'day',
            'week'  => 'week',
            'month' => 'month',
            'year'  => 'year',
        );
        $this->assertSame( $expected, aswc_get_available_time_periods() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_available_time_periods() );
        $plural = aswc_get_available_time_periods( 'plural' );
        $this->assertSame( 'days', $plural['day'] );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_trial_lengths_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $lengths = aswc_get_subscription_trial_lengths( 'day' );
        $this->assertSame( 'no', $lengths[0] );
        $this->assertSame( 'day', $lengths[1] );
        $this->assertSame( '2 days', $lengths[2] );
        $all = ASWC_Scheduler_API::get_subscription_trial_lengths();
        $this->assertArrayHasKey( 'week', $all );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_latest_action_scheduler_version_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( '0', aswc_get_latest_action_scheduler_version() );
        $this->assertSame( '0', ASWC_Scheduler_API::get_latest_action_scheduler_version() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_latest_action_scheduler_version_uses_library() {
        if ( ! class_exists( 'ActionScheduler_Versions' ) ) {
            eval(
                'class ActionScheduler_Versions {
                    public static $instance;
                    public static function instance() {
                        return self::$instance ?: ( self::$instance = new self() );
                    }
                    public function latest_version() {
                        return "9.9.9";
                    }
                }'
            );
        }
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( '9.9.9', aswc_get_latest_action_scheduler_version() );
        $this->assertSame( '9.9.9', ASWC_Scheduler_API::get_latest_action_scheduler_version() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_latest_action_scheduler_version_uses_namespaced_library() {
        if ( ! class_exists( '\\ActionScheduler\\Versions' ) ) {
            eval(
                'namespace ActionScheduler;'
                . 'class Versions {'
                . ' public static $instance;'
                . ' public static function instance() { return self::$instance ?: ( self::$instance = new self() ); }'
                . ' public function latest_version() { return "8.8.8"; }'
                . '}'
            );
        }
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( '8.8.8', aswc_get_latest_action_scheduler_version() );
        $this->assertSame( '8.8.8', ASWC_Scheduler_API::get_latest_action_scheduler_version() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_action_scheduler_store_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertNull( aswc_get_action_scheduler_store() );
        $this->assertNull( ASWC_Scheduler_API::get_action_scheduler_store() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_action_scheduler_store_uses_library() {
        if ( ! class_exists( 'ActionScheduler_Store' ) ) {
            eval(
                'class ActionScheduler_Store {
                    public const STATUS_PENDING = "pending";
                    public static $instance;
                    public static function instance() {
                        return self::$instance ?: ( self::$instance = new self() );
                    }
                    public function fetch_action( $id ) {
                        return (object) array( "id" => $id );
                    }
                }'
            );
        }
        require_once __DIR__ . '/../scheduler.php';
        $this->assertInstanceOf( ActionScheduler_Store::class, aswc_get_action_scheduler_store() );
        $this->assertInstanceOf( ActionScheduler_Store::class, ASWC_Scheduler_API::get_action_scheduler_store() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_action_scheduler_store_uses_namespaced_library() {
        if ( ! class_exists( '\\ActionScheduler\\Store' ) ) {
            eval(
                'namespace ActionScheduler;'
                . 'class Store {'
                . ' public const STATUS_PENDING = "waiting";'
                . ' public static $instance;'
                . ' public static function instance() { return self::$instance ?: ( self::$instance = new self() ); }'
                . ' public function fetch_action( $id ) { return (object) array( "id" => $id ); }'
                . '}'
            );
        }
        require_once __DIR__ . '/../scheduler.php';
        $this->assertInstanceOf( '\\ActionScheduler\\Store', aswc_get_action_scheduler_store() );
        $this->assertInstanceOf( '\\ActionScheduler\\Store', ASWC_Scheduler_API::get_action_scheduler_store() );
        $this->assertSame( 'waiting', aswc_get_action_scheduler_pending_status() );
        $this->assertSame( 'waiting', ASWC_Scheduler_API::get_action_scheduler_pending_status() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_action_scheduler_action_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertFalse( aswc_get_action_scheduler_action( 123 ) );
        $this->assertFalse( ASWC_Scheduler_API::get_action( 123 ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_action_scheduler_action_uses_library() {
        if ( ! class_exists( 'ActionScheduler_Store' ) ) {
            eval(
                'class ActionScheduler_Store {
                    public static $instance;
                    public static function instance() {
                        return self::$instance ?: ( self::$instance = new self() );
                    }
                    public function fetch_action( $id ) {
                        return (object) array( "id" => $id );
                    }
                }'
            );
        }
        require_once __DIR__ . '/../scheduler.php';
        $action = aswc_get_action_scheduler_action( 321 );
        $this->assertIsObject( $action );
        $this->assertSame( 321, $action->id );
        $this->assertEquals( $action, ASWC_Scheduler_API::get_action( 321 ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_save_cancel_delete_action_fallback() {
        require_once __DIR__ . '/../scheduler.php';

        $this->assertFalse( aswc_save_action( (object) array() ) );
        $this->assertFalse( ASWC_Scheduler_API::save_action( (object) array() ) );

        $this->assertFalse( aswc_cancel_action( 1 ) );
        $this->assertFalse( ASWC_Scheduler_API::cancel_action( 1 ) );

        $this->assertFalse( aswc_delete_action( 1 ) );
        $this->assertFalse( ASWC_Scheduler_API::delete_action( 1 ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_save_cancel_delete_action_uses_library() {
        if ( ! class_exists( 'ActionScheduler_Store' ) ) {
            eval(
                'class ActionScheduler_Store {
                    public static $instance;
                    public $canceled = array();
                    public $deleted  = array();
                    public $saved    = array();
                    public static function instance() {
                        return self::$instance ?: ( self::$instance = new self() );
                    }
                    public function save_action( $action, $date = null ) {
                        $this->saved[] = array( $action, $date );
                        return 42;
                    }
                    public function cancel_action( $id ) {
                        $this->canceled[] = $id;
                    }
                    public function delete_action( $id ) {
                        $this->deleted[] = $id;
                    }
                }'
            );
        }

        require_once __DIR__ . '/../scheduler.php';

        $action = (object) array( 'foo' => 'bar' );

        $this->assertSame( 42, aswc_save_action( $action ) );
        $this->assertSame( 42, ASWC_Scheduler_API::save_action( $action ) );

        $this->assertTrue( aswc_cancel_action( 5 ) );
        $this->assertTrue( ASWC_Scheduler_API::cancel_action( 6 ) );

        $this->assertTrue( aswc_delete_action( 7 ) );
        $this->assertTrue( ASWC_Scheduler_API::delete_action( 8 ) );

        $store = ActionScheduler_Store::instance();
        $this->assertSame(
            array( array( $action, null ), array( $action, null ) ),
            $store->saved
        );
        $this->assertSame( array( 5, 6 ), $store->canceled );
        $this->assertSame( array( 7, 8 ), $store->deleted );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_mark_action_complete_failed_fallback() {
        require_once __DIR__ . '/../scheduler.php';

        $this->assertFalse( aswc_mark_action_complete( 1 ) );
        $this->assertFalse( ASWC_Scheduler_API::mark_action_complete( 1 ) );

        $this->assertFalse( aswc_mark_action_failed( 2 ) );
        $this->assertFalse( ASWC_Scheduler_API::mark_action_failed( 2 ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_mark_action_complete_failed_use_library() {
        if ( ! class_exists( 'ActionScheduler_Store' ) ) {
            eval(
                'class ActionScheduler_Store {
                    public static $instance;
                    public $completed = array();
                    public $failed = array();
                    public static function instance() {
                        return self::$instance ?: ( self::$instance = new self() );
                    }
                    public function mark_complete( $id ) {
                        $this->completed[] = $id;
                        return true;
                    }
                    public function mark_failure( $id ) {
                        $this->failed[] = $id;
                        return true;
                    }
                }'
            );
        }

        require_once __DIR__ . '/../scheduler.php';

        $this->assertTrue( aswc_mark_action_complete( 11 ) );
        $this->assertTrue( ASWC_Scheduler_API::mark_action_complete( 12 ) );

        $this->assertTrue( aswc_mark_action_failed( 13 ) );
        $this->assertTrue( ASWC_Scheduler_API::mark_action_failed( 14 ) );

        $store = ActionScheduler_Store::instance();
        $this->assertSame( array( 11, 12 ), $store->completed );
        $this->assertSame( array( 13, 14 ), $store->failed );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_claim_and_release_actions_fallback() {
        require_once __DIR__ . '/../scheduler.php';

        $this->assertSame( array(), aswc_claim_actions( 'c', 5 ) );
        $this->assertSame( array(), ASWC_Scheduler_API::claim_actions( 'c', 5 ) );

        $this->assertFalse( aswc_release_claim( 'c' ) );
        $this->assertFalse( ASWC_Scheduler_API::release_claim( 'c' ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_claim_and_release_actions_use_library() {
        if ( ! class_exists( 'ActionScheduler_Store' ) ) {
            eval(
                'class ActionScheduler_Store {
                    public static $instance;
                    public $claims = array();
                    public $released = array();
                    public static function instance() {
                        return self::$instance ?: ( self::$instance = new self() );
                    }
                    public function claim_actions( $claim_id, $limit, $before = null, $hooks = array(), $group = "" ) {
                        $this->claims[] = func_get_args();
                        return array( 10, 11 );
                    }
                    public function release_claim( $claim_id ) {
                        $this->released[] = $claim_id;
                        return true;
                    }
                }'
            );
        }

        require_once __DIR__ . '/../scheduler.php';

        $this->assertSame( array( 10, 11 ), aswc_claim_actions( 'a', 3, 123, array( 'h1' ), 'g1' ) );
        $this->assertSame( array( 10, 11 ), ASWC_Scheduler_API::claim_actions( 'b', 4, 456, array(), 'g2' ) );

        $this->assertTrue( aswc_release_claim( 'a' ) );
        $this->assertTrue( ASWC_Scheduler_API::release_claim( 'b' ) );

        $store = ActionScheduler_Store::instance();
        $this->assertSame(
            array(
                array( 'a', 3, 123, array( 'h1' ), 'g1' ),
                array( 'b', 4, 456, array(), 'g2' ),
            ),
            $store->claims
        );
        $this->assertSame( array( 'a', 'b' ), $store->released );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_unclaim_action_and_query_actions_fallback() {
        require_once __DIR__ . '/../scheduler.php';

        $this->assertFalse( aswc_unclaim_action( 5 ) );
        $this->assertFalse( ASWC_Scheduler_API::unclaim_action( 6 ) );

        $this->assertSame( array(), aswc_query_actions( array( 'status' => 'pending' ) ) );
        $this->assertSame( array(), ASWC_Scheduler_API::query_actions( array( 'status' => 'pending' ) ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_unclaim_action_and_query_actions_use_library() {
        if ( ! class_exists( 'ActionScheduler_Store' ) ) {
            eval(
                'class ActionScheduler_Store {
                    public static $instance;
                    public $unclaimed = array();
                    public $queries   = array();
                    public static function instance() {
                        return self::$instance ?: ( self::$instance = new self() );
                    }
                    public function unclaim_action( $action_id ) {
                        $this->unclaimed[] = $action_id;
                        return true;
                    }
                    public function query_actions( $query_args = array() ) {
                        $this->queries[] = $query_args;
                        return array( 1, 2 );
                    }
                }'
            );
        }

        require_once __DIR__ . '/../scheduler.php';

        $this->assertTrue( aswc_unclaim_action( 10 ) );
        $this->assertTrue( ASWC_Scheduler_API::unclaim_action( 11 ) );

        $this->assertSame( array( 1, 2 ), aswc_query_actions( array( 'hook' => 'h1' ) ) );
        $this->assertSame( array( 1, 2 ), ASWC_Scheduler_API::query_actions( array( 'hook' => 'h2' ) ) );

        $store = ActionScheduler_Store::instance();
        $this->assertSame( array( 10, 11 ), $store->unclaimed );
        $this->assertSame( array( array( 'hook' => 'h1' ), array( 'hook' => 'h2' ) ), $store->queries );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_action_scheduler_pending_status_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( 'pending', aswc_get_action_scheduler_pending_status() );
        $this->assertSame( 'pending', ASWC_Scheduler_API::get_action_scheduler_pending_status() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_action_scheduler_pending_status_uses_library() {
        if ( ! class_exists( 'ActionScheduler_Store' ) ) {
            eval( 'class ActionScheduler_Store { public const STATUS_PENDING = "waiting"; }' );
        }
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( 'waiting', aswc_get_action_scheduler_pending_status() );
        $this->assertSame( 'waiting', ASWC_Scheduler_API::get_action_scheduler_pending_status() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider action_scheduler_status_fallback_provider
     */
    public function test_get_action_scheduler_status_fallbacks( $function, $method, $expected ) {
        require_once __DIR__ . '/../scheduler.php';

        $this->assertSame( $expected, $function() );
        $this->assertSame( $expected, ASWC_Scheduler_API::$method() );
    }

    public static function action_scheduler_status_fallback_provider() {
        return array(
            array( 'aswc_get_action_scheduler_complete_status', 'get_action_scheduler_complete_status', 'complete' ),
            array( 'aswc_get_action_scheduler_failed_status', 'get_action_scheduler_failed_status', 'failed' ),
            array( 'aswc_get_action_scheduler_running_status', 'get_action_scheduler_running_status', 'in-progress' ),
            array( 'aswc_get_action_scheduler_canceled_status', 'get_action_scheduler_canceled_status', 'canceled' ),
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @dataProvider action_scheduler_status_library_provider
     */
    public function test_get_action_scheduler_status_uses_library( $constant, $value, $function, $method ) {
        if ( ! class_exists( 'ActionScheduler_Store' ) ) {
            $constants = array(
                'STATUS_COMPLETE' => 'complete',
                'STATUS_PENDING'  => 'pending',
                'STATUS_RUNNING'  => 'in-progress',
                'STATUS_FAILED'   => 'failed',
                'STATUS_CANCELED' => 'canceled',
            );
            $constants[ $constant ] = $value;

            $code = 'class ActionScheduler_Store {';
            foreach ( $constants as $name => $val ) {
                $code .= "const {$name} = '{$val}';";
            }
            $code .= '}';
            eval( $code );
        }

        require_once __DIR__ . '/../scheduler.php';

        $this->assertSame( $value, $function() );
        $this->assertSame( $value, ASWC_Scheduler_API::$method() );
    }

    public static function action_scheduler_status_library_provider() {
        return array(
            array( 'STATUS_COMPLETE', 'done', 'aswc_get_action_scheduler_complete_status', 'get_action_scheduler_complete_status' ),
            array( 'STATUS_FAILED', 'oops', 'aswc_get_action_scheduler_failed_status', 'get_action_scheduler_failed_status' ),
            array( 'STATUS_RUNNING', 'working', 'aswc_get_action_scheduler_running_status', 'get_action_scheduler_running_status' ),
            array( 'STATUS_CANCELED', 'stopped', 'aswc_get_action_scheduler_canceled_status', 'get_action_scheduler_canceled_status' ),
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_action_scheduler_wrappers_fallback() {
        require_once __DIR__ . '/../scheduler.php';

        $this->assertSame( 0, aswc_schedule_single_action( time(), 'hook' ) );
        $this->assertSame( 0, aswc_enqueue_async_action( 'hook' ) );
        $this->assertSame( 0, aswc_schedule_recurring_action( time(), 60, 'hook' ) );
        $this->assertSame( 0, aswc_schedule_cron_action( time(), '* * * * *', 'hook' ) );
        $this->assertFalse( aswc_next_scheduled_action( 'hook' ) );
        $this->assertFalse( aswc_has_scheduled_action( 'hook' ) );
        $this->assertSame( array(), aswc_get_scheduled_actions( array() ) );

        // These should no-op when Action Scheduler is unavailable.
        aswc_unschedule_all_actions( 'hook', array(), 'grp' );
        aswc_unschedule_action( 'hook', array(), 'grp' );

        $this->assertSame( 0, ASWC_Scheduler_API::enqueue_async_action( 'hook' ) );
        $this->assertSame( 0, ASWC_Scheduler_API::schedule_recurring_action( time(), 60, 'hook' ) );
        $this->assertSame( 0, ASWC_Scheduler_API::schedule_cron_action( time(), '* * * * *', 'hook' ) );

        $this->assertTrue( true );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_action_scheduler_wrappers_use_library() {
        $GLOBALS['as_calls'] = array();

        if ( ! function_exists( 'as_schedule_single_action' ) ) {
            function as_schedule_single_action( $timestamp, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
                $GLOBALS['as_calls']['schedule_single_action'] = func_get_args();
                return 321;
            }
        }
        if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
            function as_unschedule_all_actions( $hook = null, $args = null, $group = '' ) {
                $GLOBALS['as_calls']['unschedule_all_actions'] = func_get_args();
            }
        }
        if ( ! function_exists( 'as_unschedule_action' ) ) {
            function as_unschedule_action( $hook, $args = array(), $group = '' ) {
                $GLOBALS['as_calls']['unschedule_action'] = func_get_args();
            }
        }
        if ( ! function_exists( 'as_next_scheduled_action' ) ) {
            function as_next_scheduled_action( $hook, $args = null, $group = '' ) {
                $GLOBALS['as_calls']['next_scheduled_action'] = func_get_args();
                return 123456;
            }
        }
        if ( ! function_exists( 'as_has_scheduled_action' ) ) {
            function as_has_scheduled_action( $hook, $args = null, $group = '' ) {
                $GLOBALS['as_calls']['has_scheduled_action'] = func_get_args();
                return true;
            }
        }
        if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
            function as_get_scheduled_actions( $query_args = array() ) {
                $GLOBALS['as_calls']['get_scheduled_actions'] = func_get_args();
                return array( 'foo' );
            }
        }
        if ( ! function_exists( 'as_enqueue_async_action' ) ) {
            function as_enqueue_async_action( $hook, $args = array(), $group = '' ) {
                $GLOBALS['as_calls']['enqueue_async_action'] = func_get_args();
                return 654;
            }
        }
        if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
            function as_schedule_recurring_action( $timestamp, $interval, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
                $GLOBALS['as_calls']['schedule_recurring_action'] = func_get_args();
                return 987;
            }
        }
        if ( ! function_exists( 'as_schedule_cron_action' ) ) {
            function as_schedule_cron_action( $timestamp, $schedule, $hook, $args = array(), $group = '', $unique = false, $priority = 10 ) {
                $GLOBALS['as_calls']['schedule_cron_action'] = func_get_args();
                return 789;
            }
        }

        require_once __DIR__ . '/../scheduler.php';

        $this->assertSame( 321, aswc_schedule_single_action( 10, 'hook', array( 'a' => 1 ), 'grp', true, 5 ) );
        $this->assertSame( array( 10, 'hook', array( 'a' => 1 ), 'grp', true, 5 ), $GLOBALS['as_calls']['schedule_single_action'] );
        $this->assertSame( 987, aswc_schedule_recurring_action( 15, 30, 'hook', array( 'b' => 2 ), 'grp', true, 7 ) );
        $this->assertSame( array( 15, 30, 'hook', array( 'b' => 2 ), 'grp', true, 7 ), $GLOBALS['as_calls']['schedule_recurring_action'] );
        $this->assertSame( 789, aswc_schedule_cron_action( 20, '* * * * *', 'cron', array( 'c' => 3 ), 'grp', false, 8 ) );
        $this->assertSame( array( 20, '* * * * *', 'cron', array( 'c' => 3 ), 'grp', false, 8 ), $GLOBALS['as_calls']['schedule_cron_action'] );
        unset( $GLOBALS['as_calls']['schedule_single_action'] );
        $this->assertSame( 321, aswc_schedule_unique_action( 25, 'uniq', array( 'd' => 4 ), 'grp', 6 ) );
        $this->assertSame( array( 25, 'uniq', array( 'd' => 4 ), 'grp', true, 6 ), $GLOBALS['as_calls']['schedule_single_action'] );
        aswc_unschedule_all_actions( 'hook', array( 'a' => 1 ), 'grp' );
        aswc_unschedule_action( 'hook', array( 'a' => 1 ), 'grp' );
        $this->assertSame( 123456, aswc_next_scheduled_action( 'hook', array( 'a' => 1 ), 'grp' ) );
        $this->assertTrue( aswc_has_scheduled_action( 'hook', array( 'a' => 1 ), 'grp' ) );
        $this->assertSame( array( 'foo' ), aswc_get_scheduled_actions( array( 'hook' => 'hook' ) ) );
        $this->assertSame( 654, aswc_enqueue_async_action( 'hook', array( 'a' => 1 ), 'grp' ) );
        $this->assertSame( 654, ASWC_Scheduler_API::enqueue_async_action( 'hook', array( 'a' => 1 ), 'grp' ) );
        $this->assertSame( 987, ASWC_Scheduler_API::schedule_recurring_action( 15, 30, 'hook', array( 'b' => 2 ), true, 'grp' ) );
        $this->assertSame( 789, ASWC_Scheduler_API::schedule_cron_action( 20, '* * * * *', 'cron', array( 'c' => 3 ), false, 'grp' ) );
        unset( $GLOBALS['as_calls']['schedule_single_action'] );
        $this->assertSame( 321, ASWC_Scheduler_API::schedule_unique_action( 35, 'api_uniq', array( 'e' => 5 ), 'grp', 9 ) );
        $this->assertSame( array( 35, 'api_uniq', array( 'e' => 5 ), 'grp', true, 9 ), $GLOBALS['as_calls']['schedule_single_action'] );
        $this->assertSame( array( 'hook', array( 'a' => 1 ), 'grp' ), $GLOBALS['as_calls']['unschedule_all_actions'] );
        $this->assertSame( array( 'hook', array( 'a' => 1 ), 'grp' ), $GLOBALS['as_calls']['unschedule_action'] );
        $this->assertSame( array( 'hook', array( 'a' => 1 ), 'grp' ), $GLOBALS['as_calls']['next_scheduled_action'] );
        $this->assertSame( array( 'hook', array( 'a' => 1 ), 'grp' ), $GLOBALS['as_calls']['has_scheduled_action'] );
        $this->assertSame( array( array( 'hook' => 'hook' ) ), $GLOBALS['as_calls']['get_scheduled_actions'] );
        $this->assertSame( array( 'hook', array( 'a' => 1 ), 'grp' ), $GLOBALS['as_calls']['enqueue_async_action'] );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_action_scheduler_log_fallback() {
        require_once __DIR__ . '/../scheduler.php';

        // Should not error when the logger is unavailable.
        aswc_action_scheduler_log( 1, 'msg' );
        ASWC_Scheduler_API::log_action( 2, 'msg' );

        $this->assertTrue( true );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_action_scheduler_log_uses_library() {
        if ( ! class_exists( 'ActionScheduler_Logger' ) ) {
            eval(
                'class ActionScheduler_Logger {' .
                ' public static $instance;' .
                ' public static $logged = array();' .
                ' public static function instance(){ return self::$instance ?: ( self::$instance = new self() ); }' .
                ' public function log( $action_id, $message ){ self::$logged[] = array( $action_id, $message ); }' .
                '}'
            );
        }
        require_once __DIR__ . '/../scheduler.php';

        aswc_action_scheduler_log( 123, 'first' );
        ASWC_Scheduler_API::log_action( 456, 'second' );

        $this->assertSame( array( array( 123, 'first' ), array( 456, 'second' ) ), ActionScheduler_Logger::$logged );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_action_scheduler_log_uses_namespaced_library() {
        if ( ! class_exists( '\\ActionScheduler\\Logger' ) ) {
            eval(
                'namespace ActionScheduler;'
                . 'class Logger {'
                . ' public static $instance;'
                . ' public static $logged = array();'
                . ' public static function instance(){ return self::$instance ?: ( self::$instance = new self() ); }'
                . ' public function log( $action_id, $message ){ self::$logged[] = array( $action_id, $message ); }'
                . '}'
            );
        }
        require_once __DIR__ . '/../scheduler.php';
        aswc_action_scheduler_log( 1, 'alpha' );
        ASWC_Scheduler_API::log_action( 2, 'beta' );
        $logger = '\\ActionScheduler\\Logger';
        $this->assertSame( array( array( 1, 'alpha' ), array( 2, 'beta' ) ), $logger::$logged );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_objects_property_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $obj  = (object) array( 'id' => 123 );
        $this->assertSame( 123, aswc_get_objects_property( $obj, 'id' ) );

        $obj2 = new class {
            public function get_name() { return 'tester'; }
        };
        $this->assertSame( 'tester', ASWC_Scheduler_API::get_objects_property( $obj2, 'name' ) );
        $this->assertSame( 'default', aswc_get_objects_property( new \stdClass(), 'missing', 'single', 'default' ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_action_hook_args_schedule_and_status_from_action() {
        require_once __DIR__ . '/../scheduler.php';

        $action = new class {
            public function get_hook() { return 'test_hook'; }
            public function get_args() { return array( 'id' => 42 ); }
            public function get_schedule() { return (object) array( 'id' => 99 ); }
            public function get_status() { return 'pending'; }
            public function get_group() { return 'demo-group'; }
            public function get_id() { return 7; }
            public function get_priority() { return 5; }
            public function get_attempts() { return 3; }
            public function get_claim_id() { return 4; }
            public function get_post_id() { return 123; }
            public function get_user_id() { return 456; }
            public function get_meta( $key ) { return 'meta-' . $key; }
            public function is_finished() { return true; }
        };

        $this->assertSame( 'test_hook', aswc_get_action_hook( $action ) );
        $this->assertSame( array( 'id' => 42 ), aswc_get_action_args( $action ) );
        $this->assertEquals( (object) array( 'id' => 99 ), aswc_get_action_schedule( $action ) );
        $this->assertSame( 'pending', aswc_get_action_status( $action ) );
        $this->assertSame( 'test_hook', ASWC_Scheduler_API::get_action_hook( $action ) );
        $this->assertSame( array( 'id' => 42 ), ASWC_Scheduler_API::get_action_args_from_action( $action ) );
        $this->assertEquals( (object) array( 'id' => 99 ), ASWC_Scheduler_API::get_action_schedule( $action ) );
        $this->assertSame( 'pending', ASWC_Scheduler_API::get_action_status( $action ) );
        $this->assertSame( 'demo-group', aswc_get_action_group( $action ) );
        $this->assertSame( 7, aswc_get_action_id( $action ) );
        $this->assertSame( 'demo-group', ASWC_Scheduler_API::get_action_group( $action ) );
        $this->assertSame( 7, ASWC_Scheduler_API::get_action_id( $action ) );
        $this->assertSame( 5, aswc_get_action_priority( $action ) );
        $this->assertSame( 5, ASWC_Scheduler_API::get_action_priority_from_action( $action ) );
        $this->assertSame( 3, aswc_get_action_attempts( $action ) );
        $this->assertSame( 4, aswc_get_action_claim_id( $action ) );
        $this->assertSame( 123, aswc_get_action_post_id( $action ) );
        $this->assertSame( 456, aswc_get_action_user_id( $action ) );
        $this->assertSame( 3, ASWC_Scheduler_API::get_action_attempts_from_action( $action ) );
        $this->assertSame( 4, ASWC_Scheduler_API::get_action_claim_id_from_action( $action ) );
        $this->assertSame( 123, ASWC_Scheduler_API::get_action_post_id( $action ) );
        $this->assertSame( 456, ASWC_Scheduler_API::get_action_user_id( $action ) );
        $this->assertSame( 'meta-foo', aswc_get_action_meta( $action, 'foo' ) );
        $this->assertSame( 'meta-foo', ASWC_Scheduler_API::get_action_meta( $action, 'foo' ) );
        $this->assertTrue( aswc_is_action_finished( $action ) );
        $this->assertTrue( ASWC_Scheduler_API::is_action_finished( $action ) );

        $this->assertNull( aswc_get_action_hook( new \stdClass() ) );
        $this->assertSame( array(), aswc_get_action_args( new \stdClass() ) );
        $this->assertNull( aswc_get_action_schedule( new \stdClass() ) );
        $this->assertNull( aswc_get_action_status( new \stdClass() ) );
        $this->assertSame( '', aswc_get_action_group( new \stdClass() ) );
        $this->assertSame( 0, aswc_get_action_id( new \stdClass() ) );
        $this->assertSame( 10, aswc_get_action_priority( new \stdClass() ) );
        $this->assertSame( 0, aswc_get_action_attempts( new \stdClass() ) );
        $this->assertSame( 0, aswc_get_action_claim_id( new \stdClass() ) );
        $this->assertSame( 0, aswc_get_action_post_id( new \stdClass() ) );
        $this->assertSame( 0, aswc_get_action_user_id( new \stdClass() ) );
        $this->assertNull( aswc_get_action_meta( new \stdClass(), 'foo' ) );
        $this->assertFalse( aswc_is_action_finished( new \stdClass() ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_set_and_delete_action_meta() {
        require_once __DIR__ . '/../scheduler.php';

        $GLOBALS['saved_meta']   = array();
        $GLOBALS['deleted_meta'] = array();

        $action = new class {
            public function save_meta( $key, $value ) { $GLOBALS['saved_meta'][ $key ] = $value; }
            public function delete_meta( $key ) { $GLOBALS['deleted_meta'][] = $key; }
        };

        $this->assertTrue( aswc_set_action_meta( $action, 'foo', 'bar' ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_meta( $action, 'baz', 'qux' ) );
        $this->assertTrue( aswc_delete_action_meta( $action, 'foo' ) );
        $this->assertTrue( ASWC_Scheduler_API::delete_action_meta( $action, 'baz' ) );

        $this->assertSame( array( 'foo' => 'bar', 'baz' => 'qux' ), $GLOBALS['saved_meta'] );
        $this->assertSame( array( 'foo', 'baz' ), $GLOBALS['deleted_meta'] );

        $this->assertFalse( aswc_set_action_meta( new \stdClass(), 'foo', 'bar' ) );
        $this->assertFalse( aswc_delete_action_meta( new \stdClass(), 'foo' ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_set_action_properties() {
        require_once __DIR__ . '/../scheduler.php';

        $action = new class {
            public $hook = '';
            public $args = array();
            public $schedule = null;
            public $group = '';
            public $status = '';
            public $priority = 0;
            public $attempts = 0;
            public $claim_id = 0;
            public $post_id = 0;
            public $user_id = 0;
            public function set_hook( $hook ) { $this->hook = $hook; }
            public function set_args( $args ) { $this->args = $args; }
            public function set_schedule( $schedule ) { $this->schedule = $schedule; }
            public function set_group( $group ) { $this->group = $group; }
            public function set_status( $status ) { $this->status = $status; }
            public function set_priority( $priority ) { $this->priority = $priority; }
            public function set_attempts( $attempts ) { $this->attempts = $attempts; }
            public function set_claim_id( $claim_id ) { $this->claim_id = $claim_id; }
            public function set_post_id( $post_id ) { $this->post_id = $post_id; }
            public function set_user_id( $user_id ) { $this->user_id = $user_id; }
        };

        $this->assertTrue( aswc_set_action_hook( $action, 'first' ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_hook( $action, 'second' ) );
        $this->assertSame( 'second', $action->hook );

        $this->assertTrue( aswc_set_action_args( $action, array( 'a' => 1 ) ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_args( $action, array( 'b' => 2 ) ) );
        $this->assertSame( array( 'b' => 2 ), $action->args );

        $schedule1 = (object) array( 'time' => 1 );
        $schedule2 = (object) array( 'time' => 2 );
        $this->assertTrue( aswc_set_action_schedule( $action, $schedule1 ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_schedule( $action, $schedule2 ) );
        $this->assertSame( $schedule2, $action->schedule );

        $this->assertTrue( aswc_set_action_group( $action, 'group1' ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_group( $action, 'group2' ) );
        $this->assertSame( 'group2', $action->group );

        $this->assertTrue( aswc_set_action_status( $action, 'pending' ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_status( $action, 'complete' ) );
        $this->assertSame( 'complete', $action->status );

        $this->assertTrue( aswc_set_action_priority( $action, 1 ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_priority( $action, 5 ) );
        $this->assertSame( 5, $action->priority );

        $this->assertTrue( aswc_set_action_attempts( $action, 1 ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_attempts( $action, 2 ) );
        $this->assertSame( 2, $action->attempts );

        $this->assertTrue( aswc_set_action_claim_id( $action, 3 ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_claim_id( $action, 4 ) );
        $this->assertSame( 4, $action->claim_id );

        $this->assertTrue( aswc_set_action_post_id( $action, 10 ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_post_id( $action, 20 ) );
        $this->assertSame( 20, $action->post_id );

        $this->assertTrue( aswc_set_action_user_id( $action, 30 ) );
        $this->assertTrue( ASWC_Scheduler_API::set_action_user_id( $action, 40 ) );
        $this->assertSame( 40, $action->user_id );

        $this->assertFalse( aswc_set_action_hook( new \stdClass(), 'x' ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_schedule_timestamp() {
        require_once __DIR__ . '/../scheduler.php';

        $schedule = new class {
            public function get_date() {
                return new \DateTime( '@123', new \DateTimeZone( 'UTC' ) );
            }
        };

        $this->assertSame( 123, aswc_get_schedule_timestamp( $schedule ) );
        $this->assertSame( 123, ASWC_Scheduler_API::get_schedule_timestamp( $schedule ) );
        $this->assertFalse( aswc_get_schedule_timestamp( new \stdClass() ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_schedule_gmt_timestamp() {
        require_once __DIR__ . '/../scheduler.php';

        $schedule = new class {
            public function get_date_gmt() {
                return new \DateTime( '@123', new \DateTimeZone( 'UTC' ) );
            }
        };

        $this->assertSame( 123, aswc_get_schedule_gmt_timestamp( $schedule ) );
        $this->assertSame( 123, ASWC_Scheduler_API::get_schedule_gmt_timestamp( $schedule ) );
        $this->assertFalse( aswc_get_schedule_gmt_timestamp( new \stdClass() ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_schedule_next_timestamp() {
        require_once __DIR__ . '/../scheduler.php';

        $schedule = new class {
            public function next( $after = null ) {
                return new \DateTime( '@456', new \DateTimeZone( 'UTC' ) );
            }
        };

        $after = new \DateTime( '@123', new \DateTimeZone( 'UTC' ) );

        $this->assertSame( 456, aswc_get_schedule_next_timestamp( $schedule, $after ) );
        $this->assertSame( 456, ASWC_Scheduler_API::get_schedule_next_timestamp( $schedule, $after ) );
        $this->assertFalse( aswc_get_schedule_next_timestamp( new \stdClass() ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_schedule_recurrence() {
        require_once __DIR__ . '/../scheduler.php';

        $schedule = new class {
            public function get_recurrence() {
                return 300;
            }
        };

        $this->assertSame( 300, aswc_get_schedule_recurrence( $schedule ) );
        $this->assertSame( 300, ASWC_Scheduler_API::get_schedule_recurrence( $schedule ) );
        $this->assertFalse( aswc_get_schedule_recurrence( new \stdClass() ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_is_schedule_recurring() {
        require_once __DIR__ . '/../scheduler.php';

        $schedule = new class {
            public function is_recurring() {
                return true;
            }
        };

        $this->assertTrue( aswc_is_schedule_recurring( $schedule ) );
        $this->assertTrue( ASWC_Scheduler_API::is_schedule_recurring( $schedule ) );
        $this->assertFalse( aswc_is_schedule_recurring( new \stdClass() ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_is_woocommerce_pre_fallback() {
        require_once __DIR__ . '/../scheduler.php';

        define( 'WC_VERSION', '8.5.0' );

        $this->assertTrue( aswc_is_woocommerce_pre( '8.6' ) );
        $this->assertFalse( aswc_is_woocommerce_pre( '8.5.0' ) );
        $this->assertTrue( ASWC_Scheduler_API::is_woocommerce_pre( '8.6' ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_is_custom_order_tables_usage_enabled_fallback() {
        if ( ! class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
            eval(
                'namespace Automattic\\WooCommerce\\Utilities;' .
                'class OrderUtil { public static function custom_orders_table_usage_is_enabled() { return true; } }'
            );
        }

        require_once __DIR__ . '/../scheduler.php';
        $this->assertTrue( aswc_is_custom_order_tables_usage_enabled() );
        $this->assertTrue( ASWC_Scheduler_API::is_custom_order_tables_usage_enabled() );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_is_custom_order_tables_data_sync_enabled_fallback() {
        if ( ! function_exists( 'wc_get_container' ) ) {
            function wc_get_container() {
                return new class {
                    public function get( $class ) {
                        return new $class();
                    }
                };
            }
        }

        if ( ! class_exists( '\\Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer' ) ) {
            eval(
                'namespace Automattic\\WooCommerce\\Internal\\DataStores\\Orders;' .
                'class DataSynchronizer { public function data_sync_is_enabled() { return true; } }'
            );
        }

        require_once __DIR__ . '/../scheduler.php';
        $this->assertTrue( aswc_is_custom_order_tables_data_sync_enabled() );
        $this->assertTrue( ASWC_Scheduler_API::is_custom_order_tables_data_sync_enabled() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_orders_data_synchronizer_fallback() {
        if ( ! function_exists( 'wc_get_container' ) ) {
            function wc_get_container() {
                return new class {
                    public function get( $class ) {
                        return new $class();
                    }
                };
            }
        }

        if ( ! class_exists( '\\Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer' ) ) {
            eval(
                'namespace Automattic\\WooCommerce\\Internal\\DataStores\\Orders;' .
                'class DataSynchronizer { public function data_sync_is_enabled() { return true; } }'
            );
        }

        require_once __DIR__ . '/../scheduler.php';
        $this->assertInstanceOf(
            '\\Automattic\\WooCommerce\\Internal\\DataStores\\Orders\\DataSynchronizer',
            aswc_get_orders_data_synchronizer()
        );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_edit_post_link_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( 'link-1', aswc_get_edit_post_link( 1 ) );
        $this->assertSame( 'link-2', ASWC_Scheduler_API::get_edit_post_link( 2 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_plugin_directory_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( '', aswc_get_plugin_directory() );
        $this->assertSame( '', ASWC_Scheduler_API::get_plugin_directory() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_plugin_directory_uses_plugin() {
        if ( ! class_exists( 'WC_Subscriptions_Plugin' ) ) {
            eval( 'class WC_Subscriptions_Plugin { public static function instance() { return new self(); } public function get_plugin_directory( $path = "" ) { return "/base/" . $path; } }' );
        }

        require_once __DIR__ . '/../scheduler.php';
        $this->assertSame( '/base/foo/', aswc_get_plugin_directory( 'foo/' ) );
        $this->assertSame( '/base/foo/', ASWC_Scheduler_API::get_plugin_directory( 'foo/' ) );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_logger_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $this->assertNull( aswc_get_logger() );
        $this->assertNull( ASWC_Scheduler_API::get_logger() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_logger_uses_wc_function() {
        if ( ! interface_exists( 'WC_Logger_Interface' ) ) {
            eval( 'interface WC_Logger_Interface { public function emergency( $message, array $context = array() ); public function alert( $message, array $context = array() ); public function critical( $message, array $context = array() ); public function error( $message, array $context = array() ); public function warning( $message, array $context = array() ); public function notice( $message, array $context = array() ); public function info( $message, array $context = array() ); public function debug( $message, array $context = array() ); public function log( $level, $message, array $context = array() ); }' );
        }

        if ( ! function_exists( 'wc_get_logger' ) ) {
            function wc_get_logger() {
                static $logger;
                if ( null === $logger ) {
                    $logger = new class implements WC_Logger_Interface {
                        public function emergency( $message, array $context = array() ) {}
                        public function alert( $message, array $context = array() ) {}
                        public function critical( $message, array $context = array() ) {}
                        public function error( $message, array $context = array() ) {}
                        public function warning( $message, array $context = array() ) {}
                        public function notice( $message, array $context = array() ) {}
                        public function info( $message, array $context = array() ) {}
                        public function debug( $message, array $context = array() ) {}
                        public function log( $level, $message, array $context = array() ) {}
                    };
                }
                return $logger;
            }
        }

        require_once __DIR__ . '/../scheduler.php';
        $logger = aswc_get_logger();
        $this->assertInstanceOf( WC_Logger_Interface::class, $logger );
        $this->assertSame( $logger, ASWC_Scheduler_API::get_logger() );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_item_grouping_key_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $item = new class() {
            public function get_product_id() { return 123; }
        };
        $this->assertSame( '123:0', aswc_get_subscription_item_grouping_key( $item ) );
        $this->assertSame( '123:456', ASWC_Scheduler_API::get_subscription_item_grouping_key( $item, 456 ) );
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_get_subscription_ended_statuses_fallback() {
        require_once __DIR__ . '/../scheduler.php';
        $expected = array( 'cancelled', 'trash', 'expired', 'switched', 'pending-cancel' );
        $this->assertSame( $expected, aswc_get_subscription_ended_statuses() );
        $this->assertSame( $expected, ASWC_Scheduler_API::get_subscription_ended_statuses() );
    }

}
