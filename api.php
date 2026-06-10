<?php
require_once 'config.php';

header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {

    case 'get_data':
        $categories = get_json_data(CATEGORIES_FILE);
        $channels   = get_json_data(CHANNELS_FILE);

        $data = [
            'categories' => $categories,
            'channels'   => $channels
        ];

        $response = [
            'status'    => 'success',
            'message'   => 'Data retrieved',
            'data'      => $data,
            'timestamp' => time()
        ];

        $json = json_encode($response, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            echo encrypt_data(json_encode(['status' => 'error', 'message' => 'JSON Error: ' . json_last_error_msg()]));
            exit;
        }

        echo encrypt_data($json);
        break;

    // ======= نقطة وصول لفك التشفير (للتطبيق) =======
    // الاستخدام: api.php?action=decrypt
    // يُرسل التطبيق البيانات المشفرة في body كـ POST بمفتاح "payload"
    case 'decrypt':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'POST required']);
            exit;
        }

        $payload = isset($_POST['payload']) ? $_POST['payload'] : '';
        if (empty($payload)) {
            echo json_encode(['status' => 'error', 'message' => 'No payload']);
            exit;
        }

        $decrypted = decrypt_api_response($payload);
        if ($decrypted === null) {
            echo json_encode(['status' => 'error', 'message' => 'Decryption failed']);
            exit;
        }

        header('Content-Type: application/json');
        echo $decrypted; // JSON نظيف بدون تشفير
        break;

    default:
        echo encrypt_data(json_encode(['status' => 'error', 'message' => 'Unknown action']));
        break;
}
?>
