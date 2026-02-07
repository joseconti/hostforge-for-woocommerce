<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../scheduler.php';

class Dummy_Core_For_GetAction extends ASWC_Scheduler_Core {
    public $last_id;

    public function get_action( $action_id ) {
        $this->last_id = $action_id;
        return (object) array( 'id' => $action_id );
    }
}

class SchedulerAPICoreGetActionTest extends TestCase {
    protected $core;

    protected function setUp(): void {
        $this->core = new Dummy_Core_For_GetAction();
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

    public function test_get_action_delegates_to_core() {
        $action = ASWC_Scheduler_API::get_action( 123 );

        $this->assertSame( 123, $this->core->last_id );
        $this->assertIsObject( $action );
        $this->assertSame( 123, $action->id );
    }
}
