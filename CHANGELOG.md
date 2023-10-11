# Changelog

### 1.0 (2023.10.09)
- Initial release.

### 1.1
- Add an option for encrypting cookie values for added security.

### 1.2
- Implement the cookie import/export feature.

### 1.3
- Log cookie usage, and provide reporting tools for administrators.

### 1.4 (2023.10.10)
- Reduce the code duplication and make the code more maintainable.

### 1.5
- Clean up and remove any database tables on uninstall that were created by the plugin.

### 1.6
- Added 'mn_' prefix for all custom function names.

### 1.7
- Implement the feature to automatically scan and identify cookies being set on the websites within the multisite network.

### 1.7.1
- Resolve the PHP Fatal error, bug fixes.

### 1.8
- Added error handling and logging features.
   - **WP_DEBUG** configurations are mentioned at the top as a reminder to enable them during development.
   - A `mn_log_error` function has been introduced for standardized error logging throughout the plugin.
   - Error handling has been added to the `mn_get_cookie_expiration`, `mn_create_cookie_usage_table`, and `mn_log_cookie_usage` functions to log database errors and other issues to the error log.
   - In the `mn_log_cookie_usage` function, a check has been added on the result of the `$wpdb->insert()` method call to log any errors that occur during the database insert operation.

### 1.8.1
- Rename of the main plugin file.

### 1.8.2
- Minor fixes on the plugin name in to the README.md file.

### 1.8.3
- Minor form layout and style changes.

### 1.9
- Make the plugin accessible in the admin area of all sites in a multisite network.

### 2.0 (2023.10.11)
- Major bug fixes and improvements, adding of additional error checking, sanitize JSON data, changing the name of the cookie.
    - Sanitize and handle the JSON data correctly when saving and retrieving it from the database.
    - Enable error reporting to catch any PHP errors or warnings that might be occurring.
    - Display the raw data being saved to the database on the settings page for debugging purposes.
    - Modifying `mn_get_cookie_expiration` and `mn_cookie_settings_page` functions to use `get_blog_option` and `update_blog_option` instead of `get_site_option` and `update_site_option`.
    - The cookie name will have the format `__site_name_blog_id`, where `site_name` is the name of the site (with spaces replaced by underscores and converted to lowercase), and `blog_id` is the ID of the current blog.
    - Added more detailed explanation of how the plugin works in to the README.md file.

### 2.0.1
- Added additional function for the css styles of the plugin admin, added minor styling to the plugin admin debug info, removed all inline css, minor fixes on the plugin description in to the README.md file.