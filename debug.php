<?php

if (PHP_SAPI !== 'cli') {
    exit("请通过命令行使用此脚本：php debug.php 8.8.8.8\n");
}

require_once __DIR__ . '/init.php';

$ip = $argv[1] ?? null;
if (!$ip) {
    echo "用法：\n";
    echo "  CLI:   php debug.php 8.8.8.8\n";
    echo "\n";
    echo "HTTP 调试请用：\n";
    echo "  访问网站任意页面加 ?debug_ip=8.8.8.8（需 config.php 中 debug_enabled 为 true）\n";
    exit(1);
}

$searcherFile = __DIR__ . '/ip2region/binding/php/xdb/Searcher.class.php';
require_once $searcherFile;

$xdbDir = __DIR__ . '/ip2region/data';

function testRegion($ip, $xdbPath, $label) {
    if (!file_exists($xdbPath)) {
        echo "  {$label}: 文件不存在\n";
        return;
    }
    try {
        $handle = @fopen($xdbPath, 'r');
        if (!$handle) { echo "  {$label}: 无法打开\n"; return; }
        $header = \ip2region\xdb\Util::loadHeader($handle);
        $version = $header ? \ip2region\xdb\Util::versionFromHeader($header) : null;
        $cBuff = \ip2region\xdb\Util::loadContent($handle);
        fclose($handle);
        if (!$version || !$cBuff) { echo "  {$label}: 加载失败\n"; return; }
        $searcher = \ip2region\xdb\Searcher::newWithBuffer($version, $cBuff);
        $region = $searcher->search($ip);
        $searcher->close();
        $parts = explode('|', $region);
        echo "  {$label}: {$region}\n";
        echo "  国家字段(raw): [{$parts[0]}]\n";
    } catch (\Throwable $e) {
        echo "  {$label}: {$e->getMessage()}\n";
    }
}

echo "IP: {$ip}\n\n";

if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    echo "检测到 IPv6\n";
    testRegion($ip, $xdbDir . '/ip2region_v6.xdb', 'ip2region_v6');
    testRegion($ip, $xdbDir . '/ip2region_v4.xdb', 'ip2region_v4');
} else {
    echo "检测到 IPv4\n";
    testRegion($ip, $xdbDir . '/ip2region_v4.xdb', 'ip2region_v4');
    testRegion($ip, $xdbDir . '/ip2region_v6.xdb', 'ip2region_v6');
}
