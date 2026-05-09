<template>
  <div class="install-content">
    <div class="content-header">
      <h2>系统安装</h2>
    </div>

    <div class="install-steps">
      <div
        v-for="(step, index) in installStore.steps"
        :key="step.key"
        class="step-item"
        :class="{
          'step-item--done': getStepStatus(index) === 'success',
          'step-item--active': getStepStatus(index) === 'process',
        }"
      >
        <div class="step-item__icon">
          <span v-if="getStepStatus(index) === 'success'">✓</span>
          <span v-else-if="getStepStatus(index) === 'process'" class="spinner"></span>
          <span v-else>{{ index + 1 }}</span>
        </div>
        <span class="step-item__title">{{ step.name }}</span>
      </div>
    </div>

    <div class="install-log">
      <div class="log-header">
        <h3>安装日志</h3>
      </div>
      <div class="log-content" ref="logContent">
        <div v-for="(log, index) in installLogs" :key="index" class="log-item">
          {{ log }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from '@/utils/toast.js'
import { installStep } from '@/api/install.js'
import { useInstallStore } from '@/stores/install.js'

const router = useRouter()
const currentStep = ref(0)
const logContent = ref(null)
const installLogs = ref([])
const installStore = useInstallStore()

const addLog = (message) => {
  installLogs.value.push(message)
  nextTick(() => {
    if (logContent.value) {
      logContent.value.scrollTop = logContent.value.scrollHeight
    }
  })
}

const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms))

const getStepStatus = (index) => {
  if (index < currentStep.value) return 'success'
  if (index === currentStep.value) return 'process'
  return 'wait'
}

const startInstall = async () => {
  try {
    for (const step of installStore.steps) {
      addLog(`开始执行: ${step.name}`)
      await installStep(step.key)
      addLog(`${step.name} - 完成安装`)
      currentStep.value++
      await sleep(500)
    }
    toast.success('系统安装完成！')
    router.push('/complete')
  } catch (error) {
    toast.error('安装过程中出现错误：' + error.message)
    addLog(`错误: ${error.message}`)
  }
}

onMounted(() => {
  startInstall()
})
</script>

<style scoped>
.install-content {
  background: #fff;
  border-radius: 4px;
}

.content-header {
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #dcdfe6;
}

/* 安装步骤列表 */
.install-steps {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 20px;
}

.step-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: 4px;
  font-size: 14px;
  color: #c0c4cc;
  background: #f5f7fa;
}

.step-item--active {
  color: #409eff;
  background: #ecf5ff;
}

.step-item--done {
  color: #67c23a;
  background: #f0f9eb;
}

.step-item__icon {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid currentColor;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  flex-shrink: 0;
}

.step-item--done .step-item__icon {
  background: #67c23a;
  color: #fff;
  border-color: #67c23a;
}

.step-item--active .step-item__icon {
  border-color: #409eff;
}

/* 旋转动画 */
.spinner {
  display: inline-block;
  width: 10px;
  height: 10px;
  border: 2px solid #409eff;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin .6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* 日志 */
.install-log {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  padding: 15px;
}

.log-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.log-content {
  height: 200px;
  overflow-y: auto;
  background-color: #f5f7fa;
  padding: 10px;
  border-radius: 4px;
}

.log-item {
  margin-bottom: 5px;
  font-family: monospace;
  white-space: pre-wrap;
  font-size: 13px;
  color: #606266;
}
</style>
