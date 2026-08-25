<?php
require_once __DIR__ . '/../header.php';

// [접근 제어] 최고 관리자 또는 기자만 접근 가능 (건너뛰기 방지)
if (!$is_logged_in || ($current_role !== 'admin' && $current_role !== 'reporter')) {
    echo "<div class='section-box' style='padding:40px; text-align:center; color:#c92a2a;'>";
    echo "<h3>접근 제한 구역</h3>";
    echo "<p style='margin-top:10px;'>관리자 또는 기자 권한이 필요합니다.</p>";
    echo "<a href='/login.php' class='btn-primary' style='display:inline-block; margin-top:16px;'>로그인 화면으로</a>";
    echo "</div>";
    require_once __DIR__ . '/../footer.php';
    exit;
}

$msg = '';
$error = '';
$uploaded_path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['news_image'])) {
    $file = $_FILES['news_image'];
    $upload_dir = __DIR__ . '/../uploads/';

    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    $raw_filename = basename($file['name']);
    $ext = strtolower(pathinfo($raw_filename, PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

    if (SECURE_MODE) {
        // ── 🟢 [보안 강화 모드]: 완벽한 웹쉘 업로드 방어 ──────────
        // 1. 화이트리스트 확장자 검증
        if (!in_array($ext, $allowed_exts)) {
            $error = '보안을 위해 지정된 이미지 파일(jpg, jpeg, png, gif)만 업로드할 수 있습니다.';
        } else {
            // 2. MIME 타입 검증
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($mime, $allowed_mimes)) {
                $error = '파일의 실제 내용(MIME 타입)이 올바른 이미지 형식이 아닙니다.';
            } else {
                // 3. 파일명 난수화 (UUID)
                $safe_filename = uniqid('img_', true) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                $target_file = $upload_dir . $safe_filename;

                // 4. GD 라이브러리를 통한 이미지 재생성 (매직바이트/웹쉘 코드 완전 제거)
                $img_data = @file_get_contents($file['tmp_name']);
                $src_img = @imagecreatefromstring($img_data);

                if ($src_img) {
                    $saved = false;
                    if ($ext === 'png') {
                        imagealphablending($src_img, false);
                        imagesavealpha($src_img, true);
                        $saved = imagepng($src_img, $target_file, 8);
                    } elseif ($ext === 'gif') {
                        $saved = imagegif($src_img, $target_file);
                    } else {
                        $saved = imagejpeg($src_img, $target_file, 90);
                    }
                    imagedestroy($src_img);

                    if ($saved) {
                        $msg = '보안 검사 및 이미지 정제(재생성)를 거쳐 안전하게 저장되었습니다!';
                        $uploaded_path = '/uploads/' . $safe_filename;
                    } else {
                        $error = '이미지 저장 처리 중 오류가 발생했습니다.';
                    }
                } else {
                    $error = '손상되었거나 변조된 이미지 파일입니다.';
                }
            }
        }
    } else {
        // ── 🔴 [취약 실습 모드]: 미흡한 파일 검사 (웹쉘 업로드 실습용) ───
        if (in_array($ext, $allowed_exts)) {
            $target_file = $upload_dir . $raw_filename;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                $msg = '파일이 정상적으로 서버에 저장되었습니다!';
                $uploaded_path = '/uploads/' . $raw_filename;
            } else {
                $error = '파일 저장 중 오류가 발생했습니다.';
            }
        } else {
            $error = '보안을 위해 그림 파일(jpg, jpeg, png, gif)만 업로드할 수 있습니다.';
        }
    }
}
?>

<div class="content-left" style="width: 100%;">
    <div class="section-box" style="padding: 30px;">
        <div class="section-title">
            <span>보도 사진 및 프로필 이미지 업로드</span>
            <span style="font-size: 13px; color: #1c7ed6; font-weight: bold;">미디어 보관소</span>
        </div>

        <?php if (!empty($msg)): ?>
            <div style="background-color: #ebfbee; color: #2b8a3e; padding: 14px; border-radius: 4px; font-size: 14px; margin-bottom: 20px;">
                <strong><?php echo htmlspecialchars($msg); ?></strong><br>
                <span style="font-size:13px; color:#555;">저장된 서버 경로: <code><?php echo htmlspecialchars($uploaded_path); ?></code></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div style="background-color: #ffe3e3; color: #c92a2a; padding: 14px; border-radius: 4px; font-size: 14px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="/admin/upload_image.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="news_image">업로드할 이미지 선택 (JPG, PNG, GIF)</label>
                <input type="file" name="news_image" id="news_image" class="form-control" required style="padding: 8px;">
                <p style="font-size: 12px; color: #888; margin-top: 6px;">
                    ※ 기사 본문 삽입용 사진 및 기자/관리자 프로필 사진으로 사용됩니다.
                </p>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-primary" style="padding: 10px 24px; font-size: 14px; background-color: #1c7ed6;">
                    이미지 서버로 전송
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
