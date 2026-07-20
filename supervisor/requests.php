<?php
require_once __DIR__ . '/_bootstrap.php';

$bucketFilter = clean($_GET['bucket'] ?? '');
$search       = clean($_GET['q']      ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($bucketFilter === 'new')  { $where[] = $unitSrc['new']; }
if ($bucketFilter === 'done') { $where[] = $unitSrc['done']; }
if ($search !== '') {
    // :s و :s2 باسمين مختلفين لأن الاستعلامات الفعلية لا تسمح بتكرار نفس المعامل
    $where[]        = $unitSrc['search'];
    $params[':s']   = '%' . $search . '%';
    $params[':s2']  = '%' . $search . '%';
}

$sql = $unitSrc['list'];
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ({$sql}) t");
$countStmt->execute($params);
$totalRequests = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRequests / $perPage));

$listStmt = $pdo->prepare("{$sql} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$listStmt->execute($params);
$requestsList = $listStmt->fetchAll();

$pageTitle = 'طلبات ' . $unitCfg['label'];
$activePage = 'requests.php';
require_once __DIR__ . '/../includes/header.php';

$baseUrl = '?bucket=' . urlencode($bucketFilter) . '&q=' . urlencode($search);
?>

<div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
        <h1><i class="bi <?= $unitCfg['icon'] ?> me-2"></i>طلبات <?= htmlspecialchars($unitCfg['label']) ?></h1>
        <p>عرض ومتابعة طلبات الوحدة — <span class="fw-bold"><?= $totalRequests ?></span> طلب</p>
    </div>
    <?= renderDeptBadge($unitKey, '', 'dept-badge--sm') ?>
</div>

<div class="filter-bar">
    <form method="GET" class="row g-2 align-items-end w-100">
        <div class="col-md-5">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="بحث بالاسم أو الموضوع..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4">
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
                <thead><tr><th>#</th><th>الطالب</th><th>الرقم الجامعي</th><th>الموضوع</th><th>نبذة</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                <tbody>
                <?php foreach ($requestsList as $req):
                    $brief = trim($req['brief'] ?? '');
                    $brief = $brief !== '' ? mb_substr($brief, 0, 60) . (mb_strlen($brief) > 60 ? '…' : '') : '—';
                ?>
                    <tr>
                        <td class="text-muted small">#<?= (int)$req['id'] ?></td>
                        <td class="fw-600 small"><?= htmlspecialchars(mb_substr($req['student_name'] ?? '—', 0, 25)) ?></td>
                        <td class="small text-muted font-monospace"><?= htmlspecialchars($req['student_number'] ?? '—') ?></td>
                        <td class="small"><?= htmlspecialchars(mb_substr($req['title'], 0, 40)) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($brief) ?></td>
                        <td><?= requestStatusLabel($req['status']) ?></td>
                        <td class="text-muted small"><?= formatDate($req['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3"><?= renderPagination($page, $totalPages, $baseUrl) ?></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
