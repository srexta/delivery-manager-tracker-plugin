<?php
/**
 * View for the Notes Section admin page.
 *
 * @package DMTP
 */

// Get available sprints
$sprint_args = array(
    'post_type' => 'dmtp_sprint',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
);
$sprints = get_posts($sprint_args);

// Get selected sprint
$selected_sprint = isset($_GET['sprint_id']) ? intval($_GET['sprint_id']) : 0;

?>
<div class="wrap">
    <h1><?php echo esc_html__('Sprint Notes', 'delivery-manager-tracking-plugin'); ?></h1>
    
    <p><?php echo esc_html__('View planning and retrospective notes for a selected sprint.', 'delivery-manager-tracking-plugin'); ?></p>
    
    <!-- Filters for sprint -->
    <div class="dmtp-filters">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            
            <label for="sprint_id"><?php esc_html_e('Select Sprint:', 'delivery-manager-tracking-plugin'); ?></label>
            <select id="sprint_id" name="sprint_id">
                <option value="0"><?php esc_html_e('-- Select a Sprint --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php foreach ($sprints as $sprint) : ?>
                    <option value="<?php echo esc_attr($sprint->ID); ?>" <?php selected($selected_sprint, $sprint->ID); ?>>
                        <?php echo esc_html($sprint->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="submit" value="<?php esc_attr_e('View Notes', 'delivery-manager-tracking-plugin'); ?>" class="button">
        </form>
    </div>
    
    <!-- Notes display area -->
    <div class="dmtp-notes-display">
        <?php if ($selected_sprint) : 
            $sprint_post = get_post($selected_sprint);
            $planning_note = get_post_meta($selected_sprint, 'planning_note', true);
            $retrospective_note = get_post_meta($selected_sprint, 'retrospective_note', true);
        ?>
            <h2><?php printf(esc_html__('Notes for Sprint: %s', 'delivery-manager-tracking-plugin'), esc_html($sprint_post->post_title)); ?></h2>
            
            <div class="dmtp-note-section">
                <h3><?php esc_html_e('Planning Notes', 'delivery-manager-tracking-plugin'); ?></h3>
                <div class="dmtp-note-content">
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
                <div class="dmtp-note-content">
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
            <p><?php esc_html_e('Select a sprint from the dropdown above to view its notes.', 'delivery-manager-tracking-plugin'); ?></p>
        <?php endif; ?>
    </div>
</div> 