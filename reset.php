<?php
require_once __DIR__ . '/config.php';

// ── 비밀 토큰 검증 ──────────────────────────────────────────────
// 토큰을 모르면 일반 404처럼 보이게 처리
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

// ── 1. reporter01 계정 권한/비밀번호 초기화 ──────────────────────
mysqli_query($conn, "UPDATE users SET role = 'reporter', password = '2026' WHERE username = 'reporter01'");

// ── 2. 최고관리자 권한 유지 확인 ─────────────────────────────────
mysqli_query($conn, "UPDATE users SET role = 'admin' WHERE username = 'admin_master'");

// ── 3. reporter01이 작성한 기사 전체 삭제 (pending + approved) ───
$r = mysqli_query($conn, "SELECT id FROM users WHERE username = 'reporter01'");
if ($r && $row = mysqli_fetch_assoc($r)) {
    $reporter_id = (int)$row['id'];
    // 댓글 반응 먼저 삭제 (외래키 오류 방지)
    mysqli_query($conn, "DELETE FROM comment_reactions WHERE comment_id IN (SELECT id FROM comments WHERE article_id IN (SELECT id FROM articles WHERE author_id = $reporter_id))");
    // 댓글 삭제
    mysqli_query($conn, "DELETE FROM comments WHERE article_id IN (SELECT id FROM articles WHERE author_id = $reporter_id)");
    // 기사 삭제
    mysqli_query($conn, "DELETE FROM articles WHERE author_id = $reporter_id");
}

// ── 4. 세션 role 즉시 갱신 ──────────────────────────────────────
if (isset($_SESSION['username']) && $_SESSION['username'] === 'reporter01') {
    $_SESSION['role'] = 'reporter';
}

// ── 5. 리다이렉트 (내부 URL만 허용) ─────────────────────────────
$redirect = $_GET['redirect'] ?? '/index.php';
if (!is_string($redirect) || substr($redirect, 0, 1) !== '/' || substr($redirect, 0, 2) === '//') {
    $redirect = '/index.php';
}

$alert_msg = "실습 초기화 완료\n\n박기자(reporter01) 권한: [기자]로 복구\n비밀번호: 2026 으로 초기화\n실습 기사 전체 삭제 (pending + approved)";
echo "<script>alert(" . json_encode($alert_msg) . "); location.href=" . json_encode($redirect) . ";</script>";
exit;
?>
