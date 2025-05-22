<?php
/**
 * View for the GitHub Status admin page.
 * Displays GitHub data for private repos.
 *
 * @package DMTP
 */

if ( ! defined( 'DMTP_GITHUB_TOKEN' ) ) {
    echo '<div class="notice notice-error"><p>GitHub token is not defined. Please set DMTP_GITHUB_TOKEN in wp-config.php.</p></div>';
    return;
}

$repos = array(
    'wptravelengine'      => 'Codewing-Solutions/wptravelengine',
    'trip-cart'           => 'Codewing-Solutions/trip-cart',
    'delicious-recipes'   => 'Codewing-Solutions/delicious-recipes',
);

function dmtp_github_api_get($endpoint, $headers = array()) {
    $url = "https://api.github.com/repos/$endpoint";
    $default_headers = array(
        'Authorization' => 'token ' . DMTP_GITHUB_TOKEN,
        'User-Agent'    => 'WordPress-DMTP-Plugin',
        'Accept'        => 'application/vnd.github.v3+json',
    );
    $args = array(
        'headers' => array_merge($default_headers, $headers),
        'timeout' => 15,
    );
    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        return array('error' => $response->get_error_message());
    }
    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        return array('error' => 'GitHub API error: ' . $code);
    }
    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

function dmtp_github_graphql_curl($query, $variables = array()) {
    $url = 'https://api.github.com/graphql';
    $token = DMTP_GITHUB_TOKEN;
    $data = array('query' => $query);
    if (!empty($variables)) {
        $data['variables'] = $variables;
    }
    $payload = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token,
        'User-Agent: WordPress-DMTP-Plugin',
        'Content-Type: application/json',
        'Accept: application/json'
    ));
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return array('error' => $error);
    }
    if ($httpcode !== 200) {
        return array('error' => 'GitHub GraphQL API error: ' . $httpcode . ' - ' . $response);
    }
    return json_decode($response, true);
}

function dmtp_github_api_get_labels($repo) {
    $labels = dmtp_github_api_get("$repo/labels?per_page=100");
    return is_array($labels) ? $labels : [];
}
function dmtp_github_api_get_assignees($repo) {
    $assignees = dmtp_github_api_get("$repo/assignees?per_page=100");
    return is_array($assignees) ? $assignees : [];
}

// Classic projects (REST API)
function dmtp_github_api_get_classic_projects($repo) {
    return dmtp_github_api_get("$repo/projects", array('Accept' => 'application/vnd.github.inertia-preview+json'));
}
function dmtp_github_api_get_project_columns($project_id) {
    $url = "https://api.github.com/projects/$project_id/columns";
    $args = array(
        'headers' => array(
            'Authorization' => 'token ' . DMTP_GITHUB_TOKEN,
            'User-Agent'    => 'WordPress-DMTP-Plugin',
            'Accept'        => 'application/vnd.github.inertia-preview+json',
        ),
        'timeout' => 15,
    );
    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}
