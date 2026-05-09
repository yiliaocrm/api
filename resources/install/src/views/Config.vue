<template>
  <div class="config-container">
    <div class="config-content">
      <h3 style="padding-bottom: 15px;">系统配置</h3>
      <form @submit.prevent>
        <details class="collapse" open>
          <summary class="collapse__title">数据库配置</summary>
          <div class="collapse__body">
            <div class="form-item" :class="{ 'form-item--error': errors.db_host }">
              <label class="form-label">数据库主机 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.db_host" placeholder="127.0.0.1" @blur="validate('db_host')" />
              <span v-if="errors.db_host" class="form-error">{{ errors.db_host }}</span>
            </div>
            <div class="form-item" :class="{ 'form-item--error': errors.db_port }">
              <label class="form-label">数据库端口 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.db_port" placeholder="3306" @blur="validate('db_port')" />
              <span v-if="errors.db_port" class="form-error">{{ errors.db_port }}</span>
            </div>
            <div class="form-item" :class="{ 'form-item--error': errors.db_database }">
              <label class="form-label">数据库名称 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.db_database" placeholder="saas" @blur="validate('db_database')" />
              <span v-if="errors.db_database" class="form-error">{{ errors.db_database }}</span>
            </div>
            <div class="form-item" :class="{ 'form-item--error': errors.db_username }">
              <label class="form-label">数据库用户名 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.db_username" placeholder="root" @blur="validate('db_username')" />
              <span v-if="errors.db_username" class="form-error">{{ errors.db_username }}</span>
            </div>
            <div class="form-item" :class="{ 'form-item--error': errors.db_password }">
              <label class="form-label">数据库密码 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.db_password" type="password" placeholder="请输入密码" @blur="validate('db_password')" />
              <span v-if="errors.db_password" class="form-error">{{ errors.db_password }}</span>
            </div>
          </div>
        </details>

        <details class="collapse" open>
          <summary class="collapse__title">Redis 配置</summary>
          <div class="collapse__body">
            <div class="form-item" :class="{ 'form-item--error': errors.redis_host }">
              <label class="form-label">Redis 主机 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.redis_host" placeholder="127.0.0.1" @blur="validate('redis_host')" />
              <span v-if="errors.redis_host" class="form-error">{{ errors.redis_host }}</span>
            </div>
            <div class="form-item" :class="{ 'form-item--error': errors.redis_port }">
              <label class="form-label">Redis 端口 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.redis_port" placeholder="6379" @blur="validate('redis_port')" />
              <span v-if="errors.redis_port" class="form-error">{{ errors.redis_port }}</span>
            </div>
            <div class="form-item">
              <label class="form-label">Redis 密码</label>
              <input class="form-input" v-model.trim="form.redis_password" type="password" placeholder="无密码请留空" />
            </div>
          </div>
        </details>

        <details class="collapse" open>
          <summary class="collapse__title">后台配置</summary>
          <div class="collapse__body">
            <div class="form-item" :class="{ 'form-item--error': errors.admin_username }">
              <label class="form-label">登录账号 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.admin_username" placeholder="admin" @blur="validate('admin_username')" />
              <span v-if="errors.admin_username" class="form-error">{{ errors.admin_username }}</span>
            </div>
            <div class="form-item" :class="{ 'form-item--error': errors.admin_password }">
              <label class="form-label">登录密码 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.admin_password" type="password" placeholder="请输入密码" @blur="validate('admin_password')" />
              <span v-if="errors.admin_password" class="form-error">{{ errors.admin_password }}</span>
            </div>
            <div class="form-item" :class="{ 'form-item--error': errors.central_domain }">
              <label class="form-label">后台域名 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.central_domain" placeholder="请输入后台域名" @blur="validate('central_domain')" />
              <span v-if="errors.central_domain" class="form-error">{{ errors.central_domain }}</span>
            </div>
            <div class="form-item" :class="{ 'form-item--error': errors.central_admin_path }">
              <label class="form-label">后台路径 <span class="required">*</span></label>
              <input class="form-input" v-model.trim="form.central_admin_path" placeholder="请输入后台路径" @blur="validate('central_admin_path')" />
              <span v-if="errors.central_admin_path" class="form-error">{{ errors.central_admin_path }}</span>
            </div>
          </div>
        </details>
      </form>
    </div>
    <div class="config-actions">
      <button class="btn" @click="handleBack">上一步</button>
      <button class="btn btn--primary" @click="handleNext">开始安装</button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from '@/utils/toast.js'
