/* Cache de interface apenas. API, projetos e credenciais NUNCA são cacheados. */
const CACHE='cadcraft-didatico-v1.0.3';
const STATIC=['./','./index.php','./assets/style.css?v=1.0.3','./assets/app.js?v=1.0.3','./assets/icon-192.png','./assets/icon-512.png','./assets/studycad-logo.png','./manifest.webmanifest'];
self.addEventListener('install',e=>e.waitUntil(caches.open(CACHE).then(c=>c.addAll(STATIC)).then(()=>self.skipWaiting())));
self.addEventListener('activate',e=>e.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k.startsWith('cadcraft-didatico-')&&k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',e=>{const req=e.request;if(req.method!=='GET'||new URL(req.url).pathname.endsWith('/api.php'))return;const url=new URL(req.url);if(url.origin!==self.location.origin)return;if(req.mode==='navigate'){e.respondWith(fetch(req).catch(()=>caches.match('./index.php')));return;}e.respondWith(caches.match(req).then(hit=>hit||fetch(req).then(res=>{if(res.ok&&res.type==='basic'){const copy=res.clone();caches.open(CACHE).then(c=>c.put(req,copy));}return res;})));});
