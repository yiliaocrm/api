#!/bin/bash

# ============================================
# 前端资源自动部署脚本（开发环境）
# ============================================

set -e

echo "🎨 开始部署前端资源..."
echo ""

# 1. 检查容器是否运行
echo "📦 检查容器状态..."
if ! docker-compose ps | grep -q "clinic-php.*Up"; then
    echo "❌ 错误：PHP 容器未运行，请先执行 docker-compose up -d"
    exit 1
fi
echo "✅ 容器运行正常"
echo ""

# 2. 检查是否已部署
if [ -d "public/dist/install" ] && [ -f "public/dist/install/index.html" ]; then
    echo "⏭️  前端资源已存在，跳过部署"
    echo ""
    exit 0
fi

# 3. 克隆前端仓库
echo "📥 克隆前端资源仓库..."
TEMP_DIR="/tmp/frontend-dist-$$"
git clone --depth=1 https://gitee.com/yiliaocrm/frontend-dist.git "$TEMP_DIR"
echo "✅ 克隆完成"
echo ""

# 4. 复制到项目目录（容器可以访问）
echo "📦 复制前端资源到项目目录..."
cp "$TEMP_DIR/dist.7z" ./
echo "✅ 复制完成"
echo ""

# 5. 在容器内解压
echo "📦 解压前端资源..."
docker-compose exec -u root -T php sh -c "7z x -y dist.7z -o/tmp/frontend"
echo "✅ 解压完成"
echo ""

# 6. 移动到正确位置
echo "📂 部署前端文件..."
docker-compose exec -u root -T php sh -c "
    mkdir -p public/dist
    cd /tmp/frontend
    for dir in admin his install web; do
        if [ -d \$dir ]; then
            mv \$dir /var/www/html/public/dist/
        fi
    done
"
echo "✅ 部署完成"
echo ""

# 7. 清理临时文件
echo "🧹 清理临时文件..."
rm -rf "$TEMP_DIR" ./dist.7z
docker-compose exec -u root -T php rm -rf /tmp/frontend
echo "✅ 清理完成"
echo ""

# 7. 验证部署
echo "🔍 验证部署结果..."
if docker-compose exec -T php test -f public/dist/install/index.html; then
    echo "✅ 前端资源部署成功！"
    echo ""
    echo "📁 已部署的模块："
    docker-compose exec -T php ls -1 public/dist/ 2>/dev/null || true
else
    echo "❌ 部署验证失败"
    exit 1
fi
echo ""

echo "🎉 前端资源部署完成！"
