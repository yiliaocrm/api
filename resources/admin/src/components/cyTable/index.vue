<template>
	<div :style="wrapStyles">
		<!-- 表格主体 -->
		<div :style="{ height: tableHeight, 'background-color': '#fff' }">
			<vxe-grid
				ref="gridRef"
				height="100%"
				v-bind="attrs"
				:data="data"
				:size="size"
				:stripe="stripe"
				:border="border"
				:columns="columns"
				:loading="loading"
				:rowConfig="rowConfig"
				:footer-method="remoteFooter ? remoteFooterMethod : footerMethod"
				:sort-config="sortConfig"
				:radio-config="radioConfig"
				@sort-change="sortChange"
				@radio-change="radioChange"
				@checkbox-all="checkboxAll"
				@checkbox-change="checkboxChange"
				@cell-dblclick="cellDblclick"
			>
				<template #empty>
					<el-empty :description="emptyText" :image-size="40"></el-empty>
				</template>
				<template v-for="item in Object.keys($slots)" :key="item" #[item]="params">
					<slot :name="item" v-bind="params"></slot>
				</template>
			</vxe-grid>
		</div>
		<!-- 分页 -->
		<div class="cytable-page" v-if="!hidePagination">
			<div>
				<el-checkbox
					v-if="selectAllPage"
					v-model="selectAllPageChecked"
					@change="handleSelectAllChange"
				>
					<el-text
						>选择全部<span style="color: red">{{ total }}</span
						>条数据</el-text
					>
				</el-checkbox>
			</div>
			<el-space :size="0">
				<el-pagination
					background
					size="small"
					:layout="paginationLayout"
					:total="total"
					:page-size="pageSize"
					:page-sizes="pageSizes"
					v-model:currentPage="currentPage"
					@current-change="paginationChange"
					@update:page-size="pageSizeChange"
				>
				</el-pagination>
				<el-button text icon="el-icon-refresh" @click="refresh">刷新</el-button>
			</el-space>
		</div>
	</div>
	<setting ref="settingRef" :page="tableName" :columns="columns" @change="settingChange"></setting>
</template>

