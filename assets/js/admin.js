/**
 * Modal
 * Plugin: Obydullah_POS_For_WooCommerce
 * Version: 1.0.1
 */
(function ($) {
  "use strict";

  /**
   * Alert Modal
   */
  window.showLimeModal = function (message, title = "Alert") {
    let modal = $("#lime-alert-modal");

    if (!modal.length) {
      modal = $(`
        <div id="lime-alert-modal" class="d-none position-fixed top-0 left-0 w-100 h-100" style="z-index: 9999; text-align: center;">
          <span style="display:inline-block; height:100%; vertical-align:middle;"></span>
          <div class="d-inline-block align-middle bg-white rounded shadow" style="margin: 10% auto; max-width:400px; width:90%; text-align:left;">
            
            <div id="lime-alert-title" class="bg-dark text-white p-3 font-weight-bold rounded-top"></div>
            <div id="lime-alert-message" class="p-3"></div>

            <div class="p-3 text-right">
              <button id="lime-alert-close" class="btn btn-dark">OK</button>
            </div>
          </div>
        </div>
      `);

      $("body").append(modal);
    }

    modal.find("#lime-alert-title").text(title);
    modal.find("#lime-alert-message").html(message);
    modal.removeClass("d-none");

    modal
      .find("#lime-alert-close")
      .off("click")
      .on("click", function () {
        modal.addClass("d-none");
      });

    modal.off("click").on("click", function (e) {
      if (e.target.id === "lime-alert-modal") {
        modal.addClass("d-none");
      }
    });
  };

  /**
   * Confirm Modal
   */
  window.showLimeConfirm = function (message, onYes, title = "Confirm") {
    let modal = $("#lime-confirm-modal");

    if (!modal.length) {
      modal = $(`
        <div id="lime-confirm-modal" class="d-none position-fixed top-0 left-0 w-100 h-100" style="z-index: 9999; text-align: center;">
          <span style="display:inline-block; height:100%; vertical-align:middle;"></span>
          <div class="d-inline-block align-middle bg-white rounded shadow" style="margin: 10% auto; max-width:400px; width:90%; text-align:left;">
            
            <div id="lime-confirm-title" class="bg-dark text-white p-3 font-weight-bold rounded-top"></div>
            <div id="lime-confirm-message" class="p-3"></div>

            <div class="p-3 text-right">
              <button id="lime-confirm-cancel" class="btn btn-light mr-2">No</button>
              <button id="lime-confirm-ok" class="btn btn-danger">Yes</button>
            </div>
          </div>
        </div>
      `);

      $("body").append(modal);
    }

    modal.find("#lime-confirm-title").text(title);
    modal.find("#lime-confirm-message").html(message);
    modal.removeClass("d-none");

    modal
      .find("#lime-confirm-cancel")
      .off("click")
      .on("click", function () {
        modal.addClass("d-none");
      });

    modal
      .find("#lime-confirm-ok")
      .prop("disabled", false)
      .off("click")
      .on("click", function () {
        $(this).prop("disabled", true);
        modal.addClass("d-none");
        if (typeof onYes === "function") onYes();
      });

    modal.off("click").on("click", function (e) {
      if (e.target.id === "lime-confirm-modal") modal.addClass("d-none");
    });
  };
})(jQuery);