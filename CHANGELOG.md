# Changelog

All notable changes to HostForge for WooCommerce will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-07

### Added

#### Core & Foundations
- Plugin core with singleton pattern and PSR-4 autoloading
- Module manager with activate/deactivate support per module
- Dependency checker for PHP, WordPress, and WooCommerce versions
- AES-256-CBC encryption for sensitive credentials
- Admin dashboard, settings, modules, and log viewer pages
- REST API status endpoint
- Logging system with module-level filtering
- Activity log with user tracking
- HPOS compatibility declaration
- Action Scheduler integration (no WP-Cron)

#### WooCommerce Product Types
- Shared Hosting product type with server group, package, and resource fields
- Reseller Hosting product type (extends Shared Hosting)
- VPS Server product type with OS, CPU, RAM, disk, bandwidth fields
- Dedicated Server product type (extends VPS)
- Domain product type with TLD support
- SSL Certificate product type with validation levels
- Software License product type with license key generation
- Product add-ons system for configurable options
- Checkout fields with domain and configuration collection
- Order meta handler with HPOS support
- Admin product editor panels for all product types

#### Subscription Adapters
- Subscription adapter interface with factory auto-detection
- WooCommerce Subscriptions adapter
- YITH WooCommerce Subscription adapter
- Advanced Subscriptions for WooCommerce adapter
- SUMO Subscriptions adapter

#### Server Manager Module
- Custom post type for servers (hf_server) with server groups taxonomy
- cPanel/WHM provider with API token authentication (port 2087)
- Plesk provider with API key authentication (port 8443)
- Admin server list with group and status filters
- Server form for adding/editing servers
- Server monitoring dashboard with resource usage
- Test connection, fetch packages, delete server AJAX handlers
- REST API endpoints for server management

#### Auto Provisioning Module
- Custom post type for services (hf_service)
- Provisioning queue database table with retry logic
- Provisioning engine with WooCommerce order and subscription hooks
- Username generator (8-character domain-based)
- Password generator (12-32 character secure)
- Server selector (fewest accounts in group, capacity check)
- Automatic provision on order completion
- Automatic suspend on subscription hold/expiry
- Automatic unsuspend on subscription reactivation
- Automatic terminate on subscription cancellation
- My Account hosting services page with SSO, password change, usage stats
- Admin service management with status tabs
- REST API endpoints for service actions
- Welcome, suspended, and terminated email templates

#### Support Desk Module
- Custom post types for tickets, KB articles, and canned responses
- Department and KB category taxonomies
- Ticket reply system with staff and customer roles
- Auto-close inactive tickets via Action Scheduler
- IMAP email piping for ticket creation
- My Account support tickets page
- Knowledge base frontend with category browsing and article voting
- Admin ticket management with filters and bulk actions
- Canned responses for quick replies
- REST API for tickets, replies, and knowledge base
- New ticket, reply, and closed email templates

#### Domain Manager Module
- Custom post type for domains (hf_domain)
- DNS records, TLD pricing, and domain queue database tables
- Namecheap registrar with XML API and sandbox support
- Domain engine with order-based registration, transfer, and renewal
- Domain availability search with rate limiting
- Checkout domain flow (register/transfer/own)
- EPP code encryption for secure transfers
- My Account domains page with DNS management
- Admin domain management with TLD pricing editor
- REST API for domain operations
- Registered and expiry reminder email templates

#### Security Module
- Brute force protection with IP blocking after configurable max attempts
- IP manager with allowlist/blocklist and CIDR support
- Fraud detection on checkout (country, email, IP checks)
- CAPTCHA integration (Cloudflare Turnstile and Google reCAPTCHA)
- Audit log for authentication, user, module, service, ticket, and domain events
- Login attempts and IP blocks database tables
- Admin security settings, IP blocks, login attempts, and audit log pages
- REST API for security data

#### Notifications Module
- 11 WooCommerce email classes with full customization
- Merge tags system for services, tickets, and domains
- Service emails: welcome, suspended, unsuspended, terminated, provision failed
- Ticket emails: new ticket (staff), reply (customer), reply (staff), closed
- Domain emails: registered, expiry reminder
- Plain text email template variants

#### Reports Module
- Revenue and MRR charts with Chart.js
- Service, ticket, domain, and server statistics
- Customer growth tracking
- CSV export with BOM for Excel compatibility (5 export types)
- Admin reports dashboard with summary cards
- REST API for report data
