/**
 * Stock Adjustments Manager — WooCommerce
 * Plugin: Obydullah_POS_For_WooCommerce
 * Version: 2.0.0
 */
(function ($) {
  "use strict";

  let OPFWStockAdjustments = {
    config: {
      isSubmitting: false,
      addNonce: "",
      getNonce: "",
      deleteNonce: "",
      getProductsNonce: "",
      getStockNonce: "",
      ajaxUrl: "",
      strings: {},
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalItems: 0,
      searchTerm: "",
      typeFilter: "",
      dateFilter: "",
    },

    init: function () {
      if (typeof opfwStockAdjustments !== "undefined") {
        this.config.addNonce = opfwStockAdjustments.addNonce || "";
        this.config.getNonce = opfwStockAdjustments.getNonce || "";
        this.config.deleteNonce = opfwStockAdjustments.deleteNonce || "";
        this.config.getProductsNonce = opfwStockAdjustments.getProductsNonce || "";
        this.config.getStockNonce = opfwStockAdjustments.getStockNonce || "";
        this.config.ajaxUrl = opfwStockAdjustments.ajaxUrl || "";
        this.config.strings = opfwStockAdjustments.strings || {};
      }

      this.bindEvents();
      this.loadStocks();
      this.loadAdjustments(1);
    },

    bindEvents: function () {
      var self = this;

      $("#add-adjustment-form").on("submit", function (e) {
        e.preventDefault();
        self.handleAdjustmentSubmit();
      });

      $("#adjustment-product").on("change", function () {
        var productId = $(this).val();
        var selectedOption = $(this).find("option:selected");
        var buyPrice = selectedOption.data("buy-price") || 0;

        $("#buy-price").text(parseFloat(buyPrice).toFixed(2));

        if (productId) {
          self.loadCurrentStock(productId);
        } else {
          $("#current-stock").text("0");
          self.calculateNewStock();
        }
      });

      $("#adjustment-type, #adjustment-quantity").on("change input", function () {
        self.calculateNewStock();
      });

      var searchTimeout;
      $("#adjustment-search").on("input", function () {
        clearTimeout(searchTimeout);
        self.config.searchTerm = $(this).val().trim();
        searchTimeout = setTimeout(function () {
          self.loadAdjustments(1);
        }, 500);
      });

      $("#type-filter").on("change", function () {
        self.config.typeFilter = $(this).val();
        self.loadAdjustments(1);
      });

      $("#date-filter").on("change", function () {
        self.config.dateFilter = $(this).val();
        self.loadAdjustments(1);
      });

      $("#per-page-select").on("change", function () {
        self.config.perPage = parseInt($(this).val());
        self.loadAdjustments(1);
      });

      $("#refresh-adjustments").on("click", function () {
        self.loadAdjustments(self.config.currentPage);
      });

      $("#reset-filters").on("click", function () {
        self.resetFilters();
      });

      $(document).on("click", ".first-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadAdjustments(1);
      });

      $(document).on("click", ".prev-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadAdjustments(self.config.currentPage - 1);
      });

      $(document).on("click", ".next-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadAdjustments(self.config.currentPage + 1);
      });

      $(document).on("click", ".last-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadAdjustments(self.config.totalPages);
      });

      $(document).on("keypress", "#current-page-selector", function (e) {
        if (e.which === 13) {
          var page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.loadAdjustments(page);
          }
        }
      });

      $(document).on("click", ".pos-action.delete", function () {
        self.handleDeleteAdjustment(this);
      });
    },

    loadStocks: function () {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_products_for_adjustments",
          _wpnonce: self.config.getProductsNonce,
        },
        success: function (response) {
          if (response.success) {
            var select = $("#adjustment-product");
            select.empty().append('<option value="">' + (self.config.strings.selectStock || "Select Product") + "</option>");

            $.each(response.data, function (_, stock) {
              var option = $("<option>")
                .val(stock.product_id)
                .text(stock.name)
                .data("buy-price", stock.buy_price);
              select.append(option);
            });
          }
        },
      });
    },

    loadCurrentStock: function (productId) {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_current_stock",
          product_id: productId,
          _wpnonce: self.config.getStockNonce,
        },
        success: function (response) {
          if (response.success) {
            $("#current-stock").text(response.data.current_stock || 0);
            self.calculateNewStock();
          }
        },
      });
    },

    loadAdjustments: function (page) {
      var self = this;
      self.config.currentPage = page || self.config.currentPage || 1;

      var tbody = $("#adjustment-list");
      tbody.html('<tr><td colspan="8" class="loading-stocks"><span class="spinner is-active"></span> ' + (self.config.strings.loadingAdjustments || "Loading adjustments...") + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_stock_adjustments",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          search: self.config.searchTerm,
          type: self.config.typeFilter,
          date: self.config.dateFilter,
          _wpnonce: self.config.getNonce,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.adjustments.length) {
              tbody.append('<tr><td colspan="8" class="no-stocks">' + (self.config.strings.noAdjustments || "No adjustments found.") + "</td></tr>");
              self.updatePagination(response.data.pagination);
              return;
            }

            $.each(response.data.adjustments, function (_, adjustment) {
              var row = $("<tr>").attr("data-adjustment-id", adjustment.id);

              var date = new Date(adjustment.created_at);
              var formattedDate = date.toLocaleDateString() + " " + date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
              row.append($("<td>").text(formattedDate));

              row.append($("<td>").text(adjustment.product_name || "N/A"));

              var typeClass = adjustment.adjustment_type === "increase" ? "badge bg-success" : "badge bg-danger";
              var typeText = adjustment.adjustment_type === "increase" ? (self.config.strings.increase || "Increase") : (self.config.strings.decrease || "Decrease");
              row.append(
                $("<td>").addClass("compact-status").append(
                  $("<span>").addClass(typeClass + " badge-status").text(typeText)
                )
              );

              var quantityText = (adjustment.adjustment_type === "increase" ? "+" : "-") + adjustment.quantity;
              var quantityClass = adjustment.adjustment_type === "increase" ? "quantity-increase" : "quantity-decrease";
              row.append($("<td>").addClass(quantityClass).text(quantityText));

              row.append($("<td>").text(adjustment.old_quantity || "0"));
              row.append($("<td>").text(adjustment.new_quantity || "0"));
              row.append($("<td>").text(adjustment.note || "-"));

              row.append(
                $("<td>").addClass("pos-row-actions").append(
                  $("<button>").addClass("pos-action delete").text(self.config.strings.delete || "Delete").attr("data-id", adjustment.id)
                )
              );

              tbody.append(row);
            });

            self.updatePagination(response.data.pagination);
          } else {
            tbody.append('<tr><td colspan="8" class="error-message">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          tbody.html('<tr><td colspan="8" class="error-message">' + (self.config.strings.loadError || "Failed to load adjustments.") + "</td></tr>");
        },
      });
    },

    updatePagination: function (pagination) {
      this.config.totalPages = pagination.total_pages || 1;
      this.config.totalItems = pagination.total_items || 0;

      $("#displaying-num").text(pagination.total_items + " " + (this.config.strings.items || "items"));
      $("#current-page-selector").val(this.config.currentPage);
      $(".total-pages").text(this.config.totalPages);
      $(".first-page, .prev-page").prop("disabled", this.config.currentPage === 1);
      $(".next-page, .last-page").prop("disabled", this.config.currentPage === this.config.totalPages);
    },

    calculateNewStock: function () {
      var currentStock = parseInt($("#current-stock").text()) || 0;
      var adjustmentType = $("#adjustment-type").val();
      var quantity = parseInt($("#adjustment-quantity").val()) || 0;

      var adjustmentDisplay = (adjustmentType === "increase" ? "+" : "-") + quantity;
      var newStock = adjustmentType === "increase" ? currentStock + quantity : currentStock - quantity;

      $("#adjustment-display").text(adjustmentDisplay);
      $("#new-stock").text(newStock);

      $("#adjustment-display")
        .toggleClass("text-success", adjustmentType === "increase")
        .toggleClass("text-danger", adjustmentType === "decrease");

      $("#new-stock")
        .toggleClass("text-danger", newStock < 0)
        .toggleClass("text-warning", newStock === 0)
        .toggleClass("text-primary", newStock > 0);
    },

    handleAdjustmentSubmit: function () {
      var self = this;

      if (self.config.isSubmitting) return false;

      var productId = $("#adjustment-product").val();
      var adjustmentType = $("#adjustment-type").val();
      var quantity = $("#adjustment-quantity").val();
      var note = $("#adjustment-note").val();

      if (!productId) {
        showLimeModal(self.config.strings.selectStockError || "Please select a product", "Validation Error");
        return false;
      }
      if (quantity <= 0) {
        showLimeModal(self.config.strings.invalidQuantity || "Quantity must be greater than 0", "Validation Error");
        return false;
      }

      var currentStock = parseInt($("#current-stock").text()) || 0;
      var newStock = adjustmentType === "increase" ? currentStock + parseInt(quantity) : currentStock - parseInt(quantity);

      if (newStock < 0) {
        showLimeConfirm(
          self.config.strings.negativeStockConfirm || "This will result in negative stock. Continue?",
          function onYes() {
            self.performAdjustment(productId, adjustmentType, quantity, note);
          },
          "Confirm"
        );
        return;
      }

      self.performAdjustment(productId, adjustmentType, quantity, note);
    },

    performAdjustment: function (productId, adjustmentType, quantity, note) {
      var self = this;

      self.config.isSubmitting = true;
      self.setButtonLoading(true);

      $.post(self.config.ajaxUrl, {
        action: "opfw_add_stock_adjustment",
        product_id: productId,
        adjustment_type: adjustmentType,
        quantity: quantity,
        note: note,
        _wpnonce: self.config.addNonce,
      })
        .done(function (response) {
          if (response.success) {
            self.resetForm();
            self.loadAdjustments(1);
            if (productId) self.loadCurrentStock(productId);
          } else {
            showLimeModal(self.config.strings.error + ": " + response.data, "Error");
          }
        })
        .fail(function () {
          showLimeModal(self.config.strings.requestFailed || "Request failed.", "Error");
        })
        .always(function () {
          self.config.isSubmitting = false;
          self.setButtonLoading(false);
        });
    },

    handleDeleteAdjustment: function (button) {
      var self = this;
      var $button = $(button);
      var originalText = $button.text();
      var id = $button.closest("tr").data("adjustment-id");

      if (!id) return;

      showLimeConfirm(
        self.config.strings.confirmDelete || "Are you sure you want to delete this adjustment?",
        function onYes() {
          $button.prop("disabled", true).text(self.config.strings.deleting || "Deleting...");

          $.post(self.config.ajaxUrl, {
            action: "opfw_delete_stock_adjustment",
            id: id,
            _wpnonce: self.config.deleteNonce,
          })
            .done(function (response) {
              if (response.success) {
                self.loadAdjustments(self.config.currentPage);
                showLimeModal(response.data || "Adjustment deleted.", "Success");
              } else {
                $button.prop("disabled", false).text(originalText);
                showLimeModal(response.data || "Delete failed.", "Error");
              }
            })
            .fail(function () {
              $button.prop("disabled", false).text(originalText);
              showLimeModal(self.config.strings.deleteFailed || "Delete request failed.", "Error");
            });
        },
        self.config.strings.confirmTitle || "Confirm Delete"
      );
    },

    resetFilters: function () {
      $("#adjustment-search").val("");
      $("#type-filter").val("");
      $("#date-filter").val("");
      this.config.searchTerm = "";
      this.config.typeFilter = "";
      this.config.dateFilter = "";
      this.loadAdjustments(1);
    },

    setButtonLoading: function (loading) {
      var button = $("#submit-adjustment");
      var spinner = button.find(".spinner");
      var btnText = button.find(".btn-text");

      if (loading) {
        button.prop("disabled", true);
        spinner.show();
        btnText.text(this.config.strings.applying || "Applying...");
      } else {
        button.prop("disabled", false);
        spinner.hide();
        btnText.text(this.config.strings.applyAdjustment || "Apply Adjustment");
      }
    },

    resetForm: function () {
      $("#adjustment-quantity").val("1");
      $("#adjustment-note").val("");
      this.calculateNewStock();
      this.setButtonLoading(false);
    },
  };

  $(document).ready(function () {
    if ($("#add-adjustment-form").length) {
      OPFWStockAdjustments.init();
    }
  });
})(jQuery);
