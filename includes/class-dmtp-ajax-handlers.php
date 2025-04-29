<?php
/**
 * The class responsible for handling AJAX requests.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Ajax_Handlers')) {

    /**
     * Class for handling AJAX requests.
     */
    class DMTP_Ajax_Handlers {

        /**
         * Get sprint data via AJAX.
         */
        public function get_sprint_data() {
            // Check for nonce security
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dmtp_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Security check failed.'));
            }
            
            // Check for required parameters
            if (!isset($_POST['sprint_id'])) {
                wp_send_json_error(array('message' => 'Missing sprint ID.'));
            }
            
            $sprint_id = intval($_POST['sprint_id']);
            $sprint = get_post($sprint_id);
            if (!$sprint || $sprint->post_type !== 'dmtp_sprint') {
                wp_send_json_error(array('message' => 'Invalid sprint ID.'));
            }
            
            // Get basic sprint meta data
            $sprint_data = array(
                'id' => $sprint_id,
                'title' => $sprint->post_title,
                'description' => $sprint->post_content, // Keep main content area
                'start_date' => get_post_meta($sprint_id, 'start_date', true),
                'end_date' => get_post_meta($sprint_id, 'end_date', true),
                'planning_note' => get_post_meta($sprint_id, 'planning_note', true),
                'retrospective_note' => get_post_meta($sprint_id, 'retrospective_note', true),
                'selected_teams' => get_post_meta($sprint_id, 'selected_teams', true), // Array of selected team keys
            );

            // Get performance data repeater
            $performance_data = array();
            if (have_rows('dmtp_member_performance', $sprint_id)) {
                 while (have_rows('dmtp_member_performance', $sprint_id)) : the_row();
                    $performance_data[] = array(
                        'member_name' => get_sub_field('member_name'),
                        'estimated_hours' => get_sub_field('member_estimated_hours'),
                        'committed_sp' => get_sub_field('member_total_story_points'),
                        'delivered_sp' => get_sub_field('member_delivered_story_points'),
                        'hotfixes' => get_sub_field('member_hotfixes_count'),
                        'absent_days' => get_sub_field('member_absent_days'),
                        'rating' => get_sub_field('member_rating'),
                        'notes' => get_sub_field('member_retrospective_notes'),
                    );
                endwhile;
                reset_rows();
            }
            
            $response = array(
                'sprint' => $sprint_data,
                'performance' => $performance_data,
            );
            
            wp_send_json_success($response);
        }
        
        /**
         * Update sprint data via AJAX.
         * Note: This currently only updates the main post fields, not repeater data.
         */
        public function update_sprint() {
            // Check for nonce security
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dmtp_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Security check failed.'));
            }
            
            // Check for required parameters
            if (!isset($_POST['sprint_id'])) {
                wp_send_json_error(array('message' => 'Missing sprint ID.'));
            }
            
            $sprint_id = intval($_POST['sprint_id']);
            
            // Check if the user has permission to edit this sprint
            if (!current_user_can('edit_post', $sprint_id)) {
                wp_send_json_error(array('message' => 'You do not have permission to edit this sprint.'));
            }
            
            // Update sprint post basic fields
            $post_data = array('ID' => $sprint_id);
            if (isset($_POST['title'])) {
                $post_data['post_title'] = sanitize_text_field($_POST['title']);
            }
            if (isset($_POST['description'])) {
                $post_data['post_content'] = wp_kses_post($_POST['description']);
            }
            if (count($post_data) > 1) {
                wp_update_post($post_data);
            }
            
            // Update standard meta fields (excluding repeaters)
            $meta_fields = array(
                'start_date', 'end_date', 'planning_note', 'retrospective_note', 'selected_teams'
            );
            foreach ($meta_fields as $field) {
                if (isset($_POST[$field])) {
                    $value = $_POST[$field];
                    // Basic sanitization - adjust if needed (e.g., selected_teams is array)
                     if (is_array($value)) {
                         $value = array_map('sanitize_text_field', $value);
                     } elseif (in_array($field, array('planning_note', 'retrospective_note'))) {
                         $value = wp_kses_post($value);
                     } else {
                         $value = sanitize_text_field($value);
                     }
                    update_post_meta($sprint_id, $field, $value);
                }
            }
            
            // Note: Updating repeater fields via AJAX is complex and not handled here.
            // Requires sending structured data and using ACF's update_row() or similar.
            
            wp_send_json_success(array('message' => 'Sprint core data updated successfully.'));
        }
        
        /**
         * Export data via AJAX.
         */
        public function export_data() {
            // Check for nonce security
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dmtp_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Security check failed.'));
            }
            
            // Check for required parameters
            if (!isset($_POST['export_type'])) {
                wp_send_json_error(array('message' => 'Missing export type.'));
            }
            
            $export_type = sanitize_text_field($_POST['export_type']);
            $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
            $reports = new DMTP_Reports();

            // Get data based on export type
            $data = array();
            
            switch ($export_type) {
                case 'sprint':
                    $sprint_id = isset($_POST['sprint_id']) ? intval($_POST['sprint_id']) : 0;
                    if (!$sprint_id) {
                        wp_send_json_error(array('message' => 'Missing sprint ID for sprint export.'));
                    }
                    $data = $this->get_sprint_export_data($sprint_id);
                    break;
                    
                case 'developer':
                    // Use developer *name* now, not ID
                    $developer_name = isset($_POST['developer_name']) ? sanitize_text_field($_POST['developer_name']) : '';
                    if (empty($developer_name)) {
                        wp_send_json_error(array('message' => 'Missing developer name for developer export.'));
                    }
                    // Fetch performance across multiple sprints (e.g., last 10)
                    $data = $reports->get_developer_performance($developer_name, 10);
                    break;
                    
                case 'all_sprints':
                    $data = $this->get_all_sprints_export_data();
                    break;
                    
                default:
                    wp_send_json_error(array('message' => 'Invalid export type.'));
                    break;
            }
            
            // Format data based on requested format
            $formatted_data = '';
            if ($format === 'csv') {
                $formatted_data = $this->format_data_as_csv($data, $export_type, $developer_name ?? null);
            } else {
                $formatted_data = $data;
            }
            
            wp_send_json_success(array(
                'data' => $formatted_data,
                'format' => $format,
            ));
        }
        
        /**
         * Get single sprint data for export.
         *
         * @param int $sprint_id The sprint ID.
         * @return array Sprint data including performance repeater.
         */
        private function get_sprint_export_data($sprint_id) {
            $sprint = get_post($sprint_id);
            if (!$sprint || $sprint->post_type !== 'dmtp_sprint') {
                return array();
            }
            
             $sprint_data = array(
                'id' => $sprint_id,
                'title' => $sprint->post_title,
                'start_date' => get_post_meta($sprint_id, 'start_date', true),
                'end_date' => get_post_meta($sprint_id, 'end_date', true),
                'selected_teams' => get_post_meta($sprint_id, 'selected_teams', true),
                // Add calculated totals if needed
                // 'total_committed_sp' => ..., 
            );

            // Get performance data
            $performance_data = array();
             if (have_rows('dmtp_member_performance', $sprint_id)) {
                 while (have_rows('dmtp_member_performance', $sprint_id)) : the_row();
                    $performance_data[] = array(
                        'member_name' => get_sub_field('member_name'),
                        'estimated_hours' => get_sub_field('member_estimated_hours'),
                        'committed_sp' => get_sub_field('member_total_story_points'),
                        'delivered_sp' => get_sub_field('member_delivered_story_points'),
                        'hotfixes' => get_sub_field('member_hotfixes_count'),
                        'absent_days' => get_sub_field('member_absent_days'),
                        'rating' => get_sub_field('member_rating'),
                    );
                endwhile;
                reset_rows();
            }
            $sprint_data['performance'] = $performance_data;
            
            return $sprint_data;
        }
        
        /**
         * Get all sprints data for export.
         *
         * @return array All sprints data including performance.
         */
        private function get_all_sprints_export_data() {
            $args = array(
                'post_type' => 'dmtp_sprint',
                'posts_per_page' => -1,
                'orderby' => 'meta_value',
                'meta_key' => 'start_date',
                'order' => 'DESC',
                 'meta_query' => array( // Ensure dates exist
                    array('key' => 'start_date', 'compare' => 'EXISTS'),
                    array('key' => 'end_date', 'compare' => 'EXISTS')
                )
            );
            
            $sprints_query = new WP_Query($args);
            $sprints = array();
            
            if ($sprints_query->have_posts()) {
                while ($sprints_query->have_posts()) {
                    $sprints_query->the_post();
                    $sprint_id = get_the_ID();
                    $sprints[] = $this->get_sprint_export_data($sprint_id);
                }
                wp_reset_postdata();
            }
            
            return array('sprints' => $sprints);
        }
        
        /**
         * Format data as CSV based on the export type.
         *
         * @param array $data The data to format.
         * @param string $export_type Type of export ('sprint', 'developer', 'all_sprints').
         * @param string|null $developer_name Optional developer name for header.
         * @return string CSV formatted data.
         */
        private function format_data_as_csv($data, $export_type, $developer_name = null) {
            $csv = '';
            $output = fopen('php://temp', 'w'); // Use memory stream

            if ($export_type === 'sprint' && isset($data['id'])) {
                // Single sprint export
                fputcsv($output, array('Sprint ID', 'Title', 'Start Date', 'End Date', 'Teams'));
                fputcsv($output, array(
                    $data['id'],
                    $data['title'],
                    $data['start_date'],
                    $data['end_date'],
                    implode(', ', (array)$data['selected_teams'])
                ));
                fputcsv($output, array()); // Blank line
                fputcsv($output, array('Member Performance'));
                fputcsv($output, array('Member Name', 'Est Hours', 'Committed SP', 'Delivered SP', 'Hotfixes', 'Absent Days', 'Rating'));
                if (!empty($data['performance'])) {
                    foreach ($data['performance'] as $row) {
                         fputcsv($output, array(
                            $row['member_name'],
                            $row['estimated_hours'],
                            $row['committed_sp'],
                            $row['delivered_sp'],
                            $row['hotfixes'],
                            $row['absent_days'],
                            $row['rating']
                         ));
                    }
                }

            } elseif ($export_type === 'developer' && is_array($data)) {
                 // Developer performance export
                 fputcsv($output, array('Developer Performance Report: ' . $developer_name));
                 fputcsv($output, array()); // Blank line
                 fputcsv($output, array('Sprint Name', 'Sprint ID', 'Est Hours', 'Committed SP', 'Delivered SP', 'Hotfixes', 'Absent Days', 'Rating'));
                 foreach ($data as $row) {
                      fputcsv($output, array(
                         $row['sprint_name'],
                         $row['sprint_id'],
                         $row['estimated_hours'],
                         $row['committed_sp'],
                         $row['delivered_sp'],
                         $row['hotfixes'],
                         $row['absent_days'],
                         $row['rating']
                      ));
                 }

            } elseif ($export_type === 'all_sprints' && isset($data['sprints'])) {
                // All sprints export (summary + nested performance)
                 fputcsv($output, array('All Sprints Export'));
                 fputcsv($output, array()); // Blank line

                 foreach ($data['sprints'] as $sprint) {
                     fputcsv($output, array('Sprint:', $sprint['title'], '(ID: ' . $sprint['id'] . ')'));
                     fputcsv($output, array('Start Date', 'End Date', 'Teams'));
                     fputcsv($output, array(
                        $sprint['start_date'],
                        $sprint['end_date'],
                        implode(', ', (array)$sprint['selected_teams'])
                     ));
                     fputcsv($output, array('Member Performance'));
                     fputcsv($output, array('Member Name', 'Est Hours', 'Committed SP', 'Delivered SP', 'Hotfixes', 'Absent Days', 'Rating'));
                     if (!empty($sprint['performance'])) {
                        foreach ($sprint['performance'] as $row) {
                            fputcsv($output, array(
                                $row['member_name'],
                                $row['estimated_hours'],
                                $row['committed_sp'],
                                $row['delivered_sp'],
                                $row['hotfixes'],
                                $row['absent_days'],
                                $row['rating']
                            ));
                        }
                     }
                     fputcsv($output, array()); // Blank line between sprints
                 }
            }
            
            rewind($output);
            $csv = stream_get_contents($output);
            fclose($output);
            return $csv;
        }
        
        /**
         * Escape a string for CSV.
         *
         * @param string $str The string to escape.
         * @return string The escaped string.
         */
        private function escape_csv($str) {
            // fputcsv handles escaping, so this might not be strictly needed anymore
            // If issues arise, consider manual escaping: return '"' . str_replace('"', '""', $str) . '"';
            return $str;
        }

        /**
         * Get developers (members) for a specific team via AJAX.
         */
        public function dmtp_get_developers_for_team() {
            // Check for nonce security
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dmtp_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Security check failed.'));
            }
            
            // Check for required team name parameter
            if (!isset($_POST['team_name']) || empty($_POST['team_name'])) {
                wp_send_json_error(array('message' => 'Missing team name.'));
            }
            
            $team_name_to_find = sanitize_text_field($_POST['team_name']);
            $developers = array();

            // Check if ACF function exists
            if (!function_exists('have_rows')) {
                 wp_send_json_error(array('message' => 'ACF functions not available.'));
            }

            // Loop through teams in options
            if (have_rows('dmtp_teams', 'option')) {
                while (have_rows('dmtp_teams', 'option')) : the_row();
                    $current_team_name = get_sub_field('team_name');
                    
                    // Found the matching team
                    if ($current_team_name === $team_name_to_find) {
                        if (have_rows('team_members')) {
                            while (have_rows('team_members')) : the_row();
                                $member_name = get_sub_field('member_name');
                                if ($member_name) {
                                    // Return as value => label for consistency, although just names might suffice
                                    $developers[] = array(
                                        'value' => esc_attr($member_name),
                                        'label' => esc_html($member_name)
                                    );
                                }
                            endwhile;
                        }
                        // Found the team and its members (or lack thereof), break the loop
                        break; 
                    }
                endwhile;
                reset_rows();
            }
            
            // Sort developers alphabetically by label
            usort($developers, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
            
            wp_send_json_success(array('developers' => $developers));
        }

        /**
         * Get sprints associated with a specific team for filter dropdowns.
         *
         * AJAX Action: dmtp_get_sprints_for_team_filter
         */
        public function get_sprints_for_team_filter() {
            // 1. Security Check
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dmtp_ajax_nonce')) {
                wp_send_json_error(array('message' => 'Nonce verification failed'));
                return;
            }
            
            // 2. Get and Sanitize Input
            if (!isset($_POST['team_name']) || empty($_POST['team_name'])) {
                wp_send_json_error(array('message' => 'Team name is required.'));
                return;
            }
            $team_name = sanitize_text_field($_POST['team_name']);
            
            // 3. Query Sprints
            $sprints = array();
            $args = array(
                'post_type' => 'dmtp_sprint',
                'posts_per_page' => -1, // Get all matching sprints
                'post_status' => 'publish',
                'orderby' => 'title', // Or 'meta_value' for date, etc.
                'order' => 'DESC',    // Or 'ASC'
                'meta_query' => array(
                    array(
                        'key' => 'selected_teams', // The ACF checkbox field slug
                        'value' => '"' . $team_name . '"', // ACF stores checkbox values serialized like: a:1:{i:0;s:8:"TeamName";}
                        'compare' => 'LIKE' // Check if the team name exists within the serialized array
                    )
                ),
                'fields' => 'ids' // Only fetch IDs for performance
            );
            
            $sprint_ids = get_posts($args);
            
            // 4. Format Response
            if (!empty($sprint_ids)) {
                foreach ($sprint_ids as $sprint_id) {
                    $sprints[] = array(
                        'id' => $sprint_id,
                        'title' => get_the_title($sprint_id)
                    );
                }
                // Optionally sort by title again if needed after fetching titles
                // usort($sprints, function($a, $b) { return strcmp($a['title'], $b['title']); });
            }
            
            // 5. Send JSON Response
            wp_send_json_success(array('sprints' => $sprints));
        }
    }
} 