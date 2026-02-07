# TODO

This document summarizes the current progress for Advanced Subscriptions For WooCommerce.

## Completed

### Development environment
- Install PHPCS with WordPress Coding Standards.
- Configure PHPUnit for automated tests.

### Preliminary
- Review `scheduler-api` documentation and architecture.
- Explore `plugin-referencia` for API usage patterns.
- Map modules still relying on legacy scheduler logic.

### Integration
- Rename plugin to "Advanced Subscriptions For WooCommerce".
- Bootstrap Scheduler API from the plugin entry point.
- Alias old constants with `ASWC_` prefixed versions.
- Remove legacy constant aliases now that all references use `ASWC_` prefixes.
- Rename upgrade notice hook and callback to `aswc_` prefix.
- Migrate schedule helper and subscription event scheduler to `aswc_` prefix.
- Migrate internationalization class to `aswc_` prefix.
- Migrate log class to `aswc_` prefix.
- Migrate meta data helper to `aswc_` prefix.
- Rename `WPS_Subscription` class and references to `ASWC_Subscription`.
- Rename add new payment details template to `aswc` prefix.
- Rename show subscription details template to `aswc` prefix.
- Rename email template filenames and references to `aswc` prefix.
- Rename email class files and loader references to `aswc` prefix.
- Update text domain references to `advanced-subscriptions-for-woocommerce` across core loader, React onboarding components, email classes, templates, loader modules, payment integrations, REST API modules, main plugin file, and customer account templates.
- Replace direct Action Scheduler calls with scheduler API wrappers.
- Initialize failed action manager via `ASWC_Scheduler_API::init_failed_action_manager()`.
- Update documentation and assets with new plugin name.
- Remove PayPal-related code from the plugin.
- Rename admin notice script and related AJAX handler to `aswc` prefix.
- Rename remaining admin CSS assets from `wps` to `aswc` prefix.
- Rename public cancellation script asset and nonce to `aswc` prefix.
- Replace remaining asset filenames and handles from `wps` to `aswc`.
- Added `aswc_get_wp_timestamp` function with backward compatibility wrapper.
- Refactor REST API classes, functions, and routes to `aswc` prefix.
- Renamed public subscription price helper methods and meta data references to `aswc` prefix.
- Renamed Eway gateway class, methods, and file to `aswc` prefix.
- Renamed date helper functions to `aswc_get_wp_date` and `aswc_date` with backward-compatible wrappers.
- Replaced manual `strtotime` conversions with `ASWC_Scheduler_API::date_to_time` for subscription start dates.
- Updated internal references to use `aswc_date` and `aswc_get_timestamp` helpers.
- Renamed public-facing class and assets to `aswc` prefix.
- Renamed WooCommerce settings integration class and file to `ASWC_WC_Settings` and updated text domain.
- Renamed activation failure handlers and assets to `aswc_wsp` prefix.
- Integrate scheduler API with existing subscription features.
- Refactor plugin update scheduler to use Scheduler API instead of WP-Cron.
- Remove legacy scheduler logic after adopting the Scheduler API.
- Replace legacy prefixes with `aswc_` across admin settings, public scripts, React onboarding assets, and related CSS.
- Renamed loader bootstrap hooks, helpers, and option metadata to the `aswc_loader_` prefix and migrated stored activation state away from the `woocommerce-subscriptions-pro` key.【F:includes/loader.php†L26-L69】【F:includes/loader/includes/class-aswc-include.php†L51-L71】
- Replaced the loader-wide global bootstrap reference with the `aswc_loader_instance` filter so integrations can retrieve the instance without touching `$GLOBALS` and introduced a named WooCommerce session initializer callback.【F:includes/loader.php†L71-L116】
- Wrapped loader HPOS checks in `aswc_loader_is_hpos_enabled()` to avoid referencing `OrderUtil` when WooCommerce HPOS classes are unavailable.【F:includes/loader/includes/aswc-loader-common-functions.php†L17-L33】【F:includes/loader/admin/partials/class-aswc-loaderview-renewal-list.php†L66-L82】
- Renamed the shared loader helper include to `aswc-loader-common-functions.php` and updated translations that referenced the old path.【F:includes/loader/includes/class-aswc-include.php†L140-L156】【F:languages/advanced-subscriptions-for-woocommerce.pot†L1109-L2263】
- Removed loader email `phpcs:ignoreFile` directives after aligning each notification class with WordPress Coding Standards expectations.【F:includes/loader/emails/class-aswc-loaderpause-subscription-email.php†L1-L187】【F:includes/loader/emails/class-aswc-loaderplan-going-to-expire-email.php†L1-L188】【F:includes/loader/emails/class-aswc-loaderreactivate-subscription-email.php†L1-L187】【F:includes/loader/emails/class-aswc-loaderreminder-email.php†L1-L188】【F:includes/loader/emails/class-aswc-loaderrenewal-subscription-invoice-email.php†L1-L153】
- Updated loader documentation assets to reflect the Advanced Subscriptions branding and contribution guidelines.【F:includes/loader/README.md†L1-L8】

