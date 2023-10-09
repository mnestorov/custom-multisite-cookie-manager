<?php
/*
Plugin Name: Custom Multisite Cookie Manager
Description: Manage cookies across a multisite network.
Version: 1.0
Author: Martin Nestorov
Author URI: smartystudio.net
Text Domain: custom-multisite-cookie-manager
*/

// Admin Menu Page
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

function cookie_settings_page(){
    // Get the current blog ID
    $blog_id = get_current_blog_id();

    // Create a unique cookie name for this site
    $cookie_name = 'custom_cookie_' . $blog_id;

    // Check if form is submitted and handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['custom_cookie_nonce']) && wp_verify_nonce($_POST['custom_cookie_nonce'], 'custom_cookie_nonce')) {
        // Sanitize and update settings (assuming the settings are expected to be arrays)
        $custom_cookie_expirations = (isset($_POST['custom_cookie_expirations']) && is_array($_POST['custom_cookie_expirations'])) ? array_map('sanitize_text_field', $_POST['custom_cookie_expirations']) : array();
        update_site_option('custom_cookie_expirations', $custom_cookie_expirations);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'custom-multisite-cookie-manager') . '</p></div>';
    }

    // Fetch current settings
    $custom_cookie_expirations = get_site_option('custom_cookie_expirations', '');

    // Output form
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Cookie Settings', 'custom-multisite-cookie-manager') . '</h1>';
    echo '<p>' . esc_html__('Current Blog ID:', 'custom-multisite-cookie-manager') . ' ' . esc_html($blog_id) . '</p>';
    echo '<p>' . esc_html__('Generated Cookie Name:', 'custom-multisite-cookie-manager') . ' ' . esc_html($cookie_name) . '</p>';
    echo '<form method="post">';
    wp_nonce_field('custom_cookie_nonce', 'custom_cookie_nonce');
    
    // Field for managing cookie expirations
    echo '<h2>' . esc_html__('Cookie Expirations', 'custom-multisite-cookie-manager') . '</h2>';
    echo '<textarea name="custom_cookie_expirations" rows="5" cols="50">' . esc_textarea(json_encode($custom_cookie_expirations, JSON_PRETTY_PRINT)) . '</textarea>';

    echo '<br>';
    echo '<input type="submit" value="' . esc_attr__('Save Settings', 'custom-multisite-cookie-manager') . '" class="button button-primary">';
    echo '</form>';
    echo '</div>';
}

// Handle Cookie Expiration
function get_cookie_expiration($group, $default_expiration) {
    $cookie_expirations = get_site_option('custom_cookie_expirations', array());
    $expiration = $default_expiration;
    
    if (isset($cookie_expirations[$group])) {
        // Fetching custom expiration setting for the specific group
        $custom_expiration = $cookie_expirations[$group];

        // Checking if the user is logged in
        if (is_user_logged_in()) {
            // Getting the current user data
            $current_user = wp_get_current_user();

            // Checking user role, for simplicity we'll check for 'administrator' role
            if (in_array('administrator', $current_user->roles)) {
                // If the user is an administrator, extend the cookie expiration time (e.g., by 1 day)
                $expiration = $custom_expiration + DAY_IN_SECONDS;
            } else {
                // If the user is not an administrator, shorten the cookie expiration time (e.g., by 1 hour)
                $expiration = $custom_expiration - HOUR_IN_SECONDS;
            }
        } else {
            // If the user is not logged in, set a different expiration time (e.g., default expiration minus 30 minutes)
            $expiration = $custom_expiration - (30 * MINUTE_IN_SECONDS);
        }
    }
    
    return $expiration;
}

// Set Cookies
function set_custom_cookie() {
    $default_expiration = 86400;  // Example default expiration of 1 day
    $cookie_expiration = get_cookie_expiration($default_expiration);
    $cookie_name = 'custom_cookie_' . get_current_blog_id();
    setcookie($cookie_name, 'cookie_value', time() + $cookie_expiration, "/");
}
add_action('init', 'set_custom_cookie');
