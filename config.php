<?php
// 데이터베이스 설정 및 공통 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'news_portal';
$db_port = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

// 데이터베이스 연결
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

if (!$conn) {
    // localhost 시도
    $conn = @mysqli_connect('localhost', $db_user, $db_pass, $db_name);
}

if (!$conn) {
    // 연결 실패 시 안내 메시지
    die("데이터베이스 연결 실패: " . mysqli_connect_error());
}

// 문자 인코딩 설정
mysqli_set_charset($conn, "utf8mb4");
?>

