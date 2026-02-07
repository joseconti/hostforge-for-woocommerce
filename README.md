# HostForge for WooCommerce

Modular hosting management platform for WooCommerce. Sell shared hosting, reseller, VPS, dedicated servers, domains, SSL certificates, and software licenses with automatic provisioning via cPanel and Plesk.

## Description

HostForge transforms WooCommerce into a complete hosting management system. Each feature is implemented as an independent module that can be activated or deactivated from the admin panel without affecting the rest of the platform.

### Key Features

- **7 WooCommerce Product Types**: Shared Hosting, Reseller Hosting, VPS, Dedicated Server, Domain, SSL Certificate, Software License
- **Server Manager**: Connect and manage cPanel (WHM API) and Plesk servers with health monitoring
- **Auto Provisioning**: Automatic account creation, suspension, unsuspension, and termination on order/subscription events
- **Support Desk**: Full ticket system with departments, priorities, canned responses, knowledge base, and IMAP piping
- **Domain Manager**: Domain registration, transfer, and renewal via Namecheap API with DNS management
- **Security**: Brute force protection, IP management, fraud detection, CAPTCHA integration, audit logging
- **Notifications**: 11 WooCommerce email templates with merge tags for services, tickets, and domains
- **Reports**: Revenue charts, MRR tracking, service/ticket/domain statistics, CSV export

### Architecture

- **Modular**: Each module activates/deactivates independently
- **PHP 8.0+** with PSR-4 autoloading and type hints
- **WordPress Coding Standards** compliant
- **WooCommerce native**: Uses WC products, orders, subscriptions, and checkout
- **HPOS compatible**: High-Performance Order Storage support
- **Action Scheduler**: All scheduled tasks use WooCommerce Action Scheduler (no WP-Cron)
- **No billing/invoicing**: Users install their own billing plugin

## Requirements

| Requirement | Minimum |
|-------------|---------|
| PHP | 8.0 |
| WordPress | 6.0 |
| WooCommerce | 8.0 |

### Subscription Plugins (one required for recurring products)

HostForge supports four subscription plugins via its adapter system:

- [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/)
- [YITH WooCommerce Subscription](https://yithemes.com/themes/plugins/yith-woocommerce-subscription/)
- [Advanced Subscriptions for WooCommerce](https://joseconti.com/advanced-subscriptions/)
- [SUMO Subscriptions](https://codecanyon.net/item/sumo-subscriptions-woocommerce-subscription-system/16486054)

### Server Panels (for auto-provisioning)

- cPanel/WHM (API token authentication)
- Plesk (API key authentication)

### Domain Registrars (for domain management)

- Namecheap (XML API with sandbox support)

## Installation

1. Upload the `hostforge-for-woocommerce` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **HostForge > Settings** to configure general options
4. Go to **HostForge > Modules** to activate the modules you need
5. Configure each module from its respective admin page

## Module Configuration

### Server Manager

1. Go to **HostForge > Servers > Add New**
2. Enter server hostname, select panel type (cPanel/Plesk)
3. Provide API credentials (token for WHM, API key for Plesk)
4. Test connection and save
5. Optionally assign the server to a server group

### Auto Provisioning

1. Ensure at least one server is configured in Server Manager
2. Go to **HostForge > Automation Settings**
3. Configure auto-provisioning on order completion
4. Set auto-suspend and auto-terminate schedules
5. Create hosting products and assign server groups and packages

### Support Desk

1. Go to **HostForge > Tickets** to manage tickets
2. Configure departments from **HostForge > Departments**
3. Set up canned responses for common replies
4. Manage knowledge base articles from **HostForge > Knowledge Base**

### Domain Manager

1. Go to **HostForge > Registrar Settings** and configure Namecheap credentials
2. Import TLD pricing from **HostForge > TLD Pricing**
3. Create domain products in WooCommerce

### Security

1. Go to **HostForge > Security > Settings**
2. Configure brute force protection thresholds
3. Set up IP allowlist/blocklist
4. Enable CAPTCHA (Cloudflare Turnstile or Google reCAPTCHA)
5. Configure fraud detection rules

## Custom Providers and Adapters

HostForge is designed to be extensible. You can create custom:

- **Panel Providers** — Implement `HF_Panel_Provider` interface for new server panels
- **Domain Registrars** — Implement `HF_Registrar` interface for new registrars
- **Subscription Adapters** — Implement `HF_Subscription_Adapter` interface for new subscription plugins

See the [Developer Guide](docs/developer-guide.md) for detailed instructions.

## Hooks and Filters

HostForge provides extensive hooks for customization. See the [Hooks Reference](docs/hooks-reference.md) for the complete list.

### Key Filters

- `hostforge_subscription_adapters` — Register custom subscription adapters
- `hostforge_panel_providers` — Register custom panel providers
- `hostforge_registrars` — Register custom domain registrars
- `hostforge_active_modules` — Modify active modules list

## File Structure

```
hostforge-for-woocommerce/
  hostforge-for-woocommerce.php   Main plugin file
  uninstall.php                    Data cleanup on uninstall
  includes/                        Core classes
    admin/                         Admin screens and settings
    abstracts/                     Abstract base classes
    interfaces/                    Contracts for providers/adapters
    traits/                        Shared traits
    subscriptions/                 Subscription plugin adapters
    products/                      WC product types and handlers
  modules/                         Independent modules
    server-manager/                cPanel/Plesk server management
    auto-provisioning/             Automatic service provisioning
    support-desk/                  Ticket system and knowledge base
    domain-manager/                Domain registration and DNS
    security/                      Security features
    notifications/                 WooCommerce email templates
    reports/                       Analytics and CSV export
  templates/                       Overrideable templates
    admin/                         Admin page templates
    frontend/                      My Account templates
    emails/                        Email templates
  assets/
    css/                           Stylesheets
    js/                            JavaScript files
  languages/                       Translation files
```

## Template Overrides

Frontend and email templates can be overridden by copying them to your theme:

```
your-theme/hostforge/service-list.php
your-theme/hostforge/service-detail.php
your-theme/hostforge/ticket-list.php
your-theme/hostforge/ticket-detail.php
...
```

## Frequently Asked Questions

### Does HostForge handle invoicing or billing?

No. HostForge creates WooCommerce orders and subscriptions. For invoicing, install a dedicated billing plugin that suits your country's regulations.

### Can I use HostForge without a subscription plugin?

Yes, but recurring billing for hosting services requires a subscription plugin. One-time products (domains, SSL certificates) work without one.

### Which subscription plugin should I choose?

All four supported plugins work well. WooCommerce Subscriptions is the most popular. Choose based on your existing setup and preferences.

### Can I add support for other server panels?

Yes. Implement the `HF_Panel_Provider` interface and register your provider via the `hostforge_panel_providers` filter. See the Developer Guide.

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

## Author

[Jose Conti](https://joseconti.com)
