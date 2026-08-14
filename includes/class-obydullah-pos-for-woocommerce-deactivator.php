<?php
/**
 * Fired during plugin deactivation
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_Deactivator
{
    /**
     * Plugin deactivation callback
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}
