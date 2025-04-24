<?php
/**
 * The class responsible for managing custom roles and permissions.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Permissions')) {

    /**
     * Class for managing custom roles and permissions.
     */
    class DMTP_Permissions {

        /**
         * Set up custom roles and capabilities.
         */
        public function setup_roles() {
            // Add Delivery Manager role
            add_role(
                'dmtp_manager',
                __('Delivery Manager', 'delivery-manager-tracking-plugin'),
                array(
                    'read' => true,
                    'edit_posts' => true,
                    'delete_posts' => true,
                    'publish_posts' => true,
                    'upload_files' => true,
                )
            );

            // Add Developer role
            add_role(
                'dmtp_developer',
                __('DMTP Developer', 'delivery-manager-tracking-plugin'),
                array(
                    'read' => true,
                    'edit_posts' => true,
                    'delete_posts' => false,
                    'publish_posts' => false,
                    'upload_files' => true,
                )
            );

            // Add Observer role
            add_role(
                'dmtp_observer',
                __('DMTP Observer', 'delivery-manager-tracking-plugin'),
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'publish_posts' => false,
                    'upload_files' => false,
                )
            );

            // Add capabilities for sprint post type
            $this->add_post_type_caps('dmtp_sprint');

            // Add capabilities for story post type
            $this->add_post_type_caps('dmtp_story');

            // Add capabilities for hotfix post type
            $this->add_post_type_caps('dmtp_hotfix');

            // Assign capabilities to roles
            $this->assign_capabilities();
        }

        /**
         * Add capabilities for managing a specific post type.
         *
         * @param string $post_type The post type to add capabilities for.
         */
        private function add_post_type_caps($post_type) {
            // Get existing roles
            $admin = get_role('administrator');
            $manager = get_role('dmtp_manager');

            // Define capabilities for the post type
            $caps = array(
                "edit_{$post_type}",
                "read_{$post_type}",
                "delete_{$post_type}",
                "edit_{$post_type}s",
                "edit_others_{$post_type}s",
                "publish_{$post_type}s",
                "read_private_{$post_type}s",
                "delete_{$post_type}s",
                "delete_private_{$post_type}s",
                "delete_published_{$post_type}s",
                "delete_others_{$post_type}s",
                "edit_private_{$post_type}s",
                "edit_published_{$post_type}s",
            );

            // Add capabilities to admin and manager roles
            foreach ($caps as $cap) {
                if ($admin) {
                    $admin->add_cap($cap);
                }
                
                if ($manager) {
                    $manager->add_cap($cap);
                }
            }
        }

        /**
         * Assign specific capabilities to different roles.
         */
        private function assign_capabilities() {
            // Get existing roles
            $developer = get_role('dmtp_developer');
            $observer = get_role('dmtp_observer');

            // For developers: can edit/read their own items but not others'
            if ($developer) {
                // Sprint capabilities
                $developer->add_cap('read_dmtp_sprint');
                $developer->add_cap('edit_dmtp_sprints'); // Can only edit their own
                
                // Story capabilities
                $developer->add_cap('read_dmtp_story');
                $developer->add_cap('edit_dmtp_stories'); // Can only edit their own
                $developer->add_cap('edit_published_dmtp_stories');
                
                // Hotfix capabilities
                $developer->add_cap('read_dmtp_hotfix');
                $developer->add_cap('edit_dmtp_hotfixes'); // Can only edit their own
                $developer->add_cap('edit_published_dmtp_hotfixes');
            }

            // For observers: can only read
            if ($observer) {
                // Sprint capabilities
                $observer->add_cap('read_dmtp_sprint');
                
                // Story capabilities
                $observer->add_cap('read_dmtp_story');
                
                // Hotfix capabilities
                $observer->add_cap('read_dmtp_hotfix');
            }
        }

        /**
         * Filter queries to limit developers to only see their own content.
         *
         * @param WP_Query $query The WordPress query object.
         * @return WP_Query The modified query.
         */
        public function filter_queries_by_user($query) {
            global $pagenow, $post_type;
            
            // Only apply on admin page for our post types
            if (!is_admin() || $pagenow !== 'edit.php' || 
                !in_array($post_type, array('dmtp_sprint', 'dmtp_story', 'dmtp_hotfix'))) {
                return $query;
            }
            
            // Get current user
            $user = wp_get_current_user();
            
            // If user is developer, limit to their own posts
            if (in_array('dmtp_developer', $user->roles)) {
                $query->set('author', $user->ID);
            }
            
            return $query;
        }

        /**
         * Restrict access to post editing pages based on user permissions.
         */
        public function restrict_post_access() {
            global $pagenow, $post;
            
            // Only apply on post edit pages
            if ($pagenow != 'post.php' || !isset($_GET['action']) || $_GET['action'] != 'edit') {
                return;
            }
            
            // Get current user and post
            $user = wp_get_current_user();
            $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
            $post = get_post($post_id);
            
            // Skip if not our post types
            if (!$post || !in_array($post->post_type, array('dmtp_sprint', 'dmtp_story', 'dmtp_hotfix'))) {
                return;
            }
            
            // If developer, check if post is assigned to them
            if (in_array('dmtp_developer', $user->roles) && $post->post_author != $user->ID) {
                // For stories and hotfixes, check if assigned to this user
                if ($post->post_type == 'dmtp_story' || $post->post_type == 'dmtp_hotfix') {
                    $assigned_user = get_post_meta($post_id, 'assigned_user', true);
                    if ($assigned_user != $user->ID) {
                        wp_die(__('You do not have permission to edit this item.', 'delivery-manager-tracking-plugin'));
                    }
                } else {
                    wp_die(__('You do not have permission to edit this item.', 'delivery-manager-tracking-plugin'));
                }
            }
            
            // If observer, redirect away from edit page
            if (in_array('dmtp_observer', $user->roles)) {
                wp_redirect(admin_url());
                exit;
            }
        }
    }
} 