import http from '@/utils/request.js'
import config from '@/config/index.js'

export default {
	load: {
		url: `${config.API_URL}/config/load`,
		name: '获取系统参数配置',
		get: async function () {
			return await http.get(this.url)
		}
	},
	save: {
		url: `${config.API_URL}/config/save`,
		name: '保存系统参数配置',
		post: async function (data) {
			return await http.post(this.url, data)
		}
	},
	secret: {
		url: `${config.API_URL}/config/secret`,
		name: '获取2fa密钥',
		get: async function () {
			return await http.get(this.url)
		}
	},
	verify: {
		url: `${config.API_URL}/config/verify`,
		name: '验证2fa验证码',
		post: async function (data) {
			return await http.post(this.url, data)
		}
	},
	distSync: {
		url: `${config.API_URL}/config/dist-sync`,
		name: '一键同步dist静态资源',
		post: async function () {
			return await http.post(this.url)
		}
	}
}
