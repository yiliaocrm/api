<template>
  <div class="complete-container">
    <div class="complete-content">
      <div class="result-icon">✓</div>
      <h2 class="result-title">安装完成</h2>
      <p class="result-subtitle">恭喜您，系统已成功安装！</p>
      <div class="complete-actions">
        <button class="btn" @click="handleVisitWebsite">访问官网</button>
        <button class="btn btn--primary" @click="handleLogin">进入系统</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useInstallStore } from '@/stores/install'

const router = useRouter()
const installStore = useInstallStore()

const handleLogin = () => {
  if (installStore.admin_url) {
    window.location.href = installStore.admin_url
  } else {
    router.push('/login')
  }
}

const handleVisitWebsite = () => {
  window.open('http://www.yiliaocrm.com', '_blank')
}
</script>

<style scoped>
.complete-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  justify-content: center;
  align-items: center;
}

.complete-content {
  width: 100%;
  max-width: 600px;
  text-align: center;
  padding: 40px 20px;
}

.result-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #67c23a;
  color: #fff;
  font-size: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
}

.result-title {
  font-size: 24px;
  color: #303133;
  margin: 0 0 12px;
}

.result-subtitle {
  font-size: 14px;
  color: #909399;
  margin: 0 0 30px;
}

.complete-actions {
  display: flex;
  justify-content: center;
  gap: 20px;
}

.btn {
  padding: 8px 24px;
  border-radius: 4px;
  border: 1px solid #dcdfe6;
  cursor: pointer;
  font-size: 14px;
  background: #fff;
  color: #606266;
  transition: all .2s;
}

.btn:hover { border-color: #409eff; color: #409eff; }
.btn--primary { background: #409eff; border-color: #409eff; color: #fff; }
.btn--primary:hover { background: #66b1ff; border-color: #66b1ff; }
</style>
