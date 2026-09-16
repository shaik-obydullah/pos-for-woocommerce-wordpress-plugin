<?php
/**
 * Plugin Handler
 *
 * @package Obydullah_Restaurant_POS_For_WooCommerce
 * @since   1.0.0
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$opfw_files = [
    'class-obydullah-restaurant-pos-for-woocommerce-helpers.php',
    'class-obydullah-restaurant-pos-for-woocommerce-settings.php',
    'class-obydullah-restaurant-pos-for-woocommerce-woo-products.php',
    'class-obydullah-restaurant-pos-for-woocommerce-woo-stock.php',
    'class-obydullah-restaurant-pos-for-woocommerce-pos.php',
    'class-obydullah-restaurant-pos-for-woocommerce-sales.php',
    'class-obydullah-restaurant-pos-for-woocommerce-dashboard.php',
    'class-obydullah-restaurant-pos-for-woocommerce-accounting.php',
    'class-obydullah-restaurant-pos-for-woocommerce-customers.php',
];

foreach ($opfw_files as $opfw_file) {
    $opfw_path = OPFW_PATH . 'includes/' . $opfw_file;
    if (file_exists($opfw_path)) {
        require_once $opfw_path;
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
        public $customers;

        public function __construct()
        {
            $this->opfw_init();
        }

        private function opfw_init()
        {
            $this->settings = new Obydullah_POS_For_WooCommerce_Settings();
            $this->woo_products = new Obydullah_POS_For_WooCommerce_Woo_Products();
            $this->woo_stock = new Obydullah_POS_For_WooCommerce_Woo_Stock();
            $this->pos = new Obydullah_POS_For_WooCommerce_POS();
            $this->sales = new Obydullah_POS_For_WooCommerce_Sales();
            $this->dashboard = new Obydullah_POS_For_WooCommerce_Dashboard();
            $this->accounting = new Obydullah_POS_For_WooCommerce_Accounting();
            $this->customers = new Obydullah_POS_For_WooCommerce_Customers();

            add_action('admin_menu', [$this, 'opfw_register_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'opfw_enqueue_admin_scripts']);
        }

        public function opfw_register_admin_menu()
        {
            add_menu_page(
                __('OBY Restaurant POS', 'obydullah-restaurant-pos-for-woocommerce'),
                __('OBY Restaurant POS', 'obydullah-restaurant-pos-for-woocommerce'),
                'manage_options',
                'obydullah-restaurant-pos-for-woocommerce',
                [$this->dashboard, 'opfw_render_page'],
                'dashicons-store',
                25
            );

            $submenus = [
                'obydullah-restaurant-pos-for-woocommerce' => [__('Dashboard', 'obydullah-restaurant-pos-for-woocommerce'), $this->dashboard],
                'obydullah-restaurant-pos-for-woocommerce-products' => [__('Products', 'obydullah-restaurant-pos-for-woocommerce'), $this->woo_products],
                'obydullah-restaurant-pos-for-woocommerce-stock' => [__('Stock Management', 'obydullah-restaurant-pos-for-woocommerce'), $this->woo_stock, 'opfw_render_stock_page'],
                'obydullah-restaurant-pos-for-woocommerce-stock-adjustments' => [__('Stock Adjustments', 'obydullah-restaurant-pos-for-woocommerce'), $this->woo_stock, 'opfw_render_adjustments_page'],
                'obydullah-restaurant-pos-for-woocommerce-pos' => [__('POS', 'obydullah-restaurant-pos-for-woocommerce'), $this->pos],
                'obydullah-restaurant-pos-for-woocommerce-sales' => [__('Sales', 'obydullah-restaurant-pos-for-woocommerce'), $this->sales],
                'obydullah-restaurant-pos-for-woocommerce-accounting' => [__('Accounting', 'obydullah-restaurant-pos-for-woocommerce'), $this->accounting],
                'obydullah-restaurant-pos-for-woocommerce-customers' => [__('Customers', 'obydullah-restaurant-pos-for-woocommerce'), $this->customers],
                'obydullah-restaurant-pos-for-woocommerce-settings' => [__('Settings', 'obydullah-restaurant-pos-for-woocommerce'), $this->settings],
            ];

            foreach ($submenus as $slug => $data) {
                $render_method = isset($data[2]) ? $data[2] : 'opfw_render_page';
                add_submenu_page(
                    'obydullah-restaurant-pos-for-woocommerce',
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
                strpos($hook, 'obydullah-restaurant-pos-for-woocommerce') === false &&
                strpos($current_page, 'obydullah-restaurant-pos-for-woocommerce') === false
            ) {
                return;
            }

            wp_enqueue_style(
                'obydullah-restaurant-pos-for-woocommerce-main',
                OPFW_URL . 'assets/css/main.css',
                [],
                OPFW_VERSION
            );

            wp_enqueue_style(
                'obydullah-restaurant-pos-for-woocommerce-pos-style',
                OPFW_URL . 'assets/css/pos-style.css',
                ['obydullah-restaurant-pos-for-woocommerce-main'],
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
                case 'obydullah-restaurant-pos-for-woocommerce-products':
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
                            'noProducts' => __('No products found.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'loadingProducts' => __('Loading products...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-pos-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'buyPriceUpdated' => __('Buy price updated successfully.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'items' => __('items', 'obydullah-restaurant-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-pos-for-woocommerce-stock':
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
                            'selectProduct' => __('Select Product', 'obydullah-restaurant-pos-for-woocommerce'),
                            'loadingStocks' => __('Loading...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'noStocks' => __('No products found.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'error' => __('Error', 'obydullah-restaurant-pos-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'saveStock' => __('Update Stock', 'obydullah-restaurant-pos-for-woocommerce'),
                            'items' => __('items', 'obydullah-restaurant-pos-for-woocommerce'),
                            'inStock' => __('In Stock', 'obydullah-restaurant-pos-for-woocommerce'),
                            'outOfStock' => __('Out of Stock', 'obydullah-restaurant-pos-for-woocommerce'),
                            'onBackorder' => __('On Backorder', 'obydullah-restaurant-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-pos-for-woocommerce-stock-adjustments':
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
                            'selectStock' => __('Select Product', 'obydullah-restaurant-pos-for-woocommerce'),
                            'loadingAdjustments' => __('Loading adjustments...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'noAdjustments' => __('No adjustments found.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'error' => __('Error', 'obydullah-restaurant-pos-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'confirmDelete' => __('Are you sure you want to delete this adjustment?', 'obydullah-restaurant-pos-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-restaurant-pos-for-woocommerce'),
                            'items' => __('items', 'obydullah-restaurant-pos-for-woocommerce'),
                            'applying' => __('Applying...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'applyAdjustment' => __('Apply Adjustment', 'obydullah-restaurant-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-pos-for-woocommerce-pos':
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
                            'allStocks' => __('All Products', 'obydullah-restaurant-pos-for-woocommerce'),
                            'loadingStocks' => __('Loading products...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'noStocks' => __('No products found', 'obydullah-restaurant-pos-for-woocommerce'),
                            'inStock' => __('in stock', 'obydullah-restaurant-pos-for-woocommerce'),
                            'cartEmpty' => __('Cart is empty', 'obydullah-restaurant-pos-for-woocommerce'),
                            'confirmLoadSaved' => __('Loading saved sale will clear current cart. Continue?', 'obydullah-restaurant-pos-for-woocommerce'),
                            'confirmRemove' => __('Remove this item from cart?', 'obydullah-restaurant-pos-for-woocommerce'),
                            'confirmClear' => __('Clear cart?', 'obydullah-restaurant-pos-for-woocommerce'),
                            'cartEmptyAlert' => __('Cart is empty!', 'obydullah-restaurant-pos-for-woocommerce'),
                            'processing' => __('Processing...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'processing' => __('Processing...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'saleLoaded' => __('Saved sale loaded!', 'obydullah-restaurant-pos-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-pos-for-woocommerce'),
                            'loadingSaved' => __('Loading saved sales...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'noSaved' => __('No saved sales', 'obydullah-restaurant-pos-for-woocommerce'),
                            'requestFailed' => __('An error occurred. Please try again.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'confirmDeleteSaved' => __('Are you sure you want to delete this saved sale?', 'obydullah-restaurant-pos-for-woocommerce'),
                            'saleDeleted' => __('Saved sale deleted successfully!', 'obydullah-restaurant-pos-for-woocommerce'),
                            'deleteFailed' => __('Failed to delete saved sale. Please try again.', 'obydullah-restaurant-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-pos-for-woocommerce-sales':
                    $helpers = new Obydullah_POS_For_WooCommerce_Helpers();
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
                            'items' => __('items', 'obydullah-restaurant-pos-for-woocommerce'),
                            'loading_sales' => __('Loading sales...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'no_sales' => __('No sales found.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'failed_load' => __('Failed to load sales.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'print' => __('Print', 'obydullah-restaurant-pos-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-restaurant-pos-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-pos-for-woocommerce'),
                            'confirm_delete' => __('Are you sure you want to delete this sale?', 'obydullah-restaurant-pos-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-restaurant-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-pos-for-woocommerce-customers':
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
                            'loadingCustomers' => __('Loading customers...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'noCustomers' => __('No customers found.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-pos-for-woocommerce'),
                            'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'saved' => __('Customer saved successfully!', 'obydullah-restaurant-pos-for-woocommerce'),
                            'add' => __('Add Customer', 'obydullah-restaurant-pos-for-woocommerce'),
                            'update' => __('Update Customer', 'obydullah-restaurant-pos-for-woocommerce'),
                            'edit' => __('Edit', 'obydullah-restaurant-pos-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-restaurant-pos-for-woocommerce'),
                            'confirmDelete' => __('Are you sure you want to delete this customer?', 'obydullah-restaurant-pos-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'cancel' => __('Cancel', 'obydullah-restaurant-pos-for-woocommerce'),
                        ],
                    ]);
                    break;

                case 'obydullah-restaurant-pos-for-woocommerce-accounting':
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
                            'items' => __('items', 'obydullah-restaurant-pos-for-woocommerce'),
                            'saving' => __('Saving...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'save_entry' => __('Save Entry', 'obydullah-restaurant-pos-for-woocommerce'),
                            'loading_entries' => __('Loading accounting entries...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'no_entries' => __('No accounting entries found.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'failed_load' => __('Failed to load accounting entries.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'amount_required' => __('Please enter either income or expense amount', 'obydullah-restaurant-pos-for-woocommerce'),
                            'error' => __('Error:', 'obydullah-restaurant-pos-for-woocommerce'),
                            'request_failed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'confirm_delete' => __('Are you sure you want to delete this accounting entry?', 'obydullah-restaurant-pos-for-woocommerce'),
                            'deleting' => __('Deleting...', 'obydullah-restaurant-pos-for-woocommerce'),
                            'delete_failed' => __('Delete request failed. Please try again.', 'obydullah-restaurant-pos-for-woocommerce'),
                            'delete' => __('Delete', 'obydullah-restaurant-pos-for-woocommerce'),
                        ],
                    ]);
                    break;
            }
        }
    }
}