<?php
// 데이터베이스 설정 및 공통 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // 로키 리눅스 MySQL 비밀번호 설정에 맞게 수정
$db_name = 'news_portal';

// 데이터베이스 연결
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    // 연결 실패 시 안내 메시지
    die("데이터베이스 연결 실패: " . mysqli_connect_error());
}

// 문자 인코딩 설정
mysqli_set_charset($conn, "utf8mb4");
?>
