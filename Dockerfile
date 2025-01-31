FROM --platform=linux/amd64 debian:stable

# 安装package
RUN apt update && \
    apt install -y \
    tzdata \
    nano \
    ca-certificates \
    curl \
    gnupg \
    bc \
    apache2 \
    php \
    php-dev \
    php-cli \
    php-mysql \
    php-bcmath \
    php-json \
    php-mbstring \
    php8.2-common \
    php-tokenizer \
    php-xml \
    php-pear \
    php-zip \
    php-curl \
    php-redis \
    php-gd \
    php-imagick \
    php-opcache \
    composer \
    libapache2-mod-php

# 設置時區
RUN ln -snf /usr/share/zoneinfo/"Asia/Taipei" /etc/localtime && \
    echo "Asia/Taipei" > /etc/timezone && \
    dpkg-reconfigure -f noninteractive tzdata

# RUN sed -i 's/Listen 80/Listen 8081/g' /etc/apache2/ports.conf && \
# sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8081>/g' /etc/apache2/sites-available/000-default.conf

# 設置權限
RUN groupadd webadmin && \
    usermod -a -G webadmin www-data && \
    usermod -a -G webadmin root && \
    chown -R root:webadmin /var/www && \
    chmod g+s /var/www && \
    find /var/www -type d -exec chmod g+s {} + && \
    find /var/www -type d -exec chmod 775 {} + && \
    find /var/www -type f -exec chmod 664 {} + && \
    echo 'umask 002' >> ~/.profile && \
    echo 'umask 002' >> ~/.bashrc

# Apache 配置
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    echo "<IfModule mpm_prefork_module>\nStartServers 2\nMinSpareServers 2\nMaxSpareServers 4\nServerLimit 32\nMaxClients 32\nMaxRequestsPerChild 512\n</IfModule>" >> /etc/apache2/apache2.conf && \
    sed -i 's|DocumentRoot \/var\/www\/html|DocumentRoot \/var\/www\/html\n<Directory \/var\/www\/html>\nOptions Indexes FollowSymLinks\nAllowOverride All\nRequire all granted\n<\/Directory>\n<FilesMatch \\.php\$>\nSetHandler \"application\/x-httpd-php\"\n<\/FilesMatch>|g' /etc/apache2/sites-available/000-default.conf

# 启用和禁用Apache模块
RUN a2dismod mpm_event && \
    a2enmod rewrite setenvif mpm_prefork php8.2 && \
    a2ensite 000-default

# EXPOSE 8081
EXPOSE 80

CMD ["apachectl", "-D", "FOREGROUND"]
