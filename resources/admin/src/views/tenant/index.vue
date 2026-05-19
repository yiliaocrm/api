<template>
	<el-container>
		<el-header class="header-reset pd15">
			<el-card shadow="never">
				<el-space :size="10">
					<el-text>到期日期:</el-text>
					<el-date-picker
						v-model="grid.params.expire_date"
						:shortcuts="shortcuts"
						type="daterange"
						value-format="YYYY-MM-DD"
						range-separator="至"
						start-placeholder="开始日期"
						end-placeholder="结束日期"
						style="width: 240px"
					>
					</el-date-picker>
					<el-text>创建日期:</el-text>
					<el-date-picker
						v-model="grid.params.created_at"
						:shortcuts="shortcuts"
						type="daterange"
						value-format="YYYY-MM-DD"
						range-separator="至"
						start-placeholder="开始日期"
						end-placeholder="结束日期"
						style="width: 240px"
					>
					</el-date-picker>
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
				tableName="TenantIndex"
				:apiObj="grid.url"
				:params="grid.params"
				:columns="grid.columns"
				:remoteSort="true"
			>
			</cy-table>
		</el-main>
	</el-container>
	<tenant-form ref="formRef" @success="refresh"></tenant-form>
</template>

<script lang="jsx" setup>
	import Api from '@/api/index.js'
	import dayjs from 'dayjs'
	import TenantForm from './form.vue'
	import { ElMessageBox } from 'element-plus'
	import { ref, reactive } from 'vue'
	import { confirmMessager, alertMessager, useMsgbox, copyToClipboard } from '@/utils/helper.js'

	defineOptions({
		name: 'TenantIndex'
	})

	const msgbox = useMsgbox()
	const formRef = ref(null)
	const tableRef = ref(null)

	let shortcuts = [
		{
			text: '今天',
			value() {
				return [dayjs().startOf('day').toDate(), dayjs().endOf('day').toDate()]
			}
		},
		{
			text: '本周',
			value() {
				return [dayjs().startOf('week').toDate(), dayjs().endOf('week').toDate()]
			}
		},
		{
			text: '本月',
			value() {
				return [dayjs().startOf('month').toDate(), dayjs().endOf('month').toDate()]
			}
		},
		{
			text: '今年',
			value() {
				return [dayjs().startOf('year').toDate(), dayjs().endOf('year').toDate()]
			}
		},
		{
			text: '明年',
			value() {
				return [dayjs().startOf('year').toDate(), dayjs().endOf('year').toDate()]
			}
		}
	]

	const grid = reactive({
		url: Api.tenant.index,
		params: {
			keyword: null,
			expire_date: [null, null],
			created_at: [null, null]
		},
		columns: [
			{
				field: 'status',
				title: '运行状态',
				width: 90,
				slots: {
					default: ({ row }) => {
						return row.status === 'run' ? (
							<cy-badge status="success" text="运行中" />
						) : (
							<cy-badge status="error" text="已停止" />
						)
					}
				},
				sortable: true
			},
			{
				field: 'id',
				title: '机构代码',
				width: 100
			},
			{
				field: 'version',
				title: '版本号',
				width: 160
			},
			{
				field: 'name',
				title: '机构名称',
				width: 200,
				showOverflow: 'tooltip'
			},
			{
				field: 'remark',
				title: '备注信息',
				minWidth: 200,
				showOverflow: 'tooltip'
			},
			{
				field: 'domians',
				title: '绑定域名',
				width: 300,
				slots: {
					default: ({ row }) => {
						return (
							<>
								{row.domains.map((domain) => (
									<el-tag key={domain.id}>
										<el-link href={`http://${domain.domain}`} target="_blank" type="primary">
											{domain.domain}
										</el-link>
									</el-tag>
								))}
							</>
						)
					}
				}
			},
			{
				field: 'expire_date',
				title: '到期时间',
				width: 90,
				sortable: true
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
				width: 165,
				slots: {
					default: ({ row }) => (
						<el-space spacer="|">
							{row.status === 'pause' && (
								<el-button link type="success" size="small" onClick={() => handleRun(row)}>
									恢复
								</el-button>
							)}
							{row.status === 'run' && (
								<el-button link type="primary" size="small" onClick={() => handlePause(row)}>
									暂停
								</el-button>
							)}
							<el-button link type="primary" size="small" onClick={() => handleEdit(row)}>
								编辑
							</el-button>
							<el-dropdown v-slots={dropdown(row)} trigger="click">
								<el-button link type="primary" size="small">
									<span>更多</span>
									<el-icon>
										<el-icon-arrow-down />
									</el-icon>
								</el-button>
							</el-dropdown>
						</el-space>
					)
				}
			}
		]
	})

	const handleRun = async (row) => {
		if (row.expire_date) {
			let expireDate = new Date(row.expire_date).getTime()
			let nowDate = new Date().getTime()
			if (expireDate < nowDate) {
				return alertMessager(`机构[${row.name}]已到期，无法启动！`)
			}
		}
		confirmMessager({
			message: `您确定要恢复[${row.name}]吗？`,
			callback: async (action) => {
				if (action === 'confirm') {
					msgbox.loading('正在恢复...')
					let { data, code } = await Api.tenant.run.get(row.id)
					if (data && code === 200) {
						refresh()
					}
					msgbox.close()
				}
			}
		})
	}

	const handlePause = (row) => {
		confirmMessager({
			message: `您确定要暂停[${row.name}]吗？`,
			callback: async (action) => {
				if (action === 'confirm') {
					msgbox.loading('正在暂停...')
					let { data, code } = await Api.tenant.pause.get(row.id)
					if (data && code === 200) {
						refresh()
					}
					msgbox.close()
				}
			}
		})
	}

	const handleRemove = (row) => {
		confirmMessager({
			message: `您确定要删除[${row.name}]吗？`,
			callback: async (action) => {
				if (action === 'confirm') {
					msgbox.loading('正在删除...')
					let { data, code } = await Api.tenant.remove.get(row.id)
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

	const handleLogin = (row) => {
		ElMessageBox.confirm(`确定要为[${row.name}]生成一键登录链接吗?`, '提示', {
			confirmButtonText: '确定',
			cancelButtonText: '取消',
			type: 'warning'
		})
			.then(async () => {
				msgbox.loading('正在生成链接...')
				const { data, code } = await Api.tenant.login.get(row.id)
				if (data && code === 200) {
					copyToClipboard(data.url)
					ElMessageBox.alert(`登录链接已复制到剪切板，请在 ${data.expire_at} 前使用`, '提示')
				}
				msgbox.close()
			})
			.catch(() => {})
	}

	const refresh = () => {
		tableRef.value.refresh()
	}

	/**
	 * 下拉菜单
	 * @param row
	 */
	const dropdown = (row) => ({
		dropdown: () => (
			<el-dropdown-menu>
				<el-dropdown-item icon="el-icon-position" onClick={() => handleLogin(row)}>
					一键登录
				</el-dropdown-item>
				<el-dropdown-item icon="el-icon-remove" onClick={() => handleRemove(row)}>
					删除机构
				</el-dropdown-item>
			</el-dropdown-menu>
		)
	})
</script>
