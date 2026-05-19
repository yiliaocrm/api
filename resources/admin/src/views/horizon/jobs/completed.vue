<template>
	<el-container>
		<el-main class="pd15">
			<el-card shadow="never">
				<template #header>
					<div style="display: flex; justify-content: space-between; align-items: center">
						<span>已完成任务 ({{ total }})</span>
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
					<el-table-column label="运行时长" width="120">
						<template #default="{ row }">{{ row.runtime || '-' }} ms</template>
					</el-table-column>
					<el-table-column label="完成时间" width="180">
						<template #default="{ row }">{{ formatTimestamp(row.completed_at) }}</template>
					</el-table-column>
				</el-table>
				<div v-if="pagination.hasMore" style="margin-top: 12px; text-align: center">
					<el-button @click="loadMore">加载更多</el-button>
				</div>
			</el-card>
		</el-main>
	</el-container>
	<JobDetailDrawer ref="drawerRef" />
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref } from 'vue'
	import { usePolling } from '@/utils/usePolling.js'
	import { useCursorPagination } from '@/utils/useCursorPagination.js'
	import JobDetailDrawer from '../components/JobDetailDrawer.vue'

	defineOptions({
		name: 'HorizonJobsCompleted'
	})

	const drawerRef = ref(null)
	const jobs = ref([])
	const total = ref(0)
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
		const { data, code } = await Api.horizon.completedJobs.get({ starting_at: -1 })
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
		const { data, code } = await Api.horizon.completedJobs.get({
			starting_at: pagination.startingAt
		})
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

	const handleRowClick = (row) => {
		drawerRef.value.open(row)
	}

	const { autoRefresh } = usePolling(fetchData)
</script>
