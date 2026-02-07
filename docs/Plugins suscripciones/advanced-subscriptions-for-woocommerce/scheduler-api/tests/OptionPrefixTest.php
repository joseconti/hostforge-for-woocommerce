<?php
use PHPUnit\Framework\TestCase;

class OptionPrefixTest extends TestCase {
    public function test_scheduler_api_has_no_legacy_option_prefix() {
        $root      = dirname( __DIR__ );
        $iterator  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root ) );
        $legacy    = 'woocommerce_subscriptions';
        $files_checked = 0;

        foreach ( $iterator as $file ) {
            if ( $file->isDir() ) {
                continue;
            }

            $path = $file->getPathname();

            // Skip tests directory and non-PHP files.
            if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR ) ) {
                continue;
            }
            if ( 'php' !== pathinfo( $path, PATHINFO_EXTENSION ) ) {
                continue;
            }

            $contents = file_get_contents( $path );
            $this->assertStringNotContainsString(
                $legacy,
                $contents,
                "File {$path} contains legacy option prefix."
            );
            $files_checked++;
        }

        $this->assertGreaterThan( 0, $files_checked, 'No files were checked for legacy option prefix.' );
    }
}
