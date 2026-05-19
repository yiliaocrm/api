<template>
	<el-container>
		<el-main class="pd15">
			<el-card shadow="never">
				<el-tabs v-model="activeTab" @tab-change="handleTabChange">
					<el-tab-pane label="Job 指标" name="jobs">
						<el-table
							:data="jobMetricsList"
							border
							stripe
							@row-click="handleJobMetricClick"
							style="margin-bottom: 15px"
						>
							<el-table-column prop="name" label="Job 名称" min-width="300" show-overflow-tooltip>
								<template #default="{ row }">
									<el-link type="primary">{{ row }}</el-link>
								</template>
							</el-table-column>
						</el-table>

						<template v-if="selectedJobMetric">
							<el-divider content-position="left"> {{ selectedJobMetric }} - 性能指标 </el-divider>
							<el-row :gutter="15">
								<el-col :span="12">
									<el-card shadow="never">
										<template #header>运行时间 (秒)</template>
										<div ref="runtimeChartRef" style="height: 300px"></div>
									</el-card>
								</el-col>
								<el-col :span="12">
									<el-card shadow="never">
										<template #header>吞吐量 (次/分钟)</template>
										<div ref="throughputChartRef" style="height: 300px"></div>
									</el-card>
								</el-col>
							</el-row>
						</template>
					</el-tab-pane>

					<el-tab-pane label="队列指标" name="queues">
						<el-table
							:data="queueMetricsList"
							border
							stripe
							@row-click="handleQueueMetricClick"
							style="margin-bottom: 15px"
						>
							<el-table-column prop="name" label="队列名称" min-width="300" show-overflow-tooltip>
								<template #default="{ row }">
									<el-link type="primary">{{ row }}</el-link>
								</template>
							</el-table-column>
						</el-table>

						<template v-if="selectedQueueMetric">
							<el-divider content-position="left">
								{{ selectedQueueMetric }} - 性能指标
							</el-divider>
							<el-row :gutter="15">
								<el-col :span="12">
									<el-card shadow="never">
										<template #header>运行时间 (秒)</template>
										<div ref="queueRuntimeChartRef" style="height: 300px"></div>
									</el-card>
								</el-col>
								<el-col :span="12">
									<el-card shadow="never">
										<template #header>吞吐量 (次/分钟)</template>
										<div ref="queueThroughputChartRef" style="height: 300px"></div>
									</el-card>
								</el-col>
							</el-row>
						</template>
					</el-tab-pane>
				</el-tabs>
			</el-card>
		</el-main>
	</el-container>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref, onMounted, nextTick, markRaw } from 'vue'
	import * as echarts from 'echarts'

	defineOptions({
		name: 'HorizonMetricsIndex'
	})

	const activeTab = ref('jobs')
	const jobMetricsList = ref([])
	const queueMetricsList = ref([])
	const selectedJobMetric = ref(null)
	const selectedQueueMetric = ref(null)

	const runtimeChartRef = ref(null)
	const throughputChartRef = ref(null)
	const queueRuntimeChartRef = ref(null)
	const queueThroughputChartRef = ref(null)

	let runtimeChart = null
	let throughputChart = null
	let queueRuntimeChart = null
	let queueThroughputChart = null

	const buildChartOption = (data, field, color) => {
		return {
			tooltip: { trigger: 'axis' },
			grid: { left: 60, right: 20, top: 20, bottom: 40 },
			xAxis: {
				type: 'category',
				data: data.map((_, i) => i + 1),
				axisLabel: { show: false }
			},
			yAxis: { type: 'value' },
			series: [
				{
					type: 'line',
					data: data.map((item) => item[field]),
					smooth: true,
					areaStyle: { opacity: 0.15 },
					lineStyle: { color },
					itemStyle: { color }
				}
			]
		}
	}

	const renderCharts = (data, runtimeEl, throughputEl, isQueue = false) => {
		nextTick(() => {
			if (runtimeEl) {
				const chart = echarts.init(runtimeEl)
				chart.setOption(buildChartOption(data, 'runtime', '#409eff'))
				if (isQueue) {
					queueRuntimeChart = markRaw(chart)
				} else {
					runtimeChart = markRaw(chart)
				}
			}
			if (throughputEl) {
				const chart = echarts.init(throughputEl)
				chart.setOption(buildChartOption(data, 'throughput', '#67c23a'))
				if (isQueue) {
					queueThroughputChart = markRaw(chart)
				} else {
					throughputChart = markRaw(chart)
				}
			}
		})
	}

	const handleTabChange = () => {
		if (activeTab.value === 'jobs' && jobMetricsList.value.length === 0) {
			loadJobMetrics()
		}
		if (activeTab.value === 'queues' && queueMetricsList.value.length === 0) {
			loadQueueMetrics()
		}
	}

	const loadJobMetrics = async () => {
		const { data, code } = await Api.horizon.jobMetrics.get()
		if (code === 200 && data) {
			jobMetricsList.value = data
		}
	}

	const loadQueueMetrics = async () => {
		const { data, code } = await Api.horizon.queueMetrics.get()
		if (code === 200 && data) {
			queueMetricsList.value = data
		}
	}

	const handleJobMetricClick = async (row) => {
		const jobName = typeof row === 'string' ? row : row.name || row
		selectedJobMetric.value = jobName

		// 销毁旧图表
		if (runtimeChart) {
			runtimeChart.dispose()
			runtimeChart = null
		}
		if (throughputChart) {
			throughputChart.dispose()
			throughputChart = null
		}

		const { data, code } = await Api.horizon.jobMetricDetail.get(jobName)
		if (code === 200 && data) {
			await nextTick()
			renderCharts(data, runtimeChartRef.value, throughputChartRef.value, false)
		}
	}

	const handleQueueMetricClick = async (row) => {
		const queueName = typeof row === 'string' ? row : row.name || row
		selectedQueueMetric.value = queueName

		// 销毁旧图表
		if (queueRuntimeChart) {
			queueRuntimeChart.dispose()
			queueRuntimeChart = null
		}
		if (queueThroughputChart) {
			queueThroughputChart.dispose()
			queueThroughputChart = null
		}

		const { data, code } = await Api.horizon.queueMetricDetail.get(queueName)
		if (code === 200 && data) {
			await nextTick()
			renderCharts(data, queueRuntimeChartRef.value, queueThroughputChartRef.value, true)
		}
	}

	onMounted(() => {
		loadJobMetrics()
		loadQueueMetrics()
	})
</script>
