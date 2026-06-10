<?php
// تشغيل الجلسة مرة وحدة فقط
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================= CONFIG =================
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('API_SECRET_KEY', 'NovaTvSuperSecretKey2024!@#$Nova');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

// لازم 32 بايت بالضبط
define('FILE_ENCRYPTION_KEY', '12345678901234567890123456789012');

// ================= STORAGE =================
define('STORAGE_DIR', __DIR__ . '/data/');

define('CATEGORIES_FILE', STORAGE_DIR . 'categories.json');
define('USERS_FILE', STORAGE_DIR . 'users.json');
define('CHANNELS_FILE', STORAGE_DIR . 'channels.json');
define('CODES_FILE', STORAGE_DIR . 'codes.json');
define('ADMIN_FILE', STORAGE_DIR . 'admin.json');

define('UPDATE_MSG_FILE', STORAGE_DIR . 'update.json');
define('MAINTENANCE_FILE', STORAGE_DIR . 'maintenance.json');

// ================= CREATE DATA FOLDER =================
if (!file_exists(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0777, true);
}

// ================= ENCRYPT =================
function encrypt_file_data($plaintext) {

    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);

    if ($iv_length === false) {
        return false;
    }

    $iv = random_bytes($iv_length);

    $encrypted = openssl_encrypt(
        $plaintext,
        ENCRYPTION_METHOD,
        FILE_ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($encrypted === false) {
        return false;
    }

    return base64_encode($iv . $encrypted);
}

// ================= DECRYPT =================
function decrypt_file_data($ciphertext) {

    if (empty($ciphertext)) {
        return null;
    }

    $decoded = base64_decode($ciphertext, true);

    if ($decoded === false) {
        return null;
    }

    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);

    if (strlen($decoded) < $iv_length) {
        return null;
    }

    $iv = substr($decoded, 0, $iv_length);
    $encrypted = substr($decoded, $iv_length);

    $decrypted = openssl_decrypt(
        $encrypted,
        ENCRYPTION_METHOD,
        FILE_ENCRYPTION_KEY,
        OPENSSL_RAW_DATA,
        $iv
    );

    return $decrypted !== false ? $decrypted : null;
}

// ================= SAVE JSON =================
function save_json_data($file, $data) {

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    $encrypted = encrypt_file_data($json);

    file_put_contents($file, $encrypted);
}

// ================= READ JSON =================
function get_json_data($file) {

    if (!file_exists($file)) {
        return [];
    }

    $raw = file_get_contents($file);

    if (empty($raw)) {
        return [];
    }

    $data = [];
    // محاولة فك التشفير
    $decrypted = decrypt_file_data($raw);

    if ($decrypted !== null) {
        $data = json_decode($decrypted, true);
        if (!is_array($data)) {
            $data = [];
        }
    } else {
        // دعم الملفات القديمة بدون تشفير
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = $decoded;
            // إعادة حفظ مشفر
            save_json_data($file, $data);
        }
    }

    // ترتيب تلقائي للتصنيفات إذا تم قراءة ملف التصنيفات بناءً على حقل sort_order
    if ($file === CATEGORIES_FILE && is_array($data)) {
        usort($data, function($a, $b) {
            $orderA = isset($a['sort_order']) ? intval($a['sort_order']) : 0;
            $orderB = isset($b['sort_order']) ? intval($b['sort_order']) : 0;
            if ($orderA === $orderB) {
                return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
            }
            return $orderA <=> $orderB;
        });
    }

    return $data;
}

// ================= INITIAL FILES =================
$default_admin = [
    'username' => 'admin',
    'password' => password_hash('admin123', PASSWORD_DEFAULT)
];

$files = [
    CATEGORIES_FILE   => [],
    CHANNELS_FILE     => [],
    CODES_FILE        => [],
    UPDATE_MSG_FILE   => [],
    MAINTENANCE_FILE  => [],
    ADMIN_FILE        => $default_admin
];

foreach ($files as $file => $default_data) {

    if (!file_exists($file)) {

        save_json_data($file, $default_data);
    }
}

// ================= API ENCRYPT =================
function encrypt_data($data) {

    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);

    $iv = random_bytes($iv_length);

    $encrypted = openssl_encrypt(
        $data,
        ENCRYPTION_METHOD,
        API_SECRET_KEY,
        OPENSSL_RAW_DATA,
        $iv
    );

    return base64_encode($encrypted) . '|' . base64_encode($iv);
}

// ================= API RESPONSE =================
function send_response($status, $message, $data = null) {

    $response = [
        'status'    => $status,
        'message'   => $message,
        'data'      => $data,
        'timestamp' => time()
    ];

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

    exit;
}
?>