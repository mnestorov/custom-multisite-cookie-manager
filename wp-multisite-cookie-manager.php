<?php
/** 
 * Plugin Name: MN - WordPress Multisite Cookie Manager
 * Plugin URI: https://github.com/mnestorov/wp-multisite-cookie-manager
 * Description: Manage cookies across a WordPress multisite network.
 * Version: 2.0.4
 * Author: Martin Nestorov
 * Author URI: https://github.com/mnestorov
 * Text Domain: mn-wordpress-multisite-cookie-manager
 * Tags: wp, wp-plugin, wp-admin, wordpress, wordpress-plugin, wordpress-cookie, wordpress-multisite
 */

// Enable WP_DEBUG in your WordPress configuration to catch errors during development.
// In your wp-config.php file:
// define( 'WP_DEBUG', true );
// define( 'WP_DEBUG_LOG', true );
// define( 'WP_DEBUG_DISPLAY', false ); 

/**
 * Geolocation API key
 * Get the key from: https://app.ipgeolocation.io/
 */
define('GEO_API_KEY', '24a6f2d5bd7e45759a759aaa668af953');

// Register the uninstall hook
register_uninstall_hook(__FILE__, 'mn_custom_cookie_manager_uninstall');

// Function to run on plugin deactivation
function mn_custom_cookie_manager_deactivate() {
    // Optionally remove any scheduled events related to this plugin
    wp_clear_scheduled_hook('write_cookie_usage_log_entries_hook');
}
register_deactivation_hook(__FILE__, 'mn_custom_cookie_manager_deactivate');

// Remove the `cookie_usage` table from the database
function mn_custom_cookie_manager_uninstall() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Generate the cookie name
function mn_get_unique_cookie_name() {
    // Get the site name
    $site_name = get_bloginfo('name');
    
    // Convert the site name to lowercase and replace white spaces with underscores
    $formatted_name = strtolower(str_replace(' ', '_', $site_name));
    
    // Get the current blog ID
    $blog_id = get_current_blog_id();
    
    // Add a prefix of "__" before the name and append the blog_id as a suffix
    $cookie_name = '__' . $formatted_name . '_' . $blog_id;
    
    return $cookie_name;
}

// Custom error handling function to log or display errors in a standardized way.
function mn_log_error($message, $error_type = E_USER_NOTICE) {
    if ( WP_DEBUG ) {
        if ( defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
            error_log($message);
        }
        if ( defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ) {
            trigger_error($message, $error_type);
        }
    }
}

// Function to register a new menu page in the network admin
function mn_register_cookie_settings_page() {
    add_menu_page(
        esc_html__('Cookie Settings', 'mn-wordpress-multisite-cookie-manager'),
        esc_html__('Cookie Settings', 'mn-wordpress-multisite-cookie-manager'),
        'manage_options',
        'cookie-settings',
        'mn_cookie_settings_page',
        '',
        99
    );
}
add_action('admin_menu', 'mn_register_cookie_settings_page');

