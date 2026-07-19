<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireCareGuidanceAdmin();
ensureAcademicSupportTable($pdo);

$adminId = getUserId();
$requestId = (int)($_GET['id'] ?? 0);
$type      = clean($_GET['type'] ?? 'academic');

$validTypes = ['academic', 'academic_support', 'talent', 'emergency', 'reports', 'career', 'special_needs', 'psg_appointments', 'psg_messages'];
if (!$requestId || !in_array($type, $validTypes, true)) {
    header('Location: requests.php');
    exit;
}

/** خريطة الحالات المسموحة لكل نوع (تختلف مفردات كل جدول عن الآخر) */
$statusOptions = [
    'academic'         => ['new' => 'جديد', 'under_review' => 'قيد المراجعة', 'replied' => 'تم الرد', 'completed' => 'تم الحل', 'rejected' => 'مرفوض', 'closed' => 'مغلق'],
    'academic_support' => ['new' => 'جديد', 'under_review' => 'قيد المراجعة', 'replied' => 'تم الرد', 'completed' => 'تم الحل', 'rejected' => 'مرفوض', 'closed' => 'مغلق'],
    'talent'           => ['جديد' => 'جديد', 'قيد المراجعة' => 'قيد المراجعة', 'مكتمل' => 'مكتمل', 'مرفوض' => 'مرفوض'],
    'emergency'        => ['جديد' => 'جديد', 'قيد المراجعة' => 'قيد المراجعة', 'تم إغلاقها بنجاح' => 'تم إغلاقها بنجاح'],
    'reports'          => ['جديد' => 'جديد', 'قيد المعالجة' => 'قيد المعالجة', 'متابعة مستمرة' => 'متابعة مستمرة', 'تم إغلاقها بنجاح' => 'تم إغلاقها بنجاح'],
    'career'           => ['جديد' => 'جديد', 'قيد المعالجة' => 'قيد المعالجة', 'متابعة مستمرة' => 'متابعة مستمرة', 'تم إغلاقها بنجاح' => 'تم إغلاقها بنجاح'],
    'special_needs'    => ['قيد الدراسة' => 'قيد الدراسة', 'قيد المراجعة' => 'قيد المراجعة', 'مكتمل' => 'مكتمل', 'مرفوض' => 'مرفوض'],
    'psg_appointments' => ['جديد' => 'جديد', 'نشط' => 'نشط', 'يحتاج مراجعة ذاتية' => 'يحتاج مراجعة ذاتية', 'مكتمل' => 'مكتمل', 'محال خارجياً' => 'محال خارجياً'],
    'psg_messages'     => ['جديد' => 'جديد', 'نشط' => 'نشط', 'يحتاج مراجعة ذاتية' => 'يحتاج مراجعة ذاتية', 'مكتمل' => 'مكتمل', 'محال خارجياً' => 'محال خارجياً'],
];

/** كل نوع: جدول التحديث، عمود الحالة، وجدول/شرط الردود */
$tableMap = [
    'academic'         => 'requests',
    'academic_support' => 'academic_support_requests',
    'talent'           => 'talent_requests',
    'emergency'        => 'emergency_requests',
    'reports'          => 'scr_reports',
    'career'           => 'career_requests',
    'special_needs'    => 'special_needs_requests',
    'psg_appointments' => 'psg_appointments',
    'psg_messages'     => 'psg_messages',
];

$csrfToken = generateCsrfToken();
$successMsg = '';
$errorMsg = '';

/* ── معالجة تحديث الحالة والرد الرسمي ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_request') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errorMsg = 'انتهت صلاحية الجلسة، يرجى إعادة تحميل الصفحة والمحاولة مجدداً.';
    } else {
        $newStatus = clean($_POST['status'] ?? '');
        $replyText = clean($_POST['reply_message'] ?? '');
        $table = $tableMap[$type];

        if ($newStatus !== '' && isset($statusOptions[$type][$newStatus])) {
            try {
                $pdo->prepare("UPDATE {$table} SET status = ? WHERE id = ?")->execute([$newStatus, $requestId]);
            } catch (PDOException $e) {
                $errorMsg = 'تعذّر تحديث الحالة: ' . $e->getMessage();
            }
        }

        if ($replyText !== '') {
            try {
                if ($type === 'academic') {
                    $pdo->prepare("INSERT INTO request_replies (request_id, user_id, reply_message) VALUES (?, ?, ?)")
                        ->execute([$requestId, $adminId, $replyText]);
                } else {
                    $pdo->prepare("INSERT INTO unit_request_replies (request_id, request_type, user_id, reply_message) VALUES (?, ?, ?, ?)")
                        ->execute([$requestId, $type, $adminId, $replyText]);
                }
            } catch (PDOException $e) {
                $errorMsg = 'تعذّر حفظ الرد: ' . $e->getMessage();
            }
        }

        if (!$errorMsg) {
            header("Location: request_details.php?id={$requestId}&type={$type}&updated=1");
            exit;
        }
    }
}

/* ── جلب بيانات الطلب حسب النوع ── */
$title = $service_title = $details_content = $student_name = $student_id_num = '';
$status = ''; $created_at = $updated_at = ''; $attachment_file = ''; $request = null;

