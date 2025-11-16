// Import Workbox (only for navigationPreload support, no Workbox features used)
importScripts('https://storage.googleapis.com/workbox-cdn/releases/5.1.4/workbox-sw.js');

const CACHE = "pwa-cache-v38";
const offlineFallbackPage = "offline/"; // Ensure this points to a valid offline file like "offline/index.html"

const filesToCache = [
  'offline/',
  'assets/files/backgrounds/error_page_bg.jpg',
  'assets/files/backgrounds/offline_error_expression_text_bg.jpg',
  'assets/thirdparty/bootstrap/bootstrap.min.css',
  'assets/css/error_page/error_page.css',
  'assets/files/defaults/favicon.png',
  'assets/fonts/montserrat/montserrat-bold.woff',
  'assets/fonts/montserrat/montserrat-medium.woff',
  'assets/fonts/montserrat/montserrat-semibold.woff',
  'assets/fonts/montserrat/font.css',
  'assets/thirdparty/bootstrap/bootstrap.bundle.min.js',
];

// Skip waiting when requested
self.addEventListener("message", (event) => {
  if (event.data && event.data.type === "SKIP_WAITING") {
    self.skipWaiting();
  }
});

// Install event: cache static files
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(filesToCache))
  );
  self.skipWaiting(); // Activate immediately
});

// Activate event: remove old caches
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) =>
      Promise.all(
        cacheNames
          .filter((name) => name !== CACHE)
          .map((name) => caches.delete(name))
      )
    )
  );
  self.clients.claim(); // Take control of uncontrolled clients
});

// Disable navigation preload if supported
if (workbox?.navigationPreload?.isSupported()) {
  workbox.navigationPreload.disable();
}

// Fetch event
self.addEventListener("fetch", (event) => {
  const url = new URL(event.request.url);

  // Ignore specific API endpoints
  if (url.pathname.endsWith("realtime_request/") || url.pathname.endsWith("web_request/")) {
    return;
  }

  // Navigation requests: network-first, fallback to offline page
  if (event.request.mode === "navigate") {
    event.respondWith(
      (async () => {
        try {
          return await fetch(event.request);
        } catch {
          const cache = await caches.open(CACHE);
          const fallback = await cache.match(offlineFallbackPage);
          return fallback || new Response("Offline", { status: 503 });
        }
      })()
    );
    return;
  }

  // Cache-first strategy for static files
  if (filesToCache.some((file) => url.pathname.endsWith(file))) {
    event.respondWith(
      (async () => {
        const cache = await caches.open(CACHE);
        const cached = await cache.match(event.request);
        if (cached) return cached;

        try {
          const response = await fetch(event.request);
          cache.put(event.request, response.clone());
          return response;
        } catch {
          const fallback = await cache.match(offlineFallbackPage);
          return fallback || new Response("Offline", { status: 503 });
        }
      })()
    );
  }
});


async function updateUnreadCount() {
  try {
    const dataToSend = new FormData();
    dataToSend.append('realtime', 'true');
    dataToSend.append('unread_group_messages', 0);
    dataToSend.append('unread_private_chat_messages', 0);
    dataToSend.append('unread_site_notifications', 0);

    const response = await fetch('web_request/', {
      method: 'POST',
      body: dataToSend,
      credentials: 'include',
    });

    if (!response.ok) {
      throw new Error('Network response was not ok');
    }

    const data = await response.json();
    let unread_count = 0;
    if (data?.unread_private_chat_messages) unread_count += Number(data.unread_private_chat_messages) || 0;
    if (data?.unread_site_notifications) unread_count += Number(data.unread_site_notifications) || 0;
    if (data?.unread_group_messages) unread_count += Number(data.unread_group_messages) || 0;
    
    if (unread_count !== 0) {
      setBadge(unread_count);
    }
  } catch (err) {
    console.error('Failed to fetch unread count', err);
  }
}

//setInterval(updateUnreadCount, 10000);

async function setBadge(count) {
  if ('setAppBadge' in navigator) {
    try {
      await navigator.setAppBadge(count);
      console.log('Badge set to:', count);
    } catch (err) {
      console.error('Failed to set app badge:', err);
    }
  } else {
    console.log('App Badge API is not supported on this platform.');
  }
}
