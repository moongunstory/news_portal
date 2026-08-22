<?php
// 데이터베이스 설정 및 공통 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PHP 8.1+ 예외 크래시 방지
mysqli_report(MYSQLI_REPORT_OFF);

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'news_portal';
$db_port = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

// 1차: 환경변수/기본 설정으로 연결 시도
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

// 2차: localhost root 연결 시도
if (!$conn) {
    $conn = @mysqli_connect('localhost', 'root', '', $db_name);
}

// 3차: 전용 계정 news_user 연결 시도 (Docker 컨테이너 대비)
if (!$conn) {
    $conn = @mysqli_connect('127.0.0.1', 'news_user', 'news_pass', $db_name);
}
if (!$conn) {
    $conn = @mysqli_connect('localhost', 'news_user', 'news_pass', $db_name);
}

if (!$conn) {
    die("데이터베이스 연결 실패: " . mysqli_connect_error());
}

// 문자 인코딩 설정
mysqli_set_charset($conn, "utf8mb4");
?>

