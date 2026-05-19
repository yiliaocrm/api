<template>
	<cy-dialog v-model="dialog" :title="title" :width="600" append-to-body>
		<el-form
			ref="formRef"
			class="mt5 mr10"
			:model="form"
			:rules="rules"
			label-position="left"
			label-width="130px"
		>
			<el-form-item label="Banner标题" prop="title">
				<el-input
					v-model="form.title"
					clearable
					placeholder="请输入Banner标题"
					maxlength="255"
					show-word-limit
				></el-input>
			</el-form-item>
			<el-form-item label="Banner图片" prop="image_path">
				<sc-upload
					v-model="form.image_path"
					:width="400"
					:height="200"
					title="点击上传图片"
				></sc-upload>
				<div class="el-form-item__tip">建议尺寸：650x600像素，支持jpg、大小不超过2MB</div>
			</el-form-item>
			<el-form-item label="跳转链接" prop="link_url">
				<el-input v-model="form.link_url" clearable placeholder="请输入跳转链接（可选）"></el-input>
			</el-form-item>
			<el-form-item label="排序权重" prop="order">
				<el-input-number
					v-model="form.order"
					:min="0"
					:max="9999"
					controls-position="right"
					placeholder="请输入排序权重"
				></el-input-number>
				<div class="el-form-item__tip">数字越小越靠前</div>
			</el-form-item>
			<el-form-item label="启用状态" prop="disabled">
				<el-switch
					v-model="form.disabled"
					:active-value="false"
					:inactive-value="true"
					active-text="启用"
					inactive-text="禁用"
					inline-prompt
				/>
			</el-form-item>
		</el-form>
		<template #footer>
			<el-button type="primary" @click="handleSubmit" :loading="loading">确定</el-button>
			<el-button @click="handleCancel">取消</el-button>
		</template>
	</cy-dialog>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref } from 'vue'

	defineOptions({
		name: 'BannerForm'
	})

	const emit = defineEmits(['success'])

	const formRef = ref(null)
	const dialog = ref(false)
	const title = ref(null)
	const loading = ref(false)

	const form = ref({
		id: null,
		title: null,
		image_path: null,
		link_url: null,
		order: 0,
		disabled: false
	})

	const rules = {
		title: [{ required: true, message: '请输入Banner标题', trigger: 'blur' }],
		image_path: [{ required: true, message: '请上传Banner图片', trigger: 'blur' }],
		link_url: [
			{
				type: 'url',
				message: '请输入正确的URL格式',
				trigger: 'blur'
			}
		],
		order: [{ required: true, message: '请输入排序权重', trigger: 'blur' }]
	}

	const add = () => {
		title.value = '新增Banner'
		dialog.value = true
		form.value = {
			id: null,
			title: null,
			image_path: null,
			link_url: null,
			order: 0,
			disabled: false
		}
	}

	const edit = (row) => {
		title.value = `编辑[${row.title}]`
		dialog.value = true
		form.value = {
			id: row.id,
			title: row.title,
			image_path: row.image_path,
			link_url: row.link_url,
			order: row.order,
			disabled: row.disabled
		}
	}

	const handleCancel = () => {
		dialog.value = false
	}

	const handleSubmit = () => {
		formRef.value.validate((valid) => {
			if (valid) {
				form.value.id ? updateRequest() : createRequest()
			}
		})
	}

	const createRequest = async () => {
		loading.value = true
		let { data, code } = await Api.tenant_login_banner.create.post(form.value)
		if (data && code === 200) {
			dialog.value = false
			emit('success', data)
		}
		loading.value = false
	}

	const updateRequest = async () => {
		loading.value = true
		let { data, code } = await Api.tenant_login_banner.update.post(form.value)
		if (data && code === 200) {
			dialog.value = false
			emit('success', data)
		}
		loading.value = false
	}

	defineExpose({
		add,
		edit
	})
</script>

<style scoped>
	.el-form-item__tip {
		font-size: 12px;
		color: #909399;
		margin-top: 5px;
	}
</style>
