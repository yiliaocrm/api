import http from '@/utils/request'
import config from '@/config'

export default {
	// Dashboard
	stats: {
		url: `${config.API_URL}/horizon/stats`,
		name: '队列统计',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	workload: {
		url: `${config.API_URL}/horizon/workload`,
		name: '队列负载',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	masters: {
		url: `${config.API_URL}/horizon/masters`,
		name: '主进程',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},

	// Metrics
	jobMetrics: {
		url: `${config.API_URL}/horizon/metrics/jobs`,
		name: 'Job指标',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	jobMetricDetail: {
		url: `${config.API_URL}/horizon/metrics/jobs/detail`,
		name: 'Job指标详情',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	queueMetrics: {
		url: `${config.API_URL}/horizon/metrics/queues`,
		name: '队列指标',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	queueMetricDetail: {
		url: `${config.API_URL}/horizon/metrics/queues/detail`,
		name: '队列指标详情',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},

	// Jobs
	pendingJobs: {
		url: `${config.API_URL}/horizon/jobs/pending`,
		name: '待处理任务',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	completedJobs: {
		url: `${config.API_URL}/horizon/jobs/completed`,
		name: '已完成任务',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	failedJobs: {
		url: `${config.API_URL}/horizon/jobs/failed`,
		name: '失败任务',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	failedJobDetail: {
		url: `${config.API_URL}/horizon/jobs/failed/detail`,
		name: '失败任务详情',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	retryJob: {
		url: `${config.API_URL}/horizon/jobs/retry`,
		name: '重试任务',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	silencedJobs: {
		url: `${config.API_URL}/horizon/jobs/silenced`,
		name: '静默任务',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	jobDetail: {
		url: `${config.API_URL}/horizon/jobs/detail`,
		name: '任务详情',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},

	// Monitoring
	monitoring: {
		url: `${config.API_URL}/horizon/monitoring/index`,
		name: '监控标签',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	storeMonitoring: {
		url: `${config.API_URL}/horizon/monitoring/store`,
		name: '添加监控',
		get: async function (tag) {
			return await http.get(this.url, { tag })
		}
	},
	monitoringJobs: {
		url: `${config.API_URL}/horizon/monitoring/jobs`,
		name: '监控任务',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	destroyMonitoring: {
		url: `${config.API_URL}/horizon/monitoring/destroy`,
		name: '删除监控',
		get: async function (tag) {
			return await http.get(this.url, { tag })
		}
	},

	// Batches
	batches: {
		url: `${config.API_URL}/horizon/batches/index`,
		name: '批处理列表',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	batchDetail: {
		url: `${config.API_URL}/horizon/batches/detail`,
		name: '批处理详情',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	retryBatch: {
		url: `${config.API_URL}/horizon/batches/retry`,
		name: '重试批处理',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	}
}
