# Docuements Knowledge Base

## Purpose

This document centralizes the internal knowledge required to work with **Advanced Subscriptions for WooCommerce**. It must contain every helper function, action, filter, and workflow detail necessary to extend or maintain the plugin without having to inspect the entire codebase.

## How to update this document

1. Document every new helper, action, filter, or service that you introduce, modify, or deprecate.
2. Record the expected parameters, return values, and usage examples for each entry.
3. Link to the source file and relevant tests (if available) to accelerate discovery.
4. Update related sections immediately when behavior changes so this guide remains authoritative.

## Documentation template

For each entry include:

- **Component**: File or class where the functionality lives.
- **Function / Hook**: Name of the helper, method, action, or filter.
- **Purpose**: Clear description of what the functionality does.
- **Parameters**: List of arguments with their types, defaults, and sanitization requirements.
- **Returns**: Describe the return type and structure.
- **Usage**: Provide a code snippet or procedural steps that demonstrate how to call or hook into it.
- **Notes**: Mention edge cases, prerequisites, dependencies, or links to related helpers.

## Helper functions

| Component | Function | Purpose | Parameters | Returns | Usage | Notes |
|-----------|----------|---------|------------|---------|-------|-------|
| includes/aswc-common-functions.php | `aswc_get_wp_timestamp()` | Retrieve the current Unix timestamp using the Scheduler API when available. | None. | `int` Unix timestamp aligned with the site's timezone. | `$timestamp = aswc_get_wp_timestamp();` | Falls back to `current_time( 'timestamp' )` when the Scheduler API is not loaded so legacy flows continue to work.【F:includes/aswc-common-functions.php†L47-L63】 |
| includes/aswc-common-functions.php | `aswc_next_payment_date( $subscription_id, $current_time, $trial_end )` | Calculate and persist the next payment timestamp for a subscription. | `$subscription_id` (`int`) Subscription post ID.<br>`$current_time` (`int`) Base timestamp, typically `aswc_get_wp_timestamp()`.<br>`$trial_end` (`int`) Trial end timestamp; use `0` when no trial is configured. | `int` Timestamp for the next renewal charge. | `$next_payment = aswc_next_payment_date( $subscription_id, aswc_get_wp_timestamp(), $trial_end );` | Reads subscription and product metadata to determine billing interval, logs decisions to the WooCommerce logger, and delegates arithmetic to `aswc_susbcription_calculate_time()`.【F:includes/aswc-common-functions.php†L74-L161】 |
| advanced-subscriptions-for-woocommerce.php | `aswc_get_meta_data( $id, $key, $single )` | Retrieve order/subscription metadata from HPOS or CPT storage, returning core subscription status when requested. | `$id` (`int`) Order or subscription ID.<br>`$key` (`string`) Meta key (normalized internally).<br>`$single` (`bool`) Whether to return a single value. | `mixed` Stored meta value or status string. | `$status = aswc_get_meta_data( $subscription_id, 'aswc_subscription_status', true );` | When `$key` is `aswc_subscription_status`, the function reads the core order status instead of subscription meta to keep HPOS and CPT aligned.【F:advanced-subscriptions-for-woocommerce.php†L420-L495】 |
| advanced-subscriptions-for-woocommerce.php | `aswc_update_meta_data( $id, $key, $value )` | Persist subscription/order metadata for HPOS and CPT storage, using core order status as the single source of truth for subscription status updates. | `$id` (`int`) Order or subscription ID.<br>`$key` (`string`) Meta key (normalized internally).<br>`$value` (`mixed`) Value to store; sanitize before passing when user-supplied. | `void` | `aswc_update_meta_data( $subscription_id, 'aswc_subscription_status', 'paused' );` | When `$key` is `aswc_subscription_status`, the function updates the core order status and triggers `aswc_handle_subscription_status_change()` to keep scheduler state in sync.【F:advanced-subscriptions-for-woocommerce.php†L470-L560】 |
| advanced-subscriptions-for-woocommerce.php | `aswc_handle_subscription_status_change( $subscription_id, $new_status, $old_status = '' )` | Centralize side effects when a subscription core status changes (retry counters, scheduler updates). | `$subscription_id` (`int`) Subscription ID.<br>`$new_status` (`string`) New status slug (no `wc-` prefix).<br>`$old_status` (`string`) Previous status slug (optional). | `void` | `aswc_handle_subscription_status_change( $subscription_id, 'active', 'on-hold' );` | Used by status setters and admin changes to keep Action Scheduler events aligned with core status and to reset retry counters on activation.【F:advanced-subscriptions-for-woocommerce.php†L500-L560】 |
| advanced-subscriptions-for-woocommerce.php | `aswc_get_subscription_statuses_for_query()` | Build the list of subscription status slugs for querying HPOS records. | None. | `array` Subscription status slugs (unprefixed). | `$statuses = aswc_get_subscription_statuses_for_query();` | Applies the `aswc_status_array` filter and sanitizes values before use in `wc_get_orders()` queries.【F:advanced-subscriptions-for-woocommerce.php†L400-L445】 |
| advanced-subscriptions-for-woocommerce.php | `aswc_get_subscription_post_statuses_for_query()` | Build the list of prefixed subscription post statuses for CPT queries. | None. | `array` Subscription post statuses prefixed with `wc-`. | `$post_statuses = aswc_get_subscription_post_statuses_for_query();` | Uses `aswc_get_subscription_statuses_for_query()` and prefixes each status with `wc-` (except `trash`) for `get_posts()` queries.【F:advanced-subscriptions-for-woocommerce.php†L445-L470】 |
| includes/loader.php | `aswc_update_order_meta( $id, $key, $value )` | Update order or subscription metadata from loader contexts, delegating core status changes to `aswc_update_meta_data()`. | `$id` (`int`) Order or subscription ID.<br>`$key` (`string`) Meta key (normalized internally).<br>`$value` (`mixed`) Value to store; sanitize before passing when user-supplied. | `void` | `aswc_update_order_meta( $subscription_id, 'aswc_subscription_status', 'active' );` | When `$key` is `aswc_subscription_status`, the function routes the update through `aswc_update_meta_data()` so core status and scheduler state stay aligned.【F:includes/loader.php†L180-L250】 |
| includes/class-aswc-schedule-helper.php | `ASWC_Schedule_Helper::schedule_single( $subscription_id, $timestamp, $hook, $args = array(), $group = 'aswc' )` | Queue a single scheduler action tied to a subscription. | `$subscription_id` (`int`) Subscription identifier stored on the job.<br>`$timestamp` (`int`) Execution time in seconds since the epoch.<br>`$hook` (`string`) Action hook that will be triggered.<br>`$args` (`array`) Additional arguments passed to the scheduled callback.<br>`$group` (`string`) Optional scheduler group; defaults to `aswc`. | `int|false` Action ID on success or `false` when the Scheduler API is unavailable. | `ASWC_Schedule_Helper::schedule_single( $subscription_id, $timestamp, 'aswc_process_payment' );` | Ensures the subscription ID travels with the scheduled job and routes scheduling through `ASWC_Scheduler_API::schedule_action()` so WooCommerce Action Scheduler remains the single integration point.【F:includes/class-aswc-schedule-helper.php†L1-L37】 |
| includes/loader/includes/aswc-loader-common-functions.php | `aswc_loader_is_hpos_enabled()` | Determine whether the WooCommerce HPOS data store is available before running loader routines that depend on it. | None. | `bool` indicating if HPOS tables should be used. | `if ( aswc_loader_is_hpos_enabled() ) { /* interact with HPOS orders */ }` | Prevents fatal errors on stores where `Automattic\WooCommerce\Utilities\OrderUtil` is missing by checking class availability first.【F:includes/loader/includes/aswc-loader-common-functions.php†L15-L31】 |
| includes/loader/includes/aswc-loader-common-functions.php | `aswc_enable_shipping_on_subscription()` | Decide if subscription products should allow shipping charges based on plugin settings. | None. | `bool` true when shipping is enabled for subscription products. | `if ( aswc_enable_shipping_on_subscription() ) { /* allow shipping */ }` | Normalizes option values via `aswc_is_true()` when available so `yes`, `on`, and `1` are treated as enabled.【F:includes/loader/includes/aswc-loader-common-functions.php†L1613-L1627】 |

