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
            if (SECURE_MODE) {
                // ── 🟢 [보안 강화 모드]: 화이트리스트 기반 LFI 차단 ──────
                $allowed_pages = ['daily_summary', 'visitor_stats'];

                if (in_array($page, $allowed_pages)) {
                    $target_file = __DIR__ . '/' . $page . '.php';
                    if (file_exists($target_file)) {
                        include($target_file);
                    } else {
                        echo "<p style='color:#e03131;'>요청한 통계 보고서 파일이 존재하지 않습니다.</p>";
                    }
                } else {
                    echo "<div style='color:#c92a2a; padding:15px; background:#fff5f5; border:1px solid #ffc9c9; border-radius:4px;'>";
                    echo "<strong>[보안 차단]</strong> 허용되지 않은 비인가 페이지 또는 외부 파일 접근(LFI) 요청이 차단되었습니다.";
                    echo "</div>";
                }
            } else {
                // ── 🔴 [취약 실습 모드]: 로컬 파일 인클루전 (LFI 취약점 실습용) ──
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
                    @include($page);
                    $loaded = true;
                }
            }
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
