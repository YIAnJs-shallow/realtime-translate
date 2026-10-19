/* 实时翻译 — 前端逻辑 v1：500ms 防抖、请求竞态保护、复制/清空/交换 */
(function () {
  'use strict';

  var $src = document.getElementById('src-text');
  var $out = document.getElementById('out-text');
  var $status = document.getElementById('status');
  var $srcLang = document.getElementById('srcLang');
  var $tgtLang = document.getElementById('tgtLang');
  var timer = null;
  var ctrl = null;
  var seq = 0; // 竞态保护：只渲染最后一次请求的结果

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
    if (ctrl) { ctrl.abort(); }
    ctrl = ('AbortController' in window) ? new AbortController() : null;
    var my = ++seq;
    setStatus('翻译中…');
    var body = new URLSearchParams();
    body.append('text', text);
    body.append('source', $srcLang.value);
    body.append('target', $tgtLang.value);
    fetch('api/translate.php', {
      method: 'POST',
      body: body,
      signal: ctrl ? ctrl.signal : undefined
    }).then(function (r) { return r.json(); }).then(function (d) {
      if (my !== seq) { return; }
      if (d.ok) {
        $out.value = d.text;
        setStatus('');
      } else {
        setStatus(d.error || '翻译失败', true);
      }
    }).catch(function (e) {
      if (my !== seq || (e && e.name === 'AbortError')) { return; }
      setStatus('网络错误，请检查连接', true);
    });
  }

  // 输入防抖 500ms
  $src.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(doTranslate, 500);
  });

  // 切换语言立即重译
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
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        btn.textContent = '已复制';
        setTimeout(function () { btn.textContent = '复制译文'; }, 1200);
      });
    } else {
      $out.select();
      document.execCommand('copy');
      btn.textContent = '已复制';
      setTimeout(function () { btn.textContent = '复制译文'; }, 1200);
    }
  });

  // 交换语言与文本
  document.getElementById('swap').addEventListener('click', function () {
    var s = $srcLang.value, t = $tgtLang.value;
    if (s === 'auto') { return; } // auto 无法作为目标语言
    $srcLang.value = t;
    $tgtLang.value = s;
    if ($out.value) {
      $src.value = $out.value;
      $out.value = '';
      doTranslate();
    }
  });
})();
