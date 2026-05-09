<template>
	<span v-if="dot"> 1 </span>
	<!-- 状态点 -->
	<span class="cy-badge cy-badge-status" v-else-if="status || color">
		<span :class="statusClasses" :style="statusStyles"></span>
		<span class="cy-badge-status-text">
			<slot name="text">{{ text }}</slot>
		</span>
	</span>
	<span v-else class="cy-badge">
		<slot></slot>
		<sup v-if="$slots.count" :style="styles" :class="customCountClasses">
			<slot name="count"></slot>
		</sup>
		<sup v-else-if="hasCount" :style="styles" :class="countClasses" v-show="badge">
			<slot name="text">{{ finalCount }}</slot>
		</sup>
	</span>
</template>

<script setup>
	import { ref, computed, useSlots } from 'vue'

	defineOptions({
		name: 'Badge'
	})

	const props = defineProps({
		/**
		 * 展示的数字，大于 maxCount 时显示为 ${maxCount}+，为 0 时隐藏
		 */
		count: {
			type: [Number, String]
		},
		/**
		 * 展示封顶的数字值
		 */
		overflowCount: {
			type: [Number, String],
			default: 99
		},
		/**
		 * 展示封顶的数字值
		 */
		maxCount: {
			type: [Number, String],
			default: 99
		},
		/**
		 * 不展示数字，只有一个小红点，如需隐藏 dot ，需要设置count为 0
		 */
		dot: {
			type: Boolean,
			default: false
		},
		/**
		 * 自定义小圆点的颜色
		 */
		color: {
			type: String
		},
		/**
		 * 设置 Badge 为状态点，可选值为 success、processing、default、error、warning
		 */
		status: {
			type: String,
			validator(value) {
				return ['success', 'processing', 'default', 'error', 'warning'].indexOf(value) !== -1
			}
		},
		/**
		 * 自定义提示内容
		 */
		text: {
			type: [String, Number],
			default: ''
		},
		/**
		 * 设置状态点的位置偏移，格式为 [x, y]
		 */
		offset: {
			type: Array
		},
		className: String,
		showZero: {
			type: Boolean,
			default: false
		},
		/**
		 * 使用预设的颜色，可选值为 success、primary、normal、error、warning、info
		 */
		type: {
			validator(value) {
				return ['success', 'primary', 'normal', 'error', 'warning', 'info'].includes(value)
			}
		}
	})

	const colors = ref([
		'blue',
		'green',
		'red',
		'yellow',
		'pink',
		'magenta',
		'volcano',
		'orange',
		'gold',
		'lime',
		'cyan',
		'geekblue',
		'purple'
	])

	const styles = computed(() => {
		let style = {}
		if (props.offset && props.offset.length === 2) {
			style['margin-top'] = `${props.offset[0]}px`
			style['margin-right'] = `${props.offset[1]}px`
		}
		return style
	})

	const statusClasses = computed(() => {
		return [
			'cy-badge-status-dot',
			{
				[`cy-badge-status-${props.status}`]: !!props.status,
				[`cy-badge-status-${props.color}`]:
					!!props.color && colors.value.indexOf(props.color) !== -1
			}
		]
	})

	const statusStyles = computed(() => {
		return colors.value.indexOf(props.color) !== -1 ? {} : { backgroundColor: props.color }
	})

	const customCountClasses = computed(() => {
		return [
			'cy-badge-count',
			'cy-badge-count-custom',
			{
				[`${props.className}`]: !!props.className
			}
		]
	})

	const hasCount = computed(() => {
		if (props.count || props.text !== '') return true
		if (props.showZero && parseInt(props.count) === 0) return true
		else return false
	})

	const countClasses = computed(() => {
		return [
			'cy-badge-count',
			{
				[`${props.className}`]: !!props.className,
				['cy-badge-count-alone']: !useSlots().default,
				[`cy-badge-count-${props.type}`]: !!props.type
			}
		]
	})

	const finalCount = computed(() => {
		if (props.text !== '') return props.text
		return parseInt(props.count) >= parseInt(props.overflowCount)
			? `${props.overflowCount}+`
			: props.count
	})

	const badge = computed(() => {
		let status = false

		if (props.count) {
			status = !(parseInt(props.count) === 0)
		}

		if (props.dot) {
			status = true
			if (props.count !== null) {
				if (parseInt(props.count) === 0) {
					status = false
				}
			}
		}

		if (props.text !== '') status = true

		return status || props.showZero
	})
