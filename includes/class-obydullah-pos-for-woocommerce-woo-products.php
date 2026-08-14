<?php
/**
 * WooCommerce Products Management for POS
 *
 * Replaces the standalone products and categories classes.
 * Reads product data from WooCommerce.
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since   2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_Woo_Products
{
    public function __construct()
    {
        add_action('wp_ajax_opfw_get_products', [$this, 'opfw_ajax_get_products']);
        add_action('wp_ajax_opfw_get_categories_for_products', [$this, 'opfw_ajax_get_categories']);
        add_action('wp_ajax_opfw_update_buy_price', [$this, 'opfw_ajax_update_buy_price']);
    }

    public function opfw_render_page()
    {
        ?>
<div class="wrap opfw-products-page">
    <h1 class="wp-heading-inline mb-3">
        <?php esc_html_e('Products', 'obydullah-pos-for-woocommerce'); ?>
    </h1>
    <hr class="wp-header-end">

    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="bg-light p-3 rounded shadow-sm border">
                <p class="text-muted mb-3">
                    <?php esc_html_e('Products are managed in WooCommerce. Below is a POS-specific view to set buy prices (cost of goods).', 'obydullah-pos-for-woocommerce'); ?>
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=product')); ?>" class="btn btn-sm btn-primary ml-2 opfw-add-product-btn">
                        <?php esc_html_e('Add New Product in WooCommerce', 'obydullah-pos-for-woocommerce'); ?>
                    </a>
                </p>

                <div class="search-section mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="search-group flex-grow-1">
                            <label for="product-search" class="form-label mb-1">
                                <?php esc_html_e('Search Products', 'obydullah-pos-for-woocommerce'); ?>
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="position-relative flex-grow-1">
                                    <input type="text" id="product-search" class="form-control form-control-sm"
                                        placeholder="<?php esc_attr_e('Product name', 'obydullah-pos-for-woocommerce'); ?>">
                                    <button type="button" id="clear-search"
                                        class="btn btn-sm btn-link text-decoration-none position-absolute end-0 top-50 translate-middle-y" opfw-hidden">
                                        <span class="text-muted fs-5">&times;</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered mb-2">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th width="80"><?php esc_html_e('Image', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th><?php esc_html_e('Name', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="120"><?php esc_html_e('Category', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="100"><?php esc_html_e('Regular Price', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="100"><?php esc_html_e('Buy Price', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="100"><?php esc_html_e('Stock', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="100"><?php esc_html_e('Status', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="120" class="text-right"><?php esc_html_e('Actions', 'obydullah-pos-for-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="product-list" class="bg-white">
                            <tr>
                                <td colspan="8" class="text-center p-4">
                                    <span class="spinner is-active"></span>
                                    <?php esc_html_e('Loading products...', 'obydullah-pos-for-woocommerce'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                    <div class="tablenav-pages">
                        <span class="displaying-num" id="displaying-num">0
                            <?php esc_html_e('items', 'obydullah-pos-for-woocommerce'); ?></span>
                        <span class="pagination-links ms-2">
                            <a class="first-page btn btn-sm btn-dark" href="#">&laquo;</a>
                            <a class="prev-page btn btn-sm btn-dark" href="#">&lsaquo;</a>
                            <span class="paging-input">
                                <input class="current-page form-control form-control-sm" id="current-page-selector"
                                    type="text" name="paged" value="1">
                                <span class="tablenav-paging-text"><?php esc_html_e('of', 'obydullah-pos-for-woocommerce'); ?>
                                    <span class="total-pages">1</span></span>
                            </span>
                            <a class="next-page btn btn-sm btn-dark" href="#">&rsaquo;</a>
                            <a class="last-page btn btn-sm btn-dark" href="#">&raquo;</a>
                        </span>
                    </div>
                    <div class="tablenav-pages">
                        <select id="per-page-select" class="form-control form-control-sm">
                            <option value="10">10 <?php esc_html_e('per page', 'obydullah-pos-for-woocommerce'); ?></option>
                            <option value="20">20 <?php esc_html_e('per page', 'obydullah-pos-for-woocommerce'); ?></option>
                            <option value="50">50 <?php esc_html_e('per page', 'obydullah-pos-for-woocommerce'); ?></option>
                            <option value="100">100 <?php esc_html_e('per page', 'obydullah-pos-for-woocommerce'); ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Buy Price Edit Modal -->
<div id="opfw-buy-price-modal" class="opfw-modal d-none">
    <div class="opfw-modal-overlay"></div>
    <div class="opfw-modal-content bg-white p-3 rounded shadow">
        <h3 class="mb-2 opfw-modal-title"><?php esc_html_e('Edit Buy Price', 'obydullah-pos-for-woocommerce'); ?></h3>
        <form id="buy-price-form">
            <input type="hidden" id="buy-price-product-id" value="">
            <div class="mb-2">
                <label class="form-label"><?php esc_html_e('Product', 'obydullah-pos-for-woocommerce'); ?></label>
                <p id="buy-price-product-name" class="fw-bold"></p>
            </div>
            <div class="mb-2">
                <label for="buy-price-input" class="form-label">
                    <?php esc_html_e('Buy Price (Cost of Goods)', 'obydullah-pos-for-woocommerce'); ?>
                </label>
                <input type="number" id="buy-price-input" class="form-control" step="0.01" min="0" value="0.00">
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?php esc_html_e('Save', 'obydullah-pos-for-woocommerce'); ?></button>
                <button type="button" class="btn btn-secondary opfw-modal-close"><?php esc_html_e('Cancel', 'obydullah-pos-for-woocommerce'); ?></button>
            </div>
        </form>
    </div>
</div>
<?php
    }

    public function opfw_ajax_get_products()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_get_products')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $offset = ($page - 1) * $per_page;

        $args = [
            'status' => 'publish',
            'limit' => $per_page,
            'offset' => $offset,
            'return' => 'objects',
        ];

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $total_products = wc_get_products(array_merge($args, ['limit' => -1, 'return' => 'ids', 'offset' => 0]));
        $total_items = is_array($total_products) ? count($total_products) : 0;
        $total_pages = max(1, ceil($total_items / $per_page));

        $products = wc_get_products($args);
        $formatted = [];

        foreach ($products as $product) {
            $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
            $category_name = !is_wp_error($categories) && !empty($categories) ? implode(', ', $categories) : '—';

            $formatted[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'image' => $product->get_image('thumbnail'),
                'category_name' => $category_name,
                'regular_price' => $product->get_regular_price(),
                'sale_price' => $product->get_sale_price(),
                'buy_price' => get_post_meta($product->get_id(), '_opfw_buy_price', true),
                'stock_quantity' => $product->get_stock_quantity(),
                'stock_status' => $product->get_stock_status(),
                'manage_stock' => $product->get_manage_stock(),
                'status' => $product->get_status(),
            ];
        }

        wp_send_json_success([
            'products' => $formatted,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_items,
                'total_pages' => $total_pages,
            ],
        ]);
    }

    public function opfw_ajax_get_categories()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_get_categories_for_products')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
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

    public function opfw_ajax_update_buy_price()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_update_buy_price')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-pos-for-woocommerce'));
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $buy_price = isset($_POST['buy_price']) ? floatval($_POST['buy_price']) : 0;

        if ($product_id <= 0) {
            wp_send_json_error(__('Invalid product ID', 'obydullah-pos-for-woocommerce'));
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(__('Product not found', 'obydullah-pos-for-woocommerce'));
        }

        update_post_meta($product_id, '_opfw_buy_price', $buy_price);

        wp_send_json_success(__('Buy price updated successfully', 'obydullah-pos-for-woocommerce'));
    }
}
