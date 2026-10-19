<?php
// 翻译 API：POST {text, source, target} → JSON {ok, text, source, target, provider | error}
if (PHP_SAPI !== 'cli') {
    header('Cache-Control: no-cache, must-revalidate');
    header('Content-Type: application/json; charset=utf-8');
}

require __DIR__ . '/../lib/translate.php';

function api_out(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    api_out(['ok' => false, 'error' => '方法不允许'], 405);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
if (!translate_rate_limit($ip)) {
    api_out(['ok' => false, 'error' => '请求太频繁，请稍候'], 429);
}

$text = (string)($_POST['text'] ?? '');
$source = (string)($_POST['source'] ?? 'auto');
$target = (string)($_POST['target'] ?? '');

[$ok, $result] = translate_text($text, $source, $target);
if ($ok) {
    api_out(['ok' => true] + $result);
}
api_out(['ok' => false, 'error' => $result], 400);
