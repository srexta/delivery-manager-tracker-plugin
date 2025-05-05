<?php
/**
 * Helper class for Delivery Manager metrics calculations.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Metrics_Helper')) {
    class DMTP_Metrics_Helper {
        /**
         * Calculate productivity ratio: Delivered Story Points / Estimated Hours
         * @param int $post_id Sprint post ID
         * @return float
         */
        public static function get_productivity_ratio($post_id) {
            $delivered = (float) get_field('member_delivered_story_points', $post_id);
            $estimated = (float) get_field('member_estimated_hours', $post_id);
            if ($estimated > 0) {
                return round($delivered / $estimated, 2);
            }
            return 0;
        }

        /**
         * Calculate bug density: Bugs Found / Story Points
         * @param int $post_id Sprint post ID
         * @return float
         */
        public static function get_bug_density($post_id) {
            $bugs = (int) get_field('bugs_found', $post_id);
            $story_points = (int) get_field('member_total_story_points', $post_id);
            if ($story_points > 0) {
                return round($bugs / $story_points, 2);
            }
            return 0;
        }

        /**
         * Calculate health score (weighted average of quality, delivery, cost)
         * @param int $post_id Sprint post ID
         * @return int
         */
        public static function get_health_score($post_id) {
            $quality = (int) get_field('quality_score', $post_id);
            $delivery = (int) get_field('delivery_score', $post_id);
            $cost = (int) get_field('cost_score', $post_id);
            // Example weights: Quality 40%, Delivery 40%, Cost 20%
            return round(($quality * 0.4) + ($delivery * 0.4) + ($cost * 0.2));
        }
    }
} 