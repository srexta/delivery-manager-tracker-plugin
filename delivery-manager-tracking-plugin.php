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

/**
 * Check if ACF or ACF PRO is active.
 * @return bool True if ACF or ACF PRO is active, false otherwise.
 */
function dmtp_is_acf_active() {
    // Check for ACF PRO or the free version
    if ( class_exists('ACF') ) {
        return true;
    }
    return false;
}

/**
 * Displays an admin notice if ACF is not active.
 */
function dmtp_acf_not_active_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php
            esc_html_e('The Delivery Manager Tracking Plugin requires Advanced Custom Fields (ACF) or ACF PRO to be installed and activated. Please install and activate ACF to use this plugin.', 'delivery-manager-tracking-plugin');
            // Optionally, provide a link to the ACF plugin page
            // printf(' <a href="%s" target="_blank">%s</a>', esc_url('https://wordpress.org/plugins/advanced-custom-fields/'), esc_html__('Get ACF here', 'delivery-manager-tracking-plugin'));
        ?></p>
    </div>
    <?php
}

// Hook the notice function if ACF is not active
if (!dmtp_is_acf_active()) {
    add_action('admin_notices', 'dmtp_acf_not_active_notice');

    // Optionally, prevent further execution if ACF is required for basic functionality
    // return; // Uncomment this if the plugin absolutely cannot run without ACF
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
    // Check for ACF on activation as well
    if (!dmtp_is_acf_active()) {
        // Optionally deactivate the plugin immediately
        deactivate_plugins(plugin_basename(__FILE__));
        // Trigger an admin notice about the deactivation
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php esc_html_e('Delivery Manager Tracking Plugin requires ACF or ACF PRO and has been deactivated because it is not active.', 'delivery-manager-tracking-plugin'); ?></p>
            </div>
            <?php
            // Remove the standard "Plugin activated." notice
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        });
        return; // Stop activation process
    }
    // Activation tasks
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

// --- Load the plugin only if ACF is active ---
if (dmtp_is_acf_active()) {

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
} // End ACF check 