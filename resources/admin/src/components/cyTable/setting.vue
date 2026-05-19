<template>
	<cy-dialog v-model="modal" title="表头设置" icon="el-icon-setting" :width="700" append-to-body>
		<div class="mb15">
			<vxe-grid
				ref="gridRef"
				:border="true"
				:height="400"
				:data="grid.data"
				:columns="grid.columns"
				:row-config="{ icyurrent: true, isHover: true, drag: true }"
				size="mini"
				@row-dragend="handleRowDropend"
			></vxe-grid>
		</div>
		<template #footer>
			<el-button type="primary" @click="handleSubmit">保存</el-button>
			<el-button @click="handleReset">重置</el-button>
			<el-button @click="modal = false">关闭</el-button>
		</template>
	</cy-dialog>
</template>

<script lang="jsx">
	import { cloneDeep } from 'lodash-es'
	import Api from '@/api/index.js'
	import store from '@/store'
	import { useMsgbox, confirmMessager } from '@/utils/helper'
	import { defineComponent, reactive, toRefs, ref } from 'vue'

	export default defineComponent({
		name: 'CyTableSetting',
		emits: ['change'],
		props: {
			/**
			 * 页面标识符
			 * 例如CustomerIndex
			 */
			page: {
				type: String,
				required: true
			},
			/**
			 * 字段配置
			 */
			columns: {
				type: Object,
				default: () => {}
			}
		},
		setup(props, context) {
			const state = reactive({
				grid: {
					data: [],
					columns: [
						{
							type: 'seq',
							width: 50,
							title: '序号',
							align: 'center'
						},
						{
							field: 'title',
							title: '标题'
						},
						{
							field: 'width',
							title: '宽度',
							width: 80,
							align: 'center',
							slots: {
								default: ({ row }) => {
									return <el-input v-model={row.width} size="small"></el-input>
								}
							}
						},
						{
							field: 'visible',
							title: '显示',
							width: 50,
							align: 'center',
							slots: {
								default: ({ row }) => {
									return <el-checkbox v-model={row.visible}></el-checkbox>
								}
							}
						},
						{
							title: '排序',
							width: 50,
							align: 'center',
							dragSort: true,
							slots: {
								rowDragIcon: () => {
									return (
										<el-icon size={18} class="move" style="cursor:pointer;">
											<sc-icon-move-sharp />
										</el-icon>
									)
								}
							}
						},
						{
							field: 'align',
							title: '对齐',
							align: 'center',
							width: 100,
							slots: {
								default: ({ row }) => {
									return (
										<el-space>
											<el-icon
												size={18}
												title="左对齐"
												color={row.align === 'left' ? '#409EFF' : '#606266'}
												onClick={() => setAlignValue(row, 'left')}
												style="cursor:pointer;"
											>
												<sc-icon-left-alignment />
											</el-icon>
											<el-icon
												size={18}
												title="居中"
												color={row.align === 'center' ? '#409EFF' : '#606266'}
												onClick={() => setAlignValue(row, 'center')}
												style="cursor:pointer;"
											>
												<sc-icon-center-alignment />
											</el-icon>
											<el-icon
												size={18}
												title="右对齐"
												color={row.align === 'right' ? '#409EFF' : '#606266'}
												onClick={() => setAlignValue(row, 'right')}
												style="cursor:pointer;"
											>
												<sc-icon-right-alignment />
											</el-icon>
										</el-space>
									)
								}
							}
						},
						{
							field: 'fixed',
							title: '冻结',
							width: 80,
							align: 'center',
							slots: {
								default: ({ row }) => {
									if (row.fixed === 'left') {
										return (
											<el-space>
												<el-icon
													size={18}
													title="取消固定"
													onClick={() => setFixedValue(row, null)}
													style="cursor:pointer;"
												>
													<sc-icon-fixed-left-fill />
												</el-icon>
												<el-icon
													size={18}
													title="固定在右侧"
													onClick={() => setFixedValue(row, 'right')}
													style="cursor:pointer;"
												>
													<sc-icon-fixed-right />
												</el-icon>
											</el-space>
										)
									} else if (row.fixed === 'right') {
										return (
											<el-space>
												<el-icon
													size={18}
													title="固定在左侧"
													onClick={() => setFixedValue(row, 'left')}
													style="cursor:pointer;"
												>
													<sc-icon-fixed-left />
												</el-icon>
												<el-icon
													size={18}
													title="取消固定"
													onClick={() => setFixedValue(row, null)}
													style="cursor:pointer;"
												>
													<sc-icon-fixed-right-fill />
												</el-icon>
											</el-space>
										)
									} else {
										return (
											<el-space>
												<el-icon
													size={18}
													title="固定在左侧"
													onClick={() => setFixedValue(row, 'left')}
													style="cursor:pointer;"
												>
													<sc-icon-fixed-left />
												</el-icon>
												<el-icon
													size={18}
													title="固定在右侧"
													onClick={() => setFixedValue(row, 'right')}
													style="cursor:pointer;"
												>
													<sc-icon-fixed-right />
												</el-icon>
											</el-space>
										)
									}
								}
							}
						}
					]
				},
				modal: false,
				fields: ['field', 'title', 'width', 'minWidth', 'visible', 'align', 'fixed'],
				originalColumns: []
			})

			const msgbox = useMsgbox()
			const gridRef = ref(null)

			const open = (originalColumns) => {
				state.modal = true
				state.grid.data = filterFields(props.columns)
				state.originalColumns = originalColumns
			}

			const setFixedValue = (row, fixed) => {
				row.fixed = fixed
			}

			const setAlignValue = (row, align) => {
				row.align = align
			}

			const handleSubmit = async () => {
				let config = filterFields(state.grid.data)

				msgbox.loading('配置保存中')
				let { data, code } = await Api.field.save.post({
					page: props.page,
					config: config
				})
				if (data && code === 200) {
					context.emit('change', config)
					store.dispatch('cache/updateOrCreate', { page: props.page, data: config }, { root: true })
					state.modal = false
				}
				msgbox.close()
			}

			const handleReset = () => {
				confirmMessager({
					type: 'warning',
					message: '确定要重置吗？',
					callback: async (action) => {
						if (action === 'confirm') {
							msgbox.loading('配置重置中')
							let { data, code } = await Api.field.reset.get({
								page: props.page
							})
							if (data && code === 200) {
								state.grid.data = filterFields(state.originalColumns)
								context.emit('change', state.grid.data)
								store.dispatch(
									'cache/updateOrCreate',
									{ page: props.page, data: state.grid.data },
									{ root: true }
								)
								state.modal = false
							}
							msgbox.close()
						}
					}
				})
			}

			/**
			 * 过滤字段
			 * @param {*} columns
			 */
			const filterFields = (columns) => {
				// 默认值
				let defaultValues = {
					width: 0,
					align: 'left',
					fixed: null,
					visible: true
				}
				return columns
					.filter((item) => !['seq', 'checkbox'].includes(item.type))
					.filter((item) => item.field)
					.map((item) => {
						let obj = {}
						state.fields.forEach((field) => {
							obj[field] = item[field] !== undefined ? item[field] : defaultValues[field]
						})
						return obj
					})
			}

			const handleRowDropend = (param) => {
				const { newRow, oldRow, dragPos } = param // dragPos: 'top' | 'bottom'
				const list = cloneDeep(state.grid.data)

				const oldIdx = list.findIndex((r) => r.field === oldRow.field)
				let newIdx = list.findIndex((r) => r.field === newRow.field)
				if (dragPos === 'bottom') newIdx++
				list.splice(oldIdx, 1)
				list.splice(newIdx, 0, oldRow)

				state.grid.data = list
			}

			return {
				...toRefs(state),
				gridRef,
				open,
				handleReset,
				handleSubmit,
				handleRowDropend
			}
		}
	})
</script>
