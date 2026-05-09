import config from '@/config'

//系统路由
const routes = [
	{
		name: 'layout',
		path: '/',
		component: () => import('@/layout/index.vue'),
		redirect: config.DASHBOARD_URL || '/dashboard',
		children: []
	},
	{
		path: '/login',
		component: () => import('@/views/login/index.vue'),
		meta: {
			title: '登录'
		}
	}
]

export default routes
