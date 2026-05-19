import http from '@/utils/request.js'
import config from '@/config/index.js'

export default {
	index: {
		url: `${config.API_URL}/dashboard/index`,
		name: '仪表盘首页',
		get: async function () {
			return await http.get(this.url)
		}
	}
}
