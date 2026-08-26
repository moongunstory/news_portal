<?php
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha_input = trim($_POST['captcha_code'] ?? '');

    if (SECURE_MODE) {
        // ── 🟢 [보안 강화 모드] ──────────────────────────────────
        // 1. CAPTCHA 검증 (대소문자 무시)
        $session_captcha = $_SESSION['captcha_code'] ?? '';
        if (empty($captcha_input) || strtoupper($captcha_input) !== strtoupper($session_captcha)) {
            $error = '자동 입력 방지문자가 올바르지 않습니다. 다시 입력해 주세요.';
            // 캡차 사용 후 무효화
            unset($_SESSION['captcha_code']);
        } else {
            unset($_SESSION['captcha_code']); // 1회 검증 후 폐기

            // 2. 계정 정보 및 잠금 상태 조회 (Prepared Statement - SQL Injection 방어)
            $stmt = $conn->prepare("SELECT id, username, password, name, role, gender, age_group, login_fail_count, lockout_time FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                // 3. 계정 잠금 여부 확인 (최근 10분 이내 잠금 여부)
                $lockout_time = $user['lockout_time'] ? strtotime($user['lockout_time']) : 0;
                $is_locked = ($lockout_time > 0 && (time() - $lockout_time < 600));

                if ($is_locked) {
                    $remaining = ceil((600 - (time() - $lockout_time)) / 60);
                    $error = "비밀번호 5회 연속 오류로 계정이 잠겼습니다. 약 {$remaining}분 후 다시 시도하세요.";
                } else {
                    // 4. 비밀번호 일치 확인
                    if ($password === $user['password']) {
                        // [로그인 성공] 실패 횟수 및 잠금 시간 초기화
                        $reset_stmt = $conn->prepare("UPDATE users SET login_fail_count = 0, lockout_time = NULL WHERE id = ?");
                        $reset_stmt->bind_param("i", $user['id']);
                        $reset_stmt->execute();

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
                        // [로그인 실패] 실패 횟수 증가 및 5회 이상 시 잠금
                        $new_fail_count = (int)$user['login_fail_count'] + 1;

                        if ($new_fail_count >= 5) {
                            $lock_stmt = $conn->prepare("UPDATE users SET login_fail_count = ?, lockout_time = NOW() WHERE id = ?");
                            $lock_stmt->bind_param("ii", $new_fail_count, $user['id']);
                            $lock_stmt->execute();
                            $error = "비밀번호를 5회 연속 잘못 입력하여 계정이 10분간 잠깁니다.";
                        } else {
                            $fail_stmt = $conn->prepare("UPDATE users SET login_fail_count = ? WHERE id = ?");
                            $fail_stmt->bind_param("ii", $new_fail_count, $user['id']);
                            $fail_stmt->execute();
                            $error = "아이디 또는 비밀번호가 올바르지 않습니다. (실패 횟수: {$new_fail_count}/5)";
                        }
                    }
                }
            } else {
                $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
            }
        }
    } else {
        // ── 🔴 [취약 실습 모드] ──────────────────────────────────
        // [2단계 취약점] SQL Injection 및 무차별 대입(Brute Force) 가능
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
}

require_once __DIR__ . '/header.php';
?>

<div class="content-left" style="max-width: 460px; margin: 40px auto;">
    <div class="section-box" style="padding: 30px;">
        <h2 style="font-size: 22px; font-weight: 800; text-align: center; margin-bottom: 24px; color: #03c75a;">
            포털 로그인
        </h2>

        <?php if (!empty($error)): ?>
            <div style="background-color: #ffe3e3; color: #c92a2a; padding: 12px; border-radius: 4px; font-size: 13px; margin-bottom: 16px; border: 1px solid #ffc9c9;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="/login.php" method="POST">
            <div class="form-group">
                <label for="username">아이디</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="아이디를 입력하세요" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="비밀번호를 입력하세요" required>
            </div>

            <?php if (SECURE_MODE): ?>
                <div class="form-group" style="margin-top: 15px;">
                    <label for="captcha">자동 입력 방지문자 <span style="color:#e03131;">*</span></label>
                    <div style="display: flex; gap: 10px; margin-bottom: 8px; align-items: center;">
                        <img src="/captcha_image.php" id="captcha-img" onclick="this.src='/captcha_image.php?r='+Math.random()" alt="CAPTCHA" style="border: 1px solid #ccc; height: 38px; border-radius: 4px; cursor: pointer;" title="클릭하면 새로운 문자로 새로고침됩니다">
                        <button type="button" onclick="document.getElementById('captcha-img').src='/captcha_image.php?r='+Math.random()" class="btn-primary" style="padding: 6px 12px; font-size: 12px; background-color: #495057;">새로고침</button>
                    </div>
                    <input type="text" id="captcha" name="captcha_code" class="form-control" placeholder="위 그림에 보이는 4자리 문자를 입력하세요" maxlength="6" required autocomplete="off">
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; margin-top: 15px; font-size: 15px;">
                로그인
            </button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 13px; color: #666;">
            아직 회원이 아니신가요? <a href="/register.php" style="color: #03c75a; font-weight: bold;">회원가입</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
