import { createStore } from 'vuex'

import auth from './modules/auth.js'
import global from './modules/global'
import iframe from './modules/iframe'
import viewTags from './modules/viewTags'
import keepAlive from './modules/keepAlive'

const store = createStore({
	modules: {
		auth,
		global,
		iframe,
		viewTags,
		keepAlive
	}
})

export default store
