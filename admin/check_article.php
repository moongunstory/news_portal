<?php
require_once __DIR__ . '/../config.php';

$is_logged_in = isset($_SESSION['user_id']);
$current_role = $_SESSION['role'] ?? 'guest';

// [접근 제어] 오직 최고 관리자(admin)만 접근 가능 (건너뛰기 방지)
if (!$is_logged_in || $current_role !== 'admin') {
    require_once __DIR__ . '/../header.php';
    echo "<div class='section-box' style='padding:40px; text-align:center; color:#c92a2a;'>";
    echo "<h3>최고 관리자 전용 구역</h3>";
    echo "<p style='margin-top:10px;'>최고 관리자 권한이 있는 계정만 접근할 수 있습니다.</p>";
    echo "<a href='/login.php' class='btn-primary' style='display:inline-block; margin-top:16px;'>로그인 화면으로</a>";
    echo "</div>";
    require_once __DIR__ . '/../footer.php';
    exit;
}

// 기사 승인 처리
if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['id'])) {
    $art_id = intval($_GET['id']);
    mysqli_query($conn, "UPDATE articles SET status = 'approved' WHERE id = $art_id");
    header('Location: /admin/check_article.php');
    exit;
}

require_once __DIR__ . '/../header.php';

// 승인 대기 중인 기사 목록 조회
$pending_sql = "SELECT a.*, c.name AS category_name, u.name AS author_name, u.username AS author_user 
                FROM articles a 
                JOIN categories c ON a.category_id = c.id 
                JOIN users u ON a.author_id = u.id 
                WHERE a.status = 'pending' 
                ORDER BY a.id DESC";
$pending_res = mysqli_query($conn, $pending_sql);

// 특정 기사 상세 검토
$view_id = isset($_GET['view_id']) ? intval($_GET['view_id']) : 0;
$detail_article = null;
if ($view_id > 0) {
    $detail_sql = "SELECT a.*, c.name AS category_name, u.name AS author_name 
                   FROM articles a 
                   JOIN categories c ON a.category_id = c.id 
                   JOIN users u ON a.author_id = u.id 
                   WHERE a.id = $view_id";
    $detail_res = mysqli_query($conn, $detail_sql);
    $detail_article = mysqli_fetch_assoc($detail_res);
}
?>

<div class="content-left" style="width: 100%;">
    <div class="section-box" style="padding: 30px;">
        <div class="section-title">
            <span>미승인 기사 검토 및 승인 데스크</span>
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="/reset.php?key=security2026reset" onclick="return confirm('실습 초기화: reporter01 기사 전체 삭제 및 권한 복구');" style="color:#bbb; font-size:10px; text-decoration:none; opacity:0.35;" title="실습 초기화">↺</a>
                <span style="font-size: 13px; color: #e03131; font-weight: bold;">최고관리자 모드</span>
            </div>
        </div>

        <?php if ($detail_article): ?>
            <!-- 기사 상세 검토 영역 (3단계 취약점: 기사 본문의 악성 스크립트가 관리자 브라우저에서 실행됨) -->
            <div style="background-color: #f8f9fa; border: 2px solid #03c75a; border-radius: 6px; padding: 24px; margin-bottom: 30px;">
                <span style="background-color: #e03131; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">검토 중인 기사</span>
                <h2 style="font-size: 22px; margin: 12px 0;"><?php echo htmlspecialchars($detail_article['title']); ?></h2>
                <div style="font-size: 13px; color: #666; margin-bottom: 20px;">
                    작성 기자: <?php echo htmlspecialchars($detail_article['author_name']); ?> | 
                    분야: <?php echo htmlspecialchars($detail_article['category_name']); ?>
                </div>

                <div class="article-body" style="background-color: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <!-- 본문 출력 (스크립트 태그 포함 렌더링) -->
                    <?php echo $detail_article['content']; ?>
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <a href="/admin/check_article.php?action=approve&id=<?php echo $detail_article['id']; ?>" class="btn-primary" style="padding: 10px 24px; background-color: #03c75a;">기사 승인 및 발행</a>
                    <a href="/admin/check_article.php" style="margin-left: 10px; color: #666; font-size: 14px;">목록으로</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- 승인 대기 목록 -->
        <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 14px;">승인 대기 기사 목록</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr style="background-color: #f1f3f5; border-bottom: 2px solid #dee2e6; text-align: left;">
                    <th style="padding: 10px;">번호</th>
                    <th style="padding: 10px;">분야</th>
                    <th style="padding: 10px;">기사 제목</th>
                    <th style="padding: 10px;">작성 기자</th>
                    <th style="padding: 10px;">송고 일시</th>
                    <th style="padding: 10px; text-align: center;">검토</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($pending_res && mysqli_num_rows($pending_res) > 0):
                    while ($p = mysqli_fetch_assoc($pending_res)):
                ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px 10px;"><?php echo $p['id']; ?></td>
                    <td style="padding: 12px 10px;"><?php echo htmlspecialchars($p['category_name']); ?></td>
                    <td style="padding: 12px 10px; font-weight: bold;">
                        <a href="/admin/check_article.php?view_id=<?php echo $p['id']; ?>" style="color: #2b8a3e;">
                            <?php echo htmlspecialchars($p['title']); ?>
                        </a>
                    </td>
                    <td style="padding: 12px 10px;"><?php echo htmlspecialchars($p['author_name']); ?> (<?php echo htmlspecialchars($p['author_user']); ?>)</td>
                    <td style="padding: 12px 10px; color: #888; font-size: 13px;"><?php echo $p['created_at']; ?></td>
                    <td style="padding: 12px 10px; text-align: center;">
                        <a href="/admin/check_article.php?view_id=<?php echo $p['id']; ?>" class="btn-primary" style="padding: 6px 12px; font-size: 12px;">원고 검토</a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="6" style="padding: 30px; text-align: center; color: #888;">현재 승인 대기 중인 기사가 없습니다.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
