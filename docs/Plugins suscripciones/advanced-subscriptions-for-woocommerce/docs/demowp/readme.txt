=== DemoWP ===
Contributors: joseconti
Tags: demo, sandbox, testing, staging, clone
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create temporary sandbox demos for users to test WordPress plugins and themes safely.

== Description ==

DemoWP allows developers and theme/plugin sellers to offer live demonstrations of their products. When a user visits the demo endpoint URL, a complete copy of the WordPress site (clone) is automatically created with:

* Independent database (using unique table prefixes)
* Separate file directory
* Temporary admin user
* Automatic login
* Security restrictions (cannot install/delete plugins or themes)
* Automatic cleanup after expiration

= Features =

* **Complete cloning**: Copies the database and files from the template site
* **Isolation**: Each demo is independent and does not affect the main site
* **Security**: Restrictions to prevent abuse (no plugin/theme installation)
* **Auto-cleanup**: Demos are automatically deleted after expiration
* **IP limit**: Control of simultaneous demos per IP address
* **Maintenance mode**: Ability to put the main site in maintenance mode
* **Licenses**: Automatic update system with license

= Use Cases =

* **Plugin/Theme Developers**: Offer live demos to potential buyers
* **Agencies**: Show demonstration sites to clients
* **Trainers**: Create practice environments for students
* **Technical Support**: Reproduce issues in isolated environments

= Requirements =

* PHP 7.4 or higher
* MySQL 5.7+ or MariaDB 10.3+
* WordPress 6.0 or higher
* Write permissions in wp-content

== Installation ==

1. Upload the `demowp` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to DemoWP > Settings to configure the plugin
4. Enter your license key to enable automatic updates

= Minimum Requirements =

* PHP 7.4 or greater
* MySQL 5.7 or greater
* WordPress 6.0 or greater

= Automatic Installation =

With a valid license, updates will appear automatically in Dashboard > Updates.

= Manual Installation =

1. Download the plugin ZIP file from your account at plugins.joseconti.com
2. Go to Plugins > Add New > Upload Plugin
3. Select the ZIP file and click Install Now
4. Activate the plugin

== Frequently Asked Questions ==

= How long does it take to create a demo? =

It depends on the size of the site (database and files). Typically:
* Small site: 5-15 seconds
* Medium site: 15-30 seconds
* Large site: 30-60 seconds

= How many demos can I have active? =

There is no global limit. The limit is per user IP (configurable, default 3).

= Can users install plugins in the demos? =

No. Demos have restrictions that block:
* Installing/deleting plugins
* Installing/deleting themes
* Editing files
* Updating WordPress

They can:
* Activate/deactivate existing plugins
* Switch between installed themes
* Modify settings

= What happens when a demo expires? =

1. The demo is marked as expired
2. Action Scheduler schedules its deletion
3. Database tables and files are deleted
4. The record is removed from the tracker

= Do I need a license to use the plugin? =

The plugin works without a license, but you will not receive automatic updates.

= Does it affect my site's performance? =

* **During creation**: There is CPU/IO load for a few seconds
* **With active demos**: Minimal impact (separate tables and files)
* **License server down**: Does not affect (5 second timeout)

== Screenshots ==

1. Settings page - Configure demo endpoint, lifetime, and limits
2. Active demos - View and manage all active demo installations
3. Statistics - Monitor demo usage and trends
4. Demo creation page - What users see when creating a demo
5. Demo admin - The restricted admin interface in demo mode

== Changelog ==

= 1.0.0 - 2025-01-10 =
* Initial release
* Complete site cloning (database and files)
* Automatic login system with secure tokens
* Security restrictions via MU-Plugin
* Automatic cleanup with Action Scheduler
* IP-based demo limits
* Maintenance mode for main site
* License system for automatic updates
* Admin dashboard with statistics

== Upgrade Notice ==

= 1.0.0 =
Initial release of DemoWP.
