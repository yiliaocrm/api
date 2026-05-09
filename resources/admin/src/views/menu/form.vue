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
							<el-radio-button value="directory">目录</el-radio-button>
							<el-radio-button value="menu">菜单</el-radio-button>
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
				<el-col :span="12" v-if="isFieldVisible(['menu', 'directory'])">
					<el-form-item label="访问路径" prop="path">
						<el-input v-model="form.path" clearable placeholder=""></el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12" v-if="isFieldVisible(['menu'])">
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
				<el-col :span="12" v-if="isFieldVisible(['menu', 'button'])">
					<el-form-item label="权限标识" prop="permission">
						<el-input v-model="form.permission" clearable placeholder="请输入权限标识"></el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12" v-if="isFieldVisible(['menu'])">
					<el-form-item label="组件路径" prop="component">
						<el-input v-model="form.component" clearable placeholder="">
							<template #prepend>views/</template>
						</el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12" v-if="isFieldVisible(['menu'])">
					<el-form-item label="标签" prop="tag">
						<el-input
							v-model="form.meta.tag"
							clearable
							placeholder="只支持badge组件 {value:1,is-dot type:'danger'}"
						>
						</el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12" v-if="isFieldVisible(['menu'])">
					<el-form-item label="菜单高亮" prop="active">
						<el-input
							v-model="form.active"
							clearable
							placeholder="子节点或详情页需要高亮的上级菜单路由地址"
						>
						</el-input>
					</el-form-item>
				</el-col>
				<el-col :span="12" v-if="isFieldVisible(['menu'])">
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
					<el-form-item label="权限范围" prop="permission_scope">
						<el-select
							v-model="form.permission_scope"
							multiple
							clearable
							collapse-tags
							:max-collapse-tags="2"
							placeholder="请选择权限范围"
						>
							<el-option
								v-for="scope in scopes"
								:key="scope.id"
								:label="scope.name"
								:value="scope.slug"
							>
							</el-option>
						</el-select>
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
				<el-col :span="12" v-if="isFieldVisible(['menu'])">
					<el-form-item label="是否隐藏" prop="meta.hidden">
						<el-checkbox v-model="form.meta.hidden">隐藏菜单</el-checkbox>
						<el-checkbox v-model="form.meta.hiddenBreadcrumb">隐藏面包屑</el-checkbox>
					</el-form-item>
				</el-col>
				<el-col :span="12" v-if="isFieldVisible(['menu'])">
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
			<el-button @click="closeDialog">关闭</el-button>
		</template>
	</cy-dialog>
</template>

<script setup>
	import Api from '@/api/index.js'
	import { ref, watch } from 'vue'
	import { useMsgbox } from '@/utils/helper.js'

	// 定义组件名称
	defineOptions({
		name: 'MenuForm'
	})

	// 定义事件
	const emits = defineEmits(['create', 'update'])
	const scopes = ref([])

	// 初始化表单数据
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
			tag: '',
			hidden: false,
			hiddenBreadcrumb: false
		},
		type: 'web',
		order: 0,
		remark: '',
		page_id: null,
		permission: '',
		permission_scope: []
	}

	// 使用 ref 定义各个状态
	const form = ref({ ...initForm })
	const rules = {
		'meta.title': [{ required: true, message: '菜单名称不能为空', trigger: 'blur' }]
	}
	const tree = ref([])
	const title = ref(null)
	const loading = ref(false)
	const visible = ref(false)
	const msgbox = useMsgbox()
	const formRef = ref(null)

	/**
	 * 判断字段是否可见
	 * @param allowedTypes
	 */
	const isFieldVisible = (allowedTypes) => {
		return allowedTypes.includes(form.value.meta.type)
	}

	/**
	 * 清空不可见字段的数据
	 * @param menuType 当前菜单类型
	 */
	const clearInvisibleFields = (menuType) => {
		// 根据菜单类型清空对应的不可见字段
		switch (menuType) {
			case 'directory':
				// 目录类型：清空菜单和按钮特有的字段
				form.value.name = ''
				form.value.permission = ''
				form.value.component = ''
				form.value.meta.tag = ''
				form.value.active = ''
				form.value.redirect = ''
				form.value.meta.fullpage = false
				form.value.meta.hidden = false
				form.value.meta.hiddenBreadcrumb = false
				break
			case 'menu':
				// 菜单类型：清空按钮特有的字段，保留菜单特有字段
				// 按钮特有字段在菜单类型下也可见，所以不清空 permission
				break
			case 'button':
				// 按钮类型：清空目录和菜单特有的字段
				form.value.path = ''
				form.value.name = ''
				form.value.component = ''
				form.value.meta.tag = ''
				form.value.active = ''
				form.value.redirect = ''
				form.value.meta.fullpage = false
				form.value.meta.hidden = false
				form.value.meta.hiddenBreadcrumb = false
				break
		}
	}

	// 监听菜单类型变化，清空不可见字段数据
	watch(
		() => form.value.meta.type,
		(newType, oldType) => {
			if (newType !== oldType && oldType !== undefined) {
				clearInvisibleFields(newType)
			}
		}
	)

	// 定义添加菜单的方法
	const add = async (parentid, type) => {
		resetFields()
		form.value.type = type
		await loadData()
		form.value = {
			...JSON.parse(JSON.stringify(initForm)),
			parentid: parentid || null,
			type: type
		}
		title.value = '添加菜单'
		visible.value = true
	}

	// 定义编辑菜单的方法
	const edit = async (id, type) => {
		resetFields()
		form.value.type = type
		await loadData(id)
		title.value = '编辑菜单'
		visible.value = true
	}

	// 加载数据的方法
	const loadData = async (id) => {
		msgbox.loading()
		const requests = [Api.menu.tree.get({ type: form.value.type }), Api.menu.scope.get()]
		if (id) {
			requests.push(Api.menu.info.get(id))
		}
		const [treeRes, scopesRes, infoRes] = await Promise.all(requests)
		if (treeRes.data && treeRes.code === 200) {
			tree.value = transformData(treeRes.data)
			scopes.value = scopesRes.data
		}
		if (infoRes && infoRes.data && infoRes.code === 200) {
			form.value = {
				...infoRes.data,
				meta: Object.assign(
					{ hidden: false, hiddenBreadcrumb: false, fullpage: false, type: 'menu' },
					infoRes.data.meta || {}
				),
				parentid: infoRes.data.parentid || null,
				permission_scope: infoRes.data.permission_scope || []
			}
		}
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

	// 创建菜单的方法
	const create = async () => {
		msgbox.loading()
		let { data, code } = await Api.menu.create.post(form.value)
		if (data && code === 200) {
			visible.value = false
			emits('create', data)
		}
		msgbox.close()
	}

	// 更新菜单的方法
	const update = async () => {
		msgbox.loading()
		let { data, code } = await Api.menu.update.post(form.value)
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

	// 关闭对话框的方法
	const closeDialog = () => {
		visible.value = false
	}

	// 向外暴露 add 和 edit 方法
	defineExpose({
		add,
		edit
	})
</script>
