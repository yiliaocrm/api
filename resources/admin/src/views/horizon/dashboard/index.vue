<template>
	<el-container>
		<el-main class="pd15">
			<!-- 状态指示器 -->
			<el-row :gutter="15" style="margin-bottom: 15px">
				<el-col :span="24">
					<el-card shadow="never">
						<div style="display: flex; align-items: center; justify-content: space-between">
							<div style="display: flex; align-items: center; gap: 12px">
								<span
									:style="{
										display: 'inline-block',
										width: '12px',
										height: '12px',
										borderRadius: '50%',
										backgroundColor: statusColor
									}"
								></span>
								<el-text size="large" tag="b">Horizon 状态: {{ statusText }}</el-text>
							</div>
							<div style="display: flex; align-items: center">
								<el-switch v-model="autoRefresh" size="small" style="margin-right: 6px" />
								<el-text type="info">自动刷新</el-text>
							</div>
						</div>
					</el-card>
				</el-col>
			</el-row>

			<!-- KPI 卡片 -->
			<el-row :gutter="15" style="margin-bottom: 15px">
				<el-col :span="6">
					<el-card shadow="hover" class="dashboard-card">
						<div class="card-content">
							<div class="card-icon" style="background: rgba(103, 194, 58, 0.1)">
								<el-icon size="32" color="#67c23a"><Odometer /></el-icon>
							</div>
							<div class="card-info">
								<el-text>每分钟处理</el-text>
								<div class="card-value" style="color: #67c23a">
									{{ stats.jobsPerMinute || 0 }}
								</div>
							</div>
						</div>
					</el-card>
				</el-col>
				<el-col :span="6">
					<el-card shadow="hover" class="dashboard-card">
						<div class="card-content">
							<div class="card-icon" style="background: rgba(64, 158, 255, 0.1)">
								<el-icon size="32" color="#409eff"><List /></el-icon>
							</div>
							<div class="card-info">
								<el-text>近期任务</el-text>
								<div class="card-value" style="color: #409eff">
									{{ stats.recentJobs || 0 }}
								</div>
							</div>
						</div>
					</el-card>
				</el-col>
				<el-col :span="6">
					<el-card shadow="hover" class="dashboard-card">
						<div class="card-content">
							<div class="card-icon" style="background: rgba(245, 108, 108, 0.1)">
								<el-icon size="32" color="#f56c6c"><CircleClose /></el-icon>
							</div>
							<div class="card-info">
								<el-text>失败任务</el-text>
								<div class="card-value" style="color: #f56c6c">
									{{ stats.failedJobs || 0 }}
								</div>
							</div>
						</div>
					</el-card>
				</el-col>
				<el-col :span="6">
					<el-card shadow="hover" class="dashboard-card">
						<div class="card-content">
							<div class="card-icon" style="background: rgba(144, 147, 153, 0.1)">
								<el-icon size="32" color="#909399"><Cpu /></el-icon>
							</div>
							<div class="card-info">
								<el-text>工作进程</el-text>
								<div class="card-value" style="color: #909399">
									{{ stats.processes || 0 }}
								</div>
							</div>
						</div>
					</el-card>
				</el-col>
			</el-row>

			<!-- 队列负载 -->
			<el-card shadow="never">
				<template #header>
					<span>队列负载</span>
				</template>
				<el-table :data="workloadData" border stripe>
					<el-table-column prop="name" label="队列名称" />
					<el-table-column prop="length" label="排队数量" width="120" />
					<el-table-column prop="wait" label="等待时间" width="120">
						<template #default="{ row }">{{ row.wait || 0 }}s</template>
					</el-table-column>
					<el-table-column prop="processes" label="进程数" width="100" />
				</el-table>
			</el-card>
		</el-main>
	</el-container>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref, computed } from 'vue'
	import { usePolling } from '@/utils/usePolling.js'
	import { Odometer, List, CircleClose, Cpu } from '@element-plus/icons-vue'

	defineOptions({
		name: 'HorizonDashboardIndex'
	})

	const stats = ref({})
	const workloadData = ref([])

	const statusColor = computed(() => {
		const map = {
			running: '#67c23a',
			paused: '#e6a23c',
			inactive: '#909399'
		}
		return map[stats.value.status] || '#909399'
	})

	const statusText = computed(() => {
		const map = {
			running: '运行中',
			paused: '已暂停',
			inactive: '未启动'
		}
		return map[stats.value.status] || '未知'
	})

	const fetchData = async () => {
		const [statsRes, workloadRes] = await Promise.all([
			Api.horizon.stats.get(),
			Api.horizon.workload.get()
		])
		if (statsRes.code === 200 && statsRes.data) {
			stats.value = statsRes.data
		}
		if (workloadRes.code === 200 && workloadRes.data) {
			workloadData.value = workloadRes.data
		}
	}

	const { autoRefresh } = usePolling(fetchData)
</script>

<style scoped>
	.dashboard-card {
		border-radius: 8px;
		transition: all 0.3s ease;
	}
	.dashboard-card:hover {
		transform: translateY(-2px);
	}
	.card-content {
		display: flex;
		align-items: center;
		padding: 10px;
	}
	.card-icon {
		margin-right: 20px;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 60px;
		height: 60px;
		border-radius: 50%;
	}
	.card-info {
		flex: 1;
	}
	.card-value {
		font-size: 28px;
		font-weight: bold;
	}
</style>
