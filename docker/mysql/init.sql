-- 创建中央数据库（多租户中央库）
CREATE DATABASE IF NOT EXISTS saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 刷新权限
FLUSH PRIVILEGES;
