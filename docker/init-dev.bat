@echo off
chcp 65001 >nul
echo 🚀 开始初始化 Docker 开发环境...
echo.

REM 0. 准备环境变量文件
if not exist .env (
    echo 📋 复制环境配置文件...
    copy docker\.env.docker .env >nul
    echo ✅ 环境文件创建完成
    echo.
)

REM 1. 启动容器
echo 📦 启动容器...
docker-compose up -d
if errorlevel 1 (
    echo ❌ 容器启动失败
    exit /b 1
)
echo ✅ 容器启动完成
echo.

REM 等待容器完全启动
echo ⏳ 等待容器启动...
timeout /t 5 /nobreak >nul
echo.

REM 2. 安装 Composer 依赖
echo 📚 安装 Composer 依赖...
docker-compose exec -u root -T php composer install --no-interaction
if errorlevel 1 (
    echo ❌ Composer 安装失败
    exit /b 1
)
echo ✅ 依赖安装完成
echo.

REM 3. 创建必要目录
echo 📁 创建存储目录...
docker-compose exec -u root -T php mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs
if errorlevel 1 (
    echo ❌ 目录创建失败
    exit /b 1
)
echo ✅ 目录创建完成
echo.

REM 4. 生成应用密钥
echo 🔑 生成应用密钥...
findstr /C:"APP_KEY=base64:" .env >nul 2>&1
if errorlevel 1 (
    docker-compose exec -u root -T php php artisan key:generate --ansi
    echo ✅ 密钥生成完成
) else (
    echo ⏭️  密钥已存在，跳过
)
echo.

REM 5. 重启服务
echo 🔄 重启 PHP 和 Nginx 服务...
docker-compose restart php nginx >nul 2>&1
echo ✅ 服务重启完成
echo.

echo 🎉 初始化完成！
echo.
echo 📝 下一步：
echo    1. 访问 http://localhost:8080
echo    2. 按照安装向导完成配置
echo.
