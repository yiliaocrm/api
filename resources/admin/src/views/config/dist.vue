<template>
	<cy-dialog
		v-model="visible"
		title="静态资源加速设置"
		icon="el-icon-setting"
		:width="620"
		:close-on-click-modal="false"
	>
		<div class="py-2">
			<el-alert
				title="静态资源桶需公网可读；一键同步仅覆盖上传，不会删除云端已有文件"
				type="warning"
				:closable="false"
				show-icon
				class="mb-4"
			/>
			<el-form ref="formRef" :model="form" :rules="rules" label-width="180px" @submit.prevent>
				<el-form-item label="开启同步:" prop="dist_sync_enabled">
					<el-switch
						v-model="form.dist_sync_enabled"
						active-text="开启"
						inactive-text="关闭"
						inline-prompt
					/>
				</el-form-item>
				<el-form-item label="Access Key ID:" prop="dist_sync_s3_access_key_id">
					<el-input
						v-model.trim="form.dist_sync_s3_access_key_id"
						type="password"
						show-password
						placeholder="请输入静态资源S3 Access Key ID"
						clearable
					/>
				</el-form-item>
				<el-form-item label="Secret Access Key:" prop="dist_sync_s3_secret_access_key">
					<el-input
						v-model.trim="form.dist_sync_s3_secret_access_key"
						type="password"
						show-password
						placeholder="请输入静态资源S3 Secret Access Key"
						clearable
					/>
				</el-form-item>
				<el-form-item label="Region:" prop="dist_sync_s3_region">
					<el-input
						v-model.trim="form.dist_sync_s3_region"
						placeholder="请输入Region，例如 ap-east-1"
						clearable
					/>
				</el-form-item>
				<el-form-item label="Bucket:" prop="dist_sync_s3_bucket">
					<el-input
						v-model.trim="form.dist_sync_s3_bucket"
						placeholder="请输入静态资源Bucket名称（公网可读）"
						clearable
					/>
				</el-form-item>
				<el-form-item label="Endpoint:" prop="dist_sync_s3_endpoint">
					<el-input
						v-model.trim="form.dist_sync_s3_endpoint"
						placeholder="可选，例如 https://s3.ap-east-1.amazonaws.com"
						clearable
					/>
				</el-form-item>
				<el-form-item label="访问 URL:" prop="dist_sync_s3_url">
					<el-input
						v-model.trim="form.dist_sync_s3_url"
						placeholder="例如 https://cdn.example.com 或 https://bucket.s3.region.amazonaws.com"
						clearable
					/>
				</el-form-item>
				<el-form-item label="路径样式端点:">
					<el-switch
						v-model="form.dist_sync_s3_use_path_style_endpoint"
						active-text="开启"
						inactive-text="关闭"
						inline-prompt
					/>
				</el-form-item>
			</el-form>
		</div>
		<template #footer>
			<el-button type="primary" :loading="saveLoading" @click="handleSave">保存配置</el-button>
			<el-button type="success" :loading="syncLoading" @click="handleSync">一键同步</el-button>
			<el-button @click="visible = false">关闭</el-button>
		</template>
	</cy-dialog>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref } from 'vue'
	import { ElMessage } from 'element-plus'
	import { useMsgbox } from '@/utils/helper.js'

	defineOptions({
		name: 'ConfigDistDialog'
	})

	const emits = defineEmits(['saved'])

	const msgbox = useMsgbox()
	const visible = ref(false)
	const formRef = ref(null)
	const saveLoading = ref(false)
	const syncLoading = ref(false)

	const form = ref({
		dist_sync_enabled: false,
		dist_sync_s3_access_key_id: '',
		dist_sync_s3_secret_access_key: '',
		dist_sync_s3_region: '',
		dist_sync_s3_bucket: '',
		dist_sync_s3_endpoint: '',
		dist_sync_s3_url: '',
		dist_sync_s3_use_path_style_endpoint: false
	})

	const requiredWhenEnabled = (label) => ({
		validator: (rule, value, callback) => {
			if (!form.value.dist_sync_enabled) {
				callback()
				return
			}

			if (value === undefined || value === null || String(value).trim() === '') {
				callback(new Error(`请输入${label}`))
				return
			}

			callback()
		},
		trigger: ['blur', 'change']
	})

	const rules = {
		dist_sync_s3_access_key_id: [requiredWhenEnabled('Access Key ID')],
		dist_sync_s3_secret_access_key: [requiredWhenEnabled('Secret Access Key')],
		dist_sync_s3_region: [requiredWhenEnabled('Region')],
		dist_sync_s3_bucket: [requiredWhenEnabled('Bucket')],
		dist_sync_s3_url: [requiredWhenEnabled('访问 URL')]
	}

	const buildConfigParams = () => {
		const parameters = Object.keys(form.value).map((key) => ({
			name: key,
			value: form.value[key]
		}))

		parameters.push({
			name: 'dist_path',
			value: buildDistPathByConfig()
		})

		return parameters
	}

	const validateForm = async () => {
		return await formRef.value.validate().catch(() => false)
	}

	const normalizeDistBaseUrl = (value) => {
		const url = (value || '').trim()
		if (!url) {
			return ''
		}
		return url.replace(/\/+$/, '')
	}

	const buildDistPathByConfig = () => {
		if (!form.value.dist_sync_enabled) {
			return '/dist/'
		}

		const baseUrl = normalizeDistBaseUrl(form.value.dist_sync_s3_url)
		if (!baseUrl) {
			return '/dist/'
		}

		return `${baseUrl}/dist/`
	}

	const saveConfig = async () => {
		const { code } = await Api.config.save.post({ config: buildConfigParams() })
		if (code !== 200) {
			return false
		}
		emits('saved', {
			...form.value,
			dist_path: buildDistPathByConfig()
		})
		return true
	}

	const handleSave = async () => {
		const valid = await validateForm()
		if (!valid) {
			return
		}

		saveLoading.value = true
		msgbox.loading()
		const ok = await saveConfig()
		msgbox.close()
		saveLoading.value = false

		if (ok) {
			ElMessage.success('保存成功')
		}
	}

	const handleSync = async () => {
		const valid = await validateForm()
		if (!valid) {
			return
		}

		syncLoading.value = true
		msgbox.loading()
		const ok = await saveConfig()
		if (ok) {
			const { code } = await Api.config.distSync.post()
			if (code === 200) {
				ElMessage.success('同步任务已提交')
			}
		}
		msgbox.close()
		syncLoading.value = false
	}

	const open = (payload = {}) => {
		form.value = {
			dist_sync_enabled: payload.dist_sync_enabled ?? false,
			dist_sync_s3_access_key_id: payload.dist_sync_s3_access_key_id ?? '',
			dist_sync_s3_secret_access_key: payload.dist_sync_s3_secret_access_key ?? '',
			dist_sync_s3_region: payload.dist_sync_s3_region ?? '',
			dist_sync_s3_bucket: payload.dist_sync_s3_bucket ?? '',
			dist_sync_s3_endpoint: payload.dist_sync_s3_endpoint ?? '',
			dist_sync_s3_url: payload.dist_sync_s3_url ?? '',
			dist_sync_s3_use_path_style_endpoint: payload.dist_sync_s3_use_path_style_endpoint ?? false
		}
		visible.value = true
	}

	defineExpose({
		open
	})
</script>
