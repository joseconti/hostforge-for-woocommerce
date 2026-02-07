<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../scheduler.php';

class Dummy_Core_For_ActionArgs extends ASWC_Scheduler_Core {
    public $last_call = array();

    public function get_action_args( $date_type, $subscription ) {
        $this->last_call = array( $date_type, $subscription );
        return array( 'foo' => 'bar' );
    }
}

class SchedulerAPICoreArgsTest extends TestCase {
    protected $core;

    protected function setUp(): void {
        $this->core = new Dummy_Core_For_ActionArgs();
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'core' );
        $prop->setAccessible( true );
        $prop->setValue( null, $this->core );
    }

    protected function tearDown(): void {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        $prop = $ref->getProperty( 'core' );
        $prop->setAccessible( true );
        $prop->setValue( null, null );
    }

    public function test_get_action_args_delegates_to_core() {
        $subscription = new stdClass();
        $result       = ASWC_Scheduler_API::get_action_args( 'next_payment', $subscription );

        $this->assertSame( array( 'foo' => 'bar' ), $result );
        $this->assertSame( array( 'next_payment', $subscription ), $this->core->last_call );
    }
}
