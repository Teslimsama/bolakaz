(function () {
  "use strict";

  var cookieKey = "sf_cookie_consent_v2";
  var cookieBanner = document.querySelector("[data-cookie-banner]");
  var cookieAccept = document.querySelector("[data-cookie-accept]");

  if (cookieBanner) {
    if (document.cookie.indexOf(cookieKey + "=1") > -1) {
      cookieBanner.classList.add("is-hidden");
    }
  }

  if (cookieAccept) {
    cookieAccept.addEventListener("click", function () {
      document.cookie = cookieKey + "=1; max-age=" + 60 * 60 * 24 * 365 + "; path=/";
      if (cookieBanner) {
        cookieBanner.classList.add("is-hidden");
      }
    });
  }

  var topLinks = document.querySelectorAll('a[href="#"]');
  for (var i = 0; i < topLinks.length; i++) {
    topLinks[i].addEventListener("click", function (event) {
      event.preventDefault();
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }
})();
