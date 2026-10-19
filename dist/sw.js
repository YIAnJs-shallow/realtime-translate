/* TypeTrans Service Worker v1：离线壳（界面秒开/断网可用），翻译请求不拦截 */
var CACHE = 'typetrans-v1';
var SHELL = [
  '/',
  '/assets/css/app.css?v=1',
  '/assets/js/app.js?v=2',
  '/manifest.json',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png'
];

self.addEventListener('install', function (e) {
  e.waitUntil(
    caches.open(CACHE).then(function (c) { return c.addAll(SHELL); }).then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener('activate', function (e) {
  e.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(keys.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); }));
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function (e) {
  var req = e.request;
  if (req.method !== 'GET') { return; }                 // 翻译 API（跨域 POST/GET）不拦截
  var url = new URL(req.url);
  if (url.origin !== self.location.origin) { return; }  // 外部接口直连

  // 页面导航：网络优先，断网回退缓存壳
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req).then(function (res) {
        var copy = res.clone();
        caches.open(CACHE).then(function (c) { c.put('/', copy); });
        return res;
      }).catch(function () { return caches.match('/'); })
    );
    return;
  }

  // 静态资源：缓存优先，未命中回网络并入库
  e.respondWith(
    caches.match(req).then(function (hit) {
      if (hit) { return hit; }
      return fetch(req).then(function (res) {
        if (res && res.status === 200) {
          var copy = res.clone();
          caches.open(CACHE).then(function (c) { c.put(req, copy); });
        }
        return res;
      });
    })
  );
});
