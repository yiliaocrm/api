import http from '@/utils/request'
import config from '@/config'

export default {
	index: {
		url: `${config.API_URL}/tenant/index`,
		name: '数据列表',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	remove: {
		url: `${config.API_URL}/tenant/remove`,
		name: '数据列表',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	run: {
		url: `${config.API_URL}/tenant/run`,
		name: '运行租户',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	pause: {
		url: `${config.API_URL}/tenant/pause`,
		name: '运行租户',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	create: {
		url: `${config.API_URL}/tenant/create`,
		name: '创建租户',
		post: async function (params) {
			return await http.post(this.url, params)
		}
	},
	update: {
		url: `${config.API_URL}/tenant/update`,
		name: '更新租户',
		post: async function (params) {
			return await http.post(this.url, params)
		}
	},
	login: {
		url: `${config.API_URL}/tenant/login`,
		name: '登录租户',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	}
}
