/**
 * Sales Management — WooCommerce Orders
 * Plugin: Obydullah_POS_For_WooCommerce
 * Version: 2.0.0
 */
(function ($) {
  "use strict";
  let OPFWSales = {
    config: {
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalItems: 0,
      currentSearch: "",
      dateFrom: "",
      dateTo: "",
      saleType: "",
      saleStatus: "",
      ajaxUrl: "",
      strings: {},
      currencySymbol: "$",
      shopInfo: {},
      nonces: {},
      searchTimeout: null,
    },

    init: function () {
      if (typeof opfwSalesData !== "undefined") {
        this.config.ajaxUrl = opfwSalesData.ajaxUrl || "";
        this.config.nonces = {
          get_sales: opfwSalesData.nonce_get_sales || "",
          print_sale: opfwSalesData.nonce_print_sale || "",
          delete_sale: opfwSalesData.nonce_delete_sale || "",
        };
        this.config.currencySymbol = opfwSalesData.currency_symbol || "$";
        this.config.shopInfo = opfwSalesData.shop_info || {};
        this.config.strings = opfwSalesData.strings || {};
      }

      this.bindEvents();
      this.loadOPFWSales();
    },

    bindEvents: function () {
      var self = this;

      $("#search-sales").on("click", function () {
        self.config.currentSearch = $("#search-invoice").val().trim();
        self.config.dateFrom = $("#date-from").val();
        self.config.dateTo = $("#date-to").val();
        self.config.saleType = $("#sale-type").val();
        self.config.saleStatus = $("#sale-status").val();
        self.config.currentPage = 1;
        self.loadOPFWSales();
      });

      $("#reset-filters").on("click", function () {
        $("#search-invoice").val("");
        $("#date-from").val("");
        $("#date-to").val("");
        $("#sale-type").val("");
        $("#sale-status").val("");
        self.config.currentSearch = "";
        self.config.dateFrom = "";
        self.config.dateTo = "";
        self.config.saleType = "";
        self.config.saleStatus = "";
        self.config.currentPage = 1;
        self.loadOPFWSales();
      });

      $("#search-invoice").on("input", function () {
        clearTimeout(self.config.searchTimeout);
        self.config.currentSearch = $(this).val().trim();
        self.config.searchTimeout = setTimeout(function () {
          self.config.currentPage = 1;
          self.loadOPFWSales();
        }, 500);
      });

      $("#per-page-select").on("change", function () {
        self.config.perPage = parseInt($(this).val());
        self.config.currentPage = 1;
        self.loadOPFWSales();
      });

      $(document).on("click", ".first-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) {
          self.config.currentPage = 1;
          self.loadOPFWSales();
        }
      });

      $(document).on("click", ".prev-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) {
          self.config.currentPage--;
          self.loadOPFWSales();
        }
      });

      $(document).on("click", ".next-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) {
          self.config.currentPage++;
          self.loadOPFWSales();
        }
      });

      $(document).on("click", ".last-page", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) {
          self.config.currentPage = self.config.totalPages;
          self.loadOPFWSales();
        }
      });

      $("#current-page-selector").on("keypress", function (e) {
        if (e.which === 13) {
          var page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.config.currentPage = page;
            self.loadOPFWSales();
          }
        }
      });

      $(document).on("click", ".pos-action.print", function () {
        self.handlePrintSale(this);
      });

      $(document).on("click", ".pos-action.delete", function () {
        self.handleDeleteSale(this);
      });
    },

    formatCurrency: function (amount) {
      var sym = this.config.currencySymbol || "$";
      return sym + parseFloat(amount || 0).toFixed(2);
    },

    formatSaleType: function (saleType) {
      if (!saleType) return "N/A";
      if (saleType === "dineIn") return "Dine In";
      if (saleType === "takeAway") return "Take Away";
      if (saleType === "pickup") return "Pickup";
      return saleType;
    },

    formatSaleStatus: function (status) {
      if (!status) return "N/A";
      return status.charAt(0).toUpperCase() + status.slice(1);
    },

    updatePagination: function (pagination) {
      this.config.totalPages = pagination.total_pages || 1;
      this.config.totalItems = pagination.total || 0;

      $("#displaying-num").text(pagination.total + " " + (this.config.strings.items || "items"));
      $("#current-page-selector").val(this.config.currentPage);
      $(".total-pages").text(this.config.totalPages);
      $(".first-page, .prev-page").prop("disabled", this.config.currentPage === 1);
      $(".next-page, .last-page").prop("disabled", this.config.currentPage === this.config.totalPages);
    },

    loadOPFWSales: function () {
      var self = this;

      var tbody = $("#sales-list");
      tbody.html('<tr><td colspan="7" class="loading-sales"><span class="spinner is-active"></span> ' + (self.config.strings.loading_sales || "Loading sales...") + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_sales",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          search: self.config.currentSearch,
          date_from: self.config.dateFrom,
          date_to: self.config.dateTo,
          sale_type: self.config.saleType,
          status: self.config.saleStatus,
          _wpnonce: self.config.nonces.get_sales,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.sales || response.data.sales.length === 0) {
              tbody.append('<tr><td colspan="7" class="no-sales">' + (self.config.strings.no_sales || "No sales found.") + "</td></tr>");
              self.updatePagination(response.data);
              return;
            }

            $.each(response.data.sales, function (_, sale) {
              var row = $("<tr>").attr("data-sale-id", sale.id);

              row.append($("<td>").text(sale.invoice_id || "N/A"));

              var saleDate = new Date(sale.created_at);
              var formattedDate = saleDate.toLocaleDateString() + " " + saleDate.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
              row.append($("<td>").text(formattedDate));

              row.append($("<td>").text(sale.customer_name || "Walk-in"));

              var typeClass = sale.sale_type === "dineIn" ? "badge bg-primary" : sale.sale_type === "takeAway" ? "badge bg-info" : "badge bg-warning";
              row.append(
                $("<td>").addClass("compact-status").append(
                  $("<span>").addClass(typeClass + " badge-status").text(self.formatSaleType(sale.sale_type))
                )
              );

              row.append($("<td>").text(self.formatCurrency(sale.grand_total)));

              var statusClass = sale.status === "completed" ? "badge bg-success" : sale.status === "pending" ? "badge bg-warning" : sale.status === "cancelled" ? "badge bg-danger" : "badge bg-secondary";
              row.append(
                $("<td>").addClass("compact-status").append(
                  $("<span>").addClass(statusClass + " badge-status").text(self.formatSaleStatus(sale.status))
                )
              );

              row.append(
                $("<td>").addClass("pos-row-actions")
                  .append($("<button>").addClass("pos-action print").text(self.config.strings.print || "Print").attr("data-id", sale.id))
                  .append($("<button>").addClass("pos-action delete").text(self.config.strings.delete || "Delete").attr("data-id", sale.id))
              );

              tbody.append(row);
            });

            self.updatePagination(response.data);
          } else {
            tbody.append('<tr><td colspan="7" class="error-message">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          tbody.html('<tr><td colspan="7" class="error-message">' + (self.config.strings.failed_load || "Failed to load sales.") + "</td></tr>");
        },
      });
    },

    handlePrintSale: function (button) {
      var self = this;
      var saleId = $(button).closest("tr").data("sale-id");

      if (!saleId) {
        return;
      }

      $.ajax({
        url: self.config.ajaxUrl,
        type: "POST",
        data: {
          action: "opfw_print_sale",
          sale_id: saleId,
          _wpnonce: self.config.nonces.print_sale,
        },
        success: function (response) {
          if (response.success) {
            var sale = response.data;
            var shop = sale.shop_info || self.config.shopInfo || {};

            var printContent = '<!DOCTYPE html><html><head><title>Invoice #' + (sale.invoice_id || sale.id) + '</title>';
            printContent += '<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:"Courier New",monospace;font-size:14px;line-height:1.3;padding:10px;max-width:80mm;margin:0 auto}';
            printContent += '.header{text-align:center;margin-bottom:10px;padding-bottom:5px;border-bottom:1px dashed #000}';
            printContent += '.shop-name{font-weight:bold;font-size:18px;text-transform:uppercase}';
            printContent += '.shop-address,.shop-phone{font-size:12px;margin:2px 0}';
            printContent += '.invoice-title{font-weight:bold;text-align:center;margin:5px 0;font-size:16px}';
            printContent += '.info-row{display:flex;justify-content:space-between;margin:3px 0;font-size:12px}';
            printContent += '.info-label{font-weight:bold}';
            printContent += '.divider{border-top:1px dashed #000;margin:8px 0}';
            printContent += '.items-table{width:100%;border-collapse:collapse;margin:5px 0}';
            printContent += '.items-table th{text-align:left;padding:3px 0;border-bottom:1px dashed #000;font-weight:bold}';
            printContent += '.items-table td{padding:2px 0}';
            printContent += '.total-row{display:flex;justify-content:space-between;margin:2px 0}';
            printContent += '.total-row.total{font-weight:bold;border-top:2px solid #000;padding-top:5px;margin-top:5px}';
            printContent += '.footer{text-align:center;margin-top:15px;padding-top:10px;border-top:1px dashed #000;font-size:11px}';
            printContent += '@media print{body{padding:0;margin:0}.no-print{display:none}}</style></head><body>';

            printContent += '<div class="header">';
            if (shop.name) printContent += '<div class="shop-name">' + shop.name + '</div>';
            if (shop.address) printContent += '<div class="shop-address">' + shop.address + '</div>';
            if (shop.phone) printContent += '<div class="shop-phone">Tel: ' + shop.phone + '</div>';
            printContent += '</div>';

            printContent += '<div class="invoice-title">INVOICE</div>';
            printContent += '<div class="info-row"><span class="info-label">Invoice #:</span><span>' + (sale.invoice_id || sale.id) + '</span></div>';
            if (sale.created_at) {
              var d = new Date(sale.created_at);
              printContent += '<div class="info-row"><span class="info-label">Date:</span><span>' + d.toLocaleDateString() + '</span></div>';
              printContent += '<div class="info-row"><span class="info-label">Time:</span><span>' + d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) + '</span></div>';
            }
            printContent += '<div class="info-row"><span class="info-label">Customer:</span><span>' + (sale.customer_name || "Walk-in Customer") + '</span></div>';
            printContent += '<div class="info-row"><span class="info-label">Type:</span><span>' + self.formatSaleType(sale.sale_type) + '</span></div>';

            printContent += '<div class="divider"></div>';
            printContent += '<table class="items-table"><thead><tr><th>ITEM</th><th style="text-align:center">QTY</th><th style="text-align:right">AMOUNT</th></tr></thead><tbody>';

            if (sale.items && sale.items.length > 0) {
              $.each(sale.items, function (_, item) {
                printContent += '<tr><td>' + (item.product_name || "Item") + '</td><td style="text-align:center">' + item.quantity + '</td><td style="text-align:right">' + self.formatCurrency(item.total) + '</td></tr>';
              });
            } else {
              printContent += '<tr><td colspan="3" style="text-align:center">No items</td></tr>';
            }

            printContent += '</tbody></table><div class="divider"></div>';
            printContent += '<div class="total-row total"><span>TOTAL:</span><span>' + self.formatCurrency(sale.grand_total) + '</span></div>';

            printContent += '<div class="footer">Thank you for your business!<br>Please keep this receipt</div>';
            printContent += '</body></html>';

            var printWindow = window.open("", "_blank");
            if (printWindow) {
              printWindow.document.write(printContent);
              printWindow.document.close();
              printWindow.onload = function () {
                setTimeout(function () {
                  printWindow.focus();
                  printWindow.print();
                  printWindow.onafterprint = function () { printWindow.close(); };
                }, 100);
              };
            }
          }
        },
      });
    },

    handleDeleteSale: function (button) {
      var self = this;
      var $button = $(button);
      var originalText = $button.text();
      var id = $button.closest("tr").data("sale-id");

      showLimeConfirm(
        self.config.strings.confirm_delete || "Are you sure you want to delete this sale?",
        function onYes() {
          $button.prop("disabled", true).text(self.config.strings.deleting || "Deleting...");

          $.post(self.config.ajaxUrl, {
            action: "opfw_delete_sale",
            id: id,
            _wpnonce: self.config.nonces.delete_sale,
          })
            .done(function (res) {
              if (res.success) {
                self.loadOPFWSales();
                showLimeModal(res.data, "Success");
              } else {
                showLimeModal(res.data, "Error");
              }
            })
            .fail(function () {
              showLimeModal(self.config.strings.delete_failed || "Delete request failed.", "Error");
            })
            .always(function () {
              $button.prop("disabled", false).text(originalText);
            });
        },
        "Confirm Delete"
      );
    },
  };

  $(document).ready(function () {
    if ($("#search-sales").length) {
      OPFWSales.init();
    }
  });
})(jQuery);
