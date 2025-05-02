<?php
/**
 * View for the Notes Section admin page.
 *
 * @package DMTP
 */

// --- Get available teams from ACF Options ---
$available_teams = array();
if (function_exists('have_rows') && have_rows('dmtp_teams', 'option')) {
    while (have_rows('dmtp_teams', 'option')) : the_row();
        $team_name = get_sub_field('team_name');
        if ($team_name) {
            $available_teams[esc_attr($team_name)] = esc_html($team_name);
        }
    endwhile;
    reset_rows();
}

// --- Get selected filters ---
$selected_team_name = isset($_GET['notes_team_filter']) ? sanitize_text_field($_GET['notes_team_filter']) : '';
$selected_sprint_id = isset($_GET['sprint_id']) ? intval($_GET['sprint_id']) : 0;

?>
<div class="wrap">
    <h1><?php echo esc_html__('Sprint Notes', 'delivery-manager-tracking-plugin'); ?></h1>
    
    <p><?php echo esc_html__('View planning and retrospective notes for a selected sprint.', 'delivery-manager-tracking-plugin'); ?></p>
    
    <!-- Filters for team and sprint -->
    <div class="dmtp-filters">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />

            <!-- Team Dropdown -->
            <label for="notes_team_filter" style="padding-right: 5px;"> <?php esc_html_e('Select Team:', 'delivery-manager-tracking-plugin'); ?> </label>
            <select id="notes_team_filter" name="notes_team_filter" style="margin-right: 15px;">
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

            <!-- Sprint Dropdown (AJAX) -->
            <label for="sprint_id" style="padding-right: 5px;"> <?php esc_html_e('Select Sprint:', 'delivery-manager-tracking-plugin'); ?> </label>
            <select id="sprint_id" name="sprint_id" disabled>
                <?php if ($selected_team_name && $selected_sprint_id):
                    $selected_sprint_title = get_the_title($selected_sprint_id);
                    if ($selected_sprint_title) {
                        echo '<option value="' . esc_attr($selected_sprint_id) . '" selected>' . esc_html($selected_sprint_title) . '</option>';
                    } else {
                        echo '<option value="">' . esc_html__('-- Select Team First --', 'delivery-manager-tracking-plugin') . '</option>';
                    }
                ?>
                <?php else: ?>
                    <option value=""><?php esc_html_e('-- Select Team First --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php endif; ?>
            </select>

            <input type="submit" value="<?php esc_attr_e('View Notes', 'delivery-manager-tracking-plugin'); ?>" class="button">
        </form>
    </div>

    <!-- Notes display area -->
    <div class="dmtp-notes-display" id="dmtp-notes-display">
        <?php if ($selected_sprint_id) : 
            $sprint_post = get_post($selected_sprint_id);
            $planning_note = get_post_meta($selected_sprint_id, 'planning_note', true);
            $retrospective_note = get_post_meta($selected_sprint_id, 'retrospective_note', true);
        ?>
            <button class="dmtp-copy-notes-btn" type="button">Copy Notes</button>
            <span id="dmtp-copy-notes-msg" style="display:none;position:absolute;top:22px;right:140px;color:#2176d2;font-weight:600;">Copied!</span>
            <h2><?php printf(esc_html__('Notes for Sprint: %s', 'delivery-manager-tracking-plugin'), esc_html($sprint_post->post_title)); ?></h2>
            
            <div class="dmtp-note-section">
                <h3><?php esc_html_e('Planning Notes', 'delivery-manager-tracking-plugin'); ?></h3>
                <button class="dmtp-copy-planning-btn dmtp-copy-notes-btn" type="button" style="float:right;margin-top:-6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none" style="vertical-align:middle;margin-right:8px;position:relative;top:-1px;"><rect x="6" y="6" width="10" height="12" rx="2" fill="#fff" stroke="#2176d2" stroke-width="1.5"/><rect x="4" y="2" width="10" height="12" rx="2" fill="#e6f0fa" stroke="#2176d2" stroke-width="1.5"/></svg>
                    Copy Planning
                </button>
                <span class="dmtp-copy-planning-msg" style="display:none;margin-left:10px;color:#2176d2;font-weight:600;">Copied!</span>
                <div class="dmtp-note-content" id="dmtp-planning-note-content">
                    <?php 
                    if (!empty($planning_note)) {
                        echo wp_kses_post($planning_note);
                    } else {
                        echo '<p>' . esc_html__('No planning notes entered for this sprint.', 'delivery-manager-tracking-plugin') . '</p>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="dmtp-note-section">
                <h3><?php esc_html_e('Retrospective Notes', 'delivery-manager-tracking-plugin'); ?></h3>
                <button class="dmtp-copy-retro-btn dmtp-copy-notes-btn" type="button" style="float:right;margin-top:-6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none" style="vertical-align:middle;margin-right:8px;position:relative;top:-1px;"><rect x="6" y="6" width="10" height="12" rx="2" fill="#fff" stroke="#2176d2" stroke-width="1.5"/><rect x="4" y="2" width="10" height="12" rx="2" fill="#e6f0fa" stroke="#2176d2" stroke-width="1.5"/></svg>
                    Copy Retrospective
                </button>
                <span class="dmtp-copy-retro-msg" style="display:none;margin-left:10px;color:#2176d2;font-weight:600;">Copied!</span>
                <div class="dmtp-note-content" id="dmtp-retro-note-content">
                    <?php 
                    if (!empty($retrospective_note)) {
                        echo wp_kses_post($retrospective_note);
                    } else {
                        echo '<p>' . esc_html__('No retrospective notes entered for this sprint.', 'delivery-manager-tracking-plugin') . '</p>';
                    }
                    ?>
                </div>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('Select a team and sprint from the dropdowns above to view its notes.', 'delivery-manager-tracking-plugin'); ?></p>
        <?php endif; ?>
    </div>
</div> 