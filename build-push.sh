#!/usr/bin/env bash
# ==============================================================
# HIS 诊所管理系统 - Docker 镜像构建与推送脚本
#
# 用途：构建预装 Composer 依赖的 PHP 镜像，并推送到阿里云容器镜像服务
# 用法：
#   ./build-push.sh           # 构建并推送 latest 标签
#   ./build-push.sh v1.2.0    # 构建并推送指定版本标签
#
# 前置条件：
#   1. 已安装 Docker
#   2. 已执行 docker login registry.cn-hangzhou.aliyuncs.com
# ==============================================================

set -euo pipefail  # 任何命令失败立即退出，管道命令失败也退出，未定义变量报错

# --------------------------------------------------------------
# 配置区（如需修改镜像仓库信息，只改这里）
# --------------------------------------------------------------
REGISTRY="registry.cn-hangzhou.aliyuncs.com"
NAMESPACE="yiliaocrm"
IMAGE_NAME="his"
BUILD_CONTEXT="./docker/php"
DOCKERFILE="Dockerfile.prebuilt"

# --------------------------------------------------------------
# 读取版本标签（默认 latest）
# --------------------------------------------------------------
TAG="${1:-latest}"
FULL_IMAGE="${REGISTRY}/${NAMESPACE}/${IMAGE_NAME}:${TAG}"

# --------------------------------------------------------------
# 清理函数：无论成功或失败，都删除临时复制的文件
# --------------------------------------------------------------
cleanup() {
    rm -f "${BUILD_CONTEXT}/composer.json"
    rm -f "${BUILD_CONTEXT}/composer.lock"
}
trap cleanup EXIT

echo ""
echo "=================================================="
echo "  HIS Docker 镜像构建与推送"
echo "  目标镜像: ${FULL_IMAGE}"
echo "=================================================="
echo ""

# --------------------------------------------------------------
# 第 1 步：检查必要文件
# --------------------------------------------------------------
echo ">> [1/4] 检查必要文件..."

if [ ! -f "composer.json" ]; then
    echo "错误：找不到 composer.json，请在项目根目录执行此脚本"
    exit 1
fi

if [ ! -f "composer.lock" ]; then
    echo "错误：找不到 composer.lock，请先在本地执行 composer install"
    exit 1
fi

if [ ! -f "${BUILD_CONTEXT}/${DOCKERFILE}" ]; then
    echo "错误：找不到 ${BUILD_CONTEXT}/${DOCKERFILE}"
    exit 1
fi

echo "    ✓ 文件检查通过"
echo ""

# --------------------------------------------------------------
# 第 2 步：复制 composer 文件到构建上下文
# （trap 会在脚本退出时自动清理这些文件）
# --------------------------------------------------------------
echo ">> [2/4] 准备构建上下文..."
cp composer.json "${BUILD_CONTEXT}/"
cp composer.lock "${BUILD_CONTEXT}/"
echo "    ✓ composer.json 和 composer.lock 已复制到 ${BUILD_CONTEXT}/"
echo ""

# --------------------------------------------------------------
# 第 3 步：构建镜像
# --------------------------------------------------------------
echo ">> [3/4] 开始构建镜像（首次构建约需 5-15 分钟）..."
echo ""

docker build \
    -f "${BUILD_CONTEXT}/${DOCKERFILE}" \
    -t "${IMAGE_NAME}:${TAG}" \
    -t "${FULL_IMAGE}" \
    "${BUILD_CONTEXT}"

echo ""
echo "    ✓ 镜像构建完成"
echo "    本地标签: ${IMAGE_NAME}:${TAG}"
echo "    远程标签: ${FULL_IMAGE}"
echo ""

# --------------------------------------------------------------
# 第 4 步：推送到阿里云
# --------------------------------------------------------------
echo ">> [4/4] 推送镜像到阿里云..."
echo ""

if ! docker push "${FULL_IMAGE}"; then
    echo ""
    echo "推送失败，请先登录阿里云容器镜像服务："
    echo ""
    echo "    docker login ${REGISTRY}"
    echo ""
    echo "登录后重新执行此脚本"
    exit 1
fi

echo ""
echo "=================================================="
echo "  ✓ 构建并推送完成！"
echo "  镜像地址: ${FULL_IMAGE}"
echo ""
echo "  用户可通过以下命令拉取最新镜像："
echo "    docker pull ${FULL_IMAGE}"
echo "=================================================="
echo ""
