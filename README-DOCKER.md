# Docker 部署文档

蝉印诊所管家 Docker 部署指南，支持开发和生产环境一键部署。

## 环境要求

- Docker Engine >= 20.10
- Docker Compose >= 2.0
- 可用内存 >= 4GB
- 可用磁盘 >= 20GB

---

## 安装 Docker

### macOS

**方式一：Docker Desktop（推荐）**

1. 下载：https://www.docker.com/products/docker-desktop/
2. 安装并启动 Docker Desktop
3. 验证安装：
```bash
docker --version
docker-compose --version
```

**方式二：Homebrew**
```bash
brew install --cask docker
```

### Linux

**Ubuntu/Debian**
```bash
# 安装 Docker
curl -fsSL https://get.docker.com | bash -s docker

# 启动 Docker
sudo systemctl start docker
sudo systemctl enable docker

# 添加当前用户到 docker 组（避免每次 sudo）
sudo usermod -aG docker $USER

# 重新登录生效，或执行
newgrp docker

# 验证安装
docker --version
docker-compose --version
```

**CentOS/RHEL**
```bash
# 安装 Docker
curl -fsSL https://get.docker.com | bash -s docker

# 启动 Docker
sudo systemctl start docker
sudo systemctl enable docker

# 添加用户到 docker 组
sudo usermod -aG docker $USER
newgrp docker
```

### Windows

1. 下载 Docker Desktop：https://www.docker.com/products/docker-desktop/
2. 安装并启动
3. 在 PowerShell 中验证：
```powershell
docker --version
docker-compose --version
```

---

## 镜像加速配置（国内推荐）

### Docker Hub 镜像加速

国内拉取 Docker 镜像较慢，建议配置镜像加速器：

**方式一：Docker Desktop（macOS/Windows）**

1. 打开 Docker Desktop
2. 进入 Settings → Docker Engine
3. 添加以下配置：

```json
{
  "registry-mirrors": [
    "https://docker.m.daocloud.io",
    "https://docker.nju.edu.cn",
    "https://docker.mirrors.sjtug.sjtu.edu.cn"
  ]
}
```

4. 点击 Apply & Restart

**方式二：Linux**

编辑 `/etc/docker/daemon.json`：

```bash
sudo mkdir -p /etc/docker
sudo tee /etc/docker/daemon.json <<-'EOF'
{
  "registry-mirrors": [
    "https://docker.m.daocloud.io",
    "https://docker.nju.edu.cn",
    "https://docker.mirrors.sjtug.sjtu.edu.cn"
  ]
}
EOF

sudo systemctl daemon-reload
sudo systemctl restart docker
```

**验证配置**
```bash
docker info | grep -A 5 "Registry Mirrors"
```

### Composer 镜像加速

项目已在 `composer.json` 中配置了阿里云镜像：

```json
{
  "repositories": {
    "packagist": {
      "type": "composer",
      "url": "https://mirrors.aliyun.com/composer/"
    }
  }
}
```

无需额外配置，自动使用国内源。

### npm/yarn 镜像加速（可选）

如果需要本地开发前端：

```bash
# 使用淘宝镜像
npm config set registry https://registry.npmmirror.com

# 或使用 yarn
yarn config set registry https://registry.npmmirror.com
```

---

## 快速开始（开发环境）

**所有平台使用同一个配置文件** `docker-compose.yml`，只是初始化脚本根据操作系统不同：

只需 **4 步** 完成部署：

### Linux / macOS

```bash
# 1. 准备环境变量
cp .env.docker .env

# 2. 启动容器
docker-compose up -d

# 3. 一键初始化
./docker/init-dev.sh

# 4. 部署前端（可选）
./docker/deploy-frontend.sh
```

### Windows (CMD / PowerShell)

```powershell
# 1. 准备环境变量
copy .env.docker .env

# 2. 启动容器
docker-compose up -d

# 3. 一键初始化
docker\init-dev.bat

# 4. 部署前端（可选）
docker\deploy-frontend.bat
```

### Windows (Git Bash)

```bash
# 与 Linux/macOS 相同
cp .env.docker .env
docker-compose up -d
bash ./docker/init-dev.sh
bash ./docker/deploy-frontend.sh
```

然后访问 http://localhost:8080 完成安装向导。

---

### 详细步骤

### 1. 准备环境变量

```bash
# 复制 Docker 专用配置
cp .env.docker .env
```

> **提示**：`.env.docker` 已预配置好数据库连接等信息，开发环境无需修改。

**如果遇到端口冲突**，可以修改 `.env` 文件：

```bash
# 默认端口
DOCKER_NGINX_PORT=8080

# 如果 8080 端口被占用，改为其他端口
DOCKER_NGINX_PORT=8888
```

修改后访问地址也要相应修改：`http://localhost:8888`

### 2. 启动容器

```bash
# 首次启动（自动构建镜像，约需 5-10 分钟）
docker-compose up -d

# 查看容器状态
docker-compose ps
```

### 3. 初始化应用

**方式一：一键初始化（推荐）**

Linux / macOS / Git Bash:
```bash
./docker/init-dev.sh
```

