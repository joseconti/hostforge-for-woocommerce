<?php
use PHPUnit\Framework\TestCase;

class LegacyPrefixTest extends TestCase {
    public function test_scheduler_api_defines_no_wcs_prefix_symbols() {
        $root     = dirname( __DIR__ );
        $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root ) );
        $checked  = 0;

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

            $this->assertDoesNotMatchRegularExpression(
                '/\b(class|interface|trait)\s+WCS[A-Za-z0-9_]*/',
                $contents,
                "File {$path} contains legacy WCS type definition."
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\bfunction\s+wcs_[A-Za-z0-9_]*/i',
                $contents,
                "File {$path} contains legacy wcs_ function definition."
            );
            $this->assertDoesNotMatchRegularExpression(
                '/\bconst\s+WCS[A-Za-z0-9_]*\s*=/',
                $contents,
                "File {$path} contains legacy WCS constant."
            );
            $this->assertDoesNotMatchRegularExpression(
                '/define\(\s*[\'\"]WCS[A-Za-z0-9_]*[\'\"]/',
                $contents,
                "File {$path} defines a legacy WCS constant."
            );
            $checked++;
        }

        $this->assertGreaterThan( 0, $checked, 'No files were checked for legacy WCS prefixes.' );
    }
}
