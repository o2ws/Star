<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config.php';

// ─── التحقق من المفتاح ───
$key = $_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($key !== API_SECRET_KEY) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// ─── جلب البيانات ───
$cats = get_json_data(CATEGORIES_FILE) ?: [];
$chs  = get_json_data(CHANNELS_FILE)   ?: [];

// ─── ترتيب القنوات داخل كل قسم ───
$result = [];
foreach ($cats as $cat) {
    $channels = array_values(array_filter($chs, fn($c) => $c['cat_id'] == $cat['id']));
    $result[] = [
        'id'       => (string)$cat['id'],
        'name'     => $cat['name'],
        'image'    => $cat['image'] ?? '',
        'channels' => array_map(function($ch) {
            $servers = [];
            if (!empty($ch['servers']) && is_array($ch['servers'])) {
                foreach ($ch['servers'] as $s) {
                    $servers[] = [
                        'name'      => $s['name']      ?? '',
                        'url'       => $s['url']       ?? '',
                        'useragent' => $s['useragent'] ?? '',
                        'referer'   => $s['referer']   ?? '',
                        'origin'    => $s['origin']    ?? '',
                        'drmkey'    => $s['drmkey']    ?? '',
                        'cookie'    => $s['cookie']    ?? '',
                    ];
                }
            } else {
                $servers[] = [
                    'name'      => 'سيرفر 1',
                    'url'       => $ch['url']       ?? '',
                    'useragent' => $ch['useragent'] ?? '',
                    'referer'   => $ch['referer']   ?? '',
                    'origin'    => $ch['origin']    ?? '',
                    'drmkey'    => $ch['drmkey']    ?? '',
                    'cookie'    => $ch['cookie']    ?? '',
                ];
            }
            
            return [
                'id'        => (string)$ch['id'],
                'name'      => $ch['name'],
                'url'       => $ch['url'] ?? '',
                'image'     => $ch['image']     ?? '',
                'useragent' => $ch['useragent'] ?? '',
                'referer'   => $ch['referer']   ?? '',
                'origin'    => $ch['origin']    ?? '',
                'drmkey'    => $ch['drmkey']    ?? '',
                'cookie'    => $ch['cookie']    ?? '',
                'servers'   => $servers,
            ];
        }, $channels),
    ];
}

echo json_encode([
    'status'     => 'ok',
    'total_cats' => count($cats),
    'total_chs'  => count($chs),
    'data'       => $result,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);