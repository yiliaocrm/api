<template>
	<slot :open="open">
		<el-button type="primary" plain @click="open">导出</el-button>
	</slot>
	<cy-drawer v-model="dialog" title="导出" :width="400">
		<el-main style="padding: 0 20px 20px 20px">
			<div v-loading="downLoading" element-loading-text="正在处理中...">
				<div
					v-if="downLoading && progress"
					style="
						position: absolute;
						width: 100%;
						height: 100%;
						display: flex;
						justify-content: center;
						align-items: center;
						z-index: 3000;
					"
				>
					<el-progress
						:text-inside="true"
						:stroke-width="20"
						:percentage="downLoadProgress"
						style="width: 100%; margin-bottom: 120px"
					/>
				</div>
				<el-tabs>
					<el-tab-pane label="常规" lazy>
						<el-form label-width="100px" label-position="left" style="margin: 10px 0 20px 0">
							<el-form-item label="文件名">
								<el-input v-model="formData.fileName" placeholder="请输入文件名" />
							</el-form-item>
							<el-form-item label="文件类型">
								<el-select v-model="formData.fileType" placeholder="请选择文件类型">
									<el-option
										v-for="item in fileTypes"
										:key="item"
										:label="'*.' + item"
										:value="item"
									/>
								</el-select>
							</el-form-item>
							<slot name="form" :formData="formData"></slot>
						</el-form>
						<el-button
							v-if="async"
							type="primary"
							icon="el-icon-plus"
							style="width: 100%"
							@click="handleDownload"
							:loading="asyncLoading"
							>发起导出任务</el-button
						>
						<el-button
							v-else
							type="primary"
							icon="el-icon-download"
							style="width: 100%"
							@click="handleDownload"
							>下 载</el-button
						>
					</el-tab-pane>
					<el-tab-pane label="列设置" v-if="columnData.length > 0" lazy>
						<columnSet :column="columnData"></columnSet>
					</el-tab-pane>
					<el-tab-pane label="其他参数" v-if="data && showData" lazy>
						<el-descriptions :column="1" border size="small">
							<el-descriptions-item v-for="(val, key) in data" :key="key" :label="key">{{
								val
							}}</el-descriptions-item>
						</el-descriptions>
					</el-tab-pane>
				</el-tabs>
			</div>
		</el-main>
	</cy-drawer>
</template>

