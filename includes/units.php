<?php
/**
 * السجل المركزي لجهات النظام — شارات الوحدات والمديرين/المشرفين.
 *
 * كل جهة لها: اسم معتمد، أيقونة Bootstrap Icons، لون أساسي (accent)
 * ونسخة أغمق منه بـ 15% (accent_dark)، ورابط لوحتها الافتراضي.
 * الجدول مطابق لألوان وأيقونات care_guidance_admin/reports.php.
 */

function departmentRegistry(): array
{
    static $registry = [
        'manager' => [
            'label'       => 'مدير النظام',
            'icon'        => 'bi-person-gear',
            'accent'      => '#152238',
            'accent_dark' => '#0d1626',
            'href'        => '/manager/dashboard.php',
        ],
        'care_guidance' => [
            'label'       => 'الإداره العامة للإرشاد',
            'label2'      => 'والرعاية الطلابية',
            'icon'        => 'bi-people',
            'accent'      => '#2fa6a6',
            'accent_dark' => '#21807f',
            'href'        => '/care_guidance_admin/dashboard.php',
        ],
        'academic' => [
            'label'       => 'وحدة الإرشاد الأكاديمي',
            'icon'        => 'bi-journal-bookmark-fill',
            'accent'      => '#2e6da4',
            'accent_dark' => '#204d78',
            'href'        => '/supervisor/dashboard.php',
        ],
        'talent' => [
            'label'       => 'وحدة الموهبة والابتكار',
            'icon'        => 'bi-stars',
            'accent'      => '#6b3fbf',
            'accent_dark' => '#4c2c8a',
            'href'        => '/supervisor/dashboard.php',
        ],
        'emergency' => [
            'label'       => 'وحدة الطوارئ والرعاية السريعة',
            'icon'        => 'bi-exclamation-triangle',
            'accent'      => '#c0392b',
            'accent_dark' => '#8f2b20',
            'href'        => '/supervisor/dashboard.php',
        ],
        'reports' => [
            'label'       => 'وحدة الملاحظات والبلاغات',
            'icon'        => 'bi-chat-square-text',
            'accent'      => '#d4700a',
            'accent_dark' => '#a35608',
            'href'        => '/supervisor/dashboard.php',
        ],
        'career' => [
            'label'       => 'وحدة الإرشاد المهني',
            'icon'        => 'bi-briefcase',
            'accent'      => '#3a52c4',
            'accent_dark' => '#293a8c',
            'href'        => '/supervisor/dashboard.php',
        ],
        'special_needs' => [
            'label'       => 'وحدة ذوي الاحتياجات الخاصة',
            'icon'        => 'bi-person-wheelchair',
            'accent'      => '#1e8c52',
            'accent_dark' => '#166b3e',
            'href'        => '/supervisor/dashboard.php',
        ],
    ];
    return $registry;
}

/** الوحدات الست فقط (بدون مدير النظام والإدارة العامة) — مفاتيحها = users.unit_type */
function unitRegistry(): array
{
    return array_diff_key(departmentRegistry(), ['manager' => 1, 'care_guidance' => 1]);
}

/**
 * شارة جهة بالنمط الموحّد (.dept-badge).
 *
 * @param string      $key  مفتاح الجهة في departmentRegistry()
 * @param string|null $href رابط بديل (null = الرابط الافتراضي، '' = بدون رابط)
 */
function renderDeptBadge(string $key, ?string $href = null, string $extraClass = ''): string
{
    $registry = departmentRegistry();
    if (!isset($registry[$key])) {
        return '';
    }
    $d = $registry[$key];
    $href = $href ?? $d['href'];
    $tag  = $href === '' ? 'span' : 'a';
    $hrefAttr = $href === '' ? '' : ' href="' . htmlspecialchars($href) . '"';

    $text = htmlspecialchars($d['label']);
    if (!empty($d['label2'])) {
        $text .= '<br>' . htmlspecialchars($d['label2']);
    }

    return '<' . $tag . $hrefAttr . ' class="dept-badge ' . htmlspecialchars($extraClass) . '"'
        . ' style="--accent:' . $d['accent'] . ';--accent-dark:' . $d['accent_dark'] . ';">'
        . '<span class="dept-badge__icon"><i class="bi ' . $d['icon'] . '"></i></span>'
        . '<span class="dept-badge__text">' . $text . '</span>'
        . '</' . $tag . '>';
}

