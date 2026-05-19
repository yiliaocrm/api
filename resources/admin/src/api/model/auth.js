import config from '@/config'
import http from '@/utils/request'

export default {
	login: {
		url: `${config.API_URL}/auth/login`,
		name: '登录',
		post: async function (credentials) {
			return await http.post(this.url, credentials)
		}
	},
	logout: {
		url: `${config.API_URL}/auth/logout`,
		name: '退出登录',
		get: async function () {
			return await http.get(this.url)
		}
	},
	qrcode: {
		url: `${config.API_URL}/auth/qrcode`,
		name: '获取扫码登录二维码',
		get: async function (uuid) {
			return await http.get(this.url, { uuid })
		}
	},
	config: {
		url: `${config.API_URL}/auth/config`,
		name: '获取系统配置参数',
		get: async function () {
			return await http.get(this.url)
		}
	},
	profile: {
		url: `${config.API_URL}/auth/profile`,
		name: '获取用户信息',
		get: async function () {
			return await http.get(this.url)
		}
	},
	resetPassword: {
		url: `${config.API_URL}/auth/reset-password`,
		name: '修改密码',
		post: async function (data) {
			return await http.post(this.url, data)
		}
	}
}
