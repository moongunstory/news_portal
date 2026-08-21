<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// 테이블 및 컬럼 누락 방지 자동 안전장치
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청 방식입니다.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'need_login' => true, 
        'message' => '로그인이 필요한 서비스입니다. 로그인하시겠습니까?'
    ]);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
$reaction = isset($_POST['reaction']) ? trim($_POST['reaction']) : '';

if ($comment_id <= 0 || !in_array($reaction, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 요청 파라미터입니다.']);
    exit;
}

// 댓글 존재 확인
$comment_chk = mysqli_query($conn, "SELECT id, like_count, dislike_count FROM comments WHERE id = $comment_id");
if (!$comment_chk || mysqli_num_rows($comment_chk) == 0) {
    echo json_encode(['success' => false, 'message' => '존재하지 않는 댓글입니다.']);
    exit;
}

$current_reaction = null;

// 기존 사용자의 반응 확인
$existing_query = "SELECT reaction_type FROM comment_reactions WHERE comment_id = $comment_id AND user_id = $user_id";
$existing_res = mysqli_query($conn, $existing_query);

if ($existing_res && mysqli_num_rows($existing_res) > 0) {
    $row = mysqli_fetch_assoc($existing_res);
    $prev_type = $row['reaction_type'];

    if ($prev_type === $reaction) {
        // 1. 동일 반응 재클릭 -> 취소
        mysqli_query($conn, "DELETE FROM comment_reactions WHERE comment_id = $comment_id AND user_id = $user_id");
        $col_to_dec = ($reaction === 'like') ? 'like_count' : 'dislike_count';
        mysqli_query($conn, "UPDATE comments SET $col_to_dec = GREATEST(0, $col_to_dec - 1) WHERE id = $comment_id");
        $current_reaction = null;
    } else {
        // 2. 다른 반응으로 변경 (like <-> dislike)
        mysqli_query($conn, "UPDATE comment_reactions SET reaction_type = '$reaction', created_at = NOW() WHERE comment_id = $comment_id AND user_id = $user_id");
        $col_to_dec = ($prev_type === 'like') ? 'like_count' : 'dislike_count';
        $col_to_inc = ($reaction === 'like') ? 'like_count' : 'dislike_count';
        mysqli_query($conn, "UPDATE comments SET $col_to_dec = GREATEST(0, $col_to_dec - 1), $col_to_inc = $col_to_inc + 1 WHERE id = $comment_id");
        $current_reaction = $reaction;
    }
} else {
    // 3. 신규 반응 등록
    $insert_sql = "INSERT INTO comment_reactions (comment_id, user_id, reaction_type) VALUES ($comment_id, $user_id, '$reaction')";
    if (mysqli_query($conn, $insert_sql)) {
        $col_to_inc = ($reaction === 'like') ? 'like_count' : 'dislike_count';
        mysqli_query($conn, "UPDATE comments SET $col_to_inc = $col_to_inc + 1 WHERE id = $comment_id");
        $current_reaction = $reaction;
    }
}

// 최신 카운트 조회
$updated_res = mysqli_query($conn, "SELECT like_count, dislike_count FROM comments WHERE id = $comment_id");
$updated_row = mysqli_fetch_assoc($updated_res);

echo json_encode([
    'success' => true,
    'comment_id' => $comment_id,
    'likes' => intval($updated_row['like_count']),
    'dislikes' => intval($updated_row['dislike_count']),
    'user_reaction' => $current_reaction
]);