import { startInstall, getInstallConfig } from '@/api/install.js'
import { useInstallStore } from '@/stores/install'

const router = useRouter()
const installStore = useInstallStore()

const form = reactive({
  db_host: '127.0.0.1',
  db_port: '3306',
  db_database: 'saas',
  db_username: 'root',
  db_password: '',
  redis_host: '127.0.0.1',
  redis_port: '6379',
  redis_password: '',
  admin_username: 'admin',
  admin_password: '',
  central_domain: window.location.hostname,
  central_admin_path: 'admin'
})

const rules = {
  db_host:            '请输入数据库主机地址',
  db_port:            '请输入数据库端口',
  db_database:        '请输入数据库名称',
  db_username:        '请输入数据库用户名',
  db_password:        '请输入数据库密码',
  redis_host:         '请输入 Redis 主机地址',
  redis_port:         '请输入 Redis 端口',
  admin_username:     '请输入登录账号',
  admin_password:     '请输入登录密码',
  central_domain:     '请输入后台域名',
  central_admin_path: '请输入后台路径',
}

const errors = reactive({})

const validate = (field) => {
  if (!form[field]) {
    errors[field] = rules[field]
  } else {
    errors[field] = ''
  }
}

const validateAll = () => {
  let valid = true
  for (const field of Object.keys(rules)) {
    validate(field)
    if (!form[field]) valid = false
  }
  return valid
}

const getAdminUrl = () => {
  const protocol = window.location.protocol
  const port = window.location.port
  const host = port ? `${form.central_domain}:${port}` : form.central_domain
  return `${protocol}//${host}/${form.central_admin_path}`
}

const handleBack = () => {
  router.back()
}

const handleNext = async () => {
  if (!validateAll()) return

  try {
    const { data, code } = await startInstall(form)
    if (data && code === 200) {
      installStore.setSteps(data.steps)
      installStore.setCurrentStep(0)
      installStore.setAdminUrl(getAdminUrl())
      toast.success('配置保存成功')
      router.push('/install')
    }
  } catch (error) {
    toast.error('配置保存失败：' + (error.message || '未知错误'))
  }
}

onMounted(async () => {
  try {
    const { data, code } = await getInstallConfig()
    if (data && code === 200) {
      Object.assign(form, data)
    }
  } catch (e) {
    // 静默失败，使用硬编码默认值
  }
})
</script>

<style scoped>
.config-container {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.config-content {
  flex: 1;
  margin-bottom: 20px;
  overflow-y: auto;
}

.config-actions {
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
  padding: 16px;
}

/* 表单 */
.form-item {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
  gap: 12px;
}

.form-label {
  width: 120px;
  flex-shrink: 0;
  font-size: 14px;
  color: #606266;
  text-align: right;
}

.required { color: #f56c6c; }

.form-input {
  flex: 1;
  height: 36px;
  padding: 0 12px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  font-size: 14px;
  color: #303133;
  outline: none;
  transition: border-color .2s;
}

.form-input:focus { border-color: #409eff; }
.form-item--error .form-input { border-color: #f56c6c; }

.form-error {
  font-size: 12px;
  color: #f56c6c;
  margin-left: 4px;
}

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
.btn--primary { background: #409eff; border-color: #409eff; color: #fff; }
.btn--primary:hover { background: #66b1ff; border-color: #66b1ff; }
</style>
