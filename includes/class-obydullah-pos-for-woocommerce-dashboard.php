<?php
/**
 * Dashboard — WooCommerce Data Queries
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since   2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_Dashboard
{
    private $helpers;

    public function __construct()
    {
        $this->helpers = new Obydullah_POS_For_WooCommerce_Helpers();
    }

    private function format_currency($amount)
    {
        return $this->helpers->format_currency($amount);
    }

    private function format_number($number)
    {
        return number_format(intval($number), 0, '.', ',');
    }

    private function get_stock_value()
    {
        $total = 0;
        $products = wc_get_products([
            'limit'  => -1,
            'status' => 'publish',
            'return' => 'objects',
        ]);

        foreach ($products as $product) {
            $qty = $product->get_manage_stock() ? $product->get_stock_quantity() : 0;
            $cost = floatval(get_post_meta($product->get_id(), '_opfw_buy_price', true));
            $total += $qty * $cost;
        }

        return $total;
    }

    private function get_today_sales_count()
    {
        $orders = wc_get_orders([
            'date_created' => current_time('Y-m-d'),
            'status'       => 'completed',
            'limit'        => -1,
            'return'       => 'ids',
        ]);
        return count($orders);
    }

    private function get_month_sales_count()
    {
        $first_day = current_time('Y-m-01');
        $last_day  = current_time('Y-m-t');

        $orders = wc_get_orders([
            'date_created' => $first_day . '...' . $last_day,
            'status'       => 'completed',
            'limit'        => -1,
            'return'       => 'ids',
        ]);
        return count($orders);
    }

    private function get_today_income()
    {
        $total = 0;
        $orders = wc_get_orders([
            'date_created' => current_time('Y-m-d'),
            'status'       => 'completed',
            'limit'        => -1,
        ]);

        foreach ($orders as $order) {
            $revenue = floatval($order->get_total());
            foreach ($order->get_items() as $item) {
                $pid  = $item->get_product_id();
                $cost = floatval(get_post_meta($pid, '_opfw_buy_price', true));
                $revenue -= $cost * $item->get_quantity();
            }
            $total += $revenue;
        }

        return $total;
    }

    private function get_month_income()
    {
        $first_day = current_time('Y-m-01');
        $last_day  = current_time('Y-m-t');
        $total = 0;

        $orders = wc_get_orders([
            'date_created' => $first_day . '...' . $last_day,
            'status'       => 'completed',
            'limit'        => -1,
        ]);

        foreach ($orders as $order) {
            $revenue = floatval($order->get_total());
            foreach ($order->get_items() as $item) {
                $pid  = $item->get_product_id();
                $cost = floatval(get_post_meta($pid, '_opfw_buy_price', true));
                $revenue -= $cost * $item->get_quantity();
            }
            $total += $revenue;
        }

        return $total;
    }

    private function get_today_expense()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'opfw_accounting';

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(out_amount) FROM {$table} WHERE DATE(created_at) = %s",
                current_time('Y-m-d')
            )
        );

        return $result ? floatval($result) : 0;
    }

    private function get_month_expense()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'opfw_accounting';

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(out_amount) FROM {$table} WHERE DATE(created_at) BETWEEN %s AND %s",
                current_time('Y-m-01'),
                current_time('Y-m-t')
            )
        );

        return $result ? floatval($result) : 0;
    }

    private function get_low_stock_count()
    {
        $count = 0;
        $products = wc_get_products([
            'limit'  => -1,
            'status' => 'publish',
            'return' => 'objects',
        ]);

        foreach ($products as $product) {
            if ($product->get_manage_stock() && $product->get_stock_quantity() <= $product->get_low_stock_amount()) {
                $count++;
            }
        }

        return $count;
    }

    private function get_top_products($limit = 5)
    {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT oi_meta.meta_value AS product_id,
                        SUM(oi_meta_qty.meta_value) AS total_quantity_sold,
                        COUNT(DISTINCT o.ID) AS total_orders
                 FROM {$wpdb->prefix}woocommerce_order_items oi
                 JOIN {$wpdb->prefix}woocommerce_order_itemmeta oi_meta
                      ON oi.order_item_id = oi_meta.order_item_id
                      AND oi_meta.meta_key = '_product_id'
                 JOIN {$wpdb->prefix}woocommerce_order_itemmeta oi_meta_qty
                      ON oi.order_item_id = oi_meta_qty.order_item_id
                      AND oi_meta_qty.meta_key = '_qty'
                 JOIN {$wpdb->posts} o
                      ON o.ID = oi.order_id
                      AND o.post_status = 'wc-completed'
                 WHERE oi.order_item_type = 'line_item'
                 GROUP BY oi_meta.meta_value
                 ORDER BY total_quantity_sold DESC
                 LIMIT %d",
                $limit
            )
        );

        $top = [];
        if ($results) {
            foreach ($results as $row) {
                $product = wc_get_product($row->product_id);
                if (!$product) {
                    continue;
                }
                $top[] = [
                    'id'            => $product->get_id(),
                    'name'          => $product->get_name(),
                    'product_status'=> $product->get_status(),
                    'total_orders'  => intval($row->total_orders),
                    'total_sold'    => intval($row->total_quantity_sold),
                ];
            }
        }

        return $top;
    }

    public function render_page()
    {
        $dashboard_data = [
            'stock_value'     => $this->get_stock_value(),
            'today_sale'      => $this->get_today_sales_count(),
            'month_sale'      => $this->get_month_sales_count(),
            'today_income'    => $this->get_today_income(),
            'month_income'    => $this->get_month_income(),
            'today_expense'   => $this->get_today_expense(),
            'month_expense'   => $this->get_month_expense(),
            'low_stock_count' => $this->get_low_stock_count(),
        ];
        ?>
<div class="wrap">
    <h1 class="wp-heading-inline mb-3"><?php esc_html_e('Restaurant POS Dashboard', 'obydullah-pos-for-woocommerce'); ?></h1>
    <hr class="wp-header-end">

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-info">
                <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Low Stock Items', 'obydullah-pos-for-woocommerce'); ?></h3>
                <p class="summary-number text-info mb-0 fs-3 fw-bold"><?php echo esc_html($dashboard_data['low_stock_count']); ?></p>
                <small class="text-muted mb-3"><?php esc_html_e('Items with low stock', 'obydullah-pos-for-woocommerce'); ?></small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-info">
                <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Stock Value', 'obydullah-pos-for-woocommerce'); ?></h3>
                <p class="summary-number text-info mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['stock_value'])); ?></p>
                <small class="text-muted mb-3"><?php esc_html_e('Current inventory value', 'obydullah-pos-for-woocommerce'); ?></small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-success">
                <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e("Today's Sales", 'obydullah-pos-for-woocommerce'); ?></h3>
                <p class="summary-number text-success mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_number($dashboard_data['today_sale'])); ?></p>
                <small class="text-muted mb-3"><?php esc_html_e('Completed orders today', 'obydullah-pos-for-woocommerce'); ?></small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-primary">
                <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Monthly Sales', 'obydullah-pos-for-woocommerce'); ?></h3>
                <p class="summary-number text-success mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_number($dashboard_data['month_sale'])); ?></p>
                <small class="text-muted mb-3"><?php esc_html_e('Total orders this month', 'obydullah-pos-for-woocommerce'); ?></small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-lime">
                <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e("Today's Income", 'obydullah-pos-for-woocommerce'); ?></h3>
                <p class="summary-number text-lime mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['today_income'])); ?></p>
                <small class="text-muted mb-3"><?php esc_html_e('Revenue generated today', 'obydullah-pos-for-woocommerce'); ?></small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-success">
                <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Monthly Income', 'obydullah-pos-for-woocommerce'); ?></h3>
                <p class="summary-number text-success mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['month_income'])); ?></p>
                <small class="text-muted mb-3"><?php esc_html_e('Total revenue this month', 'obydullah-pos-for-woocommerce'); ?></small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-warning">
                <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e("Today's Expense", 'obydullah-pos-for-woocommerce'); ?></h3>
                <p class="summary-number text-warning mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['today_expense'])); ?></p>
                <small class="text-muted mb-3"><?php esc_html_e('Expenses incurred today', 'obydullah-pos-for-woocommerce'); ?></small>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-danger">
                <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Monthly Expense', 'obydullah-pos-for-woocommerce'); ?></h3>
                <p class="summary-number text-danger mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['month_expense'])); ?></p>
                <small class="text-muted mb-3"><?php esc_html_e('Total expenses this month', 'obydullah-pos-for-woocommerce'); ?></small>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="bg-light p-4 rounded shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fs-6 fw-semibold mb-0"><?php esc_html_e('Top Selling Products', 'obydullah-pos-for-woocommerce'); ?></h3>
                </div>

                <?php $top_products = $this->get_top_products(5); ?>

                <?php if (!empty($top_products)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th class="ps-4"><?php esc_html_e('Product', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th class="text-center"><?php esc_html_e('Orders', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th class="text-center"><?php esc_html_e('Qty Sold', 'obydullah-pos-for-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $product): ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($product['name']); ?>
                                    <br>
                                    <small class="text-muted">
                                        Status: <?php echo esc_html(ucfirst($product['product_status'])); ?>
                                    </small>
                                </td>
                                <td class="text-center fw-bold"><?php echo esc_html($this->format_number($product['total_orders'])); ?></td>
                                <td class="text-center fw-bold text-primary"><?php echo esc_html($this->format_number($product['total_sold'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <div class="py-4">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-muted mb-3">
                            <path d="M3 3h18v18H3zM8 8v8m8-8v8m-4-4v4" />
                        </svg>
                        <p class="mb-0 text-muted"><?php esc_html_e('No sales data available.', 'obydullah-pos-for-woocommerce'); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
    }
}
