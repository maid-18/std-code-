<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCareGuidanceAdmin();
ensureAcademicSupportTable($pdo);

$unit = clean($_GET['unit'] ?? 'academic');

$unitConfig = [
    'academic'         => ['label' => 'وحدة الإرشاد الأكاديمي',        'icon' => 'bi-journal-bookmark-fill', 'color' => '#2e6da4'],
    'academic_support' => ['label' => 'وحدة الدعم الأكاديمي',          'icon' => 'bi-headset',               'color' => '#0891b2'],
    'talent'           => ['label' => 'وحدة الموهبة والابتكار',        'icon' => 'bi-stars',                 'color' => '#6b3fbf'],
    'emergency'        => ['label' => 'وحدة الطوارئ والرعاية السريعة', 'icon' => 'bi-exclamation-triangle',  'color' => '#c0392b'],
    'reports'          => ['label' => 'وحدة الملاحظات والبلاغات',      'icon' => 'bi-chat-square-text',      'color' => '#d4700a'],
    'career'           => ['label' => 'وحدة الإرشاد المهني',           'icon' => 'bi-briefcase',             'color' => '#3a52c4'],
    'special_needs'    => ['label' => 'وحدة ذوي الاحتياجات الخاصة',    'icon' => 'bi-person-wheelchair',     'color' => '#1e8c52'],
    'psg_appointments' => ['label' => 'الإرشاد النفسي والاجتماعي (مواعيد)', 'icon' => 'bi-heart-pulse',       'color' => '#0d7a8c'],
    'psg_messages'     => ['label' => 'الإرشاد النفسي والاجتماعي (رسائل)', 'icon' => 'bi-envelope-heart',     'color' => '#0d7a8c'],
];

if (!isset($unitConfig[$unit])) {
    header('Location: reports.php?unit=academic');
    exit;
}
$cfg = $unitConfig[$unit];
$siteName = getSetting('university_name', 'جامعة بيشة');

