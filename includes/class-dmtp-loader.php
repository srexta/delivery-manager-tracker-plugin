<?php
/**
 * The main loader class for the plugin.
 *
 * @package DMTP
 */

if (!class_exists('DMTP_Loader')) {

    /**
     * The DMTP_Loader class handles loading all plugin components.
     */
    class DMTP_Loader {

        /**
         * The array of actions registered with WordPress.
         *
         * @var array $actions The actions registered with WordPress to fire when the plugin loads.
         */
        protected $actions;

        /**
         * The array of filters registered with WordPress.
         *
         * @var array $filters The filters registered with WordPress to fire when the plugin loads.
         */
        protected $filters;

        /**
         * Initialize the collections used to maintain the actions and filters.
         */
        public function __construct() {
            $this->actions = array();
            $this->filters = array();

            $this->load_dependencies();
            $this->define_admin_hooks();
            $this->define_public_hooks();
        }

        /**
         * Load the required dependencies for this plugin.
         */
        private function load_dependencies() {
            // Include class files
            require_once DMTP_PLUGIN_DIR . 'includes/class-dmtp-post-types.php';
            require_once DMTP_PLUGIN_DIR . 'includes/class-dmtp-permissions.php';
            require_once DMTP_PLUGIN_DIR . 'includes/class-dmtp-ajax-handlers.php';
            require_once DMTP_PLUGIN_DIR . 'includes/class-dmtp-utilities.php';
            require_once DMTP_PLUGIN_DIR . 'includes/class-dmtp-reports.php';
            require_once DMTP_PLUGIN_DIR . 'includes/class-dmtp-hooks.php';
            
            // Admin class
            require_once DMTP_PLUGIN_DIR . 'admin/class-dmtp-admin-menu.php';
            
            // Public class
            require_once DMTP_PLUGIN_DIR . 'public/class-dmtp-public.php';
        }

        /**
         * Register all of the hooks related to the admin area functionality
         * of the plugin.
         */
        private function define_admin_hooks() {
            // Initialize classes
            $post_types = new DMTP_Post_Types();
            $permissions = new DMTP_Permissions();
            $ajax_handlers = new DMTP_Ajax_Handlers();
            $utilities = new DMTP_Utilities();
            $reports = new DMTP_Reports();
            $hooks = new DMTP_Hooks();
            $admin_menu = new DMTP_Admin_Menu();
            
            // Register hooks for post types
            $this->add_action('init', $post_types, 'register_post_types');
            $this->add_action('acf/init', $post_types, 'register_acf_fields');
            $this->add_action('acf/init', $post_types, 'register_acf_options_page');
            // Filter to populate team choices dynamically
            $this->add_filter('acf/load_field/key=field_dmtp_selected_teams', $post_types, 'acf_load_team_choices');
            // Filter to populate team choices for user profiles
            $this->add_filter('acf/load_field/key=field_dmtp_user_teams', $post_types, 'acf_load_team_choices');
            
            // Register hooks for permissions
            $this->add_action('admin_init', $permissions, 'setup_roles');
            $this->add_action('pre_get_posts', $permissions, 'filter_queries_by_user');
            $this->add_action('load-post.php', $permissions, 'restrict_post_access');
            $this->add_action('load-post-new.php', $permissions, 'restrict_post_access');
            
            // Register admin menu
            $this->add_action('admin_menu', $admin_menu, 'register_menu');
            
            // Register admin scripts and styles
            $this->add_action('admin_enqueue_scripts', $admin_menu, 'enqueue_scripts');
            
            // Register AJAX handlers
            $this->add_action('wp_ajax_dmtp_get_sprint_data', $ajax_handlers, 'get_sprint_data');
            $this->add_action('wp_ajax_dmtp_update_sprint', $ajax_handlers, 'update_sprint');
            $this->add_action('wp_ajax_dmtp_export_data', $ajax_handlers, 'export_data');
            // Add the new AJAX action for getting developers by team
            $this->add_action('wp_ajax_dmtp_get_developers_for_team', $ajax_handlers, 'dmtp_get_developers_for_team');
            // Add the new AJAX action for getting sprints by team for the filter
            $this->add_action('wp_ajax_dmtp_get_sprints_for_team_filter', $ajax_handlers, 'get_sprints_for_team_filter');
            
            // Register general hooks
            $this->add_action('save_post_dmtp_sprint', $hooks, 'save_sprint_post', 10, 3);
            $this->add_action('save_post_dmtp_story', $hooks, 'save_story_post', 10, 3);
            $this->add_action('wp_trash_post', $hooks, 'trash_post_cleanup');
            
            // Register admin column hooks for Sprints
            $this->add_filter('manage_dmtp_sprint_posts_columns', $hooks, 'add_sprint_admin_columns');
            $this->add_action('manage_dmtp_sprint_posts_custom_column', $hooks, 'display_sprint_admin_columns', 10, 2); // Need 2 args ($column, $post_id)
        }

        /**
         * Register all of the hooks related to the public-facing functionality
         * of the plugin.
         */
        private function define_public_hooks() {
            $public = new DMTP_Public();
            $hooks = new DMTP_Hooks();
            
            // Register public scripts and styles if needed
            $this->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');
            
            // Register content modification hook
            $this->add_filter('the_content', $hooks, 'modify_content_display');
            
            // Register shortcodes if any
            // $this->add_shortcode('dmtp_sprint_list', $public, 'dmtp_sprint_list_shortcode');
        }

        /**
         * Add a new action to the collection to be registered with WordPress.
         *
         * @param string $hook          The name of the WordPress action that is being registered.
         * @param object $component     A reference to the instance of the object on which the action is defined.
         * @param string $callback      The name of the function definition on the $component.
         * @param int    $priority      Optional. The priority at which the function should be fired. Default is 10.
         * @param int    $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
         */
        public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
            $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
        }

        /**
         * Add a new filter to the collection to be registered with WordPress.
         *
         * @param string $hook          The name of the WordPress filter that is being registered.
         * @param object $component     A reference to the instance of the object on which the filter is defined.
         * @param string $callback      The name of the function definition on the $component.
         * @param int    $priority      Optional. The priority at which the function should be fired. Default is 10.
         * @param int    $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
         */
        public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
            $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
        }

        /**
         * A utility function that is used to register the actions and hooks into a single
         * collection.
         *
         * @param array  $hooks         The collection of hooks that is being registered (that is, actions or filters).
         * @param string $hook          The name of the WordPress filter that is being registered.
         * @param object $component     A reference to the instance of the object on which the filter is defined.
         * @param string $callback      The name of the function definition on the $component.
         * @param int    $priority      The priority at which the function should be fired.
         * @param int    $accepted_args The number of arguments that should be passed to the $callback.
         *
         * @return array The collection of actions and filters registered with WordPress.
         */
        private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
            $hooks[] = array(
                'hook'          => $hook,
                'component'     => $component,
                'callback'      => $callback,
                'priority'      => $priority,
                'accepted_args' => $accepted_args,
            );

            return $hooks;
        }

        /**
         * Run the loader to execute all of the hooks with WordPress.
         */
        public function run() {
            // Register all actions
            foreach ($this->actions as $hook) {
                add_action(
                    $hook['hook'],
                    array($hook['component'], $hook['callback']),
                    $hook['priority'],
                    $hook['accepted_args']
                );
            }

            // Register all filters
            foreach ($this->filters as $hook) {
                add_filter(
                    $hook['hook'],
                    array($hook['component'], $hook['callback']),
                    $hook['priority'],
                    $hook['accepted_args']
                );
            }
        }

        /**
         * Add a new shortcode to the collection to be registered with WordPress.
         *
         * @param string $tag           The name of the shortcode tag.
         * @param object $component     A reference to the instance of the object on which the shortcode is defined.
         * @param string $callback      The name of the function definition on the $component.
         */
        public function add_shortcode($tag, $component, $callback) {
            // Shortcodes are added directly, not stored in an array like actions/filters
            add_shortcode($tag, array($component, $callback));
        }
    }
} 