## Actions

| Hook name | Component | Purpose | Parameters | Notes |
|-----------|-----------|---------|------------|-------|
| `aswc_renewal_email_notification` | includes/aswc-common-functions.php | Fires after the default renewal notification is sent so additional emails can be dispatched. | `$order` (`WC_Order`) Renewal order instance.<br>`$mailer` (`array`) Loaded WooCommerce email classes. | Triggered from `aswc_send_email_for_renewal_susbcription()` and allows custom reminders or integrations to run alongside the core emails.【F:includes/aswc-common-functions.php†L360-L387】 |
| `woocommerce_order_status_changed` | includes/class-aswc.php | React to subscription order status changes and synchronize scheduler side effects for core status updates. | `$order_id` (`int`) Order ID.<br>`$old_status` (`string`) Previous status slug.<br>`$new_status` (`string`) New status slug.<br>`$order` (`WC_Order`) Order object. | Delegates to `aswc_handle_subscription_status_change()` so manual admin edits stay aligned with scheduler updates when subscription core status changes.【F:includes/class-aswc.php†L334-L441】 |

## Filters

| Filter name | Component | Purpose | Parameters | Notes |
|-------------|-----------|---------|------------|-------|
| `aswc_page_screen` | includes/aswc-common-functions.php | Adjust the list of admin screen IDs that belong to Advanced Subscriptions. | `$screen_ids` (`array`) Default IDs, including the top-level Jose Conti menu and the subscriptions submenu. | Use to register additional screens that should inherit plugin styles or scripts in the admin area.【F:includes/aswc-common-functions.php†L492-L520】 |
| `aswc_check_subscription_product_type` | includes/aswc-common-functions.php | Override whether a product or variation is treated as subscription content. | `$is_subscription` (`bool`) Detection result.<br>`$product` (`WC_Product`) Product object under evaluation. | Return `true` to flag custom product types or metadata as subscription-aware products.【F:includes/aswc-common-functions.php†L548-L593】 |
| `aswc_loader_instance` | includes/loader.php | Retrieve the loader bootstrap instance after it has been registered without touching global state. | `$value` (`mixed`) Default filter payload; ignored by the callback. | Exposes the instance created in `aswc_loader_run()` so third-party code can call loader helpers after bootstrap.【F:includes/loader.php†L86-L116】 |
| `aswc_modify_cart_item_data` | public/class-aswc-public.php | Allow adjustments to calculated recurring line totals before subscription summaries are rendered. | `$line_data` (`array`) Line totals and tax data, including `shipping_fee`.<br>`$cart_item` (`array`) Cart item data.<br>`$is_subscription` (`bool`) Whether the calculation is for a subscription context. | The loader public callback adds a `shipping_fee` when `aswc_allow_shipping_subscription` is enabled via `aswc_enable_shipping_on_subscription()`.【F:public/class-aswc-public.php†L1958-L1972】【F:includes/loader/public/class-aswc-loaderpublic.php†L2013-L2027】 |
| `wc_shipping_enabled` | includes/loader/public/class-aswc-loaderpublic.php | Control whether shipping is enabled on cart and checkout when the cart contains only subscription products. | `$enabled` (`bool`) Current shipping enabled state. | Honors WooCommerce shipping settings while allowing subscription-only carts to enable shipping based on `aswc_allow_shipping_subscription` and `aswc_allow_shipping_on_subscription_first_puchase`.【F:includes/loader/public/class-aswc-loaderpublic.php†L1143-L1189】 |
| `woocommerce_cart_needs_shipping` | includes/loader/public/class-aswc-loaderpublic.php | Ensure subscription carts that require shipping still show shipping methods when shipping is enabled in plugin settings. | `$needs_shipping` (`bool`) Whether the cart needs shipping.<br>`$cart` (`WC_Cart|null`) Current cart instance (optional in older filter signatures). | Forces `true` when a non-virtual subscription product is present and shipping is enabled via `aswc_allow_shipping_subscription` or `aswc_allow_shipping_on_subscription_first_puchase`. Falls back to `WC()->cart` if the cart argument is missing.【F:includes/loader/public/class-aswc-loaderpublic.php†L1192-L1234】 |