Windows (CMD / PowerShell):
```powershell
docker\init-dev.bat
```

脚本会自动完成：
- ✅ 安装 Composer 依赖
- ✅ 创建存储目录
- ✅ 修复文件权限
- ✅ 生成应用密钥
- ✅ 重启服务

**方式二：手动初始化**

<details>
<summary>点击展开手动步骤</summary>

```bash
# 进入容器
docker-compose exec -u root php sh

# 安装依赖
composer install

# 创建存储目录
mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs

# 修复权限
chown -R www-data:www-data vendor storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 生成密钥
php artisan key:generate

# 退出容器
exit

# 重启服务
docker-compose restart php nginx
```

</details>

### 4. 部署前端资源（可选）

**方式一：一键部署（推荐）**

Linux / macOS / Git Bash:
```bash
./docker/deploy-frontend.sh
```

Windows (CMD / PowerShell):
```powershell
docker\deploy-frontend.bat
```

**方式二：手动部署**

<details>
<summary>点击展开手动步骤</summary>

```bash
# 克隆前端仓库
cd /tmp && git clone https://gitee.com/yiliaocrm/frontend-dist.git

# 复制并解压
cp frontend-dist/dist.7z .
docker-compose exec -u root php sh -c "7z x -y dist.7z"

# 移动到正确位置
docker-compose exec -u root php sh -c "
    mkdir -p public/dist
    mv /tmp/frontend/* public/dist/
"

# 清理
rm -rf /tmp/frontend-dist /tmp/dist.7z
docker-compose exec -u root php rm -rf /tmp/frontend
```

</details>

### 5. 完成安装

访问 http://localhost:8080，按照安装向导填写配置：

**数据库配置**（使用 docker-compose.yml 中的默认值）：
```
数据库主机：mysql
数据库端口：3306
数据库名称：clinic_central
数据库用户：root
数据库密码：root_password
```

> **提示**：安装向导需要创建数据库，因此使用 root 账号。安装完成后系统会自动使用 clinic 用户连接。

**管理员账号**（自定义设置）：
```
用户名：admin（或自定义）
密码：自己设置一个密码
邮箱：admin@example.com（或自己的邮箱）
```

安装向导步骤：
1. 环境检测 - 自动检测 PHP 扩展
2. 数据库配置 - 填写上述数据库信息
3. 创建管理员 - 填写上述管理员信息
4. 完成 - 安装成功

---

## 生产环境部署

### 1. 准备配置

```bash
# 基于 Docker 配置创建生产配置
cp .env.docker .env.production

# 编辑配置（必改项）
nano .env.production
```

**必须修改的配置**：

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# 数据库密码
DB_PASSWORD=your_secure_password
MYSQL_ROOT_PASSWORD=your_root_password

# Redis 密码（建议设置）
REDIS_PASSWORD=your_redis_password

# 文件存储（建议使用 S3/OSS）
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_BUCKET=your_bucket
```

### 2. 构建前端

```bash
# 如果需要本地前端开发
npm install
npm run build
```

### 3. 启动生产环境

```bash
# 启动（生产环境镜像会自动下载前端资源）
docker-compose -f docker-compose.prod.yml --env-file .env.production up -d --build

# 查看状态
docker-compose -f docker-compose.prod.yml ps
```

### 4. 配置 SSL（建议）

使用 Certbot 配置免费 SSL 证书：

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

---

## 服务说明

| 服务 | 对外端口 | 内部端口 | 说明 |
|------|---------|---------|------|
| nginx | 8080 (dev) / 80 (prod) | 80 | Web 服务器（唯一对外暴露的服务） |
| php | - | 9000 | PHP-FPM（仅内部访问） |
| mysql | - | 3306 | MySQL 8.0（仅内部访问，安全考虑） |
| redis | - | 6379 | Redis 缓存（仅内部访问，安全考虑） |
| queue | - | - | 队列处理 |
| scheduler | - | - | 定时任务 |
| vite | 5173 (dev) | 5173 | 前端开发服务器（仅开发环境） |

> **安全说明**：MySQL 和 Redis 端口未映射到宿主机，只能通过 Docker 内部网络访问，提升安全性并避免端口冲突。

---

## 常用命令

### 容器管理

```bash
# 启动
docker-compose up -d

# 停止
docker-compose down

# 重启
docker-compose restart

# 查看日志
docker-compose logs -f [服务名]

# 进入容器
docker-compose exec php sh
```

### Laravel 命令

```bash
# 进入容器后执行

# 清理缓存
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 数据库迁移
php artisan migrate --path=database/migrations/admin --force
php artisan tenants:migrate

# 队列管理
php artisan queue:restart
```

### 数据库备份

```bash
# 备份
docker-compose exec mysql mysqldump -u clinic -pclinic_password clinic_central > backup.sql

# 还原
docker-compose exec -T mysql mysql -u clinic -pclinic_password clinic_central < backup.sql
```

### 数据库访问

**注意**：为了安全，MySQL 和 Redis 端口未映射到宿主机，需要通过容器访问。

#### 方式 1：进入容器使用命令行

```bash
# 访问 MySQL
docker-compose exec mysql mysql -u root -proot_password clinic_central

