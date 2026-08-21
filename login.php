<?php
require_once __DIR__ . '/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // [2단계 취약점] SQL Injection 취약점 (사용자 입력을 그대로 쿼리에 삽입)
    // 1) SQL Injection: 아이디에 "reporter01' -- " 또는 "reporter01'#" 입력 시 비밀번호 검증 우회
    // 2) 브루트 포스(무차별 대입): 사내 보안 정책(숫자 4~8자리)에 따라 2026 등 무차별 대입 공격 가능
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    
    $result = mysqli_query($conn, $query);

    if ($result && $user = mysqli_fetch_assoc($result)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['gender'] = $user['gender'] ?? 'M';
        $_SESSION['age_group'] = $user['age_group'] ?? '20s';

        if ($user['role'] === 'reporter') {
            header('Location: /reporter/write.php');
        } elseif ($user['role'] === 'admin') {
            header('Location: /admin/check_article.php');
        } else {
            header('Location: /index.php');
        }
        exit;
    } else {
        $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
    }
}
?>

<div class="content-left" style="max-width: 460px; margin: 40px auto;">
    <div class="section-box" style="padding: 30px;">
        <h2 style="font-size: 22px; font-weight: 800; text-align: center; margin-bottom: 24px; color: #03c75a;">
            포털 로그인
        </h2>

        <?php if (!empty($error)): ?>
            <div style="background-color: #ffe3e3; color: #c92a2a; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 16px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="/login.php" method="POST">
            <div class="form-group">
                <label for="username">아이디</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="아이디를 입력하세요" required>
            </div>
            
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="비밀번호를 입력하세요">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; margin-top: 10px; font-size: 15px;">
                로그인
            </button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 13px; color: #666;">
            아직 회원이 아니신가요? <a href="/register.php" style="color: #03c75a; font-weight: bold;">회원가입</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