// Function to display the cookie settings page
function mn_cookie_settings_page() {
    // For debug
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Get the unique cookie name
    $cookie_name = mn_get_unique_cookie_name();

    // Get the current blog ID
    $blog_id = get_current_blog_id();

    // Handle form submission for updating cookie settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        // Decode the JSON data from the textarea
        $custom_cookie_expirations = json_decode(stripslashes($_POST['custom_cookie_expirations']), true);
        if (json_last_error() == JSON_ERROR_NONE && is_array($custom_cookie_expirations)) {
            update_blog_option($blog_id, 'custom_cookie_expirations', $custom_cookie_expirations);
            echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'mn-wordpress-multisite-cookie-manager') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Invalid JSON data.', 'mn-wordpress-multisite-cookie-manager') . '</p></div>';
        }
    }

    // Fetch current settings
    $custom_cookie_expirations = get_blog_option($blog_id, 'custom_cookie_expirations', array());

    // Output form for managing cookie settings
    echo '<div class="wrap">';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('custom_cookie_nonce', 'custom_cookie_nonce');
    
    echo '<h1>' . esc_html__('Cookie Settings', 'mn-wordpress-multisite-cookie-manager') . '</h1>';
    echo '<div class="mn-import-export">';
	echo '<input type="submit" name="export_settings" value="' . esc_attr__('Export Settings', 'mn-wordpress-multisite-cookie-manager') . '" class="page-title-action">';
	echo '<input type="submit" name="import_settings" value="' . esc_attr__('Import Settings', 'mn-wordpress-multisite-cookie-manager') . '" class="page-title-action">';
    echo '<input type="file" name="import_settings_file" accept=".json">';
	echo '</div>';

    echo '<table class="form-table" role="presentation"><tbody><tr>';
	echo '<th scope="row"><label>' . esc_html__('Current Blog ID:', 'mn-wordpress-multisite-cookie-manager') . '</label></th>';
	echo '<td><input type="text" class="regular-text" value="' . esc_html($blog_id) . '" disabled></td>';
	echo '</tr><tr>';
    echo '<th scope="row"><label>' . esc_html__('Generated Cookie Name:', 'mn-wordpress-multisite-cookie-manager') . '</label></th>';
	echo '<td><input type="text" class="regular-text" value="'. esc_html($cookie_name) . '" disabled></td>';
    echo '</tr></tbody></table>';

    echo '<table class="form-table" role="presentation"><tbody><tr>';
    echo '<th scope="row"><label>' . esc_html__('Cookie Expirations:', 'mn-wordpress-multisite-cookie-manager') . '</label></th>';
    echo '<td><textarea name="custom_cookie_expirations" rows="5" cols="50">' . esc_textarea(json_encode($custom_cookie_expirations, JSON_PRETTY_PRINT)) . '</textarea><p class="description">Input a JSON object with user roles and corresponding expiration times in seconds.</p></td>';
    echo '</tr></tbody></table>';
    echo '<br>';
    echo '<div class="tablenav bottom"><div class="alignleft actions bulkactions">';
    echo '<input type="submit" value="' . esc_attr__('Save Settings', 'mn-wordpress-multisite-cookie-manager') . '" class="button button-primary">';
    echo '<br class="clear">';
    echo '</div></div>';
    echo '<div class="mn-debug-info"><p>DEBUG INFO</p><pre>' . print_r($custom_cookie_expirations, true) . '</pre></div>';
    echo '</form>';
    echo '</div>';

    // Handle export of cookie settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_settings']) && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        $settings_json = mn_export_cookie_settings();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=cookie-settings.json');
        echo $settings_json;
        exit;
    }

    // Handle import of cookie settings
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_settings']) && isset($_FILES['import_settings_file']) && $_FILES['import_settings_file']['error'] == 0 && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        $json_settings = file_get_contents($_FILES['import_settings_file']['tmp_name']);
        if (mn_import_cookie_settings($json_settings)) {
            echo '<div class="updated"><p>' . esc_html__('Settings imported successfully.', 'mn-wordpress-multisite-cookie-manager') . '</p></div>';
        } else {
            echo '<div class="error"><p>' . esc_html__('Failed to import settings.', 'mn-wordpress-multisite-cookie-manager') . '</p></div>';
        }
    }
}

// Function to handle the logic for cookie expiration based on user roles and login status
function mn_get_cookie_expiration($default_expiration) {
    $blog_id = get_current_blog_id();
    $cookie_expirations = get_blog_option($blog_id, 'custom_cookie_expirations', array());
    $expiration = $default_expiration;
    
    if ($cookie_expirations) {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if (in_array('administrator', $current_user->roles)) {
                $expiration = $default_expiration + DAY_IN_SECONDS;
            } else {
                $expiration = $default_expiration - HOUR_IN_SECONDS;
            }
        } else {
            $expiration = $default_expiration - (30 * MINUTE_IN_SECONDS);
        }
    } else {
        mn_log_error('Failed to fetch custom cookie expirations from the database.');
    }
    
    return $expiration;
}

