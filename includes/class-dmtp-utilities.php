<?php
/**
 * The class containing utility functions for the plugin.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Utilities')) {

    /**
     * Class for utility functions.
     */
    class DMTP_Utilities {

        /**
         * Format a date in the desired format.
         *
         * @param string $date The date in Y-m-d format.
         * @param string $format The desired format.
         * @return string The formatted date.
         */
        public function format_date($date, $format = 'F j, Y') {
            if (empty($date)) {
                return '';
            }
            
            $date_obj = new DateTime($date);
            return $date_obj->format($format);
        }
        
        /**
         * Get the number of days between two dates.
         *
         * @param string $start_date The start date (Y-m-d format).
         * @param string $end_date The end date (Y-m-d format).
         * @return int The number of days.
         */
        public function get_days_between($start_date, $end_date) {
            if (empty($start_date) || empty($end_date)) {
                return 0;
            }
            
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = $start->diff($end);
            
            return $interval->days;
        }
        
        /**
         * Check if a date is in the past.
         *
         * @param string $date The date to check (Y-m-d format).
         * @return bool True if the date is in the past, false otherwise.
         */
        public function is_date_past($date) {
            if (empty($date)) {
                return false;
            }
            
            $date_obj = new DateTime($date);
            $today = new DateTime();
            
            return $date_obj < $today;
        }
        
        /**
         * Check if a date is in the future.
         *
         * @param string $date The date to check (Y-m-d format).
         * @return bool True if the date is in the future, false otherwise.
         */
        public function is_date_future($date) {
            if (empty($date)) {
                return false;
            }
            
            $date_obj = new DateTime($date);
            $today = new DateTime();
            
            return $date_obj > $today;
        }
        
        /**
         * Check if a date is within a range.
         *
         * @param string $date The date to check (Y-m-d format).
         * @param string $start_date The start date of the range (Y-m-d format).
         * @param string $end_date The end date of the range (Y-m-d format).
         * @return bool True if the date is within the range, false otherwise.
         */
        public function is_date_in_range($date, $start_date, $end_date) {
            if (empty($date) || empty($start_date) || empty($end_date)) {
                return false;
            }
            
            $date_obj = new DateTime($date);
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            
            return ($date_obj >= $start && $date_obj <= $end);
        }
        
        /**
         * Get sprints within a date range.
         *
         * @param string $start_date The start date (Y-m-d format).
         * @param string $end_date The end date (Y-m-d format).
         * @return array Array of sprint objects.
         */
        public function get_sprints_in_range($start_date, $end_date) {
            if (empty($start_date) || empty($end_date)) {
                return array();
            }
            
            $args = array(
                'post_type' => 'dmtp_sprint',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'OR',
                    // Sprints that start within the range
                    array(
                        'key' => 'start_date',
                        'value' => array($start_date, $end_date),
                        'compare' => 'BETWEEN',
                        'type' => 'DATE',
                    ),
                    // Sprints that end within the range
                    array(
                        'key' => 'end_date',
                        'value' => array($start_date, $end_date),
                        'compare' => 'BETWEEN',
                        'type' => 'DATE',
                    ),
                    // Sprints that span the entire range
                    array(
                        'relation' => 'AND',
                        array(
                            'key' => 'start_date',
                            'value' => $start_date,
                            'compare' => '<=',
                            'type' => 'DATE',
                        ),
                        array(
                            'key' => 'end_date',
                            'value' => $end_date,
                            'compare' => '>=',
                            'type' => 'DATE',
                        ),
                    ),
                ),
            );
            
            $query = new WP_Query($args);
            return $query->posts;
        }
        
        /**
         * Calculate the percentage of a sprint completed.
         *
         * @param int $sprint_id The sprint ID.
         * @return int The percentage completed.
         */
        public function calculate_sprint_completion($sprint_id) {
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
            
            if ($query->post_count === 0) {
                return 0;
            }
            
            $total_points = 0;
            $completed_points = 0;
            
            foreach ($query->posts as $story) {
                $story_id = $story->ID;
                $points = get_post_meta($story_id, 'story_points', true);
                $status = get_post_meta($story_id, 'status', true);
                
                if (empty($points)) {
                    $points = 0;
                }
                
                $total_points += $points;
                
                if ($status === 'done') {
                    $completed_points += $points;
                }
            }
            
            if ($total_points === 0) {
                return 0;
            }
            
            return round(($completed_points / $total_points) * 100);
        }
        
        /**
         * Get the total story points for a user in a sprint.
         *
         * @param int $user_id The user ID.
         * @param int $sprint_id The sprint ID.
         * @return array Array with total and completed points.
         */
        public function get_user_story_points($user_id, $sprint_id) {
            $args = array(
                'post_type' => 'dmtp_story',
                'posts_per_page' => -1,
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'related_sprint',
                        'value' => $sprint_id,
                        'compare' => '=',
                    ),
                    array(
                        'key' => 'assigned_user',
                        'value' => $user_id,
                        'compare' => '=',
                    ),
                ),
            );
            
            $query = new WP_Query($args);
            
            $total_points = 0;
            $completed_points = 0;
            
            foreach ($query->posts as $story) {
                $story_id = $story->ID;
                $points = get_post_meta($story_id, 'story_points', true);
                $status = get_post_meta($story_id, 'status', true);
                
                if (empty($points)) {
                    $points = 0;
                }
                
                $total_points += $points;
                
                if ($status === 'done') {
                    $completed_points += $points;
                }
            }
            
            return array(
                'total' => $total_points,
                'completed' => $completed_points,
            );
        }
        
        /**
         * Get the list of developers with their story points.
         *
         * @param int $sprint_id The sprint ID.
         * @return array Array of developers with their story points.
         */
        public function get_developers_with_points($sprint_id) {
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
            
            $developers = array();
            
            foreach ($query->posts as $story) {
                $story_id = $story->ID;
                $user_id = get_post_meta($story_id, 'assigned_user', true);
                $points = get_post_meta($story_id, 'story_points', true);
                $status = get_post_meta($story_id, 'status', true);
                
                if (empty($points)) {
                    $points = 0;
                }
                
                if (!isset($developers[$user_id])) {
                    $user = get_userdata($user_id);
                    $developers[$user_id] = array(
                        'id' => $user_id,
                        'name' => $user ? $user->display_name : 'Unknown',
                        'total_points' => 0,
                        'completed_points' => 0,
                    );
                }
                
                $developers[$user_id]['total_points'] += $points;
                
                if ($status === 'done') {
                    $developers[$user_id]['completed_points'] += $points;
                }
            }
            
            return array_values($developers);
        }
        
        /**
         * Get a list of all developers with assigned stories or hotfixes.
         *
         * @return array Array of developers.
         */
        public function get_all_developers() {
            global $wpdb;
            
            // Get user IDs from assigned_user meta field
            $user_ids = $wpdb->get_col(
                "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
                WHERE meta_key = 'assigned_user'"
            );
            
            $developers = array();
            
            foreach ($user_ids as $user_id) {
                $user = get_userdata($user_id);
                
                if ($user) {
                    $developers[] = array(
                        'id' => $user_id,
                        'name' => $user->display_name,
                        'email' => $user->user_email,
                    );
                }
            }
            
            return $developers;
        }
    }
} 