<?php
// phpcs:ignoreFile
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

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name, $default = false ) {
        if ( 'timezone_string' === $name ) {
            return 'Europe/Madrid';
        }

        return $default;
    }
}

if ( ! function_exists( 'aswc_unschedule_all_actions' ) ) {
    function aswc_unschedule_all_actions( $hook, $args = array(), $group = '' ) {
        // No-op for tests.
    }
}

if ( ! function_exists( 'aswc_schedule_single_action' ) ) {
    function aswc_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority = 10 ) {
        Test_Core_Scheduler_Timezone::$scheduled_timestamp = $timestamp;
        return 1;
    }
}

if ( ! function_exists( 'aswc_get_latest_action_scheduler_version' ) ) {
    function aswc_get_latest_action_scheduler_version() {
        return '3.6.0';
    }
}

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/../core/aswc-core-functions.php';

class Test_Core_Scheduler_Timezone extends ASWC_Scheduler_Core {
    public static $scheduled_timestamp = 0;

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        // No-op for tests.
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        return false;
    }
}

class RescheduleActionTimezoneTest extends TestCase {
    public function test_reschedule_action_handles_timezone_once() {
        $scheduler = new Test_Core_Scheduler_Timezone();
        $local     = time() + 18000; // Five hours in the future.

        $scheduler->reschedule_action( $local, 'aswc_test_hook', array() );

        $timezone = new DateTimeZone( 'Europe/Madrid' );
        $offset   = $timezone->getOffset( new DateTime( '@' . $local ) );
        $expected = $local - $offset;

        $this->assertSame( $expected, Test_Core_Scheduler_Timezone::$scheduled_timestamp );
    }
}
