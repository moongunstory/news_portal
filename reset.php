<?php
require_once __DIR__ . '/config.php';

// 1. 박기자(reporter01) 계정 권한을 '기자(reporter)'로 복구 및 기본 비밀번호 설정
$reset_user_sql = "UPDATE users SET role = 'reporter', password = '2026' WHERE username = 'reporter01'";
mysqli_query($conn, $reset_user_sql);

// 2. 최고관리자 계정 권한 유지 확인
mysqli_query($conn, "UPDATE users SET role = 'admin' WHERE username = 'admin_master'");

// 3. 실습 중 등록된 승인 대기(pending) 기사 모두 삭제 (XSS 스크립트 반복 실행 방지)
$delete_pending_sql = "DELETE FROM articles WHERE status = 'pending'";
mysqli_query($conn, $delete_pending_sql);

// 4. 현재 로그인한 사용자가 reporter01인 경우 세션의 role도 즉시 'reporter'로 갱신
if (isset($_SESSION['username']) && $_SESSION['username'] === 'reporter01') {
    $_SESSION['role'] = 'reporter';
}

// 5. 리다이렉트 경로 결정 (내부 URL만 허용)
$redirect = $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '/index.php';
if (!is_string($redirect) || substr($redirect, 0, 1) !== '/' || substr($redirect, 0, 2) === '//') {
    $redirect = '/index.php';
}

$alert_msg = "박기자(reporter01) 계정 권한이 [기자]로 초기화되고, 승인 대기 기사가 정리되었습니다.";
echo "<script>alert(" . json_encode($alert_msg) . "); location.href=" . json_encode($redirect) . ";</script>";
exit;
?>
