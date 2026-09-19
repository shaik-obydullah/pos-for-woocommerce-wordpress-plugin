<?php
/**
 * Sales Management — WooCommerce Orders
 *
 * @package Obydullah_Restaurant_POS_For_WooCommerce
 * @since   1.0.0
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_Sales
{
    /**
     * Object cache group used for sales data.
     *
     * @since 1.0.0
     * @var string
     */
    const CACHE_GROUP = 'opfw_sales';

    public function __construct()
    {
        add_action('wp_ajax_opfw_get_sales', [$this, 'opfw_ajax_get_opfw_sales']);
        add_action('wp_ajax_opfw_delete_sale', [$this, 'opfw_ajax_delete_opfw_sale']);
        add_action('wp_ajax_opfw_print_sale', [$this, 'opfw_ajax_print_opfw_sale']);
    }

    public function opfw_render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline mb-3">
                <?php esc_html_e('Sales Management', 'obydullah-restaurant-pos-for-woocommerce'); ?>
            </h1>
            <hr class="wp-header-end">

            <div class="row">
                <div class="col-lg-12">
                    <div class="bg-light p-4 rounded shadow-sm border">
                        <h2 class="h5 mb-3 fw-semibold">
                            <?php esc_html_e('Sales History', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                        </h2>

                        <div class="search-section mb-4 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="search-group flex-grow-1">
                                    <label for="search-invoice" class="form-label mb-1">
                                        <?php esc_html_e('Search Invoice', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                                    </label>
                                    <input type="text" id="search-invoice"
                                        class="form-control form-control-sm"
                                        placeholder="<?php esc_attr_e('Invoice number...', 'obydullah-restaurant-pos-for-woocommerce'); ?>">
                                    <div class="form-text">
                                        <?php esc_html_e('Search by invoice number', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label for="date-from" class="form-label small mb-1">
                                    <?php esc_html_e('Date From', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                                </label>
                                <input type="date" id="date-from" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label for="date-to" class="form-label small mb-1">
                                    <?php esc_html_e('Date To', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                                </label>
                                <input type="date" id="date-to" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <label for="sale-type" class="form-label small mb-1">
                                    <?php esc_html_e('Sale Type', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                                </label>
                                <select id="sale-type" class="form-control form-control-sm">
                                    <option value=""><?php esc_html_e('All Types', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="dineIn"><?php esc_html_e('Dine In', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="takeAway"><?php esc_html_e('Take Away', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="pickup"><?php esc_html_e('Pick Up', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sale-status" class="form-label small mb-1">
                                    <?php esc_html_e('Sale Status', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                                </label>
                                <select id="sale-status" class="form-control form-control-sm">
                                    <option value=""><?php esc_html_e('All Status', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="completed"><?php esc_html_e('Completed', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="pending"><?php esc_html_e('Pending', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="cancelled"><?php esc_html_e('Cancelled', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" id="search-sales" class="btn btn-primary btn-sm">
                                        <span class="btn-text"><?php esc_html_e('Search', 'obydullah-restaurant-pos-for-woocommerce'); ?></span>
                                        <span class="spinner opfw-hidden"></span>
                                    </button>
                                    <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm ml-1">
                                        <?php esc_html_e('Reset', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered mb-2">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th width="120"><?php esc_html_e('Invoice ID', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                        <th width="100"><?php esc_html_e('Date', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                        <th><?php esc_html_e('Customer', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                        <th width="100"><?php esc_html_e('Type', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                        <th width="120"><?php esc_html_e('Total', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                        <th width="100"><?php esc_html_e('Status', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                        <th width="150" class="text-right"><?php esc_html_e('Actions', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="sales-list" class="bg-white">
                                    <tr>
                                        <td colspan="7" class="text-center p-4">
                                            <span class="spinner is-active"></span>
                                            <?php esc_html_e('Loading sales...', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                            <div class="tablenav-pages">
                                <span class="displaying-num" id="displaying-num">0 <?php esc_html_e('items', 'obydullah-restaurant-pos-for-woocommerce'); ?></span>
                                <span class="pagination-links ms-2">
                                    <a class="first-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('First page', 'obydullah-restaurant-pos-for-woocommerce'); ?>">&laquo;</a>
                                    <a class="prev-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('Previous page', 'obydullah-restaurant-pos-for-woocommerce'); ?>">&lsaquo;</a>
                                    <span class="paging-input">
                                        <input class="current-page form-control form-control-sm" id="current-page-selector" type="text" name="paged" value="1">
                                        <span class="tablenav-paging-text"><?php esc_html_e('of', 'obydullah-restaurant-pos-for-woocommerce'); ?> <span class="total-pages">1</span></span>
                                    </span>
                                    <a class="next-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('Next page', 'obydullah-restaurant-pos-for-woocommerce'); ?>">&rsaquo;</a>
                                    <a class="last-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('Last page', 'obydullah-restaurant-pos-for-woocommerce'); ?>">&raquo;</a>
                                </span>
                            </div>
                            <div class="tablenav-pages">
                                <select id="per-page-select" class="form-control form-control-sm">
                                    <option value="10">10 <?php esc_html_e('per page', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="20">20 <?php esc_html_e('per page', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="50">50 <?php esc_html_e('per page', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="100">100 <?php esc_html_e('per page', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function opfw_ajax_get_opfw_sales()
    {
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_get_sales')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $page = isset($_GET['page']) ? max(1, intval(sanitize_text_field(wp_unslash($_GET['page'])))) : 1;
        $per_page = isset($_GET['per_page']) ? intval(sanitize_text_field(wp_unslash($_GET['per_page']))) : 10;
        $search   = sanitize_text_field(wp_unslash($_GET['search'] ?? ''));
        $date_from = sanitize_text_field(wp_unslash($_GET['date_from'] ?? ''));
        $date_to   = sanitize_text_field(wp_unslash($_GET['date_to'] ?? ''));
        $sale_type = sanitize_text_field(wp_unslash($_GET['sale_type'] ?? ''));
        $status    = sanitize_text_field(wp_unslash($_GET['status'] ?? ''));

        $args = [
            'limit'    => $per_page,
            'page'     => $page,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'return'   => 'objects',
            'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                [
                    'key'     => '_pos_order',
                    'value'   => 'yes',
                    'compare' => '=',
                ],
            ],
        ];

        if (!empty($search)) {
            $args['search'] = $search;
            $args['search_columns'] = ['_invoice_id'];
        }

        if (!empty($status)) {
            $args['status'] = [$status];
        }

        if (!empty($date_from) || !empty($date_to)) {
            $args['date_created'] = ($date_from && $date_to)
                ? $date_from . '...' . $date_to
                : ($date_from ? $date_from . '...' : '...' . $date_to);
        }

        $cache_key = 'sales_' . implode('_', [$page, $per_page, md5($search), md5($date_from), md5($date_to), md5($sale_type), md5($status)]);

        $all_sales = Obydullah_POS_For_WooCommerce_Helpers::opfw_cache_get_or_set($cache_key, function () use ($search, $sale_type, $args) {
            $count_args        = $args;
            $count_args['limit'] = -1;
            $count_args['page']  = 1;
            $all_orders         = wc_get_orders($count_args);
            $all_sales          = [];

            foreach ($all_orders as $order) {
                $invoice_id = $order->get_meta('_invoice_id');
                if ($search && stripos($invoice_id, $search) === false) {
                    continue;
                }

                $order_type = $order->get_meta('_order_type');
                if ($sale_type && $order_type !== $sale_type) {
                    continue;
                }

                $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                if (empty($customer_name)) {
                    $customer_name = __('Walk-in Customer', 'obydullah-restaurant-pos-for-woocommerce');
                }

                $all_sales[] = [
                    'id'            => $order->get_id(),
                    'invoice_id'    => $invoice_id,
                    'customer_name' => $customer_name,
                    'sale_type'     => $order_type,
                    'grand_total'   => $order->get_total(),
                    'status'        => $order->get_status(),
                    'created_at'    => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
                ];
            }

            return $all_sales;
        }, self::CACHE_GROUP);

        $sales       = array_slice($all_sales, ($page - 1) * $per_page, $per_page);
        $total       = count($all_sales);
        $total_pages = max(1, ceil($total / $per_page));

        wp_send_json_success([
            'sales'        => $sales,
            'total'        => $total,
            'total_pages'  => $total_pages,
            'showing_from' => $total > 0 ? (($page - 1) * $per_page) + 1 : 0,
            'showing_to'   => min($page * $per_page, $total),
            'current_page' => $page,
            'per_page'     => $per_page,
        ]);
    }

    public function opfw_ajax_print_opfw_sale()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_print_sale')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $sale_id = intval(sanitize_text_field(wp_unslash($_POST['sale_id'] ?? '')));
        if (!$sale_id) {
            wp_send_json_error(__('Invalid sale ID', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $order = wc_get_order($sale_id);
        if (!$order) {
            wp_send_json_error(__('Sale not found', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = [
                'product_name' => $item->get_name(),
                'quantity'     => $item->get_quantity(),
                'unit_price'   => floatval($item->get_total()) / max(1, $item->get_quantity()),
                'total'        => floatval($item->get_total()),
            ];
        }

        $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());

        wp_send_json_success([
            'id'              => $order->get_id(),
            'invoice_id'      => $order->get_meta('_invoice_id'),
            'grand_total'     => $order->get_total(),
            'status'          => $order->get_status(),
            'created_at'      => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
            'sale_type'       => $order->get_meta('_order_type'),
            'customer_name'   => $customer_name,
            'customer_mobile' => $order->get_billing_phone(),
            'customer_email'  => $order->get_billing_email(),
            'customer_address'=> $order->get_billing_address_1(),
            'items'           => $items,
            'shop_info'       => Obydullah_POS_For_WooCommerce_Helpers::opfw_get_shop_info(),
        ]);
    }

    public function opfw_ajax_delete_opfw_sale()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_delete_sale')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $id = intval(sanitize_text_field(wp_unslash($_POST['id'] ?? '')));
        if (!$id) {
            wp_send_json_error(__('Invalid sale ID', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $order = wc_get_order($id);
        if (!$order) {
            wp_send_json_error(__('Sale not found', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $order->delete(true);

        Obydullah_POS_For_WooCommerce_Helpers::opfw_cache_flush_group('opfw_sales');
        Obydullah_POS_For_WooCommerce_Helpers::opfw_cache_flush_group('opfw_dashboard');

        wp_send_json_success(__('Sale deleted successfully', 'obydullah-restaurant-pos-for-woocommerce'));
    }
}
