<template>
	<slot :open="open">
		<el-button type="primary" plain @click="open">导入</el-button>
	</slot>
	<cy-dialog v-model="dialog" title="上传导入" :width="550">
		<el-progress
			v-if="loading"
			:text-inside="true"
			:stroke-width="20"
			:percentage="percentage"
			style="margin-bottom: 15px"
		/>
		<div v-loading="loading">
			<el-upload
				ref="uploader"
				drag
				:accept="accept"
				:maxSize="maxSize"
				:limit="1"
				:data="data"
				:show-file-list="false"
				:http-request="request"
				:before-upload="before"
				:on-progress="progress"
				:on-success="success"
				:on-error="error"
			>
				<slot name="uploader">
					<el-icon class="el-icon--upload"><el-icon-upload-filled /></el-icon>
					<div class="el-upload__text">将文件拖到此处或 <em>点击选择文件上传</em></div>
				</slot>
				<template #tip>
					<div class="pt15 pb15">
						<template v-if="tip">{{ tip }}</template>
						<template v-else>请上传小于或等于 {{ maxSize }}M 的 {{ accept }} 格式文件</template>
						<p v-if="templateUrl" style="margin-top: 7px">
							<el-link :href="templateUrl" target="_blank" type="primary" :underline="false"
								>下载导入模板</el-link
							>
						</p>
					</div>
				</template>
			</el-upload>
			<el-form
				v-if="$slots.form"
				inline
				label-width="100px"
				label-position="left"
				style="margin-top: 18px"
			>
				<slot name="form" :formData="formData"></slot>
			</el-form>
		</div>
	</cy-dialog>
</template>

<script setup>
	import { ref } from 'vue'
	import { ElMessage, ElNotification } from 'element-plus'

	const props = defineProps({
		/**
		 * 上传文件的API对象
		 * 需要包含post方法用于文件上传
		 */
		apiObj: {
			type: Object,
			default: () => ({})
		},
		/**
		 * 上传时附带的额外数据
		 */
		data: {
			type: Object,
			default: () => ({})
		},
		/**
		 * 接受上传的文件类型
		 * 默认接受.xls和.xlsx格式的Excel文件
		 */
		accept: {
			type: String,
			default: '.xls, .xlsx'
		},
		/**
		 * 上传文件的最大大小(MB)
		 * 默认为10MB
		 */
		maxSize: {
			type: Number,
			default: 10
		},
		/**
		 * 自定义的上传提示文本
		 * 如果不设置则显示默认提示
		 */
		tip: {
			type: String,
			default: ''
		},
		/**
		 * 导入模板的下载链接
		 * 如果设置了此项则显示下载模板链接
		 */
		templateUrl: {
			type: String,
			default: ''
		}
	})

	const emit = defineEmits(['success'])

	const dialog = ref(false)
	const loading = ref(false)
	const percentage = ref(0)
	const formData = ref({})
	const uploader = ref(null)

	const open = () => {
		dialog.value = true
		formData.value = {}
	}

	const close = () => {
		dialog.value = false
	}

	const before = (file) => {
		const maxSize = file.size / 1024 / 1024 < props.maxSize
		if (!maxSize) {
			ElMessage.warning(`上传文件大小不能超过 ${props.maxSize}MB!`)
			return false
		}
		loading.value = true
	}

	const progress = (e) => {
		percentage.value = e.percent
	}

	const success = (res, file) => {
		uploader.value.handleRemove(file)
		uploader.value.clearFiles()
		loading.value = false
		percentage.value = 0
		emit('success', res, close)
	}

	const error = (err) => {
		loading.value = false
		percentage.value = 0
		ElNotification.error({
			title: '上传文件未成功',
			message: err
		})
	}

	const request = (param) => {
		Object.assign(param.data, formData.value)
		const data = new FormData()
		data.append(param.filename, param.file)
		for (const key in param.data) {
			data.append(key, param.data[key])
		}
		props.apiObj
			.post(data, {
				onUploadProgress: (e) => {
					const complete = parseInt(((e.loaded / e.total) * 100) | 0, 10)
					param.onProgress({ percent: complete })
				}
			})
			.then((res) => {
				param.onSuccess(res)
			})
			.catch((err) => {
				param.onError(err)
			})
	}
</script>

<style></style>
