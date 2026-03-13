const VERSION = "20260313-1";
const STATIC_CACHE = "bolakaz-static-" + VERSION;
const PAGE_CACHE = "bolakaz-pages-" + VERSION;
const OFFLINE_URL = "./offline.html";
const PRECACHE_URLS = [
  OFFLINE_URL,
  "./favicomatic/site.webmanifest",
  "./favicomatic/icon-192.png",
  "./favicomatic/icon-512.png",
  "./favicomatic/apple-touch-icon.png",
  "./favicomatic/favicon-32x32.png",
];

self.addEventListener("install", function (event) {
  self.skipWaiting();
  event.waitUntil(
    caches
      .open(STATIC_CACHE)
      .then(function (cache) {
        return cache.addAll(
          PRECACHE_URLS.map(function (url) {
            return new Request(url, { cache: "reload" });
          }),
        );
      })
      .catch(function () {
        return Promise.resolve();
      }),
  );
});

self.addEventListener("activate", function (event) {
  event.waitUntil(
    caches
      .keys()
      .then(function (keys) {
        return Promise.all(
          keys.map(function (key) {
            if (key !== STATIC_CACHE && key !== PAGE_CACHE) {
              return caches.delete(key);
            }

            return Promise.resolve(false);
          }),
        );
      })
      .then(function () {
        return self.clients.claim();
      }),
  );
});

self.addEventListener("fetch", function (event) {
  const request = event.request;
  if (request.method !== "GET") {
    return;
  }

  const url = new URL(request.url);
  if (url.origin !== self.location.origin || isBypassedPath(url.pathname)) {
    return;
  }

  if (isNavigationRequest(request)) {
    event.respondWith(handleNavigationRequest(request));
    return;
  }

  if (isStaticAsset(url.pathname)) {
    event.respondWith(handleStaticAssetRequest(request));
  }
});

function isNavigationRequest(request) {
  const accept = request.headers.get("accept") || "";
  return request.mode === "navigate" || accept.indexOf("text/html") !== -1;
}

function isStaticAsset(pathname) {
  return /\.(?:css|js|png|jpg|jpeg|svg|webp|gif|ico|woff2?)$/i.test(pathname);
}

function isBypassedPath(pathname) {
  const normalized = pathname.toLowerCase();
  if (normalized.indexOf("/admin/") !== -1) {
    return true;
  }

  return [
    "/action.php",
    "/cart_add.php",
    "/cart_delete.php",
    "/cart_fetch.php",
    "/cart_update.php",
    "/fetch_data.php",
    "/keyup.php",
    "/process.php",
    "/process_data.php",
  ].some(function (suffix) {
    return normalized.endsWith(suffix);
  });
}

async function handleNavigationRequest(request) {
  try {
    const response = await fetch(request);
    const cache = await caches.open(PAGE_CACHE);
    cache.put(request, response.clone());
    return response;
  } catch (error) {
    const cached = await caches.match(request);
    if (cached) {
      return cached;
    }

    const offline = await caches.match(OFFLINE_URL);
    if (offline) {
      return offline;
    }

    throw error;
  }
}

async function handleStaticAssetRequest(request) {
  const cached = await caches.match(request);
  const networkFetch = fetch(request)
    .then(async function (response) {
      if (response && response.ok) {
        const cache = await caches.open(STATIC_CACHE);
        cache.put(request, response.clone());
      }

      return response;
    })
    .catch(function () {
      return cached;
    });

  return cached || networkFetch;
}
