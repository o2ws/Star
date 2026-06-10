<?php
/**
 * API: /api/update.php
 * Returns update configuration only.
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config.php';

$update = file_exists(UPDATE_MSG_FILE) ? get_json_data(UPDATE_MSG_FILE) : [];

$response = [
    'enabled'  => (isset($update['enabled']) && ($update['enabled'] === true || $update['enabled'] === "true" || $update['enabled'] === 1)) ? true : false,
    'title'    => $update['title']    ?? 'New Update Available!',
    'message'  => $update['message']  ?? '',
    'url'      => $update['url']      ?? '',
    'btn_text' => $update['btn_text'] ?? 'Update Now',
    'version'  => $update['version']  ?? '1.0.0',
    'force'    => (isset($update['force']) && ($update['force'] === true || $update['force'] === "true" || $update['force'] === 1)) ? true : false
];

ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache, no-store, must-revalidate');

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
?>
