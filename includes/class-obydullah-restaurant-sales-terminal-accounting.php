<?php
/**
 * Accounting Management
 *
 * @package Obydullah_Restaurant_Sales_Terminal
 * @since   1.0.0
 * @version 1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_Restaurant_Sales_Terminal_Accounting
{
    /**
     * Object cache group used for accounting data.
     *
     * @since 1.0.0
     * @var string
     */
    const CACHE_GROUP = 'opfw_accounting';

    public function __construct()
    {
        add_action('wp_ajax_opfw_add_accounting_entry', array($this, 'opfw_ajax_add_opfw_accounting_entry'));
        add_action('wp_ajax_opfw_get_accounting_entries', array($this, 'opfw_ajax_get_opfw_accounting_entries'));
        add_action('wp_ajax_opfw_delete_accounting_entry', array($this, 'opfw_ajax_delete_opfw_accounting_entry'));
    }

    /**
     * Format currency using helper class
     *
     * @param float $amount The amount to format.
     * @return string
     */
    private function opfw_format_currency($amount)
    {
        return Obydullah_Restaurant_Sales_Terminal_Helpers::opfw_format_currency($amount);
    }

    /**
     * Format date using helper class
     *
     * @param string $date_string The date string to format.
     * @return string
     */
    private function opfw_format_date($date_string)
    {
        return Obydullah_Restaurant_Sales_Terminal_Helpers::opfw_format_date($date_string);
    }

    /**
     * Get accounting table name
     *
     * @return string
     */
    private function opfw_get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'opfw_accounting';
    }

    /**
     * Convert a date from the configured date format to MySQL Y-m-d
     */
    private function opfw_normalize_date($date)
    {
        if (empty($date)) {
            return '';
        }
        $date_format = Obydullah_Restaurant_Sales_Terminal_Helpers::opfw_get_date_format();
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
    public function opfw_render_page()
    {
        $current_date = $this->opfw_format_date(gmdate('Y-m-d'));
        ?>
<div class="wrap">
    <h1 class="wp-heading-inline mb-3">
        <?php esc_html_e('Accounting', 'obydullah-restaurant-sales-terminal'); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- Accounting Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-success">
                <h3 class="fs-6 fw-normal text-muted mb-2">
                    <?php esc_html_e('Total Income', 'obydullah-restaurant-sales-terminal'); ?>
                </h3>
                <p class="summary-number text-success mb-0 fs-3 fw-bold" id="total-income">
                    <?php echo esc_html($this->opfw_format_currency(0)); ?>
                </p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-danger">
                <h3 class="fs-6 fw-normal text-muted mb-2">
                    <?php esc_html_e('Total Expense', 'obydullah-restaurant-sales-terminal'); ?>
                </h3>
                <p class="summary-number text-danger mb-0 fs-3 fw-bold" id="total-expense">
                    <?php echo esc_html($this->opfw_format_currency(0)); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left: Add Accounting Entry Form -->
        <div class="col-lg-4 mb-4">
            <div class="bg-light p-4 rounded shadow-sm border h-100">
                <h2 class="fs-5 fw-semibold mb-3 mt-1 text-dark">
                    <?php esc_html_e('Add Accounting Entry', 'obydullah-restaurant-sales-terminal'); ?>
                </h2>
                <form id="add-accounting-form" method="post">
                    <?php wp_nonce_field('opfw_add_accounting_entry'); ?>

                    <div class="mb-3">
                        <!-- Income Amount -->
                        <div class="form-group mb-3">
                            <label for="in-amount" class="form-label fw-semibold">
                                <?php esc_html_e('Income Amount', 'obydullah-restaurant-sales-terminal'); ?>
                            </label>
                            <input name="in_amount" id="in-amount" type="number" step="0.01" min="0" value="0.00"
                                class="form-control form-control-sm" placeholder="0.00">
                        </div>

                        <!-- Expense Amount -->
                        <div class="form-group mb-3">
                            <label for="out-amount" class="form-label fw-semibold">
                                <?php esc_html_e('Expense Amount', 'obydullah-restaurant-sales-terminal'); ?>
                            </label>
                            <input name="out_amount" id="out-amount" type="number" step="0.01" min="0" value="0.00"
                                class="form-control form-control-sm" placeholder="0.00">
                        </div>

                        <!-- Description -->
                        <div class="form-group mb-3">
                            <label for="entry-description" class="form-label fw-semibold">
                                <?php esc_html_e('Description', 'obydullah-restaurant-sales-terminal'); ?>
                            </label>
                            <textarea name="description" id="entry-description" rows="3"
                                class="form-control form-control-sm"
                                placeholder="<?php esc_attr_e('Enter description for this entry (optional)', 'obydullah-restaurant-sales-terminal'); ?>"></textarea>
                        </div>

                        <!-- Date -->
                        <div class="form-group mb-3">
                            <label for="entry-date" class="form-label fw-semibold">
                                <?php esc_html_e('Date', 'obydullah-restaurant-sales-terminal'); ?>
                            </label>
                            <input name="entry_date" id="entry-date" type="date"
                                value="<?php echo esc_attr($current_date); ?>" class="form-control form-control-sm">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" id="submit-accounting" class="btn btn-primary w-100">
                            <span
                                class="btn-text"><?php esc_html_e('Save Entry', 'obydullah-restaurant-sales-terminal'); ?></span>
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
                    <?php esc_html_e('Accounting Entries', 'obydullah-restaurant-sales-terminal'); ?>
                </h2>

                <!-- Date Filter Section -->
                <div class="search-section mb-3">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="search-group flex-grow-1">
                            <label for="date-from" class="form-label mb-1">
                                <?php esc_html_e('Date Range', 'obydullah-restaurant-sales-terminal'); ?>
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="date" id="date-from" class="form-control form-control-sm"
                                        placeholder="<?php esc_attr_e('From Date', 'obydullah-restaurant-sales-terminal'); ?>">
                                    <span
                                        class="text-muted ml-1 mr-1"><?php esc_html_e('to', 'obydullah-restaurant-sales-terminal'); ?></span>
                                    <input type="date" id="date-to" class="form-control form-control-sm"
                                        placeholder="<?php esc_attr_e('To Date', 'obydullah-restaurant-sales-terminal'); ?>">
                                    <button type="button" id="search-entries" class="btn btn-sm btn-dark">
                                        <?php esc_html_e('Filter', 'obydullah-restaurant-sales-terminal'); ?>
                                    </button>
                                    <button type="button" id="reset-filters"
                                        class="btn btn-sm btn-outline-secondary ml-1">
                                        <?php esc_html_e('Reset', 'obydullah-restaurant-sales-terminal'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="form-text">
                                <?php esc_html_e('Filter entries by date range', 'obydullah-restaurant-sales-terminal'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accounting Entries Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered mb-2">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th><?php esc_html_e('Date', 'obydullah-restaurant-sales-terminal'); ?></th>
                                <th><?php esc_html_e('Description', 'obydullah-restaurant-sales-terminal'); ?></th>
                                <th width="120"><?php esc_html_e('Income', 'obydullah-restaurant-sales-terminal'); ?></th>
                                <th width="120"><?php esc_html_e('Expense', 'obydullah-restaurant-sales-terminal'); ?></th>
                                <th width="100" class="text-right">
                                    <?php esc_html_e('Actions', 'obydullah-restaurant-sales-terminal'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="accounting-list" class="bg-white">
                            <tr>
                                <td colspan="5" class="text-center p-4">
                                    <span class="spinner is-active"></span>
                                    <?php esc_html_e('Loading accounting entries...', 'obydullah-restaurant-sales-terminal'); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                    <div class="tablenav-pages">
                        <span class="displaying-num" id="displaying-num">0
                            <?php esc_html_e('items', 'obydullah-restaurant-sales-terminal'); ?></span>
                        <span class="pagination-links ms-2">
                            <a class="first-page btn btn-sm btn-dark" href="#"
                                title="<?php esc_attr_e('First page', 'obydullah-restaurant-sales-terminal'); ?>">«</a>
                            <a class="prev-page btn btn-sm btn-dark" href="#"
                                title="<?php esc_attr_e('Previous page', 'obydullah-restaurant-sales-terminal'); ?>">‹</a>
                            <span class="paging-input">
                                <input class="current-page form-control form-control-sm" id="current-page-selector"
                                    type="text" name="paged" value="1">
                                <span
                                    class="tablenav-paging-text"><?php esc_html_e('of', 'obydullah-restaurant-sales-terminal'); ?>
                                    <span class="total-pages">1</span></span>
                            </span>
                            <a class="next-page btn btn-sm btn-dark" href="#"
                                title="<?php esc_attr_e('Next page', 'obydullah-restaurant-sales-terminal'); ?>">›</a>
                            <a class="last-page btn btn-sm btn-dark" href="#"
                                title="<?php esc_attr_e('Last page', 'obydullah-restaurant-sales-terminal'); ?>">»</a>
                        </span>
                    </div>
                    <div class="tablenav-pages">
                        <select id="per-page-select" class="form-control form-control-sm">
                            <option value="10">10 <?php esc_html_e('per page', 'obydullah-restaurant-sales-terminal'); ?>
                            </option>
                            <option value="20">20 <?php esc_html_e('per page', 'obydullah-restaurant-sales-terminal'); ?>
                            </option>
                            <option value="50">50 <?php esc_html_e('per page', 'obydullah-restaurant-sales-terminal'); ?>
                            </option>
                            <option value="100">100 <?php esc_html_e('per page', 'obydullah-restaurant-sales-terminal'); ?>
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

   public function opfw_ajax_get_opfw_accounting_entries()
    {
        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'opfw_get_accounting_entries')) {
            wp_send_json_error(__('Security check failed.', 'obydullah-restaurant-sales-terminal'));
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-sales-terminal'));
        }

        $get = wp_unslash($_GET);

        $page     = isset($_GET['page']) ? max(1, intval(sanitize_text_field(wp_unslash($_GET['page'])))) : 1;
        $per_page = isset($_GET['per_page']) ? intval(sanitize_text_field(wp_unslash($_GET['per_page']))) : 10;

        $date_from = isset($get['date_from'])
            ? $this->opfw_normalize_date(sanitize_text_field($get['date_from']))
            : '';

        $date_to = isset($get['date_to'])
            ? $this->opfw_normalize_date(sanitize_text_field($get['date_to']))
            : '';

        $cache_key = 'entries_' . implode('_', [$page, $per_page, md5($date_from), md5($date_to)]);

        $response = wp_cache_get($cache_key, self::CACHE_GROUP);
        if (false !== $response) {
            wp_send_json_success($response);
        }

        global $wpdb;

        $offset = ($page - 1) * $per_page;

        $table_name = esc_sql($this->opfw_get_table_name());

        $where = [];

        if (!empty($date_from)) {
            $where[] = $wpdb->prepare('created_at >= %s', $date_from . ' 00:00:00');
        }

        if (!empty($date_to)) {
            $where[] = $wpdb->prepare('created_at <= %s', $date_to . ' 23:59:59');
        }

        $where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter -- WHERE fragments above are individually escaped with $wpdb->prepare()
        $summary = $wpdb->get_row(
            "SELECT COUNT(*) as total,
                    COALESCE(SUM(in_amount), 0) as total_income,
                    COALESCE(SUM(out_amount), 0) as total_expense
             FROM {$table_name}{$where_sql}"
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter

        $total = (int) ($summary->total ?? 0);

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter -- WHERE fragments above are individually escaped with $wpdb->prepare()
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                    "SELECT id, created_at, in_amount, out_amount, description
                     FROM {$table_name}{$where_sql}
                     ORDER BY created_at DESC
                     LIMIT %d OFFSET %d",
                array($per_page, $offset)
            )
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter

        if (!empty($entries)) {
            foreach ($entries as $entry) {
                $entry->formatted_date       = $this->opfw_format_date($entry->created_at);
                $entry->formatted_in_amount  = $this->opfw_format_currency($entry->in_amount);
                $entry->formatted_out_amount = $this->opfw_format_currency($entry->out_amount);
            }
        }

        $formatted_totals = [
            'total_income'  => $this->opfw_format_currency($summary->total_income ?? 0),
            'total_expense' => $this->opfw_format_currency($summary->total_expense ?? 0),
        ];

        $showing_from = $total > 0 ? $offset + 1 : 0;
        $showing_to   = min($offset + $per_page, $total);

        $response = [
            'entries'       => $entries,
            'total'         => $total,
            'showing_from'  => $showing_from,
            'showing_to'    => $showing_to,
            'current_page'  => $page,
            'per_page'      => $per_page,
            'totals'        => $formatted_totals,
        ];

        wp_cache_set($cache_key, $response, self::CACHE_GROUP);
        Obydullah_Restaurant_Sales_Terminal_Helpers::opfw_cache_register($cache_key, self::CACHE_GROUP);

        wp_send_json_success($response);
    }

    /** Add accounting entry */
    public function opfw_ajax_add_opfw_accounting_entry()
    {
        // Check nonce
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_add_accounting_entry')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-restaurant-sales-terminal'));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-sales-terminal'));
        }

        global $wpdb;
        $table = $this->opfw_get_table_name();

        $in_amount = floatval(sanitize_text_field(wp_unslash($_POST['in_amount'] ?? '')));
        $out_amount = floatval(sanitize_text_field(wp_unslash($_POST['out_amount'] ?? '')));
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $entry_date = sanitize_text_field(wp_unslash($_POST['entry_date'] ?? ''));

        // Validate that amounts are not negative
        if ($in_amount < 0 || $out_amount < 0) {
            wp_send_json_error(__('Amounts cannot be negative', 'obydullah-restaurant-sales-terminal'));
        }

        // Validate that at least one amount is entered
        if ($in_amount <= 0 && $out_amount <= 0) {
            wp_send_json_error(__('Please enter either income or expense amount', 'obydullah-restaurant-sales-terminal'));
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
            $normalized_date = $this->opfw_normalize_date($entry_date);
            if ($normalized_date) {
                $data['created_at'] = $normalized_date . ' ' . gmdate('H:i:s');
                $format[] = '%s';
            }
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table write; cache flushed after.
        $result = $wpdb->insert(
            $table,
            $data,
            $format
        );

        if (false === $result) {
            wp_send_json_error(__('Failed to add accounting entry', 'obydullah-restaurant-sales-terminal'));
        }

        Obydullah_Restaurant_Sales_Terminal_Helpers::opfw_cache_flush_group('opfw_accounting');
        Obydullah_Restaurant_Sales_Terminal_Helpers::opfw_cache_flush_group('opfw_dashboard');

        wp_send_json_success(__('Accounting entry added successfully', 'obydullah-restaurant-sales-terminal'));
    }

    /** Delete accounting entry */
    public function opfw_ajax_delete_opfw_accounting_entry()
    {
        // Check nonce
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_delete_accounting_entry')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-restaurant-sales-terminal'));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-sales-terminal'));
        }

        global $wpdb;
        $table = $this->opfw_get_table_name();
        $id = intval(sanitize_text_field(wp_unslash($_POST['id'] ?? '')));

        if (!$id) {
            wp_send_json_error(__('Invalid accounting entry ID', 'obydullah-restaurant-sales-terminal'));
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table write; cache flushed after.
        $result = $wpdb->delete(
            $table,
            ['id' => $id],
            ['%d']
        );

        if (false === $result) {
            wp_send_json_error(__('Failed to delete accounting entry', 'obydullah-restaurant-sales-terminal'));
        }

        if (0 === $result) {
            wp_send_json_error(__('Accounting entry not found', 'obydullah-restaurant-sales-terminal'));
        }

        Obydullah_Restaurant_Sales_Terminal_Helpers::opfw_cache_flush_group('opfw_accounting');
        Obydullah_Restaurant_Sales_Terminal_Helpers::opfw_cache_flush_group('opfw_dashboard');

        wp_send_json_success(__('Accounting entry deleted successfully', 'obydullah-restaurant-sales-terminal'));
    }
}