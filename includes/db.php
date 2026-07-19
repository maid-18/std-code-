<?php
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'std_system';
$dbUser = getenv('DB_USER') ?: 'std_app';
$dbPass = getenv('DB_PASS') ?: 'std_app_pw';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('تعذّر الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً أو مراجعة الدعم الفني.');
}
