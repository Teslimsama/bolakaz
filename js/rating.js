$(function () {
  function showFeedback(message, isError) {
    var $feedback = $("#reviewFeedback");
    if (!$feedback.length) {
      return;
    }
    $feedback
      .removeClass("alert-success alert-danger d-none")
      .addClass(isError ? "alert-danger" : "alert-success")
      .text(message);
  }

  $(".rateButton").on("click", function () {
    var selectedIndex = $(".rateButton").index(this) + 1;

    $(".rateButton")
      .removeClass("btn-primary star-selected")
      .addClass("btn-grey btn-default");

    $(".rateButton").each(function (idx) {
      if (idx < selectedIndex) {
        $(this)
          .removeClass("btn-grey btn-default")
          .addClass("btn-primary star-selected");
      }
    });

    $("#rating").val(selectedIndex);
  });

  $("#ratingForm").on("submit", function (event) {
    event.preventDefault();
    var $form = $(this);
    var $submit = $("#saveReview");

    $submit.prop("disabled", true).addClass("is-loading");

    $.ajax({
      type: "POST",
      dataType: "json",
      url: "action",
      data: $form.serialize(),
    })
      .done(function (response) {
        if (response && response.success) {
          var okMessage = response.message || "Review saved successfully.";
          showFeedback(okMessage, false);
          if (window.appNotify) {
            window.appNotify({
              message: okMessage,
              type: "success",
              title: "Review Submitted",
            });
          }
          setTimeout(function () {
            window.location.reload();
          }, 700);
          return;
        }
        var failMessage =
          (response && response.message) || "Unable to save review right now.";
        showFeedback(failMessage, true);
        if (window.appNotify) {
          window.appNotify({
            message: failMessage,
            type: "error",
            title: "Review Failed",
          });
        }
      })
      .fail(function (xhr) {
        var message = "Unable to save review right now.";
        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        }
        showFeedback(message, true);
        if (window.appNotify) {
          window.appNotify({
            message: message,
            type: "error",
            title: "Review Failed",
          });
        }
      })
      .always(function () {
        $submit.prop("disabled", false).removeClass("is-loading");
      });
  });
});
