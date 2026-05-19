<template>
	<div class="cy-msgbox-wrapper" v-show="visible">
		<div class="cy-msgbox">
			<div class="cy-msgbox-spinner">
				<div class="rect1"></div>
				<div class="rect2"></div>
				<div class="rect3"></div>
				<div class="rect4"></div>
				<div class="rect5"></div>
			</div>
			<div class="cy-msgbox-text">{{ content }}</div>
		</div>
	</div>
</template>

<script setup>
	import { ref, onMounted } from 'vue'

	defineOptions({
		name: 'Msgbox'
	})

	const props = defineProps({
		content: {
			type: String
		},
		duration: {
			type: Number,
			default: 3000
		}
	})

	const timer = ref(null)
	const visible = ref(true)

	const startTimer = () => {
		timer.value = setTimeout(() => {
			if (visible.value) {
				hide()
			}
		}, props.duration)
	}

	const clearTimer = () => {
		if (timer.value) {
			clearTimeout(timer.value)
			timer.value = null
		}
	}

	const hide = () => {
		clearTimer()
		visible.value = false
	}

	const show = () => {
		visible.value = true
		startTimer()
	}

	onMounted(() => {
		startTimer()
	})

	defineExpose({
		hide,
		show
	})
</script>

<style>
	.cy-msgbox-wrapper {
		z-index: 99999;
		width: 100%;
		height: 100%;
		top: 0;
		position: absolute;
	}

	.cy-msgbox {
		position: absolute;
		top: 50%;
		left: 50%;
		margin-right: -50%;
		transform: translate(-50%, -50%);
		z-index: 99999;
	}

	.cy-msgbox-spinner {
		width: 120px;
		height: 120px;
		text-align: center;
		font-size: 10px;
		border: 1px solid #409eff;
		padding: 18px 0 40px;
		background: rgba(255, 255, 255, 1);
		border-radius: 5px;
		box-shadow: 0 0 5px 2px rgba(0, 0, 0, 0.1);
	}

	.cy-msgbox-spinner > div {
		background-color: #409eff;
		height: 100%;
		width: 6px;
		margin: 2px;
		display: inline-block;
		animation: sk-stretchdelay 1.2s infinite ease-in-out;
	}

	.cy-msgbox-spinner .rect2 {
		animation-delay: -1.1s;
	}

	.cy-msgbox-spinner .rect3 {
		animation-delay: -1s;
	}

	.cy-msgbox-spinner .rect4 {
		animation-delay: -0.9s;
	}

	.cy-msgbox-spinner .rect5 {
		animation-delay: -0.8s;
	}

	@keyframes sk-stretchdelay {
		0%,
		40%,
		100% {
			transform: scaleY(0.4);
		}

		20% {
			transform: scaleY(1);
			-webkit-transform: scaleY(1);
		}
	}

	.cy-msgbox-text {
		text-align: center;
		position: relative;
		top: -32px;
		font-size: 14px;
		color: #409eff;
	}
</style>
