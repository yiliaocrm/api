<template>
	<cy-dialog v-model="visible" title="获取双重验证密钥" :width="500" :close-on-click-modal="false">
		<div class="py-5">
			<div v-if="qrcode" class="text-center mb-8">
				<p class="text-red-600 text-lg mb-5">请使用谷歌身份验证器APP扫描下方二维码</p>
				<div class="flex justify-center mb-5">
					<sc-qr-code :text="qrcode" :size="200" />
				</div>
			</div>
			<el-form ref="formRef" :model="form" :rules="rules" label-width="120px" @submit.prevent>
				<el-form-item label="动态口令:" prop="code">
					<el-input
						v-model="form.code"
						placeholder="请输入6位动态口令"
						maxlength="6"
						clearable
						style="width: 300px"
						@keyup.enter="handleSubmit"
					/>
				</el-form-item>
			</el-form>
		</div>
		<template #footer>
			<el-button type="primary" @click="handleSubmit" :loading="submitLoading">提交</el-button>
			<el-button @click="closeDialog">关闭</el-button>
		</template>
	</cy-dialog>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref } from 'vue'
	import { useMsgbox } from '@/utils/helper.js'
	import { ElMessage } from 'element-plus'
	import scQrCode from '@/components/scQrCode'

	defineOptions({
		name: 'TfaDialog'
	})

	const emits = defineEmits(['success'])

	const msgbox = useMsgbox()
	const visible = ref(false)
	const qrcode = ref('')
	const secret = ref('')
	const submitLoading = ref(false)
	const formRef = ref(null)

	const form = ref({
		code: ''
	})

	const rules = {
		code: [
			{ required: true, message: '请输入动态口令', trigger: 'blur' },
			{ pattern: /^\d{6}$/, message: '动态口令必须是6位数字', trigger: 'blur' }
		]
	}

	// 打开对话框
	const open = async () => {
		// 先加载数据
		msgbox.loading()
		const { data, code } = await Api.config.secret.get()
		if (code === 200) {
			secret.value = data.secret
			qrcode.value = data.qrcode
			// 数据加载完成后才显示对话框
			visible.value = true
		}
		msgbox.close()
	}

	// 提交验证
	const handleSubmit = () => {
		formRef.value.validate(async (valid) => {
			if (valid) {
				submitLoading.value = true
				let data = await Api.config.verify.post({
					code: form.value.code,
					secret: secret.value
				})
				if (data.code === 200) {
					ElMessage.success('绑定成功')
					visible.value = false
					emits('success', secret.value)
				}
				submitLoading.value = false
			}
		})
	}

	// 关闭对话框
	const closeDialog = () => {
		visible.value = false
		form.value.code = ''
		if (formRef.value) {
			formRef.value.resetFields()
		}
	}

	defineExpose({
		open
	})
</script>
