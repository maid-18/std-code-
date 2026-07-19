<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCareGuidanceAdmin();
ensureAcademicSupportTable($pdo);

$typeFilter   = clean($_GET['type']   ?? '');
$bucketFilter = clean($_GET['bucket'] ?? '');
$search       = clean($_GET['q']      ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$unitLabels = [
    'academic'         => 'الإرشاد الأكاديمي',
    'academic_support' => 'الدعم الأكاديمي',
    'talent'           => 'الموهبة والابتكار',
    'emergency'        => 'الطوارئ والرعاية السريعة',
    'reports'          => 'الملاحظات والبلاغات',
    'career'           => 'الإرشاد المهني',
    'special_needs'    => 'ذوي الاحتياجات الخاصة',
    'psg_appointments' => 'الإرشاد النفسي (مواعيد)',
    'psg_messages'     => 'الإرشاد النفسي (رسائل)',
];

/** كل مصدر: الجدول، حالات "جديد" و"مكتمل" الخاصة به، وجملة SELECT الموحّدة (title/student_name/service_title/status/created_at) */
$branches = [
    'academic' => [
        'new' => "mr.status='new'", 'done' => "mr.status='completed'",
        'select' => "SELECT mr.id, mr.title, u.full_name AS student_name, COALESCE(s.title,'إرشاد أكاديمي') AS service_title, mr.status, mr.created_at, 'academic' AS request_type
                     FROM requests mr JOIN users u ON u.id = mr.student_id LEFT JOIN services s ON s.id = mr.service_id",
        'search' => "(u.full_name LIKE :s OR mr.title LIKE :s)",
    ],
    'academic_support' => [
        'new' => "asr.status='new'", 'done' => "asr.status='completed'",
        'select' => "SELECT asr.id, asr.title, u.full_name AS student_name, 'الدعم الأكاديمي' AS service_title, asr.status, asr.created_at, 'academic_support' AS request_type
                     FROM academic_support_requests asr JOIN users u ON u.id = asr.user_id",
        'search' => "(u.full_name LIKE :s OR asr.title LIKE :s)",
    ],
    'talent' => [
        'new' => "tr.status='جديد'", 'done' => "tr.status='مكتمل'",
        'select' => "SELECT tr.id, CONCAT('موهبة: ', tr.talent_type) AS title, tr.student_name, 'وحدة الموهبة والابتكار' AS service_title, tr.status, tr.created_at, 'talent' AS request_type
                     FROM talent_requests tr",
        'search' => "tr.student_name LIKE :s",
    ],
    'emergency' => [
        'new' => "er.status='جديد'", 'done' => "er.status IN ('تم إغلاقها بنجاح','تم اغلاقها بنجاح')",
        'select' => "SELECT er.id, 'طلب دعم طارئ' AS title, er.student_name, 'وحدة الطوارئ والرعاية السريعة' AS service_title, er.status, er.created_at, 'emergency' AS request_type
                     FROM emergency_requests er",
        'search' => "er.student_name LIKE :s",
    ],
    'reports' => [
        'new' => "sr.status='جديد'", 'done' => "sr.status IN ('تم إغلاقها بنجاح','تم اغلاقها بنجاح')",
        'select' => "SELECT sr.id, sr.title, COALESCE(sr.student_name, u.full_name) AS student_name, 'وحدة الملاحظات والبلاغات' AS service_title, sr.status, sr.created_at, 'reports' AS request_type
                     FROM scr_reports sr JOIN users u ON u.id = sr.user_id",
        'search' => "(u.full_name LIKE :s OR sr.title LIKE :s)",
    ],
    'career' => [
        'new' => "cr.status='جديد'", 'done' => "cr.status IN ('تم إغلاقها بنجاح','تم اغلاقها بنجاح')",
        'select' => "SELECT cr.id, CONCAT('استشارة: ', cr.service_type) AS title, cr.student_name, 'وحدة الإرشاد المهني' AS service_title, cr.status, cr.created_at, 'career' AS request_type
                     FROM career_requests cr",
        'search' => "cr.student_name LIKE :s",
    ],
    'special_needs' => [
        'new' => "sn.status IN ('قيد الدراسة','جديد')", 'done' => "sn.status='مكتمل'",
        'select' => "SELECT sn.id, CONCAT(IF(sn.category_type='disability','إعاقة: ','احتياج خاص: '), sn.disability_type) AS title, u.full_name AS student_name, 'وحدة ذوي الاحتياجات الخاصة' AS service_title, sn.status, sn.created_at, 'special_needs' AS request_type
                     FROM special_needs_requests sn JOIN users u ON u.id = sn.user_id",
        'search' => "u.full_name LIKE :s",
    ],
    'psg_appointments' => [
        'new' => "pa.status IN ('جديد','نشط')", 'done' => "pa.status='مكتمل'",
        'select' => "SELECT pa.id, pa.topic AS title, u.full_name AS student_name, 'الإرشاد النفسي والاجتماعي' AS service_title, pa.status, pa.created_at, 'psg_appointments' AS request_type
                     FROM psg_appointments pa JOIN users u ON u.id = pa.user_id",
        'search' => "(u.full_name LIKE :s OR pa.topic LIKE :s)",
    ],
    'psg_messages' => [
        'new' => "pm.status IN ('جديد','نشط')", 'done' => "pm.status='مكتمل'",
        'select' => "SELECT pm.id, pm.subject AS title, u.full_name AS student_name, 'الإرشاد النفسي والاجتماعي' AS service_title, pm.status, pm.created_at, 'psg_messages' AS request_type
                     FROM psg_messages pm JOIN users u ON u.id = pm.user_id",
        'search' => "(u.full_name LIKE :s OR pm.subject LIKE :s)",
    ],
];

$activeBranches = $typeFilter !== '' && isset($branches[$typeFilter]) ? [$typeFilter => $branches[$typeFilter]] : $branches;

$unionParts = [];
$params = [];
$paramIndex = 0;
foreach ($activeBranches as $key => $b) {
    $where = [];
    if ($bucketFilter === 'new')  { $where[] = $b['new']; }
    if ($bucketFilter === 'done') { $where[] = $b['done']; }
    if ($search !== '') {
        // كل ظهور لـ :s يحصل على اسم فريد لأن الاستعلامات الفعلية (emulate_prepares=false)
        // لا تسمح بتكرار نفس اسم المعامل في نفس الجملة، حتى لو كانت القيمة متطابقة.
        $where[] = preg_replace_callback('/:s\b/', function () use (&$paramIndex, &$params, $search) {
            $ph = ':s' . $paramIndex++;
            $params[$ph] = '%' . $search . '%';
            return $ph;
        }, $b['search']);
    }
    $sql = $b['select'];
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $unionParts[] = "($sql)";
}

$fullQuery = implode(' UNION ALL ', $unionParts);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ({$fullQuery}) t");
$countStmt->execute($params);
$totalRequests = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRequests / $perPage));

