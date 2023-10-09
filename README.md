<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/github/explore/80688e429a7d4ef2fca1e82350fe8e3517d3494d/topics/wordpress/wordpress.png" width="100" alt="Laravel Logo"></a></p>

# WordPress - Custom Multisite Cookie Manager

[![Licence](https://img.shields.io/github/license/Ileriayo/markdown-badges?style=for-the-badge)](./LICENSE)

## Overview

**_Manage cookies across a WordPress multisite network with the Custom Multisite Cookie Manager plugin._**

This plugin allows network administrators to manage cookie expiration settings for each site within a multisite network. It provides a network admin settings page where you can specify cookie expiration times. A unique cookie will be set for each site in the network.

## Installation

1. Download the plugin files to your computer.
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to the `/wp-content/plugins/` directory of your WordPress multisite installation.
3. Navigate to the **Network Admin -> Plugins** page within your WordPress multisite network.
4. Locate **Custom Multisite Cookie Manager** in the list of available plugins and click **Network Activate**.

## Usage

1. After activating the plugin, navigate to the **Network Admin -> Settings** page.
2. Click on **Cookie Settings** in the menu.
3. On the **Cookie Settings** page, you'll find a form to manage cookie expirations:
   - Under **Cookie Expirations**, enter the expiration time (in seconds) for cookies on each site. You can specify different expiration times for different sites.
4. Click **Save Settings** to save your changes.
5. The plugin will automatically set cookies with the specified expiration times for each site in your network.

## Frequently Asked Questions

### How do I set custom cookie expiration times?

Navigate to the **Network Admin -> Settings -> Cookie Settings** page and enter the desired expiration times in the form provided. Click **Save Settings** to save your changes.

### How are cookies named?

Each cookie is named `custom_cookie_[BLOG_ID]`, where `[BLOG_ID]` is the ID of the site within the network.

## Changelog

### 1.0
- Initial release.

### 1.1
- Add an option for encrypting cookie values for added security.

### 1.2
- Implement the cookie import/export feature

---

## License

This project is released under the MIT License.
