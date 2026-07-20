<?php
require_once __DIR__ . '/_bootstrap.php';

$table = $unitSrc['table'];
$siteName = getSetting('university_name', 'جامعة بيشة');

$total    = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
$byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status")->fetchAll();
$byMonth  = $pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt FROM {$table} GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
$latest   = $pdo->query("{$unitSrc['list']} ORDER BY created_at DESC")->fetchAll();

$reportDate = date('Y/m/d');
$reportNumber = 'RPT-' . strtoupper(str_replace('_', '-', $unitKey)) . '-' . date('Ymd-His');

$pageTitle = 'تقرير ' . $unitCfg['label'];
$activePage = 'reports.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3 no-print">
    <div>
        <h4 class="fw-bold mb-0" style="color:<?= $unitCfg['accent_dark'] ?>"><i class="bi <?= $unitCfg['icon'] ?> me-2" style="color:<?= $unitCfg['accent'] ?>"></i>تقرير <?= htmlspecialchars($unitCfg['label']) ?></h4>
        <p class="text-muted small mb-0"><?= $reportDate ?> — رقم: <?= $reportNumber ?></p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <?= renderDeptBadge($unitKey, '', 'dept-badge--sm') ?>
        <button onclick="window.print()" class="btn btn-outline-primary btn-sm"><i class="bi bi-printer me-1"></i>طباعة / تصدير PDF</button>
    </div>
</div>

<div class="text-center mb-4 d-none d-print-block">
    <h3 class="fw-bold" style="color:#103754;"><?= htmlspecialchars($siteName) ?> — عمادة شؤون الطلاب</h3>
    <h5>تقرير رسمي — <?= htmlspecialchars($unitCfg['label']) ?></h5>
    <p class="text-muted small">تاريخ الإصدار: <?= $reportDate ?> — رقم المرجع: <?= $reportNumber ?></p>
    <hr>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="std-card p-4 text-center">
        <div style="font-size:2rem;font-weight:800;color:<?= $unitCfg['accent'] ?>"><?= $total ?></div>
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
    <h6 class="fw-bold mb-3"><i class="bi bi-calendar3 me-2" style="color:<?= $unitCfg['accent'] ?>;"></i>الطلبات الشهرية (آخر 6 أشهر)</h6>
    <?php if (!empty($byMonth)): $maxVal = max(array_column($byMonth, 'cnt')) ?: 1; $monthsRev = array_reverse($byMonth); ?>
    <div class="d-flex align-items-end justify-content-between gap-3" style="height:150px;border-bottom:1px solid #eef2f6;">
        <?php foreach ($monthsRev as $row): $h = round($row['cnt'] / $maxVal * 120); ?>
        <div class="flex-grow-1 text-center d-flex flex-column align-items-center justify-content-end" style="height:100%;">
            <span class="fw-bold mb-1" style="font-size:12px;color:<?= $unitCfg['accent_dark'] ?>"><?= $row['cnt'] ?></span>
            <div style="width:50%;max-width:34px;height:<?= max($h, 4) ?>px;background:linear-gradient(180deg,<?= $unitCfg['accent'] ?>,<?= $unitCfg['accent_dark'] ?>);border-radius:8px 8px 4px 4px;"></div>
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
    <h6 class="fw-bold mb-3"><i class="bi bi-table me-2" style="color:<?= $unitCfg['accent'] ?>;"></i>جميع الطلبات (<?= count($latest) ?>)</h6>
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
