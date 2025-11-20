<?php
/**
 * Admin Page
 * Displays form submissions in WordPress admin
 */

if (!defined('ABSPATH')) exit;

/**
 * Add admin menu
 */
function ccf_admin_menu() {
    add_menu_page(
        'Form Submissions',
        'Form Submissions',
        'manage_options',
        'ccf_form_submissions',
        'ccf_admin_page',
        'dashicons-email-alt',
        6
    );
}
add_action('admin_menu', 'ccf_admin_menu');

/**
 * Enqueue admin styles and scripts
 */
function ccf_admin_enqueue_scripts($hook) {
    if ($hook !== 'toplevel_page_ccf_form_submissions') {
        return;
    }
    
    // Get the plugin directory URL (go back one level from includes folder)
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    
    wp_enqueue_style(
        'ccf-admin-style',
        $plugin_url . 'assets/css/admin-style.css',
        [],
        '1.0.3'
    );
    
    wp_enqueue_script(
        'ccf-admin-script',
        $plugin_url . 'assets/js/admin-script.js',
        [],
        '1.0.3',
        true
    );
}
add_action('admin_enqueue_scripts', 'ccf_admin_enqueue_scripts');

/**
 * Admin page display
 */
function ccf_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . "contact_form";

    // Ensure table exists and structure is correct
    ccf_create_table();
    ccf_check_table_structure();

    // Handle delete single
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        check_admin_referer('ccf_delete_' . intval($_GET['id']));
        $id = intval($_GET['id']);
        $wpdb->delete($table_name, ['id' => $id]);
        echo '<div class="updated"><p>✅ Entry deleted successfully!</p></div>';
    }

    // Handle bulk delete
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && isset($_POST['bulk_ids'])) {
        check_admin_referer('ccf_bulk_action');
        $ids = array_map('intval', $_POST['bulk_ids']);
        foreach ($ids as $id) {
            $wpdb->delete($table_name, ['id' => $id]);
        }
        echo '<div class="updated"><p>✅ Selected entries deleted successfully!</p></div>';
    }

    // Handle bulk mark as read
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_mark_read' && isset($_POST['bulk_ids'])) {
        check_admin_referer('ccf_bulk_action');
        $ids = array_map('intval', $_POST['bulk_ids']);
        foreach ($ids as $id) {
            $wpdb->update($table_name, ['status' => 'read'], ['id' => $id]);
        }
        echo '<div class="updated"><p>✅ Selected entries marked as read!</p></div>';
    }

    // Handle bulk mark as unread
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_mark_unread' && isset($_POST['bulk_ids'])) {
        check_admin_referer('ccf_bulk_action');
        $ids = array_map('intval', $_POST['bulk_ids']);
        foreach ($ids as $id) {
            $wpdb->update($table_name, ['status' => 'unread'], ['id' => $id]);
        }
        echo '<div class="updated"><p>✅ Selected entries marked as unread!</p></div>';
    }

    // Handle mark as read/unread (single)
    if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
        check_admin_referer('ccf_toggle_' . intval($_GET['id']));
        $id = intval($_GET['id']);
        $entry = $wpdb->get_row("SELECT status FROM $table_name WHERE id = $id");
        if ($entry) {
            $new_status = ($entry->status === 'read') ? 'unread' : 'read';
            $wpdb->update($table_name, ['status' => $new_status], ['id' => $id]);
            echo '<div class="updated"><p>✅ Entry marked as ' . esc_html($new_status) . '.</p></div>';
        }
    }

    // Pagination setup
    $per_page = 75;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Get total count
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);

    // Get paginated results — unread first, newest first; read next, newest first
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM $table_name
        ORDER BY 
            CASE WHEN status = 'unread' THEN 0 ELSE 1 END,
            id DESC
        LIMIT %d OFFSET %d
    ", $per_page, $offset));

    echo '<div class="wrap">';
    echo '<h1>📩 Form Submissions</h1>';

    // Show stats
    $unread_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'unread'");
    echo '<p>Total: <strong>' . esc_html($total_items) . '</strong> submissions | Unread: <strong>' . esc_html($unread_count) . '</strong></p>';

    if (!$results) {
        echo '<p>No submissions yet.</p>';
    } else {
        echo '<form method="post" id="ccf-bulk-form">';
        wp_nonce_field('ccf_bulk_action');
        echo '<input type="hidden" name="action" value="" id="ccf-bulk-action-input">';
        
        echo '<div class="ccf-bulk-actions">';
        echo '<label for="ccf-bulk-action-select">Bulk Actions:</label>';
        echo '<select id="ccf-bulk-action-select">
                <option value="">— Select Action —</option>
                <option value="bulk_mark_read">Mark as Read</option>
                <option value="bulk_mark_unread">Mark as Unread</option>
                <option value="bulk_delete">Delete</option>
              </select>';
        echo '<button type="button" class="button action" id="ccf-apply-bulk">Apply</button>';
        echo '</div>';
        
        echo '<div class="ccf-table-wrapper">';
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>
                <th style="width: 40px;"><input type="checkbox" id="ccf_select_all"></th>
                <th style="width: 50px;">ID</th>
                <th style="width: 150px;">Name</th>
                <th style="width: 120px;">Phone</th>
                <th>Message</th>
                <th style="width: 200px;">Page Submitted</th>
                <th style="width: 150px;">Date</th>
                <th style="width: 120px;">Action</th>
              </tr></thead><tbody>';

        foreach ($results as $row) {
            $message = $row->message ?? '';
            $message_display = !empty($message) ? nl2br(esc_html($message)) : '<em>No message</em>';

            $page_title = $row->page_title ?? '';
            $page_url   = $row->page_url ?? '';

            if (!empty($page_url) && !empty($page_title)) {
                $page_link = '<a href="' . esc_url($page_url) . '" target="_blank">' . esc_html($page_title) . '</a>';
            } elseif (!empty($page_title)) {
                $page_link = esc_html($page_title);
            } else {
                $page_link = '<em>Unknown</em>';
            }

            $delete_url = wp_nonce_url(
                '?page=ccf_form_submissions&action=delete&id=' . $row->id,
                'ccf_delete_' . $row->id
            );

            $toggle_status_url = wp_nonce_url(
                '?page=ccf_form_submissions&action=toggle_status&id=' . $row->id,
                'ccf_toggle_' . $row->id
            );

            $status_label = ($row->status === 'read') ? 'Mark Unread' : 'Mark Read';
            $row_class = ($row->status === 'unread') ? 'ccf-unread' : 'ccf-read';

            echo '<tr class="' . esc_attr($row_class) . '">
                    <td><input type="checkbox" name="bulk_ids[]" value="' . esc_attr($row->id) . '"></td>
                    <td>' . esc_html($row->id) . '</td>
                    <td>' . esc_html($row->name) . '</td>
                    <td>' . esc_html($row->phone) . '</td>
                    <td class="ccf-message-cell">' . $message_display . '</td>
                    <td>' . $page_link . '</td>
                    <td>' . esc_html($row->created_at) . '</td>
                    <td>
                        <a href="' . esc_url($toggle_status_url) . '">' . esc_html($status_label) . '</a> |
                        <a href="' . esc_url($delete_url) . '" onclick="return confirm(\'Are you sure?\')">Delete</a>
                    </td>
                  </tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
        echo '</form>';

        // Pagination
        if ($total_pages > 1) {
            echo '<div class="ccf-pagination">';
            
            // Previous button
            if ($current_page > 1) {
                $prev_url = add_query_arg('paged', $current_page - 1, '?page=ccf_form_submissions');
                echo '<a href="' . esc_url($prev_url) . '">« Previous</a>';
            } else {
                echo '<span class="disabled">« Previous</span>';
            }

            // Page numbers
            $range = 2;
            
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)) {
                    if ($i == $current_page) {
                        echo '<span class="current">' . $i . '</span>';
                    } else {
                        $page_url = add_query_arg('paged', $i, '?page=ccf_form_submissions');
                        echo '<a href="' . esc_url($page_url) . '">' . $i . '</a>';
                    }
                } elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1) {
                    echo '<span>...</span>';
                }
            }

            // Next button
            if ($current_page < $total_pages) {
                $next_url = add_query_arg('paged', $current_page + 1, '?page=ccf_form_submissions');
                echo '<a href="' . esc_url($next_url) . '">Next »</a>';
            } else {
                echo '<span class="disabled">Next »</span>';
            }

            echo '</div>';

            // Page info
            echo '<p>Page ' . esc_html($current_page) . ' of ' . esc_html($total_pages) . ' | Showing ' . esc_html(count($results)) . ' entries</p>';
        }
    }

    echo '</div>';
}
?>