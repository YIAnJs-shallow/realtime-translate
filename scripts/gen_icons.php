<?php
// 应用图标生成器：蓝底渐变 + 白色"译"字。输出 192/512/maskable-512 三张 PNG。
// 运行：php scripts/gen_icons.php

$fonts = ['C:\\Windows\\Fonts\\msyhbd.ttc', 'C:\\Windows\\Fonts\\msyh.ttc'];
$font = null;
foreach ($fonts as $f) {
    if (is_file($f)) { $font = $f; break; }
}
if ($font === null) {
    fwrite(STDERR, "找不到微软雅黑字体\n");
    exit(1);
}

$outDir = __DIR__ . '/../dist/assets/icons';
if (!is_dir($outDir)) { mkdir($outDir, 0777, true); }

/** 生成一张图标：$glyphRatio 字号占边长比例（maskable 用小值留安全区） */
function make_icon(int $size, string $font, string $path, float $glyphRatio): void
{
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    // 垂直渐变 #2563eb → #1e40af
    $top = [0x25, 0x63, 0xeb];
    $bot = [0x1e, 0x40, 0xaf];
    for ($y = 0; $y < $size; $y++) {
        $t = $y / max(1, $size - 1);
        $c = imagecolorallocate(
            $img,
            (int)round($top[0] + ($bot[0] - $top[0]) * $t),
            (int)round($top[1] + ($bot[1] - $top[1]) * $t),
            (int)round($top[2] + ($bot[2] - $top[2]) * $t)
        );
        imageline($img, 0, $y, $size - 1, $y, $c);
    }

    // 白色"译"字居中
    $white = imagecolorallocate($img, 255, 255, 255);
    $glyph = '译';
    $boxSize = (int)round($size * $glyphRatio);
    $bbox = imagettfbbox($boxSize, 0, $font, $glyph);
    $tw = $bbox[2] - $bbox[0];
    $th = $bbox[1] - $bbox[7];
    $x = (int)round(($size - $tw) / 2 - $bbox[0]);
    $y = (int)round(($size - $th) / 2 - $bbox[7]);
    imagettftext($img, $boxSize, 0, $x, $y, $white, $font, $glyph);

    imagepng($img, $path);
    imagedestroy($img);
    echo "生成 {$path}\n";
}

make_icon(192, $font, $outDir . '/icon-192.png', 0.62);
make_icon(512, $font, $outDir . '/icon-512.png', 0.62);
make_icon(512, $font, $outDir . '/maskable-512.png', 0.50); // maskable 安全区
