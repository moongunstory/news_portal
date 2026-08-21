<?php
require_once __DIR__ . '/header.php';

$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sort = isset($_GET['sort']) && $_GET['sort'] === 'latest' ? 'latest' : 'sympathy';

// DB 컬럼 및 테이블 누락 방지 자동 안전장치
$chk_table = mysqli_query($conn, "SHOW TABLES LIKE 'comment_reactions'");
if ($chk_table && mysqli_num_rows($chk_table) == 0) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS comment_reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction_type ENUM('like', 'dislike') NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_comment (comment_id, user_id),
        FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
}

$chk_col = mysqli_query($conn, "SHOW COLUMNS FROM comments LIKE 'like_count'");
if ($chk_col && mysqli_num_rows($chk_col) == 0) {
    mysqli_query($conn, "ALTER TABLE comments ADD COLUMN like_count INT DEFAULT 0 AFTER comment");
    mysqli_query($conn, "ALTER TABLE comments ADD COLUMN dislike_count INT DEFAULT 0 AFTER like_count");
}

$chk_user_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'gender'");
if ($chk_user_col && mysqli_num_rows($chk_user_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN gender ENUM('M', 'F') DEFAULT 'M' AFTER role");
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN age_group ENUM('10s', '20s', '30s', '40s', '50s', '60s') DEFAULT '20s' AFTER gender");
}

// 조회수 증가
mysqli_query($conn, "UPDATE articles SET views = views + 1 WHERE id = $article_id");

// 기사 정보 조회
$query = "SELECT a.*, c.name AS category_name, u.name AS author_name 
          FROM articles a 
          JOIN categories c ON a.category_id = c.id 
          JOIN users u ON a.author_id = u.id 
          WHERE a.id = $article_id AND a.status = 'approved'";
$result = mysqli_query($conn, $query);
$article = mysqli_fetch_assoc($result);

if (!$article) {
    echo "<div class='section-box' style='padding:40px; text-align:center;'>존재하지 않거나 승인 대기 중인 기사입니다.</div>";
    require_once __DIR__ . '/footer.php';
    exit;
}

// 댓글 등록 처리
$comment_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_text'])) {
    if (!$is_logged_in) {
        $comment_msg = '댓글을 작성하려면 먼저 로그인하세요.';
    } else {
        $user_id = $_SESSION['user_id'];
        $comment_text = trim($_POST['comment_text']);
        if (!empty($comment_text)) {
            // 일반 댓글 XSS 방어 처리
            $safe_comment = mysqli_real_escape_string($conn, $comment_text);
            mysqli_query($conn, "INSERT INTO comments (article_id, user_id, comment, like_count, dislike_count) VALUES ($article_id, $user_id, '$safe_comment', 0, 0)");
            
            // 페이지 새로고침하여 댓글 반영
            header("Location: /view.php?id={$article_id}&sort={$sort}#comments");
            exit;
        }
    }
}

// 댓글 목록 조회 (순공감순 vs 최신순)
$order_clause = ($sort === 'latest') ? "c.id DESC" : "(c.like_count - c.dislike_count) DESC, c.id DESC";

$comments_query = "SELECT c.*, u.username, u.name AS user_name, u.role, u.gender, u.age_group 
                   FROM comments c 
                   JOIN users u ON c.user_id = u.id 
                   WHERE c.article_id = $article_id 
                   ORDER BY $order_clause";
$comments_res = mysqli_query($conn, $comments_query);
$total_comments = $comments_res ? mysqli_num_rows($comments_res) : 0;

// 댓글 리스트 배열화 및 성별/연령대 통계 집계
$comments_list = [];
$comment_ids = [];
$male_count = 0;
$female_count = 0;
$age_counts = [
    '10s' => 0,
    '20s' => 0,
    '30s' => 0,
    '40s' => 0,
    '50s' => 0,
    '60s' => 0
];

if ($comments_res && $total_comments > 0) {
    while ($row = mysqli_fetch_assoc($comments_res)) {
        $comments_list[] = $row;
        $comment_ids[] = $row['id'];

        // 성별 집계
        if (($row['gender'] ?? 'M') === 'F') {
            $female_count++;
        } else {
            $male_count++;
        }

        // 연령대 집계
        $ag = $row['age_group'] ?? '20s';
        if (isset($age_counts[$ag])) {
            $age_counts[$ag]++;
        } else {
            $age_counts['20s']++;
        }
    }
}

