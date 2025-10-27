FROM nginx:alpine

# 复制 Nginx 配置文件
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# 创建工作目录
WORKDIR /var/www/html

# 暴露端口
EXPOSE 80

# 健康检查
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s \
    CMD wget --quiet --tries=1 --spider http://localhost/health || exit 1

# 启动 Nginx
CMD ["nginx", "-g", "daemon off;"]
