<?php
/**
 * View for the Individual Tracker admin page.
 *
 * @package DMTP
 */

// Get utilities and reports
$utilities = new DMTP_Utilities();
$reports = new DMTP_Reports();

// Get available teams from Settings Page
$teams = array();
if (function_exists('get_field') && have_rows('dmtp_teams', 'option')) {
    while (have_rows('dmtp_teams', 'option')) : the_row();
        $team_name = get_sub_field('team_name');
        if ($team_name) {
            $teams[] = $team_name;
        }
    endwhile;
    sort($teams);
    reset_rows(); // Important after looping options repeater
}

// Get available sprints
$sprint_args = array(
    'post_type' => 'dmtp_sprint',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
);
$sprints = get_posts($sprint_args);

// Get selected filters (use name for developer)
$selected_team_name = isset($_GET['team_name']) ? sanitize_text_field($_GET['team_name']) : '';
$selected_developer_name = isset($_GET['developer_name']) ? sanitize_text_field($_GET['developer_name']) : '';
$selected_sprint = isset($_GET['sprint_id']) ? intval($_GET['sprint_id']) : 0;

?>
<div class="wrap">
    <h1><?php echo esc_html__('Individual Tracker', 'delivery-manager-tracking-plugin'); ?></h1>
    
    <p><?php echo esc_html__('View stored performance data for a developer within a specific sprint.', 'delivery-manager-tracking-plugin'); ?></p>
    
    <!-- Filters for sprint and developer -->
    <div class="dmtp-filters">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            
            <label for="team_name"><?php esc_html_e('Select Team:', 'delivery-manager-tracking-plugin'); ?></label>
            <select id="dmtp_team_name" name="team_name">
                <option value=""><?php esc_html_e('-- Select Team --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php foreach ($teams as $team) : ?>
                    <option value="<?php echo esc_attr($team); ?>" <?php selected($selected_team_name, $team); ?>>
                        <?php echo esc_html($team); ?>
                    </option>
                <?php endforeach; ?>
                 <?php if (empty($teams)): ?>
                    <option value="" disabled><?php esc_html_e('No teams defined in Settings', 'delivery-manager-tracking-plugin'); ?></option>
                 <?php endif; ?>
            </select>
            
            <label for="developer_name"><?php esc_html_e('Select Developer:', 'delivery-manager-tracking-plugin'); ?></label>
            <select id="dmtp_developer_name" name="developer_name" disabled>
                <option value=""><?php esc_html_e('-- Select Team First --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php 
                // Pre-select if filters are set (e.g., on page load after submit)
                if ($selected_team_name && $selected_developer_name) {
                    // We will still need JS to load the *other* developers from the selected team,
                    // but we add the currently selected one here so it shows correctly on reload.
                    echo '<option value="' . esc_attr($selected_developer_name) . '" selected>' . esc_html($selected_developer_name) . '</option>';
                } 
                ?>
            </select>
            
            <label for="sprint_id"><?php esc_html_e('Select Sprint:', 'delivery-manager-tracking-plugin'); ?></label>
            <select id="sprint_id" name="sprint_id">
                <option value="0"><?php esc_html_e('-- Select Sprint --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php foreach ($sprints as $sprint) : ?>
                    <option value="<?php echo esc_attr($sprint->ID); ?>" <?php selected($selected_sprint, $sprint->ID); ?>>
                        <?php echo esc_html($sprint->post_title); ?>
                    </option>
                <?php endforeach; ?>
                 <?php if (empty($sprints)): ?>
                    <option value="0" disabled><?php esc_html_e('No sprints found', 'delivery-manager-tracking-plugin'); ?></option>
                 <?php endif; ?>
            </select>
            
            <input type="submit" value="<?php esc_attr_e('View Performance', 'delivery-manager-tracking-plugin'); ?>" class="button">
        </form>
    </div>
    
    <!-- Individual data display area -->
    <div class="dmtp-individual-data">
        <?php if (!empty($selected_developer_name) && $selected_sprint) : 
            
            // Get the performance data row for this developer in this sprint
            $performance_data = $reports->get_developer_sprint_performance($selected_developer_name, $selected_sprint);
            
            if ($performance_data) :
        ?>
            <h2>
                <?php 
                printf(
                    esc_html__('Performance for %1$s in Sprint: %2$s', 'delivery-manager-tracking-plugin'), 
                    esc_html($selected_developer_name), 
                    esc_html(get_the_title($selected_sprint))
                );
                ?>
            </h2>
            
            <div class="dmtp-performance-summary" style="margin-bottom: 20px;">
                <h3><?php esc_html_e('Performance Summary', 'delivery-manager-tracking-plugin'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Committed Story Points', 'delivery-manager-tracking-plugin'); ?></th>
                        <td><?php echo esc_html($performance_data['committed_sp'] ?? 'N/A'); ?></td>
                    </tr>
                     <tr>
                        <th><?php esc_html_e('Delivered Story Points', 'delivery-manager-tracking-plugin'); ?></th>
                        <td><?php echo esc_html($performance_data['delivered_sp'] ?? 'N/A'); ?></td>
                    </tr>
                     <tr>
                        <th><?php esc_html_e('Estimated Hours', 'delivery-manager-tracking-plugin'); ?></th>
                        <td><?php echo esc_html($performance_data['estimated_hours'] ?? 'N/A'); ?></td>
                    </tr>
                     <tr>
                        <th><?php esc_html_e('Hotfixes Worked On', 'delivery-manager-tracking-plugin'); ?></th>
                        <td><?php echo esc_html($performance_data['hotfixes'] ?? 'N/A'); ?></td>
                    </tr>
                     <tr>
                        <th><?php esc_html_e('Absent Days', 'delivery-manager-tracking-plugin'); ?></th>
                        <td><?php echo esc_html($performance_data['absent_days'] ?? 'N/A'); ?></td>
                    </tr>
                     <tr>
                        <th><?php esc_html_e('Overall Rating (1-5)', 'delivery-manager-tracking-plugin'); ?></th>
                        <td><?php echo esc_html($performance_data['rating'] ?? 'N/A'); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="dmtp-individual-retro">
                 <h3><?php esc_html_e('Individual Retrospective Notes', 'delivery-manager-tracking-plugin'); ?></h3>
                 <div class="dmtp-retro-content" style="border: 1px solid #ccc; padding: 10px; background: #fff;">
                    <?php 
                    if (!empty($performance_data['notes'])) {
                        echo wp_kses_post($performance_data['notes']); 
                    } else {
                        echo '<p>' . esc_html__('No retrospective notes entered for this member in this sprint.', 'delivery-manager-tracking-plugin') . '</p>';
                    }
                    ?>
                 </div>
            </div>
            
            <!-- Export buttons (using developer name now) -->
            <div class="dmtp-export" style="margin-top: 20px;">
                <button data-developer-name="<?php echo esc_attr($selected_developer_name); ?>" data-format="csv" class="button dmtp-export-developer"><?php esc_html_e('Export Developer Performance History (CSV)', 'delivery-manager-tracking-plugin'); ?></button>
            </div>

            <?php else : // Performance data not found ?>
                 <p>
                    <?php 
                    printf(
                        /* translators: 1: Developer Name, 2: Sprint Title */
                        'No performance data found for %1$s in sprint %2$s. Please ensure the name matches the entry in the sprint performance tracking.', 
                        '<strong>' . esc_html($selected_developer_name) . '</strong>', 
                        '<em>' . esc_html(get_the_title($selected_sprint)) . '</em>'
                    );
                    ?>
                 </p>
            <?php endif; // end if performance_data ?>

        <?php elseif (!empty($selected_developer_name) || $selected_sprint) : ?>
            <p><?php esc_html_e('Please select both a developer and a sprint to view performance information.', 'delivery-manager-tracking-plugin'); ?></p>
        <?php else : ?>
            <p><?php esc_html_e('Select a developer and a sprint from the filters above to view their performance details.', 'delivery-manager-tracking-plugin'); ?></p>
        <?php endif; ?>
    </div>
</div> 