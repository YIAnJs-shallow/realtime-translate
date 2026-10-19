/* 实时翻译 — 静态版前端逻辑 v1：浏览器直调 MyMemory（免 Key、支持 CORS）
 * 与 PHP 版 (assets/js/app.js) 保持同一交互：500ms 防抖、竞态保护、复制/清空/交换。
 * 差异：校验在前端完成（语言白名单/长度/空输入），无服务端限流。
 */
(function () {
  'use strict';

  var API = 'https://api.mymemory.translated.net/get';
  var LANGS = ['zh-CN', 'zh-TW', 'en', 'ja', 'ko', 'fr', 'de', 'es', 'ru', 'pt', 'it', 'th', 'vi', 'ar', 'hi', 'id'];
  var MAX_BYTES = 480; // 与后端口径一致：引擎单次约 500 字节，留余量

  var $src = document.getElementById('src-text');
  var $out = document.getElementById('out-text');
  var $status = document.getElementById('status');
  var $srcLang = document.getElementById('srcLang');
  var $tgtLang = document.getElementById('tgtLang');
  var timer = null;
  var ctrl = null;
  var seq = 0;

  function setStatus(msg, isError) {
    $status.textContent = msg || '';
    $status.classList.toggle('is-error', !!isError);
  }

  function doTranslate() {
    var text = $src.value.trim();
    if (!text) {
      $out.value = '';
      setStatus('');
      return;
    }
    // 前端校验（静态版无后端，这里把关）
    var source = $srcLang.value;
    var target = $tgtLang.value;
    if (target === 'auto' || LANGS.indexOf(target) === -1) {
      setStatus('不支持该目标语言', true);
      return;
    }
    if (source !== 'auto' && LANGS.indexOf(source) === -1) {
      setStatus('不支持该源语言', true);
      return;
    }
    if (source === target) {
      setStatus('源语言与目标语言相同', true);
      return;
    }
    if (text.length > MAX_BYTES) {
      setStatus('内容过长：单次上限约 160 个汉字', true);
      return;
    }

    if (ctrl) { ctrl.abort(); }
    ctrl = ('AbortController' in window) ? new AbortController() : null;
    var my = ++seq;
    setStatus('翻译中…');

    // auto 一律按中文处理（引擎不支持自动检测），与后端口径一致
    var langpair = (source === 'auto' ? 'zh-CN' : source) + '|' + target;
    var url = API + '?' + new URLSearchParams({ q: text, langpair: langpair }).toString();

    fetch(url, { signal: ctrl ? ctrl.signal : undefined })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (my !== seq) { return; }
        var out = d && d.responseData && d.responseData.translatedText;
        if (d && (d.responseStatus === 200 || d.responseStatus === '200') && typeof out === 'string' && out !== '') {
          $out.value = out;
          setStatus('');
        } else if (d && d.quotaFinished) {
          setStatus('今日免费额度已用完，明天再试', true);
        } else {
          setStatus('翻译失败，请稍后重试', true);
        }
      })
      .catch(function (e) {
        if (my !== seq || (e && e.name === 'AbortError')) { return; }
        setStatus('网络错误，请检查连接', true);
      });
  }

  $src.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(doTranslate, 500);
  });

  $srcLang.addEventListener('change', doTranslate);
  $tgtLang.addEventListener('change', doTranslate);

  document.getElementById('clear').addEventListener('click', function () {
    $src.value = '';
    $out.value = '';
    setStatus('');
    $src.focus();
  });

  document.getElementById('copy').addEventListener('click', function () {
    var text = $out.value;
    if (!text) { return; }
    var btn = this;
    function done() {
      btn.textContent = '已复制';
      setTimeout(function () { btn.textContent = '复制译文'; }, 1200);
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done);
    } else {
      $out.select();
      document.execCommand('copy');
      done();
    }
  });

  document.getElementById('swap').addEventListener('click', function () {
    var s = $srcLang.value, t = $tgtLang.value;
    if (s === 'auto') { return; }
    $srcLang.value = t;
    $tgtLang.value = s;
    if ($out.value) {
      $src.value = $out.value;
      $out.value = '';
      doTranslate();
    }
  });
})();
