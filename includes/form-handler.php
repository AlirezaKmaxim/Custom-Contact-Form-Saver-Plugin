<?php
/**
 * Form Handler
 * Processes form submissions
 */

if (!defined('ABSPATH')) exit;

/**
 * Handle form submission via AJAX
 */
function ccf_ajax_form_handler() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ccf_form_action')) {
        wp_send_json_error(['message' => '⛔ Security check failed!']);
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . "contact_form";

    // Ensure table exists
    ccf_create_table();
    ccf_check_table_structure();

    // Sanitize inputs
    $name       = sanitize_text_field($_POST['name'] ?? '');
    $phone      = sanitize_text_field($_POST['phone'] ?? '');
    $message    = sanitize_textarea_field($_POST['message'] ?? '');
    $page_title = sanitize_text_field($_POST['page_title'] ?? '');
    $page_url   = esc_url_raw($_POST['page_url'] ?? '');

    // Validate required fields
    if (empty($name) || empty($phone)) {
        wp_send_json_error(['message' => '⚠️ لطفا نام و شماره تماس را وارد کنید']);
        wp_die();
    }

    // Prepare data for insert
    $data = [
        'name'        => $name,
        'phone'       => $phone,
        'page_title'  => $page_title,
        'page_url'    => $page_url,
        'status'      => 'unread', // new submissions are unread
        'created_at'  => current_time('mysql'), // uses WordPress timezone
    ];

    if (!empty($message)) {
        $data['message'] = $message;
    }

    // Insert into database
    $inserted = $wpdb->insert($table_name, $data);

    // Log for debugging
    if ($inserted === false) {
        error_log("❌ CCF Insert failed: " . $wpdb->last_error);
        wp_send_json_error(['message' => '❌ خطا در ثبت اطلاعات. لطفا دوباره تلاش کنید.']);
    } else {
        error_log("✅ CCF Insert success: $name - $phone (ID " . $wpdb->insert_id . ")");
        wp_send_json_success(['message' => '✅ اطلاعات شما با موفقیت ثبت شد!']);
    }

    wp_die();
}
add_action('wp_ajax_ccf_submit_form', 'ccf_ajax_form_handler');
add_action('wp_ajax_nopriv_ccf_submit_form', 'ccf_ajax_form_handler');
