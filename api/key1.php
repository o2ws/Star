<?php

header("Content-Type: application/json");

// الرمز الصحيح
$key = "1122";

// يرجع JSON للتطبيق
echo json_encode([
    "status" => true,
    "message" => $key
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

?>