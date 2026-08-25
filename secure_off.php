<?php
require_once __DIR__ . '/config.php';

// ── 비밀 토큰 검증 ──────────────────────────────────────────────
define('RESET_SECRET', 'security2026reset');

$token = $_GET['key'] ?? '';
if ($token !== RESET_SECRET) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body>'
       . '<h1>Not Found</h1>'
       . '<p>The requested URL was not found on this server.</p>'
       . '</body></html>';
    exit;
}

// ── 보안 모드 OFF (0) 설정 ─────────────────────────────────────
@mysqli_query($conn, "UPDATE site_settings SET secure_mode = 0 WHERE id = 1");

// 잠금된 계정들 잠금 해제 초기화
@mysqli_query($conn, "UPDATE users SET login_fail_count = 0, lockout_time = NULL");

// ── 업로드 디렉터리의 .htaccess 제거 (실습 취약점 복원) ───────────
$uploads_htaccess = __DIR__ . '/uploads/.htaccess';
if (file_exists($uploads_htaccess)) {
    @unlink($uploads_htaccess);
}

$redirect = $_GET['redirect'] ?? '/index.php';
if (!is_string($redirect) || substr($redirect, 0, 1) !== '/' || substr($redirect, 0, 2) === '//') {
    $redirect = '/index.php';
}

$alert_msg = "🔴 [취약 실습 모드 (Vulnerable Mode) ON]\n\n"
           . "보안 기능이 해제되어 실습용 취약점들이 활성화되었습니다.\n"
           . "(SQL Injection, 브루트포스, XSS, CSRF, 웹쉘 업로드, LFI 실습 가능)";

echo "<script>alert(" . json_encode($alert_msg) . "); location.href=" . json_encode($redirect) . ";</script>";
exit;