switch ($unit) {
    case 'academic':
        $total = $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
        $byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM requests GROUP BY status")->fetchAll();
        $byMonth = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM requests GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $latest = $pdo->query("SELECT mr.id, mr.title, mr.description AS brief, u.full_name AS student_name, COALESCE(u.student_number,'—') AS student_number, mr.status, mr.created_at FROM requests mr JOIN users u ON u.id=mr.student_id ORDER BY mr.created_at DESC")->fetchAll();
        break;
    case 'academic_support':
        $total = $pdo->query("SELECT COUNT(*) FROM academic_support_requests")->fetchColumn();
        $byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM academic_support_requests GROUP BY status")->fetchAll();
        $byMonth = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM academic_support_requests GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $latest = $pdo->query("SELECT asr.id, asr.title, asr.description AS brief, u.full_name AS student_name, COALESCE(u.student_number,'—') AS student_number, asr.status, asr.created_at FROM academic_support_requests asr JOIN users u ON u.id=asr.user_id ORDER BY asr.created_at DESC")->fetchAll();
        break;
    case 'talent':
        $total = $pdo->query("SELECT COUNT(*) FROM talent_requests")->fetchColumn();
        $byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM talent_requests GROUP BY status")->fetchAll();
        $byMonth = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM talent_requests GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $latest = $pdo->query("SELECT id, CONCAT('موهبة: ',talent_type) AS title, interests_desc AS brief, student_name, student_id AS student_number, status, created_at FROM talent_requests ORDER BY created_at DESC")->fetchAll();
        break;
    case 'emergency':
        $total = $pdo->query("SELECT COUNT(*) FROM emergency_requests")->fetchColumn();
        $byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM emergency_requests GROUP BY status")->fetchAll();
        $byMonth = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM emergency_requests GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $latest = $pdo->query("SELECT id, 'طلب دعم طارئ' AS title, reason AS brief, student_name, student_id AS student_number, status, created_at FROM emergency_requests ORDER BY created_at DESC")->fetchAll();
        break;
    case 'reports':
        $total = $pdo->query("SELECT COUNT(*) FROM scr_reports")->fetchColumn();
        $byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM scr_reports GROUP BY status")->fetchAll();
        $byMonth = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM scr_reports GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $latest = $pdo->query("SELECT sr.id, sr.title, sr.description AS brief, COALESCE(sr.student_name,u.full_name) AS student_name, COALESCE(sr.student_id,u.student_number,'—') AS student_number, sr.status, sr.created_at FROM scr_reports sr JOIN users u ON u.id=sr.user_id ORDER BY sr.created_at DESC")->fetchAll();
        break;
    case 'career':
        $total = $pdo->query("SELECT COUNT(*) FROM career_requests")->fetchColumn();
        $byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM career_requests GROUP BY status")->fetchAll();
        $byMonth = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM career_requests GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $latest = $pdo->query("SELECT id, CONCAT('استشارة: ',service_type) AS title, notes AS brief, student_name, student_id AS student_number, status, created_at FROM career_requests ORDER BY created_at DESC")->fetchAll();
        break;
    case 'special_needs':
        $total = $pdo->query("SELECT COUNT(*) FROM special_needs_requests")->fetchColumn();
        $byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM special_needs_requests GROUP BY status")->fetchAll();
        $byMonth = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM special_needs_requests GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $latest = $pdo->query("SELECT sn.id, CONCAT('إعاقة: ',sn.disability_type) AS title, sn.description AS brief, u.full_name AS student_name, COALESCE(u.student_number,'—') AS student_number, sn.status, sn.created_at FROM special_needs_requests sn JOIN users u ON u.id=sn.user_id ORDER BY sn.created_at DESC")->fetchAll();
        break;
    case 'psg_appointments':
        $total = $pdo->query("SELECT COUNT(*) FROM psg_appointments")->fetchColumn();
        $byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM psg_appointments GROUP BY status")->fetchAll();
        $byMonth = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM psg_appointments GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $latest = $pdo->query("SELECT pa.id, pa.topic AS title, pa.details AS brief, u.full_name AS student_name, COALESCE(u.student_number,'—') AS student_number, pa.status, pa.created_at FROM psg_appointments pa JOIN users u ON u.id=pa.user_id ORDER BY pa.created_at DESC")->fetchAll();
        break;
    case 'psg_messages':
        $total = $pdo->query("SELECT COUNT(*) FROM psg_messages")->fetchColumn();
        $byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM psg_messages GROUP BY status")->fetchAll();
        $byMonth = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM psg_messages GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
        $latest = $pdo->query("SELECT pm.id, pm.subject AS title, pm.message AS brief, u.full_name AS student_name, COALESCE(u.student_number,'—') AS student_number, pm.status, pm.created_at FROM psg_messages pm JOIN users u ON u.id=pm.user_id ORDER BY pm.created_at DESC")->fetchAll();
        break;
}

$reportDate = date('Y/m/d');
$reportNumber = 'RPT-' . strtoupper(str_replace('_', '-', $unit)) . '-' . date('Ymd-His');

