<?php
require_once __DIR__ . '/../config.php';

// [3단계 취약점: 요청 위조]
// 최고 관리자 세션이 있는 상태에서 호출되면 대상 사용자의 등급을 즉시 변경함
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("접근 거부: 최고 관리자 권한이 필요합니다.");
}

$target_user = $_GET['user_id'] ?? $_POST['user_id'] ?? '';
$new_role = $_GET['role'] ?? $_POST['role'] ?? 'admin';

if (!empty($target_user)) {
    $safe_user = mysqli_real_escape_string($conn, $target_user);
    $safe_role = mysqli_real_escape_string($conn, $new_role);

    $sql = "UPDATE users SET role = '$safe_role' WHERE username = '$safe_user' OR id = '$safe_user'";
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('회원 [$target_user] 님의 권한이 [$new_role] (으)로 변경되었습니다.'); location.href='/admin/check_article.php';</script>";
    } else {
        echo "권한 변경 실패: " . mysqli_error($conn);
    }
} else {
    echo "변경할 대상 사용자 아이디를 지정하세요.";
}
?>
