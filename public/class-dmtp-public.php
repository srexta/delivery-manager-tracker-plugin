<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Public')) {

    /**
     * Class for public-facing functionality.
     */
    class DMTP_Public {

        /**
         * Enqueue scripts and styles for the public-facing side.
         */
        public function enqueue_scripts() {
            // Enqueue styles for the roadmap
            wp_enqueue_style(
                'dmtp-public-styles',
                DMTP_PLUGIN_URL . 'assets/css/public-styles.css',
                array(),
                DMTP_VERSION
            );
            
            // Enqueue scripts for interactive features
            wp_enqueue_script(
                'dmtp-public-scripts',
                DMTP_PLUGIN_URL . 'assets/js/public-scripts.js',
                array('jquery'),
                DMTP_VERSION,
                true
            );
            
            // Localize script for AJAX
            wp_localize_script('dmtp-public-scripts', 'dmtp_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dmtp_update_task_status'),
                'updating_text' => __('Updating...', 'dmtp'),
                'error_text' => __('Error updating status. Please try again.', 'dmtp'),
                'success_text' => __('Status updated successfully!', 'dmtp')
            ));
        }
        
        /**
         * Sprint roadmap shortcode handler.
         * Usage: [dmtp_sprint_roadmap team="Marketing" view="roadmap" count="5" status="all"]
         *
         * @param array $atts Shortcode attributes.
         * @return string Rendered HTML output.
         */
        public function dmtp_sprint_roadmap_shortcode($atts) {
            // Parse shortcode attributes
            $atts = shortcode_atts(array(
                'team' => '',
                'view' => 'roadmap',
                'count' => 10,
                'status' => 'all',
                'show_team_filter' => 'true'
            ), $atts, 'dmtp_sprint_roadmap');

            // Check user permissions
            if (!$this->user_can_view_sprints()) {
                return '<div class="dmtp-error">You do not have permission to view sprint data.</div>';
            }

            // Get sprint data
            $sprints = $this->get_sprint_data($atts);
            
            if (empty($sprints)) {
                return '<div class="dmtp-no-data">No sprint data available.</div>';
            }

            // Generate output based on view type
            $output = '<div class="dmtp-sprint-roadmap" data-view="' . esc_attr($atts['view']) . '">';
            
            // Add team filter if enabled
            if ($atts['show_team_filter'] === 'true') {
                $output .= $this->render_team_filter($atts['team']);
            }
            
            // Render view
            switch ($atts['view']) {
                case 'timeline':
                    $output .= $this->render_timeline_view($sprints);
                    break;
                case 'cards':
                    $output .= $this->render_cards_view($sprints);
                    break;
                case 'roadmap':
                default:
                    $output .= $this->render_roadmap_view($sprints);
                    break;
            }
            
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Sprint marketing shortcode handler.
         * Usage: [dmtp_sprint_marketing team="Marketing" count="5" status="all"]
         *
         * @param array $atts Shortcode attributes.
         * @return string Rendered HTML output.
         */
        public function dmtp_sprint_marketing_shortcode($atts) {
            // Parse shortcode attributes
            $atts = shortcode_atts(array(
                'team' => '',
                'count' => 10,
                'status' => 'all',
                'show_team_filter' => 'true'
            ), $atts, 'dmtp_sprint_marketing');

            // Check user permissions
            if (!$this->user_can_view_sprints()) {
                return '<div class="dmtp-error">You do not have permission to view sprint data.</div>';
            }

            // Get sprint data
            $sprints = $this->get_sprint_data($atts);
            
            if (empty($sprints)) {
                return '<div class="dmtp-no-data">No sprint data available.</div>';
            }

            // Generate marketing-specific output
            $output = '<div class="dmtp-sprint-marketing">';
            
            // Add team filter if enabled
            if ($atts['show_team_filter'] === 'true') {
                $output .= $this->render_team_filter($atts['team']);
            }
            
            // Render marketing view
            $output .= $this->render_marketing_view($sprints);
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Sprint documentation shortcode handler.
         * Usage: [dmtp_sprint_documentation team="Documentation" count="5" status="all"]
         *
         * @param array $atts Shortcode attributes.
         * @return string Rendered HTML output.
         */
        public function dmtp_sprint_documentation_shortcode($atts) {
            // Parse shortcode attributes
            $atts = shortcode_atts(array(
                'team' => '',
                'count' => 10,
                'status' => 'all',
                'show_team_filter' => 'true'
            ), $atts, 'dmtp_sprint_documentation');

            // Check user permissions
            if (!$this->user_can_view_sprints()) {
                return '<div class="dmtp-error">You do not have permission to view sprint data.</div>';
            }

            // Get sprint data
            $sprints = $this->get_sprint_data($atts);
            
            if (empty($sprints)) {
                return '<div class="dmtp-no-data">No sprint data available.</div>';
            }

            // Generate documentation-specific output
            $output = '<div class="dmtp-sprint-documentation">';
            
            // Add team filter if enabled
            if ($atts['show_team_filter'] === 'true') {
                $output .= $this->render_team_filter($atts['team']);
            }
            
            // Render documentation view
            $output .= $this->render_documentation_view($sprints);
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Check if current user can view sprints.
         *
         * @return bool True if user can view sprints.
         */
        private function user_can_view_sprints() {
            if (!is_user_logged_in()) {
                return false;
            }
            
            $user = wp_get_current_user();
            $allowed_roles = array('administrator', 'dmtp_manager', 'dmtp_developer', 'dmtp_observer');
            
            return array_intersect($allowed_roles, $user->roles) ? true : false;
        }

        /**
         * Get sprint data based on shortcode attributes.
         *
         * @param array $atts Shortcode attributes.
         * @return array Array of sprint data.
         */
        private function get_sprint_data($atts) {
            $args = array(
                'post_type' => 'dmtp_sprint',
                'post_status' => 'publish',
                'posts_per_page' => intval($atts['count']),
                'orderby' => 'meta_value',
                'meta_key' => 'start_date',
                'order' => 'DESC'
            );

            // Filter by status if specified
            if ($atts['status'] !== 'all') {
                $today = date('Y-m-d');
                if ($atts['status'] === 'active') {
                    $args['meta_query'] = array(
                        array(
                            'key' => 'start_date',
                            'value' => $today,
                            'compare' => '<='
                        ),
                        array(
                            'key' => 'end_date',
                            'value' => $today,
                            'compare' => '>='
                        )
                    );
                } elseif ($atts['status'] === 'completed') {
                    $args['meta_query'] = array(
                        array(
                            'key' => 'end_date',
                            'value' => $today,
                            'compare' => '<'
                        )
                    );
                }
            }

            $query = new WP_Query($args);
            $sprints = array();

            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    
                    $sprint_data = array(
                        'id' => $post_id,
                        'title' => get_the_title($post_id),
                        'start_date' => get_field('start_date', $post_id),
                        'end_date' => get_field('end_date', $post_id),
                        'demonstration_date' => get_field('demonstration_date', $post_id),
                        'retrospective_day' => get_field('retrospective_day', $post_id),
                        'selected_teams' => get_field('selected_teams', $post_id),
                        'member_performance' => get_field('dmtp_member_performance', $post_id),
                        'planning_note' => get_field('planning_note', $post_id),
                        'retrospective_note' => get_field('retrospective_note', $post_id),
                        'sprint_team_hotfixes' => get_field('sprint_team_hotfixes', $post_id),
                        'marketing_tasks' => get_field('sprint_marketing_tasks', $post_id),
                        'documentation_tasks' => get_field('sprint_documentation_tasks', $post_id)
                    );
                    
                    // Filter by team if specified
                    if (!empty($atts['team']) && !empty($sprint_data['selected_teams'])) {
                        if (in_array($atts['team'], $sprint_data['selected_teams'])) {
                            $sprints[] = $sprint_data;
                        }
                    } else {
                        $sprints[] = $sprint_data;
                    }
                }
            }
            
            wp_reset_postdata();
            return $sprints;
        }

        /**
         * Render team filter dropdown.
         *
         * @param string $selected_team Currently selected team.
         * @return string HTML for team filter.
         */
        private function render_team_filter($selected_team) {
            $teams = $this->get_available_teams();
            
            if (empty($teams)) {
                return '';
            }
            
            $output = '<div class="dmtp-team-filter">';
            $output .= '<label for="dmtp-team-select">Filter by Team:</label>';
            $output .= '<select id="dmtp-team-select" class="dmtp-team-select">';
            $output .= '<option value="">All Teams</option>';
            
            foreach ($teams as $team) {
                $selected = ($selected_team === $team) ? 'selected' : '';
                $output .= '<option value="' . esc_attr($team) . '" ' . $selected . '>' . esc_html($team) . '</option>';
            }
            
            $output .= '</select>';
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Get available teams from settings.
         *
         * @return array Array of team names.
         */
        private function get_available_teams() {
            $teams_data = get_field('dmtp_teams', 'option');
            $teams = array();
            
            if (!empty($teams_data)) {
                foreach ($teams_data as $team) {
                    if (!empty($team['team_name'])) {
                        $teams[] = $team['team_name'];
                    }
                }
            }
            
            return $teams;
        }

        /**
         * Render roadmap view.
         *
         * @param array $sprints Sprint data.
         * @return string HTML for roadmap view.
         */
        private function render_roadmap_view($sprints) {
            $output = '<div class="dmtp-roadmap-view">';
            $output .= '<div class="dmtp-roadmap-timeline">';
            
            foreach ($sprints as $sprint) {
                $status_class = $this->get_sprint_status_class($sprint);
                $teams_data = !empty($sprint['selected_teams']) ? implode(',', $sprint['selected_teams']) : '';
                
                $output .= '<div class="dmtp-roadmap-item ' . $status_class . '" data-teams="' . esc_attr($teams_data) . '">';
                $output .= '<div class="dmtp-roadmap-marker"></div>';
                $output .= '<div class="dmtp-roadmap-content">';
                
                // Sprint header
                $output .= '<div class="dmtp-roadmap-header">';
                $output .= '<h3 class="dmtp-sprint-title">' . esc_html($sprint['title']) . '</h3>';
                $output .= '<div class="dmtp-sprint-dates">';
                $output .= '<span class="dmtp-start-date">' . $this->format_date($sprint['start_date']) . '</span>';
                $output .= ' → ';
                $output .= '<span class="dmtp-end-date">' . $this->format_date($sprint['end_date']) . '</span>';
                $output .= '</div>';
                $output .= '</div>';
                
                // Sprint details
                $output .= '<div class="dmtp-roadmap-details">';
                
                // Teams involved
                if (!empty($sprint['selected_teams'])) {
                    $output .= '<div class="dmtp-teams">';
                    $output .= '<strong>Teams:</strong> ' . implode(', ', $sprint['selected_teams']);
                    $output .= '</div>';
                }
                
                // Key metrics
                $output .= $this->render_sprint_metrics($sprint);
                
                // Member contributions toggle button
                if (!empty($sprint['member_performance'])) {
                    $output .= '<div class="dmtp-members-toggle">';
                    $output .= '<button class="dmtp-toggle-members" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                    $output .= '👥 View Team Contributions (' . count($sprint['member_performance']) . ' members)';
                    $output .= '</button>';
                    $output .= '</div>';
                    
                    // Member contributions table (hidden by default)
                    $output .= '<div class="dmtp-members-table" id="dmtp-members-' . esc_attr($sprint['id']) . '" style="display: none;">';
                    $output .= $this->render_member_contributions_table($sprint['member_performance']);
                    $output .= '</div>';
                }
                
                // Hotfixes toggle button
                if (!empty($sprint['sprint_team_hotfixes'])) {
                    $hotfix_count = $this->count_total_hotfixes($sprint['sprint_team_hotfixes']);
                    if ($hotfix_count > 0) {
                        $output .= '<div class="dmtp-hotfixes-toggle">';
                        $output .= '<button class="dmtp-toggle-hotfixes" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                        $output .= '🐛 View Sprint Hotfixes (' . $hotfix_count . ' hotfixes)';
                        $output .= '</button>';
                        $output .= '</div>';
                        
                        // Hotfixes table (hidden by default)
                        $output .= '<div class="dmtp-hotfixes-table" id="dmtp-hotfixes-' . esc_attr($sprint['id']) . '" style="display: none;">';
                        $output .= $this->render_hotfixes_table($sprint['sprint_team_hotfixes']);
                        $output .= '</div>';
                    }
                }
                
                // Marketing tasks toggle button
                if (!empty($sprint['marketing_tasks'])) {
                    $marketing_count = count($sprint['marketing_tasks']);
                    $output .= '<div class="dmtp-marketing-toggle">';
                    $output .= '<button class="dmtp-toggle-marketing" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                    $output .= '📈 View Marketing Tasks (' . $marketing_count . ' tasks)';
                    $output .= '</button>';
                    $output .= '</div>';
                    
                    // Marketing tasks table (hidden by default)
                    $output .= '<div class="dmtp-marketing-table" id="dmtp-marketing-' . esc_attr($sprint['id']) . '" style="display: none;">';
                    $output .= $this->render_marketing_tasks_table($sprint['marketing_tasks'], $sprint['id']);
                    $output .= '</div>';
                }
                
                // Documentation tasks toggle button
                if (!empty($sprint['documentation_tasks'])) {
                    $documentation_count = count($sprint['documentation_tasks']);
                    $output .= '<div class="dmtp-documentation-toggle">';
                    $output .= '<button class="dmtp-toggle-documentation" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                    $output .= '📚 View Documentation Tasks (' . $documentation_count . ' tasks)';
                    $output .= '</button>';
                    $output .= '</div>';
                    
                    // Documentation tasks table (hidden by default)
                    $output .= '<div class="dmtp-documentation-table" id="dmtp-documentation-' . esc_attr($sprint['id']) . '" style="display: none;">';
                    $output .= $this->render_documentation_tasks_table($sprint['documentation_tasks'], $sprint['id']);
                    $output .= '</div>';
                }
                
                // Important dates
                $output .= '<div class="dmtp-important-dates">';
                if (!empty($sprint['demonstration_date'])) {
                    $output .= '<span class="dmtp-demo-date">🎯 Demo: ' . $this->format_date($sprint['demonstration_date']) . '</span>';
                }
                if (!empty($sprint['retrospective_day'])) {
                    $output .= '<span class="dmtp-retro-date">🔄 Retro: ' . $this->format_date($sprint['retrospective_day']) . '</span>';
                }
                $output .= '</div>';
                
                $output .= '</div>';
                $output .= '</div>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Render timeline view.
         *
         * @param array $sprints Sprint data.
         * @return string HTML for timeline view.
         */
        private function render_timeline_view($sprints) {
            $output = '<div class="dmtp-timeline-view">';
            
            foreach ($sprints as $sprint) {
                $status_class = $this->get_sprint_status_class($sprint);
                $teams_data = !empty($sprint['selected_teams']) ? implode(',', $sprint['selected_teams']) : '';
                
                $output .= '<div class="dmtp-timeline-item ' . $status_class . '" data-teams="' . esc_attr($teams_data) . '">';
                $output .= '<div class="dmtp-timeline-date">';
                $output .= '<span class="dmtp-month">' . date('M', strtotime($sprint['start_date'])) . '</span>';
                $output .= '<span class="dmtp-day">' . date('d', strtotime($sprint['start_date'])) . '</span>';
                $output .= '</div>';
                
                $output .= '<div class="dmtp-timeline-content">';
                $output .= '<h4>' . esc_html($sprint['title']) . '</h4>';
                $output .= '<p class="dmtp-timeline-duration">';
                $output .= $this->format_date($sprint['start_date']) . ' - ' . $this->format_date($sprint['end_date']);
                $output .= '</p>';
                
                // Quick metrics
                $output .= $this->render_quick_metrics($sprint);
                
                // Member contributions toggle button
                if (!empty($sprint['member_performance'])) {
                    $output .= '<div class="dmtp-members-toggle dmtp-timeline-members">';
                    $output .= '<button class="dmtp-toggle-members" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                    $output .= '👥 Team (' . count($sprint['member_performance']) . ')';
                    $output .= '</button>';
                    $output .= '</div>';
                    
                    // Member contributions table (hidden by default)
                    $output .= '<div class="dmtp-members-table" id="dmtp-members-' . esc_attr($sprint['id']) . '" style="display: none;">';
                    $output .= $this->render_member_contributions_table($sprint['member_performance']);
                    $output .= '</div>';
                }
                
                // Hotfixes toggle button
                if (!empty($sprint['sprint_team_hotfixes'])) {
                    $hotfix_count = $this->count_total_hotfixes($sprint['sprint_team_hotfixes']);
                    if ($hotfix_count > 0) {
                        $output .= '<div class="dmtp-hotfixes-toggle dmtp-timeline-hotfixes">';
                        $output .= '<button class="dmtp-toggle-hotfixes" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                        $output .= '🐛 Fixes (' . $hotfix_count . ')';
                        $output .= '</button>';
                        $output .= '</div>';
                        
                        // Hotfixes table (hidden by default)
                        $output .= '<div class="dmtp-hotfixes-table" id="dmtp-hotfixes-' . esc_attr($sprint['id']) . '" style="display: none;">';
                        $output .= $this->render_hotfixes_table($sprint['sprint_team_hotfixes']);
                        $output .= '</div>';
                    }
                }
                
                // Marketing tasks toggle button
                if (!empty($sprint['marketing_tasks'])) {
                    $marketing_count = count($sprint['marketing_tasks']);
                    $output .= '<div class="dmtp-marketing-toggle dmtp-timeline-marketing">';
                    $output .= '<button class="dmtp-toggle-marketing" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                    $output .= '📈 Marketing (' . $marketing_count . ')';
                    $output .= '</button>';
                    $output .= '</div>';
                    
                    // Marketing tasks table (hidden by default)
                    $output .= '<div class="dmtp-marketing-table" id="dmtp-marketing-' . esc_attr($sprint['id']) . '" style="display: none;">';
                    $output .= $this->render_marketing_tasks_table($sprint['marketing_tasks'], $sprint['id']);
                    $output .= '</div>';
                }
                
                // Documentation tasks toggle button
                if (!empty($sprint['documentation_tasks'])) {
                    $documentation_count = count($sprint['documentation_tasks']);
                    $output .= '<div class="dmtp-documentation-toggle dmtp-timeline-documentation">';
                    $output .= '<button class="dmtp-toggle-documentation" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                    $output .= '📚 Docs (' . $documentation_count . ')';
                    $output .= '</button>';
                    $output .= '</div>';
                    
                    // Documentation tasks table (hidden by default)
                    $output .= '<div class="dmtp-documentation-table" id="dmtp-documentation-' . esc_attr($sprint['id']) . '" style="display: none;">';
                    $output .= $this->render_documentation_tasks_table($sprint['documentation_tasks'], $sprint['id']);
                    $output .= '</div>';
                }
                
                $output .= '</div>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Render cards view.
         *
         * @param array $sprints Sprint data.
         * @return string HTML for cards view.
         */
        private function render_cards_view($sprints) {
            $output = '<div class="dmtp-cards-view">';
            
            foreach ($sprints as $sprint) {
                $status_class = $this->get_sprint_status_class($sprint);
                $card_teams_data = !empty($sprint['selected_teams']) ? implode(',', $sprint['selected_teams']) : '';
                
                $output .= '<div class="dmtp-sprint-card ' . $status_class . '" data-teams="' . esc_attr($card_teams_data) . '">';
                
                // Card header
                $output .= '<div class="dmtp-card-header">';
                $output .= '<h3>' . esc_html($sprint['title']) . '</h3>';
                $output .= '<div class="dmtp-card-status">' . ucfirst(str_replace('dmtp-sprint-', '', $status_class)) . '</div>';
                $output .= '</div>';
                
                // Card body
                $output .= '<div class="dmtp-card-body">';
                $output .= '<div class="dmtp-card-dates">';
                $output .= '<span>📅 ' . $this->format_date($sprint['start_date']) . ' - ' . $this->format_date($sprint['end_date']) . '</span>';
                $output .= '</div>';
                
                // Teams
                if (!empty($sprint['selected_teams'])) {
                    $output .= '<div class="dmtp-card-teams">';
                    $output .= '<span>👥 ' . implode(', ', $sprint['selected_teams']) . '</span>';
                    $output .= '</div>';
                }
                
                // Metrics
                $output .= $this->render_card_metrics($sprint);
                
                // Member contributions toggle button
                if (!empty($sprint['member_performance'])) {
                    $output .= '<div class="dmtp-members-toggle dmtp-card-members">';
                    $output .= '<button class="dmtp-toggle-members" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                    $output .= '👥 View Team Contributions (' . count($sprint['member_performance']) . ')';
                    $output .= '</button>';
                    $output .= '</div>';
                    
                    // Member contributions table (hidden by default)
                    $output .= '<div class="dmtp-members-table" id="dmtp-members-' . esc_attr($sprint['id']) . '" style="display: none;">';
                    $output .= $this->render_member_contributions_table($sprint['member_performance']);
                    $output .= '</div>';
                }
                
                // Hotfixes toggle button
                if (!empty($sprint['sprint_team_hotfixes'])) {
                    $hotfix_count = $this->count_total_hotfixes($sprint['sprint_team_hotfixes']);
                    if ($hotfix_count > 0) {
                        $output .= '<div class="dmtp-hotfixes-toggle dmtp-card-hotfixes">';
                        $output .= '<button class="dmtp-toggle-hotfixes" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                        $output .= '🐛 View Hotfixes (' . $hotfix_count . ')';
                        $output .= '</button>';
                        $output .= '</div>';
                        
                        // Hotfixes table (hidden by default)
                        $output .= '<div class="dmtp-hotfixes-table" id="dmtp-hotfixes-' . esc_attr($sprint['id']) . '" style="display: none;">';
                        $output .= $this->render_hotfixes_table($sprint['sprint_team_hotfixes']);
                        $output .= '</div>';
                    }
                }
                
                // Marketing tasks toggle button
                if (!empty($sprint['marketing_tasks'])) {
                    $marketing_count = count($sprint['marketing_tasks']);
                    $output .= '<div class="dmtp-marketing-toggle dmtp-card-marketing">';
                    $output .= '<button class="dmtp-toggle-marketing" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                    $output .= '📈 Marketing (' . $marketing_count . ')';
                    $output .= '</button>';
                    $output .= '</div>';
                    
                    // Marketing tasks table (hidden by default)
                    $output .= '<div class="dmtp-marketing-table" id="dmtp-marketing-' . esc_attr($sprint['id']) . '" style="display: none;">';
                    $output .= $this->render_marketing_tasks_table($sprint['marketing_tasks'], $sprint['id']);
                    $output .= '</div>';
                }
                
                // Documentation tasks toggle button
                if (!empty($sprint['documentation_tasks'])) {
                    $documentation_count = count($sprint['documentation_tasks']);
                    $output .= '<div class="dmtp-documentation-toggle dmtp-card-documentation">';
                    $output .= '<button class="dmtp-toggle-documentation" data-sprint-id="' . esc_attr($sprint['id']) . '">';
                    $output .= '📚 Documentation (' . $documentation_count . ')';
                    $output .= '</button>';
                    $output .= '</div>';
                    
                    // Documentation tasks table (hidden by default)
                    $output .= '<div class="dmtp-documentation-table" id="dmtp-documentation-' . esc_attr($sprint['id']) . '" style="display: none;">';
                    $output .= $this->render_documentation_tasks_table($sprint['documentation_tasks'], $sprint['id']);
                    $output .= '</div>';
                }
                
                $output .= '</div>';
                
                // Card footer
                $output .= '<div class="dmtp-card-footer">';
                if (!empty($sprint['demonstration_date'])) {
                    $output .= '<span class="dmtp-demo">🎯 Demo: ' . $this->format_date($sprint['demonstration_date']) . '</span>';
                }
                $output .= '</div>';
                
                $output .= '</div>';
            }
            
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Render marketing view for sprints.
         *
         * @param array $sprints Sprint data.
         * @return string HTML for marketing view.
         */
        private function render_marketing_view($sprints) {
            $output = '<div class="dmtp-marketing-view">';
            
            foreach ($sprints as $sprint) {
                $status_class = $this->get_sprint_status_class($sprint);
                $teams_data = !empty($sprint['selected_teams']) ? implode(',', $sprint['selected_teams']) : '';
                
                $output .= '<div class="dmtp-marketing-item ' . $status_class . '" data-teams="' . esc_attr($teams_data) . '">';
                
                // Sprint header - ONLY title and dates
                $output .= '<div class="dmtp-marketing-header">';
                $output .= '<h3 class="dmtp-sprint-title">' . esc_html($sprint['title']) . '</h3>';
                $output .= '<div class="dmtp-sprint-dates">';
                $output .= '<span class="dmtp-start-date">' . $this->format_date($sprint['start_date']) . '</span>';
                $output .= ' → ';
                $output .= '<span class="dmtp-end-date">' . $this->format_date($sprint['end_date']) . '</span>';
                $output .= '</div>';
                $output .= '</div>';
                
                // Marketing checklist ONLY - no other content
                if (!empty($sprint['marketing_tasks'])) {
                    $output .= '<div class="dmtp-marketing-checklist">';
                    $output .= '<h4>📈 Marketing Tasks</h4>';
                    $output .= $this->render_marketing_tasks_table($sprint['marketing_tasks'], $sprint['id']);
                    $output .= '</div>';
                } else {
                    $output .= '<div class="dmtp-no-tasks">No marketing tasks for this sprint.</div>';
                }
                
                $output .= '</div>';
            }
            
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Render documentation view for sprints.
         *
         * @param array $sprints Sprint data.
         * @return string HTML for documentation view.
         */
        private function render_documentation_view($sprints) {
            $output = '<div class="dmtp-documentation-view">';
            
            foreach ($sprints as $sprint) {
                $status_class = $this->get_sprint_status_class($sprint);
                $teams_data = !empty($sprint['selected_teams']) ? implode(',', $sprint['selected_teams']) : '';
                
                $output .= '<div class="dmtp-documentation-item ' . $status_class . '" data-teams="' . esc_attr($teams_data) . '">';
                
                // Sprint header
                $output .= '<div class="dmtp-documentation-header">';
                $output .= '<h3 class="dmtp-sprint-title">' . esc_html($sprint['title']) . '</h3>';
                $output .= '<div class="dmtp-sprint-dates">';
                $output .= '<span class="dmtp-start-date">' . $this->format_date($sprint['start_date']) . '</span>';
                $output .= ' → ';
                $output .= '<span class="dmtp-end-date">' . $this->format_date($sprint['end_date']) . '</span>';
                $output .= '</div>';
                $output .= '</div>';
                
                // Documentation checklist
                if (!empty($sprint['documentation_tasks'])) {
                    $output .= '<div class="dmtp-documentation-checklist">';
                    $output .= '<h4>📚 Documentation Tasks</h4>';
                    $output .= $this->render_documentation_tasks_table($sprint['documentation_tasks'], $sprint['id']);
                    $output .= '</div>';
                } else {
                    $output .= '<div class="dmtp-no-tasks">No documentation tasks for this sprint.</div>';
                }
                
                $output .= '</div>';
            }
            
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Render sprint metrics.
         *
         * @param array $sprint Sprint data.
         * @return string HTML for sprint metrics.
         */
        private function render_sprint_metrics($sprint) {
            $output = '<div class="dmtp-sprint-metrics">';
            
            if (!empty($sprint['member_performance'])) {
                $total_committed = 0;
                $total_delivered = 0;
                $team_count = count($sprint['member_performance']);
                
                foreach ($sprint['member_performance'] as $member) {
                    $total_committed += intval($member['member_total_story_points']);
                    $total_delivered += intval($member['member_delivered_story_points']);
                }
                
                $velocity = $total_committed > 0 ? round(($total_delivered / $total_committed) * 100) : 0;
                
                $output .= '<div class="dmtp-metric">';
                $output .= '<span class="dmtp-metric-label">Story Points:</span>';
                $output .= '<span class="dmtp-metric-value">' . $total_delivered . '/' . $total_committed . '</span>';
                $output .= '</div>';
                
                $output .= '<div class="dmtp-metric">';
                $output .= '<span class="dmtp-metric-label">Velocity:</span>';
                $output .= '<span class="dmtp-metric-value">' . $velocity . '%</span>';
                $output .= '</div>';
                
                $output .= '<div class="dmtp-metric">';
                $output .= '<span class="dmtp-metric-label">Team Size:</span>';
                $output .= '<span class="dmtp-metric-value">' . $team_count . '</span>';
                $output .= '</div>';
            }
            
            // Hotfixes count
            if (!empty($sprint['sprint_team_hotfixes'])) {
                $hotfix_count = 0;
                foreach ($sprint['sprint_team_hotfixes'] as $team_hotfix) {
                    if (!empty($team_hotfix['team_hotfix_list'])) {
                        $hotfix_count += count($team_hotfix['team_hotfix_list']);
                    }
                }
                
                if ($hotfix_count > 0) {
                    $output .= '<div class="dmtp-metric">';
                    $output .= '<span class="dmtp-metric-label">Hotfixes:</span>';
                    $output .= '<span class="dmtp-metric-value">' . $hotfix_count . '</span>';
                    $output .= '</div>';
                }
            }
            
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Render quick metrics for timeline view.
         *
         * @param array $sprint Sprint data.
         * @return string HTML for quick metrics.
         */
        private function render_quick_metrics($sprint) {
            $output = '<div class="dmtp-quick-metrics">';
            
            if (!empty($sprint['member_performance'])) {
                $total_committed = 0;
                $total_delivered = 0;
                
                foreach ($sprint['member_performance'] as $member) {
                    $total_committed += intval($member['member_total_story_points']);
                    $total_delivered += intval($member['member_delivered_story_points']);
                }
                
                $velocity = $total_committed > 0 ? round(($total_delivered / $total_committed) * 100) : 0;
                $output .= '<span class="dmtp-velocity">⚡ ' . $velocity . '% velocity</span>';
            }
            
            if (!empty($sprint['selected_teams'])) {
                $output .= '<span class="dmtp-team-count">👥 ' . count($sprint['selected_teams']) . ' teams</span>';
            }
            
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Render card metrics.
         *
         * @param array $sprint Sprint data.
         * @return string HTML for card metrics.
         */
        private function render_card_metrics($sprint) {
            $output = '<div class="dmtp-card-metrics">';
            
            if (!empty($sprint['member_performance'])) {
                $total_committed = 0;
                $total_delivered = 0;
                
                foreach ($sprint['member_performance'] as $member) {
                    $total_committed += intval($member['member_total_story_points']);
                    $total_delivered += intval($member['member_delivered_story_points']);
                }
                
                $velocity = $total_committed > 0 ? round(($total_delivered / $total_committed) * 100) : 0;
                
                $output .= '<div class="dmtp-progress-bar">';
                $output .= '<div class="dmtp-progress-fill" style="width: ' . $velocity . '%"></div>';
                $output .= '<span class="dmtp-progress-text">' . $total_delivered . '/' . $total_committed . ' SP (' . $velocity . '%)</span>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Get sprint status class based on dates.
         *
         * @param array $sprint Sprint data.
         * @return string CSS class for sprint status.
         */
        private function get_sprint_status_class($sprint) {
            $today = date('Y-m-d');
            $start_date = $sprint['start_date'];
            $end_date = $sprint['end_date'];
            
            if ($today < $start_date) {
                return 'dmtp-sprint-upcoming';
            } elseif ($today >= $start_date && $today <= $end_date) {
                return 'dmtp-sprint-active';
            } else {
                return 'dmtp-sprint-completed';
            }
        }

        /**
         * Format date for display.
         *
         * @param string $date Date string.
         * @return string Formatted date.
         */
        private function format_date($date) {
            if (empty($date)) {
                return '';
            }
            
            return date('M j, Y', strtotime($date));
        }

        /**
         * Render member contributions table.
         *
         * @param array $members Member performance data.
         * @return string HTML for member contributions table.
         */
        private function render_member_contributions_table($members) {
            if (empty($members) || !is_array($members)) {
                return '<p class="dmtp-no-members">No member data available.</p>';
            }
            
            $output = '<div class="dmtp-member-table-wrapper">';
            $output .= '<table class="dmtp-member-table">';
            
            // Table header
            $output .= '<thead>';
            $output .= '<tr>';
            $output .= '<th>Member</th>';
            $output .= '<th>Role</th>';
            $output .= '<th>Est. Hrs</th>';
            $output .= '<th>Comm. SP</th>';
            $output .= '<th>Del. SP</th>';
            $output .= '<th>Velocity</th>';
            $output .= '<th>Hotfixes</th>';
            $output .= '<th>Absent</th>';
            $output .= '<th>Rating</th>';
            $output .= '<th>Notes</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            
            // Table body
            $output .= '<tbody>';
            foreach ($members as $member_index => $member) {
                $member_name = !empty($member['member_name']) ? $member['member_name'] : 'Unknown';
                $member_role = !empty($member['member_role']) ? $member['member_role'] : '-';
                $estimated_hours = !empty($member['member_estimated_hours']) ? intval($member['member_estimated_hours']) : 0;
                $committed_sp = !empty($member['member_total_story_points']) ? intval($member['member_total_story_points']) : 0;
                $delivered_sp = !empty($member['member_delivered_story_points']) ? intval($member['member_delivered_story_points']) : 0;
                $hotfixes = !empty($member['member_hotfixes_count']) ? intval($member['member_hotfixes_count']) : 0;
                $absent_days = !empty($member['member_absent_days']) ? intval($member['member_absent_days']) : 0;
                $rating = !empty($member['member_rating']) ? floatval($member['member_rating']) : 0;
                $retrospective_notes = !empty($member['member_retrospective_notes']) ? $member['member_retrospective_notes'] : '';
                
                // Calculate velocity percentage
                $velocity_percent = 0;
                if ($committed_sp > 0) {
                    $velocity_percent = round(($delivered_sp / $committed_sp) * 100, 1);
                }
                
                // Determine velocity class for color coding
                $velocity_class = '';
                if ($velocity_percent >= 90) {
                    $velocity_class = 'dmtp-velocity-excellent';
                } elseif ($velocity_percent >= 75) {
                    $velocity_class = 'dmtp-velocity-good';
                } elseif ($velocity_percent >= 50) {
                    $velocity_class = 'dmtp-velocity-average';
                } else {
                    $velocity_class = 'dmtp-velocity-poor';
                }
                
                // Rating class for color coding
                $rating_class = '';
                if ($rating >= 4.5) {
                    $rating_class = 'dmtp-rating-excellent';
                } elseif ($rating >= 3.5) {
                    $rating_class = 'dmtp-rating-good';
                } elseif ($rating >= 2.5) {
                    $rating_class = 'dmtp-rating-average';
                } else {
                    $rating_class = 'dmtp-rating-poor';
                }
                
                $output .= '<tr>';
                $output .= '<td class="dmtp-member-name" title="' . esc_attr($member_name) . '">' . esc_html($member_name) . '</td>';
                $output .= '<td class="dmtp-member-role">' . esc_html($member_role) . '</td>';
                $output .= '<td class="dmtp-estimated-hours">' . esc_html($estimated_hours) . 'h</td>';
                $output .= '<td class="dmtp-committed-sp">' . esc_html($committed_sp) . '</td>';
                $output .= '<td class="dmtp-delivered-sp">' . esc_html($delivered_sp) . '</td>';
                $output .= '<td class="dmtp-velocity ' . $velocity_class . '">' . esc_html($velocity_percent) . '%</td>';
                $output .= '<td class="dmtp-hotfixes">' . esc_html($hotfixes) . '</td>';
                $output .= '<td class="dmtp-absent-days">' . esc_html($absent_days) . 'd</td>';
                $output .= '<td class="dmtp-rating ' . $rating_class . '">' . esc_html(number_format($rating, 1)) . '/5</td>';
                
                // Notes column with View Notes button
                $output .= '<td class="dmtp-notes-column">';
                if (!empty($retrospective_notes)) {
                    $output .= '<button class="dmtp-view-notes-btn" data-member-index="' . esc_attr($member_index) . '">';
                    $output .= '📝 View Notes';
                    $output .= '</button>';
                } else {
                    $output .= '<span class="dmtp-no-notes">-</span>';
                }
                $output .= '</td>';
                $output .= '</tr>';
                
                // Notes row (hidden by default) - only if notes exist
                if (!empty($retrospective_notes)) {
                    $output .= '<tr class="dmtp-member-notes-row" id="dmtp-notes-row-' . esc_attr($member_index) . '" style="display: none;">';
                    $output .= '<td colspan="10" class="dmtp-member-notes-content">';
                    $output .= '<div class="dmtp-notes-wrapper">';
                    $output .= '<strong>📝 Notes:</strong>';
                    $output .= '<div class="dmtp-notes-text">' . wp_kses_post($retrospective_notes) . '</div>';
                    $output .= '</div>';
                    $output .= '</td>';
                    $output .= '</tr>';
                }
            }
            $output .= '</tbody>';
            
            $output .= '</table>';
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Render hotfixes table.
         *
         * @param array $hotfixes Hotfix data.
         * @return string HTML for hotfixes table.
         */
        private function render_hotfixes_table($hotfixes) {
            $output = '<div class="dmtp-hotfixes-table-wrapper">';
            $output .= '<table class="dmtp-hotfixes-table">';
            
            // Table header
            $output .= '<thead>';
            $output .= '<tr>';
            $output .= '<th>Team</th>';
            $output .= '<th>Hotfixes</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            
            // Table body
            $output .= '<tbody>';
            foreach ($hotfixes as $team_hotfix) {
                $team_name = !empty($team_hotfix['team_name']) ? $team_hotfix['team_name'] : 'Unknown';
                $hotfix_count = !empty($team_hotfix['team_hotfix_list']) ? count($team_hotfix['team_hotfix_list']) : 0;
                
                $output .= '<tr>';
                $output .= '<td class="dmtp-team-name" title="' . esc_attr($team_name) . '">' . esc_html($team_name) . '</td>';
                $output .= '<td class="dmtp-hotfixes">' . esc_html($hotfix_count) . '</td>';
                $output .= '</tr>';
            }
            $output .= '</tbody>';
            
            $output .= '</table>';
            $output .= '</div>';
            
            return $output;
        }

        /**
         * Count total hotfixes.
         *
         * @param array $hotfixes Hotfix data.
         * @return int Total hotfix count.
         */
        private function count_total_hotfixes($hotfixes) {
            $hotfix_count = 0;
            foreach ($hotfixes as $team_hotfix) {
                if (!empty($team_hotfix['team_hotfix_list'])) {
                    $hotfix_count += count($team_hotfix['team_hotfix_list']);
                }
            }
            return $hotfix_count;
        }

        /**
         * Render marketing tasks table.
         *
         * @param array $marketing_tasks Marketing tasks data.
         * @return string HTML for marketing tasks table.
         */
        private function render_marketing_tasks_table($marketing_tasks, $sprint_id) {
            if (empty($marketing_tasks)) {
                return '<div class="dmtp-no-tasks">No marketing tasks available.</div>';
            }

            $output = '<div class="dmtp-tasks-table-wrapper">';
            $output .= '<table class="dmtp-tasks-table dmtp-marketing-tasks-table">';
            $output .= '<thead>';
            $output .= '<tr>';
            $output .= '<th>Task</th>';
            $output .= '<th class="dmtp-status-column">Status</th>';
            $output .= '<th class="dmtp-verified-column">Verified</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';

            $can_edit = $this->user_can_edit_tasks();

            foreach ($marketing_tasks as $index => $task) {
                $task_status = !empty($task['marketing_task_status']) ? 'checked' : '';
                $verified_status = !empty($task['marketing_verified_by_status']) ? 'checked' : '';
                $status_class = !empty($task['marketing_task_status']) ? 'dmtp-status-completed' : 'dmtp-status-pending';
                $verified_class = !empty($task['marketing_verified_by_status']) ? 'dmtp-status-verified' : 'dmtp-status-not-verified';
                $disabled = $can_edit ? '' : 'disabled';

                $output .= '<tr class="dmtp-task-row">';
                $output .= '<td class="dmtp-task-name">' . esc_html($task['marketing_task_name']) . '</td>';
                $output .= '<td class="dmtp-task-status ' . $status_class . '">';
                $output .= '<label class="dmtp-status-checkbox">';
                $output .= '<input type="checkbox" ' . $task_status . ' ' . $disabled . ' ';
                $output .= 'data-sprint-id="' . esc_attr($sprint_id) . '" ';
                $output .= 'data-task-index="' . esc_attr($index) . '" ';
                $output .= 'data-field-type="status" ';
                $output .= 'data-task-type="marketing" ';
                $output .= 'class="dmtp-task-checkbox">';
                $output .= '<span class="dmtp-checkmark"></span>';
                $output .= '</label>';
                $output .= '</td>';
                $output .= '<td class="dmtp-task-verified ' . $verified_class . '">';
                $output .= '<label class="dmtp-verified-checkbox">';
                $output .= '<input type="checkbox" ' . $verified_status . ' ' . $disabled . ' ';
                $output .= 'data-sprint-id="' . esc_attr($sprint_id) . '" ';
                $output .= 'data-task-index="' . esc_attr($index) . '" ';
                $output .= 'data-field-type="verified" ';
                $output .= 'data-task-type="marketing" ';
                $output .= 'class="dmtp-task-checkbox">';
                $output .= '<span class="dmtp-checkmark"></span>';
                $output .= '</label>';
                $output .= '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';

            return $output;
        }

        /**
         * Render documentation tasks table.
         *
         * @param array $documentation_tasks Documentation tasks data.
         * @return string HTML for documentation tasks table.
         */
        private function render_documentation_tasks_table($documentation_tasks, $sprint_id) {
            if (empty($documentation_tasks)) {
                return '<div class="dmtp-no-tasks">No documentation tasks available.</div>';
            }

            $output = '<div class="dmtp-tasks-table-wrapper">';
            $output .= '<table class="dmtp-tasks-table dmtp-documentation-tasks-table">';
            $output .= '<thead>';
            $output .= '<tr>';
            $output .= '<th>Task</th>';
            $output .= '<th class="dmtp-status-column">Status</th>';
            $output .= '<th class="dmtp-verified-column">Verified</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';

            $can_edit = $this->user_can_edit_tasks();

            foreach ($documentation_tasks as $index => $task) {
                $task_status = !empty($task['documentation_task_status']) ? 'checked' : '';
                $verified_status = !empty($task['documentation_verified_by_status']) ? 'checked' : '';
                $status_class = !empty($task['documentation_task_status']) ? 'dmtp-status-completed' : 'dmtp-status-pending';
                $verified_class = !empty($task['documentation_verified_by_status']) ? 'dmtp-status-verified' : 'dmtp-status-not-verified';
                $disabled = $can_edit ? '' : 'disabled';

                $output .= '<tr class="dmtp-task-row">';
                $output .= '<td class="dmtp-task-name">' . esc_html($task['documentation_task_name']) . '</td>';
                $output .= '<td class="dmtp-task-status ' . $status_class . '">';
                $output .= '<label class="dmtp-status-checkbox">';
                $output .= '<input type="checkbox" ' . $task_status . ' ' . $disabled . ' ';
                $output .= 'data-sprint-id="' . esc_attr($sprint_id) . '" ';
                $output .= 'data-task-index="' . esc_attr($index) . '" ';
                $output .= 'data-field-type="status" ';
                $output .= 'data-task-type="documentation" ';
                $output .= 'class="dmtp-task-checkbox">';
                $output .= '<span class="dmtp-checkmark"></span>';
                $output .= '</label>';
                $output .= '</td>';
                $output .= '<td class="dmtp-task-verified ' . $verified_class . '">';
                $output .= '<label class="dmtp-verified-checkbox">';
                $output .= '<input type="checkbox" ' . $verified_status . ' ' . $disabled . ' ';
                $output .= 'data-sprint-id="' . esc_attr($sprint_id) . '" ';
                $output .= 'data-task-index="' . esc_attr($index) . '" ';
                $output .= 'data-field-type="verified" ';
                $output .= 'data-task-type="documentation" ';
                $output .= 'class="dmtp-task-checkbox">';
                $output .= '<span class="dmtp-checkmark"></span>';
                $output .= '</label>';
                $output .= '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';

            return $output;
        }

        /**
         * Handle AJAX request to update marketing task status.
         */
        public function ajax_update_marketing_task_status() {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'dmtp_update_task_status')) {
                wp_die('Security check failed');
            }

            // Check user permissions
            if (!$this->user_can_edit_tasks()) {
                wp_die('Insufficient permissions');
            }

            $sprint_id = intval($_POST['sprint_id']);
            $task_index = intval($_POST['task_index']);
            $field_type = sanitize_text_field($_POST['field_type']); // 'status' or 'verified'
            $new_value = $_POST['new_value'] === 'true' ? true : false;

            // Get current marketing tasks
            $marketing_tasks = get_field('sprint_marketing_tasks', $sprint_id);
            
            if (!empty($marketing_tasks) && isset($marketing_tasks[$task_index])) {
                // Update the specific field
                if ($field_type === 'status') {
                    $marketing_tasks[$task_index]['marketing_task_status'] = $new_value;
                } elseif ($field_type === 'verified') {
                    $marketing_tasks[$task_index]['marketing_verified_by_status'] = $new_value;
                }

                // Save back to ACF
                $updated = update_field('sprint_marketing_tasks', $marketing_tasks, $sprint_id);

                if ($updated !== false) {
                    wp_send_json_success(array(
                        'message' => 'Task status updated successfully',
                        'new_value' => $new_value
                    ));
                } else {
                    wp_send_json_error('Failed to update database');
                }
            } else {
                wp_send_json_error('Task not found');
            }
        }

        /**
         * Handle AJAX request to update documentation task status.
         */
        public function ajax_update_documentation_task_status() {
            // Verify nonce
            if (!wp_verify_nonce($_POST['nonce'], 'dmtp_update_task_status')) {
                wp_die('Security check failed');
            }

            // Check user permissions
            if (!$this->user_can_edit_tasks()) {
                wp_die('Insufficient permissions');
            }

            $sprint_id = intval($_POST['sprint_id']);
            $task_index = intval($_POST['task_index']);
            $field_type = sanitize_text_field($_POST['field_type']); // 'status' or 'verified'
            $new_value = $_POST['new_value'] === 'true' ? true : false;

            // Get current documentation tasks
            $documentation_tasks = get_field('sprint_documentation_tasks', $sprint_id);
            
            if (!empty($documentation_tasks) && isset($documentation_tasks[$task_index])) {
                // Update the specific field
                if ($field_type === 'status') {
                    $documentation_tasks[$task_index]['documentation_task_status'] = $new_value;
                } elseif ($field_type === 'verified') {
                    $documentation_tasks[$task_index]['documentation_verified_by_status'] = $new_value;
                }

                // Save back to ACF
                $updated = update_field('sprint_documentation_tasks', $documentation_tasks, $sprint_id);

                if ($updated !== false) {
                    wp_send_json_success(array(
                        'message' => 'Task status updated successfully',
                        'new_value' => $new_value
                    ));
                } else {
                    wp_send_json_error('Failed to update database');
                }
            } else {
                wp_send_json_error('Task not found');
            }
        }

        /**
         * Check if current user can edit tasks.
         *
         * @return bool True if user can edit tasks.
         */
        private function user_can_edit_tasks() {
            if (!is_user_logged_in()) {
                return false;
            }
            
            $user = wp_get_current_user();
            $allowed_roles = array('administrator', 'dmtp_manager', 'dmtp_developer', 'author', 'editor');
            
            return array_intersect($allowed_roles, $user->roles) ? true : false;
        }
    }
} 