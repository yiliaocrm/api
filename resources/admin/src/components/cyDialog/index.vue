<template>
	<el-dialog
		class="cy-dialog"
		v-model="visible"
		v-bind="attrs"
		:draggable="draggable"
		:closeOnClickModal="closeOnClickModal"
		:show-close="false"
		:style="{ '--dialog-body-padding': padding }"
	>
		<template #header="{ close, titleId, titleClass }">
			<el-space :size="5" :id="titleId" :class="titleClass">
				<el-icon v-if="icon">
					<component :is="icon" />
				</el-icon>
				<el-text>{{ title }}</el-text>
			</el-space>
			<div class="cy-dialog-header-right">
				<el-space>
					<el-icon :size="16" color="#b1b3b8" title="关闭" @click="close">
						<el-icon-close-bold />
					</el-icon>
				</el-space>
			</div>
		</template>
		<slot></slot>
		<template #footer v-if="slots.footer">
			<slot name="footer"></slot>
		</template>
	</el-dialog>
</template>

<script setup>
	import { ref, watch, useAttrs, useSlots } from 'vue'

	defineOptions({
		name: 'CyDialog'
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
		showClose: {
			type: Boolean,
			default: true
		},
		draggable: {
			type: Boolean,
			default: true
		},
		closeOnClickModal: {
			type: Boolean,
			default: false
		},
		padding: {
			type: String,
			default: '15px'
		},
		icon: {
			type: String,
			default: ''
		}
	})

	const visible = ref(props.modelValue)
	const attrs = useAttrs()
	const slots = useSlots()

	watch(
		() => props.modelValue,
		(val) => {
			visible.value = val
		}
	)
</script>

<style lang="scss">
	.cy-dialog {
		padding: 0;
		border-radius: var(--el-border-radius-base);

		.el-dialog__header {
			padding: 8px 16px;
			margin-right: 0;
			border-top-left-radius: var(--el-border-radius-base);
			border-top-right-radius: var(--el-border-radius-base);
			border-bottom: 1px solid #ebeef5;
			background-color: #f8f8f8;
			display: flex;
			justify-content: space-between;
		}

		.el-dialog__title {
			color: #606266;
			font-size: 14px;
			font-weight: 700;
		}

		.cy-dialog-header-right {
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: -5px;

			i {
				cursor: pointer;
			}
		}

		.el-dialog__body {
			padding: var(--dialog-body-padding);
			padding-bottom: 0;
		}

		.el-dialog__footer {
			padding: 15px;
			border-top: 1px solid #ebeef5;
		}
	}
</style>
