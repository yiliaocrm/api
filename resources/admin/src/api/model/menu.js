import http from '@/utils/request'
import config from '@/config'

export default {
	index: {
		url: `${config.API_URL}/menu/index`,
		name: '菜单管理',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	info: {
		url: `${config.API_URL}/menu/info`,
		name: '菜单详情',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	tree: {
		url: `${config.API_URL}/menu/tree`,
		name: '菜单树',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	create: {
		url: `${config.API_URL}/menu/create`,
		name: '新增菜单',
		post: async function (params) {
			return await http.post(this.url, params)
		}
	},
	update: {
		url: `${config.API_URL}/menu/update`,
		name: '修改菜单',
		post: async function (params) {
			return await http.post(this.url, params)
		}
	},
	remove: {
		url: `${config.API_URL}/menu/remove`,
		name: '删除菜单',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	},
	scope: {
		url: `${config.API_URL}/menu/scope`,
		name: '权限范围',
		get: async function () {
			return await http.get(this.url)
		}
	},
	sync: {
		url: `${config.API_URL}/menu/sync`,
		name: '同步菜单',
		get: async function () {
			return await http.get(this.url)
		}
	}
}
