<?php
require_once __DIR__ . '/header.php';

// 컬럼 누락 방지 자동 안전장치
$chk_gender = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'gender'");
if ($chk_gender && mysqli_num_rows($chk_gender) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN gender ENUM('M', 'F') DEFAULT 'M' AFTER role");
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN age_group ENUM('10s', '20s', '30s', '40s', '50s', '60s') DEFAULT '20s' AFTER gender");
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $gender = trim($_POST['gender'] ?? 'M');
    $age_group = trim($_POST['age_group'] ?? '20s');

    // 유효성 검증
    if (!in_array($gender, ['M', 'F'])) $gender = 'M';
    if (!in_array($age_group, ['10s', '20s', '30s', '40s', '50s', '60s'])) $age_group = '20s';

    if (!empty($username) && !empty($password) && !empty($name)) {
        // 비밀번호 유효성 검사 (숫자 4~8자리 제한)
        if (!preg_match('/^[0-9]{4,8}$/', $password)) {
            $error = '비밀번호는 4~8자리의 숫자만 사용할 수 있습니다.';
        } else {
            // 아이디 중복 확인
            $safe_username = mysqli_real_escape_string($conn, $username);
            $safe_name = mysqli_real_escape_string($conn, $name);
            $safe_password = mysqli_real_escape_string($conn, $password);

            $check_query = "SELECT id FROM users WHERE username = '$safe_username'";
            $check_res = mysqli_query($conn, $check_query);
            
            if (mysqli_num_rows($check_res) > 0) {
                $error = '이미 존재하는 아이디입니다.';
            } else {
                // 일반 회원(user)으로 등록 (성별, 연령대 포함)
                $insert_query = "INSERT INTO users (username, password, name, role, gender, age_group) 
                                 VALUES ('$safe_username', '$safe_password', '$safe_name', 'user', '$gender', '$age_group')";
                if (mysqli_query($conn, $insert_query)) {
                    $msg = '회원가입이 완료되었습니다! 로그인해 주세요.';
                } else {
                    $error = '회원가입 처리 중 오류가 발생했습니다.';
                }
            }
        }
    } else {
        $error = '모든 필수 항목을 입력해 주세요.';
    }
}
?>

<div class="content-left" style="max-width: 500px; margin: 40px auto;">
    <div class="section-box" style="padding: 32px;">
        <h2 style="font-size: 22px; font-weight: 800; text-align: center; margin-bottom: 24px; color: #03c75a;">
            독자 회원가입
        </h2>

        <?php if (!empty($msg)): ?>
            <div style="background-color: #ebfbee; color: #2b8a3e; padding: 12px; border-radius: 4px; font-size: 14px; margin-bottom: 16px; text-align: center; border: 1px solid #b2f2bb;">
                <?php echo $msg; ?> <br><a href="/login.php" style="font-weight:bold; color:#03c75a;">[로그인 바로가기]</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background-color: #ffe3e3; color: #c92a2a; padding: 12px; border-radius: 4px; font-size: 14px; margin-bottom: 16px; border: 1px solid #ffc9c9;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="/register.php" method="POST">
            <div class="form-group">
                <label for="username">아이디 <span style="color:#e03131;">*</span></label>
                <input type="text" id="username" name="username" class="form-control" placeholder="아이디를 입력하세요" required>
            </div>

            <div class="form-group">
                <label for="name">이름 (닉네임) <span style="color:#e03131;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" placeholder="이름 또는 닉네임을 입력하세요" required>
            </div>
            
            <div class="form-group">
                <label for="password">비밀번호 (숫자 4~8자리) <span style="color:#e03131;">*</span></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="숫자 4~8자리 입력 (예: 1234)" pattern="[0-9]{4,8}" maxlength="8" minlength="4" title="4~8자리의 숫자만 입력 가능합니다." required>
            </div>

            <div class="form-row-2col" style="display: flex; gap: 16px; margin-bottom: 16px;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label>성별 <span style="color:#e03131;">*</span></label>
                    <div style="display: flex; gap: 12px; height: 42px; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer;">
                            <input type="radio" name="gender" value="M" checked> 남성
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-weight: normal; cursor: pointer;">
                            <input type="radio" name="gender" value="F"> 여성
                        </label>
                    </div>
                </div>

                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label for="age_group">연령대 <span style="color:#e03131;">*</span></label>
                    <select id="age_group" name="age_group" class="form-control" style="height: 42px;" required>
                        <option value="10s">10대 (10~19세)</option>
                        <option value="20s" selected>20대 (20~29세)</option>
                        <option value="30s">30대 (30~39세)</option>
                        <option value="40s">40대 (40~49세)</option>
                        <option value="50s">50대 (50~59세)</option>
                        <option value="60s">60대 이상</option>
                    </select>
                </div>
            </div>

            <p style="font-size: 12px; color: #888; margin-bottom: 16px;">
                ※ 성별 및 연령대는 네이버 뉴스처럼 댓글 통계 및 작성자 표시에 활용됩니다.
            </p>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; font-size: 15px;">
                회원가입 완료
            </button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 13px; color: #666;">
            이미 계정이 있으신가요? <a href="/login.php" style="color: #03c75a; font-weight: bold;">로그인</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
