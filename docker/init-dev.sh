#!/bin/bash

# ============================================
# Docker 开发环境一键初始化脚本
# ============================================

set -e  # 遇到错误立即退出

echo "🚀 开始初始化 Docker 开发环境..."
echo ""

# 1. 检查容器是否运行
echo "📦 检查容器状态..."
if ! docker-compose ps | grep -q "clinic-php.*Up"; then
    echo "❌ 错误：PHP 容器未运行，请先执行 docker-compose up -d"
    exit 1
fi
echo "✅ 容器运行正常"
echo ""

# 2. 安装 Composer 依赖
echo "📚 安装 Composer 依赖..."
docker-compose exec -u root -T php composer install --no-interaction
echo "✅ 依赖安装完成"
echo ""

# 3. 创建必要的目录
echo "📁 创建存储目录..."
docker-compose exec -u root -T php mkdir -p \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/sessions \
    storage/logs
echo "✅ 目录创建完成"
echo ""

# 4. 修复权限
echo "🔐 修复文件权限..."
docker-compose exec -u root -T php chown -R www-data:www-data \
    vendor \
    storage \
    bootstrap/cache
docker-compose exec -u root -T php chmod -R 775 \
    storage \
    bootstrap/cache
echo "✅ 权限修复完成"
echo ""

# 5. 生成应用密钥
echo "🔑 生成应用密钥..."
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    docker-compose exec -u root -T php php artisan key:generate --ansi
    echo "✅ 密钥生成完成"
else
    echo "⏭️  密钥已存在，跳过"
fi
echo ""

# 6. 重启服务
echo "🔄 重启 PHP 和 Nginx 服务..."
docker-compose restart php nginx >/dev/null 2>&1
echo "✅ 服务重启完成"
echo ""

echo "🎉 初始化完成！"
echo ""
echo "📝 下一步："
echo "   1. 访问 http://localhost:8080"
echo "   2. 按照安装向导完成配置"
echo ""
