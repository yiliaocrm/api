<template>
	<cy-dialog v-model="visible" :title="title" :width="900" :close-on-click-modal="false">
		<el-form
			ref="formRef"
			class="mr15"
			:model="form"
			:rules="rules"
			label-width="80px"
			label-position="right"
		>
			<el-row :gutter="30">
				<el-col :span="24">
					<el-form-item label="菜单类型" prop="meta.type">
						<el-radio-group v-model="form.meta.type">
							<el-radio-button value="menu">菜单</el-radio-button>
							<el-radio-button value="iframe">iframe</el-radio-button>
							<el-radio-button value="link">外链</el-radio-button>
							<el-radio-button value="button">按钮</el-radio-button>
						</el-radio-group>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="菜单名称" prop="meta.title">
						<el-input v-model="form.meta.title" clearable placeholder="菜单显示名字"></el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="上级菜单" prop="parentid">
						<el-tree-select
							v-model="form.parentid"
							:data="tree"
							filterable
							clearable
							check-strictly
							style="width: 100%"
						>
						</el-tree-select>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="访问路径" prop="path">
						<el-input v-model="form.path" clearable placeholder=""></el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="组件名称" prop="name">
						<el-input
							v-model="form.name"
							clearable
							placeholder="和组件名一致,如Iframe的菜单,用地址"
						>
							<template #prepend>setup name=</template>
						</el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="权限标识" prop="permission">
						<el-input v-model="form.permission" clearable placeholder="请输入权限标识"></el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="组件路径" prop="component">
						<el-input v-model="form.component" clearable placeholder="">
							<template #prepend>views/</template>
						</el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="标签" prop="tag">
						<el-input
							v-model="form.meta.tag"
							clearable
							placeholder="只支持badge组件 {value:1,is-dot type:'danger'}"
						>
						</el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="菜单高亮" prop="active">
						<el-input
							v-model="form.active"
							clearable
							placeholder="子节点或详情页需要高亮的上级菜单路由地址"
						>
						</el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="重定向" prop="redirect">
						<el-input v-model="form.redirect" clearable placeholder=""></el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="菜单备注" prop="remark">
						<el-input v-model="form.remark" clearable></el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="菜单排序" prop="order">
						<el-input-number
							v-model="form.order"
							clearable
							controls-position="right"
							:min="0"
							style="width: 100%"
						>
						</el-input-number>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="是否隐藏" prop="meta.hidden">
						<el-checkbox v-model="form.meta.hidden">隐藏菜单</el-checkbox>
						<el-checkbox v-model="form.meta.hiddenBreadcrumb">隐藏面包屑</el-checkbox>
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="整页路由" prop="fullpage">
						<el-switch v-model="form.meta.fullpage" />
					</el-form-item>
				</el-col>
				<el-col :span="12">
					<el-form-item label="菜单图标" prop="meta.icon">
						<sc-icon-select v-model="form.meta.icon" clearable></sc-icon-select>
					</el-form-item>
				</el-col>
			</el-row>
		</el-form>
		<template #footer>
			<el-button type="primary" @click="handleSave">保存</el-button>
			<el-button @click="visible = false">关闭</el-button>
		</template>
	</cy-dialog>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref } from 'vue'
	import { useMsgbox } from '@/utils/helper.js'

	defineOptions({
		name: 'AdminMenuForm'
	})

	const props = defineProps({
		type: {
			type: String,
			default: ''
		}
	})

	const emits = defineEmits(['create', 'update'])

	// 初始化form数据,重置表单的时候使用
	const initForm = {
		id: '',
		parentid: '',
		name: '',
		path: '',
		component: '',
		redirect: '',
		meta: {
			title: '',
			icon: '',
			active: '',
			color: '',
			type: 'menu',
			fullpage: false,
			tag: ''
		},
		type: props.type,
		order: 0,
		remark: '',
		permission: ''
	}

	const form = ref({ ...initForm })
	const rules = ref({
		name: [{ required: true, message: '组件名称不能为空', trigger: 'blur' }],
		'meta.title': [{ required: true, message: '菜单名称不能为空', trigger: 'blur' }]
	})
	const tree = ref([])
	const title = ref(null)
	const loading = ref(false)
	const visible = ref(false)
	const formRef = ref(null)
	const msgbox = useMsgbox()

	const add = async (parentid) => {
		await loadData()
		title.value = '添加菜单'
		visible.value = true
		resetFields()
		form.value = {
			...JSON.parse(JSON.stringify(initForm)),
			type: props.type,
			parentid: parentid || null
		}
	}

	const edit = async (row) => {
		await loadData()
		title.value = '编辑菜单'
		visible.value = true
		resetFields()
		form.value = {
			...row,
			parentid: row.parentid || null
		}
	}

	const loadData = async () => {
		msgbox.loading()
		tree.value = await Api.admin_menu.tree
			.get({ type: props.type })
			.then((response) => transformData(response.data))
		msgbox.close()
	}

	/**
	 * 转换tree数据
	 * @param {Array} originalData
	 * @returns {Array}
	 */
	const transformData = (originalData) => {
		return originalData.map((item) => ({
			...item,
			label: item.title,
			value: item.id,
			children: item.children ? transformData(item.children) : []
		}))
	}

	// 处理保存按钮点击事件
	const handleSave = () => {
		formRef.value.validate(async (valid) => {
			if (valid && !loading.value) {
				loading.value = true
				msgbox.loading()
				form.value.id ? await update() : await create()
				loading.value = false
				msgbox.close()
			}
		})
	}

	const create = async () => {
		msgbox.loading()
		let { data, code } = await Api.admin_menu.create.post(form.value)
		if (data && code === 200) {
			visible.value = false
			emits('create', data)
		}
		msgbox.close()
	}

	// 更新菜单的方法
	const update = async () => {
		msgbox.loading()
		let { data, code } = await Api.admin_menu.update.post(form.value)
		if (data && code === 200) {
			visible.value = false
			emits('update', data)
		}
		msgbox.close()
	}

	// 重置表单字段的方法
	const resetFields = () => {
		if (formRef.value) {
			formRef.value.resetFields()
		}
		form.value = JSON.parse(JSON.stringify(initForm))
	}

	defineExpose({
		add,
		edit
	})
</script>
