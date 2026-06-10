<?php
/**
 * encrypt_existing.php
 * شغّل هذا الملف مرة واحدة فقط على السيرفر لتشفير ملفات JSON القديمة
 * بعد التشغيل احذف هذا الملف فوراً
 */

define('API_SECRET_KEY',    'NovaTvSuperSecretKey2024!@#$Nova');
define('ENCRYPTION_METHOD', 'AES-256-CBC');
define('FILE_ENCRYPTION_KEY', 'ReviTV_FileKey!$2024#Secure@Disk');
define('STORAGE_DIR', __DIR__ . '/data/');

$files = [
    STORAGE_DIR . 'categories.json',
    STORAGE_DIR . 'channels.json',
    STORAGE_DIR . 'codes.json',
    STORAGE_DIR . 'admin.json',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "⚠️ غير موجود: $file\n";
        continue;
    }

    $raw = file_get_contents($file);

    // تحقق هل مشفر مسبقاً
    $parts = explode('|', $raw);
    if (count($parts) === 2 && base64_decode($parts[0], true) !== false) {
        echo "✅ مشفر مسبقاً: $file\n";
        continue;
    }

    // تشفير
    $iv        = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt($raw, ENCRYPTION_METHOD, FILE_ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    $result    = base64_encode($iv) . '|' . base64_encode($encrypted);

    file_put_contents($file, $result);
    echo "🔒 تم تشفير: $file\n";
}

echo "\n✅ انتهى — احذف هذا الملف الآن!\n";
?>
