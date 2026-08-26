FROM php:8.2-apache

# MariaDB 및 필수 패키지 설치
RUN apt-get update && apt-get install -y \
    mariadb-server \
    mariadb-client \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    dos2unix \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli pdo pdo_mysql gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Apache 설정: AllowOverride All 활성화 + 디렉토리 리스팅 기본 허용 (취약 실습 기본 상태)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && printf '\n<Directory /var/www/html/>\n    AllowOverride All\n    Options Indexes FollowSymLinks\n    Require all granted\n</Directory>\n' >> /etc/apache2/apache2.conf

# 웹 소스코드 복사
COPY . /var/www/html/

# 업로드 폴더 및 플래그 파일 권한 설정
RUN mkdir -p /var/www/html/uploads \
    && chmod -R 777 /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && if [ -f /var/www/html/flag.txt ]; then cp /var/www/html/flag.txt /flag.txt && chmod 644 /flag.txt; fi

# 시작 스크립트 복사 및 CRLF -> LF 변환 + 실행 권한 부여
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN dos2unix /usr/local/bin/docker-entrypoint.sh && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
