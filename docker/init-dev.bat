@echo off
chcp 65001 >nul
echo 🚀 开始初始化 Docker 开发环境...
echo.

REM 检查 Docker 容器状态
docker-compose ps | findstr "clinic-php" | findstr "Up" >nul
if errorlevel 1 (
    echo ❌ 错误：PHP 容器未运行，请先执行 docker-compose up -d
    exit /b 1
)

echo [1/4] 安装 Composer 依赖...
docker-compose exec -u root -T php composer install --no-interaction
if errorlevel 1 (
    echo ❌ Composer 安装失败
    exit /b 1
)

echo [2/4] 创建必要目录...
docker-compose exec -u root -T php mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs
if errorlevel 1 (
    echo ❌ 目录创建失败
    exit /b 1
)

echo [3/4] 生成应用密钥...
findstr /C:"APP_KEY=base64:" .env >nul 2>&1
if errorlevel 1 (
    docker-compose exec -u root -T php php artisan key:generate --ansi
)

echo [4/4] 重启服务...
docker-compose restart php nginx >nul 2>&1

echo.
echo ✅ 初始化完成！
echo 📝 访问 http://localhost:8080 开始安装
