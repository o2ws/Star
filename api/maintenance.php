<?php
/**
 * API: /api/maintenance.php
 * Returns maintenance status only.
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

$maint = file_exists(MAINTENANCE_FILE) ? get_json_data(MAINTENANCE_FILE) : [];

$response = [
    'enabled' => (isset($maint['enabled']) && ($maint['enabled'] === true || $maint['enabled'] === "true" || $maint['enabled'] === 1)) ? true : false,
    'title'   => $maint['title']   ?? 'Maintenance Mode',
    'message' => $maint['message'] ?? '',
    'image'   => $maint['image']   ?? '',
    'contact' => $maint['contact'] ?? 'https://t.me/uruk_support'
];

ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>
