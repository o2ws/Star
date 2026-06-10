<?php

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

// ================== الايدي ==================
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    echo json_encode(["error" => "No ID"]);
    exit;
}

// ===== تنظيف وتحويل الصيغة العلمية =====
$id = trim($id);

// إذا بيه E نحوله من صيغة علمية
if (stripos($id, 'e') !== false) {
    $id = sprintf('%.0f', floatval($id));
}

// نخلي فقط أرقام (يشيل أي رموز زائدة)
$id = preg_replace('/[^0-9]/', '', $id);

// إذا بعد التنظيف صار فارغ
if (empty($id)) {
    echo json_encode(["error" => "Invalid ID"]);
    exit;
}

// نخليه نص حتى ما يتحول مستقبلاً
$id = (string)$id;


// ================== API ==================
$url = "https://a2.apk-api.com/api/event/" . $id;


// ================== CURL ==================
$headers = [
    "Accept: application/json",
    "Content-Type: application/json",
    "User-Agent: okhttp/4.12.0",
    "api_url: http://ver3.yacinelive.com"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["id" => $id]));
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode(["error" => curl_error($ch)]);
    exit;
}


// ================== فصل الهيدر عن البودي ==================
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);

curl_close($ch);


// ================== استخراج tHeader ==================
$tHeader = "";
if (preg_match('/^t:\s*(.*)$/mi', $header, $matches)) {
    $tHeader = trim($matches[1]);
}


// ================== فك التشفير ==================
try {

    $decoded = base64_decode($body);
    $key = "c!xZj+N9&G@Ev@vw" . $tHeader;

    $plain = "";
    for ($i = 0; $i < strlen($decoded); $i++) {
        $plain .= $decoded[$i] ^ $key[$i % strlen($key)];
    }

    $Ameer = str_replace("\\/", "/", $plain);

    // ================== تحويل JSON ==================
    $data = json_decode($Ameer, true);

    if (!$data) {
        echo json_encode([
            "error" => "Invalid JSON",
            "raw" => $Ameer
        ]);
        exit;
    }

    // ================== تعديل الهيدر ==================
    if (isset($data['servers'])) {
        foreach ($data['servers'] as &$s) {
            if (!isset($s['headers'])) {
                $s['headers'] = [
                    "User-Agent" => "Mozilla/5.0",
                    "Referer" => "http://ver3.yacinelive.com"
                ];
            }
        }
    }

    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}