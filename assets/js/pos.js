/**
 * POS System
 * Plugin: Obydullah_POS_For_WooCommerce
 * Version: 1.0.1
 */
(function ($) {
  "use strict";

  let OPFW_POS = {
    // Configuration
    config: {
      cart: [],
      currentCategory: "all",
      customersData: {},
      savedSaleId: "",
      currencySymbol: "$",
      vatRate: 0,
      taxRate: 0,
      ajaxUrl: "",
      strings: {},
      nonces: {},
    },

    // Cache for jQuery elements
    elements: {},

    /**
     * Initialize POS system
     */
    init: function () {
      // Initialize configuration from localized script
      if (typeof opfw_pos !== "undefined") {
        this.config = {
          ...this.config,
          ...opfw_pos,
        };
      }

      // Cache DOM elements
      this.cacheElements();

      // Bind events
      this.bindEvents();

      // Initial load
      this.loadInitialData();
    },

    /**
     * Cache frequently used DOM elements
     */
    cacheElements: function () {
      this.elements = {
        // Categories
        categoriesList: $("#opfw-categories-list"),
        stocksGrid: $("#opfw-stocks-grid"),

        // Customer
        customerSelect: $("#opfw-customer"),

        // Order type tabs
        orderTypeInputs: $('input[name="order-type"]'),
        dineInOptions: $("#dineInOptions"),
        takeAwayOptions: $("#takeAwayOptions"),
        pickupOptions: $("#pickupOptions"),

        // Take Away fields
        takeawayName: $("#takeaway-name"),
        takeawayEmail: $("#takeaway-email"),
        takeawayMobile: $("#takeaway-mobile"),
        takeawayAddress: $("#takeaway-address"),

        // Pickup fields
        pickupName: $("#pickup-name"),
        pickupMobile: $("#pickup-mobile"),

        // Cart
        cartItems: $("#opfw-cart-items"),
        clearCartBtn: $("#opfw-clear-cart"),

        // Summary
        discountInput: $("#opfw-discount"),
        deliveryInput: $("#opfw-delivery"),
        subtotalDisplay: $("#opfw-subtotal"),
        taxDisplay: $("#opfw-tax"),
        vatDisplay: $("#opfw-vat"),
        grandTotalDisplay: $("#opfw-grand-total"),

        // Notes
        notesTextarea: $("#opfw-notes"),

        // Action buttons
        saveSaleBtn: $("#opfw-save-sale"),
        completeSaleBtn: $("#opfw-complete-sale"),

        // Saved sales
        loadSavedBtn: $("#opfw-load-saved"),
        savedList: $("#opfw-saved-list"),

        // Current sale
        currentSaleId: $("#opfw-current-sale-id"),
      };
    },

    /**
     * Bind all event handlers
     */
    bindEvents: function () {
      var self = this;

      // Load saved sales
      this.elements.loadSavedBtn.click(function () {
        self.loadSavedSales();
      });

      // Saved sales list click (delegated)
      this.elements.savedList
        .on("click", ".load-saved-btn", function (e) {
          e.stopPropagation();
          var saleId = $(this).data("sale-id");
          self.loadSavedSale(saleId);
        })
        .on("click", ".delete-saved-btn", function (e) {
          e.stopPropagation();
          var saleId = $(this).data("sale-id");
          self.deleteSavedSale(saleId);
        })
        .on("click", ".opfw-saved-item", function (e) {
          // Only trigger if not clicking on buttons
          if (!$(e.target).hasClass("load-saved-btn") && !$(e.target).hasClass("delete-saved-btn")) {
            var saleId = $(this).data("sale-id");
            self.loadSavedSale(saleId);
          }
        });

      // Order type change
      this.elements.orderTypeInputs.change(function () {
        self.handleOrderTypeChange($(this).val());
      });

      // Customer select change (auto-fill)
      this.elements.customerSelect.change(function () {
        self.handleCustomerChange($(this).val());
      });

      // Categories
      this.elements.categoriesList.on("click", "button", function () {
        var category = $(this).data("category");
        self.handleCategoryClick(category, $(this));
      });

      // Stock cards
      this.elements.stocksGrid.on("click", ".opfw-stock-card:not(.out-of-stock)", function () {
        var stockId = $(this).data("stock-id");
        var stockName = $(this).find(".opfw-stock-name").text();
        var priceText = $(this).find(".opfw-stock-price").text();
        self.addToCart(stockId, stockName, priceText);
      });

      // Cart item events (delegated)
      this.elements.cartItems
        .on("click", ".qty-minus", function () {
          var index = $(this).data("index");
          self.updateQuantity(index, "decrease");
        })
        .on("click", ".qty-plus", function () {
          var index = $(this).data("index");
          self.updateQuantity(index, "increase");
        })
        .on("input", ".qty-input", function () {
          var index = $(this).data("index");
          var qty = parseInt($(this).val());
          self.updateQuantityInput(index, qty);
        })
        .on("click", ".opfw-cart-item-remove", function () {
          var index = $(this).data("index");
          self.removeCartItem(index);
        });

      // Clear cart
      this.elements.clearCartBtn.click(function () {
        self.clearCart();
      });

      // Summary inputs
      this.elements.discountInput.add(this.elements.deliveryInput).on("input", function () {
        self.updateSummary();
      });

      // Save sale
      this.elements.saveSaleBtn.click(function () {
        self.processSale("save");
      });

      // Complete sale
      this.elements.completeSaleBtn.click(function () {
        self.processSale("complete");
      });

      // Load saved sales
      this.elements.loadSavedBtn.click(function () {
        self.loadSavedSales();
      });

      // Saved sales list click (delegated)
      this.elements.savedList.on("click", ".opfw-saved-item", function () {
        var saleId = $(this).data("sale-id");
        self.loadSavedSale(saleId);
      });
    },

    /**
     * Load initial data
     */
    loadInitialData: function () {
      this.loadCategories();
      this.loadStocks();
      this.loadCustomers();
      this.loadSavedSales();
    },

    /**
     * Handle order type change
     */
    handleOrderTypeChange: function (orderType) {
      // Hide all tab contents
      this.elements.dineInOptions.hide();
      this.elements.takeAwayOptions.hide();
      this.elements.pickupOptions.hide();

      // Show selected tab content
      switch (orderType) {
        case "dineIn":
          this.elements.dineInOptions.show();
          break;
        case "takeAway":
          this.elements.takeAwayOptions.show();
          break;
        case "pickup":
          this.elements.pickupOptions.show();
          break;
      }
    },

    /**
     * Delete a saved sale
     */
    deleteSavedSale: function (saleId) {
      var self = this;

      showLimeConfirm(
        this.config.strings.confirmDeleteSaved || "Are you sure you want to delete this saved sale?",
        function onYes() {
          $.ajax({
            url: self.config.ajaxUrl,
            type: "POST",
            data: {
              action: "opfw_delete_saved_sale",
              nonce: self.config.nonces.delete_saved,
              sale_id: saleId,
            },
            success: function (response) {
              if (response.success) {
                showLimeModal(self.config.strings.saleDeleted || "Saved sale deleted successfully!", "Success");

                // Refresh the saved sales list
                self.loadSavedSales();

                // If the deleted sale was currently loaded, clear the current sale
                if (self.config.savedSaleId == saleId) {
                  self.config.savedSaleId = "";
                  self.elements.currentSaleId.val("");
                }
              } else {
                showLimeModal(self.config.strings.error + ": " + response.data, "Error");
              }
            },
            error: function () {
              showLimeModal(self.config.strings.deleteFailed || "Failed to delete saved sale. Please try again.", "Error");
            },
          });
        },
        "Confirm Delete"
      );
    },

    /**
     * Handle customer selection change
     */
    handleCustomerChange: function (customerId) {
      if (customerId && this.config.customersData[customerId]) {
        var customer = this.config.customersData[customerId];

        // Fill Take Away fields
        this.elements.takeawayName.val(customer.name || "");
        this.elements.takeawayEmail.val(customer.email || "");
        this.elements.takeawayMobile.val(customer.mobile || "");
        this.elements.takeawayAddress.val(customer.address || "");

        // Fill Pickup fields
        this.elements.pickupName.val(customer.name || "");
        this.elements.pickupMobile.val(customer.mobile || "");
      } else {
        // Clear fields if no customer selected
        this.elements.takeawayName.val("");
        this.elements.takeawayEmail.val("");
        this.elements.takeawayMobile.val("");
        this.elements.takeawayAddress.val("");
        this.elements.pickupName.val("");
        this.elements.pickupMobile.val("");
      }
    },

    /**
     * Handle category click
     */
    handleCategoryClick: function (category, button) {
      // Update UI
      this.elements.categoriesList.find("button").removeClass("active btn-primary").addClass("btn-outline-secondary");
      button.removeClass("btn-outline-secondary").addClass("active btn-primary");

      // Load stocks for category
      this.config.currentCategory = category;
      this.loadStocks(category);
    },

    /**
     * Load categories
     */
    loadCategories: function () {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_categories_for_pos",
          nonce: self.config.nonces.categories,
        },
        success: function (response) {
          if (response.success) {
            var html = '<button class="btn btn-outline-primary active mr-2 mb-2" data-category="all">' + self.config.strings.allStocks + "</button>";
            response.data.forEach(function (category) {
              html += '<button class="btn btn-outline-secondary mr-2 mb-2" data-category="' + category.id + '">' + category.name + "</button>";
            });
            self.elements.categoriesList.html(html);
          }
        },
      });
    },

    /**
     * Load stocks
     */
    loadStocks: function (categoryId) {
      var self = this;

      if (typeof categoryId === "undefined") {
        categoryId = this.config.currentCategory;
      }

      // Show loading
      self.elements.stocksGrid.html('<div class="text-center py-5">' + self.config.strings.loadingStocks + "</div>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_products_by_category",
          nonce: self.config.nonces.stocks,
          category_id: categoryId,
        },
        success: function (response) {
          if (response.success) {
            var html = "";
            if (response.data.length === 0) {
              html = '<div class="text-center py-5">' + self.config.strings.noStocks + "</div>";
            } else {
              response.data.forEach(function (stock) {
                var stockStatus = stock.stock_status || "inStock";
                var stockClass = stockStatus === "outStock" ? "out-of-stock" : "";
                var stockName = stock.name || "";
                var saleCost = parseFloat(stock.sale_cost || 0).toFixed(2);
                var quantity = stock.quantity || 0;

                html += '<div class="opfw-stock-card ' + stockClass + '" data-stock-id="' + stock.id + '">';
                html += '<div class="opfw-stock-name">' + stockName + "</div>";
                html += '<div class="opfw-stock-price">' + self.config.currencySymbol + saleCost + "</div>";
                html += '<div class="opfw-stock-quantity">' + quantity + " " + self.config.strings.inStock + "</div>";
                html += "</div>";
              });
            }
            self.elements.stocksGrid.html(html);
          }
        },
      });
    },

    /**
     * Load customers
     */
    loadCustomers: function () {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_customers_for_pos",
          nonce: self.config.nonces.customers,
        },
        success: function (response) {
          if (response.success) {
            var select = self.elements.customerSelect;
            select.find("option:not(:first)").remove();

            // Reset customers data
            self.config.customersData = {};

            response.data.forEach(function (customer) {
              select.append('<option value="' + customer.id + '">' + customer.name + "</option>");

              // Store customer data for auto-fill
              self.config.customersData[customer.id] = {
                name: customer.name,
                email: customer.email,
                mobile: customer.mobile,
                address: customer.address,
              };
            });
          }
        },
      });
    },

    /**
     * Load saved sales
     */
    loadSavedSales: function () {
      var self = this;

      // Show loading
      self.elements.savedList.html('<div class="text-center py-3">' + self.config.strings.loadingSaved + "</div>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_saved_sales",
          nonce: self.config.nonces.saved,
        },
        success: function (response) {
          if (response.success) {
            var html = "";
            if (response.data.length === 0) {
              html = '<div class="text-center py-3">' + self.config.strings.noSaved + "</div>";
            } else {
              response.data.forEach(function (sale) {
                var date = new Date(sale.created_at);
                var formattedDate = date.toLocaleDateString();
                var invoiceId = sale.invoice_id || "";
                var customerName = sale.customer_name || "";
                var grandTotal = parseFloat(sale.grand_total || 0).toFixed(2);

                html += '<div class="opfw-saved-item d-flex justify-content-between align-items-center p-2 mb-2 border rounded" data-sale-id="' + sale.id + '">';
                html += '<div class="saved-sale-info">';
                html += "<div><strong>" + invoiceId + "</strong></div>";
                html += "<div>" + customerName + " - " + self.config.currencySymbol + grandTotal + "</div>";
                html += "<small class='text-muted'>" + formattedDate + "</small>";
                html += "</div>";
                html += '<div class="saved-sale-actions">';
                html += '<button class="btn btn-sm btn-success load-saved-btn" data-sale-id="' + sale.id + '" title="Load Sale">Load</button>';
                html += '<button class="btn btn-sm btn-danger delete-saved-btn ml-2" data-sale-id="' + sale.id + '" title="Delete Sale">Delete</button>';
                html += "</div>";
                html += "</div>";
              });
            }
            self.elements.savedList.html(html);
          }
        },
      });
    },

    /**
     * Load a saved sale
     */
    loadSavedSale: function (saleId) {
      var self = this;

      // Confirm if cart is not empty
      if (self.config.cart.length > 0) {
        showLimeConfirm(
          self.config.strings.confirmLoadSaved || "Are you sure you want to load this saved sale? Current cart will be cleared.",
          function onYes() {
            self.performLoadSavedSale(saleId);
          },
          "Confirm Load"
        );
        return;
      }

      // If cart is empty, load directly
      this.performLoadSavedSale(saleId);
    },

    /**
     * Perform the actual saved sale loading
     */
    performLoadSavedSale: function (saleId) {
      var self = this;

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_load_saved_sale",
          nonce: self.config.nonces.load,
          sale_id: saleId,
        },
        success: function (response) {
          if (response.success) {
            // Clear current cart
            self.config.cart = [];

            // Add items from saved sale
            response.data.items.forEach(function (item) {
              self.config.cart.push({
                product_id: item.fk_product_id,
                name: item.product_name,
                price: parseFloat(item.unit_price),
                quantity: parseInt(item.quantity),
              });
            });

            // Set current sale ID
            self.config.savedSaleId = saleId;
            self.elements.currentSaleId.val(saleId);

            // Update cart display
            self.updateCartDisplay();

            // Show success message
            showLimeModal(self.config.strings.saleLoaded || "Sale loaded successfully!", "Success");
          } else {
            showLimeModal(self.config.strings.error + ": " + response.data, "Error");
          }
        },
      });
    },

    /**
     * Add item to cart
     */
    addToCart: function (stockId, stockName, priceText) {
      // Extract price from price text (remove currency symbol)
      var price = parseFloat(priceText.replace(this.config.currencySymbol, ""));

      // Check if item already exists in cart
      var existingItem = this.config.cart.find(function (item) {
        return item.product_id == stockId;
      });

      if (existingItem) {
        existingItem.quantity++;
      } else {
        this.config.cart.push({
          product_id: stockId,
          name: stockName,
          price: price,
          quantity: 1,
        });
      }

      this.updateCartDisplay();
    },

    /**
     * Update cart item quantity
     */
    updateQuantity: function (index, action) {
      if (index >= 0 && index < this.config.cart.length) {
        if (action === "increase") {
          this.config.cart[index].quantity++;
        } else if (action === "decrease" && this.config.cart[index].quantity > 1) {
          this.config.cart[index].quantity--;
        }
        this.updateCartDisplay();
      }
    },

    /**
     * Update cart item quantity from input
     */
    updateQuantityInput: function (index, quantity) {
      if (index >= 0 && index < this.config.cart.length && quantity > 0) {
        this.config.cart[index].quantity = quantity;
        this.updateCartDisplay();
      }
    },

    /**
     * Remove item from cart
     */
    removeCartItem: function (index) {
      if (index >= 0 && index < this.config.cart.length) {
        showLimeConfirm(
          this.config.strings.confirmRemove || "Are you sure you want to remove this item?",
          function onYes() {
            this.config.cart.splice(index, 1);
            this.updateCartDisplay();
          }.bind(this),
          "Confirm Remove"
        );
      }
    },

    /**
     * Clear cart
     */
    clearCart: function () {
      if (this.config.cart.length === 0) {
        return;
      }

      showLimeConfirm(
        this.config.strings.confirmClear || "Are you sure you want to clear the cart?",
        function onYes() {
          this.config.cart = [];
          this.config.savedSaleId = "";
          this.elements.currentSaleId.val("");
          this.updateCartDisplay();
        }.bind(this),
        "Confirm Clear Cart"
      );
    },

    /**
     * Update cart display
     */
    updateCartDisplay: function () {
      var self = this;

      // If cart is empty
      if (this.config.cart.length === 0) {
        this.elements.cartItems.html('<div class="text-center py-3 text-muted">' + this.config.strings.cartEmpty + "</div>");
        this.updateSummary();
        return;
      }

      // Build cart HTML
      var html = "";
      this.config.cart.forEach(function (item, index) {
        var total = item.price * item.quantity;
        html += '<div class="opfw-cart-item">';
        html += '<div class="opfw-cart-item-name">' + item.name + "</div>";
        html += '<div class="opfw-cart-item-qty">';
        html += '<button class="btn btn-sm btn-outline-secondary qty-minus" data-index="' + index + '">-</button>';
        html += '<input type="number" value="' + item.quantity + '" min="1" data-index="' + index + '" class="form-control form-control-sm qty-input">';
        html += '<button class="btn btn-sm btn-outline-secondary qty-plus" data-index="' + index + '">+</button>';
        html += "</div>";
        html += '<div class="opfw-cart-item-price">' + self.config.currencySymbol + total.toFixed(2) + "</div>";
        html += '<span class="opfw-cart-item-remove" data-index="' + index + '">×</span>';
        html += "</div>";
      });

      this.elements.cartItems.html(html);
      this.updateSummary();
    },

    /**
     * Update order summary
     */
    updateSummary: function () {
      // Calculate subtotal
      var subtotal = 0;
      this.config.cart.forEach(function (item) {
        subtotal += item.price * item.quantity;
      });

      // Get discount and delivery
      var discount = parseFloat(this.elements.discountInput.val()) || 0;
      var delivery = parseFloat(this.elements.deliveryInput.val()) || 0;
      var taxable = subtotal - discount;

      // Calculate tax and VAT
      var vat = taxable * (this.config.vatRate / 100);
      var tax = taxable * (this.config.taxRate / 100);
      var grandTotal = taxable + vat + tax + delivery;

      // Update displays
      this.elements.subtotalDisplay.text(this.config.currencySymbol + subtotal.toFixed(2));
      this.elements.vatDisplay.text(this.config.currencySymbol + vat.toFixed(2));
      this.elements.taxDisplay.text(this.config.currencySymbol + tax.toFixed(2));
      this.elements.grandTotalDisplay.text(this.config.currencySymbol + grandTotal.toFixed(2));
    },

    /**
     * Process sale (save or complete)
     */
    processSale: function (action) {
      var self = this;

      // Check if cart is empty
      if (this.config.cart.length === 0) {
        showLimeModal(this.config.strings.cartEmptyAlert || "Cart is empty. Add items first.", "Cart Empty");
        return;
      }

      // Get order type
      var orderType = this.elements.orderTypeInputs.filter(":checked").val();

      // Get cooking instructions based on order type
      var cookingInstructions = "";
      if (orderType === "dineIn") {
        cookingInstructions = $("#dinein-instructions").val();
      } else if (orderType === "takeAway") {
        cookingInstructions = $("#takeaway-instructions").val();
      } else if (orderType === "pickup") {
        cookingInstructions = $("#pickup-instructions").val();
      }

      // Prepare sale data
      var saleData = {
        action: action,
        items: this.config.cart,
        customer_id: this.elements.customerSelect.val() || "",
        order_type: orderType,
        discount: this.elements.discountInput.val() || 0,
        delivery_cost: this.elements.deliveryInput.val() || 0,
        cooking_instructions: cookingInstructions,
        note: this.elements.notesTextarea.val(),
        saved_sale_id: this.elements.currentSaleId.val() || "",
      };

      // Show loading on appropriate button
      var button = action === "save" ? this.elements.saveSaleBtn : this.elements.completeSaleBtn;
      var originalText = button.text();
      button.prop("disabled", true).text("...");

      // Send AJAX request
      $.ajax({
        url: this.config.ajaxUrl,
        type: "POST",
        data: {
          action: "opfw_process_sale",
          nonce: this.config.nonces.process,
          sale_data: JSON.stringify(saleData),
        },
        success: function (response) {
          // Reset button
          button.prop("disabled", false).text(originalText);

          if (response.success) {
            showLimeModal(response.data.message || "Sale processed successfully!", "Success");

            if (action === "complete") {
              // Clear cart and reset form
              self.config.cart = [];
              self.config.savedSaleId = "";
              self.elements.currentSaleId.val("");
              self.elements.notesTextarea.val("");
              self.elements.discountInput.val(0);
              self.elements.deliveryInput.val(0);
              self.updateCartDisplay();
            }

            // Refresh saved sales list
            self.loadSavedSales();
          } else {
            showLimeModal(self.config.strings.error + ": " + response.data, "Error");
          }
        },
        error: function () {
          // Reset button
          button.prop("disabled", false).text(originalText);
          showLimeModal(self.config.strings.requestFailed || "Request failed. Please try again.", "Error");
        },
      });
    },
  };

  /**
   * Initialize POS when document is ready
   */
  $(document).ready(function () {
    if ($("#opfw-categories-list").length) {
      OPFW_POS.init();
    }
  });
})(jQuery);