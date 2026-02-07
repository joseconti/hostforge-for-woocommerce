<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../scheduler.php';

class Dummy_Core_For_Reschedule_Group extends ASWC_Scheduler_Core {
    public $last_args = array();

    public function reschedule_action( $timestamp, $action_hook, $action_args, $group = null ) {
        $this->last_args = func_get_args();
    }
}

class SchedulerAPICoreRescheduleGroupTest extends TestCase {
    protected $core;

    protected function setUp(): void {
        $this->core = new Dummy_Core_For_Reschedule_Group();
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

    public function test_reschedule_action_passes_group_to_core() {
        ASWC_Scheduler_API::reschedule_action( 100, 'aswc_hook', array( 'id' => 1 ), 'custom' );

        $this->assertSame( array( 100, 'aswc_hook', array( 'id' => 1 ), 'custom' ), $this->core->last_args );
    }

    public function test_reschedule_action_can_ignore_group() {
        ASWC_Scheduler_API::reschedule_action( 100, 'aswc_hook', array( 'id' => 2 ), false );

        $this->assertSame( array( 100, 'aswc_hook', array( 'id' => 2 ), false ), $this->core->last_args );
    }
}
