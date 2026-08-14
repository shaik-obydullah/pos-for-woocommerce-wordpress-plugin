<?php
/**
 * WooCommerce Stock Management for POS
 *
 * Manages stock through WooCommerce's built-in stock system.
 * Logs adjustments in a custom table.
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since   2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_Woo_Stock
{
    private $adjustment_log_table;

    public function __construct()
    {
        global $wpdb;
        $this->adjustment_log_table = $wpdb->prefix . 'opfw_stock_adjustment_log';

        add_action('wp_ajax_opfw_get_stocks', [$this, 'ajax_get_stocks']);
        add_action('wp_ajax_opfw_update_stock', [$this, 'ajax_update_stock']);
        add_action('wp_ajax_opfw_get_products_for_stocks', [$this, 'ajax_get_products_for_stocks']);
        add_action('wp_ajax_opfw_get_stock_adjustments', [$this, 'ajax_get_adjustments']);
        add_action('wp_ajax_opfw_add_stock_adjustment', [$this, 'ajax_add_adjustment']);
        add_action('wp_ajax_opfw_delete_stock_adjustment', [$this, 'ajax_delete_adjustment']);
        add_action('wp_ajax_opfw_get_products_for_adjustments', [$this, 'ajax_get_products_for_adjustments']);
        add_action('wp_ajax_opfw_get_current_stock', [$this, 'ajax_get_current_stock']);
    }

    public function render_stock_page()
    {
        ?>
<div class="wrap opfw-stocks-page">
    <h1 class="wp-heading-inline mb-3">
        <?php esc_html_e('Stock Management', 'obydullah-pos-for-woocommerce'); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="row mb-4">
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="stock-summary-card text-center">
                <h3 class="text-muted"><?php esc_html_e('In Stock', 'obydullah-pos-for-woocommerce'); ?></h3>
                <p id="in-stock-count" class="summary-number text-primary">0</p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="stock-summary-card text-center">
                <h3 class="text-muted"><?php esc_html_e('Out of Stock', 'obydullah-pos-for-woocommerce'); ?></h3>
                <p id="out-stock-count" class="summary-number text-danger">0</p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="stock-summary-card text-center">
                <h3 class="text-muted"><?php esc_html_e('Low Stock', 'obydullah-pos-for-woocommerce'); ?></h3>
                <p id="low-stock-count" class="summary-number text-warning">0</p>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="stock-summary-card text-center">
                <h3 class="text-muted"><?php esc_html_e('Total Products', 'obydullah-pos-for-woocommerce'); ?></h3>
                <p id="total-stocks-count" class="summary-number text-info">0</p>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="bg-light p-4 rounded shadow-sm">
                <h2 class="mb-3 mt-1"><?php esc_html_e('Update Stock', 'obydullah-pos-for-woocommerce'); ?></h2>
                <form id="update-stock-form" method="post">
                    <?php wp_nonce_field('opfw_update_stock', 'stock_nonce'); ?>

                    <div class="mb-3">
                        <label for="stock-product" class="form-label d-block mb-1">
                            <?php esc_html_e('Product', 'obydullah-pos-for-woocommerce'); ?> <span class="text-danger">*</span>
                        </label>
                        <select name="product_id" id="stock-product" class="form-control" required>
                            <option value=""><?php esc_html_e('Select Product', 'obydullah-pos-for-woocommerce'); ?></option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="buy-price" class="form-label d-block mb-1">
                                <?php esc_html_e('Buy Price', 'obydullah-pos-for-woocommerce'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input name="buy_price" id="buy-price" type="number" step="0.01" min="0" value="0.00"
                                class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="sale-price" class="form-label d-block mb-1">
                                <?php esc_html_e('Sale Price', 'obydullah-pos-for-woocommerce'); ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input name="sale_price" id="sale-price" type="number" step="0.01" min="0" value="0.00"
                                class="form-control" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="stock-quantity" class="form-label d-block mb-1">
                                <?php esc_html_e('Quantity', 'obydullah-pos-for-woocommerce'); ?> <span class="text-danger">*</span>
                            </label>
                            <input name="quantity" id="stock-quantity" type="number" min="0" value="0" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="stock-status" class="form-label d-block mb-1">
                                <?php esc_html_e('Stock Status', 'obydullah-pos-for-woocommerce'); ?>
                            </label>
                            <select name="stock_status" id="stock-status" class="form-control">
                                <option value="instock"><?php esc_html_e('In Stock', 'obydullah-pos-for-woocommerce'); ?></option>
                                <option value="outofstock"><?php esc_html_e('Out of Stock', 'obydullah-pos-for-woocommerce'); ?></option>
                                <option value="onbackorder"><?php esc_html_e('On Backorder', 'obydullah-pos-for-woocommerce'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-white p-3 border rounded mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="font-weight-bold"><?php esc_html_e('Profit Margin:', 'obydullah-pos-for-woocommerce'); ?></span>
                            <span id="profit-margin" class="profit-value font-weight-bold">0.00%</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold"><?php esc_html_e('Total Profit:', 'obydullah-pos-for-woocommerce'); ?></span>
                            <span id="total-profit" class="profit-value font-weight-bold">0.00</span>
                        </div>
                    </div>

                    <div class="d-flex mt-4">
                        <button type="submit" id="submit-stock" class="btn-primary mr-2">
                            <span class="btn-text"><?php esc_html_e('Update Stock', 'obydullah-pos-for-woocommerce'); ?></span>
                            <span class="spinner" style="display: none; margin-left: 5px;"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="bg-light p-3 rounded shadow-sm border">
                <h2 class="h5 mb-3 fw-semibold"><?php esc_html_e('Stock Overview', 'obydullah-pos-for-woocommerce'); ?></h2>

                <div class="search-section mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="search-group flex-grow-1">
                            <div class="position-relative flex-grow-1">
                                <input type="text" id="stock-search" class="form-control form-control-sm"
                                    placeholder="<?php esc_attr_e('Search products...', 'obydullah-pos-for-woocommerce'); ?>">
                            </div>
                        </div>
                        <div>
                            <select id="status-filter" class="form-control form-control-sm">
                                <option value=""><?php esc_html_e('All Status', 'obydullah-pos-for-woocommerce'); ?></option>
                                <option value="instock"><?php esc_html_e('In Stock', 'obydullah-pos-for-woocommerce'); ?></option>
                                <option value="outofstock"><?php esc_html_e('Out of Stock', 'obydullah-pos-for-woocommerce'); ?></option>
                                <option value="onbackorder"><?php esc_html_e('On Backorder', 'obydullah-pos-for-woocommerce'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered mb-2">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th><?php esc_html_e('Product', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="100"><?php esc_html_e('Buy Price', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="100"><?php esc_html_e('Sale Price', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="100"><?php esc_html_e('Quantity', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="100"><?php esc_html_e('Status', 'obydullah-pos-for-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="stock-list" class="bg-white">
                            <tr>
                                <td colspan="5" class="text-center p-4">
                                    <span class="spinner is-active"></span>
                                    <?php esc_html_e('Loading...', 'obydullah-pos-for-woocommerce'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                    <div class="tablenav-pages">
                        <span class="displaying-num" id="displaying-num">0 <?php esc_html_e('items', 'obydullah-pos-for-woocommerce'); ?></span>
                        <span class="pagination-links ms-2">
                            <a class="first-page btn btn-sm btn-dark" href="#">&laquo;</a>
                            <a class="prev-page btn btn-sm btn-dark" href="#">&lsaquo;</a>
                            <span class="paging-input">
                                <input class="current-page form-control form-control-sm" id="current-page-selector" type="text" name="paged" value="1">
                                <span class="tablenav-paging-text"><?php esc_html_e('of', 'obydullah-pos-for-woocommerce'); ?> <span class="total-pages">1</span></span>
                            </span>
                            <a class="next-page btn btn-sm btn-dark" href="#">&rsaquo;</a>
                            <a class="last-page btn btn-sm btn-dark" href="#">&raquo;</a>
                        </span>
                    </div>
                    <div class="tablenav-pages">
                        <select id="per-page-select" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    }

    public function render_adjustments_page()
    {
        ?>
<div class="wrap">
    <h1 class="wp-heading-inline mb-4">
        <?php esc_html_e('Stock Adjustments', 'obydullah-pos-for-woocommerce'); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="row">
        <div class="col-md-4">
            <div class="bg-light p-4 rounded shadow-sm mb-4">
                <h2 class="h4 mb-3 mt-1"><?php esc_html_e('New Stock Adjustment', 'obydullah-pos-for-woocommerce'); ?></h2>
                <form id="add-adjustment-form" method="post">
                    <?php wp_nonce_field('opfw_add_stock_adjustment', 'adjustment_nonce'); ?>

                    <div class="form-group mb-3">
                        <label for="adjustment-product" class="form-label fw-semibold">
                            <?php esc_html_e('Product', 'obydullah-pos-for-woocommerce'); ?> <span class="text-danger">*</span>
                        </label>
                        <select name="product_id" id="adjustment-product" class="form-control" required>
                            <option value=""><?php esc_html_e('Select Product', 'obydullah-pos-for-woocommerce'); ?></option>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="adjustment-type" class="form-label fw-semibold">
                                <?php esc_html_e('Type', 'obydullah-pos-for-woocommerce'); ?> <span class="text-danger">*</span>
                            </label>
                            <select name="adjustment_type" id="adjustment-type" class="form-control" required>
                                <option value="increase"><?php esc_html_e('Increase', 'obydullah-pos-for-woocommerce'); ?></option>
                                <option value="decrease"><?php esc_html_e('Decrease', 'obydullah-pos-for-woocommerce'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="adjustment-quantity" class="form-label fw-semibold">
                                <?php esc_html_e('Quantity', 'obydullah-pos-for-woocommerce'); ?> <span class="text-danger">*</span>
                            </label>
                            <input name="quantity" id="adjustment-quantity" type="number" min="1" value="1" class="form-control" required>
                        </div>
                    </div>

                    <div class="alert alert-light border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-dark"><?php esc_html_e('Current Stock:', 'obydullah-pos-for-woocommerce'); ?></span>
                            <span id="current-stock" class="fw-bold text-dark ml-1">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-dark"><?php esc_html_e('Adjustment:', 'obydullah-pos-for-woocommerce'); ?></span>
                            <span id="adjustment-display" class="fw-bold text-success ml-1">+0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-dark"><?php esc_html_e('New Stock:', 'obydullah-pos-for-woocommerce'); ?></span>
                            <span id="new-stock" class="fw-bold text-danger ml-1">0</span>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="adjustment-note" class="form-label fw-semibold">
                            <?php esc_html_e('Note', 'obydullah-pos-for-woocommerce'); ?>
                        </label>
                        <textarea name="note" id="adjustment-note" rows="3" class="form-control"
                            placeholder="<?php esc_attr_e('Reason for adjustment...', 'obydullah-pos-for-woocommerce'); ?>"></textarea>
                    </div>

                    <div class="mt-4">
                        <button type="submit" id="submit-adjustment" class="btn btn-primary w-100">
                            <span class="btn-text"><?php esc_html_e('Apply Adjustment', 'obydullah-pos-for-woocommerce'); ?></span>
                            <span class="spinner" style="display:none;"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="bg-light p-3 rounded shadow-sm border">
                <h2 class="h5 mb-3 fw-semibold"><?php esc_html_e('Adjustments History', 'obydullah-pos-for-woocommerce'); ?></h2>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered mb-2">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th><?php esc_html_e('Date', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th><?php esc_html_e('Product', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th><?php esc_html_e('Type', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th><?php esc_html_e('Quantity', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th><?php esc_html_e('Old Qty', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th><?php esc_html_e('New Qty', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th><?php esc_html_e('Note', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th class="text-right"><?php esc_html_e('Actions', 'obydullah-pos-for-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="adjustment-list" class="bg-white">
                            <tr>
                                <td colspan="8" class="text-center p-4">
                                    <span class="spinner is-active"></span>
                                    <?php esc_html_e('Loading...', 'obydullah-pos-for-woocommerce'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                    <div class="tablenav-pages">
                        <span class="displaying-num" id="displaying-num">0 <?php esc_html_e('items', 'obydullah-pos-for-woocommerce'); ?></span>
                        <span class="pagination-links ms-2">
                            <a class="first-page btn btn-sm btn-dark" href="#">&laquo;</a>
                            <a class="prev-page btn btn-sm btn-dark" href="#">&lsaquo;</a>
                            <span class="paging-input">
                                <input class="current-page form-control form-control-sm" id="current-page-selector" type="text" name="paged" value="1">
                                <span class="tablenav-paging-text"><?php esc_html_e('of', 'obydullah-pos-for-woocommerce'); ?> <span class="total-pages">1</span></span>
                            </span>
                            <a class="next-page btn btn-sm btn-dark" href="#">&rsaquo;</a>
                            <a class="last-page btn btn-sm btn-dark" href="#">&raquo;</a>
                        </span>
                    </div>
                    <div class="tablenav-pages">
                        <select id="per-page-select" class="form-control form-control-sm">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    }

    public function ajax_get_products_for_stocks()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'opfw_get_products_for_stocks')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        $products = wc_get_products([
            'status' => 'publish',
            'limit' => -1,
            'return' => 'objects',
        ]);

        $formatted = [];
        foreach ($products as $product) {
            $formatted[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'manage_stock' => $product->get_manage_stock(),
                'buy_price' => get_post_meta($product->get_id(), '_opfw_buy_price', true) ?: '0.00',
                'sale_price' => $product->get_regular_price() ?: '0.00',
                'stock_quantity' => $product->get_stock_quantity() ?: 0,
            ];
        }

        wp_send_json_success($formatted);
    }

    public function ajax_get_stocks()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'opfw_get_stocks')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $offset = ($page - 1) * $per_page;

        $args = [
            'status' => 'publish',
            'limit' => $per_page,
            'offset' => $offset,
            'return' => 'objects',
        ];

        if (!empty($search)) {
            $args['search'] = $search;
        }

        $all_products = wc_get_products(array_merge($args, ['limit' => -1, 'return' => 'ids', 'offset' => 0]));
        $total_items = is_array($all_products) ? count($all_products) : 0;
        $total_pages = max(1, ceil($total_items / $per_page));

        $products = wc_get_products($args);
        $stocks = [];

        foreach ($products as $product) {
            $stock_status = $product->get_stock_status();
            if (!empty($status_filter) && $stock_status !== $status_filter) {
                continue;
            }

            $stocks[] = [
                'id' => $product->get_id(),
                'product_name' => $product->get_name(),
                'buy_price' => get_post_meta($product->get_id(), '_opfw_buy_price', true) ?: '0.00',
                'sale_price' => $product->get_regular_price() ?: '0.00',
                'quantity' => $product->get_stock_quantity() ?: 0,
                'status' => $stock_status,
                'manage_stock' => $product->get_manage_stock(),
            ];
        }

        wp_send_json_success([
            'stocks' => $stocks,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_items,
                'total_pages' => $total_pages,
            ],
        ]);
    }

    public function ajax_update_stock()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'opfw_update_stock')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        $product_id = intval($_POST['product_id'] ?? 0);
        $buy_price = floatval($_POST['buy_price'] ?? 0);
        $sale_price = floatval($_POST['sale_price'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $stock_status = sanitize_text_field(wp_unslash($_POST['stock_status'] ?? 'instock'));

        if ($quantity < 0) {
            wp_send_json_error(__('Quantity cannot be negative', 'obydullah-pos-for-woocommerce'));
        }

        if ($buy_price < 0 || $sale_price < 0) {
            wp_send_json_error(__('Prices cannot be negative', 'obydullah-pos-for-woocommerce'));
        }

        if ($product_id <= 0) {
            wp_send_json_error(__('Invalid product', 'obydullah-pos-for-woocommerce'));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Product not found', 'obydullah-pos-for-woocommerce'));
        }

        $old_quantity = $product->get_stock_quantity() ?: 0;

        $product->set_manage_stock(true);
        $product->set_stock_quantity($quantity);
        $product->set_stock_status($stock_status);
        $product->set_regular_price($sale_price);
        $product->save();

        update_post_meta($product_id, '_opfw_buy_price', $buy_price);

        if ($quantity !== $old_quantity) {
            $this->log_adjustment($product_id, $quantity > $old_quantity ? 'increase' : 'decrease',
                abs($quantity - $old_quantity), $old_quantity, $quantity, __('Stock update', 'obydullah-pos-for-woocommerce'));
        }

        wp_send_json_success(__('Stock updated successfully', 'obydullah-pos-for-woocommerce'));
    }

    public function ajax_get_products_for_adjustments()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'opfw_get_products_for_adjustments')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        $products = wc_get_products([
            'status' => 'publish',
            'limit' => -1,
            'return' => 'objects',
        ]);

        $formatted = [];
        foreach ($products as $product) {
            $formatted[] = [
                'product_id' => $product->get_id(),
                'name' => $product->get_name(),
                'quantity' => $product->get_stock_quantity() ?: 0,
                'buy_price' => get_post_meta($product->get_id(), '_opfw_buy_price', true) ?: '0.00',
                'stock_status' => $product->get_stock_status(),
            ];
        }

        wp_send_json_success($formatted);
    }

    public function ajax_get_current_stock()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'opfw_get_current_stock')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        $product_id = intval($_GET['product_id'] ?? 0);
        if ($product_id <= 0) {
            wp_send_json_error(__('Invalid product ID', 'obydullah-pos-for-woocommerce'));
        }

        $product = wc_get_product($product_id);
        $quantity = $product ? ($product->get_stock_quantity() ?: 0) : 0;

        wp_send_json_success(['current_stock' => $quantity]);
    }

    public function ajax_add_adjustment()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'opfw_add_stock_adjustment')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        $product_id = intval($_POST['product_id'] ?? 0);
        $adjustment_type = sanitize_text_field(wp_unslash($_POST['adjustment_type'] ?? 'increase'));
        $quantity = intval($_POST['quantity'] ?? 0);
        $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));

        if ($product_id <= 0) {
            wp_send_json_error(__('Please select a valid product', 'obydullah-pos-for-woocommerce'));
        }
        if ($quantity <= 0) {
            wp_send_json_error(__('Quantity must be greater than 0', 'obydullah-pos-for-woocommerce'));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Product not found', 'obydullah-pos-for-woocommerce'));
        }

        $old_quantity = $product->get_stock_quantity() ?: 0;

        if ($adjustment_type === 'decrease' && $quantity > $old_quantity) {
            wp_send_json_error(sprintf(
                __('Cannot decrease more than current stock. Available: %d', 'obydullah-pos-for-woocommerce'),
                $old_quantity
            ));
        }

        $new_quantity = $adjustment_type === 'increase' ? $old_quantity + $quantity : $old_quantity - $quantity;

        $product->set_manage_stock(true);
        $product->set_stock_quantity($new_quantity);

        if ($new_quantity === 0) {
            $product->set_stock_status('outofstock');
        } else {
            $product->set_stock_status('instock');
        }

        $product->save();

        $this->log_adjustment($product_id, $adjustment_type, $quantity, $old_quantity, $new_quantity, $note);

        wp_send_json_success(__('Stock adjustment applied successfully', 'obydullah-pos-for-woocommerce'));
    }

    public function ajax_get_adjustments()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'opfw_get_stock_adjustments')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        global $wpdb;

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
        $date = isset($_GET['date']) ? sanitize_text_field(wp_unslash($_GET['date'])) : '';

        $where = '1=1';
        $prepare_args = [];

        if (!empty($search)) {
            $where .= ' AND p.post_title LIKE %s';
            $prepare_args[] = '%' . $wpdb->esc_like($search) . '%';
        }

        if (!empty($type) && in_array($type, ['increase', 'decrease'], true)) {
            $where .= ' AND a.adjustment_type = %s';
            $prepare_args[] = $type;
        }

        if (!empty($date)) {
            $where .= ' AND DATE(a.created_at) = %s';
            $prepare_args[] = $date;
        }

        $count_query = "SELECT COUNT(*) FROM {$this->adjustment_log_table} a LEFT JOIN {$wpdb->posts} p ON a.product_id = p.ID WHERE {$where}";
        if (!empty($prepare_args)) {
            $count_query = $wpdb->prepare($count_query, $prepare_args);
        }
        $total = $wpdb->get_var($count_query);
        $total_pages = max(1, ceil(intval($total) / $per_page));

        $query = "SELECT a.*, p.post_title as product_name 
             FROM {$this->adjustment_log_table} a 
             LEFT JOIN {$wpdb->posts} p ON a.product_id = p.ID 
             WHERE {$where}
             ORDER BY a.created_at DESC 
             LIMIT %d OFFSET %d";

        $query_args = array_merge($prepare_args, [$per_page, $offset]);
        $results = $wpdb->get_results($wpdb->prepare($query, $query_args));

        wp_send_json_success([
            'adjustments' => $results ?: [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => intval($total),
                'total_pages' => $total_pages,
            ],
        ]);
    }

    public function ajax_delete_adjustment()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'opfw_delete_stock_adjustment')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        global $wpdb;
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(__('Invalid adjustment ID', 'obydullah-pos-for-woocommerce'));
        }

        $result = $wpdb->delete($this->adjustment_log_table, ['id' => $id], ['%d']);
        if ($result === false) {
            wp_send_json_error(__('Failed to delete adjustment', 'obydullah-pos-for-woocommerce'));
        }

        wp_send_json_success(__('Adjustment deleted successfully', 'obydullah-pos-for-woocommerce'));
    }

    private function log_adjustment($product_id, $type, $quantity, $old_qty, $new_qty, $note = '')
    {
        global $wpdb;

        $wpdb->insert($this->adjustment_log_table, [
            'product_id' => $product_id,
            'adjustment_type' => $type,
            'quantity' => $quantity,
            'old_quantity' => $old_qty,
            'new_quantity' => $new_qty,
            'note' => $note,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ], ['%d', '%s', '%d', '%d', '%d', '%s', '%d', '%s']);
    }
}