// Function to set a custom cookie on page load
function mn_set_custom_cookie() {
    $default_expiration = 86400;  // Example default expiration of 1 day
    $cookie_expiration = mn_get_cookie_expiration($default_expiration);
    $cookie_name = mn_get_unique_cookie_name(); // Get the unique cookie name

    // Get geolocation data
    $geo_data = mn_get_geolocation_data();

    // Check if a session ID cookie already exists, otherwise generate a new session ID
    $session_id = isset($_COOKIE['__user_session']) ? $_COOKIE['__user_session'] : wp_generate_uuid4();

    // Build the cookie value as a JSON object
    $cookie_value = json_encode(array(
        'session_id' => $session_id,
        'geo_data' => $geo_data
    ));

    // Set the cookie
    setcookie($cookie_name, $cookie_value, time() + $cookie_expiration, "/");

    // Optionally set a separate session ID cookie if it doesn't exist yet
    if (!isset($_COOKIE['__user_session'])) {
        setcookie('__user_session', $session_id, time() + $cookie_expiration, "/");
    }

    // Log the session_id to verify it's being set correctly
    mn_log_error('Session ID: ' . $session_id);
}
add_action('init', 'mn_set_custom_cookie');

// Function to create a new database table for logging cookie usage on plugin activation
function mn_create_cookie_usage_table() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        blog_id mediumint(9) NOT NULL,
        cookie_name varchar(255) NOT NULL,
        cookie_value TEXT NOT NULL,
        time_stamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    if ( !empty($result['errors']) ) {
        mn_log_error(print_r($result['errors'], true));
    }
}
register_activation_hook(__FILE__, 'mn_create_cookie_usage_table');

// Function to log cookie usage on page load
function mn_log_cookie_usage() {
    if ( ! session_id() ) {
        session_start();
    }

    $blog_id = get_current_blog_id();
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';

    $unique_cookie_name = mn_get_unique_cookie_name();  // Get the unique cookie name

    foreach ($_COOKIE as $cookie_name => $cookie_value) {
        
        // Check if the cookie name matches the unique cookie name
        if ($cookie_name === $unique_cookie_name) {
            
            // Decode the JSON data from the cookie_value
            $cookie_data = json_decode($cookie_value, true);
            
            // Log the decoded data to your error log for debugging
            mn_log_error(print_r($cookie_data, true));
            
            $cookie_log_entry = array(
                'blog_id' => $blog_id,
                'cookie_name' => $cookie_name,
                'cookie_value' => $cookie_value,
                'time_stamp' => current_time('mysql')
            );

            // Log the entire cookie_log_entry array before attempting to insert it
            mn_log_error(print_r($cookie_log_entry, true));

            // Check if the cookie entry already exists in the database to prevent duplicates
            $existing_entry = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE blog_id = %d AND cookie_name = %s",
                $blog_id,
                $cookie_name
            ));

            if (null === $existing_entry) {
                $insert_result = $wpdb->insert($table_name, $cookie_log_entry);
                if (false === $insert_result) {
                    mn_log_error('Failed to insert cookie usage log entry: ' . $wpdb->last_error);
                } else {
                    mn_log_error('Successfully inserted cookie usage log entry');
                }
            }

            // Log the raw cookie value to see what's being stored
            mn_log_error('Raw cookie value: ' . $cookie_value);
        }
    }
}
add_action('init', 'mn_log_cookie_usage');

// Function to write log entries from transient to database hourly
function mn_write_cookie_usage_log_entries() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';
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
add_action('write_cookie_usage_log_entries_hook', 'mn_write_cookie_usage_log_entries');

// Schedule hourly event to write log entries to database
if (!wp_next_scheduled('write_cookie_usage_log_entries_hook')) {
    wp_schedule_event(time(), 'hourly', 'write_cookie_usage_log_entries_hook');
}

// Function to register a submenu page for cookie usage reports
function mn_register_cookie_reporting_page() {
    add_submenu_page(
        'cookie-settings',
        esc_html__('Cookie Usage Reports', 'mn-wordpress-multisite-cookie-manager'),
        esc_html__('Cookie Usage Reports', 'mn-wordpress-multisite-cookie-manager'),
        'manage_options',
        'cookie-reports',
        'mn_cookie_reporting_page'
    );
}
add_action('admin_menu', 'mn_register_cookie_reporting_page');

