<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCareGuidanceAdmin();
ensureAcademicSupportTable($pdo);

$fullName = $_SESSION['full_name'] ?? '';

/* ============================================================
   إحصائيات إجمالية عبر جميع وحدات الرعاية والإرشاد (9 مصادر)
   ============================================================ */
$sources = [
    'academic'          => ['table' => 'requests',                 'new' => "status='new'",           'done' => "status='completed'"],
    'academic_support'  => ['table' => 'academic_support_requests', 'new' => "status='new'",           'done' => "status='completed'"],
    'talent'            => ['table' => 'talent_requests',           'new' => "status='جديد'",          'done' => "status='مكتمل'"],
    'emergency'         => ['table' => 'emergency_requests',        'new' => "status='جديد'",          'done' => "status IN ('تم إغلاقها بنجاح','تم اغلاقها بنجاح')"],
    'reports'           => ['table' => 'scr_reports',               'new' => "status='جديد'",          'done' => "status IN ('تم إغلاقها بنجاح','تم اغلاقها بنجاح')"],
    'career'            => ['table' => 'career_requests',           'new' => "status='جديد'",          'done' => "status IN ('تم إغلاقها بنجاح','تم اغلاقها بنجاح')"],
    'special_needs'     => ['table' => 'special_needs_requests',    'new' => "status IN ('قيد الدراسة','جديد')", 'done' => "status='مكتمل'"],
    'psg_appointments'  => ['table' => 'psg_appointments',          'new' => "status IN ('جديد','نشط')", 'done' => "status='مكتمل'"],
    'psg_messages'      => ['table' => 'psg_messages',              'new' => "status IN ('جديد','نشط')", 'done' => "status='مكتمل'"],
];
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

$total = 0; $newCount = 0; $done = 0;
$unitCounts = [];
foreach ($sources as $key => $cfg) {
    $t = (int)$pdo->query("SELECT COUNT(*) FROM {$cfg['table']}")->fetchColumn();
    $n = (int)$pdo->query("SELECT COUNT(*) FROM {$cfg['table']} WHERE {$cfg['new']}")->fetchColumn();
    $d = (int)$pdo->query("SELECT COUNT(*) FROM {$cfg['table']} WHERE {$cfg['done']}")->fetchColumn();
    $unitCounts[$key] = $t;
    $total += $t; $newCount += $n; $done += $d;
}

/* ============================================================
   آخر 10 طلبات من جميع المصادر
   ============================================================ */
$latestRequests = $pdo->query("
    (SELECT mr.id, mr.title, u.full_name AS student_name, COALESCE(s.title,'إرشاد أكاديمي') AS service_title, mr.status, mr.created_at, 'academic' AS request_type
     FROM requests mr JOIN users u ON u.id = mr.student_id LEFT JOIN services s ON s.id = mr.service_id)
    UNION ALL
    (SELECT asr.id, asr.title, u.full_name, 'الدعم الأكاديمي', asr.status, asr.created_at, 'academic_support'
     FROM academic_support_requests asr JOIN users u ON u.id = asr.user_id)
    UNION ALL
    (SELECT tr.id, CONCAT('موهبة: ', tr.talent_type), tr.student_name, 'وحدة الموهبة والابتكار', tr.status, tr.created_at, 'talent'
     FROM talent_requests tr)
    UNION ALL
    (SELECT er.id, 'طلب دعم طارئ', er.student_name, 'وحدة الطوارئ والرعاية السريعة', er.status, er.created_at, 'emergency'
     FROM emergency_requests er)
    UNION ALL
    (SELECT sr.id, sr.title, COALESCE(sr.student_name, u.full_name), 'وحدة الملاحظات والبلاغات', sr.status, sr.created_at, 'reports'
     FROM scr_reports sr JOIN users u ON u.id = sr.user_id)
    UNION ALL
    (SELECT cr.id, CONCAT('استشارة: ', cr.service_type), cr.student_name, 'وحدة الإرشاد المهني', cr.status, cr.created_at, 'career'
     FROM career_requests cr)
    UNION ALL
    (SELECT sn.id, CONCAT(IF(sn.category_type='disability','إعاقة: ','احتياج خاص: '), sn.disability_type), u.full_name, 'وحدة ذوي الاحتياجات الخاصة', sn.status, sn.created_at, 'special_needs'
     FROM special_needs_requests sn JOIN users u ON u.id = sn.user_id)
    UNION ALL
    (SELECT pa.id, pa.topic, u.full_name, 'الإرشاد النفسي والاجتماعي', pa.status, pa.created_at, 'psg_appointments'
     FROM psg_appointments pa JOIN users u ON u.id = pa.user_id)
    UNION ALL
    (SELECT pm.id, pm.subject, u.full_name, 'الإرشاد النفسي والاجتماعي', pm.status, pm.created_at, 'psg_messages'
     FROM psg_messages pm JOIN users u ON u.id = pm.user_id)
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

/* ============================================================
   عدد الطلبات خلال آخر 12 شهر (كل المصادر مجمّعة)
   ============================================================ */
$months = [];
$monthCounts = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $months[] = date('M', strtotime($month));
    $count = 0;
    foreach ($sources as $cfg) {
        $count += (int)$pdo->query("SELECT COUNT(*) FROM {$cfg['table']} WHERE DATE_FORMAT(created_at,'%Y-%m')='$month'")->fetchColumn();
    }
    $monthCounts[] = $count;
}

