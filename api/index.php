<?php
/**
 * API Index: /api/
 * Returns unified status for maintenance and updates.
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

// Ensure fresh data
$update = file_exists(UPDATE_MSG_FILE)  ? get_json_data(UPDATE_MSG_FILE)  : [];
$maint  = file_exists(MAINTENANCE_FILE) ? get_json_data(MAINTENANCE_FILE) : [];

$response = [
    'maintenance' => [
        'enabled' => (isset($maint['enabled']) && ($maint['enabled'] === true || $maint['enabled'] === "true" || $maint['enabled'] === 1)) ? true : false,
        'title'   => $maint['title']   ?? 'Maintenance Mode',
        'message' => $maint['message'] ?? '',
        'image'   => $maint['image']   ?? '',
        'contact' => $maint['contact'] ?? 'https://t.me/uruk_support'
    ],
    'update' => [
        'enabled'  => (isset($update['enabled']) && ($update['enabled'] === true || $update['enabled'] === "true" || $update['enabled'] === 1)) ? true : false,
        'title'    => $update['title']    ?? 'New Update Available!',
        'message'  => $update['message']  ?? '',
        'url'      => $update['url']      ?? '',
        'btn_text' => $update['btn_text'] ?? 'Update Now',
        'version'  => $update['version']  ?? '1.0.0',
        'force'    => (isset($update['force']) && ($update['force'] === true || $update['force'] === "true" || $update['force'] === 1)) ? true : false
    ]
];

ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
