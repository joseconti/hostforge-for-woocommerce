<?php
use PHPUnit\Framework\TestCase;

if ( ! isset( $test_actions ) ) {
    $test_actions = array();
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        global $test_actions;
        $test_actions[] = $hook;
    }
}

require_once __DIR__ . '/../scheduler.php';

class Dummy_Payment_Scheduler_Actions_Wrapper {
    public $actions;
    public $last_group = null;
    public function __construct( $actions = array() ) { $this->actions = $actions; }
    public function get_scheduled_payment_actions( $subscription, $group = null ) { $this->last_group = $group; return $this->actions; }
    public function get_last_scheduled_payment_actions( $subscription, $group = null ) { $this->last_group = $group; return $this->actions; }
}

class Dummy_Lifecycle_Scheduler_Actions_Wrapper {
    public $actions;
    public $last_group = null;
    public function __construct( $actions = array() ) { $this->actions = $actions; }
    public function get_scheduled_actions( $subscription, $group = null ) { $this->last_group = $group; return $this->actions; }
    public function get_last_scheduled_actions( $subscription, $group = null ) { $this->last_group = $group; return $this->actions; }
}

class Dummy_Notification_Scheduler_Actions_Wrapper {
    public $actions;
    public $last_group = null;
    public function __construct( $actions = array() ) { $this->actions = $actions; }
    public function get_last_scheduled_notification_actions( $subscription, $date_types = array(), $group = null ) { $this->last_group = $group; return $this->actions; }
    public function get_scheduled_notification_actions( $subscription, $date_types = array(), $group = null ) { $this->last_group = $group; return $this->actions; }
}

class ASWCActionSchedulerAggregatorsTest extends TestCase {
    protected function setUp(): void { $this->reset_api(); }
    protected function tearDown(): void { $this->reset_api(); }

    protected function reset_api() {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        foreach ( array( 'payments', 'lifecycle', 'notifications' ) as $prop ) {
            $p = $ref->getProperty( $prop );
            $p->setAccessible( true );
            $p->setValue( null, null );
        }
    }

    protected function inject_modules( $payment, $lifecycle, $notification ) {
        $ref = new ReflectionClass( 'ASWC_Scheduler_API' );
        $p = $ref->getProperty( 'payments' );
        $p->setAccessible( true );
        $p->setValue( null, $payment );
        $p = $ref->getProperty( 'lifecycle' );
        $p->setAccessible( true );
        $p->setValue( null, $lifecycle );
        $p = $ref->getProperty( 'notifications' );
        $p->setAccessible( true );
        $p->setValue( null, $notification );
    }

    public function test_get_scheduled_subscription_actions_combines_modules_and_forwards_group() {
        $payment      = new Dummy_Payment_Scheduler_Actions_Wrapper( array( 'next_payment' => 'p' ) );
        $lifecycle    = new Dummy_Lifecycle_Scheduler_Actions_Wrapper( array( 'expiration' => 'e' ) );
        $notification = new Dummy_Notification_Scheduler_Actions_Wrapper();
        $this->inject_modules( $payment, $lifecycle, $notification );
        $subscription = new stdClass();
        $result       = ASWC_Scheduler_API::get_scheduled_subscription_actions( $subscription, 'group-a' );
        $this->assertSame( array( 'next_payment' => 'p', 'expiration' => 'e' ), $result );
        $this->assertSame( 'group-a', $payment->last_group );
        $this->assertSame( 'group-a', $lifecycle->last_group );
    }

    public function test_get_all_last_scheduled_subscription_actions_includes_notifications() {
        $payment      = new Dummy_Payment_Scheduler_Actions_Wrapper( array( 'next_payment' => 'p2' ) );
        $lifecycle    = new Dummy_Lifecycle_Scheduler_Actions_Wrapper( array( 'expiration' => 'e2' ) );
        $notification = new Dummy_Notification_Scheduler_Actions_Wrapper( array( 'trial_end' => 'n' ) );
        $this->inject_modules( $payment, $lifecycle, $notification );
        $subscription = new stdClass();
        $result       = ASWC_Scheduler_API::get_all_last_scheduled_subscription_actions( $subscription, array( 'trial_end' ), 'group-b' );
        $this->assertSame( array( 'next_payment' => 'p2', 'expiration' => 'e2', 'trial_end' => 'n' ), $result );
        $this->assertSame( 'group-b', $payment->last_group );
        $this->assertSame( 'group-b', $lifecycle->last_group );
        $this->assertSame( 'group-b', $notification->last_group );
    }
}
