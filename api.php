<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!isset($_GET['eiin']) || empty(trim($_GET['eiin']))) {
    echo json_encode(["error" => "EIIN নম্বর প্রয়োজন"]);
    exit;
}

$eiin = trim($_GET['eiin']);

// Basic validation — EIIN is numeric, typically 6 digits
if (!ctype_digit($eiin)) {
    echo json_encode(["error" => "EIIN অবশ্যই সংখ্যা হতে হবে"]);
    exit;
}

$url = "http://emis.gov.bd/emis/Portal/GetTeacherDetails";

$headers = [
    "User-Agent: Mozilla/5.0 (Linux; Android 13; 220333QAG Build/TKQ1.221114.001) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.6943.138 Mobile Safari/537.36",
    "Accept: application/json, text/javascript, */*; q=0.01",
    "Accept-Encoding: gzip, deflate",
    "Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
    "X-CSRF-TOKEN: R2LbLQi4qCGdZlQBpaZX8HKO3TkIrNNiCC8_OsWBTldw4bDiFD8uFLCXXAO4UthhtO4_LPPhjJOv6YhNPrvqFAFIryHqA78XsCsLyAuyp_E1",
    "X-Requested-With: XMLHttpRequest",
    "Origin: http://emis.gov.bd",
    "Referer: http://emis.gov.bd/EMIS/portal",
    "Accept-Language: en-US,en;q=0.9",
    "Cookie: __RequestVerificationToken_L2VtaXM1=t57UIgAYXp8xI0BhiVJpQafm5DB0CP454n0H73wo6P1boE4RkAsF3-IMV5JcaTg2FLlqxqdTPRjaF14N1WqRYzmacZo6gWYX9B8qtgEA3ss1; CSRF-TOKEN=R2LbLQi4qCGdZlQBpaZX8HKO3TkIrNNiCC8_OsWBTldw4bDiFD8uFLCXXAO4UthhtO4_LPPhjJOv6YhNPrvqFAFIryHqA78XsCsLyAuyp_E1"
];

$postData = http_build_query([
    'instituteId' => '',
    'EIIN'        => $eiin,
    'isTeacher'   => 1
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(["error" => "সংযোগ ব্যর্থ: " . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(["error" => "সার্ভার সাড়া দেয়নি", "status_code" => $httpCode]);
    exit;
}

// Forward the response exactly
echo $response;
?>
