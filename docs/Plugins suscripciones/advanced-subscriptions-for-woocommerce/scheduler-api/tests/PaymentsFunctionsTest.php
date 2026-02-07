<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../scheduler.php';

class PaymentsFunctionsTest extends TestCase {
    public function test_retry_rule_wrappers() {
        $rule = new class {
            public function get_retry_interval() { return 30; }
            public function get_raw_data() { return array( 'a' => 1 ); }
            public function get_status_to_apply( $object_key ) { return 'pending_' . $object_key; }
            public function has_email_template( $recipient = 'customer' ) { return 'customer' === $recipient; }
            public function get_email_template( $recipient = 'customer' ) { return $recipient . '_template'; }
        };

        $this->assertSame( 30, ASWC_Scheduler_API::get_retry_interval_from_rule( $rule ) );
        $this->assertSame( array( 'a' => 1 ), ASWC_Scheduler_API::get_retry_rule_raw_data( $rule ) );
        $this->assertSame( 'pending_order', ASWC_Scheduler_API::get_retry_rule_status_to_apply( $rule, 'order' ) );
        $this->assertTrue( ASWC_Scheduler_API::retry_rule_has_email_template( $rule ) );
        $this->assertSame( 'customer_template', ASWC_Scheduler_API::get_retry_rule_email_template( $rule ) );

        $this->assertSame( 0, ASWC_Scheduler_API::get_retry_interval_from_rule( new stdClass() ) );
        $this->assertSame( array(), ASWC_Scheduler_API::get_retry_rule_raw_data( new stdClass() ) );
        $this->assertSame( '', ASWC_Scheduler_API::get_retry_rule_status_to_apply( new stdClass(), 'order' ) );
        $this->assertFalse( ASWC_Scheduler_API::retry_rule_has_email_template( new stdClass() ) );
        $this->assertSame( '', ASWC_Scheduler_API::get_retry_rule_email_template( new stdClass() ) );
    }
}

