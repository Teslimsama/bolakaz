$(function () {
    var $form = $("#contactForm");
    var $button = $("#sendMessageButton");
    var $success = $("#success");

    function showAlert(type, message) {
        var klass = type === "success" ? "alert-success" : "alert-danger";
        $success.html("<div class='alert " + klass + "' role='alert'>" + message + "</div>");
    }

    $form.on("submit", function (event) {
        event.preventDefault();

        if (this.checkValidity && !this.checkValidity()) {
            this.reportValidity();
            return;
        }

        var name = $.trim($("#name").val());
        var email = $.trim($("#email").val());
        var subject = $.trim($("#subject").val());
        var message = $.trim($("#message").val());

        $button.prop("disabled", true);

        $.ajax({
            url: "mail/contact_mail.php",
            type: "POST",
            data: {
                name: name,
                email: email,
                subject: subject,
                message: message,
            },
            cache: false,
        })
            .done(function () {
                showAlert("success", "Your message has been sent.");
                $form.trigger("reset");
            })
            .fail(function () {
                showAlert("error", "Sorry " + name + ", our mail server is not responding. Please try again later.");
            })
            .always(function () {
                $button.prop("disabled", false);
            });
    });

    $("#name").on("focus", function () {
        $success.html("");
    });
});
