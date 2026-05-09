<template>
	<el-container>
		<el-main class="pd15">
			<el-row :gutter="15">
				<!-- 租户总数 -->
				<el-col :span="6">
					<el-card shadow="hover" class="dashboard-card total-card">
						<div class="card-content">
							<div class="card-icon">
								<el-icon size="32" color="#409eff">
									<User />
								</el-icon>
							</div>
							<div class="card-info">
								<el-text>机构总数</el-text>
								<div class="card-value">{{ dashboardData.total }}</div>
							</div>
						</div>
					</el-card>
				</el-col>

				<!-- 增长数 -->
				<el-col :span="6">
					<el-card shadow="hover" class="dashboard-card growth-card">
						<div class="card-content">
							<div class="card-icon">
								<el-icon size="32" color="#67c23a">
									<TrendCharts />
								</el-icon>
							</div>
							<div class="card-info">
								<el-text>本月新增</el-text>
								<div class="card-value">{{ dashboardData.growth }}</div>
							</div>
						</div>
					</el-card>
				</el-col>

				<!-- 警告数 -->
				<el-col :span="6">
					<el-card shadow="hover" class="dashboard-card warning-card">
						<div class="card-content">
							<div class="card-icon">
								<el-icon size="32" color="#e6a23c">
									<Warning />
								</el-icon>
							</div>
							<div class="card-info">
								<el-space>
									<el-text>本月到期</el-text>
									<el-tooltip effect="dark" content="本月即将到期租户数量" placement="right">
										<el-icon :size="18"><el-icon-question-filled /></el-icon>
									</el-tooltip>
								</el-space>
								<div class="card-value">{{ dashboardData.warning }}</div>
							</div>
						</div>
					</el-card>
				</el-col>

				<!-- 过期数 -->
				<el-col :span="6">
					<el-card shadow="hover" class="dashboard-card expired-card">
						<div class="card-content">
							<div class="card-icon">
								<el-icon size="32" color="#f56c6c">
									<CircleClose />
								</el-icon>
							</div>
							<div class="card-info">
								<el-text>到期数量</el-text>
								<div class="card-value">{{ dashboardData.expired }}</div>
							</div>
						</div>
					</el-card>
				</el-col>
			</el-row>
		</el-main>
	</el-container>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { useMsgbox } from '@/utils/helper.js'
	import { onMounted, ref } from 'vue'
	import { User, TrendCharts, Warning, CircleClose } from '@element-plus/icons-vue'

	defineOptions({
		name: 'DashboardIndex'
	})

	const msgbox = useMsgbox()

	// 仪表板数据
	const dashboardData = ref({
		total: 0,
		growth: 0,
		warning: 0,
		expired: 0
	})

	const loadData = async () => {
		msgbox.loading()
		const { data, code } = await Api.dashboard.index.get()
		if (code === 200 && data) {
			dashboardData.value = data.dashboard
		}
		msgbox.close()
	}

	onMounted(() => {
		loadData()
	})
</script>

<style scoped>
	.dashboard-card {
		border-radius: 8px;
		transition: all 0.3s ease;
		margin-bottom: 20px;
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
		background: rgba(64, 158, 255, 0.1);
	}

	.growth-card .card-icon {
		background: rgba(103, 194, 58, 0.1);
	}

	.warning-card .card-icon {
		background: rgba(230, 162, 60, 0.1);
	}

	.expired-card .card-icon {
		background: rgba(245, 108, 108, 0.1);
	}

	.card-info {
		flex: 1;
	}

	.card-value {
		font-size: 28px;
		font-weight: bold;
		color: #303133;
	}

	.total-card .card-value {
		color: #409eff;
	}

	.growth-card .card-value {
		color: #67c23a;
	}

	.warning-card .card-value {
		color: #e6a23c;
	}

	.expired-card .card-value {
		color: #f56c6c;
	}
</style>
