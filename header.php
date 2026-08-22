<?php
require_once __DIR__ . '/config.php';
$is_logged_in = isset($_SESSION['user_id']);
$current_role = $_SESSION['role'] ?? 'guest';
$current_name = $_SESSION['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>데일리 뉴스 포털</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header class="header-top">
    <div class="container top-bar">
        <div class="logo-area">
            <a href="/index.php" class="logo">DAILY NEWS</a>
            <span class="logo-sub">뉴스 홈</span>
        </div>

        <form action="/index.php" method="GET" class="search-box">
            <input type="text" name="keyword" placeholder="뉴스 검색 (키워드를 입력하세요)" value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>">
            <button type="submit">검색</button>
        </form>

        <div class="user-nav">
            <a href="/reset.php" onclick="return confirm('박기자(reporter01) 계정 권한을 [기자]로 초기화하고 승인 대기 기사를 삭제하시겠습니까?');" style="background:#e03131; color:#fff; padding:4px 9px; border-radius:4px; font-size:12px; font-weight:bold; text-decoration:none; display:inline-flex; align-items:center; gap:4px; margin-right:4px;" title="박기자 권한 및 대기 기사 초기화">
                🔄 실습 초기화
            </a>
            <?php if ($is_logged_in): ?>
                <span class="user-badge"><?php 
                    if ($current_role === 'admin') echo '최고관리자';
                    elseif ($current_role === 'reporter') echo '기자';
                    else echo '일반회원';
                ?></span>
                <strong><?php echo htmlspecialchars($current_name); ?></strong>님
                
                <?php if ($current_role === 'reporter'): ?>
                    <a href="/reporter/write.php" style="color:#03c75a; font-weight:bold;">[기사작성]</a>
                <?php elseif ($current_role === 'admin'): ?>
                    <a href="/admin/check_article.php" style="color:#e03131; font-weight:bold;">[기사승인]</a>
                    <a href="/admin/upload_image.php" style="color:#1c7ed6; font-weight:bold;">[사진업로드]</a>
                    <a href="/admin/stats.php" style="color:#f08c00; font-weight:bold;">[통계보고서]</a>
                <?php endif; ?>

                <a href="/logout.php">로그아웃</a>
            <?php else: ?>
                <a href="/login.php">로그인</a>
                <a href="/register.php">회원가입</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<nav class="main-nav">
    <div class="container">
        <ul class="nav-list">
            <li class="nav-item <?php echo !isset($_GET['cat']) ? 'active' : ''; ?>"><a href="/index.php">홈</a></li>
            <li class="nav-item <?php echo (isset($_GET['cat']) && $_GET['cat'] == '1') ? 'active' : ''; ?>"><a href="/index.php?cat=1">정치</a></li>
            <li class="nav-item <?php echo (isset($_GET['cat']) && $_GET['cat'] == '2') ? 'active' : ''; ?>"><a href="/index.php?cat=2">경제</a></li>
            <li class="nav-item <?php echo (isset($_GET['cat']) && $_GET['cat'] == '3') ? 'active' : ''; ?>"><a href="/index.php?cat=3">사회</a></li>
            <li class="nav-item <?php echo (isset($_GET['cat']) && $_GET['cat'] == '4') ? 'active' : ''; ?>"><a href="/index.php?cat=4">IT/과학</a></li>
            <li class="nav-item <?php echo (isset($_GET['cat']) && $_GET['cat'] == '5') ? 'active' : ''; ?>"><a href="/index.php?cat=5">생활/문화</a></li>
            <li class="nav-item <?php echo (isset($_GET['cat']) && $_GET['cat'] == '6') ? 'active' : ''; ?>"><a href="/index.php?cat=6">오피니언</a></li>
        </ul>
    </div>
</nav>

<div class="container main-content">