## Scheduler API services

| Component | Service | Purpose | Notes |
|-----------|---------|---------|-------|
| scheduler-api/scheduler.php | `ASWC_Scheduler_API::schedule_all( $subscription, $offset_cb = null, $date_types = null, $group = null )` | Queue lifecycle, payment, and customer notification actions for a subscription in a single call. | Delegates to the payments, lifecycle, and notifications facades so scheduled events stay in sync with subscription metadata.【F:scheduler-api/scheduler.php†L164-L183】 |
| scheduler-api/scheduler.php | `ASWC_Scheduler_API::unschedule_all( $subscription, $date_types = null, $group = null )` | Remove every queued action for a subscription when it is cancelled or fully processed. | Clears payment, lifecycle, and notification queues, defaulting to all registered date types when none are provided.【F:scheduler-api/scheduler.php†L185-L199】 |
| scheduler-api/scheduler.php | `ASWC_Scheduler_API::schedule_action()` / `schedule_recurring_action()` / `schedule_unique_action()` | Low-level wrappers around Action Scheduler primitives used throughout the plugin. | Each helper proxies to `ASWC_Scheduler_Core` so only the API facade touches the Action Scheduler dependency.【F:scheduler-api/scheduler.php†L213-L257】 |
| scheduler-api/scheduler.php | `ASWC_Scheduler_API::init_failed_action_manager( ?WC_Logger_Interface $logger = null )` | Bootstrap the failed action manager that monitors stuck scheduled events. | Lazily instantiates `ASWC_Scheduler_Failed_Action_Manager`, falling back to the core logger if none is supplied.【F:scheduler-api/scheduler.php†L83-L112】 |

