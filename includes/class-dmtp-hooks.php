<?php
/**
 * The class responsible for handling WordPress hooks.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Hooks')) {

    /**
     * Class for handling WordPress hooks.
     */
    class DMTP_Hooks {

        /**
         * Hook: save_post for sprint post type.
         * 
         * @param int     $post_id Post ID.
         * @param WP_Post $post    Post object.
         * @param bool    $update  Whether this is an existing post being updated.
         */
        public function save_sprint_post($post_id, $post, $update) {
            // Skip if this is an autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            
            // Skip if this is not a sprint post
            if ($post->post_type !== 'dmtp_sprint') {
                return;
            }
            
            // Check if we need to update total story points
            // $this->update_sprint_story_points($post_id);
            // Check if we need to update total story estimated hours
            // $this->update_sprint_story_estimated_hours($post_id);
        }
        
        /**
         * Hook: save_post for story post type.
         * 
         * @param int     $post_id Post ID.
         * @param WP_Post $post    Post object.
         * @param bool    $update  Whether this is an existing post being updated.
         */
        public function save_story_post($post_id, $post, $update) {
            // Skip if this is an autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            
            // Skip if this is not a story post
            if ($post->post_type !== 'dmtp_story') {
                return;
            }
            
            // Get related sprint
            $sprint_id = get_post_meta($post_id, 'related_sprint', true);
            
            if ($sprint_id) {
                // Update the sprint's total story points
                // $this->update_sprint_story_points($sprint_id);
                // Update the sprint's total story estimated hours
                // $this->update_sprint_story_estimated_hours($sprint_id);
            }
        }
        
        /**
         * Update a sprint's total story points based on its stories.
         * 
         * @param int $sprint_id The sprint ID.
         */
        private function update_sprint_story_points($sprint_id) {
            // Get all stories for this sprint
            $args = array(
                'post_type' => 'dmtp_story',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'related_sprint',
                        'value' => $sprint_id,
                        'compare' => '=',
                    ),
                ),
            );
            
            $query = new WP_Query($args);
            
            $total_points = 0;
            
            foreach ($query->posts as $story) {
                $story_id = $story->ID;
                $points = get_post_meta($story_id, 'story_points', true);
                
                if (!empty($points)) {
                    $total_points += intval($points);
                }
            }
            
            // Update sprint meta
            update_post_meta($sprint_id, 'total_story_points', $total_points);
        }
        
        /**
         * Update a sprint's total estimated hours based on its stories.
         * 
         * @param int $sprint_id The sprint ID.
         */
        private function update_sprint_story_estimated_hours($sprint_id) {
            // Get all stories for this sprint
            $args = array(
                'post_type' => 'dmtp_story',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'related_sprint',
                        'value' => $sprint_id,
                        'compare' => '=',
                    ),
                ),
            );
            
            $query = new WP_Query($args);
            
            $total_hours = 0;
            
            foreach ($query->posts as $story) {
                $story_id = $story->ID;
                $hours = get_post_meta($story_id, 'estimated_hours', true);
                
                if (!empty($hours)) {
                    $total_hours += floatval($hours);
                }
            }
            
            // Update sprint meta
            update_post_meta($sprint_id, 'total_story_estimated_hours', $total_hours);
        }
        
        /**
         * Hook: pre_get_posts to filter query results.
         * 
         * @param WP_Query $query The WordPress query object.
         */
        public function filter_queries($query) {
            // Don't modify queries in the admin
            if (!is_admin()) {
                return;
            }
            
            // Only modify our custom post types
            $post_type = $query->get('post_type');
            
            if (!in_array($post_type, array('dmtp_sprint', 'dmtp_story', 'dmtp_hotfix'))) {
                return;
            }
            
            // Check user role
            $user = wp_get_current_user();
            
            // Developers can only see their own items
            if (in_array('dmtp_developer', $user->roles)) {
                // For stories and hotfixes, filter by assigned_user meta
                if (in_array($post_type, array('dmtp_story', 'dmtp_hotfix'))) {
                    $meta_query = $query->get('meta_query');
                    
                    if (!is_array($meta_query)) {
                        $meta_query = array();
                    }
                    
                    $meta_query[] = array(
                        'key' => 'assigned_user',
                        'value' => $user->ID,
                        'compare' => '=',
                    );
                    
                    $query->set('meta_query', $meta_query);
                }
                
                // For sprints, we'll allow seeing all sprints
            }
        }
        
        /**
         * Hook: wp_trash_post to handle proper cleanup.
         * 
         * @param int $post_id The post ID being trashed.
         */
        public function trash_post_cleanup($post_id) {
            $post = get_post($post_id);
            
            if (!$post) {
                return;
            }
            
            // If a sprint is being deleted, we need to handle orphaned stories
            if ($post->post_type === 'dmtp_sprint') {
                // Get all stories for this sprint
                $args = array(
                    'post_type' => 'dmtp_story',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => 'related_sprint',
                            'value' => $post_id,
                            'compare' => '=',
                        ),
                    ),
                );
                
                $query = new WP_Query($args);
                
                foreach ($query->posts as $story) {
                    // Option 1: Delete the stories
                    // wp_trash_post($story->ID);
                    
                    // Option 2: Just clear the related_sprint field
                    update_post_meta($story->ID, 'related_sprint', '');
                }
                
                // Clear the calculated total hours from the sprint as well
                delete_post_meta($post_id, 'total_story_estimated_hours');
                
                // Also handle hotfixes
                $args = array(
                    'post_type' => 'dmtp_hotfix',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => 'related_sprint',
                            'value' => $post_id,
                            'compare' => '=',
                        ),
                    ),
                );
                
                $query = new WP_Query($args);
                
                foreach ($query->posts as $hotfix) {
                    // Option 1: Delete the hotfixes
                    // wp_trash_post($hotfix->ID);
                    
                    // Option 2: Just clear the related_sprint field
                    update_post_meta($hotfix->ID, 'related_sprint', '');
                }
            }
        }
        
        /**
         * Hook: the_content to modify content display.
         * 
         * @param string $content The post content.
         * @return string The modified content.
         */
        public function modify_content_display($content) {
            global $post;
            
            if (!is_singular() || !in_the_loop() || !is_main_query()) {
                return $content;
            }
            
            // Add extra information to sprint display
            if ($post->post_type === 'dmtp_sprint') {
                $sprint_id = $post->ID;
                
                $start_date = get_post_meta($sprint_id, 'start_date', true);
                $end_date = get_post_meta($sprint_id, 'end_date', true);
                $goal = get_post_meta($sprint_id, 'sprint_goal', true);
                $estimated_hours = get_post_meta($sprint_id, 'estimated_hours', true);
                $total_story_points = get_post_meta($sprint_id, 'total_story_points', true);
                $total_story_est_hours = get_post_meta($sprint_id, 'total_story_estimated_hours', true);
                $assigned_team = get_post_meta($sprint_id, 'assigned_team', true);
                
                $additional_content = '<div class="dmtp-sprint-details">';
                $additional_content .= '<h3>' . __('Sprint Details', 'delivery-manager-tracking-plugin') . '</h3>';
                
                if (!empty($goal)) {
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Goal:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($goal) . '</div>';
                }
                
                if (!empty($start_date)) {
                    $formatted_start = date_i18n(get_option('date_format'), strtotime($start_date));
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Start Date:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($formatted_start) . '</div>';
                }
                
                if (!empty($end_date)) {
                    $formatted_end = date_i18n(get_option('date_format'), strtotime($end_date));
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('End Date:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($formatted_end) . '</div>';
                }
                
                if (!empty($estimated_hours)) {
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Estimated Hours:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($estimated_hours) . '</div>';
                }
                
                if (!empty($total_story_points)) {
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Total Story Points:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($total_story_points) . '</div>';
                }
                
                if (!empty($total_story_est_hours)) {
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Total Story Est. Hours:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($total_story_est_hours) . '</div>';
                }
                
                if (!empty($assigned_team)) {
                    // Maybe fetch team label from ACF choices if needed
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Team:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html(ucfirst($assigned_team)) . '</div>';
                }
                
                $additional_content .= '</div>';
                
                return $additional_content . $content;
            }
            
            // Add extra information to story display
            elseif ($post->post_type === 'dmtp_story') {
                $story_id = $post->ID;
                
                $sprint_id = get_post_meta($story_id, 'related_sprint', true);
                $user_id = get_post_meta($story_id, 'assigned_user', true);
                $story_points = get_post_meta($story_id, 'story_points', true);
                $status = get_post_meta($story_id, 'status', true);
                $est_hours = get_post_meta($story_id, 'estimated_hours', true);
                
                $additional_content = '<div class="dmtp-story-details">';
                $additional_content .= '<h3>' . __('Story Details', 'delivery-manager-tracking-plugin') . '</h3>';
                
                if (!empty($sprint_id)) {
                    $sprint = get_post($sprint_id);
                    if ($sprint) {
                        $additional_content .= '<div class="dmtp-detail"><strong>' . __('Sprint:', 'delivery-manager-tracking-plugin') . '</strong> <a href="' . get_permalink($sprint_id) . '">' . esc_html($sprint->post_title) . '</a></div>';
                    }
                }
                
                if (!empty($user_id)) {
                    $user = get_userdata($user_id);
                    if ($user) {
                        $additional_content .= '<div class="dmtp-detail"><strong>' . __('Assigned To:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($user->display_name) . '</div>';
                    }
                }
                
                if (!empty($story_points)) {
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Story Points:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($story_points) . '</div>';
                }
                
                if (!empty($est_hours)) {
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Estimated Hours:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($est_hours) . '</div>';
                }
                
                if (!empty($status)) {
                    $status_labels = array(
                        'backlog' => __('Backlog', 'delivery-manager-tracking-plugin'),
                        'in_progress' => __('In Progress', 'delivery-manager-tracking-plugin'),
                        'in_review' => __('In Review', 'delivery-manager-tracking-plugin'),
                        'done' => __('Done', 'delivery-manager-tracking-plugin'),
                        'blocked' => __('Blocked', 'delivery-manager-tracking-plugin'),
                    );
                    
                    $status_label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                    
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Status:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($status_label) . '</div>';
                }
                
                $additional_content .= '</div>';
                
                return $additional_content . $content;
            }
            
            // Add extra information to hotfix display
            elseif ($post->post_type === 'dmtp_hotfix') {
                $hotfix_id = $post->ID;
                
                $sprint_id = get_post_meta($hotfix_id, 'related_sprint', true);
                $user_id = get_post_meta($hotfix_id, 'assigned_user', true);
                $issue_description = get_post_meta($hotfix_id, 'issue_description', true);
                $resolution_summary = get_post_meta($hotfix_id, 'resolution_summary', true);
                
                $additional_content = '<div class="dmtp-hotfix-details">';
                $additional_content .= '<h3>' . __('Hotfix Details', 'delivery-manager-tracking-plugin') . '</h3>';
                
                if (!empty($sprint_id)) {
                    $sprint = get_post($sprint_id);
                    if ($sprint) {
                        $additional_content .= '<div class="dmtp-detail"><strong>' . __('Sprint:', 'delivery-manager-tracking-plugin') . '</strong> <a href="' . get_permalink($sprint_id) . '">' . esc_html($sprint->post_title) . '</a></div>';
                    }
                }
                
                if (!empty($user_id)) {
                    $user = get_userdata($user_id);
                    if ($user) {
                        $additional_content .= '<div class="dmtp-detail"><strong>' . __('Assigned To:', 'delivery-manager-tracking-plugin') . '</strong> ' . esc_html($user->display_name) . '</div>';
                    }
                }
                
                if (!empty($issue_description)) {
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Issue Description:', 'delivery-manager-tracking-plugin') . '</strong> ' . wp_kses_post($issue_description) . '</div>';
                }
                
                if (!empty($resolution_summary)) {
                    $additional_content .= '<div class="dmtp-detail"><strong>' . __('Resolution Summary:', 'delivery-manager-tracking-plugin') . '</strong> ' . wp_kses_post($resolution_summary) . '</div>';
                }
                
                $additional_content .= '</div>';
                
                return $additional_content . $content;
            }
            
            return $content;
        }

        /**
         * Filter the admin columns for the Sprint post type.
         *
         * @param array $columns Existing columns.
         * @return array Modified columns.
         */
        public function add_sprint_admin_columns($columns) {
            // Create a new columns array to control order
            $new_columns = array();

            // Keep checkbox and title
            if (isset($columns['cb'])) {
                $new_columns['cb'] = $columns['cb'];
                unset($columns['cb']);
            }
            if (isset($columns['title'])) {
                $new_columns['title'] = $columns['title'];
                unset($columns['title']);
            }

            // Add our custom columns
            $new_columns['duration'] = __('Duration', 'delivery-manager-tracking-plugin');
            $new_columns['teams'] = __('Team(s)', 'delivery-manager-tracking-plugin');
            $new_columns['hotfixes'] = __('Hotfixes #', 'delivery-manager-tracking-plugin');
            
            // Remove author if desired
            if (isset($columns['author'])) {
                 unset($columns['author']);
            }
            
            // Add back any remaining standard columns (like Date)
            $columns = array_merge($new_columns, $columns);

            return $columns;
        }

        /**
         * Display the content for custom Sprint admin columns.
         *
         * @param string $column  The name of the column.
         * @param int    $post_id The ID of the current post.
         */
        public function display_sprint_admin_columns($column, $post_id) {
            switch ($column) {
                case 'duration':
                    $start_date = get_post_meta($post_id, 'start_date', true);
                    $end_date = get_post_meta($post_id, 'end_date', true);
                    
                    if ($start_date && $end_date) {
                        // Format as 'M j - M j' (e.g., Sep 4 - Sep 20)
                        $start_formatted = date('M j', strtotime($start_date));
                        $end_formatted = date('M j', strtotime($end_date));
                        echo esc_html($start_formatted . ' - ' . $end_formatted);
                    } else {
                        echo 'N/A';
                    }
                    break;

                case 'teams':
                    $teams = get_post_meta($post_id, 'selected_teams', true);
                    if (!empty($teams) && is_array($teams)) {
                        echo esc_html(implode(', ', $teams));
                    } else {
                        echo 'N/A';
                    }
                    break;

                case 'hotfixes':
                    $args = array(
                        'post_type' => 'dmtp_hotfix',
                        'posts_per_page' => -1, // Count all
                        'meta_query' => array(
                            array(
                                'key' => 'related_sprint',
                                'value' => $post_id,
                                'compare' => '=',
                            ),
                        ),
                        'fields' => 'ids', // Only need IDs for counting
                    );
                    $hotfix_query = new WP_Query($args);
                    echo esc_html($hotfix_query->found_posts);
                    break;
            }
        }
    }
} 