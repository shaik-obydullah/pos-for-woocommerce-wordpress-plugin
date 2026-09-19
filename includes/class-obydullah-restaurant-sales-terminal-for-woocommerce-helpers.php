<?php
/**
 * Helper functions for Obydullah Restaurant Sales Terminal for WooCommerce
 *
 * @package Obydullah_Restaurant_Sales_Terminal_For_WooCommerce
 * @since   1.0.0
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper functions for Obydullah Restaurant Sales Terminal for WooCommerce
 */
class Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Helpers
{
    /**
     * Object cache group used for plugin settings.
     *
     * @since 1.0.0
     * @var string
     */
    const CACHE_GROUP = 'opfw_settings';

    /**
     * Registry key that tracks every cache key stored in a group so whole
     * groups can be flushed without hardcoding each dynamic cache key.
     *
     * @since 1.0.0
     * @var string
     */
    const CACHE_REGISTRY_KEY = '__opfw_cache_keys';

    /**
     * Get all POS settings
     *
     * @since 1.0.0
     * @return array
     */
    public static function opfw_get_settings()
    {
        $cached = self::opfw_cache_get('settings', self::CACHE_GROUP);
        if (false !== $cached) {
            return $cached;
        }

        $settings = array(
            'currency' => get_option('opfw_currency', '$'),
            'vat_rate' => get_option('opfw_vat_rate', '0'),
            'tax_rate' => get_option('opfw_tax_rate', '0'),
            'shop_name' => get_option('opfw_shop_name', ''),
            'shop_address' => get_option('opfw_shop_address', ''),
            'shop_phone' => get_option('opfw_shop_phone', ''),
            'currency_position' => get_option('opfw_currency_position', 'left'),
            'date_format' => get_option('opfw_date_format', 'Y-m-d'),
        );

        self::opfw_cache_set('settings', $settings, self::CACHE_GROUP);

        return $settings;
    }

    /**
     * Get an item from the WordPress object cache.
     *
     * @since 1.0.0
     * @param string $key   Cache key.
     * @param string $group Cache group.
     * @return mixed Cached value or false on a miss.
     */
    public static function opfw_cache_get($key, $group = self::CACHE_GROUP)
    {
        return wp_cache_get($key, $group);
    }

    /**
     * Store an item in the WordPress object cache.
     *
     * @since 1.0.0
     * @param string $key   Cache key.
     * @param mixed  $data  Value to cache.
     * @param string $group Cache group.
     * @return bool
     */
    public static function opfw_cache_set($key, $data, $group = self::CACHE_GROUP)
    {
        self::opfw_cache_register($key, $group);

        return wp_cache_set($key, $data, $group);
    }

    /**
     * Track a cache key inside a group's registry so it can be flushed
     * together with the rest of the group.
     *
     * @since 1.0.0
     * @param string $key   Cache key.
     * @param string $group Cache group.
     * @return void
     */
    public static function opfw_cache_register($key, $group = self::CACHE_GROUP)
    {
        $keys = wp_cache_get(self::CACHE_REGISTRY_KEY, $group);
        if (!is_array($keys)) {
            $keys = array();
        }
        $keys[$key] = time();
        wp_cache_set(self::CACHE_REGISTRY_KEY, $keys, $group);
    }

    /**
     * Delete an item from the WordPress object cache.
     *
     * @since 1.0.0
     * @param string $key   Cache key.
     * @param string $group Cache group.
     * @return bool
     */
    public static function opfw_cache_delete($key, $group = self::CACHE_GROUP)
    {
        $keys = wp_cache_get(self::CACHE_REGISTRY_KEY, $group);
        if (is_array($keys)) {
            unset($keys[$key]);
            wp_cache_set(self::CACHE_REGISTRY_KEY, $keys, $group);
        }

        return wp_cache_delete($key, $group);
    }

