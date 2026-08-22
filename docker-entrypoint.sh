#!/bin/bash
set -e

echo "[+] 1. MariaDB 데이터 디렉터리 확인..."
if [ ! -d "/var/lib/mysql/mysql" ]; then
    mysql_install_db --user=mysql --datadir=/var/lib/mysql
fi

echo "[+] 2. MariaDB 서비스 시작 중..."
service mariadb start || /etc/init.d/mariadb start

echo "[+] 3. MariaDB 연결 준비 대기..."
for i in {1..30}; do
    if mysqladmin ping --silent 2>/dev/null; then
        echo "[+] MariaDB 연결 성공!"
        break
    fi
    echo "[-] MariaDB 준비 대기 중... ($i/30)"
    sleep 1
done

echo "[+] 4. 데이터베이스 및 계정 권한 설정 중..."
mysql << 'EOF' || true
CREATE DATABASE IF NOT EXISTS news_portal DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- root 계정 패스워드 없이 접속 가능하도록 권한 설정
ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('');
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED VIA mysql_native_password USING PASSWORD('') WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED VIA mysql_native_password USING PASSWORD('') WITH GRANT OPTION;

-- 전용 news_user 계정 생성 (news_pass)
GRANT ALL PRIVILEGES ON news_portal.* TO 'news_user'@'%' IDENTIFIED BY 'news_pass';
GRANT ALL PRIVILEGES ON news_portal.* TO 'news_user'@'localhost' IDENTIFIED BY 'news_pass';
GRANT ALL PRIVILEGES ON news_portal.* TO 'news_user'@'127.0.0.1' IDENTIFIED BY 'news_pass';

FLUSH PRIVILEGES;
EOF

if [ -f /var/www/html/schema.sql ]; then
    echo "[+] 5. 테이블 스키마 초기화 중..."
    mysql news_portal < /var/www/html/schema.sql 2>/dev/null || true
fi

# uploads 폴더 권한 확인
mkdir -p /var/www/html/uploads
chmod -R 777 /var/www/html/uploads
chown -R www-data:www-data /var/www/html

echo "[+] 모든 서비스 준비 완료! Apache를 실행합니다."
exec apache2-foreground
