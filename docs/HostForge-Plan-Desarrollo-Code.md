# HOSTFORGE FOR WOOCOMMERCE — Development Plan for Claude Code

**WordPress / WooCommerce Plugin — Modular Phased Development**

Author: José Conti | Version 1.0 | February 2026 | Confidential Document

---

## TABLE OF CONTENTS

- [0. General Instructions for Claude Code](#0-general-instructions-for-claude-code)
- [PHASE 1: Plugin Core and Foundations](#phase-1-plugin-core-and-foundations)
- [PHASE 2: WooCommerce Product Types for Hosting](#phase-2-woocommerce-product-types-for-hosting)
- [PHASE 3: Server Manager Module](#phase-3-server-manager-module)
- [PHASE 4: Auto Provisioning Module](#phase-4-auto-provisioning-module)
- [PHASE 5: Support Desk Module](#phase-5-support-desk-module)
- [PHASE 6: Domain Manager Module](#phase-6-domain-manager-module)
- [PHASE 7: Additional Modules](#phase-7-additional-modules)
- [PHASE 8: Testing Security and Final Polish](#phase-8-testing-security-and-final-polish)
- [Appendix A: Database Schema](#appendix-a-database-schema)
- [Appendix B: Hooks and Filters Reference](#appendix-b-hooks-and-filters-reference)
- [Appendix C: WordPress Security Checklist](#appendix-c-wordpress-security-checklist)

---

## 0. General Instructions for Claude Code

This document is your master development guide for **HostForge for WooCommerce**. Follow these rules at all times.

### 0.1 Fundamental Rules

1. **WordPress Coding Standards (WPCS)**: All PHP must comply. Run phpcs with `WordPress-Extra` ruleset.
2. **WordPress Security**: Escape all output (`esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()`). Sanitize all input (`sanitize_text_field()`, `absint()`, etc.). Nonces on every form. `current_user_can()` before every action.
3. **Prefix**: All functions, classes, hooks, options, DB tables, constants use `hf_` or `hostforge_` or namespace `HostForge\\`.
4. **PHP 8.0+**: Type hints, return types, no deprecated functions.
5. **All strings in English**: Every user-facing string in English, wrapped in `__()`, `_e()`, `esc_html__()`, `esc_attr__()` with text-domain `hostforge`.
6. **No jQuery on new screens**: Vanilla JS or Alpine.js. Always `wp_enqueue_script()` / `wp_enqueue_style()`.
7. **Conditional asset loading**: CSS/JS only on pages where needed, never globally.
8. **Cron = Action Scheduler (MANDATORY)**: NEVER use WP-Cron. Use WooCommerce Action Scheduler: `as_schedule_single_action()`, `as_schedule_recurring_action()`, `as_enqueue_async_action()`. Register callbacks with `add_action()`.
9. **HPOS Compatibility**: Declare compatibility. Use `$order->get_meta()` / `$order->update_meta_data()` for order data.
10. **WooCommerce Blocks Checkout**: Ensure compatibility with block-based checkout.
11. **No Billing/Invoicing**: HostForge does NOT handle invoicing, billing PDFs, tax calculation, late fees, credit wallets, or quotes. European billing regulations vary by country and change constantly. Each user installs their own billing plugin (Autonomos Premium, FacturaScripts Sync, etc.). HostForge creates WooCommerce orders/subscriptions; the billing plugin handles the rest.

### 0.2 Progress Tracking System

Every phase has a checklist with columns: ID, Task, Status.

| Status | Meaning |
|--------|---------|
| `PENDING` | Not started |
| `IN PROGRESS` | Currently being developed |
| `DONE` | Completed and tested |

When you complete a task, tell the user to update this document. If you switch chats due to "Prompt Too Long", the next chat picks up from the first `PENDING` task.

> **CRITICAL**: At the start of every new chat the user will provide this document. Read status tables, find what is `DONE`, continue from first `PENDING`. Do NOT redo completed work.

### 0.3 Mandatory Modular Architecture

Modules are enabled/disabled from `HostForge > Settings > Modules`. When **disabled**:

- PHP classes NOT loaded
- Hooks NOT registered
- CSS/JS NOT enqueued
- Admin menus NOT created
- REST API endpoints NOT registered
- Action Scheduler tasks NOT scheduled

DB tables are created on first activation and NOT deleted on deactivation (only on full uninstall).

### 0.4 Plugin File Structure

```
hostforge-for-woocommerce/
├── hostforge-for-woocommerce.php          # Main plugin file
├── uninstall.php                         # Cleanup on uninstall
├── composer.json                         # PSR-4 autoloading config
├── assets/
│   ├── css/                              # Styles (admin and front)
│   ├── js/                               # Scripts (admin and front)
│   └── images/                           # Icons, logos
├── includes/
│   ├── class-hostforge.php               # Main class (singleton)
│   ├── class-hf-autoloader.php           # PSR-4 autoloader
│   ├── class-hf-activator.php            # Activation logic
│   ├── class-hf-deactivator.php          # Deactivation logic
│   ├── class-hf-module-manager.php       # Active modules manager
│   ├── class-hf-dependency-checker.php   # Verify WC, PHP versions
│   ├── abstracts/
│   │   ├── abstract-hf-module.php        # Base module class
│   │   ├── abstract-hf-api-client.php    # Base API HTTP client
│   │   └── abstract-hf-rest-controller.php # Base WP REST controller
│   ├── interfaces/
│   │   ├── interface-hf-panel-provider.php    # cPanel/Plesk contract
│   │   ├── interface-hf-registrar.php         # Domain registrar contract
│   │   └── interface-hf-subscription-adapter.php # Subscription plugin contract
│   ├── traits/
│   │   ├── trait-hf-has-logs.php          # Reusable logging
│   │   └── trait-hf-has-settings.php      # Reusable settings
│   └── helpers/
│       ├── hf-formatting-functions.php
│       └── hf-template-functions.php
├── modules/
│   ├── server-manager/
│   │   ├── class-hf-server-manager-module.php
│   │   ├── admin/
│   │   ├── api/
│   │   └── providers/
│   │       ├── class-hf-cpanel-provider.php
│   │       └── class-hf-plesk-provider.php
│   ├── auto-provisioning/
│   ├── domain-manager/
│   ├── support-desk/
│   ├── security/
│   ├── notifications/
│   └── reports/
├── templates/
│   ├── admin/
│   ├── frontend/                          # Overrideable via theme/hostforge/
│   ├── emails/                            # WC email templates
│   └── checkout/
├── languages/
│   └── hostforge.pot
└── tests/
    ├── phpunit/
    └── integration/
```

### 0.5 External API References

- **cPanel/WHM API**: https://api.docs.cpanel.net/whm/introduction
- **Plesk API**: https://docs.plesk.com/en-US/obsidian/api-rpc/introduction.79358/

### 0.6 Plesk API Decision: XML API Primary, REST API Complement

**XML API primary** because: complete feature coverage, supports Admin/Reseller/Customer roles, mature and stable, required for SSO, subscription management, reseller management.

**REST API complement** for: server info (`GET /api/v2/server`), domain listing (`GET /api/v2/domains`), DNS management (`/api/v2/dns`), CLI commands (`/api/v2/cli`).

**Implementation**: `HF_Plesk_Provider` uses two internal HTTP methods — XML (port 8443, `text/xml`) and REST (port 8443, `application/json`). Auth: `X-API-Key` header (preferred) or HTTP Basic.

---

## PHASE 1: Plugin Core and Foundations

Absolute priority. No module works without this.

### 1.1 Main Plugin File

`hostforge-for-woocommerce.php` with constants: `HOSTFORGE_VERSION`, `HOSTFORGE_PLUGIN_FILE`, `HOSTFORGE_PLUGIN_DIR`, `HOSTFORGE_PLUGIN_URL`, `HOSTFORGE_PLUGIN_BASENAME`, `HOSTFORGE_MIN_PHP` (8.0), `HOSTFORGE_MIN_WP` (6.0), `HOSTFORGE_MIN_WC` (8.0).

`defined('ABSPATH') || exit;` on line 1. Load on `plugins_loaded` priority 10. HPOS declaration via `before_woocommerce_init`. Admin notice if WC missing.

### 1.2 Autoloading System

PSR-4 in `class-hf-autoloader.php`. `HostForge\\` → `includes/`. Module namespaces → `modules/{slug}/`. Via `spl_autoload_register()`. No Composer dependency in production.

### 1.3 Main Class (Singleton)

`class-hostforge.php`: `instance()`, private `__construct()/__clone()/__wakeup()`. `init()` loads textdomain, initializes Module Manager. Global hooks: activation, deactivation, action links.

### 1.4 Module Manager

`class-hf-module-manager.php`: `register_module()`, `activate_module()`, `deactivate_module()`, `load_active_modules()`. Active modules in `hf_active_modules` option. Admin UI at `HostForge > Settings > Modules` with AJAX toggles. Dependency checking (deactivating parent deactivates children).

### 1.5 Abstract Module Class

```php
abstract class HF_Module {
    abstract public function get_id(): string;
    abstract public function get_name(): string;
    abstract public function get_description(): string;
    abstract public function get_dependencies(): array;
    abstract public function init(): void;
    public function activate(): void {}
    public function deactivate(): void {}
    public function get_admin_menu_items(): array { return []; }
    public function get_myaccount_endpoints(): array { return []; }
    public function register_rest_routes(): void {}
    public function register_scheduled_actions(): void {}
    final public function is_active(): bool { /* check option */ }
}
```

### 1.6 Activator / Deactivator / Uninstaller

**Activator**: Shared DB tables (`hf_logs`, `hf_activity_log`) via `dbDelta()`. Capabilities (`manage_hostforge`, etc.) assigned to administrator. Set `hf_db_version`. Flush rewrite rules.

**Deactivator**: `as_unschedule_all_actions()` for HostForge groups. Flush rules. No data deletion.

**Uninstaller**: Only if `hf_delete_data_on_uninstall` is true. Drop tables, delete options, meta, CPTs. Remove capabilities.

### 1.7 Logging System

Table `{prefix}hf_logs`: id, module, level, message, context (JSON), created_at. Trait `trait-hf-has-logs.php` with `$this->log()`. Admin viewer at `HostForge > Settings > Logs` with filters. Auto-prune via daily Action Scheduler task.

### 1.8 Settings Page

`HostForge > Settings > General` via Settings API: company info, debug toggle, delete-on-uninstall toggle, log retention days, license key.

### 1.9 REST API Base

Namespace `hostforge/v1`. Health check: `GET /wp-json/hostforge/v1/status`. Abstract controller extending `WP_REST_Controller`. Auth via Application Passwords. Transient-based rate limiting.

### 1.10 Admin Dashboard

`HostForge > Dashboard`: Widget-based. Modules register widgets. Defaults: active services, open tickets, server status, recent activity. AJAX-loaded data.

### Phase 1 Checklist

| ID | Task | Status |
|----|------|--------|
| 1.1 | Main plugin file with constants, headers, dependency checks | `DONE` |
| 1.2 | PSR-4 autoloader | `DONE` |
| 1.3 | Main singleton class with init() | `DONE` |
| 1.4 | Module Manager (register, activate, deactivate, load) | `DONE` |
| 1.5 | Abstract module class | `DONE` |
| 1.6 | Activator: DB tables, capabilities, version | `DONE` |
| 1.7 | Deactivator: unschedule actions, flush rules | `DONE` |
| 1.8 | Uninstaller with conditional data deletion | `DONE` |
| 1.9 | Logging system: table, trait, admin viewer | `DONE` |
| 1.10 | Settings: General page | `DONE` |
| 1.11 | Settings: Modules screen with AJAX toggles | `DONE` |
| 1.12 | REST API base: namespace, health-check, abstract controller | `DONE` |
| 1.13 | HPOS compatibility declaration | `DONE` |
| 1.14 | Dependency checker | `DONE` |
| 1.15 | Base admin CSS/JS with conditional enqueue | `DONE` |
| 1.16 | Dashboard page with widget registration | `DONE` |
| 1.17 | POT file generation setup | `DONE` |
| 1.18 | plugin_action_links with Settings link | `DONE` |

---

## PHASE 2: WooCommerce Product Types for Hosting

### 2.1 Subscription Abstraction Layer

Interface `HF_Subscription_Adapter` with methods: `is_available()`, `create_subscription()`, `cancel_subscription()`, `suspend_subscription()`, `reactivate_subscription()`, `get_status()`, `get_next_payment_date()`, `get_subscriptions_by_user()`.

Implementations: `HF_WCS_Adapter` (WooCommerce Subscriptions), `HF_YITH_Adapter` (YITH), `HF_Advanced_Subs_Adapter` (Advanced Subs), `HF_SUMO_Adapter` (SUMO Subscriptions — PENDING).

Factory auto-detects active plugin. Admin notice if none active.

> **RESEARCH NOTE (Phase 8)**: SUMO Subscriptions (v15.7.0) documentation is available in `docs/Plugins suscripciones/sumosubscriptions/`. Key integration points:
> - **Detection**: `class_exists('SUMOSubscriptions')` or `SUMOSubscriptions::instance()`
> - **CPT**: `sumo_subscription` with post meta (`sumo_get_status`, `sumo_get_parent_order_id`)
> - **Class**: `SUMOSubs_Subscription($id)` + `SUMOSubs_Subscription_Factory`
> - **Lifecycle hooks**: `sumosubscriptions_active_subscription($id, $parent_order_id)`, `sumosubscriptions_pause_subscription`, `sumosubscriptions_cancel_subscription`, `sumosubscriptions_renewal_payment_complete($post_id, $order_id)`
> - **Status mapping**: Active→active, Paused/Suspended→on-hold, Cancelled/Pending_Cancellation→cancelled, Overdue→on-hold, Trial→active, Pending→pending
> - **Duration period**: `get_duration_period()` returns 'D', 'W', 'M', 'Y'

### 2.2-2.8 Product Types

**Shared Hosting** (`hf_shared_hosting`): Server, plan, disk/bandwidth limits, email/db/subdomain limits, require domain, auto-username, setup fee, trial days.

**Reseller Hosting** (`hf_reseller_hosting`): Same + max accounts, aggregate limits, reseller plan.

**VPS/Cloud** (`hf_vps_server`): CPU, RAM, disk, IPs, hostname at checkout, root password, OS selection.

**Dedicated Server** (`hf_dedicated_server`): Physical specs, IPs, OS.

**Domain** (`hf_domain`): Availability search, TLD pricing, registrar selection.

**SSL Certificate** (`hf_ssl_certificate`): Type (DV/OV/EV/Wildcard), domain, CSR.

**Software License** (`hf_software_license`): License type, server IP, generated key.

### 2.9 Custom Checkout Fields

Each product type injects fields into checkout. Compatible with classic and block checkout. Data validated, saved as order meta (HPOS).

### 2.10 Product Add-ons

Optional extras: dedicated IP, backup, SSL, storage. Displayed on product page and checkout.

### Phase 2 Checklist

| ID | Task | Status |
|----|------|--------|
| 2.1 | Subscription adapter interface and factory | `DONE` |
| 2.2 | WCS adapter | `DONE` |
| 2.3 | YITH adapter | `DONE` |
| 2.4 | Advanced Subs adapter | `DONE` |
| 2.5 | WC_Product_HF_Shared_Hosting with all fields | `DONE` |
| 2.6 | WC_Product_HF_Reseller_Hosting | `DONE` |
| 2.7 | WC_Product_HF_VPS_Server | `DONE` |
| 2.8 | WC_Product_HF_Dedicated_Server | `DONE` |
| 2.9 | WC_Product_HF_Domain with availability search | `DONE` |
| 2.10 | WC_Product_HF_SSL_Certificate | `DONE` |
| 2.11 | WC_Product_HF_Software_License | `DONE` |
| 2.12 | Admin product data tabs for each type | `DONE` |
| 2.13 | Custom checkout fields (classic + blocks) | `DONE` |
| 2.14 | Checkout validation per product type | `DONE` |
| 2.15 | Save product meta to order (HPOS) | `DONE` |
| 2.16 | Product add-ons system | `DONE` |

---

## PHASE 3: Server Manager Module

**Module ID**: `server-manager` | **Dependencies**: None

### 3.1 CPT: `hf_server`

Meta: `_hf_panel_type` (cpanel/plesk), `_hf_hostname`, `_hf_port`, `_hf_protocol`, `_hf_auth_method`, `_hf_api_token` (encrypted), `_hf_username` (encrypted), `_hf_password` (encrypted), `_hf_max_accounts`, `_hf_current_accounts`, `_hf_server_group`, `_hf_status`, `_hf_last_check`, `_hf_packages_cache`, `_hf_packages_cache_time`.

Encryption: `openssl_encrypt()`/`openssl_decrypt()` with key from `wp_salt('auth')`.

### 3.2 Panel Provider Interface

`test_connection()`, `create_account()`, `suspend_account()`, `unsuspend_account()`, `terminate_account()`, `change_password()`, `change_package()`, `get_packages()`, `get_account_usage()`, `get_server_stats()`, `get_sso_url()`.

### 3.3 cPanel/WHM Provider

WHM API v1: `createacct`, `suspendacct`, `unsuspendacct`, `removeacct`, `passwd`, `changepackage`, `listpkgs`, `accountsummary`, `create_user_session`. HTTPS port 2087, `Authorization: whm root:{token}`, JSON, `wp_remote_post()` 30s timeout.

### 3.4 Plesk Provider

XML API: `webspace>add`, `webspace>del`, `webspace>set` (suspend/unsuspend/password/package), `service-plan>get`, `server>create_session`, `customer>add/get`. REST API: server info, domains, DNS. Port 8443, `X-API-Key` header.

### 3.5 Admin Screens

Server List (WP_List_Table), Add/Edit form, Test Connection (AJAX), Fetch Packages (AJAX), Server Groups, Status Monitor. Health check every 5 min via Action Scheduler.

### Phase 3 Checklist

| ID | Task | Status |
|----|------|--------|
| 3.1 | CPT hf_server with meta | `DONE` |
| 3.2 | Credential encryption utility | `DONE` |
| 3.3 | HF_Panel_Provider interface | `DONE` |
| 3.4 | cPanel provider: create, suspend, unsuspend, terminate | `DONE` |
| 3.5 | cPanel provider: passwd, changepackage, listpkgs | `DONE` |
| 3.6 | cPanel provider: SSO | `DONE` |
| 3.7 | Plesk provider: XML API client | `DONE` |
| 3.8 | Plesk provider: webspace operations | `DONE` |
| 3.9 | Plesk provider: service plans, customers | `DONE` |
| 3.10 | Plesk provider: REST API (server, DNS) | `DONE` |
| 3.11 | Plesk provider: SSO | `DONE` |
| 3.12 | Admin: Server list | `DONE` |
| 3.13 | Admin: Add/Edit server | `DONE` |
| 3.14 | Admin: Test Connection AJAX | `DONE` |
| 3.15 | Admin: Fetch Packages AJAX | `DONE` |
| 3.16 | Admin: Server groups | `DONE` |
| 3.17 | Admin: Status monitor | `DONE` |
| 3.18 | Action Scheduler: health check 5min | `DONE` |
| 3.19 | Dashboard widget: server status | `DONE` |
| 3.20 | REST API: server endpoints | `DONE` |

---

## PHASE 4: Auto Provisioning Module

**Module ID**: `auto-provisioning` | **Dependencies**: `server-manager`

### 4.1 CPT: `hf_service`

Meta: `_hf_order_id`, `_hf_subscription_id`, `_hf_product_id`, `_hf_user_id`, `_hf_server_id`, `_hf_panel_username`, `_hf_panel_password` (encrypted), `_hf_domain`, `_hf_status` (pending/active/suspended/terminated/cancelled), `_hf_provisioned_at`, `_hf_suspended_at`, `_hf_terminated_at`, `_hf_next_due_date`, `_hf_panel_type`.

### 4.2 Provisioning Engine

Hooks `woocommerce_order_status_completed/processing`. For each hosting product: select server (fewest accounts in group), generate username (8 chars from domain, unique), generate password, create hf_service, enqueue via `as_enqueue_async_action('hostforge_provision_service', [$service_id], 'hostforge-provisioning')`.

Subscription hooks via adapter: expired→suspend, reactivated→unsuspend, switched→change package.

### 4.3 Provisioning Queue

Table `{prefix}hf_provisioning_queue`: service_id, action, params (JSON), status, attempts, max_attempts (3), last_error, scheduled_at, completed_at. Retry with exponential backoff (5min × attempt).

### 4.4 Action Scheduler Automation

- Auto-suspend: every 6h, active services with expired subscription > X days (default 3).
- Auto-terminate: every 24h, suspended > X days (default 30).
- Auto-reactivate: on subscription payment (immediate).

Settings at `HostForge > Settings > Automation`.

### 4.5 Frontend: My Account

Endpoint `hosting-services`. List page: services with status badges. Detail page: SSO button, change password, upgrade/downgrade, cancellation request, usage stats (cached 15min).

Cancellation flow: customer requests → admin reviews → process or deny.

Templates in `templates/frontend/`, overrideable via `theme/hostforge/`.

### Phase 4 Checklist

| ID | Task | Status |
|----|------|--------|
| 4.1 | CPT hf_service with meta | `DONE` |
| 4.2 | Provisioning engine: WC order hooks | `DONE` |
| 4.3 | Provisioning engine: subscription hooks | `DONE` |
| 4.4 | Username generation with uniqueness | `DONE` |
| 4.5 | Password generation | `DONE` |
| 4.6 | Server auto-selection | `DONE` |
| 4.7 | Provisioning queue table | `DONE` |
| 4.8 | Action Scheduler: provision callback | `DONE` |
| 4.9 | Retry logic with backoff | `DONE` |
| 4.10 | Auto-suspend task | `DONE` |
| 4.11 | Auto-terminate task | `DONE` |
| 4.12 | Auto-reactivate on payment | `DONE` |
| 4.13 | Admin: Services list (all/pending/suspended/cancellations) | `DONE` |
| 4.14 | Admin: Service detail with manual actions | `DONE` |
| 4.15 | Admin: Automation settings | `DONE` |
| 4.16 | Frontend: hosting-services endpoint | `DONE` |
| 4.17 | Frontend: Service list template | `DONE` |
| 4.18 | Frontend: Service detail (SSO, password, usage) | `DONE` |
| 4.19 | Frontend: Cancellation request | `DONE` |
| 4.20 | Frontend: Upgrade/downgrade request | `DONE` |
| 4.21 | Email: Welcome with credentials | `DONE` |
| 4.22 | Email: Service suspended | `DONE` |
| 4.23 | Email: Service terminated | `DONE` |
| 4.24 | Dashboard widget: services count | `DONE` |

---

## PHASE 5: Support Desk Module

**Module ID**: `support-desk` | **Dependencies**: None

### 5.1 CPT: `hf_ticket`

Meta: `_hf_department`, `_hf_priority` (critical/high/medium/low), `_hf_status` (open/customer_reply/staff_reply/in_progress/closed), `_hf_assigned_to`, `_hf_related_service`, `_hf_client_user_id`, `_hf_last_reply_at`, `_hf_last_reply_by`, `_hf_flagged`.

Taxonomy: `hf_department`.

### 5.2 Replies via WP Comments

`comment_type` = `hf_ticket_reply`. Meta: `_hf_is_private_note`, `_hf_is_staff_reply`, `_hf_attachments`.

### 5.3 Knowledge Base: `hf_kb_article`

Taxonomy: `hf_kb_category`. Meta: `_hf_visibility`, `_hf_helpful_yes`, `_hf_helpful_no`, `_hf_related_articles`. AJAX helpfulness voting.

### 5.4 Canned Responses

CPT `hf_canned_response`. Insertable in replies. Supports merge tags.

### 5.5 Auto-close

Daily Action Scheduler: close tickets inactive > X days. Warning email 24h before.

### 5.6 Email Piping (Optional)

Action Scheduler every 5min: check IMAP mailbox, create tickets/replies from emails.

### 5.7 Admin

Ticket list (WP_List_Table with filters), ticket detail (thread, reply, notes, sidebar actions), departments, canned responses, KB management.

### 5.8 Frontend

My Account `support-tickets`: list, new ticket form (with KB suggestions), ticket detail with replies. Public KB page with categories and search.

### Phase 5 Checklist

| ID | Task | Status |
|----|------|--------|
| 5.1 | CPT hf_ticket with meta and department taxonomy | `DONE` |
| 5.2 | Reply system via WP comments | `DONE` |
| 5.3 | Private notes | `DONE` |
| 5.4 | File attachments on tickets | `DONE` |
| 5.5 | CPT hf_kb_article with taxonomy | `DONE` |
| 5.6 | KB helpfulness voting (AJAX) | `DONE` |
| 5.7 | CPT hf_canned_response | `DONE` |
| 5.8 | Auto-close inactive tickets (Action Scheduler) | `DONE` |
| 5.9 | Email piping: IMAP check | `DONE` |
| 5.10 | KB suggestions when creating ticket | `DONE` |
| 5.11 | Admin: Ticket list with filters | `DONE` |
| 5.12 | Admin: Ticket detail (replies, notes, sidebar) | `DONE` |
| 5.13 | Admin: Canned response insertion | `DONE` |
| 5.14 | Admin: Department management | `DONE` |
| 5.15 | Admin: KB management | `DONE` |
| 5.16 | Frontend: support-tickets endpoint | `DONE` |
| 5.17 | Frontend: New ticket form | `DONE` |
| 5.18 | Frontend: Ticket detail with replies | `DONE` |
| 5.19 | Frontend: Public KB page | `DONE` |
| 5.20 | Email: New ticket (to staff) | `DONE` |
| 5.21 | Email: Reply notifications | `DONE` |
| 5.22 | Email: Ticket closed | `DONE` |
| 5.23 | Dashboard widget: open tickets | `DONE` |

---

## PHASE 6: Domain Manager Module

**Module ID**: `domain-manager` | **Dependencies**: None

### 6.1 Registrar Interface

`check_availability()`, `check_availability_bulk()`, `register_domain()`, `transfer_domain()`, `renew_domain()`, `get/set_nameservers()`, `get/toggle_lock()`, `get_epp_code()`, `get/update_whois()`, `get/add/update/delete_dns_record()`, `enable/disable_auto_renew()`.

### 6.2 Implementations

Initial: `HF_OpenProvider_Registrar` or `HF_Namecheap_Registrar` (at least one for launch).

### 6.3 CPT: `hf_domain`

Meta: `_hf_domain_name`, `_hf_registrar`, `_hf_user_id`, `_hf_order_id`, `_hf_nameservers`, `_hf_status`, `_hf_registration_date`, `_hf_expiry_date`, `_hf_auto_renew`, `_hf_lock_status`, `_hf_whois_info`.

### 6.4 DNS Records Table

`{prefix}hf_dns_records`: domain_id, type (A/AAAA/CNAME/MX/TXT/NS/SRV/CAA), name, value, ttl, priority.

### 6.5 Checkout Integration

AJAX domain search in checkout. Options: register new, transfer (EPP code), use own. Domain saved as order meta.

### 6.6 Admin

TLD pricing table, registrar configuration, domain list, domain detail with DNS.

### 6.7 Frontend

My Account `domains`: list, detail with DNS/nameservers/WHOIS/lock/EPP/auto-renew.

### 6.8 Auto-renewal

Daily Action Scheduler: domains expiring in X days. Auto-renew → create WC order. No auto-renew → expiry reminder email.

### Phase 6 Checklist

| ID | Task | Status |
|----|------|--------|
| 6.1 | HF_Registrar interface | `DONE` |
| 6.2 | CPT hf_domain with meta | `DONE` |
| 6.3 | DNS records table | `DONE` |
| 6.4 | First registrar implementation (Namecheap) | `DONE` |
| 6.5 | Domain availability search (AJAX) | `DONE` |
| 6.6 | Checkout: domain search widget | `DONE` |
| 6.7 | Checkout: register/transfer/own flow | `DONE` |
| 6.8 | Auto registration on order complete | `DONE` |
| 6.9 | Admin: TLD pricing table | `DONE` |
| 6.10 | Admin: Registrar configuration | `DONE` |
| 6.11 | Admin: Domain list | `DONE` |
| 6.12 | Admin: Domain detail with DNS | `DONE` |
| 6.13 | Frontend: domains endpoint | `DONE` |
| 6.14 | Frontend: Domain detail | `DONE` |
| 6.15 | Action Scheduler: expiry check, auto-renew | `DONE` |
| 6.16 | Email: Domain registered | `DONE` |
| 6.17 | Email: Domain expiry reminder | `DONE` |
| 6.18 | Dashboard widget: domains summary | `DONE` |

---

## PHASE 7: Additional Modules

Each independently activatable.

### 7.1 Security Module (`security`)

Anti brute-force: `hf_login_attempts` table, block after X attempts, IP allowlist/blocklist.
Fraud detection: checkout hooks for IP/country/email verification.
Anti-spam: Turnstile/reCAPTCHA on forms.
Audit log: `hf_activity_log` table.
Email verification for new accounts.

### 7.2 Notifications Module (`notifications`)

WC_Email subclasses:

| Email ID | Trigger | Recipient |
|----------|---------|-----------|
| hf_service_welcome | Provisioned | Customer |
| hf_service_suspended | Suspended | Customer |
| hf_service_unsuspended | Reactivated | Customer |
| hf_service_terminated | Terminated | Customer |
| hf_ticket_new_staff | New ticket | Staff |
| hf_ticket_reply_customer | Staff replied | Customer |
| hf_ticket_reply_staff | Customer replied | Staff |
| hf_ticket_closed | Closed | Customer |
| hf_domain_registered | Registered | Customer |
| hf_domain_expiry | Expiring | Customer |
| hf_provision_failed | Failed | Admin |

Merge tags: `{customer_name}`, `{service_domain}`, `{service_username}`, `{service_password}`, `{panel_url}`, `{ticket_id}`, `{ticket_subject}`, `{domain_name}`, etc.

Templates in `templates/emails/`, overrideable via theme.

### 7.3 Reports Module (`reports`)

Dashboard widgets with Chart.js: MRR, revenue, services by type/status, customers growth, support metrics, domains, server capacity. CSV export. AJAX-loaded chart data via REST endpoints.

### Phase 7 Checklist

| ID | Task | Status |
|----|------|--------|
| 7.1 | Security: Anti brute-force | `DONE` |
| 7.2 | Security: IP allowlist/blocklist | `DONE` |
| 7.3 | Security: Fraud detection hooks | `DONE` |
| 7.4 | Security: Turnstile/reCAPTCHA | `DONE` |
| 7.5 | Security: Audit log | `DONE` |
| 7.6 | Notifications: All WC_Email subclasses | `DONE` |
| 7.7 | Notifications: Merge tags system | `DONE` |
| 7.8 | Notifications: Email templates (HTML + plain) | `DONE` |
| 7.9 | Notifications: Admin enable/disable settings | `DONE` |
| 7.10 | Reports: Dashboard with Chart.js | `DONE` |
| 7.11 | Reports: Revenue (MRR, monthly) | `DONE` |
| 7.12 | Reports: Services and support | `DONE` |
| 7.13 | Reports: CSV export | `DONE` |
| 7.14 | Reports: REST endpoints for chart data | `DONE` |

---

## PHASE 8: Testing Security and Final Polish

### 8.1 Security Audit

For every PHP file verify:

- `defined('ABSPATH') || exit;` on line 1
- All output escaped
- All input sanitized
- Nonces on all forms
- Capability checks on all actions
- `$wpdb->prepare()` on all queries
- File upload type validation
- No raw `$_GET/$_POST/$_REQUEST`
- Credentials encrypted
- REST API permission callbacks

### 8.2 Performance

- No N+1 queries
- Transient caching for external APIs
- Conditional CSS/JS loading
- DB indexes on custom tables
- `wp_cache_get/set` for frequent data
- Paginated admin list tables

### 8.3 Compatibility

- PHP 8.0, 8.1, 8.2, 8.3
- WP 6.0+
- WC 8.0+
- HPOS enabled
- Block checkout
- All 4 subscription adapters (WCS, YITH, Advanced Subs, SUMO)
- Popular themes (Storefront, Astra, GeneratePress)

### 8.4 Code Quality and Documentation

- PHPCS WordPress-Extra: zero errors
- PHPDoc on all classes/methods
- All strings use `__()` with `hostforge` textdomain
- No hardcoded URLs/paths
- README.md, CHANGELOG.md
- Hooks reference document
- Developer guide (custom providers, registrars, adapters)
- `hostforge.pot` generated

### Phase 8 Checklist

| ID | Task | Status |
|----|------|--------|
| 8.1 | Security: escape all output | `PENDING` |
| 8.2 | Security: sanitize all input | `PENDING` |
| 8.3 | Security: nonces on all forms | `PENDING` |
| 8.4 | Security: capability checks | `PENDING` |
| 8.5 | Security: $wpdb->prepare() everywhere | `PENDING` |
| 8.6 | Security: defined('ABSPATH') all files | `PENDING` |
| 8.7 | Security: REST permission callbacks | `PENDING` |
| 8.8 | Performance: no N+1, caching | `PENDING` |
| 8.9 | Performance: conditional assets | `PENDING` |
| 8.10 | Performance: DB indexes | `PENDING` |
| 8.11 | PHPCS zero errors | `PENDING` |
| 8.12 | Test: PHP 8.0-8.3 | `PENDING` |
| 8.13 | Test: HPOS | `PENDING` |
| 8.14 | Test: Block checkout | `PENDING` |
| 8.15 | SUMO Subscriptions adapter (HF_SUMO_Adapter) | `PENDING` |
| 8.16 | Test: All 4 subscription adapters (WCS, YITH, Advanced Subs, SUMO) | `PENDING` |
| 8.17 | PHPDoc complete | `PENDING` |
| 8.18 | README.md + CHANGELOG.md | `PENDING` |
| 8.19 | Hooks reference document | `PENDING` |
| 8.20 | Developer guide | `PENDING` |
| 8.21 | hostforge.pot generated | `PENDING` |

---

## Appendix A: Database Schema

All tables use `$wpdb->get_charset_collate()` and `dbDelta()`.

### Core (always created)

```
{prefix}hf_logs
  id            BIGINT UNSIGNED AUTO_INCREMENT PK
  module        VARCHAR(50)     NOT NULL        INDEX
  level         VARCHAR(20)     NOT NULL        INDEX
  message       TEXT            NOT NULL
  context       LONGTEXT
  created_at    DATETIME        NOT NULL        INDEX

{prefix}hf_activity_log
  id            BIGINT UNSIGNED AUTO_INCREMENT PK
  user_id       BIGINT UNSIGNED NOT NULL        INDEX
  action        VARCHAR(100)    NOT NULL
  object_type   VARCHAR(50)
  object_id     BIGINT UNSIGNED
  details       TEXT
  ip_address    VARCHAR(45)
  created_at    DATETIME        NOT NULL        INDEX
```

### Auto Provisioning

```
{prefix}hf_provisioning_queue
  id            BIGINT UNSIGNED AUTO_INCREMENT PK
  service_id    BIGINT UNSIGNED NOT NULL        INDEX
  action        VARCHAR(20)     NOT NULL
  params        LONGTEXT
  status        VARCHAR(20)     DEFAULT 'pending' INDEX
  attempts      INT UNSIGNED    DEFAULT 0
  max_attempts  INT UNSIGNED    DEFAULT 3
  last_error    TEXT
  scheduled_at  DATETIME        NOT NULL
  completed_at  DATETIME
  INDEX (status, scheduled_at)
```

### Domain Manager

```
{prefix}hf_dns_records
  id            BIGINT UNSIGNED AUTO_INCREMENT PK
  domain_id     BIGINT UNSIGNED NOT NULL        INDEX
  type          VARCHAR(10)     NOT NULL
  name          VARCHAR(255)    NOT NULL
  value         VARCHAR(1024)   NOT NULL
  ttl           INT UNSIGNED    DEFAULT 3600
  priority      INT UNSIGNED    NULL
  created_at    DATETIME        NOT NULL
  updated_at    DATETIME        NOT NULL
```

### Security

```
{prefix}hf_login_attempts
  id          BIGINT UNSIGNED AUTO_INCREMENT PK
  ip_address  VARCHAR(45)     NOT NULL
  username    VARCHAR(100)
  status      VARCHAR(20)     NOT NULL
  created_at  DATETIME        NOT NULL
  INDEX (ip_address, created_at)

{prefix}hf_ip_blocks
  id          BIGINT UNSIGNED AUTO_INCREMENT PK
  ip_address  VARCHAR(45)     NOT NULL  UNIQUE
  reason      VARCHAR(255)
  expires_at  DATETIME        INDEX
  created_at  DATETIME        NOT NULL
```

---

## Appendix B: Hooks and Filters Reference

### Service Lifecycle

```php
do_action( 'hostforge_before_provision', int $service_id, int $order_id );
do_action( 'hostforge_after_provision', int $service_id, array $account_data );
do_action( 'hostforge_provision_failed', int $service_id, string $error );
do_action( 'hostforge_before_suspend', int $service_id, string $reason );
do_action( 'hostforge_after_suspend', int $service_id );
do_action( 'hostforge_before_unsuspend', int $service_id );
do_action( 'hostforge_after_unsuspend', int $service_id );
do_action( 'hostforge_before_terminate', int $service_id );
do_action( 'hostforge_after_terminate', int $service_id );

apply_filters( 'hostforge_provision_params', array $params, int $order_id );
apply_filters( 'hostforge_generated_username', string $username, string $domain, int $user_id );
apply_filters( 'hostforge_generated_password', string $password, int $service_id );
```

### Modules

```php
apply_filters( 'hostforge_registered_modules', array $modules );
do_action( 'hostforge_module_activated', string $module_id );
do_action( 'hostforge_module_deactivated', string $module_id );
```

### Tickets

```php
do_action( 'hostforge_ticket_created', int $ticket_id, int $user_id );
do_action( 'hostforge_ticket_replied', int $ticket_id, int $reply_id, bool $is_staff );
do_action( 'hostforge_ticket_closed', int $ticket_id );
do_action( 'hostforge_ticket_assigned', int $ticket_id, int $staff_id );
apply_filters( 'hostforge_ticket_departments', array $departments );
apply_filters( 'hostforge_canned_response_content', string $content, int $ticket_id );
```

### Domains

```php
do_action( 'hostforge_domain_registered', int $domain_id, string $domain_name );
do_action( 'hostforge_domain_transferred', int $domain_id, string $domain_name );
do_action( 'hostforge_domain_renewed', int $domain_id, string $domain_name );
do_action( 'hostforge_domain_expired', int $domain_id, string $domain_name );
apply_filters( 'hostforge_domain_check_result', array $result, string $domain );
```

### Servers

```php
do_action( 'hostforge_server_connected', int $server_id );
do_action( 'hostforge_server_connection_failed', int $server_id, string $error );
apply_filters( 'hostforge_select_server', int $server_id, int $product_id, string $group );
```

---

## Appendix C: WordPress Security Checklist

Verify for EVERY PHP file:

| # | Rule | Implementation |
|---|------|---------------|
| 1 | Prevent direct access | `defined('ABSPATH') \|\| exit;` line 1 |
| 2 | Escape HTML output | `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()` |
| 3 | Sanitize input | `sanitize_text_field()`, `absint()`, `sanitize_email()` |
| 4 | Form nonces | `wp_nonce_field()` + `wp_verify_nonce()` |
| 5 | AJAX nonces | `check_ajax_referer('hf_action_nonce')` |
| 6 | Capability checks | `current_user_can('manage_hostforge')` |
| 7 | Prepared SQL | `$wpdb->prepare()` on EVERY query with variables |
| 8 | File upload validation | `wp_check_filetype()` |
| 9 | Encrypt credentials | `openssl_encrypt()` with `wp_salt('auth')` |
| 10 | REST permissions | `permission_callback` on every endpoint |
| 11 | No raw superglobals | Always sanitize `$_GET`, `$_POST`, `$_REQUEST` |
| 12 | Content-Type | `application/json` on REST responses |

---

## Phase Summary

| Phase | Description | Dependencies | Priority |
|-------|-------------|-------------|----------|
| 1 | Plugin Core and Foundations | None | **CRITICAL** |
| 2 | WooCommerce Product Types | Phase 1 | **CRITICAL** |
| 3 | Server Manager (cPanel/Plesk) | Phase 1 | **HIGH** |
| 4 | Auto Provisioning | Phases 2, 3 | **HIGH** |
| 5 | Support Desk (Tickets + KB) | Phase 1 | **MEDIUM** |
| 6 | Domain Manager | Phases 1, 2 | **MEDIUM** |
| 7 | Additional Modules (3) | Phases 1-6 | **NORMAL** |
| 8 | Testing, Security, Polish | All | **CRITICAL** |

**Total: ~154 tasks across 8 phases.** Progress tracking enables resuming at any point after a chat switch.

---

*Document prepared for Jose Conti — HostForge for WooCommerce — February 2026*