$listStmt = $pdo->prepare("{$fullQuery} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$listStmt->execute($params);
$requestsList = $listStmt->fetchAll();

$pageTitle = 'جميع الطلبات';
$activePage = 'requests.php';
require_once __DIR__ . '/../includes/header.php';

$baseUrl = '?type=' . urlencode($typeFilter) . '&bucket=' . urlencode($bucketFilter) . '&q=' . urlencode($search);
?>

<div class="page-header">
    <h1><i class="bi bi-inbox-fill me-2"></i>جميع طلبات وحدات الرعاية والإرشاد</h1>
    <p>عرض ومتابعة والرد على طلبات جميع الوحدات من مكان واحد — <span class="fw-bold"><?= $totalRequests ?></span> طلب</p>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end w-100">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="بحث بالاسم أو الموضوع..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="type" class="form-select form-select-sm">
                <option value="">كل الوحدات</option>
                <?php foreach ($unitLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $typeFilter === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="bucket" class="form-select form-select-sm">
                <option value="">كل الحالات</option>
                <option value="new"  <?= $bucketFilter === 'new'  ? 'selected' : '' ?>>جديدة</option>
                <option value="done" <?= $bucketFilter === 'done' ? 'selected' : '' ?>>مكتملة</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">تصفية</button>
            <a href="requests.php" class="btn btn-outline-secondary btn-sm">إعادة</a>
        </div>
    </form>
</div>

<?php if (empty($requestsList)): ?>
    <div class="std-card"><div class="empty-state"><i class="bi bi-inbox"></i><h5>لا توجد طلبات مطابقة</h5></div></div>
<?php else: ?>
    <div class="std-card">
        <div class="table-responsive">
            <table class="table table-std table-hover mb-0">
                <thead><tr><th>#</th><th>الطالب</th><th>الموضوع</th><th>الوحدة</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($requestsList as $req): ?>
                    <tr>
                        <td class="text-muted small">#<?= (int)$req['id'] ?></td>
                        <td class="fw-600 small"><?= htmlspecialchars(mb_substr($req['student_name'] ?? '—', 0, 25)) ?></td>
                        <td><a href="request_details.php?id=<?= (int)$req['id'] ?>&type=<?= htmlspecialchars($req['request_type']) ?>" class="text-decoration-none" style="color:#103754;"><?= htmlspecialchars(mb_substr($req['title'], 0, 35)) ?></a></td>
                        <td><span class="badge bg-primary-soft text-primary small"><?= htmlspecialchars(mb_substr($req['service_title'], 0, 22)) ?></span></td>
                        <td><?= requestStatusLabel($req['status']) ?></td>
                        <td class="text-muted small"><?= formatDate($req['created_at']) ?></td>
                        <td><a href="request_details.php?id=<?= (int)$req['id'] ?>&type=<?= htmlspecialchars($req['request_type']) ?>" class="btn btn-sm btn-primary px-2"><i class="bi bi-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3"><?= renderPagination($page, $totalPages, $baseUrl) ?></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
