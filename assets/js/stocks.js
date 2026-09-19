/**
 * Stock Management — WooCommerce
 * Plugin: Obydullah_Restaurant_Sales_Terminal
 * Version: 2.0.0
 */
(function ($) {
  "use strict";

  let OPFWStocks = {
    config: {
      isSubmitting: false,
      currentPage: 1,
      perPage: 20,
      totalPages: 1,
      totalItems: 0,
      searchTerm: "",
      statusFilter: "",
      addNonce: "",
      getNonce: "",
      productsNonce: "",
      ajaxUrl: "",
      strings: {},
      searchTimeout: null,
    },

    init: function () {
      if (typeof opfwStocks !== "undefined") {
        this.config.addNonce = opfwStocks.addNonce || "";
        this.config.getNonce = opfwStocks.getNonce || "";
        this.config.productsNonce = opfwStocks.productsNonce || "";
        this.config.ajaxUrl = opfwStocks.ajaxUrl || "";
        this.config.strings = opfwStocks.strings || {};
      }

      this.productsList = [];
      this.bindEvents();
      this.loadProducts();
      this.loadStocks();
      this.calculateProfit();
    },

    bindEvents: function () {
      var self = this;

      $("#update-stock-form").on("submit", function (e) {
        e.preventDefault();
        self.handleStockSubmit();
      });

      $("#stock-search").on("input", function () {
        clearTimeout(self.config.searchTimeout);
        self.config.searchTerm = $(this).val().trim();
        self.config.searchTimeout = setTimeout(function () {
          self.loadStocks(1);
        }, 500);
      });

      $("#status-filter").on("change", function () {
        self.config.statusFilter = $(this).val();
        self.loadStocks(1);
      });

      $("#per-page-select").on("change", function () {
        self.config.perPage = parseInt($(this).val());
        self.loadStocks(1);
      });

      $(".first-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadStocks(1);
      });

      $(".prev-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadStocks(self.config.currentPage - 1);
      });

      $(".next-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadStocks(self.config.currentPage + 1);
      });

      $(".last-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadStocks(self.config.totalPages);
      });

      $("#current-page-selector").on("keypress", function (e) {
        if (e.which === 13) {
          var page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.loadStocks(page);
          }
        }
      });

      $("#buy-price, #sale-price, #stock-quantity").on("input", function () {
        self.calculateProfit();
      });

      $("#stock-product-input").on("input", function () {
        self.handleProductSearch($(this).val());
      });

      $("#stock-product-input").on("focus", function () {
        self.handleProductSearch($(this).val());
      });

      $("#stock-product-input").on("keydown", function (e) {
        if (e.which === 13) {
          e.preventDefault();
          self.handleProductEnter();
        }
      });

      $(document).on("click", function (e) {
        if (!$(e.target).closest("#stock-product-input, #stock-product-results").length) {
          self.closeProductResults();
        }
      });

      $(document).on("click", ".opfw-product-option", function () {
        self.selectProduct($(this).data());
      });
    },

    handleProductSearch: function (term) {
      var self = this;
      var results = self.filterProducts(term);
      var container = $("#stock-product-results");

      container.empty();

      if (results.length === 0) {
        container.addClass("opfw-hidden");
        return;
      }

      $.each(results.slice(0, 10), function (_, product) {
        container.append(
          $("<div>")
            .addClass("opfw-product-option")
            .data({
              id: product.id,
              buyPrice: product.buy_price,
              salePrice: product.sale_price,
              qty: product.stock_quantity,
            })
            .text(product.name)
        );
      });

      container.removeClass("opfw-hidden");
    },

    handleProductEnter: function () {
      var self = this;
      var active = $("#stock-product-results .opfw-product-option.active");

      if (!active.length) {
        active = $("#stock-product-results .opfw-product-option").first();
      }

      if (active.length) {
        self.selectProduct(active.data());
      }
    },

    selectProduct: function (data) {
      if (!data || !data.id) return;

      var self = this;
      var name = "";

      if (self.productsList) {
        var product = $.grep(self.productsList, function (p) {
          return p.id === data.id;
        })[0];
        name = product ? product.name : "";
      }

      if (!name) {
        var activeEl = $(".opfw-product-option").filter(function () {
          return $(this).data("id") === data.id;
        });
        name = activeEl.text() || "";
      }

      $("#stock-product-id").val(data.id);
      $("#stock-product-input").val(name);
      $("#buy-price").val(parseFloat(data.buyPrice).toFixed(2));
      $("#sale-price").val(parseFloat(data.salePrice).toFixed(2));
      $("#stock-quantity").val(data.qty);
      self.closeProductResults();
      self.calculateProfit();
    },

    closeProductResults: function () {
      $("#stock-product-results").empty().addClass("opfw-hidden");
    },

    filterProducts: function (term) {
      var self = this;
      var query = (term || "").trim().toLowerCase();

      if (!self.productsList) return [];

      if (!query) return self.productsList.slice();

      return $.grep(self.productsList, function (product) {
        return product.name.toLowerCase().indexOf(query) !== -1;
      });
    },

    loadProducts: function () {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_products_for_stocks",
          _wpnonce: self.config.productsNonce,
        },
        success: function (response) {
          if (response.success) {
            self.productsList = response.data || [];

            $.each(self.productsList, function (_, product) {
              product.buy_price = parseFloat(product.buy_price || 0).toFixed(2);
              product.sale_price = parseFloat(product.sale_price || 0).toFixed(2);
            });
          }
        },
      });
    },

    loadStocks: function (page) {
      var self = this;
      self.config.currentPage = page || 1;

      var tbody = $("#stock-list");
      tbody.html('<tr><td colspan="5" class="loading-stocks"><span class="spinner is-active"></span> ' + (self.config.strings.loadingStocks || "Loading...") + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_stocks",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          search: self.config.searchTerm,
          status: self.config.statusFilter,
          _wpnonce: self.config.getNonce,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.stocks.length) {
              tbody.append('<tr><td colspan="5" class="no-stocks">' + (self.config.strings.noStocks || "No products found.") + "</td></tr>");
              self.updateSummaryCards();
              self.updatePagination(response.data.pagination);
              return;
            }

            var inStock = 0, outStock = 0, lowStock = 0;

            $.each(response.data.stocks, function (_, stock) {
              var row = $("<tr>").attr("data-stock-id", stock.id);

              row.append($("<td>").text(stock.product_name || "N/A"));
              row.append($("<td>").text(parseFloat(stock.buy_price).toFixed(2)));
              row.append($("<td>").text(parseFloat(stock.sale_price).toFixed(2)));

              var quantityCell = $("<td>").text(stock.quantity);
              var quantityNum = parseInt(stock.quantity);
              if (quantityNum === 0) quantityCell.addClass("quantity-zero");
              else if (quantityNum < 10) quantityCell.addClass("quantity-low");
              row.append(quantityCell);

              var statusText = stock.status === "instock" ? "In Stock" : stock.status === "outofstock" ? "Out of Stock" : "On Backorder";
              var statusClass = stock.status === "instock" ? "badge bg-success" : stock.status === "outofstock" ? "badge bg-danger" : "badge bg-warning";
              row.append(
                $("<td>").addClass("compact-status").append(
                  $("<span>").addClass(statusClass + " badge-status").text(statusText)
                )
              );

              if (stock.status === "instock") inStock++;
              else if (stock.status === "outofstock") outStock++;
              else lowStock++;

              tbody.append(row);
            });

            $("#in-stock-count").text(inStock);
            $("#out-stock-count").text(outStock);
            $("#low-stock-count").text(lowStock);
            $("#total-stocks-count").text(response.data.stocks.length);

            self.updatePagination(response.data.pagination);
          } else {
            tbody.append('<tr><td colspan="5" class="error-message">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          tbody.html('<tr><td colspan="5" class="error-message">' + (self.config.strings.loadError || "Failed to load stocks.") + "</td></tr>");
        },
      });
    },

    updateSummaryCards: function () {
      $("#in-stock-count").text(0);
      $("#out-stock-count").text(0);
      $("#low-stock-count").text(0);
      $("#total-stocks-count").text(0);
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

    calculateProfit: function () {
      var buyCost = parseFloat($("#buy-price").val()) || 0;
      var saleCost = parseFloat($("#sale-price").val()) || 0;
      var quantity = parseInt($("#stock-quantity").val()) || 0;

      var profitPerUnit = saleCost - buyCost;
      var totalProfit = profitPerUnit * quantity;
      var profitMargin = buyCost > 0 ? (profitPerUnit / buyCost) * 100 : 0;

      $("#profit-margin").text(profitMargin.toFixed(2) + "%");
      $("#total-profit").text(totalProfit.toFixed(2));

      $("#profit-margin, #total-profit")
        .removeClass("profit-positive profit-negative profit-neutral")
        .addClass(profitMargin > 0 ? "profit-positive" : profitMargin < 0 ? "profit-negative" : "profit-neutral");
    },

    handleStockSubmit: function () {
      var self = this;

      if (self.config.isSubmitting) return false;

      var productId = $("#stock-product-id").val();
      var buyPrice = $("#buy-price").val();
      var salePrice = $("#sale-price").val();
      var quantity = $("#stock-quantity").val();
      var stockStatus = $("#stock-status").val();

      if (!productId) {
        showLimeModal(self.config.strings.selectProductRequired || "Please select a product", "Validation Error");
        return false;
      }

      self.config.isSubmitting = true;
      self.setButtonLoading(true);

      $.post(self.config.ajaxUrl, {
        action: "opfw_update_stock",
        product_id: productId,
        buy_price: buyPrice,
        sale_price: salePrice,
        quantity: quantity,
        stock_status: stockStatus,
        _wpnonce: self.config.addNonce,
      })
        .done(function (res) {
          if (res.success) {
            showLimeModal(self.config.strings.successMessage || "Stock updated!", "Success");
            var modal = $("#lime-alert-modal");
            modal.find("#lime-alert-close").off("click").on("click", function () {
              $('#update-stock-form')[0].reset();
              $("#stock-product-id").val("");
              self.closeProductResults();
              self.loadStocks(self.config.currentPage);
              self.loadProducts();
              modal.addClass("d-none");
            });
          } else {
            showLimeModal(self.config.strings.error + " " + res.data, "Error");
          }
        })
        .fail(function () {
          showLimeModal(self.config.strings.requestFailed || "Request failed. Please try again.", "Error");
        })
        .always(function () {
          self.config.isSubmitting = false;
          self.setButtonLoading(false);
        });
    },

    setButtonLoading: function (loading) {
      var button = $("#submit-stock");
      var spinner = button.find(".spinner");
      var btnText = button.find(".btn-text");

      if (loading) {
        button.prop("disabled", true).addClass("button-loading");
        spinner.show();
        btnText.text(this.config.strings.saving || "Saving...");
      } else {
        button.prop("disabled", false).removeClass("button-loading");
        spinner.hide();
        btnText.text(this.config.strings.saveStock || "Update Stock");
      }
    },
  };

  $(document).ready(function () {
    if ($(".opfw-stocks-page").length) {
      OPFWStocks.init();
    }
  });
})(jQuery);
