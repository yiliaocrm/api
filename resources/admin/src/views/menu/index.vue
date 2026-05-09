<template>
	<el-container>
		<el-header class="header-reset pd15">
			<el-card shadow="never">
				<el-space :size="10">
					<el-input
						v-model="grid.params.keyword"
						placeholder="请输入菜单名称"
						clearable
						style="width: 180px"
						@keyup.enter="refresh"
					/>
					<el-button type="primary" icon="el-icon-search" @click="refresh">查询</el-button>
					<el-button @click="handleToggle">{{ expand ? '折叠全部' : '展开全部' }}</el-button>
				</el-space>
			</el-card>
		</el-header>
		<el-main class="pt0">
			<el-container class="application-container pd15">
				<el-header class="header-reset mb15">
					<div class="space-between">
						<el-radio-group v-model="grid.params.type" @change="refresh">
							<el-radio-button value="web">电脑端菜单</el-radio-button>
							<el-radio-button value="app">移动端菜单</el-radio-button>
						</el-radio-group>
						<el-space :size="10">
							<el-button type="primary" @click="handleCreate()">添加菜单</el-button>
							<el-button type="success" @click="handleSync">同步菜单</el-button>
						</el-space>
					</div>
				</el-header>
				<el-main class="nopadding">
					<cy-table
						ref="tableRef"
						tableName="MenuIndex"
						:stripe="false"
						hidePagination
						:row-config="{ keyField: 'id' }"
						:tree-config="{ transform: true, rowField: 'id', parentField: 'parentid' }"
						:apiObj="grid.url"
						:params="grid.params"
						:columns="grid.columns"
						@cellDblclick="cellDblclick"
					>
					</cy-table>
				</el-main>
			</el-container>
		</el-main>
	</el-container>
	<menu-form ref="formRef" @create="onCreate" @update="refresh"></menu-form>
</template>

<script lang="jsx" setup>
	import Api from '@/api/index.js'
	import MenuForm from './form.vue'
	import { cloneDeep } from 'lodash-es'
	import { ElMessage } from 'element-plus'
	import { ref, resolveComponent, h } from 'vue'
	import { confirmMessager, useMsgbox } from '@/utils/helper.js'

	defineOptions({
		name: 'MenuIndex'
	})

	const expand = ref(false)
	const formRef = ref(null)
	const tableRef = ref(null)
	const msgbox = useMsgbox()
	let oldExpandRow = []

	const grid = ref({
		url: Api.menu.index,
		params: {
			type: 'web',
			keyword: null
		},
		columns: [
			{
				field: 'title',
				title: '菜单名称',
				minWidth: 150,
				treeNode: true,
				slots: {
					default: ({ row }) => {
						// 检查是否有 icon 值
						const iconEl =
							row.meta && row.meta.icon
								? h(
										resolveComponent('el-icon'),
										{ size: 18, style: { marginRight: '5px', verticalAlign: 'middle' } },
										() => h(resolveComponent(row.meta.icon))
									)
								: null
						const textEl = h('span', { style: { verticalAlign: 'middle' } }, row.title)
						return [iconEl, textEl]
					}
				}
			},
			{
				field: 'path',
				title: '访问路径',
				width: 200,
				showOverflow: 'tooltip'
			},
			{
				field: 'permission',
				title: '权限标识',
				width: 200,
				showOverflow: 'tooltip'
			},
			{
				field: 'remark',
				title: '菜单备注',
				width: 210,
				showOverflow: 'tooltip'
			},
			{
				field: 'menu_type',
				title: '菜单类型',
				width: 90,
				slots: {
					default: ({ row }) => {
						const names = {
							directory: '目录',
							menu: '菜单',
							button: '按钮'
						}
						const colors = {
							directory: 'blue',
							menu: 'green',
							button: 'cyan'
						}
						const name = names[row.meta.type] || '未知'
						const color = colors[row.meta.type] || 'gray'
						return <cy-badge color={color} text={name}></cy-badge>
					}
				}
			},
			{
				field: 'order',
				title: '排序',
				width: 60
			},
			{
				title: '操作',
				fixed: 'right',
				width: 190,
				slots: {
					default: ({ row }) => {
						return (
							<el-space spacer="|">
								<el-button link type="primary" size="small" onClick={() => handleCreate(row)}>
									添加子菜单
								</el-button>
								<el-button link type="primary" size="small" onClick={() => handleEdit(row)}>
									编辑
								</el-button>
								<el-button link type="danger" size="small" onClick={() => handleRemove(row)}>
									删除
								</el-button>
							</el-space>
						)
					}
				}
			}
		]
	})

	const refresh = async () => {
		await tableRef.value.refresh()
		if (oldExpandRow.length > 0) {
			tableRef.value.gridRef.setTreeExpand(oldExpandRow, true)
		}
	}

	const handleCreate = (row) => {
		oldExpandRow = cloneDeep(tableRef.value.gridRef.getTreeExpandRecords())
		let id = row ? row.id : null
		formRef.value.add(id, grid.value.params.type)
	}

	const handleEdit = (row) => {
		oldExpandRow = cloneDeep(tableRef.value.gridRef.getTreeExpandRecords())
		formRef.value.edit(row.id, grid.value.params.type)
	}

	const handleRemove = (row) => {
		confirmMessager({
			message: `您确定要删除[${row.title}]菜单吗？`,
			callback: async (action) => {
				if (action === 'confirm') {
					msgbox.loading('正在删除...')
					let { data, code } = await Api.menu.remove.get(row.id)
					if (data && code === 200) {
						tableRef.value.gridRef.remove(row)
					}
					msgbox.close()
				}
			}
		})
	}

	const handleToggle = () => {
		expand.value
			? tableRef.value.gridRef.clearTreeExpand()
			: tableRef.value.gridRef.setAllTreeExpand(true)
		expand.value = !expand.value
	}

	const handleSync = () => {
		confirmMessager({
			message: '您确定要同步所有租户的菜单吗？',
			callback: async (action) => {
				if (action === 'confirm') {
					msgbox.loading('正在同步...')
					let { data, code } = await Api.menu.sync.get()
					if (data && code === 200) {
						ElMessage({
							type: 'success',
							message: '新增同步任务成功!'
						})
					}
					msgbox.close()
				}
			}
		})
	}

	const cellDblclick = ({ row }) => {
		handleEdit(row)
	}

	const onCreate = async (row) => {
		await tableRef.value.gridRef.insertAt(row, -1)
		let parent = tableRef.value.gridRef.getRowById(row.parentid)
		if (parent) {
			tableRef.value.gridRef.setTreeExpand(parent, true)
		}
	}
</script>
