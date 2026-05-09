<template>
	<el-container>
		<el-main class="pd15">
			<el-card shadow="never">
				<template #header>
					<div style="display: flex; justify-content: space-between; align-items: center">
						<div style="display: flex; align-items: center; gap: 12px">
							<span>标签监控</span>
							<el-input
								v-model="newTag"
								placeholder="输入要监控的标签"
								style="width: 250px"
								@keyup.enter="handleAdd"
							/>
							<el-button type="primary" @click="handleAdd">添加监控</el-button>
						</div>
						<div style="display: flex; align-items: center">
							<el-switch v-model="autoRefresh" size="small" style="margin-right: 6px" />
							<el-text type="info">自动刷新</el-text>
						</div>
					</div>
				</template>
				<el-table :data="monitorList" border stripe>
					<el-table-column prop="tag" label="标签" min-width="300" />
					<el-table-column prop="count" label="任务数" width="120" />
					<el-table-column label="操作" width="160" fixed="right">
						<template #default="{ row }">
							<el-space spacer="|">
								<el-button link type="primary" size="small" @click="handleViewJobs(row)">
									查看任务
								</el-button>
								<el-button link type="danger" size="small" @click="handleRemove(row)">
									删除
								</el-button>
							</el-space>
						</template>
					</el-table-column>
				</el-table>
			</el-card>

			<!-- 监控任务列表 -->
			<el-card v-if="showJobs" shadow="never" style="margin-top: 15px">
				<template #header>
					<div style="display: flex; justify-content: space-between; align-items: center">
						<span>标签 [{{ currentTag }}] 的任务</span>
						<el-button @click="showJobs = false">关闭</el-button>
					</div>
				</template>
				<el-table :data="tagJobs" border stripe @row-click="handleJobClick">
					<el-table-column prop="id" label="ID" width="220" show-overflow-tooltip />
					<el-table-column label="任务名称" min-width="250" show-overflow-tooltip>
						<template #default="{ row }">{{ getJobName(row) }}</template>
					</el-table-column>
					<el-table-column prop="queue" label="队列" width="120" />
					<el-table-column prop="status" label="状态" width="100">
						<template #default="{ row }">
							<el-tag :type="statusType(row.status)">{{ row.status }}</el-tag>
						</template>
					</el-table-column>
				</el-table>
				<div v-if="tagJobsHasMore" style="margin-top: 12px; text-align: center">
					<el-button @click="loadMoreJobs">加载更多</el-button>
				</div>
			</el-card>
		</el-main>
	</el-container>
	<JobDetailDrawer ref="drawerRef" />
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref } from 'vue'
	import { ElMessage } from 'element-plus'
	import { confirmMessager } from '@/utils/helper.js'
	import { usePolling } from '@/utils/usePolling.js'
	import JobDetailDrawer from '../components/JobDetailDrawer.vue'

	defineOptions({
		name: 'HorizonMonitoringIndex'
	})

	const drawerRef = ref(null)
	const monitorList = ref([])
	const newTag = ref('')
	const showJobs = ref(false)
	const currentTag = ref('')
	const tagJobs = ref([])
	const tagJobsHasMore = ref(false)
	const tagJobsStartingAt = ref(0)

	const statusType = (status) => {
		const map = {
			completed: 'success',
			failed: 'danger',
			pending: 'warning',
			reserved: 'primary'
		}
		return map[status] || 'info'
	}

	const getJobName = (row) => {
		if (!row.payload) return '-'
		return row.payload.displayName || row.payload.job || '-'
	}

	const fetchData = async () => {
		const { data, code } = await Api.horizon.monitoring.get()
		if (code === 200 && data) {
			monitorList.value = data
		}
	}

	const handleAdd = async () => {
		if (!newTag.value.trim()) {
			ElMessage.warning('请输入标签名')
			return
		}
		const { code } = await Api.horizon.storeMonitoring.get(newTag.value.trim())
		if (code === 200) {
			ElMessage.success('监控已添加')
			newTag.value = ''
			fetchData()
		}
	}

	const handleRemove = (row) => {
		confirmMessager({
			message: `确定要停止监控标签 [${row.tag}] 吗？`,
			callback: async (action) => {
				if (action === 'confirm') {
					const { code } = await Api.horizon.destroyMonitoring.get(row.tag)
					if (code === 200) {
						ElMessage.success('监控已移除')
						fetchData()
					}
				}
			}
		})
	}

	const handleViewJobs = async (row) => {
		currentTag.value = row.tag
		tagJobsStartingAt.value = 0
		const { data, code } = await Api.horizon.monitoringJobs.get({
			tag: row.tag,
			starting_at: 0,
			limit: 25
		})
		if (code === 200 && data) {
			tagJobs.value = data.jobs || []
			tagJobsHasMore.value = (data.jobs || []).length >= 25
			tagJobsStartingAt.value = (data.jobs || []).length
			showJobs.value = true
		}
	}

	const loadMoreJobs = async () => {
		const { data, code } = await Api.horizon.monitoringJobs.get({
			tag: currentTag.value,
			starting_at: tagJobsStartingAt.value,
			limit: 25
		})
		if (code === 200 && data) {
			tagJobs.value = [...tagJobs.value, ...(data.jobs || [])]
			tagJobsHasMore.value = (data.jobs || []).length >= 25
			tagJobsStartingAt.value += (data.jobs || []).length
		}
	}

	const handleJobClick = (row) => {
		drawerRef.value.open(row)
	}

	const { autoRefresh } = usePolling(fetchData)
</script>
