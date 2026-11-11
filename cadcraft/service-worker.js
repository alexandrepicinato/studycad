/* Scoped, same-origin CADCraft offline cache; never caches installer, forms or credentials. */
const VERSION = 'cadcraft-pwa-v1-2026-10';
const SHELL_CACHE = `${VERSION}-shell`;
const RUNTIME_CACHE = `${VERSION}-runtime`;
const SCOPE = self.registration.scope;
const SHELL = [
  './', './index.html', './manifest.webmanifest',
  './pwa/install.js', './pwa/install.css', './pwa/precache.json',
  './pwa/icons/cadcraft-192.png', './pwa/icons/cadcraft-512.png',
  './pwa/icons/cadcraft-maskable-512.png', './pwa/icons/cadcraft-180.png'
];
self.addEventListener('install', event => {
  event.waitUntil((async () => {
    const cache = await caches.open(SHELL_CACHE);
    await Promise.allSettled(SHELL.map(path => cache.add(new Request(new URL(path, SCOPE).href, { cache:'reload' }))));
    // Cache compiled hashed JS/WASM so the installed PWA can reopen offline after first installation.
    try {
      const manifest = await fetch(new URL('./pwa/precache.json', SCOPE), {cache:'no-store'});
      if (manifest.ok) {
        const paths = await manifest.json();
        if (Array.isArray(paths)) {
          await Promise.allSettled(paths.slice(0, 40).filter(p => typeof p === 'string' && /^\.\/[a-zA-Z0-9_.-]+\.(?:wasm|js|css)$/.test(p))
            .map(path => cache.add(new Request(new URL(path, SCOPE).href, {cache:'reload'}))));
        }
      }
    } catch (e) { /* Browser storage or offline limitations: don't block installation. */ }
    await self.skipWaiting();
  })());
});
self.addEventListener('activate', event => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter(k => k.startsWith('cadcraft-pwa-') && ![SHELL_CACHE, RUNTIME_CACHE].includes(k)).map(k => caches.delete(k)));
    await self.clients.claim();
  })());
});
self.addEventListener('fetch', event => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin || !url.href.startsWith(SCOPE)) return;
  if (/\/(?:instalar|install|index)\.php$/i.test(url.pathname) || url.pathname.includes('/.cadcraft_')) return;
  if (req.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        const fresh = await fetch(req);
        if (fresh.ok && fresh.type === 'basic') {
          const cache = await caches.open(SHELL_CACHE);
          await cache.put(new URL('./index.html', SCOPE), fresh.clone()).catch(() => {});
        }
        return fresh;
      } catch (error) {
        return (await caches.match(new URL('./index.html', SCOPE))) || Response.error();
      }
    })());
    return;
  }
  if (/\.(?:wasm|js|css|png|ico|svg|woff2?|ttf|otf|webp|json|webmanifest)$/i.test(url.pathname)) {
    event.respondWith((async () => {
      const cache = await caches.open(RUNTIME_CACHE);
      const hit = await cache.match(req);
      if (hit) return hit;
      const res = await fetch(req);
      if (res.ok && (res.type === 'basic' || res.type === 'cors')) {
        try { await cache.put(req, res.clone()); } catch (_) { /* storage quota */ }
      }
      return res;
    })());
  }
});
