<template>
	<el-drawer
		v-bind="attrs"
		v-model="drawer"
		:size="width"
		:show-close="false"
		:with-header="false"
		:append-to-body="true"
		:destroy-on-close="true"
		class="drawer-sidebar-close"
	>
		<el-container>
			<el-header class="header-reset drawer-title" v-if="title">
				<el-space :size="5">
					<el-icon v-if="icon">
						<component :is="icon" />
					</el-icon>
					<span>{{ title }}</span>
				</el-space>
				<el-icon
					color="#fff"
					:size="20"
					title="关闭"
					class="drawer-right-close"
					@click="drawer = false"
				>
					<el-icon-close />
				</el-icon>
			</el-header>
			<el-main class="nopadding">
				<slot></slot>
			</el-main>
		</el-container>
		<!-- 左侧关闭按钮 -->
		<el-button
			type="primary"
			size="large"
			color="#ff6a00"
			class="btn-close"
			title="关闭"
			@click="drawer = false"
		>
			<template #icon>
				<el-icon color="#fff" :size="26">
					<el-icon-close></el-icon-close>
				</el-icon>
			</template>
		</el-button>
	</el-drawer>
</template>

<script setup>
	import { ref, watch, useAttrs } from 'vue'

	defineOptions({
		name: 'CyDrawer'
	})

	const props = defineProps({
		modelValue: {
			type: Boolean,
			default: false
		},
		title: {
			type: String,
			default: ''
		},
		closeable: {
			type: Boolean,
			default: true
		},
		width: {
			type: [String, Number],
			default: '30%'
		},
		icon: {
			type: String,
			default: ''
		}
	})

	const drawer = ref(props.modelValue)
	const attrs = useAttrs()

	watch(
		() => props.modelValue,
		(val) => {
			drawer.value = val
		}
	)
</script>
