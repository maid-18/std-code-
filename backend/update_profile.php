<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['care_guidance_admin', 'manager', 'unit_supervisor']);

$redirectBase = getUserType() === 'unit_supervisor'
    ? '/supervisor/profile.php'
    : '/care_guidance_admin/profile.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    header("Location: {$redirectBase}?msg=" . urlencode('طلب غير صالح.') . '&type=error');
    exit;
}

$userId = getUserId();
$action = clean($_POST['action'] ?? '');

if ($action === 'update_info') {
    $fullName = clean($_POST['full_name'] ?? '');
    $phone    = clean($_POST['phone'] ?? '');

    if ($fullName === '') {
        header("Location: {$redirectBase}?msg=" . urlencode('الاسم مطلوب.') . '&type=error');
        exit;
    }

    $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?")->execute([$fullName, $phone ?: null, $userId]);
    $_SESSION['full_name'] = $fullName;
    header("Location: {$redirectBase}?msg=" . urlencode('تم حفظ بياناتك بنجاح.') . '&type=success');
    exit;
}

if ($action === 'change_password') {
    $current = (string)($_POST['current_password'] ?? '');
    $new     = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, $hash)) {
        header("Location: {$redirectBase}?msg=" . urlencode('كلمة المرور الحالية غير صحيحة.') . '&type=error');
        exit;
    }
    if (strlen($new) < 8 || $new !== $confirm) {
        header("Location: {$redirectBase}?msg=" . urlencode('كلمة المرور الجديدة غير متطابقة أو قصيرة جداً.') . '&type=error');
        exit;
    }

    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
    header("Location: {$redirectBase}?msg=" . urlencode('تم تغيير كلمة المرور بنجاح.') . '&type=success');
    exit;
}

header("Location: {$redirectBase}");
exit;
