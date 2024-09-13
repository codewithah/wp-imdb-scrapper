<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if the user has the capability to uninstall the plugin
if (!current_user_can('activate_plugins')) {
    return;
}

// Delete options
delete_option('ims_api_key');
delete_option('ims_display_options');