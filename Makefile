.PHONY: help up down restart ps logs shell composer artisan test init deploy-frontend clean rebuild

# 默认目标：显示帮助信息
help:
	@echo "蝉印诊所管家 - Docker 管理命令"
	@echo ""
	@echo "常用命令:"
	@echo "  make up              - 启动所有容器"
	@echo "  make down            - 停止并删除所有容器"
	@echo "  make restart         - 重启所有容器"
	@echo "  make ps              - 查看容器状态"
	@echo "  make logs            - 查看所有容器日志"
	@echo "  make logs-php        - 查看 PHP 容器日志"
	@echo "  make logs-nginx      - 查看 Nginx 容器日志"
	@echo "  make logs-mysql      - 查看 MySQL 容器日志"
	@echo "  make logs-queue      - 查看队列容器日志"
	@echo ""
	@echo "初始化命令:"
	@echo "  make init            - 初始化开发环境（首次使用）"
	@echo "  make deploy-frontend - 部署前端资源"
	@echo ""
	@echo "容器操作:"
	@echo "  make shell           - 进入 PHP 容器 Shell"
	@echo "  make shell-root      - 以 root 身份进入 PHP 容器"
	@echo "  make shell-mysql     - 进入 MySQL 容器"
	@echo "  make shell-redis     - 进入 Redis 容器"
	@echo ""
	@echo "Composer & Artisan:"
	@echo "  make composer CMD=\"install\"        - 执行 Composer 命令"
	@echo "  make composer-install               - 安装 Composer 依赖"
	@echo "  make composer-update                - 更新 Composer 依赖"
	@echo "  make artisan CMD=\"migrate\"         - 执行 Artisan 命令"
	@echo "  make migrate                        - 运行数据库迁移"
	@echo "  make migrate-tenant                 - 运行租户数据库迁移"
	@echo "  make cache-clear                    - 清理缓存"
	@echo "  make queue-restart                  - 重启队列"
	@echo ""
	@echo "数据库操作:"
	@echo "  make db-backup       - 备份数据库"
	@echo "  make db-restore      - 还原数据库"
	@echo ""
	@echo "测试:"
	@echo "  make test            - 运行测试"
	@echo ""
	@echo "维护命令:"
	@echo "  make clean           - 清理容器和数据卷"
	@echo "  make rebuild         - 重新构建并启动容器"

# ============================================
# 容器管理
# ============================================

# 启动容器
up:
	docker-compose up -d

# 停止容器
down:
	docker-compose down

# 重启容器
restart:
	docker-compose restart

# 查看容器状态
ps:
	docker-compose ps

# 查看所有日志
logs:
	docker-compose logs -f

# 查看 PHP 日志
logs-php:
	docker-compose logs -f php

# 查看 Nginx 日志
logs-nginx:
	docker-compose logs -f nginx

# 查看 MySQL 日志
logs-mysql:
	docker-compose logs -f mysql

# 查看队列日志
logs-queue:
	docker-compose logs -f queue

# ============================================
# 初始化命令
# ============================================

# 初始化开发环境
init:
	@bash docker/init-dev.sh

# 部署前端资源
deploy-frontend:
	@bash docker/deploy-frontend.sh

# ============================================
# 容器操作
# ============================================

# 进入 PHP 容器（www-data 用户）
shell:
	docker-compose exec php sh

# 进入 PHP 容器（root 用户）
shell-root:
	docker-compose exec -u root php sh

# 进入 MySQL 容器
shell-mysql:
	docker-compose exec mysql mysql -u root -proot_password clinic_central

# 进入 Redis 容器
shell-redis:
	docker-compose exec redis redis-cli

# ============================================
# Composer 命令
# ============================================

# 执行 Composer 命令（使用 make composer CMD="install"）
composer:
	docker-compose exec -u root -T php composer $(CMD)

# 安装 Composer 依赖
composer-install:
	docker-compose exec -u root -T php composer install --no-interaction

# 更新 Composer 依赖
composer-update:
	docker-compose exec -u root -T php composer update --no-interaction

# ============================================
# Artisan 命令
# ============================================

# 执行 Artisan 命令（使用 make artisan CMD="migrate"）
artisan:
	docker-compose exec -T php php artisan $(CMD)

# 运行数据库迁移
migrate:
	docker-compose exec -T php php artisan migrate --force

# 运行租户数据库迁移
migrate-tenant:
	docker-compose exec -T php php artisan tenants:migrate

# 清理缓存
cache-clear:
	docker-compose exec -T php php artisan cache:clear
	docker-compose exec -T php php artisan config:clear
	docker-compose exec -T php php artisan route:clear
	docker-compose exec -T php php artisan view:clear

# 重启队列
queue-restart:
	docker-compose exec -T php php artisan queue:restart

# ============================================
# 数据库操作
# ============================================

# 备份数据库
db-backup:
	@echo "📦 备份数据库..."
	docker-compose exec mysql mysqldump -u clinic -pclinic_password clinic_central > backup-$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ 备份完成: backup-$(shell date +%Y%m%d_%H%M%S).sql"

# 还原数据库（需要指定文件 make db-restore FILE=backup.sql）
db-restore:
	@if [ -z "$(FILE)" ]; then \
		echo "❌ 错误: 请指定备份文件，例如: make db-restore FILE=backup.sql"; \
		exit 1; \
	fi
	@echo "📥 还原数据库: $(FILE)"
	docker-compose exec -T mysql mysql -u clinic -pclinic_password clinic_central < $(FILE)
	@echo "✅ 还原完成"

# ============================================
# 测试
# ============================================

# 运行测试
test:
	docker-compose exec -T php php artisan test

# ============================================
# 维护命令
# ============================================

# 清理容器和数据卷
clean:
	@echo "🧹 清理容器和数据卷..."
	docker-compose down -v
	@echo "✅ 清理完成"

# 重新构建并启动
rebuild:
	@echo "🔨 重新构建容器..."
	docker-compose down
	docker-compose build --no-cache
	docker-compose up -d
	@echo "✅ 重新构建完成"
