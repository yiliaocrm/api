#!/bin/bash
set -e

echo "=========================================="
echo "初始化蝉印诊所管家数据库"
echo "=========================================="

# 创建中心数据库（已由环境变量 MYSQL_DATABASE 创建）
echo "中心数据库: clinic_central (已创建)"

# 创建默认租户数据库
echo "创建默认租户数据库..."
mysql -u root -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`clinic_tenant_demo\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL PRIVILEGES ON \`clinic_tenant_demo\`.* TO '$MYSQL_USER'@'%';
    FLUSH PRIVILEGES;
EOSQL

echo "数据库初始化完成！"
echo "=========================================="
echo "中心数据库: clinic_central"
echo "租户数据库: clinic_tenant_demo"
echo "用户: $MYSQL_USER"
echo "=========================================="
