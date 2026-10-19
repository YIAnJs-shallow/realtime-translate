<?php
// 集成测试：自带 php -S 起本地服务，curl 断言核心链路。
// 运行：php tests/integration.php  — 断言基线 15，只增不减。

error_reporting(E_ALL & ~E_DEPRECATED);
if (!extension_loaded('curl')) {
    fwrite(STDERR, "需要 curl 扩展\n");
    exit(1);
}

$root = realpath(__DIR__ . '/..');
$host = '127.0.0.1';
// 随机端口：避免与残留的旧服务器撞车（Windows 允许重复绑定同端口，请求会随机路由到旧进程）
$port = 8100 + random_int(0, 399);
$base = "http://{$host}:{$port}";

// 1. 起本地服务
$cmd = escapeshellarg(PHP_BINARY) . " -S {$host}:{$port} -t " . escapeshellarg($root);
$proc = proc_open($cmd, [
    0 => ['pipe', 'r'],
    1 => ['file', DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null', 'w'],
    2 => ['file', DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null', 'w'],
], $pipes);
if (!is_resource($proc)) {
    fwrite(STDERR, "无法启动内置服务器\n");
    exit(1);
}

// 2. 等服务就绪（最多 5 秒）
$ready = false;
for ($i = 0; $i < 50; $i++) {
    usleep(100000);
    [$st] = http('GET', '/');
    if ($st > 0) { $ready = true; break; }
}
if (!$ready) {
    fwrite(STDERR, "内置服务器启动失败\n");
    proc_terminate($proc);
    exit(1);
}

// ---- 断言设施 ----
$count = 0;
$failed = 0;
function check(string $name, bool $cond, string $detail = ''): void
{
    global $count, $failed;
    $count++;
    if ($cond) {
        echo "PASS #{$count} {$name}\n";
    } else {
        $failed++;
        echo "FAIL #{$count} {$name}" . ($detail !== '' ? " | {$detail}" : '') . "\n";
    }
}

function http(string $method, string $path, array $post = null): array
{
    global $base;
    $ch = curl_init($base . $path);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HEADER => true,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($post !== null) {
        $opts[CURLOPT_POSTFIELDS] = http_build_query($post);
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hlen = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $ctype = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    $headers = $hlen > 0 ? substr((string)$raw, 0, $hlen) : '';
    $body = $hlen > 0 ? substr((string)$raw, $hlen) : (string)$raw;
    return [$status, $body, $headers, $ctype];
}

// ---- 页面层 ----
[$st, $html, $hdr] = http('GET', '/');
check('首页返回 200', $st === 200);
check('首页含输入框 src-text', strpos($html, 'src-text') !== false);
check('首页含译文框 out-text', strpos($html, 'out-text') !== false);
check('首页含源语言选择器 srcLang', strpos($html, 'srcLang') !== false);
check('首页含目标语言选择器 tgtLang', strpos($html, 'tgtLang') !== false);
check('首页响应带 no-cache 头', stripos($hdr, 'no-cache') !== false);

// ---- API 层：合法调用（真实引擎，1 次）----
[$st, $body] = http('POST', '/api/translate.php', [
    'text' => '你好世界', 'source' => 'zh-CN', 'target' => 'en',
]);
$ok = json_decode($body, true);
check('合法翻译返回 200 且 ok=true', $st === 200 && is_array($ok) && ($ok['ok'] ?? false) === true,
    "HTTP {$st} body=" . substr($body, 0, 200));
check('译文非空字符串', isset($ok['text']) && is_string($ok['text']) && $ok['text'] !== '',
    "text=" . var_export($ok['text'] ?? null, true));
check('响应为 JSON 类型', strpos($body, '"ok"') !== false);

// ---- API 层：非法输入（不触网，走白名单/长度校验）----
[$st, $body] = http('POST', '/api/translate.php', ['text' => '  ', 'source' => 'auto', 'target' => 'en']);
$ok = json_decode($body, true);
check('空输入被拒 ok=false', $st === 400 && is_array($ok) && ($ok['ok'] ?? true) === false);

[$st, $body] = http('POST', '/api/translate.php', ['text' => str_repeat('长', 500), 'source' => 'auto', 'target' => 'en']);
$ok = json_decode($body, true);
check('超长输入被拒 ok=false', $st === 400 && is_array($ok) && ($ok['ok'] ?? true) === false);

[$st, $body] = http('POST', '/api/translate.php', ['text' => '你好', 'source' => 'auto', 'target' => 'xx']);
$ok = json_decode($body, true);
check('非法目标语言被拒', $st === 400 && is_array($ok) && ($ok['ok'] ?? true) === false);

[$st, $body] = http('POST', '/api/translate.php', ['text' => '你好', 'source' => 'xx', 'target' => 'en']);
$ok = json_decode($body, true);
check('非法源语言被拒', $st === 400 && is_array($ok) && ($ok['ok'] ?? true) === false);

[$st, $body] = http('POST', '/api/translate.php', ['text' => '你好', 'source' => 'auto', 'target' => 'auto']);
$ok = json_decode($body, true);
check('目标语言为 auto 被拒', $st === 400 && is_array($ok) && ($ok['ok'] ?? true) === false);

[$st, $body] = http('GET', '/api/translate.php');
check('GET 方法被拒 405', $st === 405);

// ---- 限流：连续 12 次快速请求（空文本不触网），窗口内至少一次 429 ----
$saw429 = false;
for ($i = 0; $i < 12; $i++) {
    [$st] = http('POST', '/api/translate.php', ['text' => '', 'source' => 'auto', 'target' => 'en']);
    if ($st === 429) { $saw429 = true; break; }
}
check('限流生效出现 429', $saw429);

// ---- 收尾 ----
// Windows 下 proc_terminate 杀不死 php -S（残留进程会占用端口、污染下次测试），必须强杀进程树
proc_terminate($proc);
$pid = proc_get_status($proc)['pid'] ?? 0;
proc_close($proc);
if ($pid > 0 && DIRECTORY_SEPARATOR === '\\') {
    @exec('taskkill /F /T /PID ' . $pid);
}

echo "\n断言 {$count} 项，失败 {$failed} 项（基线 15，只增不减）\n";
exit($failed > 0 ? 1 : 0);
