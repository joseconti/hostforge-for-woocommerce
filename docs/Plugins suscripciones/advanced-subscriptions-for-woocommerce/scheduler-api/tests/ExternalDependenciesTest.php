<?php
use PHPUnit\Framework\TestCase;

class ExternalDependenciesTest extends TestCase {

    private function assertNotMatching( string $pattern, string $subject, string $message ): void {
        $this->assertSame( 0, preg_match( $pattern, $subject ), $message );
    }

    public function test_no_direct_plugin_usage() {
        $directory = dirname(__DIR__);
        $iterator  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        $forbidden_functions = '/\bwcs_[A-Za-z0-9_]*\s*\(/';
        $forbidden_classes   = '/\b(?:WC_Subscriptions_[A-Za-z0-9_]+|WC_Subscriptions)::[A-Za-z0-9_]+\s*\(/';
        $forbidden_wcs       = '/(?:\bWCS_[A-Za-z0-9_]+\s*\(|\bWCS_[A-Za-z0-9_]+::|new\s+WCS_[A-Za-z0-9_]+)/';
        $forbidden_as        = '/\bas_(?!wc)[A-Za-z0-9_]*\s*\(/';
        $forbidden_as_classes = '/(?:\bActionScheduler_[A-Za-z0-9_]+::|new\s+ActionScheduler_[A-Za-z0-9_]+)/';
        $forbidden_as_methods = [
            'get_schedule',
            'get_status',
            'get_hook',
            'get_args',
            'get_group',
            'get_priority',
            'get_attempts',
            'get_claim_id',
            'get_post_id',
            'get_user_id',
            'get_recurrence',
            'is_recurring',
            'is_finished',
            'set_schedule',
            'set_status',
            'set_hook',
            'set_args',
            'set_group',
            'set_priority',
            'set_attempts',
            'set_claim_id',
            'set_post_id',
            'set_user_id',
            'cancel_action',
            'delete_action',
            'save_action',
        ];
        $forbidden_action_meta = '/\$[A-Za-z0-9_]*action[A-Za-z0-9_]*->get_meta\s*\(/i';
        $forbidden_action_save_meta = '/\$[A-Za-z0-9_]*action[A-Za-z0-9_]*->save_meta\s*\(/i';
        $forbidden_action_delete_meta = '/\$[A-Za-z0-9_]*action[A-Za-z0-9_]*->delete_meta\s*\(/i';
        $forbidden_action_id = '/\$[A-Za-z0-9_]*action[A-Za-z0-9_]*->get_id\s*\(/i';
        $forbidden_schedule_date = '/\$[A-Za-z0-9_]*schedule[A-Za-z0-9_]*->get_date(_gmt)?\s*\(/i';
        $forbidden_schedule_next = '/\$[A-Za-z0-9_]*schedule[A-Za-z0-9_]*->next\s*\(/i';
        $forbidden_retry_interval = '/->get_retry_interval\s*\(/';
        $forbidden_retry_raw      = '/->get_raw_data\s*\(/';
        $forbidden_retry_status   = '/->get_status_to_apply\s*\(/';
        $forbidden_retry_has_template = '/->has_email_template\s*\(/';
        $forbidden_retry_get_template = '/->get_email_template\s*\(/';
        $forbidden_wc_logger      = '/\bwc_get_logger\s*\(/';
        $forbidden_wc_get_order   = '/\bwc_get_order\s*\(/';
        $forbidden_wc_get_payment_gateway = '/\bwc_get_payment_gateway_by_order\s*\(/';
        $forbidden_wc_get_container = '/\bwc_get_container\s*\(/';
        $forbidden_data_synchronizer = '/Automattic\\\\WooCommerce\\\\Internal\\\\DataStores\\\\Orders\\\\DataSynchronizer/';
        $forbidden_wc_subscription_static = '/\bWC_Subscription::/';
        $forbidden_wc_subscription_new    = '/new\s*WC_Subscription\b/';

        $files_checked = 0;

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $path = $file->getPathname();

            if ('php' !== pathinfo($path, PATHINFO_EXTENSION)) {
                continue;
            }

            // Skip tests, documentation and wrapper definitions.
            if (false !== strpos($path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)) {
                continue;
            }
            if ('README.md' === basename($path)) {
                continue;
            }
            if (preg_match('/aswc-[a-z-]+-functions\.php$/', $path)) {
                continue;
            }

            $contents = php_strip_whitespace($path);

            $this->assertNotMatching(
                $forbidden_functions,
                $contents,
                "File {$path} calls a wcs_* function directly."
            );

            $this->assertNotMatching(
                $forbidden_classes,
                $contents,
                "File {$path} references a WC_Subscriptions_* method directly."
            );
            $this->assertNotMatching(
                $forbidden_wcs,
                $contents,
                "File {$path} references a WCS_* class or function directly."
            );

            $this->assertNotMatching(
                $forbidden_as,
                $contents,
                "File {$path} calls an Action Scheduler function directly."
            );
            $this->assertNotMatching(
                $forbidden_as_classes,
                $contents,
                "File {$path} references an Action Scheduler class directly."
            );
            $this->assertStringNotContainsString(
                '\\ActionScheduler\\',
                $contents,
                "File {$path} references a namespaced Action Scheduler class directly."
            );
            $this->assertStringNotContainsString(
                '\\Automattic\\WooCommerce\\ActionScheduler\\',
                $contents,
                "File {$path} references a namespaced Action Scheduler class directly."
            );
            foreach ( $forbidden_as_methods as $method ) {
                $this->assertNotMatching(
                    '/->' . $method . '\s*\(/',
                    $contents,
                    "File {$path} calls {$method}() on an action directly."
                );
            }
            $this->assertNotMatching(
                $forbidden_action_id,
                $contents,
                "File {$path} calls get_id() on an action directly."
            );
            $this->assertNotMatching(
                $forbidden_action_meta,
                $contents,
                "File {$path} calls get_meta() on an action directly."
            );
            $this->assertNotMatching(
                $forbidden_action_save_meta,
                $contents,
                "File {$path} calls save_meta() on an action directly."
            );
            $this->assertNotMatching(
                $forbidden_action_delete_meta,
                $contents,
                "File {$path} calls delete_meta() on an action directly."
            );
            $this->assertNotMatching(
                $forbidden_schedule_date,
                $contents,
                "File {$path} calls get_date() on a schedule directly."
            );
            $this->assertNotMatching(
                $forbidden_schedule_next,
                $contents,
                "File {$path} calls next() on a schedule directly."
            );
            $this->assertNotMatching(
                $forbidden_retry_interval,
                $contents,
                "File {$path} calls get_retry_interval() on a rule directly."
            );
            $this->assertNotMatching(
                $forbidden_retry_raw,
                $contents,
                "File {$path} calls get_raw_data() on a rule directly."
            );
            $this->assertNotMatching(
                $forbidden_retry_status,
                $contents,
                "File {$path} calls get_status_to_apply() on a rule directly."
            );

            $this->assertNotMatching(
                $forbidden_retry_has_template,
                $contents,
                "File {$path} calls has_email_template() on a rule directly."
            );

            $this->assertNotMatching(
                $forbidden_retry_get_template,
                $contents,
                "File {$path} calls get_email_template() on a rule directly."
            );

              $this->assertNotMatching(
                  $forbidden_wc_logger,
                  $contents,
                  "File {$path} calls wc_get_logger() directly."
              );
            $this->assertNotMatching(
                $forbidden_wc_get_order,
                $contents,
                "File {$path} calls wc_get_order() directly."
            );
            $this->assertNotMatching(
                $forbidden_wc_get_payment_gateway,
                $contents,
                "File {$path} calls wc_get_payment_gateway_by_order() directly."
            );

            $this->assertNotMatching(
                $forbidden_wc_get_container,
                $contents,
                "File {$path} calls wc_get_container() directly."
            );
            $this->assertNotMatching(
                $forbidden_data_synchronizer,
                $contents,
                "File {$path} references DataSynchronizer directly."
            );
            $this->assertNotMatching(
                $forbidden_wc_subscription_static,
                $contents,
                "File {$path} calls WC_Subscription statically."
            );
            $this->assertNotMatching(
                $forbidden_wc_subscription_new,
                $contents,
                "File {$path} instantiates WC_Subscription directly."
            );

            $files_checked++;
        }

        $this->assertGreaterThan(0, $files_checked, 'No files were checked for external dependencies.');
    }
}
