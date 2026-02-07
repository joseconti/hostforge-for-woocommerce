# HostForge for WooCommerce - Developer Guide

This guide explains how to extend HostForge with custom panel providers, domain registrars, and subscription adapters.

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Creating a Custom Panel Provider](#creating-a-custom-panel-provider)
- [Creating a Custom Domain Registrar](#creating-a-custom-domain-registrar)
- [Creating a Custom Subscription Adapter](#creating-a-custom-subscription-adapter)
- [Working with Hooks](#working-with-hooks)
- [Template Overrides](#template-overrides)
- [REST API](#rest-api)
- [Database Tables](#database-tables)

---

## Architecture Overview

HostForge uses a modular architecture built on WordPress and WooCommerce:

- **Namespace**: `HostForge\` maps to `includes/`, `HostForge\Modules\{Name}\` maps to `modules/{slug}/`
- **Autoloader**: PSR-4 via `includes/class-hf-autoloader.php`
- **Module Manager**: Each module extends `Abstract_HF_Module` and is registered in `HF_Module_Manager`
- **Interfaces**: Core contracts in `includes/interfaces/`
- **Scheduled Tasks**: All use WooCommerce Action Scheduler (never WP-Cron)
- **Order Storage**: HPOS compatible (`$order->get_meta()` / `$order->update_meta_data()`)

### Plugin Lifecycle

```
plugins_loaded
  → hostforge_init()
    → HostForge::instance()->init()
      → load_helpers()          // hf-formatting-functions.php, hf-template-functions.php
      → load_textdomain()
      → init_product_types()    // Register 7 WC product types
      → init_module_manager()   // Register and load active modules
      → register_hooks()        // REST API init
      → init_admin()            // Admin menus, settings (admin only)
      → do_action('hostforge_loaded')
```

---

## Creating a Custom Panel Provider

Panel providers allow HostForge to communicate with server control panels (e.g., DirectAdmin, CyberPanel, Virtualmin).

### Step 1: Implement the Interface

Create a class that implements `HostForge\Interfaces\HF_Panel_Provider`:

```php
<?php
namespace MyPlugin\Providers;

use HostForge\Interfaces\HF_Panel_Provider;

class My_DirectAdmin_Provider implements HF_Panel_Provider {

    public function get_id(): string {
        return 'directadmin';
    }

    public function get_name(): string {
        return 'DirectAdmin';
    }

    public function test_connection( int $server_id ): bool {
        $hostname = get_post_meta( $server_id, '_hf_hostname', true );
        $api_token = get_post_meta( $server_id, '_hf_api_token', true );

        // Decrypt the token.
        $token = \HostForge\HF_Encryption::decrypt( $api_token );

        // Make API call to test connection.
        $response = wp_remote_get( "https://{$hostname}:2222/CMD_API_SHOW_ALL_USERS", array(
            'headers' => array( 'Authorization' => 'Basic ' . base64_encode( "admin:{$token}" ) ),
            'sslverify' => false,
            'timeout'   => 15,
        ) );

        return ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
    }

    public function create_account( array $params, int $server_id ): array|false {
        // $params contains: username, password, domain, package, email
        // Return array with account details on success, false on failure.
        // Example return: ['username' => 'user1', 'domain' => 'example.com', 'ip' => '1.2.3.4']
    }

    public function suspend_account( string $username, int $server_id, string $reason = '' ): bool {
        // Suspend the account. Return true on success.
    }

    public function unsuspend_account( string $username, int $server_id ): bool {
        // Unsuspend the account. Return true on success.
    }

    public function terminate_account( string $username, int $server_id ): bool {
        // Remove the account. Return true on success.
    }

    public function change_package( string $username, string $package, int $server_id ): bool {
        // Change the hosting package. Return true on success.
    }

    public function get_account_stats( string $username, int $server_id ): array {
        // Return resource usage: disk_used, disk_limit, bandwidth_used, bandwidth_limit, etc.
        return array(
            'disk_used'       => 0,
            'disk_limit'      => 0,
            'bandwidth_used'  => 0,
            'bandwidth_limit' => 0,
        );
    }

    public function list_packages( int $server_id ): array {
        // Return array of available hosting packages.
        return array();
    }

    public function get_login_url( string $username, int $server_id ): string {
        // Return SSO/login URL for the user's control panel.
        return '';
    }

    public function change_password( string $username, string $new_password, int $server_id ): bool {
        // Change account password. Return true on success.
    }
}
```

### Step 2: Register the Provider

Use the `hostforge_panel_providers` filter:

```php
add_filter( 'hostforge_panel_providers', function( array $providers ): array {
    $providers['directadmin'] = new \MyPlugin\Providers\My_DirectAdmin_Provider();
    return $providers;
} );
```

---

## Creating a Custom Domain Registrar

Domain registrars handle domain registration, transfer, renewal, and DNS management.

### Step 1: Implement the Interface

Create a class that implements `HostForge\Interfaces\HF_Registrar`:

```php
<?php
namespace MyPlugin\Registrars;

use HostForge\Interfaces\HF_Registrar;

class My_Cloudflare_Registrar implements HF_Registrar {

    public function get_id(): string {
        return 'cloudflare';
    }

    public function get_name(): string {
        return 'Cloudflare Registrar';
    }

    public function check_availability( string $domain ): bool {
        // Check if domain is available for registration.
    }

    public function check_availability_bulk( array $domains ): array {
        // Check multiple domains. Return array of ['domain' => bool].
    }

    public function register_domain( string $domain, array $contact, int $years = 1 ): bool {
        // Register a new domain. $contact contains registrant info.
    }

    public function transfer_domain( string $domain, string $epp_code, array $contact ): bool {
        // Initiate domain transfer.
    }

    public function renew_domain( string $domain, int $years = 1 ): bool {
        // Renew domain registration.
    }

    public function get_domain_info( string $domain ): array {
        // Return domain details: status, expiry_date, nameservers, locked, etc.
        return array(
            'status'      => 'active',
            'expiry_date' => '2027-01-01',
            'nameservers' => array( 'ns1.example.com', 'ns2.example.com' ),
            'locked'      => true,
        );
    }

    public function set_nameservers( string $domain, array $nameservers ): bool {
        // Set domain nameservers.
    }

    public function get_nameservers( string $domain ): array {
        // Get current nameservers.
    }

    public function lock_domain( string $domain ): bool {
        // Enable registrar lock.
    }

    public function unlock_domain( string $domain ): bool {
        // Disable registrar lock.
    }

    public function get_epp_code( string $domain ): string {
        // Get EPP/authorization code for transfer.
    }

    public function get_dns_records( string $domain ): array {
        // Return DNS records.
    }

    public function add_dns_record( string $domain, array $record ): bool {
        // Add a DNS record. $record: type, name, value, ttl, priority.
    }

    public function update_dns_record( string $domain, int $record_id, array $record ): bool {
        // Update an existing DNS record.
    }

    public function delete_dns_record( string $domain, int $record_id ): bool {
        // Delete a DNS record.
    }

    public function get_tld_pricing(): array {
        // Return pricing for available TLDs.
        // ['com' => ['register' => 9.99, 'renew' => 9.99, 'transfer' => 9.99], ...]
    }

    public function get_contact_info( string $domain ): array {
        // Return WHOIS contact information.
    }
}
```

### Step 2: Register the Registrar

Use the `hostforge_registrars` filter:

```php
add_filter( 'hostforge_registrars', function( array $registrars ): array {
    $registrars['cloudflare'] = new \MyPlugin\Registrars\My_Cloudflare_Registrar();
    return $registrars;
} );
```

---

## Creating a Custom Subscription Adapter

Subscription adapters allow HostForge to work with any subscription plugin.

### Step 1: Implement the Interface

Create a class that implements `HostForge\Interfaces\HF_Subscription_Adapter`:

```php
<?php
namespace MyPlugin\Subscriptions;

use HostForge\Interfaces\HF_Subscription_Adapter;

class My_Custom_Adapter implements HF_Subscription_Adapter {

    public function is_available(): bool {
        // Return true if the subscription plugin is active.
        return defined( 'MY_SUBS_VERSION' );
    }

    public function get_name(): string {
        return 'My Subscription Plugin';
    }

    public function create_subscription( array $params ): int|false {
        // Create a subscription. $params: order_id, product_id, user_id.
        // Return subscription ID on success, false on failure.
    }

    public function cancel_subscription( int $subscription_id ): bool {
        // Cancel the subscription. Return true on success.
    }

    public function suspend_subscription( int $subscription_id ): bool {
        // Put subscription on hold. Return true on success.
    }

    public function reactivate_subscription( int $subscription_id ): bool {
        // Reactivate a suspended subscription. Return true on success.
    }

    public function get_status( int $subscription_id ): string {
        // Return normalized status: active, on-hold, cancelled, expired, pending.
    }

    public function get_next_payment_date( int $subscription_id ): ?string {
        // Return next payment date in Y-m-d H:i:s format, or null.
    }

    public function get_subscriptions_by_user( int $user_id ): array {
        // Return array of subscription IDs for the user.
    }

    public function get_status_hooks(): array {
        // Map HostForge events to your plugin's action hooks.
        return array(
            'activated'   => 'my_subs_status_active',
            'suspended'   => 'my_subs_status_on_hold',
            'cancelled'   => 'my_subs_status_cancelled',
            'expired'     => 'my_subs_status_expired',
            'reactivated' => 'my_subs_reactivated',
            'renewed'     => 'my_subs_renewal_complete',
        );
    }
}
```

### Step 2: Register the Adapter

Use the `hostforge_subscription_adapters` filter:

```php
add_filter( 'hostforge_subscription_adapters', function( array $adapters ): array {
    $adapters[] = 'MyPlugin\\Subscriptions\\My_Custom_Adapter';
    return $adapters;
} );
```

---

## Working with Hooks

HostForge provides extensive action and filter hooks. See [hooks-reference.md](hooks-reference.md) for the complete list.

### Key Extension Points

**Modify provisioning parameters before account creation:**

```php
add_filter( 'hostforge_provision_params', function( array $params, int $order_id ): array {
    // Add custom nameservers.
    $params['nameserver1'] = 'ns1.myhosting.com';
    $params['nameserver2'] = 'ns2.myhosting.com';
    return $params;
}, 10, 2 );
```

**Customize generated usernames:**

```php
add_filter( 'hostforge_generated_username', function( string $username, string $domain, int $user_id ): string {
    // Use user login as the cPanel username prefix.
    $user = get_user_by( 'id', $user_id );
    return substr( sanitize_user( $user->user_login ), 0, 8 );
}, 10, 3 );
```

**Override server selection logic:**

```php
add_filter( 'hostforge_select_server', function( int $server_id, int $product_id, string $group ): int {
    // Force a specific server for VPS products.
    $product = wc_get_product( $product_id );
    if ( $product && 'hf_vps_server' === $product->get_type() ) {
        return 42; // Your VPS server ID.
    }
    return $server_id;
}, 10, 3 );
```

**Add custom checkout fields for hosting products:**

```php
add_filter( 'hostforge_checkout_fields', function( array $fields, array $types_in_cart ): array {
    if ( in_array( 'hf_shared_hosting', $types_in_cart, true ) ) {
        $fields['hf_preferred_php'] = array(
            'type'    => 'select',
            'label'   => __( 'Preferred PHP Version', 'my-plugin' ),
            'options' => array( '8.0' => 'PHP 8.0', '8.1' => 'PHP 8.1', '8.2' => 'PHP 8.2' ),
        );
    }
    return $fields;
}, 10, 2 );
```

**React to provisioning events:**

```php
// Send Slack notification on new provisioning.
add_action( 'hostforge_after_provision', function( int $service_id, array $account_data ): void {
    $domain = $account_data['domain'] ?? 'unknown';
    wp_remote_post( 'https://hooks.slack.com/services/...', array(
        'body' => wp_json_encode( array(
            'text' => "New hosting account provisioned: {$domain}",
        ) ),
        'headers' => array( 'Content-Type' => 'application/json' ),
    ) );
}, 10, 2 );
```

**Customize fraud detection:**

```php
add_filter( 'hostforge_fraud_check_result', function( array $result, \WC_Order $order ): array {
    // Block free email providers for high-value orders.
    $email = $order->get_billing_email();
    $total = (float) $order->get_total();

    if ( $total > 100 && preg_match( '/@(gmail|yahoo|hotmail)\./i', $email ) ) {
        $result['passed']    = false;
        $result['reasons'][] = 'Free email provider on high-value order';
    }

    return $result;
}, 10, 2 );
```

---

## Template Overrides

Frontend and email templates can be overridden in your theme:

```
your-theme/hostforge/{template-name}.php
```

### Available Templates

**Frontend (My Account):**
- `service-list.php` — Hosting services list
- `service-detail.php` — Single service detail
- `ticket-list.php` — Support tickets list
- `ticket-detail.php` — Single ticket detail
- `ticket-new.php` — New ticket form
- `domain-list.php` — Domains list
- `domain-detail.php` — Single domain detail
- `kb-archive.php` — Knowledge base listing
- `kb-single.php` — Single KB article
- `kb-category.php` — KB category archive

**Emails:**
- `service-welcome.php` — New service welcome
- `service-suspended.php` — Service suspended
- `service-unsuspended.php` — Service reactivated
- `service-terminated.php` — Service terminated
- `provision-failed.php` — Provisioning failure
- `ticket-new-staff.php` — New ticket (staff notification)
- `ticket-reply.php` — Ticket reply
- `ticket-closed.php` — Ticket closed
- `domain-registered.php` — Domain registered
- `domain-expiry-reminder.php` — Domain expiry reminder

### Using hf_locate_template()

```php
$template = hf_locate_template( 'service-list.php', array(
    'services' => $services,
    'user_id'  => get_current_user_id(),
) );
```

The function checks: theme → plugin templates directory.

---

## REST API

HostForge registers REST API endpoints under the `hf/v1` namespace.

### Authentication

All admin endpoints require the `manage_woocommerce` capability. Use WordPress application passwords, cookie authentication, or nonce-based auth.

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/hf/v1/status` | Plugin status and version |
| GET | `/hf/v1/servers` | List servers |
| GET | `/hf/v1/servers/{id}` | Get server details |
| POST | `/hf/v1/servers/{id}/test` | Test server connection |
| GET | `/hf/v1/services` | List services |
| GET | `/hf/v1/services/{id}` | Get service details |
| POST | `/hf/v1/services/{id}/action` | Execute service action |
| GET | `/hf/v1/tickets` | List tickets |
| POST | `/hf/v1/tickets` | Create ticket |
| GET | `/hf/v1/tickets/{id}` | Get ticket details |
| POST | `/hf/v1/tickets/{id}/reply` | Reply to ticket |
| GET | `/hf/v1/domains` | List domains |
| GET | `/hf/v1/domains/{id}` | Get domain details |
| GET | `/hf/v1/domains/check` | Check domain availability |
| GET | `/hf/v1/reports/revenue` | Revenue data |
| GET | `/hf/v1/reports/services` | Service statistics |
| GET | `/hf/v1/kb` | Knowledge base articles |

---

## Database Tables

HostForge creates these custom tables (all prefixed with `$wpdb->prefix`):

| Table | Module | Description |
|-------|--------|-------------|
| `hf_logs` | Core | System log entries |
| `hf_activity_log` | Core | User activity audit trail |
| `hf_provisioning_queue` | Auto Provisioning | Async provisioning job queue |
| `hf_dns_records` | Domain Manager | DNS record storage |
| `hf_tld_pricing` | Domain Manager | TLD pricing data |
| `hf_domain_queue` | Domain Manager | Async domain operation queue |
| `hf_login_attempts` | Security | Login attempt tracking |
| `hf_ip_blocks` | Security | IP blocklist/allowlist |

### Custom Post Types

| CPT | Module | Description |
|-----|--------|-------------|
| `hf_server` | Server Manager | Server configurations |
| `hf_service` | Auto Provisioning | Hosting service instances |
| `hf_ticket` | Support Desk | Support tickets |
| `hf_kb_article` | Support Desk | Knowledge base articles |
| `hf_canned_response` | Support Desk | Canned ticket responses |
| `hf_domain` | Domain Manager | Domain registrations |

### Custom Taxonomies

| Taxonomy | CPT | Description |
|----------|-----|-------------|
| `hf_server_group` | `hf_server` | Server groups for load balancing |
| `hf_department` | `hf_ticket` | Support departments |
| `hf_kb_category` | `hf_kb_article` | Knowledge base categories |
