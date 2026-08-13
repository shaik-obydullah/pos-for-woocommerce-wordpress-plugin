<?php
/**
 * Plugin Name: Obydullah POS for WooCommerce
 * Plugin URI: https://obydullah.com/project/wordpress-restaurant-pos-lite-plugin
 * Description: A free plugin to manage restaurant orders, menu, and sales directly from your WordPress dashboard. Requires WooCommerce.
 * Version: 2.0.0
 * Author: Shaik Obydullah
 * Author URI: https://obydullah.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: obydullah-pos-for-woocommerce
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OPFW_VERSION', '2.0.0');
define('OPFW_PATH', plugin_dir_path(__FILE__));
define('OPFW_URL', plugin_dir_url(__FILE__));

require_once OPFW_PATH . 'includes/class-obydullah-pos-for-woocommerce-handler.php';
require_once OPFW_PATH . 'includes/class-obydullah-pos-for-woocommerce-activator.php';
require_once OPFW_PATH . 'includes/class-obydullah-pos-for-woocommerce-deactivator.php';

add_action('plugins_loaded', 'opfw_init');
function opfw_init()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p><strong>Obydullah POS for WooCommerce</strong> requires WooCommerce to be installed and active.</p></div>';
        });
        return;
    }

    static $plugin = null;
    if (null === $plugin) {
        $plugin = new Obydullah_POS_For_WooCommerce_Handler();
    }
    return $plugin;
}

register_activation_hook(__FILE__, ['Obydullah_POS_For_WooCommerce_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Obydullah_POS_For_WooCommerce_Deactivator', 'deactivate']);
