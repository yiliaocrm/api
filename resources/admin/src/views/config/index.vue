<template>
	<el-container class="pd15">
		<el-main class="application-container pt10">
			<el-form ref="formRef" :model="form" :rules="rules" label-width="180px">
				<el-tabs v-model="activeTab">
					<el-tab-pane label="平台设置" name="base" class="mt15">
						<el-divider content-position="left">租户管理平台配置</el-divider>
						<el-form-item label="后台访问路径:" prop="central_admin_path">
							<el-input v-model.trim="form.central_admin_path" clearable style="width: 400px">
								<template #append>
									<el-button @click="generateRandomPath">随机生成</el-button>
								</template>
							</el-input>
							<el-tooltip content="建议设置随机生成,以提高安全性" placement="right">
								<el-icon :size="18" style="margin-left: 10px">
									<el-icon-question-filled />
								</el-icon>
							</el-tooltip>
						</el-form-item>
						<el-form-item label="后台访问域名:" prop="central_domain">
							<el-input v-model.trim="form.central_domain" clearable style="width: 400px" />
						</el-form-item>
						<el-form-item label="静态资源路径:" prop="dist_path">
							<el-input v-model.trim="form.dist_path" clearable style="width: 400px">
								<template #append>
									<el-button @click="handleDistConfig">加速设置</el-button>
								</template>
							</el-input>
							<el-tooltip
								content="支持设置CDN地址,例如: https://cdn.example.com/dist/"
								placement="right"
							>
								<el-icon :size="18" style="margin-left: 10px">
									<el-icon-question-filled />
								</el-icon>
							</el-tooltip>
						</el-form-item>
						<el-divider content-position="left">reverb配置(websocket)</el-divider>
						<el-form-item label="应用ID:" prop="reverb_app_id">
							<el-input
								v-model.trim="form.reverb_app_id"
								type="password"
								show-password
								placeholder="请输入reverb_app_id参数"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="应用标识:" prop="reverb_app_key">
							<el-input
								v-model.trim="form.reverb_app_key"
								type="password"
								show-password
								placeholder="请输入reverb_app_key参数"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="应用密钥:" prop="reverb_app_secret">
							<el-input
								v-model.trim="form.reverb_app_secret"
								type="password"
								show-password
								placeholder="请输入reverb_app_secret参数"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="主机地址:" prop="reverb_host">
							<el-input
								v-model.trim="form.reverb_host"
								placeholder="请输入reverb_host参数"
								clearable
								style="width: 400px"
							/>
							<el-tooltip content="前端websocket地址" placement="right">
								<el-icon :size="18" style="margin-left: 10px">
									<el-icon-question-filled />
								</el-icon>
							</el-tooltip>
						</el-form-item>
						<el-form-item label="端口号:" prop="reverb_port">
							<el-input-number
								v-model="form.reverb_port"
								placeholder="请输入reverb_port参数"
								clearable
								style="width: 400px"
								:min="1"
								:max="65535"
								controls-position="right"
							/>
							<el-tooltip content="前端websocket端口" placement="right">
								<el-icon :size="18" style="margin-left: 10px">
									<el-icon-question-filled />
								</el-icon>
							</el-tooltip>
						</el-form-item>
						<el-form-item label="协议类型:" prop="reverb_scheme">
							<el-radio-group v-model="form.reverb_scheme">
								<el-radio-button value="https">HTTPS</el-radio-button>
								<el-radio-button value="http">HTTP</el-radio-button>
							</el-radio-group>
							<el-tooltip content="前端websocket协议类型" placement="right">
								<el-icon :size="18" style="margin-left: 10px">
									<el-icon-question-filled />
								</el-icon>
							</el-tooltip>
						</el-form-item>
						<el-divider content-position="left">SQL日志配置</el-divider>
						<el-form-item label="SQL查询日志:" prop="sql_log_enabled">
							<el-switch
								v-model="form.sql_log_enabled"
								active-text="开启"
								inactive-text="关闭"
								inline-prompt
							/>
						</el-form-item>
						<el-form-item label="记录阈值:" prop="sql_log_slower_than">
							<el-input-number
								v-model="form.sql_log_slower_than"
								placeholder="请输入阈值"
								clearable
								style="width: 400px"
								:min="0"
							>
								<template #suffix>
									<span>毫秒</span>
								</template>
							</el-input-number>
						</el-form-item>
						<el-divider content-position="left">高德地图配置</el-divider>
						<el-form-item label="Web端Key:" prop="amap_key">
							<el-input
								v-model.trim="form.amap_key"
								type="password"
								show-password
								placeholder="请输入高德地图Web端Key"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="安全密钥:" prop="amap_secret">
							<el-input
								v-model.trim="form.amap_secret"
								type="password"
								show-password
								placeholder="请输入高德地图安全密钥"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
					</el-tab-pane>
					<el-tab-pane label="存储配置" name="storage" class="mt15">
						<el-form-item label="附件存储方式:" prop="file_system_disk">
							<el-radio-group v-model="form.file_system_disk">
								<el-radio-button value="public">本地存储</el-radio-button>
								<el-radio-button value="s3">S3协议存储</el-radio-button>
							</el-radio-group>
						</el-form-item>
						<el-divider content-position="left">附件S3协议存储配置</el-divider>
						<el-form-item label="Access Key ID" prop="aws_access_key_id">
							<el-input
								v-model.trim="form.aws_access_key_id"
								type="password"
								show-password
								placeholder="请输入Access Key ID"
								style="width: 300px"
								clearable
							/>
						</el-form-item>
						<el-form-item label="Secret Access Key" prop="aws_secret_access_key">
							<el-input
								v-model.trim="form.aws_secret_access_key"
								type="password"
								show-password
								placeholder="请输入Secret Access Key"
								style="width: 300px"
								clearable
							/>
						</el-form-item>
						<el-form-item label="Region" prop="aws_default_region">
							<el-input
								v-model.trim="form.aws_default_region"
								placeholder="请输入Region"
								style="width: 300px"
								clearable
							/>
						</el-form-item>
						<el-form-item label="Bucket" prop="aws_bucket">
							<el-input
								v-model.trim="form.aws_bucket"
								placeholder="请输入Bucket名称"
								style="width: 300px"
								clearable
							/>
						</el-form-item>
						<el-form-item label="URL" prop="aws_url">
							<el-input
								v-model.trim="form.aws_url"
								placeholder="请输入URL"
								style="width: 300px"
								clearable
							/>
						</el-form-item>
						<el-form-item label="Endpoint" prop="aws_endpoint">
							<el-input
								v-model.trim="form.aws_endpoint"
								placeholder="请输入Endpoint"
								style="width: 300px"
								clearable
							/>
						</el-form-item>
						<el-form-item label="URL签名:" prop="aws_signed_url">
							<el-switch
								v-model="form.aws_signed_url"
								active-text="开启"
								inactive-text="关闭"
								inline-prompt
							/>
							<el-tooltip content="如果桶为私有访问请打开此项" placement="right">
								<el-icon :size="18" style="margin-left: 5px">
									<el-icon-question-filled />
								</el-icon>
							</el-tooltip>
						</el-form-item>
						<el-form-item label="使用路径样式端点">
							<el-switch
								v-model="form.aws_use_path_style_endpoint"
								inline-prompt
								active-text="开启"
								inactive-text="关闭"
								:active-value="true"
								:inactive-value="false"
							/>
						</el-form-item>
					</el-tab-pane>
					<el-tab-pane label="OEM配置" name="oem" class="mt15">
						<el-form-item label="系统名称:" prop="oem_system_name">
							<el-input
								v-model.trim="form.oem_system_name"
								placeholder="请输入系统名称"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="系统Logo:" prop="oem_system_logo">
							<el-input
								v-model.trim="form.oem_system_logo"
								placeholder="请输入系统Logo地址"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="帮助文档:" prop="oem_help_url">
							<el-input
								v-model.trim="form.oem_help_url"
								placeholder="请输入帮助文档地址"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="APP二维码:" prop="oem_app_qrcode">
							<el-input
								v-model.trim="form.oem_app_qrcode"
								placeholder="请输入APP二维码地址"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="客服二维码:" prop="oem_service_qrcode">
							<el-input
								v-model.trim="form.oem_service_qrcode"
								placeholder="请输入客服二维码地址"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="客服电话:" prop="oem_service_phone">
							<el-input
								v-model.trim="form.oem_service_phone"
								placeholder="请输入客服电话"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
						<el-form-item label="客服描述:" prop="oem_service_description">
							<el-input
								v-model.trim="form.oem_service_description"
								placeholder="请输入客服描述"
								clearable
								style="width: 400px"
							/>
						</el-form-item>
					</el-tab-pane>
					<el-tab-pane label="安全配置" name="security" class="mt15">
						<el-divider content-position="left">敏感操作二次验证</el-divider>
						<el-form-item label="扫码绑定动态口令:" prop="tfa_secret">
							<el-input
								v-model="form.tfa_secret"
								type="password"
								placeholder="请输入动态口令密钥"
								readonly
								clearable
								showPassword
								style="width: 400px"
							/>
							<el-button type="primary" plain style="margin-left: 10px" @click="handleTfaBind">
								{{ form.tfa_secret ? '重新绑定' : '扫码绑定' }}
							</el-button>
						</el-form-item>
						<el-form-item label="登录平台二次验证:" prop="central_login_tfa">
							<el-switch
								v-model="form.central_login_tfa"
								active-text="开启"
								inactive-text="关闭"
								inline-prompt
								@change="handleLoginTfaChange"
							/>
							<el-tooltip content="登录运营管理平台需要动态口令验证" placement="right">
								<el-icon :size="18" style="margin-left: 5px">
									<el-icon-question-filled />
								</el-icon>
							</el-tooltip>
						</el-form-item>
						<el-form-item label="SQL分群二次验证:" prop="sql_group_tfa">
							<el-switch
								v-model="form.sql_group_tfa"
								active-text="开启"
								inactive-text="关闭"
								inline-prompt
								@change="handleSqlGroupTfaChange"
							/>
							<el-tooltip content="机构端操作SQL分群二次验证" placement="right">
								<el-icon :size="18" style="margin-left: 5px">
									<el-icon-question-filled />
								</el-icon>
							</el-tooltip>
						</el-form-item>
					</el-tab-pane>
				</el-tabs>
				<el-form-item>
					<el-button type="primary" @click="handleSave">保存</el-button>
				</el-form-item>
			</el-form>
		</el-main>
	</el-container>
	<config-tfa ref="tfaDialogRef" @success="handleTfaSuccess" />
	<config-dist ref="distDialogRef" @saved="handleDistSaved" />