$pageTitle = 'لوحة التحكم';
$activePage = 'dashboard.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="welcome-banner">
    <div class="row align-items-center">
        <div class="col">
            <h2>مرحباً، <?= htmlspecialchars(explode(' ', $fullName)[0] ?? '') ?></h2>
            <p>متابعة أداء جميع وحدات الرعاية والإرشاد الطلابي في مكان واحد</p>
        </div>
        <div class="col-auto d-none d-md-block">
            <a href="requests.php" class="btn btn-light fw-bold">
                <i class="bi bi-inbox me-1"></i>جميع الطلبات
                <?php if ($newCount > 0): ?><span class="badge bg-danger ms-1"><?= $newCount ?></span><?php endif; ?>
            </a>
        </div>
    </div>
    <i class="bi bi-shield-heart banner-bg-icon"></i>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-soft text-primary"><i class="bi bi-inbox-fill"></i></div>
                <div><div class="stat-number text-primary"><?= $total ?></div><div class="stat-label">إجمالي الطلبات</div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-inline-start-color:#e67e22;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef9e7;color:#e67e22;"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="stat-number" style="color:#e67e22;"><?= $newCount ?></div><div class="stat-label">طلبات جديدة</div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-inline-start-color:#27ae60;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#eafaf1;color:#27ae60;"><i class="bi bi-check-circle-fill"></i></div>
                <div><div class="stat-number" style="color:#27ae60;"><?= $done ?></div><div class="stat-label">مكتملة</div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-inline-start-color:#8e44ad;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f5eef9;color:#8e44ad;"><i class="bi bi-speedometer2"></i></div>
                <div><div class="stat-number" style="color:#8e44ad;"><?= $total ? round($done / $total * 100) : 0 ?>%</div><div class="stat-label">معدل الإنجاز</div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="std-card p-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-graph-up-arrow text-primary me-2"></i>عدد الطلبات خلال آخر 12 شهر</h5>
            <div style="height:300px;position:relative;"><canvas id="monthlyChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="std-card p-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-pie-chart-fill text-primary me-2"></i>توزيع الطلبات حسب الوحدة</h5>
            <div style="height:300px;position:relative;"><canvas id="unitChart"></canvas></div>
        </div>
    </div>
</div>

<div class="std-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-inbox me-2 text-primary"></i>آخر الطلبات من جميع الوحدات</h5>
        <a href="requests.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
    </div>

    <?php if (empty($latestRequests)): ?>
        <div class="empty-state py-4"><i class="bi bi-inbox"></i><h5>لا توجد طلبات بعد</h5></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-std table-hover mb-0">
            <thead><tr><th>#</th><th>الطالب</th><th>الموضوع</th><th>الوحدة</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($latestRequests as $req): ?>
                <tr>
                    <td class="text-muted small">#<?= (int)$req['id'] ?></td>
                    <td class="fw-600 small"><?= htmlspecialchars(mb_substr($req['student_name'] ?? '—', 0, 20)) ?></td>
                    <td><a href="request_details.php?id=<?= (int)$req['id'] ?>&type=<?= htmlspecialchars($req['request_type']) ?>" class="text-decoration-none" style="color:#103754;"><?= htmlspecialchars(mb_substr($req['title'], 0, 30)) ?></a></td>
                    <td><span class="badge bg-primary-soft text-primary small"><?= htmlspecialchars(mb_substr($req['service_title'], 0, 22)) ?></span></td>
                    <td><?= requestStatusLabel($req['status']) ?></td>
                    <td class="text-muted small"><?= formatDate($req['created_at']) ?></td>
                    <td><a href="request_details.php?id=<?= (int)$req['id'] ?>&type=<?= htmlspecialchars($req['request_type']) ?>" class="btn btn-sm btn-primary px-2"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'عدد الطلبات', data: <?= json_encode($monthCounts) ?>,
            borderColor: '#103754', backgroundColor: 'rgba(16,55,84,0.15)', fill: true, tension: 0.4, borderWidth: 3
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
new Chart(document.getElementById('unitChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_values($unitLabels)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($unitCounts)) ?>,
            backgroundColor: ['#103754','#0891b2','#2E86C1','#27AE60','#F39C12','#8E44AD','#1e8c52','#0d7a8c','#3a52c4'],
            borderColor: '#fff', borderWidth: 2
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
