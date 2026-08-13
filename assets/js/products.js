/**
 * Product Management — WooCommerce Products (Read-only + Buy Price)
 * Plugin: Obydullah_POS_For_WooCommerce
 * Version: 2.0.0
 */
(function ($) {
  "use strict";

  let OPFWProducts = {
    config: {
      isSubmitting: false,
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalItems: 0,
      searchTerm: "",
      searchTimeout: null,
      getNonce: "",
      getCategoriesNonce: "",
      updateBuyPriceNonce: "",
      ajaxUrl: "",
      strings: {},
    },

    init: function () {
      if (typeof opfwProducts !== "undefined") {
        this.config.getNonce = opfwProducts.getNonce || "";
        this.config.getCategoriesNonce = opfwProducts.getCategoriesNonce || "";
        this.config.updateBuyPriceNonce = opfwProducts.updateBuyPriceNonce || "";
        this.config.ajaxUrl = opfwProducts.ajaxUrl || "";
        this.config.strings = opfwProducts.strings || {};
      }

      this.bindEvents();
      this.loadProducts();
    },

    bindEvents: function () {
      var self = this;

      $("#product-search").on("input", function () {
        clearTimeout(self.config.searchTimeout);
        self.config.searchTerm = $(this).val().trim();
        self.config.searchTimeout = setTimeout(function () {
          self.loadProducts(1);
        }, 500);
      });

      $("#clear-search").on("click", function () {
        $("#product-search").val("");
        self.config.searchTerm = "";
        self.loadProducts(1);
      });

      $("#per-page-select").on("change", function () {
        self.config.perPage = parseInt($(this).val());
        self.loadProducts(1);
      });

      $(".first-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadProducts(1);
      });

      $(".prev-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadProducts(self.config.currentPage - 1);
      });

      $(".next-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadProducts(self.config.currentPage + 1);
      });

      $(".last-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadProducts(self.config.totalPages);
      });

      $("#current-page-selector").on("keypress", function (e) {
        if (e.which === 13) {
          var page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.loadProducts(page);
          }
        }
      });

      $(document).on("click", ".pos-action.edit-buy-price", function () {
        self.openBuyPriceModal(this);
      });

      $(document).on("click", ".opfw-modal-close", function () {
        self.closeBuyPriceModal();
      });

      $(document).on("click", ".opfw-modal-overlay", function () {
        self.closeBuyPriceModal();
      });

      $("#buy-price-form").on("submit", function (e) {
        e.preventDefault();
        self.handleBuyPriceSubmit();
      });
    },

    loadProducts: function (page) {
      var self = this;
      if (page) self.config.currentPage = page;

      var tbody = $("#product-list");
      tbody.html('<tr><td colspan="8" class="loading-products"><span class="spinner is-active"></span> ' + (self.config.strings.loadingProducts || "Loading products...") + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_products",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          search: self.config.searchTerm,
          nonce: self.config.getNonce,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.products.length) {
              var message = self.config.searchTerm ? (self.config.strings.noResults || 'No results for') + ' "' + self.config.searchTerm + '"' : (self.config.strings.noProducts || "No products found.");
              tbody.append('<tr><td colspan="8" class="no-results">' + message + "</td></tr>");
              self.updatePagination(response.data.pagination);
              return;
            }

            $.each(response.data.products, function (_, product) {
              var row = $("<tr>").attr("data-product-id", product.id);

              var imageTd = $("<td>").addClass("compact-image-cell");
              if (product.image) {
                var tmp = document.createElement("div");
                tmp.innerHTML = product.image;
                var img = tmp.querySelector("img");
                if (img) {
                  imageTd.append($("<img>").addClass("compact-thumb").attr("src", img.src).attr("alt", product.name));
                } else {
                  imageTd.addClass("opfw-empty-cell").html("&mdash;");
                }
              } else {
                imageTd.addClass("opfw-empty-cell").html("&mdash;");
              }
              row.append(imageTd);

              row.append($("<td>").addClass("text-ellipsis").text(product.name));
              row.append($("<td>").addClass("compact-status").text(product.category_name || "&mdash;"));

              row.append($("<td>").text(parseFloat(product.regular_price || 0).toFixed(2)));
              row.append($("<td>").text(parseFloat(product.buy_price || 0).toFixed(2)));

              var stockText = product.manage_stock ? (product.stock_quantity || 0) : "N/A";
              row.append($("<td>").text(stockText));

              var statusClass = product.status === "publish" ? "badge bg-success" : "badge bg-secondary";
              var statusText = product.status === "publish" ? "Active" : "Draft";
              row.append(
                $("<td>").addClass("compact-status").append(
                  $("<span>").addClass(statusClass + " badge-status").text(statusText)
                )
              );

              var actions = $("<td>").addClass("pos-row-actions");
              actions.append(
                $("<button>").addClass("pos-action edit-buy-price").text(self.config.strings.editBuyPrice || "Set Buy Price").attr("data-id", product.id).attr("data-name", product.name).attr("data-buy-price", product.buy_price || 0)
              );
              row.append(actions);

              tbody.append(row);
            });

            self.updatePagination(response.data.pagination);
          } else {
            tbody.append('<tr><td colspan="8" class="error-message">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          tbody.html('<tr><td colspan="8" class="error-message">' + (self.config.strings.loadError || "Failed to load products.") + "</td></tr>");
        },
      });
    },

    updatePagination: function (pagination) {
      this.config.totalPages = pagination.total_pages || 1;
      this.config.totalItems = pagination.total_items || 0;

      $("#displaying-num").text(pagination.total_items + " " + (self.config.strings.items || "items"));
      $("#current-page-selector").val(this.config.currentPage);
      $(".total-pages").text(this.config.totalPages);
      $(".first-page, .prev-page").prop("disabled", this.config.currentPage === 1);
      $(".next-page, .last-page").prop("disabled", this.config.currentPage === this.config.totalPages);
    },

    openBuyPriceModal: function (button) {
      var productId = $(button).data("id");
      var productName = $(button).data("name");
      var buyPrice = $(button).data("buy-price");

      $("#buy-price-product-id").val(productId);
      $("#buy-price-product-name").text(productName);
      $("#buy-price-input").val(parseFloat(buyPrice || 0).toFixed(2));
      $("#opfw-buy-price-modal").removeClass("d-none");
    },

    closeBuyPriceModal: function () {
      $("#opfw-buy-price-modal").addClass("d-none");
    },

    handleBuyPriceSubmit: function () {
      var self = this;

      if (self.config.isSubmitting) return false;

      var productId = $("#buy-price-product-id").val();
      var buyPrice = $("#buy-price-input").val();

      if (!productId) return false;

      self.config.isSubmitting = true;

      $.post(self.config.ajaxUrl, {
        action: "opfw_update_buy_price",
        product_id: productId,
        buy_price: buyPrice,
        nonce: self.config.updateBuyPriceNonce,
      })
        .done(function (response) {
          if (response.success) {
            self.closeBuyPriceModal();
            showLimeModal(response.data || self.config.strings.buyPriceUpdated || "Buy price updated!", "Success");
            var modal = $("#lime-alert-modal");
            modal.find("#lime-alert-close").off("click").on("click", function () {
              self.loadProducts(self.config.currentPage);
              modal.addClass("d-none");
            });
          } else {
            showLimeModal(self.config.strings.error + " " + response.data, "Error");
          }
        })
        .fail(function () {
          showLimeModal(self.config.strings.requestFailed || "Request failed.", "Error");
        })
        .always(function () {
          self.config.isSubmitting = false;
        });
    },
  };

  $(document).ready(function () {
    if ($("#product-list").length) {
      OPFWProducts.init();
    }
  });
})(jQuery);
