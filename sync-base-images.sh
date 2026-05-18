#!/usr/bin/env bash
# ==============================================================
# HIS 诊所管理系统 - Docker 基础镜像同步脚本
#
# 用途：将 nginx、mysql、redis 官方镜像重标记并推送到阿里云容器镜像服务
# 用法：
#   ./sync-base-images.sh
#   ./sync-base-images.sh registry.cn-hangzhou.aliyuncs.com yiliaocrm
#
# 前置条件：
#   1. 已安装 Docker
#   2. 已执行 docker login registry.cn-hangzhou.aliyuncs.com
# ==============================================================

set -euo pipefail

REGISTRY="${1:-registry.cn-hangzhou.aliyuncs.com}"
NAMESPACE="${2:-yiliaocrm}"

IMAGES=(
    "nginx:1.28.3-alpine|nginx:1.28.3-alpine"
    "mysql:8.0.45-debian|mysql:8.0.45-debian"
    "redis:7.4.9-alpine3.21|redis:7.4.9-alpine3.21"
)

echo ""
echo "=================================================="
echo "  HIS Docker 基础镜像同步"
echo "  目标仓库: ${REGISTRY}/${NAMESPACE}"
echo "=================================================="
echo ""

if ! command -v docker >/dev/null 2>&1; then
    echo "错误：未找到 docker 命令，请先安装 Docker"
    exit 1
fi

if ! docker info >/dev/null 2>&1; then
    echo "错误：Docker 未启动，或当前用户没有权限访问 Docker"
    exit 1
fi

for image_pair in "${IMAGES[@]}"; do
    source_image="${image_pair%%|*}"
    target_name="${image_pair##*|}"
    target_image="${REGISTRY}/${NAMESPACE}/${target_name}"

    echo ">> 同步 ${source_image}"
    echo "   目标 ${target_image}"

    docker pull "${source_image}"
    docker tag "${source_image}" "${target_image}"

    if ! docker push "${target_image}"; then
        echo ""
        echo "推送失败，请先登录阿里云容器镜像服务："
        echo ""
        echo "    docker login ${REGISTRY}"
        echo ""
        echo "登录后重新执行此脚本"
        exit 1
    fi

    echo "   ✓ ${target_image} 已推送"
    echo ""
done

echo "=================================================="
echo "  ✓ 基础镜像同步完成"
echo "=================================================="
echo ""
