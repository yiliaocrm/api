<template>
  <div class="environment-container">
    <div class="environment-content">
      <h3 style="padding-bottom: 15px;">运行环境检测</h3>
      <div>
        <!-- PHP版本检测 -->
        <details class="collapse" open>
          <summary class="collapse__title">PHP版本</summary>
          <div class="collapse__body">
            <table class="check-table">
              <thead>
                <tr>
                  <th>检测项</th>
                  <th>当前版本</th>
                  <th>要求版本</th>
                  <th>状态</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>PHP</td>
                  <td>{{ environmentData?.php_version?.current || '-' }}</td>
                  <td>{{ environmentData?.php_version?.required || '-' }}</td>
                  <td>
                    <span class="tag" :class="environmentData?.php_version?.status ? 'tag--success' : 'tag--danger'">
                      {{ environmentData?.php_version?.status ? '通过' : '未通过' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </details>

        <!-- PHP扩展检测 -->
        <details class="collapse" open>
          <summary class="collapse__title">PHP扩展</summary>
          <div class="collapse__body">
            <table class="check-table">
              <thead>
                <tr>
                  <th>扩展名称</th>
                  <th>是否必需</th>
                  <th>是否安装</th>
                  <th>状态</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(info, name) in environmentData?.extensions" :key="name">
                  <td>{{ name }}</td>
                  <td>{{ info.required ? '是' : '否' }}</td>
                  <td>{{ info.current ? '是' : '否' }}</td>
                  <td>
                    <span class="tag" :class="info.status ? 'tag--success' : 'tag--danger'">
                      {{ info.status ? '通过' : '未通过' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </details>

        <!-- 目录权限检测 -->
        <details class="collapse" open>
          <summary class="collapse__title">目录权限</summary>
          <div class="collapse__body">
            <table class="check-table">
              <thead>
                <tr>
                  <th>目录/文件</th>
                  <th>所需权限</th>
                  <th>状态</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(info, name) in environmentData?.directories" :key="name">
                  <td>{{ name }}</td>
                  <td>{{ info.required || '可写' }}</td>
                  <td>
                    <span class="tag" :class="info.status ? 'tag--success' : 'tag--danger'">
                      {{ info.status ? '通过' : '未通过' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </details>
      </div>
    </div>

    <div class="environment-actions">
      <button class="btn" @click="handleBack">上一步</button>
      <button class="btn" @click="handleCheck" :disabled="checking">
        {{ checking ? '检测中...' : '重新检测' }}
      </button>
      <button class="btn btn--primary" @click="handleNext">下一步</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from '@/utils/toast.js'
import { getInstallEnvironment } from '@/api/install.js'

const router = useRouter()
const environmentData = ref(null)
const checking = ref(false)

const handleCheck = async () => {
  checking.value = true
  try {
    const { data } = await getInstallEnvironment()
    environmentData.value = data
  } catch (error) {
    toast.error('环境检测失败：' + (error.message || '未知错误'))
  } finally {
    checking.value = false
  }
}

const handleBack = () => {
  router.back()
}

const handleNext = () => {
  const errors = []

  if (!environmentData.value?.php_version?.status) {
    errors.push(`PHP版本不满足要求：当前版本 ${environmentData.value?.php_version?.current}，要求版本 ${environmentData.value?.php_version?.required}`)
  }

  const failedExtensions = Object.entries(environmentData.value?.extensions || {})
    .filter(([_, ext]) => ext.required && !ext.status)
    .map(([name]) => name)

  if (failedExtensions.length > 0) {
    errors.push(`以下必需的PHP扩展未安装或未启用：${failedExtensions.join('、')}`)
  }

  const failedDirectories = Object.entries(environmentData.value?.directories || {})
    .filter(([_, dir]) => !dir.status)
    .map(([name]) => name)

  if (failedDirectories.length > 0) {
    errors.push(`以下目录或文件权限不正确：${failedDirectories.join('、')}`)
  }

  if (errors.length > 0) {
    toast.error(errors.join('\n'), 5000)
    return
  }

  router.push('/config')
}

onMounted(() => {
  handleCheck()
})
</script>

<style scoped>
.environment-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.environment-content {
  flex: 1;
  margin-bottom: 20px;
  overflow-y: auto;
}

.environment-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px 0 0;
  border-top: 1px solid #e4e7ed;
}

/* 折叠面板 */
.collapse {
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  margin-bottom: 12px;
}

.collapse__title {
  padding: 12px 16px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #303133;
  background: #f5f7fa;
  border-radius: 4px;
  list-style: none;
  user-select: none;
}

.collapse__title::-webkit-details-marker { display: none; }

.collapse[open] .collapse__title {
  border-bottom: 1px solid #e4e7ed;
  border-radius: 4px 4px 0 0;
}

.collapse__body {
  padding: 12px;
}

/* 表格 */
.check-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.check-table th,
.check-table td {
  border: 1px solid #ebeef5;
  padding: 10px 12px;
  text-align: left;
}

.check-table th {
  background: #f5f7fa;
  color: #909399;
  font-weight: 500;
}

/* 标签 */
.tag {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.tag--success { background: #f0f9eb; color: #67c23a; }
.tag--danger  { background: #fef0f0; color: #f56c6c; }

/* 按钮 */
.btn {
  padding: 8px 20px;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
  cursor: pointer;
  font-size: 14px;
  background: #fff;
  color: #606266;
  transition: all .2s;
}

.btn:hover { border-color: #409eff; color: #409eff; }
.btn:disabled { cursor: not-allowed; opacity: .6; }
.btn--primary { background: #409eff; border-color: #409eff; color: #fff; }
.btn--primary:hover { background: #66b1ff; border-color: #66b1ff; }
</style>
