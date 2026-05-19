import axios from 'axios'
import { toast } from '@/utils/toast.js'

// 创建 axios 实例
const service = axios.create({
  baseURL: import.meta.env.PROD ? '/install' : '/api', // 根据环境使用不同的baseURL
  timeout: 15000, // 请求超时时间
  withCredentials: true, // 允许跨域请求携带 cookies
  headers: {
    'Content-Type': 'application/json'
  }
})

// 请求拦截器
service.interceptors.request.use(
  config => {
    // 在这里可以添加token等认证信息
    return config
  },
  error => {
    console.error('请求错误：', error)
    return Promise.reject(error)
  }
)

// 响应拦截器
service.interceptors.response.use(
  response => {
    const res = response.data

    // 处理成功响应
    if (res.code === 200) {
      return res
    }

    // 处理业务错误
    toast.error(res.msg || '请求失败')

    return Promise.reject(new Error(res.msg || '请求失败'))
  },
  error => {
    console.error('响应错误：', error)

    // 处理HTTP错误
    const errorMap = {
      400: '请求参数错误',
      401: '未授权，请重新登录',
      403: '拒绝访问',
      404: '请求错误,未找到该资源',
      405: '请求方法未允许',
      408: '请求超时',
      500: '服务器端出错',
      501: '网络未实现',
      502: '网络错误',
      503: '服务不可用',
      504: '网络超时',
      505: 'http版本不支持该请求'
    }

    const status = error.response?.status
    const errorMessage = errorMap[status] || '网络错误'

    toast.error(errorMessage)

    return Promise.reject(error)
  }
)

export default service 