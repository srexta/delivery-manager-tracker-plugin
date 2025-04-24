<?php
/**
 * The class responsible for generating reports.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Reports')) {

    /**
     * Class for generating reports.
     */
    class DMTP_Reports {

        /**
         * Calculate sprint totals and velocity based on performance repeater data.
         *
         * @param int $sprint_id The sprint ID.
         * @return array Velocity and totals data.
         */
        public function get_sprint_performance_summary($sprint_id) {
            $total_committed_points = 0;
            $total_delivered_points = 0;
            $total_estimated_hours = 0;
            $member_count = 0;

            // Ensure ACF functions exist and we have rows
            if (function_exists('have_rows') && have_rows('dmtp_member_performance', $sprint_id)) {
                while (have_rows('dmtp_member_performance', $sprint_id)) : the_row();
                    $member_count++;
                    $committed = get_sub_field('member_total_story_points');
                    $delivered = get_sub_field('member_delivered_story_points');
                    $hours = get_sub_field('member_estimated_hours');

                    $total_committed_points += !empty($committed) ? intval($committed) : 0;
                    $total_delivered_points += !empty($delivered) ? intval($delivered) : 0;
                    $total_estimated_hours += !empty($hours) ? floatval($hours) : 0;
                endwhile;
                reset_rows(); // Important after loop
            }

            // Get sprint duration in days
            $start_date = get_post_meta($sprint_id, 'start_date', true);
            $end_date = get_post_meta($sprint_id, 'end_date', true);
            
            $duration = 0; // Default duration
            if (!empty($start_date) && !empty($end_date)) {
                try {
                    $start = new DateTime($start_date);
                    $end = new DateTime($end_date);
                    // Calculate total days inclusive
                    if ($end >= $start) {
                        $interval = $start->diff($end);
                        $duration = $interval->days + 1; 
                    }
                } catch (Exception $e) {
                    // Handle potential invalid date formats gracefully
                    error_log("DMTP Error: Invalid date format for sprint ID {$sprint_id}. Start: {$start_date}, End: {$end_date}");
                    $duration = 0; // Set duration to 0 if dates are invalid
                }
            }
            
            // Calculate velocities/rates
            $delivered_velocity = ($duration > 0) ? $total_delivered_points / $duration : 0;
            
            return array(
                'committed_points' => $total_committed_points,
                'delivered_points' => $total_delivered_points,
                'estimated_hours' => $total_estimated_hours,
                'member_count' => $member_count, // Add member count
                'duration_days' => $duration,
                'delivered_velocity' => round($delivered_velocity, 2), // Points per day
                'completion_percentage' => ($total_committed_points > 0) ? round(($total_delivered_points / $total_committed_points) * 100, 2) : 0,
            );
        }
        
        /**
         * Get stored performance data for a specific developer within a sprint.
         *
         * @param string $developer_name The developer's name (must match repeater field).
         * @param int $sprint_id The sprint ID.
         * @return array|null Developer performance data row or null if not found.
         */
        public function get_developer_sprint_performance($developer_name, $sprint_id) {
            if (have_rows('dmtp_member_performance', $sprint_id)) {
                while (have_rows('dmtp_member_performance', $sprint_id)) : the_row();
                    if (get_sub_field('member_name') === $developer_name) {
                        $performance_data = array(
                            'member_name' => get_sub_field('member_name'),
                            'estimated_hours' => get_sub_field('member_estimated_hours'),
                            'committed_sp' => get_sub_field('member_total_story_points'),
                            'delivered_sp' => get_sub_field('member_delivered_story_points'),
                            'hotfixes' => get_sub_field('member_hotfixes_count'),
                            'absent_days' => get_sub_field('member_absent_days'),
                            'rating' => get_sub_field('member_rating'),
                            'notes' => get_sub_field('member_retrospective_notes'),
                        );
                        reset_rows();
                        return $performance_data;
                    }
                endwhile;
                reset_rows();
            }
            return null; // Developer not found in this sprint's performance data
        }
        
        /**
         * Get ideal burndown data for a sprint.
         * Note: Actual burndown requires historical status tracking, which is not implemented.
         *
         * @param int $sprint_id The sprint ID.
         * @return array Ideal burndown data (date, ideal_remaining).
         */
        public function get_sprint_burndown_data($sprint_id) {
            // Get sprint start and end dates
            $start_date = get_post_meta($sprint_id, 'start_date', true);
            $end_date = get_post_meta($sprint_id, 'end_date', true);
            
            if (empty($start_date) || empty($end_date)) {
                return array();
            }
            
            // Calculate total committed points from repeater
            $total_committed_points = 0;
            if (function_exists('have_rows') && have_rows('dmtp_member_performance', $sprint_id)) {
                while (have_rows('dmtp_member_performance', $sprint_id)) : the_row();
                    $committed = get_sub_field('member_total_story_points');
                    $total_committed_points += !empty($committed) ? intval($committed) : 0;
                endwhile;
                reset_rows();
            }

            if ($total_committed_points <= 0) {
                return array(); // No points committed, burndown not applicable
            }

            $burndown_data = array();
            try {
                $start = new DateTime($start_date);
                $end = new DateTime($end_date);
                
                // Ensure end date is not before start date
                if ($end < $start) {
                    return array(); 
                }
                
                $end_loop = clone $end;
                $end_loop->modify('+1 day'); // Include end date in loop
                
                $interval = new DateInterval('P1D');
                $date_range = new DatePeriod($start, $interval, $end_loop);
                
                // Calculate number of days *within* the sprint period for rate calculation
                $sprint_days = $start->diff($end)->days; // Days between start and end (0 for same day)
                if ($sprint_days <= 0) $sprint_days = 1; // Treat same-day sprint as 1 day for rate

                $points_per_day = $total_committed_points / $sprint_days;
                
                $day_counter = 0;
                
                foreach ($date_range as $date) {
                    $date_str = $date->format('Y-m-d');
                    $ideal_remaining = $total_committed_points - ($day_counter * $points_per_day);
                    
                    $burndown_data[] = array(
                        'date' => $date_str,
                        'ideal_remaining' => max(0, round($ideal_remaining, 2)),
                        // 'actual_remaining' => null, // Cannot calculate accurately yet
                    );
                    
                    $day_counter++;
                }
            } catch (Exception $e) {
                // Handle date errors
                return array();
            }

            return $burndown_data;
        }
        
        /**
         * Get velocity trend over multiple sprints.
         *
         * @param int $count The number of sprints to include.
         * @return array Velocity trend data.
         */
        public function get_velocity_trend($count = 5) {
            // Get the most recent completed sprints
            $args = array(
                'post_type' => 'dmtp_sprint',
                'posts_per_page' => $count,
                'meta_key' => 'end_date',
                'orderby' => 'meta_value',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => 'end_date',
                        'value' => date('Y-m-d'),
                        'compare' => '<=', // Only include completed sprints
                        'type' => 'DATE',
                    ),
                     array( // Ensure start date exists too
                        'key' => 'start_date',
                        'compare' => 'EXISTS'
                    )
                ),
            );
            
            $query = new WP_Query($args);
            $trend_data = array();
            
            if ($query->have_posts()) {
                while($query->have_posts()) : $query->the_post();
                    $sprint_id = get_the_ID();
                    // Fetch performance summary using the refactored method
                    $sprint_summary = $this->get_sprint_performance_summary($sprint_id);
                    
                    // Only include sprints with committed points for trend analysis
                    if ($sprint_summary['committed_points'] > 0) {
                        
                        $trend_data[] = array(
                            'sprint_id' => $sprint_id,
                            'sprint_name' => get_the_title(),
                            'committed_points' => $sprint_summary['committed_points'],
                            'delivered_points' => $sprint_summary['delivered_points'], 
                            'velocity' => $sprint_summary['delivered_velocity'], 
                            'completion_percentage' => $sprint_summary['completion_percentage'],
                        );
                    }
                endwhile;
                wp_reset_postdata();
            }
            
            // Sort by sprint end date (ascending) if needed
            usort($trend_data, function($a, $b) {
                $end_date_a = get_post_meta($a['sprint_id'], 'end_date', true);
                $end_date_b = get_post_meta($b['sprint_id'], 'end_date', true);
                // Handle potential missing dates
                if (!$end_date_a) return -1;
                if (!$end_date_b) return 1;
                return strcmp($end_date_a, $end_date_b);
            });
            
            return $trend_data;
        }
        
        /**
         * Get developer performance data across multiple sprints.
         *
         * @param string $developer_name The developer's name.
         * @param int $count The number of recent sprints to check.
         * @return array Developer performance data across sprints.
         */
        public function get_developer_performance($developer_name, $count = 5) {
            // Get the most recent completed sprints
             $args = array(
                'post_type' => 'dmtp_sprint',
                'posts_per_page' => $count,
                'meta_key' => 'end_date',
                'orderby' => 'meta_value',
                'order' => 'DESC',
                'meta_query' => array(
                    array(
                        'key' => 'end_date',
                        'value' => date('Y-m-d'),
                        'compare' => '<=', // Only include completed sprints
                        'type' => 'DATE',
                    ),
                    array( // Ensure start date exists too
                        'key' => 'start_date',
                        'compare' => 'EXISTS'
                    )
                ),
            );
            
            $query = new WP_Query($args);
            $performance_data = array();
            
             if ($query->have_posts()) {
                while($query->have_posts()) : $query->the_post();
                    $sprint_id = get_the_ID();
                    $sprint_name = get_the_title();

                    // Check if this developer has performance data in this sprint
                    $dev_sprint_data = $this->get_developer_sprint_performance($developer_name, $sprint_id);

                    if ($dev_sprint_data) {
                         $performance_data[] = array_merge(
                             array('sprint_id' => $sprint_id, 'sprint_name' => $sprint_name),
                             $dev_sprint_data
                         );
                    }
                endwhile;
                wp_reset_postdata();
            }
            
            // Sort by sprint end date (ascending)
            usort($performance_data, function($a, $b) {
                 $end_date_a = get_post_meta($a['sprint_id'], 'end_date', true);
                $end_date_b = get_post_meta($b['sprint_id'], 'end_date', true);
                if (!$end_date_a) return -1;
                if (!$end_date_b) return 1;
                return strcmp($end_date_a, $end_date_b);
            });
            
            return $performance_data;
        }
    }
} 