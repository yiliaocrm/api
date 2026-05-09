import config from './config'
import api from './api'
import tool from './utils/tool'
import http from './utils/request'
import { permission, rolePermission } from './utils/permission'

import cyBadge from './components/cyBadge/index.vue'
import cyTable from './components/cyTable/index.vue'
import CyDialog from './components/cyDialog/index.vue'
import CyDrawer from './components/cyDrawer/index.vue'

import scUpload from './components/scUpload'
import scUploadMultiple from './components/scUpload/multiple'
import scUploadFile from './components/scUpload/file'
import scQrCode from './components/scQrCode'
import scFileExport from './components/scFileExport/index.vue'
import scFileImport from './components/scFileImport/index.vue'
import scIconSelect from './components/scIconSelect/index.vue'

import auth from './directives/auth'
import auths from './directives/auths'
import authsAll from './directives/authsAll'
import role from './directives/role'
import time from './directives/time'
import copy from './directives/copy'

import * as elIcons from '@element-plus/icons-vue'
import * as scIcons from './assets/icons'

export default {
	install(app) {
		//挂载全局对象
		app.config.globalProperties.$CONFIG = config
		app.config.globalProperties.$TOOL = tool
		app.config.globalProperties.$HTTP = http
		app.config.globalProperties.$API = api
		app.config.globalProperties.$AUTH = permission
		app.config.globalProperties.$ROLE = rolePermission

		//注册全局组件
		app.component('cyBadge', cyBadge)
		app.component('CyDialog', CyDialog)
		app.component('cyTable', cyTable)
		app.component('CyDrawer', CyDrawer)

		app.component('scUpload', scUpload)
		app.component('scUploadMultiple', scUploadMultiple)
		app.component('scUploadFile', scUploadFile)
		app.component('scQrCode', scQrCode)
		app.component('scIconSelect', scIconSelect)
		app.component('scFileExport', scFileExport)
		app.component('scFileImport', scFileImport)

		//注册全局指令
		app.directive('auth', auth)
		app.directive('auths', auths)
		app.directive('auths-all', authsAll)
		app.directive('role', role)
		app.directive('time', time)
		app.directive('copy', copy)

		//统一注册el-icon图标
		for (let icon in elIcons) {
			app.component(`ElIcon${icon}`, elIcons[icon])
		}
		//统一注册sc-icon图标
		for (let icon in scIcons) {
			app.component(`ScIcon${icon}`, scIcons[icon])
		}

		//关闭async-validator全局控制台警告
		window.ASYNC_VALIDATOR_NO_WARNING = 1
	}
}
