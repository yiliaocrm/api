<template>
	<el-container>
		<el-main class="pd15">
			<el-card shadow="never">
				<template #header>
					<div style="display: flex; justify-content: space-between; align-items: center">
						<div style="display: flex; align-items: center; gap: 12px">
							<span>批处理任务</span>
							<el-input
								v-model="searchQuery"
								placeholder="搜索批处理名称或ID"
								clearable
								style="width: 250px"
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
				<el-table :data="batchList" border stripe @row-click="handleRowClick">
					<el-table-column prop="id" label="ID" width="320" show-overflow-tooltip />
					<el-table-column prop="name" label="名称" min-width="200" show-overflow-tooltip />
					<el-table-column label="进度" width="200">
						<template #default="{ row }">
							<el-progress :percentage="row.progress || 0" :status="getProgressStatus(row)" />
						</template>
					</el-table-column>
					<el-table-column label="总数/完成/失败" width="160">
						<template #default="{ row }">
							{{ row.totalJobs }} / {{ row.processedJobs }} / {{ row.failedJobs }}
						</template>
					</el-table-column>
					<el-table-column label="创建时间" width="180">
						<template #default="{ row }">{{ formatTime(row.createdAt) }}</template>
					</el-table-column>
					<el-table-column label="操作" width="100" fixed="right">
						<template #default="{ row }">
							<el-button
								v-if="row.failedJobs > 0"
								link
								type="primary"
								size="small"
								@click.stop="handleRetry(row)"
							>
								重试
							</el-button>
						</template>
					</el-table-column>
				</el-table>
			</el-card>
		</el-main>
	</el-container>

	<!-- 批处理详情抽屉 -->
	<el-drawer v-model="drawerVisible" title="批处理详情" size="600px" :destroy-on-close="true">
		<template v-if="selectedBatch">
			<el-descriptions :column="1" border>
				<el-descriptions-item label="ID">{{ selectedBatch.id }}</el-descriptions-item>
				<el-descriptions-item label="名称">{{ selectedBatch.name }}</el-descriptions-item>
				<el-descriptions-item label="进度">
					<el-progress
						:percentage="selectedBatch.progress || 0"
						:status="getProgressStatus(selectedBatch)"
						style="width: 200px"
					/>
				</el-descriptions-item>
				<el-descriptions-item label="总任务数">{{ selectedBatch.totalJobs }}</el-descriptions-item>
				<el-descriptions-item label="已完成">{{
					selectedBatch.processedJobs
				}}</el-descriptions-item>
				<el-descriptions-item label="失败数">{{ selectedBatch.failedJobs }}</el-descriptions-item>
				<el-descriptions-item label="创建时间">
					{{ formatTime(selectedBatch.createdAt) }}
				</el-descriptions-item>
				<el-descriptions-item v-if="selectedBatch.cancelledAt" label="取消时间">
					{{ formatTime(selectedBatch.cancelledAt) }}
				</el-descriptions-item>
				<el-descriptions-item v-if="selectedBatch.finishedAt" label="完成时间">
					{{ formatTime(selectedBatch.finishedAt) }}
				</el-descriptions-item>
			</el-descriptions>

			<template v-if="batchFailedJobs && batchFailedJobs.length">
				<el-divider content-position="left">失败任务</el-divider>
				<el-table :data="batchFailedJobs" border size="small">
					<el-table-column prop="id" label="Job ID" width="220" show-overflow-tooltip />
					<el-table-column label="任务名称" min-width="200" show-overflow-tooltip>
						<template #default="{ row }">{{ getJobName(row) }}</template>
					</el-table-column>
				</el-table>
			</template>
		</template>
	</el-drawer>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref } from 'vue'
	import { ElMessage } from 'element-plus'
	import { usePolling } from '@/utils/usePolling.js'

	defineOptions({
		name: 'HorizonBatchesIndex'
	})

	const batchList = ref([])
	const searchQuery = ref('')
	const drawerVisible = ref(false)
	const selectedBatch = ref(null)
	const batchFailedJobs = ref([])

	const getProgressStatus = (batch) => {
		if (batch.cancelledAt) return 'exception'
		if (batch.failedJobs > 0) return 'exception'
		if (batch.progress >= 100) return 'success'
		return ''
	}

	const formatTime = (ts) => {
		if (!ts) return '-'
		const d = new Date(ts * 1000)
		if (isNaN(d.getTime())) {
			// 可能是日期字符串
			const d2 = new Date(ts)
			if (isNaN(d2.getTime())) return ts
			return d2.toLocaleString('zh-CN')
		}
		return d.toLocaleString('zh-CN')
	}

	const getJobName = (row) => {
		if (!row.payload) return '-'
		const payload = typeof row.payload === 'string' ? JSON.parse(row.payload) : row.payload
		return payload.displayName || payload.job || '-'
	}

	const fetchData = async () => {
		const params = {}
		if (searchQuery.value) params.query = searchQuery.value
		const { data, code } = await Api.horizon.batches.get(params)
		if (code === 200 && data) {
			batchList.value = data.batches || []
		}
	}

	const handleSearch = () => {
		fetchData()
	}

	const handleRowClick = async (row) => {
		const { data, code } = await Api.horizon.batchDetail.get(row.id)
		if (code === 200 && data) {
			selectedBatch.value = data.batch
			batchFailedJobs.value = data.failedJobs || []
			drawerVisible.value = true
		}
	}

	const handleRetry = async (row) => {
		const { code } = await Api.horizon.retryBatch.get(row.id)
		if (code === 200) {
			ElMessage.success('批量重试指令已发送')
			fetchData()
		}
	}

	const { autoRefresh } = usePolling(fetchData)
</script>
