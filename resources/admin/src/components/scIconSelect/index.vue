<template>
	<div class="sc-icon-select">
		<div class="sc-icon-select__wrapper" :class="{ hasValue: value }" @click="open">
			<el-input
				:prefix-icon="value || 'el-icon-plus'"
				v-model="value"
				:disabled="disabled"
				readonly
			></el-input>
		</div>
		<cy-dialog
			title="图标选择器"
			v-model="dialogVisible"
			:width="760"
			destroy-on-close
			append-to-body
		>
			<div>
				<el-form :rules="{}">
					<el-form-item prop="searchText">
						<el-input
							prefix-icon="el-icon-search"
							v-model="searchText"
							placeholder="搜索"
							size="large"
							clearable
						/>
					</el-form-item>
				</el-form>
				<el-tabs>
					<el-tab-pane v-for="item in data" :key="item.name" lazy>
						<template #label>
							{{ item.name }}
							<el-tag size="small" type="info">{{ item.icons.length }}</el-tag>
						</template>
						<div class="sc-icon-select__list">
							<el-scrollbar>
								<ul @click="selectIcon">
									<el-empty
										v-if="item.icons.length == 0"
										:image-size="100"
										description="未查询到相关图标"
									/>
									<li v-for="icon in item.icons" :key="icon">
										<span :data-icon="icon"></span>
										<el-icon><component :is="icon" /></el-icon>
									</li>
								</ul>
							</el-scrollbar>
						</div>
					</el-tab-pane>
				</el-tabs>
			</div>
			<template #footer>
				<el-button @click="clear" text>清除</el-button>
				<el-button @click="dialogVisible = false">取消</el-button>
			</template>
		</cy-dialog>
	</div>
</template>

<script setup>
	import config from '@/config/iconSelect.js'
	import { ref, watch, onMounted } from 'vue'

	defineOptions({ name: 'ScIconSelect' })

	const props = defineProps({
		modelValue: {
			type: String,
			default: ''
		},
		disabled: {
			type: Boolean,
			default: false
		}
	})

	const emit = defineEmits(['update:modelValue'])

	const value = ref('')
	const dialogVisible = ref(false)
	const data = ref([])
	const searchText = ref('')

	watch(
		() => props.modelValue,
		(val) => {
			value.value = val
		}
	)

	watch(value, (val) => {
		emit('update:modelValue', val)
	})

	watch(searchText, (val) => {
		handleSearch(val)
	})

	onMounted(() => {
		value.value = props.modelValue
		data.value.push(...config.icons)
	})

	const open = () => {
		if (props.disabled) {
			return false
		}
		dialogVisible.value = true
	}

	const selectIcon = (e) => {
		if (e.target.tagName != 'SPAN') {
			return false
		}
		value.value = e.target.dataset.icon
		dialogVisible.value = false
	}

	const clear = () => {
		value.value = ''
		dialogVisible.value = false
	}

	const handleSearch = (text) => {
		if (text) {
			const filterData = JSON.parse(JSON.stringify(config.icons))
			filterData.forEach((t) => {
				t.icons = t.icons.filter((n) => n.includes(text))
			})
			data.value = filterData
		} else {
			data.value = JSON.parse(JSON.stringify(config.icons))
		}
	}
</script>

<style scoped>
	.sc-icon-select {
		display: inline-flex;
	}
	.sc-icon-select__wrapper {
		cursor: pointer;
		display: inline-flex;
	}
	.sc-icon-select__wrapper:deep(.el-input__wrapper).is-focus {
		box-shadow: 0 0 0 1px var(--el-input-hover-border-color) inset;
	}
	.sc-icon-select__wrapper:deep(.el-input__inner) {
		flex-grow: 0;
		width: 0;
	}
	.sc-icon-select__wrapper:deep(.el-input__icon) {
		margin: 0;
		font-size: 16px;
	}
	.sc-icon-select__wrapper.hasValue:deep(.el-input__icon) {
		color: var(--el-text-color-regular);
	}

	.sc-icon-select__list {
		height: 270px;
		overflow: auto;
	}
	.sc-icon-select__list li {
		display: inline-block;
		width: 80px;
		height: 80px;
		margin: 5px;
		vertical-align: top;
		transition: all 0.1s;
		border-radius: 4px;
		position: relative;
	}
	.sc-icon-select__list li span {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 1;
		cursor: pointer;
	}
	.sc-icon-select__list li i {
		display: inline-block;
		width: 100%;
		height: 100%;
		font-size: 26px;
		color: #6d7882;
		display: flex;
		justify-content: center;
		align-items: center;
		border-radius: 4px;
	}
	.sc-icon-select__list li:hover {
		box-shadow: 0 0 1px 4px var(--el-color-primary);
		background: var(--el-color-primary-light-9);
	}
	.sc-icon-select__list li:hover i {
		color: var(--el-color-primary);
	}
</style>
