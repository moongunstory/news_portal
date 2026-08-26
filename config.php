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

// CSRF 토큰 초기화 (세션에 없으면 생성)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
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

// ── 보안 모드 및 계정 잠금 테이블/컬럼 자동 생성 ─────────────────
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS site_settings (
    id INT PRIMARY KEY,
    secure_mode TINYINT(1) DEFAULT 0
)");

// 기본 레코드 없을 시 삽입
@mysqli_query($conn, "INSERT IGNORE INTO site_settings (id, secure_mode) VALUES (1, 0)");

// users 테이블에 login_fail_count, lockout_time 컬럼 추가 (없을 경우)
$chk_fail = @mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'login_fail_count'");
if ($chk_fail && mysqli_num_rows($chk_fail) == 0) {
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN login_fail_count INT DEFAULT 0 AFTER age_group");
    @mysqli_query($conn, "ALTER TABLE users ADD COLUMN lockout_time DATETIME DEFAULT NULL AFTER login_fail_count");
}

// ── 보안 모드(SECURE_MODE) 상수 정의 ───────────────────────────
$mode_query = @mysqli_query($conn, "SELECT secure_mode FROM site_settings WHERE id = 1");
$mode_row = $mode_query ? mysqli_fetch_assoc($mode_query) : null;
define('SECURE_MODE', ($mode_row && (int)$mode_row['secure_mode'] === 1));

// ── 보안 모드 ON 시: HTML 주석 자동 제거 (소스코드 노출 방지) ────
if (SECURE_MODE) {
    ob_start(function($buffer) {
        // HTML 주석 제거 (<!-- ... --> 패턴, 멀티라인 포함)
        return preg_replace('/<!--.*?-->/s', '', $buffer);
    });
}
?>