if ($type === 'academic') {
    $stmt = $pdo->prepare("SELECT mr.*, s.title AS service_title, u.full_name AS student_name, u.student_number FROM requests mr JOIN users u ON u.id = mr.student_id LEFT JOIN services s ON s.id = mr.service_id WHERE mr.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $title = $request['title']; $service_title = $request['service_title'] ?? 'إرشاد أكاديمي';
        $status = $request['status']; $created_at = $request['created_at']; $updated_at = $request['updated_at'] ?? $request['created_at'];
        $student_name = $request['student_name']; $student_id_num = $request['student_number'] ?? '—';
        $attachment_file = $request['attachment'] ?? '';
        $details_content = nl2br(htmlspecialchars($request['description'] ?? ''));
    }
} elseif ($type === 'academic_support') {
    $stmt = $pdo->prepare("SELECT asr.*, u.full_name AS student_name, u.student_number FROM academic_support_requests asr JOIN users u ON u.id = asr.user_id WHERE asr.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $title = $request['title']; $service_title = 'الدعم الأكاديمي';
        $status = $request['status']; $created_at = $request['created_at']; $updated_at = $request['updated_at'] ?? $request['created_at'];
        $student_name = $request['student_name']; $student_id_num = $request['student_number'] ?? '—';
        $attachment_file = $request['attachment'] ?? '';
        $details_content = '<strong>نوع المشكلة:</strong> ' . htmlspecialchars($request['issue_type'])
            . (!empty($request['course_name']) ? '<br><strong>المقرر:</strong> ' . htmlspecialchars($request['course_name']) : '')
            . (!empty($request['college']) ? '<br><strong>الكلية:</strong> ' . htmlspecialchars($request['college']) : '')
            . '<br><br>' . nl2br(htmlspecialchars($request['description']));
    }
} elseif ($type === 'talent') {
    $stmt = $pdo->prepare("SELECT * FROM talent_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $title = 'طلب انضمام لوحدة الموهبة'; $service_title = 'وحدة الموهبة والابتكار';
        $status = $request['status']; $created_at = $updated_at = $request['created_at'];
        $student_name = $request['student_name']; $student_id_num = $request['student_id'];
        $details_content = "<strong>الكلية:</strong> " . htmlspecialchars($request['college']) . "<br><strong>نوع الموهبة:</strong> " . htmlspecialchars($request['talent_type']) . "<br><br><strong>نبذة:</strong><br>" . nl2br(htmlspecialchars($request['interests_desc'] ?? ''));
    }
} elseif ($type === 'emergency') {
    $stmt = $pdo->prepare("SELECT * FROM emergency_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $title = 'طلب دعم طارئ'; $service_title = 'وحدة الطوارئ والرعاية السريعة';
        $status = $request['status']; $created_at = $updated_at = $request['created_at'];
        $student_name = $request['student_name']; $student_id_num = $request['student_id'];
        $details_content = "<strong>نوع الحالة:</strong> " . htmlspecialchars($request['case_type']) . "<br><strong>درجة الاستعجال:</strong> " . htmlspecialchars($request['urgency']) . "<br><br><strong>وصف الحالة:</strong><br>" . nl2br(htmlspecialchars($request['reason']));
    }
} elseif ($type === 'reports') {
    $stmt = $pdo->prepare("SELECT sr.*, u.full_name AS uname, u.student_number FROM scr_reports sr JOIN users u ON u.id = sr.user_id WHERE sr.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $title = $request['title']; $service_title = 'وحدة الملاحظات والبلاغات';
        $status = $request['status']; $created_at = $request['created_at']; $updated_at = $request['created_at'];
        $student_name = $request['student_name'] ?? $request['uname']; $student_id_num = $request['student_id'] ?? $request['student_number'] ?? '—';
        $attachment_file = $request['attachment'] ?? '';
        $details_content = "<strong>التصنيف:</strong> " . htmlspecialchars($request['category']) . "<br><br><strong>تفاصيل البلاغ:</strong><br>" . nl2br(htmlspecialchars($request['description']));
    }
} elseif ($type === 'career') {
    $stmt = $pdo->prepare("SELECT * FROM career_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $title = 'استشارة مهنية: ' . htmlspecialchars($request['service_type']); $service_title = 'وحدة الإرشاد المهني';
        $status = $request['status']; $created_at = $request['created_at']; $updated_at = $request['updated_at'] ?? $request['created_at'];
        $student_name = $request['student_name']; $student_id_num = $request['student_id'];
        $details_content = "<strong>نوع الخدمة:</strong> " . htmlspecialchars($request['service_type']) . "<br><strong>طريقة الخدمة:</strong> " . htmlspecialchars($request['service_method'])
            . (!empty($request['appointment_date']) ? "<br><strong>تاريخ الموعد:</strong> " . htmlspecialchars($request['appointment_date']) . ' ' . htmlspecialchars($request['appointment_time'] ?? '') : '')
            . "<br><br><strong>ملاحظات:</strong><br>" . nl2br(htmlspecialchars($request['notes'] ?? ''));
    }
} elseif ($type === 'special_needs') {
    $stmt = $pdo->prepare("SELECT sn.*, u.full_name AS student_name, u.student_number FROM special_needs_requests sn JOIN users u ON u.id = sn.user_id WHERE sn.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $categoryLabel = $request['category_type'] === 'disability' ? 'ذوي الإعاقة' : 'ذوي الاحتياجات الخاصة';
        $title = $categoryLabel . ': ' . htmlspecialchars($request['disability_type']); $service_title = 'وحدة ذوي الاحتياجات الخاصة';
        $status = $request['status']; $created_at = $updated_at = $request['created_at'];
        $student_name = $request['student_name']; $student_id_num = $request['student_number'] ?? '—';
        $attachment_file = $request['medical_report'] ?? '';
        $details_content = "<strong>التصنيف:</strong> " . htmlspecialchars($categoryLabel) . "<br><strong>النوع:</strong> " . htmlspecialchars($request['disability_type']) . "<br><br><strong>الوصف:</strong><br>" . nl2br(htmlspecialchars($request['description']));
    }
} elseif ($type === 'psg_appointments') {
    $stmt = $pdo->prepare("SELECT pa.*, u.full_name AS student_name, u.student_number FROM psg_appointments pa JOIN users u ON u.id = pa.user_id WHERE pa.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $title = 'موعد إرشاد نفسي واجتماعي: ' . htmlspecialchars($request['topic']); $service_title = 'الإرشاد النفسي والاجتماعي';
        $status = $request['status']; $created_at = $updated_at = $request['created_at'];
        $student_name = $request['student_name']; $student_id_num = $request['student_number'] ?? '—';
        $details_content = "<strong>الموضوع:</strong> " . htmlspecialchars($request['topic'])
            . (!empty($request['type']) ? "<br><strong>نوع الجلسة:</strong> " . htmlspecialchars($request['type']) : '')
            . (!empty($request['appointment_day']) ? "<br><strong>التاريخ:</strong> " . htmlspecialchars($request['appointment_day']) . '/' . htmlspecialchars($request['appointment_month'] ?? '') . '/' . htmlspecialchars($request['appointment_year'] ?? '') : '')
            . "<br><br><strong>تفاصيل:</strong><br>" . nl2br(htmlspecialchars($request['details'] ?? $request['notes'] ?? ''));
    }
} elseif ($type === 'psg_messages') {
    $stmt = $pdo->prepare("SELECT pm.*, u.full_name AS student_name, u.student_number FROM psg_messages pm JOIN users u ON u.id = pm.user_id WHERE pm.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $title = 'رسالة إرشاد نفسي واجتماعي: ' . htmlspecialchars($request['subject']); $service_title = 'الإرشاد النفسي والاجتماعي';
        $status = $request['status']; $created_at = $updated_at = $request['created_at'];
        $student_name = $request['student_name']; $student_id_num = $request['student_number'] ?? '—';
        $details_content = "<strong>الموضوع:</strong> " . htmlspecialchars($request['subject']) . "<br><br><strong>الرسالة:</strong><br>" . nl2br(htmlspecialchars($request['message']));
    }
}

