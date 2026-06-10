<?php
// api/user.php
// تسجيل مستخدم جديد أو تحديث آخر ظهور له

require_once __DIR__ . '/../config.php';

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_response('error', 'استخدم POST فقط');
}

// التحقق من مفتاح API
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($api_key !== API_SECRET_KEY) {
    send_response('error', 'مفتاح API غير صالح');
}

// استقبال البيانات
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['device_id'])) {
    send_response('error', 'device_id مطلوب');
}

$device_id  = trim($input['device_id']);
$platform   = trim($input['platform'] ?? 'android');
$app_ver    = trim($input['app_version'] ?? 'unknown');
$ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// تحميل المستخدمين
$users_file = USERS_FILE;
if (!file_exists(dirname($users_file))) {
    mkdir(dirname($users_file), 0777, true);
}
$users = file_exists($users_file) ? get_json_data($users_file) : [];

// بحث عن الجهاز
$found = false;
foreach ($users as &$user) {
    if ($user['device_id'] === $device_id) {
        $user['last_seen']   = date('Y-m-d H:i:s');
        $user['platform']    = $platform;
        $user['app_ver']     = $app_ver;
        $user['ip']          = $ip;
        $user['user_agent']  = $user_agent;
        $user['visit_count'] = ($user['visit_count'] ?? 0) + 1;
        $found = true;
        break;
    }
}
unset($user);

// إضافة جديد
if (!$found) {
    $users[] = [
        'id'          => uniqid('usr_', true),
        'device_id'   => $device_id,
        'platform'    => $platform,
        'app_ver'     => $app_ver,
        'ip'          => $ip,
        'user_agent'  => $user_agent,
        'first_seen'  => date('Y-m-d H:i:s'),
        'last_seen'   => date('Y-m-d H:i:s'),
        'visit_count' => 1
    ];
}

// حفظ
save_json_data($users_file, $users);

// إحصائيات
$total_users = count($users);
$today = date('Y-m-d');
$active_today = 0;
foreach ($users as $u) {
    if (substr($u['last_seen'], 0, 10) === $today) {
        $active_today++;
    }
}

send_response('success', $found ? 'تم تحديث بيانات المستخدم' : 'تم تسجيل مستخدم جديد', [
    'total_users'  => $total_users,
    'active_today' => $active_today,
    'is_new'       => !$found
]);