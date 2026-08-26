<?php
require_once __DIR__ . '/../config.php';

// ── 보안 모드 (SECURE_MODE = true) 인 경우: 디렉토리 리스팅 전면 차단 (403 Forbidden) ──
if (defined('SECURE_MODE') && SECURE_MODE) {
    http_response_code(403);
    echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">' . "\n";
    echo '<html><head>' . "\n";
    echo '<title>403 Forbidden</title>' . "\n";
    echo '</head><body>' . "\n";
    echo '<h1>Forbidden</h1>' . "\n";
    echo '<p>You don\'t have permission to access this resource.</p>' . "\n";
    echo '<hr>' . "\n";
    echo '<address>Apache/2.4.56 Server at ' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . ' Port 80</address>' . "\n";
    echo '</body></html>';
    exit;
}

// ── 취약 실습 모드 (SECURE_MODE = false) 인 경우: Apache 스타일 디렉토리 리스팅 제공 ──
$current_dir = __DIR__;
$files = scandir($current_dir);
$file_list = [];

foreach ($files as $file) {
    // 현재/부모 디렉토리 및 index.php 자체, .htaccess 등 숨김 파일 제외
    if ($file === '.' || $file === 'index.php' || $file === '.htaccess') {
        continue;
    }
    
    $full_path = $current_dir . '/' . $file;
    $is_dir = is_dir($full_path);
    $size = $is_dir ? '-' : filesize($full_path);
    $mtime = date('Y-m-d H:i', filemtime($full_path));
    
    $file_list[] = [
        'name' => $file . ($is_dir ? '/' : ''),
        'url' => rawurlencode($file) . ($is_dir ? '/' : ''),
        'mtime' => $mtime,
        'size' => $size,
        'is_dir' => $is_dir
    ];
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
    <meta charset="UTF-8">
    <title>Index of /secret_backup/</title>
    <style>
        body { font-family: monospace; padding: 20px; background-color: #fff; color: #000; }
        h1 { font-size: 1.5em; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; max-width: 900px; }
        th, td { text-align: left; padding: 4px 8px; }
        th { border-bottom: 1px solid #000; }
        a { text-decoration: none; color: #0000ee; }
        a:hover { text-decoration: underline; }
        .footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 0.9em; color: #555; }
    </style>
</head>
<body>
    <h1>Index of /secret_backup/</h1>
    <table>
        <tr>
            <th>Name</th>
            <th>Last modified</th>
            <th>Size</th>
            <th>Description</th>
        </tr>
        <tr>
            <td><a href="/">Parent Directory</a></td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
        </tr>
        <?php foreach ($file_list as $f): ?>
        <tr>
            <td><a href="<?php echo $f['url']; ?>"><?php echo htmlspecialchars($f['name']); ?></a></td>
            <td><?php echo $f['mtime']; ?></td>
            <td><?php echo is_numeric($f['size']) ? number_format($f['size']) . ' B' : $f['size']; ?></td>
            <td>-</td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div class="footer">
        Apache/2.4.56 Server at <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost'); ?> Port 80
    </div>
</body>
</html>