/**
 * Customer Management
 * Plugin: Obydullah_POS_For_WooCommerce
 * Version: 1.0.0
 */
(function ($) {
  "use strict";
  let OPFWCustomers = {
    // Configuration (will be populated from wp_localize_script)
    config: {
      isSubmitting: false,
      editingId: 0,
      ajaxUrl: "",
      strings: {},
      getNonce: "",
      addNonce: "",
      updateNonce: "",
      deleteNonce: "",
    },

    /**
     * Initialize customers module
     * Called from document ready
     */
    init: function () {
      // Load configuration from localized script
      if (typeof opfwCustomers !== "undefined") {
        this.config.ajaxUrl = opfwCustomers.ajaxUrl || "";
        this.config.getNonce = opfwCustomers.getNonce || "";
        this.config.addNonce = opfwCustomers.addNonce || "";
        this.config.updateNonce = opfwCustomers.updateNonce || "";
        this.config.deleteNonce = opfwCustomers.deleteNonce || "";
        this.config.strings = opfwCustomers.strings || {};
      }

      // Bind events
      this.bindEvents();

      // Load initial customers
      this.loadCustomers();
    },

    /**
     * Bind all event handlers
     */
    bindEvents: function () {
      var self = this;

      // Form submission
      $("#opfw-customer-form").on("submit", function (e) {
        e.preventDefault();
        self.handleCustomerSubmit();
      });

      // Cancel editing
      $("#opfw-customer-cancel").on("click", function () {
        self.resetForm();
      });

      // Edit customer (delegated)
      $(document).on("click", ".opfw-action.edit", function () {
        self.handleEditCustomer(this);
      });

      // Delete customer (delegated)
      $(document).on("click", ".opfw-action.delete", function () {
        self.handleDeleteCustomer(this);
      });
    },

    /**
     * Set loading state for submit button
     */
    setButtonLoading: function (loading) {
      let button = $("#opfw-customer-submit");
      if (loading) {
        button.prop("disabled", true).text(this.config.strings.saving || "Saving...");
      } else {
        button.prop("disabled", false).text(
          this.config.editingId ? this.config.strings.update : this.config.strings.add
        );
      }
    },

    /**
     * Reset form to initial state
     */
    resetForm: function () {
      this.config.editingId = 0;
      $("#opfw-customer-id").val("");
      $("#opfw-customer-name").val("");
      $("#opfw-customer-email").val("");
      $("#opfw-customer-mobile").val("");
      $("#opfw-customer-address").val("");
      $("#opfw-customer-status").val("active");
      $("#opfw-form-title").text(this.config.strings.add);
      $("#opfw-customer-cancel").addClass("opfw-hidden");
      this.setButtonLoading(false);
    },

    /**
     * Load customers
     */
    loadCustomers: function () {
      var self = this;

      let tbody = $("#opfw-customers-list");
      tbody.html(
        '<tr><td colspan="5" class="loading-customers"><span class="spinner-border spinner-border-sm text-primary"></span> ' +
          (self.config.strings.loadingCustomers || "Loading customers...") +
          "</td></tr>"
      );

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_customers",
          _wpnonce: self.config.getNonce,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.length) {
              tbody.append(
                '<tr><td colspan="5" class="no-customers">' +
                  (self.config.strings.noCustomers || "No customers found.") +
                  "</td></tr>"
              );
              return;
            }

            $.each(response.data, function (_, customer) {
              let row = $("<tr>")
                .attr("data-customer-id", customer.id)
                .attr("data-address", customer.address || "")
                .attr("data-status", customer.status || "active");

              row.append($("<td>").text(customer.name));
              row.append($("<td>").text(customer.email));
              row.append($("<td>").text(customer.mobile || "-"));
              row.append($("<td>").append($("<span>").addClass("opfw-status-badge").text(customer.status)));

              // Actions column
              row.append(
                $("<td>")
                  .addClass("pos-row-actions")
                  .append(
                    $("<button>")
                      .addClass("opfw-action edit")
                      .text(self.config.strings.edit || "Edit")
                      .attr("data-id", customer.id),
                    $("<button>")
                      .addClass("opfw-action delete")
                      .text(self.config.strings.delete || "Delete")
                      .attr("data-id", customer.id)
                  )
              );

              tbody.append(row);
            });
          } else {
            tbody.append(
              '<tr><td colspan="5" class="error-message">' + response.data + "</td></tr>"
            );
          }
        },
        error: function () {
          tbody.html(
            '<tr><td colspan="5" class="error-message">' +
              (self.config.strings.requestFailed || "Request failed. Please try again.") +
              "</td></tr>"
          );
        },
      });
    },

    /**
     * Handle form submission (add or update)
     */
    handleCustomerSubmit: function () {
      var self = this;

      // Prevent double submission
      if (self.config.isSubmitting) {
        return false;
      }

      let id = parseInt($("#opfw-customer-id").val()) || 0;
      let name = $("#opfw-customer-name").val();
      let email = $("#opfw-customer-email").val();
      let mobile = $("#opfw-customer-mobile").val();
      let address = $("#opfw-customer-address").val();
      let status = $("#opfw-customer-status").val();

      // HTML5 validation handles required name/email
      self.config.isSubmitting = true;
      self.setButtonLoading(true);

      $.post(
        self.config.ajaxUrl,
        {
          action: id ? "opfw_update_customer" : "opfw_add_customer",
          id: id,
          name: name,
          email: email,
          mobile: mobile,
          address: address,
          status: status,
          _wpnonce: id ? self.config.updateNonce : self.config.addNonce,
        },
        function (res) {
          if (res.success) {
            self.resetForm();
            self.loadCustomers();
            showLimeModal(res.data, "Success");
          } else {
            showLimeModal(res.data, "Error");
          }
        }
      )
        .fail(function () {
          showLimeModal(self.config.strings.requestFailed || "Request failed. Please try again.", "Error");
        })
        .always(function () {
          self.config.isSubmitting = false;
          self.setButtonLoading(false);
        });
    },

    /**
     * Handle edit customer (populate form)
     */
    handleEditCustomer: function (button) {
      var self = this;
      var id = $(button).data("id");
      var row = $(button).closest("tr");
      var cells = row.find("td");

      if (!id || row.length === 0) {
        return;
      }

      self.config.editingId = id;
      $("#opfw-customer-id").val(id);
      $("#opfw-customer-name").val(cells.eq(0).text());
      $("#opfw-customer-email").val(cells.eq(1).text());
      $("#opfw-customer-mobile").val(cells.eq(2).text() === "-" ? "" : cells.eq(2).text());
      $("#opfw-customer-address").val(row.data("address") || "");
      $("#opfw-customer-status").val(row.data("status") || "active");
      $("#opfw-form-title").text(self.config.strings.update);
      $("#opfw-customer-cancel").removeClass("opfw-hidden");
      $("#opfw-customer-name").focus();
    },

    /**
     * Handle delete customer
     */
    handleDeleteCustomer: function (button) {
      var self = this;
      var $button = $(button);
      var originalText = $button.text();
      var id = $button.data("id");

      showLimeConfirm(
        self.config.strings.confirmDelete || "Are you sure you want to delete this customer?",
        function onYes() {
          $button.prop("disabled", true).text(self.config.strings.deleting || "Deleting...");

          $.post(self.config.ajaxUrl, {
            action: "opfw_delete_customer",
            id: id,
            _wpnonce: self.config.deleteNonce,
          })
            .done(function (res) {
              if (res.success) {
                self.loadCustomers();
                showLimeModal(res.data, "Success");
              } else {
                showLimeModal(res.data, "Error");
              }
            })
            .fail(function () {
              showLimeModal(self.config.strings.requestFailed || "Request failed. Please try again.", "Error");
            })
            .always(function () {
              $button.prop("disabled", false).text(originalText);
            });
        },
        "Confirm Delete"
      );
    },
  };

  /**
   * Initialize when document is ready
   */
  $(document).ready(function () {
    if ($("#opfw-customer-form").length) {
      OPFWCustomers.init();
    }
  });
})(jQuery);