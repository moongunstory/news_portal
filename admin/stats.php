<?php
require_once __DIR__ . '/../header.php';

// [접근 제어] 오직 최고 관리자만 접근 가능 (건너뛰기 방지)
if (!$is_logged_in || $current_role !== 'admin') {
    echo "<div class='section-box' style='padding:40px; text-align:center; color:#c92a2a;'>";
    echo "<h3>최고 관리자 전용 구역</h3>";
    echo "<p style='margin-top:10px;'>최고 관리자 권한이 있는 계정만 접근할 수 있습니다.</p>";
    echo "<a href='/login.php' class='btn-primary' style='display:inline-block; margin-top:16px;'>로그인 화면으로</a>";
    echo "</div>";
    require_once __DIR__ . '/../footer.php';
    exit;
}

$page = $_GET['page'] ?? 'daily_summary';
?>

<div class="content-left" style="width: 100%;">
    <div class="section-box" style="padding: 30px;">
        <div class="section-title">
            <span>포털 운영 통계 및 시스템 보고서</span>
            <span style="font-size: 13px; color: #f08c00; font-weight: bold;">시스템 모니터링</span>
        </div>

        <div style="margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 12px; display: flex; gap: 10px;">
            <a href="/admin/stats.php?page=daily_summary" class="btn-primary" style="background-color: #495057; font-size: 13px;">일일 요약 통계</a>
            <a href="/admin/stats.php?page=visitor_stats" class="btn-primary" style="background-color: #495057; font-size: 13px;">방문자 유입 분석</a>
        </div>

        <div style="background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 20px;">
            <?php
            // [5단계 취약점: 파일 불러오기 - 로컬 파일 인클루전]
            // page 파라미터로 전달된 파일 경로를 검증 없이 include 하여 실행함
            // 4단계에서 올린 그림 파일 경로(?page=../uploads/profile.jpg)를 넣으면
            // 그림 파일 안의 PHP 실행 코드가 강제로 실행되면서 서버 명령어를 조종할 수 있게 됨
            $loaded = false;
            
            if (file_exists($page)) {
                include($page);
                $loaded = true;
            } elseif (file_exists(__DIR__ . '/' . $page)) {
                include(__DIR__ . '/' . $page);
                $loaded = true;
            } elseif (file_exists(__DIR__ . '/' . $page . '.php')) {
                include(__DIR__ . '/' . $page . '.php');
                $loaded = true;
            } else {
                // 경로 탐색을 통한 직접 로드
                @include($page);
                $loaded = true;
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