// 실제 네이버 뉴스 통계 비율 계산 (기본 가중치를 적절히 조합하여 사실적이고 미려한 통계 그래픽 제공)
$stat_male_pct = 50;
$stat_female_pct = 50;
$stat_age_pct = [
    '10s' => 4,
    '20s' => 28,
    '30s' => 38,
    '40s' => 18,
    '50s' => 8,
    '60s' => 4
];

if ($total_comments > 0) {
    // 실제 댓글 데이터 기반 퍼센트 산출
    $stat_male_pct = round(($male_count / $total_comments) * 100);
    $stat_female_pct = 100 - $stat_male_pct;

    $temp_sum = 0;
    foreach ($age_counts as $k => $v) {
        $stat_age_pct[$k] = round(($v / $total_comments) * 100);
        $temp_sum += $stat_age_pct[$k];
    }
    // 합계 100% 보정
    if ($temp_sum > 0 && $temp_sum !== 100) {
        // 가장 큰 연령대에 오차 보정
        arsort($stat_age_pct);
        $max_key = array_key_first($stat_age_pct);
        $stat_age_pct[$max_key] += (100 - $temp_sum);
        // 원래 키 순서대로 재정렬
        $stat_age_pct = [
            '10s' => $stat_age_pct['10s'] ?? 0,
            '20s' => $stat_age_pct['20s'] ?? 0,
            '30s' => $stat_age_pct['30s'] ?? 0,
            '40s' => $stat_age_pct['40s'] ?? 0,
            '50s' => $stat_age_pct['50s'] ?? 0,
            '60s' => $stat_age_pct['60s'] ?? 0,
        ];
    }
} else {
    // 댓글이 없을 때 기사 카테고리/ID 기반 기본 데모 통계
    $seed = ($article_id * 17) % 30;
    $stat_male_pct = 55 + ($seed % 20);
    $stat_female_pct = 100 - $stat_male_pct;
}

// 가장 높은 연령대 찾기 (하이라이트용)
$highest_age = '30s';
$highest_val = -1;
foreach ($stat_age_pct as $ag => $pct) {
    if ($pct > $highest_val) {
        $highest_val = $pct;
        $highest_age = $ag;
    }
}

// 현재 로그인 사용자의 반응 목록 조회
$user_reactions = [];
if ($is_logged_in && !empty($comment_ids)) {
    $c_ids_str = implode(',', array_map('intval', $comment_ids));
    $curr_user_id = intval($_SESSION['user_id']);
    $rxn_res = mysqli_query($conn, "SELECT comment_id, reaction_type FROM comment_reactions WHERE user_id = $curr_user_id AND comment_id IN ($c_ids_str)");
    if ($rxn_res) {
        while ($rx = mysqli_fetch_assoc($rxn_res)) {
            $user_reactions[$rx['comment_id']] = $rx['reaction_type'];
        }
    }
}

// 헬퍼 함수: 아이디 마스킹 (예: reader01 -> read****)
function mask_username($un) {
    if (empty($un)) return 'user****';
    $len = mb_strlen($un, 'utf-8');
    if ($len <= 3) {
        return mb_substr($un, 0, 1, 'utf-8') . '***';
    }
    return mb_substr($un, 0, 4, 'utf-8') . '****';
}

// 헬퍼 함수: 연령대 한글 라벨
function get_age_label($ag) {
    $map = [
        '10s' => '10대',
        '20s' => '20대',
        '30s' => '30대',
        '40s' => '40대',
        '50s' => '50대',
        '60s' => '60대+'
    ];
    return $map[$ag] ?? '20대';
}
?>

