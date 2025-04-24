<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package DMTP
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define what happens during uninstallation
// Option 1: Leave all data intact (default)
// Option 2: Remove all data (uncomment code below)

/*
// Remove plugin options
delete_option('dmtp_settings');

// Get all custom post types
$post_types = array('dmtp_sprint', 'dmtp_story', 'dmtp_hotfix');

// Delete all posts for each custom post type
foreach ($post_types as $post_type) {
    $posts = get_posts(array(
        'post_type' => $post_type,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ));

    foreach ($posts as $post_id) {
        wp_delete_post($post_id, true);
    }
}

// Clean up metadata and taxonomies if needed
// This would need to be customized based on your implementation
*/ 