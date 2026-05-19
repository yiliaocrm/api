<script lang="js" setup>
	import { ref, onMounted, nextTick } from 'vue'
	import { useRouter } from 'vue-router'
	import { Delete, CirclePlus } from '@element-plus/icons-vue'
	import { debounce } from 'lodash-es'

	const emit = defineEmits(['success'])
	const router = useRouter()
	const inputRef = ref()
	const keyword = ref('')
	const searchResult = ref([])
	const menus = ref([])
	const history = ref([])

	const debounceKeywordInput = debounce(handleSearch, 500)

	function handleKeywordInput() {
		if (keyword.value) {
			debounceKeywordInput()
		} else {
			searchResult.value = []
		}
	}

	function handleSearch() {
		searchResult.value = menuFilter(keyword.value)
	}

	function generateMenus(arr) {
		const result = []

		function generate(arr) {
			arr.forEach((item) => {
				if (item.meta.hidden || item.meta.type == 'button') {
					return false
				}
				if (item.meta.type == 'iframe') {
					item.path = `/i/${item.name}`
				}
				if (item.children && item.children.length > 0 && !item.component) {
					generate(item.children)
				} else {
					result.push(item)
				}
			})
		}

		generate(arr)

		return result
	}

	function menuFilter(queryString) {
		let res = []
		//过滤菜单树
		let filterMenu = menus.value.filter((item) => {
			if (item.keyword.toLowerCase().indexOf(queryString.toLowerCase()) >= 0) {
				return true
			}
			return false
		})
		//匹配系统路由
		let routes = router.getRoutes()
		let filterRouter = filterMenu.map((m) => {
			if (m.meta.type == 'link') {
				return routes.find((r) => r.path == '/' + m.path)
			} else {
				return routes.find((r) => r.path == m.path)
			}
		})
		//重组对象
		filterRouter.forEach((item) => {
			if (!item) return
			res.push({
				name: item.name,
				type: item.meta.type,
				path: item.meta.type == 'link' ? item.path.slice(1) : item.path,
				icon: item.meta.icon,
				title: item.meta.title,
				breadcrumb: item.meta.breadcrumb.map((v) => v.meta.title).join(' - ')
			})
		})
		return res
	}

	function handleOpenTab(item) {
		const historyHas = history.value.some((i) => i.name == item.name)
		if (historyHas === false) {
			history.value.push(item)
			localStorage.setItem('SEARCH_HISTORY', JSON.stringify(history.value))
		}
		if (item.type == 'link') {
			setTimeout(() => {
				let a = document.createElement('a')
				a.style = 'display: none'
				a.target = '_blank'
				a.href = item.path
				document.body.appendChild(a)
				a.click()
				document.body.removeChild(a)
			}, 10)
		} else {
			router.push({ path: item.path })
		}
		emit('success', true)
	}

	function handleClearHistory() {
		history.value = []
		localStorage.setItem('SEARCH_HISTORY', JSON.stringify(history.value))
	}

	function handleAddHistory(item) {
		history.value.push(item)
		localStorage.setItem('SEARCH_HISTORY', JSON.stringify(history.value))
	}

	function handleShowAddHistoryBtn(item) {
		return history.value.some((i) => i.name == item.name)
	}

	onMounted(() => {
		history.value = !localStorage.getItem('SEARCH_HISTORY')
			? []
			: JSON.parse(localStorage.getItem('SEARCH_HISTORY'))
		const m =
			sessionStorage.getItem('MENU') === null ? [] : JSON.parse(sessionStorage.getItem('MENU'))
		menus.value = generateMenus(m)
		nextTick(() => {
			inputRef.value.focus()
		})
	})
</script>

<template>
	<div>
		<div class="relative">
			<el-input
				class="custom-input"
				size="large"
				placeholder="输入菜单名称搜索"
				ref="inputRef"
				v-model="keyword"
				:clearable="true"
				@input="handleKeywordInput"
			>
				<template #prefix>
					<el-icon><el-icon-search /></el-icon>
				</template>
			</el-input>
			<div class="absolute top-[4px] right-[4px]">
				<el-button type="primary">
					<el-icon><el-icon-search /></el-icon>
				</el-button>
			</div>
		</div>
		<div class="flex mt-4 p-4 rounded-lg border border-solid border-gray-200">
			<div class="flex-1 overflow-auto h-[300px]">
				<div
					class="h-full w-full flex items-center justify-center"
					v-if="searchResult.length === 0"
				>
					<el-empty description="请输入菜单关键词搜索" :image-size="100" />
				</div>
				<div class="h-full w-full" v-else>
					<div
						class="pr-4"
						v-for="item in searchResult"
						:key="item.path"
						@click="handleOpenTab(item)"
					>
						<div
							class="w-full flex items-center p-2 rounded-lg hover:bg-gray-100 hover:cursor-pointer"
						>
							<div class="flex-none flex items-center pr-2">
								<el-icon>
									<component :is="item.icon || 'el-icon-menu'" />
								</el-icon>
							</div>
							<div class="flex-grow truncate">
								{{ item.breadcrumb }}
							</div>
							<div class="flex-none pl-2">
								<el-button
									type="primary"
									:class="handleShowAddHistoryBtn(item) ? 'opacity-0' : ''"
									:link="true"
									:icon="CirclePlus"
									:disabled="handleShowAddHistoryBtn(item)"
									@click.stop="handleAddHistory(item)"
								></el-button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="flex-1 overflow-auto h-[300px]">
				<div class="h-full w-full bg-gray-50 rounded-lg">
					<div class="h-full w-full flex flex-col">
						<div class="flex-none p-4">
							<div class="flex justify-between pb-2 border-b border-solid border-gray-200">
								<div class="border-l-4 border-solid border-blue-500 pl-4">最近访问</div>
								<div>
									<el-button type="primary" :link="true" :icon="Delete" @click="handleClearHistory"
										>清空</el-button
									>
								</div>
							</div>
						</div>
						<div class="flex-grow overflow-auto">
							<div
								class="h-full w-full flex items-center justify-center"
								v-if="history.length === 0"
							>
								<el-empty description="可将常用菜单收藏此处哦~" :image-size="100" />
							</div>
							<div class="h-full w-full px-4 pb-4 overflow-auto" v-else>
								<div
									class="w-full flex items-center p-2 rounded-lg hover:bg-gray-100 hover:cursor-pointer"
									v-for="item in history"
									:key="item.path"
									@click="handleOpenTab(item)"
								>
									<div class="flex-none flex items-center pr-2">
										<el-icon>
											<component :is="item.icon || 'el-icon-menu'" />
										</el-icon>
									</div>
									<div class="flex-grow truncate">
										{{ item.breadcrumb }}
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<style>
	.custom-input .el-input__wrapper {
		padding-right: 60px;
	}
</style>