# 访问 Redis
docker-compose exec redis redis-cli
```

#### 方式 2：临时映射端口（开发调试用）

如果需要使用图形化工具（如 Navicat），可以临时修改 docker-compose.yml 添加端口映射：

```yaml
mysql:
  ports:
    - "3306:3306"  # 临时添加此行

redis:
  ports:
    - "6379:6379"  # 临时添加此行
```

然后重启容器：
```bash
docker-compose restart mysql redis
```

使用完毕后建议移除端口映射并重启。

---

## 多租户配置

### 开发环境

修改 hosts 文件：

```
127.0.0.1 tenant1.localhost
127.0.0.1 tenant2.localhost
```

访问：http://tenant1.localhost:8080

### 生产环境

配置泛域名 DNS：`*.yourdomain.com` → 服务器 IP

访问：http://tenant1.yourdomain.com

---

## 故障排查

### 端口冲突

**问题**：启动时报错 `port is already allocated` 或 `bind: address already in use`

**原因**：宿主机端口被占用（如 8080 端口已被其他服务使用）

**解决方案**：

**方式 1：修改端口号（推荐）**

编辑 `.env` 文件：
```bash
# 修改为未被占用的端口
DOCKER_NGINX_PORT=8888
```

重启容器：
```bash
docker-compose down
docker-compose up -d
```

访问地址改为：`http://localhost:8888`

**方式 2：停止占用端口的服务**

```bash
# 查看 8080 端口占用
lsof -i :8080  # macOS/Linux
netstat -ano | findstr :8080  # Windows

# 停止占用端口的进程
kill -9 <PID>  # macOS/Linux
taskkill /PID <PID> /F  # Windows
```

### 容器无法启动

```bash
# 查看日志
docker-compose logs -f

# 检查端口占用
lsof -i :8080

# 重新构建
docker-compose down -v
docker-compose up -d --build
```

### 权限错误

```bash
# 修复 vendor 目录
docker-compose exec -u root php sh
chown -R www-data:www-data vendor
exit

# 修复 storage 目录
docker-compose exec -u root php sh
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
exit
```

### 前端 404 错误

确认前端文件已部署：

```bash
ls -la public/dist/
# 应该看到：admin, his, install, web 四个目录

# 如果缺失，按照"部署前端资源"章节操作
```

### 数据库连接失败

```bash
# 检查 MySQL 状态
docker-compose ps mysql

# 测试连接
docker-compose exec php php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 性能优化

### PHP 优化

编辑 `docker/php/php.ini`：

```ini
memory_limit = 512M
opcache.enable = 1
opcache.memory_consumption = 256
```

### MySQL 优化

编辑 `docker/mysql/my.cnf`：

```ini
innodb_buffer_pool_size = 1G
max_connections = 500
```

### Nginx 优化

编辑 `docker/nginx/default.conf`：

```nginx
# 启用 gzip
gzip on;
gzip_comp_level 6;

# 静态资源缓存
location ~* \.(jpg|jpeg|png|gif|css|js)$ {
    expires 1y;
}
```

---

## 安全建议

生产环境安全检查清单：

- [ ] 修改所有默认密码
- [ ] 设置 `APP_DEBUG=false`
- [ ] 配置 HTTPS
- [ ] 限制数据库端口仅容器内访问
- [ ] 定期备份数据库
- [ ] 使用 S3/OSS 存储替代本地存储
- [ ] 配置防火墙规则
- [ ] 监控日志和资源使用

---

## 常见问题

### 如何更新代码？

**开发环境**：
```bash
git pull
docker-compose restart
```

**生产环境**：
```bash
git pull
npm run build  # 如果有前端修改
docker-compose -f docker-compose.prod.yml down
docker-compose -f docker-compose.prod.yml up -d --build
docker-compose -f docker-compose.prod.yml exec php php artisan migrate --force
docker-compose -f docker-compose.prod.yml exec php php artisan config:cache
```

### 如何扩展队列 Worker？

编辑 `docker-compose.yml`：

```yaml
queue:
  deploy:
    replicas: 3  # 启动 3 个队列进程
```

---

## 前端说明

本项目采用前后端分离架构：
- **后端 API**：Laravel 12
- **前端界面**：Vue 3（独立部署）

### 前端模块

- `admin/` - SaaS 运营平台
- `web/` - 机构端（新版）
- `his/` - 机构端（旧版）
- `install/` - 安装向导

### 前端源码

- SaaS 运营平台：https://gitee.com/yiliaocrm/his-tenant-admin
- 安装向导：https://gitee.com/yiliaocrm/his-install-frontend
- 机构端：商用版本，暂未开源

---

## 技术支持

- 官方文档：http://help.yiliaocrm.com
- 问题反馈：https://gitee.com/yiliaocrm/api/issues
- Docker 文档：https://docs.docker.com
- Laravel 文档：https://laravel.com/docs

---

## 更新日志

**2025-10-27** - 初始版本
- 支持开发和生产环境
- 集成 MySQL、Redis、Queue、Scheduler
- 支持多租户架构
- 生产环境自动拉取前端资源
- 完整的部署和故障排查文档
