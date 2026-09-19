<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!isset($_GET['eiin']) || empty(trim($_GET['eiin']))) {
    echo json_encode(["error" => "EIIN নম্বর প্রয়োজন"]);
    exit;
}

$eiin = trim($_GET['eiin']);
if (!ctype_digit($eiin)) {
    echo json_encode(["error" => "EIIN অবশ্যই সংখ্যা হতে হবে"]);
    exit;
}

$portal_url = "http://emis.gov.bd/EMIS/portal";
$api_url    = "http://emis.gov.bd/emis/Portal/GetTeacherDetails";

$ua = "Mozilla/5.0 (Linux; Android 13; 220333QAG) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.6943.138 Mobile Safari/537.36";

// ── STEP 1: Hit portal to grab fresh Cookie + CSRF token
$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, $portal_url);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_HEADER, true);
curl_setopt($ch1, CURLOPT_USERAGENT, $ua);
curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch1, CURLOPT_TIMEOUT, 15);
curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch1, CURLOPT_SSL_VERIFYHOST, false);

$res1      = curl_exec($ch1);
$header_sz = curl_getinfo($ch1, CURLINFO_HEADER_SIZE);
curl_close($ch1);

$raw_headers = substr($res1, 0, $header_sz);
$body        = substr($res1, $header_sz);

// Extract cookies
$cookies = [];
preg_match_all('/Set-Cookie:\s*([^;]+)/i', $raw_headers, $m);
foreach ($m[1] as $c) { $cookies[] = trim($c); }
$cookie_str = implode('; ', $cookies);

// Extract CSRF from meta tag or hidden input
$csrf = '';
if (preg_match('/<meta[^>]+name=["\']csrf-token["\'][^>]+content=["\'](.*?)["\']/i', $body, $mx)) {
    $csrf = $mx[1];
} elseif (preg_match('/name=["\']__RequestVerificationToken["\'][^>]+value=["\'](.*?)["\']/i', $body, $mx)) {
    $csrf = $mx[1];
} elseif (preg_match('/CSRF[_-]TOKEN["\s:=\']+([A-Za-z0-9_\-]+)/i', $body, $mx)) {
    $csrf = $mx[1];
}

// ── STEP 2: Use extracted credentials to fetch teacher data
