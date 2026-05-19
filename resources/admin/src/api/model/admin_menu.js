import http from '@/utils/request'
import config from '@/config'

export default {
	index: {
		url: `${config.API_URL}/admin-menu/index`,
		name: '菜单管理',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	tree: {
		url: `${config.API_URL}/admin-menu/tree`,
		name: '菜单树',
		get: async function (params) {
			return await http.get(this.url, params)
		}
	},
	create: {
		url: `${config.API_URL}/admin-menu/create`,
		name: '新增菜单',
		post: async function (params) {
			return await http.post(this.url, params)
		}
	},
	update: {
		url: `${config.API_URL}/admin-menu/update`,
		name: '修改菜单',
		post: async function (params) {
			return await http.post(this.url, params)
		}
	},
	remove: {
		url: `${config.API_URL}/admin-menu/remove`,
		name: '删除菜单',
		get: async function (id) {
			return await http.get(this.url, { id })
		}
	}
}
