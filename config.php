<?php
// 출력 버퍼링 활성화 (리다이렉트 헤더 오류 방지)
if (!ob_get_level()) {
    ob_start();
}

// 문자셋 헤더 전송
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

// 데이터베이스 설정 및 공통 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 에러 보고 수준 설정
mysqli_report(MYSQLI_REPORT_OFF);

$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'news_portal';
$db_port = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

$conn = null;

// 연결 시도 목록 (환경변수 -> root -> news_user -> unix socket)
$attempts = [
    ['host' => $db_host, 'user' => $db_user, 'pass' => $db_pass, 'port' => $db_port],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'port' => 3306],
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'port' => 3306],
    ['host' => '127.0.0.1', 'user' => 'news_user', 'pass' => 'news_pass', 'port' => 3306],
    ['host' => 'localhost', 'user' => 'news_user', 'pass' => 'news_pass', 'port' => 3306],
    ['host' => 'localhost:/var/run/mysqld/mysqld.sock', 'user' => 'root', 'pass' => '', 'port' => 3306],
];

foreach ($attempts as $attempt) {
    try {
        $test_conn = @mysqli_connect(
            $attempt['host'],
            $attempt['user'],
            $attempt['pass'],
            $db_name,
            $attempt['port']
        );
        if ($test_conn && !mysqli_connect_errno()) {
            $conn = $test_conn;
            break;
        }
    } catch (Throwable $e) {
        continue;
    }
}

if (!$conn) {
    die("데이터베이스 연결 실패: MariaDB 서비스를 시작 중이거나 접근 권한을 확인해주세요.");
}

// 문자 인코딩 완전 일치 설정 (UTF-8)
mysqli_set_charset($conn, "utf8mb4");
@mysqli_query($conn, "SET NAMES 'utf8mb4'");
@mysqli_query($conn, "SET CHARACTER SET utf8mb4");
@mysqli_query($conn, "SET character_set_connection=utf8mb4");
@mysqli_query($conn, "SET character_set_results=utf8mb4");
@mysqli_query($conn, "SET character_set_client=utf8mb4");
?>
