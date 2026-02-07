<?php
use PHPUnit\Framework\TestCase;

class HookPrefixTest extends TestCase {
    public function test_no_obsolete_hook_prefix_remains() {
        $root      = dirname(__DIR__, 2);
        $iterator  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        $pattern   = 'woocommerce_scheduled_subscription_';
        $files_checked = 0;

        foreach ( $iterator as $file ) {
            if ( $file->isDir() ) {
                continue;
            }

            $path = $file->getPathname();

            if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) ) {
                continue;
            }

            if ( 'php' !== pathinfo( $path, PATHINFO_EXTENSION ) ) {
                continue;
            }

            if ( $path === __FILE__ ) {
                continue;
            }

            $contents = file_get_contents( $path );

            $this->assertStringNotContainsString(
                $pattern,
                $contents,
                "File {$path} contains obsolete hook prefix."
            );

            $files_checked++;
        }

        $this->assertGreaterThan( 0, $files_checked, 'No files were checked for obsolete hook names.' );
    }

    public function test_new_hook_prefix_exists() {
        $root     = dirname( __DIR__, 2 );
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root ) );
        $pattern  = 'advanced_scheduled_subscription_';

        $found = false;
        foreach ( $iterator as $file ) {
            if ( $file->isDir() ) {
                continue;
            }

            $path = $file->getPathname();

            if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) ) {
                continue;
            }

            if ( 'php' !== pathinfo( $path, PATHINFO_EXTENSION ) ) {
                continue;
            }

            $contents = file_get_contents( $path );
            if ( false !== strpos( $contents, $pattern ) ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue( $found, 'No advanced_scheduled_subscription_* hooks found in codebase.' );
    }
}