function dmtp_github_api_get_column_cards($column_id) {
    $url = "https://api.github.com/projects/columns/$column_id/cards";
    $args = array(
        'headers' => array(
            'Authorization' => 'token ' . DMTP_GITHUB_TOKEN,
            'User-Agent'    => 'WordPress-DMTP-Plugin',
            'Accept'        => 'application/vnd.github.inertia-preview+json',
        ),
        'timeout' => 15,
    );
    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

$selected_repo_label = $_GET['dmtp_github_repo'] ?? 'wptravelengine';
$selected_repo = $repos[$selected_repo_label];
$labels_list = dmtp_github_api_get_labels($selected_repo);
$assignees_list = dmtp_github_api_get_assignees($selected_repo);

// Fetch ProjectV2 (next gen) projects for the selected repo
$project_query = <<<GRAPHQL
query($owner: String!, $name: String!) {
  repository(owner: $owner, name: $name) {
    projectsV2(first: 20) {
      nodes {
        id
        title
        number
        url
      }
    }
  }
}
GRAPHQL;
$owner = explode('/', $selected_repo)[0];
$name = explode('/', $selected_repo)[1];
$project_result = dmtp_github_graphql_curl($project_query, array('owner' => $owner, 'name' => $name));
$projects = $project_result['data']['repository']['projectsV2']['nodes'] ?? [];
$selected_project = $_GET['dmtp_github_project'] ?? '';

// Classic projects
$classics = dmtp_github_api_get_classic_projects($selected_repo);
$selected_classic = $_GET['dmtp_github_classic'] ?? '';

?>
<div class="wrap">
    <h1><?php esc_html_e('GitHub Status', 'delivery-manager-tracking-plugin'); ?></h1>
    <form method="get" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center;">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <label for="dmtp_github_repo"><?php esc_html_e('Select Team:', 'delivery-manager-tracking-plugin'); ?></label>
        <select name="dmtp_github_repo" id="dmtp_github_repo" onchange="this.form.submit()">
            <?php foreach ($repos as $label => $repo) : ?>
                <option value="<?php echo esc_attr($label); ?>" <?php selected($selected_repo_label, $label); ?>>
                    <?php echo esc_html(ucwords(str_replace('-', ' ', $label))); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="dmtp_github_project"><?php esc_html_e('Select ProjectV2:', 'delivery-manager-tracking-plugin'); ?></label>
        <select name="dmtp_github_project" id="dmtp_github_project" onchange="this.form.submit()">
            <option value=""><?php esc_html_e('-- All ProjectV2 --', 'delivery-manager-tracking-plugin'); ?></option>
            <?php foreach ($projects as $project) : ?>
                <option value="<?php echo esc_attr($project['number']); ?>" <?php selected($selected_project, $project['number']); ?>>
                    <?php echo esc_html($project['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="dmtp_github_classic"><?php esc_html_e('Select Classic Project:', 'delivery-manager-tracking-plugin'); ?></label>
        <select name="dmtp_github_classic" id="dmtp_github_classic" onchange="this.form.submit()">
            <option value=""><?php esc_html_e('-- All Classic Projects --', 'delivery-manager-tracking-plugin'); ?></option>
            <?php if (is_array($classics)) foreach ($classics as $classic) : ?>
                <option value="<?php echo esc_attr($classic['id']); ?>" <?php selected($selected_classic, $classic['id']); ?>>
                    <?php echo esc_html($classic['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="dmtp_github_state"><?php esc_html_e('Issue State:', 'delivery-manager-tracking-plugin'); ?></label>
        <select name="dmtp_github_state" id="dmtp_github_state" onchange="this.form.submit()">
            <option value="all" <?php selected($_GET['dmtp_github_state'] ?? 'all', 'all'); ?>>All</option>
            <option value="open" <?php selected($_GET['dmtp_github_state'] ?? '', 'open'); ?>>Open</option>
            <option value="closed" <?php selected($_GET['dmtp_github_state'] ?? '', 'closed'); ?>>Closed</option>
        </select>
        <label for="dmtp_github_label"><?php esc_html_e('Label:', 'delivery-manager-tracking-plugin'); ?></label>
        <select name="dmtp_github_label" id="dmtp_github_label" onchange="this.form.submit()">
            <option value=""><?php esc_html_e('-- Any --', 'delivery-manager-tracking-plugin'); ?></option>
            <?php foreach ($labels_list as $label) : ?>
                <option value="<?php echo esc_attr($label['name']); ?>" <?php selected($_GET['dmtp_github_label'] ?? '', $label['name']); ?>>
                    <?php echo esc_html($label['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="dmtp_github_assignee"><?php esc_html_e('Assignee:', 'delivery-manager-tracking-plugin'); ?></label>
        <select name="dmtp_github_assignee" id="dmtp_github_assignee" onchange="this.form.submit()">
            <option value=""><?php esc_html_e('-- Anyone --', 'delivery-manager-tracking-plugin'); ?></option>
            <?php foreach ($assignees_list as $assignee) : ?>
                <option value="<?php echo esc_attr($assignee['login']); ?>" <?php selected($_GET['dmtp_github_assignee'] ?? '', $assignee['login']); ?>>
                    <?php echo esc_html($assignee['login']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="dmtp_github_sort"><?php esc_html_e('Sort:', 'delivery-manager-tracking-plugin'); ?></label>
        <select name="dmtp_github_sort" id="dmtp_github_sort" onchange="this.form.submit()">
            <option value="created" <?php selected($_GET['dmtp_github_sort'] ?? 'created', 'created'); ?>>Newest</option>
            <option value="updated" <?php selected($_GET['dmtp_github_sort'] ?? '', 'updated'); ?>>Recently Updated</option>
            <option value="comments" <?php selected($_GET['dmtp_github_sort'] ?? '', 'comments'); ?>>Most Commented</option>
        </select>
    </form>
    <?php
    // Only show data for the selected repo
    if (!empty($selected_project)) {
        // Fetch issues in the selected ProjectV2 using GraphQL
        $issues_query = <<<GRAPHQL
query($owner: String!, $name: String!, $number: Int!) {
  repository(owner: $owner, name: $name) {
    projectV2(number: $number) {
      items(first: 50) {
        nodes {
          content {
            ... on Issue {
              id
              number
              title
              url
              state
              createdAt
            }
          }
        }
      }
    }
  }
}
GRAPHQL;
        $issues_result = dmtp_github_graphql_curl($issues_query, array('owner' => $owner, 'name' => $name, 'number' => (int)$selected_project));
        $project_issues = $issues_result['data']['repository']['projectV2']['items']['nodes'] ?? [];
        echo '<h2>' . esc_html__('Issues in ProjectV2', 'delivery-manager-tracking-plugin') . '</h2>';
        if (empty($project_issues)) {
            echo '<p>No issues found in this project.</p>';
        } else {
            echo '<table class="widefat"><thead><tr><th>Title</th><th>Status</th><th>Created</th></tr></thead><tbody>';
            foreach ($project_issues as $item) {
                if (!isset($item['content']['title'])) continue;
                echo '<tr>';
                echo '<td><a href="' . esc_url($item['content']['url']) . '" target="_blank">' . esc_html($item['content']['title']) . '</a></td>';
                echo '<td>' . esc_html($item['content']['state']) . '</td>';
                echo '<td>' . esc_html(date('Y-m-d', strtotime($item['content']['createdAt']))) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    } elseif (!empty($selected_classic)) {
        // Fetch issues in the selected classic project
        $columns = dmtp_github_api_get_project_columns($selected_classic);
        $issue_urls = array();
        foreach ($columns as $column) {
            $cards = dmtp_github_api_get_column_cards($column['id']);
            foreach ($cards as $card) {
                if (!empty($card['content_url']) && strpos($card['content_url'], '/issues/') !== false) {
                    $issue_urls[] = $card['content_url'];
                }
            }
        }
        $issues = array();
        foreach ($issue_urls as $url) {
            $args = array(
                'headers' => array(
                    'Authorization' => 'token ' . DMTP_GITHUB_TOKEN,
                    'User-Agent'    => 'WordPress-DMTP-Plugin',
                    'Accept'        => 'application/vnd.github.v3+json',
                ),
                'timeout' => 15,
            );
            $response = wp_remote_get($url, $args);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $issues[] = json_decode($body, true);
            }
        }
        echo '<h2>' . esc_html__('Issues in Classic Project', 'delivery-manager-tracking-plugin') . '</h2>';
        if (empty($issues)) {
            echo '<p>No issues found in this classic project.</p>';
        } else {
            echo '<table class="widefat"><thead><tr><th>Title</th><th>Status</th><th>Created</th></tr></thead><tbody>';
            foreach ($issues as $issue) {
                if (is_array($issue) && isset($issue['html_url'], $issue['title'], $issue['state'], $issue['created_at'])) {
                    echo '<tr>';
                    echo '<td><a href="' . esc_url($issue['html_url']) . '" target="_blank">' . esc_html($issue['title']) . '</a></td>';
                    echo '<td>' . esc_html($issue['state']) . '</td>';
                    echo '<td>' . esc_html(date('Y-m-d', strtotime($issue['created_at']))) . '</td>';
                    echo '</tr>';
                } else {
                    echo '<tr><td colspan="3"><em>Invalid issue data</em></td></tr>';
                }
            }
            echo '</tbody></table>';
        }
    } else {
        // Fallback to milestone/label/assignee filters as before
        $milestone_param = '';
        $selected_milestone = $_GET['dmtp_github_milestone'] ?? '';
        if (!empty($selected_milestone)) {
            $milestone_param = '&milestone=' . intval($selected_milestone);
        }
        $state_param = '&state=' . urlencode($_GET['dmtp_github_state'] ?? 'all');
        $label_param = !empty($_GET['dmtp_github_label']) ? '&labels=' . urlencode($_GET['dmtp_github_label']) : '';
        $assignee_param = !empty($_GET['dmtp_github_assignee']) ? '&assignee=' . urlencode($_GET['dmtp_github_assignee']) : '';
        $sort_param = '&sort=' . urlencode($_GET['dmtp_github_sort'] ?? 'created');
        echo '<h2>' . esc_html(ucwords(str_replace('-', ' ', $selected_repo_label))) . ' (' . esc_html($selected_repo) . ')</h2>';
        echo '<h3>' . esc_html__('Latest Issues', 'delivery-manager-tracking-plugin') . '</h3>';
        $issues = dmtp_github_api_get("$selected_repo/issues?per_page=15$milestone_param$state_param$label_param$assignee_param$sort_param");
        if (isset($issues['error'])) {
            echo '<div class="notice notice-error"><p>' . esc_html($issues['error']) . '</p></div>';
        } elseif (empty($issues)) {
            echo '<p>No issues found.</p>';
        } else {
            echo '<table class="widefat"><thead><tr><th>Title</th><th>Status</th><th>Created</th></tr></thead><tbody>';
            foreach ($issues as $issue) {
                echo '<tr>';
                echo '<td><a href="' . esc_url($issue['html_url']) . '" target="_blank">' . esc_html($issue['title']) . '</a></td>';
                echo '<td>' . esc_html($issue['state']) . '</td>';
                echo '<td>' . esc_html(date('Y-m-d', strtotime($issue['created_at']))) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    }
    echo '<h3>' . esc_html__('Latest Commits', 'delivery-manager-tracking-plugin') . '</h3>';
    $commits = dmtp_github_api_get("$selected_repo/commits?per_page=15");
    if (isset($commits['error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html($commits['error']) . '</p></div>';
    } elseif (empty($commits)) {
        echo '<p>No commits found.</p>';
    } else {
        echo '<table class="widefat"><thead><tr><th>Message</th><th>Author</th><th>Date</th></tr></thead><tbody>';
        foreach ($commits as $commit) {
            echo '<tr>';
            echo '<td><a href="' . esc_url($commit['html_url']) . '" target="_blank">' . esc_html($commit['commit']['message']) . '</a></td>';
            echo '<td>' . esc_html($commit['commit']['author']['name']) . '</td>';
            echo '<td>' . esc_html(date('Y-m-d', strtotime($commit['commit']['author']['date']))) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    ?>
</div>