// Function to display cookie usage reports
function mn_cookie_reporting_page() {
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'multisite_cookie_usage';
    $unique_cookie_name = mn_get_unique_cookie_name();  // Get the unique cookie name
    
    // Modify the SQL query to include a WHERE clause that filters on cookie_name
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT cookie_name, cookie_value, COUNT(DISTINCT blog_id) as blog_count, time_stamp 
        FROM $table_name 
        WHERE cookie_name = %s 
        GROUP BY cookie_name",
        $unique_cookie_name
    ), OBJECT);

    echo '<div class="wrap">';
    echo '<h1>Cookie Usage Reports</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Cookie Name</th><th>Country</th><th>Session ID</th><th>Number of Blogs</th><th>Timestamp</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($results as $row) {
        // Remove any escape characters before decoding
        $cleaned_cookie_value = stripslashes($row->cookie_value);
        
        // Decode the cleaned JSON string into an associative array
        $cookie_data = json_decode($cleaned_cookie_value, true);
        
        // Check if json_decode was successful
        if (json_last_error() == JSON_ERROR_NONE) {
            $country = isset($cookie_data['geo_data']['country_name']) ? $cookie_data['geo_data']['country_name'] : 'Unknown';
            $session_id = isset($cookie_data['session_id']) ? $cookie_data['session_id'] : 'Unknown';
        } else {
            $country = 'JSON Decoding Error';
            $session_id = 'JSON Decoding Error';
        }
        
        echo '<tr>';
        echo '<td>' . esc_html($row->cookie_name) . '</td>';
        echo '<td>' . esc_html($country) . '</td>';  // Display country
        echo '<td>' . esc_html($session_id) . '</td>';  // Display session ID
        echo '<td>' . esc_html($row->blog_count) . '</td>';
        echo '<td>' . esc_html($row->time_stamp) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Function to export cookie settings to a JSON file
function mn_export_cookie_settings() {
    $custom_cookie_expirations = get_site_option('custom_cookie_expirations', '');
    return json_encode($custom_cookie_expirations, JSON_PRETTY_PRINT);
}

// Function to import cookie settings from a JSON file
function mn_import_cookie_settings($json_settings) {
    $settings_array = json_decode($json_settings, true);
    
    if (json_last_error() == JSON_ERROR_NONE && is_array($settings_array)) {
        update_site_option('custom_cookie_expirations', $settings_array);
        return true;
    }

    return false;
}

// Function to get geo-location data
function mn_get_geolocation_data() {
    // Check if the geolocation data is already cached
    $geo_data = get_transient('geo_data');
    if ($geo_data !== false) {
        return $geo_data;  // Return cached data if it exists
    }

    // Geolocation API key
    $api_key = GEO_API_KEY;
    // Get the user's IP address
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $api_url = "https://api.ipgeolocation.io/ipgeo?apiKey=" . $api_key . "&ip=" . $user_ip;

    // Make a request to the IP Geolocation API
    $response = wp_remote_get($api_url);

    // Check for errors in the response
    if (is_wp_error($response)) {
        mn_log_error('Geolocation API error: ' . $response->get_error_message());
        return 'Unable to retrieve geo-location data';
    }

    // Parse the response body
    $geo_data = json_decode(wp_remote_retrieve_body($response), true);

    // Check for valid data
    if (!isset($geo_data['country_name']) || !isset($geo_data['city'])) {
        return false;
    }

    // Cache the geolocation data for 1 hour
    set_transient('geo_data', $geo_data, HOUR_IN_SECONDS);

    return $geo_data;
}

// Function to inject custom CSS styling into the admin pages
function mn_custom_plugin_styles() {
    echo '
        <style type="text/css">
            .mn-debug-info {
                background-color: #f4cccc;
                border: 1px solid #c00;
                border-radius: 3px;
                padding: 10px;
                margin-top: 20px;
            }
            .mn-debug-info p {
                font-weight: bold;
            }
            .mn-import-export {
                padding: 5px; 
                background-color: #e2f3eb; 
                border: 1px solid #b7e1cd; 
                border-radius: 3px;
            }
            .mn-import-export .page-title-action {
                margin: 5px 10px 0 10px;
            }
        </style>
    ';
}
add_action('admin_head', 'mn_custom_plugin_styles');