$pageTitle = 'تقرير ' . $cfg['label'];
$activePage = 'reports.php';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
@media print {
    .std-sidebar, .std-topbar, .no-print { display: none !important; }
    .std-content { padding: 0 !important; }
    body { background: #fff !important; }
}
</style>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-0" style="color:#103754"><i class="bi <?= $cfg['icon'] ?> me-2" style="color:<?= $cfg['color'] ?>"></i>تقرير <?= htmlspecialchars($cfg['label']) ?></h4>
        <p class="text-muted small mb-0"><?= $reportDate ?> — رقم: <?= $reportNumber ?></p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer me-1"></i>طباعة / تصدير PDF</button>
</div>

<div class="d-flex gap-2 flex-wrap mb-4 no-print">
    <?php foreach ($unitConfig as $key => $c): ?>
    <a href="?unit=<?= $key ?>" class="btn btn-sm <?= $unit === $key ? 'btn-primary' : 'btn-outline-secondary' ?>"><i class="bi <?= $c['icon'] ?> me-1"></i><?= $c['label'] ?></a>
    <?php endforeach; ?>
</div>

<div class="text-center mb-4 d-none d-print-block">
    <h3 class="fw-bold" style="color:#103754;"><?= htmlspecialchars($siteName) ?> — عمادة شؤون الطلاب</h3>
    <h5>تقرير رسمي — <?= htmlspecialchars($cfg['label']) ?></h5>
    <p class="text-muted small">تاريخ الإصدار: <?= $reportDate ?> — رقم المرجع: <?= $reportNumber ?></p>
    <hr>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="std-card p-4 text-center">
        <div style="font-size:2rem;font-weight:800;color:<?= $cfg['color'] ?>"><?= $total ?></div>
        <div class="text-muted small mt-1">إجمالي الطلبات</div>
    </div></div>
    <?php foreach ($byStatus as $row): ?>
    <div class="col-6 col-md-3"><div class="std-card p-4 text-center">
        <div style="font-size:2rem;font-weight:800;color:#103754"><?= $row['cnt'] ?></div>
        <div class="text-muted small mt-1"><?= htmlspecialchars($row['status']) ?></div>
    </div></div>
    <?php endforeach; ?>
</div>

<div class="std-card p-4 mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-calendar3 me-2 text-primary"></i>الطلبات الشهرية (آخر 6 أشهر)</h6>
    <?php if (!empty($byMonth)): $maxVal = max(array_column($byMonth, 'cnt')) ?: 1; $monthsRev = array_reverse($byMonth); ?>
    <div class="d-flex align-items-end justify-content-between gap-3" style="height:150px;border-bottom:1px solid #eef2f6;">
        <?php foreach ($monthsRev as $row): $h = round($row['cnt'] / $maxVal * 120); ?>
        <div class="flex-grow-1 text-center d-flex flex-column align-items-center justify-content-end" style="height:100%;">
            <span class="fw-bold mb-1" style="font-size:12px;color:#103754"><?= $row['cnt'] ?></span>
            <div style="width:50%;max-width:34px;height:<?= max($h, 4) ?>px;background:linear-gradient(180deg,#1a6fb0,#0b2e4d);border-radius:8px 8px 4px 4px;"></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-between gap-3 mt-2">
        <?php foreach ($monthsRev as $row): ?><div class="flex-grow-1 text-center text-muted" style="font-size:11px;"><?= $row['month'] ?></div><?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center text-muted py-4"><i class="bi bi-inbox fs-2 d-block mb-2"></i>لا توجد بيانات</div>
    <?php endif; ?>
</div>

<div class="std-card p-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-table me-2 text-primary"></i>جميع الطلبات (<?= count($latest) ?>)</h6>
    <div class="table-responsive">
        <table class="table table-std table-hover mb-0">
            <thead><tr><th>#</th><th>الطالب</th><th>الرقم الجامعي</th><th>الموضوع</th><th>نبذة</th><th>الحالة</th><th>التاريخ</th></tr></thead>
            <tbody>
            <?php foreach ($latest as $i => $req):
                $brief = trim($req['brief'] ?? '');
                $brief = $brief !== '' ? mb_substr($brief, 0, 70) . (mb_strlen($brief) > 70 ? '…' : '') : '—';
            ?>
                <tr>
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td class="small fw-600"><?= htmlspecialchars($req['student_name']) ?></td>
                    <td class="small text-muted font-monospace"><?= htmlspecialchars($req['student_number'] ?? '—') ?></td>
                    <td class="small"><?= htmlspecialchars(mb_substr($req['title'], 0, 40)) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($brief) ?></td>
                    <td><?= requestStatusLabel($req['status']) ?></td>
                    <td class="text-muted small"><?= formatDate($req['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($latest)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">لا توجد طلبات مسجّلة لهذه الوحدة.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
