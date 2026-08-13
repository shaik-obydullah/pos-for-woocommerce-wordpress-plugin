<?php
/**
 * Obydullah POS for WooCommerce Settings Class
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_Settings
{

    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings()
    {
        register_setting('opfw_settings_group', 'opfw_settings', array($this, 'sanitize_settings'));

        // General Settings Section
        add_settings_section(
            'opfw_general_section',
            __('General Settings', 'obydullah-pos-for-woocommerce'),
            array($this, 'general_section_callback'),
            'obydullah-pos-for-woocommerce-settings'
        );

        add_settings_field(
            'date_format',
            __('Date Format', 'obydullah-pos-for-woocommerce'),
            array($this, 'date_format_callback'),
            'obydullah-pos-for-woocommerce-settings',
            'opfw_general_section'
        );

        add_settings_field(
            'currency',
            __('Currency', 'obydullah-pos-for-woocommerce'),
            array($this, 'currency_callback'),
            'obydullah-pos-for-woocommerce-settings',
            'opfw_general_section'
        );

        add_settings_field(
            'currency_position',
            __('Currency Position', 'obydullah-pos-for-woocommerce'),
            array($this, 'currency_position_callback'),
            'obydullah-pos-for-woocommerce-settings',
            'opfw_general_section'
        );

        add_settings_field(
            'vat_rate',
            __('VAT Rate (%)', 'obydullah-pos-for-woocommerce'),
            array($this, 'vat_rate_callback'),
            'obydullah-pos-for-woocommerce-settings',
            'opfw_general_section'
        );

        add_settings_field(
            'tax_rate',
            __('Tax Rate (%)', 'obydullah-pos-for-woocommerce'),
            array($this, 'tax_rate_callback'),
            'obydullah-pos-for-woocommerce-settings',
            'opfw_general_section'
        );

        // Shop Information Section
        add_settings_section(
            'opfw_shop_section',
            __('Shop Information', 'obydullah-pos-for-woocommerce'),
            array($this, 'shop_section_callback'),
            'obydullah-pos-for-woocommerce-settings'
        );

        add_settings_field(
            'shop_name',
            __('Restaurant Name', 'obydullah-pos-for-woocommerce'),
            array($this, 'shop_name_callback'),
            'obydullah-pos-for-woocommerce-settings',
            'opfw_shop_section'
        );

        add_settings_field(
            'shop_address',
            __('Address', 'obydullah-pos-for-woocommerce'),
            array($this, 'shop_address_callback'),
            'obydullah-pos-for-woocommerce-settings',
            'opfw_shop_section'
        );

        add_settings_field(
            'shop_phone',
            __('Phone Number', 'obydullah-pos-for-woocommerce'),
            array($this, 'shop_phone_callback'),
            'obydullah-pos-for-woocommerce-settings',
            'opfw_shop_section'
        );
    }

    public function sanitize_settings($input)
    {
        $sanitized = array();

        // General Settings
        $sanitized['date_format'] = sanitize_text_field($input['date_format'] ?? 'Y-m-d');
        $sanitized['currency'] = sanitize_text_field($input['currency'] ?? '$');
        $sanitized['currency_position'] = sanitize_text_field($input['currency_position'] ?? 'left');
        $sanitized['vat_rate'] = floatval($input['vat_rate'] ?? '0');
        $sanitized['tax_rate'] = floatval($input['tax_rate'] ?? '0');

        // Shop Information
        $sanitized['shop_name'] = sanitize_text_field($input['shop_name'] ?? '');
        $sanitized['shop_address'] = sanitize_textarea_field($input['shop_address'] ?? '');
        $sanitized['shop_phone'] = sanitize_text_field($input['shop_phone'] ?? '');

        // Update individual options for helper class compatibility
        update_option('opfw_date_format', $sanitized['date_format']);
        update_option('opfw_currency', $sanitized['currency']);
        update_option('opfw_currency_position', $sanitized['currency_position']);
        update_option('opfw_vat_rate', $sanitized['vat_rate']);
        update_option('opfw_tax_rate', $sanitized['tax_rate']);
        update_option('opfw_shop_name', $sanitized['shop_name']);
        update_option('opfw_shop_address', $sanitized['shop_address']);
        update_option('opfw_shop_phone', $sanitized['shop_phone']);

        // Add settings updated notice
        add_settings_error(
            'opfw_settings',
            'opfw_settings_updated',
            __('Settings saved successfully.', 'obydullah-pos-for-woocommerce'),
            'success'
        );

        return $sanitized;
    }

    public function general_section_callback()
    {
        echo '<p class="text-muted">' . esc_html__('Configure general POS system settings.', 'obydullah-pos-for-woocommerce') . '</p>';
    }

    public function shop_section_callback()
    {
        echo '<p class="text-muted">' . esc_html__('Enter your restaurant/shop information that will be used on receipts and reports.', 'obydullah-pos-for-woocommerce') . '</p>';
    }

    public function date_format_callback()
    {
        $date_format = get_option('opfw_date_format', 'Y-m-d');

        $date_formats = array(
            'Y-m-d' => 'YYYY-MM-DD (2024-01-15)',
            'd/m/Y' => 'DD/MM/YYYY (15/01/2024)',
            'm/d/Y' => 'MM/DD/YYYY (01/15/2024)',
            'd-m-Y' => 'DD-MM-YYYY (15-01-2024)',
            'm-d-Y' => 'MM-DD-YYYY (01-15-2024)',
        );
        ?>
        <select name="opfw_settings[date_format]" class="form-control w-auto pr-5">
            <?php foreach ($date_formats as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($date_format, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="form-text text-muted mt-1">
            <?php esc_html_e('Select the date format to be used throughout the system.', 'obydullah-pos-for-woocommerce'); ?>
        </p>
        <?php
    }

    public function currency_callback()
    {
        $currency = get_option('opfw_currency', '$');

        $currencies = array(
            '$' => 'US Dollar ($)',
            '€' => 'Euro (€)',
            '£' => 'British Pound (£)',
            '৳' => 'Bangladeshi Taka (৳)',
            '¥' => 'Japanese Yen (¥)',
            '₹' => 'Indian Rupee (₹)',
            '₽' => 'Russian Ruble (₽)',
            '₩' => 'Korean Won (₩)',
            '₪' => 'Israeli Shekel (₪)',
            '₫' => 'Vietnamese Dong (₫)',
            '฿' => 'Thai Baht (฿)',
            '₱' => 'Philippine Peso (₱)',
        );
        ?>
        <select name="opfw_settings[currency]" class="form-control w-auto pr-5">
            <?php foreach ($currencies as $symbol => $label): ?>
                <option value="<?php echo esc_attr($symbol); ?>" <?php selected($currency, $symbol); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="form-text text-muted mt-1">
            <?php esc_html_e('Select the currency symbol for your pricing.', 'obydullah-pos-for-woocommerce'); ?>
        </p>
        <?php
    }

    public function currency_position_callback()
    {
        $position = get_option('opfw_currency_position', 'left');
        ?>
        <select name="opfw_settings[currency_position]" class="form-control w-auto">
            <option value="left" <?php selected($position, 'left'); ?>>
                <?php esc_html_e('Left ($100)', 'obydullah-pos-for-woocommerce'); ?>
            </option>
            <option value="right" <?php selected($position, 'right'); ?>>
                <?php esc_html_e('Right (100$)', 'obydullah-pos-for-woocommerce'); ?>
            </option>
            <option value="left_space" <?php selected($position, 'left_space'); ?>>
                <?php esc_html_e('Left with space ($ 100)', 'obydullah-pos-for-woocommerce'); ?>
            </option>
            <option value="right_space" <?php selected($position, 'right_space'); ?>>
                <?php esc_html_e('Right with space (100 $)', 'obydullah-pos-for-woocommerce'); ?>
            </option>
        </select>
        <p class="form-text text-muted mt-1">
            <?php esc_html_e('Choose where the currency symbol appears relative to the amount.', 'obydullah-pos-for-woocommerce'); ?>
        </p>
        <?php
    }

    public function vat_rate_callback()
    {
        $vat_rate = get_option('opfw_vat_rate', '0');
        ?>
        <div class="d-flex align-items-center gap-2">
            <input type="number" name="opfw_settings[vat_rate]" value="<?php echo esc_attr($vat_rate); ?>"
                step="0.01" min="0" max="100" class="form-control w-auto" />
            <span class="text-muted">%</span>
        </div>
        <p class="form-text text-muted mt-1">
            <?php esc_html_e('Enter the VAT rate as a percentage (e.g., 20 for 20%). Set to 0 to disable VAT.', 'obydullah-pos-for-woocommerce'); ?>
        </p>
        <?php
    }

    public function tax_rate_callback()
    {
        $tax_rate = get_option('opfw_tax_rate', '0');
        ?>
        <div class="d-flex align-items-center gap-2">
            <input type="number" name="opfw_settings[tax_rate]" value="<?php echo esc_attr($tax_rate); ?>"
                step="0.01" min="0" max="100" class="form-control w-auto" />
            <span class="text-muted">%</span>
        </div>
        <p class="form-text text-muted mt-1">
            <?php esc_html_e('Enter the general tax rate as a percentage (e.g., 8.5 for 8.5%). Set to 0 to disable tax.', 'obydullah-pos-for-woocommerce'); ?>
        </p>
        <?php
    }

    public function shop_name_callback()
    {
        $shop_name = get_option('opfw_shop_name', '');
        ?>
        <input type="text" name="opfw_settings[shop_name]" value="<?php echo esc_attr($shop_name); ?>"
            class="form-control w-50" placeholder="<?php esc_attr_e('Enter restaurant name', 'obydullah-pos-for-woocommerce'); ?>">
        <?php
    }

    public function shop_address_callback()
    {
        $shop_address = get_option('opfw_shop_address', '');
        ?>
        <textarea name="opfw_settings[shop_address]" rows="3" class="form-control w-75"
            placeholder="<?php esc_attr_e('Enter full address', 'obydullah-pos-for-woocommerce'); ?>"><?php echo esc_textarea($shop_address); ?></textarea>
        <?php
    }

    public function shop_phone_callback()
    {
        $shop_phone = get_option('opfw_shop_phone', '');
        ?>
        <input type="text" name="opfw_settings[shop_phone]" value="<?php echo esc_attr($shop_phone); ?>"
            class="form-control w-50" placeholder="<?php esc_attr_e('Enter phone number', 'obydullah-pos-for-woocommerce'); ?>">
        <?php
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'obydullah-pos-for-woocommerce'));
        }

        // Show settings errors (success messages)
        settings_errors('opfw_settings');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline mb-3"><?php esc_html_e('Restaurant POS Settings', 'obydullah-pos-for-woocommerce'); ?></h1>
            <hr class="wp-header-end">

            <div class="row">
                <!-- Settings Form -->
                <div class="col-lg-8">
                    <div class="bg-light p-4 rounded shadow-sm border mb-4">
                        <form method="post" action="options.php" class="mb-0">
                            <?php
                            settings_fields('opfw_settings_group');
                            do_settings_sections('obydullah-pos-for-woocommerce-settings');
                            ?>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <?php esc_html_e('Save Settings', 'obydullah-pos-for-woocommerce'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Settings Preview Section -->
                <div class="col-lg-4">
                    <div class="bg-light p-4 rounded shadow-sm border">
                        <h3 class="fs-5 fw-semibold mb-3"><?php esc_html_e('Settings Preview', 'obydullah-pos-for-woocommerce'); ?></h3>
                        
                        <div class="mb-3">
                            <p class="mb-1">
                                <strong><?php esc_html_e('Current Date Format:', 'obydullah-pos-for-woocommerce'); ?></strong>
                                <?php echo esc_html(Obydullah_POS_For_WooCommerce_Helpers::get_current_date()); ?>
                            </p>
                            <p class="mb-1">
                                <strong><?php esc_html_e('Currency Format:', 'obydullah-pos-for-woocommerce'); ?></strong>
                                <?php echo esc_html(Obydullah_POS_For_WooCommerce_Helpers::format_currency(100)); ?>
                            </p>
                            <p class="mb-1">
                                <strong><?php esc_html_e('VAT Rate:', 'obydullah-pos-for-woocommerce'); ?></strong>
                                <span class="badge bg-info"><?php echo esc_html(get_option('opfw_vat_rate', '0')); ?>%</span>
                                <?php if (Obydullah_POS_For_WooCommerce_Helpers::is_vat_enabled()): ?>
                                    <span class="badge bg-success ms-1"><?php esc_html_e('Enabled', 'obydullah-pos-for-woocommerce'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-1"><?php esc_html_e('Disabled', 'obydullah-pos-for-woocommerce'); ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="mb-1">
                                <strong><?php esc_html_e('Tax Rate:', 'obydullah-pos-for-woocommerce'); ?></strong>
                                <span class="badge bg-info"><?php echo esc_html(get_option('opfw_tax_rate', '0')); ?>%</span>
                                <?php
                                $tax_rate = floatval(get_option('opfw_tax_rate', '0'));
                                if ($tax_rate > 0): ?>
                                    <span class="badge bg-success ms-1"><?php esc_html_e('Enabled', 'obydullah-pos-for-woocommerce'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-1"><?php esc_html_e('Disabled', 'obydullah-pos-for-woocommerce'); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php if (Obydullah_POS_For_WooCommerce_Helpers::is_vat_enabled()): ?>
                            <div class="mb-3 p-3 bg-white rounded border">
                                <h4 class="fs-6 fw-semibold mb-2"><?php esc_html_e('VAT Calculation Example', 'obydullah-pos-for-woocommerce'); ?></h4>
                                <p class="mb-1">
                                    <small class="text-muted"><?php esc_html_e('On $100:', 'obydullah-pos-for-woocommerce'); ?></small>
                                </p>
                                <?php
                                $totals = Obydullah_POS_For_WooCommerce_Helpers::calculate_totals(100);
                                ?>
                                <p class="mb-0">
                                    <span class="text-success fw-bold"><?php echo esc_html(Obydullah_POS_For_WooCommerce_Helpers::format_currency($totals['total'])); ?></span>
                                    <small class="text-muted">(<?php echo esc_html(Obydullah_POS_For_WooCommerce_Helpers::format_currency($totals['subtotal'])); ?> + <?php echo esc_html(Obydullah_POS_For_WooCommerce_Helpers::format_currency($totals['vat_amount'])); ?> VAT)</small>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php
                        $tax_rate = floatval(get_option('opfw_tax_rate', '0'));
                        if ($tax_rate > 0): ?>
                            <div class="p-3 bg-white rounded border">
                                <h4 class="fs-6 fw-semibold mb-2"><?php esc_html_e('Tax Calculation Example', 'obydullah-pos-for-woocommerce'); ?></h4>
                                <p class="mb-1">
                                    <small class="text-muted"><?php esc_html_e('On $100:', 'obydullah-pos-for-woocommerce'); ?></small>
                                </p>
                                <?php
                                $tax_amount = (100 * $tax_rate) / 100;
                                $total_with_tax = 100 + $tax_amount;
                                ?>
                                <p class="mb-0">
                                    <span class="text-success fw-bold"><?php echo esc_html(Obydullah_POS_For_WooCommerce_Helpers::format_currency($total_with_tax)); ?></span>
                                    <small class="text-muted">(<?php echo esc_html(Obydullah_POS_For_WooCommerce_Helpers::format_currency(100)); ?> + <?php echo esc_html(Obydullah_POS_For_WooCommerce_Helpers::format_currency($tax_amount)); ?> Tax)</small>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}