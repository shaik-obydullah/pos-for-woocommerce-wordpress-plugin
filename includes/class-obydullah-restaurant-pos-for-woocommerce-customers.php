<?php
/**
 * Customer Management
 *
 * @package Obydullah_Restaurant_POS_For_WooCommerce
 * @since   1.0.0
 * @version 1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_POS_For_WooCommerce_Customers
{
    /**
     * Object cache group used for customer data.
     *
     * @since 1.0.0
     * @var string
     */
    const CACHE_GROUP = 'opfw_customers';

    /**
     * Customers table name.
     *
     * @since 1.0.0
     * @var string
     */
    private $customers_table;

    public function __construct()
    {
        global $wpdb;
        $this->customers_table = $wpdb->prefix . 'opfw_customers';

        add_action('wp_ajax_opfw_get_customers', [$this, 'opfw_ajax_get_customers']);
        add_action('wp_ajax_opfw_add_customer', [$this, 'opfw_ajax_add_customer']);
        add_action('wp_ajax_opfw_update_customer', [$this, 'opfw_ajax_update_customer']);
        add_action('wp_ajax_opfw_delete_customer', [$this, 'opfw_ajax_delete_customer']);
    }

    public function opfw_render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Customer Management', 'obydullah-restaurant-pos-for-woocommerce'); ?></h1>
            <hr class="wp-header-end">

            <div class="row">
                <div class="col-lg-4">
                    <div class="bg-light p-4 rounded shadow-sm">
                        <h3 id="opfw-form-title" class="mb-3"><?php esc_html_e('Add Customer', 'obydullah-restaurant-pos-for-woocommerce'); ?></h3>
                        <form id="opfw-customer-form" class="opfw-customer-form">
                            <input type="hidden" id="opfw-customer-id" value="">
                            <div class="form-group mb-3">
                                <label for="opfw-customer-name" class="form-label"><?php esc_html_e('Name', 'obydullah-restaurant-pos-for-woocommerce'); ?></label>
                                <input type="text" id="opfw-customer-name" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="opfw-customer-email" class="form-label"><?php esc_html_e('Email', 'obydullah-restaurant-pos-for-woocommerce'); ?></label>
                                <input type="email" id="opfw-customer-email" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="opfw-customer-mobile" class="form-label"><?php esc_html_e('Mobile', 'obydullah-restaurant-pos-for-woocommerce'); ?></label>
                                <input type="text" id="opfw-customer-mobile" class="form-control">
                            </div>
                            <div class="form-group mb-3">
                                <label for="opfw-customer-address" class="form-label"><?php esc_html_e('Address', 'obydullah-restaurant-pos-for-woocommerce'); ?></label>
                                <textarea id="opfw-customer-address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label for="opfw-customer-status" class="form-label"><?php esc_html_e('Status', 'obydullah-restaurant-pos-for-woocommerce'); ?></label>
                                <select id="opfw-customer-status" class="form-control">
                                    <option value="active"><?php esc_html_e('Active', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                    <option value="inactive"><?php esc_html_e('Inactive', 'obydullah-restaurant-pos-for-woocommerce'); ?></option>
                                </select>
                            </div>
                            <button type="submit" id="opfw-customer-submit" class="btn btn-primary">
                                <?php esc_html_e('Add Customer', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                            </button>
                            <button type="button" id="opfw-customer-cancel" class="btn btn-secondary opfw-hidden">
                                <?php esc_html_e('Cancel', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="bg-light p-4 rounded shadow-sm">
                        <table class="table table-striped table-hover opfw-table align-middle">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Name', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                    <th><?php esc_html_e('Email', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                    <th><?php esc_html_e('Mobile', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                    <th><?php esc_html_e('Status', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                    <th><?php esc_html_e('Actions', 'obydullah-restaurant-pos-for-woocommerce'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="opfw-customers-list">
                                <tr>
                                    <td colspan="5">
                                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                                        <?php esc_html_e('Loading customers...', 'obydullah-restaurant-pos-for-woocommerce'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function opfw_ajax_get_customers()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_get_customers')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        global $wpdb;

        $customers = Obydullah_POS_For_WooCommerce_Helpers::opfw_cache_get_or_set('customers', function () {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cached above, escaped table name
            return $wpdb->get_results("SELECT * FROM " . esc_sql($this->customers_table) . " ORDER BY created_at DESC");
        }, self::CACHE_GROUP);

        wp_send_json_success($customers);
    }

    public function opfw_ajax_add_customer()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_add_customer')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $mobile = sanitize_text_field(wp_unslash($_POST['mobile'] ?? ''));
        $address = sanitize_textarea_field(wp_unslash($_POST['address'] ?? ''));
        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'active'));

        if (empty($name)) {
            wp_send_json_error(__('Customer name is required', 'obydullah-restaurant-pos-for-woocommerce'));
        }
        if (empty($email)) {
            wp_send_json_error(__('Customer email is required', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        global $wpdb;

        $customer_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->customers_table} WHERE email = %s LIMIT 1",
            $email
        )); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        if ($customer_exists) {
            wp_send_json_error(__('A customer with this email already exists', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $result = $wpdb->insert(
            $this->customers_table,
            [
                'name'    => $name,
                'email'   => $email,
                'mobile'  => $mobile,
                'address' => $address,
                'status'  => $status
            ],
            ['%s', '%s', '%s', '%s', '%s']
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        if (false === $result) {
            wp_send_json_error(__('Failed to add customer', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        Obydullah_POS_For_WooCommerce_Helpers::opfw_cache_flush_group(self::CACHE_GROUP);

        wp_send_json_success(__('Customer added successfully', 'obydullah-restaurant-pos-for-woocommerce'));
    }

    public function opfw_ajax_update_customer()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_update_customer')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $id = intval(sanitize_text_field(wp_unslash($_POST['id'] ?? '')));
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $mobile = sanitize_text_field(wp_unslash($_POST['mobile'] ?? ''));
        $address = sanitize_textarea_field(wp_unslash($_POST['address'] ?? ''));
        $status = sanitize_text_field(wp_unslash($_POST['status'] ?? 'active'));

        if (!$id) {
            wp_send_json_error(__('Invalid customer ID', 'obydullah-restaurant-pos-for-woocommerce'));
        }
        if (empty($name)) {
            wp_send_json_error(__('Customer name is required', 'obydullah-restaurant-pos-for-woocommerce'));
        }
        if (empty($email)) {
            wp_send_json_error(__('Customer email is required', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        global $wpdb;

        $duplicate = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->customers_table} WHERE email = %s AND id != %d LIMIT 1",
            $email,
            $id
        )); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        if ($duplicate) {
            wp_send_json_error(__('A customer with this email already exists', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $result = $wpdb->update(
            $this->customers_table,
            [
                'name'    => $name,
                'email'   => $email,
                'mobile'  => $mobile,
                'address' => $address,
                'status'  => $status
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%s'],
            ['%d']
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        if (false === $result) {
            wp_send_json_error(__('Failed to update customer', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        Obydullah_POS_For_WooCommerce_Helpers::opfw_cache_flush_group(self::CACHE_GROUP);

        wp_send_json_success(__('Customer updated successfully', 'obydullah-restaurant-pos-for-woocommerce'));
    }

    public function opfw_ajax_delete_customer()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'obydullah-restaurant-pos-for-woocommerce'));
        }
        $opfw_nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if (!wp_verify_nonce($opfw_nonce, 'opfw_delete_customer')) {
            wp_die(esc_html__('Security check failed.', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        $id = intval(sanitize_text_field(wp_unslash($_POST['id'] ?? '')));
        if (!$id) {
            wp_send_json_error(__('Invalid customer ID', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        global $wpdb;

        $result = $wpdb->delete(
            $this->customers_table,
            ['id' => $id],
            ['%d']
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        if (false === $result) {
            wp_send_json_error(__('Failed to delete customer', 'obydullah-restaurant-pos-for-woocommerce'));
        }

        Obydullah_POS_For_WooCommerce_Helpers::opfw_cache_flush_group(self::CACHE_GROUP);

        wp_send_json_success(__('Customer deleted successfully', 'obydullah-restaurant-pos-for-woocommerce'));
    }
}