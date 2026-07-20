<?php
require_once __DIR__ . '/_bootstrap.php';

$stmt = $pdo->prepare("SELECT u.*, ut.arabic_title AS user_type_title FROM users u JOIN user_types ut ON ut.id = u.user_type_id WHERE u.id = ?");
$stmt->execute([getUserId()]);
$user = $stmt->fetch();

$csrfToken = generateCsrfToken();
$pageTitle = 'الملف الشخصي';
$activePage = 'profile.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><i class="bi bi-person-circle me-2"></i>الملف الشخصي</h1>
    <p>إدارة بياناتك الشخصية</p>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-<?= ($_GET['type'] ?? '') === 'error' ? 'danger' : 'success' ?> py-2 small"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="std-card p-4 text-center">
            <img src="<?= getUserAvatar($user['profile_image'] ?? null) ?>" alt="" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #e8edf2;">
            <h5 class="fw-bold mt-3 mb-1"><?= htmlspecialchars($user['full_name']) ?></h5>
            <p class="text-muted small mb-3"><?= htmlspecialchars($user['email']) ?></p>
            <?= renderDeptBadge($unitKey, '', 'dept-badge--sm') ?>
            <hr>
            <div class="text-start">
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted"><i class="bi bi-telephone me-1"></i>الجوال</span>
                    <span class="fw-600"><?= htmlspecialchars($user['phone'] ?? '—') ?></span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted"><i class="bi bi-calendar me-1"></i>تاريخ الإنشاء</span>
                    <span class="fw-600"><?= formatDate($user['created_at']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="std-card p-4 mb-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-person me-2 text-primary"></i>البيانات الشخصية</h5>
            <form method="POST" action="/backend/update_profile.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="update_info">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">الاسم الكامل</label>
                        <input type="text" name="full_name" class="form-control" required value="<?= htmlspecialchars($user['full_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">رقم الجوال</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">البريد الإلكتروني</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        <div class="form-text">لا يمكن تغيير البريد الإلكتروني</div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-5"><i class="bi bi-save me-2"></i>حفظ التغييرات</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="std-card p-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-shield-lock me-2 text-primary"></i>تغيير كلمة المرور</h5>
            <form method="POST" action="/backend/update_profile.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">كلمة المرور الحالية</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">كلمة المرور الجديدة</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">تأكيد كلمة المرور</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-primary px-5"><i class="bi bi-lock me-2"></i>تغيير كلمة المرور</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
