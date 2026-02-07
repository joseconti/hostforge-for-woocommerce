<?php
use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WC_Logger' ) ) {
    class WC_Logger {
        public function add( $handle, $message ) {}
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name ) {
        return '';
    }
}

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
    define( 'WEEK_IN_SECONDS', 7 * 24 * 60 * 60 );
}

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
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
}

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/' );
}

require_once __DIR__ . '/../core/class-aswc-scheduler-core.php';
require_once __DIR__ . '/../background/class-aswc-scheduler-background.php';
require_once __DIR__ . '/../scheduler.php';
require_once __DIR__ . '/../../includes/core/upgrades/class-aswc-upgrade-logger.php';

class Test_Upgrade_Logger_Background extends ASWC_Scheduler_Background {
    public $scheduled = array();

    protected function build_key( $hook, $args ) {
        ksort( $args );
        return $hook . ':' . json_encode( $args );
    }

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $this->scheduled[ $this->build_key( $action_hook, $action_args ) ] = $timestamp;
        return count( $this->scheduled );
    }

    public function unschedule_actions( $action_hook, $action_args, $group = null ) {
        unset( $this->scheduled[ $this->build_key( $action_hook, $action_args ) ] );
    }

    public function next_scheduled_action( $action_hook, $action_args, $group = null ) {
        $key = $this->build_key( $action_hook, $action_args );
        return $this->scheduled[ $key ] ?? false;
    }
}

class UpgradeLoggerCleanupTest extends TestCase {
    protected $scheduler;

    protected function setUp(): void {
        $this->scheduler = new Test_Upgrade_Logger_Background();
        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'background' );
        $prop->setAccessible( true );
        $prop->setValue( null, $this->scheduler );
    }

    protected function tearDown(): void {
        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'background' );
        $prop->setAccessible( true );
        $prop->setValue( null, null );
    }

    public function test_schedule_cleanup_schedules_background_hook() {
        $expected = time() + ( WEEK_IN_SECONDS * ASWC_Upgrade_Logger::$weeks_until_cleanup );

        ASWC_Upgrade_Logger::schedule_cleanup( '', '', '1.0.0', '0.9.0' );

        $scheduled = ASWC_Scheduler_API::next_scheduled_background_action( ASWC_Upgrade_Logger::CLEANUP_HOOK );

        $this->assertNotFalse( $scheduled );
        $this->assertEqualsWithDelta( $expected, $scheduled, 1 );
    }
}
