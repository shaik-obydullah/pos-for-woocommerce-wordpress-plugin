<?php
/**
 * Accounting Management
 *
 * @package Obydullah_POS_For_WooCommerce
 * @since   1.0.0
 * @version 1.0.4
 */
if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_Accounting
{
    public function __construct()
    {
        add_action('wp_ajax_opfw_add_accounting_entry', array($this, 'ajax_add_opfw_accounting_entry'));
        add_action('wp_ajax_opfw_get_accounting_entries', array($this, 'ajax_get_opfw_accounting_entries'));
        add_action('wp_ajax_opfw_delete_accounting_entry', array($this, 'ajax_delete_opfw_accounting_entry'));
    }

    /**
     * Format currency using helper class
     *
     * @param float $amount The amount to format.
     * @return string
     */
    private function format_currency($amount)
    {
        return Obydullah_POS_For_WooCommerce_Helpers::format_currency($amount);
    }

    /**
     * Format date using helper class
     *
     * @param string $date_string The date string to format.
     * @return string
     */
    private function format_date($date_string)
    {
        return Obydullah_POS_For_WooCommerce_Helpers::format_date($date_string);
    }

    /**
     * Get accounting table name
     *
     * @return string
     */
    private function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'opfw_accounting';
    }

    /**
     * Convert a date from the configured date format to MySQL Y-m-d
     */
    private function normalize_date($date)
    {
        if (empty($date)) {
            return '';
        }
        $date_format = Obydullah_POS_For_WooCommerce_Helpers::get_date_format();
        $dt = DateTime::createFromFormat($date_format, $date);
        if ($dt && $dt->format($date_format) === $date) {
            return $dt->format('Y-m-d');
        }
        $ts = strtotime($date);
        return $ts ? gmdate('Y-m-d', $ts) : '';
    }

    /**
     * Render the accounting page
     */
    public function render_page()
    {
        $current_date = $this->format_date(gmdate('Y-m-d'));
        ?>
<div class="wrap">
    <h1 class="wp-heading-inline mb-3">
        <?php esc_html_e('Accounting', 'obydullah-pos-for-woocommerce'); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- Accounting Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-success">
                <h3 class="fs-6 fw-normal text-muted mb-2">
                    <?php esc_html_e('Total Income', 'obydullah-pos-for-woocommerce'); ?>
                </h3>
                <p class="summary-number text-success mb-0 fs-3 fw-bold" id="total-income">
                    <?php echo esc_html($this->format_currency(0)); ?>
                </p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-danger">
                <h3 class="fs-6 fw-normal text-muted mb-2">
                    <?php esc_html_e('Total Expense', 'obydullah-pos-for-woocommerce'); ?>
                </h3>
                <p class="summary-number text-danger mb-0 fs-3 fw-bold" id="total-expense">
                    <?php echo esc_html($this->format_currency(0)); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left: Add Accounting Entry Form -->
        <div class="col-lg-4 mb-4">
            <div class="bg-light p-4 rounded shadow-sm border h-100">
                <h2 class="fs-5 fw-semibold mb-3 mt-1 text-dark">
                    <?php esc_html_e('Add Accounting Entry', 'obydullah-pos-for-woocommerce'); ?>
                </h2>
                <form id="add-accounting-form" method="post">
                    <?php wp_nonce_field('opfw_add_accounting_entry', 'accounting_nonce'); ?>

                    <div class="mb-3">
                        <!-- Income Amount -->
                        <div class="form-group mb-3">
                            <label for="in-amount" class="form-label fw-semibold">
                                <?php esc_html_e('Income Amount', 'obydullah-pos-for-woocommerce'); ?>
                            </label>
                            <input name="in_amount" id="in-amount" type="number" step="0.01" min="0" value="0.00"
                                class="form-control form-control-sm" placeholder="0.00">
                        </div>

                        <!-- Expense Amount -->
                        <div class="form-group mb-3">
                            <label for="out-amount" class="form-label fw-semibold">
                                <?php esc_html_e('Expense Amount', 'obydullah-pos-for-woocommerce'); ?>
                            </label>
                            <input name="out_amount" id="out-amount" type="number" step="0.01" min="0" value="0.00"
                                class="form-control form-control-sm" placeholder="0.00">
                        </div>

                        <!-- Description -->
                        <div class="form-group mb-3">
                            <label for="entry-description" class="form-label fw-semibold">
                                <?php esc_html_e('Description', 'obydullah-pos-for-woocommerce'); ?>
                            </label>
                            <textarea name="description" id="entry-description" rows="3"
                                class="form-control form-control-sm"
                                placeholder="<?php esc_attr_e('Enter description for this entry (optional)', 'obydullah-pos-for-woocommerce'); ?>"></textarea>
                        </div>

                        <!-- Date -->
                        <div class="form-group mb-3">
                            <label for="entry-date" class="form-label fw-semibold">
                                <?php esc_html_e('Date', 'obydullah-pos-for-woocommerce'); ?>
                            </label>
                            <input name="entry_date" id="entry-date" type="date"
                                value="<?php echo esc_attr($current_date); ?>" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" id="submit-accounting" class="btn btn-primary w-100">
                            <span
                                class="btn-text"><?php esc_html_e('Save Entry', 'obydullah-pos-for-woocommerce'); ?></span>
                            <span class="spinner d-none"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right: Accounting Table -->
        <div class="col-lg-8">
            <div class="bg-light p-3 rounded shadow-sm border">
                <h2 class="h5 mb-3 fw-semibold">
                    <?php esc_html_e('Accounting Entries', 'obydullah-pos-for-woocommerce'); ?>
                </h2>

                <!-- Date Filter Section -->
                <div class="search-section mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="search-group flex-grow-1">
                            <label for="date-from" class="form-label mb-1">
                                <?php esc_html_e('Date Range', 'obydullah-pos-for-woocommerce'); ?>
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="date" id="date-from" class="form-control form-control-sm"
                                        placeholder="<?php esc_attr_e('From Date', 'obydullah-pos-for-woocommerce'); ?>">
                                    <span
                                        class="text-muted ml-1 mr-1"><?php esc_html_e('to', 'obydullah-pos-for-woocommerce'); ?></span>
                                    <input type="date" id="date-to" class="form-control form-control-sm"
                                        placeholder="<?php esc_attr_e('To Date', 'obydullah-pos-for-woocommerce'); ?>">
                                    <button type="button" id="search-entries" class="btn btn-sm btn-dark">
                                        <?php esc_html_e('Filter', 'obydullah-pos-for-woocommerce'); ?>
                                    </button>
                                    <button type="button" id="reset-filters"
                                        class="btn btn-sm btn-outline-secondary ml-1">
                                        <?php esc_html_e('Reset', 'obydullah-pos-for-woocommerce'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="form-text">
                                <?php esc_html_e('Filter entries by date range', 'obydullah-pos-for-woocommerce'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accounting Entries Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered mb-2">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th><?php esc_html_e('Date', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th><?php esc_html_e('Description', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="120"><?php esc_html_e('Income', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="120"><?php esc_html_e('Expense', 'obydullah-pos-for-woocommerce'); ?></th>
                                <th width="100" class="text-right">
                                    <?php esc_html_e('Actions', 'obydullah-pos-for-woocommerce'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="accounting-list" class="bg-white">
                            <tr>
                                <td colspan="5" class="text-center p-4">
                                    <span class="spinner is-active"></span>
                                    <?php esc_html_e('Loading accounting entries...', 'obydullah-pos-for-woocommerce'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                    <div class="tablenav-pages">
                        <span class="displaying-num" id="displaying-num">0
                            <?php esc_html_e('items', 'obydullah-pos-for-woocommerce'); ?></span>
                        <span class="pagination-links ms-2">
                            <a class="first-page btn btn-sm btn-dark" href="#"
                                title="<?php esc_attr_e('First page', 'obydullah-pos-for-woocommerce'); ?>">«</a>
                            <a class="prev-page btn btn-sm btn-dark" href="#"
                                title="<?php esc_attr_e('Previous page', 'obydullah-pos-for-woocommerce'); ?>">‹</a>
                            <span class="paging-input">
                                <input class="current-page form-control form-control-sm" id="current-page-selector"
                                    type="text" name="paged" value="1">
                                <span
                                    class="tablenav-paging-text"><?php esc_html_e('of', 'obydullah-pos-for-woocommerce'); ?>
                                    <span class="total-pages">1</span></span>
                            </span>
                            <a class="next-page btn btn-sm btn-dark" href="#"
                                title="<?php esc_attr_e('Next page', 'obydullah-pos-for-woocommerce'); ?>">›</a>
                            <a class="last-page btn btn-sm btn-dark" href="#"
                                title="<?php esc_attr_e('Last page', 'obydullah-pos-for-woocommerce'); ?>">»</a>
                        </span>
                    </div>
                    <div class="tablenav-pages">
                        <select id="per-page-select" class="form-control form-control-sm">
                            <option value="10">10 <?php esc_html_e('per page', 'obydullah-pos-for-woocommerce'); ?>
                            </option>
                            <option value="20">20 <?php esc_html_e('per page', 'obydullah-pos-for-woocommerce'); ?>
                            </option>
                            <option value="50">50 <?php esc_html_e('per page', 'obydullah-pos-for-woocommerce'); ?>
                            </option>
                            <option value="100">100 <?php esc_html_e('per page', 'obydullah-pos-for-woocommerce'); ?>
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<?php
    }

    /** Get accounting entries with pagination and date filter */
    public function ajax_get_opfw_accounting_entries()
    {
        // Check nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'] ?? '')), 'opfw_get_accounting_entries')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }

        global $wpdb;
        $accounting_table = $this->get_table_name();

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
        $date_from = isset($_GET['date_from']) ? $this->normalize_date(sanitize_text_field(wp_unslash($_GET['date_from']))) : '';
        $date_to = isset($_GET['date_to']) ? $this->normalize_date(sanitize_text_field(wp_unslash($_GET['date_to']))) : '';

        $offset = ($page - 1) * $per_page;

        // Build WHERE clause
        $where_clause = '1=1';
        $prepare_args = array();

        if (!empty($date_from)) {
            $where_clause .= ' AND DATE(created_at) >= %s';
            $prepare_args[] = $date_from;
        }

        if (!empty($date_to)) {
            $where_clause .= ' AND DATE(created_at) <= %s';
            $prepare_args[] = $date_to;
        }

        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$accounting_table} WHERE $where_clause";
        if (!empty($prepare_args)) {
            $count_query = $wpdb->prepare($count_query, $prepare_args);
        }
        $total = $wpdb->get_var($count_query);

        // Get entries data
        $query = "SELECT * FROM {$accounting_table} WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";

        // Always add pagination parameters
        $pagination_args = array($per_page, $offset);

        if (!empty($prepare_args)) {
            $query = $wpdb->prepare($query, array_merge($prepare_args, $pagination_args));
        } else {
            $query = $wpdb->prepare($query, $pagination_args);
        }

        $entries = $wpdb->get_results($query);

        // Format entries with helper functions
        if ($entries) {
            foreach ($entries as $entry) {
                $entry->formatted_date = $this->format_date($entry->created_at);
                $entry->formatted_in_amount = $this->format_currency($entry->in_amount);
                $entry->formatted_out_amount = $this->format_currency($entry->out_amount);
            }
        }

        // Calculate totals
        $totals_query = "SELECT 
            COALESCE(SUM(in_amount), 0) as total_income,
            COALESCE(SUM(out_amount), 0) as total_expense
            FROM {$accounting_table} WHERE $where_clause";

        if (!empty($prepare_args)) {
            $totals_query = $wpdb->prepare($totals_query, $prepare_args);
        }
        $totals = $wpdb->get_row($totals_query);

        // Format totals
        $formatted_totals = array(
            'total_income' => $this->format_currency($totals->total_income),
            'total_expense' => $this->format_currency($totals->total_expense)
        );

        // Calculate showing range
        $showing_from = $total > 0 ? $offset + 1 : 0;
        $showing_to = min($offset + $per_page, $total);

        wp_send_json_success(
            array(
                'entries' => $entries,
                'total' => $total,
                'showing_from' => $showing_from,
                'showing_to' => $showing_to,
                'current_page' => $page,
                'per_page' => $per_page,
                'totals' => $formatted_totals,
            )
        );
    }

    /** Add accounting entry */
    public function ajax_add_opfw_accounting_entry()
    {
        // Check nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'opfw_add_accounting_entry')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }

        global $wpdb;
        $table = $this->get_table_name();

        $in_amount = floatval($_POST['in_amount'] ?? 0);
        $out_amount = floatval($_POST['out_amount'] ?? 0);
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $entry_date = sanitize_text_field(wp_unslash($_POST['entry_date'] ?? ''));

        // Validate that amounts are not negative
        if ($in_amount < 0 || $out_amount < 0) {
            wp_send_json_error(__('Amounts cannot be negative', 'obydullah-pos-for-woocommerce'));
        }

        // Validate that at least one amount is entered
        if ($in_amount <= 0 && $out_amount <= 0) {
            wp_send_json_error(__('Please enter either income or expense amount', 'obydullah-pos-for-woocommerce'));
        }

        // Prepare data
        $data = array(
            'in_amount' => $in_amount,
            'out_amount' => $out_amount,
            'description' => $description
        );

        $format = array('%f', '%f', '%s');

        // Set custom date if provided (converted to MySQL format)
        if (!empty($entry_date)) {
            $normalized_date = $this->normalize_date($entry_date);
            if ($normalized_date) {
                $data['created_at'] = $normalized_date . ' ' . gmdate('H:i:s');
                $format[] = '%s';
            }
        }

        $result = $wpdb->insert($table, $data, $format);

        if (false === $result) {
            wp_send_json_error(__('Failed to add accounting entry', 'obydullah-pos-for-woocommerce'));
        }

        wp_send_json_success(__('Accounting entry added successfully', 'obydullah-pos-for-woocommerce'));
    }

    /** Delete accounting entry */
    public function ajax_delete_opfw_accounting_entry()
    {
        // Check nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'opfw_delete_accounting_entry')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-pos-for-woocommerce'));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-pos-for-woocommerce'));
        }

        global $wpdb;
        $table = $this->get_table_name();
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(__('Invalid accounting entry ID', 'obydullah-pos-for-woocommerce'));
        }

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if (false === $result) {
            wp_send_json_error(__('Failed to delete accounting entry', 'obydullah-pos-for-woocommerce'));
        }

        if (0 === $result) {
            wp_send_json_error(__('Accounting entry not found', 'obydullah-pos-for-woocommerce'));
        }

        wp_send_json_success(__('Accounting entry deleted successfully', 'obydullah-pos-for-woocommerce'));
    }
}