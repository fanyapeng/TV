<?php
error_reporting(0);
header('Content-Type: text/json;charset=UTF-8');

$id = $_GET['id'];
$list = $_GET['list'];
$api = 'https://v1.37o.xyz/daili/ub.php';
if (isset($list)) {
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    $url = dirname($http_type . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    $list = nget($api . '?list');
    $list = str_replace('UBlive', $url . '/' . basename(__FILE__), $list);
    exit($list);
} elseif (isset($id) && !empty($id) && is_numeric($id)) {
    $cache = "cache";
    if(!is_dir($cache)){
        mkdir(iconv("UTF-8", "GBK", $cache), 0777, true);
    }
    if(!is_dir($cache . '/' . $id)){
        mkdir(iconv("UTF-8", "GBK", $cache . '/' . $id), 0777, true);
    }
    $file = "cache/" . $id . "/" . $id . "_url.txt";
    if (file_exists($file) && filesize($file) && time() - filemtime($file) < 6 * 3600) {
            $url = file_get_contents($file);
    } else {
        $json = nget($api . '?id=' . $id);
        $json = json_decode($json, true);
        file_put_contents($file, $json['uri']);
        $url = $json['uri'];
    }
    
    $file = "cache/ubtv_token.txt";
    if (file_exists($file) && filesize($file) && time() - filemtime($file) < 2900) {
            $token = file_get_contents($file);
    } else {
        $json = nget($api . '?id=' . $id);
        $json = json_decode($json, true);
        file_put_contents($file, $json['playtoken']);
        $token = $json['playtoken'];
    }

    $headers[] = 'User-Agent: UBlive_pad/1.9.1 (Linux;Android 8.1.0)';
    $headers[] = "playtoken: $token";
    if (!isset($_GET['index'])) {
        $file = "cache/" . $id . "/" . $id . ".m3u8";
        if (file_exists($file) && filesize($file) && time() - filemtime($file) < 5) {
            echom3u8(file_get_contents($file));
        }
        $m3u8 = nget($url, $headers);
        if (strpos($m3u8, "EXTM3U")) {
            $m3u8s = explode("\n", $m3u8);
            $m3u8 = '';
            foreach ($m3u8s as $v) {
                $v = str_replace("\r", '', $v);
                if (strpos($v, ".ts") > 0) {
                    $m3u8 .= basename(__FILE__) . "?id=$id&index=" . $v . "\n";
                } elseif ($v != '') {
                    $m3u8 .= $v . "\n";
                }
            }
            file_put_contents($file, $m3u8);
            echom3u8($m3u8);
        }
    } else {
        $index = $_GET['index'];
        $file = "cache/" . $id . "/UBlive_" . $id . "_" . $index;
        if (file_exists($file) && filesize($file) && time() - filemtime($file) < 100) {
            echots(file_get_contents($file));
        }

        $before = explode("index", $url)[0];
        $url = $before . $index;
        $ts = nget($url, $headers);
        if (strlen($ts) > 500) {
            file_put_contents($file, $ts);
            echots($ts);
        }
    }
} else {
    exit('传递参数不正确。');
}

function nget($url, $headers = null) {
    if ($headers == null) {
        $headers = array('User-Agent: ZXAPI/1.0.0');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function echom3u8($current) {
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header('Content-Type: application/vnd.apple.mpegurl'); 
    header("Pragma: no-cache");
    header('Content-Transfer-Encoding: chunked'); 
    header('Content-Length: ' . strlen($current)); 
    echo $current;
    exit(0);
}

function echots($ts) {
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header('Content-Type: video/mp2t');
    header('Content-Length: ' . strlen($ts));
    header('Connection: keep-alive');
    header('Accept-Ranges: bytes');
    echo $ts;
    exit(0);
}

?>