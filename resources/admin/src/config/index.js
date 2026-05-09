const env = import.meta.env
const DEFAULT_CONFIG = {
	//标题
	APP_NAME: env.VITE_APP_TITLE,

	//首页地址
	DASHBOARD_URL: '/home/dashboard',

	//版本号
	APP_VER: '1.6.9',

	//内核版本号
	CORE_VER: '1.6.9',

	//scui默认接口地址
	API_URL: '/api',

	//请求超时
	TIMEOUT: 60000,

	//TokenName
	TOKEN_NAME: 'Authorization',

	//Token前缀，注意最后有个空格，如不需要需设置空字符串
	TOKEN_PREFIX: 'Bearer ',

	//追加其他头
	HEADERS: {},

	//请求是否开启缓存
	REQUEST_CACHE: false,

	//布局 默认：default | 通栏：header | 经典：menu | 功能坞：dock
	//dock将关闭标签和面包屑栏
	LAYOUT: 'default',

	//菜单是否折叠
	MENU_IS_COLLAPSE: false,

	//菜单是否启用手风琴效果
	MENU_UNIQUE_OPENED: true,

	//是否开启多标签
	LAYOUT_TAGS: true,

	//语言
	LANG: 'zh-cn',

	//主题颜色
	COLOR: '',

	//是否加密localStorage, 为空不加密，可填写AES(模式ECB,移位Pkcs7)加密
	LS_ENCRYPTION: '',

	//localStorageAES加密秘钥，位数建议填写8的倍数
	LS_ENCRYPTION_key: '2XNN4K8LC0ELVWN4'
}

//合并业务配置
import MY_CONFIG from './myConfig'
Object.assign(DEFAULT_CONFIG, MY_CONFIG)

// 如果生产模式，就合并动态的APP_CONFIG
// public/config.js
if (env.MODE === 'production') {
	Object.assign(DEFAULT_CONFIG, APP_CONFIG)
}

export default DEFAULT_CONFIG
