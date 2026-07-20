<?php
require_once __DIR__ . '/_bootstrap.php';

$fullName = $_SESSION['full_name'] ?? '';
$table = $unitSrc['table'];

$total    = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
$newCount = (int)$pdo->query("SELECT COUNT(*) FROM {$table} WHERE {$unitSrc['new']}")->fetchColumn();
$done     = (int)$pdo->query("SELECT COUNT(*) FROM {$table} WHERE {$unitSrc['done']}")->fetchColumn();

$byMonth = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS cnt
    FROM {$table} GROUP BY month ORDER BY month DESC LIMIT 6
")->fetchAll();

$latest = $pdo->query("{$unitSrc['list']} ORDER BY created_at DESC LIMIT 10")->fetchAll();

$pageTitle = 'لوحة التحكم — ' . $unitCfg['label'];
$activePage = 'dashboard.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="welcome-banner" style="background:linear-gradient(135deg, <?= $unitCfg['accent_dark'] ?>, <?= $unitCfg['accent'] ?>);">
    <div class="row align-items-center">
        <div class="col">
            <h2>مرحباً، <?= htmlspecialchars(explode(' ', $fullName)[0] ?? '') ?></h2>
            <p>متابعة طلبات <?= htmlspecialchars($unitCfg['label']) ?> والاطلاع على تقاريرها</p>
        </div>
        <div class="col-auto d-none d-md-block">
            <?= renderDeptBadge($unitKey, '') ?>
        </div>
    </div>
    <i class="bi <?= $unitCfg['icon'] ?> banner-bg-icon"></i>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-inline-start-color:<?= $unitCfg['accent'] ?>;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-soft" style="color:<?= $unitCfg['accent'] ?>;"><i class="bi bi-inbox-fill"></i></div>
                <div><div class="stat-number" style="color:<?= $unitCfg['accent'] ?>;"><?= $total ?></div><div class="stat-label">إجمالي الطلبات</div></div>
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="bi bi-inbox me-2" style="color:<?= $unitCfg['accent'] ?>;"></i>آخر طلبات الوحدة</h5>
        <a href="requests.php" class="btn btn-sm btn-outline-primary">عرض الكل</a>
    </div>

    <?php if (empty($latest)): ?>
        <div class="empty-state py-4"><i class="bi bi-inbox"></i><h5>لا توجد طلبات بعد</h5></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-std table-hover mb-0">
            <thead><tr><th>#</th><th>الطالب</th><th>الرقم الجامعي</th><th>الموضوع</th><th>الحالة</th><th>التاريخ</th></tr></thead>
            <tbody>
            <?php foreach ($latest as $req): ?>
                <tr>
                    <td class="text-muted small">#<?= (int)$req['id'] ?></td>
                    <td class="fw-600 small"><?= htmlspecialchars(mb_substr($req['student_name'] ?? '—', 0, 25)) ?></td>
                    <td class="small text-muted font-monospace"><?= htmlspecialchars($req['student_number'] ?? '—') ?></td>
                    <td class="small"><?= htmlspecialchars(mb_substr($req['title'], 0, 40)) ?></td>
                    <td><?= requestStatusLabel($req['status']) ?></td>
                    <td class="text-muted small"><?= formatDate($req['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
