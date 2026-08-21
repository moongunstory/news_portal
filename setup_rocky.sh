#!/bin/bash
# ==============================================================================
# 로키 리눅스(Rocky Linux) 뉴스 포털 자동 설치 및 환경 설정 스크립트
# ==============================================================================

echo "[+] 1. 패키지 저장소 업데이트 및 필수 패키지 설치 중..."
dnf -y update
dnf -y install httpd mariadb-server php php-mysqli php-gd php-json

echo "[+] 2. 아파치 및 MariaDB 서비스 활성화 및 시작..."
systemctl enable --now httpd
systemctl enable --now mariadb

echo "[+] 3. 데이터베이스 생성 및 테이블/데이터 초기화..."
mysql -u root -e "CREATE DATABASE IF NOT EXISTS news_portal DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root news_portal < /var/www/html/schema.sql

echo "[+] 4. 파일 권한 및 디렉터리 리스팅(폴더 목록 보기) 설정..."
# 업로드 폴더 권한 부여
mkdir -p /var/www/html/uploads
chmod 777 /var/www/html/uploads
chown -R apache:apache /var/www/html

# 1단계 백업 폴더 디렉터리 리스팅 허용 설정 (.htaccess 활성화 및 Options +Indexes)
cat << 'EOF' > /var/www/html/secret_backup/.htaccess
Options +Indexes
EOF

# 아파치 기본 AllowOverride All 설정 추가
sed -i 's/AllowOverride None/AllowOverride All/g' /etc/httpd/conf/httpd.conf

# 최상위 디렉터리에 최종 정답 플래그 파일 복사
cp /var/www/html/flag.txt /flag.txt
chmod 644 /flag.txt

echo "[+] 5. 아파치 웹 서버 재시작..."
systemctl restart httpd

# 방화벽 포트 개방
firewall-cmd --permanent --add-service=http
firewall-cmd --reload

echo "[+] ========================================================="
echo "[+] 뉴스 포털 웹사이트 설치가 완료되었습니다!"
echo "[+] 브라우저에서 서버 IP 주소로 접속해 보세요."
echo "[+] ========================================================="
