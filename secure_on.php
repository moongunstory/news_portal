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

// ── 보안 모드 ON (1) 설정 ──────────────────────────────────────
@mysqli_query($conn, "UPDATE site_settings SET secure_mode = 1 WHERE id = 1");

// 잠금된 계정들 잠금 해제 초기화
@mysqli_query($conn, "UPDATE users SET login_fail_count = 0, lockout_time = NULL");

// ── 업로드 디렉터리에 .htaccess 생성 (스크립트 실행 차단) ─────────
$uploads_dir = __DIR__ . '/uploads';
if (!is_dir($uploads_dir)) {
    @mkdir($uploads_dir, 0777, true);
}
$htaccess_content = "# 보안 모드: 업로드 디렉터리 내 PHP 스크립트 실행 전면 차단\n"
                  . "<FilesMatch \"\\.(php|php3|php4|php5|phtml|phps|inc|cgi|pl|py|sh|bash)$\">\n"
                  . "    Order Deny,Allow\n"
                  . "    Deny from all\n"
                  . "</FilesMatch>\n"
                  . "Options -Indexes -ExecCGI\n"
                  . "php_flag engine off\n"
                  . "AddType text/plain .php .phtml\n";
@file_put_contents($uploads_dir . '/.htaccess', $htaccess_content);

// ── 루트 디렉터리의 .htaccess 제거 (Apache 기본 -Indexes로 복귀 → 디렉토리 리스팅 차단) ──
$root_htaccess = __DIR__ . '/.htaccess';
if (file_exists($root_htaccess)) {
    @unlink($root_htaccess);
}

$redirect = $_GET['redirect'] ?? '/index.php';
if (!is_string($redirect) || substr($redirect, 0, 1) !== '/' || substr($redirect, 0, 2) === '//') {
    $redirect = '/index.php';
}

$alert_msg = "🟢 [보안 강화 모드 (Secure Mode) ON]\n\n"
           . "1. SQL Injection 차단 (Prepared Statement 적용)\n"
           . "2. 브루트포스 차단 (5회 실패 시 10분 잠금 + CAPTCHA 적용)\n"
           . "3. 비밀번호 복잡도 강화 (8자리 이상 영문+숫자+특수문자)\n"
           . "4. Stored XSS 차단 (출력값 htmlspecialchars 필터링)\n"
           . "5. CSRF 차단 (Anti-CSRF 토큰 검증 + POST 강제)\n"
           . "6. 웹쉘 업로드 차단 (MIME 검사, 이미지 재생성, 실행권한 제거)\n"
           . "7. LFI 차단 (화이트리스트 파일 인클루전 제한)\n"
           . "8. 디렉토리 리스팅 차단 (Options -Indexes 적용)\n"
           . "9. 소스코드 주석 제거 (HTML 주석 자동 스트리핑)";

echo "<script>alert(" . json_encode($alert_msg) . "); location.href=" . json_encode($redirect) . ";</script>";
exit;
