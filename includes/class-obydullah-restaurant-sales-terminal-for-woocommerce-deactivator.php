<?php
/**
 * Fired during plugin deactivation
 *
 * @package Obydullah_Restaurant_Sales_Terminal_For_WooCommerce
 * @since   1.0.0
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Deactivator
{
    /**
     * Plugin deactivation callback
     */
    public static function opfw_deactivate()
    {
        if (class_exists('Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Helpers')) {
            Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Helpers::opfw_cache_flush_all();
        }

        flush_rewrite_rules();
    }
}
