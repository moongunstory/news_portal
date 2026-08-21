<?php
require_once __DIR__ . '/header.php';

// 검색어 및 카테고리 필터링
$cat_id = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
$keyword = $_GET['keyword'] ?? '';

// 기사 조회 쿼리 구성
$query = "SELECT a.*, c.name AS category_name, u.name AS author_name 
          FROM articles a 
          JOIN categories c ON a.category_id = c.id 
          JOIN users u ON a.author_id = u.id 
          WHERE a.status = 'approved'";

if ($cat_id > 0) {
    $query .= " AND a.category_id = " . $cat_id;
}

if (!empty($keyword)) {
    $safe_keyword = mysqli_real_escape_string($conn, $keyword);
    $query .= " AND (a.title LIKE '%$safe_keyword%' OR a.content LIKE '%$safe_keyword%')";
}

$query .= " ORDER BY a.id DESC LIMIT 10";
$result = mysqli_query($conn, $query);

// 헤드라인용 첫 기사 및 서브 기사 분리
$articles = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $articles[] = $row;
    }
}
$headline = !empty($articles) ? $articles[0] : null;
$sub_articles = array_slice($articles, 1);

// 랭킹 뉴스 5건
$rank_query = "SELECT id, title, views FROM articles WHERE status = 'approved' ORDER BY views DESC LIMIT 5";
$rank_result = mysqli_query($conn, $rank_query);
?>

<div class="content-left">
    <!-- 상단 배너 광고 (Subnet Duel) -->
    <a href="https://subnet-duel.onrender.com/" target="_blank" rel="noopener noreferrer" class="ad-banner-top">
        <div class="ad-banner-content">
            <div class="ad-banner-title">
                <span class="ad-badge">AD</span>
                <span>⚔️ <span class="highlight">Subnet Duel</span> - 실시간 서브넷 계산 배틀</span>
            </div>
            <div class="ad-banner-desc">IP/CIDR을 보고 서브넷 마스크, IP 범위, 게이트웨이를 10초 안에 계산하라! (10문 스피드런)</div>
        </div>
        <div class="ad-banner-btn">배틀 시작하기 ➔</div>
    </a>

    <?php if ($headline): ?>
    <div class="section-box">
        <div class="section-title">
            <span>주요 헤드라인 뉴스</span>
            <span style="font-size:12px; color:#888; font-weight:normal;">실시간 속보</span>
        </div>
        
        <div class="headline-card">
            <div class="headline-thumb">
                <span>[보도 사진]</span>
            </div>
            <div class="headline-info">
                <a href="/view.php?id=<?php echo $headline['id']; ?>">
                    <h2 class="headline-title"><?php echo htmlspecialchars($headline['title']); ?></h2>
                </a>
                <p class="headline-summary"><?php echo htmlspecialchars($headline['summary'] ?? ''); ?></p>
                <div class="news-meta">
                    <span><?php echo htmlspecialchars($headline['category_name']); ?></span> · 
                    <span><?php echo htmlspecialchars($headline['author_name']); ?> 기자</span> · 
                    <span>조회 <?php echo $headline['views']; ?></span>
                </div>
            </div>
        </div>

        <ul class="news-list">
            <?php foreach ($sub_articles as $art): ?>
            <li class="news-item">
                <a href="/view.php?id=<?php echo $art['id']; ?>" style="font-weight: 500;">
                    <?php echo htmlspecialchars($art['title']); ?>
                </a>
                <span class="news-meta"><?php echo htmlspecialchars($art['category_name']); ?> · <?php echo date('H:i', strtotime($art['created_at'])); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <div class="section-box">
        <p style="padding: 20px; text-align: center; color: #888;">등록된 기사가 없습니다.</p>
    </div>
    <?php endif; ?>
</div>

<div class="content-right">
    <!-- 로그인 박스 (비로그인 시) -->
    <?php if (!$is_logged_in): ?>
    <div class="section-box" style="text-align: center;">
        <p style="font-size: 14px; margin-bottom: 12px; color: #555;">로그인하고 맞춤 뉴스와 댓글 기능을 이용하세요.</p>
        <a href="/login.php" class="btn-primary" style="display:block; text-align:center; padding:12px 0;">로그인</a>
        <div style="margin-top: 10px; font-size: 12px;">
            <a href="/register.php">회원가입</a> | <a href="/login.php">기자/관리자 로그인</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- 가장 많이 본 뉴스 (랭킹) -->
    <div class="section-box">
        <div class="section-title">
            <span>가장 많이 본 뉴스</span>
            <span style="font-size:12px; color:#03c75a;">LIVE</span>
        </div>
        <ul class="ranking-list">
            <?php 
            $rank = 1;
            if ($rank_result) {
                while ($r = mysqli_fetch_assoc($rank_result)): 
            ?>
            <li class="ranking-item">
                <span class="ranking-num"><?php echo $rank++; ?></span>
                <a href="/view.php?id=<?php echo $r['id']; ?>" style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <?php echo htmlspecialchars($r['title']); ?>
                </a>
            </li>
            <?php 
                endwhile; 
            }
            ?>
        </ul>
    </div>

    <!-- 스폰서 배너 광고 (Subnet Duel 사이드바 카드) -->
    <a href="https://subnet-duel.onrender.com/" target="_blank" rel="noopener noreferrer" class="ad-card-sidebar">
        <span class="ad-card-badge">SPONSORED AD</span>
        <div class="ad-card-title">⚔️ Subnet Duel</div>
        <div class="ad-card-desc">서브넷팅의 절대 고수를 가린다! 주어진 IP/CIDR을 빠르게 계산하는 실시간 1:1 서브넷 대결 연습장</div>
        <div class="ad-tags">
            <span class="ad-tag">#서브넷마스터</span>
            <span class="ad-tag">#10문_스피드런</span>
            <span class="ad-tag">#네트워크</span>
        </div>
        <div class="ad-cta">⚡ 지금 바로 무료 도전하기 →</div>
    </a>

    <!-- 오늘의 사설 & 오피니언 (세로드립 힌트 기사 노출) -->
    <div class="section-box">
        <div class="section-title">
            <span>오늘의 사설/오피니언</span>
        </div>
        <div style="font-size: 13px; line-height: 1.6; color: #444;">
            <p><strong>[사설] 디지털 인프라 전환과 정보 보안 체계</strong></p>
            <p style="margin-top:6px; color:#777;">보안 체계의 본질과 시스템 접근성에 대한 깊이 있는 분석 제언...</p>
            <a href="/view.php?id=3" style="color: #03c75a; font-weight: bold; font-size: 12px; display: inline-block; margin-top: 8px;">본문 전체 읽기 →</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
