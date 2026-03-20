#!/bin/bash
set -e

echo "Starting HIS Docker Container..."

# 等待 MySQL 就绪
echo "Waiting for MySQL..."
until nc -z mysql 3306; do
    echo "MySQL is unavailable - sleeping"
    sleep 2
done
echo "MySQL is up!"

# 等待 Redis 就绪
echo "Waiting for Redis..."
until nc -z redis 6379; do
    echo "Redis is unavailable - sleeping"
    sleep 2
done
echo "Redis is up!"

# 检查并创建 .env 文件
if [ ! -f ".env" ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
    echo ".env file created successfully!"

    # 自动修改容器环境的数据库和缓存配置
    echo "Configuring .env for Docker environment..."
    sed -i 's/^DB_HOST=.*/DB_HOST=mysql/' .env
    sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' .env
    echo "Docker environment configuration completed!"
fi

# vendor 目录处理：预构建镜像已预装，只需生成自动加载文件；否则完整安装
if [ -d "vendor" ]; then
    echo "Vendor directory exists (prebuilt), generating autoloader..."
    composer dump-autoload --optimize --no-interaction
else
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# 检查并生成应用密钥
if [ -f ".env" ]; then
    if ! grep -q "^APP_KEY=base64:" .env || grep -q "^APP_KEY=$" .env; then
        echo "Generating application key..."
        php artisan key:generate --ansi
    else
        echo "Application key already exists, skipping..."
    fi
fi

# 创建 storage 软链接
if [ ! -L "public/storage" ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

# 初始化前端静态资源
init_frontend() {
    local dist_dir="/var/www/html/public/dist"
    local required_dirs=("admin" "his" "install" "web")
    local frontend_repo="https://github.com/yiliaocrm/frontend-dist"
    local tmp_dir="/tmp/frontend-dist"

    # 检测四个目录是否都存在
    local all_exist=true
    for dir in "${required_dirs[@]}"; do
        if [ ! -d "${dist_dir}/${dir}" ]; then
            all_exist=false
            break
        fi
    done

    if [ "$all_exist" = "true" ]; then
        echo "Frontend dist directories already exist, skipping initialization."
        return 0
    fi

    echo "Frontend dist incomplete, initializing..."

    # 清理可能存在的残留目录（防止上次失败后残留）
    rm -rf "$tmp_dir"

    # 克隆前端资源仓库
    echo "Cloning frontend-dist from GitHub..."
    git clone --depth 1 "$frontend_repo" "$tmp_dir" \
        || { echo "ERROR: Failed to clone frontend-dist. Check network and GitHub availability." >&2; return 1; }

    # 确保目标目录存在
    mkdir -p "$dist_dir" || { echo "ERROR: Cannot create dist dir $dist_dir." >&2; rm -rf "$tmp_dir"; return 1; }

    # 移动四个目录到 public/dist/
    echo "Moving frontend directories to $dist_dir ..."
    for dir in "${required_dirs[@]}"; do
        if [ ! -d "${tmp_dir}/${dir}" ]; then
            echo "ERROR: Expected directory '${dir}' not found after extraction." >&2
            rm -rf "$tmp_dir"
            return 1
        fi
        rm -rf "${dist_dir:?}/${dir}"
        mv "${tmp_dir}/${dir}" "$dist_dir/"
    done

    # 设置前端目录归属（PHP-FPM 以 www-data 运行，需要读取权限）
    chown -R www-data:www-data "$dist_dir" \
        || echo "WARN: chown failed on $dist_dir, check container user privileges." >&2

    # 清理临时目录
    rm -rf "$tmp_dir"

    echo "Frontend initialization completed successfully."
}

init_frontend

# 设置权限
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
# 只修改目录权限，不修改文件权限（避免 git 检测到 .gitignore 等文件权限变化）
find /var/www/html/storage /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;
# 保持文件权限为 664
find /var/www/html/storage /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;

# 设置 .env 文件权限（确保安装程序可以写入）
if [ -f ".env" ]; then
    echo "Setting .env file permissions..."
    chown www-data:www-data .env
    chmod 664 .env
fi

echo "Starting services..."
# 启动 Supervisor（它会进一步启动 PHP-FPM 和 Horizon）
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
