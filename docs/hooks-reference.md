# HostForge for WooCommerce — Hooks Reference

**Complete reference of all `do_action()` and `apply_filters()` hooks available for developers.**

Version 1.0.0 | February 2026

---

## Table of Contents

- [Overview](#overview)
- [Core — Plugin Lifecycle](#core--plugin-lifecycle)
- [Core — Module Manager](#core--module-manager)
- [Core — Admin](#core--admin)
- [Core — Settings](#core--settings)
- [Core — Products & Checkout](#core--products--checkout)
- [Core — Templates & Utilities](#core--templates--utilities)
- [Server Manager — Module](#server-manager--module)
- [Server Manager — cPanel Provider](#server-manager--cpanel-provider)
- [Server Manager — Plesk Provider](#server-manager--plesk-provider)
- [Server Manager — Admin](#server-manager--admin)
- [Server Manager — REST API](#server-manager--rest-api)
- [Auto Provisioning — Engine](#auto-provisioning--engine)
- [Auto Provisioning — Server Selector](#auto-provisioning--server-selector)
- [Auto Provisioning — Generators](#auto-provisioning--generators)
- [Auto Provisioning — Frontend](#auto-provisioning--frontend)
- [Auto Provisioning — Admin](#auto-provisioning--admin)
- [Auto Provisioning — REST API](#auto-provisioning--rest-api)
- [Support Desk — Module](#support-desk--module)
- [Support Desk — Frontend](#support-desk--frontend)
- [Support Desk — Admin](#support-desk--admin)
- [Support Desk — REST API](#support-desk--rest-api)
- [Domain Manager — Module](#domain-manager--module)
- [Domain Manager — Engine](#domain-manager--engine)
- [Domain Manager — Search](#domain-manager--search)
- [Domain Manager — Checkout](#domain-manager--checkout)
- [Domain Manager — Frontend](#domain-manager--frontend)
- [Domain Manager — Admin](#domain-manager--admin)
- [Domain Manager — Namecheap Registrar](#domain-manager--namecheap-registrar)
- [Domain Manager — REST API](#domain-manager--rest-api)
- [Security — Module](#security--module)
- [Security — Brute Force Protection](#security--brute-force-protection)
- [Security — IP Manager](#security--ip-manager)
- [Security — CAPTCHA](#security--captcha)
- [Security — Fraud Detection](#security--fraud-detection)
- [Security — Audit Log](#security--audit-log)
- [Security — REST API](#security--rest-api)
- [Notifications — Module](#notifications--module)
- [Notifications — Merge Tags](#notifications--merge-tags)
- [Notifications — Email Classes](#notifications--email-classes)
- [Reports — Module](#reports--module)
- [Reports — Data Provider](#reports--data-provider)
- [Reports — CSV Exporter](#reports--csv-exporter)
- [Reports — REST API](#reports--rest-api)

---

## Overview

HostForge provides **47 action hooks** and **80+ unique filter hooks** across all modules. All hooks use the `hostforge_` prefix.

### Naming Convention

```
hostforge_{module}_{action}          — Actions
hostforge_{module}_{data_type}       — Filters
hostforge_rest_{module}_response     — REST API response filters
hostforge_email_{type}_{property}    — Email notification filters
```

### How to Use

```php
// Actions — run code when something happens
add_action( 'hostforge_after_provision', function( int $service_id, array $account_data ) {
    // Send a Slack notification, update external CRM, etc.
}, 10, 2 );

// Filters — modify data before it's used
add_filter( 'hostforge_generated_username', function( string $username, string $domain ) {
    return 'custom_' . $username;
}, 10, 2 );
```

---

## Core — Plugin Lifecycle

**File:** `includes/class-hostforge.php`

### `hostforge_before_init` *(action)*

Fires before HostForge begins initialization.

```php
do_action( 'hostforge_before_init' );
```

### `hostforge_loaded` *(action)*

Fires after HostForge has fully initialized.

```php
do_action( 'hostforge_loaded' );
```

### `hostforge_helpers_loaded` *(action)*

Fires after helper function files have been loaded.

```php
do_action( 'hostforge_helpers_loaded' );
```

### `hostforge_product_types_initialized` *(action)*

Fires after all HostForge product types and related components are initialized.

```php
do_action( 'hostforge_product_types_initialized' );
```

### `hostforge_modules_loaded` *(action)*

Fires after all active modules have been loaded.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$module_manager` | `HF_Module_Manager` | The module manager instance |

```php
do_action( 'hostforge_modules_loaded', $module_manager );
```

---

## Core — Module Manager

**File:** `includes/class-hf-module-manager.php`

### `hostforge_registered_modules` *(filter)*

Filters the list of registered modules.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$modules` | `array` | Associative array of module_id => class_name |

```php
$modules = apply_filters( 'hostforge_registered_modules', $modules );
```

### `hostforge_active_module_ids` *(filter)*

Filters the list of active module IDs.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$active` | `array` | Array of active module ID strings |

```php
$active = apply_filters( 'hostforge_active_module_ids', $active );
```

### `hostforge_before_module_activate` *(action)*

Fires before a module is activated.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$module_id` | `string` | The module identifier |
| `$class_name` | `string` | Fully-qualified module class name |

```php
do_action( 'hostforge_before_module_activate', $module_id, $class_name );
```

### `hostforge_module_activated` *(action)*

Fires when a module is activated.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$module_id` | `string` | The module identifier |

### `hostforge_before_module_deactivate` *(action)*

Fires before a module is deactivated.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$module_id` | `string` | The module identifier |

### `hostforge_module_deactivated` *(action)*

Fires when a module is deactivated.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$module_id` | `string` | The module identifier |

### `hostforge_module_loaded` *(action)*

Fires after a module has been loaded and initialized.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$module_id` | `string` | The module identifier |
| `$module` | `HF_Module` | The loaded module instance |

---

## Core — Admin

**File:** `includes/admin/class-hf-admin.php`

### `hostforge_admin_menus_registered` *(action)*

Fires after all HostForge admin menus have been registered.

```php
do_action( 'hostforge_admin_menus_registered' );
```

### `hostforge_admin_assets` *(action)*

Fires after HostForge admin assets have been enqueued.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$hook_suffix` | `string` | Current admin page hook suffix |

### `hostforge_admin_dashboard_data` *(filter)*

Filters the data passed to the admin dashboard template.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `array` | Dashboard template data |

### `hostforge_dashboard_widgets` *(action)*

**File:** `templates/admin/dashboard.php`

Fires after the default dashboard widgets. Modules can add their own widgets here.

```php
do_action( 'hostforge_dashboard_widgets' );
```

---

## Core — Settings

**File:** `includes/admin/class-hf-settings.php`

### `hostforge_settings_fields` *(action)*

Fires after core settings fields are registered.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$option_group` | `string` | The settings option group name (`hf_settings`) |

### `hostforge_settings_saved` *(action)*

Fires after a HostForge setting has been saved.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$option` | `string` | The option name that was updated |
| `$new_value` | `mixed` | The new value |
| `$old_value` | `mixed` | The previous value |

**File:** `includes/admin/class-hf-log-viewer.php`

### `hostforge_log_retention_days` *(filter)*

Filters the number of days to retain log entries.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$retention_days` | `int` | Days to keep logs (default: from settings) |

---

## Core — Products & Checkout

**File:** `includes/products/class-hf-checkout-fields.php`

### `hostforge_checkout_fields` *(filter)*

Filters the checkout fields for HostForge products.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fields` | `array` | Checkout fields array |
| `$types_in_cart` | `array` | Product types in cart |

### `hostforge_checkout_field_validation` *(filter)*

Filters the validation result for a single checkout field.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$is_valid` | `bool` | Whether the field value is valid |
| `$field` | `array` | The field definition array |
| `$value` | `string` | The submitted field value |

### `hostforge_checkout_meta_saved` *(action)*

Fires after checkout field meta has been saved to the order.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$order` | `WC_Order` | The WooCommerce order object |
| `$fields` | `array` | The checkout fields processed |
| `$data` | `array` | The posted checkout data |

**File:** `includes/products/class-hf-order-meta-handler.php`

### `hostforge_order_meta_keys` *(filter)*

Filters hidden order meta keys displayed in the admin.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$hidden_meta` | `array` | Array of meta key strings |

### `hostforge_order_hosting_meta` *(filter)*

Filters hosting meta fields displayed on the order.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$hosting_fields` | `array` | Hosting meta key-value pairs |
| `$order` | `WC_Order` | The order object |

**File:** `includes/products/class-hf-product-addons.php`

### `hostforge_product_addons` *(filter)*

Filters the add-ons available for a product.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$addons` | `array` | Array of addon definitions |
| `$product` | `WC_Product` | The product object |

### `hostforge_addon_cart_data` *(filter)*

Filters the selected add-ons data before adding to cart.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$selected_addons` | `array` | Selected addons array |
| `$product_id` | `int` | Product ID |
| `$cart_item_data` | `array` | Cart item data |

### `hostforge_addon_price` *(filter)*

Filters the price of an individual add-on.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$addon_price` | `float` | The addon price |
| `$addon` | `array` | The addon definition |
| `$cart_item` | `array` | Cart item data |
| `$product` | `WC_Product` | The product |

---

## Core — Templates & Utilities

**File:** `includes/helpers/hf-template-functions.php`

### `hostforge_template_path` *(filter)*

Filters the resolved template file path.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$template` | `string` | Resolved template path |
| `$template_name` | `string` | Template name |
| `$args` | `array` | Template arguments |

**File:** `includes/class-hf-encryption.php`

### `hostforge_encryption_method` *(filter)*

Filters the encryption cipher method.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$cipher` | `string` | Cipher method (default: `AES-256-CBC`) |

**File:** `includes/subscriptions/class-hf-subscription-factory.php`

### `hostforge_subscription_adapters` *(filter)*

Filters the list of available subscription adapters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$adapters` | `array` | Array of class_name => condition pairs |

---

## Server Manager — Module

**File:** `modules/server-manager/class-hf-server-manager-module.php`

### `hostforge_server_groups` *(filter)*

Filters the server groups taxonomy arguments.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$args` | `array` | Taxonomy registration arguments |

### `hostforge_server_health_data` *(filter)*

Filters server health check data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `array` | Health check data |
| `$server_id` | `int` | Server post ID |

### `hostforge_server_connected` *(action)*

Fires when a server health check succeeds.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$server_id` | `int` | Server post ID |

### `hostforge_server_connection_failed` *(action)*

Fires when a server health check fails.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$server_id` | `int` | Server post ID |
| `$error` | `string` | Error message |

---

## Server Manager — cPanel Provider

**File:** `modules/server-manager/providers/class-hf-cpanel-provider.php`

### `hostforge_cpanel_api_response` *(filter)*

Filters the raw cPanel/WHM API response.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | `array` | API response data |
| `$function` | `string` | API function called |
| `$params` | `array` | API parameters |
| `$server_id` | `int` | Server post ID |

### `hostforge_cpanel_create_params` *(filter)*

Filters account creation parameters before sending to cPanel.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$api_params` | `array` | Account creation parameters |
| `$params` | `array` | Original parameters |
| `$server_id` | `int` | Server post ID |

### `hostforge_cpanel_suspend_params` *(filter)*

Filters account suspension parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Suspension parameters |
| `$username` | `string` | Account username |
| `$server_id` | `int` | Server post ID |

### `hostforge_cpanel_unsuspend_params` *(filter)*

Filters account unsuspension parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Unsuspension parameters |
| `$username` | `string` | Account username |
| `$server_id` | `int` | Server post ID |

### `hostforge_cpanel_terminate_params` *(filter)*

Filters account termination parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Termination parameters |
| `$username` | `string` | Account username |
| `$server_id` | `int` | Server post ID |

### `hostforge_cpanel_change_package_params` *(filter)*

Filters package change parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Package change parameters |
| `$username` | `string` | Account username |
| `$plan` | `string` | New package name |
| `$server_id` | `int` | Server post ID |

---

## Server Manager — Plesk Provider

**File:** `modules/server-manager/providers/class-hf-plesk-provider.php`

### `hostforge_plesk_api_response` *(filter)*

Filters the raw Plesk API response.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | `array` | API response |
| `$xml` | `string` | XML request |
| `$server_id` | `int` | Server post ID |

### `hostforge_plesk_create_xml` *(filter)*

Filters the Plesk webspace creation XML.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$webspace_xml` | `string` | XML string |
| `$params` | `array` | Creation parameters |
| `$server_id` | `int` | Server post ID |

### `hostforge_plesk_suspend_params` *(filter)*

Filters Plesk suspension parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Suspension parameters |
| `$username` | `string` | Account username |
| `$server_id` | `int` | Server post ID |

### `hostforge_plesk_unsuspend_params` *(filter)*

Filters Plesk unsuspension parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Unsuspension parameters |
| `$username` | `string` | Account username |
| `$server_id` | `int` | Server post ID |

### `hostforge_plesk_terminate_params` *(filter)*

Filters Plesk termination parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Termination parameters |
| `$username` | `string` | Account username |
| `$server_id` | `int` | Server post ID |

### `hostforge_plesk_change_package_params` *(filter)*

Filters Plesk package change parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Package change parameters |
| `$username` | `string` | Account username |
| `$plan` | `string` | New plan name |
| `$server_id` | `int` | Server post ID |

---

## Server Manager — Admin

**File:** `modules/server-manager/admin/class-hf-server-admin.php`

### `hostforge_server_saved` *(action)*

Fires after a server is saved (created or updated) via admin.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$server_id` | `int` | Server post ID |
| `$panel_type` | `string` | Panel type (cpanel, plesk) |
| `$hostname` | `string` | Server hostname |

### `hostforge_server_deleted` *(action)*

Fires after a server is deleted via admin.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$server_id` | `int` | The deleted server post ID |
| `$server_name` | `string` | The deleted server's title |

### `hostforge_server_test_result` *(filter)*

Filters the server connection test result.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | `array` | Test result with success/message |
| `$server_id` | `int` | Server post ID |

**File:** `modules/server-manager/admin/class-hf-server-list-table.php`

### `hostforge_server_admin_columns` *(filter)*

Filters the columns displayed in the servers list table.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$columns` | `array` | Column slug => label pairs |

---

## Server Manager — REST API

**File:** `modules/server-manager/api/class-hf-rest-server-controller.php`

### `hostforge_rest_server_query_args` *(filter)*

Filters the WP_Query arguments for listing servers.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$args` | `array` | WP_Query arguments |
| `$request` | `WP_REST_Request` | Request object |

### `hostforge_rest_server_response` *(filter)*

Filters a single server's REST API response data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `array` | Server response data |
| `$post` | `WP_Post` | Server post object |

---

## Auto Provisioning — Engine

**File:** `modules/auto-provisioning/class-hf-provisioning-engine.php`

### `hostforge_before_provision` *(action)*

Fires before a service is provisioned.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_id` | `int` | Service post ID |
| `$order_id` | `int` | WooCommerce order ID |

### `hostforge_provision_account_data` *(filter)*

Filters account data before storing it on the service.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$account_data` | `array` | Account data from the provider |
| `$service_id` | `int` | Service post ID |
| `$server_id` | `int` | Server post ID |

### `hostforge_after_provision` *(action)*

Fires after a service is successfully provisioned.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_id` | `int` | Service post ID |
| `$account_data` | `array` | Account data from the provider |

### `hostforge_before_suspend` *(action)*

Fires before a service is suspended.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_id` | `int` | Service post ID |
| `$reason` | `string` | Suspension reason |

### `hostforge_after_suspend` *(action)*

Fires after a service is suspended on the server.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_id` | `int` | Service post ID |

### `hostforge_before_unsuspend` *(action)*

Fires before a service is unsuspended.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_id` | `int` | Service post ID |

### `hostforge_after_unsuspend` *(action)*

Fires after a service is unsuspended.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_id` | `int` | Service post ID |

### `hostforge_before_terminate` *(action)*

Fires before a service is terminated.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_id` | `int` | Service post ID |

### `hostforge_after_terminate` *(action)*

Fires after a service is terminated.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_id` | `int` | Service post ID |

### `hostforge_provision_failed` *(action)*

Fires when provisioning permanently fails.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_id` | `int` | Service post ID |
| `$error` | `string` | Error message |

### `hostforge_provisioning_product_types` *(filter)*

Filters the list of product types that trigger provisioning.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$types` | `array` | Array of product type slugs |

---

## Auto Provisioning — Server Selector

**File:** `modules/auto-provisioning/class-hf-server-selector.php`

### `hostforge_server_selector_query` *(filter)*

Filters the WP_Query args for finding available servers.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$args` | `array` | WP_Query arguments |
| `$product` | `WC_Product` | The product being provisioned |
| `$server_group` | `string` | Server group slug |

### `hostforge_select_server` *(filter)*

Filters the selected server before provisioning.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$best_server` | `int\|null` | Selected server post ID |
| `$product_id` | `int` | Product ID |
| `$server_group` | `string` | Server group slug |

---

## Auto Provisioning — Generators

**File:** `modules/auto-provisioning/class-hf-username-generator.php`

### `hostforge_generated_username` *(filter)*

Filters the generated username before uniqueness check.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$username` | `string` | Generated username base |
| `$domain` | `string` | Domain name |
| `$user_id` | `int` | User ID |

**File:** `modules/auto-provisioning/class-hf-password-generator.php`

### `hostforge_generated_password` *(filter)*

Filters the generated password.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$password` | `string` | Generated password |
| `$service_id` | `int` | Service post ID |

---

## Auto Provisioning — Frontend

**File:** `modules/auto-provisioning/class-hf-service-frontend.php`

### `hostforge_service_list_query` *(filter)*

Filters the query for listing user's services.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query_args` | `array` | WP_Query arguments |
| `$user_id` | `int` | Current user ID |

### `hostforge_service_detail_data` *(filter)*

Filters the service detail data shown on the frontend.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$meta` | `array` | Service meta data |
| `$service_id` | `int` | Service post ID |
| `$service` | `WP_Post` | Service post object |

### `hostforge_service_actions` *(filter)*

Filters the actions available for a service on the frontend.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$service_actions` | `array` | Array of action definitions |
| `$service_id` | `int` | Service post ID |
| `$status` | `string` | Current service status |
| `$meta` | `array` | Service meta |

### `hostforge_service_sso_url` *(filter)*

Filters the SSO URL before redirecting the user.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$sso_url` | `string` | SSO URL |
| `$service_id` | `int` | Service post ID |
| `$username` | `string` | Service username |
| `$user_id` | `int` | WordPress user ID |

---

## Auto Provisioning — Admin

**File:** `modules/auto-provisioning/admin/class-hf-service-list-table.php`

### `hostforge_service_admin_columns` *(filter)*

Filters the columns in the services list table.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$columns` | `array` | Column slug => label pairs |

---

## Auto Provisioning — REST API

**File:** `modules/auto-provisioning/api/class-hf-rest-service-controller.php`

### `hostforge_rest_service_response` *(filter)*

Filters a single service's REST API response data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `array` | Service response data |
| `$post` | `WP_Post` | Service post object |

---

## Support Desk — Module

**File:** `modules/support-desk/class-hf-support-desk-module.php`

### `hostforge_ticket_statuses` *(filter)*

Filters the available ticket statuses.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$statuses` | `array` | Slug => label pairs |

### `hostforge_ticket_priorities` *(filter)*

Filters the available ticket priorities.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$priorities` | `array` | Slug => label pairs |

### `hostforge_auto_close_query` *(filter)*

Filters the WP_Query args for finding tickets to auto-close.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$args` | `array` | WP_Query arguments |
| `$auto_close_days` | `int` | Days of inactivity before close |

### `hostforge_ticket_reply_content` *(filter)*

Filters ticket reply content before saving.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$content` | `string` | Reply content |
| `$ticket_id` | `int` | Ticket post ID |
| `$user_id` | `int` | User ID |
| `$is_staff` | `bool` | Whether reply is from staff |

### `hostforge_kb_article_data` *(filter)*

Filters KB article data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `array` | Article data |
| `$article` | `WP_Post` | KB article post |

### `hostforge_canned_response_merge_tags` *(filter)*

Filters merge tag replacements for canned responses.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$replacements` | `array` | Tag => value replacements |
| `$content` | `string` | Canned response content |
| `$ticket_id` | `int` | Ticket post ID |

### `hostforge_ticket_closed` *(action)*

Fires when a ticket is closed (manually or auto-closed).

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ticket_id` | `int` | Ticket post ID |

### `hostforge_ticket_auto_close_warning` *(action)*

Fires when a ticket is about to be auto-closed.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ticket_id` | `int` | Ticket post ID |

### `hostforge_imap_email_parsed` *(action)*

Fires after an IMAP email has been parsed.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$subject` | `string` | Email subject |
| `$body` | `string` | Email body |
| `$from` | `string` | Sender email address |
| `$user` | `WP_User` | Matched WordPress user |

### `hostforge_ticket_created` *(action)*

Fires when a new ticket is created (from frontend, admin, API, or IMAP).

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ticket_id` | `int` | Ticket post ID |
| `$user_id` | `int` | User who created the ticket |

### `hostforge_ticket_replied` *(action)*

Fires when a ticket reply is added.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ticket_id` | `int` | Ticket post ID |
| `$comment_id` | `int` | Reply comment ID |
| `$is_staff` | `bool` | Whether reply is from staff |

---

## Support Desk — Frontend

**File:** `modules/support-desk/class-hf-ticket-frontend.php`

### `hostforge_ticket_list_query` *(filter)*

Filters the query for listing user's tickets.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query_args` | `array` | WP_Query arguments |
| `$user_id` | `int` | Current user ID |

### `hostforge_ticket_form_fields` *(filter)*

Filters the "New Ticket" form fields data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$form_fields` | `array` | Form field data (departments, services, priorities) |
| `$user_id` | `int` | Current user ID |

### `hostforge_ticket_can_reply` *(filter)*

Filters whether a user can reply to a ticket.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$can_reply` | `bool` | Whether reply is allowed |
| `$ticket_id` | `int` | Ticket post ID |
| `$user_id` | `int` | User ID |

### `hostforge_ticket_allowed_file_types` *(filter)*

Filters the allowed MIME types for ticket attachments.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$allowed_types` | `array` | Array of MIME type strings |

### `hostforge_ticket_submitted` *(action)*

Fires after a ticket is submitted from the frontend form.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ticket_id` | `int` | Ticket post ID |
| `$user_id` | `int` | User who submitted |
| `$department_id` | `int` | Department term ID |
| `$related_service` | `int` | Related service ID (0 if none) |
| `$attachment_ids` | `array` | Array of attachment IDs |

### `hostforge_ticket_reply_submitted` *(action)*

Fires after a ticket reply is submitted from the frontend.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ticket_id` | `int` | Ticket post ID |
| `$comment_id` | `int` | Reply comment ID |
| `$user_id` | `int` | User who submitted |
| `$attachment_ids` | `array` | Array of attachment IDs |

---

## Support Desk — Admin

**File:** `modules/support-desk/admin/class-hf-ticket-admin.php`

### `hostforge_ticket_admin_reply_data` *(filter)*

Filters admin reply data before saving.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$reply_data` | `array` | Reply data (content, is_staff, is_private) |

### `hostforge_canned_response_content` *(filter)*

Filters canned response content before returning to admin.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$content` | `string` | Response content |
| `$title` | `string` | Response title |
| `$response_id` | `int` | Response post ID |

### `hostforge_kb_search_results` *(filter)*

Filters KB search results.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$results` | `array` | Search results |
| `$keyword` | `string` | Search keyword |

### `hostforge_ticket_assigned` *(action)*

Fires when a ticket is assigned to a staff member.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ticket_id` | `int` | Ticket post ID |
| `$assigned_to` | `int` | Staff user ID |

---

## Support Desk — REST API

**File:** `modules/support-desk/api/class-hf-rest-ticket-controller.php`

### `hostforge_rest_ticket_response` *(filter)*

Filters the tickets list REST API response.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tickets` | `array` | Array of ticket data |
| `$request` | `WP_REST_Request` | Request object |

### `hostforge_rest_ticket_create_data` *(filter)*

Filters ticket creation data via REST API.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$create_data` | `array` | Ticket creation data |
| `$request` | `WP_REST_Request` | Request object |

### `hostforge_rest_kb_response` *(filter)*

Filters the KB articles REST API response.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$articles` | `array` | Array of KB article data |
| `$request` | `WP_REST_Request` | Request object |

### `hostforge_ticket_status_updated` *(action)*

Fires when a ticket status is updated via API.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ticket_id` | `int` | Ticket post ID |
| `$new_status` | `string` | The new status |

---

## Domain Manager — Module

**File:** `modules/domain-manager/class-hf-domain-manager-module.php`

### `hostforge_registrars` *(filter)*

Filters the list of available domain registrars.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$registrars` | `array` | Registrar ID => class_name pairs |

### `hostforge_registrar_instance` *(filter)*

Filters the instantiated registrar object.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$registrar` | `object\|null` | Registrar instance |
| `$registrar_id` | `string` | Registrar identifier |

### `hostforge_domain_expiry_reminder_days` *(filter)*

Filters the days-before-expiry to send reminders.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$reminder_days` | `array` | Array of day numbers |

### `hostforge_domain_statuses` *(filter)*

Filters the available domain statuses.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$statuses` | `array` | Slug => label pairs |

### `hostforge_domain_expired` *(action)*

Fires when a domain has expired.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$domain_id` | `int` | Domain post ID |

### `hostforge_domain_expiring` *(action)*

Fires when a domain is approaching expiry.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$domain_id` | `int` | Domain post ID |
| `$days_remaining` | `int` | Days until expiry |

---

## Domain Manager — Engine

**File:** `modules/domain-manager/class-hf-domain-engine.php`

### `hostforge_domain_order_data` *(filter)*

Filters domain meta data extracted from an order.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$meta` | `array` | Domain meta data |
| `$order` | `WC_Order` | The order |
| `$product` | `WC_Product` | The domain product |
| `$domain_action` | `string` | Action: register, transfer, own |

### `hostforge_domain_created` *(action)*

Fires when a domain is created from an order.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$domain_id` | `int` | Domain post ID |
| `$order` | `WC_Order` | WooCommerce order |

### `hostforge_domain_register_params` *(filter)*

Filters domain registration parameters before sending to registrar.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Registration parameters |
| `$domain_id` | `int` | Domain post ID |

### `hostforge_domain_registered` *(action)*

Fires when a domain has been registered.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$domain_id` | `int` | Domain post ID |

### `hostforge_domain_transfer_params` *(filter)*

Filters domain transfer parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Transfer parameters |
| `$domain_id` | `int` | Domain post ID |

### `hostforge_domain_transfer_initiated` *(action)*

Fires when a domain transfer has been initiated.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$domain_id` | `int` | Domain post ID |

### `hostforge_domain_renew_params` *(filter)*

Filters domain renewal parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$params` | `array` | Renewal parameters |
| `$domain_id` | `int` | Domain post ID |

### `hostforge_domain_renewed` *(action)*

Fires when a domain has been renewed.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$domain_id` | `int` | Domain post ID |

---

## Domain Manager — Search

**File:** `modules/domain-manager/class-hf-domain-search.php`

### `hostforge_domain_search_tlds` *(filter)*

Filters the TLDs to check during domain availability search.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tlds` | `array` | Array of TLD strings |
| `$search_keyword` | `string` | Search keyword |

### `hostforge_domain_search_results` *(filter)*

Filters domain search availability results.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$results` | `array` | Search results |
| `$search_keyword` | `string` | Search keyword |
| `$tlds` | `array` | TLDs checked |

---

## Domain Manager — Checkout

**File:** `modules/domain-manager/class-hf-domain-checkout.php`

### `hostforge_domain_checkout_fields` *(filter)*

Filters the domain checkout fields.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$checkout_fields` | `array` | Checkout fields |
| `$checkout` | `WC_Checkout` | Checkout instance |

### `hostforge_domain_checkout_validation` *(filter)*

Filters domain checkout validation result.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$is_valid` | `bool` | Whether validation passed |
| `$action` | `string` | Domain action (register/transfer/own) |
| `$domain` | `string` | Domain name |

---

## Domain Manager — Frontend

**File:** `modules/domain-manager/class-hf-domain-frontend.php`

### `hostforge_domain_list_query` *(filter)*

Filters the query for listing user's domains.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query_args` | `array` | WP_Query arguments |
| `$user_id` | `int` | Current user ID |

### `hostforge_domain_detail_data` *(filter)*

Filters domain detail data shown on the frontend.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$detail_data` | `array` | Domain detail data |
| `$domain_id` | `int` | Domain post ID |

### `hostforge_domain_actions` *(filter)*

Filters the actions available for a domain on the frontend.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$domain_actions` | `array` | Action definitions |
| `$domain_id` | `int` | Domain post ID |
| `$meta` | `array` | Domain meta |

### `hostforge_dns_record_data` *(filter)*

Filters DNS record data before saving.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$record_data` | `array` | DNS record data |
| `$domain_id` | `int` | Domain post ID |
| `$record_id` | `int` | DNS record ID |

---

## Domain Manager — Admin

**File:** `modules/domain-manager/admin/class-hf-domain-admin.php`

### `hostforge_tld_pricing_data` *(filter)*

Filters TLD pricing data before saving.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `array` | TLD pricing data |
| `$id` | `int` | TLD pricing row ID |

---

## Domain Manager — Namecheap Registrar

**File:** `modules/domain-manager/registrars/class-hf-namecheap-registrar.php`

### `hostforge_namecheap_register_params` *(filter)*

Filters Namecheap domain registration API parameters.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$api_params` | `array` | API parameters |
| `$domain` | `string` | Domain name |
| `$params` | `array` | Registration parameters |

### `hostforge_namecheap_nameservers` *(filter)*

Filters nameservers for Namecheap operations.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$nameservers` | `array` | Nameserver list |
| `$domain` | `string` | Domain name |

### `hostforge_namecheap_api_response` *(filter)*

Filters the raw Namecheap API response.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$api_result` | `array` | API response |
| `$command` | `string` | API command |
| `$params` | `array` | API parameters |

---

## Domain Manager — REST API

**File:** `modules/domain-manager/api/class-hf-rest-domain-controller.php`

### `hostforge_rest_domain_response` *(filter)*

Filters the domains list REST API response.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$domains` | `array` | Array of domain data |
| `$request` | `WP_REST_Request` | Request object |

---

## Security — Module

**File:** `modules/security/class-hf-security-module.php`

### `hostforge_security_settings_defaults` *(filter)*

Filters the default security settings.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$defaults` | `array` | Default settings key => value pairs |

---

## Security — Brute Force Protection

**File:** `modules/security/class-hf-brute-force-protection.php`

### `hostforge_max_login_attempts` *(filter)*

Filters the maximum failed login attempts before blocking an IP.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$max_attempts` | `int` | Maximum allowed failed attempts |
| `$ip` | `string` | The IP address being checked |

### `hostforge_login_block_duration` *(filter)*

Filters the login block duration in seconds.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$block_seconds` | `int` | Block duration in seconds |
| `$ip` | `string` | The IP being blocked |
| `$type` | `string` | Block type (auto/manual) |

### `hostforge_login_attempt_recorded` *(action)*

Fires after a login attempt is recorded.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ip` | `string` | Visitor IP address |
| `$username` | `string` | Username attempted |
| `$status` | `string` | `'success'` or `'failed'` |

### `hostforge_ip_blocked` *(action)*

Fires when an IP is blocked due to too many failed login attempts.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ip` | `string` | The blocked IP address |
| `$type` | `string` | Block type: `'auto'` or `'manual'` |
| `$expires_at` | `string` | Expiry datetime in MySQL format |

---

## Security — IP Manager

**File:** `modules/security/class-hf-ip-manager.php`

### `hostforge_ip_allowlist` *(filter)*

Filters the IP allowlist.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ips` | `array` | Array of allowed IP/CIDR strings |
| `$ip` | `string` | The IP being checked |

### `hostforge_ip_blocklist` *(filter)*

Filters the IP blocklist.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ips` | `array` | Array of blocked IP/CIDR strings |
| `$ip` | `string` | The IP being checked |

### `hostforge_ip_access_denied` *(action)*

Fires when an IP is denied access.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$ip` | `string` | The blocked IP address |
| `$source` | `string` | `'settings_blocklist'` or `'database'` |

---

## Security — CAPTCHA

**File:** `modules/security/class-hf-captcha.php`

### `hostforge_captcha_enabled_locations` *(filter)*

Filters the locations where CAPTCHA is enabled.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$locations` | `array` | Associative array of location => bool pairs |

### `hostforge_captcha_verify_result` *(filter)*

Filters the CAPTCHA verification result.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | `bool` | Whether verification passed |
| `$provider` | `string` | `'turnstile'` or `'recaptcha'` |
| `$body` | `array` | Raw response body from provider |

---

## Security — Fraud Detection

**File:** `modules/security/class-hf-fraud-detection.php`

### `hostforge_fraud_blocked_countries` *(filter)*

Filters the list of blocked country codes.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$blocked` | `array` | Blocked country codes |
| `$data` | `array` | Checkout data |

### `hostforge_fraud_blocked_emails` *(filter)*

Filters the blocked email patterns.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$patterns` | `array` | Blocked email patterns |
| `$data` | `array` | Checkout data |

### `hostforge_fraud_risk_score` *(filter)*

Filters the fraud risk score.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$risk_score` | `int` | Calculated risk score |
| `$risk_flags` | `array` | Array of risk flag strings |
| `$order` | `WC_Order` | The order object |

---

## Security — Audit Log

**File:** `modules/security/class-hf-audit-log.php`

### `hostforge_audit_log_events` *(filter)*

Filters the list of audit log event types.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$events` | `array` | Event type definitions |

### `hostforge_audit_log_entry` *(filter)*

Filters an audit log entry before saving.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$entry` | `array` | Log entry data (event, user_id, ip, details) |

---

## Security — REST API

**File:** `modules/security/api/class-hf-rest-security-controller.php`

### `hostforge_rest_security_response` *(filter)*

Filters the security REST API response data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$response_data` | `array` | Response data |
| `$endpoint` | `string` | Endpoint: `'login-attempts'`, `'ip-blocks'`, or `'audit-log'` |
| `$request` | `WP_REST_Request` | Request object |

---

## Notifications — Module

**File:** `modules/notifications/class-hf-notifications-module.php`

### `hostforge_notification_emails` *(filter)*

Filters the list of HostForge email classes registered with WooCommerce.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$hf_emails` | `array` | Array of WC_Email class names |

### `hostforge_notification_enabled` *(filter)*

Filters whether a specific notification is enabled. Used before triggering each email.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$enabled` | `bool` | Whether notification is enabled |
| `$email_id` | `string` | Email identifier (e.g., `hf_service_welcome`) |

**Supported email IDs:** `hf_service_welcome`, `hf_service_suspended`, `hf_service_unsuspended`, `hf_service_terminated`, `hf_provision_failed`, `hf_ticket_new_staff`, `hf_ticket_reply_customer`, `hf_ticket_reply_staff`, `hf_ticket_closed`, `hf_domain_registered`, `hf_domain_expiry`

---

## Notifications — Merge Tags

**File:** `modules/notifications/class-hf-merge-tags.php`

### `hostforge_merge_tag_value` *(filter)*

Filters the value of a single merge tag.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$value` | `string` | Tag value |
| `$tag` | `string` | Tag name |
| `$content` | `string` | Full content string |

### `hostforge_email_merge_tags` *(filter)*

Filters merge tags for a specific context.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$tags` | `array` | Tag => value pairs |
| `$context` | `string` | Context: `'service'`, `'ticket'`, or `'domain'` |

---

## Notifications — Email Classes

Each of the 11 WC_Email subclasses provides two filters: one for the recipient and one for the template data.

### Recipient Filters

| Hook | File | Parameters |
|------|------|------------|
| `hostforge_email_service_welcome_recipient` | `class-hf-email-service-welcome.php` | `$recipient, $service_id` |
| `hostforge_email_service_suspended_recipient` | `class-hf-email-service-suspended.php` | `$recipient, $service_id` |
| `hostforge_email_service_unsuspended_recipient` | `class-hf-email-service-unsuspended.php` | `$recipient, $service_id` |
| `hostforge_email_service_terminated_recipient` | `class-hf-email-service-terminated.php` | `$recipient, $service_id` |
| `hostforge_email_provision_failed_recipient` | `class-hf-email-provision-failed.php` | `$recipient, $service_id` |
| `hostforge_email_ticket_new_staff_recipient` | `class-hf-email-ticket-new-staff.php` | `$recipient, $ticket_id` |
| `hostforge_email_ticket_reply_customer_recipient` | `class-hf-email-ticket-reply-customer.php` | `$recipient, $ticket_id` |
| `hostforge_email_ticket_reply_staff_recipient` | `class-hf-email-ticket-reply-staff.php` | `$recipient, $ticket_id` |
| `hostforge_email_ticket_closed_recipient` | `class-hf-email-ticket-closed.php` | `$recipient, $ticket_id` |
| `hostforge_email_domain_registered_recipient` | `class-hf-email-domain-registered.php` | `$recipient, $domain_id` |
| `hostforge_email_domain_expiry_recipient` | `class-hf-email-domain-expiry.php` | `$recipient, $domain_id` |

### Template Data Filters

| Hook | File | Parameters |
|------|------|------------|
| `hostforge_email_service_welcome_data` | `class-hf-email-service-welcome.php` | `$email_data, $service_id` |
| `hostforge_email_service_suspended_data` | `class-hf-email-service-suspended.php` | `$email_data, $service_id` |
| `hostforge_email_service_unsuspended_data` | `class-hf-email-service-unsuspended.php` | `$email_data, $service_id` |
| `hostforge_email_service_terminated_data` | `class-hf-email-service-terminated.php` | `$email_data, $service_id` |
| `hostforge_email_provision_failed_data` | `class-hf-email-provision-failed.php` | `$email_data, $service_id` |
| `hostforge_email_ticket_new_staff_data` | `class-hf-email-ticket-new-staff.php` | `$email_data, $ticket_id` |
| `hostforge_email_ticket_reply_customer_data` | `class-hf-email-ticket-reply-customer.php` | `$email_data, $ticket_id` |
| `hostforge_email_ticket_reply_staff_data` | `class-hf-email-ticket-reply-staff.php` | `$email_data, $ticket_id` |
| `hostforge_email_ticket_closed_data` | `class-hf-email-ticket-closed.php` | `$email_data, $ticket_id` |
| `hostforge_email_domain_registered_data` | `class-hf-email-domain-registered.php` | `$email_data, $domain_id` |
| `hostforge_email_domain_expiry_data` | `class-hf-email-domain-expiry.php` | `$email_data, $domain_id` |

### Example: Custom Email Recipient

```php
add_filter( 'hostforge_email_provision_failed_recipient', function( $recipient, $service_id ) {
    // Also notify the technical team
    return $recipient . ', devops@example.com';
}, 10, 2 );
```

### Example: Add Custom Data to Email Template

```php
add_filter( 'hostforge_email_service_welcome_data', function( $email_data, $service_id ) {
    $email_data['custom_instructions'] = 'Remember to configure your DNS!';
    return $email_data;
}, 10, 2 );
```

---

## Reports — Module

**File:** `modules/reports/class-hf-reports-module.php`

### `hostforge_report_types` *(filter)*

Filters the valid CSV export types.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$valid_types` | `array` | Array of type slugs (revenue, services, tickets, domains, servers) |

---

## Reports — Data Provider

**File:** `modules/reports/class-hf-report-data.php`

### `hostforge_report_revenue_data` *(filter)*

Filters revenue report data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `array` | Revenue data |
| `$start_date` | `string` | Start date (Y-m-d) |
| `$end_date` | `string` | End date (Y-m-d) |

### `hostforge_report_mrr` *(filter)*

Filters the calculated Monthly Recurring Revenue value.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$mrr` | `float` | MRR value |

### `hostforge_report_services_data` *(filter)*

Filters services report data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | `array` | Associative array of status => count |

### `hostforge_report_ticket_metrics` *(filter)*

Filters ticket metrics data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$metrics` | `array` | Metrics including by_status, avg_resolution, total_open |

### `hostforge_report_domain_stats` *(filter)*

Filters domain statistics.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$result` | `array` | Domain stats including status counts and expiring_soon |

### `hostforge_report_server_capacity` *(filter)*

Filters server capacity data.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `array` | Array of server capacity arrays (id, name, max, current, usage) |

---

## Reports — CSV Exporter

**File:** `modules/reports/class-hf-csv-exporter.php`

### `hostforge_csv_export_filename` *(filter)*

Filters the CSV export filename.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filename` | `string` | Export filename |
| `$type` | `string` | Export type |

### `hostforge_csv_export_headers` *(filter)*

Filters the CSV column headers. Called for each export type.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$headers` | `array` | Array of column header strings |
| `$type` | `string` | Export type: `'revenue'`, `'services'`, `'tickets'`, `'domains'`, `'servers'` |

### `hostforge_csv_export_rows` *(filter)*

Filters the CSV data rows. Called for each export type.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$rows` | `array` | Array of row arrays |
| `$type` | `string` | Export type: `'revenue'`, `'services'`, `'tickets'`, `'domains'`, `'servers'` |

---

## Reports — REST API

**File:** `modules/reports/api/class-hf-rest-reports-controller.php`

### `hostforge_rest_report_response` *(filter)*

Filters the reports REST API response. Called for all 6 endpoints.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$response_data` | `array` | Response data |
| `$endpoint` | `string` | Endpoint: `'revenue'`, `'customers'`, `'services'`, `'tickets'`, `'domains'`, `'servers'` |
| `$request` | `WP_REST_Request` | Request object |

---

## Hook Count Summary

| Module | Actions | Filters | Total |
|--------|---------|---------|-------|
| Core (includes/) | 15 | 17 | 32 |
| Server Manager | 4 | 16 | 20 |
| Auto Provisioning | 9 | 12 | 21 |
| Support Desk | 9 | 13 | 22 |
| Domain Manager | 6 | 17 | 23 |
| Security | 3 | 12 | 15 |
| Notifications | 0 | 25 | 25 |
| Reports | 0 | 13 | 13 |
| **Total** | **46** | **125** | **171** |

---

*Document generated for HostForge for WooCommerce v1.0.0 — February 2026*
