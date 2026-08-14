<?php
/**
 * Point of Sales (POS) - WooCommerce Integrated
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since   2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_POS
{
    private $helpers;

    public function __construct()
    {
        $this->helpers = new Obydullah_POS_For_WooCommerce_Helpers();

        add_action('wp_ajax_opfw_get_categories_for_pos', [$this, 'opfw_ajax_get_categories_for_pos']);
        add_action('wp_ajax_opfw_get_products_by_category', [$this, 'opfw_ajax_get_products_by_category']);
        add_action('wp_ajax_opfw_get_customers_for_pos', [$this, 'opfw_ajax_get_customers_for_pos']);
        add_action('wp_ajax_opfw_process_sale', [$this, 'opfw_ajax_process_sale']);
        add_action('wp_ajax_opfw_get_saved_sales', [$this, 'opfw_ajax_get_saved_sales']);
        add_action('wp_ajax_opfw_load_saved_sale', [$this, 'opfw_ajax_load_saved_sale']);
        add_action('wp_ajax_opfw_delete_saved_sale', [$this, 'opfw_ajax_delete_saved_sale']);
    }

    public function opfw_render_page()
    {
        $currency = $this->helpers->opfw_get_currency_symbol();
        ?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Point of Sale (POS)', 'obydullah-pos-for-woocommerce'); ?></h1>
    <hr class="wp-header-end">

    <div class="row">
        <div class="col-lg-8">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="bg-light p-4 rounded shadow-sm">
                        <h3 class="mb-3 mt-1"><?php esc_html_e('Categories', 'obydullah-pos-for-woocommerce'); ?></h3>
                        <div class="opfw-categories-list" id="opfw-categories-list">
                            <button class="btn btn-outline-primary active mr-2 mb-2" data-category="all">
                                <?php esc_html_e('All Products', 'obydullah-pos-for-woocommerce'); ?>
                            </button>
                            <span class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="sr-only"><?php esc_html_e('Loading...', 'obydullah-pos-for-woocommerce'); ?></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="bg-light p-4 rounded shadow-sm">
                        <h3 class="mb-3"><?php esc_html_e('Products', 'obydullah-pos-for-woocommerce'); ?></h3>
                        <div class="opfw-stocks-grid" id="opfw-stocks-grid">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only"><?php esc_html_e('Loading...', 'obydullah-pos-for-woocommerce'); ?></span>
                                </div>
                                <p class="mt-2"><?php esc_html_e('Loading products...', 'obydullah-pos-for-woocommerce'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="bg-light p-4 rounded shadow-sm">
                <div class="form-group mb-3">
                    <label for="opfw-customer" class="form-label"><?php esc_html_e('Customer:', 'obydullah-pos-for-woocommerce'); ?></label>
                    <select id="opfw-customer" class="form-control">
                        <option value=""><?php esc_html_e('Walk-in Customer', 'obydullah-pos-for-woocommerce'); ?></option>
                    </select>
                </div>

                <div class="pos-order-tabs mb-3">
                    <div class="pos-tab-buttons btn-group btn-group-toggle w-100 mb-3">
                        <label class="btn btn-outline-primary active">
                            <input type="radio" name="order-type" value="dineIn" checked>
                            <?php esc_html_e('Dine In', 'obydullah-pos-for-woocommerce'); ?>
                        </label>
                        <label class="btn btn-outline-primary">
                            <input type="radio" name="order-type" value="takeAway">
                            <?php esc_html_e('Take Away', 'obydullah-pos-for-woocommerce'); ?>
                        </label>
                        <label class="btn btn-outline-primary">
                            <input type="radio" name="order-type" value="pickup">
                            <?php esc_html_e('Pickup', 'obydullah-pos-for-woocommerce'); ?>
                        </label>
                    </div>

                    <div id="dineInOptions" class="pos-tab-content">
                        <div class="form-group mb-2">
                            <label class="form-label"><?php esc_html_e('Table Number', 'obydullah-pos-for-woocommerce'); ?></label>
                            <input type="text" id="table-number" class="form-control form-control-sm"
                                placeholder="<?php esc_attr_e('Enter table number', 'obydullah-pos-for-woocommerce'); ?>">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label"><?php esc_html_e('Cooking Instructions', 'obydullah-pos-for-woocommerce'); ?></label>
                            <textarea id="dinein-instructions" class="form-control form-control-sm" rows="2"
                                placeholder="<?php esc_attr_e('Add special cooking instructions...', 'obydullah-pos-for-woocommerce'); ?>"></textarea>
                        </div>
                    </div>

                    <div id="takeAwayOptions" class="pos-tab-content opfw-hidden">
                        <div class="form-group mb-2">
                            <label class="form-label"><?php esc_html_e('Customer Name', 'obydullah-pos-for-woocommerce'); ?></label>
                            <input type="text" id="takeaway-name" class="form-control form-control-sm"
                                placeholder="<?php esc_attr_e('Enter customer name', 'obydullah-pos-for-woocommerce'); ?>">
                        </div>
                        <div class="form-group mb-2">
                            <label class="form-label"><?php esc_html_e('Delivery Address', 'obydullah-pos-for-woocommerce'); ?></label>
                            <textarea id="takeaway-address" class="form-control form-control-sm" rows="2"
                                placeholder="<?php esc_attr_e('Enter delivery address', 'obydullah-pos-for-woocommerce'); ?>"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="form-label"><?php esc_html_e('Email', 'obydullah-pos-for-woocommerce'); ?></label>
                                    <input type="email" id="takeaway-email" class="form-control form-control-sm"
                                        placeholder="<?php esc_attr_e('Enter email address', 'obydullah-pos-for-woocommerce'); ?>">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group mb-2">
                                    <label class="form-label"><?php esc_html_e('Mobile', 'obydullah-pos-for-woocommerce'); ?></label>
                                    <input type="text" id="takeaway-mobile" class="form-control form-control-sm"
                                        placeholder="<?php esc_attr_e('Enter mobile number', 'obydullah-pos-for-woocommerce'); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label"><?php esc_html_e('Cooking Instructions', 'obydullah-pos-for-woocommerce'); ?></label>
                            <textarea id="takeaway-instructions" class="form-control form-control-sm" rows="2"
                                placeholder="<?php esc_attr_e('Enter Cooking Instructions', 'obydullah-pos-for-woocommerce'); ?>"></textarea>
                        </div>
                    </div>

                    <div id="pickupOptions" class="pos-tab-content opfw-hidden">
                        <div class="form-group mb-2">
                            <label class="form-label"><?php esc_html_e('Customer Name', 'obydullah-pos-for-woocommerce'); ?></label>
                            <input type="text" id="pickup-name" class="form-control form-control-sm"
                                placeholder="<?php esc_attr_e('Enter customer name', 'obydullah-pos-for-woocommerce'); ?>">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label"><?php esc_html_e('Mobile', 'obydullah-pos-for-woocommerce'); ?></label>
                            <input type="tel" id="pickup-mobile" class="form-control form-control-sm"
                                placeholder="<?php esc_attr_e('Enter mobile number', 'obydullah-pos-for-woocommerce'); ?>">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <h4><?php esc_html_e('Cart', 'obydullah-pos-for-woocommerce'); ?></h4>
                    <div class="opfw-cart-items" id="opfw-cart-items">
                        <div class="text-center py-3 text-muted"><?php esc_html_e('Cart is empty', 'obydullah-pos-for-woocommerce'); ?></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="bg-white p-3 rounded border">
                        <table class="table table-sm table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="pl-0"><?php esc_html_e('Subtotal:', 'obydullah-pos-for-woocommerce'); ?></td>
                                    <td class="text-right pr-0 font-weight-bold" id="opfw-subtotal"><?php echo esc_html($this->helpers->opfw_format_currency(0)); ?></td>
                                </tr>
                                <tr>
                                    <td class="pl-0"><?php esc_html_e('Discount:', 'obydullah-pos-for-woocommerce'); ?></td>
                                    <td class="text-right pr-0">
                                        <input type="number" id="opfw-discount" class="form-control form-control-sm d-inline-block" value="0" min="0" step="0.01">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pl-0"><?php esc_html_e('Delivery:', 'obydullah-pos-for-woocommerce'); ?></td>
                                    <td class="text-right pr-0">
                                        <input type="number" id="opfw-delivery" class="form-control form-control-sm d-inline-block" value="0" min="0" step="0.01">
                                    </td>
                                </tr>
                                <?php if ($this->helpers->opfw_is_tax_enabled()): ?>
                                <tr>
                                    <td class="pl-0"><?php esc_html_e('Tax:', 'obydullah-pos-for-woocommerce'); ?></td>
                                    <td class="text-right pr-0" id="opfw-tax"><?php echo esc_html($this->helpers->opfw_format_currency(0)); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($this->helpers->opfw_is_vat_enabled()): ?>
                                <tr>
                                    <td class="pl-0"><?php esc_html_e('VAT:', 'obydullah-pos-for-woocommerce'); ?></td>
                                    <td class="text-right pr-0" id="opfw-vat"><?php echo esc_html($this->helpers->opfw_format_currency(0)); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="border-top">
                                    <td class="pl-0 pt-2"><strong><?php esc_html_e('Total:', 'obydullah-pos-for-woocommerce'); ?></strong></td>
                                    <td class="text-right pr-0 pt-2"><strong id="opfw-grand-total" class="text-primary"><?php echo esc_html($this->helpers->opfw_format_currency(0)); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="row">
                        <div class="col-4">
                            <button type="button" id="opfw-clear-cart" class="btn btn-outline-danger btn-block"><?php esc_html_e('Clear', 'obydullah-pos-for-woocommerce'); ?></button>
                        </div>
                        <div class="col-4">
                            <button type="button" id="opfw-save-sale" class="btn btn-outline-primary btn-block"><?php esc_html_e('Save', 'obydullah-pos-for-woocommerce'); ?></button>
                        </div>
                        <div class="col-4">
                            <button type="button" id="opfw-complete-sale" class="btn btn-primary btn-block"><?php esc_html_e('Complete', 'obydullah-pos-for-woocommerce'); ?></button>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="opfw-notes" class="form-label"><?php esc_html_e('Notes:', 'obydullah-pos-for-woocommerce'); ?></label>
                    <textarea id="opfw-notes" class="form-control form-control-sm" rows="2"
                        placeholder="<?php esc_attr_e('Add any notes here...', 'obydullah-pos-for-woocommerce'); ?>"></textarea>
                </div>

                <div class="mt-3">
                    <h5 class="mb-2"><?php esc_html_e('Saved Sales', 'obydullah-pos-for-woocommerce'); ?></h5>
                    <button type="button" id="opfw-load-saved" class="btn btn-outline-secondary btn-sm mb-2">
                        <?php esc_html_e('Refresh List', 'obydullah-pos-for-woocommerce'); ?>
                    </button>
                    <div class="opfw-saved-list" id="opfw-saved-list"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="opfw-current-sale-id" value="">
<?php
    }

    public function opfw_ajax_get_categories_for_pos()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_get_categories_for_pos')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'fields' => 'all',
        ]);

        $categories = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $categories[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                ];
            }
        }

        wp_send_json_success($categories);
    }

    public function opfw_ajax_get_customers_for_pos()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_get_customers_for_pos')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        $customers = get_users(['role__in' => ['customer', 'subscriber'], 'fields' => 'all']);
        $formatted = [];

        foreach ($customers as $customer) {
            $formatted[] = [
                'id' => $customer->ID,
                'name' => $customer->display_name,
                'email' => $customer->user_email,
                'mobile' => get_user_meta($customer->ID, 'billing_phone', true),
                'address' => get_user_meta($customer->ID, 'billing_address_1', true),
            ];
        }

        wp_send_json_success($formatted);
    }

    public function opfw_ajax_get_products_by_category()
    {
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_get_products_by_category')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $category_id = sanitize_text_field(wp_unslash($_GET['category_id'] ?? 'all'));

        $args = [
            'status' => 'publish',
            'limit' => 50,
            'return' => 'objects',
            'stock_status' => 'instock',
        ];

        if ($category_id !== 'all') {
            $args['category'] = [get_term(absint($category_id), 'product_cat')->slug ?? ''];
        }

        $products = wc_get_products($args);
        $formatted = [];

        foreach ($products as $product) {
            $price = $product->get_price();
            $formatted[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => $product->get_image('woocommerce_thumbnail'),
                'sale_cost' => $price,
                'quantity' => $product->get_stock_quantity() ?: 0,
                'stock_status' => $product->get_stock_status(),
            ];
        }

        wp_send_json_success($formatted);
    }

    public function opfw_ajax_get_saved_sales()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_get_saved_sales')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        $orders = wc_get_orders([
            'status' => ['draft', 'pending'],
            'limit' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_pos_order',
                    'value' => 'yes',
                    'compare' => '=',
                ],
            ],
        ]);

        $formatted = [];
        foreach ($orders as $order) {
            $formatted[] = [
                'id' => $order->get_id(),
                'invoice_id' => $order->get_meta('_invoice_id'),
                'grand_total' => $order->get_total(),
                'created_at' => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
                'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            ];
        }

        wp_send_json_success($formatted);
    }

    public function opfw_ajax_load_saved_sale()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_load_saved_sale')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        $order_id = intval($_GET['sale_id'] ?? 0);
        if (!$order_id) {
            wp_send_json_error('Invalid order ID');
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Order not found');
        }

        $items = [];
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);
            $items[] = [
                'fk_product_id' => $product_id,
                'product_name' => $item->get_name(),
                'unit_price' => floatval($item->get_total()) / max(1, $item->get_quantity()),
                'quantity' => $item->get_quantity(),
                'stock_quantity' => $product ? ($product->get_stock_quantity() ?: 0) : 0,
            ];
        }

        wp_send_json_success([
            'sale' => [
                'id' => $order->get_id(),
                'invoice_id' => $order->get_meta('_invoice_id'),
                'grand_total' => $order->get_total(),
                'status' => $order->get_status(),
            ],
            'items' => $items,
        ]);
    }

    public function opfw_ajax_delete_saved_sale()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_delete_saved_sale')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        $order_id = intval($_POST['sale_id'] ?? 0);
        if (!$order_id) {
            wp_send_json_error(['message' => __('Invalid order ID', 'obydullah-pos-for-woocommerce')]);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => __('Order not found', 'obydullah-pos-for-woocommerce')]);
        }

        $invoice_id = $order->get_meta('_invoice_id');
        $order->delete(true);

        wp_send_json_success([
            'message' => __('Saved sale deleted successfully', 'obydullah-pos-for-woocommerce'),
            'deleted_id' => $order_id,
            'invoice_id' => $invoice_id,
        ]);
    }

    public function opfw_ajax_process_sale()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_process_sale')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        try {
            if (!isset($_POST['sale_data']) || empty($_POST['sale_data'])) {
                throw new Exception(__('Sale data is required', 'obydullah-pos-for-woocommerce'));
            }

            $data = json_decode(sanitize_text_field(wp_unslash($_POST['sale_data'])), true);
            if (!$data || !is_array($data) || empty($data['items'])) {
                throw new Exception(__('Invalid sale data', 'obydullah-pos-for-woocommerce'));
            }

            $action = $data['action'] ?? 'save';
            $is_updating = !empty($data['saved_sale_id']);
            $order_id = $is_updating ? intval($data['saved_sale_id']) : 0;

            if ($is_updating) {
                $order = wc_get_order($order_id);
                if (!$order) {
                    throw new Exception(__('Saved sale not found', 'obydullah-pos-for-woocommerce'));
                }
                $order->remove_order_items();
                $order->save();
            } else {
                $order = wc_create_order();
                if (is_wp_error($order)) {
                    throw new Exception($order->get_error_message());
                }
                $order_id = $order->get_id();
            }

            $invoice_id = $order->get_meta('_invoice_id');
            if (empty($invoice_id)) {
                $invoice_id = 'INV-' . gmdate('Ymd') . '-' . str_pad(wp_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $order->update_meta_data('_invoice_id', $invoice_id);
            }

            $order->update_meta_data('_pos_order', 'yes');
            $order->update_meta_data('_order_type', sanitize_text_field($data['order_type'] ?? 'dineIn'));

            if (!empty($data['cooking_instructions'])) {
                $order->update_meta_data('_cooking_instructions', sanitize_textarea_field($data['cooking_instructions']));
            }

            if (!empty($data['table_number'])) {
                $order->update_meta_data('_table_number', sanitize_text_field($data['table_number']));
            }

            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity']);
                $price = floatval($item['price']);

                $product = wc_get_product($product_id);
                if (!$product) {
                    throw new Exception(sprintf(__('Product not found: %d', 'obydullah-pos-for-woocommerce'), $product_id));
                }

                if ($action === 'complete' && $product->get_manage_stock() && $product->get_stock_quantity() < $quantity) {
                    throw new Exception(sprintf(
                        __('Insufficient stock for: %1$s. Available: %2$d', 'obydullah-pos-for-woocommerce'),
                        $product->get_name(),
                        $product->get_stock_quantity()
                    ));
                }

                $order->add_product($product, $quantity, [
                    'subtotal' => $price * $quantity,
                    'total' => $price * $quantity,
                ]);
                $subtotal += $price * $quantity;
            }

            $discount = floatval($data['discount'] ?? 0);
            $delivery_cost = floatval($data['delivery_cost'] ?? 0);
            $taxable_amount = $subtotal - $discount;

            $vat_amount = $this->helpers->opfw_is_vat_enabled() ? ($taxable_amount * $this->helpers->opfw_get_vat_rate() / 100) : 0;
            $tax_amount = $this->helpers->opfw_is_tax_enabled() ? ($taxable_amount * $this->helpers->opfw_get_tax_rate() / 100) : 0;

            if ($delivery_cost > 0) {
                $order->set_shipping_total($delivery_cost);
                $shipping_item = new WC_Order_Item_Shipping();
                $shipping_item->set_method_title(__('Delivery', 'obydullah-pos-for-woocommerce'));
                $shipping_item->set_method_id('delivery');
                $shipping_item->set_total($delivery_cost);
                $order->add_item($shipping_item);
            }

            if ($vat_amount > 0) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name('VAT');
                $fee->set_total($vat_amount);
                $order->add_item($fee);
            }

            if ($tax_amount > 0) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name('Tax');
                $fee->set_total($tax_amount);
                $order->add_item($fee);
            }

            $customer_id = intval($data['customer_id'] ?? 0);
            if ($customer_id > 0) {
                $order->set_customer_id($customer_id);
                $user = get_user_by('ID', $customer_id);
                if ($user) {
                    $order->set_billing_first_name($user->first_name ? $user->first_name : $user->display_name);
                    $order->set_billing_last_name($user->last_name);
                    $order->set_billing_email($user->user_email);
                    $order->set_billing_phone(get_user_meta($customer_id, 'billing_phone', true));
                    $order->set_billing_address_1(get_user_meta($customer_id, 'billing_address_1', true));
                    $order->set_billing_address_2(get_user_meta($customer_id, 'billing_address_2', true));
                    $order->set_billing_city(get_user_meta($customer_id, 'billing_city', true));
                    $order->set_billing_postcode(get_user_meta($customer_id, 'billing_postcode', true));
                }
            } else {
                $order->set_billing_first_name('Walk-in Customer');
            }

            if (!empty($data['note'])) {
                $order->set_customer_note(sanitize_textarea_field($data['note']));
            }

            $order->opfw_calculate_totals();

            if ($discount > 0) {
                $order->set_discount_total($discount);
                $order->set_total(max(0, $order->get_total() - $discount));
            }

            $order->save();

            if ($action === 'complete') {
                foreach ($order->get_items() as $item) {
                    $pid = $item->get_product_id();
                    $qty = $item->get_quantity();
                    $product = wc_get_product($pid);
                    if ($product && $product->get_manage_stock()) {
                        wc_update_product_stock($product, $qty, 'decrease');
                    }
                }

                $order->payment_complete();
                $order->update_status('completed');

                global $wpdb;
                $accounting_table = $wpdb->prefix . 'opfw_accounting';
                $wpdb->insert($accounting_table, [
                    'in_amount' => floatval($order->get_total()),
                    'description' => $invoice_id,
                    'created_at' => current_time('mysql'),
                ], ['%f', '%s', '%s']);
            } else {
                $order->update_status('pending');
            }

            $order->save();

            wp_send_json_success([
                'sale_id' => $order_id,
                'invoice_id' => $invoice_id,
                'message' => $action === 'complete'
                    ? __('Sale completed successfully!', 'obydullah-pos-for-woocommerce')
                    : __('Sale saved successfully!', 'obydullah-pos-for-woocommerce'),
            ]);

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}
