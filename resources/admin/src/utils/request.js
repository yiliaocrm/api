import axios from 'axios'
import { ElNotification, ElMessageBox } from 'element-plus'
import sysConfig from '@/config'
import tool from '@/utils/tool'
import router from '@/router'

axios.defaults.baseURL = ''

axios.defaults.timeout = sysConfig.TIMEOUT

// HTTP request 拦截器
axios.interceptors.request.use(
	(config) => {
		let token = tool.session.get('token')
		if (token) {
			config.headers[sysConfig.TOKEN_NAME] = sysConfig.TOKEN_PREFIX + token
		}
		if (!sysConfig.REQUEST_CACHE && config.method == 'get') {
			config.params = config.params || {}
			config.params['_'] = new Date().getTime()
		}
		Object.assign(config.headers, sysConfig.HEADERS)
		return config
	},
	(error) => {
		return Promise.reject(error)
	}
)

//FIX 多个API同时401时疯狂弹窗BUG
let MessageBox_401_show = false

// HTTP response 拦截器
axios.interceptors.response.use(
	async (response) => {
		// 处理 blob 类型响应的错误
		if (response.config.responseType === 'blob') {
			// 检查响应的 Content-Type，如果是 JSON 说明是错误响应
			const contentType = response.headers['content-type']
			if (contentType && contentType.includes('application/json')) {
				// 将 blob 转换为 JSON
				const text = await response.data.text()
				const jsonData = JSON.parse(text)

				// 显示错误提示
				if (jsonData.code && jsonData.code !== 200) {
					ElNotification.error({
						title: '请求错误',
						message: jsonData.msg || '下载文件失败'
					})
					// 抛出错误，阻止后续的下载流程
					return Promise.reject(jsonData)
				}
			}
		}

		const { code, msg } = response.data

		// 401:未登录
		if (code && code == 401) {
			if (!MessageBox_401_show) {
				MessageBox_401_show = true
				ElMessageBox.confirm('登录超时,请重新登录!', '无权限访问', {
					type: 'error',
					closeOnClickModal: false,
					center: true,
					confirmButtonText: '重新登录',
					beforeClose: (action, instance, done) => {
						MessageBox_401_show = false
						done()
					}
				})
					.then(() => {
						router.replace({ path: '/login' })
						setTimeout(() => {
							window.location.reload()
						}, 100)
					})
					.catch(() => {})
			}
		}

		if (code && msg && code !== 200) {
			ElNotification.error({
				title: '请求错误',
				message: msg
			})
		}

		return response
	},
	(error) => {
		let msg = '请求出错!'
		let code = {
			403: '禁止访问!',
			404: '请求不存在!',
			405: '请求方法未允许!',
			500: '服务器内部错误，无法完成请求'
		}

		const { response } = error

		if (code[response.status]) {
			msg = code[response.status]
		}

		ElNotification.error({
			title: '请求错误',
			message: msg
		})

		return {
			code: response.status,
			data: [],
			msg: msg
		}

		// return Promise.reject(error.response)
	}
)

var http = {
	/** get 请求
	 * @param  {string} url 接口地址
	 * @param  {object} params 请求参数
	 * @param  {object} config 参数
	 */
	get: function (url, params = {}, config = {}) {
		return new Promise((resolve, reject) => {
			axios({
				method: 'get',
				url: url,
				params: params,
				...config
			})
				.then((response) => {
					resolve(response.data)
				})
				.catch((error) => {
					reject(error)
				})
		})
	},

	/** post 请求
	 * @param  {string} url 接口地址
	 * @param  {object} data 请求参数
	 * @param  {object} config 参数
	 */
	post: function (url, data = {}, config = {}) {
		return new Promise((resolve, reject) => {
			axios({
				method: 'post',
				url: url,
				data: data,
				...config
			})
				.then((response) => {
					resolve(response.data)
				})
				.catch((error) => {
					reject(error)
				})
		})
	},

	/** put 请求
	 * @param  {string} url 接口地址
	 * @param  {object} data 请求参数
	 * @param  {object} config 参数
	 */
	put: function (url, data = {}, config = {}) {
		return new Promise((resolve, reject) => {
			axios({
				method: 'put',
				url: url,
				data: data,
				...config
			})
				.then((response) => {
					resolve(response.data)
				})
				.catch((error) => {
					reject(error)
				})
		})
	},

	/** patch 请求
	 * @param  {string} url 接口地址
	 * @param  {object} data 请求参数
	 * @param  {object} config 参数
	 */
	patch: function (url, data = {}, config = {}) {
		return new Promise((resolve, reject) => {
			axios({
				method: 'patch',
				url: url,
				data: data,
				...config
			})
				.then((response) => {
					resolve(response.data)
				})
				.catch((error) => {
					reject(error)
				})
		})
	},

	/** delete 请求
	 * @param  {string} url 接口地址
	 * @param  {object} data 请求参数
	 * @param  {object} config 参数
	 */
	delete: function (url, data = {}, config = {}) {
		return new Promise((resolve, reject) => {
			axios({
				method: 'delete',
				url: url,
				data: data,
				...config
			})
				.then((response) => {
					resolve(response.data)
				})
				.catch((error) => {
					reject(error)
				})
		})
	},

	/** jsonp 请求
	 * @param  {string} url 接口地址
	 * @param  {string} name JSONP回调函数名称
	 */
	jsonp: function (url, name = 'jsonp') {
		return new Promise((resolve) => {
			var script = document.createElement('script')
			var _id = `jsonp${Math.ceil(Math.random() * 1000000)}`
			script.id = _id
			script.type = 'text/javascript'
			script.src = url
			window[name] = (response) => {
				resolve(response)
				document.getElementsByTagName('head')[0].removeChild(script)
				try {
					delete window[name]
				} catch (e) {
					window[name] = undefined
				}
			}
			document.getElementsByTagName('head')[0].appendChild(script)
		})
	}
}

export default http
