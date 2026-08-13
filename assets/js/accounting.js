/**
 * Accounting Management
 * Plugin: Obydullah_POS_For_WooCommerce
 * Version: 1.0.1
 */
(function ($) {
  "use strict";
  let OPFWAccounting = {
    // Configuration (will be populated from wp_localize_script)
    config: {
      isSubmitting: false,
      currentPage: 1,
      perPage: 10,
      totalPages: 1,
      totalItems: 0,
      dateFrom: "",
      dateTo: "",
      ajaxUrl: "",
      strings: {},
      currencyTemplate: "",
      currentDate: "",
      addNonce: "",
      editNonce: "",
      deleteNonce: "",
      getNonce: "",
    },

    /**
     * Initialize accounting module
     * Called from document ready
     */
    init: function () {
      // Load configuration from localized script
      if (typeof opfwAccountingData !== "undefined") {
        this.config.ajaxUrl = opfwAccountingData.ajaxUrl || "";
        this.config.getNonce = opfwAccountingData.nonce_get_entries || "";
        this.config.addNonce = opfwAccountingData.nonce_add_entry || "";
        this.config.deleteNonce = opfwAccountingData.nonce_delete_entry || "";
        this.config.currencyTemplate = opfwAccountingData.currency_template || "";
        this.config.currentDate = opfwAccountingData.current_date || "";
        this.config.strings = opfwAccountingData.strings || {};
      }

      // Bind events
      this.bindEvents();

      // Load initial entries
      this.loadAccountingEntries();
    },

    /**
     * Bind all event handlers
     */
    bindEvents: function () {
      var self = this;

      // Form submission
      $("#add-accounting-form").on("submit", function (e) {
        e.preventDefault();
        self.handleAccountingSubmit();
      });

      // Filter functionality
      $("#search-entries").on("click", function () {
        self.config.dateFrom = $("#date-from").val();
        self.config.dateTo = $("#date-to").val();
        self.config.currentPage = 1;
        self.loadAccountingEntries();
      });

      // Reset filters
      $("#reset-filters").on("click", function () {
        $("#date-from").val("");
        $("#date-to").val("");
        self.config.dateFrom = "";
        self.config.dateTo = "";
        self.config.currentPage = 1;
        self.loadAccountingEntries();
      });

      // Per page change
      $("#per-page-select").on("change", function () {
        self.config.perPage = parseInt($(this).val());
        self.loadAccountingEntries(1);
      });

      // Pagination handlers
      $(".first-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadAccountingEntries(1);
      });

      $(".prev-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage > 1) self.loadAccountingEntries(self.config.currentPage - 1);
      });

      $(".next-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadAccountingEntries(self.config.currentPage + 1);
      });

      $(".last-page").on("click", function (e) {
        e.preventDefault();
        if (self.config.currentPage < self.config.totalPages) self.loadAccountingEntries(self.config.totalPages);
      });

      $("#current-page-selector").on("keypress", function (e) {
        if (e.which === 13) {
          // Enter key
          let page = parseInt($(this).val());
          if (page >= 1 && page <= self.config.totalPages) {
            self.loadAccountingEntries(page);
          }
        }
      });

      // Delete entry (delegated)
      $(document).on("click", ".pos-action.delete", function () {
        self.handleDeleteEntry(this);
      });
    },

    /**
     * Format currency using PHP helper output
     */
    formatCurrency: function (amount) {
      const amountFormatted = parseFloat(amount).toFixed(2);
      return this.config.currencyTemplate.replace("0.00", amountFormatted);
    },

    /**
     * Update summary cards
     */
    updateSummaryCards: function (totals) {
      $("#total-income").text(totals.total_income || this.formatCurrency(0));
      $("#total-expense").text(totals.total_expense || this.formatCurrency(0));
    },

    /**
     * Update pagination UI
     */
    updatePagination: function (pagination) {
      this.config.totalPages = pagination.total_pages;
      this.config.totalItems = pagination.total_items;

      // Update displaying text
      $("#displaying-num").text(pagination.total_items + " " + this.config.strings.items);

      // Update page input and total pages
      $("#current-page-selector").val(this.config.currentPage);
      $(".total-pages").text(this.config.totalPages);

      // Update pagination buttons state
      $(".first-page, .prev-page").prop("disabled", this.config.currentPage === 1);
      $(".next-page, .last-page").prop("disabled", this.config.currentPage === this.config.totalPages);
    },

    /**
     * Set loading state for submit button
     */
    setButtonLoading: function (loading) {
      let button = $("#submit-accounting");
      let spinner = button.find(".spinner");
      let btnText = button.find(".btn-text");

      if (loading) {
        button.prop("disabled", true).addClass("disabled");
        spinner.removeClass("d-none");
        btnText.text(this.config.strings.saving);
      } else {
        button.prop("disabled", false).removeClass("disabled");
        spinner.addClass("d-none");
        btnText.text(this.config.strings.save_entry);
      }
    },

    /**
     * Reset form to initial state
     */
    resetForm: function () {
      $("#in-amount").val("0.00");
      $("#out-amount").val("0.00");
      $("#entry-description").val("");
      $("#entry-date").val(this.config.currentDate);
      $("#in-amount").focus();
      this.setButtonLoading(false);
    },

    /**
     * Load accounting entries with pagination and filters
     */
    loadAccountingEntries: function (page = 1) {
      var self = this;
      self.config.currentPage = page;

      let tbody = $("#accounting-list");
      tbody.html('<tr><td colspan="5" class="loading-entries"><span class="spinner is-active"></span> ' + (self.config.strings.loadingEntries || "Loading entries...") + "</td></tr>");

      $.ajax({
        url: self.config.ajaxUrl,
        type: "GET",
        data: {
          action: "opfw_get_accounting_entries",
          page: self.config.currentPage,
          per_page: self.config.perPage,
          date_from: self.config.dateFrom,
          date_to: self.config.dateTo,
          nonce: self.config.getNonce,
        },
        success: function (response) {
          tbody.empty();
          if (response.success) {
            if (!response.data.entries.length) {
              tbody.append('<tr><td colspan="5" class="no-entries">' + (self.config.strings.noEntries || "No accounting entries found.") + "</td></tr>");
              self.updateSummaryCards(response.data.totals);
              self.updatePagination({
                total_items: response.data.total,
                total_pages: Math.ceil(response.data.total / self.config.perPage),
              });
              return;
            }

            $.each(response.data.entries, function (_, entry) {
              let row = $("<tr>").attr("data-entry-id", entry.id);

              // Date column
              let formattedDate = entry.formatted_date || new Date(entry.created_at).toLocaleDateString();
              row.append($("<td>").text(formattedDate));

              // Description column
              let description = entry.description || "-";
              let isLong = description.length > 100;
              let shortDesc = isLong ? description.substring(0, 100) + "..." : description;

              let descCell = $("<td>").append(
                $("<div>")
                  .addClass("entry-description")
                  .addClass(isLong ? "cursor-pointer" : "")
                  .css({
                    "max-width": "300px",
                    "max-height": "60px",
                    "word-wrap": "break-word",
                  })
                  .attr("data-full-text", description)
                  .attr("data-expanded", "false")
                  .text(shortDesc)
              );

              // Add click to expand if text is long
              if (isLong) {
                descCell.find(".entry-description").on("click", function () {
                  let $this = $(this);
                  let expanded = $this.attr("data-expanded") === "true";

                  if (expanded) {
                    $this.text(shortDesc).css("max-height", "60px").attr("data-expanded", "false");
                  } else {
                    $this.text(description).css("max-height", "none").attr("data-expanded", "true");
                  }
                });
              }

              row.append(descCell);

              // Income column
              let incomeAmount = parseFloat(entry.in_amount || 0);
              let formattedIncome = entry.formatted_in_amount || self.formatCurrency(incomeAmount);
              row.append($("<td>").append($("<span>").text(formattedIncome)));

              // Expense column
              let expenseAmount = parseFloat(entry.out_amount || 0);
              let formattedExpense = entry.formatted_out_amount || self.formatCurrency(expenseAmount);
              row.append($("<td>").append($("<span>").text(formattedExpense)));

              // Actions column
              row.append(
                $("<td>")
                  .addClass("pos-row-actions")
                  .append(
                    $("<button>")
                      .addClass("pos-action delete")
                      .text(self.config.strings.delete || "Delete")
                      .attr("data-id", entry.id)
                  )
              );

              tbody.append(row);
            });

            self.updateSummaryCards(response.data.totals);
            self.updatePagination({
              total_items: response.data.total,
              total_pages: Math.ceil(response.data.total / self.config.perPage),
            });
          } else {
            tbody.append('<tr><td colspan="5" class="error-message">' + response.data + "</td></tr>");
          }
        },
        error: function () {
          $("#accounting-list").html('<tr><td colspan="5" class="error-message">' + (self.config.strings.loadError || "Failed to load entries.") + "</td></tr>");
        },
      });
    },

    /**
     * Handle accounting form submission
     */
    handleAccountingSubmit: function () {
      var self = this;

      // Prevent double submission
      if (self.config.isSubmitting) {
        return false;
      }

      let inAmount = parseFloat($("#in-amount").val()) || 0;
      let outAmount = parseFloat($("#out-amount").val()) || 0;
      let description = $("#entry-description").val();
      let entryDate = $("#entry-date").val();

      // Validation
      if (inAmount === 0 && outAmount === 0) {
        showLimeModal(self.config.strings.amountRequired || "Please enter either income or expense amount", "Validation Error");
        return false;
      }

      if (inAmount < 0) {
        showLimeModal(self.config.strings.validIncome || "Please enter a valid income amount", "Validation Error");
        return false;
      }

      if (outAmount < 0) {
        showLimeModal(self.config.strings.validExpense || "Please enter a valid expense amount", "Validation Error");
        return false;
      }

      // Set submitting state
      self.config.isSubmitting = true;
      self.setButtonLoading(true);

      $.post(
        self.config.ajaxUrl,
        {
          action: "opfw_add_accounting_entry",
          in_amount: inAmount,
          out_amount: outAmount,
          description: description,
          entry_date: entryDate,
          nonce: self.config.addNonce,
        },
        function (res) {
          if (res.success) {
            showLimeModal(self.config.strings.successMessage || "Entry saved successfully!", "Success");

            const modal = $("#lime-alert-modal");
            modal
              .find("#lime-alert-close")
              .off("click")
              .on("click", function () {
                self.resetForm();
                self.loadAccountingEntries(1);
                modal.addClass("d-none");
              });
          } else {
            showLimeModal(self.config.strings.error + " " + res.data, "Error");
          }
        }
      )
        .fail(function () {
          showLimeModal(self.config.strings.requestFailed || "Request failed. Please try again.", "Error");
        })
        .always(function () {
          // Reset submitting state
          self.config.isSubmitting = false;
          self.setButtonLoading(false);
        });
    },

    /**
     * Handle delete entry
     */
    handleDeleteEntry: function (button) {
      var self = this;
      var $button = $(button);
      var originalText = $button.text();
      var id = $button.closest("tr").data("entry-id");

      // Show confirmation modal instead of default confirm
      showLimeConfirm(
        self.config.strings.confirmDelete || "Are you sure you want to delete this accounting entry?",
        function onYes() {
          // Disable button and show deleting text
          $button.prop("disabled", true).text(self.config.strings.deleting || "Deleting...");

          // Send AJAX request to delete entry
          $.post(self.config.ajaxUrl, {
            action: "opfw_delete_accounting_entry",
            id: id,
            nonce: self.config.deleteNonce,
          })
            .done(function (res) {
              if (res.success) {
                self.loadAccountingEntries(self.config.currentPage);
                showLimeModal(res.data, "Success");
              } else {
                showLimeModal(res.data, "Error");
              }
            })
            .fail(function () {
              showLimeModal(self.config.strings.deleteFailed || "Delete request failed. Please try again.", "Error");
            })
            .always(function () {
              // Re-enable button
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
    if ($("#add-accounting-form").length) {
      OPFWAccounting.init();
    }
  });
})(jQuery);