<template>
	<el-container>
		<el-main class="pd15">
			<el-card shadow="never">
				<template #header>
					<div style="display: flex; justify-content: space-between; align-items: center">
						<div style="display: flex; align-items: center; gap: 12px">
							<span>失败任务 ({{ total }})</span>
							<el-input
								v-model="tagFilter"
								placeholder="按标签过滤"
								clearable
								style="width: 200px"
								@keyup.enter="handleSearch"
								@clear="handleSearch"
							/>
							<el-button type="primary" @click="handleSearch">搜索</el-button>
						</div>
						<div style="display: flex; align-items: center">
							<el-switch v-model="autoRefresh" size="small" style="margin-right: 6px" />
							<el-text type="info">自动刷新</el-text>
						</div>
					</div>
				</template>
				<el-table :data="jobs" border stripe @row-click="handleRowClick">
					<el-table-column prop="id" label="ID" width="220" show-overflow-tooltip />
					<el-table-column label="任务名称" min-width="250" show-overflow-tooltip>
						<template #default="{ row }">{{ getJobName(row) }}</template>
					</el-table-column>
					<el-table-column prop="queue" label="队列" width="120" />
					<el-table-column label="标签" min-width="200">
						<template #default="{ row }">
							<el-tag
								v-for="tag in row.tags || []"
								:key="tag"
								size="small"
								style="margin-right: 4px"
							>
								{{ tag }}
							</el-tag>
						</template>
					</el-table-column>
					<el-table-column label="失败时间" width="180">
						<template #default="{ row }">{{ formatTimestamp(row.failed_at) }}</template>
					</el-table-column>
					<el-table-column label="操作" width="100" fixed="right">
						<template #default="{ row }">
							<el-button link type="primary" size="small" @click.stop="handleRetry(row)">
								重试
							</el-button>
						</template>
					</el-table-column>
				</el-table>
				<div v-if="pagination.hasMore" style="margin-top: 12px; text-align: center">
					<el-button @click="loadMore">加载更多</el-button>
				</div>
			</el-card>
		</el-main>
	</el-container>
	<JobDetailDrawer ref="drawerRef" @retry="fetchData" />
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref } from 'vue'
	import { ElMessage } from 'element-plus'
	import { usePolling } from '@/utils/usePolling.js'
	import { useCursorPagination } from '@/utils/useCursorPagination.js'
	import JobDetailDrawer from '../components/JobDetailDrawer.vue'

	defineOptions({
		name: 'HorizonJobsFailed'
	})

	const drawerRef = ref(null)
	const jobs = ref([])
	const total = ref(0)
	const tagFilter = ref('')
	const { pagination, reset } = useCursorPagination()

	const getJobName = (row) => {
		if (!row.payload) return '-'
		return row.payload.displayName || row.payload.job || '-'
	}

	const formatTimestamp = (ts) => {
		if (!ts) return '-'
		const d = new Date(ts * 1000)
		if (isNaN(d.getTime())) return ts
		return d.toLocaleString('zh-CN')
	}

	const fetchData = async () => {
		reset()
		const params = { starting_at: -1 }
		if (tagFilter.value) params.tag = tagFilter.value
		const { data, code } = await Api.horizon.failedJobs.get(params)
		if (code === 200 && data) {
			jobs.value = data.jobs || []
			total.value = data.total || 0
			if (data.jobs && data.jobs.length > 0) {
				const last = data.jobs[data.jobs.length - 1]
				pagination.startingAt = last.index !== undefined ? last.index : -1
				pagination.hasMore = data.jobs.length >= 50
			}
		}
	}

	const loadMore = async () => {
		const params = { starting_at: pagination.startingAt }
		if (tagFilter.value) params.tag = tagFilter.value
		const { data, code } = await Api.horizon.failedJobs.get(params)
		if (code === 200 && data) {
			jobs.value = [...jobs.value, ...(data.jobs || [])]
			total.value = data.total || 0
			if (data.jobs && data.jobs.length > 0) {
				const last = data.jobs[data.jobs.length - 1]
				pagination.startingAt = last.index !== undefined ? last.index : -1
				pagination.hasMore = data.jobs.length >= 50
			} else {
				pagination.hasMore = false
			}
		}
	}

	const handleSearch = () => {
		fetchData()
	}

	const handleRowClick = (row) => {
		drawerRef.value.open(row, true)
	}

	const handleRetry = async (row) => {
		const { code } = await Api.horizon.retryJob.get(row.id)
		if (code === 200) {
			ElMessage.success('重试指令已发送')
			fetchData()
		}
	}

	const { autoRefresh } = usePolling(fetchData)
</script>