<div class="content-left" style="width: 100%;">
    <div class="section-box">
        <!-- 카테고리 정보 -->
        <div style="font-size: 13px; color: #03c75a; font-weight: bold; margin-bottom: 8px;">
            <?php echo htmlspecialchars($article['category_name']); ?>
        </div>

        <!-- 기사 헤더 -->
        <div class="article-header">
            <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="article-info-bar">
                <div>
                    <span><strong><?php echo htmlspecialchars($article['author_name']); ?></strong> 기자</span>
                    <span style="margin: 0 8px;">|</span>
                    <span>입력 <?php echo date('Y.m.d. H:i', strtotime($article['created_at'])); ?></span>
                </div>
                <div>
                    <span>조회 <?php echo number_format($article['views']); ?></span>
                </div>
            </div>
        </div>

        <!-- 기사 본문 -->
        <div class="article-body">
            <?php 
            // 기사 본문은 HTML 허용 렌더링
            echo $article['content']; 
            ?>
        </div>

        <!-- 기사 본문 하단 추천 배너 광고 (Subnet Duel) -->
        <a href="https://subnet-duel.onrender.com/" target="_blank" rel="noopener noreferrer" class="ad-banner-in-article">
            <div>
                <div class="ad-banner-in-article-title">
                    <span class="ad-badge" style="background-color:#0284c7; color:#fff;">AD</span>
                    <span>⚔️ 서브넷팅 계산이 헷갈린다면? <strong>Subnet Duel</strong>에서 실력 점검!</span>
                </div>
                <div class="ad-banner-in-article-desc">마스크, 가용 IP 범위, 브로드캐스트/게이트웨이를 초고속으로 계산하는 실시간 1:1 퀴즈 배틀</div>
            </div>
            <div class="ad-in-article-btn">배틀 도전하기 →</div>
        </a>

        <!-- 기사 저작권 바이라인 -->
        <div style="padding: 16px; background-color: #f8f9fa; border-radius: 4px; font-size: 13px; color: #666; margin-bottom: 30px;">
            <p>※ 저작권자 ⓒ DAILY NEWS. 무단전재 및 재배포 금지</p>
            <p style="margin-top: 4px;">기사 제보 및 보도자료 문의: press@dailynews.local</p>
        </div>

        <!-- 댓글 영역 (네이버 뉴스 스타일) -->
        <div class="comment-section" id="comments">
            <div class="comment-header-wrap">
                <div class="comment-count-title">
                    <span>댓글</span>
                    <span class="comment-count-badge"><?php echo number_format($total_comments); ?></span>
                </div>
                <button type="button" class="stats-toggle-btn" id="btn-toggle-stats" onclick="toggleStats()">
                    <span>📊 참여자 통계 접기</span>
                </button>
            </div>

            <!-- 네이버 뉴스 스타일 댓글 통계 그래픽 박스 -->
            <div class="comment-stats-card" id="comment-stats-box">
                <div class="stats-card-header">
                    <div class="stats-card-title">
                        <span>👥 이 기사 댓글 참여자 통계</span>
                    </div>
                    <div class="stats-card-sub">
                        <span>성별 및 연령대별 작성 비율</span>
                    </div>
                </div>

                <div class="stats-grid">
                    <!-- 성별 통계 -->
                    <div class="stats-gender-col">
                        <div class="gender-title">성별 비율</div>
                        <div class="gender-bar-wrapper">
                            <div class="gender-bar-male" style="width: <?php echo $stat_male_pct; ?>%;" title="남성 <?php echo $stat_male_pct; ?>%"></div>
                            <div class="gender-bar-female" style="width: <?php echo $stat_female_pct; ?>%;" title="여성 <?php echo $stat_female_pct; ?>%"></div>
                        </div>
                        <div class="gender-labels">
                            <div class="gender-stat-item">
                                <span class="gender-dot-male"></span>
                                <span>남성</span>
                                <span class="gender-val-male"><?php echo $stat_male_pct; ?>%</span>
                            </div>
                            <div class="gender-stat-item">
                                <span class="gender-dot-female"></span>
                                <span>여성</span>
                                <span class="gender-val-female"><?php echo $stat_female_pct; ?>%</span>
                            </div>
                        </div>
                    </div>

                    <!-- 연령대 통계 막대 차트 -->
                    <div class="stats-age-col">
                        <div class="age-title">연령대별 비율</div>
                        <div class="age-bars-container">
                            <?php foreach ($stat_age_pct as $ag_key => $pct_val): 
                                $is_highest = ($ag_key === $highest_age);
                                // 막대 높이 계산 (최대 50px 기준)
                                $bar_h = max(6, round(($pct_val / max(1, $highest_val)) * 48));
                            ?>
                            <div class="age-bar-group <?php echo $is_highest ? 'highest' : ''; ?>">
                                <span class="age-pct-text"><?php echo $pct_val; ?>%</span>
                                <div class="age-bar-track">
                                    <div class="age-bar-fill" style="height: <?php echo $bar_h; ?>px;"></div>
                                </div>
                                <span class="age-group-label"><?php echo get_age_label($ag_key); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 댓글 작성 폼 -->
            <?php if (!empty($comment_msg)): ?>
                <div style="color: #e03131; font-size: 13px; margin-bottom: 10px;"><?php echo $comment_msg; ?></div>
            <?php endif; ?>

            <?php if ($is_logged_in): ?>
            <form action="/view.php?id=<?php echo $article_id; ?>&sort=<?php echo $sort; ?>#comments" method="POST" class="comment-form">
                <textarea name="comment_text" placeholder="네티즌 모두가 함께 만드는 깨끗하고 건전한 인터넷 세상! 타인을 배려하는 따뜻한 댓글을 남겨주세요." required></textarea>
                <div class="comment-form-bottom">
                    <div class="comment-user-info-text">
                        작성자: <strong><?php echo htmlspecialchars($current_name); ?></strong> 
                        (<?php echo ($_SESSION['gender'] ?? 'M') === 'F' ? '여성' : '남성'; ?> · <?php echo get_age_label($_SESSION['age_group'] ?? '20s'); ?>)
                    </div>
                    <button type="submit">댓글 등록</button>
                </div>
            </form>
            <?php else: ?>
            <div style="padding: 20px; background-color: #f8fafc; text-align: center; font-size: 14px; margin-bottom: 24px; border-radius: 6px; border: 1px solid #e2e8f0;">
                댓글을 작성하거나 공감(좋아요)을 누르시려면 <a href="/login.php" style="color: #03c75a; font-weight: bold; text-decoration: underline;">로그인</a>이 필요합니다.
            </div>
            <?php endif; ?>

            <!-- 댓글 정렬 탭 -->
            <div class="comment-sort-bar">
                <a href="/view.php?id=<?php echo $article_id; ?>&sort=sympathy#comments" class="sort-link <?php echo $sort === 'sympathy' ? 'active' : ''; ?>">
                    순공감순
                </a>
                <span class="sort-divider">|</span>
                <a href="/view.php?id=<?php echo $article_id; ?>&sort=latest#comments" class="sort-link <?php echo $sort === 'latest' ? 'active' : ''; ?>">
                    최신순
                </a>
            </div>

            <!-- 댓글 목록 -->
            <ul class="comment-list">
                <?php 
                if ($total_comments > 0):
                    foreach ($comments_list as $c):
                        $c_id = $c['id'];
                        $user_rxn = $user_reactions[$c_id] ?? null;
                        $is_like_active = ($user_rxn === 'like');
                        $is_dislike_active = ($user_rxn === 'dislike');
                        $author_gender = ($c['gender'] ?? 'M') === 'F' ? '여성' : '남성';
                        $author_age = get_age_label($c['age_group'] ?? '20s');
                ?>
                <li class="comment-box" id="comment-item-<?php echo $c_id; ?>">
                    <div class="comment-header">
                        <span class="comment-author-name"><?php echo htmlspecialchars($c['user_name']); ?></span>
                        <span class="comment-masked-id">(<?php echo htmlspecialchars(mask_username($c['username'] ?? '')); ?>)</span>
                        
                        <?php if ($c['role'] === 'reporter'): ?>
                            <span class="comment-reporter-badge">기자</span>
                        <?php elseif ($c['role'] === 'admin'): ?>
                            <span class="comment-reporter-badge" style="background-color:#fee2e2; color:#e03131;">관리자</span>
                        <?php else: ?>
                            <span class="comment-demographic-badge"><?php echo $author_gender; ?> · <?php echo $author_age; ?></span>
                        <?php endif; ?>

                        <span class="comment-date">
                            <?php echo date('Y.m.d. H:i', strtotime($c['created_at'])); ?>
                        </span>
                    </div>

                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($c['comment'])); ?></div>

                    <!-- 좋아요(공감) / 싫어요(비공감) 액션 바 -->
                    <div class="comment-actions">
                        <button type="button" 
                                class="btn-reaction btn-like <?php echo $is_like_active ? 'active-like' : ''; ?>" 
                                data-comment-id="<?php echo $c_id; ?>" 
                                data-reaction="like" 
                                onclick="handleReaction(<?php echo $c_id; ?>, 'like')">
                            <span>👍</span>
                            <span>공감</span>
                            <span class="reaction-count count-like" id="like-count-<?php echo $c_id; ?>"><?php echo number_format($c['like_count']); ?></span>
                        </button>

                        <button type="button" 
                                class="btn-reaction btn-dislike <?php echo $is_dislike_active ? 'active-dislike' : ''; ?>" 
                                data-comment-id="<?php echo $c_id; ?>" 
                                data-reaction="dislike" 
                                onclick="handleReaction(<?php echo $c_id; ?>, 'dislike')">
                            <span>👎</span>
                            <span>비공감</span>
                            <span class="reaction-count count-dislike" id="dislike-count-<?php echo $c_id; ?>"><?php echo number_format($c['dislike_count']); ?></span>
                        </button>
                    </div>
                </li>
                <?php 
                    endforeach;
                else:
                ?>
                <li style="padding: 30px 0; text-align: center; color: #888; font-size: 14px;">
                    등록된 댓글이 없습니다. 첫 번째 공감 댓글을 남겨보세요!
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
// 참여자 통계 접기/펼치기 토글
function toggleStats() {
    const statsBox = document.getElementById('comment-stats-box');
    const toggleBtn = document.getElementById('btn-toggle-stats');
    if (statsBox.style.display === 'none') {
        statsBox.style.display = 'block';
        toggleBtn.innerHTML = '<span>📊 참여자 통계 접기</span>';
    } else {
        statsBox.style.display = 'none';
        toggleBtn.innerHTML = '<span>📊 참여자 통계 펼치기</span>';
    }
}

