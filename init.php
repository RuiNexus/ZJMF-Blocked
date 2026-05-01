<?php

function getClientIP()
{
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    if (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function getIPRegion($ip, $xdbPath)
{
    $searcherFile = __DIR__ . '/ip2region/binding/php/xdb/Searcher.class.php';
    if (!file_exists($searcherFile) || !file_exists($xdbPath)) {
        return null;
    }
    require_once $searcherFile;

    try {
        $handle = @fopen($xdbPath, 'r');
        if ($handle === false) {
            return null;
        }

        $header = \ip2region\xdb\Util::loadHeader($handle);
        $version = $header ? \ip2region\xdb\Util::versionFromHeader($header) : null;
        $cBuff = \ip2region\xdb\Util::loadContent($handle);
        fclose($handle);

        if ($version === null || $cBuff === null) {
            return null;
        }

        $searcher = \ip2region\xdb\Searcher::newWithBuffer($version, $cBuff);
        $region = $searcher->search($ip);
        $searcher->close();

        return $region;
    } catch (\Throwable $e) {
        return null;
    }
}

function isIPv6($ip)
{
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
}

$zjmfConfig = @include __DIR__ . '/config.php';

// HTTP 调试模式（需 config.php 中 debug_enabled 为 true 才生效）
if (is_array($zjmfConfig) && !empty($zjmfConfig['debug_enabled'])
    && isset($_GET['debug_ip']) && $_GET['debug_ip'] !== '') {
    $debugSearcherFile = __DIR__ . '/ip2region/binding/php/xdb/Searcher.class.php';
    if (file_exists($debugSearcherFile)) {
        require_once $debugSearcherFile;
        $debugXdbDir = __DIR__ . '/ip2region/data';
        $debugIp = $_GET['debug_ip'];
        echo "IP: {$debugIp}\n\n";
        $debugFiles = [
            'ip2region_v4.xdb' => 'IPv4',
            'ip2region_v6.xdb' => 'IPv6',
        ];
        foreach ($debugFiles as $f => $label) {
            $p = $debugXdbDir . '/' . $f;
            if (!file_exists($p)) { echo "[{$label}] 文件不存在\n"; continue; }
            try {
                $h = @fopen($p, 'r');
                if (!$h) { echo "[{$label}] 无法打开\n"; continue; }
                $header = \ip2region\xdb\Util::loadHeader($h);
                $ver = $header ? \ip2region\xdb\Util::versionFromHeader($header) : null;
                $buf = \ip2region\xdb\Util::loadContent($h);
                fclose($h);
                if (!$ver || !$buf) { echo "[{$label}] 加载失败\n"; continue; }
                $s = \ip2region\xdb\Searcher::newWithBuffer($ver, $buf);
                $region = $s->search($debugIp);
                $s->close();
                $parts = explode('|', $region);
                echo "[{$label}] {$region}\n";
                echo "  → 国家字段: [{$parts[0]}]\n";
            } catch (\Throwable $e) {
                echo "[{$label}] {$e->getMessage()}\n";
            }
        }
    } else {
        echo "Searcher.class.php 不存在\n";
    }
    exit;
}

if (!is_array($zjmfConfig)) {
    return;
}

$mode = $zjmfConfig['mode'] ?? 'block';
$regions = $zjmfConfig['regions'] ?? [];
if (empty($regions)) {
    return;
}

$blockMessage = $zjmfConfig['block_message'] ?? 'You have been blocked from accessing';

$xdbDir = __DIR__ . '/ip2region/data';
$ip = getClientIP();

if (isIPv6($ip)) {
    $region = getIPRegion($ip, $xdbDir . '/ip2region_v6.xdb');
    if ($region === null || $region === '') {
        $region = getIPRegion($ip, $xdbDir . '/ip2region_v4.xdb');
    }
} else {
    $region = getIPRegion($ip, $xdbDir . '/ip2region_v4.xdb');
    if ($region === null || $region === '') {
        $region = getIPRegion($ip, $xdbDir . '/ip2region_v6.xdb');
    }
}

if ($region !== null && $region !== '') {
    $parts = explode('|', $region);
    $country = trim($parts[0] ?? '');
    if ($country === '' || $country === '0') {
        return;
    }

    $matched = false;
    foreach ($regions as $r) {
        if (strcasecmp(trim($r), $country) === 0) {
            $matched = true;
            break;
        }
    }

    if (($mode === 'allow' && !$matched) || ($mode === 'block' && $matched)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die($blockMessage);
    }
}
