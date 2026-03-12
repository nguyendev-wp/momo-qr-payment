<?php
/**
 * Fired when the plugin is uninstalled
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Option names
$options = array(
    'woocommerce_momo_settings',
);

// Delete options
foreach ($options as $option) {
    delete_option($option);
}

// For multisite
if (is_multisite()) {
    global $wpdb;
    $blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
    
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        foreach ($options as $option) {
            delete_option($option);
        }
        restore_current_blog();
    }
}

// Clean up post meta (optional - comment out if you want to keep transaction data)
/*
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_momo_%'");
*/
