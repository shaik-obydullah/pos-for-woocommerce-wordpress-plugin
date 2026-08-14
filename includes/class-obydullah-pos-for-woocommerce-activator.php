<?php
/**
 * Fired during plugin activation
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since   2.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_Activator
{
    public static function activate()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $table_accounting = $wpdb->prefix . 'opfw_accounting';
        $table_adjustment_log = $wpdb->prefix . 'opfw_stock_adjustment_log';

        $sql_accounting = "CREATE TABLE {$table_accounting} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            in_amount DECIMAL(10,2) DEFAULT NULL,
            out_amount DECIMAL(10,2) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        $sql_adjustment_log = "CREATE TABLE {$table_adjustment_log} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            adjustment_type ENUM('increase','decrease') NOT NULL,
            quantity INT(11) NOT NULL,
            old_quantity INT(11) NOT NULL DEFAULT 0,
            new_quantity INT(11) NOT NULL DEFAULT 0,
            note TEXT DEFAULT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id)
        ) {$charset_collate};";

        dbDelta($sql_accounting);
        dbDelta($sql_adjustment_log);

        update_option('opfw_version', OPFW_VERSION);

        if (!get_option('opfw_currency')) {
            update_option('opfw_currency', get_woocommerce_currency_symbol());
        }
        if (!get_option('opfw_tax_rate')) {
            update_option('opfw_tax_rate', '0');
        }
        if (!get_option('opfw_vat_rate')) {
            update_option('opfw_vat_rate', '0');
        }

        flush_rewrite_rules();
    }
}
