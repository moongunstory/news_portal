#!/bin/bash
set -e

# MariaDB 시작
echo "[+] MariaDB 서비스 시작 중..."
service mariadb start

# MariaDB가 완전히 뜰 때까지 대기
until mysqladmin ping --silent; do
    echo "[-] MariaDB 준비 대기 중..."
    sleep 1
done

# DB 및 사용자 권한 설정
echo "[+] 데이터베이스 권한 및 계정 설정 중..."
mysql << 'EOF'
CREATE DATABASE IF NOT EXISTS news_portal DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- root 계정 인증 플러그인 변경 및 암호 없이 모든 호스트 접속 허용
ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('');
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED VIA mysql_native_password USING PASSWORD('') WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED VIA mysql_native_password USING PASSWORD('') WITH GRANT OPTION;

-- 백업용 전용 계정 news_user 생성
GRANT ALL PRIVILEGES ON news_portal.* TO 'news_user'@'%' IDENTIFIED BY 'news_pass';
GRANT ALL PRIVILEGES ON news_portal.* TO 'news_user'@'localhost' IDENTIFIED BY 'news_pass';
GRANT ALL PRIVILEGES ON news_portal.* TO 'news_user'@'127.0.0.1' IDENTIFIED BY 'news_pass';

FLUSH PRIVILEGES;
EOF

# 스키마 초기화 (테이블 생성)
if [ -f /var/www/html/schema.sql ]; then
    echo "[+] 테이블 스키마 초기화 중..."
    mysql news_portal < /var/www/html/schema.sql 2>/dev/null || true
fi

# uploads 폴더 권한 설정
mkdir -p /var/www/html/uploads
chmod -R 777 /var/www/html/uploads
chown -R www-data:www-data /var/www/html

echo "[+] 웹 서버 및 DB가 성공적으로 준비되었습니다!"

# Apache 웹서버 포그라운드 실행
exec apache2-foreground
