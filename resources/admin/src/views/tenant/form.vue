<template>
	<cy-dialog v-model="dialog" :title="title" :width="500" append-to-body>
		<el-form ref="formRef" class="mt5 mr10" :model="form" :rules="rules" label-width="80px">
			<el-form-item label="机构名称" prop="name">
				<el-input v-model="form.name" clearable placeholder="请输入机构名称"></el-input>
			</el-form-item>
			<el-form-item label="机构代码" prop="id">
				<el-input v-model="form.id" clearable placeholder="请输入机构代码"></el-input>
			</el-form-item>
			<el-form-item label="过期时间" prop="expire_date">
				<el-date-picker
					v-model="form.expire_date"
					:shortcuts="shortcuts"
					value-format="YYYY-MM-DD"
					type="date"
					placeholder="选择过期时间"
				>
				</el-date-picker>
			</el-form-item>
			<el-form-item
				label="绑定域名"
				:prop="`domains.${index}`"
				:key="index"
				:rules="domainRule"
				v-for="(domain, index) in form.domains"
			>
				<div style="display: flex">
					<el-input
						v-model="form.domains[index]"
						clearable
						placeholder="请输入绑定域名"
						style="width: 220px"
					></el-input>
					<el-button type="primary" @click.prevent="addDomain" style="margin-left: 15px"
						>新增</el-button
					>
					<el-button @click.prevent="removeDomain(index)">删除</el-button>
				</div>
			</el-form-item>
			<el-form-item label="备注信息" prop="remark">
				<el-input
					v-model="form.remark"
					:rows="5"
					type="textarea"
					placeholder="请输入备注信息"
				></el-input>
			</el-form-item>
		</el-form>
		<template #footer>
			<el-button type="primary" @click="handleSubmit" :loading="loading">确定</el-button>
			<el-button @click="handleCancel">取消</el-button>
		</template>
	</cy-dialog>
</template>

<script>
	import Api from '@/api/index.js'
	import { alertMessager } from '@/utils/helper.js'
	import { reactive, toRefs, ref, defineComponent } from 'vue'

	export default defineComponent({
		name: 'TenantForm',
		emits: ['success'],
		setup(_, context) {
			const state = reactive({
				form: {
					id: null,
					original_id: null,
					name: null,
					domains: [''],
					expire_date: null,
					remark: null
				},
				rules: {
					id: [{ required: true, message: '请输入机构代码', trigger: 'blur' }],
					name: [{ required: true, message: '请输入机构名称', trigger: 'blur' }],
					expire_date: [{ required: true, message: '请选择到期时间', trigger: 'blur' }]
				},
				domainRule: {
					required: true,
					trigger: 'blur',
					validator: (rule, value, callback) => {
						if (!/^((?!-)[A-Za-z0-9-]{1,63}(?<!-)\.)+[A-Za-z]{2,6}$/.test(value)) {
							callback(new Error('请输入正确的域名'))
						} else {
							callback()
						}
					}
				},
				title: null,
				dialog: false,
				loading: false,
				shortcuts: [
					{
						text: '1年期',
						value: () => {
							const date = new Date()
							date.setFullYear(date.getFullYear() + 1)
							return date
						}
					},
					{
						text: '2年期',
						value: () => {
							const date = new Date()
							date.setFullYear(date.getFullYear() + 2)
							return date
						}
					},
					{
						text: '3年期',
						value: () => {
							const date = new Date()
							date.setFullYear(date.getFullYear() + 3)
							return date
						}
					},
					{
						text: '4年期',
						value: () => {
							const date = new Date()
							date.setFullYear(date.getFullYear() + 4)
							return date
						}
					},
					{
						text: '5年期',
						value: () => {
							const date = new Date()
							date.setFullYear(date.getFullYear() + 5)
							return date
						}
					}
				]
			})

			const formRef = ref(null)

			const add = () => {
				state.title = '新增租户'
				state.dialog = true
				state.form = {
					id: null,
					original_id: null,
					name: null,
					domains: [''],
					expire_date: null,
					remark: null
				}
			}

			const edit = (row) => {
				state.title = `编辑[${row.name}]租户`
				state.dialog = true

				// 遍历row.domains，将其转换为数组
				let domains = []
				row.domains.forEach((v) => {
					domains.push(v.domain)
				})

				state.form = {
					id: row.id,
					original_id: row.id,
					name: row.name,
					domains: domains,
					expire_date: row.expire_date,
					remark: row.remark
				}
			}

			const handleCancel = () => {
				state.dialog = false
			}

			const handleSubmit = () => {
				formRef.value.validate((valid) => {
					if (valid) {
						state.form.original_id ? updateRequest() : createRequest()
					}
				})
			}

			const createRequest = async () => {
				state.loading = true
				let { data, code } = await Api.tenant.create.post(state.form)
				if (data && code === 200) {
					state.dialog = false
					context.emit('success', data)
				}
				state.loading = false
			}

			const updateRequest = async () => {
				state.loading = true
				let { data, code } = await Api.tenant.update.post(state.form)
				if (data && code === 200) {
					state.dialog = false
					context.emit('success', data)
				}
				state.loading = false
			}

			const addDomain = () => {
				state.form.domains.push('')
			}

			const removeDomain = (index) => {
				if (state.form.domains.length === 1) {
					alertMessager('至少保留一个域名!', '提示', { type: 'warning' })
					return null
				}
				state.form.domains.splice(index, 1)
			}

			return {
				...toRefs(state),
				formRef,
				add,
				edit,
				handleCancel,
				handleSubmit,
				addDomain,
				removeDomain
			}
		}
	})
</script>