</template>

<script setup>
	import Api from '@/api/index.js'
	import { useMsgbox } from '@/utils/helper.js'
	import { ElMessageBox } from 'element-plus'
	import { ref, onMounted } from 'vue'
	import ConfigTfa from './tfa.vue'
	import ConfigDist from './dist.vue'

	defineOptions({
		name: 'ConfigIndex'
	})

	const msgbox = useMsgbox()
	const loading = ref(false)
	const formRef = ref(null)
	const activeTab = ref('base')
	const tfaDialogRef = ref(null)
	const distDialogRef = ref(null)

	const form = ref({
		central_admin_path: 'admin',
		central_domain: window.location.hostname,
		dist_path: '/dist/',
		dist_sync_enabled: false,
		dist_sync_s3_access_key_id: '',
		dist_sync_s3_secret_access_key: '',
		dist_sync_s3_region: '',
		dist_sync_s3_bucket: '',
		dist_sync_s3_endpoint: '',
		dist_sync_s3_url: '',
		dist_sync_s3_use_path_style_endpoint: false,
		reverb_app_id: '',
		reverb_app_key: '',
		reverb_app_secret: '',
		reverb_host: '',
		reverb_port: 8080,
		reverb_scheme: 'https',
		sql_log_enabled: false,
		sql_log_slower_than: 0,
		file_system_disk: 'local',
		aws_access_key_id: '',
		aws_secret_access_key: '',
		aws_default_region: '',
		aws_bucket: '',
		aws_url: '',
		aws_endpoint: '',
		aws_use_path_style_endpoint: false,
		aws_signed_url: false,
		oem_system_name: '',
		oem_system_logo: '',
		oem_app_qrcode: '',
		oem_help_url: '',
		oem_service_qrcode: '',
		oem_service_phone: '',
		oem_service_description: '',
		amap_key: '',
		amap_secret: '',
		tfa_secret: '',
		central_login_tfa: false,
		sql_group_tfa: false
	})

	const rules = ref({
		central_admin_path: [{ required: true, message: '请输入后台访问路径', trigger: 'blur' }],
		central_domain: [{ required: true, message: '请输入后台访问域名', trigger: 'blur' }],
		dist_path: [{ required: true, message: '请输入静态资源路径', trigger: 'blur' }],
		reverb_app_id: [{ required: true, message: '请输入Reverb应用ID', trigger: 'blur' }],
		reverb_app_key: [{ required: true, message: '请输入Reverb应用标识', trigger: 'blur' }],
		reverb_app_secret: [
			{ required: true, message: '请输入Reverb应用密钥(Secret)', trigger: 'blur' }
		],
		reverb_host: [{ required: true, message: '请输入Reverb主机地址', trigger: 'blur' }],
		reverb_port: [
			{ required: true, message: '请输入Reverb端口号', trigger: 'blur' },
			{ type: 'number', message: '端口号必须为数字', trigger: 'blur' },
			{
				validator: (rule, value, callback) => {
					if (value && (value < 1 || value > 65535)) {
						callback(new Error('端口号必须在1-65535之间'))
					} else {
						callback()
					}
				},
				trigger: 'blur'
			}
		],
		reverb_scheme: [{ required: true, message: '请选择协议类型', trigger: 'change' }],
		sql_log_slower_than: [
			{ type: 'number', message: 'SQL查询阈值必须为数字', trigger: 'blur' },
			{
				validator: (rule, value, callback) => {
					if (value && value < 0) {
						callback(new Error('SQL查询阈值不能小于0'))
					} else {
						callback()
					}
				},
				trigger: 'blur'
			}
		],
		oem_system_name: [{ required: true, message: '请输入系统名称', trigger: 'blur' }],
		oem_system_logo: [{ required: true, message: '请输入系统Logo地址', trigger: 'blur' }],
		oem_help_url: [
			{
				validator: (rule, value, callback) => {
					if (value && value.trim() !== '') {
						// URL验证正则表达式
						const urlPattern = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w .-]*)*\/?$/
						if (!urlPattern.test(value)) {
							callback(new Error('请输入有效的URL地址'))
						} else {
							callback()
						}
					} else {
						callback()
					}
				},
				trigger: 'blur'
			}
		],
		oem_app_qrcode: [
			{
				validator: (rule, value, callback) => {
					if (value && value.trim() !== '') {
						// URL验证正则表达式
						const urlPattern = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w .-]*)*\/?$/
						if (!urlPattern.test(value)) {
							callback(new Error('请输入有效的URL地址'))
						} else {
							callback()
						}
					} else {
						callback()
					}
				},
				trigger: 'blur'
			}
		]
	})

	const original_central_admin_path = ref('')

	const generateRandomPath = () => {
		let result = ''
		const characters = 'abcdefghijklmnopqrstuvwxyz0123456789'
		const charactersLength = characters.length
		for (let i = 0; i < 8; i++) {
			result += characters.charAt(Math.floor(Math.random() * charactersLength))
		}
		form.value.central_admin_path = result
	}

	const handleTfaBind = () => {
		if (tfaDialogRef.value) {
			tfaDialogRef.value.open()
		}
	}

	const handleLoginTfaChange = (value) => {
		// 如果要开启登录二次验证，但是密钥为空，则提示并恢复为关闭状态
		if (value && !form.value.tfa_secret) {
			ElMessageBox.alert('请先扫码绑定动态口令', '提示', {
				type: 'warning'
			})
			// 恢复为关闭状态
			form.value.central_login_tfa = false
		}
	}

	const handleSqlGroupTfaChange = (value) => {
		// 如果要开启SQL分群二次验证，但是密钥为空，则提示并恢复为关闭状态
		if (value && !form.value.tfa_secret) {
			ElMessageBox.alert('请先扫码绑定动态口令', '提示', {
				type: 'warning'
			})
			// 恢复为关闭状态
			form.value.sql_group_tfa = false
		}
	}

	const handleTfaSuccess = (secret) => {
		form.value.tfa_secret = secret
	}

	const handleDistConfig = () => {
		if (distDialogRef.value) {
			distDialogRef.value.open({
				dist_sync_enabled: form.value.dist_sync_enabled,
				dist_sync_s3_access_key_id: form.value.dist_sync_s3_access_key_id,
				dist_sync_s3_secret_access_key: form.value.dist_sync_s3_secret_access_key,
				dist_sync_s3_region: form.value.dist_sync_s3_region,
				dist_sync_s3_bucket: form.value.dist_sync_s3_bucket,
				dist_sync_s3_endpoint: form.value.dist_sync_s3_endpoint,
				dist_sync_s3_url: form.value.dist_sync_s3_url,
				dist_sync_s3_use_path_style_endpoint: form.value.dist_sync_s3_use_path_style_endpoint
			})
		}
	}

	const handleDistSaved = (payload) => {
		Object.keys(payload).forEach((key) => {
			form.value[key] = payload[key]
		})
	}

	const getConfig = async () => {
		msgbox.loading()
		const { data, code } = await Api.config.load.get()
		if (code === 200) {
			data.forEach((item) => {
				form.value[item.name] = item.value
			})
			original_central_admin_path.value = form.value.central_admin_path
		}
		msgbox.close()
	}

	const handleSave = async () => {
		// 表单验证
		const valid = await formRef.value.validate().catch(() => false)
		if (!valid) {
			ElMessageBox.alert('请检查表单是否填写正确', '提示', {
				type: 'warning'
			})
			return
		}

		loading.value = true
		msgbox.loading()
		const params = Object.keys(form.value).map((key) => {
			return {
				name: key,
				value: form.value[key]
			}
		})
		const { code } = await Api.config.save.post({ config: params })
		if (code === 200) {
			ElMessageBox.alert('保存成功', '提示', {
				type: 'success'
			}).then(() => {
				// 如果修改了后台访问路径，刷新页面并跳转到新的路径
				const newPath = form.value.central_admin_path
				if (newPath && newPath !== original_central_admin_path.value) {
					window.location.pathname = window.location.pathname.replace(
						original_central_admin_path.value,
						newPath
					)
				}
			})
		}
		msgbox.close()
		loading.value = false
	}

	onMounted(() => {
		getConfig()
	})
</script>
