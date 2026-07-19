<?php
/**
 * جلسات الدخول والصلاحيات — يعتمد على users.user_type_id / user_types.title
 * القيم المخزنة في الجلسة: user_id, user_type (title), full_name, email, profile_image
 */

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function getUserId(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

function getUserType(): string
{
    return $_SESSION['user_type'] ?? '';
}

function redirectToLogin(): never
{
    header('Location: /login.php');
    exit;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirectToLogin();
    }
}

/** @param string[] $allowedTypes */
function requireRole(array $allowedTypes): void
{
    requireLogin();
    if (!in_array(getUserType(), $allowedTypes, true)) {
        require_once __DIR__ . '/error_page.php';
        renderErrorPage(
            403,
            'غير مصرح لك بالدخول',
            'حسابك الحالي لا يملك صلاحية الوصول لهذه الصفحة.',
            loginRedirectPath(getUserType()),
            'الذهاب للوحتي'
        );
    }
}

function requireStudent(): void
{
    requireRole(['student']);
}

function requireCareGuidanceAdmin(): void
{
    requireRole(['care_guidance_admin']);
}

function loginUser(PDO $pdo, string $email, string $password): bool
{
    $stmt = $pdo->prepare("
        SELECT u.*, ut.title AS user_type_title
        FROM users u
        JOIN user_types ut ON ut.id = u.user_type_id
        WHERE u.email = ? AND u.status = 'active'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_type']     = $user['user_type_title'];
    $_SESSION['full_name']     = $user['full_name'];
    $_SESSION['email']         = $user['email'];
    $_SESSION['profile_image'] = $user['profile_image'];

    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

    return true;
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function loginRedirectPath(string $userType): string
{
    return match ($userType) {
        'care_guidance_admin' => '/care_guidance_admin/dashboard.php',
        'unit_supervisor'     => '/supervisor/dashboard.php',
        'student'             => '/student/dashboard.php',
        default               => '/login.php',
    };
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
