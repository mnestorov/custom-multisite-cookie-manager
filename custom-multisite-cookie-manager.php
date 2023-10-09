<?php
/** 
 * Plugin Name: MN - Custom Multisite Cookie Manager
 * Plugin URI: https://github.com/mnestorov/wp-custom-multisite-cookie-manager
 * Description: Manage cookies across a multisite network.
 * Version: 1.3
 * Author: Martin Nestorov
 * Author URI: https://github.com/mnestorov
 * Text Domain: custom-multisite-cookie-manager
 * Tags: wordpress, wordpress-plugin, wp, wp-plugin, wp-admin, wordpress-cookie
 */

// Function to register a new menu page in the network admin
function register_cookie_settings_page(){
    add_menu_page(
        esc_html__('Cookie Settings', 'custom-multisite-cookie-manager'),
        esc_html__('Cookie Settings', 'custom-multisite-cookie-manager'),
        'manage_network_options',
        'cookie-settings',
        'cookie_settings_page',
        '',
        99
    );
}
add_action('network_admin_menu', 'register_cookie_settings_page');

// Function to display the cookie settings page
function cookie_settings_page(){
    // Get the current blog ID
    $blog_id = get_current_blog_id();

    // Create a unique cookie name for this site
    $cookie_name = 'custom_cookie_' . $blog_id;

    // Handle form submission for updating cookie settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        // Sanitize and update settings
        $custom_cookie_expirations = (isset($_POST['custom_cookie_expirations']) && is_array($_POST['custom_cookie_expirations'])) ? array_map('sanitize_text_field', $_POST['custom_cookie_expirations']) : array();
        update_site_option('custom_cookie_expirations', $custom_cookie_expirations);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'custom-multisite-cookie-manager') . '</p></div>';
    }

    // Fetch current settings
    $custom_cookie_expirations = get_site_option('custom_cookie_expirations', '');

    // Output form for managing cookie settings
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Cookie Settings', 'custom-multisite-cookie-manager') . '</h1>';
    echo '<p>' . esc_html__('Current Blog ID:', 'custom-multisite-cookie-manager') . ' ' . esc_html($blog_id) . '</p>';
    echo '<p>' . esc_html__('Generated Cookie Name:', 'custom-multisite-cookie-manager') . ' ' . esc_html($cookie_name) . '</p>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('custom_cookie_nonce', 'custom_cookie_nonce');
    echo '<h2>' . esc_html__('Cookie Expirations', 'custom-multisite-cookie-manager') . '</h2>';
    echo '<textarea name="custom_cookie_expirations" rows="5" cols="50">' . esc_textarea(json_encode($custom_cookie_expirations, JSON_PRETTY_PRINT)) . '</textarea>';
    echo '<br>';
    echo '<input type="submit" value="' . esc_attr__('Save Settings', 'custom-multisite-cookie-manager') . '" class="button button-primary">';
    echo '<input type="submit" name="export_settings" value="' . esc_attr__('Export Settings', 'custom-multisite-cookie-manager') . '" class="button">';
    echo '<input type="file" name="import_settings_file" accept=".json">';
    echo '<input type="submit" name="import_settings" value="' . esc_attr__('Import Settings', 'custom-multisite-cookie-manager') . '" class="button">';
    echo '</form>';
    echo '</div>';

    // Handle export of cookie settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_settings']) && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        $settings_json = export_cookie_settings();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=cookie-settings.json');
        echo $settings_json;
        exit;
    }

    // Handle import of cookie settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_settings']) && isset($_FILES['import_settings_file']) && $_FILES['import_settings_file']['error'] == 0 && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        $json_settings = file_get_contents($_FILES['import_settings_file']['tmp_name']);
        if (import_cookie_settings($json_settings)) {
            echo '<div class="updated"><p>' . esc_html__('Settings imported successfully.', 'custom-multisite-cookie-manager') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Failed to import settings.', 'custom-multisite-cookie-manager') . '</p></div>';
        }
    }
}

// Function to handle the logic for cookie expiration based on user roles and login status
function get_cookie_expiration($group, $default_expiration) {
    $cookie_expirations = get_site_option('custom_cookie_expirations', array());
    $expiration = $default_expiration;
    
    // Check if custom expiration is set for the group
    if (isset($cookie_expirations[$group])) {
        $custom_expiration = $cookie_expirations[$group];
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if (in_array('administrator', $current_user->roles)) {
                $expiration = $custom_expiration + DAY_IN_SECONDS;
            } else {
                $expiration = $custom_expiration - HOUR_IN_SECONDS;
            }
        } else {
            $expiration = $custom_expiration - (30 * MINUTE_IN_SECONDS);
        }
    }
    
    return $expiration;
}

