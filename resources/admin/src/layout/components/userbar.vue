<template>
	<div class="user-bar">
		<div class="panel-item hidden-sm-and-down" @click="handleSearch">
			<el-icon>
				<el-icon-search />
			</el-icon>
		</div>
		<div class="screen panel-item hidden-sm-and-down" @click="handleScreen">
			<el-icon>
				<el-icon-full-screen />
			</el-icon>
		</div>
		<el-tooltip :show-arrow="false" content="帮助中心" placement="bottom" effect="light">
			<div class="tasks panel-item" @click="handleHelp">
				<el-icon>
					<el-icon-question-filled />
				</el-icon>
			</div>
		</el-tooltip>
		<div class="split"></div>
		<el-dropdown class="user panel-item" trigger="click" @command="handleUser">
			<div class="user-avatar">
				<el-avatar :size="30">{{ nameF }}</el-avatar>
				<label>{{ name }}</label>
				<el-icon class="el-icon--right">
					<el-icon-arrow-down />
				</el-icon>
			</div>
			<template #dropdown>
				<el-dropdown-menu>
					<el-dropdown-item command="uc">帐号信息</el-dropdown-item>
					<el-dropdown-item divided command="logout">退出登录</el-dropdown-item>
				</el-dropdown-menu>
			</template>
		</el-dropdown>
	</div>

	<el-dialog
		v-model="searchVisible"
		:show-close="false"
		:width="750"
		center
		destroy-on-close
		style="border-radius: 8px"
	>
		<search @success="searchVisible = false"></search>
	</el-dialog>
</template>

<script setup>
	import Tool from '@/utils/tool.js'
	import { useStore } from 'vuex'
	import { useRouter } from 'vue-router'
	import { ElMessageBox } from 'element-plus'
	import { ref, onMounted, computed } from 'vue'
	import search from './search.vue'

	const store = useStore()
	const router = useRouter()

	const name = ref('')
	const nameF = ref('')
	const searchVisible = ref(false)

	onMounted(() => {
		const userInfo = Tool.session.get('USER_INFO')
		name.value = userInfo.name
		nameF.value = name.value.substring(0, 1)
	})

	// 个人信息处理
	const handleUser = (command) => {
		if (command === 'uc') {
			router.push({ path: '/home/profile' })
		}
		if (command === 'logout') {
			ElMessageBox.confirm('确认是否退出当前用户？', '提示', {
				type: 'warning',
				confirmButtonText: '退出',
				confirmButtonClass: 'el-button--danger'
			})
				.then(() => {
					store.dispatch('auth/logout', null, {
						root: true
					})
				})
				.catch(() => {
					// 取消退出
				})
		}
	}

	// 全屏
	const handleScreen = () => {
		Tool.screen(document.documentElement)
	}

	// 搜索
	const handleSearch = () => {
		searchVisible.value = true
	}

	// 帮助中心
	const handleHelp = () => {
		window.open('http://help.yiliaocrm.com', '_blank')
	}
</script>

<style scoped>
	.user-bar {
		display: flex;
		align-items: center;
		height: 100%;
	}

	.user-bar .split {
		width: 1px;
		height: 18px;
		margin: 0 5px;
		background: #0003;
	}

	.user-bar .panel-item {
		padding: 0 10px;
		cursor: pointer;
		height: 100%;
		display: flex;
		align-items: center;
	}

	.user-bar .panel-item i {
		font-size: 16px;
	}

	.user-bar .panel-item:hover {
		background: rgba(0, 0, 0, 0.1);
	}

	.user-bar .user-avatar {
		height: 49px;
		display: flex;
		align-items: center;
	}

	.user-bar .user-avatar label {
		display: inline-block;
		margin-left: 5px;
		font-size: 12px;
		cursor: pointer;
	}
</style>