### Scheduler API module
- Refactor lifecycle event hooks to use `ASWC_Scheduler_API::lifecycle()`.
- Schedule subscription payments via `ASWC_Scheduler_API::schedule_payment()` and `ASWC_Scheduler_API::unschedule_payment()`.
- Wire customer notifications through `ASWC_Scheduler_API::notifications()`.
- Replace WP-Cron notification checks with background scheduler actions.
- Utilize background scheduler for log cleanup jobs.
- Review `plugin-referencia` implementation for scheduling flows.
- Replicate missing REST routes and hooks using `ASWC_Scheduler_API`.
- Sync scheduled action names and groups with the reference plugin.
- Begin PHPCS compliance in `admin/class-aswc-admin.php` (strict comparisons, escaping, translators comments, submenu globals).
- Removed per-function `@author` and `@link` annotations from inline documentation blocks.

### Versioning
- Normalized plugin version metadata and documentation to `1.0.0` across headers, translation catalogs, README, and inline docblocks.


### Documentation and messaging
- Replaced legacy plugin name references across README, scheduler API documentation, and loader admin messaging so everything consistently uses "Advanced Subscriptions For WooCommerce".
- Seeded `Docuements.md` with helper, action, filter, and workflow documentation to kick-start the internal knowledge base.
- Expanded the internal knowledge base with scheduler API services, loader HPOS helpers, logging utilities, REST endpoints, and onboarding guidance.【F:Docuements.md†L33-L106】
## Pending
- Audit plugin codebase for WordPress Coding Standards compliance (spacing, tabs, docblocks, inline comments); initial pass applied to retry helpers and bootstrap logic in `advanced-subscriptions-for-woocommerce.php`.
  - Remove `// phpcs:ignoreFile` directives by bringing files such as `includes/loader/public/class-aswc-loaderpublic.php` and the scheduler API classes back into compliance with escaping, sanitization, and documentation requirements.
  - Apply WPCS fixes to loader shared modules (e.g., `includes/loader/common/class-aswc-loadercommon.php`, `includes/loader/admin/class-aswc-loaderadmin.php`, and `includes/loader/includes/class-aswc-include.php`) so suppressed warnings can be removed alongside the public module cleanup.
- Normalize loader asset filenames and handles to the `aswc` prefix; legacy references (e.g., `includes/loader/public/js/woocommerce-subscriptions-pro-public.js`, matching CSS assets, and common loader resources) still enqueue files using the old plugin name.
- Regenerate loader language packs to drop `woocommerce-subscriptions-pro` text domain artifacts (see the `.po/.mo/.pot` files stored under `includes/loader/languages`) and align string references with `advanced-subscriptions-for-woocommerce`.
  - Seed the loader `advanced-subscriptions-for-woocommerce-en_US.po` file with the first batch of strings while keeping compiled `.mo` catalogues out of version control; follow-up tasks will extend the PO coverage.

