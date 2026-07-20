<?php
/**
 * تهيئة مشتركة لصفحات بوابة مشرف الوحدة:
 * تتحقق من الدور، تحدد وحدة المشرف من الجلسة (users.unit_type)،
 * وتضبط ألوان وقوائم القالب المشترك حسب هوية الوحدة.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/units.php';

requireUnitSupervisor();

$unitKey = currentSupervisorUnit();
if ($unitKey === '') {
    require_once __DIR__ . '/../includes/error_page.php';
    renderErrorPage(
        403,
        'لا توجد وحدة مرتبطة بحسابك',
        'حساب مشرف الوحدة يجب أن يكون مرتبطاً بإحدى الوحدات (unit_type). يرجى مراجعة مدير النظام.',
        '/login.php',
        'تسجيل الدخول'
    );
}

$unitCfg = unitRegistry()[$unitKey];
$unitSrc = unitSource($unitKey);

$navBase          = '/supervisor/';
$brandIcon        = $unitCfg['icon'];
$brandTitle       = 'مشرف الوحدة';
$brandSub         = $unitCfg['label'];
$roleLabel        = 'مشرف — ' . $unitCfg['label'];
$portalAccent     = $unitCfg['accent'];
$portalAccentDark = $unitCfg['accent_dark'];
$navItems = [
    'dashboard.php' => ['icon' => 'bi-speedometer2',                  'label' => 'لوحة التحكم'],
    'requests.php'  => ['icon' => 'bi-inbox-fill',                    'label' => 'طلبات الوحدة'],
    'reports.php'   => ['icon' => 'bi-file-earmark-bar-graph-fill',   'label' => 'تقرير الوحدة'],
    'profile.php'   => ['icon' => 'bi-person-circle',                 'label' => 'الملف الشخصي'],
];
