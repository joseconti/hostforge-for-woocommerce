<?php
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
}
if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) { return $value; }
}

class AdminNoticeWrapperTest extends TestCase {
    /**
     * @runInSeparateProcess
     */
    public function test_returns_instance_when_class_exists() {
        require_once __DIR__ . '/../scheduler.php';

        if ( ! class_exists( 'WCS_Admin_Notice' ) ) {
            eval('class WCS_Admin_Notice { public $type; public function __construct( $type ) { $this->type = $type; } }');
        }

        $notice = aswc_create_admin_notice( 'error' );

        $this->assertInstanceOf( WCS_Admin_Notice::class, $notice );
        $this->assertSame( 'error', $notice->type );
    }

    /**
     * @runInSeparateProcess
     */
    public function test_returns_null_when_class_missing() {
        require_once __DIR__ . '/../scheduler.php';

        $this->assertNull( aswc_create_admin_notice( 'error' ) );
    }
}
