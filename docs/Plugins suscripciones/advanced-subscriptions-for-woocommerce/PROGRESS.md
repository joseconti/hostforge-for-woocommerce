# Progress

## Completed
- Install PHPCS with WordPress Coding Standards.
- Configure PHPUnit for automated tests.
- Review `scheduler-api` documentation and architecture.
- Explore `plugin-referencia` for API usage patterns.
- Rename plugin to "Advanced Subscriptions For WooCommerce".
- Bootstrap Scheduler API from plugin entry point.
- Alias old constants with `ASWC_` prefixed versions.
- Rename upgrade notice hook and callback to `aswc_` prefix.
- Migrate schedule helper and subscription event scheduler to `aswc_` prefix.
- Migrated internationalization class to `aswc_` prefix.
- Replace direct Action Scheduler calls with scheduler API wrappers.
- Update core loader and React onboarding components text domain references.
- Updated email classes and templates to use the `advanced-subscriptions-for-woocommerce` text domain.
- Updated public and REST API modules to use the `advanced-subscriptions-for-woocommerce` text domain.
- Updated admin modules to use the `advanced-subscriptions-for-woocommerce` text domain.
- Updated payment integration modules to use the `advanced-subscriptions-for-woocommerce` text domain.
- Initialize failed action manager via `ASWC_Scheduler_API::init_failed_action_manager()`.
- Map modules still relying on legacy scheduler logic.
- Removed legacy scheduler logic after adopting the Scheduler API.
- Updated loader components to use the `advanced-subscriptions-for-woocommerce` text domain.
- Updated main plugin file and customer account templates to use the `advanced-subscriptions-for-woocommerce` text domain.
- Updated documentation and assets with new plugin name.
- Removed legacy PayPal block support integration.
 - Removed remaining PayPal-related code from the plugin.
- Renamed admin notice script and related AJAX handler to use the `aswc` prefix.
- Renamed subscription class and references to use the ASWC_Subscription prefix.
- Renamed log class and references to use the ASWC_Log prefix.
- Refactored lifecycle event hooks to use `ASWC_Scheduler_API::lifecycle()`.
- Integrated payment scheduling by invoking `ASWC_Scheduler_API::schedule_payment()` and `ASWC_Scheduler_API::unschedule_payment()`.
- Renamed add new payment details template to use the `aswc` prefix.
- Renamed meta data helper to `aswc_get_meta_data` and updated references.
- Renamed email subscription details helper to `aswc_email_subscriptions_details` and updated templates.
- Renamed asset filenames and handles from `wps` to `aswc` in admin, public, and WCFM compatibility modules.
- Renamed email template filenames and references from `wps` to `aswc` prefix.
- Renamed cart item AJAX action and checkout block filter to use the `aswc` prefix.
- Renamed subscription details template to use the `aswc` prefix.
- Renamed auto-download helper file and replacement functions to use the `aswc` prefix.
- Renamed `aswc_allow_start_date_subscription` function and references to `aswc_allow_start_date_subscription`.
- Renamed remaining admin CSS assets from `wps` to `aswc` prefix.
- Renamed public subscription price helper methods and updated meta data calls to `aswc` prefix.
- Refactored My Account subscription templates to use `aswc_get_meta_data` helper.
- Renamed WooCommerce settings integration class and file to `ASWC_WC_Settings` and updated text domain.
- Wired customer notifications through `ASWC_Scheduler_API::notifications()`.
- Replaced loader REST API class and endpoint hook with `ASWC_Rest_Api` and `aswc_add_endpoint`.
- Reviewed `plugin-referencia` implementation for scheduling flows.
- Added REST endpoint for scheduled actions via `ASWC_Scheduler_API`.
- Synced scheduled action names and groups with the reference plugin.
 - Replaced residual legacy prefixes with `aswc_` across the codebase.
- Added payment gateway feature support check for subscriptions.

## Pending
- Replace all class, function and file prefixes with `aswc_`.
- Refactor remaining modules to use Scheduler API directly.
- Integrate scheduler API with existing subscription features.
- Utilize background scheduler for migration and cleanup jobs.
- Continue replacing any remaining asset filenames and handles from `wps` to `aswc`.

