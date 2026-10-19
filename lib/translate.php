<?php
// 翻译引擎适配层：统一入口 translate_text()。当前 provider=mymemory，后续切换引擎只改本层。

function translate_load_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/translate.php';
    }
    return $cfg;
}

// 简易滑动窗口限流：按 IP 记录在系统临时目录，window 秒内最多 max 次
function translate_rate_limit(string $ip): bool
{
    $cfg = translate_load_config();
    $dir = sys_get_temp_dir() . '/myproject_rl';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $file = $dir . '/' . md5($ip) . '.json';
    $now = time();
    $hits = [];
    if (is_file($file)) {
        $hits = json_decode((string)file_get_contents($file), true) ?: [];
    }
    $hits = array_values(array_filter($hits, function ($t) use ($now, $cfg) {
        return $t > $now - $cfg['rate']['window'];
    }));
    if (count($hits) >= $cfg['rate']['max']) {
        return false;
    }
    $hits[] = $now;
    file_put_contents($file, json_encode($hits), LOCK_EX);
    return true;
}

// HTTP GET JSON，出错只返回面向用户的一句话，不暴露技术细节
function translate_http_json(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($errno || !is_string($body)) {
        return [false, '翻译服务暂时不可用，请稍后重试'];
    }
    $data = json_decode($body, true);
    if (!is_array($data)) {
        return [false, '翻译服务响应异常，请稍后重试'];
    }
    return [true, $data];
}

/**
 * 翻译入口。
 * @return array [bool ok, array|error string] ok=true 时第二个元素为数组结果
 */
function translate_text(string $text, string $source, string $target): array
{
    $cfg = translate_load_config();
    $text = trim($text);
    if ($text === '') {
        return [false, '请输入要翻译的内容'];
    }
    if (strlen($text) > $cfg['max_bytes']) {
        return [false, '内容过长：当前引擎单次上限约 ' . $cfg['max_bytes'] . ' 字节（约 160 个汉字）'];
    }
    if ($source !== 'auto' && !isset($cfg['langs'][$source])) {
        return [false, '不支持该源语言'];
    }
    if ($target === 'auto' || !isset($cfg['langs'][$target])) {
        return [false, '不支持该目标语言'];
    }
    if ($source === $target) {
        return [false, '源语言与目标语言相同'];
    }
    // MVP：引擎不支持自动检测，auto 一律按中文处理（主场景即中→外）
    if ($source === 'auto') {
        $source = 'zh-CN';
    }

    if ($cfg['provider'] === 'mymemory') {
        $p = $cfg['providers']['mymemory'];
        $query = [
            'q'        => $text,
            'langpair' => $source . '|' . $target,
        ];
        if (!empty($p['email'])) {
            $query['de'] = $p['email'];
        }
        $url = $p['api'] . '?' . http_build_query($query);
        [$ok, $data] = translate_http_json($url);
        if (!$ok) {
            return [false, $data];
        }
        $status = (int)($data['responseStatus'] ?? 0);
        $out = $data['responseData']['translatedText'] ?? '';
        if ($status === 200 && is_string($out) && $out !== '') {
            return [true, [
                'text'     => $out,
                'source'   => $source,
                'target'   => $target,
                'provider' => 'mymemory',
            ]];
        }
        if (!empty($data['quotaFinished'])) {
            return [false, '今日免费额度已用完，明天再试或更换引擎'];
        }
        return [false, '翻译失败，请稍后重试'];
    }

    return [false, '未配置的翻译引擎'];
}
