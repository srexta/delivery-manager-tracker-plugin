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
            // If needed, enqueue styles here
            /*
            wp_enqueue_style(
                'dmtp-public-styles',
                DMTP_PLUGIN_URL . 'assets/css/public-styles.css',
                array(),
                DMTP_VERSION
            );
            */
            
            // If needed, enqueue scripts here
            /*
            wp_enqueue_script(
                'dmtp-public-scripts',
                DMTP_PLUGIN_URL . 'assets/js/public-scripts.js',
                array('jquery'),
                DMTP_VERSION,
                true
            );
            */
        }
        
        // Add any other public-facing methods here, like shortcodes
        // Example shortcode:
        /*
        public function dmtp_sprint_list_shortcode($atts) {
            $atts = shortcode_atts(array(
                'count' => 5,
            ), $atts);
            
            // Logic to get and display sprints
            return 'Sprint list goes here';
        }
        */
    }
} 