// Function to set a custom cookie on page load
function set_custom_cookie() {
    $default_expiration = 86400;  // Example default expiration of 1 day
    $cookie_expiration = get_cookie_expiration($default_expiration);
    $cookie_name = 'custom_cookie_' . get_current_blog_id();
    setcookie($cookie_name, 'cookie_value', time() + $cookie_expiration, "/");
}
add_action('init', 'set_custom_cookie');

// Function to create a new database table for logging cookie usage on plugin activation
function create_cookie_usage_table() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'cookie_usage';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        blog_id mediumint(9) NOT NULL,
        cookie_name varchar(255) NOT NULL,
        cookie_value varchar(255) NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_cookie_usage_table');

// Function to log cookie usage on page load
function log_cookie_usage() {
    $cookie_log_entry = array(
        'blog_id' => get_current_blog_id(),
        'cookie_name' => 'custom_cookie_' . get_current_blog_id(),
        'cookie_value' => $_COOKIE['custom_cookie_' . get_current_blog_id()],
        'timestamp' => current_time('mysql')
    );
    $log_entries = get_transient('cookie_usage_log_entries');
    if (!$log_entries) {
        $log_entries = array();
    }
    $log_entries[] = $cookie_log_entry;
    set_transient('cookie_usage_log_entries', $log_entries, HOUR_IN_SECONDS);
}
add_action('init', 'log_cookie_usage');

// Function to write log entries from transient to database hourly
function write_cookie_usage_log_entries() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'cookie_usage';
    $log_entries = get_transient('cookie_usage_log_entries');
    if ($log_entries && is_array($log_entries)) {
        $all_inserts_successful = true;
        foreach ($log_entries as $entry) {
            $insert_result = $wpdb->insert($table_name, $entry);
            if ($insert_result === false) {
                error_log("Failed to insert cookie usage log entry: " . $wpdb->last_error);
                $all_inserts_successful = false;
            }
        }
        if ($all_inserts_successful) {
            delete_transient('cookie_usage_log_entries');
        }
    }
}
add_action('write_cookie_usage_log_entries_hook', 'write_cookie_usage_log_entries');

// Schedule hourly event to write log entries to database
if (!wp_next_scheduled('write_cookie_usage_log_entries_hook')) {
    wp_schedule_event(time(), 'hourly', 'write_cookie_usage_log_entries_hook');
}

// Function to register a submenu page for cookie usage reports
function register_cookie_reporting_page(){
    add_submenu_page(
        'cookie-settings',
        esc_html__('Cookie Usage Reports', 'custom-multisite-cookie-manager'),
        esc_html__('Cookie Usage Reports', 'custom-multisite-cookie-manager'),
        'manage_network_options',
        'cookie-reports',
        'cookie_reporting_page'
    );
}
add_action('network_admin_menu', 'register_cookie_reporting_page');

// Function to display cookie usage reports
function cookie_reporting_page(){
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'cookie_usage';
    $results = $wpdb->get_results("SELECT * FROM $table_name", OBJECT);
    echo '<div class="wrap">';
    echo '<h1>Cookie Usage Reports</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Blog ID</th><th>Cookie Name</th><th>Cookie Value</th><th>Timestamp</th></tr></thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->blog_id) . '</td>';
        echo '<td>' . esc_html($row->cookie_name) . '</td>';
        echo '<td>' . esc_html($row->cookie_value) . '</td>';
        echo '<td>' . esc_html($row->timestamp) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Function to export cookie settings to a JSON file
function export_cookie_settings() {
    $custom_cookie_expirations = get_site_option('custom_cookie_expirations', '');
    return json_encode($custom_cookie_expirations, JSON_PRETTY_PRINT);
}

// Function to import cookie settings from a JSON file
function import_cookie_settings($json_settings) {
    $settings_array = json_decode($json_settings, true);
    if (json_last_error() == JSON_ERROR_NONE && is_array($settings_array)) {
        update_site_option('custom_cookie_expirations', $settings_array);
        return true;
    }
    return false;
}
