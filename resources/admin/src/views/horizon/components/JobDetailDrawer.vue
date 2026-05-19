<template>
	<el-drawer v-model="visible" :title="title" size="600px" :destroy-on-close="true">
		<template v-if="jobData">
			<el-descriptions :column="1" border>
				<el-descriptions-item label="ID">{{ jobData.id }}</el-descriptions-item>
				<el-descriptions-item label="状态">
					<el-tag :type="statusType">{{ jobData.status }}</el-tag>
				</el-descriptions-item>
				<el-descriptions-item label="队列">{{ jobData.queue }}</el-descriptions-item>
				<el-descriptions-item label="任务名称">{{ jobName }}</el-descriptions-item>
				<el-descriptions-item v-if="jobData.tags && jobData.tags.length" label="标签">
					<el-tag v-for="tag in jobData.tags" :key="tag" size="small" style="margin-right: 4px">
						{{ tag }}
					</el-tag>
				</el-descriptions-item>
				<el-descriptions-item v-if="jobData.attempts" label="尝试次数">
					{{ jobData.attempts }}
				</el-descriptions-item>
				<el-descriptions-item v-if="jobData.reserved_at" label="开始时间">
					{{ formatTimestamp(jobData.reserved_at) }}
				</el-descriptions-item>
				<el-descriptions-item v-if="jobData.completed_at" label="完成时间">
					{{ formatTimestamp(jobData.completed_at) }}
				</el-descriptions-item>
				<el-descriptions-item v-if="jobData.failed_at" label="失败时间">
					{{ formatTimestamp(jobData.failed_at) }}
				</el-descriptions-item>
				<el-descriptions-item v-if="jobData.runtime" label="运行时长">
					{{ jobData.runtime }} ms
				</el-descriptions-item>
			</el-descriptions>

			<!-- Payload -->
			<el-divider content-position="left">Payload</el-divider>
			<el-input
				type="textarea"
				:model-value="payloadText"
				:autosize="{ minRows: 4, maxRows: 15 }"
				readonly
			/>

			<!-- 异常信息（失败任务） -->
			<template v-if="jobData.exception">
				<el-divider content-position="left">异常信息</el-divider>
				<el-input
					type="textarea"
					:model-value="jobData.exception"
					:autosize="{ minRows: 4, maxRows: 20 }"
					readonly
				/>
			</template>

			<!-- 重试历史 -->
			<template v-if="jobData.retried_by && jobData.retried_by.length">
				<el-divider content-position="left">重试历史</el-divider>
				<el-table :data="jobData.retried_by" border size="small">
					<el-table-column prop="id" label="重试Job ID" show-overflow-tooltip />
					<el-table-column prop="retried_at" label="重试时间" width="180">
						<template #default="{ row }">
							{{ formatTimestamp(row.retried_at) }}
						</template>
					</el-table-column>
				</el-table>
			</template>

			<!-- 操作按钮 -->
			<div v-if="showRetry" style="margin-top: 16px; text-align: right">
				<el-button type="primary" @click="handleRetry">重试此任务</el-button>
			</div>
		</template>
		<el-empty v-else description="暂无数据" />
	</el-drawer>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref, computed } from 'vue'
	import { ElMessage } from 'element-plus'

	defineOptions({
		name: 'JobDetailDrawer'
	})

	const emit = defineEmits(['retry'])

	const visible = ref(false)
	const jobData = ref(null)
	const showRetry = ref(false)

	const title = computed(() => {
		return jobData.value ? `任务详情 - ${jobData.value.id}` : '任务详情'
	})

	const statusType = computed(() => {
		if (!jobData.value) return 'info'
		const map = {
			completed: 'success',
			failed: 'danger',
			pending: 'warning',
			reserved: 'primary'
		}
		return map[jobData.value.status] || 'info'
	})

	const jobName = computed(() => {
		if (!jobData.value || !jobData.value.payload) return '-'
		const payload = jobData.value.payload
		return payload.displayName || payload.job || '-'
	})

	const payloadText = computed(() => {
		if (!jobData.value || !jobData.value.payload) return ''
		try {
			return JSON.stringify(jobData.value.payload, null, 2)
		} catch {
			return String(jobData.value.payload)
		}
	})

	const formatTimestamp = (ts) => {
		if (!ts) return '-'
		const d = new Date(ts * 1000)
		if (isNaN(d.getTime())) return ts
		return d.toLocaleString('zh-CN')
	}

	const open = (job, canRetry = false) => {
		jobData.value = job
		showRetry.value = canRetry
		visible.value = true
	}

	const openById = async (id, canRetry = false) => {
		const { data, code } = await Api.horizon.jobDetail.get(id)
		if (code === 200 && data) {
			jobData.value = data
			showRetry.value = canRetry
			visible.value = true
		}
	}

	const handleRetry = async () => {
		if (!jobData.value) return
		const { code } = await Api.horizon.retryJob.get(jobData.value.id)
		if (code === 200) {
			ElMessage.success('重试指令已发送')
			emit('retry')
		}
	}

	defineExpose({ open, openById })
</script>