## Logging utilities

| Component | Function | Purpose | Parameters | Notes |
|-----------|----------|---------|------------|-------|
| includes/class-aswc-log.php | `ASWC_Log::log( $message )` | Persist loader and subscription debug messages via the WooCommerce logging subsystem when logging is enabled. | `$message` (`string`) Text to record. | Respects the `aswc_logging` filter and the `aswc_enable_subscription_log` option before writing to the `aswc` log file.【F:includes/class-aswc-log.php†L21-L47】 |

## REST API endpoints

| Route | Callback | Purpose | Authentication |
|-------|----------|---------|----------------|
| `GET /aswc-route/v1/aswc-view-subscription/` | `ASWC_Rest_Api::aswc_view_subscription_callback()` | Return subscription details for the provided identifiers after passing the request through the API processor. | Requires a matching secret key supplied via `consumer_secret` and validated by `ASWC_Rest_Api::aswc_validate_secretkey()`.【F:package/rest-api/class-aswc-rest-api.php†L52-L113】 |
| `GET /aswc-route/v1/aswc-scheduled-actions/(?P<id>[\d]+)` | `ASWC_Rest_Api::aswc_get_scheduled_actions_callback()` | Fetch queued scheduler events for a subscription by ID so external systems can monitor renewal jobs. | Uses the same secret key workflow enforced by `aswc_subscription_permission_check()`.【F:package/rest-api/class-aswc-rest-api.php†L62-L89】 |

## Loader onboarding guidance

- Loader bootstrap now registers its instance through the `aswc_loader_instance` filter instead of the `$GLOBALS['aswc_obj']` global. Consumers should call `apply_filters( 'aswc_loader_instance', null )` after `plugins_loaded` to interact with loader services.【F:includes/loader.php†L86-L116】
- WooCommerce HPOS features must call `aswc_loader_is_hpos_enabled()` before referencing `OrderUtil` helpers to ensure compatibility with older WooCommerce releases.【F:includes/loader/includes/aswc-loader-common-functions.php†L15-L31】【F:includes/loader/public/class-aswc-loaderpublic.php†L1333-L1337】
- Loader asset handles and filenames have been normalized to the `aswc-loader-*` prefix across admin, public, and shared modules so integrations can enqueue styles and scripts without relying on legacy `woocommerce-subscriptions-pro` names.【F:includes/loader/admin/class-aswc-loaderadmin.php†L132-L201】【F:includes/loader/public/class-aswc-loaderpublic.php†L69-L131】【F:includes/loader/common/class-aswc-loadercommon.php†L70-L92】


## Workflows and processes

Use this section to describe multi-step processes such as subscription renewals, cancellation flows, scheduler integrations, or onboarding sequences. Provide flow diagrams or bullet lists that clarify the order of operations and the hooks involved.

### Subscription renewal scheduling

1. When an order activates subscriptions, `public/class-aswc-public.php` calls `do_action( 'aswc_after_created_subscription', $subscription_id );`, triggering the scheduler bootstrap for that record.【F:public/class-aswc-public.php†L1331-L1365】
2. `ASWC_Subscription_Event_Scheduler::init()` binds `schedule_events()` to that action, retrieves the subscription via `aswc_get_subscription()`, and schedules lifecycle, payment, and notification actions through the Scheduler API facade.【F:includes/class-aswc-subscription-event-scheduler.php†L16-L46】
3. The same class wires a cancellation hook so `aswc_subscription_cancel` clears pending lifecycle, payment, and notification jobs via the Scheduler API when a subscription is terminated.【F:includes/class-aswc-subscription-event-scheduler.php†L47-L66】
4. Renewal payment timestamps are calculated with `aswc_next_payment_date()`, which reads interval metadata, falls back to product-level settings, and stores the next charge time for subsequent scheduler runs.【F:includes/aswc-common-functions.php†L74-L161】【F:public/class-aswc-public.php†L1335-L1365】

## Pending documentation tasks

- Identify and document the helper that retrieves every subscription for a customer, including parameter expectations and return structure.
- Expand the knowledge base with onboarding guidance for the onboarding React experience once the UI overhaul lands.

