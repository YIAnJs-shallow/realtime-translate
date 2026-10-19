<?php
// 实时翻译打字工具 — 主页面（PC/手机响应式）
if (PHP_SAPI !== 'cli') {
    header('Cache-Control: no-cache, must-revalidate');
}
$cfg = require __DIR__ . '/config/translate.php';
$langs = $cfg['langs'];
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>实时翻译 · 边打字边翻译</title>
<link rel="stylesheet" href="assets/css/app.css?v=1">
</head>
<body>
<header class="topbar">
  <h1>实时翻译</h1>
  <div class="langbar">
    <select id="srcLang" aria-label="源语言">
      <option value="auto">自动检测（中文）</option>
<?php foreach ($langs as $code => $name): ?>
      <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($name) ?></option>
<?php endforeach; ?>
    </select>
    <button type="button" id="swap" title="交换语言">⇄</button>
    <select id="tgtLang" aria-label="目标语言">
<?php foreach ($langs as $code => $name): ?>
      <option value="<?= htmlspecialchars($code) ?>"<?= $code === 'en' ? ' selected' : '' ?>><?= htmlspecialchars($name) ?></option>
<?php endforeach; ?>
    </select>
  </div>
</header>
<main class="panels">
  <section class="panel">
    <textarea id="src-text" placeholder="输入文字，实时显示译文…" autofocus></textarea>
    <div class="row">
      <span id="status" aria-live="polite"></span>
      <button type="button" id="clear">清空</button>
    </div>
  </section>
  <section class="panel">
    <textarea id="out-text" readonly placeholder="译文"></textarea>
    <div class="row">
      <span class="hint">单次上限约 160 个汉字</span>
      <button type="button" id="copy">复制译文</button>
    </div>
  </section>
</main>
<script src="assets/js/app.js?v=1"></script>
</body>
</html>
