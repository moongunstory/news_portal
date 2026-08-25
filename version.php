<?php
echo "<h2>🔧 News Portal - 서버 버전 정보</h2>";
echo "<hr>";

echo "<h3>PHP Version:</h3>" . phpversion() . "<br>";
echo "<h3>Apache Version:</h3>" . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// MariaDB 버전 확인 (docker-entrypoint.sh 기준 접속 정보)
$host = '127.0.0.1';
$user = 'root';
$pass = '';

$link = @mysqli_connect($host, $user, $pass);
if (!$link) {
    // news_user 계정으로 재시도
    $link = @mysqli_connect($host, 'news_user', 'news_pass', 'news_portal');
}

if ($link) {
    echo "<h3>MariaDB Version:</h3>" . mysqli_get_server_info($link) . "<br>";
    mysqli_close($link);
} else {
    echo "<h3>MariaDB Connection Failed:</h3>" . mysqli_connect_error() . "<br>";
}

echo "<hr>";
echo "<small>확인 후 이 파일을 삭제하세요.</small>";
?>
