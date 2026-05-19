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
					<el-button type="success" icon="el-icon-plus" @click="handleCreate()">新增菜单</el-button>
				</el-space>
			</el-card>
		</el-header>
		<el-main class="pt0">
			<el-container class="application-container pd15">
				<el-main class="nopadding">
					<cy-table
						ref="tableRef"
						tableName="AdminMenuIndex"
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
	import { useMsgbox } from '@/utils/helper.js'
	import { ElMessageBox } from 'element-plus'
	import { reactive, resolveComponent, h, ref, toRefs } from 'vue'

	defineOptions({
		name: 'AdminMenuIndex'
	})

	const expand = ref(false)
	const formRef = ref(null)
	const tableRef = ref(null)
	const msgbox = useMsgbox()
	let oldExpandRow = []

	const grid = ref({
		url: Api.admin_menu.index,
		params: {
			keyword: null
		},
		columns: [
			{
				field: 'title',
				title: '菜单名称',
				treeNode: true
			},
			{
				field: 'meta.icon',
				title: '图标',
				width: 60,
				align: 'center',
				slots: {
					default: ({ row }) => {
						// 检查是否有 icon 值
						if (row.meta && row.meta.icon) {
							// 解析 icon 组件
							const IconComponent = resolveComponent(row.meta.icon)
							// 解析外层的 el-icon 组件
							const ElIcon = resolveComponent('el-icon')
							// 使用 h 函数渲染外层的 el-icon 组件，并将 IconComponent 作为子组件
							return h(ElIcon, { size: 18 }, () => h(IconComponent))
						}
						// 如果没有 icon 值，返回 null 或者占位符
						return null
					}
				}
			},
			{
				field: 'name',
				title: '组件名称',
				width: 200
			},
			{
				field: 'path',
				title: '访问路径',
				width: 250
			},
			{
				field: 'order',
				title: '排序',
				width: 80,
				align: 'center'
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
		formRef.value.add(id)
	}

	const handleEdit = (row) => {
		oldExpandRow = cloneDeep(tableRef.value.gridRef.getTreeExpandRecords())
		formRef.value.edit(row)
	}

	const handleRemove = (row) => {
		ElMessageBox.confirm(`您确定要删除[${row.title}]菜单吗？`, '提示', {
			type: 'warning',
			confirmButtonText: '确定',
			cancelButtonText: '取消',
			callback: async (action) => {
				if (action === 'confirm') {
					msgbox.loading('正在删除...')
					let { data, code } = await Api.admin_menu.remove.get(row.id)
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
