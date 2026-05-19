import Api from '@/api/index.js'
import Tool from '@/utils/tool.js'

const state = () => ({
	token: Tool.session.get('token'),
	logined: false,
	profile: {
		permissions: {}
	},
	menus: [],
	hospital: null
})

const getters = {}

const mutations = {
	setToken(state, token) {
		state.token = token
		Tool.session.set('token', token)
	},
	clearToken(state) {
		state.token = null
		state.logined = false
		Tool.session.remove('token')
	},
	setProfile(state, profile) {
		state.logined = true
		state.profile = profile.permissions ? profile : Object.assign({}, profile, { permissions: {} })
		// 兼容scui
		Tool.session.set('USER_INFO', profile)
		Tool.session.set('PERMISSIONS', profile.permissions)
	},
	setHospital(state, hospital) {
		state.hospital = hospital
	},
	logout(state) {
		state.token = null
		state.logined = false
		Tool.session.remove('token')
	},
	setMenus(state, menus) {
		state.menus = menus
		Tool.session.set('MENU', menus) // 兼容scui
	}
}

const actions = {
	setToken({ commit }, token) {
		commit('setToken', token)
	},
	clearToken({ commit }) {
		commit('clearToken')
	},
	setProfile({ commit }, profile) {
		commit('setProfile', profile)
	},
	refreshToken({ commit }, token) {
		commit('setToken', token)
	},
	async getProfile({ commit }) {
		const { data, code } = await Api.auth.profile.get()
		if (data && code == 200) {
			commit('setMenus', data.menu)
			commit('setProfile', data.user)
			commit('setHospital', data.hospital)
		}
	},
	async logout({ commit }) {
		let { data, code } = await Api.auth.logout.get()
		if (data && code == 200) {
			commit('logout')
			location.reload()
		}
	}
}

export default {
	namespaced: true,
	state,
	getters,
	mutations,
	actions
}
