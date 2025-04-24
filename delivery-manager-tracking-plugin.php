<?php
/**
 * Delivery Manager Tracking Plugin
 *
 * @package    DMTP
 * @author     Developer
 * @license    GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       Delivery Manager Tracking Plugin
 * Plugin URI:        https://example.com/delivery-manager-tracking-plugin
 * Description:       A plugin for delivery managers to track sprints, story points, estimated hours, hotfixes, and individual developer velocity.
 * Version:           1.0.0
 * Author:            Developer
 * Author URI:        https://example.com
 * Text Domain:       delivery-manager-tracking-plugin
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('DMTP_VERSION', '1.0.0');
define('DMTP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DMTP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DMTP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function dmtp_activate() {
    // Activation tasks
    // Flush rewrite rules after registering custom post types
    flush_rewrite_rules();
}

/**
 * The code that runs during plugin deactivation.
 */
function dmtp_deactivate() {
    // Deactivation tasks
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'dmtp_activate');
register_deactivation_hook(__FILE__, 'dmtp_deactivate');

/**
 * Require the loader file which will handle including all other classes.
 */
require_once DMTP_PLUGIN_DIR . 'includes/class-dmtp-loader.php';

/**
 * Begins execution of the plugin.
 */
function dmtp_run() {
    $plugin = new DMTP_Loader();
    $plugin->run();
}

dmtp_run(); 