/**
 * مصدر بيانات كل وحدة: الجدول، شرط "جديد"، شرط "مكتمل"، واستعلام القائمة
 * الموحّد (id, title, brief, student_name, student_number, status, created_at).
 */
function unitSource(string $key): ?array
{
    static $sources = [
        'academic' => [
            'table' => 'requests',
            'new'   => "status = 'new'",
            'done'  => "status = 'completed'",
            'list'  => "SELECT mr.id, mr.title, mr.description AS brief, u.full_name AS student_name,
                               COALESCE(u.student_number,'—') AS student_number, mr.status, mr.created_at
                        FROM requests mr JOIN users u ON u.id = mr.student_id",
            'search' => "(u.full_name LIKE :s OR mr.title LIKE :s2)",
        ],
        'talent' => [
            'table' => 'talent_requests',
            'new'   => "status = 'جديد'",
            'done'  => "status = 'مكتمل'",
            'list'  => "SELECT id, CONCAT('موهبة: ', talent_type) AS title, interests_desc AS brief,
                               student_name, student_id AS student_number, status, created_at
                        FROM talent_requests",
            'search' => "(student_name LIKE :s OR talent_type LIKE :s2)",
        ],
        'emergency' => [
            'table' => 'emergency_requests',
            'new'   => "status = 'جديد'",
            'done'  => "status IN ('تم إغلاقها بنجاح','تم اغلاقها بنجاح')",
            'list'  => "SELECT id, 'طلب دعم طارئ' AS title, reason AS brief,
                               student_name, student_id AS student_number, status, created_at
                        FROM emergency_requests",
            'search' => "(student_name LIKE :s OR reason LIKE :s2)",
        ],
        'reports' => [
            'table' => 'scr_reports',
            'new'   => "status = 'جديد'",
            'done'  => "status IN ('تم إغلاقها بنجاح','تم اغلاقها بنجاح')",
            'list'  => "SELECT sr.id, sr.title, sr.description AS brief,
                               COALESCE(sr.student_name, u.full_name) AS student_name,
                               COALESCE(sr.student_id, u.student_number, '—') AS student_number,
                               sr.status, sr.created_at
                        FROM scr_reports sr JOIN users u ON u.id = sr.user_id",
            'search' => "(COALESCE(sr.student_name, u.full_name) LIKE :s OR sr.title LIKE :s2)",
        ],
        'career' => [
            'table' => 'career_requests',
            'new'   => "status = 'جديد'",
            'done'  => "status IN ('تم إغلاقها بنجاح','تم اغلاقها بنجاح')",
            'list'  => "SELECT id, CONCAT('استشارة: ', service_type) AS title, notes AS brief,
                               student_name, student_id AS student_number, status, created_at
                        FROM career_requests",
            'search' => "(student_name LIKE :s OR service_type LIKE :s2)",
        ],
        'special_needs' => [
            'table' => 'special_needs_requests',
            'new'   => "status IN ('قيد الدراسة','جديد')",
            'done'  => "status = 'مكتمل'",
            'list'  => "SELECT sn.id,
                               CONCAT(IF(sn.category_type='disability','إعاقة: ','احتياج خاص: '), sn.disability_type) AS title,
                               sn.description AS brief, u.full_name AS student_name,
                               COALESCE(u.student_number,'—') AS student_number, sn.status, sn.created_at
                        FROM special_needs_requests sn JOIN users u ON u.id = sn.user_id",
            'search' => "(u.full_name LIKE :s OR sn.disability_type LIKE :s2)",
        ],
    ];
    return $sources[$key] ?? null;
}

/** جهة مشرف الوحدة الحالي حسب unit_type المخزّن في الجلسة */
function currentSupervisorUnit(): string
{
    $unit = $_SESSION['unit_type'] ?? '';
    return isset(unitRegistry()[$unit]) ? $unit : '';
}
