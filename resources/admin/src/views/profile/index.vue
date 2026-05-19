<template>
	<el-container>
		<el-main class="pd20">
			<el-card shadow="never" class="[&_.el-card__body]:!p-7.5">
				<el-form
					ref="formRef"
					:model="form"
					:rules="rules"
					label-width="100px"
					@submit.prevent="handleSubmit"
					style="max-width: 500px"
				>
					<el-form-item label="用户名">
						<el-input v-model="form.name" disabled />
					</el-form-item>
					<el-form-item label="登录账号">
						<el-input v-model="form.email" disabled />
					</el-form-item>
					<el-form-item label="当前密码" prop="old">
						<el-input
							v-model.trim="form.old"
							type="password"
							show-password
							placeholder="请输入当前密码"
							clearable
						/>
					</el-form-item>

					<el-form-item label="新密码" prop="password">
						<el-input
							v-model.trim="form.password"
							type="password"
							show-password
							placeholder="请输入新密码"
							clearable
							@input="checkPasswordStrength"
						/>
						<div class="mt-2 mb-2">
							<el-text>密码长度至少6位，建议包含数字、大小写字母和特殊字符</el-text>
						</div>
						<sc-password-strength :modelValue="form.password" />
					</el-form-item>

					<el-form-item label="确认密码" prop="confirmPassword">
						<el-input
							v-model.trim="form.confirmPassword"
							type="password"
							show-password
							placeholder="请再次输入新密码"
							clearable
						/>
					</el-form-item>

					<el-form-item>
						<el-button type="primary" :loading="loading" @click="handleSubmit" style="width: 100%">
							{{ loading ? '修改中...' : '修改密码' }}
						</el-button>
					</el-form-item>
				</el-form>
			</el-card>
		</el-main>
	</el-container>
</template>

<script setup>
	import Api from '@/api/index.js'
	import Tool from '@/utils/tool.js'
	import { useRouter } from 'vue-router'
	import { ElMessageBox } from 'element-plus'
	import { ref, onMounted, reactive } from 'vue'
	import scPasswordStrength from '@/components/scPasswordStrength/index.vue'

	defineOptions({
		name: 'ProfileIndex'
	})

	const router = useRouter()
	const formRef = ref(null)
	const loading = ref(false)

	const form = reactive({
		name: '',
		email: '',
		old: '',
		password: '',
		confirmPassword: ''
	})

	// 自定义验证规则
	const validateOld = (rule, value, callback) => {
		if (!value) {
			return callback(new Error('请输入当前密码'))
		}
		callback()
	}

	const validatePassword = (rule, value, callback) => {
		if (!value) {
			return callback(new Error('请输入新密码'))
		}
		if (value.length < 6) {
			return callback(new Error('新密码长度不能少于6位'))
		}
		if (value === form.old) {
			return callback(new Error('新密码不能与当前密码相同'))
		}
		callback()
	}

	const validateConfirmPassword = (rule, value, callback) => {
		if (!value) {
			return callback(new Error('请确认新密码'))
		}
		if (value !== form.password) {
			return callback(new Error('两次输入的密码不一致'))
		}
		callback()
	}

	const rules = reactive({
		old: [{ validator: validateOld, trigger: 'blur' }],
		password: [{ validator: validatePassword, trigger: 'blur' }],
		confirmPassword: [{ validator: validateConfirmPassword, trigger: 'blur' }]
	})

	// 密码强度检测
	const checkPasswordStrength = () => {
		// 这里可以添加密码强度检测逻辑
		// sc-password-strength 组件会自动处理
	}

	// 处理表单提交
	const handleSubmit = async () => {
		if (!formRef.value) return

		// 验证表单
		await formRef.value.validate()

		loading.value = true

		// 准备提交的数据
		const submitData = {
			old: form.old,
			password: form.password,
			password_confirmation: form.confirmPassword
		}

		// 调用API修改密码
		const response = await Api.auth.resetPassword.post(submitData)

		if (response.code === 200 || response.success) {
			// 清空表单
			form.old = ''
			form.password = ''
			form.confirmPassword = ''

			// 弹出成功提示，点击确认后清除登录信息并跳转到登录页
			ElMessageBox.alert('密码修改成功,系统将自动退出登录!', '密码修改成功', {
				confirmButtonText: '确认',
				type: 'success',
				showClose: false,
				closeOnClickModal: false,
				closeOnPressEscape: false
			}).then(() => {
				// 清除登录信息
				Tool.session.clear()
				// 跳转到登录页面
				router.push('/login')
				// 刷新页面，防止某些信息残留
				window.location.reload()
			})
		}

		loading.value = false
	}

	onMounted(() => {
		const userInfo = Tool.session.get('USER_INFO')
		if (userInfo && userInfo.name) {
			form.name = userInfo.name
			form.email = userInfo.email
		}
	})
</script>
