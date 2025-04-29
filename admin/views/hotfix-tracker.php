<?php
/**
 * View for the Hotfix Tracker admin page.
 * Displays hotfixes logged within individual Sprints.
 *
 * @package DMTP
 */

// --- Get available teams from ACF Options ---
$available_teams = array();
if (function_exists('have_rows') && have_rows('dmtp_teams', 'option')) {
    while (have_rows('dmtp_teams', 'option')) : the_row();
        $team_name = get_sub_field('team_name');
        if ($team_name) {
            // Use the team name itself as both value and label for simplicity
            $available_teams[esc_attr($team_name)] = esc_html($team_name);
        }
    endwhile;
    // No need to sort here unless desired, can be sorted in JS if needed
    reset_rows(); // Important after looping options repeater
}

// --- Get selected filters ---
$selected_team_name = isset($_GET['hotfix_team_filter']) ? sanitize_text_field($_GET['hotfix_team_filter']) : '';
$selected_sprint_id = isset($_GET['sprint_id']) ? intval($_GET['sprint_id']) : 0;

// --- Get Sprints (We'll fetch these via AJAX based on team selection, but might need the selected one if form submitted) ---
// $all_sprints = get_posts($sprint_args); // Removed: Will be populated via AJAX

?>
<div class="wrap">
    <h1><?php echo esc_html__( 'Sprint Hotfix Tracker', 'delivery-manager-tracking-plugin' ); ?></h1>

    <p><?php echo esc_html__( 'Select a team and then a sprint to view the hotfixes logged during that sprint.', 'delivery-manager-tracking-plugin' ); ?></p>

     <!-- Filters -->
    <div class="dmtp-filters">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />

            <!-- Team Filter Dropdown -->
            <label for="hotfix_team_filter" style="padding-right: 5px;"><?php esc_html_e('Select Team:', 'delivery-manager-tracking-plugin'); ?></label>
            <select id="hotfix_team_filter" name="hotfix_team_filter" style="margin-right: 15px;">
                <option value=""><?php esc_html_e('-- Select Team --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php foreach ($available_teams as $value => $label) : ?>
                    <option value="<?php echo $value; ?>" <?php selected($selected_team_name, $value); ?>>
                        <?php echo $label; ?>
                    </option>
                <?php endforeach; ?>
                <?php if (empty($available_teams)): ?>
                    <option value="" disabled><?php esc_html_e('No teams defined in Settings', 'delivery-manager-tracking-plugin'); ?></option>
                <?php endif; ?>
            </select>

            <!-- Sprint Filter Dropdown (to be populated by AJAX) -->
            <label for="sprint_id" style="padding-right: 5px;"><?php esc_html_e('Select Sprint:', 'delivery-manager-tracking-plugin'); ?></label>
            <select id="sprint_id" name="sprint_id" disabled>
                <?php if ($selected_team_name && $selected_sprint_id):
                    // If form was submitted, pre-populate the selected sprint
                    // Note: AJAX will overwrite this on team change, but keeps state on page load
                    $selected_sprint_title = get_the_title($selected_sprint_id);
                    if ($selected_sprint_title) {
                        echo '<option value="' . esc_attr($selected_sprint_id) . '" selected>' . esc_html($selected_sprint_title) . '</option>';
                    } else {
                         echo '<option value="">' . esc_html__('-- Select Team First --', 'delivery-manager-tracking-plugin') . '</option>'; // Fallback
                    }
                ?>
                <?php else: ?>
                 <option value=""><?php esc_html_e('-- Select Team First --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php endif; ?>
                 <?php /* Options will be loaded via AJAX based on team selection */ ?>
            </select>

            <input type="submit" value="<?php esc_attr_e('View Hotfixes', 'delivery-manager-tracking-plugin'); ?>" class="button">
        </form>
    </div>

    <?php
    // Display hotfixes only if a sprint is selected (remains the same logic)
    if ($selected_sprint_id) :
        
        $sprint_title = get_the_title($selected_sprint_id);
        echo '<h2>' . sprintf(esc_html__('Hotfixes for Sprint: %s', 'delivery-manager-tracking-plugin'), esc_html($sprint_title)) . '</h2>';
        
        // Check if ACF function exists
        if ( ! function_exists( 'have_rows' ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Advanced Custom Fields plugin is required but not active.', 'delivery-manager-tracking-plugin' ) . '</p></div>';
            return; 
        }
        
        // Check if the selected sprint has team hotfix logs
        if ( have_rows( 'sprint_team_hotfixes', $selected_sprint_id ) ) :
            
            while ( have_rows( 'sprint_team_hotfixes', $selected_sprint_id ) ) : the_row();
                ?>
                <div class="dmtp-sprint-team-hotfix-section" style="margin-bottom: 30px;">
                    
                    <?php
                    // Check if this team entry has any hotfixes logged
                    if ( have_rows( 'team_hotfix_list' ) ) :
                    ?>
                        <!-- Add Search and Export controls -->
                        <div class="dmtp-table-controls" style="margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <input type="search" id="hotfix-search-input-<?php echo esc_attr(sanitize_title($team_name ? $team_name : 'unknown')); ?>" placeholder="<?php esc_attr_e('Search hotfixes...', 'delivery-manager-tracking-plugin'); ?>" style="max-width: 300px;">
                            <button class="button export-hotfix-csv" data-table-id="hotfix-list-table-<?php echo esc_attr(sanitize_title($team_name ? $team_name : 'unknown')); ?>" data-sprint-title="<?php echo esc_attr($sprint_title); ?>" data-team-name="<?php echo esc_attr($team_name); ?>">
                                <?php esc_html_e('Export Team CSV', 'delivery-manager-tracking-plugin'); ?>
                            </button>
                        </div>
                        
                        <table id="hotfix-list-table-<?php echo esc_attr(sanitize_title($team_name ? $team_name : 'unknown')); ?>" class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 30%;"><?php esc_html_e( 'Hotfix Title', 'delivery-manager-tracking-plugin' ); ?></th>
                                    <th style="width: 15%;"><?php esc_html_e( 'Estimation', 'delivery-manager-tracking-plugin' ); ?></th>
                                    <th style="width: 15%;"><?php esc_html_e( 'Critical/Response Met?', 'delivery-manager-tracking-plugin' ); ?></th>
                                    <th style="width: 20%;"><?php esc_html_e( 'GitHub', 'delivery-manager-tracking-plugin' ); ?></th>
                                    <th style="width: 20%;"><?php esc_html_e( 'RCA', 'delivery-manager-tracking-plugin' ); ?></th>
                                </tr>
                            </thead>
                            <tbody id="hotfix-table-body-<?php echo esc_attr(sanitize_title($team_name ? $team_name : 'unknown')); ?>">
                            <?php
                            // Loop through hotfixes for this team entry
                            while ( have_rows( 'team_hotfix_list' ) ) : the_row();
                                $title = get_sub_field( 'nested_hotfix_title' );
                                $estimation = get_sub_field( 'hotfix_estimation_time' );
                                $critical = get_sub_field( 'hotfix_critical_response' ); 
                                $github_link = get_sub_field( 'hotfix_github_link' );
                                $rca_link = get_sub_field( 'hotfix_rca_link' );
                            ?>
                                <tr>
                                    <td style="word-break: break-word; white-space: normal;display: block;"><?php echo esc_html( $title ); ?></td>
                                    <td><?php echo esc_html( $estimation ? $estimation : '-' ); ?></td>
                                    <td><?php echo $critical ? esc_html__( 'Yes', 'delivery-manager-tracking-plugin' ) : esc_html__( 'No', 'delivery-manager-tracking-plugin' ); ?></td>
                                    <td>
                                        <?php if ( $github_link ) : ?>
                                            <a href="<?php echo esc_url( $github_link ); ?>" target="_blank" title="<?php echo esc_attr($github_link); ?>">Link</a>
                                        <?php else : ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( $rca_link ) : ?>
                                            <a href="<?php echo esc_url( $rca_link ); ?>" target="_blank" title="<?php echo esc_attr($rca_link); ?>">Link</a>
                                        <?php else : ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; // End hotfix_list loop ?>
                            </tbody>
                        </table>
                    <?php else : // No hotfixes within this team entry ?>
                        <p><?php esc_html_e( 'No specific hotfixes logged for this team entry in this sprint.', 'delivery-manager-tracking-plugin' ); ?></p>
                    <?php endif; // End check for team_hotfix_list ?>
                </div>
                <?php
            endwhile; // End sprint_team_hotfixes loop
            
            reset_rows(); // Important?
            
        else : // No team hotfix logs for this sprint
            echo '<div class="notice notice-info"><p>' . esc_html__( 'No team hotfix logs have been added to this sprint yet, or the selected team did not participate.', 'delivery-manager-tracking-plugin' ) . '</p></div>'; // Adjusted message slightly
        endif; // End check for sprint_team_hotfixes
    
    else : // No sprint selected
?>
         <p><?php esc_html_e('Please select a team and then a sprint from the dropdowns above to view associated hotfixes.', 'delivery-manager-tracking-plugin'); ?></p> <!-- Adjusted message -->
<?php 
    endif; // End check for selected_sprint_id 
?>
</div> 