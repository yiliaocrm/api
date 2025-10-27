# 多阶段构建 - PHP 基础镜像
FROM php:8.4-fpm-alpine AS base

# 设置工作目录
WORKDIR /var/www/html

# 安装系统依赖和 PHP 扩展
RUN apk add --no-cache \
    # 基础工具
    curl \
    curl-dev \
    git \
    unzip \
    zip \
    p7zip \
    # MySQL 相关
    mysql-client \
    # GD 图片处理库依赖
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    # ImageMagick 依赖
    imagemagick \
    imagemagick-dev \
    # ICU 国际化库（intl 扩展依赖）
    icu-libs \
    icu-dev \
    # Zip 扩展依赖
    libzip-dev \
    # Redis 扩展依赖
    autoconf \
    g++ \
    make \
    # xlswriter 扩展依赖
    zlib-dev \
    # Supervisor 进程管理
    supervisor \
    # ffmpeg（视频处理，可选）
    ffmpeg \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        opcache \
        pcntl \
        curl \
        intl \
    # 安装 Redis 扩展
    && pecl install redis \
    && docker-php-ext-enable redis \
    # 编译安装 xlswriter 扩展（高性能 Excel 导出）
    && pecl install xlswriter \
    && docker-php-ext-enable xlswriter \
    # 编译安装 imagick 扩展（图片处理）
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    # 清理编译依赖减小镜像体积（保留运行时库）
    # 注意：icu-libs 会被保留作为运行时依赖
    && apk del autoconf g++ make imagemagick-dev icu-dev \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 复制 PHP 配置
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# 复制 Supervisor 配置
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 创建日志目录
RUN mkdir -p /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html

# ============================================
# 前端资源获取阶段
# ============================================
FROM alpine:3.22 AS frontend-fetcher

# 安装 git 和 7zip
RUN apk add --no-cache git p7zip

# 克隆前端构建仓库
ARG FRONTEND_REPO=https://gitee.com/yiliaocrm/frontend-dist.git
ARG FRONTEND_REF=master
RUN git clone --depth=1 --branch "${FRONTEND_REF}" "${FRONTEND_REPO}" /frontend

# 解压前端资源
RUN mkdir -p /dist \
    && 7z x /frontend/dist.7z -o/tmp/frontend \
    && for dir in web his install admin; do \
        if [ -d "/tmp/frontend/${dir}" ]; then \
          cp -r "/tmp/frontend/${dir}" /dist/; \
        fi; \
       done

# ============================================
# 开发环境镜像
# ============================================
FROM base AS development

# 安装 Xdebug（仅开发环境）
# 注意：PHP 8.4 可能需要 Xdebug 最新版本，如果安装失败可以注释掉
# 开发环境不影响基本功能，需要调试时再手动安装
# RUN apk add --no-cache $PHPIZE_DEPS \
#     && pecl install xdebug \
#     && docker-php-ext-enable xdebug \
#     && apk del $PHPIZE_DEPS

# 切换到 www-data 用户
USER www-data

# 暴露端口
EXPOSE 9000

# 启动 PHP-FPM
CMD ["php-fpm"]

# ============================================
# 生产环境镜像
# ============================================
FROM base AS production

# 复制应用代码
COPY --chown=www-data:www-data . /var/www/html

# 从 frontend-fetcher 阶段复制前端资源
COPY --from=frontend-fetcher --chown=www-data:www-data /dist /var/www/html/public/dist

# 安装 Composer 依赖（生产环境优化）
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    && composer clear-cache

# 设置正确的权限
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Laravel 优化（生产环境）
RUN php artisan config:cache || true \
    && php artisan route:cache || true \
    && php artisan view:cache || true

# 切换到 www-data 用户
USER www-data

# 暴露端口
EXPOSE 9000

# 健康检查
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
    CMD php artisan tinker --execute="echo 'OK';" || exit 1

# 启动 PHP-FPM
CMD ["php-fpm"]
