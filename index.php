<?php

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

$url = "https://a2.apk-api.com/api/events";

$headers = [
    "Accept: application/json",
    "User-Agent: okhttp/4.12.0",
    "api_url: http://ver3.yacinelive.com"
];

$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_HEADER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_ENCODING => "",
    CURLOPT_TIMEOUT => 20
]);

$response = curl_exec($ch);

if ($response === false) {

    die(json_encode([
        "success" => false,
        "error" => curl_error($ch)
    ]));
}


// ================= HEADER / BODY =================

$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

$rawHeader = substr($response, 0, $header_size);

$body = substr($response, $header_size);

curl_close($ch);


// ================= tHeader =================

$tHeader = "";

if (preg_match('/^t:\s*(.+)$/mi', $rawHeader, $match)) {

    $tHeader = trim($match[1]);
}

if (!$tHeader) {

    die(json_encode([
        "success" => false,
        "error" => "Missing tHeader",
        "headers" => $rawHeader,
        "body_preview" => substr($body, 0, 300)
    ]));
}


// ================= DECODE =================

$decoded = base64_decode(trim($body), true);

if ($decoded === false) {

    die(json_encode([
        "success" => false,
        "error" => "Base64 decode failed"
    ]));
}

$key = "c!xZj+N9&G@Ev@vw" . $tHeader;

$plain = '';

for ($i = 0; $i < strlen($decoded); $i++) {

    $plain .= chr(
        ord($decoded[$i]) ^ ord($key[$i % strlen($key)])
    );
}

$Ameer = str_replace("\\/", "/", trim($plain));


// ================= JSON =================

$responseMap = json_decode($Ameer, true);

if (!$responseMap) {

    die(json_encode([
        "success" => false,
        "error" => json_last_error_msg(),
        "preview" => substr($Ameer, 0, 1000)
    ]));
}


// ================= OUTPUT =================

echo json_encode(
    $responseMap,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_PRETTY_PRINT
);