<?php
/**
 * Plugin Handler
 *
 * @package Obydullah_Restaurant_Sales_Terminal_For_WooCommerce
 * @since   1.0.0
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$opfw_files = [
    'class-obydullah-restaurant-sales-terminal-for-woocommerce-helpers.php',
    'class-obydullah-restaurant-sales-terminal-for-woocommerce-settings.php',
    'class-obydullah-restaurant-sales-terminal-for-woocommerce-woo-products.php',
    'class-obydullah-restaurant-sales-terminal-for-woocommerce-woo-stock.php',
    'class-obydullah-restaurant-sales-terminal-for-woocommerce-pos.php',
    'class-obydullah-restaurant-sales-terminal-for-woocommerce-sales.php',
    'class-obydullah-restaurant-sales-terminal-for-woocommerce-dashboard.php',
    'class-obydullah-restaurant-sales-terminal-for-woocommerce-accounting.php',
    'class-obydullah-restaurant-sales-terminal-for-woocommerce-customers.php',
];

foreach ($opfw_files as $opfw_file) {
    $opfw_path = OPFW_PATH . 'includes/' . $opfw_file;
    if (file_exists($opfw_path)) {
        require_once $opfw_path;
    }
}

if (!class_exists('Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Handler')) {
    class Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Handler
    {
        public $settings;
        public $woo_products;
        public $woo_stock;
        public $pos;
        public $sales;
        public $dashboard;
        public $accounting;
        public $customers;

        public function __construct()
        {
            $this->opfw_init();
        }

        private function opfw_init()
        {
            $this->settings = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Settings();
            $this->woo_products = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Woo_Products();
            $this->woo_stock = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Woo_Stock();
            $this->pos = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_POS();
            $this->sales = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Sales();
            $this->dashboard = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Dashboard();
            $this->accounting = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Accounting();
            $this->customers = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Customers();

            add_action('admin_menu', [$this, 'opfw_register_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'opfw_enqueue_admin_scripts']);
        }

        public function opfw_register_admin_menu()
        {
            add_menu_page(
                __('OBY Restaurant Sales Terminal', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                __('OBY Restaurant Sales Terminal', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                'manage_options',
                'obydullah-restaurant-sales-terminal',
                [$this->dashboard, 'opfw_render_page'],
                'dashicons-store',
                100
            );

            $submenus = [
                'obydullah-restaurant-sales-terminal' => [__('Dashboard', 'obydullah-restaurant-sales-terminal-for-woocommerce'), $this->dashboard],
                'obydullah-restaurant-sales-terminal-products' => [__('Products', 'obydullah-restaurant-sales-terminal-for-woocommerce'), $this->woo_products],
                'obydullah-restaurant-sales-terminal-stock' => [__('Stock Management', 'obydullah-restaurant-sales-terminal-for-woocommerce'), $this->woo_stock, 'opfw_render_stock_page'],
                'obydullah-restaurant-sales-terminal-stock-adjustments' => [__('Stock Adjustments', 'obydullah-restaurant-sales-terminal-for-woocommerce'), $this->woo_stock, 'opfw_render_adjustments_page'],
                'obydullah-restaurant-sales-terminal-pos' => [__('POS', 'obydullah-restaurant-sales-terminal-for-woocommerce'), $this->pos],
                'obydullah-restaurant-sales-terminal-sales' => [__('Sales', 'obydullah-restaurant-sales-terminal-for-woocommerce'), $this->sales],
                'obydullah-restaurant-sales-terminal-accounting' => [__('Accounting', 'obydullah-restaurant-sales-terminal-for-woocommerce'), $this->accounting],
                'obydullah-restaurant-sales-terminal-customers' => [__('Customers', 'obydullah-restaurant-sales-terminal-for-woocommerce'), $this->customers],
                'obydullah-restaurant-sales-terminal-settings' => [__('Settings', 'obydullah-restaurant-sales-terminal-for-woocommerce'), $this->settings],
            ];

            foreach ($submenus as $slug => $data) {
                $render_method = isset($data[2]) ? $data[2] : 'opfw_render_page';
                add_submenu_page(
                    'obydullah-restaurant-sales-terminal',
                    $data[0],
                    $data[0],
                    'manage_options',
                    $slug,
                    [$data[1], $render_method]
                );
            }
        }

        public function opfw_enqueue_admin_scripts($hook)
        {
            $current_page = isset($_GET['page']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                ? sanitize_text_field(wp_unslash($_GET['page'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                : '';

            if (
                strpos($hook, 'obydullah-restaurant-sales-terminal') === false &&
                strpos($current_page, 'obydullah-restaurant-sales-terminal') === false
            ) {
                return;
            }

            wp_enqueue_style(
                'obydullah-restaurant-sales-terminal-main',
                OPFW_URL . 'assets/css/main.css',
                [],
                OPFW_VERSION
            );

            wp_enqueue_style(
                'obydullah-restaurant-sales-terminal-pos-style',
                OPFW_URL . 'assets/css/pos-style.css',
                ['obydullah-restaurant-sales-terminal-main'],
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
                case 'obydullah-restaurant-sales-terminal-products':
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
                            'noProducts' => __('No products found.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'loadingProducts' => __('Loading products...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'buyPriceUpdated' => __('Buy price updated successfully.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'items' => __('items', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-sales-terminal-stock':
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
                            'selectProduct' => __('Select Product', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'loadingStocks' => __('Loading...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'noStocks' => __('No products found.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'error' => __('Error', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'saveStock' => __('Update Stock', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'items' => __('items', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'inStock' => __('In Stock', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'outOfStock' => __('Out of Stock', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'onBackorder' => __('On Backorder', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-sales-terminal-stock-adjustments':
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
                            'selectStock' => __('Select Product', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'loadingAdjustments' => __('Loading adjustments...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'noAdjustments' => __('No adjustments found.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'error' => __('Error', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'confirmDelete' => __('Are you sure you want to delete this adjustment?', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'items' => __('items', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'applying' => __('Applying...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'applyAdjustment' => __('Apply Adjustment', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-sales-terminal-pos':
                    $helpers = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Helpers();
                    wp_enqueue_script(
                        'opfw-pos-js',
                        OPFW_URL . 'assets/js/pos.js',
                        ['jquery', 'opfw-admin-js'],
                        OPFW_VERSION,
                        ['in_footer' => true, 'strategy' => 'defer']
                    );
                    wp_localize_script('opfw-pos-js', 'opfw_pos', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'currencySymbol' => $helpers->opfw_get_currency_symbol(),
                        'vatRate' => $helpers->opfw_get_vat_rate(),
                        'taxRate' => $helpers->opfw_get_tax_rate(),
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
                            'allStocks' => __('All Products', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'loadingStocks' => __('Loading products...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'noStocks' => __('No products found', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'inStock' => __('in stock', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'cartEmpty' => __('Cart is empty', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'confirmLoadSaved' => __('Loading saved sale will clear current cart. Continue?', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'confirmRemove' => __('Remove this item from cart?', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'confirmClear' => __('Clear cart?', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'cartEmptyAlert' => __('Cart is empty!', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'processing' => __('Processing...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'processing' => __('Processing...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'saleLoaded' => __('Saved sale loaded!', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'loadingSaved' => __('Loading saved sales...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'noSaved' => __('No saved sales', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'requestFailed' => __('An error occurred. Please try again.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'confirmDeleteSaved' => __('Are you sure you want to delete this saved sale?', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'saleDeleted' => __('Saved sale deleted successfully!', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'deleteFailed' => __('Failed to delete saved sale. Please try again.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-sales-terminal-sales':
                    $helpers = new Obydullah_Restaurant_Sales_Terminal_For_WooCommerce_Helpers();
                    $shop_info = $helpers->opfw_get_shop_info();
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
                        'currency_symbol' => $helpers->opfw_get_currency_symbol(),
                        'shop_info' => $shop_info,
                        'strings' => [
                            'items' => __('items', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'loading_sales' => __('Loading sales...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'no_sales' => __('No sales found.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'failed_load' => __('Failed to load sales.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'print' => __('Print', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'confirm_delete' => __('Are you sure you want to delete this sale?', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-sales-terminal-customers':
                    wp_enqueue_script(
                        'opfw-customers-js',
                        OPFW_URL . 'assets/js/customers.js',
                        ['jquery', 'opfw-admin-js'],
                        OPFW_VERSION,
                        ['in_footer' => true, 'strategy' => 'defer']
                    );
                    wp_localize_script('opfw-customers-js', 'opfwCustomers', [
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'getNonce' => wp_create_nonce('opfw_get_customers'),
                        'addNonce' => wp_create_nonce('opfw_add_customer'),
                        'updateNonce' => wp_create_nonce('opfw_update_customer'),
                        'deleteNonce' => wp_create_nonce('opfw_delete_customer'),
                        'strings' => [
                            'loadingCustomers' => __('Loading customers...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'noCustomers' => __('No customers found.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'saved' => __('Customer saved successfully!', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'add' => __('Add Customer', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'update' => __('Update Customer', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'edit' => __('Edit', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'confirmDelete' => __('Are you sure you want to delete this customer?', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'cancel' => __('Cancel', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-sales-terminal-accounting':
                    $currency = html_entity_decode(get_option('opfw_currency', '$'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
                            'items' => __('items', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'save_entry' => __('Save Entry', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'loading_entries' => __('Loading accounting entries...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'no_entries' => __('No accounting entries found.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'failed_load' => __('Failed to load accounting entries.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'amount_required' => __('Please enter either income or expense amount', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'request_failed' => __('Request failed. Please try again.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'confirm_delete' => __('Are you sure you want to delete this accounting entry?', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'delete_failed' => __('Delete request failed. Please try again.', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-restaurant-sales-terminal-for-woocommerce'),
                        ],
                    ]);
                    break;
            }
        }
    }
}