<script lang="jsx">
	import store from '@/store/index.js'
	import config from '@/config/vxeTable.js'
	import Setting from './setting.vue'
	import { cloneDeep, isEmpty } from 'lodash-es'
	import {
		computed,
		defineComponent,
		onMounted,
		reactive,
		toRefs,
		watch,
		ref,
		useAttrs,
		nextTick
	} from 'vue'

	export default defineComponent({
		name: 'cyTableIndex',
		emits: [
			'dataChange',
			'update:radioValue',
			'selectAllPage',
			'unSelectAllPage',
			'selectAll',
			'unSelectAll',
			'cellDblclick',
			'filterClick'
		],
		components: {
			Setting
		},
		props: {
			/**
			 * 表格唯一标识
			 * 例如CustomerIndex
			 */
			tableName: {
				type: String,
				required: true
			},
			/**
			 * 远程数据API对象
			 */
			apiObj: {
				type: Object,
				default: () => {}
			},
			/**
			 * 远程数据默认请求参数
			 */
			params: {
				type: Object,
				default: () => ({})
			},
			/**
			 * 表格尺寸
			 */
			size: {
				type: String,
				default: config.size
			},
			/**
			 * 表格静态数据
			 */
			data: {
				type: Array
			},
			height: {
				type: [String, Number],
				default: '100%'
			},
			/**
			 * 是否显示边框
			 */
			border: {
				type: Boolean,
				default: config.border
			},
			/**
			 * 是否显示斑马纹
			 */
			stripe: {
				type: Boolean,
				default: true
			},
			pageSize: {
				type: Number,
				default: config.pageSize
			},
			pageSizes: {
				type: Array,
				default: config.pageSizes
			},
			rowKey: {
				type: String,
				default: ''
			},
			/**
			 * 手动设置表尾数据
			 */
			footerMethod: {
				type: Function,
				default: null
			},
			/**
			 * 可自定义的列
			 */
			columns: {
				type: Object,
				default: () => {}
			},
			/**
			 * 是否开启远程排序
			 */
			remoteSort: {
				type: Boolean,
				default: false
			},
			/**
			 * 是否开启远程过滤
			 */
			remoteFilter: {
				type: Boolean,
				default: false
			},
			/**
			 * 是否开启远程表尾,需要设置showFooter参数
			 */
			remoteFooter: {
				type: Boolean,
				default: false
			},
			/**
			 * 是否隐藏分页
			 */
			hidePagination: {
				type: Boolean,
				default: false
			},
			hideRefresh: {
				type: Boolean,
				default: false
			},
			/**
			 * 是否隐藏自定义列设置按钮
			 */
			hideSetting: {
				type: Boolean,
				default: false
			},
			paginationLayout: {
				type: String,
				default: config.paginationLayout
			},
			radioValue: Object,
			radioConfig: Object,
			/**
			 * 开启选中本页
			 */
			selectAll: {
				type: Boolean,
				default: false
			},
			/**
			 * 开启选中全部页面
			 */
			selectAllPage: {
				type: Boolean,
				default: false
			},
			filteredData: {
				type: Array,
				default: () => []
			}
		},
		setup(props, context) {
			const state = reactive({
				size: props.size,
				data: props.data === undefined ? [] : props.data,
				total: props.data === undefined ? 0 : props.data.length,
				stripe: props.stripe,
				params: props.params,
				sort: null,
				order: null,
				columns: [],
				loading: false,
				footer: [],
				currentPage: 1,
				pageSize: props.pageSize,
				pageSizes: props.pageSizes,
				pageNumber: 1,
				emptyText: '暂无数据',
				selectAllChecked: false, // 选中本页状态
				selectAllPageChecked: false // 选中全部页面状态
			})

			// 自定义字段
			const custom_columns = [
				{
					type: 'seq',
					width: 60,
					align: 'center',
					fixed: 'left',
					slots: {
						header: () => {
							return props.hideSetting ? null : (
								<div class="cytable-seq">
									<el-icon
										title="自定义列"
										size={16}
										onClick={handleCustomeField}
										style="cursor: pointer;"
									>
										<el-icon-setting />
									</el-icon>
								</div>
							)
						}
					},
					showOverflow: 'tooltip'
				}
			]

			if (props.selectAll) {
				custom_columns.push({
					type: 'checkbox',
					width: 50,
					align: 'center',
					fixed: 'left'
				})
			}

			const attrs = useAttrs()

			const wrapStyles = computed(() => {
				return {
					height: Number(props.height) ? Number(props.height) + 'px' : props.height
				}
			})

			const rowConfig = computed(() => {
				return Object.assign({}, attrs['row-config'] || {}, {
					isCurrent: true,
					isHover: true
				})
			})

			const radioConfig = computed(() => {
				return props.radioConfig
					? props.radioConfig
					: {
							trigger: 'row'
						}
			})

			const sortConfig = computed(() => {
				return {
					remote: props.remoteSort
				}
			})

			const tableHeight = computed(() => {
				return props.hidePagination ? '100%' : 'calc(100% - 50px)'
			})

			watch(() => props.data, staticDataPagination, { immediate: true })

			watch(
				() => props.radioValue,
				(val) => {
					if (val === undefined) {
						gridRef.value.clearRadioRow()
					}
				}
			)

			watch(
				() => props.apiObj,
				() => {
					state.params = props.params
					refresh()
				}
			)

			watch(
				() => props.params,
				(params) => {
					state.params = params
				}
			)

			const gridRef = ref(null)
			const settingRef = ref(null)

			onMounted(async () => {
				if (props.columns) {
					await getCustomColumn()
				}

				// 判断是否静态数据
				if (props.apiObj) {
					await loadData()
				}
			})

			/**
			 * 静态数据分页计算
			 */
			function staticDataPagination() {
				if (isEmpty(props.data)) {
					state.total = 0
					state.data = []
					return
				}

				if (isEmpty(props.apiObj) === false) {
					return
				}

				// 当hidePagination为true时，显示所有数据
				if (props.hidePagination) {
					state.data = cloneDeep(props.data)
					state.total = props.data.length
				} else {
					const newData = cloneDeep(props.data)
					state.data = newData.splice((state.currentPage - 1) * state.pageSize, state.pageSize)
					state.total = props.data ? props.data.length : 0
				}
			}

			const columnsFilterableProcessing = () => {
				state.columns = state.columns.map((col) => {
					if (col.filterable) {
						col.slots = col.slots || {}
						if (!col.slots.header) {
							col.slots.header = () => {
								const field = col.filterField || col.field
								const isFiltered = props.filteredData.some((item) => {
									return item.field == field
								})
								return (
									<div class="inline-flex items-center">
										{col.title}
										<el-icon
											title="筛选"
											size={12}
											class={[
												'mx-1 hover:text-[#409eff] cursor-pointer',
												{ '!text-[#409eff]': isFiltered }
											]}
											onClick={() => filterClickHandler(col)}
										>
											<el-icon-filter />
										</el-icon>
									</div>
								)
							}
						}
					}
					return col
				})
			}

			const filterClickHandler = (params) => {
				context.emit('filterClick', params)
			}

			/**
			 * 加载自定义字段配置
			 */
			const getCustomColumn = () => {
				let caches = store.getters['cache/fields'] || []
				let cache = caches.find((v) => v.page == props.tableName)
				let columns = props.columns

				if (cache) {
					columns = [...mergeColumns(props.columns, cache.config)]
				}

				state.columns = [...custom_columns, ...columns]

				columnsFilterableProcessing()
			}

			/**
			 * 合并自定义字段和原始字段
			 * @param {*} originalColumns
			 * @param {*} newColumns
			 */
			const mergeColumns = (originalColumns, newColumns) => {
				// 先从originalColumns中取出那些没有field属性的列
				const noFieldColumns = originalColumns.filter((col) => !col.field)

				// 接着，我们用newColumns来覆盖或添加具有field属性的列
				const fieldColumns = newColumns.map((newCol) => {
					let originalCol = originalColumns.find((oriCol) => oriCol.field === newCol.field)

					// 使用新列的配置来覆盖原始列的配置
					return {
						...originalCol,
						...newCol
					}
				})

				return [...fieldColumns, ...noFieldColumns]
			}

			/**
			 * 保存自定义字段后的回调
			 * @param {*} columns
			 */
			const settingChange = (columns) => {
				state.columns = [...custom_columns, ...mergeColumns(props.columns, columns)]
				columnsFilterableProcessing()
			}

			const refresh = async () => {
				await nextTick()
				await loadData()
			}

			const loadData = async () => {
				if (!props.apiObj) {
					return null
				}

				state.loading = true

				let reqData = {
					[config.request.page]: state.currentPage,
					[config.request.pageSize]: state.pageSize
				}

				// 远程排序
				if (props.remoteSort && state.sort && state.order) {
					reqData[config.request.sort] = state.sort
					reqData[config.request.order] = state.order
				}

				// 隐藏分页时不传分页参数
				if (props.hidePagination) {
					delete reqData[config.request.page]
					delete reqData[config.request.pageSize]
				}

				Object.assign(reqData, state.params)

				try {
					var res = await props.apiObj.get(reqData)
				} catch (error) {
					state.loading = false
					state.emptyText = error.statusText
					console.error(error)
					return false
				}

				try {
					var response = config.parseData(res)
				} catch (error) {
					state.loading = false
					state.emptyText = '数据格式错误'
					console.error(error)
					return false
				}

				if (response.code != config.successCode) {
					state.loading = false
					state.emptyText = response.msg
				} else {
					state.emptyText = '暂无数据'
					if (props.hidePagination) {
						state.data = response.data?.rows || response.rows || response?.data || []
					} else {
						state.data = response.rows || []
					}
					state.total = response.total || 0
					state.footer = response.data?.footer || response.footer || []
					state.loading = false
				}

				// 选择所有页面数据
				if (state.selectAllPageChecked) {
					nextTick(() => {
						gridRef.value.setAllCheckboxRow(true)
					})
				}

				// this.$refs.scTable.setScrollTop(0)
				context.emit('dataChange', response.data)
			}

			const handleCustomeField = () => {
				settingRef.value.open(props.columns)
			}

			const paginationChange = () => {
				if (props.apiObj) {
					loadData()
				} else {
					staticDataPagination()
				}
			}

			const pageSizeChange = (size) => {
				state.pageSize = size
				if (props.apiObj) {
					loadData()
				} else {
					staticDataPagination()
				}
			}

			const sortChange = ({ order, field }) => {
				if (!props.remoteSort) {
					return null
				}
				state.sort = field
				state.order = order
				loadData()
			}

			const radioChange = () => {
				const row = gridRef.value.getRadioRecord()
				context.emit('update:radioValue', row)
			}

			const clearData = () => {
				state.data = []
				state.total = 0
			}

			/**
			 * 设置当前行
			 * @param {*} row
			 */
			const setCurrentRow = (row) => {
				gridRef.value.setCurrentRow(row)
			}

			/**
			 * 处理后端返回表尾数据
			 * @param {*} param0
			 */
			const remoteFooterMethod = ({ columns }) => {
				return state.footer.map((totalRow) => {
					return columns.map((col) => {
						if (col.field) {
							return totalRow[col.field]
						}
						return ''
					})
				})
			}

			/**
			 * 选择全部/取消选择全部
			 */
			const handleSelectAllChange = (checked) => {
				state.selectAllChecked = false
				state.selectAllPageChecked = checked
				if (checked) {
					gridRef.value.setAllCheckboxRow(true)
				} else {
					gridRef.value.clearCheckboxRow()
				}
				context.emit(checked ? 'selectAllPage' : 'unSelectAllPage')
			}

			/**
			 * 全选/取消全选事件
			 */
			const checkboxAll = ({ checked }) => {
				state.selectAllChecked = checked
				state.selectAllPageChecked = false
				context.emit(checked ? 'selectAll' : 'unSelectAll')
			}

			/**
			 * 勾选/取消勾选事件
			 */
			const checkboxChange = () => {
				state.selectAllChecked = false
				state.selectAllPageChecked = false
				context.emit(props.selectAllPage ? 'unSelectAllPage' : 'unSelectAll')
			}

			const cellDblclick = (data) => {
				context.emit('cellDblclick', data)
			}

			const getCheckboxRecords = () => {
				return gridRef.value.getCheckboxRecords()
			}

			return {
				...toRefs(state),
				gridRef,
				settingRef,
				attrs,
				rowConfig,
				radioConfig,
				sortConfig,
				wrapStyles,
				tableHeight,
				tableSlots: context.slots,
				refresh,
				clearData,
				sortChange,
				radioChange,
				setCurrentRow,
				settingChange,
				pageSizeChange,
				paginationChange,
				remoteFooterMethod,
				reloadColumn: getCustomColumn,
				checkboxAll,
				checkboxChange,
				cellDblclick,
				handleSelectAllChange,
				getCheckboxRecords
			}
		}
	})
</script>

<style>
	.cytable-seq {
		display: flex;
		justify-content: center;
		align-items: center;
	}

	.cytable-page {
		height: 50px;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
</style>
