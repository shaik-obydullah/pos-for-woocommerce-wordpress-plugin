<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since   1.0.0
 * @version 2.0.0
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

// Delete plugin options
$opfw_options = [
    'opfw_version',
    'opfw_currency',
    'opfw_tax_rate',
    'opfw_vat_rate',
    'opfw_shop_name',
    'opfw_shop_address',
    'opfw_shop_phone',
    'opfw_currency_position',
    'opfw_date_format',
];

foreach ($opfw_options as $opfw_option) {
    delete_option($opfw_option);
    delete_site_option($opfw_option);
}

// Drop custom tables (WooCommerce data is NOT removed)
$opfw_tables = [
    'opfw_accounting',
    'opfw_stock_adjustment_log',
];

foreach ($opfw_tables as $opfw_table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$opfw_table}");
}

// Clear any cached data
wp_cache_flush();
