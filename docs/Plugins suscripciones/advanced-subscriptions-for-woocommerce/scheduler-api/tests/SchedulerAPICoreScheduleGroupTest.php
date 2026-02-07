<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../scheduler.php';

class Dummy_Core_For_Schedule_Group extends ASWC_Scheduler_Core {
    public $last_args = array();

    public function schedule_action( $timestamp, $action_hook, $action_args, $unique = false, $group = null ) {
        $this->last_args = func_get_args();
        return 1;
    }

    public function enqueue_async_action( $action_hook, $action_args = array(), $group = null ) {
        $this->last_args = func_get_args();
        return 2;
    }
}

class SchedulerAPICoreScheduleGroupTest extends TestCase {
    protected $core;

    protected function setUp(): void {
        $this->core = new Dummy_Core_For_Schedule_Group();
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

    public function test_schedule_action_passes_group_to_core() {
        ASWC_Scheduler_API::schedule_action( 100, 'aswc_hook', array( 'id' => 1 ), false, 'custom' );
        $this->assertSame( array( 100, 'aswc_hook', array( 'id' => 1 ), false, 'custom' ), $this->core->last_args );
    }

    public function test_enqueue_async_action_passes_group_to_core() {
        ASWC_Scheduler_API::enqueue_async_action( 'aswc_async', array( 'id' => 2 ), 'grp' );
        $this->assertSame( array( 'aswc_async', array( 'id' => 2 ), 'grp' ), $this->core->last_args );
    }
}
