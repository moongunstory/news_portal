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

# DB 및 테이블 초기화 (최초 1회 실행)
echo "[+] 데이터베이스 확인 및 초기화 중..."
mysql -e "CREATE DATABASE IF NOT EXISTS news_portal DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if [ -f /var/www/html/schema.sql ]; then
    mysql news_portal < /var/www/html/schema.sql 2>/dev/null || true
fi

# uploads 폴더 권한 확인
mkdir -p /var/www/html/uploads
chmod -R 777 /var/www/html/uploads
chown -R www-data:www-data /var/www/html

echo "[+] 웹 서버 및 DB가 성공적으로 준비되었습니다!"

# Apache 웹서버 포그라운드 실행
exec apache2-foreground