</script>

<style>
	.cy-badge {
		position: relative;
		display: inline-block;
	}

	.cy-badge-count {
		font-family: 'Monospaced Number';
		line-height: 1;
		vertical-align: middle;
		position: absolute;
		transform: translateX(50%);
		top: -10px;
		right: 0;
		height: 20px;
		border-radius: 10px;
		min-width: 20px;
		background: #ed4014;
		border: 1px solid transparent;
		color: #fff;
		line-height: 18px;
		text-align: center;
		padding: 0 6px;
		font-size: 12px;
		white-space: nowrap;
		transform-origin: -10% center;
		z-index: 10;
		box-shadow: 0 0 0 1px #fff;
	}

	.cy-badge-count-alone {
		top: auto;
		display: block;
		position: relative;
		transform: translateX(0);
	}

	.cy-badge-count-primary {
		background: #2d8cf0;
	}

	.cy-badge-count-success {
		background: #19be6b;
	}

	.cy-badge-count-error {
		background: #ed4014;
	}

	.cy-badge-count-warning {
		background: #f90;
	}

	.cy-badge-count-info {
		background: #2db7f5;
	}

	.cy-badge-count-normal {
		background: #e6ebf1;
		color: #808695;
	}

	.cy-badge-status {
		line-height: inherit;
		vertical-align: baseline;
	}

	.cy-badge-status-dot {
		position: relative;
		top: -1px;
		display: inline-block;
		width: 6px;
		height: 6px;
		vertical-align: middle;
		border-radius: 50%;
	}

	.cy-badge-status-success {
		background-color: #52c41a;
	}

	.cy-badge-status-processing {
		position: relative;
		background-color: #1890ff;
	}

	.cy-badge-status-processing:after {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		border: 1px solid #1890ff;
		border-radius: 50%;
		animation: aniStatusProcessing 1.2s infinite ease-in-out;
		content: '';
		box-sizing: border-box;
	}

	.cy-badge-status-default {
		background-color: #d9d9d9;
	}

	.cy-badge-status-error {
		background-color: #ff4d4f;
	}

	.cy-badge-status-warning {
		background-color: #faad14;
	}

	.cy-badge-status-pink,
	.cy-badge-status-magenta {
		background: #eb2f96;
	}

	.cy-badge-status-red {
		background: #f5222d;
	}

	.cy-badge-status-volcano {
		background: #fa541c;
	}

	.cy-badge-status-orange {
		background: #fa8c16;
	}

	.cy-badge-status-yellow {
		background: #fadb14;
	}

	.cy-badge-status-gold {
		background: #faad14;
	}

	.cy-badge-status-cyan {
		background: #13c2c2;
	}

	.cy-badge-status-lime {
		background: #a0d911;
	}

	.cy-badge-status-green {
		background: #52c41a;
	}

	.cy-badge-status-blue {
		background: #1890ff;
	}

	.cy-badge-status-geekblue {
		background: #2f54eb;
	}

	.cy-badge-status-purple {
		background: #722ed1;
	}

	.cy-badge-status-text {
		margin-left: 5px;
		color: #000000d9;
		font-size: 14px;
	}

	@keyframes aniStatusProcessing {
		0% {
			transform: scale(0.8);
			opacity: 0.5;
		}

		to {
			transform: scale(2.4);
			opacity: 0;
		}
	}
</style>
