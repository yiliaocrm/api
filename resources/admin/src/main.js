import { createApp } from 'vue'
import ElementPlus from 'element-plus'
import VxeUITable from 'vxe-table'
import VxeUI from 'vxe-pc-ui'
import './style/tailwind.css'
import './assets/styles/vxe-table.scss'
import 'element-plus/dist/index.css'
import 'element-plus/theme-chalk/display.css'
import scui from './scui'
import i18n from './locales'
import router from './router'
import store from './store'
import App from './App.vue'

// vxe-table自定义渲染器
import './components/cyTable/renderer/index.js'

// vxe-table 全局配置
VxeUITable.setConfig({
	zIndex: 9999
})

const app = createApp(App)

app.use(store)
app.use(router)
app.use(VxeUI)
app.use(VxeUITable)
app.use(ElementPlus)
app.use(i18n)
app.use(scui)

//挂载app
app.mount('#app')
