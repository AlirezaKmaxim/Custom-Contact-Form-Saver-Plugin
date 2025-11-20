<?php
/*
Plugin Name: Custom Contact Form Saver
Description: فرم تماس ساده + ذخیره در دیتابیس + صفحه مدیریت
Version: 6.5
Author: AlirezaCarryMi
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('CCF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CCF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once CCF_PLUGIN_DIR . 'includes/database.php';
require_once CCF_PLUGIN_DIR . 'includes/form-handler.php';
require_once CCF_PLUGIN_DIR . 'includes/shortcode.php';
require_once CCF_PLUGIN_DIR . 'includes/admin-page.php';

// Run table creation and structure check on plugin activation
register_activation_hook(__FILE__, function() {
    ccf_create_table();
    ccf_check_table_structure();
});

// Check and update table structure when admin area loads
add_action('admin_init', 'ccf_check_table_structure');
