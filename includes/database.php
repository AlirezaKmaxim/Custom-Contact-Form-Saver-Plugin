<?php
/**
 * Database Functions
 * Handles table creation and structure updates
 */

if (!defined('ABSPATH')) exit;

/**
 * Create database table
 */
function ccf_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . "contact_form";

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        message text DEFAULT NULL,
        page_title varchar(255) DEFAULT NULL,
        page_url varchar(500) DEFAULT NULL,
        status varchar(10) DEFAULT 'unread',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Check and update table structure
 * Adds missing columns to existing tables
 */
function ccf_check_table_structure() {
    global $wpdb;
    $table_name = $wpdb->prefix . "contact_form";
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        ccf_create_table();
        return;
    }
    
    // Get existing columns
    $columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
    
    // Add missing columns dynamically
    if (!in_array('message', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN message text DEFAULT NULL AFTER phone");
    }
    
    if (!in_array('page_title', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN page_title varchar(255) DEFAULT NULL AFTER message");
    }
    
    if (!in_array('page_url', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN page_url varchar(500) DEFAULT NULL AFTER page_title");
    }

    if (!in_array('status', $columns)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN status varchar(10) DEFAULT 'unread' AFTER page_url");
    }
}