if (!$request) {
    header('Location: requests.php');
    exit;
}

/* ── جلب الردود السابقة ── */
try {
    if ($type === 'academic') {
        $stmtR = $pdo->prepare("SELECT rr.*, u.full_name, u.profile_image, ut.title AS user_type FROM request_replies rr JOIN users u ON u.id = rr.user_id JOIN user_types ut ON ut.id = u.user_type_id WHERE rr.request_id = ? ORDER BY rr.created_at ASC");
        $stmtR->execute([$requestId]);
    } else {
        $stmtR = $pdo->prepare("SELECT urr.*, u.full_name, u.profile_image, 'admin' AS user_type FROM unit_request_replies urr JOIN users u ON u.id = urr.user_id WHERE urr.request_id = ? AND urr.request_type = ? ORDER BY urr.created_at ASC");
        $stmtR->execute([$requestId, $type]);
    }
    $replies = $stmtR->fetchAll();
} catch (PDOException $e) {
    $replies = [];
}

$pageTitle = 'تفاصيل الطلب #' . $requestId;
$activePage = 'requests.php';
require_once __DIR__ . '/../includes/header.php';
?>

<nav class="modern-breadcrumb">
    <a href="dashboard.php"><i class="bi bi-house-fill"></i></a>
    <i class="bi bi-chevron-left text-muted small"></i>
    <a href="requests.php">الطلبات</a>
    <i class="bi bi-chevron-left text-muted small"></i>
    <span>طلب #<?= $requestId ?></span>
