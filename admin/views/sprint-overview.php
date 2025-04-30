<?php
/**
 * View for the Sprint Overview admin page.
 *
 * @package DMTP
 */

// Get utility functions
$utilities = new DMTP_Utilities();

// Get query parameters for filtering
$start_filter = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
$end_filter = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
$team_filter = isset($_GET['team']) ? sanitize_text_field($_GET['team']) : '';

// Get available teams (from ACF field definition or hardcode for now)
// Note: Ideally, fetch choices dynamically from ACF field group if possible
$available_teams = array(
    'wptravelengine' => 'Wptravelengine',
    'tripcart' => 'TripCart',
);

?>
<div class="wrap">
    <h1><?php echo esc_html__('Sprint Overview', 'delivery-manager-tracking-plugin'); ?></h1>
    
    <p><?php echo esc_html__('Overview of sprints, velocity, and burndown trends.', 'delivery-manager-tracking-plugin'); ?></p>
    
    <!-- Filters for date range -->
    <div class="dmtp-filters">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            <label for="start_date"><?php esc_html_e('Start Date:', 'delivery-manager-tracking-plugin'); ?></label>
            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_filter); ?>">
            
            <label for="end_date"><?php esc_html_e('End Date:', 'delivery-manager-tracking-plugin'); ?></label>
            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_filter); ?>">
            
            <label for="team"><?php esc_html_e('Team:', 'delivery-manager-tracking-plugin'); ?></label>
            <select id="team" name="team">
                <option value=""><?php esc_html_e('-- All Teams --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php foreach ($available_teams as $value => $label) : ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($team_filter, $value); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            
            <input type="submit" value="<?php esc_attr_e('Filter', 'delivery-manager-tracking-plugin'); ?>" class="button">
        </form>
    </div>
    
    <!-- Sprint list -->
    <div class="dmtp-sprint-list">
        <h2><?php esc_html_e('Sprints', 'delivery-manager-tracking-plugin'); ?></h2>
        <table class="wp-list-table widefat fixed striped sprints">
            <thead>
                <tr>
                    <th><?php esc_html_e('Sprint Name', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Team', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Start Date', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('End Date', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Est. Hours', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Committed Points', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Delivered Points', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Delivered Velocity', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Completion %', 'delivery-manager-tracking-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query Sprints
                $args = array(
                    'post_type' => 'dmtp_sprint',
                    'posts_per_page' => -1,
                    'orderby' => 'meta_value',
                    'meta_key' => 'start_date',
                    'order' => 'DESC',
                );
                
                $meta_query = array('relation' => 'AND');

                // Add date filters if provided
                if (!empty($start_filter) && !empty($end_filter)) {
                    $meta_query[] = array(
                        'relation' => 'OR',
                        array(
                            'key' => 'start_date',
                            'value' => array($start_filter, $end_filter),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE',
                        ),
                        array(
                            'key' => 'end_date',
                            'value' => array($start_filter, $end_filter),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE',
                        ),
                        array(
                            'relation' => 'AND',
                            array(
                                'key' => 'start_date',
                                'value' => $start_filter,
                                'compare' => '<=',
                                'type' => 'DATE',
                            ),
                            array(
                                'key' => 'end_date',
                                'value' => $end_filter,
                                'compare' => '>=',
                                'type' => 'DATE',
                            ),
                        ),
                    );
                }
                
                // Add team filter if provided
                if (!empty($team_filter)) {
                    $meta_query[] = array(
                        'key' => 'selected_teams',
                        'value' => '"' . $team_filter . '"',
                        'compare' => 'LIKE',
                    );
                }
                
                // Assign meta query if it contains filters
                if (count($meta_query) > 1) {
                    $args['meta_query'] = $meta_query;
                }
                
                $sprint_query = new WP_Query($args);
                $reports = new DMTP_Reports();

                if ($sprint_query->have_posts()) :
                    while ($sprint_query->have_posts()) : $sprint_query->the_post();
                        $sprint_id = get_the_ID();
                        $start_date = get_post_meta($sprint_id, 'start_date', true);
                        $end_date = get_post_meta($sprint_id, 'end_date', true);
                        $assigned_team_keys = get_post_meta($sprint_id, 'selected_teams', true);
                        $team_labels = array();
                        if (is_array($assigned_team_keys)) {
                            foreach($assigned_team_keys as $key) {
                                $team_labels[] = isset($available_teams[$key]) ? $available_teams[$key] : ucfirst(str_replace('-', ' ', $key));
                            }
                        }
                        $team_display = !empty($team_labels) ? implode(', ', $team_labels) : 'N/A';

                        // Calculate totals and velocity from repeater data
                        $sprint_summary = $reports->get_sprint_performance_summary($sprint_id);
                        
                        ?>
                        <tr>
                            <td><a href="<?php echo esc_url(get_edit_post_link($sprint_id)); ?>"><?php the_title(); ?></a></td>
                            <td><?php echo esc_html($team_display); ?></td>
                            <td><?php echo esc_html($utilities->format_date($start_date)); ?></td>
                            <td><?php echo esc_html($utilities->format_date($end_date)); ?></td>
                            <td><?php echo esc_html($sprint_summary['estimated_hours'] ? $sprint_summary['estimated_hours'] : 'N/A'); ?></td>
                            <td><?php echo esc_html($sprint_summary['committed_points']); ?></td>
                            <td><?php echo esc_html($sprint_summary['delivered_points']); ?></td>
                            <td><?php echo esc_html($sprint_summary['delivered_velocity']); ?> pts/day</td>
                            <td><?php echo esc_html($sprint_summary['completion_percentage']); ?>%</td>
                        </tr>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <tr>
                        <td colspan="8"><?php esc_html_e('No sprints found matching your criteria.', 'delivery-manager-tracking-plugin'); ?></td>
                    </tr>
                    <?php
                endif;
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Charts -->
    <div class="dmtp-charts" style="margin-top: 30px; max-width: 800px;">
        <h2><?php esc_html_e('Charts', 'delivery-manager-tracking-plugin'); ?></h2>
        
        <?php
        // Check if a specific sprint is selected for burndown
        $selected_sprint_id = isset($_GET['sprint_id']) ? intval($_GET['sprint_id']) : 0; // Reuse sprint filter if available
        if (!$selected_sprint_id && $sprint_query->post_count > 0) {
            // If no sprint selected via filter, use the latest sprint from the table query
            $selected_sprint_id = $sprint_query->posts[0]->ID;
        }
        
        $chart_data = array();
        $sprint_title = '';

        if ($selected_sprint_id) {
            // Get Burndown (only ideal line now)
            $chart_data['burndown'] = $reports->get_sprint_burndown_data($selected_sprint_id);
            $sprint_title = get_the_title($selected_sprint_id);
        }
        
        // Get velocity trend data
        $chart_data['velocity'] = $reports->get_velocity_trend(5); // Get last 5 sprints
        $chart_data['sprintTitle'] = $sprint_title;
        
        // Localize chart data for JS
        wp_localize_script('dmtp-admin-scripts', 'dmtp_chart_data', $chart_data);
        ?>
        
        <div class="dmtp-chart-container" style="margin-bottom: 20px;">
            <h3><?php esc_html_e('Sprint Burndown', 'delivery-manager-tracking-plugin'); ?></h3>
            <?php if ($selected_sprint_id) : ?>
                 <p><?php printf(esc_html__('Showing burndown for: %s', 'delivery-manager-tracking-plugin'), esc_html($sprint_title)); ?>. <?php esc_html_e('Filter by date range and select a sprint above to see its burndown.', 'delivery-manager-tracking-plugin'); ?></p>
                <canvas id="dmtp-burndown-chart" width="400" height="200"></canvas>
            <?php else:
                 // Check if any sprints exist at all
                 $any_sprints = get_posts(array('post_type' => 'dmtp_sprint', 'posts_per_page' => 1, 'fields' => 'ids'));
                 if (empty($any_sprints)) {
                     echo '<p>' . esc_html__('No sprints created yet.', 'delivery-manager-tracking-plugin') . '</p>';
                 } else {
                     echo '<p>' . esc_html__('Select a sprint from the table above (or via filter) to view its burndown chart.', 'delivery-manager-tracking-plugin') . '</p>';
                 }
             ?>
            <?php endif; ?>
        </div>

        <div class="dmtp-chart-container">
            <h3><?php esc_html_e('Velocity Trend', 'delivery-manager-tracking-plugin'); ?></h3>
             <?php if (!empty($chart_data['velocity'])) : ?>
                <canvas id="dmtp-velocity-chart" width="400" height="200"></canvas>
            <?php else: ?>
                 <p><?php esc_html_e('Not enough completed sprint data to show velocity trend.', 'delivery-manager-tracking-plugin'); ?></p>
             <?php endif; ?>
        </div>
    </div>
    
    <!-- Export buttons -->
    <div class="dmtp-export">
        <h2><?php esc_html_e('Export Data', 'delivery-manager-tracking-plugin'); ?></h2>
        <button id="dmtp-export-all-sprints-json" class="button"><?php esc_html_e('Export All Sprints (JSON)', 'delivery-manager-tracking-plugin'); ?></button>
        <button id="dmtp-export-all-sprints-csv" class="button"><?php esc_html_e('Export All Sprints (CSV)', 'delivery-manager-tracking-plugin'); ?></button>
        <!-- Add buttons for exporting individual sprints if needed -->
    </div>
</div> 