    /**
     * Flush every key in a cache group.
     *
     * @since 1.0.0
     * @param string $group Cache group.
     * @return void
     */
    public static function opfw_cache_flush_group($group)
    {
        $keys = wp_cache_get(self::CACHE_REGISTRY_KEY, $group);
        if (is_array($keys)) {
            foreach (array_keys($keys) as $key) {
                wp_cache_delete($key, $group);
            }
            wp_cache_delete(self::CACHE_REGISTRY_KEY, $group);
        }

        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group($group);
        }
    }

    /**
     * Flush every cache group used by the plugin.
     *
     * @since 1.0.0
     * @return void
     */
    public static function opfw_cache_flush_all()
    {
        $groups = array(
            'opfw_settings',
            'opfw_dashboard',
            'opfw_products',
            'opfw_stocks',
            'opfw_adjustments',
            'opfw_pos',
            'opfw_accounting',
            'opfw_sales',
        );

        foreach ($groups as $group) {
            self::opfw_cache_flush_group($group);
        }
    }

    /**
     * Get a cached value or compute and store it on a cache miss.
     *
     * @since 1.0.0
     * @param string   $key      Cache key.
     * @param callable $callback Callback that produces the value.
     * @param string   $group    Cache group.
     * @return mixed
     */
    public static function opfw_cache_get_or_set($key, $callback, $group = self::CACHE_GROUP)
    {
        $cached = self::opfw_cache_get($key, $group);
        if (false !== $cached) {
            return $cached;
        }

        $data = call_user_func($callback);
        self::opfw_cache_set($key, $data, $group);

        return $data;
    }

    /**
     * Calculate VAT amount
     *
     * @since 1.0.0
     * @param float $amount The amount to calculate VAT for.
     * @return float
     */
    public static function opfw_calculate_vat($amount)
    {
        $vat_rate = floatval(get_option('opfw_vat_rate', '0'));
        return ($amount * $vat_rate) / 100;
    }

    /**
     * Calculate TAX amount
     *
     * @since 1.0.0
     * @param float $amount The amount to calculate TAX for.
     * @return float
     */
    public static function opfw_calculate_tax($amount)
    {
        $tax_rate = floatval(get_option('opfw_tax_rate', '0'));
        return ($amount * $tax_rate) / 100;
    }

    /**
     * Calculate total with VAT and TAX
     *
     * @since 1.0.0
     * @param float $subtotal The subtotal amount.
     * @return array
     */
    public static function opfw_calculate_totals($subtotal)
    {
        $vat_amount = self::opfw_calculate_vat($subtotal);
        $tax_amount = self::opfw_calculate_tax($subtotal);
        $total = $subtotal + $vat_amount + $tax_amount;

        return array(
            'subtotal' => $subtotal,
            'vat_amount' => $vat_amount,
            'tax_amount' => $tax_amount,
            'total' => $total
        );
    }

    /**
     * Check if VAT is enabled (rate > 0)
     *
     * @since 1.0.0
     * @return bool
     */
    public static function opfw_is_vat_enabled()
    {
        $vat_rate = floatval(get_option('opfw_vat_rate', '0'));
        return $vat_rate > 0;
    }

    /**
     * Check if TAX is enabled (rate > 0)
     *
     * @since 1.0.0
     * @return bool
     */
    public static function opfw_is_tax_enabled()
    {
        $tax_rate = floatval(get_option('opfw_tax_rate', '0'));
        return $tax_rate > 0;
    }

    /**
     * Get VAT rate
     *
     * @since 1.0.0
     * @return float
     */
    public static function opfw_get_vat_rate()
    {
        return floatval(get_option('opfw_vat_rate', '0'));
    }

    /**
     * Get TAX rate
     *
     * @since 1.0.0
     * @return float
     */
    public static function opfw_get_tax_rate()
    {
        return floatval(get_option('opfw_tax_rate', '0'));
    }

    /**
     * Format currency based on settings
     *
     * @since 1.0.0
     * @param float|string $amount The amount to format.
     * @return string
     */
    public static function opfw_format_currency($amount)
    {
        $settings = self::opfw_get_settings();
        $currency = html_entity_decode($settings['currency'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $position = $settings['currency_position'];

        $amount_formatted = number_format(floatval($amount), 2);

        switch ($position) {
            case 'right':
                return $amount_formatted . $currency;
            case 'left_space':
                return $currency . ' ' . $amount_formatted;
            case 'right_space':
                return $amount_formatted . ' ' . $currency;
            case 'left':
            default:
                return $currency . $amount_formatted;
        }
    }

    /**
     * Format date based on settings
     *
     * @since 1.0.0
     * @param string $date_string The date string to format.
     * @return string
     */
    public static function opfw_format_date($date_string)
    {
        $settings = self::opfw_get_settings();
        $date_format = $settings['date_format'];

        if (empty($date_string)) {
            return '';
        }

        $timestamp = strtotime($date_string);
        if (false === $timestamp) {
            return $date_string;
        }

        return gmdate($date_format, $timestamp);
    }

    /**
     * Get shop information
     *
     * @since 1.0.0
     * @return array
     */
    public static function opfw_get_shop_info()
    {
        $settings = self::opfw_get_settings();

        return array(
            'name' => $settings['shop_name'],
            'address' => $settings['shop_address'],
            'phone' => $settings['shop_phone'],
        );
    }

    /**
     * Get shop name with fallback
     *
     * @since 1.0.0
     * @return string
     */
    public static function opfw_get_shop_name()
    {
        $settings = self::opfw_get_settings();
        return !empty($settings['shop_name'])
            ? $settings['shop_name']
            : __('Restaurant POS', 'obydullah-restaurant-sales-terminal-for-woocommerce');
    }

    /**
     * Get currency symbol — falls back to WooCommerce currency if set
     *
     * @since 1.0.0
     * @return string
     */
    public static function opfw_get_currency_symbol()
    {
        if (function_exists('get_woocommerce_currency_symbol') && function_exists('get_woocommerce_currency')) {
            return get_woocommerce_currency_symbol(get_woocommerce_currency());
        }
        $settings = self::opfw_get_settings();
        return $settings['currency'];
    }

    /**
     * Get currency position
     *
     * @since 1.0.0
     * @return string
     */
    public static function opfw_get_currency_position()
    {
        $settings = self::opfw_get_settings();
        return $settings['currency_position'];
    }

    /**
     * Get date format
     *
     * @since 1.0.0
     * @return string
     */
    public static function opfw_get_date_format()
    {
        $settings = self::opfw_get_settings();
        return $settings['date_format'];
    }

    /**
     * Sanitize price input
     *
     * @since 1.0.0
     * @param mixed $price The price to sanitize.
     * @return float
     */
    public static function opfw_sanitize_price($price)
    {
        return floatval(preg_replace('/[^0-9.-]/', '', $price));
    }

    /**
     * Format price for display
     *
     * @since 1.0.0
     * @param float $price The price to format.
     * @return string
     */
    public static function opfw_format_price($price)
    {
        return number_format(floatval($price), 2, '.', '');
    }

    /**
     * Check if a string is a valid date
     *
     * @since 1.0.0
     * @param string $date Date string to check.
     * @param string $format Date format to check against.
     * @return bool
     */
    public static function opfw_is_valid_date($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Get current date in shop format
     *
     * @since 1.0.0
     * @return string
     */
    public static function opfw_get_current_date()
    {
        return self::opfw_format_date(current_time('mysql'));
    }

    /**
     * Get default settings
     *
     * @since 1.0.0
     * @return array
     */
    public static function opfw_get_default_settings()
    {
        return array(
            'date_format' => 'Y-m-d',
            'currency' => '$',
            'currency_position' => 'left',
            'shop_name' => '',
            'shop_address' => '',
            'shop_phone' => '',
            'vat_rate' => '0',
            'tax_rate' => '0',
        );
    }
}