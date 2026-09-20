<?php
/**
 * Plugin Name: Obydullah Restaurant Sales Terminal for WooCommerce
 * Plugin URI: https://obydullah.com/project/woocommerce-pos-plugin
 * Description: Obydullah Restaurant Sales Terminal for WooCommerce is a complete restaurant Point of Sale with order management, inventory, and sales tracking.
 * Version: 1.0.0
 * Author: Shaik Obydullah
 * Author URI: https://obydullah.com
 * Text Domain: obydullah-restaurant-sales-terminal
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WooCommerce requires at least: 8.0
 * WooCommerce tested up to: 11.0
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

require_once OPFW_PATH . 'includes/class-obydullah-restaurant-sales-terminal-handler.php';
require_once OPFW_PATH . 'includes/class-obydullah-restaurant-sales-terminal-activator.php';
require_once OPFW_PATH . 'includes/class-obydullah-restaurant-sales-terminal-deactivator.php';

add_action('plugins_loaded', 'opfw_init');
function opfw_init()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p><strong>Obydullah Restaurant Sales Terminal for WooCommerce</strong> requires WooCommerce to be installed and active.</p></div>';
        });
        return;
    }

    static $plugin = null;
    if (null === $plugin) {
        $plugin = new Obydullah_Restaurant_Sales_Terminal_Handler();
    }
    return $plugin;
}

register_activation_hook(__FILE__, ['Obydullah_Restaurant_Sales_Terminal_Activator', 'opfw_activate']);
register_deactivation_hook(__FILE__, ['Obydullah_Restaurant_Sales_Terminal_Deactivator', 'opfw_deactivate']);