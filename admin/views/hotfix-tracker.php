<?php
/**
 * View for the Hotfix Tracker admin page.
 *
 * @package DMTP
 */

// Get utilities
$utilities = new DMTP_Utilities();

// Get available developers
$developers = $utilities->get_all_developers();

// Get available sprints
$sprint_args = array(
    'post_type' => 'dmtp_sprint',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
);
$sprints = get_posts($sprint_args);

// Get selected filters
$selected_developer = isset($_GET['developer_id']) ? intval($_GET['developer_id']) : 0;
$selected_sprint = isset($_GET['sprint_id']) ? intval($_GET['sprint_id']) : 0;

?>
<div class="wrap">
    <h1><?php echo esc_html__('Hotfix Tracker', 'delivery-manager-tracking-plugin'); ?></h1>
    
    <p><?php echo esc_html__('View and filter hotfixes based on sprint or developer.', 'delivery-manager-tracking-plugin'); ?></p>
    
    <!-- Filters for sprint and developer -->
    <div class="dmtp-filters">
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
            
            <label for="developer_id"><?php esc_html_e('Filter by Developer:', 'delivery-manager-tracking-plugin'); ?></label>
            <select id="developer_id" name="developer_id">
                <option value="0"><?php esc_html_e('-- All Developers --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php foreach ($developers as $developer) : ?>
                    <option value="<?php echo esc_attr($developer['id']); ?>" <?php selected($selected_developer, $developer['id']); ?>>
                        <?php echo esc_html($developer['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="sprint_id"><?php esc_html_e('Filter by Sprint:', 'delivery-manager-tracking-plugin'); ?></label>
            <select id="sprint_id" name="sprint_id">
                <option value="0"><?php esc_html_e('-- All Sprints --', 'delivery-manager-tracking-plugin'); ?></option>
                <?php foreach ($sprints as $sprint) : ?>
                    <option value="<?php echo esc_attr($sprint->ID); ?>" <?php selected($selected_sprint, $sprint->ID); ?>>
                        <?php echo esc_html($sprint->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="submit" value="<?php esc_attr_e('Filter', 'delivery-manager-tracking-plugin'); ?>" class="button">
        </form>
    </div>
    
    <!-- Hotfix list display area -->
    <div class="dmtp-hotfix-list">
        <table class="wp-list-table widefat fixed striped hotfixes">
            <thead>
                <tr>
                    <th><?php esc_html_e('Hotfix Title', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Related Sprint', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Assigned To', 'delivery-manager-tracking-plugin'); ?></th>
                    <th><?php esc_html_e('Issue Description', 'delivery-manager-tracking-plugin'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Query hotfixes
                $hotfix_args = array(
                    'post_type' => 'dmtp_hotfix',
                    'posts_per_page' => 20, // Add pagination later if needed
                    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
                    'meta_query' => array('relation' => 'AND'),
                );
                
                // Apply filters
                if ($selected_sprint) {
                    $hotfix_args['meta_query'][] = array(
                        'key' => 'related_sprint',
                        'value' => $selected_sprint,
                        'compare' => '=',
                    );
                }
                if ($selected_developer) {
                    $hotfix_args['meta_query'][] = array(
                        'key' => 'assigned_user',
                        'value' => $selected_developer,
                        'compare' => '=',
                    );
                }
                
                $hotfix_query = new WP_Query($hotfix_args);
                
                if ($hotfix_query->have_posts()) :
                    while ($hotfix_query->have_posts()) : $hotfix_query->the_post(); 
                        $hotfix_id = get_the_ID();
                        $sprint_id = get_post_meta($hotfix_id, 'related_sprint', true);
                        $user_id = get_post_meta($hotfix_id, 'assigned_user', true);
                        $issue = get_post_meta($hotfix_id, 'issue_description', true);
                        
                        $sprint_title = $sprint_id ? get_the_title($sprint_id) : 'N/A';
                        $user_info = get_userdata($user_id);
                        $user_name = $user_info ? $user_info->display_name : 'N/A';
                        
                    ?>
                        <tr>
                            <td><a href="<?php echo esc_url(get_edit_post_link($hotfix_id)); ?>"><?php the_title(); ?></a></td>
                            <td><?php echo esc_html($sprint_title); ?></td>
                            <td><?php echo esc_html($user_name); ?></td>
                            <td><?php echo esc_html($issue); ?></td>
                        </tr>
                    <?php endwhile; 
                    // Add pagination links if needed
                    $total_pages = $hotfix_query->max_num_pages;
                    if ($total_pages > 1){
                        $current_page = max(1, get_query_var('paged'));
                        echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $current_page,
                            'total' => $total_pages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;'
                        )) . '</div></div>';
                    }
                    wp_reset_postdata(); 
                else : ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No hotfixes found matching your criteria.', 'delivery-manager-tracking-plugin'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div> 