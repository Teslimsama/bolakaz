(function () {
  "use strict";

  var APP_NAME = "Bolakaz";
  var PROMPT_ID = "pwaInstallPrompt";
  var STYLE_ID = "pwaInstallPromptStyles";
  var DISMISS_KEY = "bolakaz:pwa-install-dismissed-at";
  var INSTALLED_KEY = "bolakaz:pwa-installed";
  var CACHE_PREFIXES = ["bolakaz-static-", "bolakaz-pages-"];
  var DISMISS_TTL = 7 * 24 * 60 * 60 * 1000;
  var deferredPrompt = null;

  if (window.location.protocol !== "http:" && window.location.protocol !== "https:") {
    return;
  }

  function canUseStorage() {
    try {
      var probeKey = "__pwa_install_probe__";
      window.localStorage.setItem(probeKey, "1");
      window.localStorage.removeItem(probeKey);
      return true;
    } catch (error) {
      return false;
    }
  }

  function readStorage(key) {
    if (!canUseStorage()) {
      return "";
    }

    return window.localStorage.getItem(key) || "";
  }

  function writeStorage(key, value) {
    if (!canUseStorage()) {
      return;
    }

    window.localStorage.setItem(key, value);
  }

  function removeStorage(key) {
    if (!canUseStorage()) {
      return;
    }

    window.localStorage.removeItem(key);
  }

  function clearLegacyPwaCaches() {
    if (!("caches" in window)) {
      return Promise.resolve();
    }

    return window.caches.keys().then(function (keys) {
      return Promise.all(
        keys.map(function (key) {
          var shouldDelete = CACHE_PREFIXES.some(function (prefix) {
            return key.indexOf(prefix) === 0;
          });

          if (!shouldDelete) {
            return Promise.resolve(false);
          }

          return window.caches.delete(key);
        }),
      );
    });
  }

  function isStandalone() {
    var displayModeStandalone =
      typeof window.matchMedia === "function" &&
      window.matchMedia("(display-mode: standalone)").matches;
    var navigatorStandalone = typeof window.navigator.standalone === "boolean" && window.navigator.standalone;

    return displayModeStandalone || navigatorStandalone;
  }

  function getUserAgent() {
    return String(window.navigator.userAgent || "").toLowerCase();
  }

  function isIos() {
    var ua = getUserAgent();
    var platform = String(window.navigator.platform || "");
    return /iphone|ipad|ipod/.test(ua) || (platform === "MacIntel" && window.navigator.maxTouchPoints > 1);
  }

  function isAndroid() {
    return /android/.test(getUserAgent());
  }

  function isMobile() {
    return isIos() || isAndroid();
  }

  function isChromeLike() {
    var ua = getUserAgent();
    var vendor = String(window.navigator.vendor || "").toLowerCase();
    var isCriOS = ua.indexOf("crios") !== -1;
    var isEdge = ua.indexOf("edg/") !== -1 || ua.indexOf("edgios") !== -1 || ua.indexOf("edga/") !== -1;
    var isOpera = ua.indexOf("opr/") !== -1 || ua.indexOf("opera") !== -1;
    var isSamsung = ua.indexOf("samsungbrowser") !== -1;
    var hasChromeToken = ua.indexOf("chrome") !== -1 || isCriOS;

    return hasChromeToken && !isEdge && !isOpera && !isSamsung && (isCriOS || vendor.indexOf("google") !== -1);
  }

  function isInstalledOrDismissed() {
    if (isStandalone()) {
      return true;
    }

    if (readStorage(INSTALLED_KEY) === "1") {
      return true;
    }

    var dismissedAt = Number(readStorage(DISMISS_KEY) || 0);
    if (!dismissedAt) {
      return false;
    }

    return Date.now() - dismissedAt < DISMISS_TTL;
  }

  function shouldShowFallbackHint() {
    if (!isMobile()) {
      return false;
    }

    if (deferredPrompt) {
      return false;
    }

    if (isIos()) {
      return true;
    }

    return isAndroid() && !isChromeLike();
  }

  function injectStyles() {
    if (document.getElementById(STYLE_ID)) {
      return;
    }

    var style = document.createElement("style");
    style.id = STYLE_ID;
    style.textContent =
      "#"+PROMPT_ID+"{position:fixed;left:1rem;right:1rem;bottom:1rem;z-index:1200;display:none}" +
      "#"+PROMPT_ID+".is-visible{display:block}" +
      "#"+PROMPT_ID+" .pwa-install-card{max-width:28rem;margin:0 auto;background:#111827;color:#f9fafb;border-radius:18px;padding:1rem 1rem 0.9rem;box-shadow:0 22px 50px rgba(15,23,42,.28);border:1px solid rgba(255,255,255,.1)}" +
      "#"+PROMPT_ID+" .pwa-install-title{display:block;font-size:1rem;font-weight:700;line-height:1.3;margin-bottom:.35rem}" +
      "#"+PROMPT_ID+" .pwa-install-text{margin:0;font-size:.93rem;line-height:1.5;color:rgba(249,250,251,.86)}" +
      "#"+PROMPT_ID+" .pwa-install-actions{display:flex;flex-wrap:wrap;gap:.65rem;margin-top:1rem}" +
      "#"+PROMPT_ID+" button{appearance:none;border:0;border-radius:999px;padding:.72rem 1rem;font:inherit;font-weight:700;cursor:pointer}" +
      "#"+PROMPT_ID+" .pwa-install-primary{background:#f59e0b;color:#111827}" +
      "#"+PROMPT_ID+" .pwa-install-secondary{background:rgba(255,255,255,.08);color:#f9fafb}" +
      "#"+PROMPT_ID+" .pwa-install-primary[disabled]{opacity:.68;cursor:wait}" +
      "@media (min-width:768px){#"+PROMPT_ID+"{left:auto;right:1.25rem;bottom:1.25rem}#"+PROMPT_ID+" .pwa-install-card{margin:0}}";
    document.head.appendChild(style);
  }

  function createPromptElement() {
    var existing = document.getElementById(PROMPT_ID);
    if (existing) {
      return existing;
    }

    injectStyles();

    var wrapper = document.createElement("aside");
    wrapper.id = PROMPT_ID;
    wrapper.setAttribute("aria-live", "polite");
    wrapper.innerHTML =
      '<div class="pwa-install-card">' +
      '<strong class="pwa-install-title"></strong>' +
      '<p class="pwa-install-text"></p>' +
      '<div class="pwa-install-actions">' +
      '<button type="button" class="pwa-install-primary" data-action="install">Install App</button>' +
      '<button type="button" class="pwa-install-secondary" data-action="dismiss">Not Now</button>' +
      "</div>" +
      "</div>";

    wrapper.addEventListener("click", function (event) {
      var target = event.target;
      if (!target || typeof target.getAttribute !== "function") {
        return;
      }

      var action = target.getAttribute("data-action");
      if (action === "dismiss") {
        dismissPrompt();
      }

      if (action === "install") {
        handleInstall();
      }
    });

    document.body.appendChild(wrapper);
    return wrapper;
  }

  function syncBodyPadding() {
    var prompt = document.getElementById(PROMPT_ID);
    if (!document.body) {
      return;
    }

    if (!prompt || !prompt.classList.contains("is-visible")) {
      document.body.style.removeProperty("padding-bottom");
      return;
    }

    var promptHeight = Math.ceil(prompt.getBoundingClientRect().height);
    document.body.style.paddingBottom = String(promptHeight + 24) + "px";
  }

  function hidePrompt() {
    var prompt = document.getElementById(PROMPT_ID);
    if (!prompt) {
      syncBodyPadding();
      return;
    }

    prompt.classList.remove("is-visible");
    syncBodyPadding();
  }

  function dismissPrompt() {
    writeStorage(DISMISS_KEY, String(Date.now()));
    hidePrompt();
  }

  function renderPrompt() {
    if (isInstalledOrDismissed()) {
      hidePrompt();
      return;
    }

    if (!deferredPrompt && !shouldShowFallbackHint()) {
      hidePrompt();
      return;
    }

    var prompt = createPromptElement();
    var title = prompt.querySelector(".pwa-install-title");
    var message = prompt.querySelector(".pwa-install-text");
    var installButton = prompt.querySelector('[data-action="install"]');

    if (!title || !message || !installButton) {
      return;
    }

    title.textContent = "Install " + APP_NAME;

    if (deferredPrompt) {
      message.textContent = isChromeLike()
        ? "Install " + APP_NAME + " for faster access from your home screen. Chrome gives the smoothest install flow."
        : "Install " + APP_NAME + " for faster access from your home screen. If Chrome is available on this device, its install flow is usually the smoothest.";
      installButton.hidden = false;
      installButton.disabled = false;
      installButton.textContent = "Install App";
    } else if (isIos()) {
      message.textContent = "To install " + APP_NAME + " on iPhone or iPad, open the share menu in Safari and choose Add to Home Screen.";
      installButton.hidden = true;
    } else {
      message.textContent = "For the smoothest install flow, open this page in Chrome on Android and use Install App from the browser menu.";
      installButton.hidden = true;
    }

    prompt.classList.add("is-visible");
    syncBodyPadding();
  }

  function handleInstall() {
    if (!deferredPrompt) {
      renderPrompt();
      return;
    }

    var installEvent = deferredPrompt;
    deferredPrompt = null;

    var prompt = createPromptElement();
    var installButton = prompt.querySelector('[data-action="install"]');
    if (installButton) {
      installButton.disabled = true;
      installButton.textContent = "Opening...";
    }

    installEvent.prompt();
    installEvent.userChoice
      .then(function (choice) {
        if (choice && choice.outcome === "accepted") {
          writeStorage(INSTALLED_KEY, "1");
          removeStorage(DISMISS_KEY);
          hidePrompt();
          return;
        }

        writeStorage(DISMISS_KEY, String(Date.now()));
        hidePrompt();
      })
      .catch(function () {
        if (installButton) {
          installButton.disabled = false;
          installButton.textContent = "Install App";
        }
      });
  }

  function registerServiceWorker() {
    if (!("serviceWorker" in navigator)) {
      return;
    }

    window.addEventListener("load", function () {
      clearLegacyPwaCaches()
        .catch(function () {
          return Promise.resolve();
        })
        .then(function () {
          return navigator.serviceWorker.register("sw.js");
        })
        .catch(function (error) {
          if (window.console && typeof window.console.warn === "function") {
            window.console.warn("PWA registration failed", error);
          }
        });
    });
  }

  function ready(callback) {
    if (document.readyState === "loading") {
      var onReady = function () {
        document.removeEventListener("DOMContentLoaded", onReady);
        callback();
      };

      document.addEventListener("DOMContentLoaded", onReady);
      return;
    }

    callback();
  }

  window.addEventListener("beforeinstallprompt", function (event) {
    event.preventDefault();
    deferredPrompt = event;
    ready(renderPrompt);
  });

  window.addEventListener("appinstalled", function () {
    writeStorage(INSTALLED_KEY, "1");
    removeStorage(DISMISS_KEY);
    deferredPrompt = null;
    hidePrompt();
  });

  registerServiceWorker();

  ready(function () {
    window.setTimeout(renderPrompt, 1800);
  });

  window.addEventListener("resize", function () {
    if (!document.getElementById(PROMPT_ID)) {
      return;
    }

    syncBodyPadding();
  });
})();
