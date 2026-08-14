<?php
/**
 * Plugin Handler
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since   2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$opfw_files = [
    'class-obydullah-pos-for-woocommerce-helpers.php',
    'class-obydullah-pos-for-woocommerce-settings.php',
    'class-obydullah-pos-for-woocommerce-woo-products.php',
    'class-obydullah-pos-for-woocommerce-woo-stock.php',
    'class-obydullah-pos-for-woocommerce-pos.php',
    'class-obydullah-pos-for-woocommerce-sales.php',
    'class-obydullah-pos-for-woocommerce-dashboard.php',
    'class-obydullah-pos-for-woocommerce-accounting.php',
];

foreach ($opfw_files as $file) {
    $path = OPFW_PATH . 'includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

if (!class_exists('Obydullah_POS_For_WooCommerce_Handler')) {
    class Obydullah_POS_For_WooCommerce_Handler
    {
        public $settings;
        public $woo_products;
        public $woo_stock;
        public $pos;
        public $sales;
        public $dashboard;
        public $accounting;

        public function __construct()
        {
            $this->init();
        }

        private function init()
        {
            $this->settings = new Obydullah_POS_For_WooCommerce_Settings();
            $this->woo_products = new Obydullah_POS_For_WooCommerce_Woo_Products();
            $this->woo_stock = new Obydullah_POS_For_WooCommerce_Woo_Stock();
            $this->pos = new Obydullah_POS_For_WooCommerce_POS();
            $this->sales = new Obydullah_POS_For_WooCommerce_Sales();
            $this->dashboard = new Obydullah_POS_For_WooCommerce_Dashboard();
            $this->accounting = new Obydullah_POS_For_WooCommerce_Accounting();

            add_action('admin_menu', [$this, 'register_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        }

        public function register_admin_menu()
        {
            add_menu_page(
                __('Restaurant POS', 'obydullah-pos-for-woocommerce'),
                __('Restaurant POS', 'obydullah-pos-for-woocommerce'),
                'manage_options',
                'obydullah-pos-for-woocommerce',
                [$this->dashboard, 'render_page'],
                'dashicons-store',
                25
            );

            $submenus = [
                'obydullah-pos-for-woocommerce' => [__('Dashboard', 'obydullah-pos-for-woocommerce'), $this->dashboard],
                'obydullah-pos-for-woocommerce-products' => [__('Products', 'obydullah-pos-for-woocommerce'), $this->woo_products],
                'obydullah-pos-for-woocommerce-stock' => [__('Stock Management', 'obydullah-pos-for-woocommerce'), $this->woo_stock, 'render_stock_page'],
                'obydullah-pos-for-woocommerce-stock-adjustments' => [__('Stock Adjustments', 'obydullah-pos-for-woocommerce'), $this->woo_stock, 'render_adjustments_page'],
                'obydullah-pos-for-woocommerce-pos' => [__('POS', 'obydullah-pos-for-woocommerce'), $this->pos],
                'obydullah-pos-for-woocommerce-sales' => [__('Sales', 'obydullah-pos-for-woocommerce'), $this->sales],
                'obydullah-pos-for-woocommerce-accounting' => [__('Accounting', 'obydullah-pos-for-woocommerce'), $this->accounting],
                'obydullah-pos-for-woocommerce-settings' => [__('Settings', 'obydullah-pos-for-woocommerce'), $this->settings],
            ];

            foreach ($submenus as $slug => $data) {
                $render_method = isset($data[2]) ? $data[2] : 'render_page';
                add_submenu_page(
                    'obydullah-pos-for-woocommerce',
                    $data[0],
                    $data[0],
                    'manage_options',
                    $slug,
                    [$data[1], $render_method]
                );
            }
        }

        public function enqueue_admin_scripts($hook)
        {
            $current_page = isset($_GET['page'])
                ? sanitize_text_field(wp_unslash($_GET['page']))
                : '';

            if (
                strpos($hook, 'obydullah-pos-for-woocommerce') === false &&
                strpos($current_page, 'obydullah-pos-for-woocommerce') === false
            ) {
                return;
            }

            wp_enqueue_style(
                'obydullah-pos-for-woocommerce-main',
                OPFW_URL . 'assets/css/main.css',
                [],
                OPFW_VERSION
            );

            wp_enqueue_style(
                'obydullah-pos-for-woocommerce-pos-style',
                OPFW_URL . 'assets/css/pos-style.css',
                ['obydullah-pos-for-woocommerce-main'],
                OPFW_VERSION
            );

            wp_enqueue_script(
                'opfw-admin-js',
                OPFW_URL . 'assets/js/admin.js',
                ['jquery'],
                OPFW_VERSION,
                true
            );

            switch ($current_page) {
                case 'obydullah-pos-for-woocommerce-products':
                    wp_enqueue_script(
                        'opfw-products-js',
                        OPFW_URL . 'assets/js/products.js',
                        ['jquery', 'opfw-admin-js'],
                        OPFW_VERSION,
                        ['in_footer' => true, 'strategy' => 'defer']
                    );
                    wp_localize_script('opfw-products-js', 'opfwProducts', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'getNonce' => wp_create_nonce('opfw_get_products'),
                        'getCategoriesNonce' => wp_create_nonce('opfw_get_categories_for_products'),
                        'updateBuyPriceNonce' => wp_create_nonce('opfw_update_buy_price'),
                        'strings' => [
                            'noProducts' => __('No products found.', 'obydullah-pos-for-woocommerce'),
                            'loadingProducts' => __('Loading products...', 'obydullah-pos-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-pos-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-pos-for-woocommerce'),
                            'buyPriceUpdated' => __('Buy price updated successfully.', 'obydullah-pos-for-woocommerce'),
                            'items' => __('items', 'obydullah-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-pos-for-woocommerce-stock':
                    wp_enqueue_script(
                        'opfw-stocks-js',
                        OPFW_URL . 'assets/js/stocks.js',
                        ['jquery', 'opfw-admin-js'],
                        OPFW_VERSION,
                        ['in_footer' => true, 'strategy' => 'defer']
                    );
                    wp_localize_script('opfw-stocks-js', 'opfwStocks', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'addNonce' => wp_create_nonce('opfw_update_stock'),
                        'getNonce' => wp_create_nonce('opfw_get_stocks'),
                        'productsNonce' => wp_create_nonce('opfw_get_products_for_stocks'),
                        'strings' => [
                            'selectProduct' => __('Select Product', 'obydullah-pos-for-woocommerce'),
                            'loadingStocks' => __('Loading...', 'obydullah-pos-for-woocommerce'),
                            'noStocks' => __('No products found.', 'obydullah-pos-for-woocommerce'),
                            'error' => __('Error', 'obydullah-pos-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-pos-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-pos-for-woocommerce'),
                            'saveStock' => __('Update Stock', 'obydullah-pos-for-woocommerce'),
                            'items' => __('items', 'obydullah-pos-for-woocommerce'),
                            'inStock' => __('In Stock', 'obydullah-pos-for-woocommerce'),
                            'outOfStock' => __('Out of Stock', 'obydullah-pos-for-woocommerce'),
                            'onBackorder' => __('On Backorder', 'obydullah-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-pos-for-woocommerce-stock-adjustments':
                    wp_enqueue_script(
                        'opfw-stock-adjustments-js',
                        OPFW_URL . 'assets/js/stock-adjustments.js',
                        ['jquery', 'opfw-admin-js'],
                        OPFW_VERSION,
                        ['in_footer' => true, 'strategy' => 'defer']
                    );
                    wp_localize_script('opfw-stock-adjustments-js', 'opfwStockAdjustments', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'addNonce' => wp_create_nonce('opfw_add_stock_adjustment'),
                        'getNonce' => wp_create_nonce('opfw_get_stock_adjustments'),
                        'deleteNonce' => wp_create_nonce('opfw_delete_stock_adjustment'),
                        'getProductsNonce' => wp_create_nonce('opfw_get_products_for_adjustments'),
                        'getStockNonce' => wp_create_nonce('opfw_get_current_stock'),
                        'strings' => [
                            'selectStock' => __('Select Product', 'obydullah-pos-for-woocommerce'),
                            'loadingAdjustments' => __('Loading adjustments...', 'obydullah-pos-for-woocommerce'),
                            'noAdjustments' => __('No adjustments found.', 'obydullah-pos-for-woocommerce'),
                            'error' => __('Error', 'obydullah-pos-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-pos-for-woocommerce'),
                            'confirmDelete' => __('Are you sure you want to delete this adjustment?', 'obydullah-pos-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-pos-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-pos-for-woocommerce'),
                            'items' => __('items', 'obydullah-pos-for-woocommerce'),
                            'applying' => __('Applying...', 'obydullah-pos-for-woocommerce'),
                            'applyAdjustment' => __('Apply Adjustment', 'obydullah-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-pos-for-woocommerce-pos':
                    $helpers = new Obydullah_POS_For_WooCommerce_Helpers();
                    wp_enqueue_script(
                        'opfw-pos-js',
                        OPFW_URL . 'assets/js/pos.js',
                        ['jquery', 'opfw-admin-js'],
                        OPFW_VERSION,
                        ['in_footer' => true, 'strategy' => 'defer']
                    );
                    wp_localize_script('opfw-pos-js', 'opfw_pos', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'currencySymbol' => $helpers->get_currency_symbol(),
                        'vatRate' => $helpers->get_vat_rate(),
                        'taxRate' => $helpers->get_tax_rate(),
                        'nonces' => [
                            'categories' => wp_create_nonce('opfw_get_categories_for_pos'),
                            'customers' => wp_create_nonce('opfw_get_customers_for_pos'),
                            'stocks' => wp_create_nonce('opfw_get_products_by_category'),
                            'saved' => wp_create_nonce('opfw_get_saved_sales'),
                            'load' => wp_create_nonce('opfw_load_saved_sale'),
                            'process' => wp_create_nonce('opfw_process_sale'),
                            'delete_saved' => wp_create_nonce('opfw_delete_saved_sale'),
                        ],
                        'strings' => [
                            'allStocks' => __('All Products', 'obydullah-pos-for-woocommerce'),
                            'loadingStocks' => __('Loading products...', 'obydullah-pos-for-woocommerce'),
                            'noStocks' => __('No products found', 'obydullah-pos-for-woocommerce'),
                            'inStock' => __('in stock', 'obydullah-pos-for-woocommerce'),
                            'cartEmpty' => __('Cart is empty', 'obydullah-pos-for-woocommerce'),
                            'confirmLoadSaved' => __('Loading saved sale will clear current cart. Continue?', 'obydullah-pos-for-woocommerce'),
                            'confirmRemove' => __('Remove this item from cart?', 'obydullah-pos-for-woocommerce'),
                            'confirmClear' => __('Clear cart?', 'obydullah-pos-for-woocommerce'),
                            'cartEmptyAlert' => __('Cart is empty!', 'obydullah-pos-for-woocommerce'),
                            'processing' => __('Processing...', 'obydullah-pos-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-pos-for-woocommerce'),
                            'processing' => __('Processing...', 'obydullah-pos-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-pos-for-woocommerce'),
                            'saleLoaded' => __('Saved sale loaded!', 'obydullah-pos-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-pos-for-woocommerce'),
                            'loadingSaved' => __('Loading saved sales...', 'obydullah-pos-for-woocommerce'),
                            'noSaved' => __('No saved sales', 'obydullah-pos-for-woocommerce'),
                            'requestFailed' => __('An error occurred. Please try again.', 'obydullah-pos-for-woocommerce'),
                            'confirmDeleteSaved' => __('Are you sure you want to delete this saved sale?', 'obydullah-pos-for-woocommerce'),
                            'saleDeleted' => __('Saved sale deleted successfully!', 'obydullah-pos-for-woocommerce'),
                            'deleteFailed' => __('Failed to delete saved sale. Please try again.', 'obydullah-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-pos-for-woocommerce-sales':
                    $helpers = new Obydullah_POS_For_WooCommerce_Helpers();
                    $shop_info = $helpers->get_shop_info();
                    wp_enqueue_script(
                        'opfw-sales-js',
                        OPFW_URL . 'assets/js/sales.js',
                        ['jquery', 'opfw-admin-js'],
                        OPFW_VERSION,
                        ['in_footer' => true, 'strategy' => 'defer']
                    );
                    wp_localize_script('opfw-sales-js', 'opfwSalesData', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce_get_sales' => wp_create_nonce('opfw_get_sales'),
                        'nonce_print_sale' => wp_create_nonce('opfw_print_sale'),
                        'nonce_delete_sale' => wp_create_nonce('opfw_delete_sale'),
                        'currency_symbol' => $helpers->get_currency_symbol(),
                        'shop_info' => $shop_info,
                        'strings' => [
                            'items' => __('items', 'obydullah-pos-for-woocommerce'),
                            'loading_sales' => __('Loading sales...', 'obydullah-pos-for-woocommerce'),
                            'no_sales' => __('No sales found.', 'obydullah-pos-for-woocommerce'),
                            'failed_load' => __('Failed to load sales.', 'obydullah-pos-for-woocommerce'),
                            'print' => __('Print', 'obydullah-pos-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-pos-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-pos-for-woocommerce'),
                            'confirm_delete' => __('Are you sure you want to delete this sale?', 'obydullah-pos-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-pos-for-woocommerce-accounting':
                    $currency = get_option('opfw_currency', '$');
                    $position = get_option('opfw_currency_position', 'left');
                    $formatted_amount = number_format(0, 2, '.', ',');
                    switch ($position) {
                        case 'right':
                            $currency_template = $formatted_amount . $currency;
                            break;
                        case 'left_space':
                            $currency_template = $currency . ' ' . $formatted_amount;
                            break;
                        case 'right_space':
                            $currency_template = $formatted_amount . ' ' . $currency;
                            break;
                        default:
                            $currency_template = $currency . $formatted_amount;
                    }
                    $date_format = get_option('opfw_date_format', 'Y-m-d');
                    $current_date = gmdate($date_format);
                    wp_enqueue_script(
                        'opfw-accounting-js',
                        OPFW_URL . 'assets/js/accounting.js',
                        ['jquery', 'opfw-admin-js'],
                        OPFW_VERSION,
                        ['in_footer' => true, 'strategy' => 'defer']
                    );
                    wp_localize_script('opfw-accounting-js', 'opfwAccountingData', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'nonce_get_entries' => wp_create_nonce('opfw_get_accounting_entries'),
                        'nonce_add_entry' => wp_create_nonce('opfw_add_accounting_entry'),
                        'nonce_delete_entry' => wp_create_nonce('opfw_delete_accounting_entry'),
                        'currency_template' => $currency_template,
                        'current_date' => $current_date,
                        'strings' => [
                            'items' => __('items', 'obydullah-pos-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-pos-for-woocommerce'),
                            'save_entry' => __('Save Entry', 'obydullah-pos-for-woocommerce'),
                            'loading_entries' => __('Loading accounting entries...', 'obydullah-pos-for-woocommerce'),
                            'no_entries' => __('No accounting entries found.', 'obydullah-pos-for-woocommerce'),
                            'failed_load' => __('Failed to load accounting entries.', 'obydullah-pos-for-woocommerce'),
                            'amount_required' => __('Please enter either income or expense amount', 'obydullah-pos-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-pos-for-woocommerce'),
                            'request_failed' => __('Request failed. Please try again.', 'obydullah-pos-for-woocommerce'),
                            'confirm_delete' => __('Are you sure you want to delete this accounting entry?', 'obydullah-pos-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-pos-for-woocommerce'),
                            'delete_failed' => __('Delete request failed. Please try again.', 'obydullah-pos-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-pos-for-woocommerce'),
                        ],
                    ]);
                    break;
            }
        }
    }
}
