<?php
/**
 * Plugin Name: Obydullah POS for WooCommerce
 * Plugin URI: https://obydullah.com/project/wordpress-restaurant-pos-lite-plugin
 * Description: Complete restaurant Point of Sale (POS) with inventory, order management, and sales tracking for food businesses.
 * Version: 1.0.0
 * Author: Shaik Obydullah
 * Author URI: https://obydullah.com
 * Text Domain: obydullah-pos-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 7.1
 * Requires PHP: 8.0
 * WooCommerce requires at least: 8.0
 * WooCommerce tested up to: 11.0
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * @since   1.0.0
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OPFW_VERSION', '1.0.0');
define('OPFW_PATH', plugin_dir_path(__FILE__));
define('OPFW_URL', plugin_dir_url(__FILE__));

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

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

register_activation_hook(__FILE__, ['Obydullah_POS_For_WooCommerce_Activator', 'opfw_activate']);
register_deactivation_hook(__FILE__, ['Obydullah_POS_For_WooCommerce_Deactivator', 'opfw_deactivate']);
