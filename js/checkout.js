function checkoutPayload() {
  return {
    phone: document.getElementById("phone") ? document.getElementById("phone").value : "",
    address1: document.getElementById("address1") ? document.getElementById("address1").value : "",
    address2: document.getElementById("address2") ? document.getElementById("address2").value : "",
  };
}

function notifyCheckout(message, type, title) {
  if (window.appNotify) {
    window.appNotify({
      message: message,
      type: type || "info",
      title: title || "Checkout",
      delay: 3600,
    });
    return;
  }
  alert(message);
}

function redirectTo(url) {
  if (!url) {
    return;
  }
  window.location.href = url;
}

function initializeGateway(endpoint, successTitle) {
  var payload = checkoutPayload();

  $.ajax({
    type: "POST",
    url: endpoint,
    data: payload,
    dataType: "json",
    success: function (response) {
      if (response && response.success) {
        notifyCheckout("Redirecting to secure payment page...", "success", successTitle);
        setTimeout(function () {
          redirectTo(response.authorization_url || response.link || "");
        }, 250);
        return;
      }
      notifyCheckout(
        (response && response.message) || "Unable to initialize payment.",
        "error",
        "Payment Error"
      );
    },
    error: function (xhr) {
      var message = "Unable to initialize payment.";
      if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
      }
      notifyCheckout(message, "error", "Payment Error");
    },
  });
}

function payWithPaystack(event) {
  if (event) {
    event.preventDefault();
  }
  initializeGateway("paystack_initialize.php", "Paystack");
}

function payNow(event) {
  if (event) {
    event.preventDefault();
  }
  initializeGateway("flutterwave_initialize.php", "Flutterwave");
}
