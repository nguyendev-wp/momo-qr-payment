<?php
/**
 * Plugin Name: MoMo QR Payment for WooCommerce
 * Plugin URI: https://example.com/momo-woocommerce
 * Description: Tích hợp thanh toán MoMo QR Code và tự động cập nhật đơn hàng WooCommerce
 * Version: 1.0.0
 * Author: Huynh Nguyen Dev
 * Author URI: https://example.com
 * Text Domain: momo-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// Kiểm tra WooCommerce có được kích hoạt không
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

define('MOMO_WC_VERSION', '1.0.0');
define('MOMO_WC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MOMO_WC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Khai báo tương thích HPOS
add_action('before_woocommerce_init', 'momo_wc_declare_compatibility');
function momo_wc_declare_compatibility() {
    if (class_exists('\Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
}

// Load class gateway
add_action('plugins_loaded', 'momo_wc_gateway_init', 11);

function momo_wc_gateway_init() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    
    require_once MOMO_WC_PLUGIN_DIR . 'includes/class-wc-momo-gateway.php';
    require_once MOMO_WC_PLUGIN_DIR . 'includes/class-momo-api.php';
    require_once MOMO_WC_PLUGIN_DIR . 'includes/class-momo-webhook.php';
    
    // Đăng ký gateway
    add_filter('woocommerce_payment_gateways', 'momo_wc_add_gateway');
}

function momo_wc_add_gateway($gateways) {
    $gateways[] = 'WC_MoMo_Gateway';
    return $gateways;
}

// Thêm custom endpoint cho webhook
add_action('init', 'momo_wc_add_endpoint');
function momo_wc_add_endpoint() {
    add_rewrite_rule('^momo-webhook/?', 'index.php?momo_webhook=1', 'top');
    add_rewrite_tag('%momo_webhook%', '([^&]+)');
}

// Xử lý webhook request
add_action('template_redirect', 'momo_wc_handle_webhook');
function momo_wc_handle_webhook() {
    if (get_query_var('momo_webhook')) {
        $webhook = new MoMo_Webhook();
        $webhook->handle_request();
        exit;
    }
}

// Activation hook
register_activation_hook(__FILE__, 'momo_wc_activate');
function momo_wc_activate() {
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'momo_wc_deactivate');
function momo_wc_deactivate() {
    flush_rewrite_rules();
}

// Thêm settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'momo_wc_action_links');
function momo_wc_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=momo') . '">' . __('Settings', 'momo-woocommerce') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
