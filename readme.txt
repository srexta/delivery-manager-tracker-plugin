=== Delivery Manager Tracking Plugin ===
Contributors: developer
Tags: delivery management, sprint tracking, project management
Requires at least: 5.0
Tested up to: 6.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for delivery managers to track sprints, story points, estimated hours, hotfixes, and individual developer velocity.

== Description ==

The Delivery Manager Tracking Plugin helps delivery managers track sprints, story points, estimated hours, hotfixes, and individual developer velocity. It also includes planning and retrospective notes.

**Features:**

* Custom Post Types: Sprint, Story, and Hotfix
* Track sprint goals, estimated hours, start/end dates, and notes
* Link stories to sprints and users, with story points and status
* Track urgent fixes within sprint duration
* View sprint data in the admin
* Add notes for sprint planning and retrospectives
* Filter by date range
* Show velocity per developer (committed vs completed story points)
* Show stories and hotfixes per user per sprint
* Calculate sprint velocity and burndown trends
* Export data (CSV or JSON)
* Different permission levels for managers, developers, and observers

**Requirements:**

* WordPress 5.0 or higher
* Advanced Custom Fields (ACF) Plugin

== Installation ==

1. Upload the `delivery-manager-tracking-plugin` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure Advanced Custom Fields (ACF) is installed and activated
4. Configure the plugin through the 'Delivery Manager' menu in the WordPress admin

== Frequently Asked Questions ==

= Does this plugin require Advanced Custom Fields? =

Yes, this plugin uses Advanced Custom Fields (ACF) to create and manage custom fields. Please ensure that ACF is installed and activated.

= Can I customize the fields for each post type? =

Yes, you can modify the ACF field groups to customize the fields according to your needs.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release 