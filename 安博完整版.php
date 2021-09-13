<?php
error_reporting(0);
header('Content-Type: text/json;charset=UTF-8');

$id = $_GET["id"];
$key = "Tv@Pad20210><iXm";
$iv = random(16);
$time = time();
$serial = '4414122343';
$mac = '0027522300BE';
$sign = openssl_encrypt('{"app_laguage":2,"brand":"unblocktech","cpu_api":"armeabi-v7a","cpu_api2":"armeabi","device_flag":"4045","mac":"' . $mac . '","model":"S900PROBT","serial":"' . $serial . '","time":' . $time . ',"token":""}', "AES-128-CBC", $key, 0, $iv);
$header = array("device_info: " . json_encode(array("iv"=>$iv, "sign"=>$sign)), 'User-Agent: okhttp/3.12.0');

$geturl = 'http://www.twtvcdn.com/ubpad/geturi.php';
$post = json_encode(array("iv"=>$iv, "sign"=>openssl_encrypt('{"id":"' . $id . '","re":0}', "AES-128-CBC", $key, 0, $iv)));
$json = nget($geturl, $post, $header);
$json = json_decode($json, true);
$data = openssl_decrypt($json["sign"], "AES-128-CBC", $key, 0, $json['iv']);
$data = json_decode($data,true);
$url = $data["return_uri"];

//获取token
$gettoken = 'http://www.twtvcdn.com/ubpad/gettoken.php';
$json = nget($gettoken, NULL, $header);
$json = json_decode($json, true);
$playtoken = openssl_decrypt($json["sign"], "AES-128-CBC", $key, 0, $json['iv']);
$playtoken = json_decode($playtoken, true);
$token = $playtoken['return_playtoken'];
//获取token结束

$sign = openssl_encrypt('{"brand":"unblocktech","channel_id":"' . $id . '","mac":"' . $mac . '","model":"S900PROBT"}', "AES-128-CBC", $key, 0, $iv);
$headers[] = 'User-Agent: UBlive_pad/1.9.1 (Linux;Android 8.1.0)';
$headers[] = 'header_token: {"iv":"' .  $iv . '","sign":"' . $sign . '"}';
$headers[] = "playtoken: $token";
if (!isset($_GET['index'])) {
    $cache = "cache";
    if(!is_dir($cache)){
        mkdir(iconv("UTF-8", "GBK", $cache), 0777, true);
    }
    if(!is_dir($cache . '/' . $id)){
        mkdir(iconv("UTF-8", "GBK", $cache . '/' . $id), 0777, true);
    }
    $file = "cache/" . $id . "/" . $id . ".m3u8";
    if ((file_exists($file)) && (filesize($file))) {
        $diff = time() - filemtime($file);
        if ($diff < 5) {
            echom3u8(file_get_contents($file));
        }
    }
    $m3u8 = nget($url, NULL, $headers);
    //echo $m3u8;
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
    if ((file_exists($file)) && (filesize($file))) {
        $diff = time() - filemtime($file);
        if ($diff < 8) {
            echots(file_get_contents($file));
        }
    }

    $before = explode("index", $url)[0];
    $url = $before . $index;
    $ts = nget($url, NULL, $headers);
    if (strlen($ts) > 500) {
        file_put_contents($file, $ts);
        echots($ts);
    }
}

function nget($url, $post = NULL, $headers = NULL, $getInfo = NULL) {
	if ($headers == NULL) {
		$headers = array('User-Agent: Mozilla/5.0 (Linux; U; Android 4.3; en-us; SM-N900T Build/JSS15J)');
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	if (!empty($post)) {
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if (empty($getInfo)) {
		$output = curl_exec($ch);
	} else {
		curl_exec($ch);
		$output = curl_getinfo($ch,CURLINFO_EFFECTIVE_URL);
	}
	curl_close($ch);
	return $output;
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

function random($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    for ($randomString = '',$i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, 35)];
    }
    return $randomString;
}