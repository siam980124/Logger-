<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$eiin = isset($_GET['eiin']) ? trim($_GET['eiin']) : '130459';

$portal_url = "http://emis.gov.bd/EMIS/portal";
$api_url    = "http://emis.gov.bd/emis/Portal/GetTeacherDetails";
$ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";

// STEP 1: Get fresh token
$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, $portal_url);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_HEADER, true);
curl_setopt($ch1, CURLOPT_USERAGENT, $ua);
curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch1, CURLOPT_TIMEOUT, 20);
curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);

$res1      = curl_exec($ch1);
$step1_err = curl_error($ch1);
$step1_code= curl_getinfo($ch1, CURLINFO_HTTP_CODE);
$header_sz = curl_getinfo($ch1, CURLINFO_HEADER_SIZE);
curl_close($ch1);

$raw_headers = substr($res1, 0, $header_sz);
$body        = substr($res1, $header_sz);

// Extract cookies
$cookies = [];
preg_match_all('/Set-Cookie:\s*([^;\r\n]+)/i', $raw_headers, $m);
foreach ($m[1] as $c) { $cookies[] = trim($c); }
$cookie_str = implode('; ', $cookies);

// Extract CSRF
$csrf = '';
if (preg_match('/name=["\']__RequestVerificationToken["\'][^>]+value=["\'](.*?)["\']/i', $body, $mx)) {
    $csrf = $mx[1];
} elseif (preg_match('/CSRF[_-]TOKEN["\s:=\']+([A-Za-z0-9_\-\.]+)/i', $body, $mx)) {
    $csrf = $mx[1];
} elseif (preg_match('/content=["\'](.*?)["\']/i', implode("\n", array_filter(explode("\n", $body), fn($l) => stripos($l,'csrf')!==false)), $mx)) {
    $csrf = $mx[1];
}

// STEP 2: POST to EMIS
$headers2 = [
    "User-Agent: $ua",
    "Accept: application/json, text/javascript, */*; q=0.01",
    "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
    "X-Requested-With: XMLHttpRequest",
    "Origin: http://emis.gov.bd",
    "Referer: http://emis.gov.bd/EMIS/portal",
    "Accept-Language: en-US,en;q=0.9",