<script setup>
	import columnSet from './column.vue'
	import { ref, watch, computed } from 'vue'
	import { ElNotification, ElMessageBox } from 'element-plus'

	const props = defineProps({
		/**
		 * 请求对象
		 */
		apiObj: {
			type: Object,
			default: () => ({})
		},
		/**
		 * 文件名
		 */
		fileName: {
			type: String,
			default: ''
		},
		/**
		 * 文件类型
		 */
		fileTypes: {
			type: Array,
			default: () => ['xlsx']
		},
		/**
		 * 请求参数
		 */
		data: {
			type: Object,
			default: () => ({})
		},
		showData: {
			type: Boolean,
			default: false
		},
		/**
		 * 是否异步导出
		 */
		async: {
			type: Boolean,
			default: false
		},
		column: {
			type: Array,
			default: () => []
		},
		/**
		 * 是否直接下载
		 */
		blob: {
			type: Boolean,
			default: false
		},
		progress: {
			type: Boolean,
			default: true
		},
		/**
		 * 临时链接(需要请求后端生成临时链接)
		 */
		temporaryUrl: {
			type: Boolean,
			default: false
		},
		/**
		 * 临时链接返回的下载地址字段
		 * @type {String}
		 * @default "url" 支持data.url形式
		 */
		temporaryUrlField: {
			type: String,
			default: 'url'
		}
	})

	const dialog = ref(false)
	const formData = ref({
		fileName: props.fileName,
		fileType: props.fileTypes[0]
	})
	const columnData = ref([])
	const downLoading = ref(false)
	const asyncLoading = ref(false)
	const downLoadProgress = ref(0)

	const baseUrl = computed(() => {
		return import.meta.env.BASE_URL || '/'
	})

	const getImageUrl = (path) => {
		return `${baseUrl.value}img/${path}`
	}

	watch(
		() => formData.value.fileType,
		(val) => {
			if (formData.value.fileName.includes('.')) {
				formData.value.fileName =
					formData.value.fileName.substring(0, formData.value.fileName.lastIndexOf('.')) + '.' + val
			} else {
				formData.value.fileName = formData.value.fileName + '.' + val
			}
		}
	)

	/**
	 * 生成查询字符串
	 * @param obj {Object} 参数对象
	 */
	const toQueryString = (obj) => {
		let arr = []
		for (var k in obj) {
			arr.push(`${k}=${obj[k]}`)
		}
		return (arr.length > 0 ? '?' : '') + arr.join('&')
	}

	/**
	 * 处理文件名，确保有正确的后缀
	 * @param {string} fileName 原始文件名
	 * @param {string} fileType 文件类型
	 * @returns {string} 处理后的文件名
	 */
	const handleFileName = (fileName, fileType) => {
		if (!fileName) {
			return new Date().getTime() + '.' + fileType
		}
		if (fileName.includes('.')) {
			return fileName.substring(0, fileName.lastIndexOf('.')) + '.' + fileType
		}
		return fileName + '.' + fileType
	}

	/**
	 * 点击下载
	 */
	const handleDownload = () => {
		let columnArr = {
			column: columnData.value
				.filter((n) => !n.hide)
				.map((n) => n.prop)
				.join(',')
		}
		let assignData = { ...props.data, ...formData.value, ...columnArr }
		if (props.async) {
			asyncDownload(props.apiObj, formData.value.fileName, assignData)
		} else if (props.blob) {
			downloadFile(props.apiObj, formData.value.fileName, assignData)
		} else if (props.temporaryUrl) {
			getTemporaryUrl(props.apiObj, formData.value.fileName, props.data)
		} else {
			linkFile(props.apiObj.url, formData.value.fileName, assignData)
		}
	}

	/**
	 * 获取临时下载链接
	 * @param apiObj
	 * @param fileName
	 * @param params
	 */
	const getTemporaryUrl = async (apiObj, fileName, params = {}) => {
		downLoading.value = true
		const { data, code } = await apiObj.get(params)
		if (data && code == 200) {
			linkFile(props.temporaryUrlField ? data[props.temporaryUrlField] : data, fileName)
		}
		downLoading.value = false
	}

	/**
	 * 浏览器直接下载文件
	 * @param url 请求地址
	 * @param fileName 文件名
	 * @param data 请求参数
	 */
	const linkFile = (url, fileName, data = {}) => {
		let a = document.createElement('a')
		a.style = 'display: none'
		a.target = '_blank'
		a.download = fileName
		a.href = url + toQueryString(data)
		document.body.appendChild(a)
		a.click()
		document.body.removeChild(a)
	}

	/**
	 * axios下载文件
	 * @param apiObj
	 * @param fileName
	 * @param data
	 */
	const downloadFile = (apiObj, fileName, data = {}) => {
		downLoading.value = true
		apiObj
			.get(data, {
				responseType: 'blob',
				onDownloadProgress(e) {
					if (e.lengthComputable) {
						downLoadProgress.value = parseInt((e.loaded / e.total) * 100)
					}
				}
			})
			.then((res) => {
				downLoading.value = false
				downLoadProgress.value = 0
				let url = URL.createObjectURL(res)
				let a = document.createElement('a')
				a.style = 'display: none'
				a.target = '_blank'
				a.download = fileName
				a.href = url
				document.body.appendChild(a)
				a.click()
				document.body.removeChild(a)
				URL.revokeObjectURL(url)
			})
			.catch(() => {
				downLoading.value = false
				downLoadProgress.value = 0
			})
	}

	/**
	 * 发起异步下载任务
	 * @param apiObj
	 * @param fileName
	 * @param data
	 */
	const asyncDownload = async (apiObj, fileName, data = {}) => {
		asyncLoading.value = true
		const res = await apiObj.get(data)
		asyncLoading.value = false

		if (res.code == 200) {
			dialog.value = false
			ElMessageBox.alert(
				`<div><img style="height:200px" src="${getImageUrl('tasks-example.png')}"/></div><p>已成功发起导出任务，您可以操作其他事务</p><p>稍后可在 <b>任务中心</b> 查看执行结果</p>`,
				{
					dangerouslyUseHTMLString: true
				}
			).catch(() => {})
		}
	}

	const open = () => {
		dialog.value = true
		formData.value = {
			fileName: handleFileName(props.fileName, props.fileTypes[0]),
			fileType: props.fileTypes[0]
		}
		columnData.value = JSON.parse(JSON.stringify(props.column))
	}

	const close = () => {
		dialog.value = false
	}

	defineExpose({
		open,
		close
	})
</script>