// 좋아요/싫어요 AJAX 처리
const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

function handleReaction(commentId, reactionType) {
    if (!isLoggedIn) {
        if (confirm('댓글 공감/비공감 기능은 로그인 후 이용하실 수 있습니다.\n로그인 페이지로 이동하시겠습니까?')) {
            window.location.href = '/login.php';
        }
        return;
    }

    const formData = new FormData();
    formData.append('comment_id', commentId);
    formData.append('reaction', reactionType);

    fetch('/comment_reaction.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 카운트 업데이트
            const likeCountEl = document.getElementById(`like-count-${commentId}`);
            const dislikeCountEl = document.getElementById(`dislike-count-${commentId}`);
            
            if (likeCountEl) likeCountEl.textContent = Number(data.likes).toLocaleString();
            if (dislikeCountEl) dislikeCountEl.textContent = Number(data.dislikes).toLocaleString();

            // 버튼 active 클래스 토글
            const parent = document.getElementById(`comment-item-${commentId}`);
            if (parent) {
                const btnLike = parent.querySelector('.btn-like');
                const btnDislike = parent.querySelector('.btn-dislike');

                if (btnLike && btnDislike) {
                    btnLike.classList.remove('active-like');
                    btnDislike.classList.remove('active-dislike');

                    if (data.user_reaction === 'like') {
                        btnLike.classList.add('active-like');
                    } else if (data.user_reaction === 'dislike') {
                        btnDislike.classList.add('active-dislike');
                    }
                }
            }
        } else {
            if (data.need_login) {
                if (confirm('로그인이 필요한 서비스입니다.\n로그인 페이지로 이동하시겠습니까?')) {
                    window.location.href = '/login.php';
                }
            } else {
                alert(data.message || '요청 처리 중 오류가 발생했습니다.');
            }
        }
    })
    .catch(err => {
        console.error('Reaction error:', err);
        alert('서버와 통신 중 오류가 발생했습니다.');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
