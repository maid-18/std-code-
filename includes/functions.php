<?php
/**
 * دوال مساعدة مشتركة تُستخدم عبر وحدة الإدارة العامة للرعاية والإرشاد.
 */

function clean($value): string
{
    return is_string($value) ? trim($value) : '';
}

function formatDate(?string $datetime): string
{
    if (empty($datetime)) {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts ? date('Y/m/d', $ts) : '—';
}

function formatDateTime(?string $datetime): string
{
    if (empty($datetime)) {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts ? date('Y/m/d H:i', $ts) : '—';
}

function getUserAvatar(?string $profileImage, string $gender = 'male'): string
{
    if (!empty($profileImage)) {
        $full = __DIR__ . '/../assets/uploads/' . $profileImage;
        if (is_file($full)) {
            return '/assets/uploads/' . $profileImage;
        }
    }
    return '/assets/img/avatar-default.svg';
}

/**
 * خريطة موحّدة لكل الحالات المستخدمة عبر جداول الوحدات المختلفة (إنجليزية/عربية)
 * وتُرجع شارة (badge) بلون مناسب. أي حالة غير معروفة تُعرض كما هي بلون محايد.
 */
function requestStatusLabel(?string $status): string
{
    $status = trim((string)$status);

    static $map = [
        'new'                    => ['جديد', '#f97316', '#fff7ed'],
        'under_review'           => ['قيد المراجعة', '#8b5cf6', '#f5f3ff'],
        'replied'                => ['تم الرد', '#0284c7', '#f0f9ff'],
        'completed'              => ['تم الحل', '#059669', '#ecfdf5'],
        'rejected'               => ['مرفوض', '#dc2626', '#fef2f2'],
        'closed'                 => ['مغلق', '#64748b', '#f8fafc'],

        'جديد'                   => ['جديد', '#f97316', '#fff7ed'],
        'معلق'                   => ['جديد', '#f97316', '#fff7ed'],
        'قيد المراجعة'           => ['قيد المراجعة', '#8b5cf6', '#f5f3ff'],
        'قيد المعالجة'           => ['قيد المعالجة', '#8b5cf6', '#f5f3ff'],
        'قيد الدراسة'            => ['قيد الدراسة', '#8b5cf6', '#f5f3ff'],
        'متابعة مستمرة'          => ['متابعة مستمرة', '#0284c7', '#f0f9ff'],
        'تم التواصل'             => ['تم التواصل', '#0284c7', '#f0f9ff'],
        'مكتمل'                  => ['مكتمل', '#059669', '#ecfdf5'],
        'تم إغلاقها بنجاح'       => ['تم الإغلاق بنجاح', '#059669', '#ecfdf5'],
        'تم اغلاقها بنجاح'       => ['تم الإغلاق بنجاح', '#059669', '#ecfdf5'],
        'مرفوض'                  => ['مرفوض', '#dc2626', '#fef2f2'],
        'مغلق'                   => ['مغلق', '#64748b', '#f8fafc'],

        'نشط'                    => ['نشط', '#2563eb', '#eff6ff'],
        'يحتاج مراجعة ذاتية'     => ['يحتاج مراجعة ذاتية', '#d97706', '#fffbeb'],
        'محال خارجياً'           => ['محال خارجياً', '#e11d48', '#fff1f2'],
    ];

    [$label, $color, $bg] = $map[$status] ?? [$status !== '' ? $status : '—', '#64748b', '#f8fafc'];

    return '<span class="badge-status" style="color:' . $color . ';background:' . $bg . ';">' . htmlspecialchars($label) . '</span>';
}

function priorityLabel(?string $priority): string
{
    $map = [
        'high'   => ['عاجلة', '#dc2626', '#fef2f2'],
        'medium' => ['متوسطة', '#d97706', '#fffbeb'],
        'low'    => ['منخفضة', '#059669', '#ecfdf5'],
    ];
    [$label, $color, $bg] = $map[$priority] ?? ['متوسطة', '#d97706', '#fffbeb'];

    return '<span class="badge-status" style="color:' . $color . ';background:' . $bg . ';">' . htmlspecialchars($label) . '</span>';
}

function sendNotification(PDO $pdo, int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, link)
            VALUES (:user_id, :title, :message, :type, :link)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':title'   => $title,
            ':message' => $message,
            ':type'    => $type,
            ':link'    => $link,
        ]);
    } catch (PDOException $e) {
        // لا نُفشل العملية الأساسية بسبب خطأ في الإشعارات
    }
}

function getSetting(string $key, string $default = ''): string
{
    global $pdo;
    static $cache = [];

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        $cache[$key] = $value !== false ? $value : $default;
    } catch (PDOException $e) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function renderPagination(int $page, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) {
        return '';
    }
    $sep = str_contains($baseUrl, '?') ? '&' : '?';
    $html = '<nav><ul class="pagination justify-content-center mb-0">';

    $prevDisabled = $page <= 1 ? ' disabled' : '';
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . htmlspecialchars($baseUrl . $sep . 'page=' . max(1, $page - 1)) . '">السابق</a></li>';

    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . htmlspecialchars($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
    }

    $nextDisabled = $page >= $totalPages ? ' disabled' : '';
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . htmlspecialchars($baseUrl . $sep . 'page=' . min($totalPages, $page + 1)) . '">التالي</a></li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * تجهيز جدول تذاكر الدعم الأكاديمي (وحدة الدعم الأكاديمي للطالب) — مستقل عن جدول requests العام.
 */
function ensureAcademicSupportTable(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS academic_support_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            issue_type VARCHAR(150) NOT NULL,
            course_name VARCHAR(200) DEFAULT NULL,
            college VARCHAR(255) DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            attachment VARCHAR(255) DEFAULT NULL,
            status ENUM('new','under_review','replied','completed','rejected','closed') DEFAULT 'new',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    $ensured = true;
}
