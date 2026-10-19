<?php
// 翻译服务公共配置。真实密钥一律放 Web 根目录外私有目录，禁止写进本文件、禁止入库。
// 私有目录约定：E:\e2\private\myproject\secrets.php（模板见 config/secrets.example.php）

$cfg = [
    // 当前引擎。MVP 用 MyMemory 免 Key HTTP API（2026-10-19 实测国内可达）。
    'provider' => 'mymemory',
    'providers' => [
        'mymemory' => [
            'api' => 'https://api.mymemory.translated.net/get',
            // 注册邮箱可提升每日免费额度；真实值只放私有 secrets，此处保持 null
            'email' => null,
        ],
    ],
    // MVP 引擎单次请求字节上限（MyMemory 匿名单查约 500 字节，留余量）
    'max_bytes' => 480,
    // 简易限流：window 秒内最多 max 次（按 IP）
    'rate' => ['window' => 10, 'max' => 10],
    // 支持的语言白名单（UI 与 API 校验共用）
    'langs' => [
        'zh-CN' => '简体中文',
        'zh-TW' => '繁體中文',
        'en'    => '英语',
        'ja'    => '日语',
        'ko'    => '韩语',
        'fr'    => '法语',
        'de'    => '德语',
        'es'    => '西班牙语',
        'ru'    => '俄语',
        'pt'    => '葡萄牙语',
        'it'    => '意大利语',
        'th'    => '泰语',
        'vi'    => '越南语',
        'ar'    => '阿拉伯语',
        'hi'    => '印地语',
        'id'    => '印尼语',
    ],
];

// 合并 Web 根目录外的私有密钥（存在才加载；加载机制不动真实值）
$__secrets = 'E:/e2/private/myproject/secrets.php';
if (is_file($__secrets)) {
    $__s = require $__secrets;
    if (is_array($__s) && isset($__s['translate']) && is_array($__s['translate'])) {
        $cfg = array_replace_recursive($cfg, $__s['translate']);
    }
}
unset($__secrets, $__s);

return $cfg;
