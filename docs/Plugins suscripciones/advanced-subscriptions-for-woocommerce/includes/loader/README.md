# Advanced Subscriptions for WooCommerce Loader

This package bootstraps the legacy loader that ships with Advanced Subscriptions for WooCommerce. It contains the admin, public, and common modules that enqueue assets and register hooks while the main plugin migrates to the new architecture.

## Development notes

- All loader specific functions and hooks must use the `aswc_loader_` prefix.
- Shared helpers live in `includes/aswc-loader-common-functions.php`.
- Loader strings are localized with the `advanced-subscriptions-for-woocommerce` text domain.

