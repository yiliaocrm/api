import http from '@/utils/request'
import config from '@/config'

export default {
	index: {
		url: `${config.API_URL}/tenant-login-banner/index`,
		name: 'Banner列表',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	create: {
		url: `${config.API_URL}/tenant-login-banner/create`,
		name: '创建Banner',
		post: async function (params) {
			return await http.post(this.url, params)
		}
	},
	update: {
		url: `${config.API_URL}/tenant-login-banner/update`,
		name: '更新Banner',
		post: async function (params) {
			return await http.post(this.url, params)
		}
	},
	remove: {
		url: `${config.API_URL}/tenant-login-banner/remove`,
		name: '删除Banner',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	info: {
		url: `${config.API_URL}/tenant-login-banner/info`,
		name: 'Banner详情',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	toggle: {
		url: `${config.API_URL}/tenant-login-banner/toggle`,
		name: '切换启用状态',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	}
}
