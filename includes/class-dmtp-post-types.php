<?php
/**
 * The class responsible for registering custom post types and ACF field groups.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Post_Types')) {

    /**
     * Class for registering custom post types and ACF field groups.
     */
    class DMTP_Post_Types {

        /**
         * Register the custom post types for the plugin.
         */
        public function register_post_types() {
            // Register Sprint CPT
            register_post_type('dmtp_sprint', array(
                'labels' => array(
                    'name'               => __('Sprints', 'delivery-manager-tracking-plugin'),
                    'singular_name'      => __('Sprint', 'delivery-manager-tracking-plugin'),
                    'menu_name'          => __('Sprints', 'delivery-manager-tracking-plugin'),
                    'name_admin_bar'     => __('Sprint', 'delivery-manager-tracking-plugin'),
                    'add_new'            => __('Add New', 'delivery-manager-tracking-plugin'),
                    'add_new_item'       => __('Add New Sprint', 'delivery-manager-tracking-plugin'),
                    'new_item'           => __('New Sprint', 'delivery-manager-tracking-plugin'),
                    'edit_item'          => __('Edit Sprint', 'delivery-manager-tracking-plugin'),
                    'view_item'          => __('View Sprint', 'delivery-manager-tracking-plugin'),
                    'all_items'          => __('All Sprints', 'delivery-manager-tracking-plugin'),
                    'search_items'       => __('Search Sprints', 'delivery-manager-tracking-plugin'),
                    'parent_item_colon'  => __('Parent Sprints:', 'delivery-manager-tracking-plugin'),
                    'not_found'          => __('No sprints found.', 'delivery-manager-tracking-plugin'),
                    'not_found_in_trash' => __('No sprints found in Trash.', 'delivery-manager-tracking-plugin')
                ),
                'public'              => true,
                'show_ui'             => true,
                'show_in_menu'        => false, // Will be added as submenu
                'capability_type'     => 'post',
                'has_archive'         => false,
                'hierarchical'        => false,
                'menu_position'       => null,
                'supports'            => array('title', 'editor', 'author'),
                'show_in_rest'        => true,
                'rewrite'             => array('slug' => 'sprint'),
            ));

            // Register Story CPT
            register_post_type('dmtp_story', array(
                'labels' => array(
                    'name'               => __('Stories', 'delivery-manager-tracking-plugin'),
                    'singular_name'      => __('Story', 'delivery-manager-tracking-plugin'),
                    'menu_name'          => __('Stories', 'delivery-manager-tracking-plugin'),
                    'name_admin_bar'     => __('Story', 'delivery-manager-tracking-plugin'),
                    'add_new'            => __('Add New', 'delivery-manager-tracking-plugin'),
                    'add_new_item'       => __('Add New Story', 'delivery-manager-tracking-plugin'),
                    'new_item'           => __('New Story', 'delivery-manager-tracking-plugin'),
                    'edit_item'          => __('Edit Story', 'delivery-manager-tracking-plugin'),
                    'view_item'          => __('View Story', 'delivery-manager-tracking-plugin'),
                    'all_items'          => __('All Stories', 'delivery-manager-tracking-plugin'),
                    'search_items'       => __('Search Stories', 'delivery-manager-tracking-plugin'),
                    'parent_item_colon'  => __('Parent Stories:', 'delivery-manager-tracking-plugin'),
                    'not_found'          => __('No stories found.', 'delivery-manager-tracking-plugin'),
                    'not_found_in_trash' => __('No stories found in Trash.', 'delivery-manager-tracking-plugin')
                ),
                'public'              => true,
                'show_ui'             => true,
                'show_in_menu'        => false, // Will be added as submenu
                'capability_type'     => 'post',
                'has_archive'         => false,
                'hierarchical'        => false,
                'menu_position'       => null,
                'supports'            => array('title', 'editor', 'author'),
                'show_in_rest'        => true,
                'rewrite'             => array('slug' => 'story'),
            ));
        }

        /**
         * Register ACF field groups for the custom post types.
         */
        public function register_acf_fields() {
            // Check if ACF is active
            if (!function_exists('acf_add_local_field_group')) {
                return;
            }

            // Register Options Page for Settings
            if (function_exists('acf_add_local_field_group') && function_exists('acf_add_options_page')) {
                acf_add_options_page(array(
                    'page_title'    => __('Delivery Manager Settings', 'delivery-manager-tracking-plugin'),
                    'menu_title'    => __('Settings', 'delivery-manager-tracking-plugin'),
                    'menu_slug'     => 'dmtp-settings',
                    'capability'    => 'manage_options', // Adjust capability if needed
                    'parent_slug'   => 'dmtp_sprint_overview', // Attach to main menu
                    'position'      => false,
                    'icon_url'      => false,
                    'redirect'      => false
                ));
            }

            // --- Settings Page Field Group ---
            acf_add_local_field_group(array(
                'key' => 'group_dmtp_settings',
                'title' => 'Team Settings',
                'fields' => array(
                    array(
                        'key' => 'field_dmtp_teams',
                        'label' => 'Teams',
                        'name' => 'dmtp_teams',
                        'type' => 'repeater',
                        'instructions' => 'Define your teams and their members.',
                        'required' => 0,
                        'layout' => 'block',
                        'button_label' => 'Add Team',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_dmtp_team_name',
                                'label' => 'Team Name',
                                'name' => 'team_name',
                                'type' => 'text',
                                'required' => 1,
                            ),
                            array(
                                'key' => 'field_dmtp_team_members',
                                'label' => 'Team Members',
                                'name' => 'team_members',
                                'type' => 'repeater',
                                'required' => 0,
                                'layout' => 'table',
                                'button_label' => 'Add Member',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_dmtp_member_name',
                                        'label' => 'Member Name',
                                        'name' => 'member_name',
                                        'type' => 'text',
                                        'required' => 1,
                                    ),
                                    array(
                                        'key' => 'field_dmtp_member_role',
                                        'label' => 'Role',
                                        'name' => 'member_role',
                                        'type' => 'text',
                                        'required' => 0,
                                    ),
                                    array(
                                        'key' => 'field_dmtp_member_designation',
                                        'label' => 'Designation',
                                        'name' => 'member_designation',
                                        'type' => 'text',
                                        'required' => 0,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'dmtp-settings', // Matches menu_slug
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ));

            // --- Sprint Field Group Modification ---
            acf_add_local_field_group(array(
                'key' => 'group_dmtp_sprint',
                'title' => 'Sprint Details',
                'fields' => array(
                    array(
                        'key' => 'tab_general',
                        'label' => 'General',
                        'type' => 'tab',
                        'placement' => 'top',
                        'endpoint' => 0,
                    ),
                    array(
                        'key' => 'field_sprint_start_date',
                        'label' => 'Start Date',
                        'name' => 'start_date',
                        'type' => 'date_picker',
                        'instructions' => 'Select the sprint start date',
                        'required' => 1,
                        'display_format' => 'F j, Y',
                        'return_format' => 'Y-m-d',
                        'wrapper' => array('width' => '25'),
                    ),
                    array(
                        'key' => 'field_sprint_end_date',
                        'label' => 'End Date',
                        'name' => 'end_date',
                        'type' => 'date_picker',
                        'instructions' => 'Select the sprint end date',
                        'required' => 1,
                        'display_format' => 'F j, Y',
                        'return_format' => 'Y-m-d',
                        'wrapper' => array('width' => '25'),
                    ),
                    array(
                        'key' => 'field_sprint_demo_date',
                        'label' => 'Demonstration Date',
                        'name' => 'demonstration_date',
                        'type' => 'date_picker',
                        'instructions' => 'Select the demonstration date for this sprint',
                        'required' => 0,
                        'display_format' => 'F j, Y',
                        'return_format' => 'Y-m-d',
                        'wrapper' => array('width' => '25'),
                    ),
                    array(
                        'key' => 'field_sprint_retro_day',
                        'label' => 'Retrospective Day',
                        'name' => 'retrospective_day',
                        'type' => 'date_picker',
                        'instructions' => 'Select the retrospective day for this sprint',
                        'required' => 0,
                        'display_format' => 'F j, Y',
                        'return_format' => 'Y-m-d',
                        'wrapper' => array('width' => '25'),
                    ),
                    array(
                        'key' => 'tab_teams',
                        'label' => 'Teams',
                        'type' => 'tab',
                        'placement' => 'top',
                        'endpoint' => 0,
                    ),
                    array(
                        'key' => 'field_dmtp_selected_teams',
                        'label' => 'Select Teams for this Sprint',
                        'name' => 'selected_teams',
                        'type' => 'checkbox',
                        'instructions' => 'Select the teams participating in this sprint. Performance tracking fields will appear below.',
                        'required' => 0,
                        'choices' => array(), // To be populated dynamically
                        'allow_custom' => 0,
                        'save_custom' => 0,
                        'layout' => 'vertical',
                        'toggle' => 0,
                        'return_format' => 'value',
                    ),
                    array(
                        'key' => 'field_dmtp_member_performance',
                        'label' => 'Member Performance Tracking',
                        'name' => 'dmtp_member_performance',
                        'type' => 'repeater',
                        'wrapper' => array(
                            'class' => 'dmtp-member-performance-repeater',
                        ),
                        'instructions' => 'Track individual performance for members of the selected teams.',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_dmtp_selected_teams',
                                    'operator' => '!=empty',
                                ),
                            ),
                        ),
                        'layout' => 'block',
                        'button_label' => 'Add Member Performance',
                        'sub_fields' => array(
                            // Sub-fields for the performance repeater
                             array(
                                'key' => 'field_performance_member_name',
                                'label' => 'Member Name',
                                'name' => 'member_name',
                                'type' => 'select',
                                'choices' => self::dmtp_get_all_member_choices(),
                                'allow_null' => 1,
                                'ui' => 1,
                                'ajax' => 0,
                                'placeholder' => '',
                                'required' => 1,
                                'wrapper' => array('width' => '10'),
                            ),
                             array(
                                'key' => 'field_performance_estimated_hours',
                                'label' => 'Est. Hours',
                                'name' => 'member_estimated_hours',
                                'type' => 'number',
                                'min' => 0,
                                'step' => 0.5,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                            array(
                                'key' => 'field_performance_total_sp',
                                'label' => 'Committed Story Point',
                                'name' => 'member_total_story_points',
                                'type' => 'number',
                                'min' => 0,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                            array(
                                'key' => 'field_performance_delivered_sp',
                                'label' => 'Delivered Story Point',
                                'name' => 'member_delivered_story_points',
                                'type' => 'number',
                                'min' => 0,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                            array(
                                'key' => 'field_performance_hotfixes',
                                'label' => 'Hotfixes #',
                                'name' => 'member_hotfixes_count',
                                'type' => 'number',
                                'min' => 0,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                             array(
                                'key' => 'field_performance_absent_days',
                                'label' => 'Absent Days',
                                'name' => 'member_absent_days',
                                'type' => 'number',
                                'min' => 0,
                                'step' => 0.5,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                             array(
                                'key' => 'field_performance_rating',
                                'label' => 'Rating (1-5)',
                                'name' => 'member_rating',
                                'type' => 'number',
                                'min' => 1,
                                'max' => 5,
                                'step' => 1,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                            array(
                                'key' => 'field_performance_code_reviews',
                                'label' => 'Code Reviews Submitted',
                                'name' => 'code_reviews_submitted',
                                'type' => 'text',
                                'instructions' => 'Format: submitted / assigned (e.g. 5 / 6)',
                                'required' => 0,
                                'wrapper' => array('width' => '15'),
                            ),
                            array(
                                'key' => 'field_performance_qa_pass_rate',
                                'label' => 'QA Pass Rate (%)',
                                'name' => 'qa_pass_rate',
                                'type' => 'number',
                                'instructions' => 'Enter as a percentage (e.g. 88)',
                                'min' => 0,
                                'max' => 100,
                                'step' => 1,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                            array(
                                'key' => 'field_performance_bugs_logged',
                                'label' => 'Bugs Logged on Tasks',
                                'name' => 'bugs_logged_on_tasks',
                                'type' => 'number',
                                'min' => 0,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                            array(
                                'key' => 'field_performance_task_delay_count',
                                'label' => 'Task Delay Count',
                                'name' => 'task_delay_count',
                                'type' => 'number',
                                'min' => 0,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                            array(
                                'key' => 'field_performance_low_priority_time',
                                'label' => 'Time on Low Priority Tasks (hrs)',
                                'name' => 'time_on_low_priority_tasks',
                                'type' => 'number',
                                'min' => 0,
                                'step' => 0.5,
                                'required' => 0,
                                'wrapper' => array('width' => '10'),
                            ),
                            array(
                                'key' => 'field_performance_retro_notes',
                                'label' => 'Individual Retrospective Notes',
                                'name' => 'member_retrospective_notes',
                                'type' => 'wysiwyg',
                                'tabs' => 'visual',
                                'toolbar' => 'basic',
                                'media_upload' => 0,
                                'delay' => 0,
                                'required' => 0,
                            ),
                        ),
                    ),
                    array(
                        'key' => 'tab_hotfixes',
                        'label' => 'Hotfixes',
                        'type' => 'tab',
                        'placement' => 'top',
                        'endpoint' => 0,
                    ),
                    array(
                        'key' => 'field_sprint_team_hotfixes',
                        'label' => 'Team Hotfixes for this Sprint',
                        'name' => 'sprint_team_hotfixes',
                        'type' => 'repeater',
                        'instructions' => 'Log hotfixes worked on by each team during this sprint.',
                        'required' => 0,
                        'conditional_logic' => array( 
                            array(
                                array(
                                    'field' => 'field_dmtp_selected_teams',
                                    'operator' => '!=empty',
                                ),
                            ),
                        ),
                        'layout' => 'block', 
                        'button_label' => 'Add Team Hotfix Log',
                        'sub_fields' => array(
                            array(
                                'key' => 'field_sprint_hotfix_team_name',
                                'label' => 'Team',
                                'name' => 'team_name',
                                'type' => 'select',
                                'choices' => array(), // Requires dynamic population hook/JS
                                'allow_null' => 0,
                                'required' => 1,
                                'wrapper' => array('width' => '30'),
                            ),
                            array(
                                'key' => 'field_sprint_team_hotfix_list',
                                'label' => 'Hotfixes Logged',
                                'name' => 'team_hotfix_list',
                                'type' => 'repeater',
                                'required' => 0,
                                'layout' => 'table',
                                'button_label' => 'Add Hotfix',
                                'sub_fields' => array(
                                    array(
                                        'key' => 'field_nested_hotfix_title',
                                        'label' => 'Hotfix Title',
                                        'name' => 'nested_hotfix_title',
                                        'type' => 'text',
                                        'wrapper' => array('width' => '30'),
                                        'required' => 1,
                                    ),
                                    array(
                                        'key' => 'field_nested_hotfix_estimation',
                                        'label' => 'Estimation Time',
                                        'name' => 'hotfix_estimation_time',
                                        'type' => 'text',
                                        'instructions' => 'e.g., 2 hours, 1 day',
                                        'required' => 0,
                                    ),
                                    array(
                                        'key' => 'field_nested_hotfix_critical',
                                        'label' => 'Critical/Response Met?',
                                        'name' => 'hotfix_critical_response',
                                        'type' => 'true_false',
                                        'message' => '',
                                        'ui' => 1,
                                        'ui_on_text' => 'Yes',
                                        'ui_off_text' => 'No',
                                        'default_value' => 0,
                                        'wrapper' => array('width' => '15'),
                                    ),
                                    array(
                                        'key' => 'field_nested_hotfix_github',
                                        'label' => 'GitHub Link',
                                        'name' => 'hotfix_github_link',
                                        'type' => 'url',
                                        'wrapper' => array('width' => '30'),
                                    ),
                                    array(
                                        'key' => 'field_nested_hotfix_rca',
                                        'label' => 'RCA Link',
                                        'name' => 'hotfix_rca_link',
                                        'type' => 'url',
                                        'wrapper' => array('width' => '30'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'tab_notes',
                        'label' => 'Notes',
                        'type' => 'tab',
                        'placement' => 'top',
                        'endpoint' => 0,
                    ),
                    array(
                        'key' => 'field_sprint_planning_note',
                        'label' => 'Planning Note',
                        'name' => 'planning_note',
                        'type' => 'wysiwyg',
                        'instructions' => 'Enter notes from the sprint planning meeting',
                        'required' => 0,
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ),
                    array(
                        'key' => 'field_sprint_retrospective_note',
                        'label' => 'Retrospective Note',
                        'name' => 'retrospective_note',
                        'type' => 'wysiwyg',
                        'instructions' => 'Enter notes from the sprint retrospective meeting',
                        'required' => 0,
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'media_upload' => 1,
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'dmtp_sprint',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => 1,
                'description' => '',
            ));

            // Story field group
            acf_add_local_field_group(array(
                'key' => 'group_dmtp_story',
                'title' => 'Story Details',
                'fields' => array(
                    array(
                        'key' => 'field_story_sprint',
                        'label' => 'Sprint',
                        'name' => 'related_sprint',
                        'type' => 'post_object',
                        'instructions' => 'Select the sprint this story belongs to',
                        'required' => 1,
                        'post_type' => array('dmtp_sprint'),
                        'return_format' => 'id',
                    ),
                    array(
                        'key' => 'field_story_assigned_user',
                        'label' => 'Assigned User',
                        'name' => 'assigned_user',
                        'type' => 'user',
                        'instructions' => 'Select the user assigned to this story',
                        'required' => 1,
                        'role' => '',
                        'return_format' => 'id',
                    ),
                    array(
                        'key' => 'field_story_points',
                        'label' => 'Story Points',
                        'name' => 'story_points',
                        'type' => 'number',
                        'instructions' => 'Enter the story points for this story',
                        'required' => 1,
                        'min' => 0,
                    ),
                    array(
                        'key' => 'field_story_estimated_hours',
                        'label' => 'Estimated Hours',
                        'name' => 'estimated_hours',
                        'type' => 'number',
                        'instructions' => 'Enter the estimated hours for this story.',
                        'required' => 0,
                        'min' => 0,
                        'step' => 0.5,
                    ),
                    array(
                        'key' => 'field_story_status',
                        'label' => 'Status',
                        'name' => 'status',
                        'type' => 'select',
                        'instructions' => 'Select the current status of this story',
                        'required' => 1,
                        'choices' => array(
                            'backlog' => 'Backlog',
                            'in_progress' => 'In Progress',
                            'in_review' => 'In Review',
                            'done' => 'Done',
                            'blocked' => 'Blocked',
                        ),
                        'default_value' => 'backlog',
                        'return_format' => 'value',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'dmtp_story',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => 1,
                'description' => '',
            ));

            // --- User Profile Field Group ---
            acf_add_local_field_group(array(
                'key' => 'group_dmtp_user_teams',
                'title' => 'Delivery Manager Teams',
                'fields' => array(
                    array(
                        'key' => 'field_dmtp_user_teams',
                        'label' => 'Assigned Teams',
                        'name' => 'user_teams',
                        'type' => 'checkbox',
                        'instructions' => 'Select the teams this user belongs to.',
                        'required' => 0,
                        'choices' => array(), // Dynamically populated
                        'allow_custom' => 0,
                        'save_custom' => 0,
                        'layout' => 'vertical',
                        'toggle' => 0,
                        'return_format' => 'value',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'user_role',
                            'operator' => '==',
                            'value' => 'dmtp_developer',
                        ),
                    ),
                    array(
                        array(
                            'param' => 'user_role',
                            'operator' => '==',
                            'value' => 'dmtp_manager',
                        ),
                    ),
                    // Add Administrator role if needed
                    // array(
                    //     array(
                    //         'param' => 'user_role',
                    //         'operator' => '==',
                    //         'value' => 'administrator',
                    //     ),
                    // ),
                ),
                'menu_order' => 10, // Position it appropriately on the user profile page
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => 'Assign user to relevant DMTP teams.',
            ));
        }

        /**
         * Register ACF Options page.
         */
        public function register_acf_options_page() {
            // Check if ACF and the options page function exist
            if (function_exists('acf_add_options_page')) {
                acf_add_options_page(array(
                    'page_title'    => __('Delivery Manager Settings', 'delivery-manager-tracking-plugin'),
                    'menu_title'    => __('Settings', 'delivery-manager-tracking-plugin'),
                    'menu_slug'     => 'dmtp-settings',
                    'capability'    => 'manage_options', // Adjust capability if needed
                    'parent_slug'   => 'dmtp_sprint_overview', // Attach to main menu slug
                    'position'      => 99, // Position it towards the bottom
                    'icon_url'      => false,
                    'redirect'      => false
                ));
            }
        }

        /**
         * Dynamically populates the choices for the 'selected_teams' field.
         *
         * @param array $field The field settings array.
         * @return array Modified field settings array.
         */
        public function acf_load_team_choices($field) {
            $field['choices'] = array();
        
            // Get teams from options page
            if (function_exists('have_rows') && have_rows('dmtp_teams', 'option')) {
                while (have_rows('dmtp_teams', 'option')) : the_row();
                    $team_name = get_sub_field('team_name');
                    if ($team_name) {
                        // Use a sanitized version for the key if needed, but team name itself is often fine
                        $field['choices'][esc_attr($team_name)] = esc_html($team_name);
                    }
                endwhile;
                reset_rows(); // Important after looping options repeater
            }
            
             // If no teams are defined, provide a default message or option
            if (empty($field['choices'])) {
                 $field['instructions'] .= ' ' . __('No teams defined in DMTP Settings.', 'delivery-manager-tracking-plugin');
                 // Optionally disable the field if no choices
                 // $field['disabled'] = 1;
             }
        
            return $field;
        }

        /**
         * Get all unique member names from the settings page for use in select fields.
         *
         * @return array Array of member names formatted as 'name' => 'Name'.
         */
        public static function dmtp_get_all_member_choices() {
            $member_choices = array();
            $all_members = array();

            if (function_exists('have_rows') && have_rows('dmtp_teams', 'option')) {
                while (have_rows('dmtp_teams', 'option')) : the_row();
                    if (have_rows('team_members')) {
                        while(have_rows('team_members')): the_row();
                            $member_name = get_sub_field('member_name');
                            if ($member_name && !in_array($member_name, $all_members)) {
                                $all_members[] = $member_name; // Add to list to track uniqueness
                                $member_choices[esc_attr($member_name)] = esc_html($member_name);
                            }
                        endwhile;
                    }
                endwhile;
                reset_rows();
            }

            // Sort members alphabetically by label
            asort($member_choices);

            // Add a default placeholder option
            return array('' => '- Select Member -') + $member_choices;
        }
    }
} 