<template>
	<el-container>
		<el-header class="header-reset pd15">
			<el-card shadow="never">
				<el-space :size="10">
					<el-input
						v-model="grid.params.keyword"
						placeholder="请输入关键词搜索"
						clearable
						style="width: 200px"
						@keyup.enter="refresh"
					>
					</el-input>
					<el-button type="primary" icon="el-icon-search" @click="refresh">搜索</el-button>
					<el-button type="primary" icon="el-icon-plus" @click="handleAdd">新增</el-button>
				</el-space>
			</el-card>
		</el-header>
		<el-main class="application-container pb0 ml15 mr15 mb15">
			<cy-table
				ref="tableRef"
				tableName="TenantLoginBannerIndex"
				:apiObj="grid.url"
				:params="grid.params"
				:columns="grid.columns"
				:remoteSort="true"
			>
			</cy-table>
		</el-main>
	</el-container>
	<banner-form ref="formRef" @success="refresh"></banner-form>
</template>

<script lang="jsx" setup>
	import Api from '@/api/index.js'
	import BannerForm from './form.vue'
	import { ref, reactive } from 'vue'
	import { confirmMessager, useMsgbox } from '@/utils/helper.js'
	import { ElMessageBox } from 'element-plus'

	defineOptions({
		name: 'TenantLoginBannerIndex'
	})

	const msgbox = useMsgbox()
	const formRef = ref(null)
	const tableRef = ref(null)

	const grid = reactive({
		url: Api.tenant_login_banner.index,
		params: {
			keyword: null
		},
		columns: [
			{
				field: 'id',
				title: 'ID',
				width: 80
			},
			{
				field: 'title',
				title: 'Banner标题',
				minWidth: 200,
				showOverflow: 'tooltip'
			},
			{
				field: 'image_path',
				title: '图片预览',
				width: 120,
				slots: {
					default: ({ row }) => {
						return (
							<el-image
								src={row.image_path}
								fit="cover"
								style="width: 100px; height: 50px; border-radius: 4px;"
								preview-src-list={[row.image_path]}
								preview-teleported
								hide-on-click-modal
							/>
						)
					}
				}
			},
			{
				field: 'link_url',
				title: '跳转链接',
				width: 200,
				showOverflow: 'tooltip',
				slots: {
					default: ({ row }) => {
						return row.link_url ? (
							<el-link href={row.link_url} target="_blank" type="primary">
								{row.link_url}
							</el-link>
						) : (
							<span>-</span>
						)
					}
				}
			},
			{
				field: 'order',
				title: '排序',
				width: 80,
				sortable: true
			},
			{
				field: 'disabled',
				title: '状态',
				width: 100,
				slots: {
					default: ({ row }) => {
						return (
							<el-switch
								v-model={row.disabled}
								inline-prompt
								active-text="启用"
								inactive-text="禁用"
								active-value={false}
								inactive-value={true}
								onChange={() => handleToggle(row)}
							></el-switch>
						)
					}
				}
			},
			{
				field: 'created_at',
				title: '创建时间',
				width: 145,
				sortable: true
			},
			{
				field: 'updated_at',
				title: '更新时间',
				width: 145,
				sortable: true
			},
			{
				title: '操作',
				fixed: 'right',
				width: 120,
				slots: {
					default: ({ row }) => (
						<el-space spacer="|">
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
		]
	})

	const handleToggle = (row) => {
		const title = row.disabled ? `确定要禁用[${row.title}]吗？` : `确定要启用[${row.title}]吗？`
		const defaultValue = row.disabled

		ElMessageBox.confirm(title, '提示', {
			confirmButtonText: '确定',
			cancelButtonText: '取消',
			type: 'warning'
		})
			.then(async () => {
				msgbox.loading()
				const { data, code } = await Api.tenant_login_banner.toggle.get(row.id)
				if (data && code === 200) {
					refresh()
				} else {
					row.disabled = defaultValue
				}
				msgbox.close()
			})
			.catch(() => {
				row.disabled = defaultValue
			})
	}

	const handleRemove = (row) => {
		confirmMessager({
			message: `您确定要删除[${row.title}]吗？`,
			callback: async (action) => {
				if (action === 'confirm') {
					msgbox.loading('正在删除...')
					let { data, code } = await Api.tenant_login_banner.remove.get(row.id)
					if (data && code === 200) {
						refresh()
					}
					msgbox.close()
				}
			}
		})
	}

	const handleAdd = () => {
		formRef.value.add()
	}

	const handleEdit = (row) => {
		formRef.value.edit(row)
	}

	const refresh = () => {
		tableRef.value.refresh()
	}
</script>
