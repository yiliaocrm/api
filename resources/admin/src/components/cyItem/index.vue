<template>
	<el-cascader
		v-model="value"
		:options="options"
		filterable
		v-bind="attrs"
		@change="handleChange"
	></el-cascader>
</template>

<script>
	import Api from '@/api/index.js'
	import Tool from '@/utils/tool.js'
	import { defineComponent, reactive, toRefs, useAttrs, onMounted } from 'vue'

	export default defineComponent({
		name: 'CyItem',
		emits: ['update:modelValue'],
		setup(props, context) {
			const state = reactive({
				value: null,
				options: []
			})

			const attrs = useAttrs()

			onMounted(() => {
				loadData()
			})

			const loadData = async () => {
				let { data, code } = await Api.cache.items.get()
				if (code === 200) {
					state.options = Tool.toTreeData(data)
				}
			}

			const handleChange = (value) => {
				value = value[value.length - 1]
				context.emit('update:modelValue', value)
			}

			return {
				...toRefs(state),
				attrs,
				handleChange
			}
		}
	})
</script>
