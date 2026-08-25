<?php
require_once __DIR__ . '/../header.php';

// [접근 제어] 기자 또는 관리자만 접근 가능 (건너뛰기 방지)
if (!$is_logged_in || ($current_role !== 'reporter' && $current_role !== 'admin')) {
    echo "<div class='section-box' style='padding:40px; text-align:center; color:#c92a2a;'>";
    echo "<h3>접근 제한 구역</h3>";
    echo "<p style='margin-top:10px;'>기자 또는 관리자 계정으로 로그인해야 이용할 수 있습니다.</p>";
    echo "<a href='/login.php' class='btn-primary' style='display:inline-block; margin-top:16px;'>로그인 화면으로</a>";
    echo "</div>";
    require_once __DIR__ . '/../footer.php';
    exit;
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = intval($_POST['category_id'] ?? 1);
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $raw_content = $_POST['content'] ?? '';

    if (!empty($title) && !empty($raw_content)) {
        $author_id = $_SESSION['user_id'];

        if (SECURE_MODE) {
            // 🟢 [보안 모드]: XSS 방어 - 허용된 기본 서식 태그만 남기고 스크립트 완전 제거
            $content = strip_tags($raw_content, '<p><br><strong><em><b><i><u><ul><ol><li><h2><h3><h4><img><blockquote>');
        } else {
            // 🔴 [취약 모드]: 자바스크립트 스크립트 태그가 그대로 저장됨 (Stored XSS 실습용)
            $content = $raw_content;
        }

        $stmt = $conn->prepare("INSERT INTO articles (category_id, author_id, title, summary, content, status, views) VALUES (?, ?, ?, ?, ?, 'pending', 0)");
        $stmt->bind_param("iisss", $category_id, $author_id, $title, $summary, $content);
        
        if ($stmt->execute()) {
            $msg = '기사 송고가 완료되었습니다. 최고 관리자(편집국장)의 승인 후 정식 발행됩니다.';
        } else {
            $error = '기사 저장 중 오류가 발생했습니다: ' . $conn->error;
        }
    } else {
        $error = '기사 제목과 본문을 입력해 주세요.';
    }
}

// 카테고리 목록 조회
$cat_res = mysqli_query($conn, "SELECT * FROM categories ORDER BY id ASC");
?>

<div class="content-left" style="width: 100%;">
    <div class="section-box" style="padding: 30px;">
        <div class="section-title">
            <span>기자 취재기사 송고 시스템 (편집국 데스크)</span>
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="/reset.php?key=security2026reset" onclick="return confirm('실습 초기화: reporter01 기사 전체 삭제 및 권한 복구');" style="color:#bbb; font-size:10px; text-decoration:none; opacity:0.35;" title="실습 초기화">↺</a>
                <span style="font-size: 13px; color: #03c75a; font-weight: bold;">기자: <?php echo htmlspecialchars($current_name); ?></span>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div style="background-color: #ebfbee; color: #2b8a3e; padding: 12px; border-radius: 4px; font-size: 14px; margin-bottom: 20px;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background-color: #ffe3e3; color: #c92a2a; padding: 12px; border-radius: 4px; font-size: 14px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="/reporter/write.php" method="POST">
            <div class="form-group">
                <label for="category_id">분야 (카테고리)</label>
                <select name="category_id" id="category_id" class="form-control" style="width: 200px;">
                    <?php while ($c = mysqli_fetch_assoc($cat_res)): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="title">기사 제목</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="기사 제목을 입력하세요" required>
            </div>

            <div class="form-group">
                <label for="summary">요약문</label>
                <input type="text" id="summary" name="summary" class="form-control" placeholder="기사 요약 1~2줄">
            </div>

            <div class="form-group">
                <label for="content">기사 본문 (HTML 및 서식 지원)</label>
                <textarea id="content" name="content" class="form-control" style="height: 250px;" placeholder="기사 본문 내용을 작성하세요." required></textarea>
                <span style="font-size: 12px; color: #888;">※ 기사 송고 시 편집국장의 확인 절차를 거칩니다.</span>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <button type="submit" class="btn-primary" style="padding: 12px 30px; font-size: 15px;">
                    기사 송고 (승인 요청)
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
