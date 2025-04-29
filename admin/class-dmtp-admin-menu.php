<?php
/**
 * The class responsible for creating the admin menu and pages.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Admin_Menu')) {

    /**
     * Class for creating the admin menu and pages.
     */
    class DMTP_Admin_Menu {

        /**
         * Register the main admin menu and submenus.
         */
        public function register_menu() {
            // Main menu page
            add_menu_page(
                __('Delivery Manager', 'delivery-manager-tracking-plugin'),
                __('Delivery Manager', 'delivery-manager-tracking-plugin'),
                'read_dmtp_sprint', // Use a custom capability (or 'manage_options' for admin only)
                'dmtp_sprint_overview',
                array($this, 'render_sprint_overview_page'),
                'dashicons-chart-area',
                25 // Position
            );

            // Submenu: Sprint Overview (same as main page)
            add_submenu_page(
                'dmtp_sprint_overview',
                __('Sprint Overview', 'delivery-manager-tracking-plugin'),
                __('Sprint Overview', 'delivery-manager-tracking-plugin'),
                'read_dmtp_sprint',
                'dmtp_sprint_overview',
                array($this, 'render_sprint_overview_page')
            );

            // Submenu: Individual Tracker
            add_submenu_page(
                'dmtp_sprint_overview',
                __('Individual Tracker', 'delivery-manager-tracking-plugin'),
                __('Individual Tracker', 'delivery-manager-tracking-plugin'),
                'read_dmtp_sprint',
                'dmtp_individual_tracker',
                array($this, 'render_individual_tracker_page')
            );

            // Submenu: Hotfix Tracker
            add_submenu_page(
                'dmtp_sprint_overview',
                __('Hotfix Tracker', 'delivery-manager-tracking-plugin'),
                __('Hotfix Tracker', 'delivery-manager-tracking-plugin'),
                'read_dmtp_sprint',
                'dmtp_hotfix_tracker',
                array($this, 'render_hotfix_tracker_page')
            );

            // Submenu: Notes Section
            add_submenu_page(
                'dmtp_sprint_overview',
                __('Notes', 'delivery-manager-tracking-plugin'),
                __('Notes', 'delivery-manager-tracking-plugin'),
                'read_dmtp_sprint',
                'dmtp_notes_section',
                array($this, 'render_notes_section_page')
            );
            
            // Submenu: Link to Sprints CPT list
            add_submenu_page(
                'dmtp_sprint_overview',
                __('All Sprints', 'delivery-manager-tracking-plugin'),
                __('All Sprints', 'delivery-manager-tracking-plugin'),
                'edit_dmtp_sprints',
                'edit.php?post_type=dmtp_sprint'
            );
            
            // Submenu: Link to Stories CPT list
            add_submenu_page(
                'dmtp_sprint_overview',
                __('All Stories', 'delivery-manager-tracking-plugin'),
                __('All Stories', 'delivery-manager-tracking-plugin'),
                'edit_dmtp_stories',
                'edit.php?post_type=dmtp_story'
            );
            
            // Submenu: Link to Hotfixes CPT list
            add_submenu_page(
                'dmtp_sprint_overview',
                __('All Hotfixes', 'delivery-manager-tracking-plugin'),
                __('All Hotfixes', 'delivery-manager-tracking-plugin'),
                'edit_dmtp_hotfixes',
                'edit.php?post_type=dmtp_hotfix'
            );
        }

        /**
         * Render the Sprint Overview page.
         */
        public function render_sprint_overview_page() {
            include DMTP_PLUGIN_DIR . 'admin/views/sprint-overview.php';
        }

        /**
         * Render the Individual Tracker page.
         */
        public function render_individual_tracker_page() {
            include DMTP_PLUGIN_DIR . 'admin/views/individual-tracker.php';
        }

        /**
         * Render the Hotfix Tracker page.
         */
        public function render_hotfix_tracker_page() {
            // Ensure the view file exists (it should, we recreated it)
            $view_file = DMTP_PLUGIN_DIR . 'admin/views/hotfix-tracker.php';
            if (file_exists($view_file)) {
                 include $view_file;
            } else {
                 echo '<div class="wrap"><h2>Error</h2><p>Hotfix tracker view file not found.</p></div>';
            }
        }

        /**
         * Render the Notes Section page.
         */
        public function render_notes_section_page() {
            include DMTP_PLUGIN_DIR . 'admin/views/notes-section.php';
        }

        /**
         * Enqueue admin scripts and styles.
         *
         * @param string $hook The current admin page hook.
         */
        public function enqueue_scripts($hook) {
            // Check if we are on a page related to our plugin
            // Define plugin admin pages
            $plugin_pages = array(
                'toplevel_page_dmtp_sprint_overview',
                'delivery-manager_page_dmtp_individual_tracker',
                'delivery-manager_page_dmtp_hotfix_tracker',
                'delivery-manager_page_dmtp_notes_section',
                'delivery-manager_page_dmtp_settings',
                'post.php',
                'post-new.php',
            );

            // Limit script loading to relevant pages
            if (!in_array($hook, $plugin_pages)) {
                return;
            }
            
            // Enqueue admin styles
            wp_enqueue_style(
                'dmtp-admin-styles',
                DMTP_PLUGIN_URL . 'assets/css/admin-styles.css',
                array(),
                DMTP_VERSION
            );
            
            // Enqueue admin scripts
            wp_enqueue_script(
                'dmtp-admin-scripts',
                DMTP_PLUGIN_URL . 'admin/js/admin-scripts.js',
                array('jquery', 'acf-input'),
                DMTP_VERSION,
                true
            );
            
            // Enqueue Chart.js from CDN (or bundle it locally)
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '4.4.0', // Specify a version
                true
            );
            
            // Conditionally enqueue hotfix-specific script
            if ($hook === 'delivery-manager_page_dmtp_hotfix_tracker') {
                 wp_enqueue_script(
                    'dmtp-hotfix-admin-scripts',
                    DMTP_PLUGIN_URL . 'admin/js/hotfix-admin.js',
                    array('jquery', 'dmtp-admin-scripts'), // Depends on jQuery AND the main admin script (for downloadData)
                    DMTP_VERSION,
                    true
                );
            }
            
            // Data to pass to script
            $script_data = array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dmtp_ajax_nonce'),
                'teamsData' => array(), // Default empty
                // Field keys needed by JS
                'fieldKeys' => array(
                    'teamsCheckbox' => 'field_dmtp_selected_teams',
                    'performanceRepeater' => 'field_dmtp_member_performance',
                    'memberNameSubField' => 'field_performance_member_name',
                    'sprintHotfixRepeater' => 'field_sprint_team_hotfixes',
                    'hotfixTeamSelect' => 'field_sprint_hotfix_team_name',
                ),
                 // Add translatable strings for JS
                'i18n' => array(
                    'exporting' => esc_html__('Exporting...', 'delivery-manager-tracking-plugin'),
                    'exportError' => esc_html__('Error exporting data:', 'delivery-manager-tracking-plugin'),
                    'ajaxError' => esc_html__('AJAX error:', 'delivery-manager-tracking-plugin'),
                    'downloadError' => esc_html__('Your browser does not support the necessary features for downloading.', 'delivery-manager-tracking-plugin'),
                    'sprintBurndownTitle' => esc_html__('Sprint Burndown', 'delivery-manager-tracking-plugin'),
                    'velocityTrendTitle' => esc_html__('Velocity Trend (Last %d Sprints)', 'delivery-manager-tracking-plugin'),
                    'idealLine' => esc_html__('Ideal Burndown', 'delivery-manager-tracking-plugin'),
                    'actualLine' => esc_html__('Actual Remaining Points', 'delivery-manager-tracking-plugin'),
                    'committedPoints' => esc_html__('Committed Points', 'delivery-manager-tracking-plugin'),
                    'completedPoints' => esc_html__('Completed Points', 'delivery-manager-tracking-plugin'),
                    'deliveredPoints' => esc_html__('Delivered Points', 'delivery-manager-tracking-plugin'),
                    'sprint' => esc_html__('Sprint', 'delivery-manager-tracking-plugin'),
                )
            );

            // --- Add Team Data Specifically for Sprint Edit Screens --- 
            global $pagenow, $post_type;
            if (($pagenow == 'post.php' || $pagenow == 'post-new.php') && 
                isset($_GET['post_type']) && $_GET['post_type'] == 'dmtp_sprint' || 
                isset($post_type) && $post_type == 'dmtp_sprint')
            {
                if (function_exists('get_field') && have_rows('dmtp_teams', 'option')) {
                    $teams_structure = array();
                    while (have_rows('dmtp_teams', 'option')) : the_row();
                        $team_name = get_sub_field('team_name');
                        if ($team_name) {
                            $team_key = esc_attr($team_name); // Use the same key format as in acf_load_team_choices
                            $members = array();
                            if (have_rows('team_members')) {
                                while(have_rows('team_members')): the_row();
                                    $member_name = get_sub_field('member_name');
                                    if ($member_name) {
                                        $members[] = $member_name; // Just store the names
                                    }
                                endwhile;
                            }
                            $teams_structure[$team_key] = $members;
                        }
                    endwhile;
                    reset_rows();
                    $script_data['teamsData'] = $teams_structure;
                }
            }

            // Localize script with data
            wp_localize_script('dmtp-admin-scripts', 'dmtp_localized_data', $script_data);
        }
    }
} 