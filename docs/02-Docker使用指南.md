# HIS 诊所管理系统 - Docker 部署指南

## 一、普通用户：快速启动

### 前置条件

- 已安装 [Docker Desktop](https://www.docker.com/products/docker-desktop/)

### 启动步骤

```bash
git clone <仓库地址>
cd his
docker-compose up -d
```

等待约 1-2 分钟，访问 http://localhost:8080

### 首次启动后执行数据库迁移

```bash
docker-compose exec app php artisan migrate --force
```

### 默认配置

| 项目 | 值 |
|------|----|
| 访问地址 | http://localhost:8080 |
| MySQL 端口 | 3306 |
| Redis 端口 | 6379 |
| 数据库名 | saas |
| 数据库密码 | 123456 |

### 常用命令

```bash
# 查看容器状态
docker-compose ps

# 查看应用日志
docker-compose logs -f app

# 停止服务（数据保留）
docker-compose down

# 进入 PHP 容器
docker-compose exec app bash

# 执行 Artisan 命令
docker-compose exec app php artisan migrate:status
```

### 故障排查

**端口冲突**：如果 8080 或 3306 端口被占用，直接编辑 `docker-compose.yml` 修改端口映射。

**数据库连接失败**：等待 MySQL 完全启动（约 30 秒），查看日志：
```bash
docker-compose logs mysql
```

**Windows 下 storage 权限问题**：如果看到 `Permission denied` 相关错误，执行：
```bash
docker-compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

**清理重装**：
```bash
docker-compose down -v   # 删除容器和数据卷（数据会清空）
docker-compose up -d
```

---

## 二、维护方：构建并发布镜像

### 前置条件

1. 已安装 Docker
2. 已登录阿里云容器镜像服务：
   ```bash
   docker login registry.cn-hangzhou.aliyuncs.com
   ```
3. 本地已执行 `composer install`（确保 `composer.lock` 存在）

### 一键构建并推送

```bash
# 推送 latest 标签
./build-push.sh

# 推送指定版本标签
./build-push.sh v1.2.0
```

### 何时需要重新构建

- `composer.json` 或 `composer.lock` 有变更
- PHP 扩展有变更（修改了 `docker/php/Dockerfile.prebuilt` 或 `docker/php/docker-entrypoint.sh`）
- 需要发布新版本

### 本地构建调试

如需在本机构建并测试（不推送），使用维护方专用 compose 文件：

```bash
# 复制并编辑环境变量
cp .env.docker.example .env.docker

# 本地构建并启动
docker-compose -f docker-compose.build.yml --env-file .env.docker up -d --build
```

### 镜像仓库信息

| 项目 | 值 |
|------|----|
| 仓库地址 | registry.cn-hangzhou.aliyuncs.com/yiliaocrm/his |
| 命名空间 | yiliaocrm |
| 仓库名 | his |
