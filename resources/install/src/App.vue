<template>
  <div class="install-container">
    <div class="install-card">
      <div class="card-header">
        <h2>蝉印诊所管家安装向导</h2>
        <div class="steps">
          <template v-for="(step, index) in steps" :key="index">
            <div
              class="step"
              :class="{
                'step--active': currentStep === index,
                'step--done': currentStep > index
              }"
            >
              <div class="step__circle">
                <span v-if="currentStep > index">✓</span>
                <span v-else>{{ index + 1 }}</span>
              </div>
              <span class="step__title">{{ step }}</span>
            </div>
            <div v-if="index < steps.length - 1" class="step__line"
              :class="{ 'step__line--done': currentStep > index }"
            ></div>
          </template>
        </div>
      </div>
      <div class="card-body">
        <router-view></router-view>
      </div>
    </div>
    <div class="copyright">
      Copyright © {{ new Date().getFullYear() }} 蝉印诊所管家 All Rights Reserved
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const steps = ['许可协议', '环境检测', '信息配置', '安装过程', '安装完成']
const stepMap = { '/license': 0, '/environment': 1, '/config': 2, '/install': 3, '/complete': 4 }
const currentStep = computed(() => stepMap[route.path] ?? 0)
</script>

<style scoped>
.install-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background-color: #f5f7fa;
  padding: 20px;
}

.install-card {
  width: 100%;
  max-width: 900px;
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px rgba(0,0,0,.08);
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
}

.card-header {
  padding: 20px 20px 0;
  border-bottom: 1px solid #ebeef5;
  text-align: center;
}

.card-header h2 {
  margin: 0 0 20px;
  color: #303133;
  font-size: 20px;
}

.card-body {
  flex: 1;
  padding: 20px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.steps {
  display: flex;
  justify-content: center;
  align-items: center;
  padding-bottom: 16px;
  flex-wrap: wrap;
}

.step {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #c0c4cc;
  font-size: 13px;
}

.step--active { color: #409eff; }
.step--done   { color: #67c23a; }

.step__circle {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 2px solid currentColor;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  flex-shrink: 0;
  background: #fff;
}

.step--active .step__circle {
  background: #409eff;
  color: #fff;
  border-color: #409eff;
}

.step--done .step__circle {
  background: #67c23a;
  color: #fff;
  border-color: #67c23a;
}

.step__title { white-space: nowrap; }

.step__line {
  width: 40px;
  height: 2px;
  background: #e4e7ed;
  margin: 0 4px;
  flex-shrink: 0;
}

.step__line--done { background: #67c23a; }

.copyright {
  color: #909399;
  font-size: 14px;
  text-align: center;
  margin-top: 20px;
}
</style>
