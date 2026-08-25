<?php
require_once __DIR__ . '/../config.php';

// 최고 관리자 세션 검증
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("접근 거부: 최고 관리자 권한이 필요합니다.");
}

if (SECURE_MODE) {
    // ── 🟢 [보안 강화 모드]: CSRF 방어 ───────────────────────
    // 1. POST 방식만 허용 (GET 요청 차단)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die("접근 거부: 안전하지 않은 요청 방식(GET)입니다. POST 요청만 허용됩니다 (CSRF 방어).");
    }

    // 2. Anti-CSRF Token 검증
    $submitted_token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';

    if (empty($submitted_token) || !hash_equals($session_token, $submitted_token)) {
        http_response_code(403);
        die("접근 거부: 유효하지 않은 요청(CSRF 토큰 불일치)입니다.");
    }
}

$target_user = $_POST['user_id'] ?? $_GET['user_id'] ?? '';
$new_role = $_POST['role'] ?? $_GET['role'] ?? 'admin';

if (!empty($target_user)) {
    // 허용된 역할(role)만 검증
    $allowed_roles = ['user', 'reporter', 'admin'];
    if (!in_array($new_role, $allowed_roles)) {
        $new_role = 'user';
    }

    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE username = ? OR id = ?");
    $stmt->bind_param("sss", $new_role, $target_user, $target_user);

    if ($stmt->execute()) {
        $target_user_safe = htmlspecialchars($target_user);
        $new_role_safe = htmlspecialchars($new_role);
        echo "<script>alert('회원 [{$target_user_safe}] 님의 권한이 [{$new_role_safe}] (으)로 변경되었습니다.'); location.href='/admin/check_article.php';</script>";
    } else {
        echo "권한 변경 실패: " . htmlspecialchars($conn->error);
    }
} else {
    echo "변경할 대상 사용자 아이디를 지정하세요.";
}
?>
