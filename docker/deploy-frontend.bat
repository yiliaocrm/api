@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo 🚀 开始部署前端资源...
echo.

REM 生成临时目录名
set TEMP_DIR=%TEMP%\frontend-dist-%RANDOM%

echo [1/5] 克隆前端资源仓库...
git clone --depth=1 https://gitee.com/yiliaocrm/frontend-dist.git "%TEMP_DIR%"
if errorlevel 1 (
    echo ❌ 克隆失败，请检查网络连接
    exit /b 1
)

echo [2/5] 复制压缩包到项目根目录...
copy "%TEMP_DIR%\dist.7z" . >nul
if errorlevel 1 (
    echo ❌ 复制失败
    rd /s /q "%TEMP_DIR%"
    exit /b 1
)

echo [3/5] 在容器中解压前端文件...
docker-compose exec -u root -T php 7z x -y dist.7z -o/tmp/frontend
if errorlevel 1 (
    echo ❌ 解压失败，请确保容器中已安装 7z
    del dist.7z
    rd /s /q "%TEMP_DIR%"
    exit /b 1
)

echo [4/5] 移动文件到 public/dist 目录...
docker-compose exec -u root -T php sh -c "mkdir -p public/dist && cd /tmp/frontend && for dir in admin his install web; do if [ -d $dir ]; then mv $dir /var/www/html/public/dist/; fi; done"
if errorlevel 1 (
    echo ❌ 移动文件失败
    del dist.7z
    rd /s /q "%TEMP_DIR%"
    exit /b 1
)

echo [5/5] 清理临时文件...
del dist.7z >nul 2>&1
rd /s /q "%TEMP_DIR%" >nul 2>&1
docker-compose exec -u root -T php rm -rf /tmp/frontend >nul 2>&1

echo.
echo ✅ 前端资源部署完成！
echo 📁 已部署到 public/dist 目录