</nav>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i>تم حفظ التحديث بنجاح.</div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="std-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="fw-bold mb-1"><?= $title ?></h4>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <span class="badge bg-primary-soft text-primary"><?= htmlspecialchars($service_title) ?></span>
                        <?= requestStatusLabel($status) ?>
                    </div>
                </div>
                <span class="text-muted small"><?= formatDateTime($created_at) ?></span>
            </div>
            <div class="p-3 bg-light rounded-3">
                <p class="mb-0" style="line-height:1.8"><?= $details_content ?></p>
            </div>
            <?php if (!empty($attachment_file)): ?>
                <div class="mt-3">
                    <a href="/assets/uploads/<?= htmlspecialchars($attachment_file) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-paperclip me-1"></i>عرض المرفق
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="std-card p-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-chat-left-text me-2 text-primary"></i>الردود (<?= count($replies) ?>)</h5>
            <?php if (empty($replies)): ?>
                <div class="text-center py-4 text-muted"><i class="bi bi-chat-dots fs-2 d-block mb-2 opacity-25"></i>لا توجد ردود بعد.</div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                <?php foreach ($replies as $reply): $isAdmin = ($reply['user_type'] !== 'student'); ?>
                    <div class="d-flex gap-3 <?= $isAdmin ? 'flex-row-reverse' : '' ?>">
                        <img src="<?= getUserAvatar($reply['profile_image'] ?? null) ?>" alt="" class="rounded-circle flex-shrink-0" style="width:42px;height:42px;object-fit:cover">
                        <div class="flex-grow-1 <?= $isAdmin ? 'text-end' : '' ?>">
                            <div class="d-flex align-items-center gap-2 mb-1 <?= $isAdmin ? 'justify-content-end' : '' ?>">
                                <strong class="small"><?= htmlspecialchars($reply['full_name']) ?></strong>
                                <span class="badge <?= $isAdmin ? 'bg-primary' : 'bg-secondary' ?> small"><?= $isAdmin ? 'إداري' : 'طالب' ?></span>
                                <span class="text-muted" style="font-size:.72rem"><?= formatDateTime($reply['created_at']) ?></span>
                            </div>
                            <div class="p-3 rounded-3 d-inline-block text-start" style="max-width:90%;<?= $isAdmin ? 'background:linear-gradient(135deg,#1a5f7a,#159895);color:#fff' : 'background:#f8f9fa;border:1px solid #e9ecef' ?>">
                                <p class="mb-0 small" style="white-space:pre-wrap"><?= htmlspecialchars($reply['reply_message']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="std-card p-4 mb-3">
            <h6 class="fw-bold mb-3"><i class="bi bi-person me-2 text-primary"></i>معلومات الطالب</h6>
            <div class="d-flex flex-column gap-2 small">
                <div><span class="text-muted">الاسم:</span> <strong><?= htmlspecialchars($student_name) ?></strong></div>
                <?php if (!empty($student_id_num) && $student_id_num !== '—'): ?>
                <div><span class="text-muted">الرقم الجامعي:</span> <?= htmlspecialchars($student_id_num) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="std-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-reply-fill me-2 text-primary"></i>الرد وتحديث الحالة</h6>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="update_request">

                <label class="form-label small fw-bold">حالة الطلب</label>
                <select name="status" class="form-select form-select-sm mb-3">
                    <?php foreach ($statusOptions[$type] as $val => $label): ?>
                        <option value="<?= htmlspecialchars($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="form-label small fw-bold">الرد الرسمي</label>
                <textarea name="reply_message" class="form-control form-control-sm mb-3" rows="4" placeholder="اكتب ردك هنا ليصل للطالب..."></textarea>

                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-send me-1"></i>حفظ وإرسال</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
