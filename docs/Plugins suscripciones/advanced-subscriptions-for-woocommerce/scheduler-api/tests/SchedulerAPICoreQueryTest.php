<?php
use PHPUnit\Framework\TestCase;

if ( ! isset( $test_filters ) ) {
    $test_filters = array();
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        global $test_filters;
        $args = func_get_args();

        if ( isset( $test_filters[ $tag ] ) ) {
            foreach ( $test_filters[ $tag ] as $callback ) {
                $args[1] = call_user_func_array( $callback, array_slice( $args, 1 ) );
                $args    = array_merge( array( $args[0], $args[1] ), array_slice( $args, 2 ) );
            }
        }

        return $args[1];
    }
}
if ( ! function_exists( 'add_action' ) ) { function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {} }
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        global $test_filters;
        $test_filters[ $hook ][] = $callback;
    }
}
if ( ! function_exists( 'remove_all_filters' ) ) {
    function remove_all_filters( $hook ) {
        global $test_filters;
        unset( $test_filters[ $hook ] );
    }
}
require_once __DIR__ . '/../scheduler.php';

class Dummy_Core_For_Query extends ASWC_Scheduler_Core {
    public $last_call = array();

    public function next_scheduled_action( $hook, $args, $group = null ) {
        $this->last_call = array( __FUNCTION__, func_get_args() );
        return 111;
    }

    public function last_scheduled_action( $hook, $args, $group = null ) {
        $this->last_call = array( __FUNCTION__, func_get_args() );
        return 222;
    }

    public function has_scheduled_action( $hook, $args, $group = null ) {
        $this->last_call = array( __FUNCTION__, func_get_args() );
        return true;
    }

    public function get_scheduled_action( $hook, $args = array(), $group = null ) {
        $this->last_call = array( __FUNCTION__, func_get_args() );
        return (object) array( 'hook' => $hook, 'args' => $args );
    }

    public function get_scheduled_actions( $hook, $args = array(), $group = null ) {
        $this->last_call = array( __FUNCTION__, func_get_args() );
        return array( (object) array( 'hook' => $hook, 'args' => $args ) );
    }
}

class SchedulerAPICoreQueryTest extends TestCase {
    protected $core;

    protected function setUp(): void {
        $this->core = new Dummy_Core_For_Query();
        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'core' );
        $prop->setAccessible( true );
        $prop->setValue( null, $this->core );
    }

    protected function tearDown(): void {
        $ref  = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'core' );
        $prop->setAccessible( true );
        $prop->setValue( null, null );
    }

    public function test_next_scheduled_action_delegates_group() {
        $result = ASWC_Scheduler_API::next_scheduled_action( 'hook', array( 'id' => 1 ), 'grp' );
        $this->assertSame( array( 'hook', array( 'id' => 1 ), 'grp' ), $this->core->last_call[1] );
        $this->assertSame( 111, $result );
    }

    public function test_last_scheduled_action_delegates_group() {
        $result = ASWC_Scheduler_API::last_scheduled_action( 'hook', array( 'id' => 2 ), 'grp2' );
        $this->assertSame( array( 'hook', array( 'id' => 2 ), 'grp2' ), $this->core->last_call[1] );
        $this->assertSame( 222, $result );
    }

    public function test_has_scheduled_action_delegates_group() {
        $result = ASWC_Scheduler_API::has_scheduled_action( 'hook', array( 'id' => 3 ), 'grp3' );
        $this->assertSame( array( 'hook', array( 'id' => 3 ), 'grp3' ), $this->core->last_call[1] );
        $this->assertTrue( $result );
    }

    public function test_get_scheduled_action_delegates_group() {
        $action = ASWC_Scheduler_API::get_scheduled_action( 'hook', array( 'id' => 4 ), 'grp4' );
        $this->assertSame( array( 'hook', array( 'id' => 4 ), 'grp4' ), $this->core->last_call[1] );
        $this->assertIsObject( $action );
        $this->assertSame( 'hook', $action->hook );
    }

    public function test_get_scheduled_actions_delegates_group() {
        $actions = ASWC_Scheduler_API::get_scheduled_actions( 'hook', array( 'id' => 5 ), 'grp5' );
        $this->assertSame( array( 'hook', array( 'id' => 5 ), 'grp5' ), $this->core->last_call[1] );
        $this->assertIsArray( $actions );
        $this->assertCount( 1, $actions );
        $this->assertSame( 'hook', $actions[0]->hook );
    }
}

